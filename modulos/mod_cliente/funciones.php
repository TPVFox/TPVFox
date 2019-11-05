<?php 


function obtenerClientes($BDTpv,$filtro) {
	// Function para obtener clientes y listarlos

	$clientes = array();
	$consulta = "Select * from clientes ".$filtro;//.$filtroFinal.$rango; 
	//$clientes['NItems'] = $Resql->num_rows;
	$i = 0;
	if ($Resql = $BDTpv->query($consulta)){			
		while ($fila = $Resql->fetch_assoc()) {
			$clientes[] = $fila;
		}
	}

	//$clientes ['consulta'] = $consulta;
	return $clientes;
}

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
	if (count($productos)>10){
		if ($total_producto ===-1){
			// Quiere decir que no se sabe realmente cuantos pueden ser la busqeuda completa.
			$tproductos = '* ';
		} else {
			$tproductos = $total_productos;
		}
		$html .= '<span>10 productos de '.$tproductos.'</span>';
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
					.' <td>'.number_format($producto['pvpCiva'],2).'</td>'
					.' <td>'.number_format($producto['pvpSiva'],2).'</td>'
					.'</tr>';
			$contad = $contad +1;
			if ($contad === 10){
				break;
			}
			
		}
		$html .='</tbody></table>';
	}
	$resultado['html'] = $html;
	$resultado['encontrados'] =$total_productos;
	$resultado['campo'] = $campoAbuscar;
	
	return $resultado;
