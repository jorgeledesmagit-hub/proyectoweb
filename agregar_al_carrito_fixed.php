<?php
// Versión corregida de agregar_al_carrito.php
require_once 'includes/db.php';
require_once 'includes/carrito.php';

// Iniciar sesión SIEMPRE al principio
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log de depuración
error_log("=== INICIO agregar_al_carrito.php ===");
error_log("Método: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));
error_log("Session ID: " . session_id());

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Error: Método no permitido");
    $_SESSION['error'] = "Método no permitido";
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'productos.php';
    header('Location: ' . $redirect_url);
    exit;
}

// Verificar que se recibieron los datos necesarios
if (!isset($_POST['producto_id']) || !isset($_POST['cantidad'])) {
    error_log("Error: Datos incompletos");
    $_SESSION['error'] = "Datos incompletos";
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'productos.php';
    header('Location: ' . $redirect_url);
    exit;
}

$producto_id = (int)$_POST['producto_id'];
$cantidad = (int)$_POST['cantidad'];

error_log("Producto ID: $producto_id, Cantidad: $cantidad");

// Validar que la cantidad sea positiva
if ($cantidad <= 0) {
    error_log("Error: Cantidad inválida");
    $_SESSION['error'] = "La cantidad debe ser mayor a 0";
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'productos.php';
    header('Location: ' . $redirect_url);
    exit;
}

// Verificar que el producto existe y hay stock disponible
try {
    $stmt = $db->prepare("SELECT id, nombre, stock FROM productos WHERE id = ?");
    if (!$stmt) {
        error_log("Error en prepare: " . $db->error);
        throw new Exception("Error en la consulta");
    }
    
    $stmt->bind_param("i", $producto_id);
    if (!$stmt->execute()) {
        error_log("Error en execute: " . $stmt->error);
        throw new Exception("Error al ejecutar la consulta");
    }
    
    $resultado = $stmt->get_result();
    
    if ($producto = $resultado->fetch_assoc()) {
        error_log("Producto encontrado: " . $producto['nombre'] . " (Stock: " . $producto['stock'] . ")");
        
        // Verificar stock disponible
        if ($producto['stock'] < $cantidad) {
            error_log("Error: Stock insuficiente");
            $_SESSION['error'] = "No hay suficiente stock disponible";
            $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'productos.php';
            header('Location: ' . $redirect_url);
            exit;
        }

        // Agregar al carrito
        error_log("Agregando al carrito...");
        agregarAlCarrito($producto_id, $cantidad);
        $_SESSION['success'] = "Producto agregado al carrito";
        error_log("Producto agregado exitosamente");
        
    } else {
        error_log("Error: Producto no encontrado");
        $_SESSION['error'] = "Producto no encontrado";
    }
    
} catch (Exception $e) {
    error_log("Excepción: " . $e->getMessage());
    $_SESSION['error'] = "Error interno del servidor";
}

// Redirigir de vuelta a la página anterior
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'productos.php';
error_log("Redirigiendo a: " . $redirect_url);
header('Location: ' . $redirect_url);
exit;
?> 