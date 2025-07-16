<?php
require_once 'includes/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Obtener pedidos del usuario
$stmt = $db->prepare("
    SELECT p.*, 
           COUNT(dp.id) as total_productos,
           SUM(dp.cantidad * dp.precio_unitario) as total_pedido
    FROM pedidos p 
    LEFT JOIN detalles_pedido dp ON p.id = dp.pedido_id 
    WHERE p.usuario_id = ? 
    GROUP BY p.id 
    ORDER BY p.fecha DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pedidos = $stmt->get_result();

// Obtener estadísticas del usuario
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_pedidos,
        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado = 'procesado' THEN 1 ELSE 0 END) as procesando,
        SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as enviados,
        SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) as entregados,
        SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados
    FROM pedidos 
    WHERE usuario_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Mi Tienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-badge {
            font-size: 0.8em;
        }
        .order-card {
            transition: transform 0.2s;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-shopping-bag me-2"></i>
                    Mis Pedidos
                </h1>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($mensaje); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card text-center border-primary">
                            <div class="card-body">
                                <h5 class="card-title text-primary"><?php echo $stats['total_pedidos']; ?></h5>
                                <p class="card-text small">Total Pedidos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-warning">
                            <div class="card-body">
                                <h5 class="card-title text-warning"><?php echo $stats['pendientes']; ?></h5>
                                <p class="card-text small">Pendientes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-info">
                            <div class="card-body">
                                <h5 class="card-title text-info"><?php echo $stats['procesando']; ?></h5>
                                <p class="card-text small">Procesando</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-secondary">
                            <div class="card-body">
                                <h5 class="card-title text-secondary"><?php echo $stats['enviados']; ?></h5>
                                <p class="card-text small">Enviados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-success">
                            <div class="card-body">
                                <h5 class="card-title text-success"><?php echo $stats['entregados']; ?></h5>
                                <p class="card-text small">Entregados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-danger">
                            <div class="card-body">
                                <h5 class="card-title text-danger"><?php echo $stats['cancelados']; ?></h5>
                                <p class="card-text small">Cancelados</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de pedidos -->
                <?php if ($pedidos->num_rows === 0): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                        <h3 class="text-muted">No tienes pedidos aún</h3>
                        <p class="text-muted">¡Haz tu primer pedido y comienza a comprar!</p>
                        <a href="/ProyectoWeb/api/index" class="btn btn-outline-primary">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Ir a la Tienda
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php while ($pedido = $pedidos->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card order-card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <i class="fas fa-receipt me-2"></i>
                                            Pedido #<?php echo $pedido['id']; ?>
                                        </h6>
                                        <?php
                                        $status_class = '';
                                        $status_icon = '';
                                        switch ($pedido['estado']) {
                                            case 'pendiente':
                                                $status_class = 'bg-warning';
                                                $status_icon = 'fas fa-clock';
                                                break;
                                            case 'procesado':
                                                $status_class = 'bg-info';
                                                $status_icon = 'fas fa-cogs';
                                                break;
                                            case 'enviado':
                                                $status_class = 'bg-secondary';
                                                $status_icon = 'fas fa-shipping-fast';
                                                break;
                                            case 'entregado':
                                                $status_class = 'bg-success';
                                                $status_icon = 'fas fa-check-circle';
                                                break;
                                            case 'cancelado':
                                                $status_class = 'bg-danger';
                                                $status_icon = 'fas fa-times-circle';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?> status-badge">
                                            <i class="<?php echo $status_icon; ?> me-1"></i>
                                            <?php echo ucfirst($pedido['estado']); ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <small class="text-muted">Fecha:</small><br>
                                                <strong><?php echo date('d/m/Y', strtotime($pedido['fecha'])); ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Productos:</small><br>
                                                <strong><?php echo $pedido['total_productos']; ?> items</strong>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <small class="text-muted">Total:</small><br>
                                                <strong class="text-primary">$<?php echo number_format($pedido['total'], 2); ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Cliente:</small><br>
                                                <strong><?php echo htmlspecialchars($pedido['nombre']); ?></strong>
                                            </div>
                                        </div>
                                        <?php if ($pedido['direccion']): ?>
                                            <div class="mb-2">
                                                <small class="text-muted">Dirección:</small><br>
                                                <small><?php echo htmlspecialchars($pedido['direccion']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#detalleModal<?php echo $pedido['id']; ?>">
                                            <i class="fas fa-eye me-1"></i>
                                            Ver Detalles
                                        </button>
                                        <?php if ($pedido['estado'] === 'pendiente'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger float-end"
                                                    onclick="cancelarPedido(<?php echo $pedido['id']; ?>)">
                                                <i class="fas fa-times me-1"></i>
                                                Cancelar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal de detalles -->
                            <div class="modal fade" id="detalleModal<?php echo $pedido['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                Detalles del Pedido #<?php echo $pedido['id']; ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php
                                            // Obtener detalles del pedido
                                            $stmt = $db->prepare("
                                                SELECT dp.*, p.nombre, p.imagen 
                                                FROM detalles_pedido dp 
                                                JOIN productos p ON dp.producto_id = p.id 
                                                WHERE dp.pedido_id = ?
                                            ");
                                            $stmt->bind_param("i", $pedido['id']);
                                            $stmt->execute();
                                            $detalles = $stmt->get_result();
                                            ?>
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Producto</th>
                                                            <th>Cantidad</th>
                                                            <th>Precio Unit.</th>
                                                            <th>Subtotal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while ($detalle = $detalles->fetch_assoc()): ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <?php if ($detalle['imagen']): ?>
                                                                            <img src="uploads/<?php echo $detalle['imagen']; ?>" 
                                                                                 alt="<?php echo htmlspecialchars($detalle['nombre']); ?>"
                                                                                 class="me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                                        <?php endif; ?>
                                                                        <span><?php echo htmlspecialchars($detalle['nombre']); ?></span>
                                                                    </div>
                                                                </td>
                                                                <td><?php echo $detalle['cantidad']; ?></td>
                                                                <td>$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                                                <td>$<?php echo number_format($detalle['cantidad'] * $detalle['precio_unitario'], 2); ?></td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="table-primary">
                                                            <th colspan="3" class="text-end">Total:</th>
                                                            <th>$<?php echo number_format($pedido['total'], 2); ?></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelarPedido(pedidoId) {
            if (confirm('¿Estás seguro de que quieres cancelar este pedido? Esta acción no se puede deshacer.')) {
                fetch('cancelar_pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'pedido_id=' + pedidoId,
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Respuesta del servidor:', text);
                    window.location.reload(); 
                })
                .catch(error => {
                    console.error('Error al cancelar el pedido:', error);
                    alert('Error al cancelar el pedido: ' + error.message + '. Por favor, intenta de nuevo.');
                });
            }
        }
    </script>
</body>
</html> 