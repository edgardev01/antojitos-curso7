<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "antojitos2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['categoria'])) {
    echo json_encode(['success' => false, 'error' => 'ID o categoría no especificada']);
    exit;
}

$id = intval($_GET['id']);
$categoria = $_GET['categoria'];
$producto = null;

// Buscar en la tabla especificada
switch ($categoria) {
    case 'bebidas':
        $query = "SELECT id_bebida AS id, nombre, descripcion, imagen, cantidad, 9.00 AS precio FROM bebidas WHERE id_bebida = $id";
        break;
    case 'tacos':
        $query = "SELECT id, nombre_tipo AS nombre, frase1 AS descripcion, imagen_url AS imagen, cantidad_max AS cantidad, precio FROM tacos WHERE id = $id";
        break;
    case 'huaraches':
        $query = "SELECT id, nombre_tipo AS nombre, frase1 AS descripcion, imagen_url AS imagen, cantidad_max AS cantidad, precio FROM huaraches WHERE id = $id";
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Categoría no válida']);
        exit;
}

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $producto = $result->fetch_assoc();
}

if ($producto) {
    echo json_encode(['success' => true, 'producto' => $producto]);
} else {
    echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
}

$conn->close();
?>
