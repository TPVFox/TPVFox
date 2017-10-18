
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
include_once ("./../mod_conexion/conexionBaseDatos.php");

// Incluimos funciones
include_once ("./funciones.php");

switch ($pulsado) {
    
    case 'buscarProductos':
		$busqueda = $_POST['valorCampo'];
		$campoAbuscar = $_POST['campo'];
		//cuando busco dsde el popup el estado me es indiferente
		$deDonde = $_POST['dedonde'];
		//cambio conexion a tpv
		
		//vuelta indica que busque primero = al dato sino dentro se vuelve a llamar a si misma y busca %likes%
		$vuelta = 1;
		$respuesta = BuscarProductos($campoAbuscar,$busqueda,$BDTpv,$vuelta);
		
		// Si respuesta es incorrecta, entonces devuelvo html de respuesta
		//si dedonde == 'popup' 
		if ($respuesta['Estado'] !='Correcto' ){
			// Entramos aquí tanto si es listado como si no se encontro articulos.
			$respuesta['listado']= htmlProductos($respuesta['datos'],$campoAbuscar,$busqueda);
		}
		if ($respuesta['Estado'] === 'Correcto' && $deDonde === 'popup'){
			// Cambio estado para devolver que es listado.
			$respuesta['listado']= htmlProductos($respuesta['datos'],$campoAbuscar,$busqueda);
			$respuesta['Estado'] = 'Listado';
		}
		echo json_encode($respuesta);  
		break;
	
	case 'cobrar':
		//~ echo 'cobrar';
		$totalJS = $_POST['total'];
		$productos = $_POST['productos'];
		// Convertimos productos que debería ser un objeto a array
		$productos = json_decode( json_encode( $_POST['productos'] ));

		// Recalcular totales.
		$totales = recalculoTotales($productos);
		
		
		$respuesta = htmlCobrar($totalJS);
		$respuesta['recalculo'] = $totales;

		echo json_encode($respuesta);		
		
		break;
	//modal buscar por clientes
	case 'buscarClientes':
		$busqueda = $_POST['busqueda'];
		$tabla='clientes';
		//funcion de buscar clientes
		//luego html mostrar modal 
		if ($busqueda != ''){
			$res = array();
			//$res = BusquedaClientes($busqueda);
			$res = BusquedaClientes($busqueda,$BDTpv,$tabla);
		} 
		
		$respuesta = htmlClientes($busqueda,$res['datos']);
	
		echo json_encode($respuesta);
		break;
	case 'grabarTickes';
		$respuesta = array();
		$cabecera = array(); // Array que rellenamos de con POST
		$productos 					=$_POST['productos'];
		$total 						=$_POST['total'];
		$cabecera['idTienda']		=$_POST['idTienda'];
		$cabecera['idCliente']		=$_POST['idCliente'];
		$cabecera['idUsuario'] 		=$_POST['idUsuario'];
		$cabecera['estadoTicket'] 	=$_POST['estadoTicket'];
		$cabecera['numTicket'] 		=$_POST['numTicket'];

		$res 	= grabarTicketsTemporales($BDTpv,$productos,$cabecera,$total);
		$respuesta=$res;
		echo json_encode($respuesta);
		break;
		
	case 'HtmlLineaTicket';
		$respuesta = array();
		$product 					=$_POST['producto'];
		$num_item					=$_POST['num_item'];
		$CONF_campoPeso		=$_POST['CONF_campoPeso'];
		$res 	= htmlLineaTicket($product,$num_item,$CONF_campoPeso);
		$respuesta['html'] =$res;
		echo json_encode($respuesta);
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
		$checkimprimir 					=$_POST['checkimprimir'];
		
		// Obtenemos ticket
		$ticket 	= ObtenerUnTicket($BDTpv,$cabecera['idTienda'],$cabecera['idUsuario'] ,$cabecera['numTickTemporal']);
		// Comprobamos que el resultado es correcto y recalculamos totales
		if (isset($ticket['error'])) { 
			$respuesta['error-ticket']['mensaje'] ='Error en al Obtener ticket';
			$respuesta['error-ticket']['datos'] = $ticket['error'];
			echo json_encode($respuesta); // Convierto a JSON.
			return $respuesta; // No continuamos,.
		}
		// Obtenermos los productos como array que con un JSOn por cada producto y este JSON contiene los campos de cada producto
		if (isset($ticket['productos']) && ($ticket['estadoTicket']!='Cobrado')){
			
			$productos = json_decode( json_encode( $ticket['productos'] ));
			$Datostotales = recalculoTotales($ticket['productos']);	
			if (number_format($Datostotales['total'],2) != number_format($cabecera['total'],2)){
				$respuesta['error-ticket']['mensaje']  = ' No coincidente TOTAL:'.$cabecera['total'].' con el Total recalculado';
				$respuesta['error-ticket']['datos'] = $Datostotales;
			}
			// grabamos ticket.
			$grabar = grabarTicketCobrado($BDTpv,$productos,$cabecera,$Datostotales['desglose']);
		//si numTickets = -1 , no existe indice usuario. = -2 es no se puede grabar en indices
			//echo $grabar['Numtickets'];

		
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
				include 'impresoraTicket.php';
				
			}
		} else {
			// Si llega aquí es que $resultado['error'] existe por lo que pudo haber un errror en:
			// $grabar = grabarTicketCobrado($BDTpv,$productos,$cabecera,$Datostotales['desglose']); 
			// ya que el oro 
			error_log ("Error en tareas, en if !isset($respuesta[error]");
			exit();
		}
		$respuesta['grabar'] =$grabar;
		//~ $respuesta['ticket'] = $datosImpresion;
		echo json_encode($respuesta);
		break;
		
		case 'guardarCierreCaja':
			echo 'tareas guardar cierre';
		break;
}
 
/* ===============  CERRAMOS CONEXIONES  ===============*/

mysqli_close($BDImportDbf);

 
 
?>
