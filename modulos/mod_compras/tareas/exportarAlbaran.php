<?php
// añadir clases/claseIOXML.php y modulos/mod_compras/clases/ClaseAlbaranCompraXML.php
require_once  $URLCom . '/clases/ClaseIOXML.php';
require_once $URLCom . '/modulos/mod_compras/clases/ClaseAlbaranCompraXML.php';

$CAlb = new AlbaranesCompras($BDTpv);
$Cproveedor = new Proveedores($BDTpv);
// Si existe id y no hay errores estamos modificando directamente un albaran.
$id = $_POST['id'];
$dedonde = $_POST['dedonde'];
$idTienda = $_POST['idTienda'];

$datosDocumento = $CAlb->GetAlbaran($id);
$errores = array();

$idProveedor = $datosDocumento['idProveedor'];
$proveedor = $Cproveedor->buscarProveedorId($idProveedor);
$nombreProveedor = $proveedor['nombrecomercial'];
$productos = $datosDocumento['Productos'];
$fecha = ($datosDocumento['Fecha'] == "0000-00-00 00:00:00")
    ? date('d-m-Y') : date_format(date_create($datosDocumento['Fecha']), 'd-m-Y');
$hora = date_format(date_create($datosDocumento['Fecha']), 'H:i');
$creado_por = $CAlb->obtenerDatosUsuario($datosDocumento['idUsuario']);
$formaPago = (isset($datosDocumento['formaPago'])) ? $datosDocumento['formaPago'] : 0;
$fechaVencimiento = $datosDocumento['FechaVencimiento'];

if (isset($datosDocumento['Productos'])) {
    // Obtenemos los datos totales ;
    // convertimos el objeto productos en array
    $p = (object) $productos;
    $Datostotales = $CAlb->recalculoTotales($p);
    $datosDocumento['Datostotales'] = $Datostotales;
}


// generar XML y exportar
$albaranXML = new ClaseAlbaranCompraXML($datosDocumento);
$ioXML = new ClaseIOXML(
    'modulos/mod_compras/exports/albaran_compra_' . $datosDocumento['Su_numero'] . '.xml',
    'modulos/mod_compras/albaran_compra_v1.xsd'
);

$albaranXML = new ClaseAlbaranCompraXML();

$xml = $albaranXML->arrayToSimpleXML($datosDocumento);
$rutaArchivo = $rutatmp . '/albaran_compra_' . $id . '.xml';
$rutaXSD = $URLCom . '/modulos/mod_compras/albaran_compra_v1.xsd';

$ioXML = new ClaseIOXML($RutaServidor . $rutaArchivo, $rutaXSD);

$ioXML->guardar($xml);

// si el estado del albaran es guardado cambiamos a exportado
if ($datosDocumento['estado'] == 'Guardado') {
    $CAlb->cambiarEstadoAlbaran($id, 'Exportado');
}
$respuesta = $rutaArchivo;
