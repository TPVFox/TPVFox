<?php
$datosHistorico = $CArticulo->historicoCompras($id, $dedonde, "Productos");
$datosAlbaran = $CAlbaran->datosAlbaran($id);
$datosProveedor = $CProveedor->buscarProveedorId($datosAlbaran['idProveedor']);

$htmlImprimir['html'] = "";
$htmlImprimir['cabecera'] = "";
$htmlImprimir['html'] .= '<p> ALBARÁN NÚMERO : ' . $id . '</p>';
$date = date_create($datosAlbaran['Fecha']);
$htmlImprimir['html'] .= '<p> FECHA : ' . date_format($date, 'Y-m-d') . '</p>';
$htmlImprimir['html'] .= '<p> PROVEEDOR : ' . $datosProveedor['nombrecomercial'] . '</p>';
$htmlImprimir['html'] .= '<br>';


$htmlImprimir['html'] .= '<table  WIDTH="100%">';
$htmlImprimir['html'] .= '<tr>';
$htmlImprimir['html'] .= '<td WIDTH="35%">NOMBRE</td>';
$htmlImprimir['html'] .= '<td>REFERENCIA</td>';
$htmlImprimir['html'] .= '<td>PRECIO ANTERIOR</td>';
$htmlImprimir['html'] .= '<td>PRECIO NUEVO</td>';
$htmlImprimir['html'] .= '</tr>';
$htmlImprimir['html'] .= '</table>';
$htmlImprimir['html'] .= '<table  WIDTH="100%">';
foreach ($datosHistorico as $prod) {
	$datosArticulo = $CArticulo->datosPrincipalesArticulo($prod['idArticulo']);
	$htmlImprimir['html'] .= '<tr>';
	$htmlImprimir['html'] .= '<td WIDTH="35%">' . $datosArticulo['articulo_name'] . '</td>';
	$htmlImprimir['html'] .= '<td>' . $datosArticulo['crefTienda'] . '</td>';
	$htmlImprimir['html'] .= '<td>' . $prod['Antes'] . '</td>';
	$htmlImprimir['html'] .= '<td>' . $prod['Nuevo'] . '</td>';
	$htmlImprimir['html'] .= '</tr>';
}
$htmlImprimir['html'] .= '</table>';
if ($_POST['mensaje'] !== 'KO') {
	$htmlImprimir['html'] .= '<p>Mensaje: ' . $_POST['mensaje'] . '</p>';
}
