# Eliminamos tabla que no utilizamos y no es necesaria
DROP TABLE Cli_FroPa_TipoVen;
ALTER TABLE `clientes` CHANGE `email` `email` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `clientes` CHANGE `fax` `fax` VARCHAR(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;
ALTER TABLE `clientes` CHANGE `movil` `movil` VARCHAR(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;
ALTER TABLE `clientes` CHANGE `telefono` `telefono` VARCHAR(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;
ALTER TABLE `clientes` CHANGE `codpostal` `codpostal` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;
ALTER TABLE `clientes` CHANGE `direccion` `direccion` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL; 
ALTER TABLE `clientes` CHANGE `nif` `nif` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
ALTER TABLE `clientes` CHANGE `razonsocial` `razonsocial` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL; 
ALTER TABLE `clientes` CHANGE `fomasVenci` `formasVenci` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL; 
