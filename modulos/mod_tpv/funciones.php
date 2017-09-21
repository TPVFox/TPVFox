<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvOlalla de Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero ,
 * @Descripcion	Funciones para importar datos Dbf a Mysql
 * */


function BuscarProductos($campoAbuscar,$busqueda,$BDTpv) {
	// @ Objetivo:
	// 	Es buscar por Referencia / Codbarras / Descripcion nombre.
	// @ Parametros:
	//		campoAbuscar-> indicamos que campo estamos buscando.
	//		busqueda -- string a buscar, puede contener varias palabras
	//		BDTpv-> conexion a la base datos.
	$resultado = array();
	$palabras = array(); 
	$palabras = explode(' ',$busqueda); // array de varias palabras, si las hay..
	$resultado['palabras']= $palabras;

	switch($campoAbuscar) {
		case 'Codbarras':
			$campoAbuscar = 'ac.`codBarras`';
		break;
		case 'Referencia':
			$campoAbuscar = 'at.`crefTienda`';
		break;
		case 'Descripcion':
			$campoAbuscar = 'a.`articulo_name`';
		break;
	}
	$likes = array();
	foreach($palabras as $palabra){
		$likes[] =  $campoAbuscar.' LIKE "%'.$palabra.'%"';
	}
	$buscar = implode(' and ',$likes);
	$sql = 'SELECT a.`idArticulo` , a.`articulo_name` , ac.`codBarras` , ap.pvpCiva, at.crefTienda , a.`iva` '
			.' FROM `articulos` AS a LEFT JOIN `articulosCodigoBarras` AS ac '
			.' ON a.idArticulo = ac.idArticulo LEFT JOIN `articulosPrecios` AS ap '
			.' ON a.idArticulo = ap.idArticulo AND ap.idTienda =1 LEFT JOIN `articulosTiendas` '
			.' AS at ON a.idArticulo = at.idArticulo AND at.idTienda =1 WHERE '.$buscar.' LIMIT 0 , 30 ';
			
			
				
			
	$res = $BDTpv->query($sql);
	//compruebo error en consulta
	if (mysqli_error($BDTpv)){
		$resultado['consulta'] = $sql;
		$resultado['error'] = $BDTpv->error_list;
		return $resultado;
	} 
	
	
	$products = array();
	//fetch_assoc es un boleano..
	while ($fila = $res->fetch_assoc()) {
		$products[] = $fila;
		if (trim($fila['crefTienda']) === trim($busqueda)){
			$resultado['Estado'] = 'Correcto';
			$resultado['datos'][0] = $fila;
			$resultado['numCampos'] = count($fila); //cuento numCampos para recorrerlos en js y mostrarlos
			break; 
		} else if (trim($fila['codBarras']) === trim($busqueda)){
			$resultado['Estado'] = 'Correcto';
			$resultado['datos'][0] = $fila;
			$resultado['numCampos'] = count($fila); //cuento numCampos para recorrerlos en js y mostrarlos
			break; 
		} else {
			$resultado['Estado'] = 'Listado';
			$resultado['datos'] = $products;
		}
	}
	if (!isset ($resultado['Estado'])){
		$resultado['Estado'] = 'No existe producto';
	}
	
	
	return $resultado;
}





