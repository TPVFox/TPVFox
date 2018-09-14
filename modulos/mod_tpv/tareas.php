
<?php
/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *  */
/* ===============  REALIZAMOS CONEXIONES  ===============*/


$pulsado = $_POST['pulsado'];
//~ use Mike42\Escpos\Printer;
include_once './../../inicial.php';
include_once $URLCom.'/configuracion.php';
include_once $URLCom.'/modulos/mod_tpv/funciones.php';
include_once $URLCom.'/controllers/Controladores.php';

// Incluimos controlador.
$Controler = new ControladorComun; 
$Controler->loadDbtpv($BDTpv); // Añado la conexion a controlador.

// Creamos clases de parametros 
include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
$ClasesParametros = new ClaseParametros('parametros.xml');
$parametros = $ClasesParametros->getRoot();
// Cargamos configuracion modulo tanto de parametros (por defecto) como si existen en tabla modulo_configuracion 
$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_tpv',$Usuario['id']);
include_once $URLCom.'/modulos/mod_tpv/clases/ClaseTickets.php';
// Creamos clase de ticket

$CTickets = new ClaseTickets();
include_once $URLCom.'/modulos/mod_incidencias/clases/ClaseIncidencia.php';
$CIncidencia=new ClaseIncidencia($BDTpv);
switch ($pulsado) {
    
    case 'buscarProductos':
        include_once $URLCom.'/modulos/mod_tpv/tareas/buscarProducto.php';
	break;	
	
	case 'cobrar':
		$totalJS = $_POST['total'];
		$productos = json_decode($_POST['productos']);
		$configuracion = $_POST['configuracion'];
		// Recalcular totales.
		$totales = recalculoTotales($productos);
		$respuesta = htmlCobrar($totalJS,$configuracion);
		$respuesta['recalculo'] = $totales;
		break;
	
	case 'grabarTickes';
		// @ Objetivo :
		// Grabar tickets temporales.
		include ('tareas/grabarTicketTemporal.php');
	break;
		
	case 'HtmlLineaTicket';
		$respuesta = array();
		$product 					=$_POST['producto'];
		$num_item					=$_POST['num_item'];
		$CONF_campoPeso		=$_POST['CONF_campoPeso'];
		$res 	= htmlLineaTicket($product,$num_item,$CONF_campoPeso);
		$respuesta['html'] =$res;
		$respuesta['conf_peso'] =$CONF_campoPeso;
		break;
	case 'CerrarTicket';
        include_once $URLCom.'/modulos/mod_tpv/tareas/CerrarTicket.php';
		
		break;
	
	case 'ImprimirTicketCerrados';
		// Ahora debería imprimir el ticket cerrado.
		$id					=$_POST['idTicketst'];
		$ticket = $CTickets->obtenerUnTicket($id);
		
		$datosImpresion = $CTickets->prepararParaImprimirTicket($ticket);
		$ruta_impresora = $configuracion['impresora_ticket'];
		if (ComprobarImpresoraTickets($ruta_impresora) === true){;
			include 'tareas/impresoraTicket.php';
		} else {
			$respuesta['error_impresora'] = ' no existe la impresora asignada, hay un error';
		}
		// Pendiente de realizar.
		$respuesta['idTicketST'] = $id;
		$respuesta['datosImpresion'] = $datosImpresion;
		break;

	case 'ObtenerRefTiendaWeb';
		include ('tareas/PrepararEnviarStockWeb.php');
		break;
		
	case 'RegistrarRestaStock':
		$respuesta = array();
		$id_ticketst =$_POST['id_ticketst'];
		//~ $respuesta_servidor = $_POST['respuesta_servidor'];
		$respuesta = RegistrarRestaStock($BDTpv,$id_ticketst);
		break;
	
	case 'buscarClientes':
		// Abrimos modal de clientes
		$busqueda = $_POST['busqueda'];
		$dedonde = $_POST['dedonde'];
		$tabla='clientes';
		//funcion de buscar clientes
		//luego html mostrar modal 
		if ($busqueda != ''){
			//$res = BusquedaClientes($busqueda);
			$res = BusquedaClientes($busqueda,$BDTpv,$tabla);
		} 
		if (!isset($res['datos'])){
			$res = array( 'datos' => array());
		}
		$respuesta = htmlClientes($busqueda,$dedonde,$res['datos']);
		break;
		
	case 'Grabar_configuracion':
		// Grabamos configuracion nueva configuracion
		$configuracion = $_POST['configuracion'];
		// Ahora obtenemos nombre_modulo y usuario , lo ponermos en variable y quitamos array configuracion.
		$nombre_modulo = $configuracion['nombre_modulo'];
		$idUsuario = $configuracion['idUsuario'];
		unset($configuracion['nombre_modulo'],$configuracion['idUsuario']);
		
		$respuesta = $Controler->GrabarConfiguracionModulo($nombre_modulo,$idUsuario,$configuracion);		
		$respuesta['configuracion'] = $configuracion ; 
		break;
		
	case 'abririncidencia':
		$dedonde=$_POST['dedonde'];
		$usuario=$_POST['usuario'];
		$configuracion=$_POST['configuracion'];
		$idReal=0;
		if(isset($_POST['idReal'])){
			$idReal=$_POST['idReal'];
		}
		$tipo="mod_tpv";
		$numInicidencia=0;
		$datos=array(
		'vista'=>$dedonde,
		'idReal'=>$idReal
		);
		$datos=json_encode($datos);
		
		$estado="No resuelto";
		$html=$CIncidencia->htmlModalIncidencia($datos, $dedonde, $configuracion, $estado, $numIncidencia);
		$respuesta['html']=$html;
		$respuesta['datos']=$datos;
		break;
		
	case 'nuevaIncidencia':
		$usuario= $_POST['usuario'];
		$fecha= $_POST['fecha'];
		$datos= $_POST['datos'];
		$estado= $_POST['estado'];
		$mensaje= $_POST['mensaje'];
		$numInicidencia=0;
		$usuarioSelect=0;
		$dedonde="mod_tpv";
		if(isset($_POST['usuarioSelec'])){
		$usuarioSelect=$_POST['usuarioSelec'];
		}
		if($usuarioSelect>0){
			$datos=json_decode($datos);
			$datos->usuarioSelec=$usuarioSelect;
			$datos=json_encode($datos);
		}
		if($mensaje){
			$nuevo=$CIncidencia->addIncidencia($dedonde, $datos, $mensaje, $estado, $numInicidencia);
			$respuesta=$nuevo;
		}
		break;
		
		
}
echo json_encode($respuesta);
/* ===============  CERRAMOS CONEXIONES  ===============*/
mysqli_close($BDTpv);

 
 
?>
