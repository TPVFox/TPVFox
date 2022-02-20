<?php 
function htmlLineaFamilias($item, $idProducto, $familia=''){
	// @Objetivo:
	// Montar linea de codbarras , para añadir o para modificar.
	$nuevaFila = '<tr>'
				. '<td><input type="hidden" id="idFamilias_'.$familia['idFamilia']
				.'" name="idFamilias_'.$familia['idFamilia'].'" value="'.$familia['idFamilia'].'">'
				.$familia['idFamilia'].'</td>'
				.'<td>'.$familia['familiaNombre'].'</td>'
				.'<td><a id="eliminar_'.$familia['idFamilia']
				.'" class="glyphicon glyphicon-trash" onclick="eliminarFamiliaProducto(this)"></a>'
				.'</td>'.'</tr>';
	return $nuevaFila;
}



function htmlLineaCodigoBarras($item,$codBarras=''){
	// @Objetivo:
	// Montar linea de codbarras , para añadir o para modificar.
	$nuevaFila = '<tr>'
				.'<td><input data-obj="cajaCodBarras" type="text" id="codBarras_'
				.$item.'" name="codBarras_'.$item.'" value="'.$codBarras.'" onkeydown="controlEventos(event)"></td>'
				.'<td><a id="eliminar_'.$item
				.'" class="glyphicon glyphicon-trash" onclick="eliminarCodBarras(this)"></a></td>'
				.'</tr>';
	return $nuevaFila;
}

function htmlLineaProveedorCoste($proveedor,$borrar_ref_prov='KO'){
	// @ Objetivo:
	// Montar linea de proveedores_coste, para añadir o para modificar.
	// @ Parametros :
	// 		$item -> (int) Numero item
	// 		$proveedor-> (array) Datos de proveedor: idProveedor,crefProveedor,coste,fechaActualizacion,estado,nombrecomercial,razonsocial.
	
	// Montamos campos ocultos de IDProveedor
	$camposIdProveedor = '<input class="idProveedor" type="hidden" name="idProveedor_'.$proveedor['idProveedor'].'" id="idProveedor_'.$proveedor['idProveedor'].'" value="'.$proveedor['idProveedor'].'">';
	$nom_proveedor = $proveedor['idProveedor'].'.-';
	// Monstamos nombre y razon social juntas
	if ($proveedor['nombrecomercial'] !== $proveedor['razonsocial']){
		$nom_proveedor .= $proveedor['razonsocial'].'-'.$proveedor['nombrecomercial'];
	} else {
		$nom_proveedor .= $proveedor['razonsocial'];
	}
	$atributos = ' name="check_pro" '; // Los check ponemos el mismo nombre ya solo podemos devolver uno como principal
	
	if (isset($proveedor['principal'])){
		if ($proveedor['principal'] ==='Si'){
			// Ponemos check y readonly y ponemos onclick="return false; asi no permite cambiar.. :-)
			// [OJO] -> readonly deja cambiar el check igualmente..
			$atributos .= 'readonly onclick="return false;" checked="true"';
		} 
	}else {
		$atributos .= ' disabled';
	}
	if (!isset($proveedor['crefProveedor'])){
		$proveedor['crefProveedor'] = '';
	}
	$style_color = '';
	if (isset($proveedor['ultimo_pro'])){
		$style_color = ' style="color:red;" ';
	}
	$nuevaFila = '<tr>'
				.'<td><input '.$atributos.' type="checkbox" id="check_pro_'
				.$proveedor['idProveedor'].'" value="'.$proveedor['idProveedor'].'"></td>'
				.'<td width="45%">'
				.'<small>'.$camposIdProveedor.$nom_proveedor.'</small>'
				.'</td>'
				.'<td>'
				.'<input type="text" size="10" name="prov_cref_'.$proveedor['idProveedor'].'" id="prov_cref_'
				.$proveedor['idProveedor'].'" value="'.$proveedor['crefProveedor'].'" readonly>'
				.'</td>'
				.'<td>'
				.'<input type="text" size="8" name="prov_coste_'.$proveedor['idProveedor']
				.'" pattern="[-+]?[0-9]*[.]?[0-9]+" data-obj= "cajaCosteProv" id="prov_coste_'
				.$proveedor['idProveedor'].'" value="'.$proveedor['coste'].'" readonly>'
				.'</td>'
				.'<td>'
				.'<span class="glyphicon glyphicon-calendar" title="'.$proveedor['estado'].' :'
				.$proveedor['fechaActualizacion'].'" '.$style_color.'>'.'</span>'
				.'<a style="padding:0 10px;"  id="desActivarProv_'.$proveedor['idProveedor']
				.'" class="glyphicon glyphicon-cog" onclick="desActivarCajasProveedor(this)"></a>';
    if ($borrar_ref_prov =='Ok'){
            $nuevaFila .= '<a id="eliminarRefProv_'.$proveedor['idProveedor']
                        .'" class="glyphicon glyphicon-trash" onclick="EliminarRefProveedor(this)"></a>';
    }
    $nuevaFila .= '</td>'
				.'</tr>';
					
	return $nuevaFila;
}

