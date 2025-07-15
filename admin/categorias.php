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

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'agregar':
                $nombre = trim($_POST['nombre']);
                $descripcion = trim($_POST['descripcion']);
                
                if (empty($nombre)) {
                    $mensaje = "El nombre de la categoría es obligatorio";
                    $tipo_mensaje = "danger";
                } else {
                    $stmt = $db->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)");
                    $stmt->bind_param("ss", $nombre, $descripcion);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Categoría agregada exitosamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al agregar la categoría";
                        $tipo_mensaje = "danger";
                    }
                }
                break;
                
            case 'editar':
                $id = intval($_POST['id']);
                $nombre = trim($_POST['nombre']);
                $descripcion = trim($_POST['descripcion']);
                
                if (empty($nombre)) {
                    $mensaje = "El nombre de la categoría es obligatorio";
                    $tipo_mensaje = "danger";
                } else {
                    $stmt = $db->prepare("UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $nombre, $descripcion, $id);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Categoría actualizada exitosamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al actualizar la categoría";
                        $tipo_mensaje = "danger";
                    }
                }
                break;
                
            case 'eliminar':
                $id = intval($_POST['id']);
                
                // Verificar si hay productos en esta categoría
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM productos WHERE categoria_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $productos_count = $result->fetch_assoc()['total'];
                
                if ($productos_count > 0) {
                    $mensaje = "No se puede eliminar la categoría porque tiene $productos_count producto(s) asociado(s)";
                    $tipo_mensaje = "danger";
                } else {
                    $stmt = $db->prepare("DELETE FROM categorias WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Categoría eliminada exitosamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al eliminar la categoría";
                        $tipo_mensaje = "danger";
                    }
                }
                break;
        }
    }
}

// Obtener categorías con conteo de productos
$categorias = [];
$query = "SELECT c.*, COUNT(p.id) as productos_count 
          FROM categorias c 
          LEFT JOIN productos p ON c.id = p.categoria_id 
          GROUP BY c.id 
          ORDER BY c.nombre";
$result = $db->query($query);
while ($row = $result->fetch_assoc()) {
    $categorias[] = $row;
}

// Obtener categoría para editar
$categoria_editar = null;
if (isset($_GET['editar']) && !empty($_GET['editar'])) {
    $id = intval($_GET['editar']);
    $stmt = $db->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $categoria_editar = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Categorías - Panel de Administración</title>
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
                            <a class="nav-link" href="productos.php">
                                <i class="fas fa-box me-2"></i>
                                Productos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="categorias.php">
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
                    <h1 class="h2">Gestionar Categorías</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoriaModal">
                            <i class="fas fa-plus"></i> Nueva Categoría
                        </button>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($mensaje); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Lista de categorías -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Productos</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categorias)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No hay categorías disponibles.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <tr>
                                                <td><?php echo $categoria['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($categoria['nombre']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($categoria['descripcion'] ?: 'Sin descripción'); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $categoria['productos_count']; ?> productos</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="editarCategoria(<?php echo $categoria['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($categoria['productos_count'] == 0): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="eliminarCategoria(<?php echo $categoria['id']; ?>, '<?php echo htmlspecialchars($categoria['nombre']); ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled 
                                                                    title="No se puede eliminar porque tiene productos asociados">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para agregar/editar categoría -->
    <div class="modal fade" id="categoriaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nueva Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="agregar">
                        <input type="hidden" name="id" id="categoriaId">
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la Categoría</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
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
                    <p>¿Estás seguro de que quieres eliminar la categoría "<span id="categoriaNombre"></span>"?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
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
        // Función para editar categoría
        function editarCategoria(id) {
            window.location.href = 'categorias.php?editar=' + id;
        }

        // Función para eliminar categoría
        function eliminarCategoria(id, nombre) {
            document.getElementById('eliminarId').value = id;
            document.getElementById('categoriaNombre').textContent = nombre;
            new bootstrap.Modal(document.getElementById('eliminarModal')).show();
        }

        // Cargar datos de la categoría para editar
        <?php if ($categoria_editar): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('modalTitle').textContent = 'Editar Categoría';
            document.getElementById('formAction').value = 'editar';
            document.getElementById('categoriaId').value = '<?php echo $categoria_editar['id']; ?>';
            document.getElementById('nombre').value = '<?php echo htmlspecialchars($categoria_editar['nombre']); ?>';
            document.getElementById('descripcion').value = '<?php echo htmlspecialchars($categoria_editar['descripcion']); ?>';
            
            new bootstrap.Modal(document.getElementById('categoriaModal')).show();
        });
        <?php endif; ?>
    </script>
</body>
</html> 