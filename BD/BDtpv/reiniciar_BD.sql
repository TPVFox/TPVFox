# Reiniciar datos ventas
TRUNCATE pedclit;
TRUNCATE pedcliIva;
TRUNCATE pedclilinea;
TRUNCATE pedcliltemporales;
TRUNCATE pedcliAlb;
TRUNCATE albclit;
TRUNCATE albcliIva;
TRUNCATE albclilinea;
TRUNCATE albcliltemporales;
TRUNCATE albclifac;
TRUNCATE facclit;
TRUNCATE faccliIva;
TRUNCATE facclilinea;
TRUNCATE faccliltemporales;
TRUNCATE fac_cobros;

# Reiniciar datoscompras
TRUNCATE pedprot;
TRUNCATE pedproIva;
TRUNCATE pedprolinea;
TRUNCATE pedprotemporales;
TRUNCATE pedproAlb;
TRUNCATE albprot;
TRUNCATE albproIva;
TRUNCATE albprolinea;
TRUNCATE albproltemporales;
TRUNCATE albprofac;
TRUNCATE facprot;
TRUNCATE facproIva;
TRUNCATE facprolinea;
TRUNCATE facproltemporales;
TRUNCATE facProCobros;

# Reiniciar tickets
TRUNCATE ticketst;
TRUNCATE ticketslinea;
TRUNCATE ticketstIva;
TRUNCATE ticketstemporales;


# Reiniciar Familias
TRUNCATE familias;
TRUNCATE familiasTienda;

# Reiniciar articulos
TRUNCATE articulos;
TRUNCATE articulosClientes;
TRUNCATE articulosCodigoBarras;
TRUNCATE articulosFamilias;
TRUNCATE articulosPrecios;
TRUNCATE articulosProveedores;
TRUNCATE articulosStocks;
TRUNCATE articulosTiendas;
TRUNCATE historico_precios;
TRUNCATE stocksRegularizacion;

# Virtuemart
# Estas realmente no debería estar aquí...
TRUNCATE importar_virtuemart_tickets;
# Reiniciar modulo incidencias
TRUNCATE modulo_incidencia;

# Reiniciar modulo etiquetado
TRUNCATE modulo_etiquetado;
TRUNCATE modulo_etiquetado_temporal;

# Reinicia modulo balanza
TRUNCATE modulo_balanza;
TRUNCATE modulo_balanza_plus;

# Reinicio de cierres
TRUNCATE cierres;
TRUNCATE cierres_ivas;
TRUNCATE cierres_usuariosFormasPago;
TRUNCATE cierres_usuarios_tickets;

# Reinicio proveedores
TRUNCATE proveedores;

# Reinicio clientes;
TRUNCATE descuentos_tickets;
TRUNCATE clientes;
INSERT INTO `clientes`(`idClientes`, `Nombre`, `razonsocial`,estado,fecha_creado) VALUES (1,'Sin identificar','Sin identificar','activo',NOW());

# Reinicio de permisos, configuracion .
TRUNCATE permisos;
TRUNCATE modulos_configuracion;

# Reinicio de indices.
TRUNCATE indices; 
INSERT INTO `indices`(`id`, `idTienda`, `idUsuario`, `numticket`, `tempticket`) VALUES (1,1,1,1,1);


# Reinicio de usuarios admin y password admin
TRUNCATE usuarios;
INSERT INTO usuarios (id,username,password,fecha,group_id,estado,nombre) VALUES (1,'admin','21232f297a57a5a743894a0e4a801fc3',NOW(),9,'activo','admin');

# Reinicio de tiendas
TRUNCATE tiendas;
INSERT INTO tiendas (idTienda,tipoTienda,razonsocial,nif,telefono,estado,NombreComercial,direccion,servidor_email,ano) VALUES (
        1,
        'principal',
        'Soluciones informaticas Vigo SL',
        'B999666999',
        '886112370',
        'activo',
        'Soluciones Vigo',
        'Emilia pardo Bazan 52- bajo',
        'tuservidor.emailtuyo.com',
        2022);





