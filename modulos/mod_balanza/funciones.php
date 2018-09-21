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
function  htmlTablaPlus($plus){
	// @ Objetivo
	// Montar la tabla html de codbarras
	// @ Parametros
	// 		$codBarras -> (array) con los codbarras del producto.
	$html =	 '<table id="tPlus" class="table table-striped">'
			.'		<thead>'
			.'			<tr>'
			.'				<th>Plus</th>'
			.'				<th>'.'<a id="agregar" onclick="htmlPlu()">Añadir'
			.'					<span class="glyphicon glyphicon-plus"></span>'
			.'					</a>'
			.'				</th>'
			.'			</tr>'
            .'          <tr  id="addPlu">
                        
                        </tr>' 
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
				.'<td>'.$plu['plu'].'</td>'
                .'<td>'.$plu['tecla'].'</td>'
                .'<td>'.$plu['idArticulo'].'</td>'
				.'<td><a id="eliminar_'.$plu['plu']
				.'" class="glyphicon glyphicon-trash" onclick="eliminarPlu(this)"></a>'
				.'</td>'.'</tr>';
	return $nuevaFila;
}
function htmlAddPLU($tecla){
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
    .'<input type="text" name="idArticulo id="idArticulo" value="" size="2px">'
    .'</div>'
    .'<div class="col-md-5">'
    .'<label>Nombre:</label>'
    .'<input type="text" name="nombreProducto id="nombreProducto" value="" size="20px">'
    .'</div>'
    .'<div class="col-md-3">'
    .'<label>Referencia:</label>'
    .'<input type="text" name="referencia id="referencia" value="" size="10px">'
    .'</div>'
    .'<div class="col-md-3">'
     .'<label>Cod Barras:</label>'
    .'<input type="text" name="codBarras id="codBarras" value="" size="10px">'
    .'</div>'
    .'</div>'
    .'<div class="col-md-12">'
    .'<div class="col-md-8"></div>'
    .'<div class="col-md-4"><label></label>'
    .'<a class="btn btn-success" onclick="addPlu">Añadir</a>'
    .'</div>'
    .'</div>';
    $html.='</div>';
    return $html;
}

?>
