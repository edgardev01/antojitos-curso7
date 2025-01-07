<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/Carrusel.css">
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
                        <span style="color: black; ">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                        <a href="pedidos.php">Pedidos</a>
                        <a href="cerrar_sesion.php">Cerrar sesión</a>
                    <?php else: ?>
                        <button id="login-button">Iniciar sesión</button>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    </header>
    
    <div class="carousel-container">
        <div class="carousel-slide">
            <div class="carousel-content active">
                <div class="carousel-caption">
                    <h2>¡Sumérgete en el Sabor de Nuestros Tacos!</h2>
                    <p>Con cada mordida, nuestros tacos te transportarán a un rincón de México. ¡Haz tu pedido y disfruta de la auténtica experiencia de sabores!</p>
                </div>
                <img src="Img/Carrusel/Tacos_Prece.png" alt="Tacos">
            </div>

            <div class="carousel-content">
                <div class="carousel-caption">
                    <h2>Prueba las Inigualables Gringas</h2>
                    <p>El toque perfecto de carne, queso y tortilla. ¡Una combinación que conquistará tu paladar! Anímate a pedir y disfruta de nuestras gringas.</p>
                </div>
                <img src="Img/Carrusel/Pres_Gringas.png" alt="Gringas">
            </div>

            <div class="carousel-content">
                <div class="carousel-caption">
                    <h2>¿Antojo de Tortas?</h2>
                    <p>Nuestras tortas están cargadas de sabor y frescura. ¡Elige la tuya y deja que cada bocado hable por sí mismo! Realiza tu pedido ahora.</p>
                </div>
                <img src="Img/Carrusel/Pro.png" alt="Tortas">
            </div>
        </div>

        <button class="carousel-button left" onclick="moveSlide(-1)">
            <img src="Img/Carrusel/Izquierda.png" alt="Previous">
        </button>
        <button class="carousel-button right" onclick="moveSlide(1)">
            <img src="Img/Carrusel/Derecha.png" alt="Next">
        </button>

        <div class="thumbnail-container">
            <img src="Img/Carrusel/Tumb1.png" alt="Miniatura Tacos" onclick="showSlide(0)">
            <img src="Img/Carrusel/Tumb1.png" alt="Miniatura Gringas" onclick="showSlide(1)">
            <img src="Img/Carrusel/Tumb1.png" alt="Miniatura Tortas" onclick="showSlide(2)">
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('login-button')?.addEventListener('click', () => {
            window.location.href = 'login.html';
        });


        const slides = document.querySelectorAll('.carousel-content');
        const thumbnails = document.querySelectorAll('.thumbnail-container img');
        let currentSlideIndex = 0;


        function showSlide(index) {

        slides.forEach(slide => slide.classList.remove('active'));
        thumbnails.forEach(thumbnail => thumbnail.classList.remove('active-thumbnail'));


        slides[index].classList.add('active');
        thumbnails[index].classList.add('active-thumbnail');
        currentSlideIndex = index;  
        }

        // Función para avanzar o retroceder en el carrusel
        function moveSlide(n) {
        // Calculamos el nuevo índice con bucle continuo
        let newIndex = (currentSlideIndex + n + slides.length) % slides.length;
        showSlide(newIndex);
        }

        // Event listeners para las miniaturas
        thumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener('click', () => {
            showSlide(index);
        });
        });

        // Mostrar la primera diapositiva al cargar la página
        showSlide(currentSlideIndex);

        // Event listeners para los botones de navegación
        document.querySelector('.carousel-button.left').addEventListener('click', () => moveSlide(-1));
        document.querySelector('.carousel-button.right').addEventListener('click', () => moveSlide(1));
        });

        </script>


</body>
</html>
