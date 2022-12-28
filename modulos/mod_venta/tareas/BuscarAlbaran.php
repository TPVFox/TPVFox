<?php 
//@ Objetivo:
//Busca un albarán si lo indicado en busqueda, sino lo indica, busca todos los albaranes con estado guardado de un cliente.
//@ resultado
//Si busca uno , muestra resultado de este, sino da advertencia que no encontro.
//Si busca todos, muestra numero de cuanto tiene en estado guardado, pero solo muestra 15 como maximo. Si no tuviera muestrar advertencia que no encontro.
    $busqueda   = $_POST['busqueda'];
    $idCliente  = $_POST['idCliente'];
    $dedonde    = $_POST['dedonde']; 
    // Como este mismo fichero va valer para buscar Pedidos o Albaranes, con loque, sabemos que buscar.
    
    $res=$CalbAl->AlbaranClienteGuardado($busqueda, $idCliente);
    $respuesta['res'] = json_encode($res);
    if (isset($res['error'])){
        $respuesta['error']     = $res['error'];
        $respuesta['consulta']  = $res['consulta'];
    }else{
        if ($res['Nitems'] == 1){
            $datos = $res['datos']['0'];
            // Ahora los datos cabecera albaran , los combertimos para adjunto
            $respuesta['datos'] = prepararCaberaAdjuntoTemporal($datos,$dedonde)
            // Ahora obtenemos los productos del albaran.
            $productosAlbaran   = $CalbAl->ProductosAlbaran($datos['id']);
            $productosAlbaran   = modificarArrayProductos($productosAlbaran);
            if(isset($productosAlbaran['error'])){
                $respuesta['error']     = $productosAlbaran['error'];
                $respuesta['consulta']  = $productosAlbaran['consulta'];
            } else {
                $respuesta['productos'] = $productosAlbaran;
            }
        }else{
            $respuesta          = $res;
            $modal              = modalAdjunto($res['datos']);
            $respuesta['html']  = $modal['html'];
        }
        $respuesta['Nitems'] = $res['Nitems'];
    }

?>
