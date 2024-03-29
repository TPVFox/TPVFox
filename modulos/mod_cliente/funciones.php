<?php 

function htmlProductos($total_productos,$productos,$busqueda_por,$campoAbuscar,$busqueda){
	// @ Objetivo 
	// Obtener listado de produtos despues de busqueda.
	// @ Parametros 
	// 		$total_productos -> (int) Cantidad total de registros de la consulta.
	//								Si enviamos -1 quiere decir que no se conto los posibles registros.
	
	$resultado = array();
	if ($campoAbuscar === 'idArticulo'){
				$campo_mostrar = 'crefTienda';
	}
	if ($campoAbuscar === 'Referencia'){
		$campo_mostrar = 'cref_tienda_principal';
	}
	if ($campoAbuscar === 'Descripcion'){
		$campo_mostrar = ''; // Este campo realmente no mostramos
	}
	if ($campoAbuscar === 'Codbarras'){
		$campo_mostrar = 'codBarras'; // Este campo realmente no mostramos
	}
	$html = '<label>Busqueda por '.$busqueda_por.'</label>'
			.'<input id="cajaBusqueda" name="'
			.$campoAbuscar.'" placeholder="Buscar" data-obj="cajaBusquedaproductos" size="13" value="'
			.$busqueda.'" onkeydown="controlEventos(event)" type="text">';
	if (count($productos)>20){
		if ($total_producto ===-1){
			// Quiere decir que no se sabe realmente cuantos pueden ser la busqeuda completa.
			$tproductos = '* ';
		} else {
			$tproductos = $total_productos;
		}
		$html .= '<span>20 productos de '.$tproductos.'</span>';
	}
	if ($total_productos === 0){
			// Hay que tener en cuenta tambien si la caja tiene datos ya que sino no es lo mismo.
			if (strlen($busqueda) === 0 ) {
				// Si no encontro resultados, entonces debemos porne una alert y incluso sonorá era guay...
				$html .= '<div class="alert alert-info">'
						.' <strong>Buscar!</strong> Pon las palabras para buscar productos que consideres.</div>';
			} else {
				// Si no encontro resultados, entonces debemos porne una alert y incluso sonorá era guay...
				$html .= '<div class="alert alert-warning">'
						.' <strong>Error!</strong> No se encontrado nada con esa busqueda.</div>';
			}
	} else {
	
		$html.= '<table class="table table-striped"><thead>'
				.'<th></th>'
				.'</thead><tbody>';
		
		$contad = 0;
        foreach ($productos as $producto){
				$datos = 	"'".addslashes(htmlentities($producto['articulo_name'],ENT_COMPAT))."','"
						.number_format($producto['iva'],2)."','".$producto['pvpSiva']."','"
						.number_format($producto['pvpCiva'],2)."',".$producto['idArticulo'];
			$Fila_N = 'Fila_'.$contad;
			$html .= '<tr class="FilaModal" id="'.$Fila_N.'"  onclick="escribirProductoSeleccionado('
					.$datos.');">'
					.' <td id="C'.$contad.'_Lin">'
					.'  <input id="N_'.$contad.'" name="filaproducto"  data-obj="idN"  onkeydown="controlEventos(event)" type="image" alt=""><span class="glyphicon glyphicon-plus-sign agregar"></span></td>';
			$c_m = '';
			if ($campo_mostrar !==''){
				$c_m = htmlspecialchars($producto[$campo_mostrar], ENT_QUOTES);
			}
			$html .=' <td>'.$c_m.'</td>'
					. '<td>'.htmlspecialchars($producto['articulo_name'], ENT_QUOTES).'</td>'
					.' <td>'.number_format($producto['pvpSiva'],2).'</td>'
                    .' <td>'.number_format($producto['pvpCiva'],2).'</td>'
					.'</tr>';
			$contad = $contad +1;
			if ($contad === 20){
				break;
			}
			
		}
		$html .='</tbody></table>';
	}
	$resultado['html'] = $html;
	$resultado['encontrados'] =$total_productos;
	$resultado['campo'] = $campoAbuscar;
	
	return $resultado;

}
function htmlPanelDesplegable($num_desplegable,$titulo,$body){
	// @ Objetivo:
	// Montar html de desplegable.
	// @ Parametros:
	// 		$num_desplegable -> (int) que indica el numero deplegable para un correcto funcionamiento.
	// 		$titulo-> (string) El titulo que se muestra en desplegable
	// 		$body-> (String) lo que contiene el desplegable.
	// Ejemplo tomado de:
	// https://www.w3schools.com/bootstrap/tryit.asp?filename=trybs_collapsible_panel&stacked=h 
	
	$collapse = 'collapse'.$num_desplegable;
	$html ='<div class="panel panel-default">'
			.		'<div class="panel-heading">'
			.			'<h2 class="panel-title">'
			.			'<a data-toggle="collapse" href="#'.$collapse.'">'
			.			$titulo.'</a>'
			.			'</h2>'
			.		'</div>'
			.		'<div id="'.$collapse.'" class="panel-collapse collapse">'
			.			'<div class="panel-body">'
			.				$body
			.			'</div>'
			.		'</div>'
			.'</div>';
	return $html;
	 
}
function htmlTablaGeneral($datos, $HostNombre, $dedonde){
	//Objetivo: crear el html con los datos adjuntos(tickets, albaranes, facturas, pedidos)
	//Dependiendo de donde venga la llamada a la función tiene el un enlace a los resumenes diferente
	//@Parametros:
	//dedonde: de donde son los datos que vamos a buscar:
		//-Tickets
		//-Facturas
		//-Albaranes
		//-Pedidos
        //-Descuentos tickets : Aunque está tabla tendrái ser distinta a las demas , pero la adaptamos ahora.
    $resumen= '';
    $alt= '';
	if(count($datos)>0){
        switch($dedonde){
                case 'tickets':
                    $url=$HostNombre.'/modulos/mod_tpv/ticketCobrado.php?id=';
                    $resumen='<input type="text" class="btn btn-info" onclick="resumen('."'".$dedonde."'".', '.$datos[0]['idCliente'].')" value="Resumen" name="Resumen" ></td>';
                break;

                case 'facturas':
                    $url=$HostNombre.'/modulos/mod_venta/factura.php?id=';
                break;

                case 'albaranes':
                    $url=$HostNombre.'/modulos/mod_venta/albaran.php?id=';
                    $resumen='<input type="text" class="btn btn-info" onclick="resumen('."'".$dedonde."'".', '.$datos[0]['idCliente'].')" value="Resumen" name="Resumen" ></td>';
                break;

                case 'pedidos':
                    $url=$HostNombre.'/modulos/mod_venta/pedido.php?id=';
                break;

                case 'desc_tickets':
                    $url='';
                break;
                
        }
	$html=$resumen.'	<table class="table table-striped">
		<thead>
			<tr>
				<td>Fecha</td>
				<td>Número</td>
				<td>Total</td>
				<td>Estado</td>
			</tr>
		</thead>
		<tbody>';
	
		foreach($datos as $i =>$dato){
			$html.='<tr>'.'<td>'.$dato['fecha'].'</td>';
            $href ='';
            $title='';
            if ($url !== ''){
                $href= 'href="'.$url.$dato['id'].'" ';
            }
            if ($dedonde === 'desc_tickets') {
                $title = ' title="Comprado '.$dato['importeTickets'].'€ en estos tickets"';
            }
			$html.='<td><a '.$href.$title.'>'.$dato['num'].'</a></td>'.
				'<td>'.$dato['total'].'</td>'.
				'<td>'.$dato['estado'].'</td>'.
			'</tr>';
			if($i==10){
				break;
			}
		}
		$html.='</tbody></table>';
	}else{
		$html='<div class="alert alert-info">Este cliente no tiene '.$dedonde.'</div>';
	}
	
	return $html;
}


