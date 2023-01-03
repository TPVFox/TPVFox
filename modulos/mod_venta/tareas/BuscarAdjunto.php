<?php 
//@ Objetivo:
//Busca un adjunto (albarán o pedido) segun de donde venga.
//Si el $_POST['busqueda'] viene vacio , busca todos los albaranes con estado guardado de un cliente, por el contrario solo el que sea igual a lo buscado.
//@ resultado
//Si busca uno , muestra resultado de este, sino da advertencia que no encontro.
//Si busca todos, muestra numero de cuanto tiene en estado guardado, pero solo muestra 15 como maximo. Si no tuviera muestrar advertencia que no encontro.
    $busqueda   = $_POST['busqueda'];
    $idCliente  = $_POST['idCliente'];
    $dedonde    = $_POST['dedonde']; 
    // Como este mismo fichero va valer para buscar Pedidos o Albaranes, con loque, sabemos que buscar.
    if ($dedonde =='factura'){
        $res=$CalbAl->AlbaranClienteGuardado($busqueda, $idCliente);
    } else {
        $res=$CcliPed->PedidosClienteGuardado($busqueda, $idCliente);
    }
    $respuesta['res'] = json_encode($res);
    if (isset($res['error'])){
        $respuesta['error']     = $res['error'];
        $respuesta['consulta']  = $res['consulta'];
    }else{
        $respuesta['Nitems'] = $res['Nitems'];
        if ($res['Nitems'] == 1 && $busqueda !=='') {
            // Se busco uno en concrecto y respondio uno.
            $datos = $res['datos']['0'];
            // Ahora los datos cabecera adjunto, lo estandarizamos.
            $respuesta['cabecera_adjunto'] = prepararCaberaAdjuntoTemporal($datos,$dedonde);
            // Ahora obtenemos los productos del adjunto.
            if ($dedonde =='factura'){
                $productos  = $CalbAl->ProductosAlbaran($datos['id']);
            } else {
                $productos=$CcliPed->ProductosPedidos($datos['id']);
            }
            $productos   = modificarArrayProductos($productos);
            if(isset($productos['error'])){
                $respuesta['error']     = $productos['error'];
                $respuesta['consulta']  = $productos['consulta'];
            } else {
                $respuesta['productos'] = $productos;
            }
        } else {
            $modal              = modalAdjunto($res['datos']);
            $respuesta['html']  = $modal['html'];
        }
    }

?>