// Funcion para obtener html de busqueda de producto. ( Lo ideal seria hacer fuera un plugin  )
// Para un correcto funcionamiento de la caja busqueda tenemos que tener creado cajaBusquedaproductos en xml 
// Ejemplo de configuracion input en xml 
// 		<caja_input>
//			<nombre id_input="cajaBusqueda">cajaBusquedaproductos</nombre>
//			<teclas>
//				<action tecla="13">buscarProducto</action>
//			</teclas>
//			<parametros>
//				<parametro nombre="dedonde">popup</parametro>
//				<parametro nombre="campo"></parametro>  
//			</parametros> 
//			<before>
//				<estado>Si</estado>
//			</before>
//		</caja_input>
// Tambien las clases de N_ son necesarias...


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
	if(count($datos)>0){
	switch($dedonde){
			case 'ticket':
				$url=$HostNombre.'/modulos/mod_tpv/ticketCobrado.php?id=';
				$resumen='<input type="text" class="btn btn-info" onclick="resumen('."'".$dedonde."'".', '.$datos[0]['idCliente'].')" value="Resumen" name="Resumen" ></td>';
			break;
			case 'factura':
				$url=$HostNombre.'/modulos/mod_venta/factura.php?id=';
				$resumen="";
			break;
			case 'albaran':
				$url=$HostNombre.'/modulos/mod_venta/albaran.php?id=';
				$resumen="";
			break;
			case 'pedido':
				$url=$HostNombre.'/modulos/mod_venta/pedido.php?id=';
				$resumen="";
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
	$i=0;
		foreach($datos as $dato){
			$html.='<tr>'.
				'<td>'.$dato['fecha'].'</td>'.
				'<td><a href="'.$url.$dato['id'].'">'.$dato['num'].'</a></td>'.
				'<td>'.$dato['total'].'</td>'.
				'<td>'.$dato['estado'].'</td>'.
			'</tr>';
			$i++;
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

function guardarCliente($datosPost, $BDTpv){
	//@objetivo:
	//Guardar los datos de un cliente
	//Primero realiza comprobaciones de todos los campos y dependiendo si tiene id de cliente o no
	//modifica o crear un nuevo cliente
	//Paramtros:
	//datosPost: datos que recibimos del formulario
	$Cliente=new ClaseCliente($BDTpv);
	$nif="";
	$direccion="";
	$codpostal="";
	$telefono="";
	$movil="";
	$fax="";
	$email="";
	$mod=array();
	if ($datosPost['formapago']>0||$datosPost['vencimiento']>0){
			$datosForma=array();
			$datosForma['formapago']=$datosPost['formapago'];
			$datosForma['vencimiento']=$datosPost['vencimiento'];
			$datosForma=json_encode($datosForma);
	}else{
		$datosForma=null;
	}
	if(isset($datosPost['nif'])){
		$nif=$datosPost['nif'];
	}
	if(isset($datosPost['direccion'])){
		$direccion=$datosPost['direccion'];
	}
	if(isset($datosPost['codpostal'])){
		$codpostal=$datosPost['codpostal'];
	}
	if(isset($datosPost['telefono'])){
		$telefono=$datosPost['telefono'];
	}
	if(isset($datosPost['movil'])){
		$movil=$datosPost['movil'];
	}
	if(isset($datosPost['fax'])){
		$fax=$datosPost['fax'];
	}
	if(isset($datosPost['email'])){
		$email=$datosPost['email'];
	}
	$datosNuevos=array(
		'nombre'=>$datosPost['nombre'],
		'razonsocial'=>$datosPost['razonsocial'],
		'nif'=>$nif,
		'direccion'=>$direccion,
		'codpostal'=>$codpostal,
		'telefono'=>$telefono,
		'movil'=>$movil,
		'fax'=>$fax,
		'email'=>$email,
		'estado'=>$datosPost['estado'],
		'formasVenci'=>$datosForma,
		'idCliente'=>$datosPost['idCliente']
	);
	
	$comprobar=$Cliente->comprobarExistenDatos($datosNuevos);
	if($comprobar['error']){
			$mod['buscarCliente']=$comprobar;
	}
		
	if($datosPost['idCliente']>0){
		
			$mod['cliente']=$Cliente->modificarDatosCliente($datosNuevos, $datosPost['idCliente']);
		
	}else{
		$mod['cliente']=$Cliente->addcliente($datosNuevos);
	}
	return $mod;
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



function montarHTMLimprimir($id, $BDTpv){
	$Ccli= new ClaseCliente($BDTpv);
	if($id>0){
		$datosCliente=$Ccli->getCliente($id);
		$nombre=$datosCliente['datos'][0]['Nombre'];
		$razonsocial=$datosCliente['datos'][0]['razonsocial'];
		$nif=$datosCliente['datos'][0]['nif'];
		$direccion=$datosCliente['datos'][0]['direccion'];
		$telefono=$datosCliente['datos'][0]['telefono'];
		$movil=$datosCliente['datos'][0]['movil'];
		$fax=$datosCliente['datos'][0]['fax'];
		$email=$datosCliente['datos'][0]['email'];
	}else{
		$nombre="";
		$razonsocial="";
		$nif="";
		$direccion="";
		$telefono="";
		$movil="";
		$fax="";
		$email="";
	}
	
	$textolegal="En aras a dar cumplimiento al Reglamento (UE) 2016/679 del Parlamento Europeo y del Consejo, de 27 de abril de 2016,
relativo a la protección de las personas físicas en lo que respecta al tratamiento de datos personales y a la libre circulación de
estos datos, y siguiendo las Recomendaciones e Instrucciones emitidas por la Agencia Española de Protección de Datos
(A.E.P.D.), SE INFORMA:
- Los datos de carácter personal solicitados y facilitados por usted, son incorporados un fichero de titularidad privada
cuyo responsable y único destinatario es XXXEMPRESAXXX.
- Solo serán solicitados aquellos datos estrictamente necesarios para prestar adecuadamente los servicios solicitados,
pudiendo ser necesario recoger datos de contacto de terceros, tales como representantes legales, tutores, o personas
a cargo designadas por los mismos.
- Todos los datos recogidos cuentan con el compromiso de confidencialidad, con las medidas de seguridad establecidas
legalmente, y bajo ningún concepto son cedidos o tratados por terceras personas, físicas o jurídicas, sin el previo
consentimiento del cliente, tutor o representante legal, salvo en aquellos casos en los que fuere imprescindible para
la correcta prestación del servicio.
- Una vez finalizada la relación entre la empresa y el cliente los datos serán archivados y conservados, durante un
periodo tiempo mínimo de _________________________, tras lo cual seguirá archivado o en su defecto serán
devueltos íntegramente al cliente o autorizado legal.
- Los datos que facilito serán incluidos en el Tratamiento denominado Clientes de XXXEMPRESAXXX, con la finalidad de
gestión del servicio contratado, emisión de facturas, contacto..., todas las gestiones relacionadas con los clientes y
manifiesto mi consentimiento. También se me ha informado de la posibilidad de ejercitar los derechos de acceso,
rectificación, cancelación y oposición, indicándolo por escrito a XXXEMPRESAXXX con domicilio en
________________________________________________________.
- Los datos personales sean cedidos por XXXEMPRESAXXX a las entidades que prestan servicios a la misma. ";
	$imprimir['cabecera']= <<<EOD
	<br></br>
	<font size="15">Nombre de la empresa </font><br>
	<font size="15">Ficha de Cliente $nombre</font><br>
	<font size="15">Razón social $razonsocial</font><br>
	<font size="15">NIF $nif</font><br>
	<font size="15">Dirección $direccion</font><br>
	<font size="15">Teléfono $telefono</font><br>
	<font size="15">Móvil $movil</font><br>
	<font size="15">Fax $fax</font><br>
	<font size="15">Email $email</font><br><br>
	<font size="10">$textolegal</font><br>
	<font>Firma Cliente</font>
	
EOD;
	$imprimir['html'] .='<table WIDTH="80%"></table>';
	return $imprimir;
	}

?>
