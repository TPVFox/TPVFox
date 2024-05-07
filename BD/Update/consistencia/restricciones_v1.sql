--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `albclifac`
--
ALTER TABLE `albclifac`
  ADD CONSTRAINT `albclifac_idalbaran_foreign` FOREIGN KEY (`idAlbaran`) REFERENCES `albclit` (`id`),
  ADD CONSTRAINT `albclifac_idfactura_foreign` FOREIGN KEY (`idFactura`) REFERENCES `facclit` (`id`);

--
-- Filtros para la tabla `albcliIva`
--
ALTER TABLE `albcliIva`
  ADD CONSTRAINT `albcliiva_idalbcli_foreign` FOREIGN KEY (`idalbcli`) REFERENCES `albclit` (`id`);

--
-- Filtros para la tabla `albclilinea`
--
ALTER TABLE `albclilinea`
  ADD CONSTRAINT `albclilinea_ibfk_1` FOREIGN KEY (`idalbcli`) REFERENCES `albclit` (`id`),
  ADD CONSTRAINT `albclilinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`);

--
-- Filtros para la tabla `albclit`
--
ALTER TABLE `albclit`
  ADD CONSTRAINT `albclit_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  ADD CONSTRAINT `albclit_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `albclit_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `albprofac`
--
ALTER TABLE `albprofac`
  ADD CONSTRAINT `albprofac_idalbaran_foreign` FOREIGN KEY (`idAlbaran`) REFERENCES `albprot` (`id`),
  ADD CONSTRAINT `albprofac_idfactura_foreign` FOREIGN KEY (`idFactura`) REFERENCES `facprot` (`id`);

--
-- Filtros para la tabla `albproIva`
--
ALTER TABLE `albproIva`
  ADD CONSTRAINT `albproiva_idalbpro_foreign` FOREIGN KEY (`idalbpro`) REFERENCES `albprot` (`id`);

--
-- Filtros para la tabla `albprolinea`
--
ALTER TABLE `albprolinea`
  ADD CONSTRAINT `albprolinea_ibfk_1` FOREIGN KEY (`idalbpro`) REFERENCES `albprot` (`id`),
  ADD CONSTRAINT `albprolinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`);

--
-- Filtros para la tabla `articulosCodigoBarras`
--
ALTER TABLE `articulosCodigoBarras`
  ADD CONSTRAINT `articulosCodigoBarras_ibfk_1` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`);

--
-- Filtros para la tabla `articulosFamilias`
--
ALTER TABLE `articulosFamilias`
  ADD CONSTRAINT `articulosFamilias_ibfk_1` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`);

--
-- Filtros para la tabla `articulosPrecios`
--
ALTER TABLE `articulosPrecios`
  ADD CONSTRAINT `articulosPrecios_ibfk_1` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`);

--
-- Filtros para la tabla `articulosProveedores`
--
ALTER TABLE `articulosProveedores`
  ADD CONSTRAINT `articulosproveedores_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `articulosproveedores_idproveedor_foreign` FOREIGN KEY (`idProveedor`) REFERENCES `proveedores` (`idProveedor`);

--
-- Filtros para la tabla `articulosStocks`
--
ALTER TABLE `articulosStocks`
  ADD CONSTRAINT `articulosstocks_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `articulosstocks_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`);

