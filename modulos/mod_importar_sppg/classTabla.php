<?php
// Objetivo es generar el mismo objeto para cada clase.
// el objeto tendra.
// nombre=> nombre de la tabla
// comprobaciones => Nombre de funciones a comproba
function xml_attribute($object, $attribute)
{
    if(isset($object[$attribute]))
        return (string) $object[$attribute];
}
	
	


?>
