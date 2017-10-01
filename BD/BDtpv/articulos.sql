-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Servidor: localhost
-- Tiempo de generación: 02-10-2017 a las 00:49:21
-- Versión del servidor: 5.5.57-0ubuntu0.14.04.1
-- Versión de PHP: 5.6.31-4+ubuntu14.04.1+deb.sury.org+4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de datos: `tpv`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulos`
--

CREATE TABLE IF NOT EXISTS `articulos` (
  `idArticulo` int(11) NOT NULL AUTO_INCREMENT,
  `idTienda` int(1) NOT NULL DEFAULT '0',
  `crefTienda` varchar(18) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `articulo_name` varchar(180) CHARACTER SET utf8 DEFAULT '',
  `iva` decimal(10,4) DEFAULT NULL,
  `codbarras` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `beneficio` int(1) NOT NULL DEFAULT '0',
  `costepromedio` int(1) NOT NULL DEFAULT '0',
  `estado` varchar(11) CHARACTER SET utf8 DEFAULT NULL,
  `pvpCiva` decimal(15,6) DEFAULT NULL,
  `pvpSiva` decimal(15,6) DEFAULT NULL,
  `idProveedor` int(1) NOT NULL DEFAULT '0',
  `fecha_creado` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `fecha_modificado` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`idArticulo`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=2770 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
