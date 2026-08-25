<?php

// @ Objetivo
// Admitir el fichero de intercambio subido, calcular el stock mínimo justificado,
// clasificar cada producto y devolver la composición resultante: la pantalla la
// pinta y, si el operador la descarga, viaja de vuelta tal cual para el informe
// final, sin estado en servidor entre los dos pasos.

header('Content-Type: application/json');

try {
    $contextoClase = new ClaseComprobacionContexto();
    $apertura = $contextoClase->abrir();
    if (!$apertura['ok']) {
        echo json_encode(array('ok' => false, 'message' => $apertura['motivo']));
        exit;
    }

    $io = ClaseIOXML::desdeSubida('ficheroComprobacion', null, $RutaServidor . $rutatmp . '/');
    $rutaSubida = $io->getRutaArchivo();

    $admision = new ClaseComprobacionAdmision();
    $admitido = $admision->admitir($rutaSubida, $apertura);
    $contextoClase->cerrar();
    unlink($rutaSubida);

    if (!$admitido['ok']) {
        echo json_encode(array('ok' => false, 'message' => $admitido['motivo']));
        exit;
    }

    $minimo = new ClaseComprobacionMinimo();
    $conMinimo = $minimo->calcular($admitido['filas'], $apertura, $admitido['contexto']['proveedorCierre']);

    $clasificacion = new ClaseComprobacionClasificacion();
    $clasificado = $clasificacion->clasificar($conMinimo);

    $emision = new ClaseComprobacionEmision();
    $composicion = $emision->componer($clasificado, $apertura, false, null, $admitido['contexto']);

    echo json_encode(array('ok' => true, 'composicion' => $composicion));
    exit;
} catch (Throwable $error) {
    echo json_encode(array('ok' => false, 'message' => $error->getMessage()));
    exit;
}
