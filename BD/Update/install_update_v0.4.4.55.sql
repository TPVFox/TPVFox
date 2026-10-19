-- Tabla de temporales del módulo de relaciones entre productos: lo que se prepara en la
-- pantalla de relación y todavía no rige. Una fila por relación; las nuevas no tienen número.
-- Repetible: se puede aplicar más de una vez y no toca las filas que ya tenga. No inserta datos.
-- No depende de las otras tres tablas del módulo: la versión de partida y el tipo se comprueban
-- al activar.

CREATE TABLE IF NOT EXISTS `relacionesTemporales` (
  `id`                int(11)      NOT NULL AUTO_INCREMENT,
  `numRelacion`       int(11)      DEFAULT NULL,
  `idRelacion`        int(11)      DEFAULT NULL,
  `idTipo`            int(11)      NOT NULL,
  `nombre`            varchar(100) DEFAULT NULL,
  `descripcion`       text         DEFAULT NULL,
  `diasViabilidad`    smallint(6)  DEFAULT NULL,
  `origenAjuste`      varchar(255) DEFAULT NULL,
  `lineas`            mediumtext   NOT NULL,
  `idUsuario`         int(11)      NOT NULL,
  `fechaInicio`       datetime     NOT NULL,
  `fechaModificacion` datetime     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numrelacion` (`numRelacion`),
  KEY `relacionestemporales_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `relacionestemporales_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
