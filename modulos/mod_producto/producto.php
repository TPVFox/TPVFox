<!DOCTYPE html>
<html>
    <head>
        <?php
        include './../../head.php';
        include './funciones.php';
        include ("./../mod_conexion/conexionBaseDatos.php");
        include ("./../../controllers/Controladores.php");
		// Creo objeto de controlador comun.
		$Controler = new ControladorComun; 
		// Añado la conexion
		$Controler->loadDbtpv($BDTpv);
        include ("./clases/ClaseProductos.php");
		// Cargamos los fichero parametros y creamos objeto parametros..
		include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
		$ClasesParametros = new ClaseParametros('parametros.xml');
		$parametros = $ClasesParametros->getRoot();
		// Cargamos configuracion modulo tanto de parametros (por defecto) como si existen en tabla modulo_configuracion 
		$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
		
		
		// Creamos objeto de productos		
		$CTArticulos = new ClaseProductos($BDTpv);
		
		$titulo = 'Productos:';
		$id = 0 ; // Por  defecto el id a buscar es 0
				
		$ivas = $CTArticulos->getTodosIvas(); // Obtenemos todos los ivas.
		$posibles_estados = $CTArticulos->posiblesEstados('articulos');
			
		
		if (isset($_GET['id'])) {
			// Modificar Ficha Producto
			$id=$_GET['id']; // Obtenemos id producto para modificar.
			$titulo .= "Modificar";
		} else {
			// Quiere decir que no hay id, por lo que es nuevo
			$titulo .= "Crear";
		}
		if ($_POST){
			
			$preparados= prepararParaGrabar($_POST,$CTArticulos);
			// Comprobamos los datos antes de grabar.
			if (isset($preparados['Sqls']['NuevoProducto'])){
				// Entonces es que creo uno nuevo.
				$preparado_nuevo = $preparados['Sqls']['NuevoProducto'];
				if (isset($preparado_nuevo['insert_articulos']['id_producto_nuevo'])){
					// Se añadio por lo menos a tabla articulos
					$id = $preparado_nuevo['insert_articulos']['id_producto_nuevo']; // Asi carga datos.
					// Montamos comprobaciones para enviar despues de cargar de nuevo producto.
					$success = array ( 'tipo'=>'success',
								 'mensaje' =>'Se creo el producto con id '.$id.' nuevo',
								 'dato' => $preparado_nuevo['consulta']
								);
					$preparados['Sqls']['comprobaciones'][] = $success;
					// Ahora comprobamos si añadio mas cosas en el articulo nuevo. 
					if (isset($preparado_nuevo['insert_articulos_precios'])){
						if (isset($preparado_nuevo['insert_articulos_precios']['Afectados'])){
							// Entiendo que la consulta fue correcta y que se añadio o no.
							$success = array ( 'tipo'=>'success',
								 'mensaje' =>'Se añadieron precios correctos en '
											.$preparado_nuevo['insert_articulos_precios']['Afectados'].' registros',
								 'dato' => $preparado_nuevo['consulta']
								);
							$preparados['Sqls']['comprobaciones'][] = $success;
						} else {
							// Hubo un error al insertar los precios.
							$preparados['Sqls']['comprobaciones'][] = $preparado_nuevo['insert_articulos_precios'];
						}
						
					}

				} else {
					// Quiere decir que hubo un error al principio
					$preparados['Sqls']['comprobaciones'][] = $preparado_nuevo;
				}
				if (isset($preparado_nuevo['codbarras'])){
					$preparados['Sqls']['codbarras'] = $preparado_nuevo['codbarras'];
				}
			}
		}
		// Obtenemos los datos del id, si es 0, quiere decir que es nuevo.
		$Producto = $CTArticulos->getProducto($id);
		if (isset($preparados['Sqls'])){
			// quiere decir que hizo consultas por lo que tenemos comprobaciones
			if (isset($preparados['Sqls']['comprobaciones'])){
				foreach ($preparados['Sqls']['comprobaciones'] as $comprobacion){
					$CTArticulos->SetComprobaciones($comprobacion);
				}
			}
			if (isset($preparados['Sqls']['codbarras'])){
				foreach ($preparados['Sqls']['codbarras'] as $comprobacion){
					$CTArticulos->SetComprobaciones($comprobacion);
				}
			}
		}
		$Producto['comprobaciones'] = $CTArticulos->GetComprobaciones();
		?>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
		<!-- Creo los objetos de input que hay en tpv.php no en modal.. esas la creo al crear hmtl modal -->
		<?php // -------------- Obtenemos de parametros cajas con sus acciones ---------------  //
			$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
		?>	

		<script type="text/javascript">
		// Objetos cajas de tpv
		<?php echo $VarJS;?>
		<?php 
			echo  'var producto='.json_encode($Producto).';';
		?>
		<?php 
			echo  'var ivas='.json_encode($ivas).';';
		?>
		</script>

		<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>

	</head>
	<body>
		<?php     
        include './../../header.php';
		
		// Ahora montamos html 
		$htmlIvas = htmlOptionIvas($ivas,$Producto['iva']);
		$htmlCodBarras = htmlTablaCodBarras($Producto['codBarras']);
		// Antes de montar html de proveedores añado array de proveedores cual es pricipal
		foreach ($Producto['proveedores_costes'] as $key=>$proveedor){
			if ($proveedor['idProveedor'] === $Producto['proveedor_principal']['idProveedor']){
				// Indicamos que es le principal
				$Producto['proveedores_costes'][$key]['principal'] = 'Si';
			}
		}
		$htmlProveedoresCostes = htmlTablaProveedoresCostes($Producto['proveedores_costes']);
		$htmlFamilias =  htmlTablaFamilias($Producto['familias']);
		$htmlEstados =  htmlOptionEstados($posibles_estados,$Producto['estado']);
		
		
		
		
		?>

     
		<div class="container">
				
			<?php 
			if (isset($Producto['comprobaciones'])){ 
				foreach ($Producto['comprobaciones'] as $comprobaciones){
					echo '<div class="alert alert-'.$comprobaciones['tipo'].'">'.$comprobaciones['mensaje'].'</div>';
				}
				if (isset($Producto['error'])){
				// No permito continuar, ya que hubo error grabe.
				return;
				}
			}
			?>
			<h2 class="text-center"> <?php echo $titulo;?></h2>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
			<div class="col-md-12">
				<div class="col-md-12 btn-toolbar">
					<a class="text-ritght" href="./ListaProductos.php">Volver Atrás</a>
					<input type="submit" value="Guardar">
				</div>
				<div class="col-md-6 Datos">
					<?php // si es nuevo mostramos Nuevo ?>
					<div class="col-md-7">
						<h4>Datos del producto con ID:<?php echo $id?></h4>
					</div>
					<div class="col-md-5">
					<label>Estado
						<select id="idEstado" name="estado" onchange="">
							<?php echo $htmlEstados; ?>
						</select>
					</label>
					<input type="text" id="id" name="id" size="10" style="display:none;" value="<?php echo $id;?>" >
					</div>
					<div class="col-md-12">
						<div class="form-group col-md-3 ">	
							<label class="control-label " > Referencia:</label>
							<input type="text" id="referencia" name="cref_tienda_principal" size="10" placeholder="referencia producto" data-obj= "cajaReferencia" value="<?php echo $Producto['cref_tienda_principal'];?>" onkeydown="controlEventos(event)"  >
						</div>
						<div class="form-group col-md-9 ">	
							<label class="control-label " > Nombre producto:</label>
							<input type="text" id="nombre" name="articulo_name" placeholder="nombre producto" value="<?php echo $Producto['articulo_name'];?>" data-obj= "cajaNombre" onkeydown="controlEventos(event)"   size="50" required>
							 <div class="invalid-tooltip-articulo_name" display="none">
								No permitimos la doble comilla (") 
							</div>
						</div>
					</div>
					<div class="col-md-12">
						<h4> Costes del Producto</h4>
						<div class="form-group col-md-4">
							<?php // Si es nuevo solo se utiliza para calcular precio, no se graba ?>
							<label class="control-label " >
								Coste Ultimo:
								<a onclick="desActivarCoste(event)" >
									<span title="Editamos coste ultimo, para recalcular precio. No cambia en BD !!! vete a proveedores y cambiarlo o al meter un albaran de compra." class="glyphicon glyphicon-cog"></span>
								</a>
							</label>
							<div>
								<input type="text" pattern="[-+]?[0-9]*[.]?[0-9]+" id="coste" size="8" name="ultimoCoste" value="<?php echo number_format($Producto['ultimoCoste'],2, '.', '');?>"  data-obj= "cajaCoste" onkeydown="controlEventos(event)"   readonly> 
								<span class="Euro_grande">€</span> 
							</div>
						</div>
						<div class="form-group col-md-4 ">	
							<label class="control-label " > Iva:</label>
							<select id="idIva" name="idIva" onchange="recalcularPrecioSegunCosteBeneficio();">
								<?php echo $htmlIvas; ?>
							</select>
						</div>
						<div class="form-group col-md-4 ">
							<?php // Si es nuevo no se muestra ?>
							<label class="control-label " >Coste Promedio:</label>
							<div>
								<input type="text" id="costepromedio" size="8" name="costepromedio" placeholder="coste" value="<?php echo number_format($Producto['costepromedio'],2, '.', '');?>"   readonly> 
								<span class="Euro_grande">€</span> 
							</div>
						</div>
					</div>
					<div class="col-md-12">
						<h4> Precios de venta</h4>
						<div class="col-md-4 ">	
								<?php // beneficio solo 2 enteros ?>
								<label class="control-label-inline " > Beneficio:</label>
								<input type="text" id="beneficio" size="5" name="beneficio" placeholder="beneficio" data-obj= "cajaBeneficio" onkeydown="controlEventos(event)" value="<?php echo number_format($Producto['beneficio'],2,'.','');?>"   > %
						</div>
						<div class="col-md-4 ">	
							<label class="control-label " > Precio sin Iva:</label>
							<input type="text" id="pvpSiva" size="10" name="pvpSiva"  data-obj= "cajaPvpSiva" onkeydown="controlEventos(event)" onblur="controlEventos(event)" value="<?php echo number_format($Producto['pvpSiva'],2, '.', '');?>"   >
						</div>
						<div class="col-md-4 ">	
							<label class="control-label " >
								Precio con Iva:
							<a onclick="recalcularPrecioSegunCosteBeneficio()">
							<span title ="Recalcular según beneficio y ultimo coste" class="glyphicon glyphicon-refresh"></span>
							</a>
							</label>
							<input type="text" id="pvpCiva" size="10" name="pvpCiva"  data-obj= "cajaPvpCiva" onkeydown="controlEventos(event)" onblur="controlEventos(event)"  value="<?php echo number_format($Producto['pvpCiva'],2, '.', '');?>"   >
						</div>
					</div>
				</div>
				<div class="col-md-6 text-center">
					 <div class="panel-group">
						<!-- Inicio collapse de CobBarras --> 
						<?php 
						$num = 1 ; // Numero collapse;
						$titulo = 'Códigos de Barras';
						echo htmlPanelDesplegable($num,$titulo,$htmlCodBarras);
						?>
						<!-- Inicio collapse de Proveedores --> 
						<?php 
						$num = 2 ; // Numero collapse;
						$titulo = 'Proveedores - Costes';
						echo htmlPanelDesplegable($num,$titulo,$htmlProveedoresCostes);
						?>
						<!-- Inicio collapse de Familias --> 
						<?php 
						$num = 3; // Numero collapse;
						$titulo = 'Familias';
						echo htmlPanelDesplegable($num,$titulo,$htmlFamilias);
						?>

						<!-- Inicio collapse de Referencias Tiendas --> 

					<!-- Fin de panel-group -->
					</div> 
				<!-- Fin div col-md-6 -->
				</div>
			</div>
			</form>
		<!--fin de div container-->
		<?php // Incluimos paginas modales
		include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
		?>
		</div> 
	</body>
</html>
