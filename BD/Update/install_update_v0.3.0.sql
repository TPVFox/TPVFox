


DROP TABLE IF EXISTS `descuentos_tickets`;
# Estados: Pendiente, Pagado, Caducado
CREATE TABLE `descuentos_tickets` ( 
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
    `fechaCreacion` DATETIME NOT NULL DEFAULT NOW(), 
    `estado` varchar(12) NOT NULL DEFAULT 'Pendiente' , 
    PRIMARY KEY (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `descuentos_tickets` ADD INDEX `cliente` (`idCliente`);

ALTER TABLE `clientes`
  DROP IF EXISTS `descuento_ticket`;
  ALTER TABLE `clientes` ADD `descuento_ticket` DECIMAL(5,2) NOT NULL DEFAULT '3.0' AFTER `fecha_creado`;

  ALTER TABLE `clientes`
  DROP IF EXISTS `requiere_factura`,
  DROP IF EXISTS `recargo_equivalencia`;

  ALTER TABLE `clientes` ADD `requiere_factura` BOOLEAN NOT NULL DEFAULT FALSE AFTER `descuento_ticket`, 
  ADD `recargo_equivalencia` BOOLEAN NOT NULL DEFAULT FALSE AFTER `requiere_factura`;

  ALTER TABLE `clientes` CHANGE `fecha_creado` `fecha_creado` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;