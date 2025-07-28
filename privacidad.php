<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidad - Mi Tienda Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="mb-4">Política de Privacidad</h1>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h4 mb-3">1. Compromiso con su Privacidad</h2>
                        <p>En Mi Tienda Online, valoramos y protegemos su privacidad. Nos comprometemos a:</p>
                        <ul>
                            <li>No compartir su información personal con terceros</li>
                            <li>No utilizar sus datos para fines diferentes a los de su compra</li>
                            <li>Mantener la confidencialidad de su información</li>
                            <li>Proteger sus datos con las más altas medidas de seguridad</li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h4 mb-3">2. Información que Protegemos</h2>
                        <p>La información que recopilamos se utiliza exclusivamente para:</p>
                        <ul>
                            <li>Procesar y entregar sus pedidos</li>
                            <li>Mantener comunicación sobre su compra</li>
                            <li>Garantizar la seguridad de sus transacciones</li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h4 mb-3">3. Seguridad de sus Datos</h2>
                        <p>Implementamos medidas de seguridad avanzadas para proteger su información:</p>
                        <ul>
                            <li>Sistema de encriptación de datos</li>
                            <li>Protección contra accesos no autorizados</li>
                            <li>Monitoreo constante de seguridad</li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h4 mb-3">4. Uso Limitado de Cookies</h2>
                        <p>Utilizamos cookies únicamente para:</p>
                        <ul>
                            <li>Mantener su sesión de compra activa</li>
                            <li>Mejorar su experiencia de navegación</li>
                        </ul>
                        <p>Puede desactivar las cookies en su navegador en cualquier momento.</p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h4 mb-3">5. Sus Derechos</h2>
                        <p>Usted tiene el derecho de:</p>
                        <ul>
                            <li>Solicitar información sobre sus datos</li>
                            <li>Pedir la eliminación de su información</li>
                            <li>Oponerse al uso de sus datos</li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h4 mb-3">6. Contacto</h2>
                        <p>Para cualquier consulta sobre su privacidad, contáctenos:</p>
                        <ul>
                            <li>Email: privacidad@mitienda.com</li>
                            <li>Teléfono: (123) 456-7890</li>
                        </ul>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Última actualización: <?php echo date('d/m/Y'); ?>
                </div>
            </div>
        </div>
    </div>

    <style>
    .card {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    .h4 {
        color: var(--primary-color);
        font-weight: 600;
    }

    ul {
        padding-left: 1.2rem;
    }

    ul li {
        margin-bottom: 0.5rem;
    }

    .alert-info {
        background-color: #e8f4f8;
        border-color: #b8e2f2;
        color: #0c5460;
    }
    </style>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 