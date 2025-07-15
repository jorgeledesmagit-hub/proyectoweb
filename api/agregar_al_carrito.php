<?php
require_once 'includes/db.php';
require_once 'includes/carrito.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Verificar que se recibieron los datos necesarios
if (!isset($_POST['producto_id']) || !isset($_POST['cantidad'])) {
    $_SESSION['error'] = "Datos incompletos";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$producto_id = (int)$_POST['producto_id'];
$cantidad = (int)$_POST['cantidad'];

// Validar que la cantidad sea positiva
if ($cantidad <= 0) {
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
    // Verificar stock disponible
    if ($producto['stock'] < $cantidad) {
        $_SESSION['error'] = "No hay suficiente stock disponible";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Agregar al carrito
    agregarAlCarrito($producto_id, $cantidad);
    $_SESSION['success'] = "Producto agregado al carrito";
} else {
    $_SESSION['error'] = "Producto no encontrado";
}

// Redirigir de vuelta a la página anterior
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?> 