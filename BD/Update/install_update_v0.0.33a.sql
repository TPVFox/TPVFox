CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `modulo` varchar(20) DEFAULT NULL,
  `vista` varchar(20) DEFAULT NULL,
  `accion` varchar(20) DEFAULT NULL,
  `permiso` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
  
