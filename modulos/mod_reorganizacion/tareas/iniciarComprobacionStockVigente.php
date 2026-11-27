<?php

// @ Objetivo
// Empezar la comprobación del ejercicio vigente: establecer el contexto de operación —con
// la única lectura del reloj y la fecha de corte de toda la comprobación— y repartir el
// catálogo en lotes. El contexto viaja con su resumen: cada lote lo devuelve y se reconoce
// con él, sin volver a leer el reloj.

$tamanoDeLote = 500;

try {
    $contextoClase = new ClaseComprobacionStockContexto();
    $apertura = $contextoClase->abrir();

    if (!$apertura['ok']) {
        $respuesta = array('ok' => false, 'motivo' => $apertura['motivo'], 'html' => htmlAlertaComprobacionStock($apertura['motivo']));
    } else {
        $consulta = new ClaseComprobacionStockConsulta();
        $catalogo = $consulta->catalogoFisico();
        $contextoClase->cerrar();

        $respuesta = array(
            'ok' => true,
            'contexto' => $apertura,
            'resumen' => $contextoClase->resumenDeContexto($apertura),
            'lotes' => array_chunk($catalogo, $tamanoDeLote),
        );
    }
} catch (Throwable $error) {
    registrarFalloComprobacionStock('iniciarComprobacionStockVigente', $error);
    if (isset($contextoClase)) {
        $contextoClase->cerrar();
    }
    $motivo = 'No se pudo empezar la comprobación. Inténtelo de nuevo y, si vuelve a ocurrir, avise de la incidencia.';
    $respuesta = array('ok' => false, 'motivo' => $motivo, 'html' => htmlAlertaComprobacionStock($motivo));
}
