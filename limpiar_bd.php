<?php
require_once 'includes/db.php';

echo "<h2>Limpieza y Corrección de la Base de Datos</h2>";

function ejecutar($db, $query, $descripcion) {
    echo "<b>$descripcion</b>: ";
    if ($db->query($query)) {
        echo "✅ Ejecutado correctamente<br>";
    } else {
        echo "❌ Error: " . $db->error . "<br>";
    }
}

// 1. Corregir productos sin nombre
$query = "DELETE FROM productos WHERE nombre IS NULL OR nombre = ''";
ejecutar($db, $query, "Eliminar productos sin nombre");

// 2. Corregir productos sin precio válido
$query = "UPDATE productos SET precio = 1 WHERE precio IS NULL OR precio <= 0";
ejecutar($db, $query, "Corregir productos sin precio válido (se pone precio 1)");

// 3. Corregir productos sin stock válido
$query = "UPDATE productos SET stock = 1 WHERE stock IS NULL OR stock < 0";
ejecutar($db, $query, "Corregir productos sin stock válido (se pone stock 1)");

// 4. Corregir productos sin categoría válida
$query = "UPDATE productos SET categoria_id = 1 WHERE categoria_id IS NULL OR categoria_id = 0 OR categoria_id NOT IN (SELECT id FROM categorias)";
ejecutar($db, $query, "Corregir productos sin categoría válida (se pone categoria_id 1)");

// 5. Eliminar categorías sin nombre
$query = "DELETE FROM categorias WHERE nombre IS NULL OR nombre = ''";
ejecutar($db, $query, "Eliminar categorías sin nombre");

// 6. Crear categoría 'Otros' si no existe
$query = "INSERT IGNORE INTO categorias (id, nombre) VALUES (1, 'Otros')";
ejecutar($db, $query, "Crear categoría 'Otros' si no existe");

// 7. Corregir productos con imágenes vacías
$query = "UPDATE productos SET imagen = NULL WHERE imagen = ''";
ejecutar($db, $query, "Corregir productos con imagen vacía");

// 8. Mostrar resumen
$res = $db->query("SELECT COUNT(*) as total FROM productos");
$row = $res ? $res->fetch_assoc() : ["total" => 0];
echo "<br><b>Total de productos:</b> " . $row['total'] . "<br>";
$res = $db->query("SELECT COUNT(*) as total FROM categorias");
$row = $res ? $res->fetch_assoc() : ["total" => 0];
echo "<b>Total de categorías:</b> " . $row['total'] . "<br>";

echo "<hr><a href='verificar_datos_bd.php'>Verificar Datos</a> | <a href='productos.php'>Ver Productos</a> | <a href='test_carrito.php'>Test del Carrito</a> | <a href='probar_carrito.php'>Prueba Simple del Carrito</a>";
?> 