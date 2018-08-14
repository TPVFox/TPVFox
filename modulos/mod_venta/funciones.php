<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvOlalla de Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero ,
 * @Descripcion	Funciones en php para modulo TPV
 * */
    include_once './../../inicial.php';
    include_once $URLCom.'/clases/FormasPago.php';
    include_once $URLCom.'/clases/TiposVencimiento.php';
 
function BuscarProductos($id_input,$campoAbuscar,$idcaja, $busqueda,$BDTpv, $idCliente) {
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
	$busqueda=trim($busqueda);
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
		$sql1='SELECT a.`idArticulo` , a.`articulo_name` , ac.`codBarras` , ap.pvpCiva, ap.pvpSiva, at.crefTienda , a.`iva` 
			FROM `articulos` AS a LEFT JOIN `articulosCodigoBarras` AS ac 
			ON a.idArticulo = ac.idArticulo LEFT JOIN `articulosClientes` AS ap 
			ON a.idArticulo = ap.idArticulo  LEFT JOIN `articulosTiendas` 
			AS at ON a.idArticulo = at.idArticulo AND at.idTienda =1 WHERE ap.idClientes='.$idCliente.' and ap.estado=1 and 
			'.$buscar.' group by a.idArticulo LIMIT 0 , 30';
		$res = $BDTpv->query($sql1);
		$resultado['Nitems']= $res->num_rows;
		if($resultado['Nitems']==0){
		$sql = 'SELECT a.`idArticulo` , a.`articulo_name` , ac.`codBarras` , ap.pvpCiva, ap.pvpSiva, at.crefTienda , a.`iva` '
			.' FROM `articulos` AS a LEFT JOIN `articulosCodigoBarras` AS ac '
			.' ON a.idArticulo = ac.idArticulo LEFT JOIN `articulosPrecios` AS ap '
			.' ON a.idArticulo = ap.idArticulo AND ap.idTienda =1 LEFT JOIN `articulosTiendas` '
			.' AS at ON a.idArticulo = at.idArticulo AND at.idTienda =1 WHERE '.$buscar.' group by a.idArticulo LIMIT 0 , 30 ';
		$resultado['sql'] = $sql;
		$res = $BDTpv->query($sql);
		$resultado['Nitems']= $res->num_rows;
		}
		
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
			$resultado['html'] .= '<tr id="Fila_'.$contad.'" class="FilaModal" '
								.'onclick="escribirClienteSeleccionado('.$cliente['idClientes'].', '."'".$cliente['Nombre']."'".', '."'".$dedonde."'".');">';
		
			$resultado['html'] .= '<td id="C'.$contad.'_Lin" >';
			$resultado['html'] .= '<input id="N_'.$contad.'" name="filacliente" data-obj="idN"'
								.'onkeydown="controlEventos(event)" type="image" value='.$cliente['idClientes'].' alt="">';
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

function htmlProductos($productos,$id_input,$campoAbuscar,$busqueda, $dedonde, $BDTpv,$idCliente){
	// @ Objetivo 
	// Obtener listado de produtos despues de busqueda.
	//~ $Cprod=new Producto($BDTpv);
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
			$pvpCiva=$producto['pvpCiva'];
			//comprobarTarifa=$Cprod->productosClientes($idCliente, $producto['idArticulo']);
			$sql='select * from articulosClientes where idClientes='.$idCliente.' and 
			idArticulo='.$producto['idArticulo'].' and estado=1';
			$res = $BDTpv->query($sql);
			$bandera=0;
			$resultado['Nitems']= $res->num_rows;
			if($resultado['Nitems']>0){
				if ($fila = $res->fetch_assoc()) {
					$pvpCiva=$fila['pvpCiva'];
					$bandera=1;
				}
				
			}
			$datos = 	"'".$id_input."',".
						"'".addslashes(htmlspecialchars($producto['crefTienda'],ENT_COMPAT))."','"
						.addslashes(htmlentities($producto['articulo_name'],ENT_COMPAT))."','"
						.number_format($producto['iva'],2)."','".$producto['codBarras']."',"
						.number_format($pvpCiva,2).",".$producto['idArticulo'].
						" , '".$dedonde."'";
			$resultado['html'] .= '<tr id="Fila_'.$contad.'" data-obj= "idN" class="FilaModal"'
								.'onclick="buscarProductos('."'".'idArticulo'."'".', '."'".'a.idArticulo'."'".', '."'".'idArticulo'.
								"'".', '.$producto['idArticulo'].', '."'".$dedonde."'".');">'
								.'<td id="C'.$contad.'_Lin" >'
								.'<input id="N_'.$contad.'" name="filaproducto" data-obj="idN"'
								.' onkeydown="controlEventos(event)" type="image" value='.$producto['idArticulo'].' alt="">'
								.'<span  class="glyphicon glyphicon-plus-sign agregar"></span></td>'
								.'<td>'.htmlspecialchars($producto['crefTienda'], ENT_QUOTES).'</td>'
								.'<td>'.htmlspecialchars($producto['articulo_name'], ENT_QUOTES).'</td>'
								.'<td>'.number_format($pvpCiva,2).'</td>';
								if($bandera==1){
									$resultado['html'] .='<td><strike>'.number_format($producto['pvpCiva'],2).'</strike></td>';
								}else{
									$resultado['html'] .='<td></td>';
								}
			$resultado['html'] .='</tr>';
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
		$sinIva=0;
		$product['idArticulo']=$producto['idArticulo'];
		$product['cref']=$producto['cref'];
		$product['cdetalle']=$producto['cdetalle'];
		$product['precioCiva']=$producto['precioCiva'];
		if(isset($producto['pvpSiva'])){
			$sinIva=number_format($producto['pvpSiva'],2);
		}else{
			$iva=$producto['iva']/100;
			$op1=$producto['precioCiva']*$iva;
			$sinIva=$producto['precioCiva']-$op1;
			$sinIva=number_format($sinIva,2);
		}
		$product['pvpSiva']=$sinIva;
		$product['iva']=$producto['iva'];
		$product['ccodbar']=$producto['ccodbar'];
		$product['nfila']=$producto['nfila'];
		$product['estadoLinea']=$producto['estadoLinea'];
		$product['ncant']=number_format($producto['ncant'],0);
		$product['nunidades']=$producto['nunidades'];
		if(isset($producto['NumalbCli'])){
			$product['NumalbCli']=$producto['NumalbCli'];
		}
		if(isset($producto['NumpedCli'])){
			$product['NumpedCli']=$producto['NumpedCli'];
		}
		if(isset($producto['Numalbcli'])){
			$product['Numalbcli']=$producto['Numalbcli'];
		}
		if(isset($producto['Numpedcli'])){
			$product['Numpedcli']=$producto['Numpedcli'];
		}
		$product['importe']=$sinIva*$producto['nunidades'];
		array_push($respuesta,$product);
		
	}
	return $respuesta;
}

