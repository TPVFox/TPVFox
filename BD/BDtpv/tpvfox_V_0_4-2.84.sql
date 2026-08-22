-- TPVFox — Base de datos completa
-- Versión: 0.4.2.84
-- Generado: 2026-08-17
--
-- Este fichero es la evolución de tpvfox_V_0_3-1.sql con todos los
-- parches aplicados hasta install_update_v0.4.2.84.sql incluido.
--
-- Regenerado el 2026-08-17 desde una base desplegada con
-- `mysqldump --no-data --skip-dump-date --routines --triggers --events`.
-- La versión no cambia: no se ha aplicado ningún parche de esquema
-- posterior a v0.4.2.84. Lo que se corrige es el contenido — la versión
-- anterior de este fichero declaraba 3 claves foráneas frente a las 94
-- realmente aplicadas, al no haber incorporado nunca restricciones_v1.sql.
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
--
-- Restricciones de integridad:
--   restricciones_v1.sql (2024-05-07)  91 claves foráneas, aplicadas en las
--               bases desplegadas y ausentes de este fichero hasta su
--               regeneración. Con las 3 originales suman las 94 actuales.
--   restricciones_v2.sql               2 claves foráneas NO aplicadas:
--               requieren cambios de código previos. No incluidas aquí.

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `acumulado_compras`
--

DROP TABLE IF EXISTS `acumulado_compras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `acumulado_compras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` smallint(4) unsigned NOT NULL,
  `month` tinyint(2) unsigned NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `cantidad` decimal(17,6) NOT NULL,
  `costemedio` double NOT NULL,
  `update_at` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_month_articulo` (`year`,`month`,`idArticulo`),
  KEY `articulo` (`idArticulo`),
  CONSTRAINT `articulo` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `albcliIva`
--

DROP TABLE IF EXISTS `albcliIva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `albcliIva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idalbcli` int(11) NOT NULL,
  `Numalbcli` int(11) NOT NULL,
  `iva` int(11) DEFAULT NULL,
  `importeIva` decimal(17,2) DEFAULT NULL,
  `totalbase` decimal(17,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `albcliiva_idalbcli_foreign` (`idalbcli`),
  CONSTRAINT `albcliiva_idalbcli_foreign` FOREIGN KEY (`idalbcli`) REFERENCES `albclit` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2240 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `albclifac`
--

DROP TABLE IF EXISTS `albclifac`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `albclifac` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idFactura` int(11) DEFAULT NULL,
  `numFactura` int(11) DEFAULT NULL,
  `idAlbaran` int(11) DEFAULT NULL,
  `numAlbaran` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `albclifac_idalbaran_foreign` (`idAlbaran`),
  KEY `albclifac_idfactura_foreign` (`idFactura`),
  CONSTRAINT `albclifac_idalbaran_foreign` FOREIGN KEY (`idAlbaran`) REFERENCES `albclit` (`id`),
  CONSTRAINT `albclifac_idfactura_foreign` FOREIGN KEY (`idFactura`) REFERENCES `facclit` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=714 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `albclilinea`
--

DROP TABLE IF EXISTS `albclilinea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `albclilinea_ibfk_1` (`idalbcli`),
  KEY `albclilinea_idarticulo_foreign` (`idArticulo`),
  CONSTRAINT `albclilinea_ibfk_1` FOREIGN KEY (`idalbcli`) REFERENCES `albclit` (`id`),
  CONSTRAINT `albclilinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`)
) ENGINE=InnoDB AUTO_INCREMENT=11210 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `albcliltemporales`
--

DROP TABLE IF EXISTS `albcliltemporales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=1582 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `albclit`
--

DROP TABLE IF EXISTS `albclit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `albclit_idcliente_foreign` (`idCliente`),
  KEY `albclit_idtienda_foreign` (`idTienda`),
  KEY `albclit_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `albclit_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  CONSTRAINT `albclit_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  CONSTRAINT `albclit_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=827 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `albproIva`
--

DROP TABLE IF EXISTS `albproIva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `albproIva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idalbpro` int(11) NOT NULL,
  `Numalbpro` int(11) NOT NULL,
  `iva` int(11) DEFAULT NULL,
  `importeIva` decimal(17,2) DEFAULT NULL,
  `totalbase` decimal(17,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `albproiva_idalbpro_foreign` (`idalbpro`),
  CONSTRAINT `albproiva_idalbpro_foreign` FOREIGN KEY (`idalbpro`) REFERENCES `albprot` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9093 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `albprofac`
--

DROP TABLE IF EXISTS `albprofac`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `albprofac` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idFactura` int(11) DEFAULT NULL,
  `numFactura` int(11) DEFAULT NULL,
  `idAlbaran` int(11) DEFAULT NULL,
  `numAlbaran` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `albprofac_idalbaran_foreign` (`idAlbaran`),
  KEY `albprofac_idfactura_foreign` (`idFactura`),
  CONSTRAINT `albprofac_idalbaran_foreign` FOREIGN KEY (`idAlbaran`) REFERENCES `albprot` (`id`),
  CONSTRAINT `albprofac_idfactura_foreign` FOREIGN KEY (`idFactura`) REFERENCES `facprot` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5752 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `albprolinea`
--

DROP TABLE IF EXISTS `albprolinea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `albprolinea_ibfk_1` (`idalbpro`),
  KEY `albprolinea_idarticulo_foreign` (`idArticulo`),
  CONSTRAINT `albprolinea_ibfk_1` FOREIGN KEY (`idalbpro`) REFERENCES `albprot` (`id`),
  CONSTRAINT `albprolinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`)
) ENGINE=InnoDB AUTO_INCREMENT=85254 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `albproltemporales`
--

