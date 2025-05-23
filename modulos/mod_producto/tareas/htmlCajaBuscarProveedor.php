<?php
$respuesta 		= array();
		$dedonde 		= 'producto';
		$busqueda =  $_POST['busqueda']; // Este valor puede venir vacio , por lo que...
        $DescartIdsProv = isset($_POST['idsProveedores'])? $_POST['idsProveedores'] : array(); // Descartamos los ids de los proveedores que ya tiene
                                                                                               // el producto, para que no pueda seleccionarlo.
		
		$novalidos = array();
		$descartados = array();
		if ($busqueda !==''){ 
			// Realizamos la busqueda todos los proveedores menos los que tiene añadidos en el producto..
			$proveedores = $CProveedor->buscarProveedorNombre($busqueda);
			if (!is_null($proveedores['datos'])){
				// Ahora tengo que quitar del array proveedores[datos], aquellos que no ya estan añadidos para que no se muestre.
				foreach ($proveedores['datos'] as $key=>$proveedor){
					$idProveedor = $proveedor['idProveedor'];
					if (in_array ($idProveedor,$DescartIdsProv)){
						$descartados[] = $proveedor;
						unset($proveedores['datos'][$key]);
					};
				}
			}
		} else {
			$proveedores = array();
			$proveedores['datos'] = array(); // ya enviamos datos... :-)
		}
		$respuesta = htmlBuscarProveedor($busqueda,$dedonde,$proveedores['datos'],$descartados);
		$respuesta['proveedores'] = $proveedores;
		$respuesta['busqueda'] = $busqueda;
		$respuesta['descartados'] = $descartados;
	