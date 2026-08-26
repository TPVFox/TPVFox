<?php

// @ Objetivo
// Descargar el fichero de intercambio del ejercicio vigente. Recalcula desde el
// contexto de operación: no depende de ningún estado que haya dejado una petición
// anterior, igual que la propia pantalla.
//
// El conjunto seleccionado llega en un solo campo con su recuento declarado aparte,
// y lo primero que se hace es comprobar que llegó entero: no se abre la base ni se
// compone nada de una petición que no ha llegado completa.

$modoEstricto = isset($_POST['modoEstricto']) && $_POST['modoEstricto'] === '1';

$emision = new ClaseComprobacionStockEmision();
$pedido = $emision->conjuntoPedido(
    isset($_POST['filtro']) ? $_POST['filtro'] : null,
    isset($_POST['filtroDeclarado']) ? $_POST['filtroDeclarado'] : null
);
if (!$pedido['ok']) {
    http_response_code(400);
    echo $pedido['motivo'];
    exit;
}

$contextoClase = new ClaseComprobacionStockContexto();
$apertura = $contextoClase->abrir();
if (!$apertura['ok']) {
    http_response_code(400);
    echo $apertura['motivo'];
    exit;
}

$extraccion = new ClaseComprobacionStockExtraccion();
$estadoProducto = $extraccion->extraer($apertura, $modoEstricto);
$contextoClase->cerrar();

// Si lo pedido es el conjunto entero no hay subconjunto que declarar; lo decide
// quien conoce el conjunto de verdad, que es esta ejecución al acabar de componerlo.
$filtro = $emision->filtroDeclarable($estadoProducto, $pedido['ids']);
$composicion = $emision->componer($estadoProducto, $apertura, $modoEstricto, $filtro);

$rutaTemporal = $RutaServidor . $rutatmp . '/comprobacion_' . uniqid('', true) . '.xml';
try {
    $emision->emitir($composicion, $rutaTemporal);
} catch (Throwable $error) {
    // Sin el mensaje del motor: nombra rutas del servidor y el esquema, y quien pide la
    // descarga no puede hacer nada con ellos.
    http_response_code(500);
    echo 'No se pudo generar el fichero de intercambio. Inténtelo de nuevo y, si vuelve a ocurrir, avise de la incidencia.';
    exit;
}

$contenido = file_get_contents($rutaTemporal);
unlink($rutaTemporal);

header('Content-Type: application/xml');
header('Content-Disposition: attachment; filename="comprobacion_' . $apertura['ano'] . '.xml"');
header('Content-Length: ' . strlen($contenido));
echo $contenido;
exit;
