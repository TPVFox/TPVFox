<?php
require_once  $URLCom . '/clases/ClaseIOXML.php';
require_once $URLCom . '/modulos/mod_compras/clases/ClaseAlbaranCompraXML.php';

$nombreArchivo = 'inputAlbaranCierreAno';
$archivoXSD = $URLCom . '/modulos/mod_compras/albaran_compra_v1.xsd';
$rutaArchivoXML = $RutaServidor . $rutatmp . '/' . $_FILES[$nombreArchivo]['tmp_name'];

$io = ClaseIOXML::desdeSubida(
    $nombreArchivo,
    $archivoXSD,
    $RutaServidor . $rutatmp . '/'
);

// 2) Cargar y validar XML
$xml = $io->cargar();
$albaranXML = new ClaseAlbaranCompraXML();
$albaran = $albaranXML->simpleXMLToArrayCambioAno($xml);

echo '<pre>';
print_r($albaran);
echo '</pre>';
