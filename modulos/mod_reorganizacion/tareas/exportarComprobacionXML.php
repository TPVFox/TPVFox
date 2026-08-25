<?php

// @ Objetivo
// Descargar el fichero de intercambio del ejercicio vigente. Recalcula desde el
// contexto de operación: no depende de ningún estado que haya dejado una petición
// anterior, igual que la propia pantalla.

$modoEstricto = isset($_POST['modoEstricto']) && $_POST['modoEstricto'] === '1';

$filtro = null;
if (!empty($_POST['filtro'])) {
    $filtro = array_map('intval', (array) $_POST['filtro']);
}

$contextoClase = new ClaseComprobacionContexto();
$apertura = $contextoClase->abrir();
if (!$apertura['ok']) {
    http_response_code(400);
    echo $apertura['motivo'];
    exit;
}

$extraccion = new ClaseComprobacionExtraccion();
$estadoProducto = $extraccion->extraer($apertura, $modoEstricto);
$contextoClase->cerrar();

$emision = new ClaseComprobacionEmision();
$composicion = $emision->componer($estadoProducto, $apertura, $modoEstricto, $filtro);

$rutaTemporal = $RutaServidor . $rutatmp . '/comprobacion_' . uniqid('', true) . '.xml';
if (!$emision->emitir($composicion, $rutaTemporal)) {
    http_response_code(500);
    echo 'No se pudo generar el fichero de intercambio';
    exit;
}

$contenido = file_get_contents($rutaTemporal);
unlink($rutaTemporal);

header('Content-Type: application/xml');
header('Content-Disposition: attachment; filename="comprobacion_' . $apertura['ano'] . '.xml"');
header('Content-Length: ' . strlen($contenido));
echo $contenido;
exit;
