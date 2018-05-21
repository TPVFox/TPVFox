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
				'size="13" data-obj="cajaBusquedaproveedor" value="'.$busqueda.'"
				 onkeydown="controlEventos(event)" type="text">';
				
	if (count($proveedores)>10){
		$resultado['html'] .= '<span>10 proveedores de '.count($proveedores).'</span>';
	}
	$resultado['html'] .= '<table class="table table-striped"><thead>'
	. ' <th></th> <th>Nombre</th><th>Razon social</th><th>NIF</th></thead><tbody>';
	if (count($proveedores)>0){
		$contad = 0;
		foreach ($proveedores as $proveedor){  
			
			$razonsocial_nombre=$proveedor['nombrecomercial'].' - '.$proveedor['razonsocial'];
			$datos = 	"'".$proveedor['idProveedor']."','".addslashes(htmlentities($razonsocial_nombre,ENT_COMPAT))."'";
		
			$resultado['html'] .= '<tr id="Fila_'.$contad.'" class="FilaModal" onclick="buscarProveedor('."'".$dedonde."'".' , '
			."'id_proveedor'".', '.$proveedor['idProveedor'].', '."'popup'".');">';
		
			$resultado['html'] .= '<td id="C'.$contad.'_Lin" >';
			$resultado['html'] .= '<input id="N_'.$contad.'" name="filaproveedor" data-obj="idN" onkeydown="controlEventos(event)" type="image"  alt="">'
			. '<span  class="glyphicon glyphicon-plus-sign agregar"></span></td>'
			. '<td>'.htmlspecialchars($proveedor['nombrecomercial'],ENT_QUOTES).'</td>'
			. '<td>'.htmlentities($proveedor['razonsocial'],ENT_QUOTES).'</td>'
			. '<td>'.$proveedor['nif'].'</td>'
			.'</tr>';
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
		if ($idcaja=="cajaBusqueda"){
			$busquedas[] = implode(' and ',$likes);
		}else{
			$busquedas[] = implode(' and ',$whereIdentico);
	
			$busquedas[] = implode(' and ',$likes);
		}
	}
	$i = 0;
	foreach ($busquedas as $buscar){

			$sql = 'SELECT a.`idArticulo` , a.`articulo_name` , ac.`codBarras` , a.ultimoCoste,
			 at.crefTienda ,p.`crefProveedor`, p.coste, p.fechaActualizacion,  a.`iva` '
			.' FROM `articulos` AS a LEFT JOIN `articulosCodigoBarras` AS ac '
			.' ON a.idArticulo = ac.idArticulo '
			.'  LEFT JOIN `articulosTiendas` '
			.' AS at ON a.idArticulo = at.idArticulo AND at.idTienda =1 left join articulosProveedores 
			as p on a.idArticulo=p.`idArticulo` and p.idProveedor='.$idProveedor.' WHERE '
			.$buscar.' group by  a.idArticulo LIMIT 0 , 30 ';
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
	$i=0;
	if ($res->num_rows > 0){
		//fetch_assoc es un boleano..
		while ($fila = $res->fetch_assoc()) {
			$products[] = $fila;
			//~ if(isset($products['fechaActualizacion'])){
				//~ $fecha =date_format(date_create($products['fechaActualizacion']), 'd-m-Y');
				//~ $products[$i]['fechaActualizacion']=$fecha;
			//~ }
			
			$resultado['datos']=$products;
			$i++;
			
		}
		if($resultado['Nitems']==1){
			$fecha=$resultado['datos'][0]['fechaActualizacion'];
			if($fecha!=null){
				$fecha =date_format(date_create($fecha), 'd-m-Y');
				$resultado['datos'][0]['fechaActualizacion']=$fecha;
			}
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
	
	$resultado['html'] .= '<input id="cajaBusqueda" name="'.$id_input.'" placeholder="Buscar" 
					data-obj="cajaBusquedaproductos" size="13" value="'
					.$busqueda.'" onkeydown="controlEventos(event)" type="text">';
	if (count($productos)>10){
		$resultado['html'] .= '<span>10 productos de '.count($productos).'</span>';
	}
	if ($resultado['encontrados'] === 0){
			// Hay que tener en cuenta tambien si la caja tiene datos ya que sino no es lo mismo.
			if (strlen($busqueda) === 0 ) {
				// Si no encontro resultados, entonces debemos porne una alert y incluso sonorá era guay...
				$resultado['html'] .= '<div class="alert alert-info">';
				$resultado['html'] .=' <strong>Buscar!</strong> Pon las palabras para buscar productos 
										que consideres.</div>';
			} else {
				// Si no encontro resultados, entonces debemos porne una alert y incluso sonorá era guay...
				$resultado['html'] .= '<div class="alert alert-warning">';
				$resultado['html'] .=' <strong>Error!</strong> No se encontrado nada con esa busqueda.</div>';
			}
	} else {
	
		$resultado['html'] .= '<table class="table table-striped"><thead><th></th></thead><tbody>';
		$contad = 0;
		foreach ($productos as $producto){
			$datos = 	"'".$id_input."',".
						"'".addslashes(htmlspecialchars($producto['crefTienda'],ENT_COMPAT))."','"
						.addslashes(htmlentities($producto['articulo_name'],ENT_COMPAT))."','"
						.number_format($producto['iva'],2)."','".$producto['codBarras']."','"
						.$producto['ultimoCoste']."',".$producto['idArticulo'].", '".$dedonde."' , ".
						"'".addslashes(htmlspecialchars($producto['crefProveedor'],ENT_COMPAT))."'";
			$resultado['html'] .= '<tr id="Fila_'.$contad.'" class="FilaModal"  
						onclick="escribirProductoSeleccionado('.$datos.');">';
			
			$resultado['html'] .= '<td id="C'.$contad.'_Lin" ><input id="N_'.$contad.'" 
									name="filaproducto" data-obj="idN" 	onkeydown="controlEventos(event)" type="image"  alt="">'
								.'<span class="glyphicon glyphicon-plus-sign agregar"></span></td>';
			if ($id_input=="ReferenciaPro"){
				$resultado['html'] .= '<td>'.htmlspecialchars($producto['crefProveedor'], ENT_QUOTES).'</td>';	
			}else{
				$resultado['html'] .= '<td>'.htmlspecialchars($producto['crefTienda'], ENT_QUOTES).'</td>';	
			}		
			$resultado['html'] .= '<td>'.htmlspecialchars($producto['articulo_name'], ENT_QUOTES).'</td>'
			. '<td>'.$producto['ultimoCoste'].'</td>'
			. '</tr>';
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
			$bandera=$product->iva/100;
			if (isset($desglose[$product->iva])){
			$desglose[$product->iva]['base'] = number_format($desglose[$product->iva]['base'] + $product->importe,3, '.', '');
			$desglose[$product->iva]['iva'] = number_format($desglose[$product->iva]['iva']+ ($product->importe*$bandera),3, '.', '');
			}else{
			$desglose[$product->iva]['base'] = number_format((float)$product->importe,3, '.', '');
			$desglose[$product->iva]['iva'] =number_format((float)$product->importe*$bandera, 3, '.', '');
			}
			$desglose[$product->iva]['BaseYiva'] =number_format((float)$desglose[$product->iva]['base']+$desglose[$product->iva]['iva'], 3, '.', '');	
		}			
	}
	foreach($desglose as $tipoIva=>$des){
		$subivas= $subivas+$desglose[$tipoIva]['iva'];
		$subtotal= $subtotal +$desglose[$tipoIva]['BaseYiva'];
	}	
	$respuesta['desglose'] = $desglose;
	$respuesta['subivas']=$subivas;
	$respuesta['total'] = $subtotal;
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
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'">
							<span class="glyphicon glyphicon-export"></span></a></td>';
			} else {
				$funcOnclick = ' eliminarFila('.$producto['nfila'].' , '."'".$dedonde."'".');';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'">
							<span class="glyphicon glyphicon-trash"></span></a></td>';
				$classtr = '';
				$estadoInput = '';
			}
			if ($dedonde =="albaran" || $dedonde=="factura"){
				$bandera= number_format($producto['ultimoCoste'], 4);
				$coste='<input type="text" id="ultimo_coste_'.$producto['nfila'].'" data-obj="ultimo_coste" 
					onkeydown="controlEventos(event)" name="ultimo" onBlur="controlEventos(event)" value="'
					.$bandera.'" size="6">';
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
				}
			}
			if (isset ($producto['ccodbar'])){
				if ($producto['ccodbar']>0){
					$codBarra=$producto['ccodbar'];
				}else{
					$codBarra="";
				}
			}
		 $respuesta['html'] .='<tr id="Row'.($producto['nfila']).'" '.$classtr.'>'
		 .'<td class="linea">'.$producto['nfila'].'</td>';
		 if ($dedonde=="albaran" || $dedonde=="factura"){
			$respuesta['html'].= '<td class="idArticulo">'.$numeroPed.'</td>';
		 } 
		 $respuesta['html']	.= '<td class="idArticulo">'.$producto['idArticulo'].'</td>'
		 .'<td class="referencia">'.$producto['cref'].'</td>'.$filaProveedor
		 .'<td class="codbarras">'.$codBarra.'</td>'
		 .'<td class="detalle">'.$producto['cdetalle'].'</td>';
		 $cant=number_format($producto['nunidades'],2);
		 $respuesta['html'] .= '<td><input class="unidad" id="Unidad_Fila_'.$producto['nfila']
		 .'" type="text" data-obj="Unidad_Fila"  pattern="[-+]?[0-9]*[.]?[0-9]+" name="unidad" placeholder="unidad" size="4"  value="'
		 .$cant.'"  '.$estadoInput.' onkeydown="controlEventos(event)" onBlur="controlEventos(event)"></td>'
		 .'<td class="pvp">'.$coste.'</td>'
		 . '<td class="tipoiva">'.$producto['iva'].'%</td>';
		 $importe=$producto['ultimoCoste']*$producto['nunidades'];	
		 $importe = number_format($importe,2);
		 $respuesta['html'] .='<td id="N'.$producto['nfila'].'_Importe" class="importe" >'.$importe.'</td>'
		 . $btnELiminar_Retornar
		 .'</tr>';
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
	$respuesta=array(
	'html'=>""
	);
	$respuesta['html']	.= '<table class="table table-striped"><thead>'
	. '<th><td>Número</td><td>Fecha</td>';
	if ($dedonde=="factura"){
		$respuesta['html']	.= '<td>Fecha Venci</td><td>Forma Pago</td><td>Su Número</td>';
	}
	$respuesta['html']	.= '<td>TotalCiva</td>';
	if ($dedonde=="factura"){
		$respuesta['html']	.='<td>TotalSiva</td></th></thead><tbody>';
	}
	$contad = 0;
	foreach ($adjuntos as $adjunto){
		if ($dedonde=="albaran"){
			$numAdjunto=$adjunto['Numpedpro'];
			//~ $fecha=$adjunto['FechaPedido'];
			$fecha = date_create($adjunto['FechaPedido']);
			$fecha=date_format($fecha, 'Y-m-d');
		}else{
			$numAdjunto=$adjunto['Numalbpro'];
			//~ $fecha=$adjunto['Fecha'];
			$fecha = date_create($adjunto['Fecha']);
			$fecha=date_format($fecha, 'Y-m-d');
		}
		$respuesta['html'] 	.= '<tr id="Fila_'.$contad.'" class="FilaModal" onclick="buscarAdjunto('
		."'".$dedonde."'".', '.$numAdjunto.');">';
		$respuesta['html'] 	.= '<td id="C'.$contad.'_Lin" ><input id="N_'.$contad
		.'" name="filaproducto" data-obj="idN" onkeydown="controlEventos(event)"
		 type="image"  alt=""><span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';
		$respuesta['html']	.= '<td>'.$numAdjunto.'</td><td>'.$fecha.'</td>';
		if ($dedonde=="factura"){
			if(isset($adjunto['FechaVencimiento'])){
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
			$respuesta['html']	.= '<td>'.$fechaVenci.'</td><td>'.$textformaPago.'</td>';
			if(isset($adjunto['Su_numero'])){
				$respuesta['html']	.='<td>'.$adjunto['Su_numero'].'</td>';
			}else{
				$respuesta['html']	.='<td></td>';
			}
		}
		$respuesta['html']	.= '<td>'.$adjunto['total'].'</td>';
		if ($dedonde=="factura"){
			$respuesta['html']	.= '<td>'.$adjunto['totalSiva'].'</td></tr>';
		}
		$contad = $contad +1;
		if ($contad === 30){
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
			if (isset($adjunto['NumAdjunto'])){
				$num=$adjunto['NumAdjunto'];
			}
			if (isset($adjunto['Numpedpro'])){
				$num=$adjunto['Numpedpro'];
			}
			if ($adjunto['estado']=="activo"){
				$funcOnclick = ' eliminarAdjunto('.$num.' , '."'".$dedonde."'".' , '.$adjunto['nfila'].');';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'">
				<span class="glyphicon glyphicon-trash"></span></a></td>';
				$classtr = '';
				$estadoInput = '';
			}else{
				$classtr = ' class="tachado" ';
				$estadoInput = 'disabled';
				$funcOnclick = ' retornarAdjunto('.$num.', '."'".$dedonde."'".', '.$adjunto['nfila'].');';
				$btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'">
				<span class="glyphicon glyphicon-export"></span></a></td>';
			}
		}
		$respuesta['html'] .='<tr id="lineaP'.($adjunto['nfila']).'" '.$classtr.'>';
		if (isset($adjunto['NumAdjunto'])){
		$respuesta['html'] .='<td>'.$adjunto['NumAdjunto'].'</td>';
		}
		if($dedonde=="factura"){
			if(isset($adjunto['Su_numero'])){
				$respuesta['html'] .='<td>'.$adjunto['Su_numero'].'</td>';
			}else{
				$respuesta['html'] .='<td></td>';
			}
		}
		$date=date_create($adjunto['fecha']);
		$fecha=date_format($date,'d-m-Y');
		$respuesta['html'] .='<td>'.$fecha.'</td>'
		.'<td>'.$adjunto['total'].'</td>';
		if(isset($adjunto['totalSiva'])){
			$respuesta['html'] .='<td>'.$adjunto['totalSiva'].'</td>';
		}else{
			$respuesta['html'] .='<td></td>';
		}
		$respuesta['html'].=$btnELiminar_Retornar.'</tr>';
	}
	return $respuesta;
}
function modificarArrayAdjunto($adjuntos, $BDTpv, $dedonde){
	$respuesta=array();
	$i=1;
	foreach ($adjuntos as $adjunto){
	if ($dedonde =="albaran"){
		$datosAdjunto=$BDTpv->query('SELECT * FROM pedprot WHERE id= '.$adjunto['idPedido'] );
	}else{
		//~ $datosAdjunto=$BDTpv->query('SELECT * FROM albprot WHERE id= '.$adjunto['idAlbaran'] );
		$datosAdjunto=$BDTpv->query('SELECT a.Su_numero, a.Numalbpro , a.Fecha , a.total,
		a.id , a.FechaVencimiento, a.idProveedor , a.formaPago , sum(b.totalbase) as 
		totalSiva FROM albprot as a INNER JOIN albproIva as b on a.
		`id`=b.idalbpro where a.Numalbpro='.$adjunto['idAlbaran'].' GROUP by a.id ');
	}
	while ($fila = $datosAdjunto->fetch_assoc()) {
			$adj = $fila;
	}
	if ($dedonde=="albaran"){
		$res['NumAdjunto']=$adjunto['numPedido'];
		$res['fecha']=$adj['FechaPedido'];
	}else{
		$res['NumAdjunto']=$adjunto['numAlbaran'];
		$res['fecha']=$adj['Fecha'];
		$res['totalSiva']=$adj['totalSiva'];
		$res['Su_numero']=$adj['Su_numero'];
	}
		$res['idAdjunto']=$adj['id'];
		$res['idPePro']=$adj['idProveedor'];
		$res['total']=$adj['total'];
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
		if (isset($datos['su_num_factura'])){
			$suNumero=$datos['su_num_factura'];
			$textoSuNumero='SU FAC: '.$suNumero;
		}
		$date=date_create($datos['Fecha']);
	}
	if ($dedonde=="albaran"){
		$CAlb=new AlbaranesCompras($BDTpv);
		$datos=$CAlb->datosAlbaran($id);
		$productosAdjuntos=$CAlb->ProductosAlbaran($id);
		$texto="Albarán Proveedor";
		$numero=$datos['Numalbpro'];
		if (isset($datos['su_numero'])){
			$suNumero=$datos['su_numero'];
			$textoSuNumero='SU ALB: '.$suNumero;
		}
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
	$imprimir['cabecera'].='<font size="20">Super Oliva </font><br>
			<font size="12">'.$datosTienda['razonsocial'].'</font><br>'.
			'<font size="12">'.$datosTienda['direccion'].'</font><br>'.
			'<font size="9"><b>NIF: </b>'.$datosTienda['nif'].'</font><br>'.
			'<font size="9"><b>Teléfono: </b>'.$datosTienda['telefono'].'</font><br>'.
			'<font size="17">'.$texto.' número '.$numero.' con Fecha '.$fecha.'</font>'.
			'<hr>'.
			'<font size="20">'.$datosProveedor['nombrecomercial'].'</font><br>'.
			'<table><tr><td><font size="12">'.$datosProveedor['razonsocial'].'</font></td>
			<td><font>Dirección de entrega :</font></td></tr>'.
			'<tr><td><font size="9"><b>NIF: </b>'.$datosProveedor['nif'].'</font></td>
			<td><font>'.$datosProveedor['direccion'].'</font></td></tr>'.
			'<tr><td><font size="9"><b>Teléfono: </b>'.$datosProveedor['telefono'].'</font></td>
			<td><font size="9">Código Postal: </font></td></tr>'.
			'<tr><td><font size="9">email: '.$datosProveedor['email'].'</font></td><td></td></tr></table>'.
			'<table WIDTH="80%"><tr>
			<td>Referencia</td>
			<td WIDTH="50%">Descripción del producto</td>
			<td>Unid/Peso</td>
			<td>Precio</td>
			<td>Importe</td>
			<td>IVA</td>
			</tr></table>';
	//~ //Datos del proveedor
	//~ $imprimir['cabecera'].='<p ></p><p ></p>';
	//~ $imprimir['cabecera'].='<table >';
	//~ $imprimir['cabecera'].='<tr>';
	//~ $imprimir['cabecera'].='<td>'
	//~ .'Proveedor: '.$datosProveedor['idProveedor'].'<br>'
	//~ .$datosProveedor['nombrecomercial'].'<br>'
	//~ .'Dirección:'.$datosProveedor['direccion'].'<br>'
	//~ .'NIF/CIF: '.$datosProveedor['nif'].'<br>'
	//~ .'Teléfono: '.$datosProveedor['telefono'].'<br>'
	//~ .'Email: '.$datosProveedor['email'].'<br>'
	//~ .'Fax: '.$datosProveedor['fax'].'<br>';
			//~ if (isset($suNumero)){
				//~ $imprimir['cabecera'] .=''.$textoSuNumero.'<br>';
			//~ }
	//~ $imprimir['cabecera'].='</td><td>'
			//~ .$texto.'<br>'
			//~ .'Nº: '.$numero.'<br>'
			//~ .'Fecha: '.$fecha.'<br>'
			//~ .$datosTienda['razonsocial'].'<br>'
			//~ .'Direccion: '.$datosTienda['direccion'].'<br>'
			//~ .'Telefono:'.$datosTienda['telefono'].'<br>'
			//~ .'</td></tr></table><hr/><hr/>'
			//~ .'<table  WIDTH="100%"><tr>';
	//~ if ($dedonde == "factura"){
		//~ $imprimir['cabecera'] .='<td WIDTH="10%"><b>ALB</b></td>';
	//~ }
	//~ if ($dedonde =="albaran"){
		//~ $imprimir['cabecera'] .='<td WIDTH="10%"><b>PED</b></td>';
	//~ }
	//~ $imprimir['cabecera'] .='<td WIDTH="10%"><b>REF</b></td>'
	//~ .'<td WIDTH="50%"><b>DESCRIPCIÓN</b></td>'
	//~ .'<td WIDTH="10%"><b>CANT</b></td>'
	//~ .'<td WIDTH="10%"><b>COSTE</b></td>'
	//~ .'<td WIDTH="12%"><b>IMPORTE</b></td>'
	//~ .'</tr></table>';
	
	$imprimir['html'] .='<table WIDTH="80%">';
	
	foreach($productosDEF as $producto){
		if ($producto['estado']=='Activo'){
			$imprimir['html'] .='<tr>';
			$bandera="";
			if (isset($producto['idalbpro'])){
				if ($producto['idalbpro']!==0){
					$bandera=$producto['idalbpro'];	
				}	
			}
			//~ if ($dedonde=="albaran"){
				//~ if (isset($producto['numPedido'])){
					//~ $bandera=$producto['numPedido'];
					//~ $imprimir['html'] .='<td  WIDTH="10%">'.$bandera.'</td>';
				//~ }else{
					
					//~ $imprimir['html'] .='<td  WIDTH="10%"></td>';
				//~ }
			//~ }
			//~ if ($dedonde=="factura"){
				//~ if (isset($producto['idalbpro'])){
					//~ $bandera2=$producto['idalbpro'];
					//~ $imprimir['html'] .='<td  WIDTH="10%">'.$bandera2.'</td>';
				//~ }else{
					//~ $imprimir['html'] .='<td  WIDTH="10%"></td>';
				//~ }
			//~ }
			
			
			
			if ($producto['crefProveedor']>0){
				$refPro=$producto['crefProveedor'];
			}else{
				$refPro="";
			}
			$imprimir['html'] .='<td><font size="8">('.$producto['idArticulo'].') '.$refPro.'</font></td>';
			$imprimir['html'] .='<td WIDTH="50%"><font size="8">'.$producto['cdetalle'].'</font></td>';
			$imprimir['html'] .='<td><font size="8">'.number_format($producto['nunidades'],2).'</font></td>';
			$iva=$producto['iva']/100;
			$imprimir['html'] .='<td><font size="8">'.number_format($producto['ultimoCoste'],2).'</font></td>';
			$imprimir['html'] .='<td><font size="8">'.number_format($producto['importe'],2).'</font></td>';
			$imprimir['html'] .='<td><font size="8">('.number_format($producto['iva'],0).')</font></td>';
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
	$Cpedido=new PedidosCompras($BDTpv);
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
	$fecha=date('Y-m-d');
	$fechaCreacion=date("Y-m-d H:i:s");
	$idPedido=0;
	if (isset($datosGet['tActual'])){
			$datosPost['estado']='Sin guardar';
	}
	switch($datosPost['estado']){
				case 'Sin guardar':
				case 'Abierto':
					if (isset($datosGet['tActual'])){
						$idPedidoTemporal=$datosGet['tActual'];
					}else{
						$errores[0]=array ( 'tipo'=>'Warning!',
								 'dato' => '',
								 'class'=>'alert alert-warning',
								 'mensaje' => 'El temporal ya no existe  !'
								 );
						break;
					}
					$pedidoTemporal=$Cpedido->DatosTemporal($idPedidoTemporal);
					if (isset($pedidoTemporal['error'])){
						$errores[0]=array ( 'tipo'=>'Danger!',
								 'dato' => $pedidoTemporal['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error de SQL:  !'
								 );
						break;
					}else{
						 if (isset($datosPost['fecha'])){
							//~ $fecha=$datosPost['fecha'];
							$fecha =date_format(date_create($datosPost['fecha']), 'Y-m-d');
						}else{
							if (isset($pedidoTemporal['fechaInicio'])){
								$fecha=$pedidoTemporal['fechaInicio'];
							}
						}
						if (isset ($pedidoTemporal['Productos'])){
							$productos=$pedidoTemporal['Productos'];
							$productos_para_recalculo = json_decode( $productos );
							if (count($productos_para_recalculo)>0){
								$CalculoTotales = recalculoTotales($productos_para_recalculo);
								$total=round($CalculoTotales['total'],2);
							}else{
								$errores[0]=array ( 'tipo'=>'Warning!',
								 'dato' => '',
								 'class'=>'alert alert-warning',
								 'mensaje' => 'No existen productos para el recalculo de precios!'
								 );
								break;
							}
						}else{
							$errores[0]=array ( 'tipo'=>'Warning!',
								 'dato' => '',
								 'class'=>'alert alert-warning',
								 'mensaje' => 'No existen productos !'
								 );
							break;
						}
						$datosPedido=array(
							'Numtemp_pedpro'=>$idPedidoTemporal,
							'FechaPedido'=>$fecha,
							'idTienda'=>$Tienda['idTienda'],
							'idUsuario'=>$Usuario['id'],
							'idProveedor'=>$pedidoTemporal['idProveedor'],
							'estado'=>"Guardado",
							'total'=>$total,
							'fechaCreacion'=>$fechaCreacion,
							'Productos'=>$productos,
							'DatosTotales'=>$Datostotales
							);
							if (isset($pedidoTemporal['idPedpro'])){
								$idPedido=$pedidoTemporal['idPedpro'];
								$eliminarTablasPrincipal=$Cpedido->eliminarPedidoTablas($pedidoTemporal['idPedpro']);
								if (isset($eliminarTablasPrincipal['error'])){
									$errores[0]=array ( 'tipo'=>'Danger!',
										'dato' => $eliminarTablasPrincipal['consulta'],
										'class'=>'alert alert-danger',
										'mensaje' => 'Error de SQL:  '
									);
									break;
								}
							}
							
							$addNuevo=$Cpedido->AddPedidoGuardado($datosPedido, $idPedido);
							if (isset($addNuevo['error'])){
								$errores[0]=array ( 'tipo'=>'Danger!',
										'dato' => $addNuevo['consulta'],
										'class'=>'alert alert-danger',
										'mensaje' => 'Error de SQL:  '
									);
									break;
							}else{
								if(isset($addNuevo['id'])){
									$eliminarTemporal=$Cpedido->eliminarTemporal($idPedidoTemporal, $idPedido);
									if (isset($eliminarTemporal['error'])){
										$errores[0]=array ( 'tipo'=>'Danger!',
										'dato' => $eliminarTemporal['consulta'],
										'class'=>'alert alert-danger',
										'mensaje' => 'Error de SQL:  '
										);
										break;
									}
								}
							}
					}
				break;
				case 'Modificado':
				case 'Guardado':
					if (isset($datosGet['id'])){
						if ($datosPost['fecha']){
							$fecha =date_format(date_create($datosPost['fecha']), 'Y-m-d');
							$mod=$Cpedido->modFechaPedido($fecha, $datosGet['id']);
							if (isset($mod['error'])){
								$errores[0]=array ( 'tipo'=>'Danger!',
									'dato' => $mod['consulta'],
									'class'=>'alert alert-danger',
									'mensaje' => 'Error de SQL al modificar la fecha!'
								 );
							}
						}else{
							$errores[0]=array ( 'tipo'=>'Warning!',
								 'dato' => '',
								 'class'=>'alert alert-warning',
								 'mensaje' => 'Has dejado el campo fecha vacío!'
								 );
						}
					}
				break;
				default:
						$errores[0]=array ( 'tipo'=>'Warning!',
								 'dato' => '',
								 'class'=>'alert alert-warning',
								 'mensaje' => 'No puedes modificar este pedido'
								 );
				break;
	}
	return $errores;
}
function guardarAlbaran($datosPost, $datosGet , $BDTpv, $Datostotales){
	//@Objetivo: GUardar un albarán, eliminar el temporal y comprobar cambio de precios 
	//para insertarlos en el historico
	//@Parámetros: 
	//datosPost, datosGet-> son $_POST y $_GET
	//BDTpv-> para poder inicializar las clases
	//Datostotales-> envio el array de datos totales que ya esta calculado en albarán.php
	//Primero compruebo que tengo id de la tienda y el id del usuario
	//A continuación dependiendo del estado realizo unas tareas u otras
	//@Funciones según estados:
	//	-Si el estado es sin guardar o activo realizo comprobaciones, una vez que no se detecten errores
	//	Elimino las tablas temporales en caso de que sea un albarán modificado , después inserto el 
	//	albarán nuevo , elimino el temporal y ejecuto la función historicoCostes para que quede registro de los 
	// 	productos que se modificaron costes
	// - Si el estado es Guardado sólo le modifico la fecha y sunumero ya que no se genera un temporal
	//	cuando se ejecutan estos cambios
	
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
	$suNumero="";
	$formaPago="";
	$fechaVenci="";
	//~ $fecha=$datosPost['fecha'];
	$fecha =date_format(date_create($datosPost['fecha']), 'Y-m-d');
	//~ error_log($fecha);
	$dedonde="albaran";
	$idAlbaran=0;
	$CAlb=new AlbaranesCompras($BDTpv);
		if (isset($datosGet['tActual'])){
			$datosPost['estado']='Sin guardar';
		}
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
					
					$datosAlbaran=$CAlb->buscarAlbaranTemporal($idAlbaranTemporal);
					if (isset($datosPost['suNumero'])){
						$suNumero=$datosPost['suNumero'];
					}
					if (isset ($datosPost['fecha'])){
						//~ $fecha=$datosPost['fecha'];
						$fecha=date_format(date_create($datosPost['fecha']), 'Y-m-d');
					}else{
						//~ $fecha=$datosAlbaran['fechaInicio'];
						$fecha=date_format(date_create($datosAlbaran['fechaInicio']), 'Y-m-d');
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
					if (isset($datosPost['formaVenci'])){
						$formaPago=$datosPost['formaVenci'];
					}
					if(isset($datosPost['fechaVenci'])){
						$fechaVenci=$datosPost['fechaVenci'];
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
					if ($datosAlbaran['numalbpro']){
						$eliminarTablasPrincipal=$CAlb->eliminarAlbaranTablas($datosAlbaran['numalbpro']);
						$idAlbaran=$datosAlbaran['numalbpro'];
					}
					if (isset($eliminarTablasPrincipal['error'])){
						$errores[1]=array ( 'tipo'=>'Danger!',
								 'dato' => $eliminarTablasPrincipal['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error al eliminar las tablas principales!'
								 );
						break;
					}
					$addNuevo=$CAlb->AddAlbaranGuardado($datos, $idAlbaran);
					if (isset($addNuevo['error'])){
						$errores[2]=array ( 'tipo'=>'Danger!',
								 'dato' => $addNuevo['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error añadir un nuevo albarán !'
								 );
					}else{
						if(isset($addNuevo['id'])){
							$historico=historicoCoste($productos, $dedonde, $addNuevo['id'], $BDTpv, $datosAlbaran['idProveedor'], $fecha);
							if (isset($historico['error'])){
								$errores[3]=array ( 'tipo'=>'Info!',
								 'dato' => $historico['consulta'],
								 'class'=>"alert alert-info",
								 'mensaje' => 'Error en al modificar los coste de los productos !'
								 );
							}
							$eliminarTemporal=$CAlb->EliminarRegistroTemporal($idAlbaranTemporal, $idAlbaran);
							if (isset($eliminarTemporal['error'])){
								$errores[4]=array ( 'tipo'=>'Danger!',
									 'dato' => $eliminarTemporal['consulta'],
									 'class'=>'alert alert-danger',
									 'mensaje' => 'Error al eliminar las tablas temporales!'
									 );
							}
						}else{
							$errores[3]=array ( 'tipo'=>'Danger!',
									 'dato' => '',
									 'class'=>'alert alert-danger',
									 'mensaje' => 'Error al generar id nuevo de la función AddAlbaranGuardado!'
									 );
						}
					}
					break;
				case 'Facturado':
				case 'Guardado':
					$idReal=$datosGet['id'];
					if (isset($datosPost['suNumero'])){
						$suNumero=$datosPost['suNumero'];
					}
					if(isset($datosPost['formaVenci'])){
						$formaPago=$datosPost['formaVenci'];
					}
					if(isset($datosPost['fechaVenci'])){
						$fechaVenci=$datosPost['fechaVenci'];
					}
					
					$mod=$CAlb->modFechaNumero($idReal, $suNumero, $fecha, $formaPago, $fechaVenci);
					if (isset($mod['error'])){
						$errores[0]=array ( 'tipo'=>'Danger!',
								 'dato' => $mod['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR de SQL!'
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

function guardarFactura($datosPost, $datosGet , $BDTpv, $Datostotales, $importesFactura){
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
	$CFac = new FacturasCompras($BDTpv);
	if (isset($datosGet['tActual'])){
			$datosPost['estado']='Sin guardar';
	}
	$suNumero="";
	//~ $fecha=date('Y-m-d');
	$fecha =date_format(date_create($datosPost['fecha']), 'Y-m-d');
	$estado="Guardado";
	$entregado=0;
	$dedonde="factura";
	$idFactura=0;
	
	switch($datosPost['estado']){
		case 'Sin guardar':
		case 'Abierto':
			if (isset($datosGet['tActual'])){
						$idFacturaTemporal=$datosGet['tActual'];
			}else{
				$errores[0]=array ( 'tipo'=>'Warning!',
					'dato' => '',
					'class'=>'alert alert-warning',
					'mensaje' => 'El temporal ya no existe  !'
					);
					break;
			}
			$datosFactura=$CFac->buscarFacturaTemporal($idFacturaTemporal);
			if (isset($datosFactura['error'])){
				$errores[0]=array ( 'tipo'=>'Danger!',
						'dato' => $datosFactura['consulta'],
						'class'=>'alert alert-danger',
						'mensaje' => 'Error de SQL !'
					);
			}else{
				if (isset($datosFactura['Productos'])){
					$productos_para_recalculo = json_decode( $datosFactura['Productos'] );
					$CalculoTotales = recalculoTotales($productos_para_recalculo);
					$total=round($CalculoTotales['total'],2);
				}else{
					$errores[0]=array ( 'tipo'=>'Danger!',
						'dato' => '',
						'class'=>'alert alert-danger',
						'mensaje' => 'Error no tienes productos !'
					);
					break;
				}
				if (count($importesFactura)>0){
					foreach ($importesFactura as $import){
						$entregado=$entregado+$import['importe'];
					}
					if ($total==$entregado){
						$estado="Pagado total";
					}else{
						$estado="Pagado Parci";
					}
				}
				if(isset($datosPost['suNumero'])){
					$suNumero=$datosPost['suNumero'];
				}
				if (isset($datosPost['fecha'])){
					if ($datosPost['fecha']==""){
						$errores[0]=array ( 'tipo'=>'Warning!',
						'dato' => '',
						'class'=>'alert alert-warning',
						'mensaje' => 'Has dejado el campo fecha sin cubrir !'
						);
						break;
					}else{
						$fecha=$datosPost['fecha'];
						$fecha =date_format(date_create($datosPost['fecha']), 'Y-m-d');
					}
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
				if ($datosFactura['numfacpro']){
					$idFactura=$datosFactura['numfacpro'];
					$eliminarTablasPrincipal=$CFac->eliminarFacturasTablas($idFactura);
					if (isset($eliminarTablasPrincipal['error'])){
						$errores[0]=array ( 'tipo'=>'Danger!',
						'dato' =>$eliminarTablasPrincipal['consulta'],
						'class'=>'alert alert-danger',
						'mensaje' => 'Error de SQL !'
						);
						break;
					}
				}
				$addNuevo=$CFac->AddFacturaGuardado($datos, $idFactura);
				if (isset($addNuevo['error'])){
					$errores[0]=array ( 'tipo'=>'Danger!',
					'dato' =>$addNuevo['consulta'],
					'class'=>'alert alert-danger',
					'mensaje' => 'Error de SQL !'
					);
					break;
				}else{
					if (isset($addNuevo['id'])){
						$historico=historicoCoste($datosFactura['Productos'], $dedonde, $addNuevo['id'], $BDTpv, $datosFactura['idProveedor'], $datosFactura['fechaInicio']);
						if (isset($historico['error'])){
								$errores[3]=array ( 'tipo'=>'Danger!',
								 'dato' => $historico['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error en al modificar los coste de los productos !'
								 );
						}
						$eliminarTemporal=$CFac->EliminarRegistroTemporal($idFacturaTemporal,  $idFactura);
						if (isset($eliminarTemporal['error'])){
							$errores[4]=array ( 'tipo'=>'Danger!',
								 'dato' => $eliminarTemporal['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error de SQL!'
								 );
							break;
						}
					}else{
						$errores[0]=array ( 'tipo'=>'Danger!',
						'dato' =>'',
						'class'=>'alert alert-danger',
						'mensaje' => 'Error no hizo el inset de nuevo albarán correctamente'
						);
						break;
					}
				}
				
				
			}
		break;
		case 'Pagado total':
			$errores[0]=array ( 'tipo'=>'Warning!',
				'dato' => '',
				'class'=>'alert alert-warning',
				'mensaje' => 'No puedes guardar la factura ya que tiene estado Pagado Total !'
			);
		break;
		case 'Guardado':
		 if ($datosGet['id']){
			
				if (isset($datosPost['suNumero'])){
					$suNumero=$datosPost['suNumero'];
				}
				if (isset($datosPost['fecha'])){
					if ($datosPost['fecha']==""){
						$errores[0]=array ( 'tipo'=>'Warning!',
						'dato' => '',
						'class'=>'alert alert-warning',
						'mensaje' => 'Has dejado el campo fecha sin cubrir !'
						);
					}else{
						 //~ error_log($suNumero);
						 $fecha =date_format(date_create($datosPost['fecha']), 'Y-m-d');
						$mod=$CFac->modFechaNumero($datosGet['id'], $fecha, $suNumero);
						if (isset($mod['error'])){
							$errores[0]=array ( 'tipo'=>'Danger!',
							'dato' => $mod['consulta'],
							'class'=>'alert alert-danger',
							'mensaje' => 'Error de SQL !'
							);
						}
					}
				}else{
					$errores[0]=array ( 'tipo'=>'Warning!',
						'dato' => '',
						'class'=>'alert alert-warning',
						'mensaje' => 'Has dejado el campo fecha sin cubrir !'
					);
				}
				
		}else{
			$errores[0]=array ( 'tipo'=>'Warning!',
				'dato' => '',
				'class'=>'alert alert-warning',
				'mensaje' => 'No has realizado nunguna modificación !'
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
function htmlTotales($Datostotales){
	$htmlIvas['html'] = '';
	$totalBase=0;
	$totaliva=0;
	if (isset($Datostotales['desglose'])){
		foreach ($Datostotales['desglose'] as  $key => $basesYivas){
			$key = intval($key);
			$htmlIvas['html'].='<tr id="line'.$key.'">'
			.'<td id="tipo'.$key.'"> '.$key.'%</td>'
			.'<td id="base'.$key.'"> '.number_format ($basesYivas['base'],2).'</td>'
			.'<td id="iva'.$key.'">'.number_format ($basesYivas['iva'],2).'</td>'
			.'</tr>';
		
		$totalBase=$totalBase+$basesYivas['base'];
		$totaliva=$totaliva+$basesYivas['iva'];
		}
		$htmlIvas['html'].='<tr>'
		.'<td> Totales </td>'
		.'<td>'.$totalBase.'</td>'
		.'<td>'.$totaliva.'</td>'
		.'</tr>';
	}
	return $htmlIvas;
}

function cancelarFactura( $idFacturaTemporal,$BDTpv){
	//@Objetivo: Eliminar la factura temporal y si este tiene alguún albarán adjunto cambiarle
	//el estado a "Guardado"
	//@Parametros:
	//$datosGet: envío los datos de get
	//Si no existe el id Temporal no dejo hacer las funciones siguientes 
	//y muestro un error info
	//@Funciones de clase:
	//buscarFacturaTemporal, primero busco los datos de la factura temporal
	//						comprobación de error sql en la función
	// modEstadoAlbaran, despues compruebo si tengo albaranes adjuntos a la factura
	//				si es así le modifico el estado para que se puedan adjuntar en otro
	//EliminarRegistroTemporal: Por último elimino el registro temporal y como en los 
	//					anteriores compruebo los errores de sql
	$error=array();
	$CFac = new FacturasCompras($BDTpv);
	$CAlb=new AlbaranesCompras($BDTpv);
	if ($idFacturaTemporal>0){
		//~ $idFacturaTemporal=$datosGet['tActual'];
		$idFactura=0;
		$datosFactura=$CFac->buscarFacturaTemporal($idFacturaTemporal);
		if (isset($datosFactura['error'])){
			$error =array ( 'tipo'=>'Danger!',
								 'dato' => $datosFactura['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error de SQL '
								 );
		}else{
			$albaranes=json_decode($datosFactura['Albaranes'], true);
			if(count($albaranes)>0){
				foreach ($albaranes as $albaran){
					$mod=$CAlb->modEstadoAlbaran($albaran['idAdjunto'], "Guardado");
					if(isset($mod['error'])){
						$error=array ( 'tipo'=>'Danger!',
								 'dato' => $mod['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error de SQL'
								 );
						break;
					}
				}
			}
			
			$eliminarTemporal=$CFac->EliminarRegistroTemporal($idFacturaTemporal, $idFactura);
				if (isset($eliminarTemporal['error'])){
					$error=array ( 'tipo'=>'Danger!',
								 'dato' => $eliminarTemporal['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error de SQL'
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
function cancelarPedido( $idTemporal, $BDTpv){
	//@Objetivo: Eliminar el pedido temporal 
	//@Parametros:
	//$datosGet: envío los datos de get
	//Si no existe el id Temporal no dejo hacer las funciones siguientes 
	//y muestro un error info
	//@Funciones de clase:
	//buscarPedidoTemporal, primero busco los datos del pedido temporal
	//						comprobación de error sql en la función
	//EliminarRegistroTemporal: Por último elimino el registro temporal y como en los 
	//					anteriores compruebo los errores de sql
	
	$Cped = new PedidosCompras($BDTpv);
	$error=array();
	$idPedido=0;
	if ($idTemporal>0){
		
		$datosPedido=$Cped->DatosTemporal($idTemporal);
		if (isset($datosPedido['error'])){
			$error =array ( 'tipo'=>'Danger!',
								'dato' => $datosPedido['consulta'],
								'class'=>'alert alert-danger',
								'mensaje' => 'Error de SQL '
								);
		}else{
			$eliminarTemporal=$Cped->eliminarTemporal($idTemporal, $idPedido);
			if (isset($eliminarTemporal['error'])){
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
function cancelarAlbaran( $idTemporal, $BDTpv){
	//@Objetivo: Eliminar el albarán temporal y si este tiene alguún pedido adjunto cambiarle
	//el estado a "Guardado"
	//@Parametros:
	//$idTemporal: envío los datos de get
	//Si no existe el id Temporal no dejo hacer las funciones siguientes 
	//y muestro un error info
	//@Funciones de clase:
	//buscarAlbaranTemporal, primero busco los datos del albarán temporal
	//						comprobación de error sql en la función
	// modEstadoPedido, despues compruebo si tendo pedidos adjuntos al albarán
	//				si es así le mosdifico el estado para que se puedan adjuntar en otro
	//EliminarRegistroTemporal: Por último elimino el registro temporal y como en los 
	//					anteriores compruebo los errores de sql
	
	$CAlb=new AlbaranesCompras($BDTpv);
	$Cped = new PedidosCompras($BDTpv);
	$error=array();
	$idAlbaran=0;
	if ($idTemporal>0){
		//~ $idTemporal=$datosGet['tActual'];
		$datosAlbaran=$CAlb->buscarAlbaranTemporal($idTemporal);
		if (isset($datosAlbaran['error'])){
			$error =array ( 'tipo'=>'Danger!',
								'dato' => $datosAlbaran['consulta'],
								'class'=>'alert alert-danger',
								'mensaje' => 'Error de SQL '
								);
		}else{
			if (isset($datosAlbaran['Pedidos'])){
				$pedidos=json_decode($datosAlbaran['Pedidos'], true);
				if (count($pedidos)>0){
					foreach ($pedidos as $pedido){
						$mod=$Cped->modEstadoPedido($pedido['idAdjunto'], "Guardado");
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
			}
			$eliminarTemporal=$CAlb->EliminarRegistroTemporal($idTemporal, $idAlbaran);
			if (isset($eliminarTemporal['error'])){
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
function htmlImporteFactura($datos, $BDTpv){
	$formaPago=new FormasPago($BDTpv);
	$datosPago=$formaPago->datosPrincipal($datos['forma']);
	$respuesta=array(
	'html'=>""
	);
	$respuesta['html'].='<tr><td>'.$datos['importe'].'</td>'
	.'<td>'.$datos['fecha'].'</td>'
	.'<td>'.$datosPago['descripcion'].'</td>'
	.'<td>'.$datos['referencia'].'</td>'
	.'<td>'.$datos['pendiente'].'</td></tr>';
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
	//~ error_log($total);
	foreach ($importes as $importe){
		
		$nuevo= array();
		$nuevo['importe']=$importe['importe'];
		$nuevo['fecha']=$importe['FechaPago'];
		$nuevo['referencia']=$importe['Referencia'];
		$nuevo['forma']=$importe['idFormasPago'];
		
		$imp=floatval($importe['importe']);
		$total=$total-$imp;
		//~ if($total<0){
			//~ $total=0;
		//~ }
		$nuevo['pendiente']=$total;
		$total=number_format((float)$total,2, '.', '');
		
		array_push($importesDef, $nuevo);
	}
	return $importesDef;
}
function historicoCoste($productos, $dedonde, $numDoc, $BDTpv, $idProveedor, $fecha){
	$errores=array();
	$CArt=new Articulos($BDTpv);
	$fechaCreacion=date('Y-m-d');
	$datos=array(
	'dedonde'=>$dedonde,
	'numDoc'=>$numDoc,
	'tipo'=>"compras",
	'fechaCreacion'=>$fechaCreacion
	);
	 //~ $fecha=date_format($fecha, 'Y-m-d H:i:s');
	//~ $resultado['datos']=$productos;
	//~ $error=0;
	$productos = json_decode($productos, true);
	if (count($productos)>0){
		foreach ($productos as $producto){
			$buscar=$CArt->buscarReferencia($producto['idArticulo'], $idProveedor);
			if (isset($buscar['error'])){
					$errores['error']=$buscar['error'];
					$errores['consulta']=$buscar['consulta'];
					break;
			}else{
				if (isset($producto['CosteAnt'])){
					 $datosNuevos=array(
						'coste'=>$producto['ultimoCoste'],
						'idArticulo'=>$producto['idArticulo'],
						'idProveedor'=>$idProveedor,
						'fecha'=>$fecha,
						'estado'=>"activo"
					);
					if (isset($buscar['fechaActualizacion'])){
						if ($buscar['fechaActualizacion']>$fecha){
							$errores['error']='Warning';
							$errores['consulta']='La fecha de la tabla articulos proveedor es mayor que la del albarán';
						}else{
							$mod=$CArt->modificarCosteProveedorArticulo($datosNuevos);
							if (isset($mod['error'])){
								$errores['error']=$mod['error'];
								$errores['consulta']=$mod['consulta'];
								break;
							}
						}				
					}else{
						$datosNuevos['refProveedor']="";
						$add=$CArt->addArticulosProveedores($datosNuevos);
						if (isset($add['error'])){
							$errores['error']=$add['error'];
							$errores['consulta']=$add['consulta'];
							break;
						}
					}
					
					$datos['idArticulo']=$producto['idArticulo'];
					$datos['antes']=$producto['CosteAnt'];
					$datos['nuevo']=$producto['ultimoCoste'];
					$datos['estado']="Pendiente";
					$nuevoHistorico=$CArt->addHistorico($datos);
					if (isset($nuevoHistorico['error'])){
						$errores['error']=$nuevoHistorico['error'];
						$errores['consulta']=$nuevoHistorico['consulta'];
						break;
					}
						
				}else{
					if (!isset($buscar['idArticulo'])){
						$datosNuevos=array(
							'coste'=>$producto['ultimoCoste'],
							'idArticulo'=>$producto['idArticulo'],
							'idProveedor'=>$idProveedor,
							'fecha'=>$fecha,
							'estado'=>"activo",
							'refProveedor'=>""
						);	
						$add=$CArt->addArticulosProveedores($datosNuevos);
						if (isset($add['error'])){
							$errores['error']=$add['error'];
							$errores['consulta']=$add['consulta'];
						}
					}
				}
				
			}			
		}
	}else{
		$errores['error']='Danger!';
		$errores['consulta']='Error no tiene productos';
	}
	return $errores;
}
function DatosIdAlbaran($id, $CAlb, $Cprveedor, $BDTpv){
		$idAlbaran=$id;
		$datosAlbaran=$CAlb->datosAlbaran($idAlbaran);
		if (isset($datosAlbaran['error'])){
			$errores['error'][0]=array ( 'tipo'=>'Danger!',
									 'dato' => $datosAlbaran['consulta'],
									 'class'=>'alert alert-danger',
									 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
									 );
		}else{
		$productosAlbaran=$CAlb->ProductosAlbaran($idAlbaran);
		if (isset($productosAlbaran['error'])){
			$errores['error'][1]=array ( 'tipo'=>'Danger!',
									 'dato' => $productosAlbaran['consulta'],
									 'class'=>'alert alert-danger',
									 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
									 );
		}
		$ivasAlbaran=$CAlb->IvasAlbaran($idAlbaran);
		if (isset($ivasAlbaran['error'])){
			$errores['error'][2]=array ( 'tipo'=>'Danger!',
									 'dato' => $ivasAlbaran['consulta'],
									 'class'=>'alert alert-danger',
									 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
									 );
		}
		$pedidosAlbaran=$CAlb->PedidosAlbaranes($idAlbaran);
		if (isset($pedidosAlbaran['error'])){
			$errores['error'][3]=array ( 'tipo'=>'Danger!',
									 'dato' => $pedidosAlbaran['consulta'],
									 'class'=>'alert alert-danger',
									 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
									 );
		}
		if (isset($errores['error'])){
			return $errores;
		}else{
		
				$estado=$datosAlbaran['estado'];
				$fecha=date_format(date_create($datosAlbaran['Fecha']),'Y-m-d');
				if ($datosAlbaran['formaPago']){
					$formaPago=$datosAlbaran['formaPago'];
				}else{
					$formaPago=0;
				}
				if ($datosAlbaran['FechaVencimiento']){
					if ($datosAlbaran['FechaVencimiento']==0000-00-00){
						$fechaVencimiento="";
					}else{
						$fechaVencimiento=date_format(date_create($datosAlbaran['FechaVencimiento']),'Y-m-d');
				}
				}
				$idProveedor=$datosAlbaran['idProveedor'];
				if (isset($datosAlbaran['Su_numero'])){
					$suNumero=$datosAlbaran['Su_numero'];
				}else{
					$suNumero="";
				}
				if ($idProveedor){
					$proveedor=$Cprveedor->buscarProveedorId($idProveedor);
					$nombreProveedor=$proveedor['nombrecomercial'];
				}
					$productosAlbaran=modificarArrayProductos($productosAlbaran);
					$productos=json_decode(json_encode($productosAlbaran));
				//Calciular el total con los productos que estn registrados
					$Datostotales = recalculoTotales($productos);
					$productos=json_decode(json_encode($productosAlbaran), true);
					if (isset($pedidosAlbaran)){
						 $modificarPedido=modificarArrayAdjunto($pedidosAlbaran, $BDTpv, "albaran");
						 $pedidos=json_decode(json_encode($modificarPedido), true);
						 
					}
					$respuesta=array(
						'idAlbaran'=>$idAlbaran,
						'estado'=>$estado,
						'fecha'=>$fecha,
						'formaPago'=>$formaPago,
						'fechaVencimiento'=>$fechaVencimiento,
						'idProveedor'=>$idProveedor,
						'nombreProveedor'=>$nombreProveedor,
						'suNumero'=>$suNumero,
						'productos'=>$productos,
						'DatosTotales'=>$Datostotales,
						'pedidos'=>$pedidos
					);
					return $respuesta;
			}
		}
}
function htmlDatosAdjuntoProductos($datos){
	$total=0;
	$totalSiva=0;
	$suNumero="";
if(isset($datos['total'])){
	$total=$datos['total'];
}
if(isset($datos['totalSiva'])){
	$totalSiva=$datos['totalSiva'];
}
if(isset($datos['Su_numero'])){
	$suNumero=$datos['Su_numero'];
}
	$respuesta='<tr class="success">
		<td colspan="2"><strong>Número de albarán:'.$datos['NumAdjunto'].'</strong></td>
		<td colspan="2"><strong>Su número:'.$suNumero.'</strong></td>
		<td colspan="2"><strong>Fecha:'.$datos['fecha'].'</strong></td>
		<td colspan="2"><strong>Total con IVA:'.$total.'</strong></td>
		<td colspan="4"><strong>Total sin IVA:'.$totalSiva.'</strong></td>
		</tr>';
	return $respuesta;
}
?>
