<?php
require_once 'includes/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login?error=no_autenticado');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Obtener información del usuario
$stmt = $db->prepare("SELECT id, nombre, email, is_admin, fecha_registro FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    header('Location: logout');
    exit;
}

// Procesar actualización del perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'actualizar_perfil':
                $nombre = trim($_POST['nombre'] ?? '');
                $email = trim($_POST['email'] ?? '');
                
                if (empty($nombre) || empty($email)) {
                    $mensaje = 'Todos los campos son requeridos';
                    $tipo_mensaje = 'danger';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $mensaje = 'El email no es válido';
                    $tipo_mensaje = 'danger';
                } else {
                    // Verificar si el email ya existe en otro usuario
                    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
                    $stmt->bind_param("si", $email, $_SESSION['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $mensaje = 'El email ya está en uso por otro usuario';
                        $tipo_mensaje = 'danger';
                    } else {
                        $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?");
                        $stmt->bind_param("ssi", $nombre, $email, $_SESSION['user_id']);
                        
                        if ($stmt->execute()) {
                            $_SESSION['user_name'] = $nombre;
                            $_SESSION['user_email'] = $email;
                            $usuario['nombre'] = $nombre;
                            $usuario['email'] = $email;
                            $mensaje = 'Perfil actualizado exitosamente';
                            $tipo_mensaje = 'success';
                        } else {
                            $mensaje = 'Error al actualizar el perfil';
                            $tipo_mensaje = 'danger';
                        }
                    }
                }
                break;
                
            case 'cambiar_password':
                $password_actual = $_POST['password_actual'] ?? '';
                $password_nuevo = $_POST['password_nuevo'] ?? '';
                $password_confirmar = $_POST['password_confirmar'] ?? '';
                
                if (empty($password_actual) || empty($password_nuevo) || empty($password_confirmar)) {
                    $mensaje = 'Todos los campos son requeridos';
                    $tipo_mensaje = 'danger';
                } elseif (strlen($password_nuevo) < 6) {
                    $mensaje = 'La nueva contraseña debe tener al menos 6 caracteres';
                    $tipo_mensaje = 'danger';
                } elseif ($password_nuevo !== $password_confirmar) {
                    $mensaje = 'Las contraseñas no coinciden';
                    $tipo_mensaje = 'danger';
                } else {
                    // Verificar contraseña actual
                    $stmt = $db->prepare("SELECT password FROM usuarios WHERE id = ?");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user_data = $result->fetch_assoc();
                    
                    if (!password_verify($password_actual, $user_data['password'])) {
                        $mensaje = 'La contraseña actual es incorrecta';
                        $tipo_mensaje = 'danger';
                    } else {
                        $password_hash = password_hash($password_nuevo, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                        $stmt->bind_param("si", $password_hash, $_SESSION['user_id']);
                        
                        if ($stmt->execute()) {
                            $mensaje = 'Contraseña cambiada exitosamente';
                            $tipo_mensaje = 'success';
                        } else {
                            $mensaje = 'Error al cambiar la contraseña';
                            $tipo_mensaje = 'danger';
                        }
                    }
                }
                break;
        }
    }
}

// Obtener estadísticas del usuario
$stats = [];

// Total de pedidos
$stmt = $db->prepare("SELECT COUNT(*) as total FROM pedidos WHERE usuario_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats['total_pedidos'] = $stmt->get_result()->fetch_assoc()['total'];

// Pedidos pendientes
$stmt = $db->prepare("SELECT COUNT(*) as total FROM pedidos WHERE usuario_id = ? AND estado = 'pendiente'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats['pedidos_pendientes'] = $stmt->get_result()->fetch_assoc()['total'];

// Total gastado
$stmt = $db->prepare("SELECT SUM(total) as total FROM pedidos WHERE usuario_id = ? AND estado = 'entregado'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats['total_gastado'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Mi Tienda Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <!-- Información del perfil -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-user me-2"></i>Mi Perfil
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($mensaje); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Información básica -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Información Personal</h5>
                                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($usuario['nombre']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
                                <p><strong>Rol:</strong> 
                                    <?php if ($usuario['is_admin']): ?>
                                        <span class="badge bg-primary">Administrador</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Cliente</span>
                                    <?php endif; ?>
                                </p>
                                <p><strong>Miembro desde:</strong> <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5>Estadísticas</h5>
                                <p><strong>Total de pedidos:</strong> <?php echo $stats['total_pedidos']; ?></p>
                                <p><strong>Pedidos pendientes:</strong> <?php echo $stats['pedidos_pendientes']; ?></p>
                                <p><strong>Total gastado:</strong> $<?php echo number_format($stats['total_gastado'], 2); ?></p>
                            </div>
                        </div>

                        <!-- Formulario de actualización -->
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Actualizar Información</h5>
                                <form method="POST">
                                    <input type="hidden" name="action" value="actualizar_perfil">
                                    <div class="mb-3">
                                        <label for="nombre" class="form-label">Nombre</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" 
                                               value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Actualizar Perfil
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <h5>Cambiar Contraseña</h5>
                                <form method="POST">
                                    <input type="hidden" name="action" value="cambiar_password">
                                    <div class="mb-3">
                                        <label for="password_actual" class="form-label">Contraseña Actual</label>
                                        <input type="password" class="form-control" id="password_actual" name="password_actual" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password_nuevo" class="form-label">Nueva Contraseña</label>
                                        <input type="password" class="form-control" id="password_nuevo" name="password_nuevo" minlength="6" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password_confirmar" class="form-label">Confirmar Nueva Contraseña</label>
                                        <input type="password" class="form-control" id="password_confirmar" name="password_confirmar" minlength="6" required>
                                    </div>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-key me-2"></i>Cambiar Contraseña
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="/ProyectoWeb/api/mis_pedidos" class="btn btn-outline-primary">
                                <i class="fas fa-shopping-bag me-2"></i>Mis Pedidos
                            </a>
                            <?php if ($usuario['is_admin']): ?>
                                <a href="/ProyectoWeb/api/admin/index" class="btn btn-outline-success">
                                    <i class="fas fa-tachometer-alt me-2"></i>Panel de Administración
                                </a>
                            <?php endif; ?>
                            <a href="/ProyectoWeb/api/index" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-2"></i>Ir a la Tienda
                            </a>
                            <a href="/ProyectoWeb/api/logout" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 