<?php 
// Creamos Array $Conexiones para obtener datos de conexiones
// teniendo en cuenta que le llamo a conexiones  a cada conexion a la Bases de Datos..
$Conexiones = array(); 

// [Numero conexion]
//		[NombreBD] = Nombre de la base datos..
// 		[conexion] = Correcto o Error
//		[respuesta] = " Respuesta de conexion de error o de Correcta"
//		[VariableConf] = Nombre variable de configuracion




/************************************************************************************************/
/*************   Realizamos conexion de base de datos TPVFox.				         ************/
/************************************************************************************************/
$Conexiones[1]['NombreBD'] = "importarDbf";
$Conexiones[2]['NombreBD'] = "tpv";
//~ $conexiones = array();
//~ $conexiones['importar'] = array();
//~ $conexiones['importar']['datos'] = array();
//~ $conexiones['importar']['datos']['servidor'] = "localhost";
//~ $conexiones['importar']['datos']['usuario'] = $usuarioMsyql;
//~ $conexiones['importar']['datos']['password'] = $passwordMysql;
//~ $conexiones['importar']['datos']['database'] = "importarDbf";
//~ $conexiones['importar']['cursor'] = new mysqli($conexiones['importar']['datos']['servidor'], $conexiones['importar']['datos']['usuario'], $conexiones['importar']['datos']['password'], $conexiones['importar']['datos']['database']);



// conexion a importardbf
$BDImportDbf = new mysqli("localhost", $usuarioMsyql, $passwordMysql, $Conexiones [1]['NombreBD']);
// Como connect_errno , solo muestra el error de la ultima instrucción mysqli, tenemos que crear una propiedad, en la que 
// está vacía, si no se produce error.
if ($BDImportDbf->connect_errno) {
		$Conexiones[1]['conexion'] = 'Error';
		$Conexiones[1]['respuesta']=$BDImportDbf->connect_errno.' '.$BDImportDbf->connect_error;
		$BDImportDbf->controlError = $BDImportDbf->connect_errno.':'.$BDImportDbf->connect_error;
} else {
	$Conexiones[1]['conexion'] ='Correcto';
	$Conexiones[1]['respuesta']= $BDImportDbf->host_info;
	/** cambio del juego de caracteres a utf8 */
	 mysqli_query ($BDImportDbf,"SET NAMES 'utf8'");
	 $nameBD = $Conexiones [1]['NombreBD'];
	 $sql = "SHOW TABLES FROM ".$nameBD;
	 $resultado = $BDImportDbf->query($sql);
	$tablas = array();
	$i = 0;
	while ($fila = $resultado->fetch_row()) {
		$i++;
		$tablas[$i] = $fila[0];
	}
	
	
	$Conexiones[1]['tablas'] =$tablas;
	

}


//  conexion  a tpv
$BDTpv = new mysqli("localhost", $usuarioMsyql, $passwordMysql, $Conexiones [2]['NombreBD']);
// Como connect_errno , solo muestra el error de la ultima instrucción mysqli, tenemos que crear una propiedad, en la que 
// está vacía, si no se produce error.
if ($BDTpv->connect_errno) {
		$Conexiones[2]['conexion'] = 'Error';
		$Conexiones[2]['respuesta']=$BDTpv->connect_errno.' '.$BDTpv->connect_error;
		$BDTpv->controlError = $BDTpv->connect_errno.':'.$BDTpv->connect_error;
} else {
	$Conexiones[2]['conexion'] ='Correcto';
	$Conexiones[2]['respuesta']= $BDTpv->host_info;
	/** cambio del juego de caracteres a utf8 */
	 mysqli_query ($BDTpv,"SET NAMES 'utf8'");
	 $nameBD = $Conexiones [2]['NombreBD'];
	 $sql = "SHOW TABLES FROM ".$nameBD;
	 $resultado = $BDTpv->query($sql);
	$tablas = array();
	$i = 0;
	while ($fila = $resultado->fetch_row()) {
		$i++;
		$tablas[$i] = $fila[0];
	}
	
	
	$Conexiones[2]['tablas'] =$tablas;
	

}



?>
