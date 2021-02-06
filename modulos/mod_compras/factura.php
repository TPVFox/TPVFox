<!DOCTYPE html>
<html>
<head>
<?php
	include_once './../../inicial.php';
	//Carga de archivos php necesarios
    include_once $URLCom.'/head.php';
    include_once $URLCom.'/modulos/mod_compras/funciones.php';
    include_once $URLCom.'/controllers/Controladores.php';
    include_once $URLCom.'/clases/Proveedores.php';
    include_once $URLCom.'/modulos/mod_compras/clases/albaranesCompras.php';
    include_once $URLCom.'/modulos/mod_compras/clases/facturasCompras.php';
    include_once $URLCom.'/controllers/parametros.php';
	//Carga de clases necesarias
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$Cproveedor=new Proveedores($BDTpv);
	$CAlb=new AlbaranesCompras($BDTpv);
	$CFac = new FacturasCompras($BDTpv);
	$Controler = new ControladorComun; 
	$Controler->loadDbtpv($BDTpv);
	//Inicializar las variables
	$dedonde="factura";
	$titulo="Factura De Proveedor";
    // Valores por defecto de estado y accion.
    // [estado] -> Nuevo,Sin Guardar,Guardado,Contabilizado.
    // [accion] -> editar,ver
    $estado='Nuevo';
    // Si existe accion, variable es $accion , sino es "editar"
    $accion = (isset($_GET['accion']))? $_GET['accion'] : 'editar';
	$fecha=date('d-m-Y');
	$fechaImporte=date('Y-d-m');
    $idFacturaTemporal=0;
	$idFactura=0;
    $idProveedor="";
    $formaPago=0;
	$suNumero="";
	$nombreProveedor="";
	$fechaVencimiento="";
	$Datostotales=array();
    $errores = array();
    $albaran_html_linea_producto = array();
    $JS_datos_albaranes = '';
    $html_adjuntos = '';
	$albaranes=array();
    $creado_por = array();
	//Cargamos la configuración por defecto y las acciones de las cajas 
	$parametros = $ClasesParametros->getRoot();		
	foreach($parametros->cajas_input->caja_input as $caja){
        // Ahora cambiamos el parametros por defecto que tiene dedonde = pedido y le ponemos albaran
		$caja->parametros->parametro[0]="factura";
	}
	$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
	$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
	$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_compras',$Usuario['id']);
	$configuracionArchivo=array();
	foreach ($configuracion['incidencias'] as $config){
		if(get_object_vars($config)['dedonde']==$dedonde){
			array_push($configuracionArchivo, $config);
		}
	}
    // Por GET recibimos uno o varios parametros:
    //  [id] cuando editamos o vemos un albaran pulsando en listado.
    //  [tActual] cuando pulsamos en cuadro albaranes temporales.
    //  [accion] cuando indicamos que accion vamos hacer.
	if (isset($_GET['id'])){
		$idFactura=$_GET['id'];
    }
    if (isset($_GET['tActual'])){
        $idFacturaTemporal=$_GET['tActual']; // Id de albaran temporal
    }
    // ---------- Posible errores o advertencias mostrar     ------------------- //
    if ($idFactura > 0){
    // Comprobamos cuantos temporales tiene idPedido y si tiene uno obtenemos el numero.
        $c = $CFac->comprobarTemporalIdFacpro($idFactura);
        if (isset($c['idTemporal']) && $c['idTemporal'] !== NULL){
            // Existe un temporal de este pedido por lo que cargo ese temporal.
            $idFacturaTemporal = $c['idTemporal'];
            $idFactura = 0 ; // Lo pongo en 0 para ejecute la parte temporal
            $_GET['tActual'] = $idFacturaTemporal;
            if ($accion !== 'temporal'){
                // Si entro sin accion temporal, NO PERMITO EDITAR.
                // YA PROVABLEMENTE ESTAN EDITANDO.
                $accion = 'ver';
                // Creo alert
                echo '<script>alert("No se permite editar, ya que alguien esta editandolo, hay un temporal");</script>';
            }
        } else {
            if (count($c)>0){
                 $errores= $c;
            }
        }
    }
    if ( $idFactura > 0 && count($errores) === 0){
        // Si exite id estamos y no hay errores modificando directamente un albaran.
		$datosFactura=$CFac->GetFactura($idFactura);
        if (isset($datosFactura['error'])){
            $errores=$datosFactura['error'];
        } else {
            if(isset($datosFactura['estado']) ){
                $estado=$datosFactura['estado'];
                $idFactura = $datosFactura['id'];
                if ($datosFactura['estado']=="Contabilizado"){
                    // Cambiamos accion, ya que solo puede ser ver.
                    $accion = 'ver';
                }
            }
        }
    }
    if ($idFacturaTemporal > 0 && count($errores) === 0){
        // Puede entrar cuando :
        //   -Viene de albaran temporal
        //   -Se recargo mientras editamos.
        //   -Cuando pulsamos guardar.
        $datosFactura=$CFac->buscarFacturaTemporal($idFacturaTemporal);
        if (isset($datosFactura['error'])){
                array_push($errores,$CFac->montarAdvertencia(
                                'danger',
                                'Error 1.1 en base datos.Consulta:'.json_encode($datosFactura['consulta'])
                        )
                );
        } else {
            // Preparamos datos que no viene o que vienen distintos cuando es un temporal.
            $datosFactura['FechaVencimiento'] ='0000-00-00';
            $datosFactura['Productos'] = json_decode($datosFactura['Productos'],true);
            $idFactura = $datosFactura['numfacpro'];
            $estado=$datosFactura['estadoFacPro'];
        }
    }
    
    if (count($errores) == 0){
        // Si no hay errores graves continuamos.
        if (!isset($datosFactura)){
            // Es que nuevo.
            $datosFactura = array();
            $datosFactura['Fecha']="0000-00-00 00:00:00";
            $datosFactura['Su_numero'] = '';
            $datosFactura['idProveedor'] = 0;
            $creado_por = $Usuario;
        }else {
            // Si no es nuevo
            $idProveedor=$datosFactura['idProveedor'];
            $proveedor=$Cproveedor->buscarProveedorId($idProveedor);
            $nombreProveedor=$proveedor['nombrecomercial'];
            $productos =$datosFactura['Productos'];
            $fecha = ($datosFactura['Fecha']=="0000-00-00 00:00:00")
                                ? date('d-m-Y'):date_format(date_create($datosFactura['Fecha']),'d-m-Y');
            $creado_por = $CFac->obtenerDatosUsuario($datosFactura['idUsuario']);
            if (isset($datosFactura['Albaranes'])){
                if ($idFacturaTemporal >0){
                    // Cuando viene de tActual obtenemos .
                    // Solo convertimos $idAlbaranTemporal >0 , ya que es cuando viene json
                    $datosFactura['Albaranes'] = json_decode($datosFactura['Albaranes'],true);
                }
                if (count($datosFactura['Albaranes'])>0){
                    // Ahora obtengo todos los datos de ese albaran.
                    foreach ($datosFactura['Albaranes'] as $key =>$albaran){
                        // ========             Ahora obtenemos todos los datos         ======== //
                        if ( isset($albaran['idAlbaran'])){
                            $idAlbaran = $albaran['idAlbaran'];
                        } else {
                            // Entra aquí cuando se añadio facturatemporal un albaran , pero no se guardo, solo creo temporal.
                            $idAlbaran = $albaran['idAdjunto'];
                            $datosFactura['Albaranes'][$key]['idAlbaran'] =$idAlbaran; 
                        }
                        $e = $CAlb->DatosAlbaran($idAlbaran);
                        // El indice 'estado' es el estado del albaran puede ser "Sin Guardar", "Guardado","Facturado"
                        // Ahora vamos a crear el estado del adjunto, pero teniendo en cuenta
                        // Que si estado_pedido es "Sin Guardar" tenemos que enviar un error.
                        // Si estado_pedido es "Guardado" entonces el estado adjunto es 'Eliminado'.
                        // Si estado_pedido es "Facturado" entonces el estado ajunto es 'activo'.
                        if ($e['estado'] === 'Facturado'){
                            $estado_adjunto = 'activo';
                        } else {
                            $estado_adjunto = 'Eliminado';
                            if ($e['estado'] !== 'Guardado'){
                                // Informo posible error, ya que el estado pedido no es Guardado , ni Facturado..
                                array_push($errores,$CFac->montarAdvertencia(
                                    'dannger',
                                    'Posible error, el pedido con id:'.$idPedido.' tiene estado '.$e['estado'])
                                );
                            }
                        }
                        $datosFactura['Albaranes'][$key]['estado'] = $estado_adjunto;
                        $datosFactura['Albaranes'][$key]['fecha'] = $e['Fecha'];
                        $datosAlbaran['Albaranes'][$key]['total_siniva'] = $e['total_siniva'];
                        $datosFactura['Albaranes'][$key]['total'] = $e['total'];
                        $datosFactura['Albaranes'][$key]['NumAdjunto'] = $e['Numalbpro'];
                        $datosFactura['Albaranes'][$key]['idAdjunto'] = $idAlbaran;
                        $datosFactura['Albaranes'][$key]['nfila'] = $key+1;
                        // ========                 JS_datos_pedidos                    ======== //
                        $JS_datos_albaranes .=  'datos='.json_encode($datosFactura['Albaranes'][$key]).';'
                                            .'albaranes.push(datos);';
                        // ========               $html_adjuntos                        ======== //
                        $h =lineaAdjunto($datosFactura['Albaranes'][$key], "factura",$accion);
                        $html_adjuntos .= $h['html'];
                        // ========  Array para mostrar en lineas productos de adjuntos ======== //
                        $h =htmlDatosAdjuntoProductos($datosFactura['Albaranes'][$key],$dedonde);
                        $albaran_html_linea_producto[$idAlbaran] = $h;
                    }
                }
            }
            $formaPago=(isset($datosFactura['formaPago']))? $datosFactura['formaPago'] : 0;
            $fechaVencimiento=$datosFactura['FechaVencimiento'];
            if (isset ($datosFactura['numfacpro'])){
                $d=$CFac->buscarFacturaNumero($datosFactura['numfacpro']);
                $idFactura=$d['id'];
                // Debemos saber si debemos tener incidencias para ese albaran, ya que el boton incidencia es distinto.
                $incidencias=incidenciasAdjuntas($idFactura, "mod_compras", $BDTpv, $dedonde);
            }
            if ($datosFactura['Su_num_factura']!==""){
                $suNumero=$datosFactura['Su_num_factura'];
            }
        }
        $textoFormaPago=htmlFormasVenci($formaPago, $BDTpv); // Generamos ya html.
        if(isset($datosFactura['Productos'])){
			// Obtenemos los datos totales ;
			// convertimos el objeto productos en array
            $p = (object)$productos;
            $Datostotales = $CFac->recalculoTotales($p);
        }
	}
	if(isset($datosFactura['Productos'])){
        // Obtenemos los datos totales ( fin de ticket);
        // convertimos el objeto productos en array
        $Datostotales = recalculoTotales($productos);
        $productos = json_decode(json_encode($productos), true); // Array de arrays
    }
	if (isset($_POST['Guardar'])){
            if ($_POST['fechaVenci'] === ''){
                $_POST['fechaVenci'] = '0000-00-00';
            }
            $guardar=$CFac->guardarFactura();
			if (count($guardar)==0){
                header('Location: facturasListado.php');
            }else{
                // Hubo errores o advertencias.
                foreach ($guardar as $error){
                    array_push($errores,$error);
                }
            }
	}
    // ============                 Montamos el titulo                      ==================== //
    $html_f='';
    if(isset($numFactura)){
        $html_f = ' <span style="font-size: 0.55em;vertical-align: middle;" class="label label-default">';
        $html_f .= 'factura:'.$numFactura['idFactura'];
        $html_f .='</span>';
    }
    $titulo .= ' '.$idFactura.$html_f.' - '.$accion;
    // ============= Creamos variables de estilos para cada estado y accion =================== //
    $estilos = array ( 'readonly'       => '',
                       'styleNo'        => 'style="display:none;"',
                       'pro_readonly'   => '',
                       'pro_styleNo'    => '',
                       'btn_guardar'    => '',
                       'btn_cancelar'   => '',
                       'input_factur'   => '',
                       'select_factur'  => '',
                       'evento_cambio'  => ''
                    );
    if (isset ($_GET['id']) || isset ($_GET['tActual'])){
        // Quiere decir que ya inicio , ya tuvo que meter proveedor.
        // no se permite cambiar proveedor.
        $estilos['pro_readonly']   = ' readonly';
        $estilos['pro_styleNo']    = ' style="display:none;"';
        $estilos['styleNo']    = '';
        $estilos['evento_cambio'] = 'onchange ="addTemporal('."'".$dedonde."'".')"'; // Lo utilizo para crear temporal cuando cambia valor.

    }
    if ($accion === 'ver'){
        $estilos['readonly']   = ' readonly';
        $estilos['styleNo']     = ' style="display:none;"';
        $estilos['input_factur'] = ' readonly';
        $estilos['select_factur'] = 'disabled="true"';       
    }
    if ($idFacturaTemporal === 0){
        // Solo se muestra cuando el numPedidoTemp es 0
        $estilos['btn_guardar'] = 'style="display:none;"';
        // Una vez se cree temporal, con javascript se quita style
    }
