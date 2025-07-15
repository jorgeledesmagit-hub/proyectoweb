<?php
/**
 * Archivo de protección adicional para funciones críticas del administrador
 * Este archivo se incluye en todas las páginas que requieren acceso administrativo
 */

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
    
    // Registrar acceso administrativo (opcional para auditoría)
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $page = $_SERVER['REQUEST_URI'] ?? 'unknown';
    
    // Puedes agregar aquí un log de acceso si lo deseas
    // error_log("Admin access: User ID {$_SESSION['user_id']} accessed $page from $ip");
    
} catch (Exception $e) {
    error_log('Error verificando permisos de administrador: ' . $e->getMessage());
    header('Location: ../login.php?error=error_sistema');
    exit;
}

// Función para verificar permisos específicos
function verificar_permiso_admin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        return false;
    }
    return true;
}

// Función para registrar acciones administrativas
function registrar_accion_admin($accion, $detalles = '') {
    $usuario_id = $_SESSION['user_id'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $fecha = date('Y-m-d H:i:s');
    
    // Aquí puedes implementar un sistema de logging más avanzado
    error_log("Admin Action: User $usuario_id performed '$accion' - $detalles from $ip at $fecha");
}

// Función para verificar si el usuario puede realizar acciones críticas
function puede_realizar_accion_critica($accion) {
    // Aquí puedes implementar lógica adicional de permisos
    // Por ejemplo, verificar si el usuario tiene permisos específicos para ciertas acciones
    
    $acciones_criticas = [
        'crear_admin',
        'eliminar_admin', 
        'modificar_configuracion',
        'eliminar_productos',
        'eliminar_usuarios'
    ];
    
    if (in_array($accion, $acciones_criticas)) {
        // Verificar permisos adicionales si es necesario
        return true; // Por ahora, cualquier admin puede hacer estas acciones
    }
    
    return true;
}
?> 