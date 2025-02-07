<?php
include 'db_connection.php';

// Crear Pedido
if (isset($_POST['create'])) {
    $estado = $_POST['estado'];
    $nombre_producto = $_POST['nombre_producto'];
    $cantidad = $_POST['cantidad'];
    $precio = $_POST['precio'];
    $categoria = $_POST['categoria'];
    $id_cliente = $_POST['id_cliente'];

    // Insertar en tabla pedidos
    $sql = "INSERT INTO pedidos (estado, id_cliente) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$estado, $id_cliente]);

    // Obtener el último ID insertado en la tabla pedidos
    $id_pedido = $conn->lastInsertId();

    // Insertar en tabla detalle_pedido
    $sql = "INSERT INTO detalle_pedido (id_pedido, nombre_producto, cantidad, precio, categoria) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_pedido, $nombre_producto, $cantidad, $precio, $categoria]);
}

// Eliminar Pedido
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Eliminar de la tabla pedidos (se elimina en cascada el detalle)
    $sql = "DELETE FROM pedidos WHERE id_pedido = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

// Obtener datos de un pedido para actualizar
$updateData = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];

    // Unir pedidos con detalle_pedido para editar
    $stmt = $conn->prepare("
        SELECT p.id_pedido, p.estado, p.id_cliente, dp.nombre_producto, dp.cantidad, dp.precio, dp.categoria
        FROM pedidos p
        LEFT JOIN detalle_pedido dp ON p.id_pedido = dp.id_pedido
        WHERE p.id_pedido = ?
    ");
    $stmt->execute([$id]);
    $updateData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Actualizar Pedido
if (isset($_POST['update'])) {
    $id = $_POST['id_pedido'];
    $estado = $_POST['estado'];
    $nombre_producto = $_POST['nombre_producto'];
    $cantidad = $_POST['cantidad'];
    $precio = $_POST['precio'];
    $categoria = $_POST['categoria'];
    $id_cliente = $_POST['id_cliente'];

    // Actualizar la tabla pedidos
    $sql = "UPDATE pedidos SET estado = ?, id_cliente = ? WHERE id_pedido = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$estado, $id_cliente, $id]);

    // Actualizar la tabla detalle_pedido
    $sql = "UPDATE detalle_pedido SET nombre_producto = ?, cantidad = ?, precio = ?, categoria = ? WHERE id_pedido = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nombre_producto, $cantidad, $precio, $categoria, $id]);

    header("Location: pedidos_crud.php");
    exit;
}

// Leer Pedidos con detalle
$result = $conn->query("
    SELECT 
        p.id_pedido, p.fecha_hora, p.estado, p.id_cliente, 
        dp.nombre_producto, dp.cantidad, dp.precio, dp.categoria
    FROM pedidos p
    LEFT JOIN detalle_pedido dp ON p.id_pedido = dp.id_pedido
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Pedidos</title>
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
    <h1>CRUD Pedidos</h1>

    <!-- Botón para volver al Dashboard -->
    <a href="dashboard.html" class="dashboard-button">Volver al Dashboard</a>
    <div class="form-container">
        <form method="POST">
            <?php if ($updateData): ?>
                <input type="hidden" name="id_pedido" value="<?= $updateData['id_pedido'] ?>">
                <input type="text" name="nombre_producto" placeholder="Producto" value="<?= htmlspecialchars($updateData['nombre_producto']) ?>" required>
                <input type="number" name="cantidad" placeholder="Cantidad" value="<?= $updateData['cantidad'] ?>" required>
                <input type="number" step="0.01" name="precio" placeholder="Precio" value="<?= $updateData['precio'] ?>" required>
                <select name="estado" required>
                    <option value="Pendiente" <?= $updateData['estado'] == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="Confirmado" <?= $updateData['estado'] == 'Confirmado' ? 'selected' : '' ?>>Confirmado</option>
                    <option value="Cancelado" <?= $updateData['estado'] == 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                </select>
                <input type="text" name="categoria" placeholder="Categoría" value="<?= htmlspecialchars($updateData['categoria']) ?>" required>
                <input type="number" name="id_cliente" placeholder="ID Cliente" value="<?= $updateData['id_cliente'] ?>" required>
                <button type="submit" name="update">Actualizar Pedido</button>
            <?php else: ?>
                <input type="text" name="nombre_producto" placeholder="Producto" required>
                <input type="number" name="cantidad" placeholder="Cantidad" required>
                <input type="number" step="0.01" name="precio" placeholder="Precio" required>
                <select name="estado" required>
                    <option value="Pendiente">Pendiente</option>
                    <option value="Confirmado">Confirmado</option>
                    <option value="Cancelado">Cancelado</option>
                </select>
                <input type="text" name="categoria" placeholder="Categoría" required>
                <input type="number" name="id_cliente" placeholder="ID Cliente" required>
                <button type="submit" name="create">Agregar Pedido</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabla de Pedidos -->
   
    <table>
        <tr>
            <th>ID</th>
            <th>Fecha Hora</th>
            <th>Estado</th>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio</th>
            <th>Categoría</th>
            <th>ID Cliente</th>
            <th>Acciones</th>
        </tr>
        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><?= htmlspecialchars($row['id_pedido']) ?></td>
                <td><?= htmlspecialchars($row['fecha_hora']) ?></td>
                <td><?= htmlspecialchars($row['estado']) ?></td>
                <td><?= isset($row['nombre_producto']) ? htmlspecialchars($row['nombre_producto']) : 'N/A' ?></td>
                <td><?= isset($row['cantidad']) ? htmlspecialchars($row['cantidad']) : 'N/A' ?></td>
                <td><?= isset($row['precio']) ? htmlspecialchars($row['precio']) : 'N/A' ?></td>
                <td><?= isset($row['categoria']) ? htmlspecialchars($row['categoria']) : 'N/A' ?></td>
                <td><?= htmlspecialchars($row['id_cliente']) ?></td>
                <td>
                    <a href="?edit=<?= $row['id_pedido'] ?>">Editar</a>
                    <a href="?delete=<?= $row['id_pedido'] ?>" onclick="return confirm('¿Estás seguro de eliminar este pedido?');">Eliminar</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
