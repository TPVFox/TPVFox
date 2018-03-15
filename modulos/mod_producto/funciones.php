<?php 

function htmlLineaFamilias($item,$familia=''){
	// @Objetivo:
	// Montar linea de codbarras , para añadir o para modificar.
	$nuevaFila = '<tr>';
	$nuevaFila .= '<td><input type="hidden" id="idFamilias_'.$item.'" name="idFamilias_'.$item.'" value="'.$familia['idFamilia'].'">'.$familia['idFamilia'].'</td>';
	$nuevaFila .= '<td>'.$familia['familiaNombre'].'</td>>';
	$nuevaFila .= '<td><a id="eliminar" class="glyphicon glyphicon-trash" onclick="eliminarCodBarras(this)"></a></td>'; 		
	$nuevaFila .= '</tr>';
	return $nuevaFila;
}



function htmlLineaCodigoBarras($item,$codBarras=''){
	// @Objetivo:
	// Montar linea de codbarras , para añadir o para modificar.
	$nuevaFila = '<tr>';
	$nuevaFila .= '<td><input type="text" id="codBarras_'.$item.'" name="codBarras_'.$item.'" value="'.$codBarras.'"></td>';
	$nuevaFila .= '<td><a id="eliminar_'.$item.'" class="glyphicon glyphicon-trash" onclick="eliminarCodBarras(this)"></a></td>'; 		
	$nuevaFila .= '</tr>';
	return $nuevaFila;
}

function htmlLineaProveedorCoste($item,$proveedor=''){
	// @ Objetivo:
	// Montar linea de proveedores_coste, para añadir o para modificar.
	// @ Parametros :
	// 		$item -> (int) Numero item
	// 		$proveedor-> (array) Datos de proveedor: idProveedor,crefProveedor,coste,fechaActualizacion,estado,nombrecomercial,razonsocial.
	
	// Montamos campos ocultos de IDProveedor
	$camposIdProveedor = '<input type="hidden" name="idProveedor" id="idProveedor_'.$proveedor['idProveedor'].'" value="'.$proveedor['idProveedor'].'">';
	$nom_proveedor = $proveedor['idProveedor'].'.-';
	if ($proveedor['nombrecomercial'] !== $proveedor['razonsocial']){
		$nom_proveedor .= $proveedor['razonsocial'].'-'.$proveedor['nombrecomercial'];
	} else {
		$nom_proveedor .= $proveedor['razonsocial'];
	}
	$nuevaFila = '<tr>';
	$nuevaFila .= '<td>';
	$nuevaFila .='<small>'.$camposIdProveedor.$nom_proveedor.'</small>';
	$nuevaFila .='</td>';
	$nuevaFila .= '<td>';
	$nuevaFila .= $proveedor['crefProveedor'];
	$nuevaFila .='</td>';
	$nuevaFila .= '<td>';
	$nuevaFila .= $proveedor['coste'];
	$nuevaFila .='</td>';
	$nuevaFila .= '<td>';
	$nuevaFila .= '<span class="glyphicon glyphicon-calendar" title="Fecha Actualizacion:'.$proveedor['fechaActualizacion'].'">'.$proveedor['estado'].'</span>';
	$nuevaFila .='</td>';
	$nuevaFila .= '<td><a id="eliminar" class="glyphicon glyphicon-trash" onclick="eliminarCodBarras(this)"></a></td>'; 		
	$nuevaFila .= '</tr>';
	return $nuevaFila;
}

function  htmlTablaCodBarras($codBarras){
	// @ Objetivo
	// Montar la tabla html de codbarras
	// @ Parametros
	// 		$codBarras -> (array) con los codbarras del producto.
	$html =	 '<table id="tcodigo" class="table table-striped">'
			.'		<thead>'
			.'			<tr>'
			.'				<th>Codigos Barras</th>'
			.'				<th>'.'<a id="agregar" onclick="AnhadirCodbarras()">Añadir'
			.'					<span class="glyphicon glyphicon-plus"></span>'
			.'					</a>'
			.'				</th>'
			.'			</tr>'
			.'		</thead>';
	if (count($codBarras)>0){
		foreach ($codBarras as $item=>$valor){
			$html .= htmlLineaCodigoBarras($item,$valor);
		}
	}
	$html .= '</table>	';
	return $html;
} 

