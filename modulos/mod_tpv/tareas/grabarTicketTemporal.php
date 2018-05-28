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
//~ $productos_para_recalculo = json_decode( json_encode( $_POST['productos'] ));
//~ $CalculoTotales = recalculoTotales($productos_para_recalculo);
$CalculoTotales = recalculoTotales($productos);

$nuevoArray = array(
				'desglose'=> $CalculoTotales['desglose'],
				'total' => $CalculoTotales['total']
					);

$respuesta	= grabarTicketsTemporales($BDTpv,$productos,$cabecera,$CalculoTotales['total']);
$respuesta  = array_merge($respuesta,$nuevoArray);
?>
