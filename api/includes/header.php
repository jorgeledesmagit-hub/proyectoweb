<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener el número de items en el carrito
$total_items = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $total_items += $item['cantidad'];
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
    <a class="navbar-brand" href="../../api/index.php">Mi Tienda Online</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                <a class="nav-link" href="../../api/index.php">Inicio</a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="../../api/productos.php">Productos</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                    <a class="nav-link" href="../../api/mis_pedidos.php">Mis Pedidos</a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center">
                <!-- Carrito de Compras -->
                <a href="../../api/carrito.php" class="btn btn-outline-light me-2 position-relative">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($total_items > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $total_items; ?>
                        </span>
                    <?php endif; ?>
                </a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Usuario logueado -->
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> Mi Cuenta
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../../api/perfil.php">Mi Perfil</a></li>
                        <li><a class="dropdown-item" href="../../api/mis_pedidos.php">Mis Pedidos</a></li>
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../../api/admin/productos.php">Panel Admin</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../api/logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Usuario no logueado -->
                    <a href="../../api/login.php" class="btn btn-outline-light me-2">Iniciar Sesión</a>
                    <a href="../../api/registro.php" class="btn btn-light">Registrarse</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav> 