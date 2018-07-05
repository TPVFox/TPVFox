<!DOCTYPE html>
<html>
    <head>
        <?php
        include './../../head.php';
        include './funciones.php';
        //~ include ("./../mod_conexion/conexionBaseDatos.php");
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
		
		// Cargamos el plugin que nos interesa.
		if (count($CTArticulos->GetPlugins())>0){
			foreach ($CTArticulos->GetPlugins() as $plugin){
				if ($plugin['datos_generales']['nombre_fichero_clase'] === 'ClaseVehiculos'){
					$ObjVersiones = $plugin['clase'];
				}
			}
		}
		$id = 0 ; // Por  defecto el id a buscar es 0
				
		$ivas = $CTArticulos->getTodosIvas(); // Obtenemos todos los ivas.
     
		$posibles_estados_producto = $CTArticulos->posiblesEstados('articulos');
	
		$titulo = 'Productos:';
		if (isset($_GET['id'])) {
			// Modificar Ficha Producto
			$id=$_GET['id']; // Obtenemos id producto para modificar.
			$titulo .= "Modificar";
            
		} else {
			// Quiere decir que no hay id, por lo que es nuevo
			$titulo .= "Crear";
		}
		if ($_POST){
			include_once ('./tareas/reciboPostProducto.php');
		}
		// Obtenemos los datos del id, si es 0, quiere decir que es nuevo.
		$Producto = $CTArticulos->GetProducto($id);
		
		
				
		if (isset($preparados['comprobaciones'])){
			foreach ($preparados['comprobaciones'] as $comprobacion){
				$CTArticulos->SetComprobaciones($comprobacion);
			}
		}
		if (isset($preparados['codbarras'])){
			foreach ($preparados['codbarras'] as $comprobacion){
				$CTArticulos->SetComprobaciones($comprobacion);
			}
		}
		if (isset($preparados['insert_articulos'])){
			foreach ($preparados['insert_articulos'] as $comprobacion){
				$CTArticulos->SetComprobaciones($comprobacion);
			}
		}
		
		$Producto['comprobaciones'] = $CTArticulos->GetComprobaciones();
		
		// Antes de montar html de proveedores añado array de proveedores cual es pricipal
		foreach ($Producto['proveedores_costes'] as $key=>$proveedor){
			if ($proveedor['idProveedor'] === $Producto['proveedor_principal']['idProveedor']){
				// Indicamos que es le principal
				$Producto['proveedores_costes'][$key]['principal'] = 'Si';
			}
		}
		// ==========		 Comprobamso el ultimo coste y que proveedor		====  ===== //
		$proveedores_costes = comprobarUltimaCompraProveedor($Producto['proveedores_costes']);
		
		// Ahora comprobamos si el coste ultimo es correcto.
		if (number_format($proveedores_costes['coste_ultimo'],2) != number_format($Producto['ultimoCoste'],2)){
			$success = array ( 'tipo'=>'warning',
								 'mensaje' =>'El ultimo coste, se acaba de actualizar, coste_actual: '
								 .$Producto['ultimoCoste']. ' y coste_ultimo real:'.$proveedores_costes['coste_ultimo'],
								 'dato' => array($proveedores_costes['coste_ultimo'],$Producto['ultimoCoste'])
								);
			$Producto['comprobaciones'][] = $success;
			// Ahora cambiamos el coste_ultimo
			$Producto['ultimoCoste'] = $proveedores_costes['coste_ultimo'];			
		}
		// Cargamos el plugin que nos interesa.
		//~ echo '<pre>';
		//~ print_r($Producto);
		//~ echo '</pre>';
		if (count($CTArticulos->GetPlugins())>0){
			foreach ($CTArticulos->GetPlugins() as $plugin){
				if ($plugin['datos_generales']['nombre_fichero_clase'] === 'ClaseVirtuemart'){
					// Ahora obtenemos el idVirtuemart si lo tiene el producto.
					$idVirtuemart= 0;
					if( isset($Producto['ref_tiendas'])){
						foreach ($Producto['ref_tiendas'] as $ref){
							if ($ref['idVirtuemart'] >0){
								$idVirtuemart = $ref['idVirtuemart'];
							}
						}
					}
					$ObjVirtuemart = $plugin['clase'];      
					if ($idVirtuemart>0 ){
                        
						$htmlLinkVirtuemart = $ObjVirtuemart->btnLinkProducto($idVirtuemart);
                        // Monto html de vehiculos.
                        $vehiculos =$ObjVersiones->ObtenerVehiculosUnProducto($idVirtuemart);
                        if (isset($vehiculos['Datos'])) {
                            $htmlVehiculos = $vehiculos['Datos']['html'];
                        }
                        
                        
                        //~ $datosProductoVirtual=$ObjVersiones->ObtenerDatosDeProducto($idVirtuemart);
                        $datosProductoWeb=$ObjVersiones->htmlDatosProductoSeleccionado($idVirtuemart, $ivas);
                        //~ echo '<pre>';
                        //~ print_r($datosProductoVirtual);
                        //~ echo '</pre>';
					}
				}
			}
		}
		
		// ==========		Montamos  html que mostramos. 			============ //
		$htmlIvas = htmlOptionIvas($ivas,$Producto['iva']);
		$htmlCodBarras = htmlTablaCodBarras($Producto['codBarras']);
		$htmlProveedoresCostes = htmlTablaProveedoresCostes($proveedores_costes['proveedores']);
		$htmlFamilias =  htmlTablaFamilias($Producto['familias']);
		$htmlEstadosProducto =  htmlOptionEstados($posibles_estados_producto,$Producto['estado']);
		$htmlReferenciasTiendas = htmlTablaRefTiendas($Producto['ref_tiendas']);
          //~ if(isset($datosProductoVirtual['Datos']['items']['item'])){
            //~ $datosWeb=$datosProductoVirtual['Datos']['items']['item'][0];
            //~ $htmlIvasWeb=htmlOptionIvasWeb($ivas, $datosWeb['iva']);
           
            //~ if($Producto['iva']!=$datosWeb['iva']){
                
                //~ $comprobacionIva=array(
                //~ 'tipo'=>'warning',
                //~ 'mensaje'=>'El iva del producto TPVFox y del producto en la web NO COINCIDEN'
                //~ );
               //~ $Producto['comprobaciones'][]= $comprobacionIva;
            //~ }
          //~ } 
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
							<?php echo $htmlEstadosProducto; ?>
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

                        <div class="col-md-12">
                            <h4> Stock </h4>
                            <div class="col-md-4 ">	
                                <label class="control-label-inline " > Mínimo:</label>
                                <input type="text" id="stockmin" size="5" 
                                       name="stockmin" placeholder="Stock mínimo" 
                                       data-obj= "cajaStockMin" 
                                       readonly="readonly" value="<?php echo number_format($Producto['stocks']['stockMin'], 2, '.', ''); ?>"   > 
                            </div>
                            <div class="col-md-4 ">	
                                <label class="control-label " > Máximo:</label>
                                <input type="text" id="stockmax" size="5" name="stockmax"  
                                       readonly="readonly"
                                       data-obj= "cajaStockMax" 
                                       value="<?php echo number_format($Producto['stocks']['stockMax'], 2, '.', ''); ?>"   >
                            </div>
                            <div class="col-md-4 ">	
                                <label class="control-label " >en almacén:</label>
                                <input type="text" id="stockon" size="5" name="stockon"  
                                       data-obj= "cajaStockOn" 
                                       readonly="readonly" 
                                       value="<?php echo number_format($Producto['stocks']['stockOn'], 2, '.', ''); ?>"   >
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
						<!-- Inicio collapse de Tiendas --> 
						<?php 
						$num = 4; // Numero collapse;
						$titulo = 'Productos en otras tiendas.';
						echo htmlPanelDesplegable($num,$titulo,$htmlReferenciasTiendas);
						
                        if (isset($htmlVehiculos)){
                            $num = 5; // Numero collapse;
                            $titulo = 'Vehiculos que montan este productos.';
                            echo  htmlPanelDesplegable($num,$titulo,$htmlVehiculos);
                        }
                        if (isset($htmlLinkVirtuemart)){
							echo $htmlLinkVirtuemart;
						}
                        
                    	?>
                    
						<!-- Inicio collapse de Referencias Tiendas --> 

					<!-- Fin de panel-group -->
					</div> 
				<!-- Fin div col-md-6 -->
				</div>
			</div>
            </form>
            <?php 
                        if(isset($datosProductoWeb['html'])){
                               echo $datosProductoWeb['html']; 
                        }
                        ?>
			
		<!--fin de div container-->
		<?php // Incluimos paginas modales
		echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
		include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
		?>
        </div> 
    </body>
</html>