function comprobarFechas($fechaIni, $fechaFin){
	//@Objetivo: comprobar las fechas de busqueda de resumen 
	//@Comprobaciones:
	//comprobar si las dos fechas están cubiertas 
	//comprobar el formato de las fechas de año mes y dia
    if ($fechaIni==''){
        $fechaIni = '2000-01-01';
    }
    if ($fechaFin==''){
        $fechaFin = date("Y-m-d");
    }
    $fechaIni =date_format(date_create($fechaIni), 'Y-m-d');
    $fechaFin =date_format(date_create($fechaFin), 'Y-m-d');
    $resultado['fechaIni']=$fechaIni;
    $resultado['fechaFin']=$fechaFin;
    return $resultado;
}
function getHtmlOptions($datos,$valor=0){
    // @Objetivo:
    // Crear el html de opciones para mostrar en select y si hay alguno por defecto la ponemos como predeterminada.
    // @Parametro:
    //   $datos -> Array o boreanos (FALSE)
    //              Ejemplo Array (  id => Es el valor que vamos poner, puede ser tanto int como string
    //                      descripcion=> string es el texto que muestra el select)
    //   $valor (varchar/int)-> funciona tanto con varchar como con int , es el pondría por defecto.
   
    $html_options = '<option value="0">	Seleccione opción </option>';
    if (is_array($datos)){
        foreach ($datos as $dato){
            $es_seleccionado = '';
            if ($valor === $dato['id']){
                $es_seleccionado = ' selected';
            }
            $html_options .='<option value="'.$dato['id'].'"'.$es_seleccionado.'>'.$dato['descripcion'].'</option>';
        }
    }
    return $html_options;
}

