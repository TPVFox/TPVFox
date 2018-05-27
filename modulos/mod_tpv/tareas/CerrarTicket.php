<?php
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
?>
