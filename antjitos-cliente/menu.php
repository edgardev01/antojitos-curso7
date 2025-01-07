<?php
session_start();

// Enviar `id_cliente` al frontend si está disponible en la sesión
if (isset($_SESSION['id_cliente'])) {
    echo "<script>
        localStorage.setItem('id_cliente', '" . $_SESSION['id_cliente'] . "');
        console.log('ID Cliente almacenado en localStorage:', '" . $_SESSION['id_cliente'] . "');
    </script>";
} else {
    echo "<script>console.error('ID Cliente no encontrado en la sesión');</script>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú</title>
    <link rel="stylesheet" href="css/estiMenu.css">
    <style>
        .menu-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .menu-item {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            width: 200px;
        }
        .menu-item img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        #modal, #modal-bebidas {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        #modal.show, #modal-bebidas.show {
            display: flex;
        }
        .modal-content, .modal-content-bebidas {
            background-color: rgba(0, 0, 0, 0.5);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .close-button, .close-button-bebidas {
            background: none;
            border: none;
            font-size: 24px;
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }

         /* Estilo del modal */
   #modal-bebidas {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8); /* Fondo oscuro y transparente */
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.5s ease;
    }

     #modal-bebidas.show {
        display: flex;
        opacity: 1;
    }

     .modal-content-bebidas {
        background: black;
        padding: 15px;
        border: 2px solid white; /* Borde blanco para resaltar */
        border-radius: 12px;
        width: 60%; /* Aumento de ancho */
         /* Reducido más la altura */
        overflow-y: auto;
        position: relative;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5); /* Sombra sutil */
        transition: transform 0.5s ease-in-out;
        color: white; /* Texto en blanco para contraste */
    }
    
    </style>
</head>
<body>
<header>
        <img class="logo" src="img/Carrusel/Log.png" alt="Logo">
        <nav>
            <ul class="menu-nav">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="menu.php">Menu</a></li>
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
<main>
    <div class="Encabezado">
        <h1 id="bebidas-header" style= "font-size:24px;  cursor: pointer; margin-left: 10%;">Bebidas</h1>
        <h1 id="tacos-header" style= "font-size:24px;  cursor: pointer; margin-left: 10%;">Tacos</h1>
        <h1 id="huaraches-header" style= "font-size:24px;  cursor: pointer; margin-left: 10%;">Huaraches</h1>
    </div>
    <div class="menu-container" id="menu-container"></div>
</main>

<!-- Modal para tacos y huaraches -->
<div id="modal">
    <div class="modal-content">
        <button class="close-button">Cerrar</button>
        <p class="Letra" id="Frase">Pide tu comida</p>  
        <div class="contenidoTacos">
            <img id="imagen-modal" src="" alt="Imagen del producto">
            <div class="details">
                <h2 id="titulo-modal"></h2>
                <h3 id="subtitulo-modal">¡El mejor sabor de México!</h3>
                <p id="precio-taco">Precio: $<span id="precio-modal"></span></p>
                <p id="cantidad-taco">Cantidad: <input type="number" min="1" value="1" id="cantidad-modal"></p>
                <button style="background-color: #D32F2F; color: white; padding: 10px; border-radius: 8px;">Agregar</button>
            </div>
        </div>
        <!-- <div class="ingredientes" id="ingredientes-modal">
            <p>Ingredientes</p>
            <div id="Fila">
                <div class="Primera-columna" id="Primera-columna"></div>
                <div class="Segunda-columna" id="Segunda-columna"></div>
                <div class="Tercera-columna" id="Tercera-columna"></div>
            </div>
        </div> -->
    </div>
</div>

<!-- Modal para bebidas -->
<div id="modal-bebidas">
    <div id="modal-content-bebidas" class="modal-content-bebidas" style="background-color: #000000; color: white; border-radius: 8px; padding: 20px;">
        <div id="modal-header-bebidas" style="display: flex; justify-content: space-between; align-items: center;">
            <h2 id="nombre-bebida" style="color: white; font-size: 18px;">Nombre de la Bebida</h2>
            <button id="close-button-bebidas" class="close-button-bebidas" style="background: none; border: none; font-size: 24px; color: white; cursor: pointer;">&times;</button>
        </div>
        <div id="modal-body-bebidas" style="display: flex; margin-top: 20px;">
            <div id="imagen-bebida" style="width: 30%; display: flex; justify-content: center; align-items: center;">
                <img id="image-bebida" src="" style="width: 150px; height: 150px; border: 2px solid white; border-radius: 8px;">
            </div>
            <div id="contenido-bebida" style="width: 70%; padding-left: 20px; color: white;">
                <div>
                    <p>Precio: $<span id="precio-bebida"></span></p>
                    <label>Cantidad: <input id="input-cantidad-bebida" type="number" min="1" style="width: 60px;"></label>
                </div>
                <p id="descripcion-bebida" style="margin-top: 20px;">Descripción del producto.</p>
                <button id="agregar-bebida" style="background-color: #D32F2F; color: white; padding: 10px; border-radius: 8px;">Agregar</button>
            </div>
        </div>
    </div>
</div>

<script>

