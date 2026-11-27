<?php

// @ Objetivo
// Empezar la comprobación del ejercicio vigente: establecer el contexto de operación —con
// la única lectura del reloj y la fecha de corte de toda la comprobación— y repartir el
// catálogo en lotes. El contexto viaja con su resumen: cada lote lo devuelve y se reconoce
// con él, sin volver a leer el reloj.
//
// Con un filtro —unas familias o unos proveedores, nunca de los dos tipos—, solo sus productos
// se reparten en lotes, y el filtro viaja en el contexto con sus nombres y su número de productos.

$tamanoDeLote = 500;

// Los identificadores elegidos, separados por comas, sin repetir y ordenados.
$idsDeFamilia = array();
$idsDeProveedor = array();
if (isset($_POST['idsFamiliaFiltro']) && $_POST['idsFamiliaFiltro'] !== '') {
    $idsDeFamilia = array_values(array_unique(array_filter(array_map('intval', explode(',', $_POST['idsFamiliaFiltro'])))));
    sort($idsDeFamilia);
}
if (isset($_POST['idsProveedorFiltro']) && $_POST['idsProveedorFiltro'] !== '') {
    $idsDeProveedor = array_values(array_unique(array_filter(array_map('intval', explode(',', $_POST['idsProveedorFiltro'])))));
    sort($idsDeProveedor);
}
$filtro = null;
if (!empty($idsDeFamilia)) {
    $filtro = array('tipo' => 'familia', 'ids' => $idsDeFamilia);
}
if (!empty($idsDeProveedor)) {
    $filtro = array('tipo' => 'proveedor', 'ids' => $idsDeProveedor);
}

try {
    $consulta = new ClaseComprobacionStockConsulta();
    $motivoDelFiltro = null;
    if (!empty($idsDeFamilia) && !empty($idsDeProveedor)) {
        $motivoDelFiltro = 'Elija familias o proveedores, no los dos.';
    } elseif ($filtro !== null) {
        $filtro['nombres'] = array();
        foreach ($filtro['ids'] as $id) {
            $nombre = $consulta->nombreDelFiltro($filtro['tipo'], $id);
            if ($nombre === null) {
                $motivoDelFiltro = ($filtro['tipo'] === 'familia' ? 'No existe la familia ' : 'No existe el proveedor ') . $id . '.';
                break;
            }
            $filtro['nombres'][] = $nombre;
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
