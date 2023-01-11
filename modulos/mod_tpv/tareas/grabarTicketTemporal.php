<?php
$respuesta = array();
$cabecera = array(); // Array que rellenamos de con POST
$productos 					=json_decode($_POST['productos']);
$cabecera['idTienda']		=$_POST['idTienda'];
$cabecera['idCliente']		=$_POST['idCliente'];
$cabecera['idUsuario'] 		=$_POST['idUsuario'];
$cabecera['estadoTicket'] 	=$_POST['estadoTicket'];
$cabecera['numTicket'] 		=$_POST['numTicket'];

// Ahora recalculamos nuevamente
$CalculoTotales = recalculoTotales($productos);

$respuesta = array(
				'desglose'=> $CalculoTotales['desglose'],
				'total' => $CalculoTotales['total']
					);
$respuesta['html']= htmlDesgloseIvas($CalculoTotales['desglose']);

$grabar	= grabarTicketsTemporales($BDTpv,$productos,$cabecera,$CalculoTotales['total']);
$respuesta  = array_merge($respuesta,$grabar);
?>
