<?php
require_once '../includes/db.php';
require_once 'auth_check.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté logueado y sea administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar cambios de estado o eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'cambiar_estado') {
        $pedido_id = intval($_POST['pedido_id'] ?? 0);
        $nuevo_estado = $_POST['nuevo_estado'] ?? '';
        
        $estados_validos = ['pendiente', 'procesando', 'enviado', 'entregado', 'cancelado'];
        
        if ($pedido_id > 0 && in_array($nuevo_estado, $estados_validos)) {
            $stmt = $db->prepare("UPDATE ordenes SET estado = ? WHERE id = ?");
            $stmt->bind_param("si", $nuevo_estado, $pedido_id);
            
            if ($stmt->execute()) {
                $mensaje = "Estado del pedido actualizado exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar el estado del pedido: " . $db->error;
                $tipo_mensaje = "danger";
            }
        } else {
            $mensaje = "Estado no válido";
            $tipo_mensaje = "danger";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'eliminar_pedido') {
        $pedido_id = intval($_POST['pedido_id'] ?? 0);

        if ($pedido_id === 0) {
            $mensaje = "ID de pedido inválido para eliminar.";
            $tipo_mensaje = "danger";
        } else {
            $db->begin_transaction();
            try {
                // 1. Obtener detalles del pedido para restaurar el stock
                $stmt = $db->prepare("SELECT producto_id, cantidad FROM detalles_orden WHERE orden_id = ?");
                $stmt->bind_param("i", $pedido_id);
                $stmt->execute();
                $detalles_pedido = $stmt->get_result();

                while ($detalle = $detalles_pedido->fetch_assoc()) {
                    $producto_id = $detalle['producto_id'];
                    $cantidad = $detalle['cantidad'];

                    // Restaurar stock del producto
                    $stmt_update_stock = $db->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
                    $stmt_update_stock->bind_param("ii", $cantidad, $producto_id);
                    if (!$stmt_update_stock->execute()) {
                        throw new Exception('Error al restaurar stock del producto ID: ' . $producto_id);
                    }
                }

                // 2. Eliminar detalles del pedido
                $stmt = $db->prepare("DELETE FROM detalles_orden WHERE orden_id = ?");
                $stmt->bind_param("i", $pedido_id);
                if (!$stmt->execute()) {
                    throw new Exception('Error al eliminar detalles del pedido.');
                }

                // 3. Eliminar el pedido
                $stmt = $db->prepare("DELETE FROM ordenes WHERE id = ?");
                $stmt->bind_param("i", $pedido_id);
                if (!$stmt->execute()) {
                    throw new Exception('Error al eliminar el pedido.');
                }

                $db->commit();
                $mensaje = "Pedido eliminado exitosamente y stock restaurado.";
                $tipo_mensaje = "success";

            } catch (Exception $e) {
                $db->rollback();
                $mensaje = "Error al eliminar pedido: " . $e->getMessage();
                $tipo_mensaje = "danger";
            }
        }
    }
}

