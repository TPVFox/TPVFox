<?php 
//@Objetivo:
	//Busca el proveedor según el dato insertado , si el dato viene de la caja idProveedor entonces busca por id
	//Si no busca por nombre del proveedor y muestra un modal con las coincidencias ,
	//Si no recibe busqueda muestra un modal con todos los nombres de los proveedores 
	// Contiene el control de errores de las funciones que llama a la clase proveedor


    // Saber si el valor de busqueda esta vacio.
    $buscar= array();
    $respuesta= array(  'Nitems'=> 0); 

    if ($_POST['idcaja']==="id_cliente"){
        $buscar=$Ccliente->DatosClientePorId($_POST['busqueda']);
        // Buscamos por id, pero el resultado siempre es uno..por lo que
        // sino se cambia el metodo, no podemos nunca buscar por id varios clientes.
    } else {
        // Buscamos por nombre, el resultado siempre es un array de arrays con uno o mas clientes.
        $buscar=$Ccliente->BuscarClientePorNombre($_POST['busqueda']);
    }
    if (isset($buscar['error'])){
        // Existe un error en la consulta
            $respuesta['advertencia']=$buscar;
    } else {
        // Obtuvo un resultado.
        // Ahora compruebo que su estado es NO es inactivo
        if (isset($buscar['idClientes'])){
            //NO es inactivo
            if($buscar['estado']!=='inactivo'){
                $respuesta['id']=$buscar['idClientes'];
                $respuesta['nombre']=$buscar['Nombre'];
                $respuesta['Nitems']=1;
                // realmente esto hace falta, pienso que no lo utilizamos
                $respuesta['formasVenci']=$buscar['formasVenci'];
            }else{
                // Si estado INACTIVO no tiene un id, ya queremos que nos habrá popup.
                // convierto el resultado en un array de array
                $buscar['datos']['0'] =$buscar;
            }
        }
    }
     // Obtuvo varios resultados o ninguno
    if (!isset($buscar['datos'])){
        // No obtuvo resultados.
        $respuesta['datos'] = '';
        $buscar['datos'] = array();
    } else {
        $respuesta['Nitems'] = count($buscar['datos']);
        $respuesta['datos']=$buscar['datos'];
    }
    
    if (!isset($respuesta['id'])){
        $respuesta['html'] = htmlClientes($_POST['busqueda'],$_POST['dedonde'],$_POST['idcaja'], $buscar['datos']);
    }


?>
