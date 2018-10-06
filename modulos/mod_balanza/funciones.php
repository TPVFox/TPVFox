<?php 
function htmlPanelDesplegable($num_desplegable,$titulo,$body){
	// @ Objetivo:
	// Montar html de desplegable.
	// @ Parametros:
	// 		$num_desplegable -> (int) que indica el numero deplegable para un correcto funcionamiento.
	// 		$titulo-> (string) El titulo que se muestra en desplegable
	// 		$body-> (String) lo que contiene el desplegable.
	// Ejemplo tomado de:
	// https://www.w3schools.com/bootstrap/tryit.asp?filename=trybs_collapsible_panel&stacked=h 
	
	$collapse = 'collapse'.$num_desplegable;
	$html ='<div class="panel panel-default">'
			.		'<div class="panel-heading">'
			.			'<h2 class="panel-title">'
			.			'<a data-toggle="collapse" href="#'.$collapse.'">'
			.			$titulo.'</a>'
			.			'</h2>'
			.		'</div>'
			.		'<div id="'.$collapse.'" class="panel-collapse collapse">'
			.			'<div class="panel-body">'
			.				$body
			.			'</div>'
			.		'</div>'
			.'</div>';
	return $html;
	 
}
function  htmlTablaPlus($plus, $id){
	// @ Objetivo
	// Montar la tabla html de codbarras
	// @ Parametros
	// 		$codBarras -> (array) con los codbarras del producto.
	$html =	 '<table id="tPlus" class="table table-striped">'
			.'		<thead>'
			.'			<tr>'
			.'				<th>Plus</th>'
			.'				<th>'.'<a id="agregar" onclick="htmlPlu('.$id.')">Añadir'
			.'					<span class="glyphicon glyphicon-plus"></span>'
			.'					</a>'
			.'				</th>'
			.'			</tr>'
            .'          <tr  id="addPlu" colspan="2">
                        
                        </tr>' 
            .'          <tr>'
            .'          <th>PLU</td>'
            .'          <th>Tecla</td>'
            .'          <th>idArticulo</td>'
            .'          <th>Referencia</td>'
            .'          <th>Nombre</td>'
            .'          <th>PVP</td>'
            .'          <th></td>'
            .'          </tr>' 
			.'		</thead>'
			.'		<tbody>';
	if (count($plus)>0){
		foreach ($plus as $item=>$valor){
			$html .= htmlLineaPlu($valor , $id);
		}
	}
	$html .= '</tbody> </table>	';
	return $html;
} 

function htmlLineaPlu( $plu, $idBalanza){
    //@OBjetivo: imprimir las lineas de plus de una balanza con los datos de un articulo
    $nuevaFila = '<tr id="plu_'.$plu['plu'].'">'
				. '<td><input type="hidden" id="idPlu_'.$plu['plu']
				.'" name="idPlu'.$plu['plu'].'" value="'.$plu['plu'].'">'
				.$plu['plu'].'</td>'
				
                .'<td>'.$plu['tecla'].'</td>'
                .'<td>'.$plu['idArticulo'].'</td>'
                .'<td>'.$plu['crefTienda'].'</td>'
                .'<td>'.$plu['articulo_name'].'</td>'
                .'<td>'.number_format($plu['pvpCiva'],2).'</td>'
                .'<td><a id="eliminar_'.$plu['plu']
				.'" class="glyphicon glyphicon-trash" onclick="eliminarPlu('."'".$plu['plu']."'".', '.$idBalanza.')"></a>'
				.'</td>'.'</tr>';
	return $nuevaFila;
}
function htmlAddPLU($tecla, $idBalanza){
    //@OBjetivo_: devolver html con los datos para poder añadir plu
    $style_none = ' ';
    if($tecla=='no'){
        $style_none = 'style="display:none"';
    }

    $html='<th colspan="5">'
            .'<div class="col-md-12">'
                .'<label>Plu:</label>'
				.'<input type="text" name="plu" id="plu" value="" >'
            .'</div>'
            .'<div class="col-md-12" '.$style_none.'>'
               .'<label>Tecla:</label>'
               .'<input type="text" name="teclaPlu" id="teclaPlu" value="" >';
    
    $html.='</div>'
            .'<div>'
                .'<label>Opciones de busqueda de los productos:</label>'
                .'<div class="col-md-1">'
                    .'<label>Id:</label>'
                    .'<input type="text" name="idArticulo" id="idArticulo" data-obj="cajaidArticulo" onkeydown="controlEventos(event)" value="" size="2px">'
                .'</div>'
                .'<div class="col-md-5">'
                    .'<label>Nombre:</label>'
                    .'<input type="text" name="nombreProducto" id="nombreProducto" data-obj="cajanombreProducto" onkeydown="controlEventos(event)" value="" size="20px">'
                .'</div>'
                .'<div class="col-md-3">'
                    .'<label>Referencia:</label>'
                    .'<input type="text" name="referencia" id="referencia" data-obj="cajareferencia" onkeydown="controlEventos(event)" value="" size="10px">'
                .'</div>'
                .'<div class="col-md-3">'
                    .'<label>Cod Barras:</label>'
                    .'<input type="text" name="codBarras" id="codBarras" data-obj="cajacodBarras" onkeydown="controlEventos(event)" value="" size="10px">'
                .'</div>'
            .'</div>'
        .'<div>'
            .'<div class="col-md-4"><label></label>'
            .'<a class="btn btn-success" onclick="addPlu('.$idBalanza.')">Añadir</a>'
            .'</div>'
        .'</div>';
    return $html;
}
function camposBuscar($campo, $busqueda){
    //@objetivo: devolver el string con el campo y busqueda preparado para el sql
    if($campo=='a.idArticulo'){
        $campo='a.idArticulo='.$busqueda;
    }else{
        $campo=$campo.' like "%'.$busqueda.'%"';
    }
    return $campo;
}

