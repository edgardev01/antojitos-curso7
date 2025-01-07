<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "antojitos"; // Cambia al nombre real de tu base de datos

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Error de conexión: " . $conn->connect_error]));
}

$producto_id = isset($_GET['producto_id']) ? intval($_GET['producto_id']) : 0;

if ($producto_id > 0) {
    $sql = "SELECT nombre_tipo, imagen_url, frase1, precio, cantidad_max FROM tipo_producto WHERE producto_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $tipos = [];
    while ($row = $result->fetch_assoc()) {
        $tipos[] = $row;
    }

    // Depuración: Muestra la consulta y los resultados
    echo json_encode([
        "success" => true,
        "query" => $sql,
        "producto_id" => $producto_id,
        "tipos" => $tipos
    ]);
} else {
    echo json_encode(["success" => false, "error" => "ID de producto inválido.", "producto_id" => $producto_id]);
}

$conn->close();
?>
