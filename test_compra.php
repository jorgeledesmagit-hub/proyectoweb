<?php
require_once 'includes/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Test de Proceso de Compra</h2>";

// Verificar conexión a la base de datos
echo "<h3>1. Verificar conexión a la base de datos</h3>";
try {
    $result = $db->query("SELECT 1");
    echo "✅ Conexión a la base de datos: OK<br>";
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
    exit;
}

// Verificar que el usuario esté logueado
echo "<h3>2. Verificar sesión de usuario</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ Usuario logueado: ID " . $_SESSION['user_id'] . "<br>";
    echo "✅ Nombre: " . ($_SESSION['user_name'] ?? 'N/A') . "<br>";
} else {
    echo "❌ Usuario no logueado<br>";
    echo "<a href='login.php'>Ir al Login</a><br>";
    exit;
}

// Verificar carrito
echo "<h3>3. Verificar carrito</h3>";
if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
    echo "✅ Carrito tiene " . count($_SESSION['carrito']) . " productos<br>";
    foreach ($_SESSION['carrito'] as $producto_id => $item) {
        if (is_array($item)) {
            echo "   - Producto ID $producto_id: " . $item['cantidad'] . " unidades<br>";
        } else {
            echo "   - Producto ID $producto_id: " . $item . " unidades<br>";
        }
    }
} else {
    echo "❌ El carrito está vacío<br>";
    echo "<a href='index.php'>Ir a la tienda</a><br>";
    exit;
}

// Verificar tablas necesarias
echo "<h3>4. Verificar tablas de la base de datos</h3>";
$tablas = ['productos', 'pedidos', 'detalles_pedido'];
foreach ($tablas as $tabla) {
    $result = $db->query("SHOW TABLES LIKE '$tabla'");
    if ($result->num_rows > 0) {
        echo "✅ Tabla '$tabla': Existe<br>";
    } else {
        echo "❌ Tabla '$tabla': No existe<br>";
    }
}

// Verificar productos en el carrito
echo "<h3>5. Verificar productos en el carrito</h3>";
$ids = array_keys($_SESSION['carrito']);
if (!empty($ids)) {
    $query = "SELECT id, nombre, precio, stock FROM productos WHERE id IN (" . implode(',', $ids) . ")";
    $result = $db->query($query);
    
    if ($result->num_rows > 0) {
        echo "✅ Productos encontrados en la base de datos:<br>";
        while ($producto = $result->fetch_assoc()) {
            $item_carrito = $_SESSION['carrito'][$producto['id']];
            $cantidad = is_array($item_carrito) ? $item_carrito['cantidad'] : intval($item_carrito);
            echo "   - {$producto['nombre']}: $cantidad unidades, Stock: {$producto['stock']}<br>";
            
            if ($cantidad > $producto['stock']) {
                echo "   ⚠️ Stock insuficiente para {$producto['nombre']}<br>";
            }
        }
    } else {
        echo "❌ No se encontraron productos en la base de datos<br>";
    }
}

echo "<h3>6. Próximos pasos</h3>";
echo "Si todo está OK arriba, puedes proceder con la compra desde el carrito.<br>";
echo "<a href='/proyectoweb/carrito.php'>Ir al Carrito</a><br>";
echo "<a href='index.php'>Ir a la Tienda</a><br>";
?> 