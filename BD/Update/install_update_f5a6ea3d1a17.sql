ALTER TABLE `tiendas`
  DROP `nom_bases_datos`,
  DROP `nom_usuario_base_datos`,
  DROP `prefijoBD`;

ALTER TABLE `tiendas` ADD `key_api` VARCHAR(30) NOT NULL AFTER `dominio`;

