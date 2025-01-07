<?php
include 'db_connection.php';

// Crear Pago
if (isset($_POST['create'])) {
    $id_cliente = $_POST['id_cliente'];
    $total = $_POST['total'];
    $estado = $_POST['estado'];

    $sql = "INSERT INTO pagos (id_cliente, total, estado) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_cliente, $total, $estado]);
}

// Eliminar Pago
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM pagos WHERE id_pago = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

// Obtener datos de un pago para actualizar
$updateData = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM pagos WHERE id_pago = ?");
    $stmt->execute([$id]);
    $updateData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Actualizar Pago
if (isset($_POST['update'])) {
    $id = $_POST['id_pago'];
    $id_cliente = $_POST['id_cliente'];
    $total = $_POST['total'];
    $estado = $_POST['estado'];

    $sql = "UPDATE pagos SET id_cliente = ?, total = ?, estado = ? WHERE id_pago = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_cliente, $total, $estado, $id]);
    header("Location: pagos_crud.php");
    exit;
}

// Leer Pagos
$result = $conn->query("SELECT * FROM pagos");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Pagos</title>
    <style>
        /* Estilos previos */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #121212;
            color: #f4f4f4;
            line-height: 1.6;
        }

        h1 {
            text-align: center;
            margin: 20px 0;
        }

        .form-container {
            background-color: #1c1c1c;
            padding: 20px;
            margin: 20px auto;
            border: 2px solid white;
            border-radius: 5px;
            width: 80%;
            max-width: 600px;
        }

        .form-container input, .form-container select {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #2a2a2a;
            color: #f4f4f4;
        }

        .form-container button {
            background-color: white;
            color: #121212;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s;
        }

        .form-container button:hover {
            background-color: #e76f51;
            transform: scale(1.1);
        }

        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            background-color: #1c1c1c;
            color: #f4f4f4;
        }

        table th {
            background-color: #e76f51;
            color: white;
        }

        table tr:hover {
            background-color: #2a2a2a;
        }

        a {
            text-decoration: none;
            color: white;
            background-color: red;
            padding: 5px 10px;
            border-radius: 3px;
            transition: background-color 0.3s ease;
        }

        a:hover {
            background-color: darkred;
        }

        .dashboard-button {
            display: block;
            margin: 20px auto;
            text-align: center;
            background-color: #e76f51;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            text-decoration: none;
            transition: background-color 0.3s, transform 0.2s;
        }

        .dashboard-button:hover {
            background-color: darkred;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <h1>CRUD Pagos</h1>

    <!-- Botón para volver al Dashboard -->
    <a href="dashboard.html" class="dashboard-button">Volver al Dashboard</a>

    <!-- Formulario Crear/Actualizar Pago -->
    <div class="form-container">
        <form method="POST">
            <?php if ($updateData): ?>
                <input type="hidden" name="id_pago" value="<?= $updateData['id_pago'] ?>">
                <input type="number" name="id_cliente" placeholder="ID Cliente" value="<?= htmlspecialchars($updateData['id_cliente']) ?>" required>
                <input type="number" step="0.01" name="total" placeholder="Total" value="<?= htmlspecialchars($updateData['total']) ?>" required>
                <select name="estado" required>
                    <option value="Pendiente" <?= $updateData['estado'] == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="Completado" <?= $updateData['estado'] == 'Completado' ? 'selected' : '' ?>>Completado</option>
                    <option value="Fallido" <?= $updateData['estado'] == 'Fallido' ? 'selected' : '' ?>>Fallido</option>
                </select>
                <button type="submit" name="update">Actualizar Pago</button>
            <?php else: ?>
                <input type="number" name="id_cliente" placeholder="ID Cliente" required>
                <input type="number" step="0.01" name="total" placeholder="Total" required>
                <select name="estado" required>
                    <option value="Pendiente">Pendiente</option>
                    <option value="Completado">Completado</option>
                    <option value="Fallido">Fallido</option>
                </select>
                <button type="submit" name="create">Agregar Pago</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabla de Pagos -->
    <table>
        <tr>
            <th>ID</th>
            <th>ID Cliente</th>
            <th>Total</th>
            <th>Fecha Pago</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><?= htmlspecialchars($row['id_pago']) ?></td>
                <td><?= htmlspecialchars($row['id_cliente']) ?></td>
                <td><?= htmlspecialchars($row['total']) ?></td>
                <td><?= htmlspecialchars($row['fecha_pago']) ?></td>
                <td><?= htmlspecialchars($row['estado']) ?></td>
                <td>
                    <a href="?edit=<?= $row['id_pago'] ?>">Editar</a>
                    <a href="?delete=<?= $row['id_pago'] ?>" onclick="return confirm('¿Estás seguro de eliminar este pago?');">Eliminar</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
