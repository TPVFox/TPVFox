ALTER TABLE `facprot` ADD `total_siniva` DECIMAL(17,6) NOT NULL AFTER `entregado`;
ALTER TABLE `albprot` ADD `total_siniva` DECIMAL(17,6) NOT NULL AFTER `entregado`;
ALTER TABLE `pedprot` ADD `total_siniva` DECIMAL(17,6) NOT NULL AFTER `entregado`;
# Cambios para que funcione importacion csv
ALTER TABLE `proveedores` CHANGE `movil` `movil` VARCHAR(11) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
ALTER TABLE `proveedores` CHANGE `fax` `fax` VARCHAR(11) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
ALTER TABLE `proveedores` CHANGE `email` `email` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
ALTER TABLE `proveedores` CHANGE `telefono` `telefono` VARCHAR(11) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
ALTER TABLE `articulosCodigoBarras` ADD `id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);
ALTER TABLE `articulosPrecios` DROP PRIMARY KEY, ADD INDEX (`idArticulo`, `idTienda`) USING BTREE;
ALTER TABLE `articulosPrecios` ADD `id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);
# Esta instruccion puede ser util.. si ya tenemos el indice creado primary
ALTER TABLE `articulosFamilias` DROP PRIMARY KEY, ADD UNIQUE (`idArticulo`, `idFamilia`) USING BTREE; 
ALTER TABLE `articulosFamilias` ADD `id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);
ALTER TABLE `articulosFamilias` ADD UNIQUE (`idArticulo`, `idFamilia`); 

ALTER TABLE `articulosTiendas` CHANGE `idVirtuemart` `idVirtuemart` INT(11) NULL;
ALTER TABLE `modulos_configuracion` ADD `id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);
ALTER TABLE `modulos_configuracion` CHANGE `configuracion` `configuracion` LONGTEXT NOT NULL;
