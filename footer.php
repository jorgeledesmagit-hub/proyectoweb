<footer class="footer mt-5 py-4 bg-dark text-light">
    <div class="container">
        <div class="row">
            <!-- Información de la Tienda -->
            <div class="col-md-4 mb-4">
                <h5>Mi Tienda Online</h5>
                <p class="text-muted">
                    Tu destino para encontrar los mejores productos al mejor precio.
                    Ofrecemos una amplia selección de productos de alta calidad.
                </p>
                <div class="social-links">
                    <a href="https://www.facebook.com" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://x.com/" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.instagram.com/" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.whatsapp.com/" class="text-light"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>

            <!-- Enlaces Rápidos -->
            <div class="col-md-4 mb-4">
                <h5>Enlaces Rápidos</h5>
                <ul class="list-unstyled">
                    <li><a href="/index.php" class="text-muted">Inicio</a></li>
                    <li><a href="/productos.php" class="text-muted">Productos</a></li>
                    <li><a href="/carrito.php" class="text-muted">Carrito</a></li>
                </ul>
            </div>

            <!-- Información de Contacto -->
            <div class="col-md-4 mb-4">
                <h5>Contacto</h5>
                <ul class="list-unstyled text-muted">
                    <li><i class="fas fa-map-marker-alt me-2"></i> Dirección: Calle Principal #123</li>
                    <li><i class="fas fa-phone me-2"></i> Teléfono: (123) 456-7890</li>
                    <li><i class="fas fa-envelope me-2"></i> Email: info@mitienda.com</li>
                    <li><i class="fas fa-clock me-2"></i> Horario: Lunes a Viernes 9:00 - 18:00</li>
                </ul>
            </div>
        </div>

        <!-- Línea Separadora -->
        <hr class="my-4 bg-light">

        <!-- Copyright -->
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0 text-muted">
                    &copy; <?php echo date('Y'); ?> Mi Tienda Online. Todos los derechos reservados.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <a href="/terminos.php" class="text-muted me-3">Términos y Condiciones</a>
                <a href="/privacidad.php" class="text-muted">Política de Privacidad</a>
            </div>
        </div>
    </div>
</footer>

<style>
.footer {
    background-color: var(--primary-color);
    color: white;
    padding: 40px 0;
    margin-top: 50px;
}

.footer h5 {
    color: white;
    margin-bottom: 20px;
    font-weight: 600;
}

.footer .text-muted {
    color: #adb5bd !important;
}

.footer a {
    text-decoration: none;
    transition: color 0.3s;
}

.footer a:hover {
    color: white !important;
}

.footer .social-links a {
    display: inline-block;
    width: 35px;
    height: 35px;
    line-height: 35px;
    text-align: center;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    transition: all 0.3s;
}

.footer .social-links a:hover {
    background-color: var(--accent-color);
    transform: translateY(-3px);
}

.footer ul li {
    margin-bottom: 10px;
}

.footer hr {
    border-color: rgba(255, 255, 255, 0.1);
}

@media (max-width: 768px) {
    .footer .text-center {
        text-align: center !important;
    }
    
    .footer .text-md-start {
        text-align: center !important;
    }
    
    .footer .text-md-end {
        text-align: center !important;
    }
}
</style>