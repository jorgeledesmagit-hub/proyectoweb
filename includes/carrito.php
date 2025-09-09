<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inicializar el carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = array();
}

/**
 * Agrega un producto al carrito
 */
function agregarAlCarrito($producto_id, $cantidad) {
    error_log("agregarAlCarrito - Iniciando función");
    error_log("agregarAlCarrito - Producto ID: $producto_id, Cantidad: $cantidad");
    error_log("agregarAlCarrito - Estado del carrito antes: " . print_r($_SESSION['carrito'], true));
    
    if (isset($_SESSION['carrito'][$producto_id])) {
        // Si ya existe, suma la cantidad
        $_SESSION['carrito'][$producto_id]['cantidad'] += $cantidad;
        error_log("agregarAlCarrito - Producto existente, cantidad actualizada");
    } else {
        // Si no existe, crea el array con la cantidad
        $_SESSION['carrito'][$producto_id] = ['cantidad' => $cantidad];
        error_log("agregarAlCarrito - Nuevo producto agregado");
    }
    
    error_log("agregarAlCarrito - Estado del carrito después: " . print_r($_SESSION['carrito'], true));
}

/**
 * Actualiza la cantidad de un producto en el carrito
 */
function actualizarCantidad($producto_id, $cantidad) {
    if ($cantidad > 0) {
        if (isset($_SESSION['carrito'][$producto_id]) && is_array($_SESSION['carrito'][$producto_id])) {
            $_SESSION['carrito'][$producto_id]['cantidad'] = $cantidad;
        } else {
            $_SESSION['carrito'][$producto_id] = ['cantidad' => $cantidad];
        }
    } else {
        eliminarDelCarrito($producto_id);
    }
}

/**
 * Elimina un producto del carrito
 */
function eliminarDelCarrito($producto_id) {
    if (isset($_SESSION['carrito'][$producto_id])) {
        unset($_SESSION['carrito'][$producto_id]);
    }
}

/**
 * Vacía el carrito
 */
function vaciarCarrito() {
    $_SESSION['carrito'] = array();
}

/**
 * Obtiene el contenido del carrito
 */
function obtenerCarrito($db) {
    $carrito = array();
    $total = 0;

    if (!empty($_SESSION['carrito'])) {
        $productos_ids = array_keys($_SESSION['carrito']);
        $ids_string = implode(',', $productos_ids);
        
        $query = "SELECT * FROM productos WHERE id IN ($ids_string)";
        $resultado = $db->query($query);

        while ($producto = $resultado->fetch_assoc()) {
            $cantidad = $_SESSION['carrito'][$producto['id']]['cantidad'];
            $subtotal = $producto['precio'] * $cantidad;
            $total += $subtotal;

            $carrito[] = array(
                'id' => $producto['id'],
                'nombre' => $producto['nombre'],
                'precio' => $producto['precio'],
                'cantidad' => $cantidad,
                'subtotal' => $subtotal,
                'imagen' => $producto['imagen']
            );
        }
    }

    return array(
        'items' => $carrito,
        'total' => $total
    );
}

/**
 * Procesa la orden y vacía el carrito
 */
function procesarOrden($db, $datos_cliente) {
    try {
        $db->begin_transaction();

        // Insertar la orden
        $stmt = $db->prepare("INSERT INTO ordenes (usuario_id, total, nombre_cliente, email_cliente, direccion_envio, telefono)
                             VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("idssss",
            $_SESSION['user_id'], // Asumiendo que el usuario_id está en la sesión
            $datos_cliente['total'],
            $datos_cliente['nombre'],
            $datos_cliente['email'],
            $datos_cliente['direccion'],
            $datos_cliente['telefono']
        );
        
        $stmt->execute();
        $orden_id = $db->insert_id;

        // Insertar los detalles de la orden
        $stmt = $db->prepare("INSERT INTO detalles_orden (orden_id, producto_id, cantidad, precio_unitario, subtotal) 
                             VALUES (?, ?, ?, ?, ?)");

        foreach ($_SESSION['carrito'] as $producto_id => $data) {
            $cantidad = $data['cantidad'];
            // Obtener el precio actual del producto
            $query = "SELECT precio FROM productos WHERE id = ?";
            $stmt_precio = $db->prepare($query);
            $stmt_precio->bind_param("i", $producto_id);
            $stmt_precio->execute();
            $resultado = $stmt_precio->get_result();
            $producto = $resultado->fetch_assoc();
            
            $precio_unitario = $producto['precio'];
            $subtotal = $precio_unitario * $cantidad;

            $stmt->bind_param("iiids", 
                $orden_id,
                $producto_id,
                $cantidad,
                $precio_unitario,
                $subtotal
            );
            if (!$stmt->execute()) {
                error_log("Error al insertar detalle de orden: " . $stmt->error);
                throw new Exception("Error al guardar detalles del pedido.");
            }

            // Actualizar el stock
            if (!$db->query("UPDATE productos SET stock = stock - $cantidad WHERE id = $producto_id")) {
                error_log("Error al actualizar stock: " . $db->error);
                throw new Exception("Error al actualizar el stock del producto.");
            }
        }

        $db->commit();
        vaciarCarrito();
        return $orden_id;

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}
?> 