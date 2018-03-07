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
function BusquedaClientes($busqueda,$BDTpv,$tabla, $idcaja){
	// @ Objetivo es buscar los clientes 
	// @ Parametros
	// 	$busqueda --> Lo que vamos a buscar
	// 	$BDTpv--> Conexion
	//	$tabla--> tabla donde buscar.
	// Buscamos en los tres campos... Nombre, razon social, nif
	$resultado=array();
	$buscar1= 'Nombre';
	$buscar2='razonsocial';
	$buscar3='nif';
	$resultado['caja']=$idcaja;
	if ($idcaja==='id_cliente' || $idcaja ==='id_clienteAl'){
		$sql='SELECT idClientes, nombre, razonsocial, nif FROM '.$tabla.' WHERE idClientes='.$busqueda; 
	}else{
	$sql = 'SELECT idClientes, nombre, razonsocial, nif  FROM '.$tabla.' WHERE '.$buscar1.' LIKE "%'.$busqueda.'%" OR '
			.$buscar2.' LIKE "%'.$busqueda.'%" OR '.$buscar3.' LIKE "%'.$busqueda.'%"';
		}
	$res = $BDTpv->query($sql);
			//~ $resultado['consulta'] = $sql;
$resultado['Nitems']= $res->num_rows;
	 //compruebo error en consulta
	if (mysqli_error($BDTpv)){
		$resultado['consulta'] = $sql;
		$resultado['error'] = $BDTpv->error_list;
		return $resultado;
	} 
	$resultado['consulta']=$sql;
	$arr = array();
	$i = 0;
	//fetch_assoc es un boleano..
	while ($fila = $res->fetch_assoc()) {
		$arr[$i] = $fila;
		$resultado['datos'][0] = $fila;
		$resultado['datos'] = $arr;
		$i++;
	}
	return $resultado;
}
function htmlClientes($busqueda,$dedonde, $idcaja, $clientes = array()){
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
			$razonsocial_nombre=$cliente['nombre'].' - '.$cliente['razonsocial'];
			$datos = 	"'".$cliente['idClientes']."','".addslashes(htmlentities($razonsocial_nombre,ENT_COMPAT))."'";
			$resultado['html'] .= '<tr id="Fila_'.$contad.'" onmouseout="abandonFila('.$contad
			.')" onmouseover="sobreFilaCraton('.$contad.')" onclick="escribirClienteSeleccionado('.$datos.",'".$dedonde."'".');">';
		
			$resultado['html'] .= '<td id="C'.$contad.'_Lin" >';
			$resultado['html'] .= '<input id="N_'.$contad.'" name="filacliente" onfocusout="abandonFila('
						.$contad.')" data-obj="idN" onkeydown="controlEventos(event)" onfocus="sobreFila('.$contad.')"   type="image"  alt="">';
			$resultado['html'] .= '<span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';
			$resultado['html'] .= '<td>'.htmlspecialchars($cliente['nombre'],ENT_QUOTES).'</td>';
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
function  htmlClientesCajas($clientes){
	$resultado = array();
	$cliente=$clientes[0]['nombre'];
	$resultado['script']="<script type='text/javascript'>
							var cliente=".$cliente.";
							document.getElementById('Cliente').innerHTML=cliente;
						</script>";
	return $resultado['script'];
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
						.number_format($producto['pvpCiva'],2).",".$producto['idArticulo'];
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
function htmlLineaTicket($producto,$num_item,$CONF_campoPeso){
	//@ Objetivo:
	// Obtener html de una linea de productos.
	//@ Parametros:
	// $product -> Debería ser un objeto, pero por javascritp viene como un array por lo comprobamos y convertimos.
	// Variables que vamos utilizar:
	$classtr = '' ; // para clase en tr
	$estadoInput = '' ; // estado input cantidad.
	
	if(!is_object($producto)) {
		// Comprobamos si product no es objeto lo convertimos.
		$product = (object)$producto;
		
	} else {
		$product = $producto;
	}
	
	// Si estado es eliminado tenemos añadir class y disabled input
	if ($product->estado !=='Activo'){
		$classtr = ' class="tachado" ';
		$estadoInput = 'disabled';
			$funcOnclick = ' retornarFila('.$num_item.');';
		$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-export"></span></a></td>';
	} else {
			$funcOnclick = ' eliminarFila('.$num_item.');';
		$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-trash"></span></a></td>';
	}
	$nuevaFila = '<tr id="Row'.($product->nfila).'" '.$classtr.'>';
	$nuevaFila .= '<td class="linea">'.$product->nfila.'</td>'; //num linea
	$nuevaFila .= '<td class="codbarras">'.$product->ccodebar.'</td>';
	$nuevaFila .= '<td class="referencia">'.$product->cref.'</td>';
	$nuevaFila .= '<td class="detalle">'.$product->cdetalle.'</td>';
	$nuevaFila .= '<td><input id="Unidad_Fila_'.$product->nfila.'" type="text" data-obj="Unidad_Fila" pattern="[.0-9]+" name="unidad" placeholder="unidad" size="4"  value="'.$product->unidad.'"  '.$estadoInput.' onkeydown="controlEventos(event,'."'Unidad_Fila_".$product->nfila."'".')" onBlur="controlEventos(event)"></td>';
	//si en config peso=si, mostramos columna peso
	if ($CONF_campoPeso === 'si'){
		$nuevaFila .= '<td><input id="C'.$product->nfila.'_Kilo" type="text" name="kilo" size="3" placeholder="peso" value="" ></td>'; //cant/kilo
	} else {
		$nuevaFila .= '<td style="display:none"><input id="C'.$product->nfila.'_Kilo" type="text" name="kilo" size="3" placeholder="peso" value="" ></td>'; 
	}
	$nuevaFila .= '<td class="pvp">'.$product->pvpconiva.'</td>';
	$nuevaFila .= '<td class="tipoiva">'.$product->ctipoiva.'%</td>';
	// Creamos importe --> 
	$importe = $product->pvpconiva*$product->unidad;
	$importe = number_format($importe,2);
	$nuevaFila .= '<td id="N'.$product->nfila.'_Importe" class="importe" >'.$importe.'</td>'; //importe 
	// Ahota tengo que controlar el estado del producto,para mostrar uno u otro
	$nuevaFila .= $btnELiminar_Retornar;

	$nuevaFila .='</tr>';
	return $nuevaFila;
}

function htmlLineaPedido($producto,$num_item,$CONF_campoPeso, $disable, $style){
	$classtr = '' ; // para clase en tr
	$estadoInput = '' ; // estado input cantidad.
	
	if(!is_object($producto)) {
		// Comprobamos si product no es objeto lo convertimos.
		$product = (object)$producto;
		
	} else {
		$product = $producto;
	}
	
	// Si estado es eliminado tenemos añadir class y disabled input
	if ($product->estado !=='Activo'){
		$classtr = ' class="tachado" ';
		$estadoInput = 'disabled';
			$funcOnclick = ' retornarFila('.$num_item.');';
		$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-export" style='.$style.'></span></a></td>';
	} else {
			$funcOnclick = ' eliminarFila('.$num_item.');';
		$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-trash" style='.$style.'></span></a></td>';
	}
	$nuevaFila = '<tr id="Row'.($product->nfila).'" '.$classtr.'>';
	$nuevaFila .= '<td class="linea">'.$product->nfila.'</td>';
	$nuevaFila .= '<td class="idArticulo">'.$product->idArticulo.'</td>';
	$nuevaFila .= '<td class="referencia">'.$product->crefTienda.'</td>';
	$nuevaFila .= '<td class="codbarras">'.$product->codBarras.'</td>';
	$nuevaFila .= '<td class="detalle">'.$product->articulo_name.'</td>';
	$nuevaFila .= '<td><input id="Unidad_Fila_'.$product->nfila.'" type="text" data-obj="Unidad_Fila" pattern="[.0-9]+" name="unidad" placeholder="unidad" size="4"  value="'.$product->cant.'"  '.$estadoInput.' onkeydown="controlEventos(event,'."'Unidad_Fila_".$product->nfila."'".')" onBlur="controlEventos(event)" '.$disable.'></td>';
	$nuevaFila .= '<td class="pvp">'.$product->pvpCiva.'</td>';
	$nuevaFila .= '<td class="tipoiva">'.$product->iva.'%</td>';
	$importe = $product->pvpCiva*$product->cant;
	$importe = number_format($importe,2);
	$nuevaFila .= '<td id="N'.$product->nfila.'_Importe" class="importe" >'.$importe.'</td>'; //importe 
	$nuevaFila .= $btnELiminar_Retornar;
	$nuevaFila .='</tr>';
	return $nuevaFila;
}
function recalculoTotales($productos) {
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
			$totalLinea = $product->cant * $product->pvpCiva;
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
		if ($product->estadoLinea === 'Activo'){
			$totalLinea = $product->ncant * $product->precioCiva;
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




function modificarArrayProductos($productos){
	$respuesta=array();
	foreach ($productos as $producto){
		$product['idArticulo']=$producto['idArticulo'];
		$product['crefTienda']=$producto['cref'];
		$product['articulo_name']=$producto['cdetalle'];
		$product['pvpCiva']=$producto['precioCiva'];
		$product['iva']=$producto['iva'];
		$product['codBarras']=$producto['ccodbar'];
		$product['nfila']=$producto['nfila'];
		$product['estado']=$producto['estadoLinea'];
		$product['cant']=number_format($producto['ncant'],0);
		$product['importe']=$producto['precioCiva'];
		$product['unidad']=$producto['nunidades'];
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
			if (isset ($producto['Numpedcli'])){
				if ($producto['Numpedcli']==0){
					$numeroPed="";
				}else{
					$numeroPed=$producto['Numpedcli'];
				}
			}else{
				if (isset ($producto['Numalbcli'])){
				 if ($producto['Numalbcli']>0){
					 $numeroPed=$producto['Numalbcli'];
				 }else{
					 $numeroPed="";
				 }
				}else{
					if ($producto['NumalbCli']>0){
						$numeroPed=$producto['NumalbCli'];
					}else{
						$numeroPed="";
					}
				}
				
				
			}
			
		 $respuesta['html'] .='<tr id="Row'.($producto['nfila']).'" '.$classtr.'>';
		 
		 $respuesta['html'] .='<td class="linea">'.$producto['nfila'].'</td>';
		 $respuesta['html'] .='<td>'.$numeroPed.'</td>';
		 $respuesta['html']	.= '<td class="idArticulo">'.$producto['idArticulo'].'</td>';
		 $respuesta['html'] .='<td class="referencia">'.$producto['cref'].'</td>';
		 $respuesta['html'] .='<td class="codbarras">'.$producto['ccodbar'].'</td>';
		 $respuesta['html'] .= '<td class="detalle">'.$producto['cdetalle'].'</td>';
		 $cant=number_format($producto['ncant'],0);
		 $respuesta['html'] .= '<td><input id="Unidad_Fila_'.$producto['nfila'].'" type="text" data-obj="Unidad_Fila" pattern="[.0-9]+" name="unidad" placeholder="unidad" size="4"  value="'.$cant.'"  '.$estadoInput.' onkeydown="controlEventos(event)" onBlur="controlEventos(event)"></td>';
		 $respuesta['html'] .='<td class="pvp">'.$producto['precioCiva'].'</td>';
		 $respuesta['html'] .= '<td class="tipoiva">'.$producto['iva'].'%</td>';
		 $importe = $producto['precioCiva']*$producto['ncant'];
		 $importe = number_format($importe,2);
		 $respuesta['html'] .='<td id="N'.$producto['nfila'].'_Importe" class="importe" >'.$importe.'</td>';
		 $respuesta['html'] .= $btnELiminar_Retornar;
		 $respuesta['html'] .='</tr>';
		 $respuesta['productos']=$producto;
	 return $respuesta;
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
			if ($albaran['estado']=="activo"){
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

function lineaPedidoAlbaran($pedido, $dedonde){
		$respuesta['html']="";
	if(isset($pedido)){
	if ($pedido['estado']){
			if ($pedido['Numpedcli']){
				$num=$pedido['Numpedcli'];
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
		$respuesta['html'] .='<td>'.$pedido['Numpedcli'].'</td>';
		$respuesta['html'] .='<td>'.$pedido['fecha'].'</td>';
		$respuesta['html'] .='<td>'.$pedido['total'].'</td>';
		$respuesta['html'].=$btnELiminar_Retornar;
		$respuesta['html'] .='</tr>';
	}
	return $respuesta;
}

function lineaAlbaranFactura($albaran, $dedonde){
	$respuesta['html']="";
	if(isset($albaran)){
			if ($albaran['estado']){
			if ($albaran['Numalbcli']){
				$num=$albaran['Numalbcli'];
			}
			if ($albaran['estado']=="activo"){
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
	return $respuesta;
}

function modalPedidos($pedidos){
	$contad = 0;
	$respuesta['html'] .= '<table class="table table-striped"><thead>';
	$respuesta['html'] .= '<th>';
	$respuesta['html'] .='<td>Número </td>';
	$respuesta['html'] .='<td>Fecha</td>';
	$respuesta['html'] .='<td>Total</td>';
	$respuesta['html'] .='</th>';
	$respuesta['html'] .= '</thead><tbody>';
	foreach ($pedidos as $pedido){
	$respuesta['html'] .= '<tr id="Fila_'.$contad.'" onmouseout="abandonFila('
	.$contad.')" onmouseover="sobreFilaCraton('.$contad.')"  onclick="buscarDatosPedido('.$pedido['Numpedcli'].');">';
	$respuesta['html'] .= '<td id="C'.$contad.'_Lin" ><input id="N_'.$contad.'" name="filaproducto" onfocusout="abandonFila('
	.$contad.')" data-obj="idN" onfocus="sobreFila('.$contad.')" onkeydown="controlEventos(event)" type="image"  alt=""><span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';

	$respuesta['html'].='<td>'.$pedido['Numpedcli'].'</td>';
	$respuesta['html'].='<td>'.$pedido['FechaPedido'].'</td>';
	$respuesta['html'].='<td>'.$pedido['total'].'</td>';
	$respuesta['html'].='</tr>';
	$contad = $contad +1;
	if ($contad === 10){
		break;
	}
				
	}
	$respuesta['html'].='</tbody></table>';
	return $respuesta;
}


function modalAlbaranes($albaranes){
	$contad = 0;
	$respuesta=array('html'=>'');
	$respuesta['html'] .= '<table class="table table-striped"><thead>';
	$respuesta['html'] .= '<th>';
	$respuesta['html'] .='<td>Número </td>';
	$respuesta['html'] .='<td>Fecha</td>';
	$respuesta['html'] .='<td>Total</td>';
	$respuesta['html'] .='</th>';
	$respuesta['html'] .= '</thead><tbody>';
	foreach ($albaranes as $albaran){
	$respuesta['html'] .= '<tr id="Fila_'.$contad.'" onmouseout="abandonFila('
	.$contad.')" onmouseover="sobreFilaCraton('.$contad.')"  onclick="buscarDatosAlbaran('.$albaran['Numalbcli'].');">';
	$respuesta['html'] .= '<td id="C'.$contad.'_Lin" ><input id="N_'.$contad.'" name="filaproducto" onfocusout="abandonFila('
	.$contad.')" data-obj="idN" onfocus="sobreFila('.$contad.')" onkeydown="controlEventos(event)" type="image"  alt=""><span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';

	$respuesta['html'].='<td>'.$albaran['Numalbcli'].'</td>';
	$respuesta['html'].='<td>'.$albaran['Fecha'].'</td>';
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
			$res['idPedido']=$ped[0]['id'];
			$res['fecha']=$ped[0]['FechaPedido'];
			$res['idPedCli']=$ped[0]['id'];
			$res['total']=$ped[0]['total'];
			$res['estado']="activo";
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
				$ped[] = $fila;
			}
			$res['Numalbcli']=$ped[0]['Numalbcli'];
			$res['fecha']=$ped[0]['Fecha'];
			$res['idAlbaran']=$ped[0]['id'];
			//$res['idalbCli']=$ped[0]['idCliente'];
			$res['total']=$ped[0]['total'];
			$res['estado']="activo";
			$res['nfila']=$i;
			array_push($respuesta,$res);
		$i++;
	}
	return $respuesta;
}


function htmlFormasVenci($formaVenci, $BDTpv){
	
	$formasPago=new FormasPago($BDTpv);
	$html="<select name='formaVenci' id='formaVenci' onChange='selectFormas()'>";
	
	$principal=$formasPago->datosPrincipal($formaVenci);
	$html.='<option value="'.$principal['id'].'">'.$principal['descripcion'].'</option>';
	$otras=$formasPago->formadePagoSinPrincipal($formaVenci);
	foreach ($otras as $otra){
		$html.='<option value= "'.$otra['id'].'">'.$otra['descripcion'].'</option>';
	}
	$html.='</select>';
	
	
	$respuesta['formas']=$formaVenci;
	$respuesta['html']=$html;
	return $respuesta;
}

function htmlVencimiento($nuevafecha, $BDTpv){
	$vencimiento=new TiposVencimientos($BDTpv);
	//~ if ($venci>0){
		//~ $principal=$vencimiento->datosPrincipal($venci);
		//~ $dias=$principal['dias'];
		//~ $string=" +".$dias." day ";
		//~ $fecha = date('Y-m-j');
		//~ $nuevafecha = strtotime($fecha.$string);
		//~ $nuevafecha = date ( 'Y-m-j' , $nuevafecha );
	//~ }else{
		//~ $nuevafecha = date('Y-m-j');
	//~ }
		
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

function htmlImporteFactura($importe, $fecha, $pendiente){
	$respuesta['html'].='<tr>';
	$respuesta['html'].='<td>'.$importe.'</td>';
	$respuesta['html'].='<td>'.$fecha.'</td>';
	$respuesta['html'].='<td>'.$pendiente.'</td>';
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
		$productos1=json_decode(json_encode($productos));
		$Datostotales = recalculoTotalesAl($productos1);
		
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
		$productos1=json_decode(json_encode($productos));
		$Datostotales = recalculoTotalesAl($productos1);
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
?>
