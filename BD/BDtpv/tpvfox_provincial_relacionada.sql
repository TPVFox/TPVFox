-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: host.docker.internal:3306
-- Tiempo de generación: 02-05-2024 a las 09:18:26
-- Versión del servidor: 10.11.6-MariaDB-0+deb12u1
-- Versión de PHP: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tpvfox_provincial_2023`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `albclifac`
--

CREATE TABLE `albclifac` (
  `id` int(11) NOT NULL,
  `idFactura` int(11) DEFAULT NULL,
  `numFactura` int(11) DEFAULT NULL,
  `idAlbaran` int(11) DEFAULT NULL,
  `numAlbaran` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `albcliIva`
--

CREATE TABLE `albcliIva` (
  `id` int(11) NOT NULL,
  `idalbcli` int(11) NOT NULL,
  `Numalbcli` int(11) NOT NULL,
  `iva` int(11) DEFAULT NULL,
  `importeIva` decimal(17,2) DEFAULT NULL,
  `totalbase` decimal(17,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `albclilinea`
--

CREATE TABLE `albclilinea` (
  `id` int(11) NOT NULL,
  `idalbcli` int(11) NOT NULL,
  `Numalbcli` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `cref` varchar(18) DEFAULT NULL,
  `ccodbar` varchar(18) DEFAULT NULL,
  `cdetalle` varchar(100) DEFAULT NULL,
  `ncant` decimal(17,6) DEFAULT NULL,
  `nunidades` decimal(17,6) DEFAULT NULL,
  `precioCiva` decimal(17,2) DEFAULT NULL,
  `iva` decimal(4,2) DEFAULT NULL,
  `nfila` int(11) DEFAULT NULL,
  `estadoLinea` varchar(12) DEFAULT NULL,
  `NumpedCli` int(100) DEFAULT NULL,
  `pvpSiva` decimal(17,6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `albcliltemporales`
--

CREATE TABLE `albcliltemporales` (
  `id` int(11) NOT NULL,
  `Numalbcli` int(11) DEFAULT NULL,
  `idTienda` int(11) DEFAULT NULL,
  `idUsuario` int(11) DEFAULT NULL,
  `fechaInicio` datetime DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `idCliente` int(11) DEFAULT NULL,
  `total` decimal(17,6) DEFAULT NULL,
  `total_ivas` varchar(250) DEFAULT NULL,
  `Productos` mediumblob DEFAULT NULL,
  `Pedidos` varbinary(5000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `albclit`
--

CREATE TABLE `albclit` (
  `id` int(11) NOT NULL,
  `Numalbcli` int(11) DEFAULT NULL,
  `Numtemp_albcli` int(11) DEFAULT NULL,
  `Fecha` datetime NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idCliente` int(11) NOT NULL,
  `estado` varchar(12) DEFAULT NULL,
  `formaPago` varchar(12) DEFAULT NULL,
  `entregado` decimal(17,2) DEFAULT NULL,
  `total` decimal(17,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `albprofac`
--

CREATE TABLE `albprofac` (
  `id` int(11) NOT NULL,
  `idFactura` int(11) DEFAULT NULL,
  `numFactura` int(11) DEFAULT NULL,
  `idAlbaran` int(11) DEFAULT NULL,
  `numAlbaran` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `albproIva`
--

CREATE TABLE `albproIva` (
  `id` int(11) NOT NULL,
  `idalbpro` int(11) NOT NULL,
  `Numalbpro` int(11) NOT NULL,
  `iva` int(11) DEFAULT NULL,
  `importeIva` decimal(17,2) DEFAULT NULL,
  `totalbase` decimal(17,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `albprolinea`
--

CREATE TABLE `albprolinea` (
  `id` int(11) NOT NULL,
  `idalbpro` int(11) NOT NULL,
  `Numalbpro` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `cref` varchar(18) DEFAULT NULL,
  `ccodbar` varchar(18) DEFAULT NULL,
  `cdetalle` varchar(100) DEFAULT NULL,
  `ncant` decimal(17,6) DEFAULT NULL,
  `nunidades` decimal(17,6) DEFAULT NULL,
  `costeSiva` decimal(17,4) DEFAULT NULL,
  `iva` decimal(4,2) DEFAULT NULL,
  `nfila` int(11) DEFAULT NULL,
  `estadoLinea` varchar(12) DEFAULT NULL,
  `ref_prov` varchar(24) NOT NULL,
  `idpedpro` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `albproltemporales`
--

CREATE TABLE `albproltemporales` (
  `id` int(11) NOT NULL,
  `Numalbpro` int(11) DEFAULT NULL,
  `Su_numero` varchar(20) NOT NULL,
  `estadoAlbPro` varchar(12) DEFAULT NULL,
  `idTienda` int(11) DEFAULT NULL,
  `idUsuario` int(11) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `fechaInicio` datetime DEFAULT NULL,
  `fechaFinal` datetime DEFAULT NULL,
  `idProveedor` int(11) DEFAULT NULL,
  `total` decimal(17,6) DEFAULT NULL,
  `total_ivas` varchar(250) DEFAULT NULL,
  `Productos` mediumblob DEFAULT NULL,
  `Pedidos` varbinary(5000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `albprot`
--

CREATE TABLE `albprot` (
  `id` int(11) NOT NULL,
  `Numalbpro` int(11) DEFAULT NULL,
  `Numtemp_albpro` int(11) DEFAULT NULL,
  `Su_numero` varchar(20) DEFAULT NULL,
  `Fecha` datetime NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idProveedor` int(11) NOT NULL,
  `estado` varchar(12) DEFAULT NULL,
  `formaPago` varchar(12) DEFAULT NULL,
  `entregado` decimal(17,2) DEFAULT NULL,
  `total_siniva` decimal(17,6) NOT NULL,
  `total` decimal(17,2) DEFAULT NULL,
  `FechaVencimiento` date DEFAULT NULL,
  `fechaModificacion` datetime DEFAULT NULL,
  `modify_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulos`
--

CREATE TABLE `articulos` (
  `idArticulo` int(11) NOT NULL,
  `iva` decimal(4,2) DEFAULT NULL,
  `idProveedor` varchar(6) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `articulo_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `beneficio` decimal(5,2) DEFAULT NULL,
  `costepromedio` decimal(17,6) DEFAULT NULL,
  `estado` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `fecha_creado` datetime NOT NULL,
  `fecha_modificado` datetime DEFAULT NULL,
  `ultimoCoste` float NOT NULL,
  `tipo` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulosClientes`
--

CREATE TABLE `articulosClientes` (
  `idArticulo` int(11) NOT NULL,
  `idClientes` int(11) NOT NULL,
  `pvpSiva` decimal(17,6) NOT NULL,
  `pvpCiva` decimal(17,6) NOT NULL,
  `fechaActualizacion` datetime NOT NULL,
  `estado` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulosCodigoBarras`
--

CREATE TABLE `articulosCodigoBarras` (
  `id` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `codBarras` varchar(18) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulosFamilias`
--

CREATE TABLE `articulosFamilias` (
  `id` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `idFamilia` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulosPrecios`
--

CREATE TABLE `articulosPrecios` (
  `id` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `pvpCiva` decimal(17,6) NOT NULL,
  `pvpSiva` decimal(17,6) NOT NULL,
  `idTienda` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulosProveedores`
--

CREATE TABLE `articulosProveedores` (
  `idArticulo` int(11) NOT NULL,
  `idProveedor` int(11) NOT NULL,
  `crefProveedor` varchar(24) DEFAULT NULL,
  `coste` decimal(17,6) NOT NULL,
  `fechaActualizacion` date NOT NULL,
  `estado` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulosStocks`
--

CREATE TABLE `articulosStocks` (
  `id` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `stockMin` decimal(17,6) DEFAULT NULL,
  `stockMax` decimal(17,6) DEFAULT NULL,
  `stockOn` decimal(17,6) NOT NULL,
  `fecha_modificado` datetime NOT NULL DEFAULT current_timestamp(),
  `fechaRegularizacion` datetime DEFAULT NULL,
  `usuarioRegularizacion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulosTiendas`
--

CREATE TABLE `articulosTiendas` (
  `id` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `crefTienda` varchar(18) DEFAULT NULL,
  `idVirtuemart` int(11) DEFAULT NULL,
  `estado` varchar(12) NOT NULL,
  `fechaModificacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cierres`
--

CREATE TABLE `cierres` (
  `idCierre` int(11) NOT NULL,
  `FechaCierre` date NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `FechaInicio` datetime NOT NULL,
  `FechaFinal` datetime NOT NULL,
  `FechaCreacion` datetime NOT NULL,
  `Total` decimal(17,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cierres_ivas`
--

CREATE TABLE `cierres_ivas` (
  `id` int(11) NOT NULL,
  `idCierre` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `tipo_iva` int(11) NOT NULL,
  `importe_base` decimal(17,4) NOT NULL,
  `importe_iva` decimal(17,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cierres_usuariosFormasPago`
--

CREATE TABLE `cierres_usuariosFormasPago` (
  `id` int(11) NOT NULL,
  `idCierre` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `FormasPago` varchar(100) NOT NULL,
  `importe` decimal(17,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cierres_usuarios_tickets`
--

CREATE TABLE `cierres_usuarios_tickets` (
  `id` int(11) NOT NULL,
  `idCierre` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `Importe` decimal(17,4) NOT NULL,
  `Num_ticket_inicial` int(11) NOT NULL,
  `Num_ticket_final` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `idClientes` int(11) NOT NULL,
  `Nombre` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `razonsocial` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `nif` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `direccion` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `codpostal` varchar(32) DEFAULT NULL,
  `telefono` varchar(11) DEFAULT NULL,
  `movil` varchar(11) DEFAULT NULL,
  `fax` varchar(11) DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `estado` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `formasVenci` varchar(250) DEFAULT NULL,
  `fecha_creado` datetime NOT NULL DEFAULT current_timestamp(),
  `descuento_ticket` decimal(5,2) NOT NULL DEFAULT 3.00,
  `requiere_factura` tinyint(1) NOT NULL DEFAULT 0,
  `recargo_equivalencia` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `descuentos_tickets`
--

CREATE TABLE `descuentos_tickets` (
  `id` int(11) NOT NULL,
  `idCliente` int(11) NOT NULL,
  `descuentoCliente` decimal(4,2) NOT NULL,
  `fechaInicio` datetime NOT NULL,
  `fechaFin` datetime NOT NULL,
  `numTickets` int(11) NOT NULL,
  `importeTickets` decimal(17,2) NOT NULL,
  `importeDescuento` decimal(17,2) NOT NULL,
  `idTicket` int(11) DEFAULT NULL,
  `idUsuario` int(11) NOT NULL,
  `fechaCreacion` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` varchar(12) NOT NULL DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `faccliIva`
--

CREATE TABLE `faccliIva` (
  `id` int(11) NOT NULL,
  `idfaccli` int(11) NOT NULL,
  `Numfaccli` int(11) NOT NULL,
  `iva` int(11) DEFAULT NULL,
  `importeIva` decimal(17,2) DEFAULT NULL,
  `totalbase` decimal(17,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facclilinea`
--

CREATE TABLE `facclilinea` (
  `id` int(11) NOT NULL,
  `idfaccli` int(11) NOT NULL,
  `Numfaccli` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `cref` varchar(18) DEFAULT NULL,
  `ccodbar` varchar(18) DEFAULT NULL,
  `cdetalle` varchar(100) DEFAULT NULL,
  `ncant` decimal(17,6) DEFAULT NULL,
  `nunidades` decimal(17,6) DEFAULT NULL,
  `precioCiva` decimal(17,2) DEFAULT NULL,
  `iva` decimal(4,2) DEFAULT NULL,
  `nfila` int(11) DEFAULT NULL,
  `estadoLinea` varchar(12) DEFAULT NULL,
  `NumalbCli` int(100) DEFAULT NULL,
  `pvpSiva` decimal(17,6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `faccliltemporales`
--

CREATE TABLE `faccliltemporales` (
  `id` int(11) NOT NULL,
  `Numfaccli` int(11) DEFAULT NULL,
  `idTienda` int(11) DEFAULT NULL,
  `idUsuario` int(11) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `fechaInicio` datetime DEFAULT NULL,
  `fechaVencimiento` datetime DEFAULT NULL,
  `idCliente` int(11) DEFAULT NULL,
  `total` decimal(17,6) DEFAULT NULL,
  `total_ivas` varchar(250) DEFAULT NULL,
  `Productos` mediumblob DEFAULT NULL,
  `Albaranes` varbinary(50000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facclit`
--

CREATE TABLE `facclit` (
  `id` int(11) NOT NULL,
  `Numfaccli` int(11) DEFAULT NULL,
  `Numtemp_faccli` int(11) DEFAULT NULL,
  `Fecha` datetime NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idCliente` int(11) NOT NULL,
  `estado` varchar(12) DEFAULT NULL,
  `total` decimal(17,2) DEFAULT NULL,
  `fechaCreacion` datetime DEFAULT NULL,
  `fechaVencimiento` datetime DEFAULT NULL,
  `fechaModificacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facProCobros`
--

CREATE TABLE `facProCobros` (
  `id` int(11) NOT NULL,
  `idFactura` int(11) NOT NULL,
  `idFormasPago` int(11) NOT NULL,
  `FechaPago` date NOT NULL,
  `importe` float NOT NULL,
  `Referencia` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facproIva`
--

CREATE TABLE `facproIva` (
  `id` int(11) NOT NULL,
  `idfacpro` int(11) NOT NULL,
  `Numfacpro` int(11) NOT NULL,
  `iva` int(11) DEFAULT NULL,
  `importeIva` decimal(17,2) DEFAULT NULL,
  `totalbase` decimal(17,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facprolinea`
--

CREATE TABLE `facprolinea` (
  `id` int(11) NOT NULL,
  `idfacpro` int(11) NOT NULL,
  `Numfacpro` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `cref` varchar(18) DEFAULT NULL,
  `ccodbar` varchar(18) DEFAULT NULL,
  `cdetalle` varchar(100) DEFAULT NULL,
  `ncant` decimal(17,6) DEFAULT NULL,
  `nunidades` decimal(17,6) DEFAULT NULL,
  `costeSiva` decimal(17,4) DEFAULT NULL,
  `iva` decimal(4,2) DEFAULT NULL,
  `nfila` int(11) DEFAULT NULL,
  `estadoLinea` varchar(12) DEFAULT NULL,
  `ref_prov` varchar(250) DEFAULT NULL,
  `idalbpro` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facproltemporales`
--

CREATE TABLE `facproltemporales` (
  `id` int(11) NOT NULL,
  `numfacpro` int(11) DEFAULT NULL,
  `estadoFacPro` varchar(12) DEFAULT NULL,
  `idTienda` int(11) DEFAULT NULL,
  `idUsuario` int(11) DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `fechaInicio` datetime DEFAULT NULL,
  `fechaFinal` datetime DEFAULT NULL,
  `idProveedor` int(11) DEFAULT NULL,
  `total` decimal(17,6) DEFAULT NULL,
  `total_ivas` varchar(250) DEFAULT NULL,
  `Productos` mediumblob DEFAULT NULL,
  `Albaranes` varbinary(50000) DEFAULT NULL,
  `Su_num_factura` varchar(20) DEFAULT NULL,
  `FacCobros` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facprot`
--

CREATE TABLE `facprot` (
  `id` int(11) NOT NULL,
  `Numfacpro` int(11) DEFAULT NULL,
  `Numtemp_facpro` int(11) DEFAULT NULL,
  `Su_num_factura` varchar(20) DEFAULT NULL,
  `Fecha` datetime NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idProveedor` int(11) NOT NULL,
  `estado` varchar(12) DEFAULT NULL,
  `formaPago` varchar(12) DEFAULT NULL,
  `entregado` decimal(17,2) DEFAULT NULL,
  `total_siniva` decimal(17,6) NOT NULL,
  `total` decimal(17,2) DEFAULT NULL,
  `FechaVencimiento` date DEFAULT NULL,
  `fechaModificacion` datetime DEFAULT NULL,
  `modify_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fac_cobros`
--

CREATE TABLE `fac_cobros` (
  `id` int(11) NOT NULL,
  `idFactura` int(11) NOT NULL,
  `idFormasPago` int(11) NOT NULL,
  `FechaPago` date NOT NULL,
  `importe` float NOT NULL,
  `Referencia` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `familias`
--

CREATE TABLE `familias` (
  `idFamilia` int(11) NOT NULL,
  `familiaNombre` varchar(100) NOT NULL DEFAULT '',
  `familiaPadre` int(11) NOT NULL,
  `beneficiomedio` decimal(5,2) DEFAULT NULL,
  `mostrar_tpv` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `familiasTienda`
--

CREATE TABLE `familiasTienda` (
  `id` int(11) NOT NULL,
  `idFamilia` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idFamilia_tienda` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formasPago`
--

CREATE TABLE `formasPago` (
  `id` int(11) NOT NULL,
  `descripcion` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historico_precios`
--

CREATE TABLE `historico_precios` (
  `id` int(11) NOT NULL,
  `idArticulo` int(10) NOT NULL,
  `Antes` decimal(17,4) NOT NULL,
  `Nuevo` decimal(17,4) NOT NULL,
  `Fecha_Creacion` datetime NOT NULL,
  `NumDoc` int(11) NOT NULL,
  `Dedonde` varchar(50) NOT NULL,
  `Tipo` varchar(50) NOT NULL,
  `idUsuario` int(5) DEFAULT NULL,
  `estado` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `importar_virtuemart_tickets`
--

CREATE TABLE `importar_virtuemart_tickets` (
  `id` int(11) NOT NULL,
  `idTicketst` int(11) NOT NULL,
  `Fecha` datetime NOT NULL,
  `estado` varchar(12) NOT NULL,
  `respuesta` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `indices`
--

CREATE TABLE `indices` (
  `id` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `numticket` int(11) NOT NULL,
  `tempticket` int(11) NOT NULL COMMENT 'Es el numero con guardo temporal ticket'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `iva`
--

CREATE TABLE `iva` (
  `idIva` int(11) NOT NULL,
  `descripcionIva` varchar(25) DEFAULT NULL,
  `iva` decimal(4,2) DEFAULT NULL,
  `recargo` decimal(4,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migraciones`
--

CREATE TABLE `migraciones` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos_configuracion`
--

CREATE TABLE `modulos_configuracion` (
  `id` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `nombre_modulo` varchar(50) NOT NULL,
  `configuracion` longtext NOT NULL,
  `fecha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulo_balanza`
--

CREATE TABLE `modulo_balanza` (
  `idBalanza` int(11) NOT NULL,
  `nombreBalanza` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `conTecla` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulo_balanza_plus`
--

CREATE TABLE `modulo_balanza_plus` (
  `id` int(11) NOT NULL,
  `idBalanza` int(11) NOT NULL,
  `plu` int(10) NOT NULL,
  `tecla` int(100) NOT NULL,
  `idArticulo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulo_etiquetado`
--

CREATE TABLE `modulo_etiquetado` (
  `id` int(11) NOT NULL,
  `num_lote` int(11) DEFAULT NULL,
  `tipo` varchar(12) NOT NULL,
  `fecha_env` datetime NOT NULL,
  `fecha_cad` date NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `numAlb` int(11) NOT NULL,
  `estado` varchar(12) NOT NULL,
  `productos` mediumblob NOT NULL,
  `idUsuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulo_etiquetado_temporal`
--

CREATE TABLE `modulo_etiquetado_temporal` (
  `id` int(11) NOT NULL,
  `num_lote` int(11) NOT NULL,
  `tipo` varchar(12) NOT NULL,
  `fecha_env` datetime NOT NULL,
  `fecha_cad` date NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `numAlb` int(11) NOT NULL,
  `estado` varchar(12) NOT NULL,
  `productos` mediumblob NOT NULL,
  `idUsuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulo_importar_registro`
--

CREATE TABLE `modulo_importar_registro` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `datos_fichero` mediumtext NOT NULL,
  `token` text NOT NULL,
  `type` text NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `estado` varchar(250) NOT NULL,
  `Registros_originales` int(11) NOT NULL,
  `nulos` int(11) NOT NULL DEFAULT 0,
  `errores` int(11) NOT NULL DEFAULT 0,
  `campos` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulo_incidencia`
--

CREATE TABLE `modulo_incidencia` (
  `id` int(11) NOT NULL,
  `num_incidencia` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `dedonde` varchar(15) NOT NULL,
  `mensaje` varchar(255) NOT NULL,
  `datos` varbinary(10000) NOT NULL,
  `estado` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedcliAlb`
--

CREATE TABLE `pedcliAlb` (
  `id` int(11) NOT NULL,
  `idAlbaran` int(11) DEFAULT NULL,
  `numAlbaran` int(11) DEFAULT NULL,
  `idPedido` int(11) DEFAULT NULL,
  `numPedido` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedcliIva`
--

CREATE TABLE `pedcliIva` (
  `id` int(11) NOT NULL,
  `idpedcli` int(11) NOT NULL,
  `Numpedcli` int(11) NOT NULL,
  `iva` int(11) NOT NULL,
  `importeIva` decimal(17,2) NOT NULL,
  `totalbase` decimal(17,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedclilinea`
--

CREATE TABLE `pedclilinea` (
  `id` int(11) NOT NULL,
  `idpedcli` int(11) NOT NULL,
  `Numpedcli` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `cref` varchar(18) DEFAULT NULL,
  `ccodbar` varchar(18) DEFAULT NULL,
  `cdetalle` varchar(100) NOT NULL,
  `ncant` decimal(17,6) DEFAULT NULL,
  `nunidades` decimal(17,6) DEFAULT NULL,
  `precioCiva` decimal(17,2) NOT NULL,
  `iva` decimal(4,2) NOT NULL,
  `nfila` int(11) NOT NULL,
  `estadoLinea` varchar(12) NOT NULL,
  `pvpSiva` decimal(17,6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedcliltemporales`
--

CREATE TABLE `pedcliltemporales` (
  `id` int(11) NOT NULL,
  `idTienda` int(11) DEFAULT NULL,
  `idUsuario` int(11) DEFAULT NULL,
  `fechaInicio` datetime DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `idCliente` int(11) DEFAULT NULL,
  `total` decimal(17,6) DEFAULT NULL,
  `total_ivas` varchar(250) DEFAULT NULL,
  `Productos` mediumblob DEFAULT NULL,
  `Numpedcli` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedclit`
--

CREATE TABLE `pedclit` (
  `id` int(11) NOT NULL,
  `Numpedcli` int(11) DEFAULT NULL,
  `Numtemp_pedcli` int(11) NOT NULL,
  `Fecha` date NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idCliente` int(11) NOT NULL,
  `estado` varchar(12) DEFAULT NULL,
  `formaPago` varchar(12) DEFAULT NULL,
  `entregado` decimal(17,2) DEFAULT NULL,
  `total` decimal(17,2) DEFAULT NULL,
  `fechaCreacion` datetime DEFAULT NULL,
  `fechaModificacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedproAlb`
--

CREATE TABLE `pedproAlb` (
  `id` int(11) NOT NULL,
  `idAlbaran` int(11) DEFAULT NULL,
  `numAlbaran` int(11) DEFAULT NULL,
  `idPedido` int(11) DEFAULT NULL,
  `numPedido` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedproIva`
--

CREATE TABLE `pedproIva` (
  `id` int(11) NOT NULL,
  `idpedpro` int(11) NOT NULL,
  `Numpedpro` int(11) DEFAULT NULL,
  `iva` int(11) NOT NULL,
  `importeIva` decimal(17,2) NOT NULL,
  `totalbase` decimal(17,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedprolinea`
--

CREATE TABLE `pedprolinea` (
  `id` int(11) NOT NULL,
  `idpedpro` int(11) NOT NULL,
  `Numpedpro` int(11) DEFAULT NULL,
  `idArticulo` int(11) NOT NULL,
  `cref` varchar(18) DEFAULT NULL,
  `ref_prov` varchar(24) NOT NULL,
  `ccodbar` varchar(18) DEFAULT NULL,
  `cdetalle` varchar(100) NOT NULL,
  `ncant` decimal(17,6) DEFAULT NULL,
  `nunidades` decimal(17,6) DEFAULT NULL,
  `costeSiva` decimal(17,4) NOT NULL,
  `iva` decimal(4,2) NOT NULL,
  `nfila` int(11) NOT NULL,
  `estadoLinea` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedprot`
--

CREATE TABLE `pedprot` (
  `id` int(11) NOT NULL,
  `Numpedpro` int(11) DEFAULT NULL,
  `Numtemp_pedpro` int(11) NOT NULL,
  `Fecha` datetime NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idProveedor` int(11) NOT NULL,
  `estado` varchar(12) DEFAULT NULL,
  `formaPago` varchar(12) DEFAULT NULL,
  `entregado` decimal(17,2) DEFAULT NULL,
  `total_siniva` decimal(17,6) NOT NULL,
  `total` decimal(17,2) DEFAULT NULL,
  `fechaCreacion` datetime DEFAULT NULL,
  `fechaModificacion` datetime DEFAULT NULL,
  `modify_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedprotemporales`
--

CREATE TABLE `pedprotemporales` (
  `id` int(11) NOT NULL,
  `estadoPedPro` varchar(12) DEFAULT NULL,
  `idTienda` int(11) DEFAULT NULL,
  `idUsuario` int(11) DEFAULT NULL,
  `Fecha` datetime NOT NULL,
  `fechaInicio` datetime DEFAULT NULL,
  `fechaFinal` datetime DEFAULT NULL,
  `idProveedor` int(11) DEFAULT NULL,
  `total` decimal(17,6) DEFAULT NULL,
  `total_ivas` varchar(250) DEFAULT NULL,
  `Productos` mediumblob DEFAULT NULL,
  `idPedpro` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `modulo` varchar(50) DEFAULT NULL,
  `vista` varchar(50) DEFAULT NULL,
  `accion` varchar(50) DEFAULT NULL,
  `permiso` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `idProveedor` int(11) NOT NULL,
  `nombrecomercial` varchar(100) DEFAULT NULL,
  `razonsocial` varchar(100) NOT NULL,
  `nif` varchar(10) NOT NULL,
  `direccion` varchar(100) NOT NULL,
  `telefono` varchar(11) DEFAULT NULL,
  `fax` varchar(11) DEFAULT NULL,
  `movil` varchar(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `fecha_creado` datetime NOT NULL,
  `estado` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stocksRegularizacion`
--

CREATE TABLE `stocksRegularizacion` (
  `id` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL DEFAULT 1,
  `fechaRegularizacion` datetime NOT NULL DEFAULT current_timestamp(),
  `stockActual` decimal(17,6) NOT NULL,
  `stockModif` decimal(17,6) NOT NULL,
  `stockFinal` decimal(17,6) NOT NULL,
  `stockOperacion` int(1) NOT NULL DEFAULT 1,
  `idUsuario` int(11) NOT NULL,
  `idAlbaran` int(11) DEFAULT NULL,
  `estado` int(11) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
  `fechaFinal` datetime DEFAULT NULL,
  `idClientes` int(11) NOT NULL,
  `total` decimal(17,6) NOT NULL,
  `Productos` mediumblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tiendas`
--

CREATE TABLE `tiendas` (
  `idTienda` int(2) NOT NULL,
  `tipoTienda` varchar(10) NOT NULL,
  `razonsocial` varchar(100) NOT NULL,
  `nif` varchar(10) NOT NULL,
  `telefono` varchar(11) NOT NULL,
  `estado` varchar(12) DEFAULT NULL,
  `NombreComercial` varchar(100) DEFAULT NULL,
  `direccion` varchar(100) NOT NULL,
  `ano` varchar(4) DEFAULT NULL,
  `dominio` varchar(100) DEFAULT NULL,
  `key_api` varchar(30) DEFAULT NULL,
  `servidor_email` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tiposVencimiento`
--

CREATE TABLE `tiposVencimiento` (
  `id` int(11) NOT NULL,
  `descripcion` varchar(20) NOT NULL,
  `dias` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `estado` varchar(12) NOT NULL COMMENT 'estado',
  `nombre` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `albclifac`
--
ALTER TABLE `albclifac`
  ADD PRIMARY KEY (`id`),
  ADD KEY `albclifac_idalbaran_foreign` (`idAlbaran`),
  ADD KEY `albclifac_idfactura_foreign` (`idFactura`);

--
-- Indices de la tabla `albcliIva`
--
ALTER TABLE `albcliIva`
  ADD PRIMARY KEY (`id`),
  ADD KEY `albcliiva_idalbcli_foreign` (`idalbcli`);

--
-- Indices de la tabla `albclilinea`
--
ALTER TABLE `albclilinea`
  ADD PRIMARY KEY (`id`),
  ADD KEY `albclilinea_ibfk_1` (`idalbcli`),
  ADD KEY `albclilinea_idarticulo_foreign` (`idArticulo`);

--
-- Indices de la tabla `albcliltemporales`
--
ALTER TABLE `albcliltemporales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `albclit`
--
ALTER TABLE `albclit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `albclit_idcliente_foreign` (`idCliente`),
  ADD KEY `albclit_idtienda_foreign` (`idTienda`),
  ADD KEY `albclit_idusuario_foreign` (`idUsuario`);

--
-- Indices de la tabla `albprofac`
--
ALTER TABLE `albprofac`
  ADD PRIMARY KEY (`id`),
  ADD KEY `albprofac_idalbaran_foreign` (`idAlbaran`),
  ADD KEY `albprofac_idfactura_foreign` (`idFactura`);

--
-- Indices de la tabla `albproIva`
--
ALTER TABLE `albproIva`
  ADD PRIMARY KEY (`id`),
  ADD KEY `albproiva_idalbpro_foreign` (`idalbpro`);

--
-- Indices de la tabla `albprolinea`
--
ALTER TABLE `albprolinea`
  ADD PRIMARY KEY (`id`),
  ADD KEY `albprolinea_ibfk_1` (`idalbpro`),
  ADD KEY `albprolinea_idarticulo_foreign` (`idArticulo`);

--
-- Indices de la tabla `albproltemporales`
--
ALTER TABLE `albproltemporales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `albprot`
--
ALTER TABLE `albprot`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `articulos`
--
ALTER TABLE `articulos`
  ADD PRIMARY KEY (`idArticulo`),
  ADD KEY `idProveedor` (`idProveedor`);

--
-- Indices de la tabla `articulosClientes`
--
ALTER TABLE `articulosClientes`
  ADD PRIMARY KEY (`idArticulo`,`idClientes`);

--
-- Indices de la tabla `articulosCodigoBarras`
--
ALTER TABLE `articulosCodigoBarras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `articulosCodigoBarras_ibfk_1` (`idArticulo`);

--
-- Indices de la tabla `articulosFamilias`
--
ALTER TABLE `articulosFamilias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idArticulo` (`idArticulo`,`idFamilia`) USING BTREE,
  ADD UNIQUE KEY `idArticulo_2` (`idArticulo`,`idFamilia`),
  ADD KEY `fk_categoriaFamilias` (`idFamilia`),
  ADD KEY `fk_articulos` (`idArticulo`);

--
-- Indices de la tabla `articulosPrecios`
--
ALTER TABLE `articulosPrecios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idArticulo` (`idArticulo`,`idTienda`) USING BTREE;

--
-- Indices de la tabla `articulosProveedores`
--
ALTER TABLE `articulosProveedores`
  ADD PRIMARY KEY (`idArticulo`,`idProveedor`),
  ADD KEY `articulosproveedores_idproveedor_foreign` (`idProveedor`);

--
-- Indices de la tabla `articulosStocks`
--
ALTER TABLE `articulosStocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `articulosstocks_idarticulo_foreign` (`idArticulo`),
  ADD KEY `articulosstocks_idtienda_foreign` (`idTienda`);

--
-- Indices de la tabla `articulosTiendas`
--
ALTER TABLE `articulosTiendas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idTienda` (`idTienda`),
  ADD KEY `idTienda_idArticulo` (`idArticulo`,`idTienda`) USING BTREE;

--
-- Indices de la tabla `cierres`
--
ALTER TABLE `cierres`
  ADD PRIMARY KEY (`idCierre`),
  ADD KEY `cierres_idtienda_foreign` (`idTienda`),
  ADD KEY `cierres_idusuario_foreign` (`idUsuario`);

--
-- Indices de la tabla `cierres_ivas`
--
ALTER TABLE `cierres_ivas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cierres_ivas_idcierre_foreign` (`idCierre`),
  ADD KEY `cierres_ivas_idtienda_foreign` (`idTienda`);

--
-- Indices de la tabla `cierres_usuariosFormasPago`
--
ALTER TABLE `cierres_usuariosFormasPago`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cierres_usuariosformaspago_idcierre_foreign` (`idCierre`),
  ADD KEY `cierres_usuariosformaspago_idtienda_foreign` (`idTienda`),
  ADD KEY `cierres_usuariosformaspago_idusuario_foreign` (`idUsuario`);

--
-- Indices de la tabla `cierres_usuarios_tickets`
--
ALTER TABLE `cierres_usuarios_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cierres_usuarios_tickets_idcierre_foreign` (`idCierre`),
  ADD KEY `cierres_usuarios_tickets_idtienda_foreign` (`idTienda`),
  ADD KEY `cierres_usuarios_tickets_idusuario_foreign` (`idUsuario`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`idClientes`);

--
-- Indices de la tabla `descuentos_tickets`
--
ALTER TABLE `descuentos_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente` (`idCliente`),
  ADD KEY `descuentos_tickets_idticket_foreign` (`idTicket`),
  ADD KEY `descuentos_tickets_idusuario_foreign` (`idUsuario`);

--
-- Indices de la tabla `faccliIva`
--
ALTER TABLE `faccliIva`
  ADD PRIMARY KEY (`id`),
  ADD KEY `faccliiva_idfaccli_foreign` (`idfaccli`);

--
-- Indices de la tabla `facclilinea`
--
ALTER TABLE `facclilinea`
  ADD PRIMARY KEY (`id`),
  ADD KEY `facclilinea_idarticulo_foreign` (`idArticulo`),
  ADD KEY `facclilinea_idfaccli_foreign` (`idfaccli`),
  ADD KEY `facclilinea_numalbcli_foreign` (`NumalbCli`);

--
-- Indices de la tabla `faccliltemporales`
--
ALTER TABLE `faccliltemporales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `facclit`
--
ALTER TABLE `facclit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `facclit_idcliente_foreign` (`idCliente`),
  ADD KEY `facclit_idtienda_foreign` (`idTienda`),
  ADD KEY `facclit_idusuario_foreign` (`idUsuario`);

--
-- Indices de la tabla `facProCobros`
--
ALTER TABLE `facProCobros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `facprocobros_idfactura_foreign` (`idFactura`),
  ADD KEY `facprocobros_idformaspago_foreign` (`idFormasPago`);

--
-- Indices de la tabla `facproIva`
--
ALTER TABLE `facproIva`
  ADD PRIMARY KEY (`id`),
  ADD KEY `facproiva_idfacpro_foreign` (`idfacpro`);

--
-- Indices de la tabla `facprolinea`
--
ALTER TABLE `facprolinea`
  ADD PRIMARY KEY (`id`),
  ADD KEY `facprolinea_idarticulo_foreign` (`idArticulo`),
  ADD KEY `facprolinea_idfacpro_foreign` (`idfacpro`);

--
-- Indices de la tabla `facproltemporales`
--
ALTER TABLE `facproltemporales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `facprot`
--
ALTER TABLE `facprot`
  ADD PRIMARY KEY (`id`),
  ADD KEY `facprot_idproveedor_foreign` (`idProveedor`),
  ADD KEY `facprot_idtienda_foreign` (`idTienda`),
  ADD KEY `facprot_idusuario_foreign` (`idUsuario`);

--
-- Indices de la tabla `fac_cobros`
--
ALTER TABLE `fac_cobros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fac_cobros_idfactura_foreign` (`idFactura`),
  ADD KEY `fac_cobros_idformaspago_foreign` (`idFormasPago`);

--
-- Indices de la tabla `familias`
--
ALTER TABLE `familias`
  ADD PRIMARY KEY (`idFamilia`);

--
-- Indices de la tabla `familiasTienda`
--
ALTER TABLE `familiasTienda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `familiastienda_idfamilia_foreign` (`idFamilia`),
  ADD KEY `familiastienda_idtienda_foreign` (`idTienda`);

--
-- Indices de la tabla `formasPago`
--
ALTER TABLE `formasPago`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `historico_precios`
--
ALTER TABLE `historico_precios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `historico_precios_idarticulo_foreign` (`idArticulo`),
  ADD KEY `historico_precios_idusuario_foreign` (`idUsuario`);

--
-- Indices de la tabla `importar_virtuemart_tickets`
--
ALTER TABLE `importar_virtuemart_tickets`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `indices`
--
ALTER TABLE `indices`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `iva`
--
ALTER TABLE `iva`
  ADD PRIMARY KEY (`idIva`);

--
-- Indices de la tabla `migraciones`
--
ALTER TABLE `migraciones`
  ADD PRIMARY KEY (`version`);

--
-- Indices de la tabla `modulos_configuracion`
--
ALTER TABLE `modulos_configuracion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `modulo_balanza`
--
ALTER TABLE `modulo_balanza`
  ADD PRIMARY KEY (`idBalanza`);

--
-- Indices de la tabla `modulo_balanza_plus`
--
ALTER TABLE `modulo_balanza_plus`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `modulo_etiquetado`
--
ALTER TABLE `modulo_etiquetado`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `modulo_etiquetado_temporal`
--
ALTER TABLE `modulo_etiquetado_temporal`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `modulo_importar_registro`
--
ALTER TABLE `modulo_importar_registro`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `modulo_incidencia`
--
ALTER TABLE `modulo_incidencia`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pedcliAlb`
--
ALTER TABLE `pedcliAlb`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedclialb_idalbaran_foreign` (`idAlbaran`),
  ADD KEY `pedclialb_idpedido_foreign` (`idPedido`);

--
-- Indices de la tabla `pedcliIva`
--
ALTER TABLE `pedcliIva`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedcliiva_idpedcli_foreign` (`idpedcli`);

--
-- Indices de la tabla `pedclilinea`
--
ALTER TABLE `pedclilinea`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedclilinea_idarticulo_foreign` (`idArticulo`),
  ADD KEY `pedclilinea_idpedcli_foreign` (`idpedcli`);

--
-- Indices de la tabla `pedcliltemporales`
--
ALTER TABLE `pedcliltemporales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedcliltemporales_idcliente_foreign` (`idCliente`),
  ADD KEY `pedcliltemporales_idtienda_foreign` (`idTienda`),
  ADD KEY `pedcliltemporales_idusuario_foreign` (`idUsuario`),
  ADD KEY `pedcliltemporales_numpedcli_foreign` (`Numpedcli`);

--
-- Indices de la tabla `pedclit`
--
ALTER TABLE `pedclit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedclit_idcliente_foreign` (`idCliente`),
  ADD KEY `pedclit_idtienda_foreign` (`idTienda`),
  ADD KEY `pedclit_idusuario_foreign` (`idUsuario`);

--
-- Indices de la tabla `pedproAlb`
--
ALTER TABLE `pedproAlb`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedproalb_idalbaran_foreign` (`idAlbaran`),
  ADD KEY `pedproalb_idpedido_foreign` (`idPedido`);

--
-- Indices de la tabla `pedproIva`
--
ALTER TABLE `pedproIva`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedproiva_idpedpro_foreign` (`idpedpro`);

--
-- Indices de la tabla `pedprolinea`
--
ALTER TABLE `pedprolinea`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedprolinea_idarticulo_foreign` (`idArticulo`),
  ADD KEY `pedprolinea_idpedpro_foreign` (`idpedpro`);

--
-- Indices de la tabla `pedprot`
--
ALTER TABLE `pedprot`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedprot_idproveedor_foreign` (`idProveedor`),
  ADD KEY `pedprot_idtienda_foreign` (`idTienda`),
  ADD KEY `pedprot_idusuario_foreign` (`idUsuario`);

--
-- Indices de la tabla `pedprotemporales`
--
ALTER TABLE `pedprotemporales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedprotemporales_idpedpro_foreign` (`idPedpro`),
  ADD KEY `pedprotemporales_idproveedor_foreign` (`idProveedor`),
  ADD KEY `pedprotemporales_idtienda_foreign` (`idTienda`),
  ADD KEY `pedprotemporales_idusuario_foreign` (`idUsuario`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`idProveedor`);
ALTER TABLE `proveedores` ADD FULLTEXT KEY `nombrecomercial` (`nombrecomercial`);

--
-- Indices de la tabla `stocksRegularizacion`
--
ALTER TABLE `stocksRegularizacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stocksregularizacion_idalbaran_foreign` (`idAlbaran`),
  ADD KEY `stocksregularizacion_idarticulo_foreign` (`idArticulo`),
  ADD KEY `stocksregularizacion_idtienda_foreign` (`idTienda`),
  ADD KEY `stocksregularizacion_idusuario_foreign` (`idUsuario`);

--
-- Indices de la tabla `ticketslinea`
--
ALTER TABLE `ticketslinea`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticketslinea_idarticulo_foreign` (`idArticulo`),
  ADD KEY `ticketslinea_idticketst_foreign` (`idticketst`);

--
-- Indices de la tabla `ticketst`
--
ALTER TABLE `ticketst`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticketst_idcliente_foreign` (`idCliente`),
  ADD KEY `ticketst_idtienda_foreign` (`idTienda`),
  ADD KEY `ticketst_idusuario_foreign` (`idUsuario`);

--
-- Indices de la tabla `ticketstemporales`
--
ALTER TABLE `ticketstemporales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticketstemporales_idclientes_foreign` (`idClientes`),
  ADD KEY `ticketstemporales_idtienda_foreign` (`idTienda`),
  ADD KEY `ticketstemporales_idusuario_foreign` (`idUsuario`);

--
-- Indices de la tabla `ticketstIva`
--
ALTER TABLE `ticketstIva`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticketstiva_idticketst_foreign` (`idticketst`);

--
-- Indices de la tabla `tiendas`
--
ALTER TABLE `tiendas`
  ADD PRIMARY KEY (`idTienda`);

--
-- Indices de la tabla `tiposVencimiento`
--
ALTER TABLE `tiposVencimiento`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `albclifac`
--
ALTER TABLE `albclifac`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `albcliIva`
--
ALTER TABLE `albcliIva`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `albclilinea`
--
ALTER TABLE `albclilinea`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `albcliltemporales`
--
ALTER TABLE `albcliltemporales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `albclit`
--
ALTER TABLE `albclit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `albprofac`
--
ALTER TABLE `albprofac`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `albproIva`
--
ALTER TABLE `albproIva`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `albprolinea`
--
ALTER TABLE `albprolinea`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `albproltemporales`
--
ALTER TABLE `albproltemporales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `albprot`
--
ALTER TABLE `albprot`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `articulos`
--
ALTER TABLE `articulos`
  MODIFY `idArticulo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `articulosCodigoBarras`
--
ALTER TABLE `articulosCodigoBarras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `articulosFamilias`
--
ALTER TABLE `articulosFamilias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `articulosPrecios`
--
ALTER TABLE `articulosPrecios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `articulosStocks`
--
ALTER TABLE `articulosStocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `articulosTiendas`
--
ALTER TABLE `articulosTiendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cierres`
--
ALTER TABLE `cierres`
  MODIFY `idCierre` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cierres_ivas`
--
ALTER TABLE `cierres_ivas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cierres_usuariosFormasPago`
--
ALTER TABLE `cierres_usuariosFormasPago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cierres_usuarios_tickets`
--
ALTER TABLE `cierres_usuarios_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `idClientes` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `descuentos_tickets`
--
ALTER TABLE `descuentos_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `faccliIva`
--
ALTER TABLE `faccliIva`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facclilinea`
--
ALTER TABLE `facclilinea`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `faccliltemporales`
--
ALTER TABLE `faccliltemporales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facclit`
--
ALTER TABLE `facclit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facProCobros`
--
ALTER TABLE `facProCobros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facproIva`
--
ALTER TABLE `facproIva`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facprolinea`
--
ALTER TABLE `facprolinea`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facproltemporales`
--
ALTER TABLE `facproltemporales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facprot`
--
ALTER TABLE `facprot`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fac_cobros`
--
ALTER TABLE `fac_cobros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `familias`
--
ALTER TABLE `familias`
  MODIFY `idFamilia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `familiasTienda`
--
ALTER TABLE `familiasTienda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `formasPago`
--
ALTER TABLE `formasPago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historico_precios`
--
ALTER TABLE `historico_precios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `importar_virtuemart_tickets`
--
ALTER TABLE `importar_virtuemart_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `indices`
--
ALTER TABLE `indices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `iva`
--
ALTER TABLE `iva`
  MODIFY `idIva` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulos_configuracion`
--
ALTER TABLE `modulos_configuracion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulo_balanza`
--
ALTER TABLE `modulo_balanza`
  MODIFY `idBalanza` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulo_balanza_plus`
--
ALTER TABLE `modulo_balanza_plus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulo_etiquetado`
--
ALTER TABLE `modulo_etiquetado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulo_etiquetado_temporal`
--
ALTER TABLE `modulo_etiquetado_temporal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulo_importar_registro`
--
ALTER TABLE `modulo_importar_registro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulo_incidencia`
--
ALTER TABLE `modulo_incidencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedcliAlb`
--
ALTER TABLE `pedcliAlb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedcliIva`
--
ALTER TABLE `pedcliIva`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedclilinea`
--
ALTER TABLE `pedclilinea`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedcliltemporales`
--
ALTER TABLE `pedcliltemporales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedclit`
--
ALTER TABLE `pedclit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedproAlb`
--
ALTER TABLE `pedproAlb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedproIva`
--
ALTER TABLE `pedproIva`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedprolinea`
--
ALTER TABLE `pedprolinea`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedprot`
--
ALTER TABLE `pedprot`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedprotemporales`
--
ALTER TABLE `pedprotemporales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `idProveedor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `stocksRegularizacion`
--
ALTER TABLE `stocksRegularizacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticketslinea`
--
ALTER TABLE `ticketslinea`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticketst`
--
ALTER TABLE `ticketst`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticketstemporales`
--
ALTER TABLE `ticketstemporales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticketstIva`
--
ALTER TABLE `ticketstIva`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tiendas`
--
ALTER TABLE `tiendas`
  MODIFY `idTienda` int(2) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tiposVencimiento`
--
ALTER TABLE `tiposVencimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `albclifac`
--
ALTER TABLE `albclifac`
  ADD CONSTRAINT `albclifac_idalbaran_foreign` FOREIGN KEY (`idAlbaran`) REFERENCES `albclit` (`id`),
  ADD CONSTRAINT `albclifac_idfactura_foreign` FOREIGN KEY (`idFactura`) REFERENCES `facclit` (`id`);

--
-- Filtros para la tabla `albcliIva`
--
ALTER TABLE `albcliIva`
  ADD CONSTRAINT `albcliiva_idalbcli_foreign` FOREIGN KEY (`idalbcli`) REFERENCES `albclit` (`id`);

--
-- Filtros para la tabla `albclilinea`
--
ALTER TABLE `albclilinea`
  ADD CONSTRAINT `albclilinea_ibfk_1` FOREIGN KEY (`idalbcli`) REFERENCES `albclit` (`id`),
  ADD CONSTRAINT `albclilinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`);

--
-- Filtros para la tabla `albclit`
--
ALTER TABLE `albclit`
  ADD CONSTRAINT `albclit_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  ADD CONSTRAINT `albclit_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `albclit_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `albprofac`
--
ALTER TABLE `albprofac`
  ADD CONSTRAINT `albprofac_idalbaran_foreign` FOREIGN KEY (`idAlbaran`) REFERENCES `albprot` (`id`),
  ADD CONSTRAINT `albprofac_idfactura_foreign` FOREIGN KEY (`idFactura`) REFERENCES `facprot` (`id`);

--
-- Filtros para la tabla `albproIva`
--
ALTER TABLE `albproIva`
  ADD CONSTRAINT `albproiva_idalbpro_foreign` FOREIGN KEY (`idalbpro`) REFERENCES `albprot` (`id`);

--
-- Filtros para la tabla `albprolinea`
--
ALTER TABLE `albprolinea`
  ADD CONSTRAINT `albprolinea_ibfk_1` FOREIGN KEY (`idalbpro`) REFERENCES `albprot` (`id`),
  ADD CONSTRAINT `albprolinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`);

--
-- Filtros para la tabla `articulosCodigoBarras`
--
ALTER TABLE `articulosCodigoBarras`
  ADD CONSTRAINT `articulosCodigoBarras_ibfk_1` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`);

--
-- Filtros para la tabla `articulosFamilias`
--
ALTER TABLE `articulosFamilias`
  ADD CONSTRAINT `articulosFamilias_ibfk_1` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`);

--
-- Filtros para la tabla `articulosPrecios`
--
ALTER TABLE `articulosPrecios`
  ADD CONSTRAINT `articulosPrecios_ibfk_1` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`);

--
-- Filtros para la tabla `articulosProveedores`
--
ALTER TABLE `articulosProveedores`
  ADD CONSTRAINT `articulosproveedores_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `articulosproveedores_idproveedor_foreign` FOREIGN KEY (`idProveedor`) REFERENCES `proveedores` (`idProveedor`);

--
-- Filtros para la tabla `articulosStocks`
--
ALTER TABLE `articulosStocks`
  ADD CONSTRAINT `articulosstocks_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `articulosstocks_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`);

--
-- Filtros para la tabla `articulosTiendas`
--
ALTER TABLE `articulosTiendas`
  ADD CONSTRAINT `articulostiendas_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `articulostiendas_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`);

--
-- Filtros para la tabla `cierres`
--
ALTER TABLE `cierres`
  ADD CONSTRAINT `cierres_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `cierres_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `cierres_ivas`
--
ALTER TABLE `cierres_ivas`
  ADD CONSTRAINT `cierres_ivas_idcierre_foreign` FOREIGN KEY (`idCierre`) REFERENCES `cierres` (`idCierre`),
  ADD CONSTRAINT `cierres_ivas_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`);

--
-- Filtros para la tabla `cierres_usuariosFormasPago`
--
ALTER TABLE `cierres_usuariosFormasPago`
  ADD CONSTRAINT `cierres_usuariosformaspago_idcierre_foreign` FOREIGN KEY (`idCierre`) REFERENCES `cierres` (`idCierre`),
  ADD CONSTRAINT `cierres_usuariosformaspago_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `cierres_usuariosformaspago_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `cierres_usuarios_tickets`
--
ALTER TABLE `cierres_usuarios_tickets`
  ADD CONSTRAINT `cierres_usuarios_tickets_idcierre_foreign` FOREIGN KEY (`idCierre`) REFERENCES `cierres` (`idCierre`),
  ADD CONSTRAINT `cierres_usuarios_tickets_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `cierres_usuarios_tickets_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `descuentos_tickets`
--
ALTER TABLE `descuentos_tickets`
  ADD CONSTRAINT `descuentos_tickets_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  ADD CONSTRAINT `descuentos_tickets_idticket_foreign` FOREIGN KEY (`idTicket`) REFERENCES `ticketst` (`id`),
  ADD CONSTRAINT `descuentos_tickets_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `faccliIva`
--
ALTER TABLE `faccliIva`
  ADD CONSTRAINT `faccliiva_idfaccli_foreign` FOREIGN KEY (`idfaccli`) REFERENCES `facclit` (`id`);

--
-- Filtros para la tabla `facclilinea`
--
ALTER TABLE `facclilinea`
  ADD CONSTRAINT `facclilinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `facclilinea_idfaccli_foreign` FOREIGN KEY (`idfaccli`) REFERENCES `facclit` (`id`),
  ADD CONSTRAINT `facclilinea_numalbcli_foreign` FOREIGN KEY (`NumalbCli`) REFERENCES `albclit` (`id`);

--
-- Filtros para la tabla `facclit`
--
ALTER TABLE `facclit`
  ADD CONSTRAINT `facclit_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  ADD CONSTRAINT `facclit_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `facclit_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `facProCobros`
--
ALTER TABLE `facProCobros`
  ADD CONSTRAINT `facprocobros_idfactura_foreign` FOREIGN KEY (`idFactura`) REFERENCES `facprot` (`id`),
  ADD CONSTRAINT `facprocobros_idformaspago_foreign` FOREIGN KEY (`idFormasPago`) REFERENCES `formasPago` (`id`);

--
-- Filtros para la tabla `facproIva`
--
ALTER TABLE `facproIva`
  ADD CONSTRAINT `facproiva_idfacpro_foreign` FOREIGN KEY (`idfacpro`) REFERENCES `facprot` (`id`);

--
-- Filtros para la tabla `facprolinea`
--
ALTER TABLE `facprolinea`
  ADD CONSTRAINT `facprolinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `facprolinea_idfacpro_foreign` FOREIGN KEY (`idfacpro`) REFERENCES `facprot` (`id`);

--
-- Filtros para la tabla `facprot`
--
ALTER TABLE `facprot`
  ADD CONSTRAINT `facprot_idproveedor_foreign` FOREIGN KEY (`idProveedor`) REFERENCES `proveedores` (`idProveedor`),
  ADD CONSTRAINT `facprot_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `facprot_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `fac_cobros`
--
ALTER TABLE `fac_cobros`
  ADD CONSTRAINT `fac_cobros_idfactura_foreign` FOREIGN KEY (`idFactura`) REFERENCES `facclit` (`id`),
  ADD CONSTRAINT `fac_cobros_idformaspago_foreign` FOREIGN KEY (`idFormasPago`) REFERENCES `formasPago` (`id`);

--
-- Filtros para la tabla `familiasTienda`
--
ALTER TABLE `familiasTienda`
  ADD CONSTRAINT `familiastienda_idfamilia_foreign` FOREIGN KEY (`idFamilia`) REFERENCES `familias` (`idFamilia`),
  ADD CONSTRAINT `familiastienda_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`);

--
-- Filtros para la tabla `historico_precios`
--
ALTER TABLE `historico_precios`
  ADD CONSTRAINT `historico_precios_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `historico_precios_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pedcliAlb`
--
ALTER TABLE `pedcliAlb`
  ADD CONSTRAINT `pedclialb_idalbaran_foreign` FOREIGN KEY (`idAlbaran`) REFERENCES `albclit` (`id`),
  ADD CONSTRAINT `pedclialb_idpedido_foreign` FOREIGN KEY (`idPedido`) REFERENCES `pedclit` (`id`);

--
-- Filtros para la tabla `pedcliIva`
--
ALTER TABLE `pedcliIva`
  ADD CONSTRAINT `pedcliiva_idpedcli_foreign` FOREIGN KEY (`idpedcli`) REFERENCES `pedclit` (`id`);

--
-- Filtros para la tabla `pedclilinea`
--
ALTER TABLE `pedclilinea`
  ADD CONSTRAINT `pedclilinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `pedclilinea_idpedcli_foreign` FOREIGN KEY (`idpedcli`) REFERENCES `pedclit` (`id`);

--
-- Filtros para la tabla `pedcliltemporales`
--
ALTER TABLE `pedcliltemporales`
  ADD CONSTRAINT `pedcliltemporales_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  ADD CONSTRAINT `pedcliltemporales_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `pedcliltemporales_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `pedcliltemporales_numpedcli_foreign` FOREIGN KEY (`Numpedcli`) REFERENCES `pedclit` (`id`);

--
-- Filtros para la tabla `pedclit`
--
ALTER TABLE `pedclit`
  ADD CONSTRAINT `pedclit_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  ADD CONSTRAINT `pedclit_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `pedclit_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pedproAlb`
--
ALTER TABLE `pedproAlb`
  ADD CONSTRAINT `pedproalb_idalbaran_foreign` FOREIGN KEY (`idAlbaran`) REFERENCES `albprot` (`id`),
  ADD CONSTRAINT `pedproalb_idpedido_foreign` FOREIGN KEY (`idPedido`) REFERENCES `pedprot` (`id`);

--
-- Filtros para la tabla `pedproIva`
--
ALTER TABLE `pedproIva`
  ADD CONSTRAINT `pedproiva_idpedpro_foreign` FOREIGN KEY (`idpedpro`) REFERENCES `pedprot` (`id`);

--
-- Filtros para la tabla `pedprolinea`
--
ALTER TABLE `pedprolinea`
  ADD CONSTRAINT `pedprolinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `pedprolinea_idpedpro_foreign` FOREIGN KEY (`idpedpro`) REFERENCES `pedprot` (`id`);

--
-- Filtros para la tabla `pedprot`
--
ALTER TABLE `pedprot`
  ADD CONSTRAINT `pedprot_idproveedor_foreign` FOREIGN KEY (`idProveedor`) REFERENCES `proveedores` (`idProveedor`),
  ADD CONSTRAINT `pedprot_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `pedprot_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pedprotemporales`
--
ALTER TABLE `pedprotemporales`
  ADD CONSTRAINT `pedprotemporales_idpedpro_foreign` FOREIGN KEY (`idPedpro`) REFERENCES `pedprot` (`id`),
  ADD CONSTRAINT `pedprotemporales_idproveedor_foreign` FOREIGN KEY (`idProveedor`) REFERENCES `proveedores` (`idProveedor`),
  ADD CONSTRAINT `pedprotemporales_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `pedprotemporales_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `stocksRegularizacion`
--
ALTER TABLE `stocksRegularizacion`
  ADD CONSTRAINT `stocksregularizacion_idalbaran_foreign` FOREIGN KEY (`idAlbaran`) REFERENCES `albclit` (`id`),
  ADD CONSTRAINT `stocksregularizacion_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `stocksregularizacion_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `stocksregularizacion_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `ticketslinea`
--
ALTER TABLE `ticketslinea`
  ADD CONSTRAINT `ticketslinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `ticketslinea_idticketst_foreign` FOREIGN KEY (`idticketst`) REFERENCES `ticketst` (`id`);

--
-- Filtros para la tabla `ticketst`
--
ALTER TABLE `ticketst`
  ADD CONSTRAINT `ticketst_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  ADD CONSTRAINT `ticketst_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `ticketst_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `ticketstemporales`
--
ALTER TABLE `ticketstemporales`
  ADD CONSTRAINT `ticketstemporales_idclientes_foreign` FOREIGN KEY (`idClientes`) REFERENCES `clientes` (`idClientes`),
  ADD CONSTRAINT `ticketstemporales_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `ticketstemporales_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `ticketstIva`
--
ALTER TABLE `ticketstIva`
  ADD CONSTRAINT `ticketstiva_idticketst_foreign` FOREIGN KEY (`idticketst`) REFERENCES `ticketst` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
