using System;
using System.IO;
using Microsoft.AspNetCore.Builder;
using Microsoft.AspNetCore.Hosting;
using Microsoft.Extensions.DependencyInjection;
using MySql.Data.MySqlClient;
using System.Text.Json;
using System.Text.Json.Serialization;
using UFV_MYSQL;

public class LoginRequest
{
    [JsonPropertyName("email")]
    public required string Email { get; set; }
    
    [JsonPropertyName("password")]
    public required string Password { get; set; }
}

public class RegisterRequest
{
    [JsonPropertyName("nombre")]
    public required string Nombre { get; set; }
    
    [JsonPropertyName("apellido")]
    public required string Apellido { get; set; }
    
    [JsonPropertyName("email")]
    public required string Email { get; set; }
    
    [JsonPropertyName("telefono")]
    public string? Telefono { get; set; }
    
    [JsonPropertyName("password")]
    public required string Password { get; set; }
    
    [JsonPropertyName("confirmPassword")]
    public required string ConfirmPassword { get; set; }
}

public class PrendaRequest
{
    [JsonPropertyName("nombre")]
    public required string Nombre { get; set; }
    
    [JsonPropertyName("tipo")]
    public required string Tipo { get; set; }
    
    [JsonPropertyName("estilo")]
    public required string Estilo { get; set; }
    
    [JsonPropertyName("tipo_corte")]
    public required string TipoCorte { get; set; }
    
    [JsonPropertyName("estacion")]
    public required string Estacion { get; set; }
    
    [JsonPropertyName("imagen")]
    public string? ImagenBase64 { get; set; }
}

class Program
{
    static void Main(string[] args)
    {
        var host = new WebHostBuilder()
            .UseKestrel()
            .UseUrls("http://localhost:5000")
            .ConfigureServices(services => {
                services.AddCors();
            })
            .Configure(app => {
                app.UseCors(builder => builder
                    .AllowAnyOrigin()
                    .AllowAnyMethod()
                    .AllowAnyHeader());
                
                app.Map("/api/login", HandleLogin);
                app.Map("/api/register", HandleRegister);
                app.Map("/api/checkdb", HandleCheckDb);
                app.Map("/api/prenda", HandlePrenda); // Nueva ruta para manejar prendas
            })
            .Build();

        host.Run();
    }

    static void HandleLogin(IApplicationBuilder app)
    {
        app.Run(async context => {
            if (context.Request.Method == "POST")
            {
                try
                {
                    var request = await JsonSerializer.DeserializeAsync<LoginRequest>(context.Request.Body);
                    
                    if (request == null)
                    {
                        context.Response.StatusCode = 400;
                        await context.Response.WriteAsync(JsonSerializer.Serialize(new {
                            success = false,
                            message = "Datos de solicitud inválidos"
                        }));
                        return;
                    }
                    
                    using (var connection = new MySqlConnection(Utils.CONNECTION_STRING))
                    {
                        await connection.OpenAsync();
                        
                        var command = new MySqlCommand(
                            "SELECT id, nombre, apellido, email, tipo FROM usuario WHERE email = @email AND contraseña = @password", 
                            connection);
                            
                        command.Parameters.AddWithValue("@email", request.Email);
                        command.Parameters.AddWithValue("@password", request.Password);
                        
                        using (var reader = await command.ExecuteReaderAsync())
                        {
                            if (await reader.ReadAsync())
                            {
                                var user = new {
                                    Id = reader.GetInt32(0),
                                    Nombre = reader.GetString(1),
                                    Apellido = reader.GetString(2),
                                    Email = reader.GetString(3),
                                    Tipo = reader.GetString(4)
                                };
                                
                                await context.Response.WriteAsync(JsonSerializer.Serialize(new {
                                    success = true,
                                    user = user
                                }));
                            }
                            else
                            {
                                context.Response.StatusCode = 401;
                                await context.Response.WriteAsync(JsonSerializer.Serialize(new {
                                    success = false,
                                    message = "Credenciales incorrectas"
                                }));
                            }
                        }
                    }
                }
                catch (Exception ex)
                {
                    context.Response.StatusCode = 500;
                    await context.Response.WriteAsync(JsonSerializer.Serialize(new {
                        success = false,
                        message = "Error en el servidor: " + ex.Message
                    }));
                }
            }
            else
            {
                context.Response.StatusCode = 405;
                await context.Response.WriteAsync("Método no permitido");
            }
        });
    }
    
