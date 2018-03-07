<?php
// fuente: http://mimosa.pntic.mec.es/jgomez53/matema/practica/digcontrol.htm


class ClaseCodbarras 
{
	function ObtenerDigitoControl($codbarras_sinDC){
		// Objetivo es obtener el digito de control de un secuencia numeros
		$cod_array= str_split($codbarras_sinDC); // Creamos array sin digito de control 
		$position =0;
		$suma= 0 ;
		foreach ($cod_array as $valor){
			$position = $position +1 ; // Recuerda que un array empieza posicion 0 , no puedes utilizar Key
			if ($position%2 === 0){
				// Posicion par
				$suma = $suma+(intval($valor)*3);
			} else {
				$suma = $suma+ intval($valor);
			}
			$digito_control = 10-($suma%10);
		}
		return $digito_control;
	}
	
	
	function ComprobarCodbarras($codbarras){
	// Objetivo
	// Comprobar que el digito de control del codbarras recibido es correcto.
	$ultimo_digito = substr($codbarras, -1); // Obtenermos digito de control
	$codbarras_sinDC= substr($codbarras, 0, -1); // Obtenemos codbarras sin digito de control

	$digito_control = $this->ObtenerDigitoControl($codbarras_sinDC);
	// Ahora comprobamos si el digito de control es igual al ultimo digito del codbarras.
		if ($digito_control === intval($ultimo_digito)){
			$resultado = 'OK';
		} else {
			$resultado = 'error';
		}
	
	return $resultado;
	}
	
	function DesgloseCodbarra($codbarras){
		// Objetivo 
		// Es comprobar si el codigo barras pertenece al grupo 20 o 21, si es asi, deglosamos los valores.
		// Parametros:
		// Codbarras completo ( 13 digitos )
		$resultado= array();
		if (strlen($codbarras) === 13) {
			if (substr($codbarras, 0, 2) === "20" || substr($codbarras, 0, 2) === "21") {
			// Entramos si tiene 13 digitos, si empieza por 21 o 20....
				if (substr($codbarras, 0, 2) === "20") {
					// Obtenemos peso en gramos, referencia
					$resultado['referencia'] = substr($codbarras, 2, 5);
					$entero = substr($codbarras, 7, 2);
					$decimal = substr($codbarras, 9, 3);
					$resultado['peso'] = floatval($entero.'.'.$decimal);
				}
				if (substr($codbarras, 0, 2) === "21") {
					// Obtenemos precio, referencia
					$resultado['referencia'] = substr($codbarras, 2, 5);
					// Ahora tengo que tratar precio para obtener decimales.
					$entero = substr($codbarras, 7, 3);
					$decimal = substr($codbarras, 10, 2);
					
					$resultado['precio'] = floatval($entero.'.'.$decimal);
				}
			}
		}
		return $resultado;
	}
}


?>
