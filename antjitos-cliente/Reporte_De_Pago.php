<?php
// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "antojitos2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los valores del formulario
    $id_pedido = $_POST['id_pedido'];  // Suponiendo que el ID del pedido está siendo enviado
    $numero_referencia = $_POST['reference'];
    $fecha_pago = $_POST['date-time'];
    
    // Subir el archivo de comprobante
    $target_dir = "uploads/";  // Directorio donde se almacenarán los archivos
    $target_file = $target_dir . basename($_FILES["proof"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Verificar si el archivo es una imagen real
    if (isset($_POST["submit"])) {
        $check = getimagesize($_FILES["proof"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            echo "El archivo no es una imagen.";
            $uploadOk = 0;
        }
    }
    
    // Verificar el tamaño del archivo
    if ($_FILES["proof"]["size"] > 500000) {
        echo "El archivo es demasiado grande.";
        $uploadOk = 0;
    }

    // Permitir ciertos formatos de archivo
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        echo "Solo se permiten imágenes JPG, JPEG, PNG y GIF.";
        $uploadOk = 0;
    }

    // Verificar si $uploadOk es 0 debido a un error
    if ($uploadOk == 0) {
        echo "El archivo no se subió.";
    } else {
        if (move_uploaded_file($_FILES["proof"]["tmp_name"], $target_file)) {
            echo "El archivo " . basename($_FILES["proof"]["name"]) . " se ha subido.";
            
            // Guardar los datos en la base de datos
            $sql = "INSERT INTO pagos (id_pedido, numero_referencia, fecha_pago, comprobante_pago)
                    VALUES ('$id_pedido', '$numero_referencia', '$fecha_pago', '" . basename($_FILES["proof"]["name"]) . "')";
            
            if ($conn->query($sql) === TRUE) {
                echo "Pago registrado exitosamente.";
            } else {
                echo "Error al registrar el pago: " . $conn->error;
            }
        } else {
            echo "Hubo un error al subir el archivo.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reporte de Pago</title>
  <style>
    /* Estilos generales */
    body {
      font-family: 'Arial', sans-serif;
      background-color: #f5f5f5;
      margin: 0;
      padding: 20px;
      color: #333;
    }

    .container {
      max-width: 600px;
      margin: auto;
      background-color: #ffffff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }

    h1 {
      text-align: center;
      color: #e63946; /* Rojo oscuro */
      margin-bottom: 20px;
    }

    label {
      font-weight: bold;
      margin-top: 10px;
      display: block;
    }

    input[type="text"],
    input[type="datetime-local"],
    input[type="file"] {
      width: 100%;
      padding: 12px;
      margin: 10px 0 20px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 16px;
      box-sizing: border-box;
    }

    input[type="file"] {
      padding: 5px;
    }

    /* Botón estilizado */
    button[type="submit"] {
      background-color: #e63946; /* Rojo */
      color: white;
      padding: 15px 20px;
      border: none;
      border-radius: 5px;
      font-size: 18px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
      width: 100%;
    }

    button[type="submit"]:hover {
      background-color: #d62839; /* Rojo más oscuro */
      transform: scale(1.05);
    }

    button[type="submit"]:active {
      transform: scale(0.98);
      background-color: #c72535; /* Rojo aún más oscuro */
    }

    /* Estilo de texto en pequeño */
    small {
      display: block;
      text-align: center;
      color: #777;
      margin-top: 15px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Reporte de Pago</h1>
    <form action="index.php" method="POST" enctype="multipart/form-data">
      <!-- Campo oculto para enviar el id_pedido -->
      <input type="hidden" name="id_pedido" value="<?php echo $id_pedido; ?>">

      <label for="reference">Número de Referencia</label>
      <input type="text" id="reference" name="reference" placeholder="Ingrese el código único de la transacción" required>

      <label for="date-time">Fecha y Hora</label>
      <input type="datetime-local" id="date-time" name="date-time" required>

      <label for="proof">Comprobante de Pago</label>
      <input type="file" id="proof" name="proof" accept="image/*" required>

      <button type="submit" name="submit" id="submitBtn">Generar Reporte</button>
    </form>
    <small>Por favor, asegúrese de que todos los datos sean correctos antes de enviar.</small>
  </div>

  <script>


function generarReporte() {
    const pedidos = JSON.parse(localStorage.getItem('pedidos'));

    if (pedidos && pedidos.length > 0) {
        const pedido = pedidos[0];

        const pedidoObj = {
            id_pedido: pedido.id_pedido,
            fecha_hora: new Date().toISOString().slice(0, 19).replace('T', ' '),
            estado: 'Confirmado',
            id_cliente: pedido.id_cliente
        };

        const detalles = pedido.detalles.map((detalle, index) => {
            // Mapeo de categorías para coincidir con las esperadas en la base de datos
            let categoriaMapped;
            switch (detalle.categoria.toLowerCase()) {
                case 'tacos':
                    categoriaMapped = 'Taco';
                    break;
                case 'huaraches':
                    categoriaMapped = 'Huarache';
                    break;
                case 'bebida':
                    categoriaMapped = 'Bebida';
                    break;
                default:
                    categoriaMapped = detalle.categoria; // En caso de que sea otra categoría no esperada
            }

            return {
                id_detalle: index + 1,
                id_pedido: pedido.id_pedido,
                producto_id: parseInt(detalle.producto_id, 10),
                nombre_producto: detalle.nombre_producto,
                cantidad: detalle.cantidad,
                precio: parseFloat(detalle.precio).toFixed(2),
                categoria: categoriaMapped
            };
        });

        // Enviar los datos al servidor
        fetch('guardar_pedido.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ pedido: pedidoObj, detalles: detalles })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Reporte generado correctamente');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    } else {
        alert('No se encontraron pedidos en localStorage.');
    }
}

function generarReportePago() {
    const pedidos = JSON.parse(localStorage.getItem('pedidos'));
    const totalPedido = parseFloat(localStorage.getItem('total_pedido')).toFixed(2);
    const pedidoDetalles = JSON.parse(localStorage.getItem('pedido_detalles'));

    if (pedidos && pedidos.length > 0 && totalPedido && pedidoDetalles) {
        const pedido = pedidos[0];

        const pagoObj = {
            id_pedido: pedido.id_pedido,
            id_cliente: pedido.id_cliente,
            total: totalPedido,
            fecha_pago: new Date().toISOString().slice(0, 19).replace('T', ' '),
            estado: 'Completado',
            detalles: pedido.detalles.map(detalle => ({
                referencia: "Sin referencia",
                fecha_hora: new Date().toISOString(),
                comprobante_pago: "Sin comprobante",
                localidad: pedidoDetalles.locality || "No especificada",
                direccion_exacta: pedidoDetalles.address || "No especificada",
                horario_retiro: pedidoDetalles.schedule || "No especificado",
            })),
        };

        console.log("Objeto enviado al servidor:", JSON.stringify(pagoObj, null, 2));

        fetch('guardar_detalle_pagos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(pagoObj),
        })
            .then(response => response.json())
            .then(data => {
                console.log("Respuesta del servidor:", data);
                if (data.status === 'success') {
                    alert('Pago registrado correctamente.');

                    // Limpiar el caché de `localStorage`
                    localStorage.removeItem('pedidos');
                    localStorage.removeItem('total_pedido');
                    localStorage.removeItem('pedido_detalles');

                    // Redirigir al home
                    window.location.href = 'index.php';
                } else {
                    alert('Error al registrar el pago: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error en la solicitud:', error);
            });
    } else {
        alert('Datos insuficientes para registrar el pago.');
    }
}


// // Agregar el evento al botón
// document.getElementById('submitBtn').addEventListener('click', function (event) {
//     event.preventDefault();
//     generarReporte(); // Función existente
//     generarReportePago(); // Nueva funcionalidad para registrar el pago
// });



// Agregar evento al botón de envío
// document.getElementById('submitBtn').addEventListener('click', function(event) {
//     event.preventDefault();
//     generarReportePago();
// });






// // Función para generar el objeto del formulario y localStorage
// function generarObjetoFinal() {
//     const pedidos = JSON.parse(localStorage.getItem('pedidos'));
//     const pedidoDetalles = JSON.parse(localStorage.getItem('pedido_detalles'));

//     if (!pedidos || pedidos.length === 0 || !pedidoDetalles) {
//         alert('No se encontraron pedidos o detalles en localStorage.');
//         return;
//     }

//     const formulario = {
//         id_pedido: document.querySelector('[name="id_pedido"]').value,
//         referencia: document.getElementById('reference').value,
//         fecha_hora: document.getElementById('date-time').value,
//         comprobante_pago: document.getElementById('proof').files[0]?.name || null,
//     };

//     // Unir datos del formulario con los del localStorage
//     const objetoFinal = {
//         ...formulario,
//         localidad: pedidoDetalles.locality,
//         direccion_exacta: pedidoDetalles.address,
//         horario_retiro: pedidoDetalles.schedule,
//         detalles: pedidos[0]?.detalles || []
//     };

//     console.log('Objeto final:', objetoFinal);

//     // Enviar el objeto final al servidor
//     fetch('guardar_detalle_pagos.php', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/json'
//         },
//         body: JSON.stringify(objetoFinal)
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (data.status === 'success') {
//             alert('Detalle de pago registrado correctamente.');
//         } else {
//             alert('Error al registrar el detalle de pago: ' + data.message);
//         }
//     })
//     .catch(error => {
//         console.error('Error:', error);
//     });
// }

// Agregar el evento al botón
document.getElementById('submitBtn').addEventListener('click', function(event) {
    event.preventDefault();
    generarReporte(); // Función existente
    generarReportePago(); // Nueva funcionalidad para registrar el pago

});

  </script>
</body>
</html>