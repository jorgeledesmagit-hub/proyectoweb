<?php
require_once 'includes/db.php';

// Verificar si ya existe un administrador
$stmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE is_admin = 1");
$stmt->execute();
$result = $stmt->get_result();
$admin_count = $result->fetch_assoc()['total'];

if ($admin_count > 0) {
    echo "Ya existe al menos un administrador en el sistema. Este script solo debe usarse para crear el primer administrador.";
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';
    $codigo_secreto = $_POST['codigo_secreto'] ?? '';

    // Código secreto para crear el primer administrador
    $codigo_correcto = 'PRIMER_ADMIN_2024';

    if ($codigo_secreto !== $codigo_correcto) {
        $mensaje = 'Código secreto incorrecto';
        $tipo_mensaje = 'danger';
    } elseif (empty($nombre) || empty($email) || empty($password)) {
        $mensaje = 'Todos los campos son requeridos';
        $tipo_mensaje = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'El email no es válido';
        $tipo_mensaje = 'danger';
    } elseif (strlen($password) < 8) {
        $mensaje = 'La contraseña debe tener al menos 8 caracteres';
        $tipo_mensaje = 'danger';
    } elseif ($password !== $confirmar_password) {
        $mensaje = 'Las contraseñas no coinciden';
        $tipo_mensaje = 'danger';
    } else {
        try {
            // Verificar si el email ya existe
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $mensaje = 'El email ya está registrado';
                $tipo_mensaje = 'danger';
            } else {
                // Hash de la contraseña
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Insertar primer administrador
                $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, is_admin, fecha_registro) VALUES (?, ?, ?, 1, NOW())");
                $stmt->bind_param("sss", $nombre, $email, $password_hash);
                
                if ($stmt->execute()) {
                    $mensaje = 'Primer administrador creado exitosamente. Ya puedes iniciar sesión.';
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = 'Error al crear el administrador';
                    $tipo_mensaje = 'danger';
                }
            }
        } catch (Exception $e) {
            $mensaje = 'Error en el sistema';
            $tipo_mensaje = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Primer Administrador - Mi Tienda Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css"> 
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-user-shield me-2"></i>
                            Crear Primer Administrador
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Importante:</strong> Este formulario solo debe usarse para crear el primer administrador del sistema. 
                            Una vez creado, usa el panel de administración para crear administradores adicionales.
                        </div>

                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($mensaje); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                                <div class="form-text">Mínimo 8 caracteres</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirmar_password" class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" minlength="8" required>
                            </div>
                            <div class="mb-3">
                                <label for="codigo_secreto" class="form-label">Código Secreto</label>
                                <input type="password" class="form-control" id="codigo_secreto" name="codigo_secreto" required>
                                <div class="form-text">Código especial requerido para crear el primer administrador</div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Crear Primer Administrador
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Volver al Inicio
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 