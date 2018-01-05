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
			$ivas=ivasNoPrincipal($BDTpv, $Producto['iva']);
			$titulo = "Modificar Producto";
			
			foreach ($refTiendas['ref'] as $key =>$refeTienda){ 
							if ($refeTienda['tipoTienda'] === 'principal'){
								$referencia=$refeTienda['cref'];
							}else{
								$referencia=0;
							}
							}
			
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
			
			if (isset($Producto['error'])){
				$error='NOCONTINUAR';
				$tipomensaje= "danger";
				$mensaje = "Id de producto incorrecto ( ver get) <br/>".$Producto['consulta'];
			} 
		} else {
			// Creamos ficha Producto.
			$titulo = "Crear Producto";
			$bandera=1;
			$Producto['articulo_name']="";
			$Producto['costePromedio']=0;
			$Producto['beneficio']=0;
			$Producto['iva']=21;
			$Producto['pvpCiva']=0;
			$Producto['pvpSiva']=0;
			$Producto['idProveedor']=0;
			
			
		}
		
		if ($_POST){
			$datos=$_POST;
			$datos['idTienda']=$idTienda;
			//Si el producto no esta creado
			if ($bandera == 1){
			$datos['estado']="Activo";
			$datos['idTienda']=$idTienda;
			//Se añade un producto y nos retorna al listado de productos
				$res=añadirProducto($BDTpv, $datos, $tabla);
				header('Location:ListaProductos.php');
			//~ echo '<pre>';
			//~ print_r($res);
			//~ echo '</pre>';
			}else{
				//De lo contrario se modifica y nos retorna a las especificaciones del producto con un mensaje
			$res=modificarProducto($BDTpv, $datos, $tabla);
			
			if (isset($resp['error'])){
						$tipomensaje= "danger";
						$mensaje = "Razon social de producto ya existe!";
					} else {
						$tipomensaje= "info";
						$mensaje = "Su registro de producto fue editado.";
						$i=$datos['idProducto'];
						
					}
					//~ echo '<pre>';
			//~ print_r($res);
			//~ echo '</pre>';
					header('Location: producto.php?id='.$i.'&tipo='.$tipomensaje.'&mensaje='.$mensaje);
					
			
		}
		}
		
		
		
		?>
     
		<div class="container">
				
			<?php 
			if (isset($_GET)){
			$mensaje=$_GET['mensaje'];
			$tipomensaje=$_GET['tipo'];
		}
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
				<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
			<div class="col-md-12">
				<div class="col-md-12">
					<div class="Datos">
					<input type="submit" value="Guardar">
						<h3>Datos generales:</h3>
						<div class="col-md-2">	
							<label class="control-label " > Id:</label>
							<input type="text" id="idProducto" name="idProducto"  size="10"  placeholder="id producto" value="<?php echo $idArticulo;?>" readonly>
						</div>
							<div class="col-md-2 ">	
							<label class="control-label " > Referencia:</label>
							<input type="text" id="referencia" name="referencia" size="10" placeholder="referencia producto" value="<?php echo $referencia;?>"   >
						</div>
						<div class="col-md-2 ">	
							<label class="control-label " > Nombre producto:</label>
							<input type="text" id="nombre" name="nombre" placeholder="nombre producto" value="<?php echo $Producto['articulo_name'];?>"   >
						</div>
					<div class="col-md-2 ">	
							<label class="control-label " > Promedio:</label>
							<input type="text" id="coste" size="10" name="coste" placeholder="coste" value="<?php echo number_format($Producto['costepromedio'],2, '.', '');?>"   readonly> € 
						</div>
					<div class="col-md-2 ">	
							<label class="control-label " > Beneficio:</label>
							<input type="text" id="beneficio" size="10" name="beneficio" placeholder="beneficio" value="<?php echo number_format($Producto['beneficio'],2,'.','');?>"   > %
						</div>
						<div class="col-md-2 ">	
							<label class="control-label " > Iva:</label>
							<select id="iva" name="iva" onchange="modifPrecioCiva();">
								<option value=<?php echo  $Producto['iva'];?>><?php echo  $Producto['iva'].'%';?></option>
								<?php 
								//foreach que recorre los tipos de ivas que no son el principal
								foreach ($ivas as $iva){
									echo '<option value='.$iva['iva'].'>'.$iva['iva'].'%'.'</option>';
								}
								?>
								</select>
						</div>
						
					</div>
				
				</div>
					<div class="col-md-12">
						<h3>Proveedores:</h3>
						<div class="col-md-2 ">	
							<label class="control-label " > Id proveedor:</label>
							<input type="text" id="idproveedor" name="idproveedor" <?php echo $estadoInput;?> placeholder="idproveedor" value="<?php echo $Producto['idProveedor'];?>"   >
						</div>
						<div class="col-md-2 ">	
							<label class="control-label " > Nombre proveedor:</label>
							<input type="text" id="nombreproveedor" name="nombreproveedor" <?php echo $estadoInput;?> placeholder="nombreproveedor" value="<?php echo $nombreproveedor;?>"   >
						</div>
