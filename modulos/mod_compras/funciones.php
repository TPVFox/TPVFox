<?php 
function htmlProveedores($busqueda,$dedonde, $idcaja, $proveedores = array()){
	// @ Objetivo:
	// Montar el hmtl para mostrar con los clientes si los hubiera.
	// @ parametros:
	// 		$busqueda -> El valor a buscar,aunque puede venir vacio.. 
	//		$dedonde  -> Nos indica de donde viene. (tpv,cerrados,cobrados)
	$resultado = array();
	//$resultado['proveedores']=$proveedores;
	$n_dedonde = 0 ; 
	$resultado['encontrados'] = count($proveedores);
	$idcaja;
	$resultado['html'] = '<label>Busqueda Cliente en '.$dedonde.'</label>';
	$resultado['html'] .= '<input id="cajaBusquedaproveedor" name="valorproveedor" placeholder="Buscar"'.
				'size="13" data-obj="cajaBusquedaproveedor" value="'.$busqueda.'" onkeydown="controlEventos(event)" type="text">';
				
	if (count($proveedores)>10){
		$resultado['html'] .= '<span>10 clientes de '.count($proveedores).'</span>';
	}
	$resultado['html'] .= '<table class="table table-striped"><thead>';
	$resultado['html'] .= ' <th></th>'; //cabecera blanca para boton agregar
	$resultado['html'] .= ' <th>Nombre</th>';
	$resultado['html'] .= ' <th>Razon social</th>';
	$resultado['html'] .= ' <th>NIF</th>';
	$resultado['html'] .= '</thead><tbody>';
	if (count($proveedores)>0){
		$contad = 0;
		foreach ($proveedores as $proveedor){  
			
			$razonsocial_nombre=$proveedor['nombrecomercial'].' - '.$proveedor['razonsocial'];
			$datos = 	"'".$proveedor['idProveedor']."','".addslashes(htmlentities($razonsocial_nombre,ENT_COMPAT))."'";
			$resultado['html'] .= '<tr id="Fila_'.$contad.'" onmouseout="abandonFila('.$contad
			.')" onmouseover="sobreFilaCraton('.$contad.')" onclick="escribirProveedorSeleccionado('.$datos.",'".$dedonde."'".');">';
		
			$resultado['html'] .= '<td id="C'.$contad.'_Lin" >';
			$resultado['html'] .= '<input id="N_'.$contad.'" name="filacliente" onfocusout="abandonFila('
						.$contad.')" data-obj="idN" onkeydown="controlEventos(event)" onfocus="sobreFila('.$contad.')"   type="image"  alt="">';
			$resultado['html'] .= '<span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';
			$resultado['html'] .= '<td>'.htmlspecialchars($proveedor['nombrecomercial'],ENT_QUOTES).'</td>';
			$resultado['html'] .= '<td>'.htmlentities($proveedor['razonsocial'],ENT_QUOTES).'</td>';
			$resultado['html'] .= '<td>'.$proveedor['nif'].'</td>';
			$resultado['html'] .= '</tr>';
			$contad = $contad +1;
			if ($contad === 10){
				break;
			}
			
		}
	} 
	$resultado['html'] .='</tbody></table>';
	// Ahora generamos objetos de filas.
	// Objetos queremos controlar.
	return $resultado;
}
function BuscarProductos($id_input,$campoAbuscar,$idcaja, $busqueda,$BDTpv, $idProveedor) {
	// @ Objetivo:
	// 	Es buscar por Referencia / Codbarras / Descripcion nombre.
	// @ Parametros:
	//		campoAbuscar-> indicamos que campo estamos buscando.
	//		busqueda -- string a buscar, puede contener varias palabras
	//		BDTpv-> conexion a la base datos.
	//		vuelta = 1, para buscar algo identico, si viene con 2 busca con %like% segunda llamada
	$resultado = array();
	$palabras = array(); 
	$products = array();
	$palabras = explode(' ',$busqueda); // array de varias palabras, si las hay..
	$resultado['palabras']= $palabras;
	$likes = array();
	$whereIdentico = array();
	foreach($palabras as $palabra){
		$likes[] =  $campoAbuscar.' LIKE "%'.$palabra.'%" ';
		$whereIdentico[]= $campoAbuscar.' = "'.$palabra.'"';
	}
	
	//si vuelta es distinto de 1 es que entra por 2da vez busca %likes%	
	
	$busquedas = array();
	
	if ($palabra !== ''){ 
		$busquedas[] = implode(' and ',$whereIdentico);
	
		$busquedas[] = implode(' and ',$likes);
	}
	$i = 0;
	foreach ($busquedas as $buscar){

			$sql = 'SELECT a.`idArticulo` , a.`articulo_name` , ac.`codBarras` , a.ultimoCoste, at.crefTienda ,p.`crefProveedor`,  a.`iva` '
			.' FROM `articulos` AS a LEFT JOIN `articulosCodigoBarras` AS ac '
			.' ON a.idArticulo = ac.idArticulo LEFT JOIN `articulosPrecios` AS ap '
			.' ON a.idArticulo = ap.idArticulo AND ap.idTienda =1 LEFT JOIN `articulosTiendas` '
			.' AS at ON a.idArticulo = at.idArticulo AND at.idTienda =1 left join articulosProveedores as p on a.idArticulo=p.`idArticulo` and p.idProveedor='.$idProveedor.' WHERE '.$buscar.' LIMIT 0 , 30 ';
			//~ $sql = 'SELECT a.`idArticulo` , a.`articulo_name` , ac.`codBarras` , a.ultimoCoste, at.crefTienda , a.`iva` '
			//~ .' FROM `articulos` AS a LEFT JOIN `articulosCodigoBarras` AS ac '
			//~ .' ON a.idArticulo = ac.idArticulo LEFT JOIN `articulosPrecios` AS ap '
			//~ .' ON a.idArticulo = ap.idArticulo AND ap.idTienda =1 LEFT JOIN `articulosTiendas` '
			//~ .' AS at ON a.idArticulo = at.idArticulo AND at.idTienda =1 WHERE '.$buscar.' LIMIT 0 , 30 ';
		$resultado['sql'] = $sql;
		$res = $BDTpv->query($sql);
		$resultado['Nitems']= $res->num_rows;
		//si es la 1ª vez que buscamos, y hay muchos resultados, estado correcto y salimos del foreach.
		if ($i === 0){
			if ($res->num_rows >0){
				$resultado['Estado'] = 'Correcto';
				break;
			}
		}
		//compruebo error en consulta
		if (mysqli_error($BDTpv)){
			$resultado['consulta'] = $sql;
			$resultado['error'] = $BDTpv->error_list;
			return $resultado;
		} 
		$i++;
	}	
	//si hay muchos resultados y si es mas de 1, mostrara un listado
	if ($res->num_rows > 0){
		if ($res->num_rows > 1){
			$resultado['Estado'] = 'Listado';
		}
	} else { 
		$resultado['Estado'] = 'Noexiste';
	}

	//si hay muchos resultados, recogera los datos para mostrarlos
	if ($res->num_rows > 0){
		//fetch_assoc es un boleano..
		while ($fila = $res->fetch_assoc()) {
			$products[] = $fila;
			$resultado['datos']=$products;
		}
	} 
	return $resultado;
}
function htmlProductos($productos,$id_input,$campoAbuscar,$busqueda){
	// @ Objetivo 
	// Obtener listado de produtos despues de busqueda.
	$resultado = array();
	
	$resultado['encontrados'] = count($productos);
	$resultado['html'] = "<script type='text/javascript'>
					// Ahora debemos añadir parametro campo a objeto de cajaBusquedaProductos".
						"cajaBusquedaproductos.parametros.campo.__defineSetter__ ="."'".$campoAbuscar."';
						idN.parametros.campo.__defineSetter__ ="."'".$campoAbuscar."';
						</script>";
	$resultado['html'] .= '<label>Busqueda por '.$id_input.'</label>';
	// Utilizo el metodo onkeydown ya que encuentro que onKeyup no funciona en igual con todas las teclas.
	
	$resultado['html'] .= '<input id="cajaBusqueda" name="'.$id_input.'" placeholder="Buscar" data-obj="cajaBusquedaproductos" size="13" value="'
					.$busqueda.'" onkeydown="controlEventos(event)" type="text">';
	if (count($productos)>10){
		$resultado['html'] .= '<span>10 productos de '.count($productos).'</span>';
	}
	if ($resultado['encontrados'] === 0){
			// Hay que tener en cuenta tambien si la caja tiene datos ya que sino no es lo mismo.
			if (strlen($busqueda) === 0 ) {
				// Si no encontro resultados, entonces debemos porne una alert y incluso sonorá era guay...
				$resultado['html'] .= '<div class="alert alert-info">';
				$resultado['html'] .=' <strong>Buscar!</strong> Pon las palabras para buscar productos que consideres.</div>';
			} else {
				// Si no encontro resultados, entonces debemos porne una alert y incluso sonorá era guay...
				$resultado['html'] .= '<div class="alert alert-warning">';
				$resultado['html'] .=' <strong>Error!</strong> No se encontrado nada con esa busqueda.</div>';
			}
	} else {
	
		$resultado['html'] .= '<table class="table table-striped"><thead>';
		$resultado['html'] .= ' <th></th>';
		$resultado['html'] .= '</thead><tbody>';
		
		$contad = 0;
		foreach ($productos as $producto){
			$datos = 	"'".$id_input."',".
						"'".addslashes(htmlspecialchars($producto['crefTienda'],ENT_COMPAT))."','"
						.addslashes(htmlentities($producto['articulo_name'],ENT_COMPAT))."','"
						.number_format($producto['iva'],2)."','".$producto['codBarras']."',"
						.number_format($producto['ultimoCoste'],2).",".$producto['idArticulo'];
			$resultado['html'] .= '<tr id="Fila_'.$contad.'" onmouseout="abandonFila('
						.$contad.')" onmouseover="sobreFilaCraton('.$contad.')"  onclick="escribirProductoSeleccionado('.$datos.');">';
			
			$resultado['html'] .= '<td id="C'.$contad.'_Lin" ><input id="N_'.$contad.'" name="filaproducto" onfocusout="abandonFila('
						.$contad.')" data-obj="idN" onfocus="sobreFila('.$contad.')" onkeydown="controlEventos(event)" type="image"  alt=""><span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';
			$resultado['html'] .= '<td>'.htmlspecialchars($producto['crefTienda'], ENT_QUOTES).'</td>';				
			$resultado['html'] .= '<td>'.htmlspecialchars($producto['articulo_name'], ENT_QUOTES).'</td>';
			$resultado['html'] .= '<td>'.number_format($producto['ultimoCoste'],2).'</td>';

			$resultado['html'] .= '</tr>';
			$contad = $contad +1;
			if ($contad === 10){
				break;
			}
			
		}
		$resultado['html'] .='</tbody></table>';
	}
	$resultado['campo'] = $campoAbuscar;
	
	return $resultado;
	
	
}
function recalculoTotalesAl($productos) {
	// @ Objetivo recalcular los totales y desglose del ticket
	// @ Parametro:
	// 	$productos (array) de objetos.
	$respuesta = array();
	$desglose = array();
	$ivas = array();
	$subtotal = 0;
	//~ $productosTipo=gettype($productos);
	//~ $respuesta['tipo']=$productosTipo;
	// Creamos array de tipos de ivas hay en productos.
	//~ $ivas = array_unique(array_column($productos,'ctipoiva'));
	//~ sort($ivas); // Ordenamos el array obtenido, ya que los indices seguramente no son correlativos.
	foreach ($productos as $product){
		// Si la linea esta eliminada, no se pone.
		if ($product->estado === 'Activo'){
			$bandera=$product->iva/100;
			$totalLinea=($bandera+$product->ultimoCoste)*$product->ncant;
			//$totalLinea = $product->ncant * $product->precioCiva;
			//~ $respuesta['lineatotal'][$product->nfila] = number_format($totalLinea,2);
			$subtotal = $subtotal + $totalLinea; // Subtotal sumamos importes de lineas.
			// Ahora calculmos bases por ivas
			$desglose[$product->iva]['BaseYiva'] = (!isset($desglose[$product->iva]['BaseYiva']) ? $totalLinea : $desglose[$product->iva]['BaseYiva']+$totalLinea);
			// Ahora calculamos base y iva 
			$operador = (100 + $product->iva) / 100;
			$desglose[$product->iva]['base'] = number_format(($desglose[$product->iva]['BaseYiva']/$operador),2);
			$desglose[$product->iva]['iva'] = number_format($desglose[$product->iva]['BaseYiva']-$desglose[$product->iva]['base'],2);
			//~ $desglose[$product->ctipoiva]['tipoIva'] =$iva;
		}
	
	}
	
	//~ $respuesta['ivas'] = $ivas;
	$respuesta['desglose'] = $desglose;
	$respuesta['total'] = number_format($subtotal,2);
	return $respuesta;
}
// html de la linea de los productos tanto para pedido, albaran y factura
function htmlLineaPedidoAlbaran($productos, $dedonde){
	
	if(!is_array($productos)) {
		// Comprobamos si product no es objeto lo convertimos.
		$producto = (array)$productos;
		
	} else {
		$producto = $productos;
	}
	// Si el estado es activo lo muestra normal con el boton de eleminar producto si no la linea esta desactivada con el botón de retornar
		 	if ($producto['estado'] !=='Activo'){
				$classtr = ' class="tachado" ';
				$estadoInput = 'disabled';
				$funcOnclick = ' retornarFila('.$producto['nfila'].', '."'".$dedonde."'".');';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-export"></span></a></td>';
			} else {
				$funcOnclick = ' eliminarFila('.$producto['nfila'].' , '."'".$dedonde."'".');';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-trash"></span></a></td>';
			}
			if ($producto['NumpedCli']==0){
				$numeroPed="";
			}else{
				$numeroPed=$producto['NumpedCli'];
			}
			//Si tiene referencia del proveedor lo muestra si no muestra un input para poder introducir la referencia
			if ($producto['crefProveedor']>0){
				$filaProveedor='<td class="referencia"><input id="Proveedor_Fila_'.$producto['nfila'].'" type="text" data-obj="Proveedor_Fila" pattern="[.0-9]+"  value="'.$producto['crefProveedor'].'"name="proveedor" placeholder="ref" size="7"  onkeydown="controlEventos(event)" onBlur="controlEventos(event)" disabled><a id="enlaceCambio" onclick="buscarReferencia('.$producto['idArticulo'].', '.$producto['nfila'].')" style="text-align: right"><span class="glyphicon glyphicon-cog"></span></a></td>';
			}else{
				$filaProveedor='<td><input id="Proveedor_Fila_'.$producto['nfila'].'" type="text" data-obj="Proveedor_Fila" pattern="[.0-9]+" name="proveedor" placeholder="ref" size="7"  onkeydown="controlEventos(event)" onBlur="controlEventos(event)"><a onclick="buscarReferencia('.$producto['idArticulo'].', '.$producto['nfila'].')" style="display:none" id="enlaceCambio"><span class="glyphicon glyphicon-cog"></span></a></td>';
			}
			if ($producto['ccodbar']>0){
				$codBarra=$producto['ccodbar'];
			}else{
				$codBarra="";
			}
		 $respuesta['html'] .='<tr id="Row'.($producto['nfila']).'" '.$classtr.'>';
		 
		 $respuesta['html'] .='<td class="linea">'.$producto['nfila'].'</td>';
		 //$respuesta['html'] .='<td>'.$numeroPed.'</td>';
		 $respuesta['html']	.= '<td class="idArticulo">'.$producto['idArticulo'].'</td>';
		 $respuesta['html'] .='<td class="referencia">'.$producto['cref'].'</td>';
		 $respuesta['html'] .=$filaProveedor;
		 $respuesta['html'] .='<td class="codbarras">'.$codBarra.'</td>';
		 $respuesta['html'] .= '<td class="detalle">'.$producto['cdetalle'].'</td>';
		 $cant=number_format($producto['ncant'],0);
		 $respuesta['html'] .= '<td><input id="Unidad_Fila_'.$producto['nfila'].'" type="text" data-obj="Unidad_Fila" pattern="[.0-9]+" name="unidad" placeholder="unidad" size="4"  value="'.$cant.'"  '.$estadoInput.' onkeydown="controlEventos(event)" onBlur="controlEventos(event)"></td>';
		 $respuesta['html'] .='<td class="pvp">'.$producto['ultimoCoste'].'</td>';
		 $respuesta['html'] .= '<td class="tipoiva">'.$producto['iva'].'%</td>';
		 $bandera=$producto['iva']/100;
		 $importe=($bandera+$producto['ultimoCoste'])*$producto['ncant'];
		// $importe = $producto['ultimoCoste']*$producto['ncant'];
		 $importe = number_format($importe,2);
		 $respuesta['html'] .='<td id="N'.$producto['nfila'].'_Importe" class="importe" >'.$importe.'</td>';
		 $respuesta['html'] .= $btnELiminar_Retornar;
		 $respuesta['html'] .='</tr>';
		 $respuesta['productos']=$producto;
	 return $respuesta;
}
// Modificar el array de productos para poder trabajar con el en pedidos
function modificarArrayProductos($productos){
	$respuesta=array();
	foreach ($productos as $producto){
		$pro['ccodbar']=$producto['ccodbar'];
		$pro['cdetalle']=$producto['cdetalle'];
		$pro['cref']=$producto['cref'];
		$pro['crefProveedor']=$producto['ref_prov'];
		$pro['estado']=$producto['estadoLinea'];
		$pro['idArticulo']=$producto['idArticulo'];
		$pro['idpedcli']=$producto['idpedpro'];
		$bandera=$producto['iva']/100;
		$importe=($bandera+$producto['costeSiva'])*$producto['ncant'];
		$pro['importe']=$importe;
		$pro['iva']=$producto['iva'];
		$pro['ncant']=$producto['ncant'];
		$pro['nfila']=$producto['nfila'];
		$pro['nunidades']=$producto['nunidades'];
		$pro['ultimoCoste']=$producto['costeSiva'];
		array_push($respuesta,$pro);
	}
	return $respuesta;
}

// html para cambio de referencia de proveedor
function htmlCambioRefProveedor($datos, $fila, $articulo, $coste){
	$resultado['html'] .='<label>Modificación de '.$articulo['articulo_name'].'</label>';
	$resultado['html'] .='<input type=text value="'.$fila.'" id="numFila" style="display:none">';
	$resultado['html'] .='<input type=text value="'.$datos['idArticulo'].'" id="idArticuloRef" style="display:none">';
	$resultado['html'] .='<input type=text value="'.$coste.'" id="coste" style="display:none">';
	$resultado['html'] .= '<input type=text value="'.$datos['crefProveedor'].'" data-obj="inputCambioRef" name ="cambioRef" onkeydown="controlEventos(event)" onBlur="controlEventos(event)" id ="inputCambioRef">';
	return $resultado;
}
?>
