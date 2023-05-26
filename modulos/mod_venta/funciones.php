<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvOlalla de Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero ,
 * @Descripcion	Funciones en php para modulo TPV
 * */
include_once './../../inicial.php';

function BuscarProductos($campoAbuscar,$busqueda,$BDTpv, $idCliente) {
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
	foreach($palabras as $palabra){
		$likes[] =  $campoAbuscar.' LIKE "%'.$palabra.'%" ';
	}
	
	//si vuelta es distinto de 1 es que entra por 2da vez busca %likes%	
	
	$busquedas = array();
	
	if ($palabra !== ''){ 
		$busquedas[] = $campoAbuscar.'="'.$busqueda.'"';
		$busquedas[] = implode(' and ',$likes);
	}
	$i = 0;
	foreach ($busquedas as $buscar){
        $sql= 'SELECT a.`idArticulo`, a.`articulo_name`, a.estado, a.tipo, a.beneficio, ac.`codBarras`, ap.pvpCiva, ap.pvpSiva, acli.pvpCiva as pvpCivaCLI, acli.pvpSiva as pvpSivaCLI, AT.crefTienda, a.`iva` FROM `articulos` AS a LEFT JOIN `articulosCodigoBarras` AS ac ON a.idArticulo = ac.idArticulo LEFT JOIN `articulosPrecios` AS ap ON a.idArticulo = ap.idArticulo AND ap.idTienda = 1 LEFT JOIN `articulosTiendas` AS AT ON a.idArticulo = AT.idArticulo AND AT.idTienda = 1 LEFT JOIN `articulosClientes` AS acli ON a.idArticulo = acli.idArticulo AND acli.idClientes='.$idCliente.' WHERE '.$buscar.' GROUP BY a.idArticulo LIMIT 0, 30 ';
		$res = $BDTpv->query($sql);
        if (isset($res->num_rows)) {;
            $resultado['Nitems']= $res->num_rows;
        }else {
            // No obtuvo resultado
            $resultado['Nitems']= 0;
        }
		//si es la 1ª vez que buscamos, y hay muchos resultados, estado correcto y salimos del foreach.
		if ($i === 0){
			if ($resultado['Nitems'] >0){
				$resultado['Estado'] = 'Correcto';
				break;
			}
		}
		//compruebo error en consulta
		if (mysqli_error($BDTpv)){
			$resultado['consulta'] = $sql;
			$resultado['error'] = $BDTpv->error_list;
            error_log('Error en modulo_ventas/funciones.php -> buscarProductos consulta'.$sql);
            error_log('Error en modulo_ventas/funciones.php -> buscarProductos error:'.json_encode($res));
			return $resultado;
		} 
		$i++;
	}	
	//si hay muchos resultados y si es mas de 1, mostrara un listado
	if ($resultado['Nitems'] > 0){
		if ($res->num_rows > 1){
			$resultado['Estado'] = 'Listado';
		}
	} else { 
		$resultado['Estado'] = 'Noexiste';
	}

	//si hay muchos resultados, recogera los datos para mostrarlos
	if ($resultado['Nitems'] > 0){
		//fetch_assoc es un boleano..
		while ($fila = $res->fetch_assoc()) {
			$products[] = $fila;
		}
	}
    $resultado['datos']=$products;
	return $resultado;
}


