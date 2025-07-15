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
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validar datos
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email y contraseña son requeridos']);
    exit;
}

try {
    // Buscar usuario por email
    $stmt = $db->prepare("SELECT id, nombre, email, password, is_admin FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email o contraseña incorrectos']);
        exit;
    }

    $usuario = $result->fetch_assoc();

    // Verificar contraseña
    if (!password_verify($password, $usuario['password'])) {
        echo json_encode(['success' => false, 'message' => 'Email o contraseña incorrectos']);
        exit;
    }

    // Iniciar sesión
    $_SESSION['user_id'] = $usuario['id'];
    $_SESSION['user_name'] = $usuario['nombre'];
    $_SESSION['user_email'] = $usuario['email'];
    $_SESSION['is_admin'] = $usuario['is_admin'];

    // Devolver respuesta exitosa
    echo json_encode([
        'success' => true, 
        'message' => 'Sesión iniciada exitosamente',
        'user_id' => $usuario['id'],
        'user_name' => $usuario['nombre']
    ]);

} catch (Exception $e) {
    error_log('Error en login rápido: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al iniciar sesión. Por favor, intenta nuevamente.'
    ]);
}
?> 