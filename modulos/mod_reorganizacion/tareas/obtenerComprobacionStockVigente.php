<?php

// @ Objetivo
// Componer el resultado del ejercicio vigente con el modo de trayectoria pedido, y
// devolver la tabla ya montada: la misma composición que alimentará la descarga si
// el operador la pide después. También el aviso de que no se pudo hacer va montado:
// el navegador inserta lo que reciba, sin componer nada.

$modoEstricto = isset($_POST['modoEstricto']) && $_POST['modoEstricto'] === '1';

try {
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
} catch (Throwable $error) {
    // Un fallo inesperado tiene que llegar como aviso, no como respuesta rota: una
    // pantalla vacía se leería como que no hay nada que revisar. Y el bloque de lectura
    // no se queda abierto por haber fallado a mitad.
    if (isset($contextoClase)) {
        $contextoClase->cerrar();
    }
    $respuesta = array('ok' => false, 'html' => htmlAlertaComprobacionStock($error->getMessage()));
}
