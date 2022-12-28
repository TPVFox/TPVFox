ALTER TABLE `faccliltemporales` ADD `Fecha` DATETIME NULL DEFAULT NULL AFTER `idUsuario`;
ALTER TABLE `faccliltemporales` CHANGE `numfaccli` `Numfaccli` INT(11) NULL DEFAULT NULL;
ALTER TABLE `faccliltemporales` CHANGE `idClientes` `idCliente` INT(11) NULL DEFAULT NULL;
ALTER TABLE `facclit` DROP `formaPago`;
ALTER TABLE `faccliltemporales` DROP `estadoFacCli`
