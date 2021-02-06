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
-- Table structure for table `modulo_incidencia`
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
  
  ALTER TABLE `modulo_incidencia`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `modulo_incidencia`
--
ALTER TABLE `modulo_incidencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
  
  
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
-- Table structure for table `historico_precios`
--

CREATE TABLE `historico_precios` (
  `id` int(11) NOT NULL,
  `idArticulo` int(10) NOT NULL,
  `Antes` decimal(17,0) NOT NULL,
  `Nuevo` decimal(17,0) NOT NULL,
  `Fecha_Creacion` date NOT NULL,
  `NumDoc` int(11) NOT NULL,
  `Dedonde` varchar(50) NOT NULL,
  `Tipo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `historico_precios`
--
ALTER TABLE `historico_precios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `historico_precios`
--
ALTER TABLE `historico_precios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
