<?php 
//@Objetivo:
	//Busca el proveedor según el dato insertado , si el dato viene de la caja idProveedor entonces busca por id
	//Si no busca por nombre del proveedor y muestra un modal con las coincidencias ,
	//Si no recibe busqueda muestra un modal con todos los nombres de los proveedores 
	// Contiene el control de errores de las funciones que llama a la clase proveedor


    // Saber si el valor de busqueda esta vacio.
    $buscar= array();
    $respuesta['Nitems']= 0; // por defecto
    $respuesta['id'] = 0; 
        // Tiene valor para buscar proveedor o proveedores.
        if ( $_POST['idcaja'] === "id_proveedor"){
            // Buscamos por id, pero el resultado siempre es uno..por lo que
            // sino se cambia el metodo, no podemos nunca buscar por id varios proveedores.
            $buscar=$CProveedores->buscarProveedorIdEstado($_POST['busqueda']);
        } else {
            // Buscamos por nombre, el resultado siempre es un array de arrays con uno mas proveedores.
            $buscar=$CProveedores->buscarProveedorNombreEstado($_POST['busqueda']);
        }
        if (isset($buscar['error'])){
            // Existe un error en la consulta
            $respuesta['error']=$buscar['error'];
            $respuesta['consulta']=$buscar['consulta'];
        } else {
            // NO hubo error, continuamos
            if (isset($buscar['idProveedor'])) {
                // Obtuvo un resultado.
                $respuesta['id']=$buscar['idProveedor'];
                $respuesta['nombre']=$buscar['nombrecomercial'];
                $respuesta['Nitems']=1;
            } else {
                // Obtuvo varios resultados o ninguno
                if (!isset($buscar['datos'])){
                    // No obtuvo resultados.
                    $respuesta['datos'] = '';
                    $buscar['datos'] = array();
                } else {
                $respuesta['Nitems'] = count($buscar['datos']);
                $respuesta['datos']=$buscar['datos'];
                }
            }
        }
    if (strlen(trim($_POST['busqueda'])) == 0 ){
        // Buscamos los datos de todos los proveedores ya que no tiene valor busqueda.
        $buscar['datos'] = $CProveedores->todosProveedoresActivos();
        $respuesta['Nitems'] = count( $buscar['datos']);
    }
    if ($_POST['idcaja']!=='id_proveedor'){
        $respuesta['html']=htmlProveedores($_POST['busqueda'],$_POST['dedonde'], $_POST['idcaja'], $buscar['datos']);
    }



?>
