<!DOCTYPE html>
<?php
require_once 'includes/db.php';
require_once 'includes/carrito.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("agregar_al_carrito.php - Error: Método no permitido");
    header('Location: /proyectoweb/index.php');
    exit;
}

// Verificar que se recibieron los datos necesarios
if (!isset($_POST['producto_id']) || !isset($_POST['cantidad'])) {
    error_log("agregar_al_carrito.php - Error: Datos incompletos");
    $_SESSION['error'] = "Datos incompletos";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$producto_id = (int)$_POST['producto_id'];
$cantidad = (int)$_POST['cantidad'];

error_log("agregar_al_carrito.php - Producto ID: $producto_id, Cantidad: $cantidad");

// Validar que la cantidad sea positiva
if ($cantidad <= 0) {
    error_log("agregar_al_carrito.php - Error: Cantidad inválida");
    $_SESSION['error'] = "La cantidad debe ser mayor a 0";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Verificar que el producto existe y hay stock disponible
$stmt = $db->prepare("SELECT stock FROM productos WHERE id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($producto = $resultado->fetch_assoc()) {
    error_log("agregar_al_carrito.php - Producto encontrado, stock: " . $producto['stock']);
    
    // Verificar stock disponible
    if ($producto['stock'] < $cantidad) {
        error_log("agregar_al_carrito.php - Error: Stock insuficiente");
        $_SESSION['error'] = "No hay suficiente stock disponible";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Agregar al carrito
    error_log("agregar_al_carrito.php - Agregando al carrito");
    agregarAlCarrito($producto_id, $cantidad);
    $_SESSION['success'] = "Producto agregado al carrito";
    error_log("agregar_al_carrito.php - Producto agregado exitosamente");
} else {
    error_log("agregar_al_carrito.php - Error: Producto no encontrado");
    $_SESSION['error'] = "Producto no encontrado o fue eliminado.";
    header('Location: /proyectoweb/productos.php');
    exit;
}

// Redirigir de vuelta a la página anterior
error_log("agregar_al_carrito.php - Redirigiendo a: " . $_SERVER['HTTP_REFERER']);
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?> 