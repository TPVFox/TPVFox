<?php 
	//@ Objetivo:
    //  Es buscar los adjuntos o el adjunto para ese albaran o factura.
    // Cargando los datos principales y sus productos
    $respuesta=array();
    $numAdjunto=$_POST['numAdjunto'];
    $idProveedor=$_POST['idProveedor'];
    $estado="Guardado";
    $dedonde=$_POST['dedonde'];
    if ($dedonde=="albaran"){
        $datosAdjunto=$CPed->buscarPedidoProveedorPorEstado($idProveedor, $numAdjunto, $estado);
    } else {
        $datosAdjunto=$CAlb->buscarAlbaranProveedorPorEstado($idProveedor, $numAdjunto, $estado);
    }
    if (!isset($datosAdjunto['error'])){
        $respuesta['Nitems']=$datosAdjunto['Nitems'];
        if ($respuesta['Nitems']==1){
            // Hubo solo un resultado. ( aunque no tiene mucho sentido que exista Nitem.. :-)
            $datos = $datosAdjunto['datos']['0'];    
            $respuesta['temporales']=1;
            $fecha =$datos['Fecha'];
            $date = new DateTime($fecha);
            $datos['fecha']     =date_format($date, 'Y-m-d');
            $datos['idAdjunto'] =$datos['id'];
            $datos['estado'] = 'activo'; // Estado del adjunto
            if ($dedonde=="albaran"){
                $datos['NumAdjunto']=$datos['Numpedpro'];
                $datos['totalSiva']=$datos['total_siniva'];
                $productosAdjunto=$CPed->ProductosPedidos($datos['id']);
            }else{
                $datos['NumAdjunto']=$datos['Numalbpro'];
                $productosAdjunto=$CAlb->ProductosAlbaran($datos['id']);
                
            }
            if (isset($productosAdjunto['error'])){
                $respuesta['error']=$productosAdjunto['error'];
                $respuesta['consulta']=$productosAdjunto['consulta'];
            }else{
                $respuesta['productos']=$productosAdjunto;
            }
            $respuesta['datos']=$datos;
            
        } else {
            // Hubo varios resultado o ninguno.
            if ($respuesta['Nitems']>1){
                // Montamos contenido para modal
                $modal=modalAdjunto($datosAdjunto['datos'], $dedonde, $BDTpv);
                $respuesta['html']=$modal['html'];
            } else {
                $respuesta['html']= '<div class="alert alert-warning">No se encontro resultado</div>';
            }
        }
     } else {
        // Hubo un error en la consulta.
        if ($idProveedor !=''){
            // Deberia ser un array con indices de [error] y [consulta]
            $respuesta = $datosAdjunto;
        } else {
            // Quiere decir que no se envio id de proveedor a buscar pedido o albaranes, por lo que la respuesta es
            $respuesta['error'] = 'No envias id de proveedor';
            $respuesta['consulta'] = 'Debe indicar proveedor primero';
        }
           
    }


?>