function cancelarAlbaran($idTemporal, $BDTpv){
	$Calbcli=new AlbaranesVentas($BDTpv);
	$Cped = new PedidosVentas($BDTpv);
	$error=array();
	if($idTemporal>0){
		$datosAlbaran=$Calbcli->buscarDatosTemporal($idTemporal);
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

function cancelarFactura($idTemporal, $BDTpv){
	$Calbcli= new AlbaranesVentas($BDTpv);
	$Cfaccli= new FacturasVentas($BDTpv);
	$error  = array();
	if($idTemporal>0){
		$datosFactura=$Cfaccli->buscarDatosTemporal($idTemporal);
		if(isset($datosFactura['error'])){
			$error = array ( 'tipo'=>'Danger!',
									'dato' => $datosFactura['consulta'],
									'class'=>'alert alert-danger',
									'mensaje' => 'Error de SQL '
									);
		}else{
			$albaranes=json_decode($datosFactura['Albaranes'], true);
			foreach ($albaranes as $albaran){
				$mod=$Calbcli->ModificarEstadoAlbaran($albaran['NumAdjunto'], "Guardado");
				if(isset($mod['error'])){
					$error = array ( 'tipo'=>'Danger!',
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
				$error = array ( 'tipo'=>'Danger!',
									'dato'      => $eliminarTemporal['consulta'],
									'class'     =>'alert alert-danger',
									'mensaje'   => 'Error de SQL '
									);
			}
		}
	}else{
		$error = array ( 'tipo'=>'Info!',
			'dato'      => '',
			'class'     =>'alert alert-info',
			'mensaje'   => 'Sólo se pueden cancelar las facturas Temporales'
			);
	}
	return $error;
}

function cancelarPedido($idTemporal, $BDTpv){
	$Cped = new PedidosVentas($BDTpv);
	$error= array();
	if($idTemporal>0){
		$idPedido=0;
		$eliminarTemporal=$Cped->EliminarRegistroTemporal($idTemporal, $idPedido);
		if(isset($eliminarTemporal['error'])){
			$error = array ( 'tipo'=>'Danger!',
									'dato' => $eliminarTemporal['consulta'],
									'class'=>'alert alert-danger',
									'mensaje' => 'Error de SQL '
									);
		}
	}else{
			$error= array ( 'tipo'=>'Info!',
			'dato'      => '',
			'class'     =>'alert alert-info',
			'mensaje'   => 'Sólo se pueden cancelar las facturas Temporales'
			);
	}
	return $error;
}

function fechaVencimiento($fecha, $dias){
    // @Objetivo
    // Añadirle a fecha los dias que le indicamos.
    // $Respuesta fecha o fecha actual.
    $nuevafecha = date('Y-m-d');
	if ($fecha>0){
		$string     = " +".$dias." day ";
		$fecha      = date($fecha);
		$nuevafecha = strtotime($fecha.$string);
		$nuevafecha = date ( 'Y-m-d' , $nuevafecha );
	}
	return $nuevafecha;
	
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
	$resultado['html'] = '<label>Busqueda Cliente en '.$dedonde.'</label>';
	$resultado['html'] .= '<input id="cajaBusquedacliente" name="valorCliente" placeholder="Buscar"'.
				'size="13" data-obj="cajaBusquedacliente" value="'.$busqueda.'" onkeydown="controlEventos(event)" type="text">';
				
	if (count($clientes)>10){
		$resultado['html'] .= '<span> Se muestra 12 clientes de '.count($clientes).'</span>';
	}
	$resultado['html'] .= '<table class="table table-striped"><thead>';
	$resultado['html'] .= ' <th></th>'; //cabecera blanca para boton agregar
	$resultado['html'] .= ' <th>Nombre</th>';
	$resultado['html'] .= ' <th>Razon social</th>';
	$resultado['html'] .= ' <th>NIF</th>';
	$resultado['html'] .= '</thead><tbody>';
	if (count($clientes)>0){
		$contador_inactivo = 0;
		foreach ($clientes as $key=>$cliente){ 
			$clase_inactiva = '';
			if ($cliente['estado']!=='Activo'){
				$clase_inactiva = ' danger';
				$contador_inactivo++;
			} 
			$razonsocial_nombre=$cliente['Nombre'].' - '.$cliente['razonsocial'];
			$datos = 	"'".$cliente['idClientes']."','".addslashes(htmlentities($razonsocial_nombre,ENT_COMPAT))."'";
			$resultado['html'] .= '<tr id="Fila_'.$key.'" '
								.'class="FilaModal'.$clase_inactiva.'" '
								.'onclick="buscarClientes('."'".$dedonde."','id_cliente',".$cliente['idClientes'].');">';
		
			$resultado['html'] .= '<td id="C'.$key.'_Lin" >';
			$resultado['html'] .= '<input id="N_'.$key.'" name="filacliente" data-obj="idN"'
								.'onkeydown="controlEventos(event)" type="image" value='.$cliente['idClientes'].' alt="">';
			$resultado['html'] .= '<span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';
			$resultado['html'] .= '<td>'.htmlspecialchars($cliente['Nombre'],ENT_QUOTES).'</td>';
			$resultado['html'] .= '<td>'.htmlentities($cliente['razonsocial'],ENT_QUOTES).'</td>';
			$resultado['html'] .= '<td>'.$cliente['nif'].'</td>';
			$resultado['html'] .= '</tr>';
			if ($key === 11){
				break;
			}
		}
		if ($contador_inactivo>0){
			$resultado['html'] .=	' <div class="alert alert-danger">'
									.'Recuerda que los clientes INACTIVOS están Rojo, no se puede añadir</div> ';
			}
	} 
	$resultado['html'] .='</tbody></table>';
	// Ahora generamos objetos de filas.
	// Objetos queremos controlar.
	return $resultado;
}

function htmlLineaAdjunto($adjunto, $dedonde,$accion='ver'){
    // @ Objectivo
    // Añadir una linea adjunto, si viene $accion de editar, muestro bottones retornar o eliminar fila.
	$html="";
    $btnELiminar_Retornar = '';
    $classtr = '';
    if ($accion !=='ver'){
        if ($adjunto['estado']=="Activo"){
            $funcOnclick = ' eliminarAdjunto('.$adjunto['NumAdjunto'].' , '."'".$dedonde."'".' , '.$adjunto['nfila'].');';
            $btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-trash"></span></a></td>';
        }else{
            $classtr = ' class="tachado" ';
            $funcOnclick = ' retornarAdjunto('.$adjunto['NumAdjunto'].', '."'".$dedonde."'".', '.$adjunto['nfila'].');';
            $btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-export"></span></a></td>';
        }
    }
    $html .='<tr id="lineaP'.($adjunto['nfila']).'" '.$classtr.'>';
    $html .='<td>'.$adjunto['NumAdjunto'].'</td>';
    $html .='<td>'.$adjunto['fecha'].'</td>';
    $html .='<td>'.$adjunto['total'].'</td>';
    $html .=$btnELiminar_Retornar;
    $html .='</tr>';
	return $html;
}

function htmlLineaProductos($producto, $dedonde,$accion='ver'){
    // @ Objetivo
    // Montar la tr (linea) de producto.
    // @ Parametros
    // $producto -> Array de producto, con estadoLinea, aparte del estado del producto.
    // $dedonde  -> string que indica si es pedido, albaran o factura.
    // $accion   -> indica si permitimos interactuar con linea, ver : no permite ,editar : permite cambiarlo.
    // @ Resultado
    // Enviar array resultado con $html.
	$respuesta=array();
    // Valores calculados
    $importe = $producto['pvpSiva']*$producto['nunidades'];
    $importe = number_format($importe,2);
    $cant=number_format($producto['nunidades'],2);
    $btnELiminar_Retornar ='';
    $estadoInput = 'disabled';
    $classtr = ' ';
    // ---  Obtenemos el numero del adjunto del producto para montar --- //
    $numAdjunto    ="";
    $td_numAdjunto =''; 
    if ($dedonde=="albaran"){
        if(isset($producto['NumpedCli'])){
            if ($producto['NumpedCli']>0){
                $numAdjunto=$producto['NumpedCli'];
            }
        }else if (isset($producto['Numpedcli'])){
                $numAdjunto=$producto['Numpedcli'];
        }
        $td_numAdjunto ='<td>'.$numAdjunto.'</td>'; 
    }
    if ($dedonde=="factura"){
        if(isset($producto['Numalbcli'])){
            if ($producto['Numalbcli']>0){
            $numAdjunto=$producto['Numalbcli'];
            }
        }else{
            if(isset($producto['NumalbCli'])){
                $numAdjunto=$producto['NumalbCli'];
            }
        }
        $td_numAdjunto ='<td>'.$numAdjunto.'</td>'; 
    }
    // --   Creamos btnEliminar_Retornar y estadoInput para los input     --//
    // Si la accion es distinto 'ver' y si numAdjunto existe  y dedonde es distinto Albaran bloqueamos input y no mostramos btn -- //
    if ($accion !=='ver' ) {
        $Control_btn_input = 'OK'; // Control de btnEliminar_Retornar y disabled input
        if (strval($numAdjunto) > 0){
            if ($dedonde !=="albaran"){
                $Control_btn_input = 'KO'; // No permitimos editar input y no mostramos btnEliminar_Retornar 
            }
        }
        if ($Control_btn_input == 'OK'){
            if ($producto['estadoLinea'] !=='Activo'){
                $classtr = ' class="tachado" ';
                $funcOnclick = ' retornarFila('.$producto['nfila'].', '."'".$dedonde."'".');';
                $btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-export"></span></a></td>';
            } else {
                $funcOnclick = ' eliminarFila('.$producto['nfila'].' , '."'".$dedonde."'".');';
                $btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'"><span class="glyphicon glyphicon-trash"></span></a></td>';
                $estadoInput = '';
            }
        }
    }
   	$codBarras="";
    if (isset($producto['ccodbar'])){
        $codBarras=$producto['ccodbar'];
    }
    $html ='';
    $html .='<tr id="Row'.($producto['nfila']).'" '.$classtr.'>';
     
    $html .='<td class="linea">'.$producto['nfila'].'</td>';
    $html .=$td_numAdjunto; 
    $html .= '<td class="idArticulo">'.$producto['idArticulo'].'</td>';
    $html .='<td class="referencia">'.$producto['cref'].'</td>';
    $html .='<td class="codbarras">'.$codBarras.'</td>';
    $html .= '<td class="detalle">'.$producto['cdetalle'].'</td>';
    $html .= '<td><input class="unidad" id="Unidad_Fila_'.$producto['nfila'].'" type="text" data-obj="Unidad_Fila" pattern="[-+]?[0-9]*[.]?[0-9]+" name="unidad" placeholder="unidad" size="3"  value="'.$cant.'"  '.$estadoInput.' onkeydown="controlEventos(event)" onBlur="controlEventos(event)"></td>';	
    //$html .='<td class="pvp">'.$producto['precioCiva'].'</td>';
	$html .= '<td><input class="pvp" id="precioCiva_Fila_'.$producto['nfila'].'" type="text" data-obj="precioCiva_Fila" pattern="[-+]?[0-9]*[.]?[0-9]+" name="precioCiva" placeholder="Precio CON Iva" size="5"  value="'.$producto['precioCiva'].'"  '.$estadoInput.' onkeydown="controlEventos(event)" onBlur="controlEventos(event)"></td>';
    $html .='<td id="pvpSiva_'.$producto['nfila'].'">'.$producto['pvpSiva'].'</td>';
    $html .= '<td class="tipoiva">'.$producto['iva'].'%</td>';
    $html .='<td id="N'.$producto['nfila'].'_Importe" class="importe" >'.$importe.'</td>';
    $html .= $btnELiminar_Retornar;
    $html .='</tr>';

    $respuesta['html'] = $html;
	 return $respuesta['html'];
}

function htmlOptions($opciones,$opcionSeleccionada =0 ){
	$html='';
	foreach($opciones as $f){
        $select = '';
        if ($f['id'] == $opcionSeleccionada){
            $select = ' selected="selected"';
        }
		$html.='<option value= "'.$f['id'].'"'.$select.'>'.$f['descripcion'].'</option>';
    }
	return $html;
}

function htmlListadoProductos($productos,$id_input,$campoAbuscar,$busqueda, $dedonde, $BDTpv,$idCliente){
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
	if (count($productos)>15){
		$resultado['html'] .= '<span>15 productos de '.count($productos).'</span>';
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
            // Ahora comprobamos si tiene precio en tarifa.
            if($producto['pvpCivaCLI']!==null){
                $pvpCiva = number_format($producto['pvpCivaCLI'],2);
                $pvptachado ='<strike>'.number_format($producto['pvpCiva'],2).'</strike>';
            } else {
                $pvpCiva = number_format($producto['pvpCiva'],2);
                $pvptachado = '';
            }
			$datos = 	"'".$id_input."',".
						"'".addslashes(htmlspecialchars($producto['crefTienda'],ENT_COMPAT))."','"
						.addslashes(htmlentities($producto['articulo_name'],ENT_COMPAT))."','"
						.number_format($producto['iva'],2)."','".$producto['codBarras']."',"
						.$pvpCiva.",".$producto['idArticulo'].
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
								.'<td>'.number_format($pvpCiva,2).'</td>'
                                .'<td>'.$pvptachado.'</td>';
								
			$resultado['html'] .='</tr>';
			$contad = $contad +1;
			if ($contad === 15){
				break;
			}
		}
		$resultado['html'] .='</tbody></table>';
	}
	$resultado['campo'] = $campoAbuscar;
	return $resultado;
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


function modalAdjunto($adjuntos,$onclick,$dedonde){
	$respuesta=array();
    if (count($adjuntos)>0) {
        $html = '<p>Mostramos 15 de '.count($adjuntos).'</p>';
        $html .='<table class="table table-striped"><thead>';
        $html .='<th>';
        $html .='<td>Número </td>';
        $html .='<td>Fecha</td>';
        $html .='<td>Total</td>';
        $html .='</th>';
        $html .='</thead><tbody>';
        foreach ($adjuntos as $i =>$adjunto){
            $num=$adjunto['NumAdjunto'];
            $fecha=$adjunto['fecha'];
            
            $html .= '<tr id="Fila_'.$i.'" class="FilaModal" onclick="'.$onclick."('".$dedonde."',".$num.')";>'
                                .'<td id="C'.$i.'_Lin" >'
                                .'<input id="N_'.$i.'" name="filapedido" data-obj="idN" '
                                .' onkeydown="controlEventos(event)" type="image"  alt="">'
                                .'<span  class="glyphicon glyphicon-plus-sign agregar"></span>'
                                .'</td>'
                                .'<td>'.$num.'</td>'
                                .'<td>'.$fecha.'</td>'
                                .'<td>'.$adjunto['total'].'</td>'
                                .'</tr>';
            if ($i === 15){
                break;
            }
        }
        $html .='</tbody></table>';
    } else {
            $html = '<div class="alert alert-warning">No se encontro ningun albaran de ese cliente con estado guardado</div>';
    }
    $respuesta['html'] =$html;
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
			$res['fecha']=$ped['Fecha'];
			$res['idPedCli']=$ped['id'];
			$res['total']=$ped['total'];
			$res['estado']="Activo";
			$res['nfila']=$i;
			array_push($respuesta,$res);
		$i++;
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
        if(isset($producto['Numalbcli'])){
			$product['NumalbCli']=$producto['Numalbcli'];
		}
		if(isset($producto['NumpedCli'])){
			$product['NumpedCli']=$producto['NumpedCli'];
		}
		if(isset($producto['Numpedcli'])){
			$product['Numpedcli']=$producto['Numpedcli'];
		}
		$product['importe']=$sinIva*$producto['nunidades'];
		array_push($respuesta,$product);
	}
	return $respuesta;
}

function montarHTMLimprimir($id , $BDTpv, $dedonde, $datosTienda){
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
		$datos=$Cpedido->datosPedido($id);
		$datosCliente=$Ccliente->DatosClientePorId($datos['idCliente']);
		$textoCabecera="Pedido de cliente";
		$numero=$datos['Numpedcli'];
		$productos=$Cpedido->ProductosPedido($id);
    }
	if ($dedonde =='albaran'){
		$Calbaran=new AlbaranesVentas($BDTpv);
		$datos=$Calbaran->datosAlbaran($id);
		$datosCliente=$Ccliente->DatosClientePorId($datos['idCliente']);
		$textoCabecera="Albarán de Cliente";
		$numero=$datos['Numalbcli'];
		$productos=$Calbaran->ProductosAlbaran($id);
	}
	if ($dedonde=='factura'){
		$Cfaccli=new FacturasVentas($BDTpv);
		$datos=$Cfaccli->datosFactura($id);
		$datosCliente=$Ccliente->DatosClientePorId($datos['idCliente']);
		$textoCabecera="Factura de Cliente";
		$numero=$datos['Numfaccli'];
		$productos=$Cfaccli->ProductosFactura($id);
		$albaranes=$Cfaccli->obtenerAlbaranesFactura($id);
		$alb_html=[];
		if (count($albaranes['Items'])>0){
			 foreach ($albaranes['Items'] as $adjunto){ 
				$total=0;
                $fecha1="";
				if(isset($adjunto['total'])){
					$total=$adjunto['total'];
				}
				if (isset ($adjunto['fecha'])){
					$fecha1=date_create($adjunto['fecha']);
					$fecha1=date_format($fecha1,'Y-m-d');
				}
				$alb_html[]='<tr><td colspan="2"><b><font size="9">Nun Alb:'.$adjunto['NumalbCli'].'</font></b></td><td><b><font size="9">'.$fecha1.'</font></b></td>
				<td colspan="2"><b><font size="9">Total  : '.$total.'€</font></b></td></tr>';
			}
		}
	}
    // Valores comunes
    $idCliente=$datos['idCliente'];
    $productosMod=modificarArrayProductos($productos);
	$prod_paraTotales=json_decode(json_encode($productosMod));
	$Datostotales = recalculoTotales($prod_paraTotales);
    $date=date_create($datos['Fecha']);

    $fecha="";
	if (isset ($date)){
		$fecha=date_format($date,'d-m-Y');
	}
    $direccion =  ucwords(strtolower($datosCliente['direccion']));
    $imprimir['cabecera'] =  '<table>'
                            .'<tr>'
                            .'<td><font size="20">'.$textoCabecera.'</font></td>'
                            .'<td><font size="9"><b>Número:</b>'.$numero.'<b><br>Fecha:</b>'.$fecha.'</font></td>'
                            .'</tr>'
                            .'</table>'
                            .'<hr style="color:black ; cap:0;join:0;dash:1;phase:0;"/>';

    $imprimir['cabecera'] .= '<table>'
                            .'<tr>'
                            .'<td>'
                            .'<font size="12">'.$datosTienda['NombreComercial'].'</font><br>'
                            .'<font size="9">'.$datosTienda['razonsocial'].'</font><br>'
                            .'<font size="9"><b>Direccion:</b>'.$datosTienda['direccion'].'</font><br>'
                            .'<font size="9"><b>NIF: </b>'.$datosTienda['nif'].'</font><br>'
                            .'<font size="9"><b>Teléfono: </b>'.$datosTienda['telefono'].'</font><br>'
                            .'</td>'
                            .'<td>'
                            .'<font size="9"><b>Datos de Cliente:</b></font><br>'
                            .'<font size="12">'.$datosCliente['Nombre'].'</font><br>'
                            .'<font size="9">'.$datosCliente['razonsocial'].'</font><br>'
                            .'<font size="9"><b>Direccion:</b>'.$direccion.'</font><br>'
                            .'<font size="9"><b>NIF: </b>'.$datosCliente['nif'].'</font><br>'
                            .'<font size="9"><b>Teléfono: </b>'.$datosCliente['telefono'].'</font><br>'
                            .'</td>'
                            .'</tr>'
                            .'</table>';

                            
    $imprimir['cabecera'] .=<<<EOD

<table WIDTH="100%" border="1px" ALIGN="center">
<tr>
<td WIDTH="5%"><font size="9"><b>Linea</b></font></td>
<td WIDTH="10%"><font size="9"><b>IdArticulo</b></font></td>
<td WIDTH="56%"><font size="9"><b>Descripción del producto</b></font></td>
<td WIDTH="8%"><b><font size="9">Cant.</font></b></td>
<td WIDTH="8%"><b><font size="9">Precio</font></b></td>
<td WIDTH="8%"><b><font size="9">Importe</font></b></td>
<td WIDTH="5%"><b><font size="9">IVA</font></b></td>
</tr>
</table>
EOD;
		$imprimir['html'].='<table>';
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
            $importe = $producto['pvpSiva']*$producto['nunidades'];
			$importe = number_format($importe,2);
    		$imprimir['html']   .='<tr>'
                                .'<td WIDTH="5%"><font size="8">'.$producto['nfila'].'</font></td>'
                                .'<td WIDTH="10%"><font size="8">&nbsp;&nbsp;'.$producto['idArticulo'].'</font></td>'
                                .'<td WIDTH="56%" ><font size="8">&nbsp;&nbsp;'.$producto['cdetalle'].'</font></td>'
                                .'<td ALIGN="right" WIDTH="8%"><font size="8">'.number_format($producto['nunidades'],2).' &nbsp;&nbsp;</font></td>'
                                .'<td ALIGN="right" WIDTH="8%"><font size="8">'.number_format($producto['pvpSiva'],2).' &nbsp;&nbsp;</font></td>'
                                .'<td ALIGN="right" WIDTH="8%"><font size="8">'.$importe.'</font></td>'
                                .'<td ALIGN="right" WIDTH="5%"><font size="8">'.number_format($producto['iva'],0).'% &nbsp;&nbsp;</font></td>'
                                .'</tr>';
		}
		$imprimir['html'].='</table>';
        $imprimir['html'] .=<<<EOD
<table WIDTH="70%" border="1px"><tr><th ALIGN="center">Tipo</th><th ALIGN="center">Base</th><th ALIGN="center">Importe de IVA</th></tr>
EOD;
	if (isset($Datostotales)){
		// Montamos ivas y bases
		foreach ($Datostotales['desglose'] as  $iva => $basesYivas){
            $base= number_format($basesYivas['base'],2);
            $importe_iva = number_format($basesYivas['iva'],2);
            $imprimir['html'].=<<<EOD
<tr><td ALIGN="right">$iva % &nbsp;</td><td ALIGN="right">$base &nbsp;</td><td ALIGN="right">$importe_iva &nbsp;</td></tr>
EOD;
		}
	}
	$imprimir['html'] .='</table>';
	$imprimir['html'] .='<p align="right"> TOTAL: ';
	$imprimir['html'] .=(isset($Datostotales['total']) ? '<font size="20">'.number_format($Datostotales['total'],2).'</font>' : '');
	$imprimir['html'] .='</p>';
	return $imprimir;
}

function prepararCaberaAdjuntoTemporal($adjunto,$dedonde){
    //@ Objetivo
    //  Preparar adjunto para añadir al temporal.
    //  Lo que hacemos es cambiar indices del array para que el adjunto sea estandar en todas las vistas.
    //  aparte añadimos o modificamos el estado, ya que el estado del adjunto son activo o eliminado, solamente.
    //@ Devolvemos
    //  adjunto si fue modificado, sino devolvemos array() vacio.
    $respuesta = array();
    if ($dedonde == 'factura'){
        $respuesta['id']        = $adjunto['id'];
        $respuesta['NumAdjunto']= $adjunto['NumalbCli'];
        $respuesta['fecha']     = $adjunto['Fecha'];
        $respuesta['total']     = $adjunto['total'];
        $respuesta['estado']    = "Activo";
    } else {
        $respuesta['id']        = $adjunto['id'];
        $respuesta['NumAdjunto']= $adjunto['Numpedcli'];
        $respuesta['fecha']     = $adjunto['Fecha'];
        $respuesta['total']     = $adjunto['total'];
        $respuesta['estado']    = "Activo";
    }
    return $respuesta;
}

function prepararAdjuntos($adjuntos,$dedonde,$accion='ver'){
    //@ Objetivo
    //  Preparar adjuntos para añadir al temporal.
    //  Lo que hacemos es cambiar indices del array para que el adjunto sea ESTANDAR en todas las vistas.
    //  aparte añadimos estado y nfila, esta ultima lo hacemos en esta funcion, ya que prepararCabeceraAdjuntoTemporal
    //  la utilizamos en BuscarAdjunto y el numerofila no lo podemos poner.
    //  Los estados de los adjuntos son activos o eliminados, solamente.
    //@ Devolvemos
    //  respuesta = array( html-> Que el html de los adjuntos
    //                      adjuntos-> array con los adjuntos);
    $respuesta = array('html' => '',
                       'adjuntos'=> array()
                       );
    $html_adjuntos ='';
    foreach ($adjuntos as $k=>$adjunto){
        // Ahora comprobamos que no esta preparado.
        if (!isset($adjunto['NumAdjunto'])){
            // Ya que enviamos a esta funcion tanto cuando es factura como temporal que este ya esta preparado
            $adjunto = prepararCaberaAdjuntoTemporal($adjunto,$dedonde);
        }
        $adjunto['nfila'] =intval($k)+1; // Sumamos uno ya que empieza en 0
        array_push($respuesta['adjuntos'],$adjunto);
        $html_adjuntos .= htmlLineaAdjunto($adjunto, $dedonde,$accion);
    }
    $respuesta['html'] = $html_adjuntos;
    return $respuesta;
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
			$bandera=number_format(($product->iva/100),2,'.','');
            $importe=number_format($product->importe,2,'.','');
			// Ahora calculmos bases por ivas
			// Ahora calculamos base y iva 
			if (!isset($desglose[$product->iva])){
                $desglose[$product->iva]['base'] = $importe;
                $desglose[$product->iva]['iva'] =  $importe*$bandera;
			}else{
                $desglose[$product->iva]['base'] = $desglose[$product->iva]['base'] + ($importe);
                $desglose[$product->iva]['iva'] = $desglose[$product->iva]['iva'] + ($importe * $bandera);
			}
                $desglose[$product->iva]['BaseYiva'] =$desglose[$product->iva]['base']+$desglose[$product->iva]['iva'];
		}
	}
    
	foreach($desglose as $tipoIva=>$basesYivas){
        $desglose[$tipoIva]['iva']      = number_format(round($basesYivas['iva'],2),2,'.','');
        $desglose[$tipoIva]['BaseYiva'] = number_format(round($basesYivas['BaseYiva'],2),2,'.','');
		$subivas += $desglose[$tipoIva]['iva'];
		$subtotal+= $basesYivas['BaseYiva'];
	}
	
	$respuesta['desglose'] = $desglose;
	$respuesta['subivas']=number_format($subivas,2,'.','');
	$respuesta['total'] = number_format($subtotal,2,'.','');
	return $respuesta;
}
?>
