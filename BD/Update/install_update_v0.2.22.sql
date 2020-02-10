# Cambio la campos tabla temporal para dejarlo igual que tabla alprot y ahorramos codigo.
ALTER TABLE `albproltemporales` CHANGE `numalbpro` `Numalbpro` INT(11) NULL DEFAULT NULL;
# ===================  MAL  =========================== #
# Aunque hago este cambio, no es lo correcto, ya que debería ser id no num como hago albarane y facturas.
# Esto es el primer paso para eliminar el campo Numpedpro de las tablas auxiliares, ya que no tiene sentido.
ALTER TABLE `pedproIva` CHANGE `Numpedpro` `Numpedpro` INT(11) NULL;
ALTER TABLE `pedprolinea` CHANGE `Numpedpro` `Numpedpro` INT(11) NULL; 
# Creo campo Fecha
ALTER TABLE `albproltemporales` ADD `Fecha` DATETIME DEFAULT NULL AFTER `idUsuario`;
ALTER TABLE `facproltemporales` ADD `Fecha` DATETIME DEFAULT NULL AFTER `idUsuario`;
# Creo campo de fecha vencimiento que no la tenía.
ALTER TABLE `facprot` ADD `FechaVencimiento` DATETIME NULL AFTER `total`;
# Cambio nombre de campo
# El albprolinea y facprolines se cambia Num po id porque puede ser distinto del numero de pedido y el numero albaran.
# al id registro , de momento no esta preparo para hacerlo, pero en futuro se podría hacer.
ALTER TABLE `albprolinea` CHANGE `Numpedpro` `idpedpro` INT(10) NULL DEFAULT NULL; 
ALTER TABLE `facprolinea` CHANGE `Numalbpro` `idalbpro` INT(10) NULL DEFAULT NULL;
# Cambio `Su_numero` ya que en facturas es  Su_num_factura
ALTER TABLE `facproltemporales` CHANGE `Su_numero` `Su_num_factura` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
# Cambio tipo campo en facprot
ALTER TABLE `facprot` CHANGE `FechaVencimiento` `FechaVencimiento` DATE NULL DEFAULT NULL;
# Añado campos para controlar quien modifica un pedido, albaran o factura de un proveedor.
ALTER TABLE `pedprot` ADD `modify_by` INT NULL AFTER `fechaModificacion`; 
ALTER TABLE `albprot` ADD `fechaModificacion` DATETIME NULL AFTER `FechaVencimiento`;
ALTER TABLE `albprot` ADD `modify_by` INT NULL AFTER `fechaModificacion`; 
ALTER TABLE `facprot` ADD `fechaModificacion` DATETIME NULL AFTER `FechaVencimiento`;
ALTER TABLE `facprot` ADD `modify_by` INT NULL AFTER `fechaModificacion`; 
# Si hay temporales hay que cubrir el campo fecha.
UPDATE `albproltemporales` SET `Fecha`=fechaInicio WHERE 1;
UPDATE `facproltemporales` SET `Fecha`=fechaInicio WHERE 1;
# Cambio en tabla tienda, ya que al reiniciar BD no obliga poner dominio y key , y no es correcto.
ALTER TABLE `tiendas` CHANGE `dominio` `dominio` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
ALTER TABLE `tiendas` CHANGE `key_api` `key_api` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL; 
