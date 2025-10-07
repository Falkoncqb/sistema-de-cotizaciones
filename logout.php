<?php
// Iniciar la sesión para poder acceder a sus variables.
session_start();

// Eliminar todas las variables de la sesión.
$_SESSION = array();

// Destruir la sesión por completo.
session_destroy();

// Redirigir al usuario a la página de inicio de sesión (login.php).
header("location: login.php");
exit;
?>