--
-- Filtros para la tabla `articulosTiendas`
--
ALTER TABLE `articulosTiendas`
  ADD CONSTRAINT `articulostiendas_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `articulostiendas_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`);

--
-- Filtros para la tabla `cierres`
--
ALTER TABLE `cierres`
  ADD CONSTRAINT `cierres_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `cierres_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `cierres_ivas`
--
ALTER TABLE `cierres_ivas`
  ADD CONSTRAINT `cierres_ivas_idcierre_foreign` FOREIGN KEY (`idCierre`) REFERENCES `cierres` (`idCierre`),
  ADD CONSTRAINT `cierres_ivas_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`);

--
-- Filtros para la tabla `cierres_usuariosFormasPago`
--
ALTER TABLE `cierres_usuariosFormasPago`
  ADD CONSTRAINT `cierres_usuariosformaspago_idcierre_foreign` FOREIGN KEY (`idCierre`) REFERENCES `cierres` (`idCierre`),
  ADD CONSTRAINT `cierres_usuariosformaspago_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `cierres_usuariosformaspago_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `cierres_usuarios_tickets`
--
ALTER TABLE `cierres_usuarios_tickets`
  ADD CONSTRAINT `cierres_usuarios_tickets_idcierre_foreign` FOREIGN KEY (`idCierre`) REFERENCES `cierres` (`idCierre`),
  ADD CONSTRAINT `cierres_usuarios_tickets_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `cierres_usuarios_tickets_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `descuentos_tickets`
--
ALTER TABLE `descuentos_tickets`
  ADD CONSTRAINT `descuentos_tickets_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  ADD CONSTRAINT `descuentos_tickets_idticket_foreign` FOREIGN KEY (`idTicket`) REFERENCES `ticketst` (`id`),
  ADD CONSTRAINT `descuentos_tickets_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `faccliIva`
--
ALTER TABLE `faccliIva`
  ADD CONSTRAINT `faccliiva_idfaccli_foreign` FOREIGN KEY (`idfaccli`) REFERENCES `facclit` (`id`);

--
-- Filtros para la tabla `facclilinea`
--
ALTER TABLE `facclilinea`
  ADD CONSTRAINT `facclilinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `facclilinea_idfaccli_foreign` FOREIGN KEY (`idfaccli`) REFERENCES `facclit` (`id`);

--
-- Filtros para la tabla `facclit`
--
ALTER TABLE `facclit`
  ADD CONSTRAINT `facclit_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  ADD CONSTRAINT `facclit_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `facclit_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `facProCobros`
--
ALTER TABLE `facProCobros`
  ADD CONSTRAINT `facprocobros_idfactura_foreign` FOREIGN KEY (`idFactura`) REFERENCES `facprot` (`id`),
  ADD CONSTRAINT `facprocobros_idformaspago_foreign` FOREIGN KEY (`idFormasPago`) REFERENCES `formasPago` (`id`);

--
-- Filtros para la tabla `facproIva`
--
ALTER TABLE `facproIva`
  ADD CONSTRAINT `facproiva_idfacpro_foreign` FOREIGN KEY (`idfacpro`) REFERENCES `facprot` (`id`);

--
-- Filtros para la tabla `facprolinea`
--
ALTER TABLE `facprolinea`
  ADD CONSTRAINT `facprolinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `facprolinea_idfacpro_foreign` FOREIGN KEY (`idfacpro`) REFERENCES `facprot` (`id`);  -- Falla el programa.

--
-- Filtros para la tabla `facprot`
--
ALTER TABLE `facprot`
  ADD CONSTRAINT `facprot_idproveedor_foreign` FOREIGN KEY (`idProveedor`) REFERENCES `proveedores` (`idProveedor`),
  ADD CONSTRAINT `facprot_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `facprot_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `fac_cobros`
--
ALTER TABLE `fac_cobros`
  ADD CONSTRAINT `fac_cobros_idfactura_foreign` FOREIGN KEY (`idFactura`) REFERENCES `facclit` (`id`),
  ADD CONSTRAINT `fac_cobros_idformaspago_foreign` FOREIGN KEY (`idFormasPago`) REFERENCES `formasPago` (`id`);

--
-- Filtros para la tabla `familiasTienda`
--
ALTER TABLE `familiasTienda`
  ADD CONSTRAINT `familiastienda_idfamilia_foreign` FOREIGN KEY (`idFamilia`) REFERENCES `familias` (`idFamilia`),
  ADD CONSTRAINT `familiastienda_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`);

--
-- Filtros para la tabla `historico_precios`
--
ALTER TABLE `historico_precios`
  ADD CONSTRAINT `historico_precios_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `historico_precios_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pedcliAlb`
--
ALTER TABLE `pedcliAlb`
  ADD CONSTRAINT `pedclialb_idalbaran_foreign` FOREIGN KEY (`idAlbaran`) REFERENCES `albclit` (`id`),
  ADD CONSTRAINT `pedclialb_idpedido_foreign` FOREIGN KEY (`idPedido`) REFERENCES `pedclit` (`id`);

--
-- Filtros para la tabla `pedcliIva`
--
ALTER TABLE `pedcliIva`
  ADD CONSTRAINT `pedcliiva_idpedcli_foreign` FOREIGN KEY (`idpedcli`) REFERENCES `pedclit` (`id`);

--
-- Filtros para la tabla `pedclilinea`
--
ALTER TABLE `pedclilinea`
  ADD CONSTRAINT `pedclilinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `pedclilinea_idpedcli_foreign` FOREIGN KEY (`idpedcli`) REFERENCES `pedclit` (`id`);

