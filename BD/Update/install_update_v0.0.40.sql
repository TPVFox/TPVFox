ALTER TABLE `familiasTienda` CHANGE `ref_familia_tienda` `idFamilia_tienda` INT(11) NOT NULL;
ALTER TABLE `articulos` ADD `tipo` VARCHAR(10) NULL AFTER `ultimoCoste`;

CREATE TABLE `tpvfox_provincial`.`modulo_balanza` ( `idBalanza` INT NOT NULL AUTO_INCREMENT , `nombreBalanza` VARCHAR(100) NOT NULL , `modelo` VARCHAR(100) NOT NULL , `conTecla` VARCHAR(3) NOT NULL , PRIMARY KEY (`idBalanza`)) ENGINE = InnoDB;

CREATE TABLE `tpvfox_provincial`.`modulo_balanza_plus` ( `idBalanza` INT NOT NULL , `plu` INT(10) NOT NULL , `tecla` INT(100) NOT NULL , `idArticulo` INT NOT NULL ) ENGINE = InnoDB;

UPDATE `articulos` SET tipo='unidad'
