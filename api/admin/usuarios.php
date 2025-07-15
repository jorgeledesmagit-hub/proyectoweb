<?php
require_once '../includes/db.php';
require_once 'auth_check.php';

$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'cambiar_rol':
                $usuario_id = intval($_POST['usuario_id']);
                $nuevo_rol = intval($_POST['nuevo_rol']);
                
                // No permitir cambiar el rol del usuario actual
                if ($usuario_id === $_SESSION['user_id']) {
                    $mensaje = "No puedes cambiar tu propio rol";
                    $tipo_mensaje = "warning";
                } else {
                    $stmt = $db->prepare("UPDATE usuarios SET is_admin = ? WHERE id = ?");
                    $stmt->bind_param("ii", $nuevo_rol, $usuario_id);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Rol del usuario actualizado exitosamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al actualizar el rol del usuario";
                        $tipo_mensaje = "danger";
                    }
                }
                break;
                
            case 'eliminar':
                $usuario_id = intval($_POST['usuario_id']);
                
                // No permitir eliminar el usuario actual
                if ($usuario_id === $_SESSION['user_id']) {
                    $mensaje = "No puedes eliminar tu propia cuenta";
                    $tipo_mensaje = "warning";
                } else {
                    // Verificar si el usuario tiene pedidos
                    $stmt = $db->prepare("SELECT COUNT(*) as total FROM pedidos WHERE usuario_id = ?");
                    $stmt->bind_param("i", $usuario_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $pedidos_count = $result->fetch_assoc()['total'];
                    
                    if ($pedidos_count > 0) {
                        $mensaje = "No se puede eliminar el usuario porque tiene $pedidos_count pedido(s) asociado(s)";
                        $tipo_mensaje = "danger";
                    } else {
                        $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
                        $stmt->bind_param("i", $usuario_id);
                        
                        if ($stmt->execute()) {
                            $mensaje = "Usuario eliminado exitosamente";
                            $tipo_mensaje = "success";
                        } else {
                            $mensaje = "Error al eliminar el usuario";
                            $tipo_mensaje = "danger";
                        }
                    }
                }
                break;
        }
    }
}

// Obtener filtros
$rol_filtro = $_GET['rol'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Construir consulta con filtros
$where_conditions = [];
$params = [];
$param_types = "";

if (!empty($rol_filtro)) {
    $where_conditions[] = "is_admin = ?";
    $params[] = intval($rol_filtro);
    $param_types .= "i";
}

if (!empty($busqueda)) {
    $where_conditions[] = "(nombre LIKE ? OR email LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $param_types .= "ss";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Obtener usuarios
$query = "SELECT u.*, COUNT(p.id) as pedidos_count 
          FROM usuarios u 
          LEFT JOIN pedidos p ON u.id = p.usuario_id 
          $where_clause 
          GROUP BY u.id 
          ORDER BY u.fecha_registro DESC";

if (!empty($params)) {
    $stmt = $db->prepare($query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $usuarios = $stmt->get_result();
} else {
    $usuarios = $db->query($query);
}

// Obtener estadísticas
$stats = [];
$result = $db->query("SELECT COUNT(*) as total FROM usuarios");
$stats['total'] = $result->fetch_assoc()['total'];

$result = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE is_admin = 1");
$stats['admins'] = $result->fetch_assoc()['total'];

$result = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE is_admin = 0");
$stats['clientes'] = $result->fetch_assoc()['total'];

$result = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE DATE(fecha_registro) = CURDATE()");
$stats['hoy'] = $result->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - Panel de Administración</title>
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
        .role-badge {
            font-size: 0.8em;
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
                            <a class="nav-link" href="productos.php">
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
                            <a class="nav-link active" href="usuarios.php">
                                <i class="fas fa-users me-2"></i>
                                Usuarios
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
                    <h1 class="h2">Gestionar Usuarios</h1>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($mensaje); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $stats['total']; ?></h5>
                                <p class="card-text">Total Usuarios</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-primary">
                            <div class="card-body">
                                <h5 class="card-title text-primary"><?php echo $stats['admins']; ?></h5>
                                <p class="card-text">Administradores</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-success">
                            <div class="card-body">
                                <h5 class="card-title text-success"><?php echo $stats['clientes']; ?></h5>
                                <p class="card-text">Clientes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-info">
                            <div class="card-body">
                                <h5 class="card-title text-info"><?php echo $stats['hoy']; ?></h5>
                                <p class="card-text">Registrados Hoy</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="busqueda" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="busqueda" name="busqueda" 
                                       placeholder="Nombre o email..." value="<?php echo htmlspecialchars($busqueda); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="rol" class="form-label">Rol</label>
                                <select name="rol" id="rol" class="form-select">
                                    <option value="">Todos los roles</option>
                                    <option value="1" <?php echo $rol_filtro === '1' ? 'selected' : ''; ?>>Administradores</option>
                                    <option value="0" <?php echo $rol_filtro === '0' ? 'selected' : ''; ?>>Clientes</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-outline-primary me-2">Filtrar</button>
                                <a href="usuarios.php" class="btn btn-outline-secondary">Limpiar</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de usuarios -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Pedidos</th>
                                        <th>Fecha Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($usuarios->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No hay usuarios disponibles.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $usuario['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
                                                    <?php if ($usuario['id'] === $_SESSION['user_id']): ?>
                                                        <br><small class="text-muted">(Tú)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                                <td>
                                                    <?php if ($usuario['is_admin']): ?>
                                                        <span class="badge bg-primary role-badge">Administrador</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success role-badge">Cliente</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $usuario['pedidos_count']; ?> pedidos</span>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_registro'])); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <?php if ($usuario['id'] !== $_SESSION['user_id']): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                    data-bs-toggle="dropdown">
                                                                <i class="fas fa-cog"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li><h6 class="dropdown-header">Cambiar Rol</h6></li>
                                                                <?php if ($usuario['is_admin']): ?>
                                                                    <li>
                                                                        <form method="POST" style="display: inline;">
                                                                            <input type="hidden" name="action" value="cambiar_rol">
                                                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                                            <input type="hidden" name="nuevo_rol" value="0">
                                                                            <button type="submit" class="dropdown-item">
                                                                                <i class="fas fa-user text-success"></i>
                                                                                Hacer Cliente
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                <?php else: ?>
                                                                    <li>
                                                                        <form method="POST" style="display: inline;">
                                                                            <input type="hidden" name="action" value="cambiar_rol">
                                                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                                            <input type="hidden" name="nuevo_rol" value="1">
                                                                            <button type="submit" class="dropdown-item">
                                                                                <i class="fas fa-user-shield text-primary"></i>
                                                                                Hacer Administrador
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                <?php endif; ?>
                                                                <?php if ($usuario['pedidos_count'] == 0): ?>
                                                                    <li><hr class="dropdown-divider"></li>
                                                                    <li>
                                                                        <form method="POST" style="display: inline;">
                                                                            <input type="hidden" name="action" value="eliminar">
                                                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                                            <button type="submit" class="dropdown-item text-danger" 
                                                                                    onclick="return confirm('¿Estás seguro de que quieres eliminar este usuario?')">
                                                                                <i class="fas fa-trash"></i>
                                                                                Eliminar Usuario
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        <?php else: ?>
                                                            <span class="text-muted">No disponible</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 