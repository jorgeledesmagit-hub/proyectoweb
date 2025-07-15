<?php
require_once 'includes/db.php';
require_once 'includes/carrito.php';

// Obtener productos
   // Filtrado por categoría
   $where = "";
   $params = [];
   if (isset($_GET['categoria']) && $_GET['categoria'] !== "") {
       $categoria_id = intval($_GET['categoria']);
       $where = "WHERE p.categoria_id = $categoria_id";
   }

   $query = "SELECT p.*, c.nombre as categoria_nombre 
             FROM productos p 
             LEFT JOIN categorias c ON p.categoria_id = c.id 
             $where
             ORDER BY p.fecha_creacion DESC";
   $productos = $db->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Mi Tienda Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #e74c3c;
            --accent-color: #3498db;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background-color: var(--primary-color);
            padding: 1rem 0;
        }

        .navbar-brand {
            color: white !important;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .nav-link {
            color: white !important;
            margin: 0 10px;
        }

        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .footer {
            background-color: var(--primary-color);
            color: white;
            padding: 40px 0;
            margin-top: 50px;
        }
    </style>
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

    <!-- Encabezado de la página -->
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Nuestros Productos</h2>
            <div>
            <a href="../admin/productos.php" class="btn btn-primary">
                    <i class="fas fa-cog"></i> Gestionar Productos
                </a>
                <a href="../admin/upload.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Nuevo Producto
                </a>
            </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form class="d-flex" method="GET">
                    <input type="text" name="buscar" class="form-control me-2" placeholder="Buscar productos...">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            <div class="col-md-6">
                <form class="d-flex justify-content-end" method="GET">
                    <select name="categoria" class="form-select me-2" style="width: auto;">
                        <option value="">Todas las categorías</option>
                        <?php
                        $categorias = $db->query("SELECT * FROM categorias ORDER BY nombre");
                        while ($cat = $categorias->fetch_assoc()) {
                            $selected = (isset($_GET['categoria']) && $_GET['categoria'] == $cat['id']) ? 'selected' : '';
                            echo "<option value='{$cat['id']}' {$selected}>{$cat['nombre']}</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </form>
            </div>
        </div>

        <!-- Productos -->
        <div class="row">
            <?php if ($productos->num_rows > 0): ?>
                <?php while ($producto = $productos->fetch_assoc()): ?>
                <div class="col-md-3 mb-4">
                    <div class="product-card">
                        <?php if (!empty($producto['imagen']) && file_exists($producto['imagen'])): ?>
                            <img src="<?php echo htmlspecialchars('../' . $producto['imagen']); ?>" 
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
                            <form action="agregar_al_carrito.php" method="POST" class="d-flex align-items-center gap-2">
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
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 