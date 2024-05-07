-- ------ ESTE FICHERO ES RECOMENDABLE ANTES EJECUTARLO DEBEMOS TENER UNA COPIA DE SEGURIDAD BASES DATOS   -------
/* Este fichero es creado para ejecutar ante del fichero restricciones.sql que es la creacion relaciones entre las tablas
   Recomendables hacer los select primero, para ver que tablas pueden darte problemas y intentar resolverlas.
 */
 

-- VALORES QUE DEBEMOS CAMBIAR SI FUERA NECESARIO
-- ## Cliente donde los albaranes que tiene idCliente 0 
SET @var_idcliente = 4; 

-- Se consulta todas las facturas que el idcliente este roto y el valor sea 0
SET @consulta = (SELECT id FROM `facclit` WHERE idCliente NOT IN (SELECT idClientes FROM clientes) AND Total=0);
-- Consultas automaticas.
UPDATE `albclit` SET `idCliente`=@var_idcliente WHERE idCliente= 0; 
DELETE FROM `articulosProveedores` WHERE (idArticulo NOT IN (SELECT idArticulo FROM articulos));
DELETE FROM `articulosStocks` WHERE idArticulo NOT IN (SELECT idArticulo FROM articulos);
DELETE FROM `articulosTiendas` WHERE idArticulo NOT IN (SELECT idArticulo FROM articulos);
DELETE FROM `facclit` WHERE idCliente NOT IN (SELECT idClientes FROM clientes) AND `total`=0;
DELETE FROM `historico_precios` WHERE (idArticulo NOT IN (SELECT idArticulo FROM articulos)) AND `NumDoc` = 0 ;
ALTER TABLE `stocksRegularizacion` CHANGE `idAlbaran` `idAlbaran` INT(11) NULL DEFAULT NULL; 
UPDATE `stocksRegularizacion` SET `idAlbaran`=null WHERE `idAlbaran`= 0;
-- Error en facturas rotas
DELETE FROM `facclilinea` WHERE `idfaccli` IN (@consulta);
DELETE FROM `faccliIva` WHERE `idfaccli` IN (@consulta); 
DELETE FROM  `albclifac` WHERE idFactura IN (@consulta);
DELETE FROM `facclit` WHERE idCliente NOT IN (SELECT idClientes FROM clientes) AND Total=0;
-- Fin error de facturas rotas
DELETE FROM `historico_precios` WHERE (idArticulo NOT IN (SELECT idArticulo FROM articulos)) AND `NumDoc` = 0 ; 
DELETE FROM `albproIva` WHERE idalbpro NOT IN (SELECT id FROM albprot); 
DELETE FROM `albprolinea` WHERE idalbpro NOT IN (SELECT id FROM albprot); 
DELETE FROM fac_cobros WHERE idFactura NOT IN (SELECT id FROM facclit); 


-- Buscamos facturas  clientes rotas
-- Consultamos si hay facturas con clientes que no existen y tiene valor.
SELECT * FROM `facclit` WHERE idCliente NOT IN (SELECT idClientes FROM clientes) AND total<>0;
-- Consultamos si hay lineas de facturas y lineas ivas con un idCliente que no existe.
SET @consulta=(SELECT id FROM `facclit` WHERE idCliente NOT IN (SELECT idClientes FROM clientes) AND Total<>0);
SELECT * FROM `facclilinea` WHERE `idfaccli` IN (@consulta);
SELECT * FROM  `albclifac` WHERE idFactura IN (@consulta);
SELECT * FROM `faccliIva` WHERE `idfaccli` IN (@consulta); 




-- EXISTE HISTORICO_PRECIOS CON ALBARAN PERO QUE EXISTE ALBARAN
-- CONSULTA
-- EXISTE HISTORICO_PRECIOS CON ALBARAN PERO QUE EXISTE ALBARAN
SELECT * FROM `historico_precios` WHERE (idArticulo NOT IN (SELECT idArticulo FROM articulos)) AND `NumDoc` <> 0 ; 
-- EXISTE HISTORICO_PRECIOS CON idUsuario que no existe
SELECT * FROM `historico_precios` where idUsuario NOT IN (SELECT id FROM usuarios);

-- DELETE
DELETE FROM `historico_precios` WHERE (idArticulo NOT IN (SELECT idArticulo FROM articulos)) AND `NumDoc` <> 0 ; 


-- ERROR EN pedcliltemporales- EXISTEN pedidos con Numpedcli que no existe, normalmente 0.
-- CONSULTA
SELECT * FROM `pedcliltemporales` where Numpedcli NOT IN (SELECT Numpedcli FROM pedclit);


-- ERROR EN sctockRegularizacion - EXISTEN registros con idArticulo que no existe en tabla articulos.
-- CONSULTA
SELECT * FROM `stocksRegularizacion` WHERE `idArticulo` NOT IN (SELECT idArticulo FROM articulos); 


-- ERROR EN TICKET- EXISTEN LINEAS Y NO CABECERA
-- CONSULTA
SELECT * FROM `ticketslinea` WHERE `idArticulo` NOT IN (SELECT idArticulo FROM articulos); 
SELECT * FROM `ticketslinea` WHERE `idticketst` NOT IN (SELECT id FROM ticketst); 
SELECT * FROM `ticketstIva` WHERE `idticketst` NOT IN (SELECT id FROM ticketst); 
-- DELETE
DELETE FROM `ticketslinea` WHERE `idticketst` NOT IN (SELECT id FROM ticketst); 
DELETE FROM `ticketstIva` WHERE `idticketst` NOT IN (SELECT id FROM ticketst); 









-- AÑO ANTERIRORES COMO SE BORRA LA TIENDA WEB HAY QUE ELIMINAR RELACIONES.
-- CONSULTA
SELECT * FROM `articulosTiendas` WHERE `idTienda`=2; 
SELECT * FROM `familiasTienda` WHERE `idTienda`=2

-- ELIMINAR
DELETE FROM `articulosTiendas` WHERE `idTienda`=2; 
DELETE FROM `familiasTienda` WHERE `idTienda`=2;



