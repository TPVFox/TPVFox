-- TPVFox — Base de datos completa
-- Versión: 0.4.2.84
-- Generado: 2026-04-02
--
-- Este fichero es la evolución de tpvfox_V_0_3-1.sql con todos los
-- parches aplicados hasta install_update_v0.4.2.84.sql incluido.
--
-- Historial de cambios incorporados:
--   v0.3.0      descuentos_tickets, columnas clientes
--   v0.3.1.0    tiendas.servidor_email (JSON → LONGTEXT en v0.3.1.51)
--   v0.3.1.1    articulosTiendas.fechaModificacion
--   v0.3.1.5    reestructura faccliltemporales, albcliltemporales,
--               pedcliltemporales, pedclit.Fecha, facclit sin formaPago
--   v0.3.1.51   tiendas.servidor_email cambia a LONGTEXT
--   v0.3.5.25_8 familiasTienda añade PK auto_increment
--   v0.3.5.35_62 (dato: estado tiendas a 'Activo', no afecta esquema)
--   v0.3.9.0_1  elimina FK/index pedcliltemporales
--   v0.4.0.0    tareas_cron, acumulado_compras, diario_cron
--   v0.4.0.30   modulo_balanza: renombra conTecla, añade columnas;
--               modulo_balanza_plus: renombra tecla→seccion
--   v0.4.1.0    dispositivos, temperaturas
--   v0.4.1.36   proveedores.registro_sanitario
--   v0.4.2.0    vistas vw_jerarquias_familias, vw_resumenClientes*,
--               vw_resumenProveedoresFacturas
--   v0.4.2.60   dispositivos: añade estado 'automatico', media, sd,
--               temp_min, temp_max
--   v0.4.2.84   (incluido en parche 4.2.60)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tpvfox_provincial`
--

-- --------------------------------------------------------
-- TABLAS
-- --------------------------------------------------------

--
-- Tabla `acumulado_compras`  [nueva en v0.4.0.0]
--

CREATE TABLE `acumulado_compras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` smallint(4) UNSIGNED NOT NULL,
  `month` tinyint(2) UNSIGNED NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `cantidad` decimal(17,6) NOT NULL,
  `costemedio` double NOT NULL,
  `update_at` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_month_articulo` (`year`,`month`,`idArticulo`),
  CONSTRAINT `articulo` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabla `albclifac`
--

