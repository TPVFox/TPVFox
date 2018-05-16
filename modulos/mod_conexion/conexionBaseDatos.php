<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogoproductos - Funciones sincronizar.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero - SolucionesVigo
 * @Descripcion	Crear los arrya de las conexiones a BD que vamos utilizar.

$Conexiones = array(); 

// [Numero conexion]
//		[NombreBD] = Nombre de la base datos..
// 		[conexion] = Correcto o Error
//		[respuesta] =  Donde puede ser:
* 						-" Respuesta de conexion de error o de Correcta"
* 						- Info conexion ->host_info
* 								[][tablas]-> Nombre tabla.
*/


/***********************************************************************************************
 *************   Realizamos conexion de base de datos TPVFox.			****************
 ***********************************************************************************************/
$Conexiones[1]['NombreBD'] = $nombrebdMysqlImpor;
$Conexiones[2]['NombreBD'] = $nombrebdMysql;
$Conexiones[3]['NombreBD'] = $nombre_onlineBD;
// Lo ideal sería hacer un foreach y ejecutar leyendo array , el problem es el nombre conexión cambia.

// Evitamos errores si no tiene datos el parametro configuracion
if ($nombrebdMysqlImpor !=''){
	// conexion a importardbf
	$BDImportDbf = new mysqli("localhost", $usuarioMysqlImpor, $passwordMysqlImpor, $Conexiones [1]['NombreBD']);
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

}
//  conexion  a tpv
$BDTpv = new mysqli("localhost", $usuarioMysql, $passwordMysql, $Conexiones [2]['NombreBD']);
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
// Evitamos errores si no tiene datos el parametro configuracion
if ($nombre_onlineBD !=''){
	//  conexion  a Virtuemart
	$BDVirtuemart = new mysqli("localhost", $Usuario_onlineBD, $pass_onlineBD, $Conexiones [3]['NombreBD']);
	// Como connect_errno , solo muestra el error de la ultima instrucción mysqli, tenemos que crear una propiedad, en la que 
	// está vacía, si no se produce error.
	if ($BDVirtuemart->connect_errno) {
			$Conexiones[3]['conexion'] = 'Error';
			$Conexiones[3]['respuesta']=$BDVirtuemart->connect_errno.' '.$BDVirtuemart->connect_error;
			$BDTpv->controlError = $BDVirtuemart->connect_errno.':'.$BDVirtuemart->connect_error;
	} else {
		$Conexiones[3]['conexion'] ='Correcto';
		$Conexiones[3]['respuesta']= $BDVirtuemart->host_info;
		/** cambio del juego de caracteres a utf8 */
		 mysqli_query ($BDVirtuemart,"SET NAMES 'utf8'");
		 $nameBD = $Conexiones [3]['NombreBD'];
		 $sql = "SHOW TABLES FROM ".$nameBD;
		 $resultado = $BDVirtuemart->query($sql);
		$tablas = array();
		$i = 0;
		while ($fila = $resultado->fetch_row()) {
			$i++;
			$tablas[$i] = $fila[0];
		}
		$Conexiones[3]['tablas'] =$tablas;
	}
}



