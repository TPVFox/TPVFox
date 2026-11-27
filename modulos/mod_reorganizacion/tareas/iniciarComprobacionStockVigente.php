<?php

// @ Objetivo
// Empezar la comprobación del ejercicio vigente: establecer el contexto de operación —con
// la única lectura del reloj y la fecha de corte de toda la comprobación— y repartir el
// catálogo en lotes. El contexto viaja con su resumen: cada lote lo devuelve y se reconoce
// con él, sin volver a leer el reloj.

//
// Con un filtro —una familia o un proveedor, nunca los dos—, solo sus productos se reparten
// en lotes, y el filtro viaja en el contexto con su nombre y su número de productos.

$tamanoDeLote = 500;

$idFamiliaFiltro = isset($_POST['idFamiliaFiltro']) ? (int) $_POST['idFamiliaFiltro'] : 0;
$idProveedorFiltro = isset($_POST['idProveedorFiltro']) ? (int) $_POST['idProveedorFiltro'] : 0;
$filtro = null;
if ($idFamiliaFiltro > 0) {
    $filtro = array('tipo' => 'familia', 'id' => $idFamiliaFiltro);
}
if ($idProveedorFiltro > 0) {
    $filtro = array('tipo' => 'proveedor', 'id' => $idProveedorFiltro);
}

try {
    $consulta = new ClaseComprobacionStockConsulta();
    $motivoDelFiltro = null;
    if ($idFamiliaFiltro > 0 && $idProveedorFiltro > 0) {
        $motivoDelFiltro = 'Elija una familia o un proveedor, no los dos.';
    } elseif ($filtro !== null) {
        $filtro['nombre'] = $consulta->nombreDelFiltro($filtro['tipo'], $filtro['id']);
        if ($filtro['nombre'] === null) {
            $motivoDelFiltro = $filtro['tipo'] === 'familia' ? 'La familia elegida no existe.' : 'El proveedor elegido no existe.';
        }
    }

    $contextoClase = new ClaseComprobacionStockContexto();
    $apertura = ($motivoDelFiltro === null) ? $contextoClase->abrir() : array('ok' => false, 'motivo' => $motivoDelFiltro);

    if (!$apertura['ok']) {
        $respuesta = array('ok' => false, 'motivo' => $apertura['motivo'], 'html' => htmlAlertaComprobacionStock($apertura['motivo']));
    } else {
        $catalogo = $consulta->catalogoFisico(null, $filtro);
        $contextoClase->cerrar();
        if ($filtro !== null) {
            $filtro['productos'] = count($catalogo);
            $apertura['filtroDeCatalogo'] = $filtro;
        }

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
