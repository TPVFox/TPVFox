<?php

// @ Objetivo
// Admitir el fichero de intercambio subido, calcular el stock mínimo justificado,
// clasificar cada producto y devolver la composición resultante: la pantalla la
// pinta y, si el operador la descarga, viaja de vuelta tal cual para el informe
// final, sin estado en servidor entre los dos pasos.

header('Content-Type: application/json');

try {
    $contextoClase = new ClaseComprobacionStockContexto();
    $apertura = $contextoClase->abrir();
    if (!$apertura['ok']) {
        echo json_encode(array('ok' => false, 'message' => $apertura['motivo']));
        exit;
    }

    $io = ClaseIOXML::desdeSubida('ficheroComprobacionStock', null, $RutaServidor . $rutatmp . '/');
    $rutaSubida = $io->getRutaArchivo();

    $admision = new ClaseComprobacionStockAdmision();
    $admitido = $admision->admitir($rutaSubida, $apertura);
    $contextoClase->cerrar();
    unlink($rutaSubida);

    if (!$admitido['ok']) {
        echo json_encode(array('ok' => false, 'message' => $admitido['motivo']));
        exit;
    }

    $minimo = new ClaseComprobacionStockMinimo();
    $conMinimo = $minimo->calcular($admitido['filas'], $apertura, $admitido['contexto']['proveedorCierre']);

    $clasificacion = new ClaseComprobacionStockClasificacion();
    $clasificado = $clasificacion->clasificar($conMinimo);

    $emision = new ClaseComprobacionStockEmision();
    $composicion = $emision->componer($clasificado, $apertura, false, null, $admitido['contexto']);

    echo json_encode(array('ok' => true, 'composicion' => $composicion));
    exit;
} catch (Throwable $error) {
    echo json_encode(array('ok' => false, 'message' => $error->getMessage()));
    exit;
}
