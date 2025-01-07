<?php
require 'db_connection.php'; // Archivo con la conexión a la base de datos

// Registro de Cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_client'])) {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    try {
        // Verificar si el correo ya existe
        $stmt = $conn->prepare("SELECT 1 FROM clientes WHERE correo_electronico = :correo");
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        if ($stmt->fetch()) {
            echo "El correo electrónico ya está en uso.";
            exit;
        }

        // Insertar un nuevo cliente
        $stmt = $conn->prepare("INSERT INTO clientes (nombre, apellido, correo_electronico, password) 
                                VALUES (:nombre, :apellido, :correo, :password)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':password', $password); // Aquí deberías usar hash para la contraseña en producción
        $stmt->execute();

        // Obtener el ID del cliente recién registrado
        $id_cliente = $conn->lastInsertId();

        // Iniciar sesión automáticamente
        session_start();
        $_SESSION['id_cliente'] = $id_cliente;
        $_SESSION['nombre'] = $nombre;
        $_SESSION['apellido'] = $apellido;
        $_SESSION['correo'] = $correo;

        // Registrar log de sesión
        $stmt = $conn->prepare("INSERT INTO logs_sesion_clientes (cliente_id, fecha_hora) VALUES (:cliente_id, NOW())");
        $stmt->bindParam(':cliente_id', $id_cliente);
        $stmt->execute();

        // Redirección a index.php
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        echo "Error en el registro: " . $e->getMessage();
    }
}

// Inicio de Sesión de Cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_client'])) {
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    try {
        // Buscar el cliente en la base de datos
        $stmt = $conn->prepare("SELECT id_cliente, nombre, apellido, correo_electronico 
                                FROM clientes 
                                WHERE correo_electronico = :correo AND password = :password");
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':password', $password);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            session_start(); // Iniciar sesión
            $_SESSION['id_cliente'] = $user['id_cliente']; // Guardar el ID del cliente en sesión
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['apellido'] = $user['apellido'];
            $_SESSION['correo'] = $user['correo_electronico'];

            // Registrar log de sesión
            $stmt = $conn->prepare("INSERT INTO logs_sesion_clientes (cliente_id, fecha_hora) VALUES (:cliente_id, NOW())");
            $stmt->bindParam(':cliente_id', $user['id_cliente']);
            $stmt->execute();

            // Redirección a index.php
            header('Location: index.php');
            exit;
        } else {
            echo "Credenciales incorrectas.";
        }
    } catch (PDOException $e) {
        echo "Error en el inicio de sesión: " . $e->getMessage();
    }
}
?>
