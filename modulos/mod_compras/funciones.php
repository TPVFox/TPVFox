<?php 
include_once './../../inicial.php';
include_once $URLCom.'/configuracion.php';
include_once $URLCom.'/clases/FormasPago.php';
include_once $URLCom.'/clases/articulos.php';
//~ include_once $URLCom.'/clases/ClaseTablaTienda.php';
include_once $URLCom.'/modulos/mod_tienda/clases/ClaseTienda.php';
include_once $URLCom.'/clases/imprimir.php';



function htmlProveedores($busqueda,$dedonde, $idcaja, $proveedores = array()){
	// @ Objetivo:
	// Montar el hmtl para mostrar con los proveeodr si los hubiera.
	// @ parametros:
	// 		$busqueda -> El valor a buscar,aunque puede venir vacio.. 
	//		$dedonde  -> Nos indica de donde viene. ()
	$resultado = array();
	$resultado['encontrados'] = count($proveedores);
	$resultado['html'] = '<label>Busqueda Proveedor en '.$dedonde.'</label>'
                        .'<input id="cajaBusquedaproveedor" name="valorproveedor" placeholder="Buscar"'
                        .'size="13" data-obj="cajaBusquedaproveedor" value="'.$busqueda
                        .'" onkeydown="controlEventos(event)" type="text">';
				
	if (count($proveedores)>10){
		$resultado['html'] .= '<span> Se muestra 10 proveedores de '.count($proveedores).'</span>';
	}
	$resultado['html'] .= '<table class="table table-striped"><thead>'
	. ' <th></th> <th>Nombre</th><th>Razon social</th><th>NIF</th></thead><tbody>';
	if (count($proveedores)>0){
		$contador_inactivo = 0 ;
		foreach ($proveedores as $key=>$proveedor){  
			$clase_inactiva = '';
			if ($proveedor['estado']=='inactivo'){
				$clase_inactiva = ' danger';
				$contador_inactivo++;
			}
			$resultado['html'] .= '<tr id="Fila_'.$key
                                .'" class="FilaModal'
								.$clase_inactiva.'" onclick="buscarProveedor('."'".$dedonde."'".' , '
                                ."'id_proveedor'".', '.$proveedor['idProveedor'].', '."'popup'".');" >'
                                .'<td id="C'.$key.'_Lin" >'
                                .'<input id="N_'
                                .$key.'" name="filaproveedor" '
                                .'data-obj="idN" onkeydown="controlEventos(event)" type="image"  alt="">'
                                . '<span  class="glyphicon glyphicon-plus-sign agregar"></span></td>'
                                . '<td>'.htmlspecialchars($proveedor['nombrecomercial'],ENT_QUOTES).'</td>'
                                . '<td>'.htmlentities($proveedor['razonsocial'],ENT_QUOTES).'</td>'
                                . '<td>'.$proveedor['nif'].'</td>'
                                .'</tr>';
			if ($key === 10){
                // Solo mostramos 10 como máximo.
				break;
			}
		}
        if ($contador_inactivo>0){
        $resultado['html'] .=	' <div class="alert alert-danger">'
								.'Recuerda que los proveedores INACTIVOS están Rojo, no se puede añadir</div> ';
		}
	} else {
        // No se encontro nada con esa busqueda.
        $resultado['html'] .=' <div class="alert alert-warning">No se encontro ningun proveedor, para esa busqueda</div> ';
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
			 at.crefTienda ,p.`crefProveedor` as ref_prov, p.coste, p.fechaActualizacion,  a.`iva` , a.estado as estadoTabla'
			.' FROM `articulos` AS a LEFT JOIN `articulosCodigoBarras` AS ac '
			.' ON a.idArticulo = ac.idArticulo '
			.'  LEFT JOIN `articulosTiendas` '
			.' AS at ON a.idArticulo = at.idArticulo AND at.idTienda =1 left join articulosProveedores 
			as p on a.idArticulo=p.`idArticulo` and p.idProveedor='.$idProveedor.' WHERE '
			.$buscar.' group by  a.idArticulo LIMIT 0 , 30 ';
        $resultado['sql'] = $sql;
        $res = $BDTpv->query($sql);
        $resultado['Nitems']=0;
        if (isset($res->num_rows)){
            $resultado['Nitems']= $res->num_rows;
        }
        // "$i" es el contador busquedas, ya podemos buscar de varias formas, identico o like.
        if ($i === 0){
            // Es la primera busqueda ( es decir puede ser la identico, no volvemos a buscar. )
            if (isset($res->num_rows) && $res->num_rows > 0){
                $resultado['Estado'] = 'Correcto';
                break;
            }
        }
		if (mysqli_error($BDTpv)){
            //compruebo error en consulta
			$resultado['consulta'] = $sql;
			$resultado['error'] = $BDTpv->error_list;
            // Volvemo y salimos de bucle ya que hubo un error.
			return $resultado;
		} 
		$i++;
	}	
	if (isset($res->num_rows) && $res->num_rows > 0){
        //fetch_assoc es un boleano..
		while ($fila = $res->fetch_assoc()) {
			$products[] = $fila;
			$resultado['datos']=$products;
		}
		if ($res->num_rows > 1){
            //si hay muchos resultados y si es mas de 1, mostrara un listado
			$resultado['Estado'] = 'Listado';
		} else {
            // Hay un solo resultado.
			$fecha=$resultado['datos'][0]['fechaActualizacion'];
			if($fecha!=null){
				$fecha =date_format(date_create($fecha), 'd-m-Y');
				$resultado['datos'][0]['fechaActualizacion']=$fecha;
			}
        }
	} else { 
		$resultado['Estado'] = 'Noexiste';
	}
	return $resultado;
}

