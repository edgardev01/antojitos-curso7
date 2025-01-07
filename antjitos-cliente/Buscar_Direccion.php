<?php
// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "antojitos2"; // Cambia el nombre de la base de datos según tu configuración

// Crear la conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Comprobar la conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Consulta SQL para obtener las localidades
$sql = "SELECT * FROM localidades";
$result = $conn->query($sql);

$localidades = array();

// Verificar si hay resultados
if ($result->num_rows > 0) {
    // Guardar los resultados en un array
    while($row = $result->fetch_assoc()) {
        $localidades[] = array(
            "id" => $row["id"],
            "nombre" => $row["nombre"]
        );
    }
}

// Convertir el array en JSON y devolverlo
echo json_encode($localidades);

// Cerrar la conexión
$conn->close();
?>
