# Aportación de Guillermo que asi vuela.... :-)
alter table articulosPrecios add primary key (idArticulo, idTienda);
ALTER TABLE `articulosTiendas` CHANGE `crefTienda` `crefTienda` VARCHAR(18) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
