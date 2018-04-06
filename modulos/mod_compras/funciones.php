<?php 

include '../../configuracion.php';
include_once '../../clases/FormasPago.php';
include_once '../../clases/articulos.php';
include_once '../../clases/ClaseTablaTienda.php';

function htmlProveedores($busqueda,$dedonde, $idcaja, $proveedores = array()){
	// @ Objetivo:
	// Montar el hmtl para mostrar con los proveeodr si los hubiera.
	// @ parametros:
	// 		$busqueda -> El valor a buscar,aunque puede venir vacio.. 
	//		$dedonde  -> Nos indica de donde viene. ()
	$resultado = array();
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
			.' AS at ON a.idArticulo = at.idArticulo AND at.idTienda =1 left join articulosProveedores as p on a.idArticulo=p.`idArticulo` and p.idProveedor='.$idProveedor.' WHERE '.$buscar.' group by  a.idArticulo LIMIT 0 , 30 ';
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
						.$producto['ultimoCoste'].",".$producto['idArticulo'].", '".$dedonde."' , ".
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
			$resultado['html'] .= '<td>'.$producto['ultimoCoste'].'</td>';

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
	
	$respuesta['desglose'] = $desglose;
	$respuesta['subivas']=$subivas;
	$respuesta['total'] = number_format($subtotal,2);
	return $respuesta;
}

