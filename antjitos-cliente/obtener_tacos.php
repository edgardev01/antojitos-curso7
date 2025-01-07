<?php
header('Content-Type: application/json');

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "antojitos2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

$query = "SELECT id AS id, nombre_tipo AS nombre, imagen_url AS imagen, frase1 AS descripcion, precio FROM tacos ORDER BY nombre_tipo ASC";
$result = $conn->query($query);

$productos = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $productos[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'imagen' => $row['imagen'],
            'descripcion' => $row['descripcion'],
            'precio' => $row['precio']
        ];
    }
    echo json_encode(['success' => true, 'productos' => $productos]);
} else {
    echo json_encode(['success' => false, 'message' => 'No se encontraron tacos']);
}

$conn->close();
?>
