<?php

// @ Objetivo
// Descargar el informe final del ejercicio anterior, a partir de la composición que
// el propio navegador devuelve: es la misma que ya se pintó en pantalla tras la
// admisión, sin estado en servidor entre los dos pasos.

$composicion = json_decode($_POST['composicion'], true);
if (!is_array($composicion) || !isset($composicion['filas'], $composicion['contexto'], $composicion['contextoVigente'])) {
    http_response_code(400);
    echo 'Composición inválida';
    exit;
}

$rutaTemporal = $RutaServidor . $rutatmp . '/informe_comprobacion_' . uniqid('', true) . '.csv';
$emision = new ClaseComprobacionEmision();
$emision->emitirInforme($composicion, $rutaTemporal);

$contenido = file_get_contents($rutaTemporal);
unlink($rutaTemporal);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="informe_comprobacion_' . $composicion['contexto']['ano'] . '.csv"');
header('Content-Length: ' . strlen($contenido));
echo $contenido;
exit;