function htmlProductos($productos,$campoAbuscar,$busqueda){
	// @ Objetivo 
	// Obtener listado de produtos despues de busqueda.
	$resultado = array();

	
	$resultado['html'] = '<label>Busqueda por '.$campoAbuscar.'</label>';
	$resultado['html'] .= '<input id="cajaBusqueda" name="cajaBusqueda" placeholder="Buscar" size="13" value="'
					.$busqueda.'" onkeyup="teclaPulsada(event,'."'cajaBusqueda',0,'".$campoAbuscar."'".')" type="text">';
	if (count($productos)>10){
		$resultado['html'] .= '<span>10 productos de '.count($productos).'</span>';
	}
	$resultado['html'] .= '<table class="table table-striped"><thead>';
	$resultado['html'] .= ' <th></th>';
	$resultado['html'] .= '</thead><tbody>';
	
	$contad = 0;
	foreach ($productos as $producto){
			$producto['CREF'] = $producto['crefTienda'];
			$producto['CDETALLE'] = $producto['articulo_name'];
			$producto['CTIPOIVA'] = $producto['iva'];
			$producto['CCODEBAR'] = $producto['codBarras'];
			$producto['NPCONIVA'] = $producto['pvpCiva'];
			
		
		$datos = "'".$producto['CREF']."','".$producto['CDETALLE']."','"
					.number_format($producto['CTIPOIVA'],2)."','".$producto['CCODEBAR']."',"
					.number_format($producto['NPCONIVA'],2).",".$producto['idArticulo'];
		$resultado['html'] .= '<tr id="Fila_'.$contad.'" onmouseout="abandonProducto('
					.$contad.')" onmouseover="sobreProductoCraton('.$contad.')"  onclick="cerrarModal('.$datos.');">';
		
		$resultado['html'] .= '<td id="C'.$contad.'_Lin" ><input id="N_'.$contad.'" name="filaproducto" onfocusout="abandonProducto('
					.$contad.')" onfocus="sobreProducto('.$contad.')" onkeyup="teclaPulsada(event,'."filaproducto".",".$contad.')" type="image"  alt=""><span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';
		$resultado['html'] .= '<td>'.$producto['CREF'].'</td>';
		$resultado['html'] .= '<td>'.$producto['CDETALLE'].'</td>';
		$resultado['html'] .= '<td>'.number_format($producto['NPCONIVA'],2).'</td>';

		$resultado['html'] .= '</tr>';
		$contad = $contad +1;
		if ($contad === 10){
			break;
		}
		
	}
	$resultado['html'] .='</tbody></table>';
	$resultado['campo'] = $campoAbuscar;
	
	return $resultado;
	
	
}
// funcion buscar Clientes y HTML clientes para vista modal
//busqueda = valor que queremos buscar
//tabla donde queremos buscar
//BDTpv conexion
//campoAbuscar , le pasamos campos a comparar : 
	//nombre comercial
	//razon social
	//nif
function BusquedaClientes($busqueda,$BDTpv,$tabla){
	$resultado=array();
	$buscar1= 'Nombre';
	$buscar2='razonsocial';
	$buscar3='nif';
	$sql = 'SELECT idClientes, nombre, razonsocial, nif  FROM '.$tabla.' WHERE '.$buscar1.' LIKE "%'.$busqueda.'%" OR '
			.$buscar2.' LIKE "%'.$busqueda.'%" OR '.$buscar3.' LIKE "%'.$busqueda.'%"';
	$res = $BDTpv->query($sql);
	
	 //compruebo error en consulta
	if (mysqli_error($BDTpv)){
		$resultado['consulta'] = $sql;
		$resultado['error'] = $BDTpv->error_list;
		return $resultado;
	} 
	
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

function htmlClientes($busqueda,$clientes){
	$resultado = array();
	
	$resultado['html'] = '<label>Busqueda Cliente</label>';
	$resultado['html'] .= '<input id="cajaBusquedacliente" name="valorCliente" placeholder="Buscar"'.
				'size="13" value="'.$busqueda.'" onkeyup="teclaPulsada(event,'."'".'cajaBusquedacliente'."'".')" type="text">';
				
	if (count($clientes)>10){
		$resultado['html'] .= '<span>10 productos de '.count($clientes).'</span>';
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
			$datos = 	"'".$cliente['idClientes']."','".$razonsocial_nombre."'";
			$resultado['html'] .= '<tr id="Fila_'.$contad.'" onmouseout="abandonProducto('
						.$contad.')" onmouseover="sobreProductoCraton('.$contad.')" onclick="cerrarModalClientes('.$datos.');">';
			$resultado['html'] .= '<td id="C'.$contad.'_Lin" >';
			$resultado['html'] .= '<input id="N_'.$contad.'" name="filacliente" onfocusout="abandonProducto('
						.$contad.')" onkeyup="teclaPulsada(event,'."'filacliente',".$contad.')" onfocus="sobreProducto('.$contad.')"   type="image"  alt="">';
			$resultado['html'] .= '<span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';
			$resultado['html'] .= '<td>'.$cliente['nombre'].'</td>';
			$resultado['html'] .= '<td>'.$cliente['razonsocial'].'</td>';
			$resultado['html'] .= '<td>'.$cliente['nif'].'</td>';
			$resultado['html'] .= '</tr>';
			$contad = $contad +1;
			if ($contad === 10){
				break;
			}
			
		}
	} 
	$resultado['html'] .='</tbody></table>';
	return $resultado;
}


