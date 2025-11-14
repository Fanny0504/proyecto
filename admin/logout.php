<?php
// 1. Iniciamos la sesión
session_start();

// 2. Destruimos TODAS las variables de sesión
$_SESSION = array();

// 3. Destruimos la sesión (el archivo en el servidor)
session_destroy();

// 4. Redirigimos al usuario al formulario de login
header("Location: login.php?status=logout_success");
exit;

?>