<?php
require_once '../includes/db.php';
require_once 'auth_check.php';

$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'agregar':
                $nombre = trim($_POST['nombre']);
                $descripcion = trim($_POST['descripcion']);
                $precio = floatval($_POST['precio']);
                $stock = intval($_POST['stock']);
                $categoria_id = intval($_POST['categoria_id']);
                
                // Procesar imagen
                $imagen = '';
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
                    $upload_dir = '../uploads/productos/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $filename = uniqid() . '.' . $file_extension;
                        $filepath = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $filepath)) {
                            $imagen = 'uploads/productos/' . $filename;
                        }
                    }
                }
                
                $stmt = $db->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, categoria_id, imagen, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssdiis", $nombre, $descripcion, $precio, $stock, $categoria_id, $imagen);
                
                if ($stmt->execute()) {
                    $mensaje = "Producto agregado exitosamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al agregar el producto";
                    $tipo_mensaje = "danger";
                }
                break;
                
            case 'editar':
                $id = intval($_POST['id']);
                $nombre = trim($_POST['nombre']);
                $descripcion = trim($_POST['descripcion']);
                $precio = floatval($_POST['precio']);
                $stock = intval($_POST['stock']);
                $categoria_id = intval($_POST['categoria_id']);
                
                $stmt = $db->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, categoria_id = ? WHERE id = ?");
                $stmt->bind_param("ssdiii", $nombre, $descripcion, $precio, $stock, $categoria_id, $id);
                
                if ($stmt->execute()) {
                    $mensaje = "Producto actualizado exitosamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al actualizar el producto";
                    $tipo_mensaje = "danger";
                }
                break;
                
            case 'eliminar':
                $id = intval($_POST['id']);
                
                // Obtener la imagen para eliminarla
                $stmt = $db->prepare("SELECT imagen FROM productos WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($producto = $result->fetch_assoc()) {
                    if (!empty($producto['imagen']) && file_exists('../' . $producto['imagen'])) {
                        unlink('../' . $producto['imagen']);
                    }
                }
                
                $stmt = $db->prepare("DELETE FROM productos WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $mensaje = "Producto eliminado exitosamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al eliminar el producto";
                    $tipo_mensaje = "danger";
                }
                break;
        }
    }
}

// Obtener categorías
$categorias = [];
$result = $db->query("SELECT id, nombre FROM categorias ORDER BY nombre");
while ($row = $result->fetch_assoc()) {
    $categorias[] = $row;
}

// Obtener productos
$where_clause = "1=1";
$params = [];
$param_types = "";

if (isset($_GET['stock']) && $_GET['stock'] === 'bajo') {
    $where_clause .= " AND stock < 5";
}

if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
    $where_clause .= " AND categoria_id = ?";
    $params[] = intval($_GET['categoria']);
    $param_types .= "i";
}

$query = "SELECT p.*, c.nombre as categoria_nombre 
          FROM productos p 
          LEFT JOIN categorias c ON p.categoria_id = c.id 
          WHERE $where_clause 
          ORDER BY p.fecha_creacion DESC";

