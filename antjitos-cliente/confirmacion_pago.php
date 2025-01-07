<?php
session_start();
require 'db_connection.php'; // Conexión a la base de datos

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_cliente'])) {
    die("Error: Debe iniciar sesión para ver sus pedidos.");
}

$id_cliente = $_SESSION['id_cliente'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagar Pedido</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .payment-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .payment-container h2 {
            color: red;
            margin-bottom: 20px;
        }
        .total-amount {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }
        .bank-card {
            background-color: #333;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            position: relative;
            font-size: 18px;
        }
        .bank-card::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            width: 50px;
            height: 30px;
            background-color: white; /* Color del logo */
            border-radius: 4px;
        }
        .bank-card .bank-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .bank-card .account-number {
            font-size: 16px;
            margin-top: 10px;
        }
        .next-button {
            background-color: red;
            color: white;
            padding: 15px 20px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .next-button:hover {
            background-color: darkred;
        }
    </style>
</head>
<body>

<div class="payment-container">
    <h2>Total a Pagar</h2>
    <div class="total-amount" id="total_pago">Cargando...</div>
    <div class="bank-card">
        <div class="bank-name">Banco Ejemplo</div>
        <div class="account-number">Número de Cuenta: 1234567890</div>
    </div>
    <button class="next-button" onclick="redirectToReporteDePago()">Siguiente</button>
</div>

<script>
    // Obtener el total de pago desde localStorage
    document.addEventListener("DOMContentLoaded", () => {
        const totalPago = localStorage.getItem("total_pedido");
        const totalElement = document.getElementById("total_pago");

        // Mostrar el total o un mensaje si no está definido
        if (totalPago) {
            totalElement.textContent = `${parseFloat(totalPago).toFixed(2)} MXN`;
        } else {
            totalElement.textContent = "No se pudo obtener el total.";
        }
    });

    // Función para redirigir a la página de Reporte_De_Pago.php
    function redirectToReporteDePago() {
        window.location.href = 'Reporte_De_Pago.php';
    }
</script>

</body>
</html>