function getHtmlTdProducto($producto,$tipo){
    // @ Objetivo:
    // Obtener html de con los td de producto de los campos para resumen
    // @ Parametros:
    // $producto:
    // $tipo: (String) que nos indica que es para pantalla o para pdf
    // @ Respuesta:
    // String con html de td montado.
    $calculado_precio_medio = (isset($producto['pm']) ? $producto['pm'] :'');
     $html = '';
    if ($tipo === 'pdf'){
        $abrir_td = '<td><font size="8">';
        $cerrar_td = '</font></td>';
    } else {
        $abrir_td = '<td>';
        $cerrar_td = '</td>';
        // En pdf no ponemos el codbarras, ni idArticulo
        $html .=$abrir_td.htmlspecialchars($producto['idArticulo'], ENT_QUOTES).$cerrar_td.
                $abrir_td.htmlspecialchars($producto['ccodbar'], ENT_QUOTES).$cerrar_td;
    }
    $html .=$abrir_td.htmlspecialchars($producto['cdetalle'], ENT_QUOTES).$cerrar_td.
            $abrir_td.number_format($producto['totalUnidades'],2).$cerrar_td.
            $abrir_td.number_format($producto['precioCiva'],2).$calculado_precio_medio.$cerrar_td.
            $abrir_td.number_format($producto['total_linea'],2).$cerrar_td;
    
    return $html;

}

function getHmtlTrProductos($productos,$tipo){
    // @ Objetivo
    // Obtener html de las filas de los productos del resumen.
    // @ Parametros
    // $productos: Array de array de productos.
    // $tipo : Indicando si es para pantalla o para pdf.
    // @ Respuesta:
    // Devolvemos array con html y total de lineas.
    $respuesta = array ('html'=> '',
                        'totalLineas' => 0);
    $totalProductos=0;
    $totalLineas = 0;
    if(isset($productos)){
        foreach ($productos as $row) {
            $aux[] = $row['cdetalle'];
        }
        array_multisort($aux, SORT_ASC, $productos);

		$resumen_productos = []; // inicializa tabla que aparece como resumen productos
        foreach ($productos as $producto) {			
            $id_producto = $producto['idArticulo'];
            if(!array_key_exists($id_producto, $resumen_productos)){ // busca el indice. Si no existe lo crea con $producto
			  $resumen_productos[$id_producto] = $producto;
			  $resumen_productos[$id_producto]['precioCiva'] = $producto['precioCiva'];
			  $resumen_productos[$id_producto]['precio_medio'] = 0;
			  $resumen_productos[$id_producto]['totalUnidades'] = $producto['totalUnidades'];
			} else {  // Si ya existe suma las unidades y calcula el precio medio
				$total_producto = $producto['totalUnidades'] * $producto['precioCiva'];  
				if($resumen_productos[$id_producto]['precioCiva'] !== $producto['precioCiva']){
					$resumen_productos[$id_producto]['precio_medio'] = 1;
					$resumen_productos[$id_producto]['pm'] = $tipo === 'pdf' ? '<span>*</span>' : '<span title="Precio medio">*</span>';
                    // Si la suma unidades es 0 , el precio medio se deja tal cual..
                    $suma = $resumen_productos[$id_producto]['totalUnidades'] + $producto['totalUnidades'];
                    if ( $suma != 0){
                        $resumen_productos[$id_producto]['precioCiva'] = ($resumen_productos[$id_producto]['total_linea'] + $total_producto) / $suma;
                    }
				}				
				$resumen_productos[$id_producto]['totalUnidades'] += $producto['totalUnidades'];
			}
			$resumen_productos[$id_producto]['total_linea'] = $resumen_productos[$id_producto]['totalUnidades'] * $resumen_productos[$id_producto]['precioCiva'];
        }

		$totalLineas = 0; 
		foreach($resumen_productos as $producto){  // genera la tabla html para mostrar, con el resumen de productos
			$html_td = getHtmlTdProducto($producto, $tipo);
			$respuesta['html'] .= '<tr>' . $html_td . '</tr>';
			$totalLineas += $producto['total_linea'];
		}
    }

	
    $respuesta['totalLineas'] = $totalLineas;
    return $respuesta;
}