document.addEventListener("DOMContentLoaded", () => {
    const menuContainer = document.getElementById('menu-container');
    const modal = document.getElementById('modal');
    const modalBebidas = document.getElementById('modal-bebidas');

    document.querySelector('.close-button').addEventListener('click', () => modal.classList.remove('show'));
    document.querySelector('.close-button-bebidas').addEventListener('click', () => modalBebidas.classList.remove('show'));

    function cargarProductos(endpoint, categoria) {
        menuContainer.innerHTML = `<h2>Cargando ${categoria}...</h2>`;
        fetch(endpoint)
            .then(response => response.json())
            .then(data => {
                menuContainer.innerHTML = '';
                data.productos.forEach(producto => {
                    const item = document.createElement('div');
                    item.className = 'menu-item';
                    item.innerHTML = `
                        <img src="${producto.imagen}" alt="${producto.nombre}">
                        <h3>${producto.nombre}</h3>
                        <p>Precio: $${producto.precio}</p>
                        <button onclick="mostrarDetalles(${producto.id}, '${categoria}')"  style="background-color: #D32F2F; color: white; padding: 10px; border-radius: 8px;">Ver Detalles</button>
                    `;
                    menuContainer.appendChild(item);
                });
            });
    }

    window.mostrarDetalles = (id, categoria) => {
    fetch(`obtener_detalles_producto.php?id=${id}&categoria=${categoria}`)
        .then(response => response.json())
        .then(data => {
            const producto = data.producto;

            if (categoria === 'bebidas') {
                // Mostrar modal bebidas
                document.getElementById('nombre-bebida').textContent = producto.nombre;
                document.getElementById('image-bebida').src = producto.imagen;
                document.getElementById('descripcion-bebida').textContent = producto.descripcion;
                document.getElementById('precio-bebida').textContent = producto.precio;

                document.getElementById('agregar-bebida').onclick = () => {
                    guardarPedido(producto.id, producto.nombre, document.getElementById('input-cantidad-bebida').value, producto.precio, 'Bebida');
                };
                modalBebidas.classList.add('show');
            } else {
                // Mostrar modal tacos/huaraches
                document.getElementById('titulo-modal').textContent = producto.nombre;
                document.getElementById('imagen-modal').src = producto.imagen;
                document.getElementById('precio-modal').textContent = producto.precio;

                document.querySelector('#modal button').onclick = () => {
                    guardarPedido(producto.id, producto.nombre, document.getElementById('cantidad-modal').value, producto.precio, categoria);
                };
                modal.classList.add('show');
            }
        });
};

function guardarPedido(producto_id, nombre, cantidad, precio, categoria) {
    console.log('Preparando para guardar en localStorage:', { producto_id, nombre, cantidad, precio, categoria });

    // Validar si el `id_cliente` está disponible en el localStorage
    const id_cliente = parseInt(localStorage.getItem('id_cliente'));
    if (!id_cliente) {
        alert('No se encontró el ID del cliente en localStorage. Asegúrate de iniciar sesión.');
        console.error('ID Cliente no encontrado en localStorage.');
        return;
    }

    // Obtener pedidos actuales del localStorage o inicializarlo
    let pedidos = JSON.parse(localStorage.getItem('pedidos')) || [];

    // Verificar si ya existe un `id_pedido`
    let id_pedido = pedidos.length > 0 ? pedidos[0].id_pedido : parseInt(Date.now().toString().slice(0, 9));


    // Buscar el pedido actual por `id_pedido`
    let pedidoExistente = pedidos.find(pedido => pedido.id_pedido === id_pedido);

    if (!pedidoExistente) {
        // Si no hay un pedido con el `id_pedido`, crear uno nuevo
        pedidoExistente = {
            id_pedido: id_pedido,
            id_cliente: id_cliente, // Asociar el `id_cliente` actual
            detalles: []
        };
        pedidos.push(pedidoExistente);
    }

    // Verificar si el producto ya existe en los detalles
    const productoExistente = pedidoExistente.detalles.find(detalle => detalle.producto_id === producto_id);
    if (productoExistente) {
        // Si ya existe el producto, actualizar su cantidad
        productoExistente.cantidad += parseInt(cantidad);
    } else {
        // Si no existe, agregarlo como nuevo detalle
        const detalle = {
            producto_id: producto_id,
            nombre_producto: nombre,
            cantidad: parseInt(cantidad),
            precio: parseFloat(precio),
            categoria: categoria
        };
        pedidoExistente.detalles.push(detalle);
    }

    // Guardar los pedidos actualizados en `localStorage`
    localStorage.setItem('pedidos', JSON.stringify(pedidos));

    alert(`Producto añadido al pedido`);
    console.log('Pedidos actuales en localStorage:', pedidos);
}



// Función para simular mostrar pedidos guardados (opcional para debug)
function mostrarPedidosGuardados() {
    const pedidos = JSON.parse(localStorage.getItem('pedidos')) || [];
    console.log('Pedidos en localStorage:', pedidos);
}

    document.getElementById('bebidas-header').addEventListener('click', () => {
        cargarProductos('obtener_bebidas.php', 'bebidas');
    });

    document.getElementById('tacos-header').addEventListener('click', () => {
        cargarProductos('obtener_tacos.php', 'tacos');
    });

    document.getElementById('huaraches-header').addEventListener('click', () => {
        cargarProductos('obtener_huaraches.php', 'huaraches');
    });
});

</script>
</body>
</html>