if (!empty($params)) {
    $stmt = $db->prepare($query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $productos = $stmt->get_result();
} else {
    $productos = $db->query($query);
}

// Obtener producto para editar
$producto_editar = null;
if (isset($_GET['editar']) && !empty($_GET['editar'])) {
    $id = intval($_GET['editar']);
    $stmt = $db->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $producto_editar = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Productos - Panel de Administración</title>
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
        .product-image {
            max-width: 50px;
            max-height: 50px;
            object-fit: cover;
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
                            <a class="nav-link active" href="productos.php">
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
                            <a class="nav-link" href="/ProyectoWeb/api/admin/index">
                                <i class="fas fa-home me-2"></i>
                                Volver al panel
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
                    <h1 class="h2">Gestionar Productos</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productoModal">
                            <i class="fas fa-plus"></i> Nuevo Producto
                        </button>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($mensaje); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="categoria" class="form-label">Categoría</label>
                                <select name="categoria" id="categoria" class="form-select">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>" 
                                                <?php echo (isset($_GET['categoria']) && $_GET['categoria'] == $categoria['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="stock" class="form-label">Stock</label>
                                <select name="stock" id="stock" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="bajo" <?php echo (isset($_GET['stock']) && $_GET['stock'] === 'bajo') ? 'selected' : ''; ?>>Stock bajo</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-outline-primary me-2">Filtrar</button>
                                <a href="productos.php" class="btn btn-outline-secondary">Limpiar</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de productos -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Imagen</th>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Precio</th>
                                        <th>Stock</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($producto = $productos->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($producto['imagen']) && file_exists('../' . $producto['imagen'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                                         class="product-image rounded" 
                                                         alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                                <?php else: ?>
                                                    <div class="product-image bg-light d-flex align-items-center justify-content-center rounded">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($producto['descripcion'], 0, 50)); ?>...</small>
                                            </td>
                                            <td><?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?></td>
                                            <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                                            <td>
                                                <span class="badge <?php echo $producto['stock'] < 5 ? 'bg-danger' : ($producto['stock'] < 10 ? 'bg-warning' : 'bg-success'); ?>">
                                                    <?php echo $producto['stock']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($producto['stock'] > 0): ?>
                                                    <span class="badge bg-success">Disponible</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Sin stock</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="editarProducto(<?php echo $producto['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="eliminarProducto(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para agregar/editar producto -->
    <div class="modal fade" id="productoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="/ProyectoWeb/api/admin/productos" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="agregar">
                        <input type="hidden" name="id" id="productoId">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre del Producto</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="precio" class="form-label">Precio</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="stock" class="form-label">Stock</label>
                                            <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="categoria_id" class="form-label">Categoría</label>
                                    <select class="form-select" id="categoria_id" name="categoria_id" required>
                                        <option value="">Seleccionar categoría</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo $categoria['id']; ?>">
                                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="imagen" class="form-label">Imagen</label>
                                    <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                                    <div id="imagenPreview" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="eliminarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que quieres eliminar el producto "<span id="productoNombre"></span>"?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="/ProyectoWeb/api/admin/productos" style="display: inline;">
                        <input type="hidden" name="action" value="eliminar">
                        <input type="hidden" name="id" id="eliminarId">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para editar producto
        function editarProducto(id) {
            // Aquí deberías hacer una petición AJAX para obtener los datos del producto
            // Por ahora, redirigimos a la misma página con el parámetro editar
            window.location.href = 'productos.php?editar=' + id;
        }

        // Función para eliminar producto
        function eliminarProducto(id, nombre) {
            document.getElementById('eliminarId').value = id;
            document.getElementById('productoNombre').textContent = nombre;
            new bootstrap.Modal(document.getElementById('eliminarModal')).show();
        }

        // Preview de imagen
        document.getElementById('imagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagenPreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" style="max-height: 150px;">`;
                }
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        });

        // Cargar datos del producto para editar
        <?php if ($producto_editar): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('modalTitle').textContent = 'Editar Producto';
            document.getElementById('formAction').value = 'editar';
            document.getElementById('productoId').value = '<?php echo $producto_editar['id']; ?>';
            document.getElementById('nombre').value = '<?php echo htmlspecialchars($producto_editar['nombre']); ?>';
            document.getElementById('descripcion').value = '<?php echo htmlspecialchars($producto_editar['descripcion']); ?>';
            document.getElementById('precio').value = '<?php echo $producto_editar['precio']; ?>';
            document.getElementById('stock').value = '<?php echo $producto_editar['stock']; ?>';
            document.getElementById('categoria_id').value = '<?php echo $producto_editar['categoria_id']; ?>';
            
            new bootstrap.Modal(document.getElementById('productoModal')).show();
        });
        <?php endif; ?>
    </script>
</body>
</html> 