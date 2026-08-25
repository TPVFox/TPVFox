<?php

// @ Objetivo
// Admitir el fichero de intercambio subido, calcular el stock mínimo justificado,
// clasificar cada producto y devolver la composición resultante, ya pintada. La
// tabla se monta aquí, en servidor; la composición viaja junto a ella porque, si
// el operador descarga el informe, vuelve tal cual y no hay estado en servidor
// entre los dos pasos.

header('Content-Type: application/json');

try {
    $contextoClase = new ClaseComprobacionStockContexto();
    $apertura = $contextoClase->abrir();
    if (!$apertura['ok']) {
        echo json_encode(array('ok' => false, 'html' => htmlAlertaComprobacionStock($apertura['motivo'])));
        exit;
    }

    $io = ClaseIOXML::desdeSubida('ficheroComprobacionStock', null, $RutaServidor . $rutatmp . '/');
    $rutaSubida = $io->getRutaArchivo();

    $admision = new ClaseComprobacionStockAdmision();
    $admitido = $admision->admitir($rutaSubida, $apertura);
    unlink($rutaSubida);

    if (!$admitido['ok']) {
        $contextoClase->cerrar();
        echo json_encode(array('ok' => false, 'html' => htmlAlertaComprobacionStock($admitido['motivo'])));
        exit;
    }

    // La reconstrucción del mínimo es la última lectura de esta rama, y va dentro del
    // bloque de solo lectura como el resto: cerrarlo antes lo dejaría fuera del límite.
    $minimo = new ClaseComprobacionStockMinimo();
    $conMinimo = $minimo->calcular($admitido['filas'], $apertura, $admitido['contexto']['proveedorCierre']);
    $contextoClase->cerrar();

    $clasificacion = new ClaseComprobacionStockClasificacion();
    $clasificado = $clasificacion->clasificar($conMinimo);

    $emision = new ClaseComprobacionStockEmision();
    $composicion = $emision->componer($clasificado, $apertura, false, null, $admitido['contexto']);

    echo json_encode(array(
        'ok' => true,
        'html' => htmlTablaComprobacionStock($composicion, 'anterior'),
        'composicion' => $composicion,
    ));
    exit;
} catch (Throwable $error) {
    echo json_encode(array('ok' => false, 'html' => htmlAlertaComprobacionStock($error->getMessage())));
    exit;
}
