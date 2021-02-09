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
        $datosAdjunto=$CPed->buscarPedidoProveedorGuardado($idProveedor, $numAdjunto, $estado);
    } else {
        $datosAdjunto=$CAlb->buscarAlbaranProveedorGuardado($idProveedor, $numAdjunto, $estado);
    }
    if (!isset($datosAdjunto['error'])){
        if (isset($datosAdjunto['Nitem'])){
            // Hubo solo un resultado. ( aunque no tiene mucho sentido que exista Nitem.. :-)
            $respuesta['temporales']=1;
            $fecha =$datosAdjunto['Fecha'];
            if ($dedonde=="albaran"){
                $respuesta['datos']['NumAdjunto']=$datosAdjunto['Numpedpro'];
                $respuesta['datos']['idAdjunto']=$datosAdjunto['id'];
                $productosAdjunto=$CPed->ProductosPedidos($datosAdjunto['id']);
                if (isset($productosAdjunto['error'])){
                    $respuesta['error']=$productosAdjunto['error'];
                    $respuesta['consulta']=$productosAdjunto['consulta'];
                }else{
                    $respuesta['productos']=$productosAdjunto;
                }
            }else{
                $respuesta['datos']['NumAdjunto']=$datosAdjunto['Numalbpro'];
                $respuesta['datos']['idAdjunto']=$datosAdjunto['id'];
                $respuesta['datos']['totalSiva']=$datosAdjunto['totalSiva'];
                $respuesta['datos']['Su_numero']=$datosAdjunto['Su_numero'];
                $productosAdjunto=$CAlb->ProductosAlbaran($datosAdjunto['id']);
                if (isset($productosAdjunto['error'])){
                    $respuesta['error']=$productosAdjunto['error'];
                    $respuesta['consulta']=$productosAdjunto['consulta'];
                }else{
                    $respuesta['productos']=$productosAdjunto;
                }
            }
            $date = new DateTime($fecha);
            $respuesta['datos']['fecha']=date_format($date, 'Y-m-d');
            $respuesta['datos']['total']=$datosAdjunto['total'];
            $respuesta['datos']['estado']="activo";
            
            $respuesta['Nitems']=$datosAdjunto['Nitem'];
        } else {
            // Hubo varios resultado y mostramos modal.
            $respuesta['datos']=$datosAdjunto;
            $modal=modalAdjunto($datosAdjunto['datos'], $dedonde, $BDTpv);
            $respuesta['html']=$modal['html'];
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