DROP TABLE IF EXISTS `albproltemporales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7123 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `albprot`
--

DROP TABLE IF EXISTS `albprot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4711 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `articulos`
--

DROP TABLE IF EXISTS `articulos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `articulos` (
  `idArticulo` int(11) NOT NULL AUTO_INCREMENT,
  `iva` decimal(4,2) DEFAULT NULL,
  `idProveedor` varchar(6) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `articulo_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `beneficio` decimal(5,2) DEFAULT NULL,
  `costepromedio` decimal(17,6) DEFAULT NULL,
  `estado` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `fecha_creado` datetime NOT NULL,
  `fecha_modificado` datetime DEFAULT NULL,
  `ultimoCoste` float NOT NULL,
  `tipo` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`idArticulo`),
  KEY `idProveedor` (`idProveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=13882 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `articulosClientes`
--

DROP TABLE IF EXISTS `articulosClientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `articulosClientes` (
  `idArticulo` int(11) NOT NULL,
  `idClientes` int(11) NOT NULL,
  `pvpSiva` decimal(17,6) NOT NULL,
  `pvpCiva` decimal(17,6) NOT NULL,
  `fechaActualizacion` datetime NOT NULL,
  `estado` varchar(12) NOT NULL,
  PRIMARY KEY (`idArticulo`,`idClientes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `articulosCodigoBarras`
--

DROP TABLE IF EXISTS `articulosCodigoBarras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `articulosCodigoBarras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idArticulo` int(11) NOT NULL,
  `codBarras` varchar(18) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `articulosCodigoBarras_ibfk_1` (`idArticulo`),
  CONSTRAINT `articulosCodigoBarras_ibfk_1` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`)
) ENGINE=InnoDB AUTO_INCREMENT=10459 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `articulosFamilias`
--

DROP TABLE IF EXISTS `articulosFamilias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `articulosFamilias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idArticulo` int(11) NOT NULL,
  `idFamilia` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idArticulo` (`idArticulo`,`idFamilia`) USING BTREE,
  UNIQUE KEY `idArticulo_2` (`idArticulo`,`idFamilia`),
  KEY `fk_categoriaFamilias` (`idFamilia`),
  KEY `fk_articulos` (`idArticulo`),
  CONSTRAINT `articulosFamilias_ibfk_1` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`)
) ENGINE=InnoDB AUTO_INCREMENT=9695 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `articulosPrecios`
--

DROP TABLE IF EXISTS `articulosPrecios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `articulosPrecios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idArticulo` int(11) NOT NULL,
  `pvpCiva` decimal(17,6) NOT NULL,
  `pvpSiva` decimal(17,6) NOT NULL,
  `idTienda` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idArticulo` (`idArticulo`,`idTienda`) USING BTREE,
  CONSTRAINT `articulosPrecios_ibfk_1` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`)
) ENGINE=InnoDB AUTO_INCREMENT=8530 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `articulosProveedores`
--

DROP TABLE IF EXISTS `articulosProveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `articulosProveedores` (
  `idArticulo` int(11) NOT NULL,
  `idProveedor` int(11) NOT NULL,
  `crefProveedor` varchar(24) DEFAULT NULL,
  `coste` decimal(17,6) NOT NULL,
  `fechaActualizacion` date NOT NULL,
  `estado` varchar(12) NOT NULL,
  PRIMARY KEY (`idArticulo`,`idProveedor`),
  KEY `articulosproveedores_idproveedor_foreign` (`idProveedor`),
  CONSTRAINT `articulosproveedores_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  CONSTRAINT `articulosproveedores_idproveedor_foreign` FOREIGN KEY (`idProveedor`) REFERENCES `proveedores` (`idProveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `articulosStocks`
--

DROP TABLE IF EXISTS `articulosStocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `articulosstocks_idarticulo_foreign` (`idArticulo`),
  KEY `articulosstocks_idtienda_foreign` (`idTienda`),
  CONSTRAINT `articulosstocks_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  CONSTRAINT `articulosstocks_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`)
) ENGINE=InnoDB AUTO_INCREMENT=22007 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `articulosTiendas`
--

