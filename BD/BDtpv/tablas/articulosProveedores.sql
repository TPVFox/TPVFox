-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 08-11-2017 a las 13:41:27
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
-- Estructura de tabla para la tabla `articulosProveedores`
--

CREATE TABLE `articulosProveedores` (
  `idArticulo` int(11) NOT NULL,
  `idProveedor` int(11) NOT NULL,
  `crefProveedor` varchar(24) DEFAULT NULL,
  `coste` decimal(17,6) NOT NULL,
  `fechaActualizacion` date NOT NULL,
  `estado` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `articulosProveedores`
--
ALTER TABLE `articulosProveedores`
  ADD PRIMARY KEY (`idArticulo`,`idProveedor`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