?>
	<script type="text/javascript">
	// Esta variable global la necesita para montar la lineas.
	// En configuracion podemos definir SI / NO
	<?php echo 'var configuracion='.json_encode($configuracionArchivo).';';?>	
	var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
		cabecera['idUsuario'] = <?php echo $creado_por['id'];?>; // Tuve que adelantar la carga, sino funcionaria js.
		cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
		cabecera['estado'] ='<?php echo $estado ;?>'; // Si no hay datos GET es 'Nuevo'
		cabecera['idTemporal'] = <?php echo $idFacturaTemporal ;?>;
		cabecera['idReal'] = '<?php echo $idFactura ;?>';
		cabecera['fecha'] ='<?php echo $fecha ;?>';
		cabecera['idProveedor'] = '<?php echo $idProveedor ;?>';
		cabecera['suNumero']='<?php echo $suNumero; ?>';
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array
	var albaranes =[];
<?php 
	if (isset($idFacturaTemporal)|| isset($idFactura)){ 
		if (isset($productos)){
			foreach($productos as $k => $product){
?>	
                datos=<?php echo json_encode($product); ?>;
                productos.push(datos);
<?php 
                // cambiamos estado y cantidad de producto creado si fuera necesario.
                if ($product['estado'] !== 'Activo'){
                ?>	productos[<?php echo $k;?>].estado=<?php echo'"'.$product['estado'].'"';?>;
                <?php
                }
			}
		}
        if (isset ($datosFactura['Albaranes'])){
            if ($JS_datos_albaranes != ''){
                echo $JS_datos_albaranes;
            }
		}
	}