<!--
						<div class="col-md-2 ">	
							<label class="control-label " > Referencia:</label>
							<input type="text" id="referencia" name="referencia"  placeholder="referencia" value="0"   >
						</div>
						<div class="col-md-2 ">	
							<label class="control-label " > Fecha actualización:</label>
							<input type="text" id="fechaAc" name="fechaAc"  placeholder="fecha actuañización" value="0"   >
						</div>
-->
						</div>
				<div class="col-md-6"> <!--precios-->
					<h3>Precios:</h3>
					<div class="col-sm-6 ">	
						<label class="control-label " > Precio con Iva:</label>
						<input type="text" id="pvpCiva" name="pvpCiva"  onchange="modifPrecioSiva();" value="<?php echo number_format($Producto['pvpCiva'],2, '.', '');?>"   >
					</div>
					<div class="col-sm-6 ">	
						<label class="control-label " > Precio sin Iva:</label>
						<input type="text" id="pvpSiva" name="pvpSiva"  onchange="modifPrecioCiva();" value="<?php echo number_format($Producto['pvpSiva'],2, '.', '');?>"   >
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
<!--
								<th>referencia</th>
-->
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
<!--
							<td><?php /*echo $refeTienda['cref'];*/ ?></td>
-->
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
							<!--<th><a id="agregar" class="glyphicon glyphicon-plus" onclick="agregoCodBarrasVacio(<?php /*echo $contNuevo; */?>); comprobarVacio(<?php /*echo $contNuevo; */?>)"></a></th>	-->							
						<th><a id="agregar" class="glyphicon glyphicon-plus" onclick="comprobarVacio(<?php echo $contNuevo; ?>)"></a></th>								
					
							</tr>
							
						</thead>
						
						<?php 
						
						//si  no hay codigoBarras no hay nada que recorrer
						if ($codigosBarras['codigos']===''){
							/*$codBarras='No hay codigos';*/
							$codBarras='';
							?>
							<tr>
								<td><input type="text" id="codBarras" name="codBarras_0"  value="<?php echo $codBarras;?>"   ></td>
							<td><a id="eliminar" class="glyphicon glyphicon-trash" onclick="eliminarCodBarras(this)"></a></td>

							</tr>
						<?php	
						} else {
							$contExiste=0;
			
							foreach ($codigosBarras['codigos'] as $key =>$codigo){ 
							
							?>
							<tr id="Existe<?php echo $contExiste+1;?>">
								<td><input type="text" id="codBarras" name="codBarras_<?php echo $contExiste+1;?>"  value="<?php echo $codigo['codBarras'];?>"   ></td>
								<!-- <td><span class="glyphicon glyphicon-trash"></span></td>-->
								<td><a id="eliminar" class="glyphicon glyphicon-trash" onclick="eliminarCodBarras(this)"></a></td>
							</tr>
							<?php
							$contExiste++;
							}
						}
						?>
						
					</table>
					
				</div>
				
				</form>
				
		</div> <!-- container-->
	</body>
</html>
