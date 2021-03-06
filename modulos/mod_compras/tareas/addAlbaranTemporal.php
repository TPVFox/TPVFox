<?php 
//@ Objetivo:
///Añadir los datos de albaran a temporal , cuando lo añadimos o modificamos, cada dato que introduzcamos
    //ya que en caso perder conexion siempre estan los datos.
    $respuesta 	= array();
    $pedidos 	= array();
    $errores    = array();
    $idAlbaranTemporal=$_POST['idTemporal'];
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
    $idAlbaran=$_POST['idReal'];
    $fecha=$_POST['fecha'];
    $fecha = new DateTime($fecha);
    $fecha = $fecha->format('Y-m-d');
    $hora="";
    if(isset($_POST['hora'])){
        $hora=$_POST['hora'];
    }
    if($hora !=""){
        $fecha1=$fecha.' '.$hora.':00';
        $fecha=date_format(date_create($fecha1), 'Y-m-d H:i:s');
    }
    $productos=json_decode($_POST['productos']);
    if (isset($_POST['pedidos'])){
        $pedidos=$_POST['pedidos'];
    }
    $idProveedor=$_POST['idProveedor'];
    $suNumero=$_POST['suNumero'];
    if ($idAlbaranTemporal>0){
        // El temporal ya esta creado.
        $rest=$CAlb->modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estado, $fecha ,  $idAlbaranTemporal, $productos, $pedidos, $suNumero);
        if(isset($rest['error'])){
            array_push($errores,$CFac->montarAdvertencia(
                                'danger',
                                'Error add 1:'.$rest['error'].' .Consulta:'.$rest['consulta']
                                ,'KO')
                        );
        }else{
                $existe=1;
            }
    }else{
        //Si no existe el temporal se crea , con control de errores 
        $rest=$CAlb->insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estado, $fecha ,  $productos, $idProveedor, $pedidos, $suNumero);
        if(isset($rest['error'])){
            array_push($errores,$CFac->montarAdvertencia(
                                'danger',
                                'Error add 2:'.$rest['error'].' .Consulta:'.$rest['consulta']
                                ,'KO')
                        );
        }else{
            $existe=0;
            $idAlbaranTemporal=$rest['id'];
        }
    }
    if ($idAlbaran>0 && count($errores)===0){
        // Agregamos el numero de la albaran si ya existe y no hubo errores
        $modId=$CAlb->addNumRealTemporal($idAlbaranTemporal, $idAlbaran);
        if(isset($modId['error'])){
            array_push($errores,$CFac->montarAdvertencia(
                                'danger',
                                'Error add 3:'.$modId['error'].' .Consulta:'.$modId['consulta']
                                ,'KO')
                        );
        }else{
		$estado="Sin Guardar";
		$modEstado=$CAlb->modEstadoAlbaran($idAlbaran, $estado);
            if (isset($modEstado['error'])){
                array_push($errores,$CFac->montarAdvertencia(
                                'danger',
                                'Error add 4:'.$modEstado['error'].' .Consulta:'.$modEstado['consulta']
                                ,'KO')
                        );
            }
	}
    }
    if (isset($productos) && count($errores)===0){
        $CalculoTotales = $CAlb->recalculoTotales($productos);
        $respuesta['total']=round($CalculoTotales['total'],2);
        $respuesta['totales']=$CalculoTotales;
        $modTotal=$CAlb->modTotales($idAlbaranTemporal, $respuesta['total'], $CalculoTotales['subivas']);
        if (isset($modTotal['error'])){
            array_push($errores,$CFac->montarAdvertencia(
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
    $respuesta['id']=$idAlbaranTemporal;
    $respuesta['existe']=$existe;
    $respuesta['productos']=$_POST['productos'];

?>
