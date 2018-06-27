ALTER TABLE `articulosStocks` 
    ADD `fechaRegularizacion` DATETIME NULL AFTER `fecha_modificado`, 
    ADD `usuarioRegularizacion` INT NULL AFTER `fechaRegularizacion`;

CREATE TABLE `stocksRegularizacion` (
  `id` int(11) NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `idTienda` int(11) NOT NULL DEFAULT '1',
  `fechaRegularizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stockActual` decimal(17,6) NOT NULL,
  `stockModif` decimal(17,6) NOT NULL,
  `stockFinal` decimal(17,6) NOT NULL,
  `stockOperacion` int(1) NOT NULL DEFAULT '1',
  `idUsuario` int(11) NOT NULL,
  `idAlbaran` int(11) NOT NULL DEFAULT 0,
  `estado` int(11) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

