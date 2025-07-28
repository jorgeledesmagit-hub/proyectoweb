<?php
require_once '../includes/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté logueado y sea administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

// Obtener pedidos
$pedidos = $db->query("SELECT * FROM pedidos ORDER BY fecha DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Gestionar Pedidos</h1>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pedidos->num_rows === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No hay pedidos disponibles.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($pedido = $pedidos->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $pedido['id']; ?></td>
                                        <td><?php echo htmlspecialchars($pedido['nombre']); ?></td>
                                        <td>$<?php echo number_format($pedido['total'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $pedido['estado'] === 'pendiente' ? 'warning' : 'success'; ?>">
                                                <?php echo ucfirst($pedido['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="index.php" class="btn btn-primary">Volver al Dashboard</a>
        </div>
    </div>
</body>
</html> 