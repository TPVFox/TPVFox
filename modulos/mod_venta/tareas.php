<?php
/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *  */
/* ===============  REALIZAMOS CONEXIONES  ===============*/
$pulsado = $_POST['pulsado'];
include_once ("./../../configuracion.php");
// Crealizamos conexion a la BD Datos
include_once ("./../../inicial.php");
// Incluimos funciones
include_once ("./funciones.php");
include_once ("../mod_incidencias/popup_incidencias.php");
include_once("clases/pedidosVentas.php");
$CcliPed=new PedidosVentas($BDTpv);
include_once("../../clases/producto.php");
$Cprod=new Producto($BDTpv);
include_once ("clases/albaranesVentas.php");
$CalbAl=new AlbaranesVentas($BDTpv);
include_once("../../clases/cliente.php");
$Ccliente=new Cliente($BDTpv);
include_once("clases/facturasVentas.php");
$CFac=new FacturasVentas($BDTpv);
switch ($pulsado) {
  
		case 'buscarProductos':
			include 'tareas/BuscarProductos.php';
		break;
		
	    case 'buscarClientes':
			include 'tareas/BuscarClientes.php';
		break;	
		
		case 'buscarPedido':
			include 'tareas/BuscarPedido.php';
		break;
		
		case 'buscarAlbaran':
			include 'tareas/BuscarAlbaran.php';
		break;
		case 'anhadirPedidoTemp':
			include 'tareas/AddPedidoTemporal.php';
		break;
		case 'anhadirAlbaranTemporal':
			include 'tareas/AddAlbaranTemporal.php';
		break;
		case 'anhadirfacturaTemporal':
			include 'tareas/AddFacturaTemporal.php';
		break;
			case 'modificarEstadoPedido':
		//Objetivo:
		//Modificar el estado de un pedido a Sin Guardar si viene de pedidos , si viene de albarán a facturado
		//Y si viene de factura entonces no es un pedido es un albarán que lo pasa a facturado
			$idPedido=$_POST['idModificar'];
			$estado=$_POST['estado'];
			$respuesta=array();
			$modEstado=$CcliPed->ModificarEstadoPedido($idPedido, $estado);
			if(isset($modEstado['error'])){
				$respuesta['error']=$modEstado['error'];
				$respuesta['consulta']=$modEstado['consulta'];
			}
		break;
		
		case 'comprobarPedidos':
			include 'tareas/comprobarPedidos.php';
		break;
		case 'comprobarAlbaran':
			include 'tareas/comprobarAlbaranes.php';
		break;
		
		
		case 'htmlAgregarFilaPedido':
		//Objetivo:
		//Devuelve el html de la fila del pedido 
			$res=htmlPedidoAlbaran($_POST['datos'], $_POST['dedonde']);
			$respuesta['html']=$res['html'];
		break;
		
		case 'htmlAgregarFilaAlbaran':
		//Objetivo:
		//Devuelve el html de la fila albarán
		$arrayAlbaranes=array();
		array_push($arrayAlbaranes, $_POST['datos']);
		$res=htmlAlbaranFactura($arrayAlbaranes, $_POST['dedonde']);
		$respuesta['html']=$res['html'];
			
		break;
		
		case 'htmlAgregarFilasProductos':
		//Objetivo:
		//HTML mostrar las lineas de productos
		$productos=$_POST['productos']; // (array) Un array de varios productos, o un array de un producto..
		$dedonde=$_POST['dedonde'];
		$respuesta =array('html'=>'');
		 foreach($productos as $producto){
			if (!is_array($producto)){
				 // Si no es un array, es un producto, por lo que se hace linea productos ( que es uno solo )
				 $res=htmlLineaPedidoAlbaran($productos, $dedonde);
				 $respuesta['html']=$res;
				break;
			}else{
				//Como es un array de productos ejecutamos foreach
				$res=htmlLineaPedidoAlbaran($producto, $dedonde);
				$respuesta['html'].=$res;
			}
		 }
		break;
		
		case 'htmlFomasVenci':
			//@Objetivo:
			//MUestra las formas de vencimiento de esa factura
			$formasVenci=$_POST['formasVenci'];
			if ($_POST['formasVenci']){
				$formaPago=json_decode($formasVenci, true);
				$forma=$formaPago['formapago'];
				$venci=$formaPago['vencimiento'];
			}else{
				$forma=0;
				$venci=0;
			}
			$for=htmlFormasVenci($forma, $BDTpv);
			$respuesta['html1']=$for['html'];
			$fun=fechaVencimiento($venci, $BDTpv);
			$ven=htmlVencimiento($fun, $BDTpv);
			$respuesta['html2']=$ven['html'];
			$respuesta['fecha']=$fun;
		break;
		
		case 'ModificarFormasVencimiento':
		//@Objetivo:
		//MOdificar la forma de vencimiento de esa factura en concreto
		$opcion=$_POST['opcion'];
		$fechaVenci=$_POST['fechaVenci'];
		$idTemporal=$_POST['idFacTem'];
		$formasVenci=array();
		$formasVenci['forma']=$opcion;
		$formasVenci['fechaVencimiento']=$fechaVenci;
		$respuesta=array();
		$json=json_encode($formasVenci);
		
		if ($idTemporal>0){
			$modTemporal=$CFac->formasVencimientoTemporal($idTemporal, $json);
			if(isset($modTemporal['error'])){
					$respuesta['error']=$modTemporal['error'];
					$respuesta['consulta']=$modTemporal['consulta'];
			}
		}
		break;
		
		case 'modificarEstadoFactura':
		//@Objetivo:
		//Modificar el estado de una factura 
		$idFactura=$_POST['idModificar'];
		$estado=$_POST['estado'];
		$respuesta=array();
		$modEstado=$CFac->modificarEstado($idFactura, $estado);
		if(isset($modEstado['error'])){
					$respuesta['error']=$modEstado['error'];
					$respuesta['consulta']=$modEstado['consulta'];
		}
		break;
		
		case 'modificarEstadoAlbaran':
		//@Objetivo:
		//modificar el estado de un alabrán
		$idAlbaran=$_POST['idModificar'];
		$estado=$_POST['estado'];
		$respuesta=array();
		$modEstado=$CalbAl->ModificarEstadoAlbaran($idAlbaran, $estado);
		if (isset($modEstado['error'])){
			$respuesta['error']=$modEstado['error'];
			$respuesta['consulta']=$modEstado['consulta'];
		}
		break;
		
		case 'insertarImporte':
			include 'tareas/insertarImporte.php';
		break;
		
		case 'datosImprimir':
			//@Objetivo:
		//enviar los datos para imprimir el pdf
			$id=$_POST['id'];
			$dedonde=$_POST['dedonde'];
			$tienda=$_POST['tienda'];
			$nombreTmp=$dedonde."ventas.pdf";
			$htmlImprimir=montarHTMLimprimir($id, $BDTpv, $dedonde, $tienda);
			$cabecera=$htmlImprimir['cabecera'];
			$html=$htmlImprimir['html'];
			require_once('../../lib/tcpdf/tcpdf.php');
			include ('../../clases/imprimir.php');
			include('../../controllers/planImprimir.php');
			$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
			$respuesta=$ficheroCompleto;
		break;
		case 'abririncidencia':
			$dedonde=$_POST['dedonde'];
			$usuario=$_POST['usuario'];
			$idReal=0;
			if(isset($_POST['idReal'])){
				$idReal=$_POST['idReal'];
			}
			$configuracion=$_POST['configuracion'];
			$numInicidencia=0;
			$tipo="mod_ventas";
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
				break;
				case 'factura':
					$cancelar=cancelarFactura( $idTemporal, $BDTpv);
				break;
			 }
		break;
	
		
}
echo json_encode($respuesta);
return $respuesta;
