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
  
ALTER TABLE `permisos` CHANGE `modulo` `modulo` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;
ALTER TABLE `permisos` CHANGE `vista` `vista` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;
ALTER TABLE `permisos` CHANGE `accion` `accion` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;
