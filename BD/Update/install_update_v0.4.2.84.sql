ALTER TABLE dispositivos
MODIFY estado ENUM('activo','inactivo','automatico') DEFAULT 'activo',
ADD COLUMN media DECIMAL(5,2) NULL AFTER estado,
ADD COLUMN sd DECIMAL(5,2) NULL AFTER media,
ADD COLUMN temp_min DECIMAL(5,2) NULL AFTER sd,
ADD COLUMN temp_max DECIMAL(5,2) NULL AFTER temp_min;
