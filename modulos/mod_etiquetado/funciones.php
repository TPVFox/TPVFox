<?php 

function repetirLineasProducto($veces, $idProducto, $BDTpv, $idTienda, $fechaCad){
	$CArticulo= new Articulos($BDTpv);
	$respuesta=array();
	$datosArticulo=$CArticulo->datosArticulosPrincipal($idProducto, $idTienda);
	$respuesta['datos']=$datosArticulo;
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
		$nuevoProducto['Nfila']=$i;
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
		 .'</tr>';
		
	}
	$respuesta['productos']=$Productos;
	$respuesta['html']=$html;
	return $respuesta;
	
}

?>
