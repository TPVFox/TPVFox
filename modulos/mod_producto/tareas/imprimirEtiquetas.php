<?php 
$respuesta = array();
		$IdsProductos=json_decode($_POST['productos']);
		$idTienda=$_POST['idTienda'];
		$tamano=$_POST['tamano'];
		$productos = array();
		foreach ($IdsProductos as $id){
			$productos[]= $NCArticulo->getProducto($id);
		}
        //~ echo '<pre>';
        //~ print_r($productos);
        //~ echo '</pre>';
		$dedonde="Etiqueta";
		$nombreTmp=$dedonde."etiquetas.pdf";
		switch ($tamano){
			case '1':
				$imprimir=ImprimirA8($productos);
			break;
			case '2':
				$imprimir=ImprimirA5($productos);
			break;
            case '2F':
				$imprimir=ImprimirA5($productos);
			break;
			case '3':
				$imprimir=ImprimirA7($productos);
			break;
			case '4':
				$imprimir=ImprimirA9($productos);
            break;
		}
		
		$cabecera=$imprimir['cabecera'];
		$html=$imprimir['html'];
		//~ $ficheroCompleto=$html;
		//~ require_once($rutaCompleta.'/lib/tcpdf/tcpdf.php');
		include ($rutaCompleta.'/clases/imprimir.php');
		include($rutaCompleta.'/controllers/planImprimirRe.php');
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
		$respuesta['html']=$html;
		$respuesta['fichero'] = $ficheroCompleto;
		$respuesta['productos'] = $productos;
		
