ALTER TABLE `familiasTienda` CHANGE `ref_familia_tienda` `idFamilia_tienda` INT(11) NOT NULL;
ALTER TABLE `articulos` ADD `tipo` VARCHAR(10) NULL AFTER `ultimoCoste`;

CREATE TABLE `tpvfox_provincial`.`modulo_balanza` ( `idBalanza` INT NOT NULL AUTO_INCREMENT , `nombreBalanza` VARCHAR(100) NOT NULL , `modelo` VARCHAR(100) NOT NULL , `conTecla` VARCHAR(3) NOT NULL , PRIMARY KEY (`idBalanza`)) ENGINE = InnoDB;

CREATE TABLE `tpvfox_provincial`.`modulo_balanza_plus` ( `idBalanza` INT NOT NULL , `plu` INT(10) NOT NULL , `tecla` INT(100) NOT NULL , `idArticulo` INT NOT NULL ) ENGINE = InnoDB;

UPDATE `articulos` SET tipo='unidad';
-- Esto es como cambiariamos familias.. los idFamilia depende de la instalacion.
UPDATE `articulos` as a inner join articulosFamilias as b on a.idArticulo=b.idArticulo set a.tipo='peso' where b.idFamilia in (28, 214, 25, 24, 26, 215, 444, 112, 85, 217, 27, 34, 54, 33, 35, 159, 160, 158, 62, 59, 60, 357, 387, 58, 380, 388, 356, 386, 61, 404, 379, 401, 374, 378, 399, 403, 372, 402, 414, 373)