?>
</script>
</head>
<body>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
    <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_compras/js/AccionesDirectas.js"></script>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
<?php
     include_once $URLCom.'/modulos/mod_menu/menu.php';
?>
<script type="text/javascript">
    <?php
	if (isset($_POST['Cancelar'])){
	?>
        mensajeCancelar(<?php echo $idFacturaTemporal;?>, <?php echo "'".$dedonde."'"; ?>); 
        <?php
	}
	echo $VarJS;
    ?>
    function anular(e) {
        tecla = (document.all) ? e.keyCode : e.which;
        return (tecla != 13);
    }
</script>
<div class="container">
	<?php
	if (isset($errores)){
        foreach ($errores as $comprobaciones){
            echo $CAlb->montarAdvertencia($comprobaciones['tipo'],$comprobaciones['mensaje'],'OK');
            if ($comprobaciones['tipo'] === 'danger'){
                exit; // No continuo.
            }
        }
    }
    ?>
    <form action="" method="post" name="formProducto" onkeypress="return anular(event)">
    <?php 
        echo '<h3 class="text-center">'.$titulo;
        if ($accion !=='ver'){
            echo ' temporal:'.'<input type="text" readonly size ="4" name="idTemporal" value="'.$idFacturaTemporal.'">';
        }
        echo '</h3>';
    ?>
    <div class="col-md-12">
        <div class="col-md-8" >
            <?php echo $Controler->getHtmlLinkVolver('Volver');
            // Botones de incidencias.
            if($idFactura>0){
                echo '<input class="btn btn-warning" size="12" onclick="abrirModalIndicencia('."'".$dedonde
                ."'".' , configuracion, 0,'.$idFactura
                .');" value="Añadir incidencia " name="addIncidencia" id="addIncidencia">';
            }
            if( isset($incidencias) && count( $incidencias)> 0){
                echo ' <input class="btn btn-info" size="15" onclick="abrirIncidenciasAdjuntas('
                .$idFactura.', '."'mod_compras','factura'"
                .')" value="Incidencias Adjuntas " name="incidenciasAdj" id="incidenciasAdj">';
            }
            if ($estado != "Contabilizado" || $accion != "ver"){
                // El btn guardar solo se crea si el estado es "Nuevo","Sin Guardar","Guardado"
                echo '<input class="btn btn-primary" '.$estilos['btn_guardar']
                    .' type="submit" value="Guardar" name="Guardar" id="bGuardar">';
            }
            ?>
        </div>
        <div class="col-md-4 text-right" >
            <?php
            if ($estado != "Contabilizado" && $accion != "ver"){?>
            <span class="glyphicon glyphicon-cog" title="Escoje casilla de salto"></span>
             <?php echo htmlSelectConfiguracionSalto();
                // El btn cancelar solo se crea si el estado es "Nuevo"
                // pero solo se muestra cuando hay un temporal, ya que no tiene sentido mostrarlo si no hay temporal
                if ($estado != "Nuevo"){
                    $estilos['btn_cancelar'] = ' style="display:none;"';
                    // Se cambia con javascript cuando creamos el temporal y el estado es Nuevo.
                }
                echo '<input type="submit" class="btn btn-danger"'
                    .$estilos['btn_cancelar']. ' value="Cancelar" name="Cancelar" id="bCancelar">';
            }
            ?>
        </div>
    </div>
    <div class="row" >
        <div class="col-md-7">
            <div class="col-md-12">
                    <label class="text-center">Proveedor</label>
                    <?php
                    echo '<div class="col-md-2">
                            <input type="text" id="id_proveedor" name="id_proveedor" data-obj= "cajaIdProveedor" value="'
                            .$idProveedor.'" '.$estilos['pro_readonly'].' size="2" onkeydown="controlEventos(event)" placeholder="id">
                        </div>';
                    echo '<div class="col-md-8">
                            <input type="text" id="Proveedor" name="Proveedor" data-obj= "cajaProveedor" '
                            .'placeholder="Nombre de proveedor" onkeydown="controlEventos(event)" value="'
                            .$nombreProveedor.'" '.$estilos['pro_readonly'].' size="60" >
                         </div>';
                         
                    echo   '<div class="col-md-2">
                                <a id="buscar" '.$estilos['pro_styleNo'].' class="btn glyphicon glyphicon-search buscar"'
                                .' onclick="buscarProveedor('."'".'factura'."'".',Proveedor.value)"></a>
                          </div>';
                    ?>
            </div>
            <div class="col-md-12">
				<div class="col-md-4">
					<label>Fecha:</label>
                    <?php
                        $pattern_numerico = ' pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" ';
                        $title_fecha =' placeholder="dd-mm-yyyy" title=" Formato de entrada dd-mm-yyyy"';
                        echo '<input type="text" name="fecha" id="fecha" size="8" data-obj= "cajaFecha" '
                        . $estilos['input_factur'].' value="'.$fecha.'" '.$estilos['evento_cambio'].' onkeydown="controlEventos(event)" '
                            . $pattern_numerico.$title_fecha.'/>';
                    ?>
				</div>
				<div class="col-md-4">
					<label>Estado:</label>
					<span id="EstadoTicket"> <input type="text" id="estado" name="estado" value="<?php echo $estado;?>" size="10" readonly></span><br>
				</div>
				<div class="col-md-4">
					<label>Creado por:</label>
					<input type="text" id="Usuario" name="Usuario" value="<?php echo $creado_por['nombre'];?>" size="10" readonly>
				</div>
            </div>
            <div class="col-md-12">
                <div class="col-md-4">
					<label>Su número:</label>
                    <input type="text" id="suNumero" name="suNumero" value="<?php echo $suNumero;?>" size="10" <?php echo $estilos['evento_cambio'];?> onkeydown="controlEventos(event)" data-obj= "CajaSuNumero" <?php echo $estilos['input_factur'];?>>
                </div>
                <div class="col-md-4">
                        <label>Fecha vencimiento:</label>
                        <?php
                             echo '<input type="date" name="fechaVenci" id="fechaVenci" size="8" '
                                . $estilos['input_factur'].' value="'.$fechaVencimiento.'" onkeydown="controlEventos(event)" '
                                . $pattern_numerico.$title_fecha.'>';
                        ?>
                </div>
                <div class="col-md-4">
                    <label>Forma de pago:</label>
                    <div id="formaspago">
                        <select name='formaVenci' id='formaVenci' <?php echo   $estilos['select_factur'];?>>
                    <?php 
                    if(isset ($textoFormaPago)){
                            echo $textoFormaPago['html'];
                    }
                    ?>
                        </select>
                    </div>
                </div>
            </div>
	</div>
	<div class="col-md-5 adjunto" >
        <?php
        if ($accion !=='ver'){
        ?>
            <label  id="numPedidoT">Número del albarán:</label>
            <input  type="text" id="numPedido" name="numPedido" value="" size="5" placeholder='Num' data-obj= "numPedido" onkeydown="controlEventos(event)">
            <a id="buscarPedido" class="glyphicon glyphicon-search buscar" onclick="buscarAdjunto('factura')"></a>
        <?php
        } ?>
            <table  class="col-md-12" id="tablaPedidos"> 
				<thead>
                <tr>
                    <td><b>Número</b></td>
                    <td><b>Su Número</b></td>
                    <td><b>Fecha</b></td>
                    <td><b>TotalCiva</b></td>
                    <td><b>TotalSiva</b></td>
                    <td></td>
                </tr>
				</thead>
				<?php 
				if (isset($datosFactura['Albaranes'])){
                    if( $html_adjuntos != ''){
                        echo  $html_adjuntos;
                    }
                }
				?>
			</table>
	</div>
	<!-- Tabla de lineas de productos -->
	<div>
		<table id="tabla" class="table table-striped">
		<thead>
		  <tr>
			<th>L</th>
			<th>Num Albaran</th>
			<th>Id Articulo</th>
			<th>Referencia</th>
			<th>Referencia Proveedor</th>
			<th>Cod Barras</th>
			<th>Descripcion</th>
			<th>Unid</th>
			<th>Coste</th>
			<th>Iva</th>
			<th>Importe</th>
			<th></th>
		  </tr>
		  <tr id="Row0" <?php echo $estilos['styleNo'];?>>  
			<td id="C0_Linea" ></td>
			<td id="C0_Linea" ></td>
			<td><input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo" data-obj= "cajaidArticulo" size="4" value=""  onkeydown="controlEventos(event)"></td>
			<td><input id="Referencia" type="text" name="Referencia" placeholder="Referencia" data-obj="cajaReferencia" size="8" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="ReferenciaPro" type="text" name="ReferenciaPro" placeholder="Referencia" data-obj="cajaReferenciaPro" size="10" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras" data-obj= "cajaCodBarras" size="12" value="" data-objeto="cajaCodBarras" onkeydown="controlEventos(event)"></td>
			<td><input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion" data-obj="cajaDescripcion" size="17" value="" onkeydown="controlEventos(event)"></td>
		</tr>
		</thead>
		<tbody>
			<?php 
                    //Recorremos los productos y vamos escribiendo las lineas.
			if (isset($productos)){
                $numAdjunto=0;
                foreach (array_reverse($productos) as $producto){
                    // Ahora tengo que controlar si son lineas de adjunto, para añadir linea de adjunto.
                    $numeroDoc = 0;
                    if (isset($producto['idalbpro']) && $producto['idalbpro']>0 ){
                        $numeroDoc= $producto['idalbpro'];
                    }
                    if (isset($producto['numAlbaran']) && $producto['numAlbaran'] > 0){
                        $numeroDoc= $producto['numAlbaran'];
                    }
                        
                    if($numeroDoc<>$numAdjunto){
                        // Si numero documento es distinto a numerAdjunto,
                        // entonces debemos obtener linea de adjunto para poner en productos.
                        $numAdjunto=$numeroDoc;
                        echo $albaran_html_linea_producto[$numeroDoc];
					}
                    
					$html=htmlLineaProducto($producto, "factura",$estilos['input_factur']);
					echo $html['html'];
				}
			}
			?>
		</tbody>
	  </table>
	</div>
	<?php 
	if (isset($DatosTotales)){
		?>
		<script type="text/javascript">
			total = <?php echo $Datostotales['total'];?>;
		</script>
		<?php
	}
	?>
	<div class="col-md-10 col-md-offset-2 pie-ticket">
		<table id="tabla-pie" class="col-md-6">
		<thead>
			<tr>
				<th>Tipo</th>
				<th>Base</th>
				<th>IVA</th>
			</tr>
		</thead>
		<tbody>
			<?php 
			if (isset($Datostotales)){
				$htmlIvas=htmlTotales($Datostotales);
				echo $htmlIvas['html'];
			}
			  ?>
		</tbody>
		</table>
		<div class="col-md-6">
			<div class="col-md-4">
			<h3>TOTAL</h3>
			</div>
			<div class="col-md-8 text-rigth totalImporte" style="font-size: 3em;">
				<?php echo (isset($Datostotales['total']) ? number_format ($Datostotales['total'],2, '.', '') : '');?>
			</div>
		</div>
	</div>
</div>
</form>
</div>
<?php // Incluimos paginas modales
echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
?>
</body>
</html>
