--  Alagoro Mayo 2024
-- Crear tabla tareas Cron
DROP TABLE IF EXISTS tareas_cron;
CREATE TABLE tareas_cron (
 id INT(11) NOT NULL AUTO_INCREMENT ,
 `nombre` VARCHAR(50) NOT NULL ,
 `periodo` INT(11) NOT NULL , 
 `nombre_clase` VARCHAR(50) NOT NULL ,
 `ultima_ejecucion` DATE NULL, 
 `estado` INT(2) NOT NULL DEFAULT 1, 
 PRIMARY KEY (`id`)
 ) ENGINE = InnoDB;