//mostrar TotalTicket
function htmlCobrar($total){
	
	$resultado = array();
	$resultado['entregado'] = 0;
	$resultado['modoPago'] = 0;
	$resultado['imprimir'] = 0;
	$resultado['html'] = '<div style="margin:0 auto; display:table; text-align:right;">';
	$resultado['html'] .= '<h1>'.number_format($total,2).'<span class="small"> €</span></h1>';
	$resultado['html'] .= '<h4> Entrega &nbsp <input id="entrega" autofocus value="" size="8" onkeydown="teclaPulsada(event,'."'entrega',0".')" autofocus></input></h4>';
												
	$resultado['html'] .= '<h4> Cambio &nbsp<input id="cambio" size="8" type="text" name="cambio" value=""></input></h4>';
	
	
	$resultado['html'] .= '<div class="checkbox" style="text-align:center">';
	$resultado['html'] .= '<label><input type="checkbox" checked> Imprimir</label>';
	$resultado['html'] .= '</div>';
	

	$resultado['html'] .= '<div>';
	$resultado['html'] .= '<select name="modoPago" >';
	$resultado['html'] .= '<option value="contado">Contado</option>';
	$resultado['html'] .= '<option value="tarjeta">Tarjeta</option>';
	$resultado['html'] .= '</select>';
	
	$resultado['html'] .= ' <button type="button" class="btn">Aceptar</button>';// falta imprimir cerrar modal 
	$resultado['html'] .= '</div>';
	
	$resultado['html'] .= '</div>';
	
//totalTicket texto
//input Entrega Xdinero
//cambio texto
	
	
	return $resultado;
}

//titulo = "iniciar sesion" abrirModal (titulo,HtmlSesion)
//html sesion usuario
function htmlSesion(){
	$resultado = array();
	$resultado['html']  = '<div style="margin:0 auto; display:table; text-align:right;">';
	$resultado['html'] .= '<form action="index.php" method="POST"/>';  //valido en index home
	$resultado['html'] .= '<tr><td>Nombre:</td>';
	$resultado['html'] .= '<td><input type="text" name="nombre"/></td></tr>';
	$resultado['html'] .= '<tr><td>Clave:</td>';
	$resultado['html'] .= '<td><input type="password" name="clave"/></td></tr>';
	$resultado['html'] .= '<tr><td></td>';
    $resultado['html'] .= '<td><input type="submit" value="Acceder"/></td>';
    $resultado['html'] .= '</tr>';
    $resultado['html'] .= '</form>';
    $resultado['html'] .= '</div>';
	
	
	return $resultado;
}

