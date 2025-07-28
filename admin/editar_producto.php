<?php
session_start();
// Conexión a la base de datos
$db = new mysqli('localhost', 'root', '', 'tienda_online');
if ($db->connect_error) {
    die('Error de conexión: ' . $db->connect_error);
}

// Obtener ID del producto
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'ID de producto inválido';
    header('Location: /proyectoweb/productos.php');
    exit;
}
$producto_id = (int)$_GET['id'];

// Obtener datos del producto
$stmt = $db->prepare('SELECT * FROM productos WHERE id = ?');
$stmt->bind_param('i', $producto_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Producto no encontrado';
    header('Location: /proyectoweb/productos.php');
    exit;
}
$producto = $result->fetch_assoc();

// Obtener categorías
$categorias = $db->query('SELECT * FROM categorias ORDER BY nombre');

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $categoria_id = intval($_POST['categoria_id']);
    $imagen_actual = $producto['imagen'];
    $nueva_imagen = $imagen_actual;

    // Procesar imagen si se sube una nueva
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['imagen'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 5 * 1024 * 1024;
        if (!in_array($file['type'], $allowed_types)) {
            $error = 'Tipo de archivo no permitido. Solo JPG, PNG y WEBP';
        } elseif ($file['size'] > $max_size) {
            $error = 'El archivo es demasiado grande. Máximo 5MB permitido';
        } else {
            $upload_dir = __DIR__ . '/../assets/images/productos';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid('producto-') . '.' . $file_extension;
            $target_path = $upload_dir . '/' . $unique_filename;
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                @chmod($target_path, 0644);
                // Eliminar imagen anterior si existe
                if (!empty($imagen_actual) && file_exists(__DIR__ . '/../' . $imagen_actual)) {
                    @unlink(__DIR__ . '/../' . $imagen_actual);
                }
                $nueva_imagen = 'assets/images/productos/' . $unique_filename;
            } else {
                $error = 'Error al subir la nueva imagen.';
            }
        }
    }

    if (!isset($error)) {
        $stmt = $db->prepare('UPDATE productos SET nombre=?, descripcion=?, precio=?, stock=?, categoria_id=?, imagen=? WHERE id=?');
        $stmt->bind_param('ssdiisi', $nombre, $descripcion, $precio, $stock, $categoria_id, $nueva_imagen, $producto_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Producto actualizado correctamente';
            header('Location: /proyectoweb/productos.php');
            exit;
        } else {
            $error = 'Error al actualizar el producto: ' . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-body">
            <h2 class="mb-4">Editar Producto</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"> <?php echo htmlspecialchars($error); ?> </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3" required><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Precio</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="precio" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($producto['precio']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Stock</label>
                        <input type="number" name="stock" class="form-control" min="0" value="<?php echo htmlspecialchars($producto['stock']); ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Categoría</label>
                    <select name="categoria_id" class="form-select" required>
                        <option value="">Seleccionar categoría</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php if ($producto['categoria_id'] == $cat['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Imagen actual</label><br>
                    <?php if (!empty($producto['imagen']) && file_exists('../' . $producto['imagen'])): ?>
                        <img src="../<?php echo htmlspecialchars($producto['imagen']); ?>" alt="Imagen actual" style="max-width: 120px; max-height: 120px; object-fit: cover;">
                    <?php else: ?>
                        <span class="text-muted">Sin imagen</span>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cambiar imagen</label>
                    <input type="file" name="imagen" class="form-control" accept="image/*">
                    <small class="text-muted">(Opcional, solo si deseas cambiar la imagen)</small>
                </div>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <a href="/proyectoweb/productos.php" class="btn btn-secondary ms-2">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 