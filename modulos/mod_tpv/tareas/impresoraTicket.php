<?php 
/**
 * This print-out shows how large the available font sizes are. It is included
 * separately due to the amount of text it prints.
 *
 * @author Michael Billington <michael.billington@gmail.com>
 */

/* Problemas encontrados con codePage 
 * - No imprime €
 * Una vez identificado el $numeroIDCodiPage con la instruccion:
 *  selectCharacterTable($table), ya podemos cambiar para imprimir el caracter chr(128) que es el €
 * El problema es que con esa codigo de caracteres, no muestra correctamente otros caracteres como é
 * */

require  $URLCom.'/lib/escpos-php/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\CapabilityProfiles\DefaultCapabilityProfile;
$profile = DefaultCapabilityProfile::getInstance();

$connector = new FilePrintConnector($ruta_impresora);

$printer = new Printer($connector,$profile);
// Ahora obtenemos Code Pages CP1252 que contiene el simbolo Euro
$codes = $profile -> getCodePages();
foreach ($codes as $table => $name){
	if (strtolower($name -> getId()) == 'cp1252'){
		// Obtenemos la numero de la tabla que es CP1252 para imprimir Euro.
		// Voy a utilizar varios codepage en el ticket
		$numeroIdCodigoPage = $table;
		
	}
}

/* Initialize */
				$printer -> initialize();
				// Reseteamos CODE PAGE para imprimir correctamente carateres
				$printer -> selectCharacterTable();

				$printer -> setJustification(Printer::JUSTIFY_CENTER);
				$printer -> setTextSize(4, 2);
				$printer -> text($datosImpresion['cabecera1']);
				$printer -> selectPrintMode(); // Reset
				$printer -> text($datosImpresion['cabecera1-datos']);

				$printer -> setTextSize(2,1);
				$printer -> text($datosImpresion['cabecera2']);
				$printer -> selectPrintMode(); // Reset
				$printer -> text($datosImpresion['cabecera2-datos']);
				
				$printer -> selectPrintMode(); // Reset

				$printer -> text($datosImpresion['body']);
				$printer -> text($datosImpresion['pie-datos']);
				$printer -> setJustification(Printer::JUSTIFY_RIGHT);
				$printer -> text('Total:');
				$printer -> setTextSize(2,2);
				// cambiamos Code Page a CP1252 para imprima € que es el chr(128) 
				$printer -> selectCharacterTable($numeroIdCodigoPage);

				$printer -> text($datosImpresion['pie-total'].chr(128)."\n");
				$printer -> selectPrintMode(); // Reset
				$printer -> text('Forma pago:');
				$printer -> text($datosImpresion['pie-formaPago']."  - Entregado:");
				$printer -> text($datosImpresion['pie-entregado']."\n");

				$printer -> text('Cambio:');
				$printer -> text($datosImpresion['pie-cambio']."\n");
				

				$printer -> selectPrintMode(); // Reset
				$printer -> setJustification(Printer::JUSTIFY_CENTER);
				$printer -> text($datosImpresion['pie-datos2']."\n");
				$printer -> text(' '."\n"."\n");
				$printer -> cut();
				$printer -> close();
				