function htmlProductos($productos,$id_input,$campoAbuscar,$busqueda, $dedonde){
	// @ Objetivo 
	// Obtener listado de produtos despues de busqueda.
	$resultado = array();
	$resultado['encontrados'] = count($productos);
    $html = '';
    $html = "<script type='text/javascript'>
			// Ahora debemos añadir parametro campo a objeto de cajaBusquedaProductos".
			"cajaBusquedaproductos.parametros.campo.__defineSetter__ ="."'".$campoAbuscar."';
			idN.parametros.campo.__defineSetter__ ="."'".$campoAbuscar."';
			</script>";
	$html .= '<label>Busqueda por '.$id_input.'</label>'.
            '<input id="cajaBusqueda" name="'.$id_input.'" placeholder="Buscar" 
			data-obj="cajaBusquedaproductos" size="13" value="'
			.$busqueda.'" onkeydown="controlEventos(event)" type="text">';
	if (count($productos)>10){
		$html .= '<span>10 productos de '.count($productos).'</span>';
	}
	if ($resultado['encontrados'] === 0){
			// Hay que tener en cuenta tambien si la caja tiene datos ya que sino no es lo mismo.
			if (strlen($busqueda) === 0 ) {
				// Si no encontro resultados, entonces debemos porne una alert y incluso sonorá era guay...
				$html .= '<div class="alert alert-info">'.
                        ' <strong>Buscar!</strong> Pon las palabras para buscar productos que consideres.</div>';
			} else {
				// Si no encontro resultados, entonces debemos porne una alert y incluso sonorá era guay...
				$html .= '<div class="alert alert-warning">'.
				' <strong>Error!</strong> No se encontrado nada con esa busqueda.</div>';
			}
	} else {
		$html .= '<table class="table table-striped"><thead><th></th></thead><tbody>';
		$contad = 0;
		foreach ($productos as $producto){
            $style="";
			$datos = 	"'".$id_input."',".
						"'".addslashes(htmlspecialchars($producto['crefTienda'],ENT_COMPAT))."','"
						.addslashes(htmlentities($producto['articulo_name'],ENT_COMPAT))."','"
						.number_format($producto['iva'],2)."','".$producto['codBarras']."','"
						.$producto['ultimoCoste']."',".$producto['idArticulo'].", '".$dedonde."' , ".
						"'".addslashes(htmlspecialchars($producto['ref_prov'],ENT_COMPAT))."' , '".$producto['coste']."'";
			if(strlen($producto['ref_prov'])==0){
                $style='style="opacity:0.5;"';
            }
            if($producto['estadoTabla']=="Baja"){
                $style='style="background-color:#f5b7b1;"';
                $onclick="";
            }else{
                $onclick='onclick="escribirProductoSeleccionado('.$datos.');"';
            }
            $html .= '<tr id="Fila_'.$contad.'" '. $style.' class="FilaModal" '.$onclick.
                     ' >'.
                     '<td id="C'.$contad.'_Lin" ><input id="N_'.$contad.
                     '" name="filaproducto" data-obj="idN" 	onkeydown="controlEventos(event)" '.
                     ' type="image"  alt=""><span class="glyphicon glyphicon-plus-sign agregar"></span></td>';
			if ($id_input=="ReferenciaPro"){
				$html .= '<td>'.htmlspecialchars($producto['ref_prov'], ENT_QUOTES).'</td>';	
			}else{
				$html .= '<td>'.htmlspecialchars($producto['crefTienda'], ENT_QUOTES).'</td>';	
			}		
            if(strlen($producto['coste'])==0){
                $style='style="opacity:0.5;"';
            }
            
			$html   .= '<td>'.htmlspecialchars($producto['articulo_name'], ENT_QUOTES).'</td>'
                    . '<td '.$style.'>'.$producto['ultimoCoste'].'</td>'
                    . '<td '.$style.'>'.number_format ($producto['coste'],2, '.', '').'</td>'
                    
                    . '</tr>';
			$contad = $contad +1;
			if ($contad === 10){
				break;
			}
		}
		$resultado['html'] =$html.'</tbody></table>';
	}
	$resultado['campo'] = $campoAbuscar;
	return $resultado;		
}

