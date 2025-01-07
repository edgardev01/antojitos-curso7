<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "antojitos2";

// Conexión a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Error al conectar con la base de datos']));
}

// Consultar las categorías
$query = "SELECT id, nombre_producto FROM producto";
$result = $conn->query($query);

$menu = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $menu[] = $row;
    }
}

// Respuesta en formato JSON
echo json_encode(['success' => true, 'menu' => $menu]);

$conn->close();
?>
