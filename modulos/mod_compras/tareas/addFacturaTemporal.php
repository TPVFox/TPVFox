<?php 
//@Objetivo:
    //Añadir los datos de factura a temporal , cuando lo añadimos o modificamos, cada dato que introduzcamos
    //ya que en caso perder conexion siempre estan los datos.
    $respuesta  = array();
    $albaranes  = array();
    $errores    = array();
    $idFacturaTemp=$_POST['idTemporal'];
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
    $idFactura=$_POST['idReal'];
    $fecha=$_POST['fecha'];
    /* Codigo para validar una fecha*/
    $valores = explode('-', $fecha);
    $fControl= 'KO';
    if(count($valores) == 3){
        if (checkdate($valores[1], $valores[0], $valores[2])===true){
            $fControl = 'OK';
        }
    }
    if ($fControl === 'OK'){
        $fecha = new DateTime($fecha);
        // Se produce un error cuando la fecha por post, no viene correcta.
        // no identifico cuando se produce ese error.
        $fecha = $fecha->format('Y-m-d');
        $productos=json_decode($_POST['productos']);
        if(isset ($_POST['albaranes'])){
            $albaranes=$_POST['albaranes'];
        }
        $idProveedor=$_POST['idProveedor'];
        $suNumero=$_POST['suNumero'];
        if ($idFacturaTemp>0){
            // El temporal ya esta creado.
            $rest=$CFac->modificarDatosFacturaTemporal($_POST['idUsuario'], $_POST['idTienda'], $estado, $fecha ,  $idFacturaTemp, $productos, $albaranes, $suNumero);
            if(isset($rest['error'])){
                array_push($errores,$CFac->montarAdvertencia(
                                    'danger',
                                    'Error add 1:'.$rest['error'].' .Consulta:'.$rest['consulta'],
                                    'KO')
                            );
            }
        }else{
            //Si no existe el temporal se crea , con control de errores 
            $rest=$CFac->insertarDatosFacturaTemporal($idUsuario, $idTienda, $estado, $fecha ,  $productos, $idProveedor, $albaranes, $suNumero);
            if(isset($rest['error'])){
                array_push($errores,$CFac->montarAdvertencia(
                                    'danger',
                                    'Error add 2:'.$rest['error'].' .Consulta:'.$rest['consulta'],
                                    'KO')
                            );
            }else{
                $idFacturaTemp=$rest['id'];
            }
        }
    } else {
        // Esta mal la fecha:
        array_push($errores,$CFac->montarAdvertencia(
                                    'danger',
                                    'La fecha es erronea : '.$fecha,
                                    'KO')
                    );
    }
    if ($idFactura>0 && count($errores)===0){
        // Agregamos el numero de la factura si ya existe y no hubo errores
        $modId=$CFac->addNumRealTemporal($idFacturaTemp, $idFactura);
        if(isset($modId['error'])){
            array_push($errores,$CFac->montarAdvertencia(
                                'danger',
                                'Error add 3:'.$modId['error'].' .Consulta:'.$modId['consulta'],
                                'KO')
                        );
        }else{
            $estado="Sin Guardar";
            $modEstado=$CFac->modEstadoFactura($idFactura, $estado);
            if (isset($modEstado['error'])){
                array_push($errores,$CFac->montarAdvertencia(
                                'danger',
                                'Error add 4:'.$modEstado['error'].' .Consulta:'.$modEstado['consulta'],
                                'KO')
                        );
            }
        }
    }
    if (isset($productos) && count($errores)===0){
        $CalculoTotales = recalculoTotales($productos);
        $total=round($CalculoTotales['total'],2);
        $respuesta['total']=round($CalculoTotales['total'],2);
        $respuesta['totales']=$CalculoTotales;
        $modTotal=$CFac->modTotales($idFacturaTemp, $respuesta['total'], $CalculoTotales['subivas']);
        
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
    $respuesta['id']=$idFacturaTemp;
    
    $respuesta['productos']=$_POST['productos'];
?>