function recalculoTotales($productos,$campo_estado = 'estado') {
    // === Funcion ya creada en claseCompras =====
    // Pendiente cambiarlo en facturas para eliminarlo.
    
	// @ Objetivo recalcular los totales y desglose del ticket
	// @ Parametro:
	// 	$productos (array) de objetos.
    //  $campo_estado -> (string) por compatibilidad de versiones anteriores
	$respuesta = array();
	$desglose = array();
	$subivas = 0;
	$subtotal = 0;
    foreach ($productos as $product){
        // Comprobamos que producto es un objeto
        if ( gettype($product) !== 'object' ){
            // Por compatibilidad con versiones anteriores
            $product = (object)$product;
        }
		// Si la linea esta eliminada, no se pone.
        if ($product->$campo_estado === 'Activo'){
			$b=$product->iva/100;
            if (!isset($product->importe)){
                // Por comtabilidad con versiones anterires.
                $importe = $product->ncant*$product->ultimoCoste;
            } else {
                $importe= $product->importe;
            }
			if (isset($desglose[$product->iva])){
			$desglose[$product->iva]['base'] = number_format($desglose[$product->iva]['base'] + $importe,3, '.', '');
			$desglose[$product->iva]['iva'] = number_format($desglose[$product->iva]['iva']+ ($importe*$b),3, '.', '');
			}else{
			$desglose[$product->iva]['base'] = number_format((float)$importe,3, '.', '');
			$desglose[$product->iva]['iva'] =number_format((float)$importe*$b, 3, '.', '');
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

function htmlLineaProducto($producto, $dedonde,$solo_lectura=''){
        //@ Objetivo:
        // Objetivo montar el html de la linea de los productos tanto para pedido, albaran y factura
        //@ Parametros
        // $solo_lectura = No es obligatorio, y si vienes es readonly
        $respuesta=array('html'=>'');
        if(!is_array($producto)) {
            // Comprobamos si product es objeto lo convertimos en array.
            $producto = (array)$producto;
        } 
        // Valores por defecto o calculo.
        $producto['ultimoCoste'] = floatval($producto['ultimoCoste']);
        $codBarra="";
        $cant=number_format($producto['nunidades'],2);
        $importe=$producto['ultimoCoste']*$producto['nunidades'];	
        $importe = number_format($importe,2);
        $importeIva=number_format(( $importe ) + ( $importe * ( $producto['iva'] / 100 ) ),2);
        $classtr = '';
        $estadoInput = '';
        $funcOnclick = ' eliminarFila('.$producto['nfila'].' , '."'".$dedonde."'".');';
        $iconE_R = '<span class="glyphicon glyphicon-trash"></span>';
        $html_numeroDoc=''; // Valor por defecto.
        $coste= number_format($producto['ultimoCoste'], 4); 
        $html_coste = $coste;
        // Si hay valor de ccodbar lo ponemos en variable.
        if (isset ($producto['ccodbar'])){
            if ($producto['ccodbar']>0){
                $codBarra=$producto['ccodbar'];
            }
        }
        // Si el estado es activo lo muestra normal con el boton de eleminar producto si no la linea esta desactivada con el botón de retornar
        if ($producto['estado'] !=='Activo'){
            $classtr = ' class="tachado" ';
            $estadoInput = 'disabled';
            $funcOnclick = ' retornarFila('.$producto['nfila'].', '."'".$dedonde."'".');';
            $iconE_R = '<span class="glyphicon glyphicon-export"></span>';
        } 
        if ($solo_lectura ==='') {
            // Si es solo lectura no se muestra , para evitar que lo elimine
            $btnELiminar_Retornar= '<td class="eliminar"><a onclick="'.$funcOnclick.'">'.$iconE_R.'</a></td>';
        } else {
            $btnELiminar_Retornar='<td></td>';
        }
        if ($dedonde =="albaran" || $dedonde=="factura"){
            // En albaran y factura se puede cambiar el coste.
            // Ademas se tiene que obtener numdocumento, ya que pudieron ser añadido los productos
            // con un adjunto.
            $html_coste  ='<input type="text" id="ultimo_coste_'.$producto['nfila']
                    .'" data-obj="ultimo_coste" onkeydown="controlEventos(event)"'
                    .' name="ultimo[]" onBlur="controlEventos(event)" value="'.$coste.'" '.$solo_lectura.' size="4">';
            
            // Ahora montamos td de numDoc
            $numeroDoc = '';
            // El array de producto, puede traer los dos campos: NumpedPro o Numalbpro
            // por eso se comprueba dedonde.
            if ($dedonde =="albaran"){
                if (isset($producto['idpedpro']) && $producto['idpedpro']>0){
                    // Si obtuvo con metodo de la clase AlbCompra
                    $numeroDoc= $producto['idpedpro'];
                } 
            }
            if ( $dedonde=="factura") {
                if (isset($producto['Numalbpro']) && $producto['Numalbpro']>0 ){
                    $numeroDoc= $producto['Numalbpro'];
                }
                if (isset($producto['numAlbaran']) && $producto['numAlbaran'] > 0){
                    $numeroDoc= $producto['numAlbaran'];
                }
            }
            $html_numeroDoc='<td class="Ndocumento">'.$numeroDoc.'</td>';
        } 
        // ================== Montamos td de referencia de filaProveedor ========================
        // Montamos td de referencia proveedor,
        // Es input, que puede ser solo lectura si $solo_lectura = readonly y no montamos btn_ref_prov ( esto es estado ver)
        // Montamos btn_ref_prov siempre que $solo_lectura esta vacio, aunque no lo mostramos siempre.
        // Solo mostramos btn_ref_prov cuando contienes datos $producto[ref_prov], asi identificamos aquellos que metimos nuevos en ese momento.
        $btn_ref_prov = '';
        $displayRefProv = 'display:none'; // Por defecto si no existe.
        $ref_prov = 'value="" placeholder="ref"'; // Por defecto si no existe.
        if( isset ($producto['ref_prov'])){
            // Existe -- Ahora compruebo si tiene datos.
            if (strlen($producto['ref_prov']) > 0){
                $displayRefProv = 'text-align: right'; // Para mostrar btn_ref_prov
                $ref_prov = 'value="'.$producto['ref_prov'].'"';
            }
        }
        if ($solo_lectura ===''){
            // Montamos btn_ref_prov referencia proveedor.
            $btn_ref_prov= '<a onclick="permitirModificarReferenciaProveedor('."'".'Proveedor_Fila_'
                            .$producto['nfila']."'".')" style="'.$displayRefProv.'" id="enlaceCambio'
                            .$producto['nfila'].'">'
                            .'<span class="glyphicon glyphicon-cog"></span>'
                            .'</a>';
        }
        $filaProveedor ='<td><input id="Proveedor_Fila_'
                        .$producto['nfila'].'" type="text" data-obj="Proveedor_Fila" '
                        .'name="proveedor_fila[]" '.$ref_prov.' size="7"  onkeydown="controlEventos(event)" '
                        .'onBlur="controlEventos(event)"'.$solo_lectura.'>'.$btn_ref_prov;
        $filaProveedor .= '</td>';
        // =================  FIN de montar td de referencia de filaProveedor  ==============================
        
        $respuesta['html'] .='<tr id="Row'.($producto['nfila']).'" '.$classtr.'>'
                            .'<td class="linea">'.$producto['nfila'].'</td>'
                            . $html_numeroDoc
                            .'<td class="idArticulo">'.$producto['idArticulo'].'</td>'
                            .'<td class="referencia">'.$producto['cref'].'</td>'.$filaProveedor
                            .'<td class="codbarras">'.$codBarra.'</td>'
                            .'<td class="detalle">'.$producto['cdetalle'].'</td>'
                            .'<td><input class="unidad" id="Unidad_Fila_'.$producto['nfila']
                            .'" type="text" data-obj="Unidad_Fila"  '
                            .' pattern="[-+]?[0-9]*[.]?[0-9]+" name="unidad[]" placeholder="unidad"'
                            .' size="3"  value="'.$cant.'" '.$solo_lectura.' '
                            .$estadoInput.' onkeydown="controlEventos(event)" '
                            .' onBlur="controlEventos(event)"/></td>'
                            .'<td class="pvp">'.$html_coste.'</td>'
                            .'<td class="tipoiva">'.$producto['iva'].'%</td>'
                            .'<td  id="N'.$producto['nfila'].'_Importe" class="importe" >'
                            .$importe.'</td>'
                            .'<td class="ImporteIva" id="N'.$producto['nfila'].'_ImporteIva">'.$importeIva.'</td>'
                            . $btnELiminar_Retornar.'</tr>';
                        
        $respuesta['productos']=$producto;
	 return $respuesta;
}

function modificarArrayProductos($productos){
	//@Objetivo:
	// Modificar el array de productos para poder trabajar en facturas , pedidos y albaranes
	$respuesta=array();
	foreach ($productos as $producto){
        $pro = array(   'ccodbar'       =>$producto['ccodbar'],
                        'cdetalle'      =>$producto['cdetalle'],
                        'cref'          =>$producto['cref'],
                        'ref_prov'      =>$producto['ref_prov'],
                        'estado'        =>$producto['estadoLinea'],
                        'idArticulo'    =>$producto['idArticulo'],
                        'importe'       =>$producto['costeSiva']*$producto['nunidades'],
                        'iva'           =>$producto['iva'],
                        'ncant'         =>$producto['ncant'],
                        'nfila'         =>$producto['nfila'],
                        'nunidades'     =>$producto['nunidades'],
                        'ultimoCoste'   =>$producto['costeSiva']
                    );
        if (isset($producto['Numpedpro'])){
			$pro['numPedido']=$producto['Numpedpro'];
		}
		if (isset ($producto['Numalbpro'])){
			$pro['numAlbaran']=$producto['Numalbpro'];
		}
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
        $fecha = date_create($adjunto['Fecha']);
        if ($dedonde=="albaran"){
			$numAdjunto=$adjunto['Numpedpro'];
		}else{
			$numAdjunto=$adjunto['Numalbpro'];
		}
        $fecha=date_format($fecha, 'Y-m-d');
		$respuesta['html'] 	.= '<tr id="Fila_'.$contad.'" class="FilaModal" onclick="buscarAdjunto('
		."'".$dedonde."'".', '.$numAdjunto.');">';
		
		$respuesta['html'] 	.= '<td id="C'.$contad.'_Lin" ><input id="N_'.$contad
		.'" name="filaproducto" data-obj="idN" onkeydown="controlEventos(event)"
		 type="image"  alt=""><span  class="glyphicon glyphicon-plus-sign agregar"></span></td>';
		$respuesta['html']	.= '<td>'.$numAdjunto.'</td><td>'.$fecha.'</td>';
		if ($dedonde=="factura"){
            $fechaVenci="";
            $textformaPago="";
			if(isset($adjunto['FechaVencimiento'])){
				if ($adjunto['FechaVencimiento']!="0000-00-00"){
					$fechaVenci=$adjunto['FechaVencimiento'];
				}
			}
			if ($adjunto['formaPago']){
				$formasPago=new FormasPago($BDTpv);
				$datosFormaPago=$formasPago->datosPrincipal($adjunto['formaPago']);
				$textformaPago=$datosFormaPago['descripcion'];
			}
			$respuesta['html']	.= '<td>'.$fechaVenci.'</td><td>'.$textformaPago.'</td>';
            $respuesta['html'].=ControladorComun::insertTd($adjunto['Su_numero']);
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

function modalSelectEstadoAlbaranes(){
    // @Objetivo:
    // Obtener los posibles estados de albaranes compra
     $html="";
    $html.=' <div>
            <label>Nuevo estado para albaranes: </label>
            <select id="Nuevo_estado_albaranes" class="estados">
                <option value="0"></option>
                <option value="Guardado">Guardado</option>
                <option value="Facturado">Facturado</option>
            </select>
            <button class="btn btn-primary" onclick="cambiarEstadoVariosAlbaranes()">Cambiar</button>
            </div>';
        return $html;
}


function lineaAdjunto($adjunto, $dedonde,$accion ='editar'){
	//@Objetivo:
	//Retornar el html de la linea de adjuntos(esto puede ser un pedido en albarán o un albarán en factura).
	//@Parametros:
	//adjunto: los datos del albarán o pedido a adjuntar
	//dedonde: de donde venimos si de albarán o de factura
    //accion : que puede hacer.
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
            $a = (isset($adjunto['Su_numero']))? $adjunto['Su_numero'] : '';
            $respuesta['html'].='<td>'.$a.'</td>';
		}
		$date=date_create($adjunto['fecha']);
		$fecha=date_format($date,'d-m-Y');
        $totalSiva = (isset($adjunto['totalSiva']))? $adjunto['totalSiva'] : '0.00';
		$respuesta['html'] .='<td>'.$fecha.'</td>'.
                             '<td>'.number_format ($totalSiva,2, '.', '').'</td>'.
                            '<td>'.$adjunto['total'].'</td>';
        
        if ($accion !=='ver'){
            $respuesta['html'].=$btnELiminar_Retornar;
        }
        $respuesta['html'].='</tr>';
	}
	return $respuesta;
}

function modificarArrayAdjunto($adjuntos, $BDTpv, $dedonde){
	$respuesta=array();
	$i=1;
    $res= array();
    foreach ($adjuntos as $adjunto){
        if ($dedonde =="albaran"){
            $res['NumAdjunto']=$adjunto['numPedido'];
            $datosAdjunto=$BDTpv->query('SELECT * FROM pedprot WHERE id= '.$adjunto['idPedido'] );
        }else{
            $res['NumAdjunto']=$adjunto['numAlbaran'];
            $datosAdjunto=$BDTpv->query('SELECT a.Su_numero, a.Numalbpro , a.Fecha , a.total,
            a.id , a.FechaVencimiento, a.idProveedor , a.formaPago , sum(b.totalbase) as 
            totalSiva FROM albprot as a INNER JOIN albproIva as b on a.
            `id`=b.idalbpro where a.Numalbpro='.$adjunto['idAlbaran'].' GROUP by a.id ');
        }

        while ($fila = $datosAdjunto->fetch_assoc()) {
            $adj = $fila;
            $res['idAdjunto']=$adj['id'];
            $res['idPePro']=$adj['idProveedor'];
            $res['total']=$adj['total'];
            $res['fecha']=$adj['Fecha'];

            if ($dedonde !== "albaran"){
                $res['totalSiva']=$adj['totalSiva'];
                $res['Su_numero']=$adj['Su_numero'];
            }
        }
        $res['estado']="activo";
        $res['nfila']=$i;
        array_push($respuesta,$res);
        $i++;
	}
	return $respuesta;
}

function generarPDFTemporal($dedonde, $id, $BDTpv, $idTienda, $rutatmp, $URLCom){

	$nombreTmp=$dedonde."compras.pdf";
	$htmlImprimir=montarHTMLimprimir($id, $BDTpv, $dedonde, $idTienda);
	$cabecera=$htmlImprimir['cabecera'];
	$margen_top_caja_texto= 56;
	$html=$htmlImprimir['html'];
	include_once $URLCom.'/controllers/planImprimir.php';
	
	$ficheroCompleto=$rutatmp.'/'.$nombreTmp;

	return $ficheroCompleto;
}

function htmlFormularioEmail($destinatario,$conf_email,$id,$dedonde,$idTienda){
    $html = '   <div class="row">'.
            '        <div class="col-md-12">'.
            '            <div class="well well-sm">'.
            '                 <form class="form-horizontal" id="FormEmail"  method="post">'.
            '                    <fieldset>'.
            '                        <div class="form-group">'.
            '                            <span class="col-md-1 col-md-offset-2 text-center"><i class="glyphicon glyphicon-user"></i></span>'.
            '                            <div class="col-md-8">'.
            '                                <input id="email" name="destinatario" type="text" class="form-control" value ="'.$destinatario.'" readonly>'.
            '                            </div>'.
            '                        </div>'.
            '                        <div class="form-group">'.
            '                            <span class="col-md-1 col-md-offset-2 text-center"><i class="glyphicon glyphicon-pencil"></i></span>'.
            '                            <div class="col-md-12">'.
            '                                <input id="asunto" name="asunto" type="text"  class="form-control" value="'.$conf_email['asunto'].'">'.
            '                            </div>'.
            '                        </div>'.
            '                        <div class="form-group">'.
            '                            <span class="col-md-1 col-md-offset-2 text-center"><i class="glyphicon glyphicon-pencil"></i></span>'.
            '                            <div class="col-md-12">'.
            '                                <textarea class="form-control" id="message" name="message" placeholder="Enter your massage for us here. We will get back to you within 2 business days." rows="7">'.$conf_email['body'].'</textarea>'.
            '                            </div>'.
            '                       </div>'.
            '                        <div class="form-group">'.
            '                            <div class="col-md-12 text-center">'.
            '                               <input name="id" type="hidden" value="'.$id.'">'.
            '                               <input name="dedonde" type="hidden" value="'.$dedonde.'">'.
            '                               <input name="idTienda" type="hidden" value="'.$idTienda.'">'.
            '                               <input name="pulsado" type="hidden" value="enviarEmail">'.
            '                               <input type="button" class="btn btn-primary btn-lg" id="botonenviar" onclick="enviarCorreo(this)"  value="Enviar">'.
            '                            </div>'.
            '                        </div>'.
            '                    </fieldset>'.
            '                </form>'.
            '            </div>'.
            '        </div>'.
            '   </div>';
    return $html;


}

function comprobarAlbaran($idProveedor, $BDTpv){
	$Calb=new AlbaranesCompras($BDTpv);
	$estado="Guardado";
    $bandera=0;
	$con=$Calb->albaranesProveedorGuardado($idProveedor, $estado);
	if (count($con)>0){
		$bandera=1;
	}
	return $bandera;
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
		.'<td>'.number_format($totalBase,2).'</td>'
		.'<td>'.number_format($totaliva,2).'</td>'
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
	//@ Objetivo:
    // Eliminar el albarán temporal
    // Si tiene algún pedido adjunto cambiarle el estado a "Guardado"
	//@ Parametros:
	//$idTemporal: envío los datos de get
    //@ Devolvemos :
    // errores si hubo algun error al cancelarAlbaran temporal.
    
	$CAlb=new AlbaranesCompras($BDTpv);
	$Cped = new PedidosCompras($BDTpv);
	$error=array();
	$idAlbaran=0;
	if ($idTemporal>0){
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

function htmlSelectConfiguracionSalto(){
    $html = '<select  title="Escoje casilla de salto" id="salto" name="salto">'
                .'<option value="0">Seleccionar</option>'
                .'<option value="1">Id Articulo</option>'
                .'<option value="2">Referencia</option>'
                .'<option value="3">Referencia Proveedor</option>'
                .'<option value="4">Cod Barras</option>'
                .'<option value="5">Descripción</option>'
            .'</select>';
    return $html;
}

function htmlFormasVenci($formaVenci, $BDTpv){
	$html="";
	$formasPago=new FormasPago($BDTpv);
	$principal=$formasPago->datosPrincipal(intval ($formaVenci));
    if ( gettype($principal) ==='array') {
        $html.='<option value="'.$principal['id'].'">'.$principal['descripcion'].'</option>';
    } else {
      	$html.='<option value=""></option>';
    }
    
	$otras=$formasPago->formadePagoSinPrincipal(intval ($formaVenci));
	foreach ($otras as $otra){
		$html.='<option value= "'.$otra['id'].'">'.$otra['descripcion'].'</option>';
    }
	$respuesta['formas']=$formaVenci;
	$respuesta['html']=$html;
	return $respuesta;
}

function modificarArraysImportes($importes, $total){
	$importesDef= array();
	foreach ($importes as $importe){
        $imp=floatval($importe['importe']);
		$total=$total-$imp;
        $total=number_format((float)$total,2, '.', '');
		$nuevo= array(
                    'importe'   =>$importe['importe'],
                    'fecha'     =>$importe['FechaPago'],
                    'referencia'=>$importe['Referencia'],
                    'forma'     =>$importe['idFormasPago'],
                    'pendiente' =>$total
            );
		array_push($importesDef, $nuevo);
	}
	return $importesDef;
}


function htmlDatosAdjuntoProductos($datos,$dedonde){
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
    if ($dedonde === 'albaran'){
        $n_adjunto = '<strong>NºPedido:';
    } else {
        $n_adjunto = '<strong>NºAlbaran:';
    }
    $n_adjunto .= $datos['NumAdjunto'].'</strong>';
        $respuesta='<tr class="success">
            <td colspan="2">'.$n_adjunto .'</td>
            <td colspan="3"><strong>Su número:'.$suNumero.'</strong></td>
            <td colspan="2"><strong>Fecha:'.$datos['fecha'].'</strong></td>
            <td colspan="2"><strong>Total sin IVA:'.$totalSiva.'</strong></td>
            <td><strong>Total con IVA:'.$total.'</strong></td>
            </tr>';
	return $respuesta;
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
		$html.=<<<EOD
<div class="col-md-12"><h4>Incidencia:</h4><div class="col-md-6"><label>Fecha:</label>
<input type="date" name="inci_fecha" id="inci_fecha" value="$dato[fecha_creacion]" readonly=""></div>
<div class="col-md-6"><label>Dedonde:</label>
<input type="text" name="inci_dedonde" id="inci_dedonde" value="$dato[dedonde]" readonly=""></div></div>
<div class="col-md-12"><div class="col-md-6"><label>Estado:</label>
<input type="text" name="estado" id="estado" value="$dato[estado]" readonly=""></div>
<div class="col-md-6"><label>Usuario:</label>
<input type="text" name="usuario" id="usuario" value="$dato[id_usuario]" readonly=""></div></div>
<div class="col-md-12"><div class="col-md-6"><label>Datos:</label>
<textarea rows="4" cols="20" readonly> $dato[datos]</textarea>'</div>
<div class="col-md-6"><label>Mensaje:</label>
<textarea rows="4" cols="20" readonly> $dato[mensaje]</textarea>
</div></div>
EOD;
					
	}
	return $html;
}

?>
