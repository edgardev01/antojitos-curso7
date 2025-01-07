<?php
require 'db_connection.php';

if (isset($_GET['locality'])) {
    $locality_id = intval($_GET['locality']); // Aseguramos que sea un número entero

    try {
        // Consultar las calles según la localidad
        $query = "SELECT id, nombre_calle, numero FROM calles WHERE localidad_id = :locality_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':locality_id', $locality_id, PDO::PARAM_INT);
        $stmt->execute();
        $streets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Verificar si hay resultados
        if (empty($streets)) {
            echo json_encode(['error' => 'No se encontraron calles para esta localidad']);
        } else {
            echo json_encode($streets);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error al obtener datos: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'No locality ID provided']);
}
?>
