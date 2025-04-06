using System;
using MySql.Data.MySqlClient;

namespace UFV_MYSQL
{
    public class Program
    // gonzalo.delasheras@ufv.es
    {
        static void Main(string[] args)
        {      

            string query = @"
            SELECT 
                O.id AS id_outfit,
                O.nombre AS nombre_outfit,
                P.id AS id_prenda,
                P.nombre AS nombre_prenda,
                P.tipo AS tipo_prenda
            FROM 
                outfit O
                INNER JOIN outfit_prenda OP ON O.id = OP.id_outfit
                INNER JOIN prenda P ON OP.id_prenda = P.id
            ORDER BY O.id, P.id;
            ";

            MySqlConnection databaseConn = new MySqlConnection(Utils.CONNECTION_STRING);
            MySqlCommand command = new MySqlCommand(query, databaseConn);
            command.CommandTimeout = 60;
            MySqlDataReader reader;

            try
            {
                databaseConn.Open();
                reader = command.ExecuteReader();

                if (reader.HasRows)
                {
                    while (reader.Read())
                    {
                        Console.WriteLine(reader.GetString(0) + " || " + reader.GetInt32(1) +
                            " || " + reader.GetString(2) + " || " + reader.GetString(3));
                    }
                }
                else 
                {
                    Console.WriteLine("No hay datos");
                }
            }
            catch (Exception Ex)
            {
                // Excepcion
                Console.WriteLine(Ex.Message);
            }
        }
    }
}
