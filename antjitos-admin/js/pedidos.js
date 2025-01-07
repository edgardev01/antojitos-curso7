function loadPedidos() {
    fetch('crud_pedidos.php', {
        method: 'POST',
        body: new URLSearchParams({ action: 'read' }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                renderPedidos(data.pedidos);
            } else {
                alert('Error al cargar pedidos');
            }
        });
}

function renderPedidos(pedidos) {
    const pedidosArea = document.getElementById('pedidosArea');
    pedidosArea.innerHTML = `
        <h3>Lista de Pedidos</h3>
        <table border="1">
            <tr>
                <th>ID</th><th>Nombre</th><th>Cantidad</th><th>Precio</th><th>Categoría</th><th>Acciones</th>
            </tr>
            ${pedidos
                .map(
                    (pedido) => `
                <tr>
                    <td>${pedido.id_pedido}</td>
                    <td><input value="${pedido.nombre_producto}" id="nombre_${pedido.id_pedido}"></td>
                    <td><input value="${pedido.cantidad}" id="cantidad_${pedido.id_pedido}"></td>
                    <td><input value="${pedido.precio}" id="precio_${pedido.id_pedido}"></td>
                    <td><input value="${pedido.categoria}" id="categoria_${pedido.id_pedido}"></td>
                    <td>
                        <button onclick="updatePedido(${pedido.id_pedido})">Actualizar</button>
                        <button onclick="deletePedido(${pedido.id_pedido})">Eliminar</button>
                    </td>
                </tr>`
                )
                .join('')}
        </table>
        <h3>Crear Nuevo Pedido</h3>
        <input placeholder="Nombre Producto" id="new_nombre">
        <input placeholder="Cantidad" id="new_cantidad" type="number">
        <input placeholder="Precio" id="new_precio" type="number">
        <input placeholder="Categoría" id="new_categoria">
        <input placeholder="ID Cliente" id="new_id_cliente" type="number">
        <button onclick="createPedido()">Crear Pedido</button>
    `;
}

function createPedido() {
    const formData = new URLSearchParams({
        action: 'create',
        nombre_producto: document.getElementById('new_nombre').value,
        cantidad: document.getElementById('new_cantidad').value,
        precio: document.getElementById('new_precio').value,
        categoria: document.getElementById('new_categoria').value,
        id_cliente: document.getElementById('new_id_cliente').value,
    });

    fetch('crud_pedidos.php', {
        method: 'POST',
        body: formData,
    })
        .then((response) => response.json())
        .then((data) => {
            alert(data.message);
            loadPedidos();
        });
}

function updatePedido(id) {
    const formData = new URLSearchParams({
        action: 'update',
        id_pedido: id,
        nombre_producto: document.getElementById(`nombre_${id}`).value,
        cantidad: document.getElementById(`cantidad_${id}`).value,
        precio: document.getElementById(`precio_${id}`).value,
        categoria: document.getElementById(`categoria_${id}`).value,
    });

    fetch('crud_pedidos.php', {
        method: 'POST',
        body: formData,
    })
        .then((response) => response.json())
        .then((data) => {
            alert(data.message);
            loadPedidos();
        });
}

function deletePedido(id) {
    const formData = new URLSearchParams({
        action: 'delete',
        id_pedido: id,
    });

    fetch('crud_pedidos.php', {
        method: 'POST',
        body: formData,
    })
        .then((response) => response.json())
        .then((data) => {
            alert(data.message);
            loadPedidos();
        });
}
