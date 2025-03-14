--  Alagoro Mayo 2024
-- Crear tabla tareas Cron
DROP TABLE IF EXISTS tareas_cron;
CREATE TABLE tareas_cron (
 `id` INT(11) NOT NULL AUTO_INCREMENT ,
 `nombre` VARCHAR(50) NOT NULL ,
 `cantidad_periodo` INT(11) NOT NULL , 
 `tipo_periodo` INT(2) NOT NULL , 
 `nombre_clase` VARCHAR(50) NOT NULL ,
 `inicio_ejecucion` DATE NOT NULL, 
 `ultima_ejecucion` DATETIME NULL, 
 `estado` INT(2) NOT NULL DEFAULT 1, 
 PRIMARY KEY (`id`)
 ) ENGINE = InnoDB;

--  Alagoro Julio 2024

-- SELECT lineas.idArticulo AS idarticulo, YEAR(albaranes.Fecha) as year, MONTH(albaranes.Fecha) as mes, IF(SUM(ncant) <> 0, SUM(costeSiva * ncant)/SUM(ncant),0) as costemedio, SUM(ncant) as cantidad FROM `albprolinea` as lineas LEFT OUTER JOIN albprot as albaranes ON (albaranes.id=lineas.idalbpro) GROUP BY idarticulo, year, mes;

-- Crear tabla Acumulados proveedor

--
-- Estructura de tabla para la tabla `acumulado_ventas`
--

DROP TABLE IF EXISTS `acumulado_compras`;
CREATE TABLE `acumulado_compras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` smallint(4) UNSIGNED NOT NULL,
  `month` tinyint(2) UNSIGNED NOT NULL,
  `idArticulo` int(11) NOT NULL,
  `cantidad` decimal(17,6) NOT NULL,  
  `costemedio` double NOT NULL,
  `update_at` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `acumulado_compras` ADD CONSTRAINT `articulo` FOREIGN KEY (`idArticulo`) REFERENCES `articulos`(`idArticulo`) ;

ALTER TABLE `acumulado_compras` ADD UNIQUE `year_month_articulo` (`year`, `month`, `idArticulo`);

DROP TABLE IF EXISTS `diario_cron`;
CREATE TABLE `diario_cron` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` VARCHAR(150) NOT NULL ,
  `ejecucion` TIMESTAMP NOT NULL, 
  `tarea_id` int(11) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `diario_cron` ADD CONSTRAINT `diario_cron_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `tareas_cron` (`id`) ;