function  htmlTablaFamilias($familias){
	// @ Objetivo
	// Montar la tabla html de familias del producto
	// @ Parametros
	// 		$familias -> (array) idFamilias y NombreFamilias 
	$html =	 '<table id="tfamilias" class="table table-striped">'
			.'		<thead>'
			.'			<tr>'
			.'				<th>idFamilias</th>'
			.'				<th>Nombre de Familia</th>'
			.'				<th>'.'<a id="agregar" onclick="comprobarVacio()">Añadir'
			.'					<span class="glyphicon glyphicon-plus"></span>'
			.'					</a>'
			.'				</th>'
			.'			</tr>'
			.'		</thead>';
	if (count($familias)>0){
		foreach ($familias as $item=>$valor){
			$html .= htmlLineaFamilias($item,$valor);
		}
	}
	$html .= '</table>	';
	return $html;
} 



function  htmlTablaProveedoresCostes($proveedores){
	// @ Objetivo
	// Montar la tabla html de codbarras
	// @ Parametros
	// 		// 		$proveedores-> (array) de Arrays con datos de proveedor: idProveedor,crefProveedor,coste,fechaActualizacion,estado,nombrecomercial,razonsocial.
	$html =	 '<table id="tproveedor" class="table table-striped">'
			.'		<thead>'
			.'			<tr>'
			.'				<th>Proveedor</th>'
			.'				<th>Ref_proveedor</th>'
			.'				<th>Coste</th>'
			.'				<th>Estado/Fecha</th>'
			.'				<th>'.'<a id="agregar" onclick="BuscarProveedor()">Añadir'
			.'					<span class="glyphicon glyphicon-plus"></span>'
			.'					</a>'
			.'				</th>'
			.'			</tr>'
			.'		</thead>';
	if (count($proveedores)>0){
		foreach ($proveedores as $item=>$proveedor_coste){
			$html .= htmlLineaProveedorCoste($item,$proveedor_coste);
		}
	}
	$html .= '</table>	';
	return $html;
} 


function htmlOptionIvas($ivas,$ivaProducto){
	//  Objetivo :
	// Montar html Option para selecciona Ivas, poniendo seleccionado el ivaProducto
	$htmlIvas = '';
	foreach ($ivas as $item){
			$es_seleccionado = '';
			if ($ivaProducto === $item['iva']){
				$es_seleccionado = ' selected';
			}
			$htmlIvas .= '<option value="'.$item['idIva'].'" '.$es_seleccionado.'>'.$item['iva'].'%'.'</option>';
		}
	return $htmlIvas;	
	
}


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

