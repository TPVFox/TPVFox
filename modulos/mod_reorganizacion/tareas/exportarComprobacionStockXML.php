<?php

// @ Objetivo
// Descargar el fichero de intercambio del ejercicio vigente con la misma composición que se
// pintó: llegan los lotes con sus resúmenes y el contexto con el suyo, y se emite lo que se
// calculó, sin volver a leer la base. Lo que llegue cambiado no se emite.
//
// El conjunto seleccionado llega en un solo campo con su recuento declarado aparte,
// y lo primero que se hace es comprobar que llegó entero.

$modoEstricto = isset($_POST['modoEstricto']) && $_POST['modoEstricto'] === '1';

$emision = new ClaseComprobacionStockEmision();
$contextoClase = new ClaseComprobacionStockContexto();
$pedido = $emision->conjuntoPedido(
    isset($_POST['filtro']) ? $_POST['filtro'] : null,
    isset($_POST['filtroDeclarado']) ? $_POST['filtroDeclarado'] : null
);
if (!$pedido['ok']) {
    http_response_code(400);
    echo $pedido['motivo'];
    exit;
}

$contextoRecibido = json_decode(isset($_POST['contexto']) ? $_POST['contexto'] : '', true);
$resumenRecibido = isset($_POST['resumen']) ? $_POST['resumen'] : null;
if (!is_array($contextoRecibido) || $resumenRecibido === null
    || !hash_equals($contextoClase->resumenDeContexto($contextoRecibido), (string) $resumenRecibido)
) {
    http_response_code(422);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'El contexto de la comprobación no es el que se estableció al empezar: vuelva a empezar la comprobación';
    exit;
}
$admitidos = $emision->lotesAdmisibles($resumenRecibido, json_decode(isset($_POST['lotes']) ? $_POST['lotes'] : '', true));
if (!$admitidos['ok']) {
    http_response_code(422);
    header('Content-Type: text/plain; charset=utf-8');
    echo $admitidos['motivo'];
    exit;
}

$rutaTemporal = null;
try {
    // Si lo pedido es el conjunto entero no hay subconjunto que declarar; lo decide
    // quien conoce el conjunto de verdad, que es la composición que se pintó.
    $filtro = $emision->filtroDeclarable($admitidos['filas'], $pedido['ids']);
    $composicion = $emision->componer($admitidos['filas'], $contextoRecibido, $modoEstricto, $filtro);

    $rutaTemporal = $RutaServidor . $rutatmp . '/comprobacion_' . uniqid('', true) . '.xml';
    $emision->emitir($composicion, $rutaTemporal);
} catch (Throwable $error) {
    registrarFalloComprobacionStock('exportarComprobacionStockXML', $error);
    if ($rutaTemporal !== null && file_exists($rutaTemporal)) {
        unlink($rutaTemporal);
    }
    http_response_code(500);
    echo 'No se pudo generar el fichero de intercambio. Inténtelo de nuevo y, si vuelve a ocurrir, avise de la incidencia.';
    exit;
}

$apertura = $contextoRecibido;
$contenido = file_get_contents($rutaTemporal);
unlink($rutaTemporal);

header('Content-Type: application/xml');
header('Content-Disposition: attachment; filename="comprobacion_' . $apertura['ano'] . '.xml"');
header('Content-Length: ' . strlen($contenido));
echo $contenido;
exit;
