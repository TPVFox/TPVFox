<?php 

function htmlInputRatio($tiposTiendas,$tipo_tienda_actual){
    // @ Objetivo:
    // $tiposTiendas -> Array con los datos tipos tiendas que tenemos
    // $tipo_tienda_actual -> String con el tipo tienda que tenemos checked
    $html = '';
    $inputHidden = '';
    foreach ($tiposTiendas as $key =>$tipoTienda){
        if ($tipoTienda['montar_radio'] === 'Si'){
            // Solo monstamos los que indicamos en array
            if ($tipo_tienda_actual === $key) {
                $checked = "checked"; // Marcamos check que tipo empresa es.
            } else {
                $checked = "";
            }
            $html .= '<label class="radio-inline">';
            $html .= '<input type="radio" name="tipoTienda" value="'.$key.
                '" title="'.$tipoTienda['texto_title'].'" '.$checked.'  '.$tipoTienda['disabled'].'>'.
                $tipoTienda['title'].'</label>';
            // Si lo tenemos disabled y checked enviamos input oculto para enviar en formulario.
            if (($tipoTienda['disabled'] === 'disabled') && ($checked === 'checked')){
                $inputHidden= '<input type="hidden" name ="tipoTienda" value="'.$key.'">';
            }
        }
    }
    // Si es disabled los inputs radios, entonces el que esta marcado lo mandamos oculto...
    $html .= $inputHidden;
    return $html;
}

function htmlEstados($estado_actual){
    // Definimos posibles estados para Select.
    $estados = array(
        '0' => array( 
                'valor' =>'cerrado',
                'porDefecto' => 'selected' // Indicamos por defecto
                ),
        '1' => array(
                'valor' =>'activo',
                'porDefecto' =>  ''
                )
    );
    // Obtenemos el estado que tiene la tienda.
    foreach ($estados as $key => $estado){
        if ( $estado_actual== $estado['valor']){
            $estados[$key]['porDefecto'] = "selected"; // Indicamos por defecto
        } else {
            $estados[$key]['porDefecto'] = ''; // Indicamos por defecto
        }
    }
    $html = '<label for="sel1">Estado:'
            .'<select class="form-control" name="estado" id="sel1">';
    foreach ($estados as $estado){
        $html .= '<option value="'.$estado['valor'].'" '.(isset($estado['porDefecto']) ? $estado['porDefecto'] : '').'>'
                .$estado['valor'].'</option>';
        
    }
    $html .= '</select></label>';

    return $html;
}






//parametros: 
//datos array de post 
//BDTpv conexion bbdd tpv
//tabla en la que trabajar usuarios
//idSelecc , usuario concreto a modificar , check seleccionado en listaUsuarios
function modificarDatos($datos,$BDTpv,$tabla,$idTienda){
	// @ Parametros:
	//    $datos= Array con datos de tabla que vamos a guarda.
	//    $tabla= nombre de la tabla.
	$resultado = array();
	
	
	// [PENDIENTE VER COMO HACER UPDATE AUTOMATICO, SEGUN TIPO DE TIENDA...

	$resultado['Rdatos']= $datos;
	$updateSet = array();
	foreach ($datos as $key => $dato){
		$updateSet[]= $key.'="'.$dato.'"';
	}
	$envioUpdate = implode(',',$updateSet);
	
	
	 
	$sql ='UPDATE '.$tabla.' SET '.$envioUpdate.' WHERE idTienda ='.$idTienda;

	$resultado['consulta']= $sql;
	if ($consulta = $BDTpv-> query($sql)){
		// Ya modificamos.
		// Comprobamos que solo modifique un registro, si son mas hubo error grave.
		$resultado['Num_registros'] = $BDTpv->affected_rows;
		if ($resultado['Num_registros'] > 1){
			// Quiere decir que el resultado esta mal, ya que cambio dos registros.
			$resultado['error'] = 'Error, modifico dos registros';
		}
	} else {
		// Quiere decir que hubo un error en la consulta.
		$resultado['error'] = 'Error en consulta';
		$resultado['numero_error_Mysql']= $BDTpv->errno;
	
	}
	

	return $resultado;
}

?>
