# Cambio la campos tabla temporal para dejarlo igual que tabla alprot y ahorramos codigo.
ALTER TABLE `albproltemporales` CHANGE `numalbpro` `Numalbpro` INT(11) NULL DEFAULT NULL;
ALTER TABLE `albproltemporales` ADD `Fecha` DATETIME DEFAULT NULL AFTER `idUsuario`;
# Esto es el primer paso para eliminar el campo Numpedpro de las tablas auxiliares, ya que no tiene sentido.
ALTER TABLE `pedproIva` CHANGE `Numpedpro` `Numpedpro` INT(11) NULL;
ALTER TABLE `pedprolinea` CHANGE `Numpedpro` `Numpedpro` INT(11) NULL; 
# Añado campos para controlar quien modifica un pedido, albaran o factura de un proveedor.
ALTER TABLE `pedprot` ADD `modify_by` INT NULL AFTER `fechaModificacion`; 
ALTER TABLE `albprolinea` CHANGE `Numpedpro` `idpedpro` INT(10) NULL DEFAULT NULL; 
ALTER TABLE `albprot` ADD `fechaModificacion` DATETIME NULL AFTER `FechaVencimiento`;
ALTER TABLE `albprot` ADD `modify_by` INT NULL AFTER `fechaModificacion`; 
ALTER TABLE `facproltemporales` ADD `Fecha` DATETIME DEFAULT NULL AFTER `idUsuario`;
# Si hay temporales hay que cubrir el campo fecha.
UPDATE `albproltemporales` SET `Fecha`=fechaInicio WHERE 1;
UPDATE `facproltemporales` SET `Fecha`=fechaInicio WHERE 1;
