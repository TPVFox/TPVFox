DELETE FROM `familiasTienda` WHERE `idTienda`=1 
ALTER TABLE `familiasTienda` ADD `id` INT NOT NULL AUTO_INCREMENT AFTER `idFamilia_tienda`, ADD PRIMARY KEY (`id`);
