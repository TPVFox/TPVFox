<?php

// @ Objetivo
// Componer el resultado del ejercicio vigente con el modo de trayectoria pedido, y
// devolver la tabla ya montada: la misma composición que alimentará la descarga si
// el operador la pide después.

$modoEstricto = isset($_POST['modoEstricto']) && $_POST['modoEstricto'] === '1';

$contextoClase = new ClaseComprobacionContexto();
$apertura = $contextoClase->abrir();

if (!$apertura['ok']) {
    $respuesta = array('ok' => false, 'motivo' => $apertura['motivo']);
} else {
    $extraccion = new ClaseComprobacionExtraccion();
    $estadoProducto = $extraccion->extraer($apertura, $modoEstricto);
    $contextoClase->cerrar();

    $emision = new ClaseComprobacionEmision();
    $composicion = $emision->componer($estadoProducto, $apertura, $modoEstricto);

    $respuesta = array('ok' => true, 'html' => htmlTablaComprobacionVigente($composicion));
}
