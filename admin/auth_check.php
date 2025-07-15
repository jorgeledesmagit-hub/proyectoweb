<?php
// Verificar que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?error=no_autenticado');
    exit;
}

// Verificar que el usuario sea administrador
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php?error=acceso_denegado');
    exit;
}

// Verificar que el usuario existe en la base de datos y sigue siendo administrador
require_once '../includes/db.php';

try {
    $stmt = $db->prepare("SELECT id, nombre, email, is_admin FROM usuarios WHERE id = ? AND is_admin = 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // El usuario ya no es administrador o no existe
        session_destroy();
        header('Location: ../login.php?error=privilegios_revocados');
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Actualizar la sesión con datos actualizados
    $_SESSION['user_name'] = $user['nombre'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['is_admin'] = $user['is_admin'];
    
} catch (Exception $e) {
    error_log('Error verificando permisos de administrador: ' . $e->getMessage());
    header('Location: ../login.php?error=error_sistema');
    exit;
}
?> 