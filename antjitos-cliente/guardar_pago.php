<?php
// Configuración de conexión a la base de datos
$host = "localhost"; // Cambiar según la configuración del servidor
$user = "root";      // Cambiar al usuario de tu base de datos
$password = "";      // Cambiar a la contraseña de tu base de datos
$dbname = "antojitos2"; // Cambiar al nombre de tu base de datos

// Crear conexión
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos: ' . $conn->connect_error]));
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Datos no válidos o faltantes']);
    exit;
}

// Validar campos requeridos
$id_cliente = $data['id_cliente'] ?? null;
$total = $data['total'] ?? null;
$fecha_pago = $data['fecha_pago'] ?? null;
$estado = $data['estado'] ?? 'Pendiente';
$id_pedido = $data['id_pedido'] ?? null;
$detalles = $data['detalles'] ?? []; // Detalles del pago

if (!$id_cliente || !$total || !$fecha_pago || !$id_pedido) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan campos requeridos']);
    exit;
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // Insertar el pago en la tabla `pagos`
    $stmt_pago = $conn->prepare("INSERT INTO pagos (id_cliente, total, fecha_pago, estado, id_pedido) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt_pago) {
        throw new Exception("Error al preparar la consulta de pago: " . $conn->error);
    }

    $stmt_pago->bind_param("idssi", $id_cliente, $total, $fecha_pago, $estado, $id_pedido);
    if (!$stmt_pago->execute()) {
        throw new Exception("Error al registrar el pago: " . $stmt_pago->error);
    }

    $id_pago = $conn->insert_id; // Obtener el ID del pago recién insertado

    // Insertar los detalles en la tabla `detalle_pagos`
    if (!empty($detalles)) {
        $stmt_detalle = $conn->prepare("INSERT INTO detalle_pagos (id_pago, id_pedido, referencia, fecha_hora, comprobante_pago, localidad, direccion_exacta, horario_retiro) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt_detalle) {
            throw new Exception("Error al preparar la consulta de detalle: " . $conn->error);
        }

        foreach ($detalles as $detalle) {
            $referencia = $detalle['referencia'] ?? "Sin referencia";
            $detalle_fecha_hora = $detalle['fecha_hora'] ?? date("Y-m-d H:i:s");
            $comprobante_pago = $detalle['comprobante_pago'] ?? "Sin comprobante";
            $localidad = $detalle['localidad'] ?? "No especificada";
            $direccion_exacta = $detalle['direccion_exacta'] ?? "No especificada";
            $horario_retiro = $detalle['horario_retiro'] ?? "No especificado";

            $stmt_detalle->bind_param("iissssss", $id_pago, $id_pedido, $referencia, $detalle_fecha_hora, $comprobante_pago, $localidad, $direccion_exacta, $horario_retiro);
            if (!$stmt_detalle->execute()) {
                throw new Exception("Error al registrar el detalle: " . $stmt_detalle->error);
            }
        }
    }

    // Confirmar la transacción
    $conn->commit();

    echo json_encode(['status' => 'success', 'message' => 'Pago y detalles registrados correctamente']);
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// Cerrar conexiones
$stmt_pago->close();
if (!empty($detalles)) {
    $stmt_detalle->close();
}
$conn->close();
?>
