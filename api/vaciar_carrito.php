<?php
require_once 'includes/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vaciar completamente el carrito
$_SESSION['carrito'] = array();

// Redirigir de vuelta al carrito con mensaje de éxito
header('Location: carrito?success=carrito_vaciado');
exit;
?> 