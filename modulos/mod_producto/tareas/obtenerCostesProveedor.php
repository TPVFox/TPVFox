<?php
$respuesta = array();
$idProveedor = $_POST['idProveedor'];
$idProducto = $_POST['idProducto'];
// Compruebo que realmente no tenga coste para ese producto es proveedor.
$comprobarCosteProveedor = $NCArticulo->ObtenerCostesDeUnProveedor($idProducto,$idProveedor);
if (isset($comprobarCosteProveedor['error'])){
	// Quiere decir que realmente no encontro registros articuloProveedor para ese producto y proveedor.
	// Buscarmos datos para ese proveedor.
	$proveedores= $CProveedor->buscarProveedorId($idProveedor);
} else {
	$respuesta['error'] = $comprobarCosteProveedor['error'];
}
if ( count($proveedores) >0 && (!isset($respuesta['error'])) ){
	//Quiere decir que fue correcto, obtuvimos un proveedor
	// montamos array de proveedor para enviar.
	$proveedor = $proveedores;
	$proveedor['fechaActualizacion']= date("Y-m-d H:i:s");
	$proveedor['estado']			= 'Nuevo';
	$proveedor['coste']				= '0.00' ; // Debería ser el ultimo coste... 
	$htmlFilaProveedor = htmlLineaProveedorCoste($proveedor);
	$respuesta['htmlFilaProveedor'] = $htmlFilaProveedor ;
	$respuesta['proveedores'] = $proveedores;
	$respuesta['proveedor'] = $proveedor;


}	else {
	$respuesta['error'] ='Error se obtuvo mas de un proveedor no es posible';
	$respuesta['proveedores'] = $proveedores;
	
}
