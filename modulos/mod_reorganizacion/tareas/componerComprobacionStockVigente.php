<?php

// @ Objetivo
// Terminar la comprobación del ejercicio vigente: reconocer el contexto y los lotes que
// vuelven del navegador, componer el resultado una sola vez y devolver la tabla ya montada.
// La misma composición es la que se descarga después: la descarga recibe los mismos lotes.

$modoEstricto = isset($_POST['modoEstricto']) && $_POST['modoEstricto'] === '1';
$contextoRecibido = json_decode(isset($_POST['contexto']) ? $_POST['contexto'] : '', true);
$resumenRecibido = isset($_POST['resumen']) ? $_POST['resumen'] : null;
$lotesRecibidos = json_decode(isset($_POST['lotes']) ? $_POST['lotes'] : '', true);

$contextoClase = new ClaseComprobacionStockContexto();
$emision = new ClaseComprobacionStockEmision();

if (!is_array($contextoRecibido) || $resumenRecibido === null
    || !hash_equals($contextoClase->resumenDeContexto($contextoRecibido), (string) $resumenRecibido)
) {
    $motivo = 'El contexto de la comprobación no es el que se estableció al empezar: vuelva a empezar la comprobación';
    $respuesta = array('ok' => false, 'motivo' => $motivo, 'html' => htmlAlertaComprobacionStock($motivo));
} else {
    $admitidos = $emision->lotesAdmisibles($resumenRecibido, $lotesRecibidos);
    if (!$admitidos['ok']) {
        $respuesta = array('ok' => false, 'motivo' => $admitidos['motivo'], 'html' => htmlAlertaComprobacionStock($admitidos['motivo']));
    } else {
        $composicion = $emision->componer($admitidos['filas'], $contextoRecibido, $modoEstricto);
        $aviso = $emision->avisoDeVolumen(
            count($composicion['filas']),
            ini_get('upload_max_filesize'),
            ini_get('post_max_size')
        );
        $respuesta = array(
            'ok' => true,
            'html' => ($aviso !== null ? htmlAvisoComprobacionStock($aviso) : '')
                . htmlTablaComprobacionStock($composicion, 'vigente'),
        );
    }
}
