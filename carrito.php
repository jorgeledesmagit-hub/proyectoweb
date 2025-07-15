<?php
require_once 'includes/db.php';
require_once 'includes/carrito.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener productos del carrito
$productos_carrito = [];
$total = 0;

if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
    $ids = array_keys($_SESSION['carrito']);
    $query = "SELECT * FROM productos WHERE id IN (" . implode(',', $ids) . ")";
    $result = $db->query($query);
    
    while ($producto = $result->fetch_assoc()) {
        $item_carrito = $_SESSION['carrito'][$producto['id']];
        
        // Manejar tanto arrays como números para compatibilidad
        if (is_array($item_carrito)) {
            $cantidad = $item_carrito['cantidad'];
        } else {
            $cantidad = intval($item_carrito);
        }
        
        $subtotal = $producto['precio'] * $cantidad;
        $total += $subtotal;
        
        $productos_carrito[] = [
            'id' => $producto['id'],
            'nombre' => $producto['nombre'],
            'precio' => $producto['precio'],
            'cantidad' => $cantidad,
            'subtotal' => $subtotal,
            'imagen' => $producto['imagen'],
            'stock' => $producto['stock']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - Mi Tienda Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Carrito de Compras</h2>

        <!-- Mensajes de estado -->
        <?php if (isset($_GET['success'])): ?>
            <?php if ($_GET['success'] === 'producto_eliminado'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Producto eliminado del carrito exitosamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['success'] === 'carrito_vaciado'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Carrito vaciado exitosamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['success'] === 'cantidad_actualizada'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Cantidad actualizada exitosamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['success'] === 'producto_anadido'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Producto añadido al carrito exitosamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <?php if ($_GET['error'] === 'producto_no_especificado'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> No se especificó qué producto eliminar.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['error'] === 'producto_no_encontrado'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> El producto no se encontró en el carrito.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['error'] === 'metodo_no_permitido'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> Método no permitido.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['error'] === 'datos_faltantes'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> Faltan datos requeridos.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['error'] === 'producto_no_existe'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> El producto no existe en la base de datos.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['error'] === 'stock_insuficiente'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> Stock insuficiente para la cantidad solicitada.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (empty($productos_carrito)): ?>
            <div class="alert alert-info">
                <i class="fas fa-shopping-cart"></i> Tu carrito está vacío.
                <a href="productos.php" class="alert-link">Ver productos</a>
            </div>
        <?php else: ?>
            <!-- Botón para vaciar todo el carrito -->
            <div class="mb-3">
                <a href="vaciar_carrito.php" class="btn btn-outline-danger" 
                   onclick="return confirm('¿Estás seguro de que quieres vaciar todo el carrito?')">
                    <i class="fas fa-trash me-2"></i>Vaciar Todo el Carrito
                </a>
            </div>

            <div class="row">
                <!-- Lista de Productos -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <?php foreach ($productos_carrito as $index => $item): ?>
                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-2">
                                        <?php if (!empty($item['imagen']) && file_exists($item['imagen'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['imagen']); ?>" 
                                                 class="img-fluid rounded" 
                                                 alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($item['nombre']); ?></h5>
                                        <p class="text-muted mb-0">$<?php echo number_format($item['precio'], 2); ?> c/u</p>
                                    </div>
                                    <div class="col-md-3">
                                        <form action="actualizar_carrito.php" method="POST" class="d-flex align-items-center">
                                            <input type="hidden" name="producto_id" value="<?php echo $item['id']; ?>">
                                            <input type="number" name="cantidad" value="<?php echo $item['cantidad']; ?>" 
                                                   min="1" max="<?php echo $item['stock']; ?>" 
                                                   class="form-control form-control-sm me-2">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <p class="mb-0 fw-bold">$<?php echo number_format($item['subtotal'], 2); ?></p>
                                        <a href="eliminar_del_carrito.php?id=<?php echo $item['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('¿Estás seguro de que quieres eliminar este producto?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php if ($index < count($productos_carrito) - 1): ?>
                                    <hr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Resumen del Pedido -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Resumen del Pedido</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>$<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Envío:</span>
                                <span>Gratis</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong>$<?php echo number_format($total, 2); ?></strong>
                            </div>
                            
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="checkout.php" class="btn btn-primary w-100">
                                    <i class="fas fa-credit-card"></i> Proceder al Pago
                                </a>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-info-circle"></i> 
                                    Para finalizar la compra, por favor 
                                    <a href="login.php" class="alert-link">inicia sesión</a> o 
                                    <a href="registro.php" class="alert-link">regístrate</a>.
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                                    </button>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#registroModal">
                                        <i class="fas fa-user-plus me-2"></i>Registrarse Rápidamente
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="productos.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Seguir Comprando
                                </a>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#finalizarCompraModal">
                                    <i class="fas fa-shopping-cart me-2"></i>Finalizar Compra
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Finalizar Compra -->
    <div class="modal fade" id="finalizarCompraModal" tabindex="-1" aria-labelledby="finalizarCompraModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="finalizarCompraModalLabel">Finalizar Compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="finalizarCompraForm">
                        <input type="hidden" name="total" value="<?php echo $total; ?>">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" required>
                        </div>
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección de Envío</label>
                            <textarea class="form-control" id="direccion" name="direccion" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="procesarCompra()">
                        <i class="fas fa-check me-2"></i>Confirmar Compra
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Login -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">Iniciar Sesión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm">
                        <div class="mb-3">
                            <label for="loginEmail" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="loginEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="loginPassword" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="loginPassword" name="password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="procesarLogin()">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Registro -->
    <div class="modal fade" id="registroModal" tabindex="-1" aria-labelledby="registroModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registroModalLabel">Registro Rápido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="registroForm">
                        <div class="mb-3">
                            <label for="registroNombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="registroNombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="registroEmail" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="registroEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="registroPassword" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="registroPassword" name="password" minlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label for="registroConfirmarPassword" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="registroConfirmarPassword" name="confirmar_password" minlength="6" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="procesarRegistro()">
                        <i class="fas fa-user-plus me-2"></i>Registrarse
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function procesarCompra() {
        // Validar formulario
        const form = document.getElementById('finalizarCompraForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Mostrar indicador de carga
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
        button.disabled = true;

        // Crear un formulario temporal para enviar la solicitud
        const formData = new FormData(form);
        
        // Enviar los datos del formulario y limpiar el carrito
        fetch('limpiar_carrito.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('¡Gracias por tu compra! Te enviaremos un correo con los detalles de tu pedido.');
                window.location.href = 'index.php';
            } else {
                console.error('Error del servidor:', data);
                alert('Error: ' + (data.message || 'Hubo un error al procesar tu compra. Por favor, intenta nuevamente.'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión: ' + error.message + '\n\nSi el problema persiste, contacta al administrador.');
        })
        .finally(() => {
            // Restaurar botón
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }

    function procesarLogin() {
        const form = document.getElementById('loginForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Iniciando...';
        button.disabled = true;

        const formData = new FormData(form);
        
        fetch('login_rapido.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('¡Sesión iniciada exitosamente!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión. Por favor, intenta nuevamente.');
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }

    function procesarRegistro() {
        const form = document.getElementById('registroForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const password = document.getElementById('registroPassword').value;
        const confirmarPassword = document.getElementById('registroConfirmarPassword').value;

        if (password !== confirmarPassword) {
            alert('Las contraseñas no coinciden');
            return;
        }

        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Registrando...';
        button.disabled = true;

        const formData = new FormData(form);
        
        fetch('registro_rapido.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('¡Usuario registrado exitosamente! Ya puedes finalizar tu compra.');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión. Por favor, intenta nuevamente.');
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
    </script>

    <style>
    .carrito-total {
        font-size: 1.25rem;
        font-weight: bold;
        text-align: right;
        padding: 1rem;
        background-color: #f8f9fa;
        border-radius: 4px;
        margin-top: 1rem;
    }

    .btn-success {
        background-color: var(--accent-color);
        border-color: var(--accent-color);
    }

    .btn-success:hover {
        background-color: #2980b9;
        border-color: #2980b9;
    }

    .modal-content {
        border-radius: 8px;
    }

    .modal-header {
        background-color: var(--primary-color);
        color: white;
        border-radius: 8px 8px 0 0;
    }

    .modal-title {
        font-weight: 600;
    }

    .btn-close {
        filter: brightness(0) invert(1);
    }
    </style>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 