DROP TABLE IF EXISTS `articulosTiendas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  KEY `idTienda_idArticulo` (`idArticulo`,`idTienda`) USING BTREE,
  CONSTRAINT `articulostiendas_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  CONSTRAINT `articulostiendas_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`)
) ENGINE=InnoDB AUTO_INCREMENT=14108 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cierres`
--

DROP TABLE IF EXISTS `cierres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cierres` (
  `idCierre` int(11) NOT NULL AUTO_INCREMENT,
  `FechaCierre` date NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `FechaInicio` datetime NOT NULL,
  `FechaFinal` datetime NOT NULL,
  `FechaCreacion` datetime NOT NULL,
  `Total` decimal(17,4) NOT NULL,
  PRIMARY KEY (`idCierre`),
  KEY `cierres_idtienda_foreign` (`idTienda`),
  KEY `cierres_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `cierres_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  CONSTRAINT `cierres_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=187 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cierres_ivas`
--

DROP TABLE IF EXISTS `cierres_ivas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cierres_ivas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idCierre` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `tipo_iva` int(11) NOT NULL,
  `importe_base` decimal(17,4) NOT NULL,
  `importe_iva` decimal(17,4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cierres_ivas_idcierre_foreign` (`idCierre`),
  KEY `cierres_ivas_idtienda_foreign` (`idTienda`),
  CONSTRAINT `cierres_ivas_idcierre_foreign` FOREIGN KEY (`idCierre`) REFERENCES `cierres` (`idCierre`),
  CONSTRAINT `cierres_ivas_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`)
) ENGINE=InnoDB AUTO_INCREMENT=587 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cierres_usuariosFormasPago`
--

DROP TABLE IF EXISTS `cierres_usuariosFormasPago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cierres_usuariosFormasPago` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idCierre` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `FormasPago` varchar(100) NOT NULL,
  `importe` decimal(17,4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cierres_usuariosformaspago_idcierre_foreign` (`idCierre`),
  KEY `cierres_usuariosformaspago_idtienda_foreign` (`idTienda`),
  KEY `cierres_usuariosformaspago_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `cierres_usuariosformaspago_idcierre_foreign` FOREIGN KEY (`idCierre`) REFERENCES `cierres` (`idCierre`),
  CONSTRAINT `cierres_usuariosformaspago_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  CONSTRAINT `cierres_usuariosformaspago_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=925 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cierres_usuarios_tickets`
--

DROP TABLE IF EXISTS `cierres_usuarios_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cierres_usuarios_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idCierre` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `Importe` decimal(17,4) NOT NULL,
  `Num_ticket_inicial` int(11) NOT NULL,
  `Num_ticket_final` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cierres_usuarios_tickets_idcierre_foreign` (`idCierre`),
  KEY `cierres_usuarios_tickets_idtienda_foreign` (`idTienda`),
  KEY `cierres_usuarios_tickets_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `cierres_usuarios_tickets_idcierre_foreign` FOREIGN KEY (`idCierre`) REFERENCES `cierres` (`idCierre`),
  CONSTRAINT `cierres_usuarios_tickets_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  CONSTRAINT `cierres_usuarios_tickets_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=514 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `idClientes` int(11) NOT NULL AUTO_INCREMENT,
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
  `recargo_equivalencia` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`idClientes`)
) ENGINE=InnoDB AUTO_INCREMENT=919 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `descuentos_tickets`
--

DROP TABLE IF EXISTS `descuentos_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  KEY `cliente` (`idCliente`),
  KEY `descuentos_tickets_idticket_foreign` (`idTicket`),
  KEY `descuentos_tickets_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `descuentos_tickets_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  CONSTRAINT `descuentos_tickets_idticket_foreign` FOREIGN KEY (`idTicket`) REFERENCES `ticketst` (`id`),
  CONSTRAINT `descuentos_tickets_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17420 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `diario_cron`
--

DROP TABLE IF EXISTS `diario_cron`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `diario_cron` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(150) NOT NULL,
  `ejecucion` timestamp NOT NULL DEFAULT current_timestamp(),
  `tarea_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `diario_cron_tarea` (`tarea_id`),
  CONSTRAINT `diario_cron_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `tareas_cron` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dispositivos`
--

DROP TABLE IF EXISTS `dispositivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `facProCobros`
--

DROP TABLE IF EXISTS `facProCobros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `facProCobros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idFactura` int(11) NOT NULL,
  `idFormasPago` int(11) NOT NULL,
  `FechaPago` date NOT NULL,
  `importe` float NOT NULL,
  `Referencia` varchar(25) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `facprocobros_idfactura_foreign` (`idFactura`),
  KEY `facprocobros_idformaspago_foreign` (`idFormasPago`),
  CONSTRAINT `facprocobros_idfactura_foreign` FOREIGN KEY (`idFactura`) REFERENCES `facprot` (`id`),
  CONSTRAINT `facprocobros_idformaspago_foreign` FOREIGN KEY (`idFormasPago`) REFERENCES `formasPago` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fac_cobros`
--

DROP TABLE IF EXISTS `fac_cobros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fac_cobros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idFactura` int(11) NOT NULL,
  `idFormasPago` int(11) NOT NULL,
  `FechaPago` date NOT NULL,
  `importe` float NOT NULL,
  `Referencia` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fac_cobros_idfactura_foreign` (`idFactura`),
  KEY `fac_cobros_idformaspago_foreign` (`idFormasPago`),
  CONSTRAINT `fac_cobros_idfactura_foreign` FOREIGN KEY (`idFactura`) REFERENCES `facclit` (`id`),
  CONSTRAINT `fac_cobros_idformaspago_foreign` FOREIGN KEY (`idFormasPago`) REFERENCES `formasPago` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `faccliIva`
--

DROP TABLE IF EXISTS `faccliIva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faccliIva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idfaccli` int(11) NOT NULL,
  `Numfaccli` int(11) NOT NULL,
  `iva` int(11) DEFAULT NULL,
  `importeIva` decimal(17,2) DEFAULT NULL,
  `totalbase` decimal(17,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `faccliiva_idfaccli_foreign` (`idfaccli`),
  CONSTRAINT `faccliiva_idfaccli_foreign` FOREIGN KEY (`idfaccli`) REFERENCES `facclit` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=424 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `facclilinea`
--

DROP TABLE IF EXISTS `facclilinea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `facclilinea_idarticulo_foreign` (`idArticulo`),
  KEY `facclilinea_idfaccli_foreign` (`idfaccli`),
  CONSTRAINT `facclilinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  CONSTRAINT `facclilinea_idfaccli_foreign` FOREIGN KEY (`idfaccli`) REFERENCES `facclit` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4070 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `faccliltemporales`
--

DROP TABLE IF EXISTS `faccliltemporales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=274 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `facclit`
--

DROP TABLE IF EXISTS `facclit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `facclit_idcliente_foreign` (`idCliente`),
  KEY `facclit_idtienda_foreign` (`idTienda`),
  KEY `facclit_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `facclit_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  CONSTRAINT `facclit_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  CONSTRAINT `facclit_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=258 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `facproIva`
--

DROP TABLE IF EXISTS `facproIva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `facproIva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idfacpro` int(11) NOT NULL,
  `Numfacpro` int(11) NOT NULL,
  `iva` int(11) DEFAULT NULL,
  `importeIva` decimal(17,2) DEFAULT NULL,
  `totalbase` decimal(17,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `facproiva_idfacpro_foreign` (`idfacpro`),
  CONSTRAINT `facproiva_idfacpro_foreign` FOREIGN KEY (`idfacpro`) REFERENCES `facprot` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1619 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `facprolinea`
--

DROP TABLE IF EXISTS `facprolinea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `facprolinea_idarticulo_foreign` (`idArticulo`),
  KEY `facprolinea_idfacpro_foreign` (`idfacpro`),
  CONSTRAINT `facprolinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  CONSTRAINT `facprolinea_idfacpro_foreign` FOREIGN KEY (`idfacpro`) REFERENCES `facprot` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38288 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `facproltemporales`
--

DROP TABLE IF EXISTS `facproltemporales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=1187 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `facprot`
--

DROP TABLE IF EXISTS `facprot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `facprot_idproveedor_foreign` (`idProveedor`),
  KEY `facprot_idtienda_foreign` (`idTienda`),
  KEY `facprot_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `facprot_idproveedor_foreign` FOREIGN KEY (`idProveedor`) REFERENCES `proveedores` (`idProveedor`),
  CONSTRAINT `facprot_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  CONSTRAINT `facprot_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=900 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `familias`
--

DROP TABLE IF EXISTS `familias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `familias` (
  `idFamilia` int(11) NOT NULL AUTO_INCREMENT,
  `familiaNombre` varchar(100) NOT NULL DEFAULT '',
  `familiaPadre` int(11) NOT NULL,
  `beneficiomedio` decimal(5,2) DEFAULT NULL,
  `mostrar_tpv` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`idFamilia`)
) ENGINE=InnoDB AUTO_INCREMENT=467 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `familiasTienda`
--

DROP TABLE IF EXISTS `familiasTienda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `familiasTienda` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idFamilia` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL,
  `idFamilia_tienda` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `familiastienda_idfamilia_foreign` (`idFamilia`),
  KEY `familiastienda_idtienda_foreign` (`idTienda`),
  CONSTRAINT `familiastienda_idfamilia_foreign` FOREIGN KEY (`idFamilia`) REFERENCES `familias` (`idFamilia`),
  CONSTRAINT `familiastienda_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`)
) ENGINE=InnoDB AUTO_INCREMENT=466 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `formasPago`
--

DROP TABLE IF EXISTS `formasPago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `formasPago` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `historico_precios`
--

DROP TABLE IF EXISTS `historico_precios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `historico_precios_idarticulo_foreign` (`idArticulo`),
  KEY `historico_precios_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `historico_precios_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  CONSTRAINT `historico_precios_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9044 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `importar_virtuemart_tickets`
--

DROP TABLE IF EXISTS `importar_virtuemart_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `importar_virtuemart_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idTicketst` int(11) NOT NULL,
  `Fecha` datetime NOT NULL,
  `estado` varchar(12) NOT NULL,
  `respuesta` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `indices`
--

DROP TABLE IF EXISTS `indices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `indices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idTienda` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `numticket` int(11) NOT NULL,
  `tempticket` int(11) NOT NULL COMMENT 'Es el numero con guardo temporal ticket',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `iva`
--

DROP TABLE IF EXISTS `iva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `iva` (
  `idIva` int(11) NOT NULL AUTO_INCREMENT,
  `descripcionIva` varchar(25) DEFAULT NULL,
  `iva` decimal(4,2) DEFAULT NULL,
  `recargo` decimal(4,2) DEFAULT NULL,
  PRIMARY KEY (`idIva`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migraciones`
--

DROP TABLE IF EXISTS `migraciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migraciones` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modulo_balanza`
--

DROP TABLE IF EXISTS `modulo_balanza`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `modulo_balanza` (
  `idBalanza` int(11) NOT NULL AUTO_INCREMENT,
  `nombreBalanza` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `conSeccion` varchar(3) NOT NULL,
  `Grupo` tinyint(3) unsigned NOT NULL,
  `Dirección` tinyint(3) unsigned NOT NULL,
  `IP` varchar(45) NOT NULL,
  `soloPLUS` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idBalanza`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modulo_balanza_plus`
--

DROP TABLE IF EXISTS `modulo_balanza_plus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `modulo_balanza_plus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idBalanza` int(11) NOT NULL,
  `plu` int(10) NOT NULL,
  `seccion` int(100) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=386 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modulo_etiquetado`
--

DROP TABLE IF EXISTS `modulo_etiquetado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modulo_etiquetado_temporal`
--

DROP TABLE IF EXISTS `modulo_etiquetado_temporal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=1082 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modulo_importar_registro`
--

DROP TABLE IF EXISTS `modulo_importar_registro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modulo_incidencia`
--

DROP TABLE IF EXISTS `modulo_incidencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modulos_configuracion`
--

DROP TABLE IF EXISTS `modulos_configuracion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `modulos_configuracion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idusuario` int(11) NOT NULL,
  `nombre_modulo` varchar(50) NOT NULL,
  `configuracion` longtext NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pedcliAlb`
--

DROP TABLE IF EXISTS `pedcliAlb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedcliAlb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idAlbaran` int(11) DEFAULT NULL,
  `numAlbaran` int(11) DEFAULT NULL,
  `idPedido` int(11) DEFAULT NULL,
  `numPedido` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pedclialb_idalbaran_foreign` (`idAlbaran`),
  KEY `pedclialb_idpedido_foreign` (`idPedido`),
  CONSTRAINT `pedclialb_idalbaran_foreign` FOREIGN KEY (`idAlbaran`) REFERENCES `albclit` (`id`),
  CONSTRAINT `pedclialb_idpedido_foreign` FOREIGN KEY (`idPedido`) REFERENCES `pedclit` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pedcliIva`
--

DROP TABLE IF EXISTS `pedcliIva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedcliIva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idpedcli` int(11) NOT NULL,
  `Numpedcli` int(11) NOT NULL,
  `iva` int(11) NOT NULL,
  `importeIva` decimal(17,2) NOT NULL,
  `totalbase` decimal(17,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pedcliiva_idpedcli_foreign` (`idpedcli`),
  CONSTRAINT `pedcliiva_idpedcli_foreign` FOREIGN KEY (`idpedcli`) REFERENCES `pedclit` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pedclilinea`
--

DROP TABLE IF EXISTS `pedclilinea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `pedclilinea_idarticulo_foreign` (`idArticulo`),
  KEY `pedclilinea_idpedcli_foreign` (`idpedcli`),
  CONSTRAINT `pedclilinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  CONSTRAINT `pedclilinea_idpedcli_foreign` FOREIGN KEY (`idpedcli`) REFERENCES `pedclit` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pedcliltemporales`
--

DROP TABLE IF EXISTS `pedcliltemporales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `pedcliltemporales_idcliente_foreign` (`idCliente`),
  KEY `pedcliltemporales_idtienda_foreign` (`idTienda`),
  KEY `pedcliltemporales_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `pedcliltemporales_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  CONSTRAINT `pedcliltemporales_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  CONSTRAINT `pedcliltemporales_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pedclit`
--

DROP TABLE IF EXISTS `pedclit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `pedclit_idcliente_foreign` (`idCliente`),
  KEY `pedclit_idtienda_foreign` (`idTienda`),
  KEY `pedclit_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `pedclit_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  CONSTRAINT `pedclit_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  CONSTRAINT `pedclit_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pedproAlb`
--

DROP TABLE IF EXISTS `pedproAlb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedproAlb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idAlbaran` int(11) DEFAULT NULL,
  `numAlbaran` int(11) DEFAULT NULL,
  `idPedido` int(11) DEFAULT NULL,
  `numPedido` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pedproalb_idalbaran_foreign` (`idAlbaran`),
  KEY `pedproalb_idpedido_foreign` (`idPedido`),
  CONSTRAINT `pedproalb_idalbaran_foreign` FOREIGN KEY (`idAlbaran`) REFERENCES `albprot` (`id`),
  CONSTRAINT `pedproalb_idpedido_foreign` FOREIGN KEY (`idPedido`) REFERENCES `pedprot` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pedproIva`
--

DROP TABLE IF EXISTS `pedproIva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedproIva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idpedpro` int(11) NOT NULL,
  `Numpedpro` int(11) DEFAULT NULL,
  `iva` int(11) NOT NULL,
  `importeIva` decimal(17,2) NOT NULL,
  `totalbase` decimal(17,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pedproiva_idpedpro_foreign` (`idpedpro`),
  CONSTRAINT `pedproiva_idpedpro_foreign` FOREIGN KEY (`idpedpro`) REFERENCES `pedprot` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2082 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pedprolinea`
--

DROP TABLE IF EXISTS `pedprolinea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `pedprolinea_idarticulo_foreign` (`idArticulo`),
  KEY `pedprolinea_idpedpro_foreign` (`idpedpro`),
  CONSTRAINT `pedprolinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  CONSTRAINT `pedprolinea_idpedpro_foreign` FOREIGN KEY (`idpedpro`) REFERENCES `pedprot` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14499 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pedprot`
--

DROP TABLE IF EXISTS `pedprot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `pedprot_idproveedor_foreign` (`idProveedor`),
  KEY `pedprot_idtienda_foreign` (`idTienda`),
  KEY `pedprot_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `pedprot_idproveedor_foreign` FOREIGN KEY (`idProveedor`) REFERENCES `proveedores` (`idProveedor`),
  CONSTRAINT `pedprot_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  CONSTRAINT `pedprot_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=883 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pedprotemporales`
--

DROP TABLE IF EXISTS `pedprotemporales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `pedprotemporales_idpedpro_foreign` (`idPedpro`),
  KEY `pedprotemporales_idproveedor_foreign` (`idProveedor`),
  KEY `pedprotemporales_idtienda_foreign` (`idTienda`),
  KEY `pedprotemporales_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `pedprotemporales_idpedpro_foreign` FOREIGN KEY (`idPedpro`) REFERENCES `pedprot` (`id`),
  CONSTRAINT `pedprotemporales_idproveedor_foreign` FOREIGN KEY (`idProveedor`) REFERENCES `proveedores` (`idProveedor`),
  CONSTRAINT `pedprotemporales_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  CONSTRAINT `pedprotemporales_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10767 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permisos`
--

DROP TABLE IF EXISTS `permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permisos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` int(11) NOT NULL,
  `modulo` varchar(50) DEFAULT NULL,
  `vista` varchar(50) DEFAULT NULL,
  `accion` varchar(50) DEFAULT NULL,
  `permiso` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8640 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=191 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stocksRegularizacion`
--

DROP TABLE IF EXISTS `stocksRegularizacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  `idAlbaran` int(11) DEFAULT NULL,
  `estado` int(11) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stocksregularizacion_idalbaran_foreign` (`idAlbaran`),
  KEY `stocksregularizacion_idarticulo_foreign` (`idArticulo`),
  KEY `stocksregularizacion_idtienda_foreign` (`idTienda`),
  KEY `stocksregularizacion_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `stocksregularizacion_idalbaran_foreign` FOREIGN KEY (`idAlbaran`) REFERENCES `albclit` (`id`),
  CONSTRAINT `stocksregularizacion_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  CONSTRAINT `stocksregularizacion_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  CONSTRAINT `stocksregularizacion_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tareas_cron`
--

DROP TABLE IF EXISTS `tareas_cron`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `temperaturas`
--

DROP TABLE IF EXISTS `temperaturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `temperaturas` (
  `idTemperatura` int(11) NOT NULL AUTO_INCREMENT,
  `idDispositivo` int(11) NOT NULL,
  `temperatura` decimal(5,2) NOT NULL,
  `fechaRegistro` datetime DEFAULT current_timestamp(),
  `idUsuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`idTemperatura`),
  KEY `idDispositivo` (`idDispositivo`),
  CONSTRAINT `temperaturas_ibfk_1` FOREIGN KEY (`idDispositivo`) REFERENCES `dispositivos` (`idDispositivo`)
) ENGINE=InnoDB AUTO_INCREMENT=495 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticketslinea`
--

DROP TABLE IF EXISTS `ticketslinea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `ticketslinea_idarticulo_foreign` (`idArticulo`),
  KEY `ticketslinea_idticketst_foreign` (`idticketst`),
  CONSTRAINT `ticketslinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  CONSTRAINT `ticketslinea_idticketst_foreign` FOREIGN KEY (`idticketst`) REFERENCES `ticketst` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=222077 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticketst`
--

DROP TABLE IF EXISTS `ticketst`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `ticketst_idcliente_foreign` (`idCliente`),
  KEY `ticketst_idtienda_foreign` (`idTienda`),
  KEY `ticketst_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `ticketst_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  CONSTRAINT `ticketst_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  CONSTRAINT `ticketst_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59220 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticketstIva`
--

DROP TABLE IF EXISTS `ticketstIva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticketstIva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idticketst` int(11) NOT NULL,
  `Numticket` int(11) NOT NULL,
  `iva` int(11) NOT NULL,
  `importeIva` decimal(17,2) NOT NULL,
  `totalbase` decimal(17,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ticketstiva_idticketst_foreign` (`idticketst`),
  CONSTRAINT `ticketstiva_idticketst_foreign` FOREIGN KEY (`idticketst`) REFERENCES `ticketst` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=101655 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticketstemporales`
--

DROP TABLE IF EXISTS `ticketstemporales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`id`),
  KEY `ticketstemporales_idclientes_foreign` (`idClientes`),
  KEY `ticketstemporales_idtienda_foreign` (`idTienda`),
  KEY `ticketstemporales_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `ticketstemporales_idclientes_foreign` FOREIGN KEY (`idClientes`) REFERENCES `clientes` (`idClientes`),
  CONSTRAINT `ticketstemporales_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  CONSTRAINT `ticketstemporales_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59220 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tiendas`
--

DROP TABLE IF EXISTS `tiendas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tiposVencimiento`
--

DROP TABLE IF EXISTS `tiposVencimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tiposVencimiento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(20) NOT NULL,
  `dias` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `group_id` int(11) NOT NULL COMMENT 'id grupo permisos',
  `estado` varchar(12) NOT NULL COMMENT 'estado',
  `nombre` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `vw_jerarquias_familias`
--

DROP TABLE IF EXISTS `vw_jerarquias_familias`;
/*!50001 DROP VIEW IF EXISTS `vw_jerarquias_familias`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_jerarquias_familias` AS SELECT
 1 AS `idFamilia`,
  1 AS `nivel`,
  1 AS `familiaNombre`,
  1 AS `idN1`,
  1 AS `idN2`,
  1 AS `familiaPadre`,
  1 AS `ruta` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_resumenClientesFacturas`
--

DROP TABLE IF EXISTS `vw_resumenClientesFacturas`;
/*!50001 DROP VIEW IF EXISTS `vw_resumenClientesFacturas`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_resumenClientesFacturas` AS SELECT
 1 AS `idCliente`,
  1 AS `nif`,
  1 AS `ejercicio`,
  1 AS `q1`,
  1 AS `q1Iva`,
  1 AS `q2`,
  1 AS `q2Iva`,
  1 AS `q3`,
  1 AS `q3Iva`,
  1 AS `q4`,
  1 AS `q4Iva`,
  1 AS `totalIva`,
  1 AS `total` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_resumenClientesTickets`
--

DROP TABLE IF EXISTS `vw_resumenClientesTickets`;
/*!50001 DROP VIEW IF EXISTS `vw_resumenClientesTickets`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_resumenClientesTickets` AS SELECT
 1 AS `idCliente`,
  1 AS `nif`,
  1 AS `ejercicio`,
  1 AS `q1`,
  1 AS `q1Iva`,
  1 AS `q2`,
  1 AS `q2Iva`,
  1 AS `q3`,
  1 AS `q3Iva`,
  1 AS `q4`,
  1 AS `q4Iva`,
  1 AS `total`,
  1 AS `totalIva` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_resumenProveedoresFacturas`
--

DROP TABLE IF EXISTS `vw_resumenProveedoresFacturas`;
/*!50001 DROP VIEW IF EXISTS `vw_resumenProveedoresFacturas`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_resumenProveedoresFacturas` AS SELECT
 1 AS `idProveedor`,
  1 AS `nif`,
  1 AS `ejercicio`,
  1 AS `q1`,
  1 AS `q1Iva`,
  1 AS `q2`,
  1 AS `q2Iva`,
  1 AS `q3`,
  1 AS `q3Iva`,
  1 AS `q4`,
  1 AS `q4Iva`,
  1 AS `total`,
  1 AS `totalIva` */;
SET character_set_client = @saved_cs_client;

--
-- Dumping events for database 'tpvfox'
--

--
-- Dumping routines for database 'tpvfox'
--

--
-- Final view structure for view `vw_jerarquias_familias`
--

/*!50001 DROP VIEW IF EXISTS `vw_jerarquias_familias`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
CREATE OR REPLACE SQL SECURITY INVOKER VIEW vw_jerarquias_familias AS
WITH RECURSIVE ArbolFamilias AS (
    -- 1. CASO BASE: Nivel 1 (Los Departamentos)
    SELECT
        idFamilia,
        familiaNombre,
        familiaPadre,
        1 AS nivel,
        idFamilia AS idN1,          -- Ella misma es su N1
        CAST(NULL AS UNSIGNED) AS idN2, -- No tiene N2 aún
        CAST(familiaNombre AS CHAR(500)) AS ruta
    FROM familias
    WHERE familiaPadre = 0 OR familiaPadre IS NULL

    UNION ALL

    -- 2. CASO RECURSIVO: Niveles 2, 3, 4...
    SELECT
        f.idFamilia,
        f.familiaNombre,
        f.familiaPadre,
        af.nivel + 1,
        af.idN1,                    -- Hereda el N1 del padre siempre
        CASE
            WHEN af.nivel = 1 THEN f.idFamilia -- Si el padre es N1, ella es el N2
            ELSE af.idN2                       -- Si no, hereda el N2 que ya traía el padre
        END,
        CONCAT(af.ruta, ' > ', f.familiaNombre)
    FROM familias f
    INNER JOIN ArbolFamilias af ON f.familiaPadre = af.idFamilia
)
-- 3. RESULTADO FINAL DE LA VISTA
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
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_resumenClientesFacturas`
--

/*!50001 DROP VIEW IF EXISTS `vw_resumenClientesFacturas`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
CREATE OR REPLACE SQL SECURITY INVOKER VIEW vw_resumenClientesFacturas AS
SELECT
    c.idClientes AS idCliente,
    c.nif,
    f.ejercicio,
    SUM(CASE WHEN f.trimestre = 1 THEN f.totalFactura ELSE 0 END) AS q1,
    SUM(CASE WHEN f.trimestre = 1 THEN f.totalIva ELSE 0 END) AS q1Iva,
    SUM(CASE WHEN f.trimestre = 2 THEN f.totalFactura ELSE 0 END) AS q2,
    SUM(CASE WHEN f.trimestre = 2 THEN f.totalIva ELSE 0 END) AS q2Iva,
    SUM(CASE WHEN f.trimestre = 3 THEN f.totalFactura ELSE 0 END) AS q3,
    SUM(CASE WHEN f.trimestre = 3 THEN f.totalIva ELSE 0 END) AS q3Iva,
    SUM(CASE WHEN f.trimestre = 4 THEN f.totalFactura ELSE 0 END) AS q4,
    SUM(CASE WHEN f.trimestre = 4 THEN f.totalIva ELSE 0 END) AS q4Iva,
    SUM(f.totalIva) AS totalIva,
    SUM(f.totalFactura) AS total
FROM (
    SELECT
        fac.idCliente,
        YEAR(fac.Fecha) AS ejercicio,
        QUARTER(fac.Fecha) AS trimestre,
        fac.total AS totalFactura,
        SUM(fiva.importeIva) AS totalIva
    FROM facclit fac
    JOIN faccliIva fiva ON fiva.idfaccli = fac.id
    WHERE fac.estado <> 'Sin Guardar'
    GROUP BY fac.id -- Agrupamos por ID de factura para obtener su IVA total
) f
JOIN clientes c ON c.idClientes = f.idCliente
GROUP BY c.idClientes, c.nif, f.ejercicio;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_resumenClientesTickets`
--

/*!50001 DROP VIEW IF EXISTS `vw_resumenClientesTickets`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
CREATE OR REPLACE SQL SECURITY INVOKER VIEW vw_resumenClientesTickets AS
SELECT
    c.idClientes AS idCliente,
    c.nif,
    t.ejercicio,
    SUM(CASE WHEN t.trimestre = 1 THEN t.totalTicket ELSE 0 END) AS q1,
    SUM(CASE WHEN t.trimestre = 1 THEN t.totalIva ELSE 0 END) AS q1Iva,
    SUM(CASE WHEN t.trimestre = 2 THEN t.totalTicket ELSE 0 END) AS q2,
    SUM(CASE WHEN t.trimestre = 2 THEN t.totalIva ELSE 0 END) AS q2Iva,
    SUM(CASE WHEN t.trimestre = 3 THEN t.totalTicket ELSE 0 END) AS q3,
    SUM(CASE WHEN t.trimestre = 3 THEN t.totalIva ELSE 0 END) AS q3Iva,
    SUM(CASE WHEN t.trimestre = 4 THEN t.totalTicket ELSE 0 END) AS q4,
    SUM(CASE WHEN t.trimestre = 4 THEN t.totalIva ELSE 0 END) AS q4Iva,
    SUM(t.totalIva) AS totalIva,
    SUM(t.totalTicket) AS total
FROM (
    SELECT
        tick.idCliente,
        YEAR(tick.Fecha) AS ejercicio,
        QUARTER(tick.Fecha) AS trimestre,
        tick.total AS totalTicket,
        SUM(tiva.importeIva) AS totalIva
    FROM ticketst tick
    JOIN ticketstIva tiva ON tiva.idticketst = tick.id
    WHERE tick.estado = 'Cerrado'
    GROUP BY tick.id
) t
JOIN clientes c ON c.idClientes = t.idCliente
GROUP BY c.idClientes, c.nif, t.ejercicio;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_resumenProveedoresFacturas`
--

/*!50001 DROP VIEW IF EXISTS `vw_resumenProveedoresFacturas`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
CREATE OR REPLACE SQL SECURITY INVOKER VIEW vw_resumenProveedoresFacturas AS
SELECT
    p.idProveedor,
    p.nif,
    f.ejercicio,
    -- Agregación por trimestres
    SUM(CASE WHEN f.trimestre = 1 THEN f.totalFactura ELSE 0 END) AS q1,
    SUM(CASE WHEN f.trimestre = 1 THEN f.totalIva ELSE 0 END) AS q1Iva,
    SUM(CASE WHEN f.trimestre = 2 THEN f.totalFactura ELSE 0 END) AS q2,
    SUM(CASE WHEN f.trimestre = 2 THEN f.totalIva ELSE 0 END) AS q2Iva,
    SUM(CASE WHEN f.trimestre = 3 THEN f.totalFactura ELSE 0 END) AS q3,
    SUM(CASE WHEN f.trimestre = 3 THEN f.totalIva ELSE 0 END) AS q3Iva,
    SUM(CASE WHEN f.trimestre = 4 THEN f.totalFactura ELSE 0 END) AS q4,
    SUM(CASE WHEN f.trimestre = 4 THEN f.totalIva ELSE 0 END) AS q4Iva,
    -- Totales anuales
    SUM(f.totalIva) AS totalIva,
    SUM(f.totalFactura) AS total
FROM (
    -- Subconsulta para calcular el IVA por factura primero
    SELECT
        fac.idProveedor,
        YEAR(fac.Fecha) AS ejercicio,
        QUARTER(fac.Fecha) AS trimestre,
        fac.total AS totalFactura,
        SUM(fiva.importeIva) AS totalIva
    FROM facprot fac
    JOIN facproIva fiva ON fiva.idfacpro = fac.id
    WHERE fac.estado <> 'Sin Guardar'
    GROUP BY fac.id -- Agrupamos por ID de factura para tener el IVA total de cada una
) f
JOIN proveedores p ON p.idProveedor = f.idProveedor
GROUP BY p.idProveedor, p.nif, f.ejercicio;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
