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
if ( isset($preparados)){
    // La $preparados se monta en ./tareas/reciboPostProductos.php	
    // No podemos añadir al producto en recibosPostProducto.php porque cargamos despues el producto 
    // y eso hace las comprobaciones reinicien
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
    if (isset($preparados['familias'])){
        foreach ($preparados['familias'] as $comprobacion){
            $CTArticulos->SetComprobaciones($comprobacion);
        }
    }
    if (isset($preparados['insert_articulos'])){
        foreach ($preparados['insert_articulos'] as $comprobacion){
            $CTArticulos->SetComprobaciones($comprobacion);
        }
    }
}



$Producto['comprobaciones'] = $CTArticulos->GetComprobaciones();

// Antes de montar html de proveedores añado array de proveedores cual es principal
if ( !isset($Producto['proveedores_costes'])) {
    // No existe costes..
    $Producto['proveedores_costes']= array();
} else {
    if ( count($Producto['proveedor_principal']) > 0) {
        foreach ($Producto['proveedores_costes'] as $key=>$proveedor){
            if ($proveedor['idProveedor'] === $Producto['proveedor_principal']['idProveedor']){
                // Indicamos que es le principal
                $Producto['proveedores_costes'][$key]['principal'] = 'Si';
            }
        }
    }
} 
// ==========		 Comprobamso el ultimo coste y que proveedor		====  ===== //
$albaranes_ultimo = $CTArticulos->getUltimoPrecioCompra($Producto['idArticulo']);
$proveedores_costes = comprobarUltimaCompraProveedor($Producto['proveedores_costes']);

