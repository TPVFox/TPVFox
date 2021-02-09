-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 02-04-2018 a las 18:09:48
-- Versión del servidor: 10.1.26-MariaDB-0+deb9u1
-- Versión de PHP: 7.0.27-0+deb9u1

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
-- Estructura de tabla para la tabla `tiposVencimiento`
--

CREATE TABLE `tiposVencimiento` (
  `id` int(11) NOT NULL,
  `descripcion` varchar(20) NOT NULL,
  `dias` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `tiposVencimiento`
--

INSERT INTO `tiposVencimiento` (`id`, `descripcion`, `dias`) VALUES
(1, 'Contado', 1),
(2, 'Semanal', 7),
(3, 'Quincenal', 15),
(4, 'Mensual', 30),
(5, 'Semestral', 181);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tiposVencimiento`
--
ALTER TABLE `tiposVencimiento`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `tiposVencimiento`
--
ALTER TABLE `tiposVencimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
