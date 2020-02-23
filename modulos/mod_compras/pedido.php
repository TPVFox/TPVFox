<!DOCTYPE html>
<html>
<head>
<?php
	//llamadas  a archivos php 
	include_once './../../inicial.php';
	include_once $URLCom.'/head.php';
	include_once $URLCom.'/modulos/mod_compras/funciones.php';
	include_once $URLCom.'/controllers/Controladores.php';
	include_once $URLCom.'/modulos/mod_compras/clases/pedidosCompras.php';
	include_once $URLCom.'/clases/Proveedores.php';
	include_once ($URLCom.'/controllers/parametros.php');
	//Carga de clases necesarias
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$Cpedido=new PedidosCompras($BDTpv);
	$Cproveedor=new Proveedores($BDTpv);
	$Controler = new ControladorComun; 
	$Controler->loadDbtpv($BDTpv);
	// Valores por defecto de variables
	$titulo="Pedido de Proveedor:";
	$dedonde="pedidos";
	$fecha=date('d-m-Y');
	$idPedido=0;
	$numPedidoTemp=0;
	$idProveedor='';
	$nombreProveedor='';
	$Datostotales=array();
    $errores = array();
	$inciden=0;
    // Valores por defecto de estado y accion.
    // [estado] -> Nuevo,Sin Guardar,Guardado,Facturado.
    // [accion] -> editar,ver
    $estado='Nuevo';
    // Si existe accion, variable es $accion , sino es "editar"
    $accion = (isset($_GET['accion']))? $_GET['accion'] : 'editar';
	//Carga de los parametros de configuración y las acciones de las cajas
	$parametros = $ClasesParametros->getRoot();
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
    //  [id] cuando editamos o vemos un pedido pulsando en listado.
    //  [tActual] cuando pulsamos en cuadro pedidos temporales.
    //  [accion] cuando indicamos que accion vamos hacer.
    if (isset($_GET['id'])){
        $idPedido=$_GET['id'];  // Id real de pedido
    }
    if (isset($_GET['tActual'])){
        $numPedidoTemp=$_GET['tActual']; // Id de pedido temporal
    }
    // ---------- Posible errores o advertencias mostrar     ------------------- //
    if ($idPedido > 0){
        // Comprobamos cuantos temporales tiene idPedido y si tiene uno obtenemos el numero.
        $c = $Cpedido->comprobarTemporalIdPedpro($idPedido);
        if (isset($c['idTemporal']) && $c['idTemporal'] !== NULL){
            // Existe un temporal de este pedido por lo que cargo ese temporal.
            $numPedidoTemp = $c['idTemporal'];
            $idPedido = 0 ; // Lo pongo en 0 para ejecute la parte temporal
            $_GET['tActual'] = $numPedidoTemp;
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
    if ( $idPedido > 0 && count($errores) === 0){
        // Si idPedido es 0, quiere decir que existe un temporal de $GET['id'] por lo que no entro aquí
        $datosPedido=$Cpedido->DatosPedido($idPedido);
        if (isset($datosPedido['error'])){
                array_push($errores,$this->montarAdvertencia(
                                'danger',
                                'Error 1.1 en base datos.Consulta:'.json_encode($datosAlbaran['consulta'])
                        )
                );
        } else {
            $datosPedido['Productos'] = $Cpedido->ProductosPedidos($idPedido);
            $estado=$datosPedido['estado'];
            if ($estado=='Facturado'){
                $accion = 'ver'; // Con estado facturado la accion es solo ver.
                // Obtenemos el numero albaran que tiene este pedido.
                $Albaran_creado = $Cpedido->NumAlbaranDePedido($idPedido);
            } 
            $fecha =date_format(date_create($datosPedido['FechaPedido']), 'd-m-Y');
            $productos=modificarArrayProductos($datosPedido['Productos']);
            // Obtenemos la incidencias si hay.
            $incidenciasAdjuntas=incidenciasAdjuntas($idPedido, "mod_compras", $BDTpv, "pedidos");
            $inciden=count($incidenciasAdjuntas['datos']);
        }
    }
    if ( $numPedidoTemp >0 && count($errores) === 0){           
        $datosPedido=$Cpedido->DatosTemporal($numPedidoTemp);
        if (isset($datosPedido['idPedpro'])){
            $idPedido=$datosPedido['idPedpro'];	
            // Si $idPedido >0 compruebo que no existan mas pedidotemporales de ese pedido para evitar errores.
            if ($idPedido > 0){
                $c = $Cpedido->comprobarTemporalIdPedpro($idPedido);
                if (isset($c['idTemporal'])){
                    // Existe un temporal de este pedido por lo que cargo ese temporal.
                    if ($_GET['tActual'] !== $c['idTemporal']){
                        // Hay un error grabe.
                        echo 'Error grabe';
                        exit();
                    }
                } else {
                    if (count($c)>0){
                         $errores= $c;
                    }
                }
            }
        }
        if ( count($errores) === 0) {
            $estado=$datosPedido['estadoPedPro'];         
            if ($datosPedido['fechaInicio']){
                $bandera=new DateTime($datosPedido['fechaInicio']);
                $fecha=$bandera->format('d-m-Y');
            }
            $productos = json_decode( $datosPedido['Productos'],true); // Array de objetos
            $datosPedido['Productos'] = $productos;
        }
    }
    if (isset ($datosPedido['idProveedor']) && $datosPedido['idProveedor'] > 0){
        //  Obtenemos los datos del proveedor:
        $idProveedor=$datosPedido['idProveedor'];
        $datosProveedor=$Cproveedor->buscarProveedorId($idProveedor);
        $nombreProveedor=$datosProveedor['nombrecomercial'];
    }
    if(isset($datosPedido['Productos'])){
        // Obtenemos los datos totales;
        // convertimos el objeto productos en array
        $Datostotales = $Cpedido->recalculoTotales($productos);
    }
    
    //  ---------  Control y procesos para guardar el pedido. ------------------ //
    if (isset($_POST['Guardar']) && count($errores)===0){
        // Cuando el estado es pedido que recibimos por POST es "Guardado"
        // puede ser que no modificará nada o que exista un temporal, recien creado.
        // lo compruebo.
        $guardar = $Cpedido->guardarPedido();
        if (!isset($guardar['errores']) || count($guardar['errores'])===0){
                // Fue todo correcto.
                // Aunque si hubiera errores o advertencias nunca lo mostraría ya que redirecciono directamente.
                header('Location: pedidosListado.php');
        } else {
            if (isset($guardar['errores']) || is_array($guardar['errores'])){
                $errores = $guardar['errores'];
            }
            if (isset($guardar['id_guardo'])){
                // Hay que indicar que se guardo, aunque hay errores.
                array_push($errores,$Cpedido->montarAdvertencia('warning',
                                    '<strong>Se guardo el id:'.$guardar['id_guardar'].' </strong>  <br>'
                                    .'Ojo que puede generar un duplicado'
                                    )
                        );
            }
            if (isset($guardar['modPedido'])){
                // Se modifico todo o algo, pero hubo un error.
                array_push($errores,$Cpedido->montarAdvertencia('warning',
                                    '<strong>Se modifico algo pero hubo un error.</strong><br/>'
                                    .'Ojo que puede generar un duplicado'
                                    .json_encode($guardar['modPedido'])
                                    )
                        );
            }
        }
    }
    $htmlIvas=htmlTotales($Datostotales);
    // ============          Otros controles posibles errores               ==================== //
    // Controlamos que el estado sea uno de los tres posibles.
    $posibles_estados = array ('Sin Guardar','Guardado','Nuevo','Facturado');
    if (!in_array($estado, $posibles_estados)){
        // No existe ese estado.
        array_push($errores,$Cpedido->montarAdvertencia('warning',
                                    '<strong>El estado que tiene no es corrrecto.</strong><br/>'
                                    .'El estado:'.$estado.' no existe en los posibles estados para un pedido.'
                                    )
                        );
    }
    
    // ============                 Montamos el titulo                      ==================== //
    $html_albaran='';
    if(isset($Albaran_creado)){
        $html_albaran = ' <span style="font-size: 0.55em;vertical-align: middle;" class="label label-default">';
        $html_albaran .= 'albaran:'.$Albaran_creado['numAlbaran'];
        $html_albaran .='</span>';
    }
    // Añadimos al titulo el estado
	$titulo .= ' '.$idPedido.$html_albaran.' - '.$accion;
    // ============= Creamos variables de estilos para cada estado y accion =================== //
    $estilos = array ( 'readonly'       => '',
                       'styleNo'        => 'style="display:none;"',
                       'pro_readonly'   => '',
                       'pro_styleNo'    => '',
                       'btn_guardar'    => '',
                       'btn_cancelar'   => ''
                    );
    if (isset ($_GET['id']) || isset ($_GET['tActual'])){
        // Quiere decir que ya inicio , ya tuvo que meter proveedor.
        // no se permite cambiar proveedor.
        $estilos['pro_readonly']   = ' readonly';
        $estilos['pro_styleNo']    = ' style="display:none;"';
        $estilos['styleNo']    = '';

    }
    if ($accion === 'ver'){
        $estilos['readonly']   = ' readonly';
        $estilos['styleNo']     = ' style="display:none;"';
    }
    if ($numPedidoTemp === 0){
        // Solo se muestra cuando el numPedidoTemp es 0
        $estilos['btn_guardar'] = 'style="display:none;"';
        // Una vez se cree temporal, con javascript se quita style
    }
    
?>

<script type="text/javascript">
    <?php
    // Esta variable global la necesita para montar la lineas.
	// En configuracion podemos definir SI / NO
	echo 'var configuracion='.json_encode($configuracionArchivo).';';
    ?>	
	var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
		cabecera['idUsuario'] = <?php echo $Usuario['id'];?>; // Tuve que adelantar la carga, sino funcionaria js.
		cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
		cabecera['estado'] ='<?php echo $estado ;?>'; 
		cabecera['idTemporal'] = <?php echo $numPedidoTemp ;?>;
		cabecera['idReal'] = <?php echo $idPedido ;?>;
		cabecera['idProveedor']='<?php echo $idProveedor ;?>';
		cabecera['fecha']='<?php echo $fecha;?>';
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array
	<?php 
	$i= 0;
	if (isset($productos)){
		if ($productos){
			foreach($productos as $product){
	?>
			datos=<?php echo json_encode($product); ?>;
			productos.push(datos);
	<?php //cambiamos estado y cantidad de producto creado si fuera necesario.
				if (isset ($product->estado)){
					if ($product['estado'] !== 'Activo'){
					?>
                        productos[<?php echo $i;?>].estado=<?php echo'"'.$product['estado'].'"';?>;
					<?php
					}
				}
				$i++;
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
        mensajeCancelar(<?php echo $numPedidoTemp;?>, <?php echo "'".$dedonde."'"; ?>);
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
            echo $Cpedido->montarAdvertencia($comprobaciones['tipo'],$comprobaciones['mensaje'],'OK');
            if ($comprobaciones['tipo'] === 'danger'){
                exit; // No continuo.
            }
        }
    }
    ?>
	<form class="form-group" action="" method="post" name="formProducto" onkeypress="return anular(event)">
        <h3 class="text-center">
        <?php
        echo $titulo;
        // Se debe imprimir siempre el pedido para que no se repita.
        echo  ' temporal:<input  readonly size="4" type="text" name="idTemporal" value='.$numPedidoTemp.'>';
        ?>    
        </h3>
		<div class="col-md-12">
			<div class="col-md-8" >
                <?php echo $Controler->getHtmlLinkVolver('Volver');
                if($idPedido>0){
                    echo '<input class="btn btn-warning" size="12" 
                    onclick="abrirModalIndicencia('."'".$dedonde."'".' , configuracion, 0, '.$idPedido.');" 
                    value="Añadir incidencia " name="addIncidencia" id="addIncidencia">';
                }
                if($inciden>0){
                   echo '<input class="btn btn-info" size="15" 
                   onclick="abrirIncidenciasAdjuntas('.$idPedido.', '."'".'mod_compras'."'".', '."'".'pedidos'."'".')"
                   value="Incidencias Adjuntas " name="incidenciasAdj" id="incidenciasAdj">';
                }
                if ($estado != "Facturado" && $accion != "ver"){
                    // El btn guardar solo se crea si el estado es "Nuevo","Sin Guardar","Guardado"
                    echo '<input class="btn btn-primary" '.$estilos['btn_guardar']
                            .' type="submit" value="Guardar" name="Guardar" id="bGuardar">';
                }
                ?>
			</div>
            <div class="col-md-4 text-right" >
            <?php
            if ($estado != "Facturado" || $accion != "ver"){?>
                <span class="glyphicon glyphicon-cog" title="Escoje casilla de salto"></span>
                <?php echo htmlSelectConfiguracionSalto();
                // El btn cancelar solo se crea si el estado es "Nuevo"
                // pero solo se muestra cuando hay un temporal, ya que no tiene sentido mostrarlo si no hay temporal
                if ($estado != "Nuevo"){
                    $estilos['btn_cancelar'] = 'style="display:none;"';
                    // Se cambia con javascript cuando creamos el temporal y el estado es Nuevo.
                }
                echo '<input type="submit" class="btn btn-danger"'
                    .$estilos['btn_cancelar']. 'value="Cancelar" name="Cancelar" id="bCancelar">';
            }
            ?>
            </div>
           
		</div>
	<div class="col-md-8">
			<div class="col-md-3">
				<label>Estado:</label>
				<input type="text" id="estado" name="estado" value="<?php echo $estado;?>" readonly>
			</div>
			<div class="col-md-3">
				<label>Usuario:</label>
				<input type="text" id="Usuario" name="Usuario" value="<?php echo $Usuario['nombre'];?>" size="13" readonly>
			</div>
			<div class="col-md-3">
				<label>Fecha Pedido:</label>
				<input type="text" name="fecha" id="fecha" data-obj= "cajaFecha"  value=<?php echo '"'.$fecha.'"'.' ';?> onkeydown="controlEventos(event)" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" placeholder='dd-mm-yyyy' title=" Formato de entrada dd-mm-yyyy">
			</div>
		<div class="col-md-12">
			<label>Proveedor:</label>
            <?php
                echo '<input type="text" id="id_proveedor" name="id_proveedor" data-obj= "cajaIdProveedor" value="'
                    .$idProveedor.'" '.$estilos['pro_readonly'].' size="2" onkeydown="controlEventos(event)">';
                echo '<input type="text" id="Proveedor" name="Proveedor" data-obj= "cajaProveedor" '
                    .'placeholder="Nombre de proveedor" onkeydown="controlEventos(event)" value="'
                    .$nombreProveedor.'" '.$estilos['pro_readonly'].' size="60" >';
                echo '<a id="buscar" '.$estilos['pro_styleNo'].' class="glyphicon glyphicon-search buscar"'
                    .'onclick="buscarProveedor('."'".'pedidos'."'".')"></a>';
            ?>
		</div>
	</div>
	<!-- Tabla de lineas de productos -->
	<div class="row">
		<table id="tabla" class="table table-striped" >
			<thead>
            <tr>
				<th>L</th>
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
            <tr id="Row0"<?php echo $estilos['styleNo'];?>>  
                <td id="C0_Linea" > </td>
				<td class="algo"><input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo" data-obj= "cajaidArticulo" size="4" value=""  onkeydown="controlEventos(event)"></td>
				<td><input id="Referencia" type="text" name="Referencia" placeholder="Referencia" data-obj="cajaReferencia" size="8" value="" onkeydown="controlEventos(event)"></td>
				<td><input id="ReferenciaPro" type="text" name="ReferenciaPro" placeholder="Referencia" data-obj="cajaReferenciaPro" size="10" value="" onkeydown="controlEventos(event)"></td>
				<td><input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras" data-obj= "cajaCodBarras" size="12" value="" data-objeto="cajaCodBarras" onkeydown="controlEventos(event)"></td>
				<td><input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion" data-obj="cajaDescripcion" size="17" value="" onkeydown="controlEventos(event)"></td>
            </tr>
			</thead>
			<tbody>
				<?php 
				if (isset($productos)){
					foreach (array_reverse($productos) as $producto){
						$h=htmlLineaProducto($producto, "pedidos",$estilos['readonly']);
						echo $h['html'];
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
			echo $htmlIvas['html']; ?>
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
</form>
</div>
<?php // Incluimos paginas modales
 echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
?>
</body>
</html>
