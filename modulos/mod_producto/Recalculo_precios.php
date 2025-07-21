<?php 
        include_once './../../inicial.php';
        include_once $URLCom.'/modulos/mod_producto/funciones.php';
        include_once $URLCom.'/controllers/Controladores.php';
        include_once $URLCom.'/modulos/mod_compras/clases/albaranesCompras.php';
		include_once $URLCom.'/clases/articulos.php';
		include_once $URLCom.'/clases/Proveedores.php';
		include_once ($URLCom.'/controllers/parametros.php');
		$Controler = new ControladorComun; 
		$Controler->loadDbtpv($BDTpv);
		$ClasesParametros = new ClaseParametros('parametros.xml');
		$parametros = $ClasesParametros->getRoot();
		$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
		$CProveedor=new Proveedores($BDTpv);
		$CAlbaran=new AlbaranesCompras($BDTpv);
		$CArticulo=new Articulos($BDTpv);
		$ruta_volver= $HostNombre.'/modulos/mod_compras/albaranesListado.php';
		$titulo="Recalculo precios PVP ";
        $comprobar="";
		$Usuario = $_SESSION['usuarioTpv'];
        $i=1;
        if (isset($_GET['id'])){
			$id=$_GET['id'];
			$dedonde="albaran";
			$subtitulo='de '.$dedonde.' :'.$id;
			$titulo=$titulo.' '.$subtitulo;
			$datosAlbaran=$CAlbaran->datosAlbaran($id);
			$fecha=date_create($datosAlbaran['Fecha']);
			$fecha=date_format($fecha, 'Y-m-d');
			$productosHistoricos=$CArticulo->historicoCompras($id, "albaran", "compras");
            $productosHistoricos=comprobarRecalculosSuperiores($productosHistoricos, $CArticulo );
			$datosProveedor=$CProveedor->buscarProveedorId($datosAlbaran['idProveedor']);
		}
		if (isset($_POST['Guardar'])){
			$id=$_GET['id'];
			$estado="";
			$fechaCreacion=date('Y-m-d');
            // Existe algun producto dentro de $productosHistoricos que tenga tipo peso en la tabla articulos
            $productosPeso = array();
            foreach ($productosHistoricos as $producto) {
                $tipo = $CArticulo->getTipoArticulo($producto['idArticulo']);
                // Simplificamos el array para asocia idArticulo con tipo
                $productosPeso[$producto['idArticulo']] = $tipo[$producto['idArticulo']];
            }
            // Si hay algún producto con tipo peso, se procede a la comunicación con la balanza
            // Buscar si algún producto tiene tipo 'peso'
            $hayPeso = false;
            foreach ($productosPeso as $tipo) {
                if ($tipo === 'peso') {
                $hayPeso = true;
                break;
                }
            }
            if ($hayPeso) {
                include_once $URLCom.'/modulos/mod_balanza/clases/ClaseComunicacionBalanza.php';
                include_once $URLCom.'/modulos/mod_balanza/clases/ClaseBalanza.php';
                $traductorBalanza = new ClaseComunicacionBalanza();
                $CBalanza = new ClaseBalanza($BDTpv);
                $balanzas = $CBalanza->obtenerBalanzasEnvio();
                $idBalanza = 2; // ID de la balanza a utilizar
                $ruta_balanza = '/balanza2';
                $salidaBalanza = []; // Array que almacena 1 salida por balanza
                // Mover al final a la traducción
                $traductorBalanza->setModoComunicacion('L'); // Modo de comunicación L!
                // asignar gupo 0 dirección 50 a la balanza
                $traductorBalanza->setGrupo(0);//!
                $traductorBalanza->setDireccion(50);//!
            }            
            foreach ($productosHistoricos as $producto){
				if ($producto['estado']=="Pendiente"){
					$idArticulo=$producto['idArticulo'];
					$pvpRecomendadoCiva=$_POST['pvpRecomendado_'.$i];
					$pvpRecomendadoCiva=(float)$pvpRecomendadoCiva;
					$datosArticulo=$CArticulo->datosPrincipalesArticulo($idArticulo);
					$datosPrecios=$CArticulo->articulosPrecio($idArticulo);
					$articuloPrecioAnt=$datosPrecios['pvpCiva'];
					if ($pvpRecomendadoCiva != $articuloPrecioAnt){
						$ivaPrecio=$datosArticulo['iva']/100;
						$ivaProducto=$producto['Nuevo']*$ivaPrecio;
						$precioProducto=$producto['Nuevo']+$ivaProducto;
						$beneficio=$datosArticulo['beneficio']/100;
						$beneficioArticulo=$precioProducto*$beneficio;
						$pvpRecomendado=$beneficioArticulo+$precioProducto;
						if ($pvpRecomendado<>$pvpRecomendadoCiva){
							$estado="A mano";
						}else{
							$estado="Recomendado";
						}
						$nuevoIva=1+$ivaPrecio;
						$nuevo=$pvpRecomendadoCiva/$nuevoIva;
						$nuevoSiva=number_format($nuevo,6);
						$nuevo=number_format($pvpRecomendadoCiva,2);
						$datosHistorico=array(
						'idArticulo'=>$idArticulo,
						'antes'=>$datosPrecios['pvpCiva'],
						'nuevo'=>$pvpRecomendadoCiva,
						'fechaCreacion'=>$fechaCreacion,
						'numDoc'=>$id,
						'dedonde'=>"Recalculo",
						'tipo'=>"Productos",
						'estado'=>$estado,
						'idUsuario'=>$Usuario['id']
						);
						$nuevoHistorico=$CArticulo->addHistorico($datosHistorico);	
						$modPrecios=$CArticulo->modArticulosPrecio($pvpRecomendadoCiva, $nuevoSiva, $idArticulo);
                        // Aqui se hacen las tareas para comunicar con la balanza
                        if ($productosPeso[$producto['idArticulo']] === 'peso') {
                            // Comprobar que balanza o balanzas tiene asociadas el producto
                            $balanzasProducto = $CBalanza->obtenerBalanzaPorIdArticulo($datosHistorico['idArticulo']);
                            foreach ($balanzas as &$balanzaExistente) {
                                $balanzaExistente['relacionada'] = false; // Inicialmente no relacionada
                                foreach ($balanzasProducto as $balanzaProducto) {
                                    if ($balanzaExistente['idBalanza'] == $balanzaProducto['idBalanza']) {
                                        $balanzaExistente['relacionada'] = true;
                                        break;
                                    }
                                    unset($balanzaExistente);
                                    break;
                                }
                            }
                            // Añadir balanzasProducto que no estén en $balanzas
                            foreach ($balanzasProducto as $balanzaProducto) {
                                $existe = false;
                                foreach ($balanzas as $balanzaExistente) {
                                    if ($balanzaExistente['idBalanza'] == $balanzaProducto['idBalanza']) {
                                        $existe = true;
                                        $balanzaExistente['relacionada'] = true;
                                        break;
                                    }
                                }
                                if (!$existe) {
                                    $balanzaProducto['relacionada'] = true;
                                    $balanzas[] = $balanzaProducto;
                                }
                            }
                            $balanzasUnicas = [];
                            foreach ($balanzas as $balanza) {
                                $balanzasUnicas[$balanza['idBalanza']] = $balanza;
                            }
                            $balanzas = array_values($balanzasUnicas);
                            $datosH2 = array(
                                'codigo' => $datosArticulo['crefTienda'],
                                'nombre' => $datosArticulo['articulo_name'],
                                'precio' => $pvpRecomendadoCiva,
                                'PLU' => '',
                            );
                            $datosH3 = array(
                                'codigo' => $producto['id'],
                                'tipoProducto' => $productosPeso[$producto['idArticulo']],
                                'iva' => $datosArticulo['iva'],
                                'seccion' => '',
                            );
                            // Para cada balanza traducimos los datos
                            foreach ($balanzas as $balanza) {
                                /*
                                [0] => Array
                                    (
                                        [idBalanza] => 1
                                        [nombreBalanza] => Carniceria DIGITAL SCALAS
                                        [modelo] => DM1Z
                                        [conSeccion] => no
                                        [Grupo] => 0
                                        [Dirección] => 50
                                        [IP] => 192.168.4.3
                                        [soloPLUS] => 0
                                        [relacionada] => 1
                                    )
                                */
                                // Asignamos dirección y grupo de la balanza
                                $traductorBalanza->setGrupo($balanza['Grupo']);
                                $traductorBalanza->setDireccion($balanza['Dirección']);
                                // Si la balanza tiene campos específicos para PLU o sección, se pueden ajustar aquí
                                // Si la balanza está relacionada, siempre añadir el PLU.
                                if ($balanza['relacionada']) {
                                    $relacion = $CBalanza->obtenerPluActual( $balanza['idBalanza'],$datosHistorico['idArticulo']);
                                    echo '<pre>';
                                    print_r($relacion);
                                    echo '</pre>';
                                    $datosH2['PLU'] = $relacion['plu'];
                                    // Si la balanza tiene sección (conSeccion == 'si'), añadir la sección.
                                    if (isset($balanza['conSeccion']) && strtolower($balanza['conSeccion']) === 'si') {
                                        $datosH3['seccion'] = $relacion['seccion'];
                                    }
                                } 
                                // Si la balanza no tiene sección se debe cambiar a modo de comunicación L
                                if (strtolower($balanza['conSeccion']) !== 'si') {
                                    $traductorBalanza->setModoComunicacion('L'); // Modo de comunicación L
                                } else {
                                    $traductorBalanza->setModoComunicacion('H'); // Modo de comunicación H
                                }
                                $traductorBalanza->setH2Data($datosH2);
                                $traductorBalanza->setH3Data($datosH3);
                                error_log('['.$_SESSION['usuarioTpv']['nombre'] . "] Datos a enviar a balanza (ID {$balanza['idBalanza']}): " . json_encode($datosH2) . json_encode($datosH3));
                                if (!isset($salidaBalanza[$balanza['idBalanza']])) {
                                    $salidaBalanza[$balanza['idBalanza']] = '';
                                }
                                $salidaBalanza[$balanza['idBalanza']] .= (string)$traductorBalanza->traducirH2();
                                $salidaBalanza[$balanza['idBalanza']] .= (string)$traductorBalanza->traducirH3();
 
                            }
                        }
 					}
				}
                if($producto['estado']=="Pendiente" || $producto['estado']=="Sin revisar" || $producto['estado']=="Sin Cambios"){
                    $i++;
                }
				$estado="";
			}
			$modificarHistorico=$CArticulo->modificarEstadosHistorico($id, $dedonde );
            if ($hayPeso) {
                foreach ($balanzas as $balanza) {
                    // Definimos la ruta de la balanza
                    $ruta_balanza = '/' . str_replace(' ', '', $balanza['nombreBalanza']) . $balanza['idBalanza'];
                    $directorioBalanza = $RutaServidor . $rutatmp . $ruta_balanza_actual;
                    $traductorBalanza->setRutaBalanza($ruta_balanza);

                    $directorioBalanza = $RutaServidor . $rutatmp . $ruta_balanza;
                    
                    $salida = $salidaBalanza[$balanza['idBalanza']];
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
                                'mensaje' => 'Error grave de Comunicación: Fallo al ejecutar el driver de la balanza (ID '.$balanza['idBalanza'].').',
                                'dato' => array()
                            );
                            error_log('Fallo al ejecutar el driver de la balanza (ID '.$balanza['idBalanza'].').');
                        } else {
                            $ComunicacionBalanza['Comprobaciones'][] = array(
                                'tipo' => 'success',
                                'mensaje' => 'Comunicación con la balanza (ID '.$balanza['idBalanza'].') realizada correctamente.',
                                'dato' => array($datosH2, $datosH3)
                            );
                        }
                    }
                }
            }
		}
		?>
