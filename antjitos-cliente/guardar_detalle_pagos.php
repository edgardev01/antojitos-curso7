<?php
// Configuración inicial
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.log'); // Log de errores
error_reporting(E_ALL);
header('Content-Type: application/json');

// Conexión a la base de datos
$host = "localhost";
$user = "root";
$password = "";
$dbname = "antojitos2";

$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Leer datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Datos no válidos o faltantes']);
    exit;
}

// Validar datos principales
$id_cliente = $data['id_cliente'] ?? null;
$total = $data['total'] ?? null;
$fecha_pago = $data['fecha_pago'] ?? null;
$estado = $data['estado'] ?? 'Pendiente';
$id_pedido = $data['id_pedido'] ?? null;
$detalles = $data['detalles'] ?? [];

if (!$id_cliente || !$total || !$fecha_pago || !$id_pedido) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan campos requeridos']);
    exit;
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // Insertar en la tabla pagos
    $stmt_pago = $conn->prepare("INSERT INTO pagos (id_cliente, total, fecha_pago, estado, id_pedido) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt_pago) {
        throw new Exception("Error al preparar la consulta de pago: " . $conn->error);
    }
    $stmt_pago->bind_param("idssi", $id_cliente, $total, $fecha_pago, $estado, $id_pedido);
    if (!$stmt_pago->execute()) {
        throw new Exception("Error al insertar el pago: " . $stmt_pago->error);
    }
    $id_pago = $conn->insert_id;

    // Verificar y evitar duplicados en la tabla detalle_pagos
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM detalle_pagos WHERE id_pedido = ? AND id_pago = ?");
    if (!$stmt_check) {
        throw new Exception("Error al preparar la consulta de duplicados: " . $conn->error);
    }
    $stmt_check->bind_param("ii", $id_pedido, $id_pago);
    $stmt_check->execute();
    $stmt_check->bind_result($exists);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($exists > 0) {
        throw new Exception("Los detalles para este pago ya existen");
    }

    // Insertar en la tabla detalle_pagos
    $stmt_detalle = $conn->prepare("INSERT INTO detalle_pagos (id_pago, id_pedido, referencia, fecha_hora, comprobante_pago, localidad, direccion_exacta, horario_retiro) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt_detalle) {
        throw new Exception("Error al preparar la consulta de detalles: " . $conn->error);
    }

    foreach ($detalles as $detalle) {
        $referencia = $detalle['referencia'] ?? "Sin referencia";
        $fecha_hora = $detalle['fecha_hora'] ?? date("Y-m-d H:i:s");
        $comprobante_pago = $detalle['comprobante_pago'] ?? "Sin comprobante";
        $localidad = $detalle['localidad'] ?? "No especificada";
        $direccion_exacta = $detalle['direccion_exacta'] ?? "No especificada";
        $horario_retiro = $detalle['horario_retiro'] ?? "No especificado";

        $stmt_detalle->bind_param("iissssss", $id_pago, $id_pedido, $referencia, $fecha_hora, $comprobante_pago, $localidad, $direccion_exacta, $horario_retiro);
        if (!$stmt_detalle->execute()) {
            throw new Exception("Error al insertar detalle: " . $stmt_detalle->error);
        }
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Pago y detalles registrados correctamente']);
} catch (Exception $e) {
    $conn->rollback();
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// Cerrar conexiones
$stmt_pago->close();
$stmt_detalle->close();
$conn->close();
