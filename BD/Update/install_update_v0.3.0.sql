


DROP TABLE IF EXISTS `descuentos_tickets`;
CREATE TABLE `descuentos_tickets` (
CREATE TABLE `tpvfox`.`descuentos_tickets` ( 
    `id` INT NOT NULL AUTO_INCREMENT , 
    `idCliente` INT NOT NULL , 
    `fechaInicio` DATETIME NOT NULL , 
    `fechaFin` DATETIME NOT NULL , 
    `numTickets` INT NOT NULL , 
    `importeTickets` FLOAT(0.0) NOT NULL , 
    `importeDescuento` FLOAT(0.0) NOT NULL , 
    `idTicket` INT NULL , 
    `idUsuario` INT NOT NULL , 
    `fechaCreacion` INT NOT NULL , `estado` SMALLINT NOT NULL DEFAULT '1' , 
    PRIMARY KEY (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;