function htmlLineaRefTienda($item,$crefTienda,$link,$permiso_borrar){
	// @ Objetivo:
	// Montar linea de proveedores_coste, para añadir o para modificar.
	// @ Parametros :
	// 		$item -> (int) Numero item
	// 		$crefTienda-> (array) Datos de crefTienda: idTienda,crefTienda,idVirtuemart,...
	
	
	
	$nuevaFila = '<tr id="ref_tienda_'.$item.'">';
	$nuevaFila .= '<td>'.$crefTienda['idTienda'].'</td>';
	$nuevaFila .= '<td>';
	$nuevaFila .='<small>'.$crefTienda['crefTienda'].'/'.$crefTienda['idVirtuemart'].'</small>';
	$nuevaFila .='</td>';
	$nuevaFila .= '<td>';
	$nuevaFila .= $crefTienda['tipoTienda'];
	$nuevaFila .='</td>';
	$nuevaFila .= '<td>'.$link.'</td>';
    $nuevaFila .= '<td>';
    if ($permiso_borrar != 0){
        $nuevaFila .='<a id="eliminarref_tienda_'.$item.'" class="glyphicon glyphicon-trash" onclick="EliminarReferenciaTienda('.$crefTienda['id'].',this)"></a>';
    }
    $nuevaFila .= '</td></tr>';
	return $nuevaFila;
}

function  htmlTablaBalanza($relacion_balanza){
	// @ Objetivo
	// Montar la tabla html de codbarras
	// @ Parametros
	// 		$codBarras -> (array) con los codbarras del producto.
	$html =	 '<table id="tbalanzas" class="table table-striped">'
			.'		<thead>'
			.'			<tr>'
			.'				<th>Balanza</th>'
            .'              <th>Plu</th>'
            .'              <th>Tecla</th>'
			.'			</tr>'
			.'		</thead>'
			.'		<tbody>';
	if (count($relacion_balanza)>0){
		foreach ($relacion_balanza as $item=>$balanza){
			$html .=    '<tr><td>'.$balanza['idBalanza']
                            .'<td>'.$balanza['plu'].'</td>'
                            .'<td>'.$balanza['tecla'].'</td>'
                        .'</tr>';
		}
	}
			
	$html .= '</tbody> </table>	';
	return $html;
} 

function  htmlTablaCodBarras($codBarras){
	// @ Objetivo
	// Montar la tabla html de codbarras
	// @ Parametros
	// 		$codBarras -> (array) con los codbarras del producto.
	$html =	 '<table id="tcodigo" class="table table-striped">'
			.'		<thead>'
			.'			<tr>'
			.'				<th>Codigos Barras</th>'
			.'				<th>'.'<a id="agregar" onclick="AnhadirCodbarras()">Añadir'
			.'					<span class="glyphicon glyphicon-plus"></span>'
			.'					</a>'
			.'				</th>'
			.'			</tr>'
			.'		</thead>'
			.'		<tbody>';
	if (count($codBarras)>0){
		foreach ($codBarras as $item=>$valor){
			$html .= htmlLineaCodigoBarras($item,$valor);
		}
	}
			
	$html .= '</tbody> </table>	';
	return $html;
} 

function  htmlTablaFamilias($familias, $idProducto){
	// @ Objetivo
	// Montar la tabla html de familias del producto
	// @ Parametros
	// 		$familias -> (array) idFamilias y NombreFamilias 
	$html =	 '<table id="tfamilias" class="table table-striped">'
			.'		<thead>'
			.'			<tr>'
			.'				<th>idFamilias</th>'
			.'				<th>Nombre de Familia</th>'
			.'				<th>'.'<a id="agregar" onclick="modalFamiliaProducto('.$idProducto.')">Añadir'
			.'					<span class="glyphicon glyphicon-plus"></span>'
			.'					</a>'
			.'				</th>'
			.'			</tr>'
			.'		</thead>';
    if (isset($familias)){
		foreach ($familias as $item=>$valor){
			$html .= htmlLineaFamilias($item, $idProducto, $valor);
		}
	}
	$html .= '</table>	';
	return $html;
} 



function  htmlTablaProveedoresCostes($proveedores,$borrar_ref_prov){
	// @ Objetivo
	// Montar la tabla html de codbarras
	// @ Parametros
	// 		// 		$proveedores-> (array) de Arrays con datos de proveedor: idProveedor,crefProveedor,coste,fechaActualizacion,estado,nombrecomercial,razonsocial.
	$html =	 '<table id="tproveedor" class="table table-striped">'
			.'		<thead>'
			.'			<tr>'
			.'				<th><span title="Proveedor Principal" class="glyphicon glyphicon-check"></span></th>'
			.'				<th>Proveedor</th>'
			.'				<th>Ref_proveedor</th>'
			.'				<th>Coste</th>'
			.'				<th>Fecha</th>'
			.'				<th>'
			.'				<a  title="Añade un posible proveedor para este producto"'
			.'				id="agregar_proveedor" class="glyphicon glyphicon-plus" onclick="BuscarProveedor()"></a>'
			.'				</th>'
			.'			</tr>'
			.'		</thead>';
	if (count($proveedores)>0){
		// Creamos <script> para añadir varible de proveedores, 
		// ya que no hace falta para no añadirlo en la cja busqueda proveedores.
		$JSproveedores = 'var proveedores ='.json_encode($proveedores).';';
		foreach ($proveedores as $item=>$proveedor_coste){
			$html .= htmlLineaProveedorCoste($proveedor_coste,$borrar_ref_prov);
		}
	}
	$html .= '</table>	';
	// Solo si hay claro proveedores, añadimos variable proveedores a JS.
	if (isset($JSproveedores)){
		$html .= '<script>'.$JSproveedores.'</script>';
	}
	return $html;
} 


