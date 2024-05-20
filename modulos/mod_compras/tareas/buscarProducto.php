<?php
//@Objetivo;
//Busqueda de productos: Recive el valor a buscar el campo por el que tiene que buscar 
    $busqueda = $_POST['valorCampo'];
    $campoAbuscar = $_POST['campo'];
    $id_input = $_POST['id_input'];
    $idcaja=$_POST['idcaja'];
    $idProveedor=$_POST['idProveedor'];
    $dedonde=$_POST['dedonde'];
    
    $res = BuscarProductos($id_input,$campoAbuscar, $idcaja, $busqueda,$BDTpv, $idProveedor);
    //~ $respuesta['consulta']= $res['sql'];
    if (isset($res['datos'])){
        // fue Correcta la consulta.
        $respuesta['Nitems']= count($res['datos']);
        if ($respuesta['Nitems'] == 1 && $idcaja<>"cajaBusqueda"){
            $respuesta['datos']=$res['datos'];
        } else {
            $respuesta['html'] = htmlProductos($res['datos'],$id_input,$campoAbuscar,$busqueda, $dedonde);
        }
    } else {
        // Hubo un error en la consulta 
        $respuesta['error'] = $res;
    }
    return $respuesta;
?>
