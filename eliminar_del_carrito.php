<?php
require_once 'includes/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que se recibió el ID del producto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: carrito.php?error=producto_no_especificado');
    exit;
}

$producto_id = intval($_GET['id']);

// Verificar que el producto existe en el carrito
if (!isset($_SESSION['carrito']) || !isset($_SESSION['carrito'][$producto_id])) {
    header('Location: carrito.php?error=producto_no_encontrado');
    exit;
}

// Eliminar el producto del carrito
unset($_SESSION['carrito'][$producto_id]);

// Redirigir de vuelta al carrito con mensaje de éxito
header('Location: carrito.php?success=producto_eliminado');
exit;
?> 