<!DOCTYPE html>
<html>
	<head>
        <?php  include_once $URLCom.'/head.php'; ?>
		<script type="text/javascript">
        <?php echo $VarJS;?>
        </script>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/js/AccionesDirectas.js"></script>
		<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
	</head>
	<body>
        <?php
         //~ include_once $URLCom.'/header.php';
         include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
        <script type="text/javascript">
         function anular(e) {
              tecla = (document.all) ? e.keyCode : e.which;
              return (tecla != 13);
          }
          <?php 
          if (isset($_POST['Guardar'])){
            ?>
              mensajeImprimir(<?php echo $id;?>, <?php echo "'".$dedonde."'"; ?>);
            <?php
          }
          if (isset($_POST['Imprimir'])){
            ?>
             imprimir(<?php echo $id;?>, <?php echo "'".$dedonde."'"; ?>);
            <?php	
            }
          ?>
        </script>
		<div class="container">
			<h2 class="text-center"><?php echo $titulo;?></h2>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
                <div class="col-md-12">
                    <!-- De momento devolvemos a albaranes ya que es donde se hace recalculo
                         pero esto tendrá cambiar, ya que el recalculo se podrá acceder desde varios sitios -->
                    <a class="text-right" href="<?php echo $ruta_volver;?>">Volver Atrás</a>
                    <input type="submit" value="Guardar" name="Guardar" id="Guardar" onclick="">
                    <input type="submit" value="Imprimir" name="Imprimir" id="Imprimir" onclick="">
                </div>
                <div class="col-md-12">
                    <div class="col-md-2">
                        <strong>Fecha albarán:</strong><br>
                        <input type="date" name="fecha" id="fecha" size="10"   value="<?php echo $fecha;?>" readonly >
                    </div>
                    <div class="col-md-2">
                        <strong>ID Proveedor:</strong><br>
                        <input type="text" name="idProveedor" id="idProveedor" size="10"   value="<?php echo $datosAlbaran['idProveedor'];?>" readonly >
                    </div>
                    <div class="col-md-3">
                        <strong>Proveedor:</strong><br>
                    
                        <input type="text" name="nombreProveedor" id="nombreProveedor" size="10"   value="<?php echo $datosProveedor['nombrecomercial'];?>" readonly >
                    </div>
                    <div class="col-md-3">
                        <strong>Proveedor:</strong><br>
                        
                        <input type="text" name="razonsocial" id="razonsocial" size="10"   value="<?php echo $datosProveedor['razonsocial'];?>" readonly >
                    </div>
                    <div class="col-md-2">
                        <strong>Estado albarán:</strong><br>
                        <input type="text" name="estado" id="estado" size="10"   value="<?php echo $datosAlbaran['estado'];?>" readonly >
                    </div>
                </div>
                <div class="col-md-12">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>NOMBRE</th>
                                <th>REFERENCIA</th>
                                <th>COSTE ULTIMO</th>
                                <th>COSTE ANTERIOR</th>
                                <th>BENEFICIO</th>
                                <th>IVA</th>
                                <th>PVP ACTUAL</th>
                                <th>PVP RECOMENDADO</th>
                                <th>ELIMINAR</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $i=1;
                        foreach ($productosHistoricos as $producto){
                            if ($producto['estado']<>"Revisado"){
                                $datosArticulo=$CArticulo->datosPrincipalesArticulo($producto['idArticulo']);
                                $datosPrecios=$CArticulo->articulosPrecio($producto['idArticulo']);
                                $datosArticuloProveedor=$CArticulo->buscarReferencia($producto['idArticulo'], $datosAlbaran['idProveedor']);
                                $ivaPrecio=$datosArticulo['iva']/100;
                                $ivaProducto=$producto['Nuevo']*$ivaPrecio;
                                $precioProducto=$producto['Nuevo']+$ivaProducto;
                                $beneficio=$datosArticulo['beneficio']/100;
                                $beneficioArticulo=$precioProducto*$beneficio;
                                $pvpRecomendado=$beneficioArticulo+$precioProducto;
                                
                                if ($producto['estado']=="Pendiente" || $producto['estado']=="Sin revisar"){
                                    $class="";
                                }else{
                                    $class="class='tachado'";
                                }
                                echo '<tr id="Row'.$i.'" '.$class.'>';
                                echo '<td>'.$producto['idArticulo'].'</td>';
                                echo '<td>'.$datosArticulo['articulo_name'].'</td>';
                                echo '<td>'.$datosArticuloProveedor['crefProveedor'].'</td>';
                                echo '<td>'.$producto['Nuevo'].'</td>';
                                echo '<td>'.$producto['Antes'].'</td>';
                                echo '<td>'.$datosArticulo['beneficio'].'</td>';
                                echo '<td>'.$datosArticulo['iva'].'</td>';
                                echo '<td>'.number_format($datosPrecios['pvpCiva'],4).'</td>';
                                if($producto['estado']=="Sin revisar"){
                                      echo '<td><input type="text" id="pvpRecomendado_'.$i.'" name="pvpRecomendado_'.$i.'"  
                                      onkeydown="controlEventos(event)" data-obj="pvpRecomendado" 
                                      value="'.number_format($pvpRecomendado,2).'" size="5" disabled>
                                      <span class="glyphicon glyphicon-ban-circle" style="color:red"  title="Este producto tiene recalculos de precio posteriores"></span>
                                      </td>';
                                }else{
                                    echo '<td><input type="text" id="pvpRecomendado_'.$i.'" name="pvpRecomendado_'.$i.'"  onkeydown="controlEventos(event)" data-obj="pvpRecomendado" value="'.number_format($pvpRecomendado,2).'" size="5"></td>';
                                }
                                if($producto['estado']=="Sin revisar"){
                                    echo '<td></td>';
                                }else{
                                    if ($producto['estado']=="Pendiente" ){
                                        echo '<td class="eliminar"><a onclick="eliminarCoste('.$producto['idArticulo'].', '."'".$dedonde."'".', '.$id.', '."'".'compras'."'".', '.$i.')"><span class="glyphicon glyphicon-trash"></span></a></td>';
                                    }else{
                                        echo '<td class="eliminar"><a onclick="retornarCoste('.$producto['idArticulo'].', '."'".$dedonde."'".', '.$id.', '."'".'compras'."'".', '.$i.')"><span class="glyphicon glyphicon-export"></span></a></td>';
                                    }
                                }
                                echo '</tr>';
                                $i++;
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </form>
		</div>
	</body>	

<?php



        
?>
</html>
