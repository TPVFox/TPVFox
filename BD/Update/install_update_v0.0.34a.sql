SET foreign_key_checks=0;

CREATE TABLE migraciones (
  version bigint(20) NOT NULL,
  migration_name varchar(100) NULL DEFAULT NULL,
  start_time timestamp NULL DEFAULT NULL,
  end_time timestamp NULL DEFAULT NULL,
  breakpoint tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

SET foreign_key_checks=1;

ALTER TABLE familias ADD COLUMN beneficiomedio decimal(5, 2) NULL DEFAULT NULL;
