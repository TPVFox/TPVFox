<?php 


function verSelec($BDTpv,$idSelec,$tabla){
	//ver seleccionado en check listado	
	// Obtener datos de un id de usuario.
	$where = 'idProveedor = '.$idSelec;
	$consulta = 'SELECT * FROM '. $tabla.' WHERE '.$where;
	
	$unaOpc = $BDTpv->query($consulta);
	if (mysqli_error($BDTpv)) {
		$fila['error'] = 'Error en la consulta '.$BDTpv->errno;
	} else {
		if ($unaOpc->num_rows > 0){
			$fila = $unaOpc->fetch_assoc();
		} else {
			$fila['error']= ' No se a encontrado proveedor';
		}
	}
	$fila['Nrow']= $unaOpc->num_rows;
	$fila['sql'] = $consulta;
	return $fila ;
}




function htmlTablaGeneral($datos, $HostNombre, $dedonde){
	if(count($datos)>0){
        switch($dedonde){
                case 'facturas':
                    $url=$HostNombre.'/modulos/mod_compras/factura.php?id=';
                    $resumen="";
                break;
                case 'albaranes':
                    $url=$HostNombre.'/modulos/mod_compras/albaran.php?id=';
                    $resumen='<input type="text" class="btn btn-info" onclick="resumen('."'".$dedonde."'".', '.$datos[0]['idProveedor'].')" value="Resumen" name="Resumen" ></td>';
                break;
                case 'pedidos':
                    $url=$HostNombre.'/modulos/mod_compras/pedido.php?id=';
                    $resumen="";
                break;
        }
        $html=$resumen.'<table class="table table-striped">
            <thead>
                <tr>
                    <td>Fecha</td>
                    <td>Número</td>
                    <td>Total</td>
                </tr>
            </thead>
            <tbody>';
        
            foreach($datos as $dato){
                $html.='<tr>'.
                    '<td>'.$dato['fecha'].'</td>'.
                    '<td><a href="'.$url.$dato['id'].'&accion=ver">'.$dato['num'].'</a></td>'.
                    '<td>'.$dato['total'].'</td>'.
                '</tr>';
            }
            $html.='</tbody></table>';
	}else{
		$html='<div class="alert alert-info">Este proveedor no tiene '.$dedonde.'</div>';
	}
	
	return $html;
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


function comprobarFechas($fechaIni, $fechaFin){
	//@Objetivo: comprobar las fechas de busqueda de resumen 
	//@Comprobaciones:
	//comprobar si las dos fechas están cubiertas 
	//comprobar el formato de las fechas de año mes y dia
	$resultado=array();
	if($fechaIni=="" ||$fechaFin==""){
		$resultado['error']='Error';
		$resultado['consulta']='Una de las fechas está sin cubrir';
	}else{
		$fechaIni =date_format(date_create($fechaIni), 'Y-m-d');
		$fechaFin =date_format(date_create($fechaFin), 'Y-m-d');
		$resultado['fechaIni']=$fechaIni;
		$resultado['fechaFin']=$fechaFin;
	}
	return $resultado;
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
            $abrir_td.number_format($producto['CosteSiva'],2).$calculado_precio_medio.$cerrar_td.
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
			  $resumen_productos[$id_producto]['CosteSiva'] = $producto['costeSiva'];
			  $resumen_productos[$id_producto]['precio_medio'] = 0;
			  $resumen_productos[$id_producto]['totalUnidades'] = $producto['totalUnidades'];
			} else {  // Si ya existe suma las unidades y calcula el precio medio
				$total_producto = $producto['totalUnidades'] * $producto['costeSiva'];  
				if($resumen_productos[$id_producto]['costeSiva'] !== $producto['costeSiva']){
					$resumen_productos[$id_producto]['precio_medio'] = 1;
					$resumen_productos[$id_producto]['pm'] = $tipo === 'pdf' ? '<span>*</span>' : '<span title="Precio medio">*</span>';
                    // Si la suma unidades es 0 , el precio medio se deja tal cual..
                    $suma = $resumen_productos[$id_producto]['totalUnidades'] + $producto['totalUnidades'];
                    if ( $suma != 0){
                        $resumen_productos[$id_producto]['costeSiva'] = ($resumen_productos[$id_producto]['total_linea'] + $total_producto) / $suma;
                    }
				}				
				$resumen_productos[$id_producto]['totalUnidades'] += $producto['totalUnidades'];
			}
			$resumen_productos[$id_producto]['total_linea'] = $resumen_productos[$id_producto]['totalUnidades'] * $resumen_productos[$id_producto]['costeSiva'];
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




?>
