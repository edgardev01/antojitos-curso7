-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-01-2025 a las 20:21:48
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `antojitos2`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `crearCuentaUsuario` (IN `nombre` VARCHAR(255), IN `apellido` VARCHAR(255), IN `correo_electronico` VARCHAR(255), IN `password` VARCHAR(255))   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verificar si el correo electrónico ya está en uso
    IF EXISTS (SELECT 1 FROM usuarios WHERE correo_electronico = correo_electronico) 
    THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'El correo electrónico ya está en uso';
        ROLLBACK;
    ELSE
        INSERT INTO usuarios (nombre, apellido, correo_electronico, password) 
        VALUES (nombre, apellido, correo_electronico, password);
        COMMIT;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `iniciarSesion` (IN `correo` VARCHAR(255), IN `contrasena` VARCHAR(255))   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error durante el inicio de sesión';
    END;

    START TRANSACTION;

    -- Verificar credenciales
    IF EXISTS (SELECT 1 FROM usuarios WHERE correo_electronico = correo AND password = contrasena) THEN

        -- Paso 2: Registrar log de sesión
        INSERT INTO logs_sesion (usuario_id, fecha_hora)
        VALUES ((SELECT id FROM usuarios WHERE correo_electronico = correo), NOW());

        -- Paso 3: Actualizar último inicio en `estado_inicio`
        INSERT INTO estado_inicio (usuario_id, ultimo_inicio)
        VALUES ((SELECT id FROM usuarios WHERE correo_electronico = correo), NOW())
        ON DUPLICATE KEY UPDATE ultimo_inicio = VALUES(ultimo_inicio);

    ELSE
        -- Si las credenciales son incorrectas
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Credenciales incorrectas';
    END IF;

    -- Confirmar cambios
    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `iniciarSesionCliente` (IN `correo` VARCHAR(255), IN `contrasena` VARCHAR(255))   BEGIN
    DECLARE cliente_id INT;

    -- Verificar credenciales
    SELECT id_cliente INTO cliente_id
    FROM clientes
    WHERE correo_electronico = correo AND password = contrasena;

    IF cliente_id IS NULL THEN
        -- Devolver una fila con un valor de control
        SELECT NULL AS nombre, NULL AS apellido, NULL AS correo_electronico;
    ELSE
        -- Registrar log de sesión
        INSERT INTO logs_sesion_clientes (cliente_id, fecha_hora)
        VALUES (cliente_id, NOW());

        -- Devolver los datos del cliente
        SELECT nombre, apellido, correo_electronico
        FROM clientes
        WHERE id_cliente = cliente_id;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrarCliente` (IN `nombre_param` VARCHAR(255), IN `apellido_param` VARCHAR(255), IN `correo_param` VARCHAR(255), IN `password_param` VARCHAR(255))   BEGIN
    -- Declaración de variables
    DECLARE resultado_validacion VARCHAR(255);

    -- Validar la contraseña
    SET resultado_validacion = validar_contraseña(password_param);
    IF resultado_validacion != 'La contraseña es válida' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = resultado_validacion;
    END IF;

    -- Verificar si el correo ya existe
    IF EXISTS (SELECT 1 FROM clientes WHERE correo_electronico = correo_param) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El correo electrónico ya está en uso';
    ELSE
        -- Insertar al cliente
        INSERT INTO clientes (nombre, apellido, correo_electronico, password)
        VALUES (nombre_param, apellido_param, correo_param, password_param);
    END IF;
END$$

--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `calcular_total_pedido` (`id_pedido` INT) RETURNS DECIMAL(10,2) DETERMINISTIC BEGIN
    DECLARE total DECIMAL(10,2);
    
    -- Sumar los precios de tacos, huaraches y bebidas asociados al pedido
    SELECT SUM(precio_total) INTO total
    FROM (
        -- Calcular total de tacos
        SELECT t.precio * dp.cantidad AS precio_total
        FROM tacos t
        INNER JOIN detalle_pedido dp ON t.producto_id = dp.id_producto
        WHERE dp.id_pedido = id_pedido

        UNION ALL
        
        -- Calcular total de huaraches
        SELECT h.precio * dp.cantidad AS precio_total
        FROM huaraches h
        INNER JOIN detalle_pedido dp ON h.producto_id = dp.id_producto
        WHERE dp.id_pedido = id_pedido

        UNION ALL
        
        -- Calcular total de bebidas
        SELECT b.precio * dp.cantidad AS precio_total
        FROM volumenes b
        INNER JOIN detalle_pedido dp ON b.id_bebida = dp.id_producto
        WHERE dp.id_pedido = id_pedido
    ) AS totales;

    RETURN IFNULL(total, 0);
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `validar_contraseña` (`contra_input` VARCHAR(255)) RETURNS VARCHAR(255) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN
    DECLARE largo INT;
    DECLARE tiene_numero INT;
    DECLARE tiene_mayuscula INT;
    DECLARE tiene_caracter_especial INT;

    SET largo = CHAR_LENGTH(contra_input);
    SET tiene_numero = IF(contra_input REGEXP '[0-9]', 1, 0);
    SET tiene_mayuscula = IF(contra_input REGEXP '[A-Z]', 1, 0);
    SET tiene_caracter_especial = IF(contra_input REGEXP '[^A-Za-z0-9]', 1, 0);

    IF largo < 8 THEN
        RETURN 'La contraseña debe tener al menos 8 caracteres';
    ELSEIF tiene_numero = 0 THEN
        RETURN 'La contraseña debe contener al menos un número';
    ELSEIF tiene_mayuscula = 0 THEN
        RETURN 'La contraseña debe contener al menos una letra mayúscula';
    ELSEIF tiene_caracter_especial = 0 THEN
        RETURN 'La contraseña debe contener al menos un carácter especial';
    ELSE
        RETURN 'La contraseña es válida';
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bebidas`
--

