<?php
require_once '../includes/db.php';
require_once 'auth_check.php';

$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario de registro de administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';
    $codigo_admin = $_POST['codigo_admin'] ?? '';

    // Validar código de administrador (código secreto)
    $codigo_admin_correcto = 'ADMIN2024';

    if ($codigo_admin !== $codigo_admin_correcto) {
        $mensaje = 'Código de administrador incorrecto';
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

                // Insertar nuevo administrador
                $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, is_admin, fecha_registro) VALUES (?, ?, ?, 1, NOW())");
                $stmt->bind_param("sss", $nombre, $email, $password_hash);
                
                if ($stmt->execute()) {
                    $mensaje = 'Administrador registrado exitosamente';
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = 'Error al registrar el administrador';
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
    <title>Crear Administrador - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .sidebar .nav-link {
            color: #adb5bd;
        }
        .sidebar .nav-link:hover {
            color: #fff;
        }
        .sidebar .nav-link.active {
            color: #fff;
            background: #495057;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">Panel Admin</h4>
                        <small class="text-muted">Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name']); ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/proyectoweb/productos.php">
                                <i class="fas fa-box me-2"></i>
                                Productos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categorias.php">
                                <i class="fas fa-tags me-2"></i>
                                Categorías
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pedidos.php">
                                <i class="fas fa-shopping-cart me-2"></i>
                                Pedidos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="usuarios.php">
                                <i class="fas fa-users me-2"></i>
                                Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="crear_admin.php">
                                <i class="fas fa-user-shield me-2"></i>
                                Crear Admin
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../index.php">
                                <i class="fas fa-home me-2"></i>
                                Ver Tienda
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Crear Nuevo Administrador</h1>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h4 class="mb-0">
                                    <i class="fas fa-user-shield me-2"></i>
                                    Registro de Administrador
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Acceso Restringido:</strong> Solo administradores autorizados pueden crear nuevos administradores.
                                </div>

                                <?php if ($mensaje): ?>
                                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                                        <?php echo htmlspecialchars($mensaje); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="nombre" class="form-label">Nombre Completo</label>
                                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Correo Electrónico</label>
                                                <input type="email" class="form-control" id="email" name="email" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="password" class="form-label">Contraseña</label>
                                                <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                                                <div class="form-text">Mínimo 8 caracteres</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="confirmar_password" class="form-label">Confirmar Contraseña</label>
                                                <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" minlength="8" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="codigo_admin" class="form-label">Código de Administrador</label>
                                        <input type="password" class="form-control" id="codigo_admin" name="codigo_admin" required>
                                        <div class="form-text">Código especial requerido para crear administradores</div>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-user-plus me-2"></i>Crear Administrador
                                        </button>
                                        <a href="index.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 