-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-05-2026 a las 09:45:33
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `luz_de_hogar`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administrador`
--

CREATE TABLE `administrador` (
  `ID` bigint(20) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contraseña` varchar(255) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `carrito_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `administrador`
--

INSERT INTO `administrador` (`ID`, `nombre`, `email`, `contraseña`, `cliente_id`, `producto_id`, `pedido_id`, `carrito_id`) VALUES
(1, 'Administrador', 'admin@luzdehogar.es', '16387b09507b019016e1fb92dd9bb92d8700abf415d728a64f80094942f6dd5c', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito`
--

CREATE TABLE `carrito` (
  `ID` int(11) NOT NULL,
  `fecha_creacion` date DEFAULT NULL,
  `cliente_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito_item`
--

CREATE TABLE `carrito_item` (
  `ID` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `carrito_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `ID` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`ID`, `nombre`, `descripcion`) VALUES
(1, 'Ceramica', 'Piezas artesanales de arcilla y barro'),
(2, 'Velas', 'Velas de soja con esencias naturales');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `ID` int(11) NOT NULL,
  `Nombre` varchar(100) DEFAULT NULL,
  `Apellido` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(100) DEFAULT NULL,
  `contraseña` varchar(255) DEFAULT NULL,
  `tokens` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`ID`, `Nombre`, `Apellido`, `email`, `telefono`, `direccion`, `contraseña`, `tokens`) VALUES
(1, 'María', 'García', 'cliente@luzdehogar.es', NULL, 'C/ Dragon Ball Z, 25', '$2y$10$4da5o3RBfzWcnuU7Akyu7ueGL3nZY8t58wWlzQd09eJ0jTK6iLYOC', 0),
(2, 'Antonio', 'Romero', 'cliente2@luzdehogar.es', '', NULL, '$2y$10$KhgVMfpLcJHSuxAAORaHTeEyWqIeu7yokaSOlkJk/UrKmL9Kn31oW', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direccion`
--

CREATE TABLE `direccion` (
  `ID` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `alias` varchar(50) DEFAULT NULL COMMENT 'Casa, Trabajo, etc.',
  `calle` varchar(150) NOT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `codigo_postal` varchar(10) DEFAULT NULL,
  `pais` varchar(50) DEFAULT 'España',
  `telefono` varchar(20) DEFAULT NULL,
  `predeterminada` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `direccion`
--

INSERT INTO `direccion` (`ID`, `cliente_id`, `alias`, `calle`, `ciudad`, `codigo_postal`, `pais`, `telefono`, `predeterminada`, `fecha_creacion`) VALUES
(2, 2, 'Casa', 'Avda,  Papa Luna, 60', 'Peñiscola', '12598', 'España', NULL, 0, '2026-05-06 08:23:15'),
(3, 2, 'Casa', 'Calle Mago de Oz', 'Zaragoza', '50019', 'España', NULL, 1, '2026-05-07 14:39:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evento`
--

CREATE TABLE `evento` (
  `ID` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_evento` datetime NOT NULL,
  `url_imagen` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `ubicacion_mapa` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `evento`
--

INSERT INTO `evento` (`ID`, `titulo`, `descripcion`, `fecha_evento`, `url_imagen`, `creado_en`, `ubicacion_mapa`) VALUES
(1, 'Taller de Velas Artesanales', 'Taller en el que aprenderás las principales técnicas para la creación de velas artesanales.', '2026-05-18 10:00:00', '  img/eventos/1779265756_1779265573_evento_1.jpg', '2026-05-17 12:03:50', NULL),
(2, 'Taller de Cerámica Verano.', 'Ven a disfrutar con nosotros de la cerámica al aire libre.', '2026-06-02 11:00:00', 'img/eventos/1779268551_Ceramica-y-vino_Portada-1200x600-1-2930534429.jpg', '2026-05-20 09:15:51', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3037.7107585513863!2d-3.686040624172731!3d40.41525797143982!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd42289ff511827b%3A0x9e6c2716b524a3ae!2sParque%20de%20El%20Retiro!5e0!3m2!1ses!2ses!4v1779268051877!5m2!1ses!2ses');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

CREATE TABLE `pedido` (
  `ID` int(11) NOT NULL,
  `fecha` date DEFAULT NULL,
  `Hora` time DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'Pendiente',
  `total` decimal(10,2) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `direccion_envio_id` int(11) DEFAULT NULL,
  `direccion_envio_snapshot` varchar(255) DEFAULT NULL,
  `cliente_nombre_snapshot` varchar(200) DEFAULT NULL,
  `cliente_apellido_snapshot` varchar(200) DEFAULT NULL,
  `cliente_email_snapshot` varchar(100) DEFAULT NULL,
  `cliente_telefono_snapshot` varchar(20) DEFAULT NULL,
  `tokens_usados` int(11) NOT NULL DEFAULT 0,
  `tokens_ganados` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pedido`
--

INSERT INTO `pedido` (`ID`, `fecha`, `Hora`, `estado`, `total`, `cliente_id`, `direccion_envio_id`, `direccion_envio_snapshot`, `cliente_nombre_snapshot`, `cliente_apellido_snapshot`, `cliente_email_snapshot`, `cliente_telefono_snapshot`, `tokens_usados`, `tokens_ganados`) VALUES
(1, '2026-05-05', '12:08:47', 'Entregado', 24.99, 2, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0),
(2, '2026-05-06', '09:25:52', 'Enviado', 22.20, 2, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0),
(3, '2026-05-06', '12:05:03', 'Pendiente', 17.59, 2, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_estado_historial`
--

CREATE TABLE `pedido_estado_historial` (
  `ID` bigint(20) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_nuevo` varchar(50) NOT NULL,
  `fecha_cambio` datetime NOT NULL DEFAULT current_timestamp(),
  `comentario` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pedido_estado_historial`
--

INSERT INTO `pedido_estado_historial` (`ID`, `pedido_id`, `estado_anterior`, `estado_nuevo`, `fecha_cambio`, `comentario`) VALUES
(1, 1, 'Pendiente', 'Entregado', '2026-05-06 01:36:37', ''),
(2, 2, 'Pendiente', 'Enviado', '2026-05-06 09:28:34', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_item`
--

CREATE TABLE `pedido_item` (
  `ID` bigint(20) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pedido_item`
--

INSERT INTO `pedido_item` (`ID`, `cantidad`, `precio_unitario`, `pedido_id`, `producto_id`) VALUES
(1, 1, 15.00, 1, 2),
(2, 1, 9.99, 1, 9),
(3, 1, 22.20, 2, 3),
(4, 1, 17.59, 3, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `ID` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `descripcion` longtext DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`ID`, `nombre`, `descripcion`, `precio`, `stock`, `categoria_id`) VALUES
(1, 'Taza Tecnica Gres', 'Taza artesanal de gres, resistente y elegante. Perfecta para tu cafe de manana.', 18.00, 15, 1),
(2, 'Vela Dorota', 'Vela de soja con aroma a vainilla y caramelo. Duracion 40 horas.', 15.00, 20, 2),
(3, 'Jarron Porcelana de Lunares', 'Jarron decorativo de porcelana con diseno de lunares azules.', 22.20, 8, 1),
(4, 'Mortero', 'Mortero artesanal para cocina, ideal para especias.', 17.59, 12, 1),
(5, 'Juego Vajilla', 'Set completo de vajilla para 4 personas. Incluye platos, bowls y tazas.', 39.99, 5, 1),
(6, 'Vela Cactus', 'Vela decorativa en forma de cactus. Aroma fresco y natural.', 15.50, 18, 2),
(7, 'Vela Lavanda', 'Vela de soja con esencia de lavanda. Relajante y calmante.', 19.50, 25, 2),
(8, 'Vaso', 'Vaso artesanal de ceramica esmaltada. Disponible en varios colores.', 9.99, 30, 1),
(9, 'Vela Vainilla y Jazmin', 'Combinacion dulce de vainilla con el toque floral del jazmin.', 9.99, 22, 2),
(10, 'Cuenco', 'Cuenco pequeno perfecto para aperitivos o decoracion.', 7.50, 40, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_imagenes`
--

CREATE TABLE `producto_imagenes` (
  `ID` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `url_imagen` varchar(500) NOT NULL,
  `alt_texto` varchar(200) DEFAULT NULL,
  `orden` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `producto_imagenes`
--

INSERT INTO `producto_imagenes` (`ID`, `producto_id`, `url_imagen`, `alt_texto`, `orden`) VALUES
(1, 1, 'producto-ceramica-taza-gres.jpg', 'Taza artesanal de gres', 1),
(2, 2, 'producto-velas-soja.jpg', 'Vela de soja Dorota', 1),
(3, 3, 'producto-ceramica-jarron-lunares.jpg', 'Jarron de porcelana con lunares', 1),
(4, 4, 'producto-ceramica-mortero.jpg', 'Mortero de ceramica artesanal', 1),
(5, 5, 'producto-ceramica-vajilla.jpg', 'Juego completo de vajilla', 1),
(6, 6, 'producto-velas-cactus.jpg', 'Vela decorativa en forma de cactus', 1),
(7, 7, 'producto-velas-lavanda.jpg', 'Vela de lavanda relajante', 1),
(8, 8, 'producto-ceramica-vaso.jpg', 'Vaso de ceramica esmaltada', 1),
(9, 9, 'producto-velas-vainilla-jazmin.jpg', 'Vela de vainilla y jazmin', 1),
(10, 10, 'producto-ceramica-cuenco.jpg', 'Cuenco de ceramica pequeno', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resena`
--

CREATE TABLE `resena` (
  `ID` bigint(20) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `puntuacion` tinyint(4) NOT NULL,
  `comentario` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `visible` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `resena`
--

INSERT INTO `resena` (`ID`, `cliente_id`, `producto_id`, `puntuacion`, `comentario`, `fecha`, `visible`) VALUES
(1, 2, 2, 4, 'Un producto que a la vez es muy estético tiene un aroma impresionante.', '2026-05-06 01:54:07', 1);

--
-- Disparadores `resena`
--
DELIMITER $$
CREATE TRIGGER `trg_resena_before_insert` BEFORE INSERT ON `resena` FOR EACH ROW BEGIN
  IF NEW.puntuacion < 1 OR NEW.puntuacion > 5 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La puntuacion debe estar entre 1 y 5';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_resena_before_update` BEFORE UPDATE ON `resena` FOR EACH ROW BEGIN
  IF NEW.puntuacion < 1 OR NEW.puntuacion > 5 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La puntuacion debe estar entre 1 y 5';
  END IF;
END
$$
DELIMITER ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administrador`
--
ALTER TABLE `administrador`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_ADMIN_CLIENTE` (`cliente_id`),
  ADD KEY `FK_ADMIN_PEDIDO` (`pedido_id`),
  ADD KEY `FK_ADMIN_PRODUCTO` (`producto_id`),
  ADD KEY `FK_ADMIN_CARRITO` (`carrito_id`);

--
-- Indices de la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_CARRITO_CLIENTE` (`cliente_id`);

--
-- Indices de la tabla `carrito_item`
--
ALTER TABLE `carrito_item`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_CARRITO_ITEM_CARRITO` (`carrito_id`),
  ADD KEY `FK_CARRITO_ITEM_PRODUCTO` (`producto_id`);

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `direccion`
--
ALTER TABLE `direccion`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_DIRECCION_CLIENTE` (`cliente_id`);

--
-- Indices de la tabla `evento`
--
ALTER TABLE `evento`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_PEDIDO_CLIENTE` (`cliente_id`),
  ADD KEY `FK_PEDIDO_DIRECCION` (`direccion_envio_id`);

--
-- Indices de la tabla `pedido_estado_historial`
--
ALTER TABLE `pedido_estado_historial`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_HISTORIAL_PEDIDO` (`pedido_id`);

--
-- Indices de la tabla `pedido_item`
--
ALTER TABLE `pedido_item`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_PEDIDO_ITEM_PEDIDO` (`pedido_id`),
  ADD KEY `FK_PEDIDO_ITEM_PRODUCTO` (`producto_id`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_PRODUCTO_CATEGORIA` (`categoria_id`);

--
-- Indices de la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_PROD_IMG_PRODUCTO` (`producto_id`);

--
-- Indices de la tabla `resena`
--
ALTER TABLE `resena`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `FK_RESENA_CLIENTE` (`cliente_id`),
  ADD KEY `FK_RESENA_PRODUCTO` (`producto_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administrador`
--
ALTER TABLE `administrador`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `carrito`
--
ALTER TABLE `carrito`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `carrito_item`
--
ALTER TABLE `carrito_item`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `direccion`
--
ALTER TABLE `direccion`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `evento`
--
ALTER TABLE `evento`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pedido`
--
ALTER TABLE `pedido`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pedido_estado_historial`
--
ALTER TABLE `pedido_estado_historial`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pedido_item`
--
ALTER TABLE `pedido_item`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `resena`
--
ALTER TABLE `resena`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `administrador`
--
ALTER TABLE `administrador`
  ADD CONSTRAINT `FK_ADMIN_CARRITO` FOREIGN KEY (`carrito_id`) REFERENCES `carrito` (`ID`),
  ADD CONSTRAINT `FK_ADMIN_CLIENTE` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`ID`),
  ADD CONSTRAINT `FK_ADMIN_PEDIDO` FOREIGN KEY (`pedido_id`) REFERENCES `pedido` (`ID`),
  ADD CONSTRAINT `FK_ADMIN_PRODUCTO` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`ID`);

--
-- Filtros para la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD CONSTRAINT `FK_CARRITO_CLIENTE` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`ID`);

--
-- Filtros para la tabla `carrito_item`
--
ALTER TABLE `carrito_item`
  ADD CONSTRAINT `FK_CARRITO_ITEM_CARRITO` FOREIGN KEY (`carrito_id`) REFERENCES `carrito` (`ID`),
  ADD CONSTRAINT `FK_CARRITO_ITEM_PRODUCTO` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`ID`);

--
-- Filtros para la tabla `direccion`
--
ALTER TABLE `direccion`
  ADD CONSTRAINT `FK_DIRECCION_CLIENTE` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`ID`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD CONSTRAINT `FK_PEDIDO_CLIENTE` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`ID`),
  ADD CONSTRAINT `FK_PEDIDO_DIRECCION` FOREIGN KEY (`direccion_envio_id`) REFERENCES `direccion` (`ID`);

--
-- Filtros para la tabla `pedido_estado_historial`
--
ALTER TABLE `pedido_estado_historial`
  ADD CONSTRAINT `FK_HISTORIAL_PEDIDO` FOREIGN KEY (`pedido_id`) REFERENCES `pedido` (`ID`);

--
-- Filtros para la tabla `pedido_item`
--
ALTER TABLE `pedido_item`
  ADD CONSTRAINT `FK_PEDIDO_ITEM_PEDIDO` FOREIGN KEY (`pedido_id`) REFERENCES `pedido` (`ID`),
  ADD CONSTRAINT `FK_PEDIDO_ITEM_PRODUCTO` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`ID`);

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `FK_PRODUCTO_CATEGORIA` FOREIGN KEY (`categoria_id`) REFERENCES `categoria` (`ID`);

--
-- Filtros para la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  ADD CONSTRAINT `FK_PROD_IMG_PRODUCTO` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`ID`);

--
-- Filtros para la tabla `resena`
--
ALTER TABLE `resena`
  ADD CONSTRAINT `FK_RESENA_CLIENTE` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`ID`),
  ADD CONSTRAINT `FK_RESENA_PRODUCTO` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