CREATE TABLE `bebidas` (
  `id_bebida` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `imagen` varchar(255) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `bebidas`
--

INSERT INTO `bebidas` (`id_bebida`, `producto_id`, `nombre`, `cantidad`, `descripcion`, `imagen`, `fecha_creacion`) VALUES
(1, 42, 'Zevenup', 12, 'Refrescante lima-limón', 'uploads/seven.png', '2024-12-16 05:04:58'),
(2, 42, 'Mirinda', 11, 'Explosión de naranja', 'uploads/mirinda.png', '2024-12-16 02:53:41'),
(3, 42, 'Coca cola', 11, 'Sabor original y único', 'uploads/cocacola.jpg', '2024-12-09 13:31:14'),
(4, 42, 'Pepsi', 7, 'Refrescante y deliciosa', 'uploads/pepsi.png', '2024-12-09 13:30:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calles`
--

CREATE TABLE `calles` (
  `id` int(11) NOT NULL,
  `localidad_id` int(11) NOT NULL,
  `nombre_calle` varchar(255) NOT NULL,
  `numero` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `calles`
--

INSERT INTO `calles` (`id`, `localidad_id`, `nombre_calle`, `numero`) VALUES
(1, 1, 'Calle 1', 101),
(2, 1, 'Calle 2', 102),
(3, 2, 'Avenida Principal', 201),
(4, 2, 'Calle 3', 202),
(5, 3, 'Calle 4', 301),
(6, 3, 'Calle 5', 302);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checkbox`
--

CREATE TABLE `checkbox` (
  `id` int(11) NOT NULL,
  `tipo_producto_id` int(11) NOT NULL,
  `nombre_tipo` varchar(255) NOT NULL,
  `columna` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `checkbox`
--

INSERT INTO `checkbox` (`id`, `tipo_producto_id`, `nombre_tipo`, `columna`) VALUES
(42, 46, 'asj', 'Primera-columna'),
(43, 47, 'sajksa', 'Primera-columna'),
(44, 48, 'SAJKJSA', 'Primera-columna'),
(45, 48, 'ZXN', 'Segunda-columna');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `apellido` varchar(255) NOT NULL,
  `correo_electronico` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `nombre`, `apellido`, `correo_electronico`, `password`) VALUES
(1, 'Carlos', 'González', 'carlos.gonzalez@example.com', 'Password123!'),
(2, 'test1', 'test1', 'test@gmail.com', 'Password123!'),
(3, 'test2', 'test2', 'test2@gmail.com', 'Password123!'),
(4, 'Gerardo', 'Saenz', 'gerardosaenz678@gmail.com', '!lupoo2@):');

--
-- Disparadores `clientes`
--
DELIMITER $$
CREATE TRIGGER `before_insert_cliente` BEFORE INSERT ON `clientes` FOR EACH ROW BEGIN
    DECLARE resultado_validacion VARCHAR(255);

    -- Validar contraseña
    SET resultado_validacion = validar_contraseña(NEW.password);

    IF resultado_validacion != 'La contraseña es válida' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = resultado_validacion;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `combobox`
--

CREATE TABLE `combobox` (
  `id` int(11) NOT NULL,
  `tipo_producto_id` int(11) NOT NULL,
  `tipo` varchar(255) NOT NULL,
  `ingredientes` varchar(255) NOT NULL,
  `columna` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pagos`
--

CREATE TABLE `detalle_pagos` (
  `id_detalle_pago` int(11) NOT NULL,
  `id_pago` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `referencia` varchar(255) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `comprobante_pago` varchar(255) NOT NULL,
  `localidad` varchar(255) DEFAULT NULL,
  `direccion_exacta` varchar(255) DEFAULT NULL,
  `horario_retiro` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `id_detalle` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `nombre_producto` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `categoria` enum('Bebida','Taco','Huarache') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`id_detalle`, `id_pedido`, `producto_id`, `nombre_producto`, `cantidad`, `precio`, `categoria`) VALUES
(1, 1, 4, 'Pepsi', 1, 9.00, 'Bebida'),
(2, 1, 1, 'Taco de suadero', 1, 9.00, 'Taco'),
(3, 1, 2, 'Huarache longaniza', 1, 9.00, 'Huarache');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_inicio`
--

CREATE TABLE `estado_inicio` (
  `usuario_id` int(11) NOT NULL,
  `ultimo_inicio` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estado_inicio`
--

INSERT INTO `estado_inicio` (`usuario_id`, `ultimo_inicio`) VALUES
(1, '2024-12-17 13:39:55');

--
-- Disparadores `estado_inicio`
--
DELIMITER $$
CREATE TRIGGER `after_update_estado_inicio` AFTER UPDATE ON `estado_inicio` FOR EACH ROW BEGIN
    -- Insertar un log de sesión
    INSERT INTO logs_sesion (usuario_id, fecha_hora)
    VALUES (NEW.usuario_id, NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_inicio_clientes`
--

CREATE TABLE `estado_inicio_clientes` (
  `cliente_id` int(11) NOT NULL,
  `ultimo_inicio` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estado_inicio_clientes`
--

INSERT INTO `estado_inicio_clientes` (`cliente_id`, `ultimo_inicio`) VALUES
(1, '2025-01-07 12:41:55'),
(2, '2024-12-16 19:23:22'),
(3, '2024-12-16 22:21:57'),
(4, '2024-12-17 07:42:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `huaraches`
--

CREATE TABLE `huaraches` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `nombre_tipo` varchar(50) NOT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `frase1` varchar(255) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `cantidad_max` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `huaraches`
--

INSERT INTO `huaraches` (`id`, `producto_id`, `nombre_tipo`, `imagen_url`, `frase1`, `precio`, `cantidad_max`) VALUES
(1, 43, 'Huarache suadero', 'uploads/huarachesuadero.jpg', 'cebolaa', 9.00, 13),
(2, 43, 'Huarache longaniza', 'uploads/huarache longaniza.jpg', 'limon', 9.00, 12),
(3, 43, 'Huarache camaron', 'uploads/huarache camaron.jpg', 'salsa', 9.00, 11);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `localidades`
--

CREATE TABLE `localidades` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `localidades`
--

INSERT INTO `localidades` (`id`, `nombre`) VALUES
(1, 'Cuixcuatitla'),
(2, 'Tepatate'),
(3, 'Barrio La Vega');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_sesion`
--

CREATE TABLE `logs_sesion` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha_hora` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `logs_sesion`
--

INSERT INTO `logs_sesion` (`id`, `usuario_id`, `fecha_hora`) VALUES
(1, 1, '2024-12-17 13:39:55');

--
-- Disparadores `logs_sesion`
--
DELIMITER $$
CREATE TRIGGER `after_insert_logs_sesion` AFTER INSERT ON `logs_sesion` FOR EACH ROW BEGIN
    -- Actualizar el último inicio de sesión en `estado_inicio`
    INSERT INTO estado_inicio (usuario_id, ultimo_inicio)
    VALUES (NEW.usuario_id, NEW.fecha_hora)
    ON DUPLICATE KEY UPDATE ultimo_inicio = VALUES(ultimo_inicio);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_sesion_clientes`
--

CREATE TABLE `logs_sesion_clientes` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `logs_sesion_clientes`
--

INSERT INTO `logs_sesion_clientes` (`id`, `cliente_id`, `fecha_hora`) VALUES
(2, 1, '2024-12-15 23:03:23'),
(3, 1, '2024-12-15 23:04:35'),
(4, 1, '2024-12-15 23:06:30'),
(5, 2, '2024-12-15 23:18:53'),
(6, 1, '2024-12-16 11:23:16'),
(7, 1, '2024-12-16 11:37:02'),
(8, 1, '2024-12-16 19:19:44'),
(9, 1, '2024-12-16 19:22:56'),
(10, 2, '2024-12-16 19:23:22'),
(11, 1, '2024-12-16 19:45:51'),
(12, 1, '2024-12-16 19:48:17'),
(13, 1, '2024-12-16 20:00:53'),
(14, 1, '2024-12-16 20:02:53'),
(15, 1, '2024-12-16 20:05:16'),
(17, 1, '2024-12-16 20:08:32'),
(18, 1, '2024-12-16 20:08:35'),
(19, 1, '2024-12-16 20:08:52'),
(20, 1, '2024-12-16 20:18:58'),
(21, 1, '2024-12-16 20:20:30'),
(22, 1, '2024-12-16 20:22:09'),
(23, 1, '2024-12-16 20:58:52'),
(24, 1, '2024-12-16 21:11:45'),
(25, 1, '2024-12-16 22:07:48'),
(26, 3, '2024-12-16 22:21:57'),
(27, 4, '2024-12-17 00:47:27'),
(28, 4, '2024-12-17 00:55:38'),
(29, 4, '2024-12-17 00:56:55'),
(30, 4, '2024-12-17 01:26:31'),
(31, 4, '2024-12-17 01:58:52'),
(32, 4, '2024-12-17 02:00:26'),
(33, 4, '2024-12-17 02:28:44'),
(34, 4, '2024-12-17 06:34:33'),
(35, 4, '2024-12-17 07:42:39'),
(36, 1, '2024-12-18 15:30:30'),
(37, 1, '2025-01-07 12:41:55');

--
-- Disparadores `logs_sesion_clientes`
--
DELIMITER $$
CREATE TRIGGER `after_insert_logs_sesion_clientes` AFTER INSERT ON `logs_sesion_clientes` FOR EACH ROW BEGIN
    INSERT INTO estado_inicio_clientes (cliente_id, ultimo_inicio)
    VALUES (NEW.cliente_id, NEW.fecha_hora)
    ON DUPLICATE KEY UPDATE ultimo_inicio = VALUES(ultimo_inicio);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('Pendiente','Completado','Fallido') DEFAULT 'Pendiente',
  `id_pedido` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id_pago`, `id_cliente`, `total`, `fecha_pago`, `estado`, `id_pedido`) VALUES
(1, 1, 9.00, '2025-01-07 19:03:39', 'Completado', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('Pendiente','Confirmado','Cancelado') NOT NULL DEFAULT 'Pendiente',
  `id_cliente` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id_pedido`, `fecha_hora`, `estado`, `id_cliente`) VALUES
(1, '2024-12-19 22:22:44', 'Confirmado', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `id` int(11) NOT NULL,
  `nombre_producto` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`id`, `nombre_producto`) VALUES
(42, 'Huaraches'),
(43, 'Tacos'),
(44, 'Tacos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL,
  `nombre_producto` varchar(255) NOT NULL,
  `precio` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `nombre_producto`, `precio`) VALUES
(1, 'Producto 1', 10.50),
(2, 'Producto 2', 20.00),
(3, 'Producto 3', 15.75),
(4, 'Producto 1', 10.50),
(5, 'Producto 2', 20.00),
(6, 'Producto 3', 15.75);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tacos`
--

CREATE TABLE `tacos` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `nombre_tipo` varchar(255) NOT NULL,
  `imagen_url` varchar(255) NOT NULL,
  `frase1` varchar(255) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `cantidad_max` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tacos`
--

INSERT INTO `tacos` (`id`, `producto_id`, `nombre_tipo`, `imagen_url`, `frase1`, `precio`, `cantidad_max`) VALUES
(1, 44, 'Taco de suadero', 'uploads/suadero.jpg', 'salsa', 9.00, 13),
(2, 44, 'Taco de pastor', 'uploads/pastor.jpg', 'limon', 9.00, 12),
(3, 44, 'Taco de carnitas', 'uploads/carnitas.jpg', 'cebolla', 9.00, 11);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_producto`
--

CREATE TABLE `tipo_producto` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `nombre_tipo` varchar(255) NOT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `frase1` varchar(255) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `cantidad_max` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_producto`
--

INSERT INTO `tipo_producto` (`id`, `producto_id`, `nombre_tipo`, `imagen_url`, `frase1`, `precio`, `cantidad_max`) VALUES
(46, 42, 'Res', 'uploads/f.jpg', 'saksa', 9.00, 13),
(47, 43, 'Pollo', 'uploads/f.jpg', 'ksalks', 9.00, 12),
(48, 44, 'POLLO', 'uploads/f.jpg', 'HDSHS', 9.00, 11);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `apellido` varchar(255) NOT NULL,
  `correo_electronico` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `correo_electronico`, `password`) VALUES
(1, 'Juan', 'Pérez', 'juan.perez@example.com', 'Password123!');

--
-- Disparadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `before_insert_usuario` BEFORE INSERT ON `usuarios` FOR EACH ROW BEGIN
    DECLARE resultado_validacion VARCHAR(255);
    -- Validar contraseña con la función
    SET resultado_validacion = validar_contraseña(NEW.password);

    -- Si la contraseña no es válida, genera un error
    IF resultado_validacion != 'La contraseña es válida' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = resultado_validacion;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `volumenes`
--

CREATE TABLE `volumenes` (
  `id_volumen` int(11) NOT NULL,
  `id_bebida` int(11) NOT NULL,
  `volumen` varchar(50) NOT NULL,
  `precio` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `volumenes`
--

INSERT INTO `volumenes` (`id_volumen`, `id_bebida`, `volumen`, `precio`) VALUES
(1, 16, '22', 15.50),
(2, 17, '19', 18.00),
(3, 18, '899', 25.00),
(4, 19, '9.00', 10.00);

--
-- Disparadores `volumenes`
--
DELIMITER $$
CREATE TRIGGER `before_insert_volumenes` BEFORE INSERT ON `volumenes` FOR EACH ROW BEGIN
    -- Verificar que el precio sea mayor a 0
    IF NEW.precio <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El precio debe ser mayor a cero';
    END IF;
END
$$
DELIMITER ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bebidas`
--
ALTER TABLE `bebidas`
  ADD PRIMARY KEY (`id_bebida`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `calles`
--
ALTER TABLE `calles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `localidad_id` (`localidad_id`);

--
-- Indices de la tabla `checkbox`
--
ALTER TABLE `checkbox`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipo_producto_id` (`tipo_producto_id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `correo_electronico` (`correo_electronico`);

--
-- Indices de la tabla `combobox`
--
ALTER TABLE `combobox`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipo_producto_id` (`tipo_producto_id`);

--
-- Indices de la tabla `detalle_pagos`
--
ALTER TABLE `detalle_pagos`
  ADD PRIMARY KEY (`id_detalle_pago`),
  ADD KEY `id_pago` (`id_pago`),
  ADD KEY `id_pedido` (`id_pedido`);

--
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_pedido` (`id_pedido`);

--
-- Indices de la tabla `estado_inicio`
--
ALTER TABLE `estado_inicio`
  ADD PRIMARY KEY (`usuario_id`);

--
-- Indices de la tabla `estado_inicio_clientes`
--
ALTER TABLE `estado_inicio_clientes`
  ADD PRIMARY KEY (`cliente_id`);

--
-- Indices de la tabla `huaraches`
--
ALTER TABLE `huaraches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `localidades`
--
ALTER TABLE `localidades`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `logs_sesion`
--
ALTER TABLE `logs_sesion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `logs_sesion_clientes`
--
ALTER TABLE `logs_sesion_clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_pedido` (`id_pedido`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`);

--
-- Indices de la tabla `tacos`
--
ALTER TABLE `tacos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `tipo_producto`
--
ALTER TABLE `tipo_producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo_electronico` (`correo_electronico`);

--
-- Indices de la tabla `volumenes`
--
ALTER TABLE `volumenes`
  ADD PRIMARY KEY (`id_volumen`),
  ADD KEY `id_bebida` (`id_bebida`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bebidas`
--
ALTER TABLE `bebidas`
  MODIFY `id_bebida` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `calles`
--
ALTER TABLE `calles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `checkbox`
--
ALTER TABLE `checkbox`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `combobox`
--
ALTER TABLE `combobox`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_pagos`
--
ALTER TABLE `detalle_pagos`
  MODIFY `id_detalle_pago` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `huaraches`
--
ALTER TABLE `huaraches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `localidades`
--
ALTER TABLE `localidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `logs_sesion`
--
ALTER TABLE `logs_sesion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `logs_sesion_clientes`
--
ALTER TABLE `logs_sesion_clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `tacos`
--
ALTER TABLE `tacos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tipo_producto`
--
ALTER TABLE `tipo_producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `volumenes`
--
ALTER TABLE `volumenes`
  MODIFY `id_volumen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bebidas`
--
ALTER TABLE `bebidas`
  ADD CONSTRAINT `bebidas_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `calles`
--
ALTER TABLE `calles`
  ADD CONSTRAINT `calles_ibfk_1` FOREIGN KEY (`localidad_id`) REFERENCES `localidades` (`id`);

--
-- Filtros para la tabla `checkbox`
--
ALTER TABLE `checkbox`
  ADD CONSTRAINT `checkbox_ibfk_1` FOREIGN KEY (`tipo_producto_id`) REFERENCES `tipo_producto` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `combobox`
--
ALTER TABLE `combobox`
  ADD CONSTRAINT `combobox_ibfk_1` FOREIGN KEY (`tipo_producto_id`) REFERENCES `tipo_producto` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_pagos`
--
ALTER TABLE `detalle_pagos`
  ADD CONSTRAINT `detalle_pagos_ibfk_1` FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_pagos_ibfk_2` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `detalle_pedido_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE;

--
-- Filtros para la tabla `estado_inicio`
--
ALTER TABLE `estado_inicio`
  ADD CONSTRAINT `estado_inicio_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `estado_inicio_clientes`
--
ALTER TABLE `estado_inicio_clientes`
  ADD CONSTRAINT `estado_inicio_clientes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`);

--
-- Filtros para la tabla `huaraches`
--
ALTER TABLE `huaraches`
  ADD CONSTRAINT `huaraches_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `logs_sesion`
--
ALTER TABLE `logs_sesion`
  ADD CONSTRAINT `logs_sesion_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `logs_sesion_clientes`
--
ALTER TABLE `logs_sesion_clientes`
  ADD CONSTRAINT `logs_sesion_clientes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE,
  ADD CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tacos`
--
ALTER TABLE `tacos`
  ADD CONSTRAINT `tacos_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tipo_producto`
--
ALTER TABLE `tipo_producto`
  ADD CONSTRAINT `tipo_producto_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