    static void HandleRegister(IApplicationBuilder app)
    {
        app.Run(async context => {
            if (context.Request.Method == "POST")
            {
                try
                {
                    var request = await JsonSerializer.DeserializeAsync<RegisterRequest>(context.Request.Body);
                    
                    if (request == null)
                    {
                        context.Response.StatusCode = 400;
                        await context.Response.WriteAsync(JsonSerializer.Serialize(new {
                            success = false,
                            message = "Datos de solicitud inválidos"
                        }));
                        return;
                    }
                    
                    // Validar que las contraseñas coincidan
                    if (request.Password != request.ConfirmPassword)
                    {
                        context.Response.StatusCode = 400;
                        await context.Response.WriteAsync(JsonSerializer.Serialize(new {
                            success = false,
                            message = "Las contraseñas no coinciden"
                        }));
                        return;
                    }
                    
                    using (var connection = new MySqlConnection(Utils.CONNECTION_STRING))
                    {
                        await connection.OpenAsync();
                        
                        // Verificar si el email ya existe
                        var checkCommand = new MySqlCommand(
                            "SELECT COUNT(*) FROM usuario WHERE email = @email", 
                            connection);
                        checkCommand.Parameters.AddWithValue("@email", request.Email);
                        
                        var exists = (long)await checkCommand.ExecuteScalarAsync();
                        if (exists > 0)
                        {
                            context.Response.StatusCode = 400;
                            await context.Response.WriteAsync(JsonSerializer.Serialize(new {
                                success = false,
                                message = "El email ya está registrado"
                            }));
                            return;
                        }
                        
                        // Insertar nuevo usuario (todos los campos requeridos)
                        var insertCommand = new MySqlCommand(
                            "INSERT INTO usuario (nombre, apellido, email, telefono, contraseña, tipo) " +
                            "VALUES (@nombre, @apellido, @email, @telefono, @password, 'CLIENTE')", 
                            connection);
                            
                        insertCommand.Parameters.AddWithValue("@nombre", request.Nombre);
                        insertCommand.Parameters.AddWithValue("@apellido", request.Apellido);
                        insertCommand.Parameters.AddWithValue("@email", request.Email);
                        insertCommand.Parameters.AddWithValue("@telefono", request.Telefono ?? (object)DBNull.Value);
                        insertCommand.Parameters.AddWithValue("@password", request.Password);
                        
                        await insertCommand.ExecuteNonQueryAsync();
                        
                        // Obtener el ID del nuevo usuario
                        var getIdCommand = new MySqlCommand("SELECT LAST_INSERT_ID()", connection);
                        var newUserId = (ulong)await getIdCommand.ExecuteScalarAsync();
                        
                        // Crear registros relacionados en cliente, armario y calendario
                        var clienteCommand = new MySqlCommand(
                            "INSERT INTO cliente (id_cliente, genero, fecha_nacimiento) VALUES (@id, 'Masculino', @fechaNac)",
                            connection);
                        clienteCommand.Parameters.AddWithValue("@id", newUserId);
                        clienteCommand.Parameters.AddWithValue("@fechaNac", new DateTime(2000, 1, 1)); // Fecha por defecto
                        await clienteCommand.ExecuteNonQueryAsync();
                        
                        var armarioCommand = new MySqlCommand(
                            "INSERT INTO armario (id_cliente) VALUES (@id)",
                            connection);
                        armarioCommand.Parameters.AddWithValue("@id", newUserId);
                        await armarioCommand.ExecuteNonQueryAsync();
                        
                        var calendarioCommand = new MySqlCommand(
                            "INSERT INTO calendario (id_cliente) VALUES (@id)",
                            connection);
                        calendarioCommand.Parameters.AddWithValue("@id", newUserId);
                        await calendarioCommand.ExecuteNonQueryAsync();
                        
                        await context.Response.WriteAsync(JsonSerializer.Serialize(new {
                            success = true,
                            message = "Usuario registrado con éxito",
                            userId = newUserId
                        }));
                    }
                }
                catch (Exception ex)
                {
                    context.Response.StatusCode = 500;
                    await context.Response.WriteAsync(JsonSerializer.Serialize(new {
                        success = false,
                        message = "Error en el servidor: " + ex.Message
                    }));
                }
            }
            else
            {
                context.Response.StatusCode = 405;
                await context.Response.WriteAsync("Método no permitido");
            }
        });
    }

    static void HandleCheckDb(IApplicationBuilder app)
    {
        app.Run(async context => {
            try
            {
                using (var connection = new MySqlConnection(Utils.CONNECTION_STRING))
                {
                    await connection.OpenAsync();
                    var command = new MySqlCommand("SELECT COUNT(*) FROM usuario", connection);
                    var count = await command.ExecuteScalarAsync();
                    
                    await context.Response.WriteAsync($"Conexión exitosa. Usuarios registrados: {count}");
                }
            }
            catch (Exception ex)
            {
                context.Response.StatusCode = 500;
                await context.Response.WriteAsync($"Error de conexión: {ex.Message}");
            }
        });
    }
    
