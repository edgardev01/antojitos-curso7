<?php
require 'db_connection.php';

// Registro de Administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_admin'])) {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("CALL crearCuentaUsuario(:nombre, :apellido, :correo, :password)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':password', $password);
        $stmt->execute();

        // Redirigir a la página de dashboard si el registro es exitoso
        header("Location: dashboard.html");
        exit();
    } catch (PDOException $e) {
        echo "Error en el registro: " . $e->getMessage();
    }
}

// Inicio de Sesión de Administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_admin'])) {
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("CALL iniciarSesion(:correo, :password)");
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':password', $password);
        $stmt->execute();

        // Redirigir a la página de dashboard si el inicio de sesión es exitoso
        header("Location: dashboard.html");
        exit();
    } catch (PDOException $e) {
        echo "Error en el inicio de sesión: " . $e->getMessage();
    }
}
?>
