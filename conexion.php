<?php
// Configuración de la conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sistema_cotizaciones";

// Crear la conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar si hay errores en la conexión
if ($conn->connect_error) {
    // Si la conexión falla, detener el script y mostrar un error claro.
    die("Falló la conexión a la base de datos: " . $conn->connect_error);
}

// --- CORRECCIÓN IMPORTANTE ---
// Establecer el conjunto de caracteres a UTF-8 después de conectar.
// Esto asegura que los acentos y caracteres especiales se manejen correctamente.
if (!$conn->set_charset("utf8")) {
    printf("Error al cargar el conjunto de caracteres utf8: %s\n", $conn->error);
    exit();
}
?>

