<?php
require_once 'includes/db.php';
require_once 'includes/carrito.php';

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Prueba Simple del Carrito</h2>";

// Si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $producto_id = (int)$_POST['producto_id'];
    $cantidad = (int)$_POST['cantidad'];
    
    echo "<h3>Procesando agregar producto...</h3>";
    echo "Producto ID: $producto_id<br>";
    echo "Cantidad: $cantidad<br>";
    
    // Verificar si el producto existe
    $stmt = $db->prepare("SELECT id, nombre, stock FROM productos WHERE id = ?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($producto = $result->fetch_assoc()) {
        echo "✅ Producto encontrado: " . $producto['nombre'] . "<br>";
        
        if ($producto['stock'] >= $cantidad) {
            // Agregar al carrito
            agregarAlCarrito($producto_id, $cantidad);
            echo "✅ Producto agregado al carrito<br>";
        } else {
            echo "❌ Stock insuficiente<br>";
        }
    } else {
        echo "❌ Producto no encontrado<br>";
    }
}

// Mostrar estado actual del carrito
echo "<h3>Estado Actual del Carrito</h3>";
if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
    echo "Productos en el carrito:<br>";
    foreach ($_SESSION['carrito'] as $producto_id => $data) {
        echo "- Producto ID: $producto_id, Cantidad: " . $data['cantidad'] . "<br>";
    }
} else {
    echo "El carrito está vacío<br>";
}

// Mostrar productos disponibles
echo "<h3>Productos Disponibles</h3>";
$productos = $db->query("SELECT id, nombre, precio, stock FROM productos LIMIT 5");
if ($productos) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Stock</th></tr>";
    while ($producto = $productos->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $producto['id'] . "</td>";
        echo "<td>" . $producto['nombre'] . "</td>";
        echo "<td>$" . $producto['precio'] . "</td>";
        echo "<td>" . $producto['stock'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Formulario de prueba
echo "<h3>Formulario de Prueba</h3>";
echo '<form method="POST">';
echo '<label>Producto ID: <input type="number" name="producto_id" value="1" min="1"></label><br>';
echo '<label>Cantidad: <input type="number" name="cantidad" value="1" min="1"></label><br>';
echo '<button type="submit" name="agregar">Agregar al Carrito</button>';
echo '</form>';

// Botones de acción
echo "<h3>Acciones</h3>";
echo '<a href="vaciar_carrito.php">Vaciar Carrito</a><br>';
echo '<a href="/proyectoweb/carrito.php">Ver Carrito Completo</a><br>';
echo '<a href="/proyectoweb/productos.php">Ver Productos</a><br>';
echo '<a href="probar_carrito.php">Recargar</a>';
?> 