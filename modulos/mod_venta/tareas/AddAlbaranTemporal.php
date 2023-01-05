<?php 
	//@ Objetivo:
    //  Añadir Albaran temporal hace exactamente lo mismo que el añadir pedido temporal pero esta vez con albaranes
    //  Deberiamos:
    //    - STANDARIZAR en un proceso unico.
    //    - Comprobar si los productos tiene numero albaran o pedido, que venga los adjuntos.

    $idAlbaranTemp=$_POST['idTemporal'];
    $idUsuario=$_POST['idUsuario'];
    $idTienda=$_POST['idTienda'];
    $numAlbaran=$_POST['idReal'];
    $fecha=$_POST['fecha'];
    $fecha = new DateTime($fecha);
    $fecha = $fecha->format('Y-m-d');
    $productos=json_decode($_POST['productos']);
    $idCliente=$_POST['idCliente'];
    if (isset($_POST['pedidos'])){
        $pedidos=$_POST['pedidos'];
    }else{
        $pedidos=array();
    }
    $respuesta=array();
    $existe=0;
    if ($idAlbaranTemp>0){
        $rest=$CalbAl->modificarDatosAlbaranTemporal($idUsuario, $idTienda, $fecha , $pedidos, $idAlbaranTemp, $productos);
        if (isset($rest['error'])){
            $respuesta['error']=$rest['error'];
            $respuesta['consulta']=$rest['consulta'];
        }else{
            $existe=1;	
        }
    }else{
       $rest=$CalbAl->insertarDatosAlbaranTemporal($idUsuario, $idTienda, $fecha , $pedidos, $productos, $idCliente);
       //~ error_log(json_encode($rest));
        if (isset($rest['error'])){
            $respuesta['error']=$rest['error'];
            $respuesta['consulta']=$rest['consulta'];
        }else{
            $existe=0;
            $idAlbaranTemp=$rest['id'];
        }
    }
    $respuesta['numAlbaran']=$numAlbaran;
    if ($numAlbaran>0){
        $modId=$CalbAl->addNumRealTemporal($idAlbaranTemp,$numAlbaran);
        if (isset($modId['error'])){
            $respuesta['error']=$modId['error'];
            $respuesta['consulta']=$modId['consulta'];
        }
    }
    if (isset($productos)){
        $CalculoTotales = recalculoTotales($productos);
        $total=round($CalculoTotales['total'],2);
        $respuesta['total']=round($CalculoTotales['total'],2);
        $respuesta['totales']=$CalculoTotales;
        $modTotal=$CalbAl->modTotales($idAlbaranTemp, $respuesta['total'], $CalculoTotales['subivas']);
        if (isset($modTotal['error'])){
            $respuesta['error']=$modTotal['error'];
            $respuesta['consulta']=$modTotal['consulta'];
        }
        $htmlTotales=htmlTotales($CalculoTotales);
        $respuesta['htmlTabla']=$htmlTotales['html'];
    }
    $respuesta['id']=$idAlbaranTemp;
    $respuesta['existe']=$existe;
    $respuesta['productos']=$_POST['productos'];
?>
