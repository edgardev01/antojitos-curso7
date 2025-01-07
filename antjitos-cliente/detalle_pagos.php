<?php
header('Content-Type: application/json');

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "antojitos2";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Verificar si se ha recibido una solicitud POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos enviados desde el frontend
    $data = json_decode(file_get_contents('php://input'), true);

    // Validar los datos principales
    $id_pedido = $data['id_pedido'] ?? null;
    $id_cliente = $data['id_cliente'] ?? null;
    $total = $data['total'] ?? null;
    $fecha_pago = $data['fecha_pago'] ?? null;
    $estado = $data['estado'] ?? 'Pendiente';
    $detalles = $data['detalles'] ?? [];

    // Validar que los datos requeridos estén presentes
    if (!$id_pedido || !$id_cliente || !$total || !$fecha_pago || empty($detalles)) {
        echo json_encode(['status' => 'error', 'message' => 'Datos requeridos faltantes o inválidos.']);
        exit;
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Insertar el pago en la tabla `pagos`
        $stmt_pago = $conn->prepare("INSERT INTO pagos (id_cliente, total, fecha_pago, estado, id_pedido) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt_pago) {
            throw new Exception("Error al preparar la consulta de pagos: " . $conn->error);
        }
        $stmt_pago->bind_param("idssi", $id_cliente, $total, $fecha_pago, $estado, $id_pedido);
        if (!$stmt_pago->execute()) {
            throw new Exception("Error al insertar el pago: " . $stmt_pago->error);
        }
        $id_pago = $conn->insert_id; // Obtener el ID del pago recién insertado

        // Insertar los detalles en la tabla `detalle_pagos`
        $stmt_detalle = $conn->prepare(
            "INSERT INTO detalle_pagos (id_pago, id_pedido, referencia, fecha_hora, comprobante_pago, localidad, direccion_exacta, horario_retiro) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt_detalle) {
            throw new Exception("Error al preparar la consulta de detalles: " . $conn->error);
        }

        // Recorrer los detalles y agregarlos uno por uno
        foreach ($detalles as $detalle) {
            $referencia = $detalle['referencia'] ?? "Sin referencia";
            $fecha_hora = $detalle['fecha_hora'] ?? date("Y-m-d H:i:s");
            $comprobante_pago = $detalle['comprobante_pago'] ?? "Sin comprobante";
            $localidad = $detalle['localidad'] ?? "No especificada";
            $direccion_exacta = $detalle['direccion_exacta'] ?? "No especificada";
            $horario_retiro = $detalle['horario_retiro'] ?? "No especificado";

            $stmt_detalle->bind_param(
                "iissssss",
                $id_pago,
                $id_pedido,
                $referencia,
                $fecha_hora,
                $comprobante_pago,
                $localidad,
                $direccion_exacta,
                $horario_retiro
            );

            if (!$stmt_detalle->execute()) {
                throw new Exception("Error al insertar detalle: " . $stmt_detalle->error);
            }
        }

        // Confirmar la transacción
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Pago y detalles registrados correctamente.']);
    } catch (Exception $e) {
        // Revertir cambios en caso de error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    } finally {
        // Cerrar las conexiones preparadas
        if (isset($stmt_pago)) {
            $stmt_pago->close();
        }
        if (isset($stmt_detalle)) {
            $stmt_detalle->close();
        }
        $conn->close();
    }
}
?>
