<?php 

function repetirLineasProducto($veces, $idProducto, $BDTpv, $idTienda, $fechaCad, $numProd){
	$CArticulo= new Articulos($BDTpv);
	$respuesta=array();
	$datosArticulo=$CArticulo->datosArticulosPrincipal($idProducto, $idTienda);
	//~ $respuesta['datos']=$datosArticulo;
	//~ error_log($datosArticulo);
	$Productos=array();
	$html="";
	
	for($i=1;$i<=$veces;$i++){
		
		$nuevoProducto=array();
		$nuevoProducto['nombre']=$datosArticulo['articulo_name'];
		$nuevoProducto['peso']=1;
		$nuevoProducto['precio']=$datosArticulo['pvpCiva'];
		$nuevoProducto['Fecha']=$fechaCad;
		$nuevoProducto['NumAlb']="";
		$nuevoProducto['codBarras']="";
		$nuevoProducto['estado']='Activo';
		$nuevoProducto['Nfila']=$numProd+$i;
		array_push($Productos, $nuevoProducto);
		
		if ($nuevoProducto['estado'] !=='Activo'){
				$classtr = ' class="tachado" ';
				$estadoInput = 'disabled';
				$funcOnclick = ' retornarFila('.$nuevoProducto['Nfila'].', "etiquetas");';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'">
							<span class="glyphicon glyphicon-export"></span></a></td>';
			} else {
				$funcOnclick = ' eliminarFila('.$nuevoProducto['Nfila'].' , "etiquetas");';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'">
							<span class="glyphicon glyphicon-trash"></span></a></td>';
				$classtr = '';
				$estadoInput = '';
			}
		
		$html.='<tr id="Row'.($nuevoProducto['Nfila']).'" '.$classtr.'>'
		 .'<td class="linea">'.$nuevoProducto['Nfila'].'</td>'
		 .'<td><input type="text" id="nombre_'.$nuevoProducto['Nfila'].'" value="'.$nuevoProducto['nombre'].'"></td>'
		 .'<td><input type="text" id="peso_'.$nuevoProducto['Nfila'].'" value="'.$nuevoProducto['peso'].'"></td>'
		 .'<td><input type="text" id="precio_'.$nuevoProducto['Nfila'].'" value="'.$nuevoProducto['precio'].'"></td>'
		 .'<td><input type="text" id="fecha_'.$nuevoProducto['Nfila'].'" value="'.$nuevoProducto['Fecha'].'"></td>'
		 .'<td><input type="text" id="numAlb_'.$nuevoProducto['Nfila'].'" value="'.$nuevoProducto['NumAlb'].'"></td>'
		 .'<td><input type="text" id="codBarras_'.$nuevoProducto['Nfila'].'" value="'.$nuevoProducto['codBarras'].'"></td>'
		 . $btnELiminar_Retornar
		 .'</tr>';
		
	}
	$respuesta['productos']=$Productos;
	$respuesta['html']=$html;
	return $respuesta;
	
}
function htmlProductos($busqueda, $productos){
	$resultado = array();
	$resultado['encontrados'] = count($productos);
	$resultado['html'] = '<label>Busqueda De Productos</label>
				<input id="cajaBusquedaproductos" name="valorProducto" placeholder="Buscar"'.
				'size="13" data-obj="cajaBusquedaproductos" value="'.$busqueda.'"
				 onkeydown="controlEventos(event)" type="text">';
	if (count($productos)>10){
		$resultado['html'] .= '<span>10 Productos de '.count($productos).'</span>';
	}
	$resultado['html'] .= '<table class="table table-striped"><thead>'
	. ' <th></th> <th>Id</th><th>Nombre del Producto</th><th>PVPCiva</th></thead><tbody>';
	if (count($productos)>0){
			$contad = 0;
			foreach($productos as $producto){
				$resultado['html'] .= '<tr id="Fila_'.$contad.'" class="FilaModal" onclick="buscarProducto('
				.$producto['idArticulo'].', '."'".'id_producto'."'".')">'
				.'<td id="C'.$contad.'_Lin" >'
				.'<input id="N_'.$contad.'" name="filaproducto" data-obj="idN" onkeydown="controlEventos(event)" type="image"  alt="">'
				. '<span  class="glyphicon glyphicon-plus-sign agregar"></span></td>'
				. '<td>'.$producto['idArticulo'].'</td>'
				. '<td>'.$producto['articulo_name'].'</td>'
				. '<td>'.$producto['pvpCiva'].'</td>'
				.'</tr>';
				$contad = $contad +1;
				if ($contad === 10){
					break;
				}
			}
			$resultado['html'] .='</tbody></table>';
			return $resultado;
	}
}
function lineasProductos($productos){
	$html="";
	foreach($productos as $producto){
			if ($producto['estado'] !=='Activo'){
				$classtr = ' class="tachado" ';
				$estadoInput = 'disabled';
				$funcOnclick = ' retornarFila('.$producto['Nfila'].', "etiquetas");';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'">
							<span class="glyphicon glyphicon-export"></span></a></td>';
			} else {
				$funcOnclick = ' eliminarFila('.$producto['Nfila'].' , "etiquetas");';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'">
							<span class="glyphicon glyphicon-trash"></span></a></td>';
				$classtr = '';
				$estadoInput = '';
			}
		
		$html.='<tr id="Row'.($producto['Nfila']).'" '.$classtr.'>'
		 .'<td class="linea">'.$producto['Nfila'].'</td>'
		 .'<td><input type="text" id="nombre_'.$producto['Nfila'].'" value="'.$producto['nombre'].'"></td>'
		 .'<td><input type="text" id="peso_'.$producto['Nfila'].'" value="'.$producto['peso'].'"></td>'
		 .'<td><input type="text" id="precio_'.$producto['Nfila'].'" value="'.$producto['precio'].'"></td>'
		 .'<td><input type="text" id="fecha_'.$producto['Nfila'].'" value="'.$producto['Fecha'].'"></td>'
		 .'<td><input type="text" id="numAlb_'.$producto['Nfila'].'" value="'.$producto['NumAlb'].'"></td>'
		 .'<td><input type="text" id="codBarras_'.$producto['Nfila'].'" value="'.$producto['codBarras'].'"></td>'
		 . $btnELiminar_Retornar
		 .'</tr>';
		 
	}
	return $html;
}
?>
