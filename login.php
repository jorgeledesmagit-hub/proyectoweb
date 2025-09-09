<?php
require_once 'includes/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya está logueado, redirigir al inicio
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Verificar si hay mensajes de error en la URL
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'no_autenticado':
            $error = 'Debes iniciar sesión para acceder a esta página.';
            break;
        case 'acceso_denegado':
            $error = 'No tienes permisos para acceder a esta página. Solo los administradores pueden acceder al panel de administración.';
            break;
        case 'privilegios_revocados':
            $error = 'Tus privilegios de administrador han sido revocados. Contacta al administrador del sistema.';
            break;
        case 'error_sistema':
            $error = 'Error del sistema. Por favor, intenta nuevamente.';
            break;
        default:
            $error = 'Error desconocido.';
    }
}

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, completa todos los campos.';
    } else {
        // Buscar el usuario en la base de datos
        $stmt = $db->prepare("SELECT id, nombre, email, password, is_admin, telefono, direccion FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            // Verificar la contraseña
            if (password_verify($password, $user['password'])) {
                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nombre'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['user_telefono'] = $user['telefono']; // Guardar teléfono en sesión
                $_SESSION['user_direccion'] = $user['direccion']; // Guardar dirección en sesión
                
                // Verificar si el teléfono o la dirección están vacíos
                if (empty($user['telefono']) || empty($user['direccion'])) {
                    $_SESSION['info'] = 'Por favor, completa tu número de teléfono y dirección de envío.';
                    header('Location: perfil.php');
                    exit;
                }
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Contraseña incorrecta.';
            }
        } else {
            $error = 'No existe una cuenta con ese email.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Mi Tienda Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </h3>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0">¿No tienes cuenta?</p>
                            <a href="registro.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus"></i> Registrarse
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 