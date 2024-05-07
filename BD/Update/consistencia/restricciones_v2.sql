
--RESTRINCCIONES QUE HAY QUE HACER CAMBIOS EN  EL CODIGO
-- Filtros para la tabla `facclilinea`
--
ALTER TABLE `facclilinea`
  ADD CONSTRAINT `facclilinea_numalbcli_foreign` FOREIGN KEY (`NumalbCli`) REFERENCES `albclit` (`id`);


--
-- Filtros para la tabla `facprolinea`
--
ALTER TABLE `facprolinea`
  ADD CONSTRAINT `facprolinea_idalbpro_foreign` FOREIGN KEY (`idalbpro`) REFERENCES `albprot` (`id`),


