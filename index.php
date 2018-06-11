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
								<a class="text-center" title="Productos" href="modulos/mod_producto/ListaProductos.php"><img width="40px" src="css/img/basket-1520891.svg" alt="Productos" /></a>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Familias</h6>
								<a class="text-center" title="Familias" href="modulos/mod_etiquetado/ListadoEtiquetas.php"><img width="40px" src="css/img/boy-1300621.svg" alt="Familias" /></a>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Lotes</h6>
								<a class="text-center" title="Lotes" href="modulos/mod_etiquetado/ListadoEtiquetas.php"><img width="50px" src="css/img/bar-code-150961.svg" alt="Lotes" /></a>
							</div>
						</div>
						
					</div>
					<div class="col-md-4" >
						<div class="col-md-11" style="margin: 4%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
						<h4 class="text-center">Compras</h4>
						<div class="col-md-4 text-center">
								<h6 class="text-center">Pedidos</h6>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Albaranes</h6>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Facturas</h6>
							</div>
						</div>
						
					</div>
					<div class="col-md-4" >
						<div class="col-md-11" style="margin: 4%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
						<h4 class="text-center">Ventas</h4>
						<div class="col-md-4">
								<h6 class="text-center">Pedidos</h6>
							</div>
							<div class="col-md-4">
								<h6 class="text-center">Albaranes</h6>
							</div>
							<div class="col-md-4">
								<h6 class="text-center">Facturas</h6>
							</div>
						</div>
						
					</div>
				</div>
					<div class="col-md-12 row">
					
					<div class="col-md-4">
						<div class="col-md-11" style="margin: 4%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
						<h4 class="text-center text-center">Tickets</h4>
						<div class="col-md-4">
								<h6 class="text-center">Venta</h6>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Cierres</h6>
								<a class="text-center" title="Tickets" href="modulos/mod_cierres/CierreCaja.php?dedonde=tpv"><img width="50px" src="css/img/castle-378353.svg" alt="Cierres" /></a>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Tikets</h6>
								    <a title="Tickets" href="modulos/mod_tpv/ListaTickets.php?estado=Cobrado"><img width="50px" src="css/img/ticket-150090.svg" alt="Tickets" /></a>
							</div>
						</div>
						
					</div>
					<div class="col-md-4" >
						<div class="col-md-11" style="margin: 4%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
						<h4 class="text-center">Contactos</h4>
						<div class="col-md-4 text-center">
								<h6 class="text-center">Usuarios</h6>
								<a class="text-center" title="Usuarios" href="modulos/mod_usuario/ListaUsuarios.php"><img width="40px" src="css/img/avatar-1299805.svg" alt="Usuarios" /></a>
							</div>
							<div class="col-md-4 text-center">
								<h6 class="text-center">Clientes</h6>
									<a class="text-center" title="Clientes" href="modulos/mod_cliente/ListaClientes.php"><img width="40px" src="css/img/user-1633249.svg" alt="Clientes" /></a>
							</div>
							<div class="col-md-4 text-center ">
								<h6 class="text-center">Proveedores</h6>
								<a class="text-center" title="Proveedores" href="modulos/mod_proveedor/ListaProveedores.php"><img width="60px" src="css/img/van-19185551.ai.svg" alt="Proveedores" /></a>
							</div>
						</div>
						
					</div>
					<div class="col-md-4" >
						<div class="col-md-11" style="margin: 4%;padding:3%;border-radius:10px;background-color:#f3f3f6;">
						<h4 class="text-center">Sistema</h4>
						<div class="col-md-4 text-center ">
								<h6 class="text-center">Tiendas</h6>
									<a class="text-center" title="Tienda" href="modulos/mod_tienda/ListaTiendas.php"><img width="40px" src="css/img/home-146585.svg" alt="Proveedores" /></a>
							</div>
							<div class="col-md-4 text-center ">
								<h6 class="text-center">Incidendias</h6>
							</div>
							<div class="col-md-4 text-center ">
								<h6 class="text-center">Tablas Principales</h6>
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
