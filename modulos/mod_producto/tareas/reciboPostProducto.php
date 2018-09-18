<?php
/* Objetivo
 *  Preparar y GRABAR dando la informacion de lo que hizo o los errores posibles
 * 
 * */
 
$preparados = array();
if (isset($_POST['id'])){
	$id= $_POST['id'];
} else {
	// Hubo que haber un error
	exit();
}
// Comprobamos los datos y grabamos.
//~ echo '<pre>';
//~ print_r($_POST);
//~ echo '</pre>';
$DatosPostProducto= prepararandoPostProducto($_POST,$CTArticulos);
// Ahora vemos si hay advertencias de campos
if (isset($DatosPostProducto['comprobaciones'])){
	foreach ($DatosPostProducto['comprobaciones'] as $key =>$comprobacion){
		$preparados['comprobaciones'][] = $comprobacion;
		if ($comprobacion['tipo'] === 'danger'){
			echo '<pre>';
				print_r($comprobacion);
			echo '</pre>';
			exit();
		} 
	}
	
}
//~ echo '<pre>';
//~ print_r($DatosPostProducto);
//~ echo '</pre>';

//~ // --- Ahora comprobamos y grabamos ---- //

//  -----------------------------------     NUEVO  O  MODIFICADO 		------------------------------------- //
if ($id >0 ){
		// ------------------------            MODIFICADO 			-------------------------------//
		// ---			Comprobamos  y grabamos datos generales . 			--- //
		$comprobaciones = $CTArticulos->ComprobarNuevosDatosProducto($id,$DatosPostProducto);
		
		foreach ($comprobaciones as $comprobacion){
			if (isset($comprobacion['NAfectados'])){
				// Fue correcto el grabar.
				$success = array ( 'tipo'=>'success',
							 'mensaje' =>'Se ha grabado correctamente los cambios generales.',
							 'dato' => ' No controlo que cambios realizaron por separador en ComprobarNuevosDatosProducto'
							);
				$preparados['comprobaciones'][] = $success;
				//~ echo '<pre>';
				//~ print_r($preparados);
				//~ echo '</pre>';
			
			}
			if (isset($comprobacion['error'])){
				echo '<pre>';
				print_r($comprobacion);
				echo '</pre>';
				exit();
			}
		}
		// ---			Comprobamos  y  grabamos los precios venta .		---//
		
		
		$comprobaciones = $CTArticulos->ComprobarNuevosPreciosProducto($id,$DatosPostProducto,$Usuario['id']);
		foreach ($comprobaciones['mensajes'] as $mensaje){
			$preparados['comprobaciones'][]= $mensaje;
		}
				
		// ---			Comprobamos  y grabamos los codbarras .				---//
		$comprobaciones = $CTArticulos->ComprobarCodbarrasUnProducto($id,$DatosPostProducto['codBarras']);
		$preparados['codbarras'] = $comprobaciones;
        //~ echo '<pre>';
        //~ print_r($_POST);
        //~ echo '</pre>';
        $comprobaciones=$CTArticulos->ComprobarFamiliasProducto($id, $DatosPostProducto['familias']);
        $preparados['familias'] = $comprobaciones;
		// ---	Comprobamos y grabamos los proveedores . ---//
		$comprobaciones = $CTArticulos->ComprobarProveedoresCostes($id,$DatosPostProducto['proveedores_costes']);
//~ echo '<pre>';
//~ print_r($DatosPostProducto);
//~ echo '</pre>';
        foreach ($comprobaciones as $key => $comprobacion){
			
			if ($key === 'nuevo'){
				foreach ($comprobacion as $nuevo){
					if ($nuevo['error']){
					   $success = array ( 'tipo'=>'danger',
							 'mensaje' =>'Hubo un error al añadir un coste ,referencia de proveedor.',
							 'dato' => $nuevo
							);
					} else {
						$success = array ( 'tipo'=>'success',
							 'mensaje' =>'Se ha añadido proveedor .',
							 'dato' => $nuevo
							);
					}
				$preparados['comprobaciones']['proveedor_nuevo'] = $success;
				}
			}
	
			if ($key === 'modificado'){
				foreach ($comprobacion as $modificado){
					if ($modificado['error']){
					   $success = array ( 'tipo'=>'danger',
							 'mensaje' =>'Hubo un error al modificarr un coste ,referencia de proveedor.',
							 'dato' => $modificado
							);
					} else {
						$success = array ( 'tipo'=>'success',
							 'mensaje' =>'Se ha modificado proveedor .',
							 'dato' => $modificado
							);
					}
				$preparados['comprobaciones']['proveedor_modificado'] = $success;
				}
			}
			
		}
		
		$comprobaciones= $CTArticulos->ComprobarReferenciaProductoTienda($id, $DatosPostProducto['refProducto']);

} else {
		// ----------------------------  			NUEVO 				  ------------------------  //
	
		$comprobaciones = $CTArticulos->comprobacionCamposObligatoriosProducto($DatosPostProducto);
		if (count($comprobaciones)=== 0){
          
			$anhadir = $CTArticulos->AnhadirProductoNuevo($DatosPostProducto);
			$DatosPostProducto['Sqls']['NuevoProducto']=$anhadir;
			// Se creo uno NUEVO fijo.
			if (isset($anhadir['insert_articulos']['id_producto_nuevo'])){
				// Ponemos el id para poder mostrar los datos ya grabados.
				$id = $anhadir['insert_articulos']['id_producto_nuevo']; 
				// Montamos comprobaciones para enviar despues de cargar de nuevo producto.
				$success = array ( 'tipo'=>'success',
							 'mensaje' =>'Se creo el producto con id '.$id.' nuevo',
							 'dato' => $anhadir['consulta']
							);
				$preparados['comprobaciones'][] = $success;
				// Ahora comprobamos si añadio mas cosas en el articulo nuevo. 
				if (isset($anhadir['insert_articulos_precios'])){
					if (isset($anhadir['insert_articulos_precios']['Afectados'])){
						// Entiendo que la consulta fue correcta y que se añadio o no.
						$success = array ( 'tipo'=>'success',
							 'mensaje' =>'Se añadieron precios correctos en '
										.$anhadir['insert_articulos_precios']['Afectados'].' registros',
							 'dato' => $anhadir['consulta']
							);
						$preparados['comprobaciones'][] = $success;
					} else {
						// Hubo un error al insertar los precios.
						$preparados['comprobaciones'][] = $anhadir['insert_articulos_precios'];
					}
				}
				if (isset($anhadir['codbarras'])){
					$preparados['codbarras'] = $anhadir['codbarras'];
				}
				if(isset($anhadir['RefTienda'])){
					$preparados['RefTienda']=$anhadir['RefTienda'];
				}

			} 
		}else {
			// Quiere decir que hubo un error al principio
			$preparados['comprobaciones'][] = $comprobaciones;
		}
		
}
//~ echo '<pre>';
//~ print_r($preparados);
//~ echo '</pre>';
?>