function grabarTicketsTemporales($BDTpv,$productos,$cabecera,$total) {
	// @ parametros:
	// 	$BDTpv -> Conexion a base de datos.
	// 	$productos -> Array de productos añadidos a ticket
	// 	$cabecera _> Array con datos de la cabecera.	
	// Objetivo guardar datos en tabla temporal de tickets.
	$resultado = array();
	// Tomamos el valor de la fecha actual.
	$fecha		=  date("Y-m-d H:i:s");
	// Ponemods datos de variables cabecera.
	$idTienda	= $cabecera['idTienda'];
	$idCliente	= $cabecera['idCliente'];
	$idUsuario	= $cabecera['idUsuario'];
	// Sabemos comprobamos estado ticket para saber si obtenemos numero.
	if ($cabecera['estadoTicket'] === 'Nuevo'){
		// Tenemos que obtener en que numero ticket temporal de tabla indices.
		$sql = "SELECT `tempticket` FROM `indices` WHERE `idTienda` =1 AND `idUsuario` =1";
		$resp = $BDTpv->query($sql);
		$row = $resp->fetch_array(MYSQLI_NUM); 
		if (count($row) === 1) {
			$numTicket = $row[0] +1;
		} else {
			error_log('Algo salio mal en mod_tpv/funciones.php en funcion grabarTicketTemporal');
			exit;
		}
	} else {
		// Sino es nuevo , será abierto, por lo que ya exite numero.
		$numTicket = $cabecera['numTicket'];
	}
	/*  ================ Montamos el json para guardar productos en un solo campo. ==== */
	$productos_json = array();
	foreach ($productos as $product){
		$productos_json[] = json_encode($product);
	}
	$UnicoCampoProductos 	=json_encode($productos_json);
	$PrepProductos = $BDTpv->real_escape_string($UnicoCampoProductos); //  Escapa los caracteres especiales de una cadena para usarla en una sentencia SQL, tomando en cuenta el conjunto de caracteres actual de la conexión
	/*  ================ Montamos instrucción, según estado. ==== */

	if ($cabecera['estadoTicket'] === 'Nuevo'){
		// Variables cambiadas.
		$resultado['estadoTicket'] = 'Actual'; 
		$resultado['fechaInicial'] = $fecha;

		// Insertamos el nuevo tickettemporal
		$SQL = 'INSERT INTO `ticketstemporales`(`numticket`,`estadoTicket`, `idTienda`, `idUsuario`, `fechaInicio`, `idClientes`, `total`, `Productos`) VALUES ('.$numTicket.',"'.$resultado['estadoTicket'].'",'.$idTienda.','.$idUsuario.',"'.$fecha.'",'.$idCliente.','.$total.',"'.$PrepProductos.'")';
		$BDTpv->query($SQL);
		if (mysqli_error($BDTpv)){
			$resultado['consulta'] = $SQL;
			$resultado['error'] = $BDTpv->error_list;
		} 
		// Ahora comprobamos los estado de los ticket,ya que solo podemos tener uno como actual y los ponemos abiertos.
		
		// Tambien cambiamos el numero ticket temporal por el que se acaba de crear.
		$sql = "UPDATE `indices` SET `tempTicket`=".$numTicket." WHERE `idTienda` =".$idTienda." AND `idUsuario` =".$idUsuario;
		$BDTpv->query($sql);
		if (mysqli_error($BDTpv)){
			$resultado['consulta2'] = $sql;
			$resultado['error2'] = $BDTpv->error_list;
		} 

	} else {
		// Si NO es Nuevo entonces se hace UPDATE
		$SQL = 'UPDATE `ticketstemporales` SET `idClientes`='.$idCliente.',`fechaFinal`="'.$fecha.'",`total`='.$total.',`Productos`='."'".$PrepProductos."'".' WHERE `idTienda`='.$idTienda.' and `idUsuario`='.$idUsuario.' and numticket ='.$numTicket;
		$BDTpv->query($SQL);
		if ($cabecera['estadoTicket'] != 'Abierto'){
			// Quiere decir que no es el actual...
			// aun no las tengo todas conmigo.. para decir esto.
		}
		
		$resultado['estadoTicket'] = 'Actual';
		$resultado['fechaFinal'] = $fecha;
		if (mysqli_error($BDTpv)){
			$resultado['consulta3'] = $SQL;
			$resultado['error3'] = $BDTpv->error_list;
		} 
	}
	$resultado['NumeroTicket'] = $numTicket;
	//~ $resultado['productos'] = $productos_json;	
	$resultado['productos'] = $PrepProductos;
	return $resultado;
	
}

