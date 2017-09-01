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
function BuscarProducto($campoAbuscar,$busqueda,$BDImportDbf) {
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
	foreach($palabras as $palabra){
		$palabras[$cont] =  $campoAbuscar.' LIKE "%'.$palabra.'%"';
		$cont++;
	}
	$buscar = implode(' and ',$palabras);
	
	$resultado = array();
	//$sql = 'SELECT CCODEBAR,CREF,CDETALLE,NPCONIVA,CTIPOIVA FROM articulo WHERE '.$campoAbuscar.'='.$busqueda;
	
	$sql = 'SELECT CCODEBAR,CREF,CDETALLE,NPCONIVA,CTIPOIVA FROM articulo WHERE '.$buscar;
	$res = $BDImportDbf->query($sql);
	
	//compruebo error en consulta
	if (mysqli_error($BDImportDbf)){
		$resultado['consulta'] = $sql;
		$resultado['error'] = $BDImportDbf->error_list;
		return $resultado;
	} 
	
	
	$arr = array();
	$i = 0;
	//fetch_assoc es un boleano..
	while ($fila = $res->fetch_assoc()) {
		$arr[$i] = $fila;
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
	$resultado['html'] .= '<input id="cajaBusqueda" name="cajaBusqueda" autofocus placeholder="Buscar"'.
				 'size="13" value="'.$busqueda.'" onkeypress="teclaPulsada(event,'."'cajaBusqueda',0,'".$campoAbuscar."'".')" type="text">';
	if (count($productos>20)){
		$resultado['html'] .= '<span>20 productos de '.count($productos).'</span>';
	}
	$resultado['html'] .= '<table class="table table-striped"><thead>';
	$resultado['html'] .= ' <th></th>';
	$resultado['html'] .= '</thead><tbody>';
	
	$contad = 0;
	foreach ($productos as $producto){
		
		$resultado['html'] .= '<tr id="Fila_'.$contad.'">';
		$datos = 	"'".$producto['CREF']."','".$producto['CDETALLE'].
					"','".$producto['CTIPOIVA']."','".$producto['CCODEBAR']."',".number_format($producto['NPCONIVA'],2).
					$producto['CCODEBAR'];
		$resultado['html'] .= '<td id="C'.$contad.'_Lin" ><a onclick="cerrarModal('.$datos.');"><span id="agregar" class="glyphicon glyphicon-plus-sign agregar"></span></a></td>';
		$resultado['html'] .= '<td>'.$producto['CREF'].'</td>';
		$resultado['html'] .= '<td>'.$producto['CDETALLE'].'</td>';
		$resultado['html'] .= '<td>'.number_format($producto['NPCONIVA'],2).'</td>';

		$resultado['html'] .= '</tr>';
		$contad = $contad +1;
		if ($contad === 20){
			

			break;
		}
		
	}
	$resultado['html'] .='</tbody></table>';
	$resultado['campo'] = $campoAbuscar;
	
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
	$resultado['html'] .= '<h4> Entrega &nbsp <input id="entrega" value="" size="8" onkeypress="teclaPulsada(event,'."'entrega',0".')" autofocus></input></h4>';
												
	$resultado['html'] .= '<h4> Cambio &nbsp<input id="cambio" size="8" type="text" name="cambio" value=""></input></h4>';
	
	//$resultado['html'] .= '<h4> Cambio <div id="cambioText"></div></h4>';
	
	$resultado['html'] .= '<div class="checkbox" style="text-align:center">';
	$resultado['html'] .= '<label><input type="checkbox" checked> Imprimir</label>';
	$resultado['html'] .= '</div>';
	

	$resultado['html'] .= '<div>';
	$resultado['html'] .= '<select multiple name="modoPago" >';
	$resultado['html'] .= '<option value="contado">Contado</option>';
	$resultado['html'] .= '<option value="tarjeta">Tarjeta</option>';
	$resultado['html'] .= '</select>';
	$resultado['html'] .= '<button>Aceptar</button>';
	
	$resultado['html'] .= '</div>';
	

	

	$resultado['html'] .= '</div>';
	
//totalTicket texto
//input Entrega Xdinero
//cambio texto
	
	
	return $resultado;
}


?>
