<?php
// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar sesión para mantener mensajes de error
session_start();

// Función para logging
function logError($message) {
    $logDir = '../logs';
    $logFile = $logDir . '/upload_errors.log';
    
    // Intentar crear el directorio si no existe
    if (!file_exists($logDir)) {
        if (!@mkdir($logDir, 0777, true)) {
            // Si no podemos crear el directorio, mostrar el error pero continuar
            error_log("No se pudo crear el directorio de logs: " . error_get_last()['message']);
            return;
        }
    }
    
    // Intentar escribir en el archivo de log
    if (!@error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, $logFile)) {
        // Si no podemos escribir en el archivo, mostrar el error pero continuar
        error_log("No se pudo escribir en el archivo de logs: " . error_get_last()['message']);
    }
}

try {
    // Conexión a la base de datos
    $db = new mysqli('localhost', 'root', '', 'tienda_online');

    if ($db->connect_error) {
        throw new Exception('Error de conexión a la base de datos: ' . $db->connect_error);
    }

    // Obtener categorías
    $categorias = $db->query("SELECT * FROM categorias ORDER BY nombre");

    // Obtener mensajes de error o éxito de la URL
    $error_message = isset($_GET['error']) ? $_GET['error'] : '';
    $success_message = isset($_GET['success']) ? 'Producto agregado exitosamente' : '';

} catch (Exception $e) {
    logError('Error: ' . $e->getMessage());
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - Subir Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .upload-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .preview-image {
            max-width: 200px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="upload-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Nuevo Producto</h2>
                <a href="/proyectoweb/productos.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a Productos
                </a>
            </div>

            <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <form action="process_upload.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre del Producto</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                </div>
                
                <div class="mb-3">
                    <label for="imagen" class="form-label">Imagen del Producto</label>
                    <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*" required>
                    <div id="imagePreview" class="mt-2"></div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="precio" class="form-label">Precio</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="categoria_id" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria_id" name="categoria_id" required>
                        <option value="">Seleccionar categoría</option>
                        <?php while ($cat = $categorias->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['nombre']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Guardar Producto</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Vista previa de la imagen
        document.getElementById('imagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.innerHTML = `<img src="${e.target.result}" class="preview-image" alt="Vista previa">`;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 