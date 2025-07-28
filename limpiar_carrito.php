<?php
require_once 'includes/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no logueado']);
    exit;
}

// Verificar que hay productos en el carrito
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
    exit;
}

// Verificar que se recibieron los datos del formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos del formulario
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $total = floatval($_POST['total'] ?? 0);

    // Validar datos
    if (empty($nombre) || empty($email) || empty($telefono) || empty($direccion)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
        exit;
    }

    // Iniciar transacción
    $db->begin_transaction();

    // Calcular total real del carrito
    $total_real = 0;
    $productos_carrito = [];

    if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
        $ids = array_keys($_SESSION['carrito']);
        $query = "SELECT * FROM productos WHERE id IN (" . implode(',', $ids) . ")";
        $result = $db->query($query);
        
        while ($producto = $result->fetch_assoc()) {
            $item_carrito = $_SESSION['carrito'][$producto['id']];
            
            // Manejar tanto arrays como números para compatibilidad
            if (is_array($item_carrito)) {
                $cantidad = $item_carrito['cantidad'];
            } else {
                $cantidad = intval($item_carrito);
            }
            
            $subtotal = $producto['precio'] * $cantidad;
            $total_real += $subtotal;
            
            $productos_carrito[] = [
                'id' => $producto['id'],
                'nombre' => $producto['nombre'],
                'precio' => $producto['precio'],
                'cantidad' => $cantidad,
                'subtotal' => $subtotal,
                'stock' => $producto['stock']
            ];
        }
    }

    // Verificar que el total coincida
    if (abs($total_real - $total) > 0.01) {
        throw new Exception('El total no coincide con los productos del carrito');
    }

    // Crear el pedido
    $stmt = $db->prepare("
        INSERT INTO pedidos (usuario_id, nombre, email, telefono, direccion, total, fecha, estado) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), 'pendiente')
    ");
    $stmt->bind_param("issssd", 
        $_SESSION['user_id'],
        $nombre,
        $email,
        $telefono,
        $direccion,
        $total_real
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Error al crear el pedido: ' . $stmt->error);
    }
    
    $pedido_id = $db->insert_id;

    // Crear los detalles del pedido
    $stmt = $db->prepare("
        INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario, subtotal) 
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($productos_carrito as $producto) {
        // Verificar stock
        if ($producto['cantidad'] > $producto['stock']) {
            throw new Exception('Stock insuficiente para el producto: ' . $producto['nombre']);
        }

        $stmt->bind_param("iiids", 
            $pedido_id,
            $producto['id'],
            $producto['cantidad'],
            $producto['precio'],
            $producto['subtotal']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al crear detalle del pedido: ' . $stmt->error);
        }

        // Actualizar stock
        $nuevo_stock = $producto['stock'] - $producto['cantidad'];
        $update_stmt = $db->prepare("UPDATE productos SET stock = ? WHERE id = ?");
        $update_stmt->bind_param("ii", $nuevo_stock, $producto['id']);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Error al actualizar stock: ' . $update_stmt->error);
        }
    }

    // Confirmar transacción
    $db->commit();

    // Limpiar el carrito
    $_SESSION['carrito'] = array();

    // Enviar respuesta exitosa
    echo json_encode([
        'success' => true, 
        'message' => 'Pedido creado exitosamente',
        'pedido_id' => $pedido_id
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($db->connect_errno === 0) {
        $db->rollback();
    }
    
    error_log('Error al procesar compra: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al procesar la compra: ' . $e->getMessage()
    ]);
}
?> 