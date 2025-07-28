<?php
require_once 'includes/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que se recibieron los datos necesarios
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /proyectoweb/carrito.php?error=metodo_no_permitido');
    exit;
}

if (!isset($_POST['producto_id']) || !isset($_POST['cantidad'])) {
    header('Location: /proyectoweb/carrito.php?error=datos_faltantes');
    exit;
}

$producto_id = intval($_POST['producto_id']);
$cantidad = intval($_POST['cantidad']);

// Validar cantidad
if ($cantidad <= 0) {
    // Si la cantidad es 0 o menor, eliminar el producto
    if (isset($_SESSION['carrito'][$producto_id])) {
        unset($_SESSION['carrito'][$producto_id]);
    }
    header('Location: /proyectoweb/carrito.php?success=producto_eliminado');
    exit;
}

// Verificar que el producto existe en la base de datos
$stmt = $db->prepare("SELECT id, nombre, stock FROM productos WHERE id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: /proyectoweb/carrito.php?error=producto_no_existe');
    exit;
}

$producto = $result->fetch_assoc();

// Verificar stock
if ($cantidad > $producto['stock']) {
    header('Location: /proyectoweb/carrito.php?error=stock_insuficiente');
    exit;
}

// Actualizar la cantidad en el carrito
if (isset($_SESSION['carrito'][$producto_id])) {
    $item_carrito = $_SESSION['carrito'][$producto_id];
    
    // Manejar tanto arrays como números para compatibilidad
    if (is_array($item_carrito)) {
        $_SESSION['carrito'][$producto_id]['cantidad'] = $cantidad;
    } else {
        $_SESSION['carrito'][$producto_id] = ['cantidad' => $cantidad];
    }
    
    header('Location: /proyectoweb/carrito.php?success=cantidad_actualizada');
} else {
    // Si el producto no está en el carrito, añadirlo
    $_SESSION['carrito'][$producto_id] = ['cantidad' => $cantidad];
    header('Location: /proyectoweb/carrito.php?success=producto_anadido');
}

exit;
?> 