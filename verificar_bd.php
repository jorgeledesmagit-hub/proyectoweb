<?php
require_once 'includes/db.php';

echo "<h2>Verificación de Base de Datos</h2>";

// Verificar si la base de datos existe
echo "<h3>1. Verificación de Base de Datos</h3>";
$result = $db->query("SELECT DATABASE() as db_name");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✅ Base de datos actual: " . $row['db_name'] . "<br>";
} else {
    echo "❌ Error al obtener nombre de BD<br>";
}

// Verificar tablas existentes
echo "<h3>2. Tablas Existentes</h3>";
$result = $db->query("SHOW TABLES");
if ($result) {
    echo "Tablas encontradas:<br>";
    while ($row = $result->fetch_array()) {
        echo "- " . $row[0] . "<br>";
    }
} else {
    echo "❌ Error al listar tablas<br>";
}

// Verificar estructura de tabla productos
echo "<h3>3. Estructura de Tabla 'productos'</h3>";
$result = $db->query("DESCRIBE productos");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Llave</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Error al describir tabla productos<br>";
}

// Verificar datos de productos
echo "<h3>4. Datos de Productos</h3>";
$result = $db->query("SELECT id, nombre, precio, stock FROM productos LIMIT 5");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Stock</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nombre'] . "</td>";
        echo "<td>" . $row['precio'] . "</td>";
        echo "<td>" . $row['stock'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Error al consultar productos<br>";
}

// Verificar configuración de sesiones
echo "<h3>5. Configuración de Sesiones</h3>";
echo "session.save_handler: " . ini_get('session.save_handler') . "<br>";
echo "session.save_path: " . ini_get('session.save_path') . "<br>";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "<br>";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "<br>";

// Verificar permisos de escritura en directorio de sesiones
$session_path = ini_get('session.save_path');
if ($session_path && is_writable($session_path)) {
    echo "✅ Directorio de sesiones es escribible<br>";
} else {
    echo "❌ Directorio de sesiones no es escribible o no existe<br>";
}

echo "<hr>";
echo "<a href='test_carrito.php'>Ir a Test del Carrito</a><br>";
echo "<a href='productos.php'>Ver Productos</a>";
?> 