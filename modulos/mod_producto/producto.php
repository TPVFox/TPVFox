<!DOCTYPE html>
<html>
    <head>
        <?php
		// Reinicio variables
        include './../../head.php';
        include './funciones.php';
        include ("./../mod_conexion/conexionBaseDatos.php");
		
		
		?>
		<!-- Cargamos libreria control de teclado -->
		<script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
		
	</head>
	<body>
		<?php
        include './../../header.php';
		// ===========  datos producto segun id enviado por url============= //
		
		$tabla= 'articulos'; // Tablas que voy utilizar.
		$estadoInput = 'disabled'; //desactivado input de entrada 
		
		//idTienda, para ver producto de esa tienda.
		$idTienda = $_SESSION['tiendaTpv']['idTienda'];
		$contNuevoCodBarras=0; //nuevo contador codigo barras
		//	print_r($_GET);
		//	print_r($_SESSION['tiendaTpv']);
			
		if (isset($_GET['id'])) {
			// Modificar Ficha Producto
			$id=$_GET['id']; // Obtenemos id producto para modificar.
			$idArticulo = $id;
			$Producto = verSelec($BDTpv,$id,$tabla,$idTienda);	
			$refTiendas = referenciasTiendas($BDTpv,$id);
			$codigosBarras = codigosBarras($BDTpv,$id);
			$familias = nombreFamilias($BDTpv,$idArticulo);
			$titulo = "Modificar Producto";
			
			$tablas = array ('articulos' => array(
								'idProveedor'	=> $Producto['idProveedor'],
								'nombre' 		=> $Producto['articulo_name'],	
								'beneficio' 	=>  $Producto['beneficio'],
								'costepromedio' => $Producto['costepromedio'],
								'iva'			=> $Producto['iva'] // tengo que obtener array IVAS varios y uno SELECCIONADO
								),
							'artPrecios' => array (
								'pvpCiva'		=> $Producto['pvpCiva'],
								'pvpSiva'		=> $Producto['pvpSiva']
								),
							'artFamilias' => array(
									'idFamilia'		=> $familias['idFamilia'],
									'nombreFamilia' => $familias['familiaNombre']
								),
							'artCodBarras'	=> array(
									'codigo' => $codigosBarras['codigos'][0]['codBarras']
								)
							//falta proveedor nombres e idproveedor
							);
			
			
		
			$nombreproveedor = $Producto['razonsocial'];
			
		
			
			echo '<pre>';
			print_r($tablas);
			echo '</pre>';
			
			
			
			if (isset($Producto['error'])){
				$error='NOCONTINUAR';
				$tipomensaje= "danger";
				$mensaje = "Id de producto incorrecto ( ver get) <br/>".$Producto['consulta'];
			} else {
				// Cambiamos atributo de login para que no pueda modificarlo.
				$AtributoLogin='readonly';
				// Ahora ponemos el estado por defecto segun el dato obtenido en la BD .
				if (count($_POST) ===0){
					
				
				} 
			}
		} else {
			// Creamos ficha Producto.
			$titulo = "Crear Producto";
			
		}
		
		
		
		?>
     
		<div class="container">
				
			<?php 
			
			if (isset($mensaje) || isset($error)){   ?> 
				<div class="alert alert-<?php echo $tipomensaje; ?>"><?php echo $mensaje ;?></div>
				<?php 
				if (isset($error)){
				// No permito continuar, ya que hubo error grabe.
				return;
				}
				?>
			<?php
			}
			?>
			<h2 class="text-center"> <?php echo $titulo;?></h2>
			<a class="text-ritght" href="./ListaProductos.php">Volver Atrás</a>
			
			<div class="col-md-12">
				<div class="col-md-12">
					<div class="Datos">
						<h3>Datos generales:</h3>
						<div class="col-md-2 ">	
							<label class="control-label " > Id:</label>
							<input type="text" id="idProducto" name="idProducto" <?php echo $estadoInput;?> placeholder="id producto" value="<?php echo $idArticulo;?>"   >
						</div>
						<div class="col-md-4 ">	
							<label class="control-label " > Nombre producto:</label>
							<input type="text" id="nombre" name="nombre" placeholder="nombre producto" value="<?php echo $Producto['articulo_name'];?>"   >
						</div>
					
						<div class="col-md-2 ">	
							<label class="control-label " > Id proveedor:</label>
							<input type="text" id="idproveedor" name="idproveedor" <?php echo $estadoInput;?> placeholder="idproveedor" value="<?php echo $Producto['idProveedor'];?>"   >
						</div>
						<div class="col-md-3 ">	
							<label class="control-label " > Nombre proveedor:</label>
							<input type="text" id="nombreproveedor" name="nombreproveedor" <?php echo $estadoInput;?> placeholder="nombreproveedor" value="<?php echo $nombreproveedor;?>"   >
						</div>
						
						<div class="col-md-2 ">	
							<label class="control-label " > Coste:</label>
							<input type="text" id="coste" name="coste" placeholder="coste" value="<?php echo round($Producto['costepromedio'],2).'€';?>"   >
						</div>
						<div class="col-md-2 ">	
							<label class="control-label " > Beneficio:</label>
							<input type="text" id="beneficio" name="beneficio" placeholder="beneficio" value="<?php echo $Producto['beneficio'].'%';?>"   >
						</div>
						<div class="col-md-2 ">	
							<label class="control-label " > Iva:</label>
							<input type="text" id="iva" name="iva" placeholder="iva" value="<?php echo  $Producto['iva'].'%';?>"   >
						</div>
					</div>
					
				</div>
				<div class="col-md-6"> <!--precios-->
					<h3>Precios:</h3>
					<div class="col-sm-6 ">	
						<label class="control-label " > Precio con Iva:</label>
						<input type="text" id="pvpCiva" name="pvpCiva"   value="<?php echo round($Producto['pvpCiva'],2).'€';?>"   >
					</div>
					<div class="col-sm-6 ">	
						<label class="control-label " > Precio con Iva:</label>
						<input type="text" id="pvpSiva" name="pvpSiva"  value="<?php echo round($Producto['pvpSiva'],2).'€';?>"   >
					</div>
							
				</div>
				<div class="col-md-6"> <!--Familias-->
					<h3>Familias:</h3>
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
			</div> <!-- div 12-->
			<div class="col-md-10"> <!--Tiendas--><!-- referencias por tiendas-->
					<div class="col-md-8 ">
						<h3>Referencias en las distintas tiendas:</h3>
					<table class="table table-striped">
						<thead>
							<tr>
								<th>referencia</th>
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
							if ($refeTienda['tipoTienda'] === 'web'){
								$icono = '<span class="glyphicon glyphicon-globe"></span>';
							}
						?>
						<tr>
							<td><?php echo $refeTienda['cref']; ?></td>
							<td><?php echo $refeTienda['idVirtu']; ?></td>
							<td><?php echo $refeTienda['idTienda']; ?></td>
							<td><?php echo $refeTienda['nombreTienda']; echo ' '.$icono; ?></td>
							<td><?php echo $refeTienda['pvpCiva']; ?></td>
							<td><?php echo $refeTienda['estado']; ?></td>
						</tr>
						<?php
						}
						?>
					</table>
					</div> <!-- div contiene tabla-->
					
					<div class="col-md-3 text-center">
						<h3>Codigos de Barras:</h3>
						<?php 
						$contNuevo = $contNuevoCodBarras+1;
						?>
					<table id="tcodigo" class="table table-striped">
						<thead>
							<tr>
								<th>Codigos Barras</th> 
								<th><a id="agregar" class="glyphicon glyphicon-plus" onclick="agregoCodBarrasVacio(<?php echo $contNuevo; ?>)"></a></th>								
							</tr>
							
						</thead>
						
						<?php 
						
						//si  no hay codigoBarras no hay nada que recorrer
						if ($codigosBarras['codigos']===''){
							$codBarras='No hay codigos'; ?>
							<tr>
								<td><input type="text" id="codBarras" name="codBarras" <?php echo $estadoInput;?> value="<?php echo $codBarras;?>"   ></td>
							</tr>
						<?php	
						} else {
							$contExiste=0;
			
							foreach ($codigosBarras['codigos'] as $key =>$codigo){ 
							
							?>
							<tr id="Existe<?php echo $contExiste+1;?>">
								<td><input type="text" id="codBarras" name="codBarras" <?php echo $estadoInput;?> value="<?php echo $codigo['codBarras'];?>"   ></td>
								<td><span class="glyphicon glyphicon-trash"></span></td>
							</tr>
							<?php
							$contExiste++;
							}
						}
						?>
						
					</table>
					
				</div>
				
		</div> <!-- container-->
	</body>
</html>
