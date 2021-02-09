CREATE TABLE `facProCobros` (
  `id` int(11) NOT NULL,
  `idFactura` int(11) NOT NULL,
  `idFormasPago` int(11) NOT NULL,
  `FechaPago` date NOT NULL,
  `importe` float NOT NULL,
  `Referencia` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


ALTER TABLE `facProCobros`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `facProCobros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIEN


ALTER TABLE `facproltemporales` ADD `FacCobros` VARBINARY(5000) NOT NULL AFTER `Su_numero`;
