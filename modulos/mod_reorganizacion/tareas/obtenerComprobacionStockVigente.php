<?php

// @ Objetivo
// Componer el resultado del ejercicio vigente con el modo de trayectoria pedido, y
// devolver la tabla ya montada: la misma composición que alimentará la descarga si
// el operador la pide después. También el aviso de que no se pudo hacer va montado:
// el navegador inserta lo que reciba, sin componer nada.

$modoEstricto = isset($_POST['modoEstricto']) && $_POST['modoEstricto'] === '1';

$contextoClase = new ClaseComprobacionStockContexto();
$apertura = $contextoClase->abrir();

if (!$apertura['ok']) {
    $respuesta = array('ok' => false, 'html' => htmlAlertaComprobacionStock($apertura['motivo']));
} else {
    $extraccion = new ClaseComprobacionStockExtraccion();
    $estadoProducto = $extraccion->extraer($apertura, $modoEstricto);
    $contextoClase->cerrar();

    $emision = new ClaseComprobacionStockEmision();
    $composicion = $emision->componer($estadoProducto, $apertura, $modoEstricto);

    $respuesta = array('ok' => true, 'html' => htmlTablaComprobacionStock($composicion, 'vigente'));
}
