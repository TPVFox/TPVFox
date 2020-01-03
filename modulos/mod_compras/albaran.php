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
	include_once $URLCom.'/modulos/mod_compras/clases/pedidosCompras.php';
	include_once ($URLCom.'/controllers/parametros.php');
	//cargar las clases necesarias
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$Cproveedor=new Proveedores($BDTpv);
	$CAlb=new AlbaranesCompras($BDTpv);
	$Cped = new PedidosCompras($BDTpv);
	$Controler = new ControladorComun; 
	$Controler->loadDbtpv($BDTpv);
	//Inicializar las variables
	$dedonde="albaran";
	$titulo="Albarán de proveedor: ";
	// Valores por defecto de estado y accion.
    // [estado] -> Nuevo,Sin Guardar,Guardado,Facturado.
    // [accion] -> editar,ver
    $estado='Nuevo';
    // Si existe accion, variable es $accion , sino es "editar"
    $accion = (isset($_GET['accion']))? $_GET['accion'] : 'editar';
	$fecha=date('d-m-Y');
	$hora="";
	$idAlbaranTemporal=0;
	$idAlbaran=0;
	$idProveedor="";
    $formaPago= 0;
	$suNumero="";
	$nombreProveedor="";
	$fechaVencimiento="";
	$Datostotales=array();
	$inciden=0;
	$errores = array();
	//Cargamos la configuración por defecto y las acciones de las cajas 
	$parametros = $ClasesParametros->getRoot();	
	foreach($parametros->cajas_input->caja_input as $caja){
        // Ahora cambiamos el parametros por defecto que tiene dedonde = pedido y le ponemos albaran
		$caja->parametros->parametro[0]="albaran";
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
	if (isset($_GET['id']) || isset($_GET['tActual'])) {
        // Si existe id o tActual es que no es nuevo
        if (isset($_GET['id'])){
            // Si exite id estamos modificando directamente un albaran.
            // Deberíamos comprobar que no exista ningun temporal....

            // Obtenemos la incidencias, por si había.
            $incidenciasAdjuntas=incidenciasAdjuntas($idAlbaran, "mod_compras", $BDTpv, "albaran");
            $inciden=count($incidenciasAdjuntas['datos']);
            $datosAlbaran = $CAlb->GetAlbaran($_GET['id']);
            if (isset($datosAlbaran['error'])){
                $errores=$datosAlbaran['error'];
            } else {
                if(isset($datosAlbaran['estado']) ){
                    $estado=$datosAlbaran['estado'];
                    $idAlbaran = $datosAlbaran['id'];
                    if ($datosAlbaran['estado']=="Facturado"){
                        // Cambiamos accion, ya que solo puede ser ver.
                        $accion = 'ver';
                        // Obtenemos los datos de factura.
                        $numFactura=$CAlb->NumfacturaDeAlbaran($idAlbaran);
                        if(isset($numFactura['error'])){
                            array_push($errores,$this->montarAdvertencia(
                                            'danger',
                                            'Error 1.1 en base datos.Consulta:'.json_encode($numFactura['consulta'])
                                    )
                            );
                        }
                    }
                }
            }
        }

        if (isset($_GET['tActual'])){
            // Viene de albaran temporal, o esta editando y recargo mientras editamos.¡
            $idAlbaranTemporal=$_GET['tActual'];
            $datosAlbaran=$CAlb->buscarAlbaranTemporal($idAlbaranTemporal);
            if (isset($datosAlbaran['error'])){
                    array_push($errores,$this->montarAdvertencia(
                                    'danger',
                                    'Error 1.1 en base datos.Consulta:'.json_encode($datosAlbaran['consulta'])
                            )
                    );
            } else {
                // Preparamos datos que no viene o que vienen distintos cuando es un temporal.
                $datosAlbaran['FechaVencimiento'] ='0000-00-00';
                $datosAlbaran['Productos'] = json_decode($datosAlbaran['Productos'],true);
            }
        }
    }
    if (count($errores) == 0){
        // Si no hay errores graves continuamos.
        if (!isset($datosAlbaran)){
            // Es que nuevo.
            $datosAlbaran = array();
            $datosAlbaran['Fecha']="0000-00-00 00:00:00";
            $datosAlbaran['Su_numero'] = '';
            $datosAlbaran['idProveedor'] = 0;
        } else {
            // Si no es nuevo
            $idProveedor=$datosAlbaran['idProveedor'];
            $proveedor=$Cproveedor->buscarProveedorId($idProveedor);
            $nombreProveedor=$proveedor['nombrecomercial'];
            $productos =$datosAlbaran['Productos'];
            $fecha = ($datosAlbaran['Fecha']=="0000-00-00 00:00:00")
                                ? date('d-m-Y'):date_format(date_create($datosAlbaran['Fecha']),'d-m-Y');
            $hora=date_format(date_create($datosAlbaran['Fecha']),'H:i');
            // Un albaran puede tener ser generado por varios pedidos del mismo proveedor.
            // por ello podemos obtener un array de arrays.
            $pe = $CAlb->PedidosAlbaranes($idAlbaran,'OK');
            
            $datosAlbaran['Pedidos'] = '';
            if (count($pe) >0){
                $datosAlbaran['Pedidos'] = $pe;
            }
            $formaPago=(isset($datosAlbaran['formaPago']))? $datosAlbaran['formaPago'] : 0;
            $fechaVencimiento=$datosAlbaran['FechaVencimiento'];
            if (isset ($datosAlbaran['Numalbpro'])){
                $d=$CAlb->buscarAlbaranNumero($datosAlbaran['Numalbpro']);
                $idAlbaran=$d['id'];
            }
            if ($datosAlbaran['Su_numero']!==""){
                $suNumero=$datosAlbaran['Su_numero'];
            }
        }
        
        $textoFormaPago=htmlFormasVenci($formaPago, $BDTpv); // Generamos ya html.
        if(isset($datosAlbaran['Productos'])){
			// Obtenemos los datos totales ;
			// convertimos el objeto productos en array
            $p = (object)$productos;
            $Datostotales = $CAlb->recalculoTotales($p);
        }
        
    }
	if (isset($_POST['Guardar'])){
		//@Objetivo:
        // Enviamos los datos de guardarAlbaran , si el resultado 0 ,
        // todo fue OK , pero sino mostramos el error.
        if ($_POST['fechaVenci'] === ''){
            $_POST['fechaVenci'] = '0000-00-00';
        }
        $guardar=guardarAlbaran($_POST, $_GET, $BDTpv, $Datostotales);
		if (count($guardar)==0){
			header('Location: albaranesListado.php');
		}else{
			foreach ($guardar as $error){
				echo '<div class="'.$error['class'].'">'
				. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br> '.$error['dato']
				. '</div>';
			}
		}
	}
    // ============                 Montamos el titulo                      ==================== //
    $html_facturado='';
    if(isset($numFactura)){
        $html_facturado = ' <span style="font-size: 0.55em;vertical-align: middle;" class="label label-default">';
        $html_facturado .= 'factura:'.$numFactura['idFactura'];
        $html_facturado .='</span>';
    }
    $titulo .= ' '.$idAlbaran.$html_facturado.' - '.$accion;
    // ============= Creamos variables de estilos para cada estado y accion =================== //
    $estilos = array ( 'readonly'       => '',
                       'styleNo'        => 'style="display:none;',
                       'pro_readonly'   => '',
                       'pro_styleNo'    => '',
                       'btn_guardar'    => '',
                       'btn_cancelar'   => '',
                       'input_factur'   => '',
                       'select_factur'  => ''
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
        $estilos['input_factur'] = ' readonly';
        $estilos['select_factur'] = 'disabled="true"';       
    }
    if ($idAlbaranTemporal === 0){
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
		cabecera['idUsuario'] = <?php echo $Usuario['id'];?>; // Tuve que adelantar la carga, sino funcionaria js.
		cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
		cabecera['estado'] ='<?php echo $estado ;?>'; // Si no hay datos GET es 'Nuevo'
		cabecera['idTemporal'] = <?php echo $idAlbaranTemporal ;?>;
		cabecera['idReal'] = <?php echo $idAlbaran ;?>;
		cabecera['fecha'] = '<?php echo $fecha;?>';
		cabecera['hora'] = '<?php echo $hora;?>';
		cabecera['idProveedor'] ='<?php echo $idProveedor;?>';
		cabecera['suNumero']='<?php echo $suNumero; ?>';
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array
	var pedidos =[];
<?php 
	if (isset($albaranTemporal)|| isset($idAlbaran)){ 
	$i= 0;
		if (isset($productos)){
			foreach($productos as $product){
?>	
				datos=<?php echo json_encode($product); ?>;
				productos.push(datos);
<?php 
		// cambiamos estado y cantidad de producto creado si fuera necesario.
				if ($product['estado'] !== 'Activo'){
				?>	productos[<?php echo $i;?>].estado=<?php echo'"'.$product['estado'].'"';?>;
				<?php
				}
				$i++;
			}
		}
		if (isset ($datosAlbaran['Pedidos'])){
			if (is_array($datosAlbaran['Pedidos']) && count($datosAlbaran['Pedidos'])>0){
                // Si es un array y tiene datos
                $i = 0;
				foreach ($datosAlbaran['Pedidos'] as $key=>$pedi ){
                    // Ahora tengo añadir cada pedido el estado,Numpedpro
                    // por contabilidad con version anterior y lo utilizamos en htmllineaadjunto
                    $i ++;
                    $datosAlbaran['Pedidos'][$key]['NumAdjunto'] = $pedi['numPedido'];
                    $datosAlbaran['Pedidos'][$key]['nfila'] = $i;
                    // Estado no tiene nada que ver con el estado pedido, ya que se entiende que el estado puede ser
                    // Guardado , Sin guardar o Facturado, pero este estado no referimos a si esta añadido o no.
                    $datosAlbaran['Pedidos'][$key]['estado'] = 'activo';
					?>
					datos=<?php echo json_encode($datosAlbaran['Pedidos'][$key]);?>;
					pedidos.push(datos);
					<?php
				}
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
		 mensajeCancelar(<?php echo $idAlbaranTemporal;?>, <?php echo "'".$dedonde."'"; ?>);
		  <?php
	  }
	  ?>
<?php echo $VarJS;?>
     function anular(e) {
          tecla = (document.all) ? e.keyCode : e.which;
          return (tecla != 13);
      }
</script>
<div class="container">
	<?php
	if (isset($errores)){
		foreach($errores as $error){
				echo '<div class="'.$error['class'].'">'
				. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br>Sentencia: '.$error['dato']
				. '</div>';
		}
	}
	?>
    <form action="" method="post" name="formProducto" onkeypress="return anular(event)">
    <?php 
    echo '<h3 class="text-center">'.$titulo;
    if ($accion !=='ver'){
    echo ' temporal:'.'<input type="text" readonly size ="4" name="idTemporal" value="'.$idAlbaranTemporal.'">';
    }
    echo '</h3>';  
    ?>
    <div class="col-md-12">
        <div class="col-md-8" >
            <a href="./albaranesListado.php">Volver Atrás</a>
            <?php
            // Botones de incidencias.
            if($idAlbaran>0){
                echo '<input class="btn btn-warning" size="12" onclick="abrirModalIndicencia('."'".$dedonde
                    ."'".' , configuracion, 0 ,'.$idAlbaran
                    .');" value="Añadir incidencia " name="addIncidencia" id="addIncidencia">';
            }
            if($inciden>0){
                echo ' <input class="btn btn-info" size="15" onclick="abrirIncidenciasAdjuntas('
                    .$idAlbaran." ,'mod_compras', 'albaran'"
                    .')" value="Incidencias Adjuntas " name="incidenciasAdj" id="incidenciasAdj"> ';
            }
            if ($estado != "Facturado" || $accion != "ver"){
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
    <div class="row" >
        <div class="col-md-7">
            <div class="col-md-12">
                <input type="text" name="estado" id="estado" style="display:none;" value="<?php echo $estado;?>">
                    <div class="col-md-3">
                        <label>Fecha albarán:</label>
                        <?php
                            $pattern_numerico = ' pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" ';
                            $title_fecha =' placeholder="dd-mm-yyyy" title " Formato de entrada dd-mm-yyyy"';
                            echo '<input type="text" name="fecha" id="fecha" size="8" data-obj= "cajaFecha" '
                                . $estilos['input_factur'].' value="'.$fecha.'" onkeydown="controlEventos(event)" '
                                . $pattern_numerico.$title_fecha.'>';
                        ?>
                    </div>
                    <div class="col-md-3">
                        <label>Hora de entrega:</label>
                        <?php
                            echo '<input type="time" id="hora" '.$estilos['input_factur'].' value="'.$hora.'" '
                                .' data-obj= "cajaHora" onkeydown="controlEventos(event)"  name="hora" size="5"'
                                .' max="24:00" min="00:00" '
                                . $pattern_numerico.' placeholder="HH:MM" title=" Formato de entrada HH:MM">';
                        ?>
                    </div>
                    <div class="col-md-3">
                        <label>Estado:</label>
                        <input type="text" id="estado" name="estado" size="9" value="<?php echo $estado;?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label>Empleado:</label>
                        <input type="text" id="Usuario" name="Usuario" value="<?php echo $Usuario['nombre'];?>" size="8" readonly>
                    </div>
            </div>
            <div class="col-md-12">
                <div class="col-md-4">
                    <label>Su número:</label>
                    <input type="text" id="suNumero" name="suNumero" value="<?php echo $suNumero;?>" size="10" onkeydown="controlEventos(event)" data-obj= "CajaSuNumero" <?php echo $estilos['input_factur'];?>>
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
            <div class="form-group">
                <label>Proveedor:</label>
                <?php
                echo '<input type="text" id="id_proveedor" name="id_proveedor" data-obj= "cajaIdProveedor" value="'
                    .$idProveedor.'" '.$estilos['pro_readonly'].' size="2" onkeydown="controlEventos(event)" placeholder="id">';
                echo '<input type="text" id="Proveedor" name="Proveedor" data-obj= "cajaProveedor" '
                    .'placeholder="Nombre de proveedor" onkeydown="controlEventos(event)" value="'
                    .$nombreProveedor.'" '.$estilos['pro_readonly'].' size="60" >';
                echo '<a id="buscar" '.$estilos['pro_styleNo'].' class="glyphicon glyphicon-search buscar"'
                    .'onclick="buscarProveedor('."'".'albaran'."'".',Proveedor.value)"></a>';
                ?>

            </div>
        </div>
        <div class="col-md-5 div_adjunto">
            <label id="numPedidoT">Número del pedido:</label>
            <input type="text" id="numPedido" name="numPedido" value="" size="5" placeholder='Num' data-obj= "numPedido" onkeydown="controlEventos(event)" <?php echo $estilos['input_factur'];?>>
            <a id="buscarPedido" class="glyphicon glyphicon-search buscar" onclick="buscarAdjunto('albaran')"></a>
            <table class="col-md-12" id="tablaPedidos"> 
                <thead>
                    <td><b>Número</b></td>
                    <td><b>Fecha</b></td>
                    <td><b>Total</b></td>
                </thead>
                <?php 
                if (isset($datosAlbaran['Pedidos'])){
                    if (is_array($datosAlbaran['Pedidos'])){
                        foreach ($datosAlbaran['Pedidos'] as $pedido){
                            
                            $html=lineaAdjunto($pedido, "albaran");
                            echo $html['html'];
                        }
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
                    <th>Num Pedido</th>
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
                        foreach (array_reverse($productos) as $producto){
                            $html=htmlLineaProducto($producto, "albaran",$estilos['readonly']);
                            echo $html['html'];
                        }
                    }
                    ?>
                </tbody>
              </table>
        </div>
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
                if (isset ($Datostotales)){
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
