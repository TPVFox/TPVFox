-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 16, 2018 at 05:19 PM
-- Server version: 10.1.26-MariaDB-0+deb9u1
-- PHP Version: 7.0.27-0+deb9u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tpv`
--

-- --------------------------------------------------------

--
-- Table structure for table `pedprolinea`
--

CREATE TABLE `pedprolinea` (
  `id` int(11) NOT NULL,
  `idpedpro` int(11) NOT NULL,
  `Numpedpro` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `cref` varchar(18) DEFAULT NULL,
  `ref_prov` varchar(18) NOT NULL,
  `ccodbar` varchar(18) DEFAULT NULL,
  `cdetalle` varchar(100) NOT NULL,
  `ncant` decimal(17,6) DEFAULT NULL,
  `nunidades` decimal(17,6) DEFAULT NULL,
  `costeSiva` decimal(17,4) NOT NULL,
  `iva` decimal(4,2) NOT NULL,
  `nfila` int(11) NOT NULL,
  `estadoLinea` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pedprolinea`
--
ALTER TABLE `pedprolinea`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pedprolinea`
--
ALTER TABLE `pedprolinea`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
