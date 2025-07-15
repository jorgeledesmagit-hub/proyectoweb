<?php
require_once 'includes/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=no_autenticado');
    exit;
}

// Solo aceptar solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mis_pedidos.php?mensaje=Metodo_no_permitido&tipo=danger');
    exit;
}

$pedido_id = intval($_POST['pedido_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($pedido_id === 0) {
    header('Location: mis_pedidos.php?mensaje=ID_de_pedido_invalido&tipo=danger');
    exit;
}

$db->begin_transaction();

try {
    // 1. Verificar que el pedido existe y pertenece al usuario y está pendiente
    $stmt = $db->prepare("SELECT id, estado FROM pedidos WHERE id = ? AND usuario_id = ? FOR UPDATE");
    $stmt->bind_param("ii", $pedido_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pedido = $result->fetch_assoc();

    if (!$pedido) {
        throw new Exception('Pedido no encontrado o no pertenece a este usuario.');
    }

    if ($pedido['estado'] !== 'pendiente') {
        throw new Exception('Solo se pueden cancelar pedidos pendientes.');
    }

    // 2. Obtener detalles del pedido para restaurar el stock
    $stmt = $db->prepare("SELECT producto_id, cantidad FROM detalles_pedido WHERE pedido_id = ?");
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $detalles_pedido = $stmt->get_result();

    while ($detalle = $detalles_pedido->fetch_assoc()) {
        $producto_id = $detalle['producto_id'];
        $cantidad = $detalle['cantidad'];

        // Restaurar stock del producto
        $stmt_update_stock = $db->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
        $stmt_update_stock->bind_param("ii", $cantidad, $producto_id);
        if (!$stmt_update_stock->execute()) {
            throw new Exception('Error al restaurar stock del producto ID: ' . $producto_id);
        }
    }

    // 3. Actualizar el estado del pedido a 'cancelado'
    $stmt = $db->prepare("UPDATE pedidos SET estado = 'cancelado' WHERE id = ?");
    $stmt->bind_param("i", $pedido_id);
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar el estado del pedido a cancelado.');
    }

    $db->commit();
    header('Location: mis_pedidos.php?mensaje=Pedido_cancelado_exitosamente&tipo=success');
    exit;

} catch (Exception $e) {
    $db->rollback();
    error_log('Error al cancelar pedido: ' . $e->getMessage());
    header('Location: mis_pedidos.php?mensaje=' . urlencode($e->getMessage()) . '&tipo=danger');
    exit;
}
?> 