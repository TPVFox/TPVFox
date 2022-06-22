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


function obtenerIconoOrden($campoOrden,$sentidoOrden,$campo){
    // Objetivo:
    // Obtener string con icono de orden.
    // Parametros:
    // $campoOrden -> Nombre de campo por el que esta ordenado.
    // $sentidoOrden -> ASC o DESC el orden.
    // $campo -> Actual , para comparar.
    $icon = '<span class="glyphicon glyphicon-sort';
    if ( $campoOrden == $campo ) {
        if($sentidoOrden=='ASC') { 
            $icon .= '-by-attributes-alt"></span>';
        } else { 
            $icon .= '-by-attributes"></span>';
        } 
    }else { 										
        $icon .= '"></span>';
    } 
    return $icon;
}
?>
