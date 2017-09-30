<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvOlalla de Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero ,
 * @Descripcion	Funciones para importar datos de Virtuemart a Tpv
 * */
 function crearTablaTempArticulosComp ($BDVirtuemart,$BDTpv)
 {
	//@ Objetivo crear las tablastmp_articulosCompleta temporales de las base datos (tpv y Virtuemart);
	$resultado = array();
	$sql = 	'CREATE TEMPORARY TABLE tmp_articulosCompleta (
				idArticulo int(11) AUTO_INCREMENT PRIMARY KEY,
				crefTienda VARCHAR(18),
				idTienda int(11),
				articulo_name VARCHAR(100),iva DECIMAL(4,2),
				codbarras VARCHAR(18),
				beneficio DECIMAL(5,2),costepromedio DECIMAL(17,6),
				estado VARCHAR(12),
				pvpCiva DECIMAL(17,6),
				pvpSiva DECIMAL(17,6),
				idProveedor int(11),
				fecha_creado DATETIME,fecha_modificado DATETIME)';
	if ($BDTpv->query($sqk) === TRUE) {
		printf("Se creó con éxtio la tabla myCity.\n");
		$resultado['BDTpv']['creado'] = true;
	}else {
		printf("Algo paso.. no salio bien.\n");
		$resultado['BDTpv']['creado'] = false;
	}
	
	
	
	return $resultado;
}
function borradoTablaTempArticulosComp ($BDVirtuemart,$BDTpv)
 {
	  //@ Objetivo borrar las tablas tmp_articulosCompleta temporales de las base datos (tpv y Virtuemart);
	  
}

function InsertPvpCivaTempArticulosComp ($BDVirtuemart,$BDTpv)
 {
	  //@ Objetivo crear campo idArticulo autoincremental y cubri el campo de precio con iva
	  
}




?>
