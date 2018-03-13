<?php 

//~ include '../../clases/Proveedores.php';
//~ include 'clases/facturasCompras.php';
include '../../configuracion.php';
function htmlProveedores($busqueda,$dedonde, $idcaja, $proveedores = array()){
	// @ Objetivo:
	// Montar el hmtl para mostrar con los proveeodr si los hubiera.
	// @ parametros:
	// 		$busqueda -> El valor a buscar,aunque puede venir vacio.. 
	//		$dedonde  -> Nos indica de donde viene. ()
	$resultado = array();
	//$resultado['proveedores']=$proveedores;
	$n_dedonde = 0 ; 
	$resultado['encontrados'] = count($proveedores);
	$idcaja;
	$resultado['html'] = '<label>Busqueda Proveedor en '.$dedonde.'</label>';
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
			//~ $resultado['html'] .= '<tr id="Fila_'.$contad.'" onmouseout="abandonFila('.$contad
			//~ .')" onmouseover="sobreFilaCraton('.$contad.')" onclick="escribirProveedorSeleccionado('.$datos.",'".$dedonde."'".');">';
		
		
		
			$resultado['html'] .= '<tr id="Fila_'.$contad.'" onmouseout="abandonFila('.$contad
			.')" onmouseover="sobreFilaCraton('.$contad.')" onclick="buscarProveedor('."'".$dedonde."'".' , '."'id_proveedor'".', '.$proveedor['idProveedor'].', '."'popup'".');">';
		
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

			$sql = 'SELECT a.`idArticulo` , a.`articulo_name` , ac.`codBarras` , a.ultimoCoste, at.crefTienda ,p.`crefProveedor`, p.coste, p.fechaActualizacion,  a.`iva` '
			.' FROM `articulos` AS a LEFT JOIN `articulosCodigoBarras` AS ac '
			.' ON a.idArticulo = ac.idArticulo '
			.'  LEFT JOIN `articulosTiendas` '
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
function htmlProductos($productos,$id_input,$campoAbuscar,$busqueda, $dedonde){
	// @ Objetivo 
	// Obtener listado de produtos despues de busqueda.
	$resultado = array();
	$resultado['html']=" ";
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
						.number_format($producto['ultimoCoste'],2).",".$producto['idArticulo'].", '".$dedonde."' , ".
						"'".addslashes(htmlspecialchars($producto['crefProveedor'],ENT_COMPAT))."'";
			$resultado['html'] .= '<tr id="Fila_'.$contad.'" onmouseout="abandonFila('
						.$contad.')" onmouseover="sobreFilaCraton('.$contad.')"  onclick="escribirProductoSeleccionado('.$datos.');">';
			
			$resultado['html'] .= '<td id="C'.$contad.'_Lin" ><input id="N_'.$contad.'" name="filaproducto" onfocusout="abandonFila('
						.$contad.')" data-obj="idN" onfocus="sobreFila('.$contad.')" onkeydown="controlEventos(event)" type="image"  alt=""><span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';
			if ($id_input=="ReferenciaPro"){
				$resultado['html'] .= '<td>'.htmlspecialchars($producto['crefProveedor'], ENT_QUOTES).'</td>';	
			}else{
				$resultado['html'] .= '<td>'.htmlspecialchars($producto['crefTienda'], ENT_QUOTES).'</td>';	
			}
						
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
function recalculoTotales($productos) {
	// @ Objetivo recalcular los totales y desglose del ticket
	// @ Parametro:
	// 	$productos (array) de objetos.
	$respuesta = array();
	$desglose = array();
	$subivas = 0;
	$subtotal = 0;
	//~ $productosTipo=gettype($productos);
	//~ $respuesta['tipo']=$productosTipo;
	// Creamos array de tipos de ivas hay en productos.
	//~ $ivas = array_unique(array_column($productos,'ctipoiva'));
	//~ sort($ivas); // Ordenamos el array obtenido, ya que los indices seguramente no son correlativos.
	foreach ($productos as $product){
		// Si la linea esta eliminada, no se pone.
		if ($product->estado === 'Activo'){
			//error_log(json_encode($product));
			$bandera=$product->iva/100;
			// Ahora calculmos bases por ivas
			// Ahora calculamos base y iva 
			if (isset($desglose[$product->iva])){
			$desglose[$product->iva]['base'] = $desglose[$product->iva]['base'] + number_format(($product->importe),2);
			$desglose[$product->iva]['iva'] = $desglose[$product->iva]['iva']+ number_format($product->importe * $bandera,2);
			}else{
			$desglose[$product->iva]['base'] = number_format($product->importe,2);
			$desglose[$product->iva]['iva'] = number_format($product->importe*$bandera,2);
			}
			$desglose[$product->iva]['BaseYiva'] =$desglose[$product->iva]['base']+$desglose[$product->iva]['iva'];
			
		}
		
	
	}
	foreach($desglose as $tipoIva=>$des){
		$subivas= $subivas+$desglose[$tipoIva]['iva'];
		$subtotal= $subtotal +$desglose[$tipoIva]['BaseYiva'];
	}
	//~ $respuesta['ivas'] = $ivas;
	$respuesta['desglose'] = $desglose;
	$respuesta['subivas']=$subivas;
	$respuesta['total'] = number_format($subtotal,2);
	return $respuesta;
}
// html de la linea de los productos tanto para pedido, albaran y factura
function htmlLineaProducto($productos, $dedonde){
	 $respuesta=array('html'=>'');
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
				$classtr = '';
				$estadoInput = '';
			}
			if ($dedonde =="albaran" || $dedonde=="factura"){
				$coste='<input type="text" id="ultimo_coste_'.$producto['nfila'].'" data-obj="ultimo_coste" onkeydown="controlEventos(event)" name="ultimo" onBlur="controlEventos(event)" value="'.$producto['ultimoCoste'].'" size="6">';
			}else{
				$coste= $producto['ultimoCoste'];
			}
			if (isset($producto['numPedido'])){
				if ($producto['numPedido']==0){
					$numeroPed="";
				}else{
					$numeroPed=$producto['numPedido'];
				}
			}else{
			
				$numeroPed="";
			}
			
			if ($dedonde=="factura"){
				if (isset($producto['numAlbaran'])){
					if ($producto['numAlbaran']>0){
						$numeroPed=$producto['numAlbaran'];
					}else{
						$numeroPed="";
					}
				}else{
					$numeroPed="";
				}
				
				
			}
			//Si tiene referencia del proveedor lo muestra si no muestra un input para poder introducir la referencia
			if (isset($producto['crefProveedor'])){
				if ($producto['crefProveedor']){
				$filaProveedor='<td class="referencia"><input id="Proveedor_Fila_'.$producto['nfila'].'" type="text" data-obj="Proveedor_Fila" pattern="[.0-9]+"  value="'.$producto['crefProveedor'].'"name="proveedor" placeholder="ref" size="7"  onkeydown="controlEventos(event)" onBlur="controlEventos(event)" disabled><a id="enlaceCambio'.$producto['nfila'].'" onclick=buscarReferencia("Proveedor_Fila_'.$producto['nfila'].'") style="text-align: right"><span class="glyphicon glyphicon-cog"></span></a></td>';
				}else{
				$filaProveedor='<td><input id="Proveedor_Fila_'.$producto['nfila'].'" type="text" data-obj="Proveedor_Fila" pattern="[.0-9]+" name="proveedor" placeholder="ref" size="7"  onkeydown="controlEventos(event)" onBlur="controlEventos(event)"><a onclick=buscarReferencia("Proveedor_Fila_'.$producto['nfila'].'") style="display:none" id="enlaceCambio'.$producto['nfila'].'"><span class="glyphicon glyphicon-cog"></span></a></td>';
				}
			}else{
				$filaProveedor='<td><input id="Proveedor_Fila_'.$producto['nfila'].'" type="text" data-obj="Proveedor_Fila" pattern="[.0-9]+" name="proveedor" placeholder="ref" size="7"  onkeydown="controlEventos(event)" onBlur="controlEventos(event)"><a onclick=buscarReferencia("Proveedor_Fila_'.$producto['nfila'].'") style="display:none" id="enlaceCambio'.$producto['nfila'].'"><span class="glyphicon glyphicon-cog"></span></a></td>';
			}
			
			
			
			if (isset ($producto['ccodbar'])){
				if ($producto['ccodbar']>0){
					$codBarra=$producto['ccodbar'];
				}else{
					$codBarra="";
				}
			}
			
		 $respuesta['html'] .='<tr id="Row'.($producto['nfila']).'" '.$classtr.'>';
		 $respuesta['html'] .='<td class="linea">'.$producto['nfila'].'</td>';
		 if ($dedonde=="albaran" || $dedonde=="factura"){
			$respuesta['html'].= '<td class="idArticulo">'.$numeroPed.'</td>';
		
		 } 
		 $respuesta['html']	.= '<td class="idArticulo">'.$producto['idArticulo'].'</td>';
		 $respuesta['html'] .='<td class="referencia">'.$producto['cref'].'</td>';
		 $respuesta['html'] .=$filaProveedor;
		 $respuesta['html'] .='<td class="codbarras">'.$codBarra.'</td>';
		 $respuesta['html'] .= '<td class="detalle">'.$producto['cdetalle'].'</td>';
		 $cant=number_format($producto['nunidades'],0);
		 $respuesta['html'] .= '<td><input id="Unidad_Fila_'.$producto['nfila'].'" type="text" data-obj="Unidad_Fila" pattern="[.0-9]+" name="unidad" placeholder="unidad" size="4"  value="'.$cant.'"  '.$estadoInput.' onkeydown="controlEventos(event)" onBlur="controlEventos(event)"></td>';
		 $respuesta['html'] .='<td class="pvp">'.$coste.'</td>';
		 $respuesta['html'] .= '<td class="tipoiva">'.$producto['iva'].'%</td>';
		 $importe=$producto['ultimoCoste']*$producto['nunidades'];	
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
		if (isset($producto['Numpedpro'])){
			$pro['numPedido']=$producto['Numpedpro'];
		}
		if (isset ($producto['Numalbpro'])){
			$pro['numAlbaran']=$producto['Numalbpro'];
		}
		//$bandera=$producto['iva']/100;
		//$importe=($bandera+$producto['costeSiva'])*$producto['nunidades'];
		$importe=$producto['costeSiva']*$producto['nunidades'];
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
//Modal para cuando buscamos un pedido de un proveedor en albaranes

function modalAdjunto($adjuntos, $dedonde){
	$respuesta['html']	.= '<table class="table table-striped"><thead>';
	$respuesta['html']	.= '<th>';
	$respuesta['html']	.= '<td>Número </td>';
	$respuesta['html']	.= '<td>Fecha</td>';
	$respuesta['html']	.= '<td>Total</td>';
	$respuesta['html']	.= '</th>';
	$respuesta['html'] 	.=  '</thead><tbody>';
	$contad = 0;
	
	foreach ($adjuntos as $adjunto){
		if ($dedonde=="albaran"){
			$numAdjunto=$adjunto['Numpedpro'];
			$fecha=$adjunto['FechaPedido'];
		}else{
			
			$numAdjunto=$adjunto['Numalbpro'];
			$fecha=$adjunto['Fecha'];
		}
		$respuesta['html'] 	.= '<tr id="Fila_'.$contad.'" onmouseout="abandonFila('
		.$contad.')" onmouseover="sobreFilaCraton('.$contad.')"  onclick="buscarAdjunto('."'".$dedonde."'".', '.$numAdjunto.');">';
		$respuesta['html'] 	.= '<td id="C'.$contad.'_Lin" ><input id="N_'.$contad.'" name="filaproducto" onfocusout="abandonFila('
		.$contad.')" data-obj="idN" onfocus="sobreFila('.$contad.')" onkeydown="controlEventos(event)" type="image"  alt=""><span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';

		$respuesta['html']	.= '<td>'.$numAdjunto.'</td>';
		$respuesta['html']	.= '<td>'.$fecha.'</td>';
		$respuesta['html']	.= '<td>'.$adjunto['total'].'</td>';
		$respuesta['html']	.= '</tr>';
		$contad = $contad +1;
		if ($contad === 10){
			// Mostramos solo 10 albaranes... 
			// Un problema. ( Puede haber muchos mas);
			break;
		}				
	}
	$respuesta['html'].='</tbody></table>';
	return $respuesta;
}
//Agrega la linea de pedidos a un alabaran con los datos necesarios
function lineaAdjunto($pedido, $dedonde){
		$respuesta['html']="";
	if(isset($pedido)){
		if ($pedido['estado']){
			if ($pedido['NumAdjunto']){
				$num=$pedido['NumAdjunto'];
			}
			if ($pedido['estado']=="activo"){
				$funcOnclick = ' eliminarAdjunto('.$num.' , '."'".$dedonde."'".' , '.$pedido['nfila'].');';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-trash"></span></a></td>';
				$classtr = '';
				$estadoInput = '';
			}else{
				$classtr = ' class="tachado" ';
				$estadoInput = 'disabled';
				$funcOnclick = ' retornarAdjunto('.$num.', '."'".$dedonde."'".', '.$pedido['nfila'].');';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-export"></span></a></td>';
	
			}
		}
		$respuesta['html'] .='<tr id="lineaP'.($pedido['nfila']).'" '.$classtr.'>';
		if (isset($pedido['NumAdjunto'])){
		$respuesta['html'] .='<td>'.$pedido['NumAdjunto'].'</td>';
		}
		
		$respuesta['html'] .='<td>'.$pedido['fecha'].'</td>';
		$respuesta['html'] .='<td>'.$pedido['total'].'</td>';
		
		$respuesta['html'].=$btnELiminar_Retornar;
		$respuesta['html'] .='</tr>';
	}
	return $respuesta;
}

//Modifica el array de pedidos . Esta función se carga en albaranes.php
function modificarArrayPedidos($pedidos, $BDTpv){
	$respuesta=array();
		$i=1;
	foreach ($pedidos as $pedido){
			$datosPedido=$BDTpv->query('SELECT * FROM pedprot WHERE id= '.$pedido['idPedido'] );
			while ($fila = $datosPedido->fetch_assoc()) {
				$ped = $fila;
			}
			$res['Numpedpro']=$pedido['numPedido'];
			$res['idPedido']=$ped['id'];
			$res['fecha']=$ped['FechaPedido'];
			$res['idPePro']=$ped['idProveedor'];
			$res['total']=$ped['total'];
			$res['estado']="activo";
			$res['nfila']=$i;
			array_push($respuesta,$res);
		$i++;
	}
	return $respuesta;
}
//MOdifica el array de albaranes , esta función se carga en facturas.php
function modificarArrayAlbaranes($alabaranes, $BDTpv){
	$respuesta=array();
	$i=1;
	foreach ($alabaranes as $albaran){
			$datosAlbaran=$BDTpv->query('SELECT * FROM albprot WHERE id= '.$albaran['idAlbaran'] );
			while ($fila = $datosAlbaran->fetch_assoc()) {
				$alb = $fila;
			}
			$res['Numalbpro']=$albaran['numAlbaran'];
			$res['idAlbaran']=$alb['id'];
			$res['fecha']=$alb['Fecha'];
			$res['idPePro']=$alb['idProveedor'];
			$res['total']=$alb['total'];
			$res['estado']="activo";
			$res['nfila']=$i;
			array_push($respuesta,$res);
		$i++;
	}
	return $respuesta;
}
//Función que monta el html del pdf, primero se carga los datos dependiendo de donde venga 
//A continuación se va montando el html pero en dos partes :
//				- UNa la cabecera : son los datos que queremos fijos en todas las páginas 
//				- otro es el cuerpo 
//No hayq eu preocuparse si es mucho contenido ya que la librería pasa automaticamente a la siguiente hoja
function montarHTMLimprimir($id , $BDTpv, $dedonde, $idTienda){
	$CProv= new Proveedores($BDTpv);
	if ($dedonde=="factura"){
		$CFac=new FacturasCompras($BDTpv);
		$datos=$CFac->datosFactura($id);
		$datosProveedor=$CProv->buscarProveedorId($datos['idProveedor']);
		$productosFAc=$CFac->ProductosFactura($id);
		$productosDEF=modificarArrayProductos($productosFAc);
		$productos=json_decode(json_encode($productosDEF));
		$Datostotales = recalculoTotales($productos);
		$texto="Factura Proveedor";
		$numero=$datos['Numfacpro'];
		$suNumero=$datos['su_num_factura'];
		$textoSuNumero='SU FAC: '.$suNumero;
	}
	if ($dedonde=="albaran"){
		$CAlb=new AlbaranesCompras($BDTpv);
		$datos=$CAlb->datosAlbaran($id);
		$datosProveedor=$CProv->buscarProveedorId($datos['idProveedor']);
		$productosAlbaran=$CAlb->ProductosAlbaran($id);
		$productosDEF=modificarArrayProductos($productosAlbaran);
		$productos=json_decode(json_encode($productosDEF));
		$Datostotales = recalculoTotales($productos);
		$texto="Albarán Proveedor";
		$numero=$datos['Numalbpro'];
		$suNumero=$datos['su_numero'];
		$textoSuNumero='SU ALB: '.$suNumero;
	}
	if ($dedonde=="pedido"){
		$Cpedido=new PedidosCompras($BDTpv);
		$datos=$Cpedido->DatosPedido($id);
		$productosPedido=$Cpedido->ProductosPedidos($id);
		$productosDEF=modificarArrayProductos($productosPedido);
		$productos=json_decode(json_encode($productosDEF));
		$Datostotales = recalculoTotales($productos);
		$datosProveedor=$CProv->buscarProveedorId($datos['idProveedor']);
		$texto="Pedido Proveedor";
		$numero=$datos['Numpedpro'];
	}
	if (isset ($datos['Fecha'])){
		$date=date_create($datos['Fecha']);
		$fecha=date_format($date,'Y-m-d');
	}else{
		$fecha="";
	}
	
	$imprimir=array('cabecera'=>'',
	'html'=>''
	
	);
	//Datos del proveedor
	
	$imprimir['cabecera'].='<table margin>';
	$imprimir['cabecera'].='<tr>';
	$imprimir['cabecera'].='<td>';
	$imprimir['cabecera'].= '<div>';
	$imprimir['cabecera'].='<p>Proveedor: '.$datosProveedor['idProveedor'].'</p>';
	$imprimir['cabecera'] .='<p>'.$datosProveedor['nombrecomercial'].'</p>';
			if (isset ($datosProveedor['direccion '])){
				$imprimir['cabecera'] .='<p>'.$datosProveedor['direccion '].'</p>';
			}
			if (isset($suNumero)){
				$imprimir['cabecera'] .='<p>'.$textoSuNumero.'</p>';
			}
			$imprimir['cabecera'] .= '<p> NIF: '.$datosProveedor['nif'].'</p>';
			$imprimir['cabecera'] .='</div>';
	$imprimir['cabecera'].='</td>';
	$imprimir['cabecera'] .='<td>';
			$imprimir['cabecera'] .='<div>';
			$imprimir['cabecera'] .= '<p>'.$texto.'</p>';
			$imprimir['cabecera'] .= '<p> Nº: '.$numero.'</p>';
			$imprimir['cabecera'] .= '<p>Fecha: '.$fecha.'</p>';
			$imprimir['cabecera'] .= '<p>'.$idTienda.'</p>';
			$imprimir['cabecera'] .='</div>';
	$imprimir['cabecera'].='</td>';
	$imprimir['cabecera'].='</tr>';
	$imprimir['cabecera'].='</table>';
	
	
	$imprimir['cabecera'] .='<table  WIDTH="100%">';
	$imprimir['cabecera'] .='<tr>';
	if ($dedonde == "factura"){
		$imprimir['cabecera'] .='<td WIDTH="10%">ALB</td>';
	}
	if ($dedonde =="albaran"){
		$imprimir['cabecera'] .='<td WIDTH="10%">PED</td>';
	}
	$imprimir['cabecera'] .='<td WIDTH="10%">REF</td>';
	$imprimir['cabecera'] .='<td WIDTH="50%">DESCRIPCIÓN</td>';
	$imprimir['cabecera'] .='<td WIDTH="10%">CANT</td>';
	$imprimir['cabecera'] .='<td WIDTH="10%">PRECIO</td>';
	$imprimir['cabecera'] .='<td WIDTH="12%">IMPORTE</td>';
	$imprimir['cabecera'] .='</tr>';
	$imprimir['cabecera'] .='</table>';
	$imprimir['html'] .='<table  WIDTH="100%">';
	
	foreach($productosDEF as $producto){
		if ($producto['estado']=='Activo'){
			$imprimir['html'] .='<tr>';
			$bandera="";
			if (isset($producto['idalbpro'])){
				if ($producto['idalbpro']!==0){
					$bandera=$producto['idalbpro'];	
				}	
			}
			if ($dedonde=="albaran"){
				if ($producto['numPedido']==0){
					$imprimir['html'] .='<td  WIDTH="10%">'.$bandera.'</td>';
				}else{
					$bandera=$producto['numPedido'];
					$imprimir['html'] .='<td  WIDTH="10%">'.$bandera.'</td>';
				}
			}
			if ($dedonde=="factura"){
				if ($producto['idalbpro']==0){
					$imprimir['html'] .='<td  WIDTH="10%">'.$bandera2.'</td>';
				}else{
					$bandera2=$producto['idalbpro'];
					$imprimir['html'] .='<td  WIDTH="10%">'.$bandera2.'</td>';
				}
			}
			
			
			
			if ($producto['crefProveedor']>0){
				$refPro=$producto['crefProveedor'];
			}else{
				$refPro="";
			}
			$imprimir['html'] .='<td WIDTH="10%">'.$refPro.'</td>';
			$imprimir['html'] .='<td WIDTH="50%">'.$producto['cdetalle'].'</td>';
			$imprimir['html'] .='<td WIDTH="10%">'.number_format($producto['nunidades'],0).'</td>';
			$iva=$producto['iva']/100;
			$imprimir['html'] .='<td WIDTH="10%">'.number_format($producto['ultimoCoste'],2).'</td>';
			$imprimir['html'] .='<td WIDTH="12%">'.number_format($producto['importe'],2).'</td>';
			$imprimir['html'] .='</tr>';
		}
	}
	$imprimir['html'] .='</table>';
	$imprimir['html'] .='<br>';
	$imprimir['html'] .='<br>';
	$imprimir['html'] .='<table>';
	$imprimir['html'] .='
			<tr>
				<th>Tipo</th>
				<th>Base</th>
				<th>IVA</th>
			</tr>
		';

	if (isset($Datostotales)){
		// Montamos ivas y bases
		foreach ($Datostotales['desglose'] as  $iva => $basesYivas){
			$imprimir['html'].='<tr>';
			$imprimir['html'].='<td> '.$iva.'%</td>';
			$imprimir['html'].='<td> '.$basesYivas['base'].'</td>';
			$imprimir['html'].='<td>'.$basesYivas['iva'].'</td>';
			$imprimir['html'].='</tr>';
		}
	}
	$imprimir['html'] .='</table>';
	$imprimir['html'] .='<p align="right"> TOTAL: ';
	$imprimir['html'] .=(isset($Datostotales['total']) ? $Datostotales['total'] : '');
	$imprimir['html'] .='</p>';
	
	return $imprimir;
}


function comprobarPedidos($idProveedor, $BDTpv ){
	$Cped=new PedidosCompras($BDTpv);
	$estado="Guardado";
	$con=$Cped->pedidosProveedorGuardado($idProveedor, $estado);
	if(count($con)>0){
		$bandera=1;
	}else{
		$bandera=2;
	}
	return $bandera;
	
}
function comprobarAlbaran($idProveedor, $BDTpv){
	$Calb=new AlbaranesCompras($BDTpv);
	$estado="Guardado";
	$con=$Calb->albaranesProveedorGuardado($idProveedor, $estado);
	if (count($con)>0){
		$bandera=1;
	}else{
		$bandera=2;
	}
	return $bandera;
}
function guardarPedido($datosPost, $datosGet, $BDTpv){
	//@OBjetivo: guardar el pedido , para ello busca primero si ya tiene un pedido real o no , si es asi lo elimina 
	//Elimina también todos los registros de ese pedido real para poder añadir uno nuevo . Una vez que este guardado el nuevo registro 
	//de pedido, eliminamos el temporal 
	//@Parametros recibidos: 
	//datosPost: son los datos del $_POST
	//datosGet: son los datos del $_GET
	//$BDTpv: son los datos de configuración para poder llamar a la clase correspondiente
	//$error: crea todas las comprobaciones si algo no esta correcto se iguala a 1 y es la variable que retornamos
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];
	$error=0;
	
	$Cpedido=new PedidosCompras($BDTpv);
	if ($datosPost['idTemporal']){
		$numPedidoTemp=$datosPost['idTemporal'];
	}else{
		$numPedidoTemp=$datosGet['tActual'];
	}
	if (isset ($numPedidoTemp)) {
		$pedidoTemporal=$Cpedido->DatosTemporal($numPedidoTemp);
		if($pedidoTemporal['total']){
			$total=$pedidoTemporal['total'];
		}else{
			$error=1;
			$total=0;
		}
		if (isset($datosPost['fecha'])){
			$bandera=new DateTime($datosPost['fecha']);
			$fecha=$bandera->format('Y-m-d');
		}else{
			if ($pedidoTemporal['fechaInicio']){
				$bandera=new DateTime($pedidoTemporal['fechaInicio']);
				$fecha=$bandera->format('Y-m-d');
			}else{
				$fecha=date('Y-m-d');		
			}
		}
		if ($pedidoTemporal['idPedpro']){
			$datosPedidoReal=$Cpedido->DatosPedido($pedidoTemporal['idPedpro']);
			$numPedido=$datosPedidoReal['Numpedpro'];
		}else{
			$numPedido=0;
		}
		if (isset ($pedidoTemporal['Productos'])){
			$productos=$pedidoTemporal['Productos'];
		}else{
			$error=1;
		}
		$fechaCreacion=date("Y-m-d H:i:s");
		$datosPedido=array(
			'Numtemp_pedpro'=>$numPedidoTemp,
			'FechaPedido'=>$fecha,
			'idTienda'=>$Tienda['idTienda'],
			'idUsuario'=>$Usuario['id'],
			'idProveedor'=>$pedidoTemporal['idProveedor'],
			'estado'=>"Guardado",
			'total'=>$total,
			'numPedido'=>$numPedido,
			'fechaCreacion'=>$fechaCreacion,
			'Productos'=>$productos,
			'DatosTotales'=>$Datostotales
		);
	}else{
		$error=1;
	}
	
	if ($error==0){
		if ($pedidoTemporal['idPedpro']){
			$idPedido=$pedidoTemporal['idPedpro'];
			$eliminarTablasPrincipal=$Cpedido->eliminarPedidoTablas($idPedido);
			$addNuevo=$Cpedido->AddPedidoGuardado($datosPedido, $idPedido, $numPedido);
			$eliminarTemporal=$Cpedido->eliminarTemporal($numPedidoTemp, $idPedido);
		}else{
			$idPedido=0;
			$addNuevo=$Cpedido->AddPedidoGuardado($datosPedido, $idPedido);
			$eliminarTemporal=$Cpedido->eliminarTemporal($numPedidoTemp, $idPedido);
		}
	}
	return $error;
	
}
?>
