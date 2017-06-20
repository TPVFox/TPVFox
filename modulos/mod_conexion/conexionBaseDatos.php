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
/*************   Realizamos conexion de base de datos de ImportarRecambios.          ************/
/************************************************************************************************/
$Conexiones [1]['NombreBD'] = "tpv";
$BDImportRecambios = new mysqli("localhost", "tpv", "tpv", "tpvolalla");
// Como connect_errno , solo muestra el error de la ultima instrucción mysqli, tenemos que crear una propiedad, en la que 
// está vacía, si no se produce error.
if ($BDImportRecambios->connect_errno) {
		$Conexiones [1]['conexion'] = 'Error';
		$Conexiones [1]['respuesta']=$BDImportRecambios->connect_errno.' '.$BDImportRecambios->connect_error;
		$BDImportRecambios->controlError = $BDImportRecambios->connect_errno.':'.$BDImportRecambios->connect_error;
} else {
	$Conexiones [1]['conexion'] ='Correcto';
	$Conexiones [1]['respuesta']= $BDImportRecambios->host_info;
/** cambio del juego de caracteres a utf8 */
 mysqli_query ($BDImportRecambios,"SET NAMES 'utf8'");
}

/************************************************************************************************/
/*****************   Realizamos conexion de base de datos de Recambios.          ****************/
/************************************************************************************************/
$Conexiones [2]['NombreBD'] = "recambios";
$BDRecambios = @new mysqli("localhost", "coches", "coches", "recambios");
// Como connect_errno , solo muestra el error de la ultima instrucción mysqli, tenemos que crear una propiedad, en la que 
// está vacía, si no se produce error.

if ($BDRecambios->connect_errno) {
		$Conexiones [2]['conexion'] = 'Error';
		$Conexiones [2]['respuesta'] = $BDRecambios->connect_errno.' '.$BDRecambios->connect_error;
		$BDRecambios->controlError = $BDRecambios->connect_errno.':'.$BDRecambios->connect_error;
} else {
	/** cambio del juego de caracteres a utf8 */
	$Conexiones [2]['conexion'] ='Correcto';
	$Conexiones [2]['respuesta'] = $BDRecambios->host_info;
	mysqli_query ($BDRecambios,"SET NAMES 'utf8'");	
}



/************************************************************************************************/
/*****************   Realizamos conexion de base de datos Web Multipiezas        ****************/
/************************************************************************************************/
// Hay que tener en cuenta que los prefijos de instalación cambian
// que los usuarios y contraseña cambian según instalacion, esto debería haber un 
// proceso configuracion y instalacion.
$Conexiones [3]['NombreBD'] = $nombreBDJoomla;
$Conexiones [3]['prefijoJoomla'] = $prefijoJoomla;
$BDWebJoomla = @new mysqli("localhost", "coches", "coches", $nombreBDJoomla);
// Como connect_errno , solo muestra el error de la ultima instrucción mysqli, tenemos que crear una propiedad, en la que 
// está vacía, si no se produce error.

if ($BDWebJoomla->connect_errno) {
		$Conexiones [3]['conexion'] = 'Error';
		$Conexiones [3]['respuesta'] = $BDWebJoomla->connect_errno.' '.$BDWebJoomla->connect_error;
		$BDWebJoomla->controlError = $BDWebJoomla->connect_errno.':'.$BDWebJoomla->connect_error;
} else {
/** cambio del juego de caracteres a utf8 */
	$Conexiones [3]['conexion'] = 'Correcto';
	$Conexiones [3]['respuesta']= $BDWebJoomla->host_info;
	mysqli_query ($BDWebJoomla,"SET NAMES 'utf8'");
}



/************************************************************************************************/
/*****************   Realizamos conexion de base de datos de Recambios.          ****************/
/************************************************************************************************/
$Conexiones [4]['NombreBD'] = "vehiculos";
$BDVehiculos = @new mysqli("localhost", "coches", "coches", "vehiculos");
// Como connect_errno , solo muestra el error de la ultima instrucción mysqli, tenemos que crear una propiedad, en la que 
// está vacía, si no se produce error.

if ($BDVehiculos->connect_errno) {
		$Conexiones [4]['conexion'] = 'Error';
		$Conexiones [4]['respuesta'] = $BDVehiculos->connect_errno.' '.$BDVehiculos->connect_error;
		$BDVehiculos->controlError = $BDVehiculos->connect_errno.':'.$BDVehiculos->connect_error;
} else {
	/** cambio del juego de caracteres a utf8 */
	$Conexiones [4]['conexion'] ='Correcto';
	$Conexiones [4]['respuesta'] = $BDVehiculos->host_info;
	mysqli_query ($BDVehiculos,"SET NAMES 'utf8'");	
}




?>
