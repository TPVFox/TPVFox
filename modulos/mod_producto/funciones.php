<?php 
function htmlLineaFamilias($item,$familia=''){
	// @Objetivo:
	// Montar linea de codbarras , para añadir o para modificar.
	$nuevaFila = '<tr>'
				. '<td><input type="hidden" id="idFamilias_'.$familia['idFamilia']
				.'" name="idFamilias_'.$familia['idFamilia'].'" value="'.$familia['idFamilia'].'">'
				.$familia['idFamilia'].'</td>'
				.'<td>'.$familia['familiaNombre'].'</td>'
				.'<td><a id="eliminar_'.$familia['idFamilia']
				.'" class="glyphicon glyphicon-trash" onclick="eliminarCodBarras(this)"></a>'
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

function htmlLineaProveedorCoste($proveedor){
	// @ Objetivo:
	// Montar linea de proveedores_coste, para añadir o para modificar.
	// @ Parametros :
	// 		$item -> (int) Numero item
	// 		$proveedor-> (array) Datos de proveedor: idProveedor,crefProveedor,coste,fechaActualizacion,estado,nombrecomercial,razonsocial.
	
	// Montamos campos ocultos de IDProveedor
	$camposIdProveedor = '<input type="hidden" name="idProveedor_'.$proveedor['idProveedor'].'" id="idProveedor_'.$proveedor['idProveedor'].'" value="'.$proveedor['idProveedor'].'">';
	$nom_proveedor = $proveedor['idProveedor'].'.-';
	// Monstamos nombre y razon social juntas
	if ($proveedor['nombrecomercial'] !== $proveedor['razonsocial']){
		$nom_proveedor .= $proveedor['razonsocial'].'-'.$proveedor['nombrecomercial'];
	} else {
		$nom_proveedor .= $proveedor['razonsocial'];
	}
	$atributos = ' name="check_pro"'; // Los check ponemos el mismo nombre ya solo podemos devolver uno como principal
	
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
	$nuevaFila = '<tr>'
				.'<td><input '.$atributos.' type="checkbox" id="check_pro_'
				.$proveedor['idProveedor'].'" value="'.$proveedor['idProveedor'].'"></td>'
				.'<td>'
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
				.'<span class="glyphicon glyphicon-calendar" title="Fecha Actualizacion:'
				.$proveedor['fechaActualizacion'].'">'.$proveedor['estado'].'</span>'
				.'</td>'
				.'<td><a id="desActivarProv_'.$proveedor['idProveedor']
				.'" class="glyphicon glyphicon-cog" onclick="desActivarCajasProveedor(this)"></a></td>'
				.'</tr>';
					
	return $nuevaFila;
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

function  htmlTablaFamilias($familias){
	// @ Objetivo
	// Montar la tabla html de familias del producto
	// @ Parametros
	// 		$familias -> (array) idFamilias y NombreFamilias 
	$html =	 '<table id="tfamilias" class="table table-striped">'
			.'		<thead>'
			.'			<tr>'
			.'				<th>idFamilias</th>'
			.'				<th>Nombre de Familia</th>'
			.'				<th>'.'<a id="agregar" onclick="comprobarVacio()">Añadir'
			.'					<span class="glyphicon glyphicon-plus"></span>'
			.'					</a>'
			.'				</th>'
			.'			</tr>'
			.'		</thead>';
	if (count($familias)>0){
		foreach ($familias as $item=>$valor){
			$html .= htmlLineaFamilias($item,$valor);
		}
	}
	$html .= '</table>	';
	return $html;
} 



function  htmlTablaProveedoresCostes($proveedores){
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
			.'				<th>Estado/Fecha</th>'
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
			$html .= htmlLineaProveedorCoste($proveedor_coste);
		}
	}
	$html .= '</table>	';
	// Solo si hay claro proveedores, añadimos variable proveedores a JS.
	if (isset($JSproveedores)){
		$html .= '<script>'.$JSproveedores.'</script>';
	}
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

function htmlOptionEstados($posibles_estados,$estado){
	//  Objetivo :
	// Montar html Option para selecciona Estados, poniendo seleccionado el estado enviado
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

function modificarProducto($BDTpv, $datos, $tabla){
	$resultado = array();
	$id=$datos['idProducto'];
	$nombre=$datos['nombre'];
	$coste=$datos['coste'];
	$beneficio=$datos['beneficio'];
	$iva=$datos['iva'];
	$pvpCiva=$datos['pvpCiva'];
	$pvpSiva=$datos['pvpSiva'];
	$referencia=$datos['referencia'];
	$tienda=$datos['idTienda'];
	// Montar un array con los las claves del array datos
	$keys=array_keys($datos);
	$codBarras = [];
	// Se va recorriendo  
	foreach($keys as $key){
		// Los que coincidan con el campo cod quiere decir que es un codigo de barras y se añaden al array codBarras[]
		$nombre1="cod";
		if (strpos($key, $nombre1)>-1){
			if ($datos[$key]<>""){
				$codBarras[] = '('.$id.',"'.$datos[$key].'")';
			}
		}
	}
	$stringCodbarras = implode(',',$codBarras);
	//Fecha y hora del sistema
	$fechaMod=date("Y-m-d H:i:s");
	$sql='UPDATE '.$tabla.' SET articulo_name="'.$nombre.'", costepromedio='.$coste.', beneficio='.$beneficio.' , iva ='.$iva.', fecha_modificado="'.$fechaMod.'" WHERE idArticulo='.$id;
	$sql2='UPDATE articulosPrecios SET pvpCiva='.$pvpCiva.', pvpSiva='.$pvpSiva.' WHERE idArticulo='.$id  ;
	$sql3='DELETE FROM articulosCodigoBarras where idArticulo='.$id;
	$sql5='UPDATE articulosTiendas set crefTienda ="'.$referencia.'" WHERE  idArticulo='.$id.' and idTienda='.$tienda;
	$sql4='INSERT INTO articulosCodigoBarras (idArticulo, codBarras) VALUES '.$stringCodbarras;
	$consulta = $BDTpv->query($sql);
	$consulta = $BDTpv->query($sql2);
	$consulta = $BDTpv->query($sql3);
	$consulta = $BDTpv->query($sql4);
	$consulta = $BDTpv->query($sql5);
	$resultado['sql'] =$sql;
	$resultado['sql2'] =$sql2;
	$resultado['sql3'] =$sql3;
	$resultado['sql4'] =$sql4;
	$resultado['sql6'] =$sql5;
	$resultado['sql5']=$keys;
	return $resultado;	
}
/*Función para añadir un producto nuevo*/
function añadirProducto($BDTpv, $datos, $tabla){
	$nombre=$datos['nombre'];
	$coste=$datos['coste'];
	$beneficio=$datos['beneficio'];
	$iva=$datos['iva'];
	$pvpCiva=$datos['pvpCiva'];
	$pvpSiva=$datos['pvpSiva'];
	$idProovedor=$datos['idProveedor'];
	$estado=$datos['estado'];
	$pvpCiva=$datos['pvpCiva'];
	$pvpSiva=$datos['pvpSiva'];
	$idTienda=$datos['idTienda'];
	$referencia=$datos['referencia'];
	
	//Fecha y hora del sistema 
	$fechaAdd=date("Y-m-d H:i:s");
	$keys=array_keys($datos);
	$codBarras = [];
	$sql='INSERT INTO '.$tabla.' (iva, idProveedor , articulo_name, beneficio, costepromedio, estado, fecha_creado) VALUES ("'.$iva.'" , "'.$idProovedor.'" , "'.$nombre.'", "'.$beneficio.'", "'.$coste.'", "'. $estado .'", "'.$fechaAdd.'")';
	$consulta = $BDTpv->query($sql);
	//Id del inster anterior 
	$idGenerado=$BDTpv->insert_id;
	foreach($keys as $key){
		// Los que coincidan con el campo cod quiere decir que es un codigo de barras y se añaden al array codBarras[]
		$nombre1="cod";
		if (strpos($key, $nombre1)>-1){
			if ($datos[$key]<>""){
				$codBarras[] = '('.$idGenerado.',"'.$datos[$key].'")';
			}
		}
	}
	$stringCodbarras = implode(',',$codBarras);
	$sql2='INSERT INTO articulosPrecios (idArticulo, pvpCiva , pvpSiva, idTienda ) VALUES ('.$idGenerado.', '.$pvpCiva.', '.$pvpSiva.' , '.$idTienda.')';
	if ($referencia == 0){
		$referencia="Sin ref";
	}
	$sql4='INSERT INTO articulosTiendas (idArticulo, idTienda, crefTienda) VALUES ('.$idGenerado.', '.$idTienda.', "'.$referencia.'")';
	$sql3='INSERT INTO articulosCodigoBarras (idArticulo, codBarras) VALUES '.$stringCodbarras;
	$consulta = $BDTpv->query($sql2);
	$consulta = $BDTpv->query($sql3);
		$consulta = $BDTpv->query($sql4);
	$resultado['sql'] =$sql;
		$resultado['sql1'] =$sql2;
			$resultado['sql2'] =$sql3;
			$resultado['sql4'] =$sql4;
	return $resultado;
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
				$c ='checked '.$c;
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

function prepararYgrabar($array,$claseArticulos){
	//@ Objetivo
	// Preparar y Grabar los datos obtenidos ( POST) en productos,
	// Debemos tener en cuenta que :
	//   id = 0 es nuevo..
	//   id = ??? es modificado.
	
	
	// Obtenemos (array) Key del array recibido
	$keys_array = array_keys($array);
	
	// Recorremos las keys
	$DatosProducto = array();
	$Sqls = array();
	$DatosProducto['codBarras'] = array();
	$DatosProducto['proveedores_costes'] = array();
	$DatosProducto['familias'] = array();


	// Primero de todo obtengo idTienda.
	$DatosProducto['idTienda'] = $claseArticulos->GetIdTienda();
	foreach ($keys_array as $key){
		switch ($key) {
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
				array_push($DatosProducto['codBarras'],$array[$key]);

				break;
			
			case (substr($key, 0,12)==='idProveedor_'):
				// Montamos array de provedores y costes
				// Este proceso le queda una parte que se debe hacer despues.
				// ya que el array de proveedores tiene los siguiente elementos:
				//		[idArticulo] =>  Id producto que si es nuevo no lo tenemos...
                //	    [idProveedor] => Tenemos que extraerlo de Key
                // 		[crefProveedor] => Que es la Key -> prov_cref_idProveedor
                //      [coste] => Que es la valor key prov_coste_idProveedor
                // 	--Los siguiente elementos no los tenemos.... 
                //	    [fechaActualizacion] => Esta si no cambia no la cambiamos.
                //	    [estado] => No lo cambiamos sino No cambio valor ningúno...
                //      [nombrecomercial] => Hay que obtenerlo , NO HACE FALTA
                // 		[razonsocial] => Hay que obtenerlo, NO HACE FALTA
                // 		[principal] => Hay comprobar si el mismo, NO HACE FALTA
				$resto = 12-strlen($key);
				$idProveedor = substr($key,$resto);
				$prov_coste = array(
						'idArticulo' 	=> $idProveedor,
						'crefProveedor'	=> $array['prov_cref_'.$idProveedor],
						'coste'			=> $array['prov_coste_'.$idProveedor]
					);
				array_push($DatosProducto['proveedores_costes'],$prov_coste);
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
	
	// Ahora empiezo con las comprobaciones .
	// Primero comprobamos si es nuevo o ya existia.
	if ($array['id'] >0 ){
		// ---------------            Se esta modificando. ------------------------------------//
		// --- Comprobamos los codbarras y vemos cuales añadio,modifico o elimino. --//
		$comprobaciones = $claseArticulos->ComprobarCodbarrasUnProducto($array['id'],$DatosProducto['codBarras']);
		$DatosProducto['Sqls']['codbarras'] = $comprobaciones;
	} else {
		// ----------------------------  SE ESTA AÑADIENDO UN PRODUCTO NUEVO  ------------------------  //
		$anhadir = $claseArticulos->AnhadirProductoNuevo($DatosProducto);
		$DatosProducto['Sqls']['NuevoProducto']=$anhadir;
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
	$imprimir['html'] .='<td WIDTH="50%">NOMBRE</td>';
	$imprimir['html'] .='<td>PRECIO ANTERIOR</td>';
	$imprimir['html'] .='<td>PRECIO NUEVO</td>';
	$imprimir['html'] .='</tr>';
	$imprimir['html'] .= '</table>';
	$imprimir['html'] .='<table  WIDTH="100%">';
	foreach ($datosHistorico as $prod){
		$datosArticulo=$CArticulo->datosPrincipalesArticulo($prod['idArticulo']);
		$imprimir['html'].='<tr>';
		$imprimir['html'].='<td WIDTH="50%">'.$datosArticulo['articulo_name'].'</td>';
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
function productosSesion($idProducto){
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


function ImprimirA8($productos){
	$imprimir=array(
		'html'=>'',
		'cabecera'=>''
	);
	$i=0;
	$imprimir['html'].="";
	$imprimir['html'].='<table border="1px">';
	$imprimir['html'].='<tr>';
	
	$imprimir['productos']=$productos;
	foreach ($productos as $producto){
		if($i==3){
			$i=0;
			$imprimir['html'].='<tr>';
		}
		$imprimir['html'].='<td align="center">';
		$imprimir['html'].='<font size="9 em"><b>'.$producto['articulo_name'].'</b></font><br>';
		$imprimir['html'].='<b><font size="30 em">'.number_format($producto['pvpCiva'],2,',','').'</font><font size="6.5 em" >€</font></b><br>';
		if(strlen ($producto['articulo_name'])<=30){
			$imprimir['html'].='<br>';
		}
		$imprimir['html'].='<font size="6.5 em">  Fecha: '.date('Y-m-d').'</font>';
		$imprimir['html'].='<font size="6.5 em" >  Codbarras: ';
		foreach($producto['codBarras'] as $codigo){
				$imprimir['html'].=$codigo.' ';
		}
		$imprimir['html'].='</font>';
		$imprimir['html'].='<font size="6.5 em">  Ref: '.$producto['cref_tienda_principal'].'</font>';
		$imprimir['html'].='<font size="6.5 em">  Id: '.$producto['idArticulo'].'</font>';
		//~ $imprimir['html'].=' RefProv:</font><br>';
		
		
		$imprimir['html'].='</td>';
		if($i==2){
			$imprimir['html'].='</tr>';
		}
		
		$i++;
	}
	if($i<=2){
		$rep=3-$i;
		$imprimir['html'].= str_repeat("<td></td>", $rep);
		$imprimir['html'].='</tr>';
	}
	
	$imprimir['html'].='</table>';
	return $imprimir;
}
function ImprimirA7($productos){
$imprimir=array(
		'html'=>'',
		'cabecera'=>''
	);
	$imprimir['html'].="";
	$imprimir['html'].='<table border="1px">';
	$imprimir['html'].='<tr>';
	$i=0;
	foreach ($productos as $producto){
		if($i==2){
			$i=0;
			$imprimir['html'].='<tr>';
		}
		$imprimir['html'].='<td align="center"  style="height:200px;" >';
		$imprimir['html'].='<b><font size="13 em">'.$producto['articulo_name'].'</font></b><br>';
		$imprimir['html'].='<b><font size="100 em">'.number_format($producto['pvpCiva'],2,',','').'</font>€</b><br><br><br>';
		$imprimir['html'].='<font size="6.5 em">  Fecha: '.date('Y-m-d').'</font>';
		$imprimir['html'].='<font size="6.5 em" >  Codbarras: ';
		foreach($producto['codBarras'] as $codigo){
				$imprimir['html'].=$codigo.' ';
		}
		$imprimir['html'].='</font>';
		$imprimir['html'].='<font size="6.5 em">Ref: '.$producto['cref_tienda_principal'].'</font>';
		$imprimir['html'].='<font size="6.5 em">  Id: '.$producto['idArticulo'].'</font>';
		
		
		
		$imprimir['html'].='</td>';
		if($i==1){
			$imprimir['html'].='</tr>';
		}
	$i++;
	}
	if($i<=1){
		$rep=2-$i;
		$imprimir['html'].= str_repeat("<td></td>", $rep);
		$imprimir['html'].='</tr>';
	}
	//~ $imprimir['html'].='</tr>';
	$imprimir['html'].='</table>';
	return $imprimir;

}
function ImprimirA5($productos){
	$imprimir=array(
		'html'=>'',
		'cabecera'=>''
	);
	$imprimir['html'].="";
	$imprimir['html'].='<table border="1px" height="527" style="table-layout: fixed;">';
		foreach ($productos as $producto){
			$imprimir['html'].='<tr>';
			$imprimir['html'].='<td align="center"  style="height:200px;" >';
			$imprimir['html'].='<b><font size="30 em">'.$producto['articulo_name'].'</font></b><br><br><br>';
			$imprimir['html'].='<b><font size="35 em"> </font></b><br>';
			$imprimir['html'].='<b><font size="250 em">'.number_format($producto['pvpCiva'],2,',','').'</font>€</b><br><br><br><br>';
			$imprimir['html'].='<font size="12 em">  Fecha: '.date('Y-m-d').'</font>';
			$imprimir['html'].='<font size="12 em" >  Codbarras: ';
			foreach($producto['codBarras'] as $codigo){
					$imprimir['html'].=$codigo.' ';
			}
			$imprimir['html'].='</font>';
			$imprimir['html'].='<font size="12 em">Ref: '.$producto['cref_tienda_principal'].'</font>';
			$imprimir['html'].='<font size="12 em">  Id: '.$producto['idArticulo'].'</font>';
			$imprimir['html'].='</td>';
			$imprimir['html'].='</tr>';
		}
		$imprimir['html'].='</table>';
	return $imprimir;
	
}
function eliminarSeleccion(){
	$_SESSION['productos_seleccionados']=array();

}

?>
