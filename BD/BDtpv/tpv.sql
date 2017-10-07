-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 07-10-2017 a las 17:48:30
-- Versión del servidor: 10.1.26-MariaDB-0+deb9u1
-- Versión de PHP: 7.0.19-1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tpv`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulos`
--

CREATE TABLE `articulos` (
  `idArticulo` int(11) NOT NULL,
  `iva` decimal(4,2) DEFAULT NULL,
  `idProveedor` varchar(6) CHARACTER SET utf8 DEFAULT NULL,
  `articulo_name` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  `beneficio` decimal(5,2) DEFAULT NULL,
  `costepromedio` decimal(17,6) DEFAULT NULL,
  `fechaalta` datetime NOT NULL,
  `estado` varchar(12) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulosCodigoBarras`
--

CREATE TABLE `articulosCodigoBarras` (
  `idArticulo` int(11) NOT NULL,
  `codBarras` varchar(18) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulosFamilia`
--

CREATE TABLE `articulosFamilia` (
  `idArticulo` int(11) NOT NULL,
  `idFamilia` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulosImagenes`
--

CREATE TABLE `articulosImagenes` (
  `id` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `virtuemart_product_id` int(11) NOT NULL,
  `file_url` varchar(900) NOT NULL,
  `virtuemart_media_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulosPrecios`
--

CREATE TABLE `articulosPrecios` (
  `idArticulo` int(11) NOT NULL,
  `pvpCiva` decimal(17,6) NOT NULL,
  `pvpSiva` decimal(17,6) NOT NULL,
  `idTienda` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulosProveedores`
--

CREATE TABLE `articulosProveedores` (
  `idArticulo` int(11) NOT NULL,
  `idProveedor` int(11) NOT NULL,
  `crefProveedor` varchar(24) DEFAULT NULL,
  `coste` decimal(17,6) NOT NULL,
  `fechaActualizacion` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulosTiendas`
--

CREATE TABLE `articulosTiendas` (
  `idArticulo` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `crefTienda` varchar(18) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `idCategoria` int(11) NOT NULL,
  `categoriaNombre` varchar(100) NOT NULL DEFAULT '',
  `categoriaPadre` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `idClientes` int(11) NOT NULL,
  `Nombre` varchar(100) CHARACTER SET utf8 NOT NULL,
  `razonsocial` varchar(100) CHARACTER SET utf8 NOT NULL,
  `nif` varchar(10) CHARACTER SET utf8 NOT NULL,
  `direccion` varchar(100) CHARACTER SET utf8 NOT NULL,
  `telefono` varchar(11) NOT NULL,
  `movil` varchar(11) NOT NULL,
  `fax` varchar(11) NOT NULL,
  `email` varchar(100) CHARACTER SET utf8 NOT NULL,
  `estado` varchar(12) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `indices`
--

CREATE TABLE `indices` (
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `numticket` int(11) NOT NULL,
  `tempticket` int(11) NOT NULL COMMENT 'Es el numero con guardo temporal ticket'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `iva`
--

CREATE TABLE `iva` (
  `idIva` int(11) NOT NULL,
  `descripcionIva` varchar(25) DEFAULT NULL,
  `iva` decimal(4,2) DEFAULT NULL,
  `recargo` decimal(4,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `idProveedor` int(11) NOT NULL,
  `nombrecomercial` varchar(100) DEFAULT NULL,
  `razonsocial` varchar(10) NOT NULL,
  `nif` varchar(10) NOT NULL,
  `direccion` varchar(100) NOT NULL,
  `telefono` varchar(11) NOT NULL,
  `fax` varchar(11) NOT NULL,
  `movil` varchar(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `fechaalta` date NOT NULL,
  `idusuario` int(11) NOT NULL,
  `estado` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticketslinea`
--

CREATE TABLE `ticketslinea` (
  `id` int(11) NOT NULL,
  `idticketst` int(11) NOT NULL,
  `Numticket` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `cref` varchar(18) NOT NULL,
  `ccodbar` varchar(18) NOT NULL,
  `cdetalle` varchar(100) NOT NULL,
  `ncant` decimal(17,6) NOT NULL,
  `nunidades` decimal(17,6) NOT NULL,
  `precioCiva` decimal(17,2) NOT NULL,
  `iva` decimal(4,2) NOT NULL,
  `nfila` int(11) NOT NULL,
  `estadoLinea` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticketst`
--

CREATE TABLE `ticketst` (
  `id` int(11) NOT NULL,
  `Numticket` int(11) NOT NULL,
  `Numtempticket` int(11) NOT NULL,
  `Fecha` datetime NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idCliente` int(11) NOT NULL,
  `estado` varchar(12) NOT NULL,
  `formaPago` varchar(12) NOT NULL,
  `entregado` decimal(17,2) NOT NULL,
  `total` decimal(17,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticketstemporales`
--

CREATE TABLE `ticketstemporales` (
  `id` int(11) NOT NULL,
  `numticket` int(11) NOT NULL,
  `estadoTicket` varchar(12) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `fechaInicio` datetime NOT NULL,
  `fechaFinal` datetime NOT NULL,
  `idClientes` int(11) NOT NULL,
  `total` decimal(17,6) NOT NULL,
  `total_ivas` varchar(250) NOT NULL,
  `Productos` varbinary(50000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticketstIva`
--

CREATE TABLE `ticketstIva` (
  `id` int(11) NOT NULL,
  `idticketst` int(11) NOT NULL,
  `Numticket` int(11) NOT NULL,
  `iva` int(11) NOT NULL,
  `importeIva` decimal(17,2) NOT NULL,
  `totalbase` decimal(17,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tiendas`
--

CREATE TABLE `tiendas` (
  `idTienda` int(2) NOT NULL,
  `NombreComercial` varchar(100) DEFAULT NULL,
  `razonsocial` varchar(100) NOT NULL,
  `nif` varchar(10) NOT NULL,
  `telefono` varchar(11) NOT NULL,
  `direccion` varchar(100) NOT NULL,
  `ano` varchar(4) DEFAULT NULL,
  `estado` varchar(12) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `group_id` int(11) NOT NULL COMMENT 'id grupo permisos',
  `estado` varchar(8) NOT NULL COMMENT 'estado',
  `nombre` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `articulos`
--
ALTER TABLE `articulos`
  ADD PRIMARY KEY (`idArticulo`),
  ADD KEY `idProveedor` (`idProveedor`);

--
-- Indices de la tabla `articulosCodigoBarras`
--
ALTER TABLE `articulosCodigoBarras`
  ADD UNIQUE KEY `codBarras` (`codBarras`);

--
-- Indices de la tabla `articulosFamilia`
--
ALTER TABLE `articulosFamilia`
  ADD PRIMARY KEY (`idArticulo`,`idFamilia`),
  ADD KEY `fk_categoriaFamilias` (`idFamilia`),
  ADD KEY `fk_articulos` (`idArticulo`);

--
-- Indices de la tabla `articulosProveedores`
--
ALTER TABLE `articulosProveedores`
  ADD PRIMARY KEY (`idArticulo`,`idProveedor`);

--
-- Indices de la tabla `articulosTiendas`
--
ALTER TABLE `articulosTiendas`
  ADD PRIMARY KEY (`idArticulo`,`idTienda`),
  ADD KEY `idTienda` (`idTienda`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`idCategoria`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`idClientes`);

--
-- Indices de la tabla `iva`
--
ALTER TABLE `iva`
  ADD PRIMARY KEY (`idIva`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`idProveedor`);

--
-- Indices de la tabla `ticketslinea`
--
ALTER TABLE `ticketslinea`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `ticketst`
--
ALTER TABLE `ticketst`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `ticketstemporales`
--
ALTER TABLE `ticketstemporales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `ticketstIva`
--
ALTER TABLE `ticketstIva`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tiendas`
--
ALTER TABLE `tiendas`
  ADD PRIMARY KEY (`idTienda`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `articulos`
--
ALTER TABLE `articulos`
  MODIFY `idArticulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10507;
--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `idClientes` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;
--
-- AUTO_INCREMENT de la tabla `iva`
--
ALTER TABLE `iva`
  MODIFY `idIva` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT de la tabla `ticketslinea`
--
ALTER TABLE `ticketslinea`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=587;
--
-- AUTO_INCREMENT de la tabla `ticketst`
--
ALTER TABLE `ticketst`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=197;
--
-- AUTO_INCREMENT de la tabla `ticketstemporales`
--
ALTER TABLE `ticketstemporales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=367;
--
-- AUTO_INCREMENT de la tabla `ticketstIva`
--
ALTER TABLE `ticketstIva`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=291;
--
-- AUTO_INCREMENT de la tabla `tiendas`
--
ALTER TABLE `tiendas`
  MODIFY `idTienda` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
