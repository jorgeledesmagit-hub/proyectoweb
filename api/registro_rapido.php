<?php
require_once 'includes/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que se recibieron los datos necesarios
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmar_password = $_POST['confirmar_password'] ?? '';

// Validar datos
$errores = [];

if (empty($nombre)) {
    $errores[] = 'El nombre es requerido';
}

if (empty($email)) {
    $errores[] = 'El email es requerido';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El email no es válido';
}

if (empty($password)) {
    $errores[] = 'La contraseña es requerida';
} elseif (strlen($password) < 6) {
    $errores[] = 'La contraseña debe tener al menos 6 caracteres';
}

if ($password !== $confirmar_password) {
    $errores[] = 'Las contraseñas no coinciden';
}

// Si hay errores, devolverlos
if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errores)]);
    exit;
}

try {
    // Verificar si el email ya existe
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'El email ya está registrado. Por favor, inicia sesión.']);
        exit;
    }

    // Hash de la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar nuevo usuario SOLO COMO CLIENTE (is_admin = 0)
    $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, is_admin, fecha_registro) VALUES (?, ?, ?, 0, NOW())");
    $stmt->bind_param("sss", $nombre, $email, $password_hash);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al crear el usuario: ' . $stmt->error);
    }

    $user_id = $db->insert_id;

    // Iniciar sesión automáticamente COMO CLIENTE
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $nombre;
    $_SESSION['user_email'] = $email;
    $_SESSION['is_admin'] = 0; // Asegurar que es cliente

    // Devolver respuesta exitosa
    echo json_encode([
        'success' => true, 
        'message' => 'Cliente registrado exitosamente',
        'user_id' => $user_id,
        'user_name' => $nombre,
        'is_admin' => 0
    ]);

} catch (Exception $e) {
    error_log('Error en registro rápido de cliente: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al registrar el cliente. Por favor, intenta nuevamente.'
    ]);
}
?> 