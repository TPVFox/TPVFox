<?php 
$respuesta = array();
		$articulos=json_decode($_POST['productos'],true);
		$tamano=$_POST['tamano'];
		$productos = array();
		foreach ($articulos as $key=>$articulo){
			$productos[]= $NCArticulo->getProducto($articulo['idArticulo']);
            $productos[$key]['numEtiquetas'] = $articulo['numEtiquetas'];
            if ( $ClasePermisos->getModulo('mod_balanza') == 1) {
                // Ahora obtenemos los las teclas de las balanza en los que esté este producto.
                $relacion_balanza = $NCArticulo->obtenerTeclaBalanzas($articulo['idArticulo']);
                if (!isset($relacion_balanza['error'])){
                    // Quiere decir que se obtuvo algun registro.Array ['idBalanza']['plu']['tecla']
                    // demomento tomamos solo plu y del primer item.
					if (isset($relacion_balanza[0]['seccion']) && $relacion_balanza[0]['seccion'] != '') {
						// Si tiene seccion, la ponemos como plu.
						$productos[$key]['plu'] = $relacion_balanza[0]['seccion'] . '-' . $relacion_balanza[0]['plu'];
					} else {
						// Si no tiene seccion, ponemos el plu normal.
						$productos[$key]['plu'] = $relacion_balanza[0]['plu'];
					}
                }
            }
		}
		$dedonde="Etiqueta";
		$nombreTmp=$dedonde."etiquetas.pdf";
		switch ($tamano){
			case '1':
				$imprimir=ImprimirEtiquetas($productos,'A5');
			break;
            case '1T':
				$imprimir=ImprimirEtiquetas($productos,'A5','tecla');
			break;
            case '2':
                $imprimir=ImprimirEtiquetas($productos,'A7');
			break;
			case '2T':
                $imprimir=ImprimirEtiquetas($productos,'A7','tecla');
			break;
            case '3':
                $imprimir=ImprimirEtiquetas($productos,'A8');
			break;
			case '4':
                $imprimir=ImprimirEtiquetas($productos,'A9');
            break;
		}
		
		$cabecera=$imprimir['cabecera'];
		$html=$imprimir['html'];
		
		include ($rutaCompleta.'/clases/imprimir.php');
		include($rutaCompleta.'/controllers/planImprimirRe.php');
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
		$respuesta['html']=$html;
		$respuesta['fichero'] = $ficheroCompleto;
		$respuesta['productos'] = $productos;
		
