ALTER TABLE `pedprotemporales` ADD `Fecha` DATETIME NOT NULL AFTER `idUsuario`;
UPDATE `pedprotemporales` SET `Fecha`=fechaInicio WHERE 1;

