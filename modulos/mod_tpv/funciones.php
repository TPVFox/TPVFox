<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvOlalla de Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero ,
 * @Descripcion	Funciones para importar datos Dbf a Mysql
 * */

//Funcion donde se lee Dbf y se obtiene array *
//~ ,$numFinal,$numInic,$campos 

//busqueda , lo que queremos buscar
//campoAbuscar es codbarras, ref, o descripc campo a comparar
function BuscarProducto($campoAbuscar,$busqueda,$BDImportDbf) {
	// Objetivo:
	// Es buscar por Referencia o Codbarras
	//campos:
	//CREF es referencia
	//CCODEBAR es codigo de barras
	//campos a mostrar en hmtl:
		//CCODEBAR , CREF, CDETALLE, NPCONIVA, CTIPOIVA
		//codigobarras, ref, descripc, pvpConIva, tipoIva
		
		//**CASE tipoIva , S=4, R=10, G=21 % 
		// pvpSinIVA = NPVP
	
	//DETERMINAR si es una ref o un codigoBarras el dato que me pasan para buscar...
	
	if ($BDImportDbf->connect_errno) {
		echo 'error al conectar';
	} else {
		$sql = 'SELECT CCODEBAR,CREF,CDETALLE,NPCONIVA,CTIPOIVA FROM articulo WHERE '.$campoAbuscar.'='.$busqueda;
		$res = $BDImportDbf->query($sql);
		}
//	$resultado = array();
	
	
	$arr = array();
	$i = 0;
	while ($fila = $res->fetch_row()) {
		
		$arr[$i] = $fila;
		$i++;
	}
	
	return $arr;
}


function BuscarDescripcion($buscar,$BDImportDbf) {
	// Objetivo:
	// Es buscar por Referencia o Codbarras
	// Campos:
	//CDETALLE es descripcion
	$resultado = array();
	return $resultado;
}




?>