function  htmlTablaRefTiendas($crefTiendas,$link,$permiso_borrar=0){
	// @ Objetivo
	// Montar la tabla html de codbarras
	// @ Parametros
	// 		// 		$crefTiendas-> (array) de Arrays con datos de productos en otras tiendas.
	$html =	 '<table id="tproveedor" class="table table-striped">'
            .'  <thead>'
            .'      <tr>'
            .'          <th>idTienda</th>'
            .'          <th>Cref / id </th>'
            .'          <th>Tipo Tienda</th>'
            .'          <th>link</th>'
            .'          <th></th>'
            .'      </tr>'
            .'  </thead>';
	if (count($crefTiendas)>0){
		foreach ($crefTiendas as $item=>$crefTienda){
			if ($crefTienda['tipoTienda'] !=='principal'){
				// No generamos html de tienda principal ya que no tiene sentido.
				$html .= htmlLineaRefTienda($item,$crefTienda,$link,$permiso_borrar);
			}
		}
	}
	$html .= '</table>	';
	return $html;
} 

function htmlTablaHistoricoPrecios($historicoPrecios){
    $lineas=0;
    $html =	 '<table id="thitorico" class="table table-striped">'
			.'		<thead>'
			.'			<tr>'
			.'				<th>Fecha</th>'
			.'				<th>Antes</th>'
			.'				<th>Nuevo</th>'
			.'				<th>NumDoc</th>'
			.'				<th>De donde</th>'
			.'				<th>Tipo</th>'
            .'              <th></th>'
			.'			</tr>'
			.'		</thead>';
            if(isset($historicoPrecios)){
                $lineas=$lineas+1;
                $historicoPrecios=array_reverse ($historicoPrecios);
                foreach ($historicoPrecios as $historico){
                    $html.='<tr>'
                            .'<td>'.date_format(date_create($historico['Fecha_Creacion']), 'd-m-Y').'</td>'
                            .'<td>'.$historico['Antes'].'</td>'
                            .'<td>'.$historico['Nuevo'].'</td>'
                            .'<td>'.$historico['NumDoc'].'</td>'
                            .'<td>'.$historico['Dedonde'].'</td>'
                            .'<td>'.$historico['Tipo'].'</td>'
                            .'<td><a class="glyphicon glyphicon-trash" id="eliminarHist_'.$lineas.'" onclick="EliminarHistorico('.$historico['id'].', this)"></a></td>'
                    .'</tr>';
                }
            }
            $html .= '</table>	';
	return $html;
}

function htmlOptionIvas($ivas,$ivaProducto){
	//  Objetivo :
	// Montar html Option para selecciona Ivas, poniendo seleccionado el ivaProducto
	$htmlIvas = '';
	foreach ($ivas as $item){
			$es_seleccionado = '';
			if ($ivaProducto === $item['iva']){
				$es_seleccionado = ' selected';
			}
			$htmlIvas .= '<option value="'.$item['idIva'].'" '.$es_seleccionado.'>'.$item['iva'].'%'.'</option>';
		}
	return $htmlIvas;	
	
}

