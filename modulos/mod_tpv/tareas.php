
<?php
/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *  */
/* ===============  REALIZAMOS CONEXIONES  ===============*/


$pulsado = $_POST['pulsado'];
use Mike42\Escpos\Printer;

include_once ("./../../configuracion.php");

// Crealizamos conexion a la BD Datos
include_once ("./../../inicial.php");

// Incluimos funciones
include_once ("./funciones.php");

// Incluimos controlador.
include ("./../../controllers/Controladores.php");
$Controler = new ControladorComun; 
// Añado la conexion a controlador.
$Controler->loadDbtpv($BDTpv);

include_once '../mod_incidencias/clases/ClaseIncidencia.php';
$CIncidencia=new ClaseIncidencia($BDTpv);
switch ($pulsado) {
    
    case 'buscarProductos':
		include ('tareas/buscarProducto.php');
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
		$URLCom = $RutaServidor . $HostNombre;
		$respuesta = array();
		$cabecera = array(); // Array que rellenamos de con POST
		$cabecera['total']				=$_POST['total'];
		$cabecera['entregado']			=$_POST['entregado'];
		$cabecera['formaPago']			=$_POST['formaPago'];
		$cabecera['idTienda']			=$_POST['idTienda'];
		$cabecera['idCliente']			=$_POST['idCliente'];
		$cabecera['idUsuario'] 			=$_POST['idUsuario'];
		$cabecera['estadoTicket'] 		=$_POST['estadoTicket'];
		$cabecera['numTickTemporal'] 	=$_POST['numTickTemporal'];
		$cabecera['cambio'] 			=$_POST['cambio'];

		$checkimprimir 					=$_POST['checkimprimir'];
		$ruta_impresora					=$_POST['ruta_impresora'];
		// Obtenemos ticket
		$ticket 	= ObtenerUnTicketTemporal($BDTpv,$cabecera['idTienda'],$cabecera['idUsuario'] ,$cabecera['numTickTemporal']);
		// Comprobamos que el resultado es correcto y recalculamos totales
		if (isset($ticket['error'])) { 
			$respuesta['error'][]['tipo'] = 'danger';
			$respuesta['error'][]['mensaje'] ='Error en al Obtener ticket temporal:'+$cabecera['numTickTemporal']+'No grabamos';
			$respuesta['error'][]['datos'] = $ticket['error'];
			echo json_encode($respuesta); // Convierto a JSON.
			return $respuesta; // No continuamos,.
		}
		// Obtenermos los productos como array que con un JSOn por cada producto y este JSON contiene los campos de cada producto
		if (isset($ticket['productos']) && ($ticket['estadoTicket']!='Cobrado')){
			
			$productos = json_decode( json_encode( $ticket['productos'] ));
			$Datostotales = recalculoTotales($ticket['productos']);	
			if (number_format($Datostotales['total'],2) != number_format($cabecera['total'],2)){
				$respuesta['error'][]['tipo'] = 'warning';
				$respuesta['error'][]['mensaje']  = ' No coincidente TOTAL:'.$cabecera['total'].' con el Total recalculado';
				$respuesta['error'][]['datos'] = $Datostotales;
			}
			// grabamos ticket.
			$grabar = grabarTicketCobrado($BDTpv,$productos,$cabecera,$Datostotales['desglose']);
			// Si hubo un error 
			if  (isset($grabar['error'])){
				$respuesta['error'][] = $grabar['error'];
			}
			$respuesta['grabar'] =$grabar;
		
		}
		if (!isset($respuesta['error']) ){
			//si esta marcado el check de imprimir al cobrar
			if ($checkimprimir === 'true'){
				// Obtenemos y organizamos datos antes imprimir
				$cabecera['fecha'] = $grabar['fecha'] ; // Fecha con la que grabamos el ticket.
				
				$cabecera['NumTicket'] = $grabar['Numtickets']; // El numero con el grabamos el ticket.
				$cabecera['Serie'] = $cabecera['idTienda'].'-'.$cabecera['idUsuario'];
				$DatosTienda = DatosTiendaID($BDTpv,$cabecera['idTienda']);
				$datosImpresion = ImprimirTicket($productos,$cabecera,$Datostotales['desglose'],$DatosTienda);
				// Incluimos fichero para imprimir ticket, con los datosImpresion.
				// Comprobamos si existe impresora.
				if (ComprobarImpresoraTickets($ruta_impresora) === true){;
					
					include 'impresoraTicket.php';
				} else {
					$respuesta['error_impresora'] = ' no existe la impresora asignada, hay un error';
				}
	
				
			}
		} 
		break;
		
	case 'ObtenerRefTiendaWeb';
		include('tareas/PrepararEnviarStockWeb.php');
		break;
		
	case 'RegistrarRestaStock':
		$respuesta = array();
		$id_ticketst =$_POST['id_ticketst'];
		$respuesta_servidor = $_POST['respuesta_servidor'];
		$respuesta = RegistrarRestaStock($BDTpv,$id_ticketst,$respuesta_servidor);
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
