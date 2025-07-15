<?php
require_once 'includes/db.php';

// Verificar si ya existe un usuario administrador
$stmt = $db->prepare("SELECT id FROM usuarios WHERE email = 'admin@tienda.com'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Actualizar la contraseña del usuario existente
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE usuarios SET password = ?, is_admin = 1 WHERE email = 'admin@tienda.com'");
    $stmt->bind_param("s", $password_hash);
    
    if ($stmt->execute()) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px;'>";
        echo "<h3>✅ Usuario administrador actualizado</h3>";
        echo "<p><strong>Email:</strong> admin@tienda.com</p>";
        echo "<p><strong>Contraseña:</strong> admin123</p>";
        echo "<p><strong>Estado:</strong> Administrador</p>";
        echo "<br><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Login</a>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px;'>";
        echo "<h3>❌ Error al actualizar el usuario</h3>";
        echo "<p>Error: " . $stmt->error . "</p>";
        echo "</div>";
    }
} else {
    // Crear nuevo usuario administrador
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, is_admin) VALUES (?, ?, ?, 1)");
    $nombre = 'Administrador';
    $email = 'admin@tienda.com';
    $stmt->bind_param("sss", $nombre, $email, $password_hash);
    
    if ($stmt->execute()) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px;'>";
        echo "<h3>✅ Usuario administrador creado</h3>";
        echo "<p><strong>Email:</strong> admin@tienda.com</p>";
        echo "<p><strong>Contraseña:</strong> admin123</p>";
        echo "<p><strong>Estado:</strong> Administrador</p>";
        echo "<br><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Login</a>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px;'>";
        echo "<h3>❌ Error al crear el usuario</h3>";
        echo "<p>Error: " . $stmt->error . "</p>";
        echo "</div>";
    }
}

// Mostrar información adicional
echo "<div style='background: #e2e3e5; color: #383d41; padding: 15px; border-radius: 5px; margin: 20px;'>";
echo "<h4>📋 Información adicional:</h4>";
echo "<ul>";
echo "<li>Este script crea/actualiza un usuario administrador</li>";
echo "<li>La contraseña se hashea correctamente usando PHP</li>";
echo "<li>Una vez que inicies sesión, podrás acceder al panel de administración</li>";
echo "<li>Después de usar este script, puedes eliminarlo por seguridad</li>";
echo "</ul>";
echo "</div>";
?> 