function modificarProducto($BDTpv, $datos, $tabla){
	$resultado = array();
	$id=$datos['idProducto'];
	$nombre=$datos['nombre'];
	$coste=$datos['coste'];
	$beneficio=$datos['beneficio'];
	$iva=$datos['iva'];
	$pvpCiva=$datos['pvpCiva'];
	$pvpSiva=$datos['pvpSiva'];
	$referencia=$datos['referencia'];
	$tienda=$datos['idTienda'];
	// Montar un array con los las claves del array datos
	$keys=array_keys($datos);
	$codBarras = [];
	// Se va recorriendo  
	foreach($keys as $key){
		// Los que coincidan con el campo cod quiere decir que es un codigo de barras y se añaden al array codBarras[]
		$nombre1="cod";
		if (strpos($key, $nombre1)>-1){
			if ($datos[$key]<>""){
				$codBarras[] = '('.$id.',"'.$datos[$key].'")';
			}
		}
	}
	$stringCodbarras = implode(',',$codBarras);
	//Fecha y hora del sistema
	$fechaMod=date("Y-m-d H:i:s");
	$sql='UPDATE '.$tabla.' SET articulo_name="'.$nombre.'", costepromedio='.$coste.', beneficio='.$beneficio.' , iva ='.$iva.', fecha_modificado="'.$fechaMod.'" WHERE idArticulo='.$id;
	$sql2='UPDATE articulosPrecios SET pvpCiva='.$pvpCiva.', pvpSiva='.$pvpSiva.' WHERE idArticulo='.$id  ;
	$sql3='DELETE FROM articulosCodigoBarras where idArticulo='.$id;
	$sql5='UPDATE articulosTiendas set crefTienda ="'.$referencia.'" WHERE  idArticulo='.$id.' and idTienda='.$tienda;
	$sql4='INSERT INTO articulosCodigoBarras (idArticulo, codBarras) VALUES '.$stringCodbarras;
	$consulta = $BDTpv->query($sql);
	$consulta = $BDTpv->query($sql2);
	$consulta = $BDTpv->query($sql3);
	$consulta = $BDTpv->query($sql4);
	$consulta = $BDTpv->query($sql5);
	$resultado['sql'] =$sql;
	$resultado['sql2'] =$sql2;
	$resultado['sql3'] =$sql3;
	$resultado['sql4'] =$sql4;
	$resultado['sql6'] =$sql5;
	$resultado['sql5']=$keys;
	return $resultado;	
}
/*Función para añadir un producto nuevo*/
function añadirProducto($BDTpv, $datos, $tabla){
	$nombre=$datos['nombre'];
	$coste=$datos['coste'];
	$beneficio=$datos['beneficio'];
	$iva=$datos['iva'];
	$pvpCiva=$datos['pvpCiva'];
	$pvpSiva=$datos['pvpSiva'];
	$idProovedor=$datos['idProveedor'];
	$estado=$datos['estado'];
	$pvpCiva=$datos['pvpCiva'];
	$pvpSiva=$datos['pvpSiva'];
	$idTienda=$datos['idTienda'];
	$referencia=$datos['referencia'];
	
	//Fecha y hora del sistema 
	$fechaAdd=date("Y-m-d H:i:s");
	$keys=array_keys($datos);
	$codBarras = [];
	$sql='INSERT INTO '.$tabla.' (iva, idProveedor , articulo_name, beneficio, costepromedio, estado, fecha_creado) VALUES ("'.$iva.'" , "'.$idProovedor.'" , "'.$nombre.'", "'.$beneficio.'", "'.$coste.'", "'. $estado .'", "'.$fechaAdd.'")';
	$consulta = $BDTpv->query($sql);
	//Id del inster anterior 
	$idGenerado=$BDTpv->insert_id;
	foreach($keys as $key){
		// Los que coincidan con el campo cod quiere decir que es un codigo de barras y se añaden al array codBarras[]
		$nombre1="cod";
		if (strpos($key, $nombre1)>-1){
			if ($datos[$key]<>""){
				$codBarras[] = '('.$idGenerado.',"'.$datos[$key].'")';
			}
		}
	}
	$stringCodbarras = implode(',',$codBarras);
	$sql2='INSERT INTO articulosPrecios (idArticulo, pvpCiva , pvpSiva, idTienda ) VALUES ('.$idGenerado.', '.$pvpCiva.', '.$pvpSiva.' , '.$idTienda.')';
	if ($referencia == 0){
		$referencia="Sin ref";
	}
	$sql4='INSERT INTO articulosTiendas (idArticulo, idTienda, crefTienda) VALUES ('.$idGenerado.', '.$idTienda.', "'.$referencia.'")';
	$sql3='INSERT INTO articulosCodigoBarras (idArticulo, codBarras) VALUES '.$stringCodbarras;
	$consulta = $BDTpv->query($sql2);
	$consulta = $BDTpv->query($sql3);
		$consulta = $BDTpv->query($sql4);
	$resultado['sql'] =$sql;
		$resultado['sql1'] =$sql2;
			$resultado['sql2'] =$sql3;
			$resultado['sql4'] =$sql4;
	return $resultado;
}




?>
