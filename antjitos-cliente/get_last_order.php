<?php
//session_start();
require 'db_connection.php'; // Conexión a la base de datos

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_cliente'])) {
    die("Error: Debe iniciar sesión para ver el reporte de pago.");
}

$id_cliente = $_SESSION['id_cliente']; // ID del cliente autenticado

// Consultar el último pedido del usuario
try {
    $stmt = $conn->prepare("SELECT id_pedido FROM pedidos WHERE id_cliente = :id_cliente ORDER BY fecha_hora DESC LIMIT 1");
    $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
    $stmt->execute();
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pedido) {
        $id_pedido = $pedido['id_pedido']; // Obtener el ID del pedido
    } else {
        die("Error: No se encontró ningún pedido para este cliente.");
    }
} catch (PDOException $e) {
    die("Error al obtener el pedido: " . $e->getMessage());
}

// Retornar el ID del pedido
return $id_pedido;
?>
