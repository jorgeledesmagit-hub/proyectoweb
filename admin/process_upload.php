<?php
session_start();
// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de la base de datos
$db = new mysqli('localhost', 'root', '', 'tienda_online');

if ($db->connect_error) {
    die('Error de conexión: ' . $db->connect_error);
}

// Función para registrar errores
function logError($message) {
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/error.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar campos requeridos
        $required_fields = ['nombre', 'descripcion', 'precio', 'categoria_id', 'stock'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("El campo $field es requerido");
            }
        }

        // Validar y procesar la imagen
        if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error al subir la imagen: " . ($_FILES['imagen']['error'] ?? 'No se subió ninguna imagen'));
        }

        $file = $_FILES['imagen'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception("Tipo de archivo no permitido. Solo se permiten JPG, PNG y WEBP");
        }

        if ($file['size'] > $max_size) {
            throw new Exception("El archivo es demasiado grande. Máximo 5MB permitido");
        }

        // Crear directorio de productos si no existe
        $upload_dir = __DIR__ . '/../assets/images/productos';
        if (!file_exists($upload_dir)) {
            if (!@mkdir($upload_dir, 0777, true)) {
                $error = error_get_last();
                throw new Exception("No se pudo crear el directorio de productos: " . ($error['message'] ?? 'Error desconocido'));
            }
        }

        // Verificar permisos de escritura
        if (!is_writable($upload_dir)) {
            throw new Exception("El directorio de productos no tiene permisos de escritura");
        }

        // Generar nombre único para la imagen
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid('producto-') . '.' . $file_extension;
        $target_path = $upload_dir . '/' . $unique_filename;

        // Mover la imagen al directorio de productos
        if (!@move_uploaded_file($file['tmp_name'], $target_path)) {
            $error = error_get_last();
            throw new Exception("Error al mover la imagen al directorio de productos: " . ($error['message'] ?? 'Error desconocido'));
        }

        // Asegurar permisos de lectura para la imagen
        @chmod($target_path, 0644);

        // Preparar y ejecutar la consulta SQL
        $stmt = $db->prepare("INSERT INTO productos (nombre, descripcion, precio, categoria_id, imagen, stock) VALUES (?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $db->error);
        }

        // La ruta de la imagen en la base de datos será relativa a assets/images/productos
        $imagen_path = 'assets/images/productos/' . $unique_filename;
        
        $stmt->bind_param("ssdiss", 
            $_POST['nombre'],
            $_POST['descripcion'],
            $_POST['precio'],
            $_POST['categoria_id'],
            $imagen_path,
            $_POST['stock']
        );

        if (!$stmt->execute()) {
            // Si hay error, eliminar la imagen subida
            unlink($target_path);
            throw new Exception("Error al guardar en la base de datos: " . $stmt->error);
        }

        // Redirigir a la página de productos con mensaje de éxito
        header("Location: /proyectoweb/productos.php?success=1");
        exit;

    } catch (Exception $e) {
        logError($e->getMessage());
        header("Location: upload.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    // Si no es POST, redirigir al formulario
    header("Location: upload.php");
    exit;
}
?> 