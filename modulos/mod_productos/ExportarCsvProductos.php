<?php
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_productos/clases/ClaseSeleccionProductos.php';
include_once $URLCom . '/modulos/mod_producto/clases/ClaseProductos.php';

$CSeleccion = new ClaseSeleccionProductos((int) $Usuario['id'], __DIR__ . '/cache');
$ids        = $CSeleccion->getIds();

if (empty($ids)) {
    header('Location: ListaProductos.php');
    exit();
}

$CTArticulos = new ClaseProductos($BDTpv);

$nombreFichero = 'productos_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreFichero . '"');

$out = fopen('php://output', 'w');

// BOM para que Excel lo abra correctamente en UTF-8
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Cabecera
fputcsv($out, [
    'ID',
    'Nombre',
    'Código Barras',
    'Familias',
    'Último Coste',
    'Beneficio %',
    'P.V.P. sin IVA',
    'IVA %',
    'P.V.P. con IVA',
    'Stock',
    'Estado',
], ';');

foreach ($ids as $idArticulo) {
    $p = $CTArticulos->GetProducto($idArticulo);

    $codBarras = '';
    if (!empty($p['codBarras'])) {
        $codBarras = implode(' | ', $p['codBarras']);
    }

    $familias = '';
    if (!empty($p['familias']) && is_array($p['familias'])) {
        $nombres = array_column($p['familias'], 'familiaNombre');
        $familias = implode(' | ', $nombres);
    }

    fputcsv($out, [
        $p['idArticulo'],
        $p['articulo_name'],
        $codBarras,
        $familias,
        number_format($p['ultimoCoste'],  2, '.', ''),
        $p['beneficio'],
        number_format($p['pvpSiva'],      2, '.', ''),
        $p['iva'],
        number_format($p['pvpCiva'],      2, '.', ''),
        number_format($p['stocks']['stockOn'], 2, '.', ''),
        $p['estado'],
    ], ';');
}

fclose($out);
