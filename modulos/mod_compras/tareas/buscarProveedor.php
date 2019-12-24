<?php 
//@Objetivo:
	//Busca el proveedor según el dato insertado , si el dato viene de la caja idProveedor entonces busca por id
	//Si no busca por nombre del proveedor y muestra un modal con las coincidencias ,
	//Si no recibe busqueda muestra un modal con todos los nombres de los proveedores 
	// Contiene el control de errores de las funciones que llama a la clase proveedor
	
if (isset($_POST['idcaja']) && $_POST['idcaja'] =="id_proveedor"){
    $buscarId=$CProveedores->buscarProveedorId($_POST['busqueda']);
    if (isset($buscarId['error'])){
        $respuesta['error']=$buscarId['error'];
        $respuesta['consulta']=$buscarId['consulta'];
    }else{
        if (isset($buscarId['idProveedor'])){
            $respuesta['id']=$buscarId['idProveedor'];
            $respuesta['nombre']=$buscarId['nombrecomercial'];
            $respuesta['Nitems']=1;
        }else{
            $respuesta['Nitems']=2;
        }
    }
}else{
    $buscarTodo=$CProveedores->buscarProveedorNombre($_POST['busqueda']);
    if (isset($buscarTodo['error'])){
        $respuesta['error']=$buscarTodo['error'];
        $respuesta['consulta']=$buscarTodo['consulta'];
    }else{
        $respuesta['html']=htmlProveedores($_POST['busqueda'],$_POST['dedonde'], $_POST['idcaja'], $buscarTodo['datos']);
        $respuesta['datos']=$buscarTodo['datos'];
    }
    
}


?>
