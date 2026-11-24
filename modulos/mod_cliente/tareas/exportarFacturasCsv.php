<?php
// @ Objetivo:
// Descargar en CSV todas las facturas de un cliente, con las columnas del listado de facturas de
// venta. Sin permiso de ver clientes o sin facturas, vuelve a la ficha.
// Termina con exit(), para que tareas.php no escriba su respuesta detrás del fichero.
include_once $URLCom . '/modulos/mod_venta/clases/facturasVentas.php';
$Cfactura = new FacturasVentas($BDTpv);
$idCliente = (int) $_POST['idClientes'];
$mod_vista_cliente = array('modulo' => 'mod_cliente', 'vista' => 'ListaClientes.php');
$facturas = array();
if (isset($ClasePermisos) && $ClasePermisos->getAccion('ver', $mod_vista_cliente) == 1) {
	$listado = $Cfactura->TodosFacturaFiltro('WHERE a.idCliente = ? ORDER BY a.Numfaccli', array($idCliente));
	if (isset($listado['Items'])) {
		$facturas = $listado['Items'];
	}
}
if (count($facturas) === 0) {
	header('Location: ./cliente.php?id=' . $idCliente);
	exit();
}

$nombreFichero = 'facturas_cliente_' . $idCliente . '_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreFichero . '"');

$out = fopen('php://output', 'w');
// BOM para que Excel lo abra correctamente en UTF-8
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
fputcsv($out, array('Nº factura', 'Fecha', 'Cliente', 'Base', 'IVA', 'Total', 'ID cliente', 'Estado'), ';');
foreach ($facturas as $factura) {
	$totaliva = $Cfactura->sumarIva($factura['Numfaccli']);
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
		$factura['Numfaccli'],
		date_format($date, 'Y-m-d'),
		$factura['Nombre'],
		$totalBase,
		$importeIva,
		$factura['total'],
		$factura['idCliente'],
		$factura['estado']
	), ';');
}
fclose($out);
exit();