function recalculoTotales($productos) {
	// @ Objetivo recalcular los totales y desglose del ticket
	// @ Parametro:
	// 	$productos (array) no objeto.
	$respuesta = array();
	$desglose = array();
	$subtotal = 0;
	// Creamos array de tipos de ivas hay en productos.
	$ivas = array_unique(array_column($productos,'ctipoiva'));
	sort($ivas); // Ordenamos el array obtenido, ya que los indices seguramente no son correlativos.
	foreach ($productos as $product){
		// Si la linea esta eliminada, no se pone.
		if ($product['estado'] === 'Activo'){
			$totalLinea = $product['unidad'] * $product['pvpconiva'];
			$respuesta['lineatotal'][$product['nfila']] = number_format($totalLinea,2);
			$subtotal = $subtotal + $totalLinea; // Subtotal sumamos importes de lineas.
			// Ahora calculmos bases por ivas
			foreach ($ivas as $key=>$iva){
				if ($product['ctipoiva'] === $iva) {
					$desglose[$key]['BaseYiva'] = (!isset($desglose[$key]['BaseYiva']) ? $totalLinea : $desglose[$key]['BaseYiva']+$totalLinea);
					// Ahora calculamos base y iva 
					if ($iva <10){
						$operador = '1.0'.$iva;
					} else {
						$operador = '1.'.$iva;
					}
					$desglose[$key]['base'] = number_format(($desglose[$key]['BaseYiva']/$operador),2);
					$desglose[$key]['iva'] = number_format($desglose[$key]['BaseYiva']-$desglose[$key]['base'],2);
					$desglose[$key]['tipoIva'] =$iva;

				}
			}
		}
	}
	$respuesta['ivas'] = $ivas;
	$respuesta['desglose'] = $desglose;
	$respuesta['total'] = number_format($subtotal,2);
	return $respuesta;
}
function ControlEstadoTicketsAbierto($BDTpv,$idUsuario,$idTienda) {
	// @ Objetivo:
	// Es poner el estado Abierto todos los tickets temporales de ese usuario y tienda que tenga estado Actual.
	// Se entiende que al entrar en ticket tpv , vamos hacer uno nuevo y abandonamos el que estuvieramos haciendo.
	// por lo cual lo pasamos a abierto.
	$respuesta = array();
	// Montamos consulta
	$sql = 'UPDATE `ticketstemporales` SET `estadoTicket` = "Abierto" WHERE `idTienda` ='.$idTienda.' AND `idUsuario` ='.$idUsuario.' AND estadoTicket ="Actual"';
	$BDTpv->query($sql);
	if (mysqli_error($BDTpv)){
		$resultado['consulta'] = $sql;
		$resultado['error'] = $BDTpv->error_list;
	} 
	// Si fue correcto comprobamos a cuantos afectos, que sería los tickets abiertos.
	$respuesta['num_afectados'] = $BDTpv->affected_rows;
	return $respuesta;
	}
function ObtenerCabeceraTicketAbierto($BDTpv,$idUsuario,$idTienda,$numTicket=0){
	// @ Objetivo es obtener las cabeceras de los ticketAbiertos.
	$respuesta = array();
	// Montamos consulta
	$sql = 'SELECT t.`numticket`,t.`idClientes`,t.`fechaInicio`,t.`fechaFinal`,t.`total`,t.`total_ivas`,c.Nombre, c.razonsocial FROM `ticketstemporales` as t LEFT JOIN clientes as c ON t.idClientes=c.idClientes WHERE t.idTienda ='.$idTienda.' AND t.idUsuario ='.$idUsuario.' AND estadoTicket="Abierto"';
	if ($res = $BDTpv->query($sql)) {
		/* obtener un array asociativo */
			$i= 0;
			while ( $fila = $res->fetch_assoc()){
				if ($numTicket != $fila['numticket']){
				// Añadimos fila a items si el numero ticket no es igual al que recibimos...
				// Si es mismo, quiere decir que estamos modificando o viendo un ticket ( Abierto o Cerrado);
					$respuesta['items'][$i]= $fila;
				$i++;
				}
			}
	} elseif (mysqli_error($BDTpv)){
		$respuesta['consulta'] = $sql;
		$respuesta['error'] = $BDTpv->error_list;
	} 
	
	/* liberar el conjunto de resultados */
    $res->free();
	return $respuesta;
}
function ObtenerUnTicket($BDTpv,$idTienda,$idUsuario,$numero_ticket){
	// @ Objetivo
	// Obtener los datos de un ticket ( ticketsTemporal ), con sus productos en un array.
	// Hay que tener en cuenta que todos los productos del tickets esta en un campo unico, en un array JSON
	$respuesta = array();
	$productos = array();
	$Sql  = 'SELECT * FROM `ticketstemporales` WHERE `idTienda` ='
			.$idTienda.' AND `idUsuario` ='.$idUsuario.' AND `numticket` ='.$numero_ticket;
	if ($resp = $BDTpv->query($Sql)){
		// Quiere decir que hay resultados.
		$respuesta['Numero_rows'] = $resp->num_rows;
		if ( $respuesta['Numero_rows'] === 1) {
			$row = $resp->fetch_assoc(); 
			$productos_json= json_decode ($row['Productos']); // Obtenemos array de productos con campo unico que es un Json con los campos
			foreach ( $productos_json as $product) {
				$productos[] = json_decode($product);// Obtenemos campos del producto.
			}
		} else {
			// Quiere decir que algo salio mal, ya que obtuvo mas o ninguno registro.
			$respuesta['error'] = 'Algo salío mal ';
			return $respuesta; // No continuamos.
		}
	} elseif (mysqli_error($BDTpv)){
		$respuesta['consulta'] = $sql;
		$respuesta['error'] = $BDTpv->error_list;
		return $respuesta; // No continuamos si hay error en la consulta.
	} 
	/* liberar el conjunto de resultados */
    $resp->free();
	$respuesta['productos'] = $productos;
	return $respuesta;
}
function anhadirLineasTicket($productos,$CONF_campoPeso){
	//@ Objetivo:
	// Obtener html de todas las lineas de productos.
	$htmlLineas = array();
	$num_item = 0;
	foreach($productos as $product){
		$unaLinea = htmlLineaTicket($product,$num_item,$CONF_campoPeso);
		$htmlLineas[$num_item] = $unaLinea;
		$num_item++;
	}
	return $htmlLineas;
}