function modalProductos($busqueda, $productos, $campoAbuscar){
    //@OBjetivo: devolver html con los datos del modal
    $resultado = array();
	$resultado['encontrados'] = count($productos);
    $resultado['html'] =  "<script type='text/javascript'>
			 ".
			 "cajaBusquedaProducto.parametros.campo="."'".$campoAbuscar."';
			idN.parametros.campo.__defineSetter__ ="."'".$campoAbuscar."';
			</script>";
	$resultado['html'] .= '<label>Busqueda Producto </label>';
	$resultado['html'] .= '<input id="cajaBusquedaProducto" name="valorProducto" placeholder="Buscar"'.
				'size="13" data-obj="cajaBusquedaProducto" value="'.$busqueda.'"
				 onkeydown="controlEventos(event)" type="text">';
				
	if (count($productos)>10){
		$resultado['html'] .= '<span>10 productos de '.count($productos).'</span>';
	}
	$resultado['html'] .= '<table class="table table-striped"><thead>'
	. ' <th></th> <th>id</th><th>Nombre</th><th>Referencia</th></thead><tbody>';
	if (count($productos)>0){
		$contad = 0;
		foreach ($productos as $producto){  
			$resultado['html'] .= '<tr id="Fila_'.$contad.'" class="FilaModal" onclick="buscarProductosModal('.
            $producto['idArticulo'].', '."'".$producto['articulo_name']."'".', '."'".$producto['crefTienda']."'".', '.
            "'".$producto['codBarras']."'".');" >';
		
			$resultado['html'] .= '<td id="C'.$contad.'_Lin" >';
			$resultado['html'] .= '<input id="N_'.$contad.'" name="filaProducto" data-obj="idN" onkeydown="controlEventos(event)" type="image"  alt="">'
			. '<span  class="glyphicon glyphicon-plus-sign agregar"></span></td>'
            .'<td>'.$producto['idArticulo'].'</td>'
			. '<td>'.htmlspecialchars($producto['articulo_name'],ENT_QUOTES).'</td>'
			. '<td>'.htmlentities($producto['crefTienda'],ENT_QUOTES).'</td>'
			.'</tr>';
			$contad = $contad +1;
			if ($contad === 10){
				break;
			}
			
		}
	} 
	$resultado['html'] .='</tbody></table>';
	return $resultado;
}

function htmlDatosListadoPrincipal($datosBalanza, $datosplu, $opcionSelect){
    //Objetivo: devolver html con los datos de una balanza y plus para el listado principal
    $resultado=array();
    $html="";
    $htmlBalanza="";
    $htmlBalanza.='<p><b>Nombre de balanza: </b>'.$datosBalanza['nombreBalanza'].'</p>
    <p><b>Modelo de Balanza: </b>'.$datosBalanza['modelo'].'</p>
    <p><label>Filtrar por: </label><select id="filtroBalanza" >';
    if($opcionSelect=='a.plu'){
        $htmlBalanza.='<option value="a.plu" onclick="mostrarDatosBalanza('.$datosBalanza['idBalanza'].')">PLU</option>
            <option value="a.tecla" onclick="mostrarDatosBalanza('.$datosBalanza['idBalanza'].')">TECLA</option>
        </select></p>';
    }else{
         $htmlBalanza.='<option value="a.tecla" onclick="mostrarDatosBalanza('.$datosBalanza['idBalanza'].')">TECLA</option>
        <option value="a.plu" onclick="mostrarDatosBalanza('.$datosBalanza['idBalanza'].')">PLU</option>
        </select></p>';
    }
   
    $html.='<tr>
            <td><b>PLU</b></td>
            <td><b>Tecla</b></td>
            <td><b>idArticulo</b></td>
            <td><b>Descripción</b></td>
            <td><b>Referencia</b></td>
            <td><b>PVP</b></td>
        </tr>';
        $indice=0;
    foreach ($datosplu as $plu){
        $espacio="";
        $sigIndice=$indice+1;
        
        $html.='<tr>
            <td>'.$plu['plu'].'</td>
            <td>'.$plu['tecla'].'</td>
            <td>'.$plu['idArticulo'].'</td>
            <td>'.$plu['articulo_name'].'</td>
            <td>'.$plu['crefTienda'].'</td>
            <td>'.number_format($plu['pvpCiva'],2).'</td>
        </tr>';
        if(isset($datosplu[$sigIndice])){
            // Si hay plu no utilizados mostramos advertencia.
            $resta=$datosplu[$sigIndice]['plu']-$datosplu[$indice]['plu'];
            if($resta>1){
                 $html.='<tr><td COLSPAN="4" class="warning">Faltan números entre el anterior y el siguiente</td></tr>';
            }
        }
        $indice++;
       
    }
    $resultado['html']=$html;
    $resultado['htmlBalanza']= $htmlBalanza;
    return $resultado;
}
function htmlTecla($tecla){
    //@Objetivo: html con las opciones de la tecla
    if($tecla=="si"){
        $html ='<option value="si" selected="selected">Si</option>';
  
    }else{
        $html ='<option value="si">Si</option>';
    }
    if($tecla=="no"){
         $html .='<option value="no" selected="selected">No</option>';
    } else {
         $html .='<option value="no">No</option>';

    }
    return $html;
}
?>
