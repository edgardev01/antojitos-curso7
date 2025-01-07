<?php
session_start();
require 'db_connection.php';

// Obtener los productos de la base de datos
try {
    $stmt = $conn->query("SELECT id_producto, nombre_producto, precio FROM productos");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Agregar productos al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idProducto = $_POST['id_producto'];
    $cantidad = $_POST['cantidad'];

    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    $encontrado = false;
    foreach ($_SESSION['carrito'] as &$item) {
        if ($item['id_producto'] == $idProducto) {
            $item['cantidad'] += $cantidad;
            $encontrado = true;
            break;
        }
    }

    if (!$encontrado) {
        $_SESSION['carrito'][] = ['id_producto' => $idProducto, 'cantidad' => $cantidad];
    }

    header('Location: productos.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        nav {
            background-color: #c8102e;
            color: white;
            padding: 10px;
            text-align: center;
        }

        nav a {
            color: white;
            margin: 0 15px;
            text-decoration: none;
        }

        .container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .product {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .product:last-child {
            border-bottom: none;
        }

        .product-name {
            font-size: 18px;
            font-weight: bold;
        }

        .product-price {
            font-size: 16px;
            color: #c8102e;
        }

        .product form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .product input {
            width: 50px;
            text-align: center;
        }

        button {
            background-color: #c8102e;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #a70f25;
        }
    </style>
</head>
<body>
    <nav>
        <a href="index.html">Inicio</a>
        <a href="pedidos.php">Ver Pedido</a>
    </nav>

    <div class="container">
        <h1>Lista de Productos</h1>
        <?php if (!empty($productos)): ?>
            <?php foreach ($productos as $producto): ?>
                <div class="product">
                    <div>
                        <span class="product-name"><?= htmlspecialchars($producto['nombre_producto']) ?></span>
                        <span class="product-price">$<?= number_format($producto['precio'], 2) ?></span>
                    </div>
                    <form method="POST" action="productos.php">
                        <input type="hidden" name="id_producto" value="<?= $producto['id_producto'] ?>">
                        <input type="number" name="cantidad" value="1" min="1">
                        <button type="submit">Agregar</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay productos disponibles.</p>
        <?php endif; ?>
    </div>
</body>
</html>
