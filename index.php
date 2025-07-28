<?php
require_once 'includes/db.php';
require_once 'includes/carrito.php';
// Obtener productos destacados (los más recientes)
$query = "SELECT p.*, c.nombre as categoria_nombre 
          FROM productos p 
          LEFT JOIN categorias c ON p.categoria_id = c.id 
          ORDER BY p.fecha_creacion DESC 
          LIMIT 4";
$productos = $db->query($query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Tienda Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/proyectoweb/assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Mensajes de éxito/error -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo htmlspecialchars($_SESSION['success']);
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo htmlspecialchars($_SESSION['error']);
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1 class="display-4">Bienvenido a Mi Tienda Online</h1>
            <p class="lead">Descubre nuestra selección de productos de alta calidad</p>
            <a href="/proyectoweb/productos.php" class="btn btn-light btn-lg">Ver Productos</a>
        </div>
    </section>

    <!-- Productos Destacados -->
    <section class="container my-5">
        <h2 class="text-center mb-4">Productos Destacados</h2>
        <div class="row">
            <?php if ($productos->num_rows > 0): ?>
                <?php while ($producto = $productos->fetch_assoc()): ?>
                <div class="col-md-3 mb-4">
                    <div class="product-card">
                        <?php if (!empty($producto['imagen']) && file_exists($producto['imagen'])): ?>
                            <img src="/proyectoweb/<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                 class="img-fluid mb-3" 
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <?php else: ?>
                            <div class="no-image mb-3">
                                <i class="fas fa-image fa-3x"></i>
                                <p>Imagen no disponible</p>
                            </div>
                        <?php endif; ?>
                        <h5><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                        <p class="text-muted">$<?php echo number_format($producto['precio'], 2); ?></p>
                        <p class="small text-muted">Categoría: <?php echo htmlspecialchars($producto['categoria_nombre']); ?></p>
                        
                        <?php if ($producto['stock'] > 0): ?>
                            <form action="/proyectoweb/agregar_al_carrito.php" method="POST" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                <input type="number" name="cantidad" value="1" min="1" max="<?php echo $producto['stock']; ?>" 
                                       class="form-control form-control-sm" style="width: 70px;">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="fas fa-cart-plus"></i> Agregar
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-secondary w-100" disabled>
                                <i class="fas fa-times"></i> Sin stock
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-center">No hay productos disponibles en este momento.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
