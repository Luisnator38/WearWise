<?php
// Sección de procesamiento PHP (al inicio del archivo)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Conexión a la base de datos
    $servername = "localhost";
    $username = "root"; 
    $password = "";
    $dbname = "wear_wise";

    // Crear conexión
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");

    // Verificar conexión
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'message' => "Error de conexión: " . $conn->connect_error]));
    }

    // Obtener datos del formulario
    $nombre = isset($_POST['nombre']) ? $conn->real_escape_string($_POST['nombre']) : '';
    $tipo = isset($_POST['tipo']) ? $conn->real_escape_string($_POST['tipo']) : '';
    $estilo = isset($_POST['estilo']) ? $conn->real_escape_string($_POST['estilo']) : '';
    $tipo_corte = isset($_POST['tipo_corte']) ? $conn->real_escape_string($_POST['tipo_corte']) : '';
    $estacion = isset($_POST['estacion']) ? $conn->real_escape_string($_POST['estacion']) : '';
    
    // Procesar la imagen
    $imagen_prenda = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $imagen_prenda = file_get_contents($_FILES['imagen']['tmp_name']);
    }
    
    // Preparar la consulta SQL
    $stmt = $conn->prepare("INSERT INTO prenda (nombre, tipo, estilo, tipo_corte, estacion, imagen_prenda) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nombre, $tipo, $estilo, $tipo_corte, $estacion, $imagen_prenda);
    
    // Ejecutar la consulta
    $result = ['success' => false, 'message' => 'Error al guardar la prenda'];
    
    if ($stmt->execute()) {
        // Obtener el ID de la prenda insertada
        $prenda_id = $conn->insert_id;
        
        // Obtener el ID del armario del usuario (asumimos usuario con ID 1)
        $user_id = 1; // Reemplazar con el ID del usuario actual cuando tengas sistema de login
        
        // Obtener el ID del armario
        $armario_query = "SELECT id FROM armario WHERE id_cliente = ?";
        $armario_stmt = $conn->prepare($armario_query);
        $armario_stmt->bind_param("i", $user_id);
        $armario_stmt->execute();
        $armario_result = $armario_stmt->get_result();
        
        if ($armario_row = $armario_result->fetch_assoc()) {
            $armario_id = $armario_row['id'];
            
            // Insertar en la tabla armario_prenda
            $ap_stmt = $conn->prepare("INSERT INTO armario_prenda (id_armario, id_prenda) VALUES (?, ?)");
            $ap_stmt->bind_param("ii", $armario_id, $prenda_id);
            $ap_stmt->execute();
            $ap_stmt->close();
            
            $result = ['success' => true, 'message' => 'Prenda guardada correctamente', 'prenda_id' => $prenda_id];
        } else {
            $result = ['success' => false, 'message' => 'No se encontró un armario para este usuario'];
        }
        
        $armario_stmt->close();
    } else {
        $result = ['success' => false, 'message' => 'Error al guardar la prenda: ' . $stmt->error];
    }
    
    $stmt->close();
    $conn->close();
    
    // Devolver respuesta como JSON y terminar la ejecución
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Prendas</title>
    <link rel="stylesheet" href="estilos/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
   <!-- navbar-expand-md: el navbar se expande completamente a partir de 768px-->
   <nav class="navbar navbar-expand-md custom-navbar navbar-dark">
    <!--Para que ocupe todo el ancho de la página-->
    <div class="container-fluid">
        <!--Diseño flexible y el contenedor ocupa todo el espacio restante-->
        <div class="d-flex flex-grow-1">
            <!--Ancho del 100% y se oculta en pantallas grandes siendo un bloque visible en pantallas menores-->
            <span class="w-100 d-lg-none d-block">
            </span>
            <!--Se oculta en pantallas pequeñas y se muestra como un bloque en pantallas mayores o iguales que sm-->
            <a class="navbar-brand d-none d-sm-inline-block" href="index.html">
                <img src="imagenes/LogoWearWise.png" alt="logo"  class="logo-img d-none d-sm-inline-block" width="120px">
               </a>
            <div class="w-100 text-right">
                <!--mt: margin-top; mx: margin-right-->
                <button class="navbar-toggler mt-1 mx-2" type="button" data-bs-toggle="collapse"
                    data-bs-target="#myNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </div>
        <!--Cuando se pulsa el botón se despliega el menú ocupando todo el espacio (flex-grow) y alineando todos los textos a la derecha-->
        <div class="collapse navbar-collapse flex-grow-1 text-right" id="myNavbar">
            <!--ms-auto: pone la lista a la derecha; flex-nowrap: los elementos de la lista no se envuelven-->
            <ul class="navbar-nav ms-auto flex-nowrap nav-underline">
                <li class="nav-item active">
                    <a href="index.html" class="nav-link m-1 menu-item " aria-current="page">
                        Inicio</a>
                </li>
                <li class="nav-item">
                    <a href="uploader.php" class="nav-link active m-1 menu-item">                          
                        Subir artículo</a>
                </li>
                <li class="nav-item">
                    <a href="miInventario.html" class="nav-link m-1 menu-item">                      
                        Mi inventario</a>
                </li>
                <li class="nav-item">
                    <a href="outfit.html" class="nav-link m-1 menu-item">
                        Crear outfit</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
    <div class="container">
        <div class="header">
            <h1>Añade una nueva prenda</h1>
        </div>
        <div class="content">
            <!-- Contenedor de vista previa -->
            <div class="preview-container" id="previewContainer">
                <div class="preview-placeholder" id="previewPlaceholder">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <p>Haz clic o arrastra una imagen aquí</p>
                </div>
                <img id="previewImage" class="preview-image hidden" alt="Vista previa">
                <video id="cameraPreview" class="hidden" autoplay playsinline></video>
                <button id="clearButton" class="clear-button hidden">✕</button>
                <!-- Botón de captura para la cámara -->
                <button id="captureButton" class="capture-button hidden">
                    <div class="capture-button-inner"></div>
                </button>
            </div>
            
            <!-- Nombre del archivo -->
            <p id="fileName" class="file-name hidden"></p>
            
            <!-- Mensaje de error -->
            <p id="errorMessage" class="error-message hidden"></p>
            
            <!-- Mensaje de estado -->
            <p id="statusMessage" class="status-message hidden"></p>
            
            <!-- Formulario de prenda (inicialmente oculto) -->
            <div id="formContainer" class="form-container hidden">
                <h2 class="form-title">Información de la Prenda</h2>
                
                <div class="form-group">
                    <label for="nombre">Nombre de la prenda</label>
                    <input type="text" id="nombre" class="form-control" required placeholder="Ej: Camisa Blanca">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo">Tipo</label>
                        <select id="tipo" class="form-control" required>
                            <option value="">Seleccionar...</option>
                            <option value="Pantalón">Pantalón</option>
                            <option value="Falda">Falda</option>
                            <option value="Vestido">Vestido</option>
                            <option value="Zapatos">Zapatos</option>
                            <option value="Blusa">Blusa</option>
                            <option value="Camiseta">Camiseta</option>
                            <option value="Abrigo">Abrigo</option>
                            <option value="Jersey">Jersey</option>
                            <option value="Chaqueta">Chaqueta</option>
                            <option value="Top">Top</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="estilo">Estilo</label>
                        <select id="estilo" class="form-control" required>
                            <option value="">Seleccionar...</option>
                            <option value="Formal">Formal</option>
                            <option value="Deportivo">Deportivo</option>
                            <option value="Vintage">Vintage</option>
                            <option value="Minimalista">Minimalista</option>
                            <option value="Clásico">Clásico</option>
                            <option value="Urbano">Urbano</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo_corte">Tipo de Corte</label>
                        <select id="tipo_corte" class="form-control" required>
                            <option value="">Seleccionar...</option>
                            <option value="Otro">Otro</option>
                            <option value="Manga corta">Manga corta</option>
                            <option value="Manga larga">Manga larga</option>
                            <option value="Tirantes">Tirantes</option>
                            <option value="Manga media">Manga media</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="estacion">Estación</label>
                        <select id="estacion" class="form-control" required>
                            <option value="">Seleccionar...</option>
                            <option value="Verano">Verano</option>
                            <option value="Invierno">Invierno</option>
                            <option value="Primavera">Primavera</option>
                            <option value="Otoño">Otoño</option>
                        </select>
                    </div>
                </div>
                
                <button id="saveButton" class="btn-save">Guardar Nueva Prenda</button>
            </div>
            
            <!-- Input oculto para galería -->
            <input type="file" id="fileInput" class="hidden" accept=".jpg,.jpeg,.png,.gif,.bmp,.tiff,.tif,.webp,.heif,.heic,.raw,.cr2,.nef,.arw,.orf,.dng,.svg,.ai,.eps,.pdf,.ico,.psd,.xcf,.exr,.tga,.targa">
            
            <!-- Canvas oculto para capturar la imagen -->
            <canvas id="canvas" class="hidden"></canvas>
        </div>
        <div class="footer">
            <button class="btn btn-outline" id="galleryButton">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Galería
            </button>
            <button class="btn btn-primary" id="cameraButton">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                </svg>
                Cámara
            </button>
        </div>
    </div>

    <script>
        // Elementos del DOM
        const fileInput = document.getElementById('fileInput');
        const galleryButton = document.getElementById('galleryButton');
        const cameraButton = document.getElementById('cameraButton');
        const previewContainer = document.getElementById('previewContainer');
        const previewPlaceholder = document.getElementById('previewPlaceholder');
        const previewImage = document.getElementById('previewImage');
        const cameraPreview = document.getElementById('cameraPreview');
        const clearButton = document.getElementById('clearButton');
        const captureButton = document.getElementById('captureButton');
        const fileName = document.getElementById('fileName');
        const errorMessage = document.getElementById('errorMessage');
        const statusMessage = document.getElementById('statusMessage');
        const canvas = document.getElementById('canvas');
        
        // Elementos del formulario
        const formContainer = document.getElementById('formContainer');
        const nombreInput = document.getElementById('nombre');
        const tipoSelect = document.getElementById('tipo');
        const estiloSelect = document.getElementById('estilo');
        const tipoCorteSelect = document.getElementById('tipo_corte');
        const estacionSelect = document.getElementById('estacion');
        const saveButton = document.getElementById('saveButton');
        
        // Variables para la cámara
        let stream = null;
        
        // Variable para almacenar la imagen actual
        let currentImageUrl = null;
        let currentImageFile = null;
        
        // Formatos de imagen soportados
        const supportedFormats = [
            "image/jpeg", "image/jpg", "image/png", "image/gif", "image/bmp", 
            "image/tiff", "image/webp", "image/heif", "image/heic", 
            "image/svg+xml", "application/pdf", "image/vnd.adobe.photoshop",
            "image/x-icon", "image/x-xcf", "image/x-exr", "image/x-tga"
        ];

        // Función para verificar la extensión del archivo
        function checkFileExtension(filename) {
            const supportedExtensions = [
                '.jpg', '.jpeg', '.png', '.gif', '.bmp', '.tiff', '.tif', '.webp', 
                '.heif', '.heic', '.raw', '.cr2', '.nef', '.arw', '.orf', '.dng', 
                '.svg', '.ai', '.eps', '.pdf', '.ico', '.psd', '.xcf', '.exr', '.tga', '.targa'
            ];
            
            const lowerFilename = filename.toLowerCase();
            return supportedExtensions.some(ext => lowerFilename.endsWith(ext));
        }

        // Función para manejar el cambio de archivo
        function handleFileChange(e) {
            let file;
            
            // Verificar si es un evento de input file o un evento de drop
            if (e.dataTransfer && e.dataTransfer.files.length > 0) {
                file = e.dataTransfer.files[0];
            } else if (e.target && e.target.files && e.target.files.length > 0) {
                file = e.target.files[0];
            } else {
                return; // No hay archivo para procesar
            }
            
            // Verificar si el formato es soportado
            if (!supportedFormats.includes(file.type) && !checkFileExtension(file.name)) {
                showError("Formato de imagen no soportado");
                return;
            }
            
            // Guardar el archivo para enviarlo más tarde
            currentImageFile = file;
            
            // Mostrar nombre del archivo
            fileName.textContent = `Archivo: ${file.name}`;
            fileName.classList.remove('hidden');
            
            // Limpiar error si existe
            errorMessage.classList.add('hidden');
            statusMessage.classList.add('hidden');
            
            // Crear URL para la vista previa
            const imageUrl = URL.createObjectURL(file);
            currentImageUrl = imageUrl;
            
            // Mostrar la imagen
            showImage(imageUrl);
            
            // Mostrar el formulario
            showForm();
        }

        // Función para mostrar la imagen en la vista previa
        function showImage(imageUrl) {
            // Ocultar la cámara si está activa
            stopCamera();
            
            // Mostrar la imagen
            previewImage.src = imageUrl;
            previewImage.classList.remove('hidden');
            previewPlaceholder.classList.add('hidden');
            cameraPreview.classList.add('hidden');
            captureButton.classList.add('hidden');
            previewContainer.classList.add('has-image');
            clearButton.classList.remove('hidden');
        }

        // Función para mostrar el formulario
        function showForm() {
            // Mostrar el formulario
            formContainer.classList.remove('hidden');
            
            // Resetear los campos del formulario
            resetFormFields();
        }
        
        // Función para resetear los campos del formulario
        function resetFormFields() {
            nombreInput.value = "";
            tipoSelect.value = "";
            estiloSelect.value = "";
            tipoCorteSelect.value = "";
            estacionSelect.value = "";
            errorMessage.classList.add('hidden');
        }

        // Función para mostrar error
        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.classList.remove('hidden');
            statusMessage.classList.add('hidden');
            console.error("Error:", message);
        }

        // Función para mostrar mensaje de estado
        function showStatus(message) {
            statusMessage.textContent = message;
            statusMessage.classList.remove('hidden');
            errorMessage.classList.add('hidden');
            console.log("Estado:", message);
        }
        
        // Función para mostrar mensaje de éxito flotante
        function showFloatingSuccess(message) {
            // Crear elemento para el mensaje
            const successElement = document.createElement('div');
            successElement.className = 'floating-success';
            successElement.textContent = message;
            
            // Añadir al body
            document.body.appendChild(successElement);
            
            // Eliminar después de la animación
            setTimeout(() => {
                successElement.remove();
            }, 3000);
        }

        // Función para limpiar la imagen y resetear el formulario
        function clearImage(e) {
            if (e) {
                e.stopPropagation(); // Evitar que el clic se propague al contenedor
            }
            
            resetToInitialState();
        }
        
        // Función para resetear todo al estado inicial
        function resetToInitialState() {
            previewImage.classList.add('hidden');
            previewPlaceholder.classList.remove('hidden');
            cameraPreview.classList.add('hidden');
            captureButton.classList.add('hidden');
            previewContainer.classList.remove('has-image');
            clearButton.classList.add('hidden');
            fileName.classList.add('hidden');
            errorMessage.classList.add('hidden');
            statusMessage.classList.add('hidden');
            formContainer.classList.add('hidden');
            
            // Limpiar el input
            fileInput.value = '';
            
            // Detener la cámara si está activa
            stopCamera();
            
            // Revocar URL de objeto para liberar memoria
            if (previewImage.src) {
                URL.revokeObjectURL(previewImage.src);
                previewImage.src = '';
                currentImageUrl = null;
                currentImageFile = null;
            }
            
            // Resetear los campos del formulario
            resetFormFields();
        }

        // Función para iniciar la cámara
        async function startCamera() {
            try {
                // Mostrar mensaje de estado
                showStatus("Solicitando acceso a la cámara...");
                
                // Solicitar acceso a la cámara
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: "environment" }, 
                    audio: false 
                });
                
                // Conectar el stream al elemento de video
                cameraPreview.srcObject = stream;
                
                // Mostrar la interfaz de la cámara
                previewPlaceholder.classList.add('hidden');
                previewImage.classList.add('hidden');
                cameraPreview.classList.remove('hidden');
                captureButton.classList.remove('hidden'); // Mostrar el botón de captura
                clearButton.classList.remove('hidden'); // Mostrar el botón para cancelar
                fileName.classList.add('hidden');
                statusMessage.classList.add('hidden');
                formContainer.classList.add('hidden');
                
                // Esperar a que el video se cargue
                await new Promise(resolve => {
                    cameraPreview.onloadedmetadata = () => {
                        resolve();
                    };
                });
                
                // Iniciar reproducción (necesario en algunos navegadores)
                await cameraPreview.play();
                
                // Ya no configuramos el contenedor para capturar la imagen al hacer clic
                // Ahora usamos el botón de captura
                previewContainer.onclick = null;
                
            } catch (error) {
                console.error("Error al acceder a la cámara:", error);
                
                if (error.name === 'NotAllowedError') {
                    showError("Acceso a la cámara denegado. Por favor, permite el acceso a la cámara en la configuración de tu navegador.");
                } else if (error.name === 'NotFoundError') {
                    showError("No se encontró ninguna cámara en tu dispositivo.");
                } else {
                    showError("Error al acceder a la cámara: " + error.message);
                }
                
                // Restaurar el comportamiento normal del contenedor
                previewContainer.onclick = openFileDialog;
            }
        }

        // Función para detener la cámara
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
                cameraPreview.srcObject = null;
                
                // Restaurar el comportamiento normal del contenedor
                previewContainer.onclick = openFileDialog;
            }
        }

        // Función para capturar una imagen de la cámara
        function captureImage() {
            if (!stream) return;
            
            try {
                // Configurar el canvas con las dimensiones del video
                canvas.width = cameraPreview.videoWidth;
                canvas.height = cameraPreview.videoHeight;
                
                // Dibujar el frame actual del video en el canvas
                const ctx = canvas.getContext('2d');
                ctx.drawImage(cameraPreview, 0, 0, canvas.width, canvas.height);
                
                // Convertir el canvas a una imagen
                canvas.toBlob(blob => {
                    // Crear un archivo a partir del blob
                    currentImageFile = new File([blob], "foto_capturada.jpg", { type: "image/jpeg" });
                    
                    // Crear URL para la vista previa
                    const imageUrl = URL.createObjectURL(blob);
                    currentImageUrl = imageUrl;
                    
                    // Mostrar la imagen capturada
                    showImage(imageUrl);
                    
                    // Mostrar nombre del archivo
                    fileName.textContent = "Archivo: foto_capturada.jpg";
                    fileName.classList.remove('hidden');
                    
                    // Mostrar el formulario
                    showForm();
                }, 'image/jpeg', 0.95);
                
                // Detener la cámara
                stopCamera();
                
                // Restaurar el comportamiento normal del contenedor
                previewContainer.onclick = openFileDialog;
                
            } catch (error) {
                console.error("Error al capturar la imagen:", error);
                showError("Error al capturar la imagen: " + error.message);
                stopCamera();
            }
        }
        
        // Función para abrir el diálogo de selección de archivos
        function openFileDialog() {
            // No abrir el diálogo si ya hay una imagen o la cámara está activa
            if (stream || !previewImage.classList.contains('hidden')) {
                return;
            }
            
            fileInput.click();
        }
        
        // Funciones para el manejo de arrastrar y soltar
        function handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            previewContainer.classList.add('drag-over');
        }
        
        function handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            previewContainer.classList.remove('drag-over');
        }
        
        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            previewContainer.classList.remove('drag-over');
            
            // Procesar el archivo soltado
            handleFileChange(e);
        }
        
        // Función para guardar la prenda en la base de datos
        function savePrenda() {
            // Validar que todos los campos estén completos
            if (!validateForm()) {
                showError("Por favor, completa todos los campos del formulario");
                return;
            }
            
            // Validar que haya una imagen
            if (!currentImageFile) {
                showError("Por favor, selecciona o captura una imagen");
                return;
            }
            
            // Mostrar mensaje de estado
            showStatus("Guardando prenda...");
            
            // Crear FormData para enviar al servidor
            const formData = new FormData();
            formData.append('nombre', nombreInput.value);
            formData.append('tipo', tipoSelect.value);
            formData.append('estilo', estiloSelect.value);
            formData.append('tipo_corte', tipoCorteSelect.value);
            formData.append('estacion', estacionSelect.value);
            formData.append('imagen', currentImageFile);
            
            // Enviar datos al servidor (a este mismo archivo)
            fetch('uploader.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    showFloatingSuccess(data.message);
                    
                    // Resetear todo al estado inicial
                    resetToInitialState();
                    
                    console.log("Prenda guardada:", data);
                } else {
                    // Mostrar mensaje de error
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error("Error al guardar la prenda:", error);
                showError("Error al guardar la prenda: " + error.message);
            });
        }
        
        // Función para validar el formulario
        function validateForm() {
            return (
                nombreInput.value.trim() !== "" &&
                tipoSelect.value !== "" &&
                estiloSelect.value !== "" &&
                tipoCorteSelect.value !== "" &&
                estacionSelect.value !== ""
            );
        }

        // Event Listeners
        fileInput.addEventListener('change', handleFileChange);
        
        galleryButton.addEventListener('click', () => {
            fileInput.click();
        });
        
        cameraButton.addEventListener('click', () => {
            startCamera();
        });
        
        clearButton.addEventListener('click', clearImage);
        
        // Añadir event listener para el botón de captura
        captureButton.addEventListener('click', (e) => {
            e.stopPropagation(); // Evitar que el clic se propague al contenedor
            captureImage();
        });
        
        saveButton.addEventListener('click', savePrenda);
        
        // Event listeners para el contenedor de vista previa
        previewContainer.addEventListener('click', openFileDialog);
        previewContainer.addEventListener('dragover', handleDragOver);
        previewContainer.addEventListener('dragleave', handleDragLeave);
        previewContainer.addEventListener('drop', handleDrop);
        
        // Event listeners para validación en tiempo real
        const formInputs = [nombreInput, tipoSelect, estiloSelect, tipoCorteSelect, estacionSelect];
        formInputs.forEach(input => {
            input.addEventListener('input', () => {
                // Si todos los campos están completos, quitar mensaje de error
                if (validateForm()) {
                    errorMessage.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
