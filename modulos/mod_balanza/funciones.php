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
            .'          <tr  id="addPlu">
                        
                        </tr>' 
            .'          <tr>'
            .'          <td>PLU</td>'
            .'          <td>Tecla</td>'
            .'          <td>idArticulo</td>'
            .'          <td></td>'
            .'          </tr>' 
			.'		</thead>'
			.'		<tbody>';
	if (count($plus)>0){
		foreach ($plus as $item=>$valor){
			$html .= htmlLineaPlu($item,$valor);
		}
	}
	$html .= '</tbody> </table>	';
	return $html;
} 

function htmlLineaPlu($item, $plu){
    $nuevaFila = '<tr>'
				. '<td><input type="hidden" id="idPlu_'.$plu['plu']
				.'" name="idPlu'.$plu['plu'].'" value="'.$plu['plu'].'">'
				.$plu['plu'].'</td>'
				
                .'<td>'.$plu['tecla'].'</td>'
                .'<td>'.$plu['idArticulo'].'</td>'
				.'<td><a id="eliminar_'.$plu['plu']
				.'" class="glyphicon glyphicon-trash" onclick="eliminarPlu(this)"></a>'
				.'</td>'.'</tr>';
	return $nuevaFila;
}
function htmlAddPLU($tecla, $idBalanza){
    $html='<div class="col-md-12">'
					.'<div class="col-md-6">'
						.'<label>Plu:</label>'
						.'<input type="text" name="plu" id="plu" value="" >'
					.'</div>';
    if($tecla=='si'){
        $html.='<div class="col-md-6">'
        .'<label>Tecla:</label>'
        .'<input type="text" name="teclaPlu" id="teclaPlu" value="" >'
        .'</div>';
    }
    $html.='<div class="col-md-12">'
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
    .'<div class="col-md-12">'
    .'<div class="col-md-8"></div>'
    .'<div class="col-md-4"><label></label>'
    .'<a class="btn btn-success" onclick="addPlu('.$idBalanza.')">Añadir</a>'
    .'</div>'
    .'</div>';
    $html.='</div>';
    return $html;
}
function camposBuscar($idInput, $busqueda){
    $campo="";
    switch ($idInput){
        case 'idArticulo':
            $campo='a.idArticulo='.$busqueda;
        break;
        case 'nombreProducto':
            $campo='a.articulo_name like "%'.$busqueda.'%"';
        break;
        case 'referencia':
            $campo='b.crefTienda like "%'.$busqueda.'%"';
        break;
        case 'codBarras':
            $campo='c.codBarras like "%'.$busqueda.'%"';
        break;
    }
    return $campo;
}

function modalProductos($busqueda, $productos){
    $resultado = array();
	$resultado['encontrados'] = count($productos);
	$resultado['html'] = '<label>Busqueda Producto </label>';
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
?>
