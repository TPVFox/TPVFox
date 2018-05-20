<?php
//@Objetivo;
//Busqueda de productos: Recive el valor a buscar el campo por el que tiene que buscar 
	$busqueda = $_POST['valorCampo'];
	$campoAbuscar = $_POST['campo'];
	$id_input = $_POST['id_input'];
	$idcaja=$_POST['idcaja'];
	$idProveedor=$_POST['idProveedor'];
	$dedonde=$_POST['dedonde'];
	$res = BuscarProductos($id_input,$campoAbuscar, $idcaja, $busqueda,$BDTpv, $idProveedor);
	if ($res['Nitems']===1 && $idcaja<>"cajaBusqueda"){
		$respuesta=$res;
		$respuesta['Nitems']=$res['Nitems'];
	}else{
		if (isset($res['datos'])){
			$respuesta['listado']= htmlProductos($res['datos'],$id_input,$campoAbuscar,$busqueda, $dedonde);
			$respuesta['Estado'] = 'Listado';
			$respuesta['html']=$respuesta['listado'];
		}else{
			$respuesta['Nitems']=2;
		}
	}
	$respuesta['sql']=$res['sql'];
?>
