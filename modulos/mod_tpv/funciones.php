<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvOlalla de Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero ,
 * @Descripcion	Funciones para importar datos Dbf a Mysql
 * */

//Funcion donde se lee Dbf y se obtiene array *
//~ ,$numFinal,$numInic,$campos 

//busqueda , lo que queremos buscar
//campoAbuscar es codbarras, ref, o descripc campo a comparar
function BuscarProducto($campoAbuscar,$busqueda,$BDTpv) {
	// Objetivo:
	// Es buscar por Referencia o Codbarras
	//campos:
	//campoAbuscar 
	//busqueda -- string q puede tener varias palabras
	
	//creamos array de palabras, por si buscan por descripcion Leche larsa
	// asi buscamos que contenga esas palabras en descripc
	//explode junta strings e implode las separa para trabajar mejor con ellas.
	$palabras = array();
	$palabras = explode(' ',$busqueda);
	$cont = 0;
	switch($campoAbuscar) {
		case 'CCODEBAR':
			$campoAbuscar = 'ac.`codBarras`';
		break;
		case 'CREF':
			$campoAbuscar = 'at.`crefTienda`';
		break;
		case 'CDETALLE':
			$campoAbuscar = 'a.`articulo_name`';
		break;
	return $campoAbuscar;
	}
	
	foreach($palabras as $palabra){
		$palabras[$cont] =  $campoAbuscar.' LIKE "%'.$palabra.'%"';
		$cont++;
	}
	$buscar = implode(' and ',$palabras);
	
	$resultado = array();
	//CCODEBAR -> ac.`codBarras`, CREF->at.crefTienda, CDETALLE->a.`articulo_name`, NPCONIVA->ap.pvpCiva
	//CTIPOIVA-> a.iva (es numerico 4.00, 10.00,21.00)
	//$sql = 'SELECT CCODEBAR,CREF,CDETALLE,NPCONIVA,CTIPOIVA FROM articulo WHERE '.$buscar;
	
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
	
	
	$arr = array();
	$i = 0;
	//fetch_assoc es un boleano..
	while ($fila = $res->fetch_assoc()) {
		$arr[$i] = $fila;
		$fila['CREF'] = $fila['crefTienda'];
		$fila['CCODEBAR'] =$fila['codBarras'];
		
		
		$fila['CDETALLE'] = $fila['articulo_name'];
		$fila['CTIPOIVA'] = $fila['iva'];
		$fila['NPCONIVA'] = $fila['pvpCiva'];
		
		
		if (trim ($fila['CREF']) === trim($busqueda)){
			$resultado['Estado'] = 'Correcto';
			$resultado['datos'][0] = $fila;
			$resultado['numCampos'] = count($fila); //cuento numCampos para recorrerlos en js y mostrarlos
			break; 
		} else if (trim ($fila['CCODEBAR']) === trim($busqueda)){
			$resultado['Estado'] = 'Correcto';
			$resultado['datos'][0] = $fila;
			$resultado['numCampos'] = count($fila); //cuento numCampos para recorrerlos en js y mostrarlos
			break; 
		} else {
			$resultado['Estado'] = 'Listado';
			
			$resultado['datos'] = $arr;
		}
		
		$i++;
	}
	if (!isset ($resultado['Estado'])){
		$resultado['Estado'] = 'No existe producto';
		$resultado['datos'] = $arr;
	}
	
	
	return $resultado;
}


function htmlProductos($productos,$campoAbuscar,$busqueda){
	$resultado = array();

	
	$resultado['html'] = '<label>Busqueda por '.$campoAbuscar.'</label>';
	$resultado['html'] .= '<input id="cajaBusqueda" name="cajaBusqueda" placeholder="Buscar" size="13" value="'
					.$busqueda.'" onkeydown="teclaPulsada(event,'."'cajaBusqueda',0,'".$campoAbuscar."'".')" type="text">';
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
			
		
		$datos = 	"'".$producto['CREF']."','".$producto['CDETALLE'].
					"','".number_format($producto['CTIPOIVA'])."','".$producto['CCODEBAR']."',".number_format($producto['NPCONIVA'],2).
					$producto['CCODEBAR'];
		$resultado['html'] .= '<tr id="Fila_'.$contad.'" onmouseout="abandonProducto('
					.$contad.')" onmouseover="sobreProducto('.$contad.')"  onclick="cerrarModal('.$datos.');">';
		
		$resultado['html'] .= '<td id="C'.$contad.'_Lin" ><a href=""><span  class="glyphicon glyphicon-plus-sign agregar"></span></a></td>';
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
				 'size="13" value="'.$busqueda.'" onkeydown="teclaPulsada(event,'."'".'busquedaCliente'."'".',0,'."'".'valorCliente'."'".')" type="text">';
	if (count($clientes)>10){
		$resultado['html'] .= '<span>10 productos de '.count($clientes).'</span>';
	}
	$resultado['html'] .= '<table class="table table-striped"><thead>';
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
						.$contad.')" onmouseover="sobreProducto('.$contad.')"  onclick="cerrarModalClientes('.$datos.');">';
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
	//$resultado['html'] = '<label>COBRAR</label>';
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

?>