function htmlLineaPedidoAlbaran($productos, $dedonde){
	$codBarras="";
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
				if(isset($producto['NumpedCli'])){
					if ($producto['NumpedCli']>0){
						$numeroPed=$producto['NumpedCli'];
					}
				}else if (isset($producto['Numpedcli'])){
						$numeroPed=$producto['Numpedcli'];
				}	
				
			}
			if ($dedonde=="factura"){
				if(isset($producto['Numalbcli'])){
					if ($producto['Numalbcli']>0){
					$numeroPed=$producto['Numalbcli'];
					}
				}else{
					if(isset($producto['NumalbCli'])){
						$numeroPed=$producto['NumalbCli'];
					}
					if(isset($producto['numalbcli'])){
						$numeroPed=$producto['numalbcli'];
					}
				}
				
			}
			if (isset($producto['ccodbar'])){
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
		 $cant=number_format($producto['nunidades'],2);
		 $respuesta['html'] .= '<td><input class="unidad" id="Unidad_Fila_'.$producto['nfila'].'" type="text" data-obj="Unidad_Fila" pattern="[-+]?[0-9]*[.]?[0-9]+" name="unidad" placeholder="unidad" size="3"  value="'.$cant.'"  '.$estadoInput.' onkeydown="controlEventos(event)" onBlur="controlEventos(event)"></td>';
		 $respuesta['html'] .='<td class="pvp">'.$producto['precioCiva'].'</td>';
		 $respuesta['html'] .='<td class="psi">'.$producto['pvpSiva'].'</td>';
		 $respuesta['html'] .= '<td class="tipoiva">'.$producto['iva'].'%</td>';
		 $importe = $producto['pvpSiva']*$producto['nunidades'];
		 $importe = number_format($importe,2);
		 $respuesta['html'] .='<td id="N'.$producto['nfila'].'_Importe" class="importe" >'.$importe.'</td>';
		 $respuesta['html'] .= $btnELiminar_Retornar;
		 $respuesta['html'] .='</tr>';
	 return $respuesta['html'];
}


