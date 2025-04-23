<?php
// Configuración de la base de datos
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'qr_system';

// Crear conexión con manejo de errores
$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name);

// Verificar conexión
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Establecer charset
mysqli_set_charset($conn, "utf8mb4");

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Habilitar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
