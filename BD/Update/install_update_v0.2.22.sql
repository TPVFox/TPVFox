# Cambio la campos tabla temporal para dejarlo igual que tabla alprot y ahorramos codigo.
ALTER TABLE `albproltemporales` CHANGE `numalbpro` `Numalbpro` INT(11) NULL DEFAULT NULL;
ALTER TABLE `albproltemporales` ADD `Fecha` DATETIME DEFAULT NULL AFTER `idUsuario`;
# Esto es el primer paso para eliminar el campo Numpedpro de las tablas auxiliares, ya que no tiene sentido.
ALTER TABLE `pedproIva` CHANGE `Numpedpro` `Numpedpro` INT(11) NULL;
ALTER TABLE `pedprolinea` CHANGE `Numpedpro` `Numpedpro` INT(11) NULL; 
# Añado campos para controlar quien modifica un pedido, albaran o factura de un proveedor.
ALTER TABLE `pedprot` ADD `modify_by` INT NULL AFTER `fechaModificacion`; 
