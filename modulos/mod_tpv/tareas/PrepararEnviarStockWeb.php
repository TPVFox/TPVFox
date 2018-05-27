<?php
// @Objetivo :
// Obtener la referencia producto de una tienda. (web)
$respuesta = array();
$productos =json_decode($_POST['productos']);
$idweb	 = $_POST['web'];
//Ahora obtenemos datos tienda web.
$tienda = BuscarTienda($BDTpv,$idweb);
$respuesta = ObtenerRefWebProductos($BDTpv,$productos,$idweb);
$respuesta['tienda'] = $tienda;
?>
