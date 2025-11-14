<?php
// --- IMPORTANTE: Escribe aquí la contraseña que quieres usar ---
$mi_contrasena_secreta = "admin123"; // <-- CAMBIA ESTO

$hash = password_hash($mi_contrasena_secreta, PASSWORD_DEFAULT);

echo "Copia y pega este hash en phpMyAdmin:";
echo "<br><br>";
echo "<textarea rows='3' cols='60' readonly>" . $hash . "</textarea>";
?>