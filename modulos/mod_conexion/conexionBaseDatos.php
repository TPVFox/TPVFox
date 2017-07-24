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

// Obtenemos las tablas para conexion.
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




?>