--
-- Filtros para la tabla `pedcliltemporales`
--
ALTER TABLE `pedcliltemporales`
  ADD CONSTRAINT `pedcliltemporales_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  ADD CONSTRAINT `pedcliltemporales_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `pedcliltemporales_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `pedcliltemporales_numpedcli_foreign` FOREIGN KEY (`Numpedcli`) REFERENCES `pedclit` (`id`);

--
-- Filtros para la tabla `pedclit`
--
ALTER TABLE `pedclit`
  ADD CONSTRAINT `pedclit_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  ADD CONSTRAINT `pedclit_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `pedclit_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pedproAlb`
--
ALTER TABLE `pedproAlb`
  ADD CONSTRAINT `pedproalb_idalbaran_foreign` FOREIGN KEY (`idAlbaran`) REFERENCES `albprot` (`id`),
  ADD CONSTRAINT `pedproalb_idpedido_foreign` FOREIGN KEY (`idPedido`) REFERENCES `pedprot` (`id`);

--
-- Filtros para la tabla `pedproIva`
--
ALTER TABLE `pedproIva`
  ADD CONSTRAINT `pedproiva_idpedpro_foreign` FOREIGN KEY (`idpedpro`) REFERENCES `pedprot` (`id`);

--
-- Filtros para la tabla `pedprolinea`
--
ALTER TABLE `pedprolinea`
  ADD CONSTRAINT `pedprolinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `pedprolinea_idpedpro_foreign` FOREIGN KEY (`idpedpro`) REFERENCES `pedprot` (`id`);

--
-- Filtros para la tabla `pedprot`
--
ALTER TABLE `pedprot`
  ADD CONSTRAINT `pedprot_idproveedor_foreign` FOREIGN KEY (`idProveedor`) REFERENCES `proveedores` (`idProveedor`),
  ADD CONSTRAINT `pedprot_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `pedprot_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pedprotemporales`
--
ALTER TABLE `pedprotemporales`
  ADD CONSTRAINT `pedprotemporales_idpedpro_foreign` FOREIGN KEY (`idPedpro`) REFERENCES `pedprot` (`id`),
  ADD CONSTRAINT `pedprotemporales_idproveedor_foreign` FOREIGN KEY (`idProveedor`) REFERENCES `proveedores` (`idProveedor`),
  ADD CONSTRAINT `pedprotemporales_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `pedprotemporales_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `stocksRegularizacion`
--
ALTER TABLE `stocksRegularizacion`
  ADD CONSTRAINT `stocksregularizacion_idalbaran_foreign` FOREIGN KEY (`idAlbaran`) REFERENCES `albclit` (`id`),
  ADD CONSTRAINT `stocksregularizacion_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `stocksregularizacion_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `stocksregularizacion_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `ticketslinea`
--
ALTER TABLE `ticketslinea`
  ADD CONSTRAINT `ticketslinea_idarticulo_foreign` FOREIGN KEY (`idArticulo`) REFERENCES `articulos` (`idArticulo`),
  ADD CONSTRAINT `ticketslinea_idticketst_foreign` FOREIGN KEY (`idticketst`) REFERENCES `ticketst` (`id`);

--
-- Filtros para la tabla `ticketst`
--
ALTER TABLE `ticketst`
  ADD CONSTRAINT `ticketst_idcliente_foreign` FOREIGN KEY (`idCliente`) REFERENCES `clientes` (`idClientes`),
  ADD CONSTRAINT `ticketst_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `ticketst_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `ticketstemporales`
--
ALTER TABLE `ticketstemporales`
  ADD CONSTRAINT `ticketstemporales_idclientes_foreign` FOREIGN KEY (`idClientes`) REFERENCES `clientes` (`idClientes`),
  ADD CONSTRAINT `ticketstemporales_idtienda_foreign` FOREIGN KEY (`idTienda`) REFERENCES `tiendas` (`idTienda`),
  ADD CONSTRAINT `ticketstemporales_idusuario_foreign` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `ticketstIva`
--
ALTER TABLE `ticketstIva`
  ADD CONSTRAINT `ticketstiva_idticketst_foreign` FOREIGN KEY (`idticketst`) REFERENCES `ticketst` (`id`);