    // Nuevo método para manejar la subida de prendas
    static void HandlePrenda(IApplicationBuilder app)
    {
        app.Run(async context => {
            // Configurar el tipo de contenido de la respuesta
            context.Response.ContentType = "application/json";
            
            if (context.Request.Method == "POST")
            {
                try
                {
                    // Leer el cuerpo de la solicitud
                    var requestBody = await new StreamReader(context.Request.Body).ReadToEndAsync();
                    var request = JsonSerializer.Deserialize<PrendaRequest>(requestBody, new JsonSerializerOptions
                    {
                        PropertyNameCaseInsensitive = true
                    });
                    
                    if (request == null)
                    {
                        context.Response.StatusCode = 400;
                        await context.Response.WriteAsync(JsonSerializer.Serialize(new {
                            success = false,
                            message = "Datos de solicitud inválidos"
                        }));
                        return;
                    }
                    
                    // Convertir la imagen de base64 a bytes
                    byte[] imagenBytes = null;
                    if (!string.IsNullOrEmpty(request.ImagenBase64))
                    {
                        // Eliminar el prefijo "data:image/jpeg;base64," si existe
                        string base64Data = request.ImagenBase64;
                        if (base64Data.Contains(","))
                        {
                            base64Data = base64Data.Substring(base64Data.IndexOf(",") + 1);
                        }
                        
                        imagenBytes = Convert.FromBase64String(base64Data);
                    }
                    
                    using (var connection = new MySqlConnection(Utils.CONNECTION_STRING))
                    {
                        await connection.OpenAsync();
                        
                        // Insertar la prenda en la base de datos
                        var insertCommand = new MySqlCommand(
                            "INSERT INTO prenda (nombre, tipo, estilo, tipo_corte, estacion, imagen_prenda) " +
                            "VALUES (@nombre, @tipo, @estilo, @tipo_corte, @estacion, @imagen_prenda)",
                            connection);
                            
                        insertCommand.Parameters.AddWithValue("@nombre", request.Nombre);
                        insertCommand.Parameters.AddWithValue("@tipo", request.Tipo);
                        insertCommand.Parameters.AddWithValue("@estilo", request.Estilo);
                        insertCommand.Parameters.AddWithValue("@tipo_corte", request.TipoCorte);
                        insertCommand.Parameters.AddWithValue("@estacion", request.Estacion);
                        
                        if (imagenBytes != null)
                        {
                            insertCommand.Parameters.AddWithValue("@imagen_prenda", imagenBytes);
                        }
                        else
                        {
                            insertCommand.Parameters.AddWithValue("@imagen_prenda", DBNull.Value);
                        }
                        
                        await insertCommand.ExecuteNonQueryAsync();
                        
                        // Obtener el ID de la prenda insertada
                        var getIdCommand = new MySqlCommand("SELECT LAST_INSERT_ID()", connection);
                        var prendaId = Convert.ToInt32(await getIdCommand.ExecuteScalarAsync());
                        
                        // Obtener el ID del armario del usuario (asumimos usuario con ID 1)
                        int userId = 1; // Reemplazar con el ID del usuario actual cuando tengas sistema de login
                        
                        var armarioQuery = new MySqlCommand(
                            "SELECT id FROM armario WHERE id_cliente = @id_cliente",
                            connection);
                        armarioQuery.Parameters.AddWithValue("@id_cliente", userId);
                        
                        var armarioId = Convert.ToInt32(await armarioQuery.ExecuteScalarAsync());
                        
                        // Relacionar la prenda con el armario
                        var armarioPrendaCommand = new MySqlCommand(
                            "INSERT INTO armario_prenda (id_armario, id_prenda) VALUES (@id_armario, @id_prenda)",
                            connection);
                        armarioPrendaCommand.Parameters.AddWithValue("@id_armario", armarioId);
                        armarioPrendaCommand.Parameters.AddWithValue("@id_prenda", prendaId);
                        
                        await armarioPrendaCommand.ExecuteNonQueryAsync();
                        
                        await context.Response.WriteAsync(JsonSerializer.Serialize(new {
                            success = true,
                            message = "Prenda guardada correctamente",
                            prendaId = prendaId
                        }));
                    }
                }
                catch (Exception ex)
                {
                    context.Response.StatusCode = 500;
                    await context.Response.WriteAsync(JsonSerializer.Serialize(new {
                        success = false,
                        message = "Error en el servidor: " + ex.Message
                    }));
                }
            }
            else
            {
                context.Response.StatusCode = 405;
                await context.Response.WriteAsync(JsonSerializer.Serialize(new {
                    success = false,
                    message = "Método no permitido"
                }));
            }
        });
    }
}
