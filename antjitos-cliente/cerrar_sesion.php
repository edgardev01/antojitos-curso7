<?php
session_start(); // Iniciar la sesión
session_unset(); // Eliminar todas las variables de sesión
session_destroy(); // Destruir la sesión actual
header('Location: index.php'); // Redirigir a la página principal
exit;
?>