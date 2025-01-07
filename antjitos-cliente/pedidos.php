<?php
session_start();
require 'db_connection.php'; // Conexión a la base de datos

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_cliente'])) {
    die("Error: Debe iniciar sesión para ver sus pedidos.");
}

$id_cliente = $_SESSION['id_cliente'];

// Consultar los pedidos del usuario con los detalles de los productos
try {
    $stmt = $conn->prepare("
        SELECT p.id_pedido, dp.nombre_producto, dp.cantidad, dp.precio, dp.categoria
        FROM pedidos p
        INNER JOIN detalle_pedido dp ON p.id_pedido = dp.id_pedido
        WHERE p.id_cliente = :id_cliente
    ");
    $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
    $stmt->execute();
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular el total
    $total = 0;
    foreach ($pedidos as $pedido) {
        $total += $pedido['cantidad'] * $pedido['precio'];
    }
} catch (PDOException $e) {
    die("Error al obtener los pedidos: " . $e->getMessage());
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen del Pedido</title>
    <style>

      /* Estilos generales */
      body {
            font-family: 'Arial', sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding: 20px;
            background-color: white; /* Fondo suave */
            margin: 0;
        }


        /* Contenedor principal */
        .container {
            display: flex;
            gap: 20px;
            width: 90%;
            max-width: 1200px;
            margin-top: 80px; /* Ajuste para el header fijo */
            flex-direction: row; /* Cambiar a fila */
            justify-content: space-between; /* Espacio entre productos y total */
        }

        /* Lista de productos */
        .product-list {
            background-color: white;
            border-radius: 12px; /* Bordes más redondeados */
            padding: 20px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
            flex: 1; /* Hacer que la lista de productos ocupe el espacio disponible */
            position: relative; /* Para el elemento decorativo */
        }

        /* Elemento decorativo para los productos */
        .decorative-element {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-image: url('https://via.placeholder.com/1200x200/ff4d4d/ffffff?text=Antojitos+Paradise'); /* Fondo decorativo */
            background-size: cover;
            opacity: 0.05; /* Ligera opacidad para no distraer */
            border-radius: 12px;
            z-index: -1; /* Colocar detrás de los productos */
        }

        /* Elemento de cada producto */
        .product-item {
            border-bottom: 1px solid #ddd;
            padding: 15px 0;
            position: relative;
            transition: transform 0.2s; /* Transición para efecto hover */
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-item:hover {
            transform: scale(1.02); /* Efecto de aumento al pasar el mouse */
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-name {
            font-size: 22px; /* Tamaño de fuente más grande */
            font-weight: bold;
            color: #333; /* Color del texto */
        }

        .product-price {
            font-size: 20px; /* Tamaño de fuente para el precio */
            color: #c8102e; /* Color del precio */
            font-weight: bold;
        }

        .product-details {
            margin-top: 10px;
            padding-left: 15px;
            color: #666;
            font-size: 14px;
        }

        .product-details li {
            margin-bottom: 5px;
        }

        /* Cantidad y botón de eliminar */
        .product-actions {
            display: flex;
            justify-content: space-between;
            align-items: center; /* Centrar verticalmente */
            margin-top: 10px;
        }

        .product-quantity {
            font-size: 18px; /* Tamaño de fuente más grande para cantidad */
            color: #555;
            font-weight: bold; /* Enfatizar cantidad */
        }

        /* Botón de eliminar */
        .delete-button {
            background-color: transparent; /* Sin color de fondo */
            color: #c8102e; /* Color rojo */
            padding: 5px 10px;
            cursor: pointer;
            font-size: 14px;
            border: none; /* Sin borde */
            transition: color 0.2s; /* Transición suave */
        }

        .delete-button:hover {
            color: #a70f25; /* Cambiar color al pasar el mouse */
        }

        /* Costo total */
        .total-cost {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            width: 300px; /* Ancho fijo para la sección de total */
            display: flex;
            flex-direction: column; /* Organizar verticalmente */
            justify-content: space-between; /* Espacio entre total y botón */
        }

        /* Elemento decorativo total */
        .total-decorative {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-image: url('https://via.placeholder.com/600x100/ff4d4d/ffffff?text=Total+a+Pagar'); /* Fondo decorativo */
            background-size: cover;
            opacity: 0.1; /* Ligera opacidad para no distraer */
            z-index: -1; /* Colocar detrás del total */
        }

        .total-cost h3 {
            font-size: 24px;
            color: #c8102e;
            margin-bottom: 10px;
        }

        .total-cost p {
            font-size: 28px; /* Tamaño de fuente más grande para total */
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        /* Botón realizar pedido */
        .order-button {
            background-color: #c8102e; /* Color rojo */
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px; /* Espacio entre el total y el botón */
            transition: background-color 0.2s; /* Transición suave */
            width: 100%; /* Hacer que el botón ocupe todo el ancho */
        }

        .order-button:hover {
            background-color: #a70f25; /* Color más oscuro al pasar el mouse */
        }

                
        header {
            background-color: white;
            width: 90%;

            display: flex;
            justify-content: space-between;
            align-items: center;
      
            
        }


        .logo {
            width: 200px;
            height: auto;
            margin-right: 40px;
        }

        ul li img {
            width: 40px;
            height: 40px;
        }

        nav ul.menu-nav {
            display: flex;
            align-items: center;
        }

        nav ul.menu-nav li {
            margin-right: 50px;
            list-style: none;
            color: black
        }

        nav ul.menu-nav li a {
            color: black;
            text-decoration: none;
        }

        nav ul.menu-nav li a:hover {
            color: gray;
        }

        nav ul.menu-nav li button {
            color: red;
            background-color: transparent;
            border: none;
            cursor: pointer;
            font-size: 20px;
        }

        nav ul.menu-nav li button:hover {
            color: darkred;
        }

        nav ul li a {
            text-decoration: none;
            color: black;
            font-size: 18px;
            padding: 10px 15px;
            transition: color 0.3s;
        }

        nav ul li a:hover {
            color: #9b8178;
        }

        ul li button {
            background: none;
            border: none;
            font: inherit;
            color: inherit;
            cursor: pointer;
            padding: 10px;
            transition: background-color 0.3s, color 0.3s, text-decoration 0.3s;
        }

        ul li button:hover {
            background-color: #ddd;
            color: black;
        }

        ul li button.active {
            color: red;
            text-decoration: underline;
        }
    </style>
</head>
<body>
<header>
    <img class="logo" src="img/Carrusel/Log.png" alt="Logo">
    <nav>
        <ul class="menu-nav">
            <li><a href="index.php">Inicio</a></li>
            <li><a href="menu.php">Menú</a></li>
            <li>
                <?php if (isset($_SESSION['nombre'])): ?>
                    <span style="color: black; font-size:18px ">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                    <a href="pedidos.php">Pedidos</a>
                    <a href="cerrar_sesion.php">Cerrar sesión</a>
                <?php else: ?>
                    <button id="login-button">Iniciar sesión</button>
                <?php endif; ?>
            </li>
        </ul>
    </nav>
</header>

<div class="container">
    <!-- Lista de productos -->
    <div class="product-list">
        <h2>Tus pedidos</h2>
        <div id="productos-container">
            <!-- Aquí se cargarán los productos -->
        </div>
    </div>
    <!-- Total a pagar -->
    <div class="total-cost">
        <h3>Total a Pagar:</h3>
        <p id="total-pagar">$0.00</p>
        <form id="pedidoForm" action="confirmacion_pago.php" method="POST">
            <input type="hidden" name="total" id="totalInput" value="0">
            <button type="button" class="order-button" id="realizarPedidoBtn">Realizar Pedido</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const productosContainer = document.getElementById("productos-container");
        const totalPagarElement = document.getElementById("total-pagar");
        const totalInput = document.getElementById("totalInput");

        // Recuperar los datos de localStorage
        let pedidos = JSON.parse(localStorage.getItem("pedidos")) || [];

        // Función para renderizar los productos
        function renderizarProductos() {
            productosContainer.innerHTML = ""; // Limpiar contenedor
            let total = 0;

            // Si no hay pedidos
            if (pedidos.length === 0) {
                productosContainer.innerHTML = "<p>No tienes pedidos registrados.</p>";
                totalPagarElement.textContent = "$0.00";
                totalInput.value = "0.00";
                localStorage.setItem("total_pedido", "0.00");
                return;
            }

            // Recorrer los pedidos y mostrarlos
            pedidos.forEach((pedido, pedidoIndex) => {
                pedido.detalles.forEach((detalle, detalleIndex) => {
                    const productoItem = document.createElement("div");
                    productoItem.classList.add("product-item");
                    productoItem.innerHTML = `
                        <div class="product-header">
                            <span class="product-name">${detalle.nombre_producto}</span>
                            <span class="product-price">$${(detalle.precio * detalle.cantidad).toFixed(2)}</span>
                        </div>
                        <p>Cantidad: ${detalle.cantidad}</p>
                        <p>Categoría: ${detalle.categoria}</p>
                        <button class="delete-button" data-pedido-index="${pedidoIndex}" data-detalle-index="${detalleIndex}">
                            Eliminar
                        </button>
                    `;
                    productosContainer.appendChild(productoItem);

                    // Sumar al total
                    total += detalle.precio * detalle.cantidad;
                });
            });

            // Mostrar el total
            totalPagarElement.textContent = `$${total.toFixed(2)}`;
            totalInput.value = total.toFixed(2);

            // Guardar el total en el localStorage
            localStorage.setItem("total_pedido", total.toFixed(2));
        }

        // Evento para eliminar productos
        productosContainer.addEventListener("click", function (event) {
            if (event.target.classList.contains("delete-button")) {
                const pedidoIndex = event.target.dataset.pedidoIndex;
                const detalleIndex = event.target.dataset.detalleIndex;

                // Eliminar el detalle seleccionado
                pedidos[pedidoIndex].detalles.splice(detalleIndex, 1);

                // Si el pedido queda vacío, eliminarlo completamente
                if (pedidos[pedidoIndex].detalles.length === 0) {
                    pedidos.splice(pedidoIndex, 1);
                }

                // Guardar los pedidos actualizados en el localStorage
                localStorage.setItem("pedidos", JSON.stringify(pedidos));

                // Volver a renderizar los productos
                renderizarProductos();
            }
        });

        // Redirigir al confirmar pedido
        document.getElementById("realizarPedidoBtn").addEventListener("click", function () {
            window.location.href = "Opciones_Entr.html";
        });

        // Renderizar los productos al cargar la página
        renderizarProductos();
    });
</script>
</body>
</html>