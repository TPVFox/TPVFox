<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvOlalla de Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero ,
 * @Descripcion	Funciones en php para modulo TPV
 * */
	include_once '../../clases/FormasPago.php';
	include_once '../../clases/TiposVencimiento.php';
 
function BuscarProductos($id_input,$campoAbuscar,$idcaja, $busqueda,$BDTpv) {
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
		$sql = 'SELECT a.`idArticulo` , a.`articulo_name` , ac.`codBarras` , ap.pvpCiva, at.crefTienda , a.`iva` '
			.' FROM `articulos` AS a LEFT JOIN `articulosCodigoBarras` AS ac '
			.' ON a.idArticulo = ac.idArticulo LEFT JOIN `articulosPrecios` AS ap '
			.' ON a.idArticulo = ap.idArticulo AND ap.idTienda =1 LEFT JOIN `articulosTiendas` '
			.' AS at ON a.idArticulo = at.idArticulo AND at.idTienda =1 WHERE '.$buscar.' LIMIT 0 , 30 ';
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

function htmlClientes($busqueda,$dedonde, $idcaja, $clientes){
	// @ Objetivo:
	// Montar el hmtl para mostrar con los clientes si los hubiera.
	// @ parametros:
	// 		$busqueda -> El valor a buscar,aunque puede venir vacio.. 
	//		$dedonde  -> Nos indica de donde viene. (tpv,cerrados,cobrados)
	$resultado = array();
	$n_dedonde = 0 ; 
	$resultado['encontrados'] = count($clientes);
	$idcaja;
	$resultado['html'] = '<label>Busqueda Cliente en '.$dedonde.'</label>';
	$resultado['html'] .= '<input id="cajaBusquedacliente" name="valorCliente" placeholder="Buscar"'.
				'size="13" data-obj="cajaBusquedacliente" value="'.$busqueda.'" onkeydown="controlEventos(event)" type="text">';
				
	if (count($clientes)>10){
		$resultado['html'] .= '<span>10 clientes de '.count($clientes).'</span>';
	}
	$resultado['html'] .= '<table class="table table-striped"><thead>';
	$resultado['html'] .= ' <th></th>'; //cabecera blanca para boton agregar
	$resultado['html'] .= ' <th>Nombre</th>';
	$resultado['html'] .= ' <th>Razon social</th>';
	$resultado['html'] .= ' <th>NIF</th>';
	$resultado['html'] .= '</thead><tbody>';
	if (count($clientes)>0){
		$contad = 0;
		foreach ($clientes as $cliente){  
			$razonsocial_nombre=$cliente['Nombre'].' - '.$cliente['razonsocial'];
			$datos = 	"'".$cliente['idClientes']."','".addslashes(htmlentities($razonsocial_nombre,ENT_COMPAT))."'";
			$resultado['html'] .= '<tr id="Fila_'.$contad.'" onmouseout="abandonFila('.$contad
			.')" onmouseover="sobreFilaCraton('.$contad.')" onclick="escribirClienteSeleccionado('.$datos.",'".$dedonde."'".');">';
		
			$resultado['html'] .= '<td id="C'.$contad.'_Lin" >';
			$resultado['html'] .= '<input id="N_'.$contad.'" name="filacliente" onfocusout="abandonFila('
						.$contad.')" data-obj="idN" onkeydown="controlEventos(event)" onfocus="sobreFila('.$contad.')"   type="image"  alt="">';
			$resultado['html'] .= '<span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';
			$resultado['html'] .= '<td>'.htmlspecialchars($cliente['Nombre'],ENT_QUOTES).'</td>';
			$resultado['html'] .= '<td>'.htmlentities($cliente['razonsocial'],ENT_QUOTES).'</td>';
			$resultado['html'] .= '<td>'.$cliente['nif'].'</td>';
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

function htmlProductos($productos,$id_input,$campoAbuscar,$busqueda, $dedonde){
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
						.number_format($producto['pvpCiva'],2).",".$producto['idArticulo'].
						" , '".$dedonde."'";
			$resultado['html'] .= '<tr id="N_'.$contad.'" data-obj= "idN" onmouseout="abandonFila('
						.$contad.')" onmouseover="sobreFilaCraton('.$contad.')"  onclick="escribirProductoSeleccionado('.$datos.');">';
			
			$resultado['html'] .= '<td id="C'.$contad.'_Lin" ><input id="N_'.$contad.'" name="filaproducto" onfocusout="abandonFila('
						.$contad.')" data-obj="idN" onfocus="sobreFila('.$contad.')" onkeydown="controlEventos(event)" type="image"  alt=""><span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';
			$resultado['html'] .= '<td>'.htmlspecialchars($producto['crefTienda'], ENT_QUOTES).'</td>';				
			$resultado['html'] .= '<td>'.htmlspecialchars($producto['articulo_name'], ENT_QUOTES).'</td>';
			$resultado['html'] .= '<td>'.number_format($producto['pvpCiva'],2).'</td>';

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
		if ($product->estadoLinea === 'Activo'){
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


function modificarArrayProductos($productos){
	$respuesta=array();
	foreach ($productos as $producto){
		$product['idArticulo']=$producto['idArticulo'];
		$product['cref']=$producto['cref'];
		$product['cdetalle']=$producto['cdetalle'];
		$product['precioCiva']=$producto['precioCiva'];
		$product['iva']=$producto['iva'];
		$product['ccodbar']=$producto['ccodbar'];
		$product['nfila']=$producto['nfila'];
		$product['estadoLinea']=$producto['estadoLinea'];
		$product['ncant']=number_format($producto['ncant'],0);
		$product['nunidades']=$producto['nunidades'];
		
		$product['importe']=$producto['precioCiva']*$producto['nunidades'];
		array_push($respuesta,$product);
		
	}
	return $respuesta;
}

function htmlLineaPedidoAlbaran($productos, $dedonde){
	
	if(!is_array($productos)) {
		// Comprobamos si product no es objeto lo convertimos.
		$producto = (array)$productos;
		
	} else {
		$producto = $productos;
	}
	$producto=$productos;
	$respuesta=array('html'=>'');
	
		 	if ($producto['estadoLinea'] !=='Activo'){
				$classtr = ' class="tachado" ';
				$estadoInput = 'disabled';
				$funcOnclick = ' retornarFila('.$producto['nfila'].', '."'".$dedonde."'".');';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-export"></span></a></td>';
			} else {
				$funcOnclick = ' eliminarFila('.$producto['nfila'].' , '."'".$dedonde."'".');';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-trash"></span></a></td>';
				$classtr = ' ';
				$estadoInput = ' ';
			}
			$numeroPed="";
			if ($dedonde=="albaran"){
				if ($producto['NumpedCli']>0){
					$numeroPed=$producto['NumpedCli'];
				}else if ($producto['Numpedcli']>0){
					$numeroPed=$producto['Numpedcli'];
				}
			}
			if ($dedonde=="factura"){
				if ($producto['Numalbcli']>0){
					$numeroPed=$producto['Numalbcli'];
				}
			}
			if ($producto['ccodbar']==0){
				$codBarras="";
			}else{
				$codBarras=$producto['ccodbar'];
			}
			
		 $respuesta['html'] .='<tr id="Row'.($producto['nfila']).'" '.$classtr.'>';
		 
		 $respuesta['html'] .='<td class="linea">'.$producto['nfila'].'</td>';
		 if ($dedonde<>"pedidos"){
				$respuesta['html'] .='<td>'.$numeroPed.'</td>';
		}
		 $respuesta['html']	.= '<td class="idArticulo">'.$producto['idArticulo'].'</td>';
		 $respuesta['html'] .='<td class="referencia">'.$producto['cref'].'</td>';
		 $respuesta['html'] .='<td class="codbarras">'.$codBarras.'</td>';
		 $respuesta['html'] .= '<td class="detalle">'.$producto['cdetalle'].'</td>';
		 $cant=number_format($producto['nunidades'],0);
		 $respuesta['html'] .= '<td><input class="unidad" id="Unidad_Fila_'.$producto['nfila'].'" type="text" data-obj="Unidad_Fila" pattern="?-[0-9]+" name="unidad" placeholder="unidad" size="4"  value="'.$cant.'"  '.$estadoInput.' onkeydown="controlEventos(event)" onBlur="controlEventos(event)"></td>';
		 $respuesta['html'] .='<td class="pvp">'.$producto['precioCiva'].'</td>';
		 $respuesta['html'] .= '<td class="tipoiva">'.$producto['iva'].'%</td>';
		 $importe = $producto['precioCiva']*$producto['nunidades'];
		 $importe = number_format($importe,2);
		 $respuesta['html'] .='<td id="N'.$producto['nfila'].'_Importe" class="importe" >'.$importe.'</td>';
		 $respuesta['html'] .= $btnELiminar_Retornar;
		 $respuesta['html'] .='</tr>';
	 return $respuesta['html'];
}


function htmlPedidoAlbaran($pedidos, $dedonde){
	$respuesta="";
	$respuesta['html']="";
	if(isset($pedidos)){
	foreach($pedidos as $pedido){
		if ($pedido['estado']){
			if ($pedido['Numpedcli']){
				$num=$pedido['Numpedcli'];
			}
			if ($pedido['estado']=="Activo"){
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
		$respuesta['html'] .='<td>'.$pedido['Numpedcli'].'</td>';
		$respuesta['html'] .='<td>'.$pedido['fecha'].'</td>';
		$respuesta['html'] .='<td>'.$pedido['total'].'</td>';
		$respuesta['html'].=$btnELiminar_Retornar;
		$respuesta['html'] .='</tr>';
	}
	}
	return $respuesta;
}


function htmlAlbaranFactura($albaranes, $dedonde){
	$respuesta="";
	$respuesta['html']="";
	if(isset($albaranes)){
	foreach($albaranes as $albaran){
		if ($albaran['estado']){
			if ($albaran['Numalbcli']){
				$num=$albaran['Numalbcli'];
			}
			if ($albaran['estado']=="Activo"){
				$funcOnclick = ' eliminarAdjunto('.$num.' , '."'".$dedonde."'".' , '.$albaran['nfila'].');';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-trash"></span></a></td>';
				$classtr = '';
				$estadoInput = '';
			}else{
				$classtr = ' class="tachado" ';
				$estadoInput = 'disabled';
				$funcOnclick = ' retornarAdjunto('.$num.', '."'".$dedonde."'".', '.$albaran['nfila'].');';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-export"></span></a></td>';
	
			}
		}
		$respuesta['html'] .='<tr id="lineaP'.($albaran['nfila']).'" '.$classtr.'>';
		$respuesta['html'] .='<td>'.$albaran['Numalbcli'].'</td>';
		$respuesta['html'] .='<td>'.$albaran['fecha'].'</td>';
		$respuesta['html'] .='<td>'.$albaran['total'].'</td>';
		$respuesta['html'].=$btnELiminar_Retornar;
		$respuesta['html'] .='</tr>';
	}
	}
	return $respuesta;
}

//~ function lineaPedidoAlbaran($pedido, $dedonde){
		//~ $respuesta['html']="";
	//~ if(isset($pedido)){
	//~ if ($pedido['estado']){
			//~ if ($pedido['Numpedcli']){
				//~ $num=$pedido['Numpedcli'];
			//~ }
			//~ if ($pedido['estado']=="activo"){
				//~ $funcOnclick = ' eliminarAdjunto('.$num.' , '."'".$dedonde."'".' , '.$pedido['nfila'].');';
				//~ $btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-trash"></span></a></td>';
				//~ $classtr = '';
				//~ $estadoInput = '';
			//~ }else{
				//~ $classtr = ' class="tachado" ';
				//~ $estadoInput = 'disabled';
				//~ $funcOnclick = ' retornarAdjunto('.$num.', '."'".$dedonde."'".', '.$pedido['nfila'].');';
				//~ $btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-export"></span></a></td>';
	
			//~ }
		//~ }
		//~ $respuesta['html'] .='<tr id="lineaP'.($pedido['nfila']).'" '.$classtr.'>';
		//~ $respuesta['html'] .='<td>'.$pedido['Numpedcli'].'</td>';
		//~ $respuesta['html'] .='<td>'.$pedido['fecha'].'</td>';
		//~ $respuesta['html'] .='<td>'.$pedido['total'].'</td>';
		//~ $respuesta['html'].=$btnELiminar_Retornar;
		//~ $respuesta['html'] .='</tr>';
	//~ }
	//~ return $respuesta;
//~ }

//~ function lineaAlbaranFactura($albaran, $dedonde){
	//~ $respuesta['html']="";
	//~ if(isset($albaran)){
			//~ if ($albaran['estado']){
			//~ if ($albaran['Numalbcli']){
				//~ $num=$albaran['Numalbcli'];
			//~ }
			//~ if ($albaran['estado']=="Activo"){
				//~ $funcOnclick = ' eliminarAdjunto('.$num.' , '."'".$dedonde."'".' , '.$albaran['nfila'].');';
				//~ $btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-trash"></span></a></td>';
				//~ $classtr = '';
				//~ $estadoInput = '';
			//~ }else{
				//~ $classtr = ' class="tachado" ';
				//~ $estadoInput = 'disabled';
				//~ $funcOnclick = ' retornarAdjunto('.$num.', '."'".$dedonde."'".', '.$albaran['nfila'].');';
				//~ $btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-export"></span></a></td>';
	
			//~ }
		//~ }
		//~ $respuesta['html'] .='<tr id="lineaP'.($albaran['nfila']).'" '.$classtr.'>';
		//~ $respuesta['html'] .='<td>'.$albaran['Numalbcli'].'</td>';
		//~ $respuesta['html'] .='<td>'.$albaran['fecha'].'</td>';
		//~ $respuesta['html'] .='<td>'.$albaran['total'].'</td>';
		//~ $respuesta['html'].=$btnELiminar_Retornar;
		//~ $respuesta['html'] .='</tr>';
	//~ }
	//~ return $respuesta;
//~ }

//~ function modalPedidos($pedidos){
	//~ $contad = 0;
	//~ $respuesta['html'] .= '<table class="table table-striped"><thead>';
	//~ $respuesta['html'] .= '<th>';
	//~ $respuesta['html'] .='<td>Número </td>';
	//~ $respuesta['html'] .='<td>Fecha</td>';
	//~ $respuesta['html'] .='<td>Total</td>';
	//~ $respuesta['html'] .='</th>';
	//~ $respuesta['html'] .= '</thead><tbody>';
	//~ foreach ($pedidos as $pedido){
	//~ $respuesta['html'] .= '<tr id="Fila_'.$contad.'" onmouseout="abandonFila('
	//~ .$contad.')" onmouseover="sobreFilaCraton('.$contad.')"  onclick="buscarDatosPedido('.$pedido['Numpedcli'].');">';
	//~ $respuesta['html'] .= '<td id="C'.$contad.'_Lin" ><input id="N_'.$contad.'" name="filaproducto" onfocusout="abandonFila('
	//~ .$contad.')" data-obj="idN" onfocus="sobreFila('.$contad.')" onkeydown="controlEventos(event)" type="image"  alt=""><span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';

	//~ $respuesta['html'].='<td>'.$pedido['Numpedcli'].'</td>';
	//~ $respuesta['html'].='<td>'.$pedido['FechaPedido'].'</td>';
	//~ $respuesta['html'].='<td>'.$pedido['total'].'</td>';
	//~ $respuesta['html'].='</tr>';
	//~ $contad = $contad +1;
	//~ if ($contad === 10){
		//~ break;
	//~ }
				
	//~ }
	//~ $respuesta['html'].='</tbody></table>';
	//~ return $respuesta;
//~ }


//~ function modalAlbaranes($albaranes){
	//~ $contad = 0;
	//~ $respuesta=array('html'=>'');
	//~ $respuesta['html'] .= '<table class="table table-striped"><thead>';
	//~ $respuesta['html'] .= '<th>';
	//~ $respuesta['html'] .='<td>Número </td>';
	//~ $respuesta['html'] .='<td>Fecha</td>';
	//~ $respuesta['html'] .='<td>Total</td>';
	//~ $respuesta['html'] .='</th>';
	//~ $respuesta['html'] .= '</thead><tbody>';
	//~ foreach ($albaranes as $albaran){
	//~ $respuesta['html'] .= '<tr id="Fila_'.$contad.'" onmouseout="abandonFila('
	//~ .$contad.')" onmouseover="sobreFilaCraton('.$contad.')"  onclick="buscarDatosAlbaran('.$albaran['Numalbcli'].');">';
	//~ $respuesta['html'] .= '<td id="C'.$contad.'_Lin" ><input id="N_'.$contad.'" name="filaproducto" onfocusout="abandonFila('
	//~ .$contad.')" data-obj="idN" onfocus="sobreFila('.$contad.')" onkeydown="controlEventos(event)" type="image"  alt=""><span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';

	//~ $respuesta['html'].='<td>'.$albaran['Numalbcli'].'</td>';
	//~ $respuesta['html'].='<td>'.$albaran['Fecha'].'</td>';
	//~ $respuesta['html'].='<td>'.$albaran['total'].'</td>';
	//~ $respuesta['html'].='</tr>';
	//~ $contad = $contad +1;
	//~ if ($contad === 10){
		//~ break;
	//~ }
				
	//~ }
	//~ $respuesta['html'].='</tbody></table>';
	//~ return $respuesta;
//~ }

function modalAdjunto($adjuntos){
	$contad = 0;
	$respuesta=array('html'=>'');
	$respuesta['html'] .= '<table class="table table-striped"><thead>';
	$respuesta['html'] .= '<th>';
	$respuesta['html'] .='<td>Número </td>';
	$respuesta['html'] .='<td>Fecha</td>';
	$respuesta['html'] .='<td>Total</td>';
	$respuesta['html'] .='</th>';
	$respuesta['html'] .= '</thead><tbody>';
	foreach ($adjuntos as $adjunto){
		if ($adjunto['Numalbcli']){
			$num=$adjunto['Numalbcli'];
			}else{
				$num=$adjunto['Numpedcli'];
				}
		if($adjunto['Fecha']){
			$fecha=$adjunto['Fecha'];
			}else{
				$fecha=$adjunto['FechaPedido'];
				}
	$respuesta['html'] .= '<tr id="Fila_'.$contad.'" onmouseout="abandonFila('
	.$contad.')" onmouseover="sobreFilaCraton('.$contad.')"  onclick="buscarDatosAlbaran('.$num.');">';
	$respuesta['html'] .= '<td id="C'.$contad.'_Lin" ><input id="N_'.$contad.'" name="filaproducto" onfocusout="abandonFila('
	.$contad.')" data-obj="idN" onfocus="sobreFila('.$contad.')" onkeydown="controlEventos(event)" type="image"  alt=""><span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';

	$respuesta['html'].='<td>'.$num.'</td>';
	$respuesta['html'].='<td>'.$fecha.'</td>';
	$respuesta['html'].='<td>'.$albaran['total'].'</td>';
	$respuesta['html'].='</tr>';
	$contad = $contad +1;
	if ($contad === 10){
		break;
	}
				
	}
	$respuesta['html'].='</tbody></table>';
	return $respuesta;
	}



function modificarArrayPedidos($pedidos, $BDTpv){
	$respuesta=array();
		$i=1;
	foreach ($pedidos as $pedido){
			$datosPedido=$BDTpv->query('SELECT * FROM pedclit WHERE id= '.$pedido['idPedido'] );
			while ($fila = $datosPedido->fetch_assoc()) {
				$ped[] = $fila;
			}
			if ($pedido['numPedido']){
				$numPedido=$pedido['numPedido'];
			}else{
				$numPedido=$pedido['Numpedcli'];
			}
			$res['Numpedcli']=$numPedido;
			$res['idPedido']=$ped['id'];
			$res['fecha']=$ped['FechaPedido'];
			$res['idPedCli']=$ped['id'];
			$res['total']=$ped['total'];
			$res['estado']="Activo";
			$res['nfila']=$i;
			array_push($respuesta,$res);
		$i++;
	}
	return $respuesta;
}

function modificarArrayAlbaranes($albaranes, $BDTpv){
	$respuesta=array();
	$i=1;
	foreach ($albaranes as $albaran){
			$datosPedido=$BDTpv->query('SELECT * FROM albclit WHERE id= '.$albaran['idAlbaran'] );
			while ($fila = $datosPedido->fetch_assoc()) {
				$ped = $fila;
			}
			$res['Numalbcli']=$ped['Numalbcli'];
			$res['fecha']=$ped['Fecha'];
			$res['idAlbaran']=$ped['id'];
			$res['total']=$ped['total'];
			$res['estado']="Activo";
			$res['nfila']=$i;
			array_push($respuesta,$res);
		$i++;
	}
	return $respuesta;
}


function htmlFormasVenci($formaVenci, $BDTpv){
	
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

function htmlVencimiento($nuevafecha, $BDTpv){
	$vencimiento=new TiposVencimientos($BDTpv);
		$html='<input type="date" name="fechaVenci" id="fechaVenci" data-obj= "fechaVenci" onBlur="selectFormas()" value='.$nuevafecha.' >';
		$respuesta['html']=$html;
		return $respuesta;
    
}
function fechaVencimiento($fecha, $BDTpv){
	if ($fecha>0){
	$vencimiento=new TiposVencimientos($BDTpv);
	$principal=$vencimiento->datosPrincipal($fecha);
	$dias=$principal['dias'];
	$string=" +".$dias." day ";
	$fecha = date('Y-m-j');
	$nuevafecha = strtotime($fecha.$string);
	$nuevafecha = date ( 'Y-m-j' , $nuevafecha );
	}else{
		 $nuevafecha = date('Y-m-j');
	}
	return $nuevafecha;
	
}

function htmlImporteFactura($datos){
	$respuesta['html'].='<tr>';
	$respuesta['html'].='<td>'.$datos['importe'].'</td>';
	$respuesta['html'].='<td>'.$datos['fecha'].'</td>';
	$respuesta['html'].='<td>'.$datos['forma'].'</td>';
	$respuesta['html'].='<td>'.$datos['referencia'].'</td>';
	$respuesta['html'].='</tr>';
	return $respuesta;
	
}
function montarHTMLimprimir($id , $BDTpv, $dedonde, $tienda){
	$Ccliente=new Cliente($BDTpv);
	if ($dedonde=='pedido'){
		$Cpedido=new PedidosVentas($BDTpv);
		$datos=$Cpedido->datosPedidos($id);
		$idCliente=$datos['idCliente'];
		$datosCliente=$Ccliente->DatosClientePorId($idCliente);
		$textoCabecera="Pedido de cliente";
		$numero=$datos['Numpedcli'];
		$fecha=$datos['FechaPedido'];
		$productos=$Cpedido->ProductosPedidos($id);
		$productosMod=modificarArrayProductos($productos);
		$productos1=json_decode(json_encode($productosMod));
		$Datostotales = recalculoTotales($productos1);
	}
	if ($dedonde =='albaran'){
		$Calbaran=new AlbaranesVentas($BDTpv);
		$datos=$Calbaran->datosAlbaran($id);
		$idCliente=$datos['idCliente'];
		$datosCliente=$Ccliente->DatosClientePorId($idCliente);
		$textoCabecera="Albarán de Cliente";
		$numero=$datos['Numalbcli'];
		$fecha=$datos['Fecha'];
		$productos=$Calbaran->ProductosAlbaran($id);
		$productosMod=modificarArrayProductos($productos);
		$productos1=json_decode(json_encode($productosMod));
		$Datostotales = recalculoTotales($productos1);
		
	}
	if ($dedonde=='factura'){
		$Cfaccli=new FacturasVentas($BDTpv);
		$datos=$Cfaccli->datosFactura($id);
		$idCliente=$datos['idCliente'];
		$datosCliente=$Ccliente->DatosClientePorId($idCliente);
		$textoCabecera="Factura de Cliente";
		$numero=$datos['Numfaccli'];
		$fecha=$datos['Fecha'];
		$productos=$Cfaccli->ProductosFactura($id);
		$productosMod=modificarArrayProductos($productos);
		$productos1=json_decode(json_encode($productosMod));
		$Datostotales = recalculoTotales($productos1);
	}
		$imprimir['cabecera'].='<table>';
		$imprimir['cabecera'].='<tr>';
		$imprimir['cabecera'].='<td>'.$tienda['NombreComercial'].'</td>';
		$imprimir['cabecera'].='<td>'.$textoCabecera.'</td>';
		$imprimir['cabecera'].='</tr>';
		$imprimir['cabecera'].='<tr>';
		$imprimir['cabecera'].='<td>'.$tienda['direccion'].'</td>';
		$imprimir['cabecera'].='<td>Nª'.$numero.'</td>';
		$imprimir['cabecera'].='</tr>';
		$imprimir['cabecera'].='<tr>';
		$imprimir['cabecera'].='<td> NIF: '.$tienda['nif'].'</td>';
		$imprimir['cabecera'].='<td>Fecha: '.$fecha.'</td>';
		$imprimir['cabecera'].='</tr>';
		$imprimir['cabecera'].='<tr>';
		$imprimir['cabecera'].='<td> Teléfono: '.$tienda['telefono'].'</td>';
		$imprimir['cabecera'].='<td></td>';
		$imprimir['cabecera'].='</tr>';
		$imprimir['cabecera'].='</table>';
		
		$imprimir['cabecera'].='<hr/><hr/>';
		$imprimir['cabecera'].='<p>DATOS DEL CLIENTE: '.$datosCliente['Clientes'].'</p>';
		
		$imprimir['cabecera'].='<table>';
		$imprimir['cabecera'].='<tr>';
		$imprimir['cabecera'].='<td>'.$datosCliente['Nombre'].'</td>';
		$imprimir['cabecera'].='<td>NIF: '.$datosCliente['nif'].'</td>';
		$imprimir['cabecera'].='</tr>';
		$imprimir['cabecera'].='<tr>';
		$imprimir['cabecera'].='<td>'.$datosCliente['direccion'].'</td>';
		$imprimir['cabecera'].='<td>CODPOSTAL: '.$datosCliente['codpostal'].'</td>';
		$imprimir['cabecera'].='</tr>';
		$imprimir['cabecera'].='<tr>';
		$imprimir['cabecera'].='<td>'.$datosCliente['razonsocial'].'</td>';
		$imprimir['cabecera'].='<td>TELÉFONO: '.$datosCliente['telefono'].'</td>';
		$imprimir['cabecera'].='</tr>';
		$imprimir['cabecera'].='</table>';
		$imprimie['cabecera'].='</br></br>';
			$imprimir['cabecera'].='<hr/><hr/>';
			$imprimie['cabecera'].='</br></br>';
		$imprimir['cabecera'].='<table>';
		$imprimir['cabecera'].='<tr>';
		if ($dedonde=="albaran"){
			$imprimir['cabecera'].='<td WIDTH="5%" align="center">PED</td>';
		}
		if ($dedonde=="factura"){
			$imprimir['cabecera'].='<td WIDTH="5%" align="center">ALB</td>';
		}
		$imprimir['cabecera'].='<td WIDTH="15%" >REF</td>';
		$imprimir['cabecera'].='<td WIDTH="40%">DESCRIPCIÓN</td>';
		$imprimir['cabecera'].='<td WIDTH="7%" >CANT</td>';
		$imprimir['cabecera'].='<td WIDTH="10%" >PRECIO</td>';
		$imprimir['cabecera'].='<td WIDTH="7%" >IVA</td>';
		$imprimir['cabecera'].='<td WIDTH="20%" >IMPORTE</td>';
		$imprimir['cabecera'].='</tr>';
		$imprimir['cabecera'].='</table>';
		$imprimir['html'].='<table>';
		foreach ($productos as $producto){
			$imprimir['html'].='<tr>';
			if ( $dedonde=="albaran"){
				if ($producto['NumpedCli'] ){
					$numPed=$producto['NumpedCli'];
				}else{
					$numPed="";
				}
				$imprimir['html'].='<td WIDTH="5%">'.$numPed.'</td>';
			}
			if ($dedonde=="factura"){
				if ($producto['NumalbCli']){
					$numAlb=$producto['NumalbCli'];
				}else{
					$numAlb="";
				}
				$imprimir['html'].='<td WIDTH="5%">'.$numAlb.'</td>';
			}
			$imprimir['html'].='<td WIDTH="15%" >'.$producto['cref'].'</td>';
			$imprimir['html'].='<td WIDTH="40%" >'.$producto['cdetalle'].'</td>';
			$imprimir['html'].='<td WIDTH="7%" aling="center">'.number_format($producto['ncant'],0).'</td>';
			$imprimir['html'].='<td WIDTH="10%" >'.number_format($producto['precioCiva'],2).'</td>';
			$imprimir['html'].='<td WIDTH="7%" >'.$producto['iva'].'</td>';
			$importe = $producto['precioCiva']*$producto['ncant'];
			$importe = number_format($importe,2);
			$imprimir['html'].='<td WIDTH="20%" align="center">'.$importe.'</td>';
			$imprimir['html'].='</tr>';
		}
		$imprimir['html'].='</table>';
		$imprimir['html'].='<hr/><hr/>';
			if (isset($Datostotales)){
		foreach ($Datostotales['desglose'] as  $iva => $basesYivas){
				switch ($iva){
					case 4 :
						$base4 = $basesYivas['base'];
						$iva4 = $basesYivas['iva'];
					break;
					case 10 :
						$base10 = $basesYivas['base'];
						$iva10 = $basesYivas['iva'];
					break;
					case 21 :
						$base21 = $basesYivas['base'];
						$iva21 = $basesYivas['iva'];
					break;
				}
			}
	}
	$imprimir['html'] .='<table>';
	$imprimir['html'] .='
			<tr>
				<th>Tipo</th>
				<th>Base</th>
				<th>IVA</th>
			</tr>
		';
		if (isset ($base4)){
		$imprimir['html'].='<tr>';
		$imprimir['html'].='<td>';
		$imprimir['html'].= (isset($base4) ? " 4%" : '');
		$imprimir['html'].='</td>';
		
		$imprimir['html'].='<td>';
		$imprimir['html'].= (isset($base4) ? $base4 : '');
		$imprimir['html'].='</td>';
	
		$imprimir['html'].='<td>';
		$imprimir['html'].= (isset($iva4) ? $iva4 : '');
		$imprimir['html'].='</td>';
		$imprimir['html'].='</tr>';
	}
	if (isset ($base10)){
		
		$imprimir['html'].='<tr>';
		$imprimir['html'].='<td>';
		$imprimir['html'].= (isset($base10) ? "10%" : '');
		$imprimir['html'].='</td>';
		
		$imprimir['html'].='<td>';
		$imprimir['html'].= (isset($base10) ? $base10 : '');
		$imprimir['html'].='</td>';
		
		$imprimir['html'].='<td>';
		$imprimir['html'].= (isset($iva10) ? $iva10 : '');
		$imprimir['html'].='</td>';
		$imprimir['html'].='</tr>';
	}
	if (isset ($base21)){
	
		$imprimir['html'].='<tr>';
		$imprimir['html'].='<td>';
		$imprimir['html'].= (isset($base21) ? "21%" : '');
		$imprimir['html'].='</td>';
		
		$imprimir['html'].='<td>';
		$imprimir['html'].= (isset($base21) ? $base21 : '');
		$imprimir['html'].='</td>';
	
		$imprimir['html'].='<td>';
		$imprimir['html'].= (isset($iva21) ? $iva21 : '');
		$imprimir['html'].='</td>';
		$imprimir['html'].='</tr>';
	}
		$imprimir['html'] .='</table>';
	$imprimir['html'] .='<p align="right"> TOTAL: ';
	$imprimir['html'] .=(isset($Datostotales['total']) ? $Datostotales['total'] : '');
	$imprimir['html'] .='</p>';
		return $imprimir;
}
function htmlTotales($Datostotales){
	$htmlIvas['html'] = '';
		foreach ($Datostotales['desglose'] as  $key => $basesYivas){
			$key = intval($key);
			$htmlIvas['html'].='<tr id="line'.$key.'">';
			$htmlIvas['html'].='<td id="tipo'.$key.'"> '.$key.'%</td>';
			$htmlIvas['html'].='<td id="base'.$key.'"> '.$basesYivas['base'].'</td>';
			$htmlIvas['html'].='<td id="iva'.$key.'">'.$basesYivas['iva'].'</td>';
			$htmlIvas['html'].='</tr>';
		}
	return $htmlIvas;
}
?>
