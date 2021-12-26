<?php
//@ Objetivo:
//Añadir un pedido temporal, recibe los campos necesarios para añadir el pedido
    //Si ya existe modifica el registro si no lo crea, devuelve siempre el id del temporal
    $respuesta 	= array();
    $errores    = array();
    $idPedidoTemporal=$_POST['idTemporal'];
    $idUsuario=$_POST['idUsuario'];
    $idTienda=$_POST['idTienda'];
    // Ahora compruebo estado, ya que si esta Guardado, tengo que pasarlo a Sin Guardar.
    if ($_POST['estado'] === 'Guardado'){
        // No cambio los que son estado Nuevo ,ya que esos no estan grabado, por lo que
        // los dejamos como Nuevo en temporal.
        $estado = 'Sin Guardar';
    } else {
        $estado=$_POST['estado'];               
    }
    $idPedido=$_POST['idReal'];
    $fecha=$_POST['fecha'];
    $fecha = new DateTime($fecha);
    $fecha = $fecha->format('Y-m-d');
    $productos=json_decode($_POST['productos']);
    $idProveedor=$_POST['idProveedor'];
    $existe=0; // Variable para devolver y saber si modifico o insert.
    //Existe la utilizo como bandera para que el javascript solo me cree una vez la url del temporal
    if ($idPedidoTemporal>0){
        // El temporal ya esta creado.
        $rest=$CPed->modificarDatosPedidoTemporal($idUsuario, $idTienda, $estado, $fecha ,  $idPedidoTemporal, $productos);
        if (isset($rest['error'])){
                $respuesta['error']=$rest['error'];
                $respuesta['consulta']=$rest['consulta'];
        }else{
            $existe=1;
        }
    }else{
        //Si no existe el temporal se crea , con control de errores 
        $rest=$CPed->insertarDatosPedidoTemporal($idUsuario, $idTienda, $estado, $fecha ,  $productos, $idProveedor);
        if(isset($rest['error'])){
            array_push($errores,$CPed->montarAdvertencia(
                                'danger',
                                'Error add 2:'.$rest['error'].' .Consulta:'.$rest['consulta']
                                ,'KO')
                        );
        }else{
            $existe=0;
            $idPedidoTemporal=$rest['id'];
        }
    }
    if ($idPedido>0 && count($errores)===0){
        // Agregamos el numero de la pedido si ya existe y no hubo errores
        $modId=$CPed->addNumRealTemporal($idPedidoTemporal, $idPedido);
        if (isset($modId['error'])){
            array_push($errores,$CFac->montarAdvertencia(
                                'danger',
                                'Error add 3:'.$modId['error'].' .Consulta:'.$modId['consulta']
                                ,'KO')
                        );
        } else {
        // Se modifica el estado del pedido real a sin guardar
        $modEstado=$CPed->modEstadoPedido($idPedido, $estado);
            if (isset($modEstado['error'])){
                array_push($errores,$CPed->montarAdvertencia(
                                'danger',
                                'Error add 4:'.$modEstado['error'].' .Consulta:'.$modEstado['consulta']
                                ,'KO')
                        );
            }
        }
    }
    if (isset($productos) && count($errores)===0){
    //Recalcula el valor de los productos
        $CalculoTotales = $CPed->recalculoTotales($productos);
        $respuesta['total']=round($CalculoTotales['total'],2);
        $respuesta['totales']=$CalculoTotales;
        $modTotal=$CPed->modTotales($idPedidoTemporal, $respuesta['total'], $CalculoTotales['subivas']);
        if (isset($modTotal['error'])){
            array_push($errores,$CPed->montarAdvertencia(
                                'danger',
                                'Error add 5:'.$modTotal['error'].' .Consulta:'.$modTotal['consulta']
                                ,'KO')
                        );
        }
        $respuesta['sqlmodtotal']=$modTotal['sql'];
        $htmlTotales=htmlTotales($CalculoTotales);
        $respuesta['htmlTabla']=$htmlTotales['html'];
    }
    if (count($errores)> 0){
        $respuesta['error'] = $errores; // Devolvemos array no html.
    }
    
    $respuesta['id']=$idPedidoTemporal;
    $respuesta['existe']=$existe;
    $respuesta['productos']=$_POST['productos']; 
?>
