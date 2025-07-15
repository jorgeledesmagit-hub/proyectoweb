<?php
session_start();
require_once '../includes/db.php';

// Verificar que sea una petición POST y que tenga los datos necesarios
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id']) || !isset($_POST['estado'])) {
    $_SESSION['error'] = "Solicitud inválida";
    header('Location: productos.php');
    exit;
}

$producto_id = (int)$_POST['id'];
$nuevo_estado = $_POST['estado'];

// Validar que el estado sea válido
$estados_permitidos = ['activo', 'sin_stock', 'descontinuado'];
if (!in_array($nuevo_estado, $estados_permitidos)) {
    $_SESSION['error'] = "Estado no válido";
    header('Location: productos.php');
    exit;
}

try {
    // Iniciar transacción
    $db->begin_transaction();

    // Verificar si el producto existe
    $stmt = $db->prepare("SELECT nombre FROM productos WHERE id = ?");
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

    // Actualizar el estado del producto
    $stmt = $db->prepare("UPDATE productos SET estado = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta de actualización: " . $db->error);
    }

    $stmt->bind_param("si", $nuevo_estado, $producto_id);
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar el estado del producto: " . $stmt->error);
    }

    // Si el estado es 'sin_stock', establecer stock a 0
    if ($nuevo_estado === 'sin_stock') {
        $stmt = $db->prepare("UPDATE productos SET stock = 0 WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Error al actualizar el stock: " . $db->error);
        }
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
    }

    // Confirmar transacción
    $db->commit();

    // Mensaje de éxito según el estado
    $mensajes = [
        'activo' => "El producto '{$producto['nombre']}' ha sido activado",
        'sin_stock' => "El producto '{$producto['nombre']}' ha sido marcado como sin stock",
        'descontinuado' => "El producto '{$producto['nombre']}' ha sido marcado como descontinuado"
    ];
    $_SESSION['success'] = $mensajes[$nuevo_estado];

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $db->rollback();
    $_SESSION['error'] = $e->getMessage();
}

// Redirigir de vuelta a la página de productos
header('Location: productos.php');
exit;
?> 