function htmlOptionEstados($posibles_estados,$estado=''){
	//  Objetivo :
	// Montar html Option para selecciona Estados, poniendo seleccionado el estado enviado
	//  @ Parametros 
	// 		$posibles_estados -> (array) de posibles estados..
	//		$ estado -> (string) Con el estado que queremos este seleccionado
	$htmlEstados = '';
	foreach ($posibles_estados as $item){
			$es_seleccionado = '';
			if ($estado === $item['estado']){
				$es_seleccionado = ' selected';
			}
			$htmlEstados .= '<option value="'.$item['estado'].'" '.$es_seleccionado.'>'.$item['estado'].'</option>';
		}
	return $htmlEstados;	
	
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


function MostrarColumnaConfiguracion($mostrar_lista,$parametro){
	// @ Objetivo:
	// Es comprobar el estado de la configuracion, para saber si muestra o no, lo utilizo Listadoproductos
	// @ Parametros:
	// 		$mostrar_lista:(Array) Array de objectos de configuracion de mostrar_lista.
	// 		$parametro: (String) Nombre de campo que ponemos en configuracion.
	// @ Devuelve:
	// (String) Si o No.
	foreach ($mostrar_lista as $lista){
		if ($parametro == $lista->nombre){
			return $lista->valor;
		}
	}
}
	


function HtmlListadoCheckMostrar($mostrar_lista){
	// @ Objetivo:
	// Obtener el html de los parametros que no sea por defecto para poder cambiarlos.
	// @ Parametros:
	// 		$mostrar_lista:(Array) Array de objectos de configuracion de mostrar_lista.
	// @ Devuelve:
	// 	 Array :
	// 		[htmlCheck] 	(string) Los inputs con lo check que podemos modificar 
	// 		[htmlOption]	(string) Select con opciones si sesta marcado como SI.
	// 		[error] 		(string) Si hubo mas de un parametro por defecto.
	// 		[campo_defecto]	(string) Con el nombre del parametro por defecto.
	$respuesta 				= array();
	$respuesta['htmlOption']= '';
	$respuesta['htmlCheck'] = '';
	$c = 0; // Contador de campos con buscar_default
	foreach ($mostrar_lista as $mostrar){
		if (!isset($mostrar->buscar_default)){
			// No pongo opcion de cambiar aquel que esta por defecto.
			// Solo lo podemos cambiar fichero parametros.xml.
			$c= ' onchange="GuardarConfiguracion(this)"';
			if ($mostrar->valor==='Si'){
				$c =' checked '.$c;
				$respuesta['htmlOption'] .= '<option value="'.$mostrar->nombre.'">'.$mostrar->descripcion.'</option>';
			}
			$respuesta['htmlCheck'] .= '<input class="configuracion" type="checkbox" name="'.$mostrar->nombre.'" value="'.$mostrar->valor.'"'.$c.'>'.$mostrar->descripcion.'<br>';
		} else {
			$c ++;
			if ( $c > 1){
				// Hubo un error no puede haber mas que uno por default.
				$respuesta['error'] = 'El fichero de parametros o la tabla modulos_configuracion para este usuario y modulo es incorrecta, ya que tiene mas de un parametro por default.';
				// No continuamos.
				return $respuesta;
			}
			$respuesta['campo_defecto'] = 	$mostrar->nombre;
			$respuesta['htmlOption'] 	=	'<option value="'.$mostrar->nombre.'">'
											.$mostrar->descripcion.'</option>'.$respuesta['htmlOption'];
		}
		
	}	
	return $respuesta;


}

function prepararandoPostProducto($array,$claseArticulos){
	//@ Objetivo
	//	Preparar los que recibimos por POST en productos,
	
	//@ Parametros
	// 	 $array => Array ( post) con los datos del formulario.
	
	// Obtenemos (array) Key del array recibido
	$Post = array_keys($array);
	
	// Recorremos las keys y montamos DatosProducto que son los datos Post formateados.
	$DatosProducto = array();
	$DatosProducto['codBarras'] = array();
	$DatosProducto['proveedores_costes'] = array();
	$DatosProducto['familias'] = array();
	// Primero de todo obtengo idTienda.
	$DatosProducto['idTienda'] = $claseArticulos->GetIdTienda();
	$DatosProducto['refProducto']=$array['cref_tienda_principal'];
	foreach ($Post as $key){
		switch ($key) {
			case 'articulo_name':
				if (trim($array['articulo_name']) === ''){
					// Viene vacio, por lo que no continuamos.
					$advertencia = array ( 'tipo'=>'danger',
								'mensaje' =>'El campo nombre producto no puede estar vacio.',
								'dato' => 'Case de funciones.php en prepararandoPostProducto'
								);
					$DatosProducto['comprobaciones'][] = $advertencia;
				} else {
					$DatosProducto['articulo_name'] = $array['articulo_name'];

				}
				break;
			case 'idIva':
				// Obtenemos iva según id obtenido en el formulario.
				$DatosProducto['iva']		= $claseArticulos->GetUnIva($array['idIva']);
				break;
			case 'id':
				// Solo creamos elemento array (id) si es mayor 0, es decir modificado.
				if ($array['id'] >0){
					$DatosProducto['idArticulo'] = $array['id'];
				}
				break;
			case 'check_pro':
				// Obtener array de proveedor principal
				$claseArticulos->ObtenerDatosProvPredeter($array['check_pro']);
				$DatosProducto['proveedor_principal'] = $claseArticulos->GetProveedorPrincipal();
				break;
			 
			case (substr($key, 0, 10)=== 'codBarras_'):
				if (trim($array[$key])!== ''){
					// No enviamos texto blanco o vacio
					array_push($DatosProducto['codBarras'],$array[$key]);
				}
				break;
			
			case (substr($key, 0,12)==='idProveedor_'):
				// Montamos array de provedores y costes
				$resto = 12-strlen($key);
				$idProveedor = substr($key,$resto);
				$p_r = trim($array['prov_cref_'.$idProveedor]);
				$p_c = trim($array['prov_coste_'.$idProveedor]);
				$monto = 'Si';
				if ( $p_r === '' &&  $p_c == 0 ){
					// No tiene datos comprobamos si esta marcado como principal
					// sino no lo montamos.
					if ($array['check_pro'] !== $idProveedor){
						$monto = 'No';
						// Monstamos advertencia
						$advertencia = array ( 'tipo'=>'warning',
								'mensaje' =>'El proveedor '.$idProveedor.' no lo añadimos ya que no tiene referencia , ni coste y no esta marcado como principal',
								'dato' => $sqlArticulo
								);
						$DatosProducto['comprobaciones'][] = $advertencia;
					}
				}
				if ($monto === 'Si') {
					// Contiene datos no esta vacio.
					$prov_coste = array(
							'idArticulo' 	=> $array['id'],
							'idProveedor' 	=> $idProveedor,
							'crefProveedor'	=> $p_r,
							'coste'			=> $p_c
						);
					array_push($DatosProducto['proveedores_costes'],$prov_coste);
				}
				break;
			
			case (substr($key, 0,11)==='idFamilias_')  :
				// Montamos array de familias.
				// El array que debo obtener es:
				// 	[idFamilia] => Id de la familia ( que obtengo de array[idFamilia_XX]
				//  [familiaNombre] => NOmbre tengo que obtenerlo
				//  [familiaPadre] => Id de padre , tengo que obtenerlo.
				$resto = 11-strlen($key);
				$idFamilia = substr($key,$resto);
				$familia = array(
							'idFamilia'	=> $idFamilia	
							);
				array_push($DatosProducto['familias'],$familia);
			
			default:
				// tengo descarta elemento prov_cref_XX , prov_coste_XX y idFamilias_XX
				if (substr($key, 0,10) !=='prov_cref_' && substr($key, 0,11) !=='prov_coste_' && substr($key, 0,11) !=='idFamilias_'){
					$DatosProducto[$key] 		= $array[$key]; 
				}
		}
	}
	
	return $DatosProducto;
	
}



function montarHTMLimprimir($id, $BDTpv, $dedonde, $CArticulo, $CAlbaran, $CProveedor){
	$datosHistorico=$CArticulo->historicoCompras($id, $dedonde, "Productos");
	$datosAlbaran=$CAlbaran->datosAlbaran($id);
  	$datosProveedor=$CProveedor->buscarProveedorId($datosAlbaran['idProveedor']);

    $imprimir['html']="";
	$imprimir['cabecera']="";
	$imprimir['html'] .='<p> ALBARÁN NÚMERO : '.$id.'</p>';
	$date = date_create($datosAlbaran['Fecha']);
	$imprimir['html'] .='<p> FECHA : '.date_format($date, 'Y-m-d').'</p>';
	$imprimir['html'] .='<p> PROVEEDOR : '.$datosProveedor['nombrecomercial'].'</p>';
	$imprimir['html'] .='<br>';
	
	
	$imprimir['html'] .='<table  WIDTH="100%">';
	$imprimir['html'] .='<tr>';
	$imprimir['html'] .='<td WIDTH="35%">NOMBRE</td>';
    $imprimir['html'] .='<td>REFERENCIA</td>';
	$imprimir['html'] .='<td>PRECIO ANTERIOR</td>';
	$imprimir['html'] .='<td>PRECIO NUEVO</td>';
	$imprimir['html'] .='</tr>';
	$imprimir['html'] .= '</table>';
	$imprimir['html'] .='<table  WIDTH="100%">';
	foreach ($datosHistorico as $prod){
		$datosArticulo=$CArticulo->datosPrincipalesArticulo($prod['idArticulo']);
		$imprimir['html'].='<tr>';
		$imprimir['html'].='<td WIDTH="35%">'.$datosArticulo['articulo_name'].'</td>';
        $imprimir['html'].='<td>'.$datosArticulo['crefTienda'].'</td>';
		$imprimir['html'].='<td>'.$prod['Antes'].'</td>';
		$imprimir['html'].='<td>'.$prod['Nuevo'].'</td>';
		$imprimir['html'].='</tr>';
	}
	return $imprimir;
}
function montarHTMLimprimirSinGuardar($id, $BDTpv, $dedonde, $CArticulo, $CAlbaran, $CProveedor){
	// Objetivo
	// Funcion para imprimir recalculo, sin guardar.
	
	$datosHistorico=$CArticulo->historicoCompras($id, $dedonde, "compras");
	$datosAlbaran=$CAlbaran->datosAlbaran($id);
	$datosProveedor=$CProveedor->buscarProveedorId($datosAlbaran['idProveedor']);
	$imprimir['html']="";
	$imprimir['cabecera']="";
	$imprimir['html'] .='<p> ALBARÁN NÚMERO : '.$id.'</p>';
	$date = date_create($datosAlbaran['Fecha']);
	$imprimir['html'] .='<p> FECHA : '.date_format($date, 'Y-m-d').'</p>';
	$imprimir['html'] .='<p> PROVEEDOR : '.$datosProveedor['nombrecomercial'].'</p>';
	$imprimir['html'] .='<br>';
	
	$imprimir['html'] .='<table  WIDTH="100%">';
	$imprimir['html'] .='<tr>';
	$imprimir['html'] .='<td WIDTH="50%">NOMBRE</td>';
	$imprimir['html'] .='<td>REFERENCIA</td>';
	$imprimir['html'] .='<td>PRECIO ANTERIOR</td>';
	$imprimir['html'] .='</tr>';
	$imprimir['html'] .= '</table>';
	$imprimir['html'] .='<table  WIDTH="100%">';
	foreach ($datosHistorico as $prod){
		$datosArticulo=$CArticulo->datosPrincipalesArticulo($prod['idArticulo']);
		$datosArticuloProveedor=$CArticulo->buscarReferencia($prod['idArticulo'], $datosAlbaran['idProveedor']);
		$precioArticulo=$CArticulo->articulosPrecio($prod['idArticulo']);
		$imprimir['html'].='<tr>';
		$imprimir['html'].='<td WIDTH="50%">'.$datosArticulo['articulo_name'].'</td>';
		$imprimir['html'].='<td>'.$datosArticuloProveedor['crefProveedor'].'</td>';
		$imprimir['html'].='<td>'.number_format($precioArticulo['pvpCiva'],2).'</td>';
		$imprimir['html'].='</tr>';
	}
	return $imprimir;
	
}

function productosSesion($idProducto, $seleccionar){
	// @ Objetivo
	// Guardar en la session los productos seleccionados.
	// @ Parametro:
	// 		idProducto-> (int) Id del producto seleccionado.
	// 		session-> (array) de los valores de session obtenidos.
	$respuesta = array();
	$respuesta['Nitems'] = 0 ;// Por defecto. items..
	if (!isset($_SESSION['productos_seleccionados'])){
		// Si no existe lo creamos como un array
		$_SESSION['productos_seleccionados'] = array();
	}
    
    switch ($seleccionar) {
        case 'seleccionar':
            if (!in_array($idProducto, $_SESSION['productos_seleccionados'])){
                array_push($_SESSION['productos_seleccionados'], $idProducto);
            }
        break;
        case 'NoSeleccionar':
            foreach($_SESSION['productos_seleccionados'] as $key=>$prod){
                    if($prod==$idProducto){
                        $respuesta['prod']=$prod;
                        unset($_SESSION['productos_seleccionados'][$key]);
                    }
                }
        break;
        default:
            if (!in_array($idProducto, $_SESSION['productos_seleccionados'])){
                array_push($_SESSION['productos_seleccionados'], $idProducto);
            }else{
                foreach($_SESSION['productos_seleccionados'] as $key=>$prod){
                    if($prod==$idProducto){
                        $respuesta['prod']=$prod;
                        unset($_SESSION['productos_seleccionados'][$key]);
                    }
                }
	}
        break;
        
    }
    
    
    
    
    
    
	
	
	if(count($_SESSION['productos_seleccionados'])>0){
			$respuesta['Nitems']=count($_SESSION['productos_seleccionados']);
	}
	$_SESSION['productos_seleccionados'] = array_values($_SESSION['productos_seleccionados']);
	$respuesta['idProducto']=$idProducto;
	$respuesta['productos_seleccionados']= $_SESSION['productos_seleccionados'];
	return $respuesta;
}
function htmlBuscarProveedor($busqueda,$dedonde, $proveedores,$descartados){
	// @ Objetivo:
	// Montar el hmtl para mostrar con los proveeodr si los hubiera.
	// @ parametros:
	// 		$busqueda 	-> (string) El valor a buscar,aunque puede venir vacio.. 
	//		$dedonde  	-> (string) Nos indica de donde viene. ()
	//		$provedores -> (array) Con o sin datos de los proveedores que encontramos.
	//		$descartados-> (array) Con o sin datos de los proveedores descartados, porque ya los tiene añadidos.
	$resultado = array();
	$resultado['encontrados'] = count($proveedores);
	$resultado['html'] = '<label>Busqueda Proveedor en '.$dedonde.'</label>'
					.'<input id="cajaBusquedaproveedor" name="valorproveedor" placeholder="Buscar"'
					.'size="13" data-obj="cajaBusquedaproveedor" value="'.$busqueda
					.'" onkeydown="controlEventos(event)" type="text">';
				
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
			$idFila = 'Fila_'.$contad;
			$resultado['html'] 	.= '<tr class="FilaModal" id="'.$idFila.'" onclick="seleccionProveedor('
								."'".$dedonde."'".' , '."'".$proveedor['idProveedor']."'".')">'
								.'<td id="C'.$contad.'_Lin" >'
								.'<input id="N_'.$contad.'" name="filaproveedor" data-obj="idN" onkeydown="controlEventos(event)" type="image"  alt="">'
								.'<span  class="glyphicon glyphicon-plus-sign agregar"></span></td>'
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
	// Ahora mostramos los proveedores descartados.
	
	if (count($descartados) > 0){
		$resultado['html'] .='<div class="alert alert-danger">'
							.'<h4>Proveedores descartados porque ya existen</h4>';
		foreach ($descartados as $descartado){
			$resultado['html'] .='<p>'.$descartado['nombrecomercial'].' - '.$descartado['razonsocial'].'</p>';
		}
		$resultado['html'] .='</div>';

	}
	// Ahora generamos objetos de filas.
	// Objetos queremos controlar.
	return $resultado;

}

function htmlEtiquetaLinea1($producto,$medida){
    // Objetivo:
    // Obtener la primera linea de cada etiqueta.
    // Variable que puede no imprimirse:
    $linea='';
    $proveedor_principal = '';
    if ($producto['proveedor_principal'] !== null){
        $proveedor_principal = ' Prov:'.$producto['proveedor_principal']['idProveedor'];
    }
    $linea.='<font size="'.$medida['font_linea'].' em" align="center"><br>  Fecha: '.date('Y-m-d').'  Id: '.$producto['idArticulo'].$proveedor_principal.'<br></font>';
    return $linea;
}


function htmlEtiquetaLineaNombre($producto,$medida,$plu=''){
    // Objetivo:
    // Obtener la primera linea de cada etiqueta.
    // Variable que puede no imprimirse:
    $linea='';
    $emNombre = $medida['font_nombre']; // Defecto A5
    $emTecla  = $medida['font_tecla'];
    if (strlen(trim($producto['articulo_name'])) >15 ){
            $emNombre = $medida['font_nombre_largo'];
    }
    if ($plu !==''){
        // Creamos tabla solo si tiene imprimir plu
        $linea .='<table '.$medida['width'].'>'
        .'<tr><td '.$medida['width_tecla'].'>'
        .'<font size="10 em" align="left">Tecla:<br></font><font size="'.$emTecla.' em" align="left"><b>'
        .$plu.'</b></font> '
        .'</td><td>';
    }
    $linea.='<b><font size="'.$emNombre.' em">'.$producto['articulo_name'].'<br></font></b>';
    if ($plu !=''){
        $linea .='</td></tr></table>';
    }
    return $linea;
}

function htmlEtiquetaLineaUltima($producto,$medida){
    // @ Objetivo:
    // Obtener la referencia nuestra y los codigos de barras. ( Ojo que puede tener muchos codbarras, por lo que hay que controlar cual se muestra)
    // @ Parametros:
    //   $productos -> Array del producto en cuestion.
    //   $em -> String con la medida de la letra.
    $em= $medida['font_linea'].' em';
    $linea = '<font size="'.$em.'" >';
    if ($producto['cref_tienda_principal'] !==''){
        $linea .='Ref: '.$producto['cref_tienda_principal'];
    }
    if (count($producto['codBarras'])>0){
        $linea.=' Codbarras: ';
        $cod =array();
        foreach($producto['codBarras'] as $codigo){
            $cod[]=$codigo;
        }
        $linea.= implode(',',$cod);
    }
    $linea.='</font>';
    
    return $linea;
}




function ObtenerMedidasEtiquetas($tipo) {
    // @ Objetivo:
    // Imprimir etiquetas
    $medidas = array(   'A5' => array ( 'etiquetas_hoja' => 2,
                                        'etiquetas_columna' => 1,
                                        'height' => 'height:375px;',
                                        'width' => 'width="790px"',
                                        'width_tecla' => 'width="100px"',
                                        'font_precio' => 200,
                                        'font_tecla' => 45,
                                        'font_nombre' => 30,
                                        'font_nombre_largo' => 20,
                                        'font_linea' => 10
                                    ),
                        'A7' => array ( 'etiquetas_hoja' => 8,
                                        'etiquetas_columna' => 2,
                                        'height' => 'height:187px;',
                                        'width' => 'width="360px"',
                                        'width_tecla' => 'width="60px"',
                                        'font_precio' => 80,
                                        'font_tecla' => 20,
                                        'font_nombre' => 12,
                                        'font_nombre_largo' => 10,
                                        'font_linea' => 7
                                    ),
                        'A8' => array ( 'etiquetas_hoja' => 24,
                                        'etiquetas_columna' => 3,
                                        'height' => 'height:94px;',
                                        'width' => 'width="260px"',
                                        'width_tecla' => 'width="30px"',
                                        'font_precio' => 28,
                                        'font_tecla' => 20,
                                        'font_nombre' => 10,
                                        'font_nombre_largo' => 8,
                                        'font_linea' => 6
                                    ),
                        'A9' => array ( 'etiquetas_hoja' => 36,
                                        'etiquetas_columna' => 4,
                                        'height' => 'height:82px;',
                                        'width' => 'width="195px"',
                                        'width_tecla' => 'width="20px"',
                                        'font_precio' => 25,
                                        'font_tecla' => 14,
                                        'font_nombre' => 9,
                                        'font_nombre_largo' => 7,
                                        'font_linea' => 5
                                    )
                    );
     return $medidas[$tipo];
    
}

function ImprimirEtiquetas($productos,$tipo,$balanza=''){
	//@objetivo: imprimir las etiquetas de tamaño A5 
	//@Parametros: 
	//  $Productos: listado de productos que vamos a imprimir
    //  $balanza: La balanza queremos que muestre tecla, el id
	//@Return:
	//html montado para imprimir
    $medida = ObtenerMedidasEtiquetas($tipo);
	$imprimir=array(
		'html'=>'',
		'cabecera'=>''
	);
    $etiqueta_hoja=0;
    $columna = 0;
	$imprimir['html'].="";
	$imprimir['html'].='<table border="1px" style="table-layout: fixed;">';
    foreach ($productos as $producto){
        // La etiqueta puede pidamos mas una para algun producto
        for ($i = 1; $i <= $producto['numEtiquetas']; $i++) {
            $columna++;
            if ($columna === 1){
                $imprimir['html'].='<tr>';
            }
            $imprimir['html'].='<td align="center" style="'.$medida['height'].'" >';
            // Obtenemos primera linea
            $plu = '';
            if ($balanza !==''){
                if (isset($producto['plu'])){
                    $plu = $producto['plu'];
                }
            }
            $Linea1 = htmlEtiquetaLinea1($producto,$medida);
            $imprimir['html'].= '<font size="5em">'.$tipo.'</font>'.$Linea1;
            $imprimir['html'].= htmlEtiquetaLineaNombre($producto,$medida,$plu);
            $imprimir['html'].='<b><font size="'.$medida['font_precio'].' em">'.number_format($producto['pvpCiva'],2,',','').'</font>€</b>';
            $imprimir['html'].='<font size="'.$medida['font_precio'].' em"><br></font>';
            $imprimir['html'].= htmlEtiquetaLineaUltima($producto,$medida);
            $imprimir['html'].=  '</td>';

            if ($columna == $medida['etiquetas_columna']){
                $imprimir['html'].='</tr>';
                $columna = 0;
            }
            $etiqueta_hoja++;
            if($etiqueta_hoja==$medida['etiquetas_hoja']){
                // Volvemos abrir la tabla
                $imprimir['html'].='</table><table border="1px">';
                $etiqueta_hoja=0;
            }
        }
    }
    if($columna<$medida['etiquetas_columna'] && $columna !== 0){
		$rep=$medida['etiquetas_columna']-$columna;
        
		$imprimir['html'].= str_repeat("<td></td>", $rep);
		$imprimir['html'].='</tr>';
	}
    $imprimir['html'].='</table>';
	return $imprimir;
	
}
function eliminarSeleccion(){
	$_SESSION['productos_seleccionados']=array();

}


function comprobarUltimaCompraProveedor($Pro_costes){
	// @Objetivo 
	// Comprobar a quien se compro por ultima vez un producto.
	// @Parametros
	// 		$Pro_costes -> Array con los proveedores y los costes que se compro es te producto.
	$respuesta = array();
    $ultimo_coste = 0;
	if (count($Pro_costes) > 0){
		$id_proveedor_ultimo = 0;
        $fecha_ultima = "0000-00-00";
		foreach ($Pro_costes as $key =>$proveedor){
			if ($proveedor['fechaActualizacion']>$fecha_ultima){
				$id_proveedor_ultimo = $proveedor['idProveedor'];
				$ultimo_coste = $proveedor['coste'];
				$fecha_ultima = $proveedor['fechaActualizacion'];
				$item = $key;
			}
		}
		$Pro_costes[$item]['ultimo_pro'] = 'Si';
	}
	$respuesta['proveedores'] = $Pro_costes;
	$respuesta['coste_ultimo'] = $ultimo_coste;
	return $respuesta;
}
function comprobarRecalculosSuperiores($productos, $CArticulo){
    // @ Objetivo:
    // Comprobar si hay registros con fecha superior.
    
    $i=0;
    foreach ($productos as $producto){
		//producto['idArticulo'] es el id del articulo
        $datosHistorico=$CArticulo->ComprobarFechasHistorico($producto['idArticulo'], $producto['Fecha_Creacion']);
        if(isset($datosHistorico['error'])){
            $productos['error']=$datosHistorico['error'];
            $productos['consulta']=$datosHistorico['consulta'];
        }else{
            // Si encuentra uno superior entonces cambiamos estado "Sin revisar" ya que o se va revisar o ya esta revisado
            // con fecha superior.
            if(count($datosHistorico)>0){
                $productos[$i]['estado']="Sin revisar";
                //producto['id'] es el id del regitro de historico precio de ese producto
                $modHistorico=$CArticulo->modificarRegHistorico($producto['id'], "Sin revisar");
                  if(isset($modHistorico['error'])){
                    $productos['error']=$modHistorico['error'];
                    $productos['consulta']=$modHistorico['consulta'];
                }
            }
        }
        $i++;
    }
    return $productos;
}
function modalAutocompleteFamilias($familias, $idProducto){
    $cantidad=count($familias);
    $html="";
    $html.=' <input type="text" value="'.$idProducto.'" id="idProductoModal" style="visibility:hidden">
            <div class="ui-widget" id="divFamilias">
            <label for="tags">Familias: '.$cantidad.'</label>
            <select id="combobox" class="familias">
            <option value="0"></option>';
    foreach($familias as $familia){
        $html.='<option value="'.$familia['idFamilia'].'">'.$familia['familiaNombre'].'</option>';
    }
    $html.='</select></div>';
    if($idProducto>0){
         $html.='<p id="botonEnviar"></p>';
    }else{
         $html.='<p id="botonEnviar2"></p>';
    }
   
    return $html;
}

function modalAutocompleteEstadoProductos($productos,$posibles_estados){
    // @ Objetivo
    // Montar inpurt y select para autocompleto de posibles estados.
    $stringProductos=implode(",", $productos);
    
     $html="";
    $html.='<input type="text" value="'.$stringProductos.'" id="idProductosModal" style="visibility:hidden">
            <div class="ui-widget" id="divEstados">
            <label for="tags">Estados: </label>
            <select id="combobox" class="estados">
                <option value="0"></option>';
    foreach ($posibles_estados as $e){
            $html.= '<option value="'.$e['estado'].'">'.$e['estado'].'</option>';
    }
     $html.= ' </select>';
        $html.='<p id="botonEnviarEstados"></p>';
        return $html;
}
function selectFamilias($padre=0, $espacio, $array_familias, $conexion,$nombre_completo = ''){
    
        $sql = 'select idFamilia, familiaNombre, familiaPadre  FROM familias where familiaPadre='.$padre.' ORDER BY idFamilia ASC';
        $res = $conexion->query($sql);
       if($padre>0){
           $espacio.='-';
       }
        
        $total= $res->num_rows;
      
        if($total>0){
            
            while ($row = $res->fetch_assoc()) {
                if (strlen($nombre_completo) >0){
                    $nombre_completo = $nombre_completo.'&#187;'.$row['familiaNombre'];
                } else {
                    $nombre_completo = $row['familiaNombre'];
                }
                $array_familias[]=array(
                                    "id" => $row['idFamilia'],
                                    "name" => $espacio . $row['familiaNombre'],
                                    "title" => $nombre_completo );
               
                 $array_familias= selectFamilias($row['idFamilia'], $espacio, $array_familias , $conexion,$nombre_completo);
            }
        }
        
        return $array_familias;
}

function htmlTipoProducto($tipo){
        switch($tipo){
            case 'unidad':
             $html='<select name="tipo">
                <option value="unidad" selected="">Unidad</option>
                <option value="peso">Peso</option>
                </select>';
            break;
            case 'peso':
             $html='<select name="tipo">
                <option value="peso" selected="">peso</option>
                <option value="unidad">Unidad</option>
                </select>';
            break;
            default: 
             $html='<select name="tipo">
                <option value="unidad" selected="">Unidad</option>
                <option value="peso">Peso</option>
                </select>';
            break;
        }
    return $html;
}


function construirHTMLEliminarProductos($productosEliminados, $productosNoEliminados){
    $miHtml = '<h4>Productos Eliminados</h4>';
    $miHtml .= '<table>';
	if(count($productosEliminados) == 0){
		$miHtml .= '<tr><td> Productos eliminados: 0</td></tr>';
	} else {
		$miHtml .= '<tr><th>id</th><th>nombre</td></tr>';
		foreach($productosEliminados as $productoEliminado){
			$miHtml .= '<tr><td>'. $productoEliminado['id'].'</td><td>'. $productoEliminado['nombre'].'</td></tr>';
		}
	}
	$miHtml .= '</table>';
	if(count($productosNoEliminados) > 0){
        $miHtml .= '<h4>Productos NO Eliminados</h4>';
        $miHtml .= '<table>';
		$miHtml .= '<tr><th>id - nombre</td><td>mensaje/s - relación/es</td></tr>';
		foreach($productosNoEliminados as $noEliminado){
			$miHtml .= '<tr><td>'. $noEliminado['id'].' - '. $noEliminado['nombre'].'</td></tr>';
			$miHtml .='<tr><td> <ul>';
			foreach($noEliminado['comprobaciones'] as $comprobacion){
				$miHtml.='<li>'.$comprobacion['mensaje'].'</li>';
			}
			$miHtml .='</ul></td></tr>';
		}
        $miHtml .= '</table>';
	}
	
return $miHtml;    
}

