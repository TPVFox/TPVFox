# Cambio la campos tabla temporal para dejarlo igual que tabla alprot y ahorramos codigo.
ALTER TABLE `albproltemporales` CHANGE `numalbpro` `Numalbpro` INT(11) NULL DEFAULT NULL;
ALTER TABLE `albproltemporales` ADD `Fecha` DATETIME DEFAULT NULL AFTER `idUsuario`;  
