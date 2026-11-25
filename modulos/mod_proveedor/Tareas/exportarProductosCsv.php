<?php
// @ Objetivo:
// Descargar en CSV todos los productos de un proveedor, con las columnas de su listado, la relación y si
// tienen movimientos en el ejercicio. Sin permiso de ver proveedores o sin productos, vuelve al listado.
// Termina con exit(), para que tareas.php no escriba su respuesta detrás del fichero.
$idProveedor = (int) $_POST['idProveedor'];
$mod_vista_proveedor = array('modulo' => 'mod_proveedor', 'vista' => 'ListaProveedores.php');
$productos = array();
if (isset($ClasePermisos) && $ClasePermisos->getAccion('ver', $mod_vista_proveedor) == 1 && $idProveedor > 0) {
	$listado = $NCArticulo->GetProductosProveedor($idProveedor, 'articulo_name', 'ASC');
	if ($listado['NItems'] > 0) {
		$productos = productosDelProveedor($NCArticulo, $listado['Items'], $idProveedor);
	}
}
if (count($productos) === 0) {
	header('Location: ./OtrasVistas/ListadoProductosDeProveedor.php?id=' . $idProveedor);
	exit();
}
$conMovimientos = $CProveedor->productosConMovimientos(array_column($productos, 'idArticulo'));

$nombreFichero = 'productos_proveedor_' . $idProveedor . '_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreFichero . '"');

$out = fopen('php://output', 'w');
// BOM para que Excel lo abra correctamente en UTF-8
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
fputcsv($out, array('ID', 'Producto', 'Relación', 'Último coste', 'Ref. proveedor', 'Coste proveedor', 'Fecha actualización', 'Precio', 'Códigos de barras', 'Stock', 'Estado', 'Movimientos'), ';');
foreach ($productos as $producto) {
	// Los valores, como los pinta el listado
	$decimalesStock = ($producto['tipo'] == 'peso') ? 3 : 0;
	fputcsv($out, array(
		$producto['idArticulo'],
		$producto['articulo_name'],
		$producto['es_principal'] ? 'Principal' : 'Asociado',
		number_format($producto['ultimoCoste'], 2),
		$producto['Ref_proveedor'] ?? '',
		number_format($producto['costeProveedor'] ?? 0, 2),
		$producto['fecha_actualizacion_proveedor'] ?? '',
		number_format($producto['pvpCiva'], 2),
		implode(' ', $producto['codBarras']),
		number_format($producto['stocks']['stockOn'], $decimalesStock),
		$producto['estado'],
		in_array((int) $producto['idArticulo'], $conMovimientos, true) ? 'Sí' : 'No'
	), ';');
}
fclose($out);
exit();
