<?php
include 'db_connection.php';

// Crear Cliente
if (isset($_POST['create'])) {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    $sql = "INSERT INTO clientes (nombre, apellido, correo_electronico, password) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nombre, $apellido, $correo, $password]);
}

// Eliminar Cliente
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM clientes WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

// Obtener datos de un cliente para actualizar
$updateData = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
    $stmt->execute([$id]);
    $updateData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Actualizar Cliente
if (isset($_POST['update'])) {
    $id = $_POST['id_cliente'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];

    $sql = "UPDATE clientes SET nombre = ?, apellido = ?, correo_electronico = ? WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nombre, $apellido, $correo, $id]);
    header("Location: clientes_crud.php");
    exit;
}

// Leer Clientes
$result = $conn->query("SELECT * FROM clientes");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Clientes</title>
    <style>
        /* Tus estilos proporcionados */
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

        .form-container input {
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
    <h1>CRUD Clientes</h1>
    <!-- Botón para volver al Dashboard -->
    <a href="dashboard.html" class="dashboard-button">Volver al Dashboard</a>

    <!-- Formulario Crear/Actualizar Cliente -->
    <div class="form-container">
        <form method="POST">
            <?php if ($updateData): ?>
                <input type="hidden" name="id_cliente" value="<?= $updateData['id_cliente'] ?>">
                <input type="text" name="nombre" placeholder="Nombre" value="<?= htmlspecialchars($updateData['nombre']) ?>" required>
                <input type="text" name="apellido" placeholder="Apellido" value="<?= htmlspecialchars($updateData['apellido']) ?>" required>
                <input type="email" name="correo" placeholder="Correo Electrónico" value="<?= htmlspecialchars($updateData['correo_electronico']) ?>" required>
                <button type="submit" name="update">Actualizar Cliente</button>
            <?php else: ?>
                <input type="text" name="nombre" placeholder="Nombre" required>
                <input type="text" name="apellido" placeholder="Apellido" required>
                <input type="email" name="correo" placeholder="Correo Electrónico" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="submit" name="create">Agregar Cliente</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabla de Clientes -->
    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Correo Electrónico</th>
            <th>Acciones</th>
        </tr>
        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><?= htmlspecialchars($row['id_cliente']) ?></td>
                <td><?= htmlspecialchars($row['nombre']) ?></td>
                <td><?= htmlspecialchars($row['apellido']) ?></td>
                <td><?= htmlspecialchars($row['correo_electronico']) ?></td>
                <td>
                    <a href="?edit=<?= $row['id_cliente'] ?>">Editar</a>
                    <a href="?delete=<?= $row['id_cliente'] ?>" onclick="return confirm('¿Estás seguro de eliminar este cliente?');">Eliminar</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
