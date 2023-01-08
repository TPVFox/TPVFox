<?php 
	//@ Objetivo:
    //  Añadir factura temporal hace exactamente lo mismo que el añadir albarán temporal pero esta vez con facturas
    //  Deberiamos:
    //    - STANDARIZAR en un proceso unico.
    //    - Comprobar si los productos tiene numero albaran o pedido, que venga los adjuntos.
    
    $idTemporal=$_POST['idTemporal'];
    $idUsuario=$_POST['idUsuario'];
    $idTienda=$_POST['idTienda'];
    $numDocumento=$_POST['idReal'];
    $fecha=$_POST['fecha'];
    $fecha = new DateTime($fecha);
    $fecha = $fecha->format('Y-m-d');
    $productos=json_decode($_POST['productos']);
    $idCliente=$_POST['idCliente'];
    $adjuntos = array();
    if (isset($_POST['adjuntos'])){
        $adjuntos=$_POST['adjuntos'];
    }
    if ($_POST['dedonde'] =='albaran'){
        $Clase= $CalbAl;
    }
    if ($_POST['dedonde'] =='factura'){
        $Clase= $CFac;
    }
    $respuesta=array();
    $existe=0;
    if ($idTemporal>0){
        $rest=$Clase->modificarDatosTemporal($idUsuario, $idTienda, $fecha , $adjuntos, $idTemporal, $productos);
        if (isset($rest['error'])){
            $respuesta['error']=$rest['error'];
            $respuesta['consulta']=$rest['consulta'];
        }else{
            $existe=1;	
        }
    }else{
        $rest=$Clase->insertarDatosTemporal($idUsuario, $idTienda,  $fecha , $adjuntos, $productos, $idCliente);
        if (isset($rest['error'])){
            $respuesta['error']=$rest['error'];
            $respuesta['consulta']=$rest['consulta'];
        }else{
            $existe=0;
            $idTemporal=$rest['id'];
        }
        
    }
    $respuesta['numDocumento']=$numDocumento;
    if ($numDocumento>0){
        $modId=$Clase->addNumRealTemporal($idTemporal, $numDocumento);
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
        $modTotal=$Clase->modTotales($idTemporal, $respuesta['total'], $CalculoTotales['subivas']);
        if (isset($modTotal['error'])){
            $respuesta['error']=$modTotal['error'];
            $respuesta['consulta']=$modTotal['consulta'];
        }
        $htmlTotales=htmlTotales($CalculoTotales);
        $respuesta['htmlTabla']=$htmlTotales['html'];
        
    }
    $respuesta['id']=$idTemporal;
    $respuesta['existe']=$existe;
    $respuesta['productos']=$_POST['productos'];
?>
