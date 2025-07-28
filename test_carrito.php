<?php
// Script de prueba para diagnosticar el problema del carrito
require_once 'includes/db.php';
require_once 'includes/carrito.php';

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Diagnóstico del Carrito</h2>";

// 1. Verificar conexión a la base de datos
echo "<h3>1. Verificación de Base de Datos</h3>";
try {
    $test_query = "SELECT COUNT(*) as total FROM productos";
    $result = $db->query($test_query);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ Conexión a BD exitosa. Total productos: " . $row['total'] . "<br>";
    } else {
        echo "❌ Error en consulta de productos<br>";
    }
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
}

// 2. Verificar sesión
echo "<h3>2. Verificación de Sesión</h3>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Sesión activa<br>";
    echo "ID de sesión: " . session_id() . "<br>";
} else {
    echo "❌ Sesión no activa<br>";
}

// 3. Verificar carrito actual
echo "<h3>3. Estado del Carrito</h3>";
if (isset($_SESSION['carrito'])) {
    echo "✅ Carrito existe en sesión<br>";
    echo "Contenido del carrito: <pre>" . print_r($_SESSION['carrito'], true) . "</pre>";
} else {
    echo "❌ Carrito no existe en sesión<br>";
}

// 4. Probar agregar un producto
echo "<h3>4. Prueba de Agregar Producto</h3>";
$producto_id = 1; // Asumiendo que existe un producto con ID 1
$cantidad = 1;

// Verificar si el producto existe
$stmt = $db->prepare("SELECT id, nombre, stock FROM productos WHERE id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result = $stmt->get_result();

if ($producto = $result->fetch_assoc()) {
    echo "✅ Producto encontrado: " . $producto['nombre'] . " (Stock: " . $producto['stock'] . ")<br>";
    
    // Intentar agregar al carrito
    agregarAlCarrito($producto_id, $cantidad);
    echo "✅ Producto agregado al carrito<br>";
    
    // Verificar el carrito después de agregar
    echo "Carrito después de agregar: <pre>" . print_r($_SESSION['carrito'], true) . "</pre>";
} else {
    echo "❌ Producto con ID $producto_id no encontrado<br>";
}

// 5. Verificar función obtenerCarrito
echo "<h3>5. Prueba de obtenerCarrito</h3>";
$carrito_data = obtenerCarrito($db);
echo "Datos del carrito: <pre>" . print_r($carrito_data, true) . "</pre>";

// 6. Verificar variables POST
echo "<h3>6. Variables POST</h3>";
echo "Método de petición: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "Variables POST: <pre>" . print_r($_POST, true) . "</pre>";

// 7. Verificar configuración de PHP
echo "<h3>7. Configuración de PHP</h3>";
echo "display_errors: " . ini_get('display_errors') . "<br>";
echo "error_reporting: " . ini_get('error_reporting') . "<br>";
echo "session.save_handler: " . ini_get('session.save_handler') . "<br>";
echo "session.save_path: " . ini_get('session.save_path') . "<br>";

echo "<hr>";
echo "<h3>Formulario de Prueba</h3>";
echo '<form action="agregar_al_carrito.php" method="POST">';
echo '<input type="hidden" name="producto_id" value="1">';
echo '<input type="number" name="cantidad" value="1" min="1">';
echo '<button type="submit">Agregar Producto ID 1</button>';
echo '</form>';

echo "<h3>Enlaces de Prueba</h3>";
echo '<a href="/proyectoweb/productos.php">Ver Productos</a><br>';
echo '<a href="/proyectoweb/carrito.php">Ver Carrito</a><br>';
echo '<a href="test_carrito.php">Recargar Diagnóstico</a>';
?> 