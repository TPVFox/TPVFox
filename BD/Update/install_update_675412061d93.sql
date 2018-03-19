-- Actualizacion tablas 
-- Commit : 675412061d93f55bbd5d48fc926b9e4cc5ed95ae
--	modified:   BD/BDtpv/tablas/albprolinea.sql
--	modified:   BD/BDtpv/tablas/facprolinea.sql
--	modified:   BD/BDtpv/tablas/pedprolinea.sql

ALTER TABLE `albprolinea` CHANGE `costeSiva` `costeSiva` DECIMAL(17,4) NULL DEFAULT NULL; 
ALTER TABLE `facprolinea` CHANGE `costeSiva` `costeSiva` DECIMAL(17,4) NULL DEFAULT NULL; 
ALTER TABLE `pedprolinea` CHANGE `costeSiva` `costeSiva` DECIMAL(17,4) NOT NULL; 

