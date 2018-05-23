ALTER TABLE `pedclilinea` ADD `pvpSiva` DECIMAL(17.6) NULL AFTER `estadoLinea`;

ALTER TABLE `albclilinea` ADD `pvpSiva` DECIMAL(17.6) NULL AFTER `NumpedCli`;

ALTER TABLE `facclilinea` ADD `pvpSiva` DECIMAL(17.6) NULL AFTER `NumalbCli`;
