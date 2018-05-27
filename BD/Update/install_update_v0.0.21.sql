ALTER TABLE `pedclilinea` ADD `pvpSiva` DECIMAL(17.6) NULL AFTER `estadoLinea`;

ALTER TABLE `albclilinea` ADD `pvpSiva` DECIMAL(17.6) NULL AFTER `NumpedCli`;


ALTER TABLE `facclilinea` ADD `pvpSiva` DECIMAL(17.6) NULL AFTER `NumalbCli`;


-- Queda pendiente cubrir el campo de registros anteriores
-- podría cubrir todos los campos con esto, pero podría provocar que no coincida los totales.
-- update pedclilinea SET pvpSiva = precioCiva / (1+iva/100);
-- update albclilinea SET pvpSiva = precioCiva / (1+iva/100);
-- update facclilinea SET pvpSiva = precioCiva / (1+iva/100);
