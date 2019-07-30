ALTER TABLE `articulosTiendas` DROP PRIMARY KEY, ADD INDEX `idTienda_idArticulo` (`idArticulo`, `idTienda`) USING BTREE;
ALTER TABLE `articulosTiendas` ADD `id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);
