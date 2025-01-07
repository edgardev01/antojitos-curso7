<?php
// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "antojitos2";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decodificar el JSON recibido
    $inputData = json_decode(file_get_contents('php://input'), true);

    // Validar los datos del pedido
    if (isset($inputData['pedido']) && isset($inputData['detalles'])) {
        $pedido = $inputData['pedido'];
        $detalles = $inputData['detalles'];

        // Insertar datos en la tabla `pedidos`
        $stmtPedido = $conn->prepare("INSERT INTO pedidos (id_pedido, fecha_hora, estado, id_cliente) VALUES (?, ?, ?, ?)");
        $stmtPedido->bind_param("isss", $pedido['id_pedido'], $pedido['fecha_hora'], $pedido['estado'], $pedido['id_cliente']);

        if ($stmtPedido->execute()) {
            // Insertar datos en la tabla `detalle_pedido`
            $stmtDetalle = $conn->prepare("INSERT INTO detalle_pedido (id_detalle, id_pedido, producto_id, nombre_producto, cantidad, precio, categoria) VALUES (?, ?, ?, ?, ?, ?, ?)");

            foreach ($detalles as $detalle) {
                $stmtDetalle->bind_param(
                    "iiisids",
                    $detalle['id_detalle'],
                    $detalle['id_pedido'],
                    $detalle['producto_id'],
                    $detalle['nombre_producto'],
                    $detalle['cantidad'],
                    $detalle['precio'],
                    $detalle['categoria']
                );
                $stmtDetalle->execute();
            }

            echo json_encode(["status" => "success", "message" => "Datos insertados correctamente"]);
        } else {
            // Capturar el error de ejecución
            echo json_encode([
                "status" => "error",
                "message" => "Error al insertar el pedido: " . $stmtPedido->error,
            ]);
        }

        $stmtPedido->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
}

$conn->close();

?>
