<?php

/*
 * Archivo de Conexión a la Base de Datos
 * Proyecto: Flor y Hojaldra (v1.0)
 */

// 1. Definimos los parámetros de conexión
$servername = "localhost";
$username = "root";
$password = ""; // Por defecto, XAMPP no tiene contraseña. Déjalo así.
$database = "floryhojaldra"; // El nombre de la BD que creamos.

// 2. Creamos la conexión usando MySQLi (la 'i' es por 'improved')
$conn = new mysqli($servername, $username, $password, $database);

// 3. Verificamos si la conexión falló
if ($conn->connect_error) {
    // Si falla, detenemos la ejecución y mostramos el error.
    die("Error de Conexión: " . $conn->connect_error);
}

// 4. Aseguramos que los datos se comuniquen en UTF-8
// Esto es VITAL para manejar acentos (Hojaldra) y caracteres especiales.
$conn->set_charset("utf8mb4");

// 5. (Opcional por ahora) Imprimimos un éxito si todo salió bien.
// echo "¡Conexión Exitosa!"; 

// No cerramos el' de PHP para evitar espacios en blanco al incluirlo.?>