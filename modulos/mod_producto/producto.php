<?php
include_once './../../inicial.php';
include_once $URLCom.'/modulos/mod_producto/funciones.php';
include_once $URLCom.'/controllers/Controladores.php';
include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
$OtrosVarJS ='';
// Creo objeto de controlador comun.
$Controler = new ControladorComun; 
// Añado la conexion
$Controler->loadDbtpv($BDTpv);
// Cargamos los fichero parametros y creamos objeto parametros..
include_once ($URLCom.'/controllers/parametros.php');
$ClasesParametros = new ClaseParametros('parametros.xml');
$parametros = $ClasesParametros->getRoot();
// Cargamos configuracion modulo tanto de parametros (por defecto) como si existen en tabla modulo_configuracion 
$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
// Creamos objeto de productos		
$CTArticulos = new ClaseProductos($BDTpv);

$id = 0 ; // Por  defecto el id a buscar es 0

$ivas = $CTArticulos->getTodosIvas(); // Obtenemos todos los ivas.

$posibles_estados_producto = $CTArticulos->posiblesEstados('articulos');
$Link_volver = $Controler->getHtmlLinkVolver('Volver ');
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


$Producto['comprobaciones'] = $CTArticulos->GetComprobaciones();

// Antes de montar html de proveedores añado array de proveedores cual es principal
if ( !isset($Producto['proveedores_costes'])) {
    // No existe costes..
    $Producto['proveedores_costes']= array();
} else {
    if ( gettype($Producto['proveedor_principal']) == 'array') {
        foreach ($Producto['proveedores_costes'] as $key=>$proveedor){
            if ($proveedor['idProveedor'] === $Producto['proveedor_principal']['idProveedor']){
                // Indicamos que es le principal
                $Producto['proveedores_costes'][$key]['principal'] = 'Si';
            }
        }
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
$idVirtuemart = 0;
if( isset($Producto['ref_tiendas'])){
    // Esto no es del todo correcto... ?
    foreach ($Producto['ref_tiendas'] as $ref){
        // Debemos comprobar que es la referencia de la tienda web.. FALTA
        if ($ref['idVirtuemart'] >0){
            $idVirtuemart = $ref['idVirtuemart'];
        }
    }
  
}  
if ($CTArticulos->SetPlugin('ClaseVirtuemart') !== false && $ClasePermisos->getAccion("verWebEnProducto") == 1){
    // Sino tiene permisos ya no hacemos consulta a la web.
    $datosWebCompletos=array();
    // Creo el objeto de plugin Virtuemart.
    $ObjVirtuemart = $CTArticulos->SetPlugin('ClaseVirtuemart');     
    // Cargo caja_input de parametros de plugin de virtuemart.
    $ClasesParametrosPluginVirtuemart = new ClaseParametros($RutaServidor . $HostNombre . '/plugins/mod_producto/virtuemart/parametros.xml');
    $parametrosVirtuemart = $ClasesParametrosPluginVirtuemart->getRoot();
    $OtrosVarJS = $Controler->ObtenerCajasInputParametros($parametrosVirtuemart);
    // Obtengo el id de la tienda Web
    $tiendaWeb=$ObjVirtuemart->getTiendaWeb();
    if (count($tiendaWeb) >0){
        // Se conecta a la web y obtiene los datos de producto cruzado.
        $datosWebCompletos=$ObjVirtuemart->datosCompletosTiendaWeb($idVirtuemart,$Producto['iva'],$Producto['idArticulo'],$tiendaWeb['idTienda']);

        // Esto para comprobaciones iva... ??? Es correcto , si esto se hace JSON, no por POST.
        if(isset($datosWebCompletos['comprobarIvas']['comprobaciones'])){
            $Producto['comprobaciones'][]= $datosWebCompletos['comprobarIvas']['comprobaciones'];
        }
        
        if ($idVirtuemart>0 ) { 
           $cambiarEstado=$CTArticulos->modificarEstadoWeb($id, $datosWebCompletos['datosWeb']['estado'], $tiendaWeb['idTienda']);
        }
    }
}
// ==========		Montamos  html que mostramos. 			============ //
    if ($id == 0 ) {
        $Producto['iva']=$conf_defecto['iva_predeterminado'];
    }
    $htmlIvas = htmlOptionIvas($ivas,$Producto['iva']);
    $htmlTipo=htmlTipoProducto($Producto['tipo']);
    $htmlEstadosProducto =  htmlOptionEstados($posibles_estados_producto,$Producto['estado']);


    // Obtenemos si tiene permisopara eliminar registros.
    $borrar_ref_prov = 'Ok';
    if($ClasePermisos->getAccion("eliminarRefProveedores") == 0){
        $borrar_ref_prov = 'KO';
    }
    $htmltabla = array();
    if ( $ClasePermisos->getModulo('mod_balanza') == 1) {
        // Ahora obtenemos los las teclas de las balanza en los que esté este producto.
        $relacion_balanza = $CTArticulos->obtenerTeclaBalanzas($id);
        if (!isset($relacion_balanza['error'])){
            // Quiere decir que se obtuvo algun registro.
            // Puede ser un array.
            $htmltabla[] = array (  'titulo' => 'Plu y Teclas en balanzas',
                                    'html' => htmlTablaBalanza($relacion_balanza)
                                );
        }
    }
    $htmltabla[] = array (  'titulo' => 'Códigos de Barras',
                                    'html' => htmlTablaCodBarras($Producto['codBarras'])
                                );
    $htmltabla[] = array (  'titulo' => 'Proveedores - Costes',
                                    'html' => htmlTablaProveedoresCostes($proveedores_costes['proveedores'],$borrar_ref_prov)
                                );
    $htmltabla[] = array (  'titulo' => 'Familias',
                                    'html' => htmlTablaFamilias($Producto['familias'], $id)
                                );
    $htmltabla[] = array (  'titulo' => 'Productos en otras tiendas.',
                                    'html' => htmlTablaRefTiendas($Producto['ref_tiendas'],$datosWebCompletos['htmlLinkVirtuemart'],$ClasePermisos->getAccion("eliminarRefWebDeProducto"))
                                );
    $htmltabla[] = array (  'titulo' => 'Historico Precios.<span class="glyphicon glyphicon-info-sign" title="Ultimos 15 cambios precios"></span>',
                                    'html' => htmlTablaHistoricoPrecios($Producto['productos_historico'])
                                );
 // -------------- Obtenemos de parametros cajas con sus acciones en JS ---------------  //
    $VarJS = $Controler->ObtenerCajasInputParametros($parametros).$OtrosVarJS;
?>

<!DOCTYPE html>
<html>
    <head>
        <?php include_once $URLCom.'/head.php'; ?>
        <script src="<?php echo $HostNombre; ?>/jquery/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="<?php echo $HostNombre;?>/jquery/jquery-ui.min.css" type="text/css">
        <script src="<?php echo $HostNombre; ?>/lib/js/autocomplete.js"></script>    
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
        <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
		<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
		<script type="text/javascript">
		// Objetos cajas de tpv
		<?php
            echo $VarJS;
            echo 'var producto = new Object();';
            echo 'producto.idArticulo = '.$id.';';
			echo  'var ivas='.json_encode($ivas).';';
        ?>
		</script>

	</head>
	<body>
		<?php     
       //~ include_once $URLCom.'/header.php';
       include_once $URLCom.'/modulos/mod_menu/menu.php';
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
			<form method="post" name="formProducto" onkeypress="return anular(event)">
			<div class="col-md-12">
				<div class="col-md-12 ">
                    <?php echo $Link_volver;?>
					<input type="submit" value="Guardar" class="btn btn-primary">
				</div>
				<div class="col-md-6 Datos">
					<?php // si es nuevo mostramos Nuevo ?>
					<div class="col-md-4">
						<label>ID Producto:</label>
                            <?php echo $id?>
                        <input type="text" id="id" name="id" size="10" style="display:none;" value="<?php echo $id;?>" >
					</div>
					<div class="col-md-4">
					<label>Estado</label>
					<select id="idEstado" name="estado" onchange="">
						<?php echo $htmlEstadosProducto; ?>
					</select>
					</div>
                    <div class="col-md-4">
                        <label class="control-label " > Tipo:</label>
                        <?php 
                            echo $htmlTipo;
                        ?>
                    </div>
                 
					<div class="col-md-12">
						<div class="form-group col-lg-3 ">	
							<label class="control-label " > Referencia:</label>
							<input type="text" id="referencia" name="cref_tienda_principal" size="10" placeholder="referencia producto" data-obj= "cajaReferencia" value="<?php echo $Producto['cref_tienda_principal'];?>" onkeydown="controlEventos(event)"  >
						</div>
						<div class="form-group col-lg-9 ">	
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
                                <?php
                                    // Si es nuevo el producto permitimos de entrada poder editarlo.
                                    $solo_lectura = '';
                                    if ( $id > 0) {
                                        $solo_lectura =  ' readonly';
                                    }
                                ?>
                                
								<input type="text" pattern="[-+]?[0-9]*[.]?[0-9]+" id="coste" size="8" name="ultimoCoste" value=<?php echo '"'.number_format($Producto['ultimoCoste'],2, '.', '').'" '.$solo_lectura;?>  data-obj= "cajaCoste" onkeydown="controlEventos(event)"> 
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
                                       readonly="readonly" 
                                       data-obj= "cajaStockMin" 
                                        value="<?php echo number_format($Producto['stocks']['stockMin'], 2, '.', ''); ?>"   > 
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
                            foreach ($htmltabla as $i=>$h){
                                echo htmlPanelDesplegable($i,$h['titulo'],$h['html']);
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
             if($ClasePermisos->getAccion("verWebEnProducto")==1){
                        if(isset($datosWebCompletos['htmlproducto']['html'])){
                               echo $datosWebCompletos['htmlproducto']['html']; 
                        }
                        ?>
                        
                         <div class="col-md-6 text-center">
                            
                                <div class="panel-group">
                                    <?php
                                    if(isset( $datosWebCompletos['htmlnotificaciones']['html'])){
                                         $num = 6; // Numero collapse;
                                            $titulo = 'Notificaciones de clientes.';
                                            echo  htmlPanelDesplegable($num,$titulo,$datosWebCompletos['htmlnotificaciones']['html']);
                                    }
                                    if (isset($datosWebCompletos['htmlLinkVirtuemart'])){
                                            echo $datosWebCompletos['htmlLinkVirtuemart'];
                                    }
                                    
            }
                                     ?>
                                </div>
                         </div>
			
		<!--fin de div container-->
		<?php // Incluimos paginas modales
		echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
		include $RutaServidor.'/'.$HostNombre.'/plugins/modal/ventanaModal.php';
		?>
        </div> 
     <script type="text/javascript">
        <?php 
        if($ClasePermisos->getAccion("modificarStock")==1){ 
            ?>
            $("#stockmin").removeAttr("readonly");
            $("#stockmax").removeAttr("readonly");
           
        <?php 
        }
        if($ClasePermisos->getAccion("verCodBarras")==0){
            ?>
            $("#tcodigo a").hide();
            $("#tcodigo input").attr("readonly","readonly");
              <?php
        }
        if($ClasePermisos->getAccion("verProveedores")==0){
            ?>
             $("#tproveedor a").hide();
            $("#tproveedor input").attr("readonly","readonly");
            <?php
        } 
        if($ClasePermisos->getAccion("verFamilias")==0){
            ?>
              $("#tfamilias a").hide();
            <?php
        }
        
        if($ClasePermisos->getAccion("verHistoricoPrecios")==0){
            ?>
               $("#thitorico a").hide();
             <?php 
        }
        ?>
    </script> 
        <style>
           
#enlaceIcon{
    height: 2.2em;
}
 .custom-combobox {
    position: relative;
    display: inline-block;
  }
  .custom-combobox-toggle {
    position: absolute;
    top: 0;
    bottom: 0;
    margin-left: -1px;
    padding: 0;
  }
  .custom-combobox-input {
    margin: 0;
    padding: 5px 10px;
  }
  ul.ui-autocomplete {
    z-index: 1050;
}
</style>
    </body>
</html>
