<?php
// @ Objetivo:
// Descargar en CSV todas las facturas de un proveedor, con las columnas del listado de facturas de
// compras y su número de factura. Sin permiso de ver proveedores o sin facturas, vuelve a la ficha.
// Termina con exit(), para que tareas.php no escriba su respuesta detrás del fichero.
include_once $URLCom . '/modulos/mod_compras/clases/facturasCompras.php';
$CFac = new FacturasCompras($BDTpv);
$idProveedor = (int) $_POST['idProveedor'];
$mod_vista_proveedor = array('modulo' => 'mod_proveedor', 'vista' => 'ListaProveedores.php');
$facturas = array();
if (isset($ClasePermisos) && $ClasePermisos->getAccion('ver', $mod_vista_proveedor) == 1) {
	$listado = $CFac->TodosFacturaLimite('WHERE a.idProveedor = ? ORDER BY a.Numfacpro', array($idProveedor));
	$facturas = $listado['Items'];
}
if (count($facturas) === 0) {
	header('Location: ./proveedor.php?id=' . $idProveedor);
	exit();
}

$nombreFichero = 'facturas_proveedor_' . $idProveedor . '_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreFichero . '"');

$out = fopen('php://output', 'w');
// BOM para que Excel lo abra correctamente en UTF-8
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
fputcsv($out, array('Nº factura', 'Fecha', 'Proveedor', 'Base', 'IVA', 'Total', 'ID proveedor', 'Estado', 'Su número'), ';');
foreach ($facturas as $factura) {
	$totaliva = $CFac->sumarIva($factura['Numfacpro']);
	$totalBase = "0.00";
	$importeIva = "0.00";
	if (isset($totaliva['totalbase'])) {
		$totalBase = $totaliva['totalbase'];
	}
	if (isset($totaliva['importeIva'])) {
		$importeIva = $totaliva['importeIva'];
	}
	$date = date_create($factura['Fecha']);
	fputcsv($out, array(
		$factura['Numfacpro'],
		date_format($date, 'Y-m-d'),
		$factura['nombrecomercial'],
		$totalBase,
		$importeIva,
		$factura['total'],
		$factura['idProveedor'],
		$factura['estado'],
		$factura['Su_num_factura']
	), ';');
}
fclose($out);
exit();
