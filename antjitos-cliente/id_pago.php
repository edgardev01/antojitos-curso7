<?php
// Iniciar sesión
session_start();
require 'db_connection.php'; // Conexión a la base de datos

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_cliente'])) {
    die("Error: Debe iniciar sesión para realizar esta acción.");
}

$id_cliente = $_SESSION['id_cliente']; // ID del cliente autenticado

// Incluir el archivo para obtener el ID del pedido
$id_pedido = include('get_last_order.php'); // Captura el valor retornado

// Verificar si el formulario ha sido enviado (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id_pedido'])) {
        $id_pedido = $_POST['id_pedido'];

        // Verificar si el pedido pertenece al cliente autenticado
        try {
            $stmt = $conn->prepare("SELECT id_cliente FROM pedidos WHERE id_pedido = :id_pedido");
            $stmt->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
            $stmt->execute();
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pedido && $pedido['id_cliente'] == $id_cliente) {
                // El pedido pertenece al cliente, ahora actualizamos su estado a "Confirmado"
                $updateStmt = $conn->prepare("UPDATE pedidos SET estado = 'Confirmado' WHERE id_pedido = :id_pedido");
                $updateStmt->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
                $updateStmt->execute();

                // Redirigir al index.php después de confirmar el pedido
                header("Location: index.php");
                exit();
            } else {
                die("Error: Este pedido no pertenece a su cuenta.");
            }
        } catch (PDOException $e) {
            die("Error al verificar el pedido: " . $e->getMessage());
        }
    } else {
        die("Error: No se recibió el ID del pedido.");
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Pago</title>
    <style>
        /* Estilos generales */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        h1 {
            text-align: center;
            color: #e63946; /* Rojo oscuro */
            margin-bottom: 20px;
        }

        .order-id {
            font-size: 36px;
            color: white;
            background-color: red;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        button {
            background-color: #e63946;
            color: white;
            padding: 15px 20px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }

        button:hover {
            background-color: #d62839;
            transform: scale(1.05);
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Tu pago ha sido autorizado</h1>
    <p>Tu número de ID de pedido es:</p>
    <div class="order-id"><?php echo $id_pedido; ?></div>
    <form action="index.php" method="POST">
        <input type="hidden" name="id_pedido" value="<?php echo $id_pedido; ?>"> <!-- ID del pedido -->
        <button type="submit">Siguiente</button>
    </form>
</div>

</body>
</html>