CREATE TABLE `albclifac` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idFactura` int(11) DEFAULT NULL,
  `numFactura` int(11) DEFAULT NULL,
  `idAlbaran` int(11) DEFAULT NULL,
  `numAlbaran` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabla `albcliIva`
--

CREATE TABLE `albcliIva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idalbcli` int(11) NOT NULL,
  `Numalbcli` int(11) NOT NULL,
  `iva` int(11) DEFAULT NULL,
  `importeIva` decimal(17,2) DEFAULT NULL,
  `totalbase` decimal(17,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `albclilinea`
--

CREATE TABLE `albclilinea` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `pvpSiva` decimal(17,6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `albcliltemporales`  [reestructurada en v0.3.1.5]
--

CREATE TABLE `albcliltemporales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Numalbcli` int(11) DEFAULT NULL,
  `idTienda` int(11) DEFAULT NULL,
  `idUsuario` int(11) DEFAULT NULL,
  `fechaInicio` datetime DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `idCliente` int(11) DEFAULT NULL,
  `total` decimal(17,6) DEFAULT NULL,
  `total_ivas` varchar(250) DEFAULT NULL,
  `Productos` mediumblob DEFAULT NULL,
  `Pedidos` varbinary(5000) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `albclit`
--

CREATE TABLE `albclit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Numalbcli` int(11) DEFAULT NULL,
  `Numtemp_albcli` int(11) DEFAULT NULL,
  `Fecha` datetime NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idCliente` int(11) NOT NULL,
  `estado` varchar(12) DEFAULT NULL,
  `formaPago` varchar(12) DEFAULT NULL,
  `entregado` decimal(17,2) DEFAULT NULL,
  `total` decimal(17,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `albprofac`
--

CREATE TABLE `albprofac` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idFactura` int(11) DEFAULT NULL,
  `numFactura` int(11) DEFAULT NULL,
  `idAlbaran` int(11) DEFAULT NULL,
  `numAlbaran` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabla `albproIva`
--

CREATE TABLE `albproIva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idalbpro` int(11) NOT NULL,
  `Numalbpro` int(11) NOT NULL,
  `iva` int(11) DEFAULT NULL,
  `importeIva` decimal(17,2) DEFAULT NULL,
  `totalbase` decimal(17,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `albprolinea`
--

CREATE TABLE `albprolinea` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `idpedpro` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `albproltemporales`
--

CREATE TABLE `albproltemporales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `Pedidos` varbinary(5000) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `albprot`
--

CREATE TABLE `albprot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `modify_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `articulos`
--

CREATE TABLE `articulos` (
  `idArticulo` int(11) NOT NULL AUTO_INCREMENT,
  `iva` decimal(4,2) DEFAULT NULL,
  `idProveedor` varchar(6) CHARACTER SET utf8 DEFAULT NULL,
  `articulo_name` varchar(100) CHARACTER SET utf8 NOT NULL,
  `beneficio` decimal(5,2) DEFAULT NULL,
  `costepromedio` decimal(17,6) DEFAULT NULL,
  `estado` varchar(12) CHARACTER SET utf8 NOT NULL,
  `fecha_creado` datetime NOT NULL,
  `fecha_modificado` datetime DEFAULT NULL,
  `ultimoCoste` float NOT NULL,
  `tipo` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idArticulo`),
  KEY `idProveedor` (`idProveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabla `articulosClientes`
--

CREATE TABLE `articulosClientes` (
  `idArticulo` int(11) NOT NULL,
  `idClientes` int(11) NOT NULL,
  `pvpSiva` decimal(17,6) NOT NULL,
  `pvpCiva` decimal(17,6) NOT NULL,
  `fechaActualizacion` datetime NOT NULL,
  `estado` varchar(12) NOT NULL,
  PRIMARY KEY (`idArticulo`,`idClientes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `articulosCodigoBarras`
--

CREATE TABLE `articulosCodigoBarras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idArticulo` int(11) NOT NULL,
  `codBarras` varchar(18) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `articulosFamilias`
--

CREATE TABLE `articulosFamilias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idArticulo` int(11) NOT NULL,
  `idFamilia` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idArticulo` (`idArticulo`,`idFamilia`) USING BTREE,
  UNIQUE KEY `idArticulo_2` (`idArticulo`,`idFamilia`),
  KEY `fk_categoriaFamilias` (`idFamilia`),
  KEY `fk_articulos` (`idArticulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `articulosPrecios`
--

CREATE TABLE `articulosPrecios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idArticulo` int(11) NOT NULL,
  `pvpCiva` decimal(17,6) NOT NULL,
  `pvpSiva` decimal(17,6) NOT NULL,
  `idTienda` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idArticulo` (`idArticulo`,`idTienda`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `articulosProveedores`
--

CREATE TABLE `articulosProveedores` (
  `idArticulo` int(11) NOT NULL,
  `idProveedor` int(11) NOT NULL,
  `crefProveedor` varchar(24) DEFAULT NULL,
  `coste` decimal(17,6) NOT NULL,
  `fechaActualizacion` date NOT NULL,
  `estado` varchar(12) NOT NULL,
  PRIMARY KEY (`idArticulo`,`idProveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `articulosStocks`
--

CREATE TABLE `articulosStocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idArticulo` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `stockMin` decimal(17,6) DEFAULT NULL,
  `stockMax` decimal(17,6) DEFAULT NULL,
  `stockOn` decimal(17,6) NOT NULL,
  `fecha_modificado` datetime NOT NULL DEFAULT current_timestamp(),
  `fechaRegularizacion` datetime DEFAULT NULL,
  `usuarioRegularizacion` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `articulosTiendas`  [v0.3.1.1: fechaModificacion]
--

CREATE TABLE `articulosTiendas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idArticulo` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `crefTienda` varchar(18) DEFAULT NULL,
  `idVirtuemart` int(11) DEFAULT NULL,
  `estado` varchar(12) NOT NULL,
  `fechaModificacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idTienda` (`idTienda`),
  KEY `idTienda_idArticulo` (`idArticulo`,`idTienda`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `cierres`
--

CREATE TABLE `cierres` (
  `idCierre` int(11) NOT NULL AUTO_INCREMENT,
  `FechaCierre` date NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `FechaInicio` datetime NOT NULL,
  `FechaFinal` datetime NOT NULL,
  `FechaCreacion` datetime NOT NULL,
  `Total` decimal(17,4) NOT NULL,
  PRIMARY KEY (`idCierre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `cierres_ivas`
--

CREATE TABLE `cierres_ivas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idCierre` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `tipo_iva` int(11) NOT NULL,
  `importe_base` decimal(17,4) NOT NULL,
  `importe_iva` decimal(17,4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `cierres_usuariosFormasPago`
--

CREATE TABLE `cierres_usuariosFormasPago` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idCierre` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `FormasPago` varchar(100) NOT NULL,
  `importe` decimal(17,4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `cierres_usuarios_tickets`
--

CREATE TABLE `cierres_usuarios_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idCierre` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `Importe` decimal(17,4) NOT NULL,
  `Num_ticket_inicial` int(11) NOT NULL,
  `Num_ticket_final` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `clientes`  [v0.3.0: descuento_ticket, requiere_factura, recargo_equivalencia; fecha_creado default]
--

CREATE TABLE `clientes` (
  `idClientes` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(100) CHARACTER SET utf8 NOT NULL,
  `razonsocial` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  `nif` varchar(10) CHARACTER SET utf8 DEFAULT NULL,
  `direccion` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  `codpostal` varchar(32) DEFAULT NULL,
  `telefono` varchar(11) DEFAULT NULL,
  `movil` varchar(11) DEFAULT NULL,
  `fax` varchar(11) DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  `estado` varchar(12) CHARACTER SET utf8 NOT NULL,
  `formasVenci` varchar(250) DEFAULT NULL,
  `fecha_creado` datetime NOT NULL DEFAULT current_timestamp(),
  `descuento_ticket` decimal(5,2) NOT NULL DEFAULT 3.00,
  `requiere_factura` tinyint(1) NOT NULL DEFAULT 0,
  `recargo_equivalencia` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`idClientes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=2;

--
-- Datos iniciales `clientes`
--

INSERT INTO `clientes` (`idClientes`, `Nombre`, `razonsocial`, `nif`, `direccion`, `codpostal`, `telefono`, `movil`, `fax`, `email`, `estado`, `formasVenci`, `fecha_creado`, `descuento_ticket`, `requiere_factura`, `recargo_equivalencia`) VALUES
(1, 'Sin identificar', 'Sin identificar', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'activo', NULL, '2022-12-10 21:20:29', '3.00', 0, 0);

-- --------------------------------------------------------

--
-- Tabla `descuentos_tickets`  [v0.3.0]
--

CREATE TABLE `descuentos_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `estado` varchar(12) NOT NULL DEFAULT 'Pendiente',
  PRIMARY KEY (`id`),
  KEY `cliente` (`idCliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabla `diario_cron`  [nueva en v0.4.0.0]
--

CREATE TABLE `diario_cron` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(150) NOT NULL,
  `ejecucion` timestamp NOT NULL DEFAULT current_timestamp(),
  `tarea_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `diario_cron_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `tareas_cron` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabla `dispositivos`  [nueva en v0.4.1.0; ampliada en v0.4.2.60]
--

CREATE TABLE `dispositivos` (
  `idDispositivo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `estado` enum('activo','inactivo','automatico') DEFAULT 'activo',
  `media` decimal(5,2) DEFAULT NULL,
  `sd` decimal(5,2) DEFAULT NULL,
  `temp_min` decimal(5,2) DEFAULT NULL,
  `temp_max` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`idDispositivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabla `faccliIva`
--

CREATE TABLE `faccliIva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idfaccli` int(11) NOT NULL,
  `Numfaccli` int(11) NOT NULL,
  `iva` int(11) DEFAULT NULL,
  `importeIva` decimal(17,2) DEFAULT NULL,
  `totalbase` decimal(17,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `facclilinea`
--

CREATE TABLE `facclilinea` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `pvpSiva` decimal(17,6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `faccliltemporales`  [reestructurada en v0.3.1.5]
--

CREATE TABLE `faccliltemporales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `Albaranes` varbinary(50000) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `facclit`  [v0.3.1.5: sin formaPago]
--

CREATE TABLE `facclit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `fechaModificacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `facProCobros`
--

CREATE TABLE `facProCobros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idFactura` int(11) NOT NULL,
  `idFormasPago` int(11) NOT NULL,
  `FechaPago` date NOT NULL,
  `importe` float NOT NULL,
  `Referencia` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabla `facproIva`
--

CREATE TABLE `facproIva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idfacpro` int(11) NOT NULL,
  `Numfacpro` int(11) NOT NULL,
  `iva` int(11) DEFAULT NULL,
  `importeIva` decimal(17,2) DEFAULT NULL,
  `totalbase` decimal(17,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `facprolinea`
--

CREATE TABLE `facprolinea` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `idalbpro` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `facproltemporales`
--

CREATE TABLE `facproltemporales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `FacCobros` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `facprot`
--

CREATE TABLE `facprot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `modify_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `fac_cobros`
--

CREATE TABLE `fac_cobros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idFactura` int(11) NOT NULL,
  `idFormasPago` int(11) NOT NULL,
  `FechaPago` date NOT NULL,
  `importe` float NOT NULL,
  `Referencia` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabla `familias`
--

CREATE TABLE `familias` (
  `idFamilia` int(11) NOT NULL AUTO_INCREMENT,
  `familiaNombre` varchar(100) NOT NULL DEFAULT '',
  `familiaPadre` int(11) NOT NULL,
  `beneficiomedio` decimal(5,2) DEFAULT NULL,
  `mostrar_tpv` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`idFamilia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `familiasTienda`  [v0.3.5.25_8: añade PK auto_increment]
--

CREATE TABLE `familiasTienda` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idFamilia` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idFamilia_tienda` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `formasPago`
--

CREATE TABLE `formasPago` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=6;

INSERT INTO `formasPago` (`id`, `descripcion`) VALUES
(1, 'Efectivo'),
(2, 'Tarjeta'),
(3, 'Recibo bancario'),
(4, 'Transferencia bancaria'),
(5, 'Talón');

-- --------------------------------------------------------

--
-- Tabla `historico_precios`
--

CREATE TABLE `historico_precios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idArticulo` int(10) NOT NULL,
  `Antes` decimal(17,4) NOT NULL,
  `Nuevo` decimal(17,4) NOT NULL,
  `Fecha_Creacion` datetime NOT NULL,
  `NumDoc` int(11) NOT NULL,
  `Dedonde` varchar(50) NOT NULL,
  `Tipo` varchar(50) NOT NULL,
  `idUsuario` int(5) DEFAULT NULL,
  `estado` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabla `importar_virtuemart_tickets`
--

CREATE TABLE `importar_virtuemart_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idTicketst` int(11) NOT NULL,
  `Fecha` datetime NOT NULL,
  `estado` varchar(12) NOT NULL,
  `respuesta` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `indices`
--

CREATE TABLE `indices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `numticket` int(11) NOT NULL,
  `tempticket` int(11) NOT NULL COMMENT 'Es el numero con guardo temporal ticket',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=2;

INSERT INTO `indices` (`id`, `idTienda`, `idUsuario`, `numticket`, `tempticket`) VALUES
(1, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Tabla `iva`
--

CREATE TABLE `iva` (
  `idIva` int(11) NOT NULL AUTO_INCREMENT,
  `descripcionIva` varchar(25) DEFAULT NULL,
  `iva` decimal(4,2) DEFAULT NULL,
  `recargo` decimal(4,2) DEFAULT NULL,
  PRIMARY KEY (`idIva`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=5;

INSERT INTO `iva` (`idIva`, `descripcionIva`, `iva`, `recargo`) VALUES
(1, 'I.V.A. al cero', '0.00', '0.00'),
(2, 'Super Reducido', '4.00', '0.50'),
(3, 'Reducido', '10.00', '1.00'),
(4, 'General', '21.00', '4.00');

-- --------------------------------------------------------

--
-- Tabla `migraciones`
--

CREATE TABLE `migraciones` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `modulos_configuracion`
--

CREATE TABLE `modulos_configuracion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idusuario` int(11) NOT NULL,
  `nombre_modulo` varchar(50) NOT NULL,
  `configuracion` longtext NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `modulo_balanza`  [v0.4.0.30: conTecla→conSeccion, Grupo, Dirección, IP, soloPLUS]
--

CREATE TABLE `modulo_balanza` (
  `idBalanza` int(11) NOT NULL AUTO_INCREMENT,
  `nombreBalanza` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `conSeccion` varchar(3) NOT NULL,
  `Grupo` tinyint(3) UNSIGNED NOT NULL,
  `Dirección` tinyint(3) UNSIGNED NOT NULL,
  `IP` varchar(45) NOT NULL,
  `soloPLUS` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idBalanza`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabla `modulo_balanza_plus`  [v0.4.0.30: tecla→seccion]
--

CREATE TABLE `modulo_balanza_plus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idBalanza` int(11) NOT NULL,
  `plu` int(10) NOT NULL,
  `seccion` int(100) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabla `modulo_etiquetado`
--

CREATE TABLE `modulo_etiquetado` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `num_lote` int(11) DEFAULT NULL,
  `tipo` varchar(12) NOT NULL,
  `fecha_env` datetime NOT NULL,
  `fecha_cad` date NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `numAlb` int(11) NOT NULL,
  `estado` varchar(12) NOT NULL,
  `productos` mediumblob NOT NULL,
  `idUsuario` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabla `modulo_etiquetado_temporal`
--

CREATE TABLE `modulo_etiquetado_temporal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `num_lote` int(11) NOT NULL,
  `tipo` varchar(12) NOT NULL,
  `fecha_env` datetime NOT NULL,
  `fecha_cad` date NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `numAlb` int(11) NOT NULL,
  `estado` varchar(12) NOT NULL,
  `productos` mediumblob NOT NULL,
  `idUsuario` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabla `modulo_importar_registro`
--

CREATE TABLE `modulo_importar_registro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `datos_fichero` mediumtext NOT NULL,
  `token` text NOT NULL,
  `type` text NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `estado` varchar(250) NOT NULL,
  `Registros_originales` int(11) NOT NULL,
  `nulos` int(11) NOT NULL DEFAULT 0,
  `errores` int(11) NOT NULL DEFAULT 0,
  `campos` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabla `modulo_incidencia`
--

CREATE TABLE `modulo_incidencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `num_incidencia` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `dedonde` varchar(15) NOT NULL,
  `mensaje` varchar(255) NOT NULL,
  `datos` varbinary(10000) NOT NULL,
  `estado` varchar(12) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `pedcliAlb`
--

CREATE TABLE `pedcliAlb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idAlbaran` int(11) DEFAULT NULL,
  `numAlbaran` int(11) DEFAULT NULL,
  `idPedido` int(11) DEFAULT NULL,
  `numPedido` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabla `pedcliIva`
--

CREATE TABLE `pedcliIva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idpedcli` int(11) NOT NULL,
  `Numpedcli` int(11) NOT NULL,
  `iva` int(11) NOT NULL,
  `importeIva` decimal(17,2) NOT NULL,
  `totalbase` decimal(17,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `pedclilinea`
--

CREATE TABLE `pedclilinea` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `pvpSiva` decimal(17,6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `pedcliltemporales`  [reestructurada en v0.3.1.5; FK eliminada en v0.3.9.0_1]
--

CREATE TABLE `pedcliltemporales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idTienda` int(11) DEFAULT NULL,
  `idUsuario` int(11) DEFAULT NULL,
  `fechaInicio` datetime DEFAULT NULL,
  `Fecha` datetime DEFAULT NULL,
  `idCliente` int(11) DEFAULT NULL,
  `total` decimal(17,6) DEFAULT NULL,
  `total_ivas` varchar(250) DEFAULT NULL,
  `Productos` mediumblob DEFAULT NULL,
  `Numpedcli` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `pedclit`  [v0.3.1.5: FechaPedido→Fecha]
--

CREATE TABLE `pedclit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `fechaModificacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `pedproAlb`
--

CREATE TABLE `pedproAlb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idAlbaran` int(11) DEFAULT NULL,
  `numAlbaran` int(11) DEFAULT NULL,
  `idPedido` int(11) DEFAULT NULL,
  `numPedido` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabla `pedproIva`
--

CREATE TABLE `pedproIva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idpedpro` int(11) NOT NULL,
  `Numpedpro` int(11) DEFAULT NULL,
  `iva` int(11) NOT NULL,
  `importeIva` decimal(17,2) NOT NULL,
  `totalbase` decimal(17,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `pedprolinea`
--

CREATE TABLE `pedprolinea` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `estadoLinea` varchar(12) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `pedprot`
--

CREATE TABLE `pedprot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `modify_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `pedprotemporales`
--

CREATE TABLE `pedprotemporales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `idPedpro` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` int(11) NOT NULL,
  `modulo` varchar(50) DEFAULT NULL,
  `vista` varchar(50) DEFAULT NULL,
  `accion` varchar(50) DEFAULT NULL,
  `permiso` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabla `proveedores`  [v0.4.1.36: registro_sanitario]
--

CREATE TABLE `proveedores` (
  `idProveedor` int(11) NOT NULL AUTO_INCREMENT,
  `nombrecomercial` varchar(100) DEFAULT NULL,
  `razonsocial` varchar(100) NOT NULL,
  `nif` varchar(10) NOT NULL,
  `direccion` varchar(100) NOT NULL,
  `telefono` varchar(11) DEFAULT NULL,
  `fax` varchar(11) DEFAULT NULL,
  `movil` varchar(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `fecha_creado` datetime NOT NULL,
  `estado` varchar(12) NOT NULL,
  `registro_sanitario` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`idProveedor`),
  FULLTEXT KEY `nombrecomercial` (`nombrecomercial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `stocksRegularizacion`
--

CREATE TABLE `stocksRegularizacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idArticulo` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL DEFAULT 1,
  `fechaRegularizacion` datetime NOT NULL DEFAULT current_timestamp(),
  `stockActual` decimal(17,6) NOT NULL,
  `stockModif` decimal(17,6) NOT NULL,
  `stockFinal` decimal(17,6) NOT NULL,
  `stockOperacion` int(1) NOT NULL DEFAULT 1,
  `idUsuario` int(11) NOT NULL,
  `idAlbaran` int(11) NOT NULL DEFAULT 0,
  `estado` int(11) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `tareas_cron`  [nueva en v0.4.0.0]
--

CREATE TABLE `tareas_cron` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `cantidad_periodo` int(11) NOT NULL,
  `tipo_periodo` int(2) NOT NULL,
  `nombre_clase` varchar(50) NOT NULL,
  `inicio_ejecucion` date NOT NULL,
  `ultima_ejecucion` datetime DEFAULT NULL,
  `estado` int(2) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabla `temperaturas`  [nueva en v0.4.1.0]
--

CREATE TABLE `temperaturas` (
  `idTemperatura` int(11) NOT NULL AUTO_INCREMENT,
  `idDispositivo` int(11) NOT NULL,
  `temperatura` decimal(5,2) NOT NULL,
  `fechaRegistro` datetime DEFAULT current_timestamp(),
  `idUsuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`idTemperatura`),
  CONSTRAINT `temperaturas_ibfk_1` FOREIGN KEY (`idDispositivo`) REFERENCES `dispositivos` (`idDispositivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabla `ticketslinea`
--

CREATE TABLE `ticketslinea` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `estadoLinea` varchar(12) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `ticketst`
--

CREATE TABLE `ticketst` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Numticket` int(11) NOT NULL,
  `Numtempticket` int(11) NOT NULL,
  `Fecha` datetime NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idCliente` int(11) NOT NULL,
  `estado` varchar(12) NOT NULL,
  `formaPago` varchar(12) NOT NULL,
  `entregado` decimal(17,2) NOT NULL,
  `total` decimal(17,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `ticketstemporales`
--

CREATE TABLE `ticketstemporales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numticket` int(11) NOT NULL,
  `estadoTicket` varchar(12) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `fechaInicio` datetime NOT NULL,
  `fechaFinal` datetime DEFAULT NULL,
  `idClientes` int(11) NOT NULL,
  `total` decimal(17,6) NOT NULL,
  `Productos` mediumblob DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `ticketstIva`
--

CREATE TABLE `ticketstIva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idticketst` int(11) NOT NULL,
  `Numticket` int(11) NOT NULL,
  `iva` int(11) NOT NULL,
  `importeIva` decimal(17,2) NOT NULL,
  `totalbase` decimal(17,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabla `tiendas`  [v0.3.1.0: servidor_email JSON; v0.3.1.51: LONGTEXT]
--

CREATE TABLE `tiendas` (
  `idTienda` int(2) NOT NULL AUTO_INCREMENT,
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
  `servidor_email` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`idTienda`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=2;

INSERT INTO `tiendas` (`idTienda`, `tipoTienda`, `razonsocial`, `nif`, `telefono`, `estado`, `NombreComercial`, `direccion`, `ano`, `dominio`, `key_api`, `servidor_email`) VALUES
(1, 'principal', 'Soluciones informaticas Vigo SL', 'B999666999', '886112370', 'Activo', 'Soluciones Vigo', 'Emilia pardo Bazan 52- bajo', '2022', NULL, NULL, 'tuservidor.emailtuyo.com');

-- --------------------------------------------------------

--
-- Tabla `tiposVencimiento`
--

CREATE TABLE `tiposVencimiento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(20) NOT NULL,
  `dias` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=6;

INSERT INTO `tiposVencimiento` (`id`, `descripcion`, `dias`) VALUES
(1, 'Contado', 0),
(2, 'Semanal', 7),
(3, 'Quincenal', 15),
(4, 'Mensual', 30),
(5, 'Semestral', 181);

-- --------------------------------------------------------

--
-- Tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `group_id` int(11) NOT NULL COMMENT 'id grupo permisos',
  `estado` varchar(12) NOT NULL COMMENT 'estado',
  `nombre` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=2;

INSERT INTO `usuarios` (`id`, `username`, `password`, `fecha`, `group_id`, `estado`, `nombre`) VALUES
(1, 'admin', '21232f297a57a5a743894a0e4a801fc3', '2022-12-10', 9, 'activo', 'admin');

-- --------------------------------------------------------
-- VISTAS  [nuevas en v0.4.2.0]
-- --------------------------------------------------------

--
-- Vista `vw_jerarquias_familias`
--

CREATE OR REPLACE VIEW vw_jerarquias_familias AS
WITH RECURSIVE ArbolFamilias AS (
    SELECT
        idFamilia,
        familiaNombre,
        familiaPadre,
        1 AS nivel,
        idFamilia AS idN1,
        CAST(NULL AS UNSIGNED) AS idN2,
        CAST(familiaNombre AS CHAR(500)) AS ruta
    FROM familias
    WHERE familiaPadre = 0 OR familiaPadre IS NULL

    UNION ALL

    SELECT
        f.idFamilia,
        f.familiaNombre,
        f.familiaPadre,
        af.nivel + 1,
        af.idN1,
        CASE
            WHEN af.nivel = 1 THEN f.idFamilia
            ELSE af.idN2
        END,
        CONCAT(af.ruta, ' > ', f.familiaNombre)
    FROM familias f
    INNER JOIN ArbolFamilias af ON f.familiaPadre = af.idFamilia
)
SELECT
    idFamilia,
    nivel,
    familiaNombre,
    idN1,
    idN2,
    familiaPadre,
    ruta
FROM ArbolFamilias
ORDER BY ruta;

--
-- Vista `vw_resumenClientesFacturas`
--

CREATE OR REPLACE VIEW vw_resumenClientesFacturas AS
SELECT
    c.idClientes AS idCliente,
    c.nif,
    f.ejercicio,
    SUM(CASE WHEN f.trimestre = 1 THEN f.totalFactura ELSE 0 END) AS q1,
    SUM(CASE WHEN f.trimestre = 1 THEN f.totalIva    ELSE 0 END) AS q1Iva,
    SUM(CASE WHEN f.trimestre = 2 THEN f.totalFactura ELSE 0 END) AS q2,
    SUM(CASE WHEN f.trimestre = 2 THEN f.totalIva    ELSE 0 END) AS q2Iva,
    SUM(CASE WHEN f.trimestre = 3 THEN f.totalFactura ELSE 0 END) AS q3,
    SUM(CASE WHEN f.trimestre = 3 THEN f.totalIva    ELSE 0 END) AS q3Iva,
    SUM(CASE WHEN f.trimestre = 4 THEN f.totalFactura ELSE 0 END) AS q4,
    SUM(CASE WHEN f.trimestre = 4 THEN f.totalIva    ELSE 0 END) AS q4Iva,
    SUM(f.totalIva)      AS totalIva,
    SUM(f.totalFactura)  AS total
FROM (
    SELECT
        fac.idCliente,
        YEAR(fac.Fecha)    AS ejercicio,
        QUARTER(fac.Fecha) AS trimestre,
        fac.total          AS totalFactura,
        SUM(fiva.importeIva) AS totalIva
    FROM facclit fac
    JOIN faccliIva fiva ON fiva.idfaccli = fac.id
    WHERE fac.estado <> 'Sin Guardar'
    GROUP BY fac.id
) f
JOIN clientes c ON c.idClientes = f.idCliente
GROUP BY c.idClientes, c.nif, f.ejercicio;

--
-- Vista `vw_resumenClientesTickets`
--

CREATE OR REPLACE VIEW vw_resumenClientesTickets AS
SELECT
    c.idClientes AS idCliente,
    c.nif,
    t.ejercicio,
    SUM(CASE WHEN t.trimestre = 1 THEN t.totalTicket ELSE 0 END) AS q1,
    SUM(CASE WHEN t.trimestre = 1 THEN t.totalIva    ELSE 0 END) AS q1Iva,
    SUM(CASE WHEN t.trimestre = 2 THEN t.totalTicket ELSE 0 END) AS q2,
    SUM(CASE WHEN t.trimestre = 2 THEN t.totalIva    ELSE 0 END) AS q2Iva,
    SUM(CASE WHEN t.trimestre = 3 THEN t.totalTicket ELSE 0 END) AS q3,
    SUM(CASE WHEN t.trimestre = 3 THEN t.totalIva    ELSE 0 END) AS q3Iva,
    SUM(CASE WHEN t.trimestre = 4 THEN t.totalTicket ELSE 0 END) AS q4,
    SUM(CASE WHEN t.trimestre = 4 THEN t.totalIva    ELSE 0 END) AS q4Iva,
    SUM(t.totalIva)     AS totalIva,
    SUM(t.totalTicket)  AS total
FROM (
    SELECT
        tick.idCliente,
        YEAR(tick.Fecha)    AS ejercicio,
        QUARTER(tick.Fecha) AS trimestre,
        tick.total          AS totalTicket,
        SUM(tiva.importeIva) AS totalIva
    FROM ticketst tick
    JOIN ticketstIva tiva ON tiva.idticketst = tick.id
    WHERE tick.estado = 'Cerrado'
    GROUP BY tick.id
) t
JOIN clientes c ON c.idClientes = t.idCliente
GROUP BY c.idClientes, c.nif, t.ejercicio;

--
-- Vista `vw_resumenProveedoresFacturas`
--

CREATE OR REPLACE VIEW vw_resumenProveedoresFacturas AS
SELECT
    p.idProveedor,
    p.nif,
    f.ejercicio,
    SUM(CASE WHEN f.trimestre = 1 THEN f.totalFactura ELSE 0 END) AS q1,
    SUM(CASE WHEN f.trimestre = 1 THEN f.totalIva    ELSE 0 END) AS q1Iva,
    SUM(CASE WHEN f.trimestre = 2 THEN f.totalFactura ELSE 0 END) AS q2,
    SUM(CASE WHEN f.trimestre = 2 THEN f.totalIva    ELSE 0 END) AS q2Iva,
    SUM(CASE WHEN f.trimestre = 3 THEN f.totalFactura ELSE 0 END) AS q3,
    SUM(CASE WHEN f.trimestre = 3 THEN f.totalIva    ELSE 0 END) AS q3Iva,
    SUM(CASE WHEN f.trimestre = 4 THEN f.totalFactura ELSE 0 END) AS q4,
    SUM(CASE WHEN f.trimestre = 4 THEN f.totalIva    ELSE 0 END) AS q4Iva,
    SUM(f.totalIva)     AS totalIva,
    SUM(f.totalFactura) AS total
FROM (
    SELECT
        fac.idProveedor,
        YEAR(fac.Fecha)    AS ejercicio,
        QUARTER(fac.Fecha) AS trimestre,
        fac.total          AS totalFactura,
        SUM(fiva.importeIva) AS totalIva
    FROM facprot fac
    JOIN facproIva fiva ON fiva.idfacpro = fac.id
    WHERE fac.estado <> 'Sin Guardar'
    GROUP BY fac.id
) f
JOIN proveedores p ON p.idProveedor = f.idProveedor
GROUP BY p.idProveedor, p.nif, f.ejercicio;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
