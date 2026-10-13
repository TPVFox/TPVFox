-- Tablas del módulo de relaciones entre productos.
-- Repetible: se puede aplicar más de una vez. No inserta datos: los tipos de relación los
-- crea el administrador de cada tienda desde las tablas principales.

CREATE TABLE IF NOT EXISTS `relacionesTipos` (
  `id`            int(11)     NOT NULL AUTO_INCREMENT,
  `nombre`        varchar(50) NOT NULL,
  `forma`         enum('1:N','N:1','1:1') NOT NULL,
  `llevaCantidad` tinyint(1)  NOT NULL DEFAULT 1,
  `estado`        enum('Activo','Baja') NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `relaciones` (
  `id`            int(11)      NOT NULL AUTO_INCREMENT,
  `numRelacion`    int(11)      NOT NULL,
  `idTipo`        int(11)      NOT NULL,
  `nombre`        varchar(100) DEFAULT NULL,
  `descripcion`   text         DEFAULT NULL,
  `diasViabilidad` smallint(6) DEFAULT NULL,
  `version`       varchar(11)  NOT NULL,
  `tipoCambio`    enum('alta','composicion','proporciones','datos','baja') DEFAULT NULL,
  `origenAjuste`  varchar(255) DEFAULT NULL,
  `idUsuario`     int(11)      NOT NULL,
  `fechaCreacion` datetime     NOT NULL,
  `estado`        enum('Borrador','Activo','Sustituido','Baja') NOT NULL DEFAULT 'Borrador',
  PRIMARY KEY (`id`),
  UNIQUE KEY `numrelacion_version` (`numRelacion`, `version`),
  KEY `relaciones_idtipo_foreign` (`idTipo`),
  KEY `relaciones_idusuario_foreign` (`idUsuario`),
  CONSTRAINT `relaciones_idtipo_foreign` FOREIGN KEY (`idTipo`) REFERENCES `relacionesTipos` (`id`),
  CONSTRAINT `relaciones_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `relacionesLineas` (
  `id`         int(11)       NOT NULL AUTO_INCREMENT,
  `idRelacion`  int(11)       NOT NULL,
  `idArticulo` int(11)       NOT NULL,
  `sentido`    enum('entrada','salida') NOT NULL,
  `cantidad`   decimal(17,6) DEFAULT NULL,
  `unidad`     enum('kg','ud') DEFAULT NULL,
  `estado`     enum('Activo','Eliminado') NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idrelacion_idarticulo` (`idRelacion`, `idArticulo`),
  KEY `relacioneslineas_idarticulo_foreign` (`idArticulo`),
  CONSTRAINT `relacioneslineas_idrelacion_foreign` FOREIGN KEY (`idRelacion`) REFERENCES `relaciones` (`id`),
  CONSTRAINT `relacioneslineas_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
