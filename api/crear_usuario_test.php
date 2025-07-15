<?php
require_once 'includes/db.php';

// Crear o actualizar usuario de prueba
$nombre = 'Usuario Test';
$email = 'test@tienda.com';
$password = 'test123';
$is_admin = 0;

// Hash de la contraseña
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Verificar si el usuario ya existe
$stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Actualizar usuario existente
    $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, password = ? WHERE email = ?");
    $stmt->bind_param("sss", $nombre, $password_hash, $email);
    if ($stmt->execute()) {
        echo "Usuario actualizado exitosamente<br>";
    } else {
        echo "Error al actualizar usuario: " . $stmt->error . "<br>";
    }
} else {
    // Crear nuevo usuario
    $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, is_admin, fecha_registro) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssi", $nombre, $email, $password_hash, $is_admin);
    if ($stmt->execute()) {
        echo "Usuario creado exitosamente<br>";
    } else {
        echo "Error al crear usuario: " . $stmt->error . "<br>";
    }
}

// También resetear la contraseña del admin
$admin_password = 'admin123';
$admin_password_hash = password_hash($admin_password, PASSWORD_DEFAULT);

$stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE email = 'admin@tienda.com'");
$stmt->bind_param("s", $admin_password_hash);
if ($stmt->execute()) {
    echo "Contraseña del admin reseteada exitosamente<br>";
} else {
    echo "Error al resetear contraseña del admin: " . $stmt->error . "<br>";
}

echo "<br><strong>Credenciales de prueba:</strong><br>";
echo "Admin: admin@tienda.com / admin123<br>";
echo "Usuario: test@tienda.com / test123<br>";
echo "<br><a href='login.php'>Ir al Login</a>";
?> 