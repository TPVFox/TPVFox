<?php 


// Funciones para vista Recambio unico.
function CopiarDescripcion($id,$DatosRefCruzadas,$prefijoJoomla,$BDWebJoomla) {
	// Function para añadir contenido a descripcion de tabla virtuemart_products_es_es.

	
	$resumen = array();
	$tabla = $prefijoJoomla."_virtuemart_products_es_es";
	// Borramos datos antes añadir.
	$whereC= "  WHERE `virtuemart_product_id`=".$id;
	$consulta = "UPDATE ".$tabla." SET `product_desc`=''".$whereC;
	$resultado = $BDWebJoomla->query($consulta);
	$Afectados = $BDWebJoomla->affected_rows;

	// Ahora dividimos los datos recibidos ya que si es muy grande el update falla.
	$DatosArray = str_split($DatosRefCruzadas,10000);
	$resumen ['PartirString'] =$DatosArray;
	foreach ( $DatosArray as $Datos) {
		$consulta = "UPDATE ".$tabla." SET `product_desc`= concat(product_desc,'".$Datos."')".$whereC;
		$resultado = $BDWebJoomla->query($consulta);
	}
	//~ $consulta = "UPDATE ".$tabla." SET `product_desc`= '".$DatosRefCruzadas."' ".$whereC;
	//~ $resultado = $BDWebJoomla->query($consulta);



	$resumen['consulta']=$consulta;
	$resumen['RowsAfectados']= $Afectados;
	return $resumen; 
}

?>
