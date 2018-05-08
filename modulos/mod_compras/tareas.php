<?php 

/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *  */
$pulsado = $_POST['pulsado'];
include_once ("./../../configuracion.php");
include_once ("./../mod_conexion/conexionBaseDatos.php");
include_once ("./funciones.php");
include_once ("../mod_incidencias/popup_incidencias.php");
include_once "clases/pedidosCompras.php";
include_once '../../clases/Proveedores.php';
include_once "clases/albaranesCompras.php";
include_once "clases/facturasCompras.php";
include_once "../../clases/articulos.php";

$CProveedores=new Proveedores($BDTpv);
$CPed=new PedidosCompras($BDTpv);
$CAlb=new AlbaranesCompras($BDTpv);
$CFac= new FacturasCompras($BDTpv);
$CArticulos=new Articulos($BDTpv);
switch ($pulsado) {
	case 'buscarProveedor':
		include ('tareas/buscarProveedor.php');
	break;
	case 'buscarProductos':
		include ('tareas/buscarProducto.php');
	break;	
	
	case 'htmlAgregarFilasProductos':
	//@objetivo:
	//Agrega la fila de productos : crea las filas de los productos para posteriormente insertar en la tabla 
			$productos=$_POST['productos'];
			$dedonde=$_POST['dedonde'];
			$respuesta=array('html'=>"");
			foreach($productos as $producto){
					 if (!is_array($producto)){ 
						 $res=htmlLineaProducto($productos, $dedonde);
						 $respuesta['html'].=$res['html'];
						 break;
					 }else{
					$res=htmlLineaProducto($producto, $dedonde);
					$respuesta['html'].=$res['html'];
					}
			 }
		break;
		
		case 'addProveedorArticulo':
			include ('tareas/addProveedorArticulo.php');
		break;
		
		case 'comprobarAdjunto':
			include ('tareas/comprobarAdjunto.php');
		break;
	
		case 'buscarAdjunto':
			include ('tareas/buscarAdjunto.php');
		break;
		
		case 'addPedidoTemporal';
			include ('tareas/addPedidoTemporal.php');
		break;
		

		case 'addAlbaranTemporal':
			//@Objetivo:
			//Añade un albaran temporal es igual que la de pedidos pero esta vez en la tabla temporal de albaranes
			$idAlbaranTemporal=$_POST['idTemporal'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estado=$_POST['estado'];
			$idAlbaran=$_POST['idReal'];
			$fecha=$_POST['fecha'];
			//~ error_log($fecha);
			$fecha = new DateTime($fecha);
			$fecha = $fecha->format('Y-m-d');
			//~ error_log($fecha);
			//~ $fecha=date_format($fecha, 'Y-m-d');
			//~ error_log($fecha);
			$productos=json_decode($_POST['productos']);
			if (isset($_POST['pedidos'])){
				$pedidos=$_POST['pedidos'];
			}else{
				$pedidos=array();
			}
			$suNumero=$_POST['suNumero'];
			$idProveedor=$_POST['idProveedor'];
			$existe=0;
		//Si existe el albaran  temporal se modifica , devuelve el control de errores
		//Si no tiene  errores devuelve el idTemporal y la bandera que se utiliza el el js de existe
			if ($idAlbaranTemporal>0){
				$rest=$CAlb->modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estado, $fecha ,  $idAlbaranTemporal, $productos, $pedidos, $suNumero);
					if (isset($rest['error'])){
						$respuesta['error']=$rest['error'];
						$respuesta['consulta']=$rest['consulta'];
						break;
					}else{
						$existe=1;
						$res=$rest['idTemporal'];
						$respuesta['id']=$rest['idTemporal'];
					}
			}else{
				//Si no existe el temporal se crea , con control de errores 
				$rest=$CAlb->insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estado, $fecha ,  $productos, $idProveedor, $pedidos, $suNumero);
				if (isset($rest['error'])){
					$respuesta['error']=$rest['error'];
					$respuesta['consulta']=$rest['consulta'];
					$existe=0;
						break;
					
				}else{
					$existe=0;
					$idAlbaranTemporal=$rest['id'];
					$respuesta['id']=$rest['id'];
					$respuesta['sqlTemporal']=$rest['sql'];
				}
			}
			//Si es un albarán que se está modificando se guarda en el Real el idTemporal
			//Y se cambia el estado a Sin guardar
			//Con control de errores las dos funciones
			if ($idAlbaran>0){
				$modId=$CAlb->addNumRealTemporal($idAlbaranTemporal, $idAlbaran);
				if (isset($modId['error'])){
						$respuesta['error']=$modId['error'];
						$respuesta['consulta']=$modId['consulta'];
						break;
				}
				$estado="Sin Guardar";
				$modEstado=$CAlb->modEstadoAlbaran($idAlbaran, $estado);
				if (isset($modEstado['error'])){
						$respuesta['error']=$modEstado['error'];
						$respuesta['consulta']=$modEstado['consulta'];
						break;
				}
			}
			if ($productos){
				$CalculoTotales = recalculoTotales($productos);
				$total=round($CalculoTotales['total'],2);
				$respuesta['total']=round($CalculoTotales['total'],2);
				$respuesta['totales']=$CalculoTotales;
				$modTotal=$CAlb->modTotales($idAlbaranTemporal, $respuesta['total'], $CalculoTotales['subivas']);
				if (isset($modTotal['error'])){
						$respuesta['error']=$modTotal['error'];
						$respuesta['consulta']=$modTotal['consulta'];
						break;
				}
				$respuesta['sqlmodtotal']=$modTotal['sql'];
				$htmlTotales=htmlTotales($CalculoTotales);
				$respuesta['htmlTabla']=$htmlTotales['html'];
				
			}
			$respuesta['existe']=$existe;
			$respuesta['productos']=$_POST['productos'];
		break;
		
		case 'addFacturaTemporal':
		//@Objetivo:
			//Añadir factura temporal 
			// [NOTA] Es igual que añadir pedido temporal con la diferencia de que cambia la tabla temporal de facturas
			$idFacturaTemp=$_POST['idTemporal'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estado=$_POST['estado'];
			$idFactura=$_POST['idReal'];
			$fecha=$_POST['fecha'];
			$fecha = new DateTime($fecha);
			$fecha = $fecha->format('Y-m-d');
			$respuesta=array();
			$productos=json_decode($_POST['productos']);
			if(isset ($_POST['albaranes'])){
				$albaranes=$_POST['albaranes'];
			}else{
				$albaranes=array();
			}
			$idProveedor=$_POST['idProveedor'];
			$suNumero=$_POST['suNumero'];
			
			
			if ($idFacturaTemp>0){
				$rest=$CFac->modificarDatosFacturaTemporal($idUsuario, $idTienda, $estado, $fecha ,  $idFacturaTemp, $productos, $albaranes, $suNumero);
				if(isset($rest['error'])){
					$respuesta['error']=$rest['error'];
					$respuesta['consulta']=$rest['consulta'];
				}else{
					$existe=1;
					$res=$rest['idTemporal'];
					$pro=$rest['productos'];
				}
			}else{
				$rest=$CFac->insertarDatosFacturaTemporal($idUsuario, $idTienda, $estado, $fecha ,  $productos, $idProveedor, $albaranes, $suNumero);
				if(isset($rest['error'])){
					$respuesta['error']=$rest['error'];
					$respuesta['consulta']=$rest['consulta'];
				}else{
					$existe=0;
					$pro=$rest['productos'];
					$res=$rest['id'];
					$idFacturaTemp=$res;
				}
			}
			if ($idFactura>0){
				$modId=$CFac->addNumRealTemporal($idFacturaTemp, $idFactura);
				if (isset($modId['error'])){
					$respuesta['error']=$modId['error'];
					$respuesta['consulta']=$modId['consulta'];
				}else{
					$estado="Sin Guardar";
					$modEstado=$CFac->modEstadoFactura($idFactura, $estado);
					if (isset($modEstado['error'])){
						$respuesta['error']=$modEstado['error'];
						$respuesta['consulta']=$modEstado['consulta'];
					}
				}
			}
			if ($productos){
				$CalculoTotales = recalculoTotales($productos);
				$total=round($CalculoTotales['total'],2);
				$respuesta['total']=round($CalculoTotales['total'],2);
				$respuesta['totales']=$CalculoTotales;
				$modTotal=$CFac->modTotales($res, $respuesta['total'], $CalculoTotales['subivas']);
				if (isset($modTotal['error'])){
						$respuesta['error']=$modTotal['error'];
						$respuesta['consulta']=$modTotal['consulta'];
				}
				$respuesta['sqlmodtotal']=$modTotal['sql'];
				$htmlTotales=htmlTotales($CalculoTotales);
				$respuesta['htmlTabla']=$htmlTotales['html'];
				
			}
			$respuesta['id']=$res;
			$respuesta['existe']=$existe;
			$respuesta['productos']=$_POST['productos'];
		break;
		
		case 'modificarEstado':
		//@Objetivo: 
		//Modificar el estado dependiento de donde venga 
		//@Parámetros que recibe: 
		//id -> id que recibimos , puede ser id de pedido o id de albaran
		//dedonde->Para poder filtrar que función tenemos que ejecutar	
			$respuesta=array();
			$estado=$_POST['estado'];
			if ($_POST['dedonde']=="albaran"){
				$modEstado=$CPed->modEstadoPedido($_POST['id'], $estado);
				if (isset($mosEstado['error'])){
						$respuesta['error']=$mosEstado['error'];
						$respuesta['consulta']=$modEstado['consulta'];
				}
			}
			if ($_POST['dedonde']=="factura"){
				$modEstado=$CAlb->modEstadoAlbaran($_POST['id'], $estado);
				if (isset($mosEstado['error'])){
						$respuesta['error']=$mosEstado['error'];
						$respuesta['consulta']=$modEstado['consulta'];
				}
			}
		break;
		case 'htmlAgregarFilaAdjunto':
		//OBjetivo: agregar la fila con los datos del albaran o pedido adjunto
			$res=lineaAdjunto($_POST['datos'], $_POST['dedonde']);
			$respuesta['html']=$res['html'];
		break;
		
		case 'datosImprimir':
			//@Objetivo:
			//Imprimir un documento , dependiendo de donde venga se pone el nombre y envía todos los datos  
			//a la función montarHTMLimprimir que lo que realiza es simplemente montar el html una parte copn la cabecera y 
			//otra con el cuerpo del documento
			//debajo cargamos las clases de imprimir y la plantilla una vez generada y lista la plantilla devolvemos la ruta
			//para así desde javascript poder abrirla
			$id=$_POST['id'];
			$dedonde=$_POST['dedonde'];
			$idTienda=$_POST['idTienda'];
			$nombreTmp=$dedonde."compras.pdf";
			$htmlImprimir=montarHTMLimprimir($id, $BDTpv, $dedonde, $idTienda);
			$cabecera=$htmlImprimir['cabecera'];
			$html=$htmlImprimir['html'];
			require_once('../../lib/tcpdf/tcpdf.php');
			include ('../../clases/imprimir.php');
			include('../../controllers/planImprimir.php');
			$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
			$respuesta=$ficheroCompleto;
		break;
		case 'insertarImporte':
			//@Objetivo:
			//Insertar un nuevo importe a una factura
			//@Proceso:
			//Primero se buscan los importes que tiene ya esa factura, si tiene elimina el registro
			 $importe=$_POST['importe'];
			 $fecha=$_POST['fecha'];
			 $idFactura=$_POST['idTemporal'];
			 $formaPago=$_POST['forma'];
			 $referencia=$_POST['referencia'];
			 $total=$_POST['total'];
			 $idReal=$_POST['idReal'];
			 $arrayPrincipal=array();
			 $error=0;
			 $bandera=$importe;
			 $respuesta=array();
			 $importesReal=array();
			 $importesReal=$CFac->importesFactura($idReal);
			 if (isset($importesReal['error'])){
				$respuesta['error']=$importesReal['error'];
				$respuesta['consulta']=$importesReal['consulta'];
			}
			 $respuesta['importeReal']=$importesReal;
			 if(count($importesReal)>0){
				$importesReal=modificarArraysImportes($importesReal, $total);
				$importesTemporal=json_encode($importesReal);
				$eliminarReal=$CFac->eliminarRealImportes($idReal);
				if (isset($eliminarReal['error'])){
					$respuesta['error']=$eliminarReal['error'];
					$respuesta['consulta']=$eliminarReal['consulta'];
				}
				$respuesta['impTemporal']=$importesTemporal;
			 }else{
				 $importesTemporal=$CFac->importesTemporal($idFactura);
				if (isset($importesTemporal['error'])){
					$respuesta['error']=$importesTemporal['error'];
					$respuesta['consulta']=$importesTemporal['consulta'];
				}else{
				 $importesTemporal=$importesTemporal['FacCobros'];
				 $bandera=$importe;
				}
			 }
			 if ($importesTemporal){
				$importes=json_decode($importesTemporal, true);
				$respuesta['importes']= $importes;
				 foreach ($importes as $import){
					 $bandera=$bandera+(string)$import['importe'];
					 array_push($arrayPrincipal, $import);
				 }
				 if ($bandera>$total){
					 $respuesta['mensaje']=1;
					 $error=1;
				 }
				 $respuesta['bandera']=$bandera;
			 }
			 if ($error==0){
				$pendiente=$total-$bandera;
				$nuevo=array();
				$nuevo['importe']=$importe;
				$nuevo['fecha']=$fecha;
				$nuevo['forma']=$formaPago;
				$nuevo['referencia']=$referencia;
				$nuevo['pendiente']=$pendiente;
				$respuesta['nuevo']=$nuevo;
				array_push($arrayPrincipal, $nuevo);
				$jsonImporte=json_encode($arrayPrincipal);
				$modImportes=$CFac->modificarImportesTemporal($idFactura, $jsonImporte);
				if (isset($modImportes['error'])){
					$respuesta['error']=$modImportes['error'];
					$respuesta['consulta']=$modImportes['consulta'];
				}
				$html=htmlImporteFactura($nuevo, $BDTpv);
				$respuesta['html']=$html['html'];
			}
		break;
		case 'abririncidencia':
		//@OBJETIVO:
		//Mostrar el modal de incidencias con los datos de compras, según en el archivo en el que está 
		//situado envía los datos de este. El idReal es el id del albarán, pedido o factura guardado si no lo 
		//envía en 0
		//@Retornar: devuelve el html para insertar en el js del modal
			$dedonde=$_POST['dedonde'];
			$usuario=$_POST['usuario'];
			$idReal=0;
			if(isset($_POST['idReal'])){
				$idReal=$_POST['idReal'];
			}
			
			$configuracion=$_POST['configuracion'];
			$numInicidencia=0;
			$tipo="mod_compras";
			$fecha=date('Y-m-d');
			$datos=array(
			'dedonde'=>$dedonde,
			'idReal'=>$idReal
			);
			$datos=json_encode($datos);
			$estado="No resuelto";
			$html=modalIncidencia($usuario, $datos, $fecha, $tipo, $estado, $numInicidencia, $configuracion, $BDTpv);
			$respuesta['html']=$html;
			$respuesta['datos']=$datos;
		break;
		
		case 'nuevaIncidencia':
		//@Objetivo: Agregar una nueva incidencia, dirigimos los datos a la función addIncidencia
		//esta está situada en el modulo de incidencias e inserta una nueva fila a la tabla de módulo
		//de incidencias con los datos seleccionado en el modal.
			$usuario= $_POST['usuario'];
			$fecha= $_POST['fecha'];
			$datos= $_POST['datos'];
			$dedonde= $_POST['dedonde'];
			$estado= $_POST['estado'];
			$mensaje= $_POST['mensaje'];
			$usuarioSelect=0;
			if(isset($_POST['usuarioSelec'])){
				$usuarioSelect=$_POST['usuarioSelec'];
			}
			if($usuarioSelect>0){
				$datos=json_decode($datos);
				$datos->usuarioSelec=$usuarioSelect;
				$datos=json_encode($datos);
			}
			$numInicidencia=0;
			if($mensaje){
				$nuevo=addIncidencia($usuario, $fecha, $dedonde, $datos, $estado, $mensaje, $BDTpv,  $numInicidencia);
				$respuesta=$nuevo['sql'];
			}
		break;
		case 'cancelarTemporal':
		//@Objetivo: cancelar el archivo temporal , cuando cancelamos un temporal muestra de primeros una alert
		//donde aceptamos en caso de querrer eliminar, a  continuación dependiendo de del archivo donde estemos
		//situados ejecuta su función 
		//@Retorno: en principio devuelve un array vacio a no ser que se tenga un error en la función ejecutada
			$idTemporal=$_POST['idTemporal'];
			$dedonde=$_POST['dedonde'];
			$respuesta=array();
			switch($dedonde){
				case 'pedidos':
					$cancelar=cancelarPedido( $idTemporal, $BDTpv);
					$respuesta=$cancelar;
				break;
				case 'albaran':
					$cancelar=cancelarAlbaran( $idTemporal, $BDTpv);
					$respuesta=$cancelar;
				break;
				case 'factura':
					$cancelar=cancelarFactura( $idTemporal, $BDTpv);
					$respuesta=$cancelar;
				break;
			 }
			
		break;
		
	
}
 echo json_encode($respuesta);
 return $respuesta;
?>
