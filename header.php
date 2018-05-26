<?php
    /*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvOlalla de Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero ,
 * @Descripcion	Header de items de menu de TPV
 * */
	// Debug puedes necesitar también variable $TPVsession
	//~ echo '<pre>';
	//~ print_r($TPVsession);
	//~ echo '</pre>';
	// Ponemos valor a variables control o reiniciamos.

	
	// NOTA:
	// Aquellos los links que quieres limitar el acceso , debemos poner un controlador.
	
?>

<header>
<!-- Debería generar un fichero de php que se cargue automaticamente el menu -->
	<nav class="navbar navbar-default">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
				  <span class="sr-only">Desplegar navegación</span>
				  <span class="icon-bar"></span>
				  <span class="icon-bar"></span>
				  <span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="#">TpvFox</a>
			</div>
			<div class="collapse navbar-collapse navbar-ex1-collapse">
				<ul class="nav navbar-nav navbar-left ">
					<li><a href="<?php echo $HostNombre.'/index.php'?>">Home</a></li>
                                        <li class="dropdown">
                                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">Almacén
							<span class="caret"></span></a>
                                                        <ul class="dropdown-menu">
                                                <li><a href="<?php echo $HostNombre.'/modulos/mod_producto/ListaProductos.php';?>">Productos</a></li>
                                                <li><a href="<?php echo $HostNombre.'/modulos/mod_familia/ListaFamilias.php';?>">Familias</a></li>
                                                <li><a href="<?php echo $HostNombre.'/modulos/mod_producto/ListaFamilias.php';?>">Familias (producto)</a></li>
                                            </ul></li>
					<li><a href="<?php echo $HostNombre.'/modulos/mod_cliente/ListaClientes.php';?>">Clientes</a></li>
					<li><a href="<?php echo $HostNombre.'/modulos/mod_proveedor/ListaProveedores.php';?>">Proveedores</a></li>
					<li><a href="<?php echo $HostNombre.'/modulos/mod_cierres/ListaCierres.php';?>">Cierres</a></li>
					<li><a href="<?php echo $HostNombre.'/estatico';?>">Documentacion</a></li>
				<li class="dropdown">
							<a class="dropdown-toggle" data-toggle="dropdown" href="#">Compras
							<span class="caret"></span></a>
							<ul class="dropdown-menu">
								
								<li><a href="<?php echo $HostNombre.'/modulos/mod_compras/pedidosListado.php';?>">Pedidos</a></li>
								<li><a href="<?php echo $HostNombre.'/modulos/mod_compras/albaranesListado.php';?>">Albaranes</a></li>
								<li><a href="<?php echo $HostNombre.'/modulos/mod_compras/facturasListado.php';?>">Facturas</a></li>
							</ul>
						</li>
							<li class="dropdown">
							<a class="dropdown-toggle" data-toggle="dropdown" href="#">Ventas
							<span class="caret"></span></a>
							<ul class="dropdown-menu">
								<li><a href="<?php echo $HostNombre.'/modulos/mod_tpv/tpv.php';?>">Tickets</a></li>
								<li><a href="<?php echo $HostNombre.'/modulos/mod_venta/pedidosListado.php';?>">Pedidos</a></li>
								<li><a href="<?php echo $HostNombre.'/modulos/mod_venta/albaranesListado.php';?>">Albaranes</a></li>
								<li><a href="<?php echo $HostNombre.'/modulos/mod_venta/facturasListado.php';?>">Facturas</a></li>
							</ul>
						</li>
					<?php 
					if ($Usuario['group_id'] > '1'){?>
						<?php //coloco dropdown importar, al pinchar tengo 2 opc en lista?>
						<li class="dropdown">
							<a class="dropdown-toggle" data-toggle="dropdown" href="#">Sistema
							<span class="caret"></span></a>
							<ul class="dropdown-menu">
								<li><a href="<?php echo $HostNombre.'/modulos/mod_importar_sppg/Importar_sppg.php';?>">Importar SPPG</a></li>
								<li><a href="<?php echo $HostNombre.'/modulos/mod_importar_virtuemart/Importar_virtuemart.php';?>">Importar Virtuemart</a></li>
								<li><a href="<?php echo $HostNombre.'/modulos/mod_usuario/ListaUsuarios.php';?>">Usuarios</a></li>
								<li><a href="<?php echo $HostNombre.'/modulos/mod_tienda/ListaTiendas.php';?>">Tiendas</a></li>
								<li><a href="<?php echo $HostNombre.'/modulos/mod_copia_seguridad/CopiaSeguridad.php';?>">Copia Seguridad</a></li>
								<li><a href="<?php echo $HostNombre.'/modulos/mod_incidencias/ListadoIncidencias.php';?>">Incidencias</a></li>
								<li><a href="<?php echo $HostNombre.'/modulos/mod_etiquetado/ListadoEtiquetas.php';?>">Etiquetado</a></li>

							</ul>
						</li>
						
					<?php 
					};?>
				</ul>
				
				<div class="nav navbar-nav navbar-right">
					
					<span class="glyphicon glyphicon-user"></span><?php echo $Usuario['login'];?>
					<?php
					if ($_SESSION['estadoTpv'] == "Correcto"){
					?>

					<a href="<?php echo $HostNombre.'/plugins/controlUser/modalUsuario.php?tipo=cerrar';?>">Cerrar</a>
					<?php
			}
				
				?>
				</div>
				<div class="nav navbar-nav navbar-right" style="margin-right:50px">
					<div id="tienda"><?php echo $Tienda['razonsocial'];?></div>
					
				</div>
			</div>
			
		</div>
	</nav>
<!-- Fin de menu -->
</header>

<?php 
// Mostramos formulario si no tiene acceso.
	// Bloqueamos si 	
	if ($_SESSION['estadoTpv'] != "Correcto"){
		// Mostramos modal de usuario.
		include_once ($URLCom."/plugins/controlUser/modalUsuario.php");
		?>
		</body>
		</html>
		<?php
		exit;	
	}
	?>
