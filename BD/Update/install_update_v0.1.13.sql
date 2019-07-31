ALTER TABLE `articulosTiendas` DROP PRIMARY KEY, ADD INDEX `idTienda_idArticulo` (`idArticulo`, `idTienda`) USING BTREE;
ALTER TABLE `articulosTiendas` ADD `id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);
ALTER TABLE `ticketstemporales` CHANGE `fechaFinal` `fechaFinal` DATETIME NULL;
ALTER TABLE `ticketstemporales` DROP `total_ivas`;
