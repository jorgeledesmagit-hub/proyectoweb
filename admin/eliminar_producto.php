<?php
session_start();
require_once '../includes/db.php';

// Verificar que sea una petición GET y que tenga un ID
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id'])) {
    $_SESSION['error'] = "Solicitud inválida";
    header('Location: /proyectoweb/productos.php');
    exit;
}

$producto_id = (int)$_GET['id'];

try {
    // Iniciar transacción
    $db->begin_transaction();

    // Verificar si el producto existe y obtener su información
    $stmt = $db->prepare("SELECT p.imagen, p.nombre, COUNT(dp.id) as total_pedidos 
                         FROM productos p 
                         LEFT JOIN detalles_pedido dp ON p.id = dp.producto_id 
                         WHERE p.id = ? 
                         GROUP BY p.id");
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $db->error);
    }

    $stmt->bind_param("i", $producto_id);
    if (!$stmt->execute()) {
        throw new Exception("Error al verificar el producto: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("El producto no existe");
    }

    $producto = $result->fetch_assoc();

    // Verificar si el producto está en pedidos
    if ($producto['total_pedidos'] > 0) {
        throw new Exception("No se puede eliminar el producto '{$producto['nombre']}' porque está incluido en {$producto['total_pedidos']} pedido(s). " .
                          "En su lugar, puedes marcarlo como 'sin stock' o 'descontinuado'.");
    }

    // Eliminar el producto
    $stmt = $db->prepare("DELETE FROM productos WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta de eliminación: " . $db->error);
    }

    $stmt->bind_param("i", $producto_id);
    if (!$stmt->execute()) {
        throw new Exception("Error al eliminar el producto: " . $stmt->error);
    }

    // Si el producto tenía una imagen, eliminarla
    if (!empty($producto['imagen'])) {
        $imagen_path = __DIR__ . '/../' . $producto['imagen'];
        if (file_exists($imagen_path)) {
            unlink($imagen_path);
        }
    }

    // Confirmar transacción
    $db->commit();
    $_SESSION['success'] = "Producto eliminado exitosamente";

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $db->rollback();
    $_SESSION['error'] = $e->getMessage();
}

// Redirigir de vuelta a la página de productos
header('Location: /proyectoweb/productos.php');
exit;
?> 