function htmlLineaTicket($producto,$num_item,$CONF_campoPeso){
	//@ Objetivo:
	// Obtener html de una linea de productos.
	//@ Parametros:
	// $product -> Debería ser un objeto, pero por javascritp viene como un array por lo comprobamos y convertimos.
	 if(!is_object($producto)) {
		// Comprobamos si product no es objeto lo convertimos.
		$product = (object)$producto;
		
	} else {
		$product = $producto;
	}
	
	
	// Variables que vamos utilizar:
	$classtr = '' ; // para clase en tr
	$estadoInput = '' ; // estado input cantidad.
	
	// Si estado es eliminado tenemos añadir class y disabled input
	if ($product->estado !=='Activo'){
		$classtr = ' class="tachado" ';
		$estadoInput = 'disabled';
		$btnELiminar_Retornar= '<td class="eliminar"><a onclick="retornarFila('.$num_item.');"><span class="glyphicon glyphicon-export"></span></a></td>';
	} else {
		$btnELiminar_Retornar= '<td class="eliminar"><a onclick="eliminarFila('.$num_item.');"><span class="glyphicon glyphicon-trash"></span></a></td>';
	}
	$nuevaFila = '<tr id="Row'.($product->nfila).'" '.$classtr.'>';
	$nuevaFila .= '<td class="linea">'.$product->nfila.'</td>'; //num linea
	$nuevaFila .= '<td class="codbarras">'.$product->ccodebar.'</td>';
	$nuevaFila .= '<td class="referencia">'.$product->cref.'</td>';
	$nuevaFila .= '<td class="detalle">'.$product->cdetalle.'</td>';
	$nuevaFila .= '<td><input id="N'.$product->nfila.'_Unidad" type="text" pattern="[.0-9]+" name="unidad" placeholder="unidad" size="4"  value="'.$product->unidad.'"  '.$estadoInput.' onkeyup="teclaPulsada(event,'."'Unidad'".','.$product->nfila.')" ></td>';
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
	$importe = number_format($importe);
	$nuevaFila .= '<td id="N'.$product->nfila.'_Importe" class="importe" >'.$importe.'</td>'; //importe 
	// Ahota tengo que controlar el estado del producto,para mostrar uno u otro
	$nuevaFila .= $btnELiminar_Retornar;

	$nuevaFila .='</tr>';
	return $nuevaFila;
}
?>
