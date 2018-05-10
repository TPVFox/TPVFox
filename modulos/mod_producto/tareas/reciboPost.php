<?php
/* Objetivo
 *  Preparar y GRABAR dando la informacion de lo que hizo o los errores posibles
 * 
 * */
 
echo '<pre>';
print_r($_POST);
echo '</pre>';
$preparados= prepararYgrabar($_POST,$CTArticulos);
// Comprobamos los datos antes de grabar.
if (isset($preparados['Sqls']['NuevoProducto'])){
	// Entonces es que creo uno nuevo.
	$preparado_nuevo = $preparados['Sqls']['NuevoProducto'];
	if (isset($preparado_nuevo['insert_articulos']['id_producto_nuevo'])){
		// Se añadio por lo menos a tabla articulos
		$id = $preparado_nuevo['insert_articulos']['id_producto_nuevo']; // Asi carga datos.
		// Montamos comprobaciones para enviar despues de cargar de nuevo producto.
		$success = array ( 'tipo'=>'success',
					 'mensaje' =>'Se creo el producto con id '.$id.' nuevo',
					 'dato' => $preparado_nuevo['consulta']
					);
		$preparados['Sqls']['comprobaciones'][] = $success;
		// Ahora comprobamos si añadio mas cosas en el articulo nuevo. 
		if (isset($preparado_nuevo['insert_articulos_precios'])){
			if (isset($preparado_nuevo['insert_articulos_precios']['Afectados'])){
				// Entiendo que la consulta fue correcta y que se añadio o no.
				$success = array ( 'tipo'=>'success',
					 'mensaje' =>'Se añadieron precios correctos en '
								.$preparado_nuevo['insert_articulos_precios']['Afectados'].' registros',
					 'dato' => $preparado_nuevo['consulta']
					);
				$preparados['Sqls']['comprobaciones'][] = $success;
			} else {
				// Hubo un error al insertar los precios.
				$preparados['Sqls']['comprobaciones'][] = $preparado_nuevo['insert_articulos_precios'];
			}
			
		}

	} else {
		// Quiere decir que hubo un error al principio
		$preparados['Sqls']['comprobaciones'][] = $preparado_nuevo['insert_articulos'];
	}
	if (isset($preparado_nuevo['codbarras'])){
		$preparados['Sqls']['codbarras'] = $preparado_nuevo['codbarras'];
	}
}
?>
