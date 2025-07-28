<?php
// Evitar que se inicie la sesión dos veces
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la base de datos
$host = 'localhost';
$usuario = 'root';
$password = '';
$database = 'tienda_online';

// Habilitar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Crear conexión con manejo de errores mejorado
try {
    // Usar el socket de XAMPP en macOS
    $socket = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
    $db = new mysqli($host, $usuario, $password, $database, 3306, $socket);

    // Verificar conexión
    if ($db->connect_error) {
        error_log("Error de conexión a la base de datos: " . $db->connect_error);
        throw new Exception("Error de conexión a la base de datos. Por favor, intente más tarde.");
    }

    // Verificar que la base de datos existe
    if (!$db->select_db($database)) {
        error_log("La base de datos '$database' no existe");
        throw new Exception("Error de configuración de la base de datos. Por favor, contacte al administrador.");
    }

    // Establecer charset
    if (!$db->set_charset("utf8mb4")) {
        error_log("Error al establecer el charset: " . $db->error);
        throw new Exception("Error de configuración de la base de datos. Por favor, contacte al administrador.");
    }

    // Verificar que las tablas necesarias existen
    $tablas_requeridas = ['productos', 'usuarios', 'ordenes', 'detalles_orden'];
    foreach ($tablas_requeridas as $tabla) {
        $result = $db->query("SHOW TABLES LIKE '$tabla'");
        if ($result->num_rows === 0) {
            error_log("La tabla '$tabla' no existe en la base de datos");
            throw new Exception("Error de configuración de la base de datos. Por favor, contacte al administrador.");
        }
    }

} catch (Exception $e) {
    error_log("Error crítico en la conexión a la base de datos: " . $e->getMessage());
    die(json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos. Por favor, intente más tarde.'
    ]));
}
?> 