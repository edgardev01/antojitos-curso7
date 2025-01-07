<?php
session_start();
require 'db_connection.php'; // Archivo de conexión a la base de datos

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_cliente'])) {
    die("Error: Debe iniciar sesión para realizar el pago.");
}

// Obtener el total del formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['total'])) {
    $id_cliente = $_SESSION['id_cliente'];
    $total = $_POST['total'];

    try {
        // Insertar el pago en la tabla pagos
        $stmt = $conn->prepare("INSERT INTO pagos (id_cliente, total, estado) VALUES (:id_cliente, :total, 'Completado')");
        $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
        $stmt->bindParam(':total', $total, PDO::PARAM_STR);
        $stmt->execute();
    } catch (PDOException $e) {
        die("Error al realizar el pago: " . $e->getMessage());
    }
} else {
    echo "Error: No se pudo procesar el pago.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Confirmado</title>
    <style>
        /* Estilos Generales */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: #121212;
            color: #f4f4f4;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .confirmation-container {
            background: #1c1c1c;
            border: 2px solid white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            padding: 30px;
            text-align: center;
            max-width: 400px;
        }

        h2 {
            color: #e76f51;
            font-size: 28px;
            margin-bottom: 20px;
        }

        p {
            font-size: 18px;
            margin: 10px 0;
        }

        .total-amount {
            font-size: 24px;
            font-weight: bold;
            color: #f4f4f4;
            background-color: #333333;
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: white;
            color: #121212;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s;
        }

        a:hover {
            background-color: #e76f51;
            transform: scale(1.05);
        }

    </style>
</head>
<body>
    <div class="confirmation-container">
        <h2>¡Pago realizado con éxito!</h2>
        <p>Total pagado:</p>
        <p class="total-amount">$<?php echo number_format($total, 2); ?></p>
        <a href="index.php">Volver al inicio</a>
    </div>
</body>
</html>