// Ahora comprobamos si el coste ultimo es correcto.
if (isset($albaranes_ultimo) ||   isset($proveedores_costes['coste_ultimo'])){
    // Verificamos que no hemos actualizado el coste de producto
    $actualizado = false;
    $valor_actualizado = 0.00;
    // Comprobamos el precio del producto con el albaran
    if (isset($albaranes_ultimo) &&
        number_format($albaranes_ultimo,2) != number_format($Producto['ultimoCoste'],2)){
        $success = array ( 'tipo'=>'warning',
                            'mensaje' =>'El ultimo coste, se acaba de actualizar con respecto al ultimo precio del albaran, coste_actual: '
                            .$Producto['ultimoCoste']. ' y coste_ultimo real: '.$albaranes_ultimo,
                            'dato' => array($albaranes_ultimo,$Producto['ultimoCoste'])
                            );
        // Ahora cambiamos el coste_ultimo
        $valor_actualizado = $albaranes_ultimo;
        $Producto['comprobaciones'][] = $success;
        $actualizado = true;			
    }
    // Comprobamos el precio del producto con el proveedor y damos un aviso del estado
    if (isset($proveedores_costes['coste_ultimo']) &&
        $actualizado &&
        number_format($proveedores_costes['coste_ultimo'],2) != number_format($Producto['ultimoCoste'],2)){
        $success = array ( 'tipo'=>'warning',
                            'mensaje' =>'El proveedor tiene un precio de tarifa que no coincide con el coste actual, coste_actual: '
                            .$Producto['ultimoCoste']. ' y coste_ultimo real: '.$proveedores_costes['coste_ultimo'],
                            'dato' => array($proveedores_costes['coste_ultimo'],$Producto['ultimoCoste'])
                            );
        $Producto['comprobaciones'][] = $success;
    }
    // Comprobamos el precio del producto con el proveedor y actualizamos el precio del producto
    if (isset($proveedores_costes['coste_ultimo']) &&
        !$actualizado &&
        number_format($proveedores_costes['coste_ultimo'],2) != number_format($Producto['ultimoCoste'],2)){
        $success = array ( 'tipo'=>'warning',
                            'mensaje' =>'El ultimo coste,  se acaba de actualizar con respecto al precio del proveedor, coste_actual: '
                            .$Producto['ultimoCoste']. ' y coste_ultimo real: '.$proveedores_costes['coste_ultimo'],
                            'dato' => array($proveedores_costes['coste_ultimo'],$Producto['ultimoCoste'])
                            );
        $Producto['comprobaciones'][] = $success;
        // Ahora cambiamos el coste_ultimo
        $valor_actualizado = $proveedores_costes['coste_ultimo'];			
    }
    if ($valor_actualizado != 0.00){
        $Producto['ultimoCoste'] = $valor_actualizado;
    }
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
if ($CTArticulos->SetPlugin('ClaseVirtuemart') !== false ){
    // Sino tiene permisos ya no hacemos consulta a la web.
    if($ClasePermisos->getModulo("mod_virtuemart")==1){
        $datosWebCompletos=array();
        // Creo el objeto de plugin Virtuemart.
        $ObjVirtuemart = $CTArticulos->SetPlugin('ClaseVirtuemart');     
        // Cargo caja_input de parametros de plugin de virtuemart.
        $ClasesParametrosPluginVirtuemart = new ClaseParametros($RutaServidor . $HostNombre . '/plugins/mod_producto/virtuemart/parametros.xml');
        $parametrosVirtuemart = $ClasesParametrosPluginVirtuemart->getRoot();
        $OtrosVarJS = $Controler->ObtenerCajasInputParametros($parametrosVirtuemart);
        // Obtengo el id de la tienda Web
        $tiendaWeb=$ObjVirtuemart->getTiendaWeb();
        if (count($tiendaWeb) >0 && $Producto['idArticulo'] > 0){
            // Se conecta a la web y obtiene los datos de producto cruzado
            // Obtenemos ademas los ivas de la web para poder hacer la relación , por eso se hace la consulta igualmente aunque idVirtuemart sea 0
            $datosWebCompletos=$ObjVirtuemart->datosCompletosTiendaWeb($idVirtuemart,$Producto['iva'],$Producto['idArticulo'],$tiendaWeb['idTienda']);
            // Esto para comprobaciones iva... ??? Es correcto , si esto se hace JSON, no por POST.
            if (isset($datosWebCompletos['errores'])) {
                    $Producto['comprobaciones'][]= $datosWebCompletos['errores'];
            } else  {
                if ($idVirtuemart>0 ) { 
                   // Cambiamos el registro en local de la relacion y ponemos los datos actualizados.
                   $cambiarEstado=$CTArticulos->modificarEstadoWeb($id, $datosWebCompletos['datosWeb']['estado'], $tiendaWeb['idTienda']);
                }
            }
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
        // Ahora obtenemos los las plu y secciones de las balanza en los que esté este producto.
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
    $htmltabla[] = array (  'titulo' => 'Albaranes de Compra',
                                    'html' => htmlTablaAlbaranes($Producto['albaranes'])
                                );
    $htmltabla[] = array (  'titulo' => 'Pedidos de Compra',
                                    'html' => htmlTablaPedidos($Producto['pedidos'])
                                );
    $htmltabla[] = array (  'titulo' => 'Familias',
                                    'html' => htmlTablaFamilias($Producto['familias'], $id)
                                );
    // echo '<pre>';
    // print_r($Producto);
    // echo '</pre>';
    if (isset ( $datosWebCompletos['datosWeb']) == true && !isset($datosWebCompletos['errores']) ){
        if ($datosWebCompletos['datosWeb']['estado'] == 0 ){
        $linkVirtuemart = 'No esta publicado';
        } else {
            $linkVirtuemart = $datosWebCompletos['htmlsLinksVirtuemart']['html_frontEnd'];
        }
    } else {
        // hubo un error al obtener datos de la web
         $linkVirtuemart = 'Error al obtener datos';
    }
    $htmltabla[] = array (  'titulo' => 'Productos en otras tiendas.',
                                    'html' => htmlTablaRefTiendas($Producto['ref_tiendas'],$linkVirtuemart,$ClasePermisos->getAccion("eliminarRefWebDeProducto"))
                                );
    $htmltabla[] = array (  'titulo' => 'Historico Precios.<span class="glyphicon glyphicon-info-sign" title="Ultimos 15 cambios precios"></span>',
                                    'html' => htmlTablaHistoricoPrecios($Producto['productos_historico'])
                                );

    if ($precioNuevo && $Producto['tipo'] == 'peso') {
        include_once $URLCom.'/modulo/mod_balanza/clases/ClaseComunicacionBalanza.php';
        $traductorBalanza = new ClaseComunicacionBalanza();
        $idBalanza = 1;
        $ruta_balanza = '/balanza';
        $ComunicacionBalanza = array('Comprobaciones' => array());
        $faltanDatos = [];

        if (empty($Producto['cref_tienda_principal'])) {
            $faltanDatos[] = 'Referencia principal (cref_tienda_principal)';
        } elseif (!is_numeric($Producto['cref_tienda_principal'])) {
            $faltanDatos[] = 'Referencia principal (cref_tienda_principal)';
            $ComunicacionBalanza['Comprobaciones'][] = array(
                'tipo' => 'warning',
                'mensaje' => 'La referencia principal debe ser numérica. Valor recibido: ' . $Producto['cref_tienda_principal'],
                'dato' => array($Producto['cref_tienda_principal'])
            );
            error_log('El dato cref_tienda_principal debe ser numérico, valor recibido: ' . $Producto['cref_tienda_principal']);
        }
        if (empty($Producto['articulo_name'])) {
            $faltanDatos[] = 'Nombre producto (articulo_name)';
        }
        if (!isset($Producto['pvpCiva'])) {
            $faltanDatos[] = 'Precio con IVA (pvpCiva)';
        }
        if (empty($Producto['tipo'])) {
            $faltanDatos[] = 'Tipo de producto (tipo)';
        }
        if (!isset($Producto['iva'])) {
            $faltanDatos[] = 'IVA (iva)';
        }
        if (!empty($faltanDatos)) {
            $ComunicacionBalanza['Comprobaciones'][] = array(
                'tipo' => 'warning',
                'mensaje' => 'Faltan datos obligatorios para la comunicación con la balanza: ' . implode(', ', $faltanDatos),
                'dato' => $faltanDatos
            );
            error_log('Faltan datos obligatorios para la comunicación con la balanza: ' . implode(', ', $faltanDatos));
        } else {
            $datosH2 = array(
                'codigo' => $Producto['cref_tienda_principal'],
                'nombre' => $Producto['articulo_name'],
                'precio' => $Producto['pvpCiva'],
                'PLU' => '',
            );
            $datosH3 = array(
                'codigo' => $Producto['cref_tienda_principal'],
                'tipoProducto' => $Producto['tipo'],
                'iva' => $Producto['iva'],
                'seccion' => '',
            );
            if (!isset($relacion_balanza['error'])) {
                foreach ($relacion_balanza as $relacion) {
                    if ($relacion['idBalanza'] == $idBalanza) {
                        $ruta_balanza = '/balanza';
                        $datosH2['PLU'] = $relacion['plu'];
                        $datosH3['seccion'] = $relacion['seccion'];
                        break;
                    }
                }
            }
            $traductorBalanza->setH2Data($datosH2);
            $traductorBalanza->setH3Data($datosH3);
            error_log('['.$_SESSION['usuarioTpv']['nombre'] . "] Datos a enviar a balanza: " . json_encode($datosH2) . json_encode($datosH3));
            $salida = $traductorBalanza->traducirH2();
            $salida .= $traductorBalanza->traducirH3();
            $directorioBalanza = $RutaServidor . $rutatmp . $ruta_balanza;

            $resultado = @file_put_contents($directorioBalanza . "/filetx", $salida);
            if ($resultado === false) {
                $ComunicacionBalanza['Comprobaciones'][] = array(
                    'tipo' => 'warning',
                    'mensaje' => 'Error grave de Comunicación: No se pudo escribir el fichero de comunicación con la balanza en ' . $directorioBalanza . "/filetx",
                    'dato' => array($directorioBalanza . "/filetx")
                );
                error_log('No se pudo escribir el fichero de comunicación con la balanza en ' . $directorioBalanza . "/filetx");
            } else {
                $traductorBalanza->setRutaBalanza($directorioBalanza);
                $ejecucion = $traductorBalanza->ejecutarDriverBalanza();
                if ($ejecucion === false) {
                    $ComunicacionBalanza['Comprobaciones'][] = array(
                        'tipo' => 'warning',
                        'mensaje' => 'Error grave de Comunicación: Fallo al ejecutar el driver de la balanza.',
                        'dato' => array()
                    );
                    error_log('Fallo al ejecutar el driver de la balanza.');
                } else {
                    $ComunicacionBalanza['Comprobaciones'][] = array(
                        'tipo' => 'success',
                        'mensaje' => 'Comunicación con la balanza realizada correctamente.',
                        'dato' => array($datosH2, $datosH3)
                    );
                }
                // Si hay alertas del traductor, añadirlas como warning
                // $alertas = $traductorBalanza->getAlertas();
                // if (!empty($alertas)) {
                //     foreach ($alertas as $alerta) {
                //         $ComunicacionBalanza['Comprobaciones'][] = array(
                //             'tipo' => 'warning',
                //             'mensaje' => $alerta,
                //             'dato' => array()
                //         );
                //     }
                // }
            }
        }
        // Mostrar avisos en pantalla
    }
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
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/js/AccionesDirectas.js"></script>
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

            if (!empty($ComunicacionBalanza['Comprobaciones'])) {
                foreach ($ComunicacionBalanza['Comprobaciones'] as $comprobacion) {
                    echo '<div class="alert alert-'.$comprobacion['tipo'].'">'.$comprobacion['mensaje'].'</div>';
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
                    <div class="row">
                        <div class="col-md-2">
                            <label>ID Producto:</label>
                                <?php echo $id?>
                            <input type="text" id="id" name="id" size="10" style="display:none;" value="<?php echo $id;?>" >
                        </div>
                        <div class="col-md-2">
                        <label>Estado</label>
                        <select id="idEstado" name="estado" onchange="">
                            <?php echo $htmlEstadosProducto; ?>
                        </select>
                        </div>
                        <div class="col-md-2">
                            <label class="control-label " > Tipo:</label>
                            <?php 
                                echo $htmlTipo;
                            ?>
                        </div>
                        <div class="col-md-4">
                           
                            <?php 
                                if($id>0){
                                  ?>
                                   <label class="control-label " > Fecha Creación:</label>
                            <input type="date" value="<?php  echo date('Y-m-d', strtotime($Producto['fecha_creado']));?>" disabled />

                                  <?php  
                                }
                              //  echo $htmlTipo;
                            ?>
                        </div>
                    </div>
                 
					<div class="row">
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
					<div class="row">
						<div class="col-md-12"><h4> Costes del Producto</h4></div>
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
					<div class="row">
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

                    <div class="row">
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
                    <?php
                    echo '<a class="glyphicon glyphicon-list" href="./DetalleMayor.php?idArticulo='
                            .$Producto['idArticulo'].'">Listado mayor todo el año</a>';?>
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
                                    if(isset( $datosWebCompletos['htmlnotificaciones'])){
                                         $num = 6; // Numero collapse;
                                            $titulo = 'Notificaciones de clientes:<span class="num_notificaciones">'
                                                    .$datosWebCompletos['num_notificaciones'].'</span>';
                                            echo  htmlPanelDesplegable($num,$titulo,$datosWebCompletos['htmlnotificaciones']);
                                    }
                                    if (isset($datosWebCompletos['htmlsLinksVirtuemart']['html_backEnd'])){
                                            echo $datosWebCompletos['htmlsLinksVirtuemart']['html_backEnd'];
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
