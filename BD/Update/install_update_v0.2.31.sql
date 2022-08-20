ALTER TABLE `albprolinea` CHANGE `ref_prov` `ref_prov` VARCHAR(24) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL; 
ALTER TABLE `pedprolinea` CHANGE `ref_prov` `ref_prov` VARCHAR(24) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `facprolinea` CHANGE `ref_prov` `ref_prov` VARCHAR(24) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; 