function htmlLineaProducto($productos, $dedonde){
	//@Objetivo:
	// html de la linea de los productos tanto para pedido, albaran y factura
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
				$bandera= number_format($producto['ultimoCoste'], 4);
				$coste='<input type="text" id="ultimo_coste_'.$producto['nfila'].'" data-obj="ultimo_coste" onkeydown="controlEventos(event)" name="ultimo" onBlur="controlEventos(event)" value="'.$bandera.'" size="6">';
			}else{
				$coste= number_format($producto['ultimoCoste'], 4);
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
			$filaProveedor='<td><input id="Proveedor_Fila_'
								.$producto['nfila'].'" type="text" data-obj="Proveedor_Fila" '
								.'name="proveedor" placeholder="ref" size="7"  onkeydown="controlEventos(event)" '
								.'onBlur="controlEventos(event)">'
								.'<a onclick=buscarReferencia("Proveedor_Fila_'
								.$producto['nfila'].'") style="display:none" id="enlaceCambio'
								.$producto['nfila'].'">'
								.'<span class="glyphicon glyphicon-cog"></span>'
								.'</a></td>';
			if (isset($producto['crefProveedor'])){
				if ($producto['crefProveedor']){
				$filaProveedor='<td class="referencia">'
								.'<input id="Proveedor_Fila_'.$producto['nfila']
								.'" type="text" data-obj="Proveedor_Fila"  value="'
								.$producto['crefProveedor']
								.'"name="proveedor" placeholder="ref" size="7"  onkeydown="controlEventos(event)"'
								.' onBlur="controlEventos(event)" disabled>'
								.'<a id="enlaceCambio'
								.$producto['nfila'].'" onclick=buscarReferencia("Proveedor_Fila_'
								.$producto['nfila'].'") style="text-align: right">'
								.'<span class="glyphicon glyphicon-cog"></span>'
								.'</a></td>';
				//~ }else{
				//~ $filaProveedor='<td><input id="Proveedor_Fila_'
								//~ .$producto['nfila'].'" type="text" data-obj="Proveedor_Fila" '
								//~ .'name="proveedor" placeholder="ref" size="7"  onkeydown="controlEventos(event)" '
								//~ .'onBlur="controlEventos(event)">'.
								//~ .'<a onclick=buscarReferencia("Proveedor_Fila_'
								//~ .$producto['nfila'].'") style="display:none" id="enlaceCambio'
								//~ .$producto['nfila'].'">'
								//~ .'<span class="glyphicon glyphicon-cog"></span>'
								//~ .'</a></td>';
				}
			//~ }else{
				//~ $filaProveedor='<td><input id="Proveedor_Fila_'
								//~ .$producto['nfila'].'" type="text" data-obj="Proveedor_Fila" '
								//~ .'name="proveedor" placeholder="ref" size="7"  onkeydown="controlEventos(event)" '
								//~ .'onBlur="controlEventos(event)">'
								//~ .'<a onclick=buscarReferencia("Proveedor_Fila_'
								//~ .$producto['nfila'].'") style="display:none" id="enlaceCambio'
								//~ .$producto['nfila'].'">'.
								//~ .'<span class="glyphicon glyphicon-cog"></span>'
								//~ .'</a></td>';
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
		 $cant=number_format($producto['nunidades'],2);
		 $respuesta['html'] .= '<td><input class="unidad" id="Unidad_Fila_'.$producto['nfila'].'" type="text" data-obj="Unidad_Fila"  pattern="?-[0-9]+" name="unidad" placeholder="unidad" size="4"  value="'.$cant.'"  '.$estadoInput.' onkeydown="controlEventos(event)" onBlur="controlEventos(event)"></td>';
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

function modificarArrayProductos($productos){
	//@Objetivo:
	// Modificar el array de productos para poder trabajar en facturas , pedidos y albaranes
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

function modalAdjunto($adjuntos, $dedonde, $BDTpv){
	//@Objetivo: 
	//retornar el html dle modal de adjuntos tanto como si buscamos un pedido en albaranes o un albarán en facturas
	$respuesta['html']	.= '<table class="table table-striped"><thead>';
	$respuesta['html']	.= '<th>';
	$respuesta['html']	.= '<td>Número </td>';
	$respuesta['html']	.= '<td>Fecha</td>';
	if ($dedonde=="factura"){
		$respuesta['html']	.= '<td>Fecha Venci</td>';
		$respuesta['html']	.= '<td>Forma Pago</td>';
	}
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
		if ($dedonde=="factura"){
			if($adjunto['FechaVencimiento']){
				if ($adjunto['FechaVencimiento']=="0000-00-00"){
					$fechaVenci="";
				}else{
					$fechaVenci=$adjunto['FechaVencimiento'];
				}
				
			}else{
				$fechaVenci="";
			}
			if ($adjunto['formaPago']){
				$formasPago=new FormasPago($BDTpv);
				$datosFormaPago=$formasPago->datosPrincipal($adjunto['formaPago']);
				$textformaPago=$datosFormaPago['descripcion'];
			}else{
				$textformaPago="";
			}
			$respuesta['html']	.= '<td>'.$fechaVenci.'</td>';
			$respuesta['html']	.= '<td>'.$textformaPago.'</td>';
		}
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
function lineaAdjunto($adjunto, $dedonde){
	//@Objetivo:
	//Retornar el html de la linea de adjuntos(esto puede ser un pedido en albarán o un albarán en factura).
	//@Parametros:
	//adjunto: los datos del albarán o pedido a adjuntar
	//dedonde: de donde venimos si de albarán o de factura
		$respuesta['html']="";
	if(isset($adjunto)){
		if ($adjunto['estado']){
			if ($adjunto['NumAdjunto']){
				$num=$adjunto['NumAdjunto'];
			}
			if ($adjunto['Numpedpro']){
				$num=$adjunto['Numpedpro'];
			}
			if ($adjunto['estado']=="activo"){
				$funcOnclick = ' eliminarAdjunto('.$num.' , '."'".$dedonde."'".' , '.$adjunto['nfila'].');';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-trash"></span></a></td>';
				$classtr = '';
				$estadoInput = '';
			}else{
				$classtr = ' class="tachado" ';
				$estadoInput = 'disabled';
				$funcOnclick = ' retornarAdjunto('.$num.', '."'".$dedonde."'".', '.$adjunto['nfila'].');';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-export"></span></a></td>';
	
			}
		}
		$respuesta['html'] .='<tr id="lineaP'.($adjunto['nfila']).'" '.$classtr.'>';
		if (isset($adjunto['NumAdjunto'])){
		$respuesta['html'] .='<td>'.$adjunto['NumAdjunto'].'</td>';
		}
		
		$respuesta['html'] .='<td>'.$adjunto['fecha'].'</td>';
		$respuesta['html'] .='<td>'.$adjunto['total'].'</td>';
		
		$respuesta['html'].=$btnELiminar_Retornar;
		$respuesta['html'] .='</tr>';
	}
	return $respuesta;
}


function modificarArrayPedidos($pedidos, $BDTpv){
	//Objetivo : 
	//Modificar el array de pedidos . Esta función se carga en albaranes.php
	$respuesta=array();
		$i=1;
	foreach ($pedidos as $pedido){
			$datosPedido=$BDTpv->query('SELECT * FROM pedprot WHERE id= '.$pedido['idPedido'] );
			while ($fila = $datosPedido->fetch_assoc()) {
				$ped = $fila;
			}
			//$res['Numpedpro']=$pedido['numPedido'];
			$res['NumAdjunto']=$pedido['numPedido'];
			//$res['idPedido']=$ped['id'];
			$res['idAdjunto']=$ped['id'];
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

function modificarArrayAlbaranes($alabaranes, $BDTpv){
	//@Objetivo:
	//MOdificar el array de albaranes , esta función se carga en facturas.php
	$respuesta=array();
	$i=1;
	foreach ($alabaranes as $albaran){
			$datosAlbaran=$BDTpv->query('SELECT * FROM albprot WHERE id= '.$albaran['idAlbaran'] );
			while ($fila = $datosAlbaran->fetch_assoc()) {
				$alb = $fila;
			}
			//~ $res['Numalbpro']=$albaran['numAlbaran'];
			//~ $res['idAlbaran']=$alb['id'];
			$res['NumAdjunto']=$albaran['numAlbaran'];
			$res['idAdjunto']=$alb['id'];
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

function montarHTMLimprimir($id , $BDTpv, $dedonde, $idTienda){
	//@Objetivo:
	//Función que monta el html del pdf, primero se carga los datos dependiendo de donde venga 
	//A continuación se va montando el html pero en dos partes :
	//				- UNa la cabecera : son los datos que queremos fijos en todas las páginas 
	//				- otro es el cuerpo 
	//No hayq eu preocuparse si es mucho contenido ya que la librería pasa automaticamente a la siguiente hoja
	$CProv= new Proveedores($BDTpv);
	$Ctienda=new ClaseTablaTienda($BDTpv);
	$datosTienda=$Ctienda->DatosTienda($idTienda);
	if ($dedonde=="factura"){
		$CFac=new FacturasCompras($BDTpv);
		$datos=$CFac->datosFactura($id);
		$productosAdjuntos=$CFac->ProductosFactura($id);
		$texto="Factura Proveedor";
		$numero=$datos['Numfacpro'];
		$suNumero=$datos['su_num_factura'];
		$textoSuNumero='SU FAC: '.$suNumero;
		$date=date_create($datos['Fecha']);
	}
	if ($dedonde=="albaran"){
		$CAlb=new AlbaranesCompras($BDTpv);
		$datos=$CAlb->datosAlbaran($id);
		$productosAdjuntos=$CAlb->ProductosAlbaran($id);
		$texto="Albarán Proveedor";
		$numero=$datos['Numalbpro'];
		$suNumero=$datos['su_numero'];
		$textoSuNumero='SU ALB: '.$suNumero;
		$date=date_create($datos['Fecha']);
	}
	if ($dedonde=="pedido"){
		$Cpedido=new PedidosCompras($BDTpv);
		$datos=$Cpedido->DatosPedido($id);
		$productosAdjuntos=$Cpedido->ProductosPedidos($id);
		$texto="Pedido Proveedor";
		$numero=$datos['Numpedpro'];
		$date=date_create($datos['FechaPedido']);
	}
	
	$datosProveedor=$CProv->buscarProveedorId($datos['idProveedor']);
	$productosDEF=modificarArrayProductos($productosAdjuntos);
	$productos=json_decode(json_encode($productosDEF));
	$Datostotales = recalculoTotales($productos);
	
	if (isset ($date)){
		
		$fecha=date_format($date,'Y-m-d');
	}else{
		$fecha="";
	}
	
	$imprimir=array('cabecera'=>'',
	'html'=>''
	
	);
	//Datos del proveedor
	$imprimir['cabecera'].='<p ></p><p ></p>';
	$imprimir['cabecera'].='<table >';
	$imprimir['cabecera'].='<tr>';
	$imprimir['cabecera'].='<td>'
	.'Proveedor: '.$datosProveedor['idProveedor'].'<br>'
	.$datosProveedor['nombrecomercial'].'<br>'
	.'Dirección:'.$datosProveedor['direccion'].'<br>'
	.'NIF/CIF: '.$datosProveedor['nif'].'<br>'
	.'Teléfono: '.$datosProveedor['telefono'].'<br>'
	.'Email: '.$datosProveedor['email'].'<br>'
	.'Fax: '.$datosProveedor['fax'].'<br>';
			if (isset($suNumero)){
				$imprimir['cabecera'] .=''.$textoSuNumero.'<br>';
			}
	$imprimir['cabecera'].='</td>';
	$imprimir['cabecera'] .='<td>'
			
			.$texto.'<br>'
			.'Nº: '.$numero.'<br>'
			.'Fecha: '.$fecha.'<br>'
			.$datosTienda['razonsocial'].'<br>'
			.'Direccion: '.$datosTienda['direccion'].'<br>'
			.'Telefono:'.$datosTienda['telefono'].'<br>';
			
	$imprimir['cabecera'].='</td>';
	$imprimir['cabecera'].='</tr>';
	$imprimir['cabecera'].='</table>';
	
	$imprimir['cabecera'].='<hr/><hr/>';
	$imprimir['cabecera'] .='<table  WIDTH="100%">';
	$imprimir['cabecera'] .='<tr>';
	if ($dedonde == "factura"){
		$imprimir['cabecera'] .='<td WIDTH="10%"><b>ALB</b></td>';
	}
	if ($dedonde =="albaran"){
		$imprimir['cabecera'] .='<td WIDTH="10%"><b>PED</b></td>';
	}
	$imprimir['cabecera'] .='<td WIDTH="10%"><b>REF</b></td>';
	$imprimir['cabecera'] .='<td WIDTH="50%"><b>DESCRIPCIÓN</b></td>';
	$imprimir['cabecera'] .='<td WIDTH="10%"><b>CANT</b></td>';
	$imprimir['cabecera'] .='<td WIDTH="10%"><b>COSTE</b></td>';
	$imprimir['cabecera'] .='<td WIDTH="12%"><b>IMPORTE</b></td>';
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
	$imprimir['html'].='<hr/><hr/>';
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
function guardarPedido($datosPost, $datosGet, $BDTpv, $Datostotales){
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
			$numPedido=0;
			$addNuevo=$Cpedido->AddPedidoGuardado($datosPedido, $idPedido, $numPedido);
			$eliminarTemporal=$Cpedido->eliminarTemporal($numPedidoTemp, $idPedido);
		}
	}else{
		if ($datosGet['id']){
				$fecha=$datosPost['fecha'];
				$mod=$Cpedido->modFechaPedido($fecha, $datosGet['id']);
				
				$error=0;
			}else{
				$error=1;
			}
	}
	return $error;
	
}
function guardarAlbaran($datosPost, $datosGet , $BDTpv, $Datostotales){
	//@Objetivo: guardar los da tos del albarán 
	//Primero se eliminan todos los registros que tenga el id del albarán real de esta manera a continuación insertamos los nuevo
	//registros
	//Por último se elimina el albarán temporal
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];
	$error=0;
	$respuesta=array();
	$CAlb=new AlbaranesCompras($BDTpv);
		if (isset ($datosPost['idTemporal'])){
				$idAlbaranTemporal=$datosPost['idTemporal'];
		}else{
				$idAlbaranTemporal=$datosGet['tActual'];
		}
		
		if (isset($idAlbaranTemporal)){
			$datosAlbaran=$CAlb->buscarAlbaranTemporal($idAlbaranTemporal);
			if($datosAlbaran['total']){
				$total=$datosAlbaran['total'];
			}else{
				$error=1;
				$total=0;
			}
	
			if ($datosPost['suNumero']>0){
				$suNumero=$datosPost['suNumero'];
			}else{
				$suNumero=0;
			}
			if (isset ($datosPost['fecha'])){
				$fecha=$datosPost['fecha'];
			}else{
				$fecha=$datosAlbaran['fechaInicio'];
			}
			if (isset ($datosAlbaran['Productos'])){
				$productos=$datosAlbaran['Productos'];
			}else{
				$productos=0;
				$error=1;
			}
			if ($datosPost['formaVenci']){
				$formaPago=$datosPost['formaVenci'];
			}else{
				$formaPago=0;
			}
			if($datosPost['fechaVenci']){
				$fechaVenci=$datosPost['fechaVenci'];
			}else{
				$fechaVenci="";
			}
			
			$datos=array(
				'Numtemp_albpro'=>$idAlbaranTemporal,
				'fecha'=>$fecha,
				'idTienda'=>$Tienda['idTienda'],
				'idUsuario'=>$Usuario['id'],
				'idProveedor'=>$datosAlbaran['idProveedor'],
				'estado'=>"Guardado",
				'total'=>$total,
				'DatosTotales'=>$Datostotales,
				'productos'=>$productos,
				'pedidos'=>$datosAlbaran['Pedidos'],
				'suNumero'=>$suNumero,
				'formaPago'=>$formaPago,
				'fechaVenci'=>$fechaVenci
			);
			$dedonde="albaran";
			
		}else{
			$error=1;
		}
		//Si recibe número de albarán quiere decir que ya existe por esta razón tenemos que eliminar todos los datos del albarán
		//original para poder poner los nuevo, una vez que este todo guardado eliminamos el temporal.
		//Si no es así, es un albarán nuevo solo tenemos que crear un albarán definitivo y eliminar el temporal
		if ($error==0){
			if ($datosAlbaran['numalbpro']){
					$numAlbaran=$datosAlbaran['numalbpro'];
					$datosReal=$CAlb->buscarAlbaranNumero($numAlbaran);
					$idAlbaran=$datosReal['id'];
					$eliminarTablasPrincipal=$CAlb->eliminarAlbaranTablas($idAlbaran);
					$addNuevo=$CAlb->AddAlbaranGuardado($datos, $idAlbaran);
					$historico=historicoCoste($productos, $dedonde, $addNuevo['id'], $BDTpv, $datosAlbaran['idProveedor'], $fecha);
					$eliminarTemporal=$CAlb->EliminarRegistroTemporal($idAlbaranTemporal, $idAlbaran);
					
					
			}else{
					$idAlbaran=0;
					$numAlbaran=0;
					$addNuevo=$CAlb->AddAlbaranGuardado($datos, $idAlbaran);
					$historico=historicoCoste($productos, $dedonde, $addNuevo['id'], $BDTpv, $datosAlbaran['idProveedor'], $fecha);

					$eliminarTemporal=$CAlb->EliminarRegistroTemporal($idAlbaranTemporal, $idAlbaran);
					
			}
			
		}else{
			if ($datosGet['id']){
				if ($datosPost['suNumero']>0){
					$suNumero=$datosPost['suNumero'];
				}else{
					$suNumero=0;
				}
				
				$fecha=$datosPost['fecha'];
				$mod=$CAlb->modFechaNumero($datosGet['id'], $suNumero, $fecha);
				
				$error=0;
			}else{
				$error=1;
			}
			
		}
		$respuesta['historico']=$historico;
		$respuesta['sql']=$addNuevo;
		$respuesta['texto']="nuevo albaran";
	return $error;
	//~ return $respuesta;
}

function guardarFactura($datosPost, $datosGet , $BDTpv, $Datostotales, $importesFactura){
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];
	$error=0;
	$CFac = new FacturasCompras($BDTpv);
	if ($datosPost['idTemporal']){
		$idFacturaTemporal=$datosPost['idTemporal'];
	}else{
		$idFacturaTemporal=$datosGet['tActual'];
	}
	if(isset ($idFacturaTemporal)){
		$datosFactura=$CFac->buscarFacturaTemporal($idFacturaTemporal);
		if(['total']){
				$total=$datosFactura['total'];
		}else{
				$total=0;
				$error=1;
		}
		$fecha=$datosPost['fecha'];
		$estado="Guardado";
		if (is_array($importesFactura)){
				
				foreach ($importesFactura as $import){
					$entregado=$entregado+$import['importe'];
				}
				if ($total==$entregado){
					$estado="Pagado total";
				}else{
					$estado="Pagado Parci";
				}
			}
		if ($datosPost['suNumero']>0){
				$suNumero=$datosPost['suNumero'];
		}else{
			$suNumero=0;
		}
		$datos=array(
			'Numtemp_facpro'=>$idFacturaTemporal,
			'fecha'=>$fecha,
			'idTienda'=>$Tienda['idTienda'],
			'idUsuario'=>$Usuario['id'],
			'idProveedor'=>$datosFactura['idProveedor'],
			'estado'=>$estado,
			'total'=>$total,
			'DatosTotales'=>$Datostotales,
			'productos'=>$datosFactura['Productos'],
			'albaranes'=>$datosFactura['Albaranes'],
			'importes'=>$importesFactura,
			'suNumero'=>$suNumero
		);
		$dedonde="factura";
		if ($error==0){
			if ($datosFactura['numfacpro']){
				$numFactura=$datosFactura['numfacpro'];
				$datosReal=$CFac->buscarFacturaNumero($numFactura);
				$idFactura=$datosReal['id'];
				$eliminarTablasPrincipal=$CFac->eliminarFacturasTablas($idFactura);
				$addNuevo=$CFac->AddFacturaGuardado($datos, $idFactura, $numFactura);
				$historico=historicoCoste($datosFactura['Productos'], $dedonde, $addNuevo['id'], $BDTpv, $datosFactura['idProveedor'], $datosFactura['fechaInicio']);

				$eliminarTemporal=$CFac->EliminarRegistroTemporal($idFacturaTemporal, $idFactura);
			}else{
				$idFactura=0;
				$numFactura=0;
				$addNuevo=$CFac->AddFacturaGuardado($datos, $idFactura, $numFactura);
				$historico=historicoCoste($datosFactura['Productos'], $dedonde, $addNuevo['id'], $BDTpv, $datosFactura['idProveedor'], $datosFactura['fechaInicio']);

				$eliminarTemporal=$CFac->EliminarRegistroTemporal($idFacturaTemporal, $idFactura);
			}
			//~ $respuesta['historico']=$historico;
			//~ $respuesta['sql']=$addNuevo;
			//~ $respuesta['texto']="nuevo albaran";
		}else{
			$error=1;
		}
		
	}else{
		if ($datosGet['id']){
				if ($datosPost['suNumero']>0){
					$suNumero=$datosPost['suNumero'];
				}else{
					$suNumero=0;
				}
				
				$fecha=$datosPost['fecha'];
				$mod=$CFac->modFechaNumero($datosGet['id'], $fecha, $suNumero);
				
				$error=0;
				//~ $respuesta['sqlMod']=$mod;
			}else{
				$error=1;
			}
	}
	//~ return $respuesta;
	return $error;
	
}
function htmlTotales($Datostotales){
	$htmlIvas['html'] = '';
	if (isset($Datostotales['desglose'])){
		foreach ($Datostotales['desglose'] as  $key => $basesYivas){
			$key = intval($key);
			$htmlIvas['html'].='<tr id="line'.$key.'">';
			$htmlIvas['html'].='<td id="tipo'.$key.'"> '.$key.'%</td>';
			$htmlIvas['html'].='<td id="base'.$key.'"> '.$basesYivas['base'].'</td>';
			$htmlIvas['html'].='<td id="iva'.$key.'">'.$basesYivas['iva'].'</td>';
			$htmlIvas['html'].='</tr>';
		}
	}
	return $htmlIvas;
}

function cancelarFactura($datosPost, $datosGet,$BDTpv){
	$CFac = new FacturasCompras($BDTpv);
	$CAlb=new AlbaranesCompras($BDTpv);
	$error=0;
	if ($datosPost['idTemporal']){
			$idFacturaTemporal=$datosPost['idTemporal'];
	}else{
			$idFacturaTemporal=$datosGet['tActual'];
	}
	if (isset($idFacturaTemporal)){
		$datosFactura=$CFac->buscarFacturaTemporal($idFacturaTemporal);
		$albaranes=json_decode($datosFactura['Albaranes'], true);
		foreach ($albaranes as $albaran){
			$mod=$CAlb->modEstadoAlbaran($albaran['idAdjunto'], "Guardado");
		}
		$idFactura=0;
		$eliminarTemporal=$CFac->EliminarRegistroTemporal($idFacturaTemporal, $idFactura);
	}else{
		$error=1;
	}
	return $error;
}

function cancelarAlbaran($datosPost, $datosGet, $BDTpv){
	$CAlb=new AlbaranesCompras($BDTpv);
	$Cped = new PedidosCompras($BDTpv);
	$error=0;
	if ($datosPost['idTemporal']){
		$idTemporal=$datosPost['idTemporal'];
	}else{
		$idTemporal=$datosGet['tActual'];
	}
	if (isset($idTemporal)){
		$datosAlbaran=$CAlb->buscarAlbaranTemporal($idTemporal);
		$pedidos=json_decode($datosAlbaran['Pedidos'], true);
		foreach ($pedidos as $pedido){
			$mod=$Cped->modEstadoPedido($pedido['idAdjunto'], "Guardado");
		}
		$idAlbaran=0;
		$eliminarTemporal=$CAlb->EliminarRegistroTemporal($idTemporal, $idAlbaran);
	}else{
		$error=1;
	}
	return $error;
}
function htmlImporteFactura($datos, $BDTpv){
	$formaPago=new FormasPago($BDTpv);
	$datosPago=$formaPago->datosPrincipal($datos['forma']);
	$respuesta['html'].='<tr>';
	$respuesta['html'].='<td>'.$datos['importe'].'</td>';
	$respuesta['html'].='<td>'.$datos['fecha'].'</td>';
	$respuesta['html'].='<td>'.$datosPago['descripcion'].'</td>';
	$respuesta['html'].='<td>'.$datos['referencia'].'</td>';
	$respuesta['html'].='<td>'.$datos['pendiente'].'</td>';
	$respuesta['html'].='</tr>';
	return $respuesta;
	
}
function htmlFormasVenci($formaVenci, $BDTpv){
	$html="";
	$formasPago=new FormasPago($BDTpv);
	$principal=$formasPago->datosPrincipal($formaVenci);
	$html.='<option value="'.$principal['id'].'">'.$principal['descripcion'].'</option>';
	$otras=$formasPago->formadePagoSinPrincipal($formaVenci);
	foreach ($otras as $otra){
		$html.='<option value= "'.$otra['id'].'">'.$otra['descripcion'].'</option>';
}
	$respuesta['formas']=$formaVenci;
	$respuesta['html']=$html;
	return $respuesta;
}
function modificarArraysImportes($importes, $total){
	$importesDef= array();
	foreach ($importes as $importe){
		$nuevo= array();
		$nuevo['importe']=$importe['importe'];
		$nuevo['fecha']=$importe['FechaPago'];
		$nuevo['referencia']=$importe['Referencia'];
		$nuevo['forma']=$importe['idFormasPago'];
		$total=$total-$importe['importe'];
		$nuevo['pendiente']=$total;
		array_push($importesDef, $nuevo);
	}
	return $importesDef;
}
function historicoCoste($productos, $dedonde, $numDoc, $BDTpv, $idProveedor, $fecha){
	$CArt=new Articulos($BDTpv);
	$fechaCreacion=date('Y-m-d');
	$datos=array(
	'dedonde'=>$dedonde,
	'numDoc'=>$numDoc,
	'tipo'=>"compras",
	'fechaCreacion'=>$fechaCreacion
	);
	$resultado['datos']=$productos;
	$error=0;
	$productos = json_decode($productos, true);
	foreach ($productos as $producto){
		if (isset($producto['CosteAnt'])){
			$buscar=$CArt->buscarReferencia($producto['idArticulo'], $idProveedor);
			$datosNuevos=array(
				'coste'=>$producto['ultimoCoste'],
				'idArticulo'=>$producto['idArticulo'],
				'idProveedor'=>$idProveedor,
				'fecha'=>$fecha,
				'estado'=>"activo"
			);
			if ($buscar){
				if ($buscar['fechaActualizacion']>$fecha){
					$error=1;
				}else{
					$mod=$CArt->modificarCosteProveedorArticulo($datosNuevos);
				}
				
			}else{
				$datosNuevos['refProveedor']=0;
				$add=$CArt->addArticulosProveedores($datosNuevos);
			}
			
			$datos['idArticulo']=$producto['idArticulo'];
			$datos['antes']=$producto['CosteAnt'];
			$datos['nuevo']=$producto['ultimoCoste'];
			$datos['estado']="Pendiente";
			if ($error==0){
				$nuevoHistorico=$CArt->addHistorico($datos);
				$resultado['sql']=$nuevoHistorico;
			}
			
		}
		
		
	}
	return $resultado;
}
?>
