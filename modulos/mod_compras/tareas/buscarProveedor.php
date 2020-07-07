<?php 
//@Objetivo:
	//Busca el proveedor según el dato insertado , si el dato viene de la caja idProveedor entonces busca por id
	//Si no busca por nombre del proveedor y muestra un modal con las coincidencias ,
	//Si no recibe busqueda muestra un modal con todos los nombres de los proveedores 
	// Contiene el control de errores de las funciones que llama a la clase proveedor


    // Saber si el valor de busqueda esta vacio.
    $buscar= array();
    $respuesta['Nitems']= 0; // por defecto
    
        // Tiene valor para buscar proveedor o proveedores.
        if ( $_POST['idcaja'] === "id_proveedor"){
            // Buscamos por id, pero el resultado siempre es uno..por lo que
            // sino se cambia el metodo, no podemos nunca buscar por id varios proveedores.
            $buscar=$CProveedores->buscarProveedorId($_POST['busqueda']);
        } else {
            // Buscamos por nombre, el resultado siempre es un array de arrays con uno mas proveedores.
            $buscar=$CProveedores->buscarProveedorNombre($_POST['busqueda']);
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
                // Obtuvo varios resultados.
                $respuesta['Nitems'] = count($buscar['datos']);
                if (count($buscar) > 0){
                    $respuesta['datos']=$buscar['datos'];
                }
            }
        }
    if (strlen(trim($_POST['busqueda'])) == 0 ){
        // Buscamos los datos de todos los proveedores ya que no tiene valor busqueda.
        $buscar['datos'] = $CProveedores->todosProveedores();
        $respuesta['Nitems'] = count( $buscar['datos']);
    } 
    
    if  ( $respuesta['Nitems'] !== 1 ){
        // Ahoras si tenemos mas de un resultado O estamos en modal, cargamos el html para modal de busqueda
        $respuesta['html']=htmlProveedores($_POST['busqueda'],$_POST['dedonde'], $_POST['idcaja'], $buscar['datos']);
    }



    
//~ if (isset($_POST['idcaja']) && $_POST['idcaja'] =="id_proveedor"){
    
    //~ $buscarId=$CProveedores->buscarProveedorId($_POST['busqueda']);
    //~ if (isset($buscarId['error'])){
        //~ $respuesta['error']=$buscarId['error'];
        //~ $respuesta['consulta']=$buscarId['consulta'];
    //~ }else{
        //~ if (isset($buscarId['idProveedor'])){
            //~ $respuesta['id']=$buscarId['idProveedor'];
            //~ $respuesta['nombre']=$buscarId['nombrecomercial'];
            //~ $respuesta['Nitems']=1;
        //~ }else{
            //~ $respuesta['Nitems']=2;
        //~ }
    //~ }
//~ }else{
    //~ $buscarTodo=$CProveedores->buscarProveedorNombre($_POST['busqueda']);
    //~ if (isset($buscarTodo['error'])){
        //~ $respuesta['error']=$buscarTodo['error'];
        //~ $respuesta['consulta']=$buscarTodo['consulta'];
    //~ }else{
        //~ $respuesta['html']=htmlProveedores($_POST['busqueda'],$_POST['dedonde'], $_POST['idcaja'], $buscarTodo['datos']);
        //~ $respuesta['datos']=$buscarTodo['datos'];
    //~ }
    
//~ }


?>