// Obtener filtros
$estado_filtro = $_GET['estado'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Construir consulta con filtros
$where_conditions = [];
$params = [];
$param_types = "";

if (!empty($estado_filtro)) {
    $where_conditions[] = "p.estado = ?";
    $params[] = $estado_filtro;
    $param_types .= "s";
}

if (!empty($fecha_desde)) {
    $where_conditions[] = "DATE(p.fecha_creacion) >= ?";
    $params[] = $fecha_desde;
    $param_types .= "s";
}

if (!empty($fecha_hasta)) {
    $where_conditions[] = "DATE(p.fecha_creacion) <= ?";
    $params[] = $fecha_hasta;
    $param_types .= "s";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Obtener pedidos
$query = "SELECT p.*, u.nombre as usuario_nombre, u.email as usuario_email
          FROM ordenes p
          LEFT JOIN usuarios u ON p.usuario_id = u.id
          $where_clause
          ORDER BY p.fecha_creacion DESC";

if (!empty($params)) {
    $stmt = $db->prepare($query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $pedidos = $stmt->get_result();
} else {
    $pedidos = $db->query($query);
}

// Obtener estadísticas
$stats = [];
$result = $db->query("SELECT COUNT(*) as total FROM ordenes");
$stats['total'] = $result->fetch_assoc()['total'];

$result = $db->query("SELECT COUNT(*) as total FROM ordenes WHERE estado = 'pendiente'");
$stats['pendientes'] = $result->fetch_assoc()['total'];

$result = $db->query("SELECT COUNT(*) as total FROM ordenes WHERE estado = 'procesando'");
$stats['procesando'] = $result->fetch_assoc()['total'];

$result = $db->query("SELECT COUNT(*) as total FROM ordenes WHERE estado = 'enviado'");
$stats['enviados'] = $result->fetch_assoc()['total'];

$result = $db->query("SELECT COUNT(*) as total FROM ordenes WHERE estado = 'entregado'");
$stats['entregados'] = $result->fetch_assoc()['total'];

$result = $db->query("SELECT SUM(total) as total FROM ordenes WHERE estado = 'entregado'");
$stats['ventas_totales'] = $result->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Pedidos - Panel de Administración</title>
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
        .status-badge {
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
                            <a class="nav-link active" href="pedidos.php">
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
                    <h1 class="h2">Gestionar Pedidos</h1>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($mensaje); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $stats['total']; ?></h5>
                                <p class="card-text">Total Pedidos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-warning">
                            <div class="card-body">
                                <h5 class="card-title text-warning"><?php echo $stats['pendientes']; ?></h5>
                                <p class="card-text">Pendientes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-info">
                            <div class="card-body">
                                <h5 class="card-title text-info"><?php echo $stats['procesando']; ?></h5>
                                <p class="card-text">Procesando</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-primary">
                            <div class="card-body">
                                <h5 class="card-title text-primary"><?php echo $stats['enviados']; ?></h5>
                                <p class="card-text">Enviados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-success">
                            <div class="card-body">
                                <h5 class="card-title text-success"><?php echo $stats['entregados']; ?></h5>
                                <p class="card-text">Entregados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-success">
                            <div class="card-body">
                                <h5 class="card-title text-success">$<?php echo number_format($stats['ventas_totales'], 2); ?></h5>
                                <p class="card-text">Ventas Totales</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select name="estado" id="estado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="pendiente" <?php echo $estado_filtro === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="procesando" <?php echo $estado_filtro === 'procesando' ? 'selected' : ''; ?>>Procesando</option>
                                    <option value="enviado" <?php echo $estado_filtro === 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                                    <option value="entregado" <?php echo $estado_filtro === 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                                    <option value="cancelado" <?php echo $estado_filtro === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_desde" class="form-label">Fecha Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?php echo $fecha_desde; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-outline-primary me-2">Filtrar</button>
                                <a href="pedidos.php" class="btn btn-outline-secondary">Limpiar</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de pedidos -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Contacto</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($pedidos->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No hay pedidos disponibles.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php while ($pedido = $pedidos->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo $pedido['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></strong>
                                                    <?php if ($pedido['usuario_id']): ?>
                                                        <br><small class="text-muted">Usuario registrado</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($pedido['email_cliente']); ?></div>
                                                    <div><?php echo htmlspecialchars($pedido['telefono']); ?></div>
                                                </td>
                                                <td>$<?php echo number_format($pedido['total'], 2); ?></td>
                                                <td>
                                                    <?php
                                                    $estado_colors = [
                                                        'pendiente' => 'warning',
                                                        'procesando' => 'info',
                                                        'enviado' => 'primary',
                                                        'entregado' => 'success',
                                                        'cancelado' => 'danger'
                                                    ];
                                                    $color = $estado_colors[$pedido['estado']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?php echo $color; ?> status-badge">
                                                        <?php echo ucfirst($pedido['estado']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="verDetalles(<?php echo $pedido['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                                data-bs-toggle="dropdown">
                                                            <i class="fas fa-cog"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><h6 class="dropdown-header">Cambiar Estado</h6></li>
                                                            <?php foreach ($estado_colors as $estado => $color): ?>
                                                                <?php if ($estado !== $pedido['estado']): ?>
                                                                    <li>
                                                                        <form method="POST" style="display: inline;">
                                                                            <input type="hidden" name="action" value="cambiar_estado">
                                                                            <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                                                            <input type="hidden" name="nuevo_estado" value="<?php echo $estado; ?>">
                                                                            <button type="submit" class="dropdown-item">
                                                                                <i class="fas fa-circle text-<?php echo $color; ?>"></i>
                                                                                <?php echo ucfirst($estado); ?>
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este pedido? Esta acción es irreversible y restaurará el stock de los productos.');">
                                                                    <input type="hidden" name="action" value="eliminar_pedido">
                                                                    <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                                                    <button type="submit" class="dropdown-item text-danger">
                                                                        <i class="fas fa-trash-alt me-1"></i>
                                                                        Eliminar Pedido
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
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

    <!-- Modal para ver detalles del pedido -->
    <div class="modal fade" id="detallesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detallesContent">
                    <!-- El contenido se cargará dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verDetalles(pedidoId) {
            // Aquí podrías hacer una petición AJAX para obtener los detalles del pedido
            // Por ahora, mostraremos un mensaje
            document.getElementById('detallesContent').innerHTML = `
                <div class="text-center">
                    <p>Detalles del pedido #${pedidoId}</p>
                    <p class="text-muted">Esta funcionalidad se puede expandir para mostrar los productos del pedido.</p>
                </div>
            `;
            new bootstrap.Modal(document.getElementById('detallesModal')).show();
        }
    </script>
</body>
</html> 