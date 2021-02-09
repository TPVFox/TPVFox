<?php
// @Objetivo :
// Obtener la referencia producto de una tienda. (web)
$respuesta = array();
$id =$_POST['idTicket'];
$ticket = $CTickets->obtenerUnTicket($id);
$idweb	 = $_POST['web'];
//Ahora obtenemos datos tienda web.
$tienda = BuscarTienda($BDTpv,$idweb);
// Ahora obtenemos productos.
$productos = array();
foreach ($ticket['lineas'] as $key =>$dato) {
    if ($dato['estadoLinea'] !== 'Eliminado'){
        // Eliminamos de array productos los eliminados.
        $productos[] = $dato;
    }
}
$obtener = ObtenerRefWebProductos($BDTpv,$productos,$idweb);
$respuesta['productos']=$obtener['productos'];
$respuesta['obtener'] = $obtener;
$respuesta['tienda'] = $tienda;
?>