function htmlPedidoAlbaran($pedido, $dedonde){
	$respuesta=array();
	$respuesta['html']='';
	if(isset($pedido)){
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
			$respuesta['html'] .='<tr id="lineaP'.($pedido['nfila']).'" '.$classtr.'>';
			$respuesta['html'] .='<td>'.$pedido['Numpedcli'].'</td>';
			$respuesta['html'] .='<td>'.$pedido['fecha'].'</td>';
			$respuesta['html'] .='<td>'.$pedido['total'].'</td>';
			$respuesta['html'].=$btnELiminar_Retornar;
			$respuesta['html'] .='</tr>';
	}
	return $respuesta;
}


function htmlAlbaranFactura($albaranes, $dedonde){
	$respuesta=array();
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
		$num=$adjunto['Numpedcli'];
        $onclick="buscarDatosPedido";
        $fecha=$adjunto['FechaPedido'];
		if (isset($adjunto['Numalbcli'])){
			$onclick="buscarDatosAlbaran";
			$num=$adjunto['Numalbcli'];
		}
		if(isset($adjunto['Fecha'])){
			$fecha=$adjunto['Fecha'];
		}
		$respuesta['html'] .= '<tr id="Fila_'.$contad.'" class="FilaModal" onclick="'.$onclick.'('.$num.');">'
							.'<td id="C'.$contad.'_Lin" >'
							.'<input id="N_'.$contad.'" name="filapedido" data-obj="idN" '
							.' onkeydown="controlEventos(event)" type="image"  alt="">'
							.'<span  class="glyphicon glyphicon-plus-sign agregar"></span>'
							.'</td>'
							.'<td>'.$num.'</td>'
							.'<td>'.$fecha.'</td>'
							.'<td>'.$adjunto['total'].'</td>'
							.'</tr>';
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
				$ped= $fila;
			}
            $numPedido=$pedido['Numpedcli'];
			if ($pedido['numPedido']){
				$numPedido=$pedido['numPedido'];
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
	$html="";
	$forma="";
	$formasPago=new FormasPago($BDTpv);
	if(isset($formaVenci)){
		$forma=$formaVenci;
	}
	$principal=$formasPago->datosPrincipal($forma);
	$html.='<option value="'.$principal['id'].'">'.$principal['descripcion'].'</option>';
	$otras=$formasPago->formadePagoSinPrincipal($forma);
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
    $nuevafecha = date('Y-m-d');
	if ($fecha>0){
		$vencimiento=new TiposVencimientos($BDTpv);
		$principal=$vencimiento->datosPrincipal($fecha);
		$dias=$principal['dias'];
		$string=" +".$dias." day ";
		$fecha = date('Y-m-j');
		$nuevafecha = strtotime($fecha.$string);
		$nuevafecha = date ( 'Y-m-d' , $nuevafecha );
	}
	return $nuevafecha;
	
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
function montarHTMLimprimir($id , $BDTpv, $dedonde, $tienda){
	$Ccliente=new Cliente($BDTpv);
	$imprimir=array(
	'cabecera'=>"",
	'html'=>""
	);
	$datosCliente=array(
	'Clientes'=>""
	);
	if ($dedonde=='pedido'){
		$Cpedido=new PedidosVentas($BDTpv);
		$datos=$Cpedido->datosPedidos($id);
		$idCliente=$datos['idCliente'];
		$datosCliente=$Ccliente->DatosClientePorId($idCliente);
		$textoCabecera="Pedido de cliente";
		$numero=$datos['Numpedcli'];
		$date=date_create($datos['FechaPedido']);
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
		$date=date_create($datos['Fecha']);
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
		$date=date_create($datos['Fecha']);
		$productos=$Cfaccli->ProductosFactura($id);
		$productosMod=modificarArrayProductos($productos);
		$productos1=json_decode(json_encode($productosMod));
		$Datostotales = recalculoTotales($productos1);
		$albaranFactura=$Cfaccli->AlbaranesFactura($id);
		$alb_html=[];
		if ($albaranFactura){
			 $modificaralbaran=modificarArrayAlbaranes($albaranFactura, $BDTpv);
			 $albaranes=json_decode(json_encode($modificaralbaran), true);
			 foreach ($albaranes as $adjunto){ 
				 
				$total=0;
                $fecha1="";
				if(isset($adjunto['total'])){
					$total=$adjunto['total'];
				}
				if (isset ($adjunto['fecha'])){
					$fecha1=date_create($adjunto['fecha']);
					$fecha1=date_format($fecha1,'Y-m-d');
				}
				$alb_html[]='<tr><td><b><font size="9">Nun Alb:'.$adjunto['Numalbcli'].'</font></b></td><td WIDTH="50%"><b><font size="9">'.$fecha1.'</font></b></td>
				<td colspan="4"><b><font size="9">Total  : '.$total.'€</font></b></td></tr>';
			}
		}
		$alb_html=array_reverse($alb_html);
	}
    $fecha="";
	if (isset ($date)){
		$fecha=date_format($date,'Y-m-d');
	}
    $productos=array_reverse($productos);
	$imprimir['cabecera'].='<p></p><font size="20">Super Oliva </font><br>
		<font size="12">'.$tienda['razonsocial'].'</font><br>'.
		'<font size="12">'.$tienda['direccion'].'</font><br>'.
		'<font size="9"><b>NIF: </b>'.$tienda['nif'].'</font><br>'.
		'<font size="9"><b>Teléfono: </b>'.$tienda['telefono'].'</font><br>'.
		'<font size="17">'.$textoCabecera.' número '.$numero.' con Fecha '.$fecha.'</font>'.
		'<hr>'.
		'<font size="20">'.$datosCliente['Nombre'].'</font><br>'.
		'<table><tr><td><font size="12">'.$datosCliente['razonsocial'].'</font></td>
		<td><font>Dirección de entrega :</font></td></tr>'.
		'<tr><td><font size="9"><b>NIF: </b>'.$datosCliente['nif'].'</font></td>
		<td><font size="9">'.$datosCliente['direccion'].'</font></td></tr>'.
		'<tr><td><font size="9"><b>Teléfono: </b>'.$datosCliente['telefono'].'</font></td>
		<td><font size="9">Código Postal: </font></td></tr>'.
		'<tr><td><font size="9">email: '.$datosCliente['email'].'</font></td><td></td></tr></table>'.
		'<table WIDTH="80%" border="1px"><tr>
			<td>Referencia</td>
			<td WIDTH="50%">Descripción del producto</td>
			<td>Unid/Peso</td>
			<td>Precio</td>
			<td>Importe</td>
			<td>IVA</td>
			</tr></table>';
		$imprimir['html'].='<table WIDTH="80%">';
		$i=0;
		$numAdjunto=0;
		foreach ($productos as $producto){
			if($dedonde=="factura"){
				$numAdjuntoProd=$producto['NumalbCli'];
				if($numAdjuntoProd<>$numAdjunto){
					$imprimir['html'] .= $alb_html[$i];
					$numAdjunto=$numAdjuntoProd;
					$i++;
				}
			}
			$imprimir['html'].='<tr>';
			$imprimir['html'].='<td><font size="8">'.$producto['cref'].'</font></td>';
			$imprimir['html'].='<td WIDTH="50%" ><font size="8">'.$producto['cdetalle'].'</font></td>';
			$imprimir['html'].='<td><font size="8">'.number_format($producto['nunidades'],2).'</font></td>';
			$imprimir['html'].='<td><font size="8">'.number_format($producto['pvpSiva'],2).'</font></td>';
			$importe = $producto['pvpSiva']*$producto['nunidades'];
			$importe = number_format($importe,2);
			$imprimir['html'].='<td><font size="8">'.$importe.'</font></td>';
			$imprimir['html'].='<td><font size="8">'.number_format($producto['iva'],0).'%</font></td>';
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
	$totalBase=0;
	$totaliva=0;
	$htmlIvas['html'] = '';
	if (isset($Datostotales['desglose'] )){
		foreach ($Datostotales['desglose'] as  $key => $basesYivas){
			$key = intval($key);
			$htmlIvas['html'].='<tr id="line'.$key.'">';
			$htmlIvas['html'].='<td id="tipo'.$key.'"> '.$key.'%</td>';
			$htmlIvas['html'].='<td id="base'.$key.'"> '.$basesYivas['base'].'</td>';
			$htmlIvas['html'].='<td id="iva'.$key.'">'.$basesYivas['iva'].'</td>';
			$htmlIvas['html'].='</tr>';
			
		$totalBase=$totalBase+$basesYivas['base'];
		$totaliva=$totaliva+$basesYivas['iva'];
		}
		$htmlIvas['html'].='<tr>'
		.'<td> Totales </td>'
		.'<td>'.$totalBase.'</td>'
		.'<td>'.$totaliva.'</td>'
		.'</tr>';
	return $htmlIvas;
	}
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

function guardarAlbaran($datosPost, $datosGet, $BDTpv, $Datostotales){
	$errores=array();
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];
	if (!isset($Tienda['idTienda']) || !isset($Usuario['id'])){
			$errores[0]=array ( 'tipo'=>'Danger!',
								 'dato' => '',
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR NO HAY DATOS DE SESIÓN!'
								 );
			return $errores;
	}
	$Calbcli=new AlbaranesVentas($BDTpv);
	if (isset($datosGet['tActual'])){
			$datosPost['estado']='Sin guardar';
	}
	$fecha =date_format(date_create($datosPost['fecha']), 'Y-m-d');
	switch($datosPost['estado']){
				case 'Sin guardar':
				case 'Abierto':
					if (isset($datosGet['tActual'])){
						$idAlbaranTemporal=$datosGet['tActual'];
					}else{
						$errores[0]=array ( 'tipo'=>'Warning!',
								 'dato' => '',
								 'class'=>'alert alert-warning',
								 'mensaje' => 'El temporal ya no existe  !'
								 );
						break;
					}
					$datosAlbaran=$Calbcli->buscarDatosAlabaranTemporal($idAlbaranTemporal);
                    $fecha =date_format(date_create($datosAlbaran['fechaInicio']), 'Y-m-d');
					if (isset ($datosPost['fecha'])){
						$fecha =date_format(date_create($datosPost['fecha']), 'Y-m-d');
					}
					if (isset ($datosAlbaran['Productos'])){
						$productos=$datosAlbaran['Productos'];
						$productos_para_recalculo = json_decode( $productos );
						if(count($productos_para_recalculo)>0){
							$CalculoTotales = recalculoTotales($productos_para_recalculo);
							$total=round($CalculoTotales['total'],2);
						}else{
							$errores[0]=array ( 'tipo'=>'Warning!',
								 'dato' => '',
								 'class'=>'alert alert-warning',
								 'mensaje' => 'No tienes productos  !'
								 );
						break;
						}
					}else{
						$errores[0]=array ( 'tipo'=>'Warning!',
								 'dato' => '',
								 'class'=>'alert alert-warning',
								 'mensaje' => 'No tienes productos  !'
								 );
						break;
					}
					$datos=array(
						'Numtemp_albcli'=>$idAlbaranTemporal,
						'Fecha'=>$fecha,
						'idTienda'=>$Tienda['idTienda'],
						'idUsuario'=>$Usuario['id'],
						'idCliente'=>$datosAlbaran['idClientes'],
						'estado'=>"Guardado",
						'total'=>$total,
						'DatosTotales'=>$Datostotales,
						'productos'=>$datosAlbaran['Productos'],
						'pedidos'=>$datosAlbaran['Pedidos']
					);
					if($datosAlbaran['numalbcli']>0){
						$idAlbaran=$datosAlbaran['numalbcli'];
						$eliminarTablasPrincipal=$Calbcli->eliminarAlbaranTablas($idAlbaran);
						if (isset($eliminarTablasPrincipal['error'])){
						$errores[0]=array ( 'tipo'=>'Danger!',
													 'dato' => $eliminarTablasPrincipal['consulta'],
													 'class'=>'alert alert-danger',
													 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
													 );
						}
						
					}else{
						$idAlbaran=0;
					}
					if(count($errores)==0){
							$addNuevo=$Calbcli->AddAlbaranGuardado($datos, $idAlbaran);
							if(isset($addNuevo['error'])){
							$errores[1]=array ( 'tipo'=>'Danger!',
														 'dato' => $addNuevo['consulta'],
														 'class'=>'alert alert-danger',
														 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
														 );
							}
							$eliminarTemporal=$Calbcli->EliminarRegistroTemporal($idAlbaranTemporal, $datosAlbaran['numalbcli']);
							if(isset($eliminarTemporal['error'])){
							$errores[2]=array ( 'tipo'=>'Danger!',
														 'dato' => $eliminarTemporal['consulta'],
														 'class'=>'alert alert-danger',
														 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
														 );
							}
					}
					break;
					case 'Guardado':
					if (isset($datosGet['id'])){
						$idReal=$datosGet['id'];
						$modFecha=$Calbcli->modificarFecha($idReal, $fecha);
						if(isset($modFecha['error'])){
							$errores[2]=array ( 'tipo'=>'Danger!',
														 'dato' => $modFecha['consulta'],
														 'class'=>'alert alert-danger',
														 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
														 );
							}
					}else{
						$errores[0]=array ( 'tipo'=>'Danger!',
								 'dato' => '',
								 'class'=>'aalert alert-danger',
								 'mensaje' => 'No ha recibido ningún id para modificar !'
						);
					}
					break;
					default:
					$errores[0]=array ( 'tipo'=>'Warning!',
								 'dato' => '',
								 'class'=>'alert alert-warning',
								 'mensaje' => 'No has realizado nunguna modificación !'
					);
			
					
					break;
				}
				return $errores;
}
function cancelarAlbaran($idTemporal, $BDTpv){
	$Calbcli=new AlbaranesVentas($BDTpv);
	$Cped = new PedidosVentas($BDTpv);
	$error=array();
	if($idTemporal>0){
		$datosAlbaran=$Calbcli->buscarDatosAlabaranTemporal($idTemporal);
		if(isset($datosAlbaran['error'])){
			$error =array ( 'tipo'=>'Danger!',
								'dato' => $datosAlbaran['consulta'],
								'class'=>'alert alert-danger',
								'mensaje' => 'Error de SQL '
								);
		}else{
			if (isset($datosAlbaran['Pedidos'])){
				$pedidos=json_decode($datosAlbaran['Pedidos'], true);
				foreach ($pedidos as $pedido){
					$mod=$Cped->ModificarEstadoPedido($pedido['idPedCli'], "Guardado");
						if(isset($mod['error'])){
							$error =array ( 'tipo'=>'Danger!',
									'dato' => $mod['consulta'],
									'class'=>'alert alert-danger',
									'mensaje' => 'Error de SQL '
									);
								break;
						}
				}
			}
			$idAlbaran=0;
			$eliminarTemporal=$Calbcli->EliminarRegistroTemporal($idTemporal, $idAlbaran);
			if(isset($eliminarTemporal['error'])){
				$error =array ( 'tipo'=>'Danger!',
									'dato' => $eliminarTemporal['consulta'],
									'class'=>'alert alert-danger',
									'mensaje' => 'Error de SQL '
									);
			}
		}
	}else{
		$error=array ( 'tipo'=>'Info!',
			'dato' => '',
			'class'=>'alert alert-info',
			'mensaje' => 'Sólo se pueden cancelar las facturas Temporales'
			);
	}
	return $error;
}
function cancelarPedido($idTemporal, $BDTpv){
	$Cped = new PedidosVentas($BDTpv);
	$error=array();
	if($idTemporal>0){
		$idPedido=0;
		$eliminarTemporal=$Cped->EliminarRegistroTemporal($idTemporal, $idPedido);
		if(isset($eliminarTemporal['error'])){
			$error =array ( 'tipo'=>'Danger!',
									'dato' => $eliminarTemporal['consulta'],
									'class'=>'alert alert-danger',
									'mensaje' => 'Error de SQL '
									);
		}
	}else{
			$error=array ( 'tipo'=>'Info!',
			'dato' => '',
			'class'=>'alert alert-info',
			'mensaje' => 'Sólo se pueden cancelar las facturas Temporales'
			);
	}
	return $error;
}
function cancelarFactura($idTemporal, $BDTpv){
	$Calbcli=new AlbaranesVentas($BDTpv);
	$Cfaccli=new FacturasVentas($BDTpv);
	$error=array();
	if($idTemporal>0){
		$datosFactura=$Cfaccli->buscarDatosFacturasTemporal($idTemporal);
		if(isset($datosFactura['error'])){
			$error =array ( 'tipo'=>'Danger!',
									'dato' => $datosFactura['consulta'],
									'class'=>'alert alert-danger',
									'mensaje' => 'Error de SQL '
									);
		}else{
			$albaranes=json_decode($datosFactura['Albaranes'], true);
			foreach ($albaranes as $albaran){
				$mod=$Calbcli->ModificarEstadoAlbaran($albaran['idAlbaran'], "Guardado");
				if(isset($mod['error'])){
					$error =array ( 'tipo'=>'Danger!',
									'dato' => $mod['consulta'],
									'class'=>'alert alert-danger',
									'mensaje' => 'Error de SQL '
									);
					break;
				}
			}
			$idFactura=0;
			$eliminarTemporal=$Cfaccli->EliminarRegistroTemporal($idTemporal, $idFactura);
			if(isset($eliminarTemporal['error'])){
				$error =array ( 'tipo'=>'Danger!',
									'dato' => $eliminarTemporal['consulta'],
									'class'=>'alert alert-danger',
									'mensaje' => 'Error de SQL '
									);
			}
			
			
		}
	}else{
		$error=array ( 'tipo'=>'Info!',
			'dato' => '',
			'class'=>'alert alert-info',
			'mensaje' => 'Sólo se pueden cancelar las facturas Temporales'
			);
	}
	return $error;
}
function comprobarAlbaran($idCliente, $BDTpv){
	$Calb=new AlbaranesVentas($BDTpv);
	$busqueda="";
    $bandera=0;
	$con=$Calb->AlbaranClienteGuardado($busqueda, $idCliente);
	if (count($con)>0){
		$bandera=1;
	}
	return $bandera;
}
function incidenciasAdjuntas($id, $dedonde, $BDTpv, $vista){
	include_once('../mod_incidencias/clases/ClaseIncidencia.php');
	$Cindicencia=new ClaseIncidencia($BDTpv);
	$incidenciasAdjuntas=$Cindicencia->incidenciasAdjuntas($id, $dedonde, $vista);
	if(isset($incidenciasAdjuntas['error'])){
		$respuesta['error']=$incidenciasAdjuntas['error'];
		$respuesta['consulta']=$incidenciasAdjuntas['consulta'];
	}else{
		
		$respuesta['datos']=$incidenciasAdjuntas;
	}
	return $respuesta;
}
function modalIncidenciasAdjuntas($datos){
	$html="";
	foreach($datos as $dato){
		$html.='<div class="col-md-12">'
					.'<h4>Incidencia:</h4>'
					.'<div class="col-md-6">'
						.'<label>Fecha:</label>'
						.'<input type="date" name="inci_fecha" id="inci_fecha" value="'.$dato['fecha_creacion'].'" readonly="">'
					.'</div>'
					.'<div class="col-md-6">'
						.'<label>Dedonde:</label>'
						.'<input type="text" name="inci_dedonde" id="inci_dedonde" value="'.$dato['dedonde'].'" readonly="">'
					.'</div>'
				.'</div>'
				.'<div class="col-md-12">'
					.'<div class="col-md-6">'
						.'<label>Estado:</label>'
						.'<input type="text" name="estado" id="estado" value="'.$dato['estado'].'" readonly="">'
					.'</div>'
					.'<div class="col-md-6">'
						.'<label>Usuario:</label>'
						.'<input type="text" name="usuario" id="usuario" value="'.$dato['id_usuario'].'" readonly="">'
					.'</div>'
				.'</div>'
				.'<div class="col-md-12">'
					.'<div class="col-md-6">'
						.'<label>Datos:</label>'
						.'<textarea rows="4" cols="20" readonly> '.$dato['datos'].'</textarea>'
					.'</div>'
					.'<div class="col-md-6">'
						.'<label>Mensaje:</label>'
						.'<textarea rows="4" cols="20" readonly> '.$dato['mensaje'].'</textarea>'
					.'</div>'
				.'</div>';
				
					
	}
	return $html;
}
?>
