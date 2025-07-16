<?php
require_once 'includes/db.php';
require_once 'includes/carrito.php';

// Verificar si hay productos en el carrito
$carrito = obtenerCarrito($db);
if (empty($carrito['items'])) {
    header('Location: carrito');
    exit;
}

$error = null;
$success = false;

// Procesar el formulario de checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar datos del formulario
        $required_fields = ['nombre', 'email', 'direccion', 'telefono'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("El campo $field es requerido");
            }
        }

        // Validar email
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El email no es válido");
        }

        // Preparar datos para procesar la orden
        $datos_cliente = array(
            'nombre' => $_POST['nombre'],
            'email' => $_POST['email'],
            'direccion' => $_POST['direccion'],
            'telefono' => $_POST['telefono'],
            'total' => $carrito['total']
        );

        // Procesar la orden
        $orden_id = procesarOrden($db, $datos_cliente);
        $success = true;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container my-4">
        <h1 class="mb-4">Checkout</h1>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <h4 class="alert-heading">¡Gracias por tu compra!</h4>
                <p>Tu orden ha sido procesada exitosamente. El número de orden es: #<?php echo $orden_id; ?></p>
                <p>Te enviaremos un correo electrónico con los detalles de tu compra.</p>
                <hr>
                <p class="mb-0">
                    <a href="/ProyectoWeb/api/index" class="btn btn-primary">Volver a la tienda</a>
                </p>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Información de Contacto</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="checkout-form">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre completo</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" 
                                           value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="direccion" class="form-label">Dirección de envío</label>
                                    <textarea class="form-control" id="direccion" name="direccion" rows="3" required><?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : ''; ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary">Confirmar Pedido</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Resumen del Pedido</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($carrito['items'] as $item): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo htmlspecialchars($item['nombre']); ?> x <?php echo $item['cantidad']; ?></span>
                                    <span>$<?php echo number_format($item['subtotal'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                            
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong>$<?php echo number_format($carrito['total'], 2); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 