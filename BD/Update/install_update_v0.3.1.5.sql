ALTER TABLE `faccliltemporales` ADD `Fecha` DATETIME NULL DEFAULT NULL AFTER `idUsuario`;
ALTER TABLE `faccliltemporales` CHANGE `numfaccli` `Numfaccli` INT(11) NULL DEFAULT NULL;
ALTER TABLE `faccliltemporales` CHANGE `idClientes` `idCliente` INT(11) NULL DEFAULT NULL;
ALTER TABLE `faccliltemporales` DROP `estadoFacCli`;
ALTER TABLE `faccliltemporales` DROP `FacCobros`;
ALTER TABLE `faccliltemporales` CHANGE `fechaFinal` `fechaVencimiento` DATETIME NULL DEFAULT NULL;
ALTER TABLE `facclit` DROP `formaPago`;
UPDATE `tiposVencimiento` SET `dias` = '0' WHERE `tiposVencimiento`.`id` = 1; 
