<?php
$busqueda = $_POST['valorCampo'];
$campoAbuscar = $_POST['campo'];
$id_input = $_POST['cajaInput'];
$deDonde = $_POST['dedonde']; // Obtenemos de donde viene

if ($id_input === "Codbarras") {
	// Si la busqueda es por codbarras y comprobamos el codbarras es propio, 
	// es decir que empiece por 21 o 20
	// YA que entonces tendremos que buscar por referencia.
	include ("./../../controllers/codbarras.php");
	$Ccodbarras = new ClaseCodbarras ; 
	$codigo_correcto = $Ccodbarras->ComprobarCodbarras($busqueda);
	if ($codigo_correcto === 'OK'){
		// Se comprobo código barras y es correcto.
		$codBarrasPropio= $Ccodbarras->DesgloseCodbarra($busqueda);
		if (count($codBarrasPropio)>0){
			// Obtenemos el campo a buscar de parametros de referencia, porque lo necesitamos
			// Cargamos los fichero parametros.
			include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
			$ClasesParametros = new ClaseParametros('parametros.xml');
			//~ $parametros = $ClasesParametros->getRoot();
			$xml_campo_cref = $ClasesParametros->Xpath('cajas_input//caja_input[nombre="cajaReferencia"]//parametros//parametro[@nombre="campo"]');
			$campoAbuscar =(string)$xml_campo_cref[0];
			$id_input='Referencia';
			$codBarrasPropio['codbarras_leido'] = $busqueda; // Guardamos en array el codbarras leido
			$busqueda= $codBarrasPropio['referencia'];
		}
	}
}

$respuesta = BuscarProductos($id_input,$campoAbuscar,$busqueda,$BDTpv);
if (!isset($respuesta['datos'])){
		// Para evitar error envio, lo generamos vacio..
		$respuesta['datos']= array();
	}
if ($respuesta['Estado'] !='Correcto' ){
	// Al ser incorrecta entramos aquí.
	// Mostramos popUp tanto si encontro varios como si no encontro ninguno.
	$respuesta['listado']= htmlProductos($respuesta['datos'],$id_input,$campoAbuscar,$busqueda);
}
if ($respuesta['Estado'] === 'Correcto' && $deDonde === 'popup'){
	// Cambio estado para devolver que es listado.
	$respuesta['listado']= htmlProductos($respuesta['datos'],$id_input,$campoAbuscar,$busqueda);
	$respuesta['Estado'] = 'Listado';
}

if ( isset($codBarrasPropio)){
	if (count($codBarrasPropio)>0){
		// Si hay datos , nos enviamos referencia y (precio o peso) obtenidos.
		$respuesta['codBarrasPropio'] = $codBarrasPropio;
		if (count($respuesta['datos'])=== 1){
			// Solo permito cambiar datos si hay un solo resultado.
			$respuesta['datos'][0]['codBarras'] = $codBarrasPropio['codbarras_leido'];
			$respuesta['datos'][0]['crefTienda'] = $codBarrasPropio['referencia'];
			if (isset($codBarrasPropio['peso'])){
				// [OJO] aquí cambiaría si tuvieramos activado campo de cantidad/peso, ya que es donde lo podríamos.
				$respuesta['datos'][0]['unidad'] = $codBarrasPropio['peso'];
			}
			if (isset($codBarrasPropio['precio'])){
				$respuesta['datos'][0]['pvpCiva'] = $codBarrasPropio['precio'];
			}
		// Ahora cambiamos $respuesta['datos'] , el peso o precio para referencia
		
		}
	}
}
$respuesta['dedonde'] = $deDonde; // Enviamos de donde para tratarlo en javascript.
?>
