<?php
require_once 'includes/db.php';

echo "<h2>Crear Productos de Prueba</h2>";

// 1. Crear categorías básicas
$categorias = [
    ['nombre' => 'Electrónicos', 'descripcion' => 'Productos electrónicos'],
    ['nombre' => 'Ropa', 'descripcion' => 'Vestimenta y accesorios'],
    ['nombre' => 'Hogar', 'descripcion' => 'Artículos para el hogar'],
    ['nombre' => 'Deportes', 'descripcion' => 'Artículos deportivos'],
    ['nombre' => 'Otros', 'descripcion' => 'Otros productos']
];

echo "<h3>1. Creando categorías...</h3>";
foreach ($categorias as $cat) {
    $stmt = $db->prepare("INSERT IGNORE INTO categorias (nombre, descripcion) VALUES (?, ?)");
    $stmt->bind_param("ss", $cat['nombre'], $cat['descripcion']);
    if ($stmt->execute()) {
        echo "✅ Categoría {$cat['nombre']}' creada/verificada<br>";
    } else {
        echo "❌ Error al crear categoría {$cat['nombre']}'<br>";
    }
}

// 2. Crear productos de prueba
$productos = [
    ['nombre' => 'Laptop HP Pavilion', 'precio' => 899.99, 'stock' => 10, 'categoria' => 'Electrónicos'],
    ['nombre' => 'Smartphone Samsung', 'precio' => 599.99, 'stock' => 15, 'categoria' => 'Electrónicos'],
    ['nombre' => 'Camiseta Básica', 'precio' => 19.99, 'stock' => 50, 'categoria' => 'Ropa'],
    ['nombre' => 'Pantalón Jeans', 'precio' => 39.99, 'stock' => 30, 'categoria' => 'Ropa'],
    ['nombre' => 'Lámpara de Mesa', 'precio' => 45.0, 'stock' => 15, 'categoria' => 'Hogar'],
    ['nombre' => 'Sofá 3 Plazas', 'precio' => 2990.99, 'stock' => 5, 'categoria' => 'Hogar'],
    ['nombre' => 'Pelota de Fútbol', 'precio' => 29.99, 'stock' => 25, 'categoria' => 'Deportes'],
    ['nombre' => 'Raqueta de Tenis', 'precio' => 89.99, 'stock' => 12, 'categoria' => 'Deportes'],
    ['nombre' => 'Libro de Cocina', 'precio' => 24.99, 'stock' => 20, 'categoria' => 'Otros'],
    ['nombre' => 'Reloj de Pared', 'precio' => 340.99, 'stock' => 8, 'categoria' => 'Otros']
];

echo "<h3>2. Creando productos...</h3>";
foreach ($productos as $prod) {
    $stmt = $db->prepare("INSERT IGNORE INTO productos (nombre, precio, stock, categoria_id) 
                          SELECT ?, ?, ?, c.id FROM categorias c WHERE c.nombre = ?");
    $stmt->bind_param("sdis", $prod['nombre'], $prod['precio'], $prod['stock'], $prod['categoria']);
    if ($stmt->execute()) {
        echo "✅ Producto {$prod['nombre']}' creado/verificado<br>";
    } else {
        echo "❌ Error al crear producto {$prod['nombre']}'<br>";
    }
}

// 3. Mostrar resumen
echo "<h3>3. Resumen</h3>";
$res = $db->query("SELECT COUNT(*) as total FROM productos");
$row = $res ? $res->fetch_assoc() : ["total" => 0];
echo "<b>Total de productos:</b> {$row['total']}<br>";

$res = $db->query("SELECT COUNT(*) as total FROM categorias");
$row = $res ? $res->fetch_assoc() : ["total" => 0];
echo "<b>Total de categorías:</b> {$row['total']}<br>";

$res = $db->query("SELECT COUNT(*) as total FROM productos WHERE stock > 0");
$row = $res ? $res->fetch_assoc() : ["total" => 0];
echo "<b>Productos con stock disponible:</b> {$row['total']}. <br>";

echo "<hr>";
echo "<h3>Enlaces de Prueba</h3>";
echo "<a href='productos.php'>Ver Productos</a><br>";
echo "<a href='test_carrito.php'>Test del Carrito</a><br>";
echo "<a href='probar_carrito.php'>Prueba Simple del Carrito</a><br>";
echo "<a href='verificar_datos_bd.php'>Verificar Datos</a>";
?> 