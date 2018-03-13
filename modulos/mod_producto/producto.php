<!DOCTYPE html>
<html>
    <head>
        <?php
        include './../../head.php';
        include './funciones.php';
        include ("./../mod_conexion/conexionBaseDatos.php");
        include ("./../../clases/ClaseTablaArticulos.php");
		$CTArticulos = new ClaseTablaArticulos($BDTpv);
		?>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
	</head>
	<body>
		<?php     
        include './../../header.php';
		$titulo = 'Productos:';
		$id = 0 ; // Por  defecto el id a buscar es 0
		$tabla= 'articulos'; // Tablas que voy utilizar.
		$estadoInput = 'disabled'; //desactivado input de entrada 
		
		$idTienda = $_SESSION['tiendaTpv']['idTienda']; // Necesito este dato, para obtener datos de esta tienda.
		$ivas = $CTArticulos->getTodosIvas(); // Obtenemos todos los ivas.
		
		if (isset($_GET['id'])) {
			// Modificar Ficha Producto
			$id=$_GET['id']; // Obtenemos id producto para modificar.
			$titulo .= "Modificar";
		} else {
			// Quiere decir que no hay id, por lo que es nuevo
			$titulo .= "Crear";

		}
		// Obtenemos los datos del id, si es 0, quiere decir que es nuevo.
		$Producto = $CTArticulos->getProducto($_GET['id']);
		// Ahora montamos html 
		$htmlIvas = htmlOptionIvas($ivas,$Producto['iva']);
		$htmlCodBarras = htmlTablaCodBarras($Producto['codBarras']);
		
		
		//~ echo '<pre>';
		//~ print_r($htmlCodBarras);
		//~ echo '</pre>';
			
			
		
		if ($_POST){
			// Comprobamos los datos antes de grabar.
			// header('Location: producto.php?id='.$i.'&tipo='.$tipomensaje.'&mensaje='.$mensaje);
		}

		
		?>
     
		<div class="container">
				
			<?php 
			if (isset($Producto['comprobaciones'])){   ?> 
				<?php 
				foreach ($Producto['comprobaciones'] as $comprobaciones){?>
					<div class="alert alert-<?php echo $comprobaciones['tipo']; ?>"><?php echo $comprobaciones['mensaje'] ;?></div>
					<?php 
				}
				if (isset($Producto['error'])){
				// No permito continuar, ya que hubo error grabe.
				return;
				}
				?>
			<?php
			}
			?>
			<h2 class="text-center"> <?php echo $titulo;?></h2>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
			<div class="col-md-12">
				<div class="col-md-12 btn-toolbar">
					<a class="text-ritght" href="./ListaProductos.php">Volver Atrás</a>
					<input type="submit" value="Guardar">
				</div>
				<div class="col-md-7 Datos">
					<?php // si es nuevo mostramos Nuevo ?>
					<h4>Datos del producto con ID:<?php echo $id?></h4>
					<input type="text" id="id" name="id" size="10" style="display:none;" value="<?php echo $id?>"   >
					<div class="col-md-12">
						<div class="form-group col-md-3 ">	
							<label class="control-label " > Referencia:</label>
							<input type="text" id="referencia" name="referencia" size="10" placeholder="referencia producto" value="<?php echo $Producto['cref_tienda_principal'];?>"   >
						</div>
						<div class="form-group col-md-9 ">	
							<label class="control-label " > Nombre producto:</label>
							<input type="text" id="nombre" name="nombre" placeholder="nombre producto" value="<?php echo $Producto['articulo_name'];?>"    size="50" >
						</div>
					</div>
					<div class="col-md-12">
						<h4> Costes del Producto</h4>
						<div class="form-group col-md-4">
							<?php // Si es nuevo solo se utiliza para calcular precio, no se graba ?>
							<label class="control-label " > Coste Ultimo:</label>
							<div>
								<input type="text" id="coste" size="8" name="costeultimo" value="<?php echo number_format($Producto['ultimoCoste'],2, '.', '');?>"   readonly> 
								<span class="Euro_grande">€</span> 
							</div>
						</div>
						<div class="form-group col-md-4 ">	
							<label class="control-label " > Iva:</label>
							<select id="iva" name="iva" onchange="modifPrecioCiva();">
								<?php echo $htmlIvas; ?>
							</select>
						</div>
						<div class="form-group col-md-4 ">
							<?php // Si es nuevo no se muestra ?>
							<label class="control-label " >Coste Promedio:</label>
							<div>
								<input type="text" id="coste" size="8" name="coste" placeholder="coste" value="<?php echo number_format($Producto['costepromedio'],2, '.', '');?>"   readonly> 
								<span class="Euro_grande">€</span> 
							</div>
						</div>
					</div>
					<div class="col-md-12">
						<h4> Precios de venta</h4>
						<div class="col-md-4 ">	
								<?php // beneficio solo 2 enteros ?>
								<label class="control-label-inline " > Beneficio:</label>
								<input type="text" id="beneficio" size="5" name="beneficio" placeholder="beneficio" value="<?php echo number_format($Producto['beneficio'],2,'.','');?>"   > %
						</div>
						<div class="col-md-4 ">	
							<label class="control-label " > Precio sin Iva:</label>
							<input type="text" id="pvpSiva" name="pvpSiva"  onchange="modifPrecioCiva();" value="<?php echo number_format($Producto['pvpSiva'],2, '.', '');?>"   >
						</div>
						<div class="col-md-4 ">	
							<label class="control-label " > Precio con Iva:</label>
							<input type="text" id="pvpCiva" name="pvpCiva"  onchange="modifPrecioSiva();" value="<?php echo number_format($Producto['pvpCiva'],2, '.', '');?>"   >
						</div>
					</div>
				</div>
				<div class="col-md-5 text-center">
					 <div class="panel-group">
						<?php 
						$num = 1 ; // Numero collapse;
						$titulo = 'Códigos de Barras';
						echo htmlPanelDesplegable($num,$titulo,$htmlCodBarras);
						
						?>
						<!-- Inicio collapse de Familias --> 
						<div class="panel panel-default">
							<div class="panel-heading">
							  <h4 class="panel-title">
								<a data-toggle="collapse" href="#collapse2">Familias</a>
							  </h4>
							</div>
							<div id="collapse2" class="panel-collapse collapse">
								<div class="panel-body">
									<ol class="breadcrumb">
									<?php 
										$i=0;
										foreach ($familias['familias'] as $familia){
										?>
										  <li><a><?php echo $familia['nombreFam'];?></a></li>
										<?php
											$i++;
										}
									?>
									</ol>
								</div>
							</div>
						</div>
						<!-- Inicio collapse de Proveedores --> 
						<div class="panel panel-default">
							<div class="panel-heading">
							  <h4 class="panel-title">
								<a data-toggle="collapse" href="#collapse3">Proveedores</a>
							  </h4>
							</div>
							<div id="collapse3" class="panel-collapse collapse">
								<div class="panel-body">
									<div class="col-md-2 ">	
										<label class="control-label " > Id proveedor:</label>
										<input type="text" id="idproveedor" name="idproveedor" <?php echo $estadoInput;?> placeholder="idproveedor" value="<?php echo $Producto['idProveedor'];?>"   >
									</div>
									<div class="col-md-2 ">	
										<label class="control-label " > Nombre proveedor:</label>
										<input type="text" id="nombreproveedor" name="nombreproveedor" <?php echo $estadoInput;?> placeholder="nombreproveedor" value="<?php echo $nombreproveedor;?>"   >
									</div>
								</div>
							</div>
						</div>
						<!-- Inicio collapse de Referencias Tiendas --> 
						<div class="panel panel-default">
							<div class="panel-heading">
							  <h4 class="panel-title">
								<a data-toggle="collapse" href="#collapse4">Referencias en otras tiendas</a>
							  </h4>
							</div>
							<div id="collapse4" class="panel-collapse collapse">
								<div class="panel-body">
									<table class="table table-striped">
										<thead>
											<tr>
												<th>id virtuemart</th>
												<th>id tienda</th>
												<th>nombre tienda</th>
												<th>precio con iva</th>
												<th>estado</th>
											</tr>
										</thead>
										<?php 
									$icono ='';
									foreach ($refTiendas['ref'] as $key =>$refeTienda){ 
											/*Si el tipo de tienda es web entonces se añade un icono con un enlace al producto de virtalmark*/
											if ($refeTienda['tipoTienda'] === 'web'){
												$icono = '<a href=http://'.$refeTienda['dominio'].'/index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$refeTienda['idVirtu'].'><span class="glyphicon glyphicon-globe"></span></a>';
											}
										if ($refeTienda['tipoTienda'] <> 'principal'){
										?>
										<tr>
											<td><?php echo $refeTienda['idVirtu'];?></td>
											<td><?php echo $refeTienda['idTienda']; ?></td>
											<td><?php echo $refeTienda['nombreTienda']; echo ' '.$icono; ?></td>
											<td><?php echo $refeTienda['pvpCiva']; ?></td>
											<td><?php echo $refeTienda['estado']; ?></td>
										</tr>
										<?php
										}
									}
									?>
									</table>
								</div>
							</div>
						</div>
					<!-- Fin de panel-group -->
					</div> 
				<!-- Fin div col-md-5 -->
				</div>
			</div>
		<!--fin de div container-->
		</div> 
	</body>
</html>
