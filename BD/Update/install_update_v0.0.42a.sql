DELETE FROM `familiasTienda` WHERE `idTienda`=1;
ALTER TABLE `familiasTienda` ADD `id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);
