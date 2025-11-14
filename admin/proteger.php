<?php
// Iniciamos la sesión en CADA página que usemos esto
session_start();

// Verificamos si la variable de sesión 'id_usuario' NO existe
if (!isset($_SESSION['id_usuario'])) {
    
    // Si no existe, significa que el usuario no está logueado.
    // Lo redirigimos al formulario de login.
    header("Location: login.php");
    exit; // Detenemos la ejecución del script
}

// Si la variable SÍ existe, el script continúa y carga el resto de la página.
?>