<?php
	/*
	 *  @Aplicacion    TpvFox (Soluciones Vigo)
	 *  @copyright  Copyright (C) 2017 - 2018  Soluciones Vigo.
	 *  @license    GNU General Public License version 2 or later; see LICENSE.txt
	 * */
?>

<!DOCTYPE html>
<html>
<head>
<?php
	include 'head.php';?>
</head>
<body>
	<?php 
	include 'header.php';
	?>
	
	<section>
		<div class="container">
			<div class="col-md-8">
				<h1>TPVfox</h1>
			</div>
			<div class="col-md-12 row">
				<h2 class="text-center">Accesos Directos</h2>
				<div class="col-md-12 row">
					
					<div class="col-md-4">
						<div class="col-md-11" style="margin: 4%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
						<h4 class="text-center">Almacén</h4>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Productos</h6>
								<a class="text-center" title="Productos" href="modulos/mod_producto/ListaProductos.php"><img  src="css/img/productosP.png" alt="Productos" /></a>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Familias</h6>
								<a class="text-center" title="Familias" href="modulos/mod_familia/ListaFamilias.php"><img  src="css/img/familiasP.png" alt="Familias" /></a>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Lotes</h6>
								<a class="text-center" title="Lotes" href="modulos/mod_etiquetado/ListadoEtiquetas.php"><img  src="css/img/lotesP.png" alt="Lotes" /></a>
							</div>
						</div>
						
					</div>
					<div class="col-md-4" >
						<div class="col-md-11" style="margin: 4%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
						<h4 class="text-center">Compras</h4>
						<div class="col-md-4 text-center">
								<h6 class="text-center">Pedidos</h6>
								<a class="text-center" title="Pedidos" href="modulos/mod_compras/pedidosListado.php"><img  src="css/img/pedidosComP.png" alt="Pedidos" /></a>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Albaranes</h6>
								<a class="text-center" title="Albaran" href="modulos/mod_compras/albaranesListado.php"><img src="css/img/albaranComP.png" alt="Albaran" /></a>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Facturas</h6>
								<a class="text-center" title="Factura" href="modulos/mod_compras/facturasListado.php"><img  src="css/img/facturasComP.png" alt="Factura" /></a>
							</div>
						</div>
						
					</div>
					<div class="col-md-4" >
						<div class="col-md-11" style="margin: 4%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
						<h4 class="text-center">Ventas</h4>
						<div class="col-md-4 text-center">
								<h6 class="text-center">Pedidos</h6>
								<a class="text-center" title="Pedidos" href="modulos/mod_venta/pedidosListado.php"><img  src="css/img/PedidosVenP.png" alt="Pedidos" /></a>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Albaranes</h6>
								<a class="text-center" title="Albaranes" href="modulos/mod_venta/albaranesListado.php"><img  src="css/img/albaranVenP.png" alt="Albaranes" /></a>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Facturas</h6>
								<a class="text-center" title="Factura" href="modulos/mod_venta/facturasListado.php"><img  src="css/img/facturaVenP.png" alt="Factura" /></a>
							</div>
						</div>
						
					</div>
				</div>
					<div class="col-md-12 row">
					
					<div class="col-md-4">
						<div class="col-md-11" style="margin: 4%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
						<h4 class="text-center text-center">Tickets</h4>
						<div class="col-md-4 text-center">
								<h6 class="text-center">Caja</h6>
								<a class="text-center" title="Caja" href="modulos/mod_tpv/tpv.php"><img  src="css/img/cajaP.png" alt="Caja" /></a>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Cierres</h6>
								<a class="text-center" title="Cierres" href="modulos/mod_cierres/CierreCaja.php?dedonde=tpv"><img  src="css/img/cierresP.png" alt="Cierres" /></a>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Tikets</h6>
								    <a title="Tickets" href="modulos/mod_tpv/ListaTickets.php?estado=Cobrado"><img  src="css/img/ticketsP.png" alt="Tickets" /></a>
							</div>
						</div>
						
					</div>
					<div class="col-md-4" >
						<div class="col-md-11" style="margin: 4%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
						<h4 class="text-center">Contactos</h4>
						<div class="col-md-4 text-center">
								<h6 class="text-center">Usuarios</h6>
								<a class="text-center" title="Usuarios" href="modulos/mod_usuario/ListaUsuarios.php"><img  src="css/img/usuariosP.png" alt="Usuarios" /></a>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Clientes</h6>
									<a class="text-center" title="Clientes" href="modulos/mod_cliente/ListaClientes.php"><img  src="css/img/clientesP.png" alt="Clientes" /></a>
							</div>
							<div class="col-md-4 text-center ">
								<h6 class="text-center">Proveedores</h6>
								<a class="text-center" title="Proveedores" href="modulos/mod_proveedor/ListaProveedores.php"><img  src="css/img/proveedoresP.png" alt="Proveedores" /></a>
							</div>
						</div>
						
					</div>
					<div class="col-md-4" >
						<div class="col-md-11" style="margin: 4%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
						<h4 class="text-center">Sistema</h4>
						<div class="col-md-4 text-center ">
								<h6 class="text-center">Tiendas</h6>
									<a class="text-center" title="Tienda" href="modulos/mod_tienda/ListaTiendas.php"><img src="css/img/tiendaP.png" alt="Tiendas" /></a>
							</div>
							<div class="col-md-4 text-center ">
								<h6 class="text-center">Incidendias</h6>
								<a class="text-center" title="Incidencias" href="modulos/mod_incidencias/ListadoIncidencias.php"><img src="css/img/incidenciasP.png" alt="Incidencias" /></a>
							</div>
							<div class="col-md-4 text-center ">
								<h6 class="text-center">Tablas</h6>
								<a class="text-center" title="Tablas" href="modulos/mod_configuracion/tablasPrincipales.php"><img  src="css/img/tablasP.png" alt="Tablas" /></a>
							</div>
						</div>
						
					</div>
				</div>
				</div>
				<div class="col-md-12">
				</div>
			</div>
			<div id="col-md-12">
				<p>Está aplicación es OPEN SOURCE, con ello queremos decir que puedes utilizar este código en otras aplicaciones y modificarlo sin problemas.</p>
			</div>
			
		</div>
	</div>
	</section>
</body>
</html>
