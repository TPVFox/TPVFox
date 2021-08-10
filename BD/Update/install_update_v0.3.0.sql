


DROP TABLE IF EXISTS `descuentos_tickets`;
# Estados: Pendiente, Pagado, Caducado
CREATE TABLE `tpvfox`.`descuentos_tickets` ( 
    `id` INT NOT NULL AUTO_INCREMENT , 
    `idCliente` INT NOT NULL , 
    descuentoCliente decimal(4,2) NOT NULL,
    `fechaInicio` DATETIME NOT NULL , 
    `fechaFin` DATETIME NOT NULL , 
    `numTickets` INT NOT NULL , 
    `importeTickets` decimal(17,2) NOT NULL , 
    `importeDescuento` decimal(17,2) NOT NULL , 
    `idTicket` INT NULL , 
    `idUsuario` INT NOT NULL , 
    `fechaCreacion` INT NOT NULL , 
    `estado` varchar(12) NOT NULL DEFAULT 'Pendiente' , 
    PRIMARY KEY (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `tpvfox`.`descuentos_tickets` ADD INDEX `cliente` (`idCliente`);

ALTER TABLE `clientes`
  DROP IF EXISTS `descuento_ticket`;
  ALTER TABLE `clientes` ADD `descuento_ticket` DECIMAL(5,2) NOT NULL DEFAULT '3.0' AFTER `fecha_creado`;