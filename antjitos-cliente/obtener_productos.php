<?php
// Mostrar todos los errores de PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "antojitos2";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => "Conexión fallida: " . $conn->connect_error]));
}

// Obtener los productos y sus detalles
$query_productos = "SELECT p.id as producto_id, p.nombre_producto, tp.nombre_tipo, tp.imagen_url, tp.frase1, tp.precio, tp.cantidad_max
                    FROM producto p
                    JOIN tipo_producto tp ON p.id = tp.producto_id";
$result_productos = $conn->query($query_productos);

$productos = [];
if ($result_productos->num_rows > 0) {
    while ($row = $result_productos->fetch_assoc()) {
        $producto_id = $row['producto_id'];

        // Obtener checkboxes
        $checkboxes = [];
        $checkbox_query = "SELECT nombre_tipo, columna FROM checkbox WHERE tipo_producto_id = ?";
        $stmt = $conn->prepare($checkbox_query);
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $checkbox_result = $stmt->get_result();

        while ($checkbox_row = $checkbox_result->fetch_assoc()) {
            $checkboxes[] = [
                'nombre_tipo' => $checkbox_row['nombre_tipo'],
                'columna' => $checkbox_row['columna']
            ];
        }

        // Obtener comboboxes
        $comboboxes = [];
        $combobox_query = "SELECT tipo, ingredientes, columna FROM combobox WHERE tipo_producto_id = ?";
        $stmt = $conn->prepare($combobox_query);
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $combobox_result = $stmt->get_result();

        while ($combobox_row = $combobox_result->fetch_assoc()) {
            $comboboxes[] = [
                'tipo' => $combobox_row['tipo'],
                'ingredientes' => explode(', ', $combobox_row['ingredientes']),
                'columna' => $combobox_row['columna']
            ];
        }

        $productos[] = [
            'producto_id' => $row['producto_id'],
            'nombre_producto' => $row['nombre_producto'],
            'nombre_tipo' => $row['nombre_tipo'],
            'imagen_url' => $row['imagen_url'],
            'frase1' => $row['frase1'],
            'precio' => $row['precio'],
            'cantidad_max' => $row['cantidad_max'],
            'checkboxes' => $checkboxes,
            'comboboxes' => $comboboxes
        ];
    }
}

// Preparar la respuesta JSON
$response = [
    'success' => true,
    'productos' => $productos,
];

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>
