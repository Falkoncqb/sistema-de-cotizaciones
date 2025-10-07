<?php
// Iniciar la sesión para poder manejar variables de sesión.
session_start();

// Definir las credenciales correctas.
$usuario_correcto = 'MBSOLUCIONES';
$contrasena_correcta = 'Mbsoluciones2025';

// Verificar si el formulario se ha enviado.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Obtener los datos del formulario.
    $usuario_ingresado = $_POST['usuario'] ?? '';
    $contrasena_ingresada = $_POST['contrasena'] ?? '';

    // Comparar los datos ingresados con las credenciales correctas.
    if ($usuario_ingresado === $usuario_correcto && $contrasena_ingresada === $contrasena_correcta) {
        // Si las credenciales son correctas, establecer las variables de sesión.
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $usuario_correcto;

        // Redirigir al usuario al panel principal.
        header("location: panel.php");
        exit;
    } else {
        // Si las credenciales son incorrectas, redirigir de vuelta al login con un mensaje de error.
        header("location: login.php?error=1");
        exit;
    }
} else {
    // Si alguien intenta acceder a este archivo directamente sin enviar el formulario,
    // simplemente lo redirigimos al login.
    header("location: login.php");
    exit;
}
?>

