<?php

// @ Objetivo
// Un lote de la comprobación del ejercicio vigente: reconocer el contexto con que empezó,
// componer la trayectoria de los productos del lote y devolver los que bajan de cero, con
// el resumen que los reconocerá al componer.

$modoEstricto = isset($_POST['modoEstricto']) && $_POST['modoEstricto'] === '1';
$contextoRecibido = json_decode(isset($_POST['contexto']) ? $_POST['contexto'] : '', true);
$resumenRecibido = isset($_POST['resumen']) ? $_POST['resumen'] : null;
$idsDelLote = array();
if (isset($_POST['ids']) && $_POST['ids'] !== '') {
    $idsDelLote = array_map('intval', explode(',', $_POST['ids']));
}

try {
    $contextoClase = new ClaseComprobacionStockContexto();
    $operacion = $contextoClase->reanudar($contextoRecibido, $resumenRecibido);

    if (!$operacion['ok']) {
        $respuesta = array('ok' => false, 'motivo' => $operacion['motivo'], 'html' => htmlAlertaComprobacionStock($operacion['motivo']));
    } else {
        $extraccion = new ClaseComprobacionStockExtraccion();
        $filas = $extraccion->extraer($operacion, $modoEstricto, $idsDelLote);
        $contextoClase->cerrar();

        $emision = new ClaseComprobacionStockEmision();
        $respuesta = array('ok' => true, 'filas' => $filas, 'resumen' => $emision->resumenDeLote($resumenRecibido, $filas));
    }
} catch (Throwable $error) {
    registrarFalloComprobacionStock('loteComprobacionStockVigente', $error);
    if (isset($contextoClase)) {
        $contextoClase->cerrar();
    }
    $motivo = 'La comprobación no terminó: falló un lote. Inténtelo de nuevo y, si vuelve a ocurrir, avise de la incidencia.';
    $respuesta = array('ok' => false, 'motivo' => $motivo, 'html' => htmlAlertaComprobacionStock($motivo));
}
