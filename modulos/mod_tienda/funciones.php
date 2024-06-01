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
                'valor' =>'Activo',
                'porDefecto' =>  ''
                )
    );
    $html = htmlSelect($estados,array('label'=>'Estado','id'=>'sel1','name'=>'estado'),$estado_actual);
    return $html;
}

function htmlSelect($opciones,$atributos = array(),$valor_select =''){
    // @ Objetivo:
    // Crear un html de Select
    // @ Parametros:
    // $opciones -> array(), lista de arrays con las opciones.
    //          Ejemplo = array ('0'=>array('valor' => 1, 'texto' = 'Si','porDefecto' ='select'),
    //                          ('1'=>array('valor' => 2, 'texto' = 'No','porDefecto' ='select'),
    //                           );
    //                  El indice texto, no es necesario enviarlo, se toma el valor como texto.
    // $atributos -> array (), con los atributos del select, no se oblicatorio.
    //          Ejemplo = array( 'id' => 'select1',
    //                           'name' =>'campo_algo',
    //                           'label'=>'Estado')
    // $valor_select -> Que opcion esta seleccionada.
    // @ Devolvemos:
    //  string -> html con select o con texto error de que el valor indicado no se encuentra.
    $control_valor_select = 'KO';
    // Comprobamo atributos necesarios
    $id = '';
    $name = '';
    $label = '';
    if (isset($atributos['name'])){
        $name = 'name="'.$atributos['name'].'"';
        if (!isset($atributos['label'])){
            $label = $atributos['name'];
        }
    }
    if (isset($atributos['id'])){
        $id = 'id="'.$atributos['id'].'"';
        if ($name === ''){
            // Si existe id , pero name lo creamos iguales.
            $name = 'name="'.$atributos['id'].'"';
            if (!isset($atributos['label'])){
                $label = $atributos['id'];
            }
        }
    }
    if (isset($atributos['label'])){
        $label =$atributos['label'];
        if ($name === ''){
            // Si existe label , pero name lo creamos iguales.
            $name = 'name="'.$atributos['label'].'"';
        }
    } 
    if ($name === ''){
        // Si no hay name creamos uno igualmente.
        $name = 'name="sel1"';
        
    }
    if ($label === ''){
        // Si no hay label indicamos...
        $label = 'Sin identificar';
    }
    $html = '<label>'.$label.':'
            .'<select class="form-control" '.$name.' '.$id.'">';

    foreach ($opciones as $opcion){
        $seleccionado = '';
        if ( isset($opcion['porDefecto'])){
            $seleccionado = $opcion['porDefecto'];
        }
        if ( $valor_select == $opcion['valor']){
            $seleccionado = "selected"; // Indicamos por defecto
            $control_valor_select = 'OK';
        }
        $texto = isset($opcion['texto']) ? $opcion['texto'] : $opcion['valor'];
        $html .= '<option value="'.$opcion['valor'].'" '.$seleccionado.'>'
                .$texto.'</option>';
    }
    $html .= '</select></label>';
    if ( $valor_select !== ''){
        // Si no envia valor seleccionado , este control es OK siempre
        $control_valor_select = 'OK';
    } else  {
        if ($control_valor_select = 'KO'){
            // Se mando valor_select pero no coincide con ningun valor de las opciones, se manda string con error
            $html ='<span>Mandas valor seleccionado pero no coincide con ninguna opcion</span>';
        }
    }
    return $html;
}


function matrizError($tipo,$mensaje){
    $error = array ('tipo'     => $tipo,
                    'mensaje'  => $mensaje
                    );
    return $error;
}

function obtenerTiendaPrincipalYWeb($ClaseTienda){
    // @ Objetivo
    // Obtener array de tienda principal y tienda web si existe
    // @ devolvemos array de tiendas que existen, las que no existen no.
    $tiendas =array();
    $t = $ClaseTienda->tiendasWeb();
    if (isset($t['datos']['0'])){
        $tiendas['web'] = $t['datos']['0'];
    }
    $t = $ClaseTienda->tiendaPrincipal();
    if (isset($t['datos']['0'])){
        $tiendas['principal'] = $t['datos']['0'];
    }
    return $tiendas;

}

function htmlAdvertencias($errores){
    $html = '';
    if (count($errores) > 0){
        foreach ($errores as $error){
            $html .=  '<div class="alert alert-'.$error['tipo'].'">'.$error['mensaje'].'</div>';
        }
    }
    return $html;

}

?>
