<?php

// @ Objetivo
// Admitir el fichero de intercambio subido, calcular el stock mínimo justificado,
// clasificar cada producto y devolver la composición resultante, ya pintada. La
// tabla se monta aquí, en servidor; la composición viaja junto a ella porque, si
// el operador descarga el informe, vuelve tal cual y no hay estado en servidor
// entre los dos pasos.
//
// Los desenlaces son tres y no dos: se admite, se rechaza, o no se pudo terminar.
// Rechazar dice que el fichero no vale y que hay que traer otro; no poder terminar
// dice que el fichero puede estar bien y que quien falló fue el sistema. Confundirlos
// deja al operador sin saber cuál de las dos cosas hacer.

header('Content-Type: application/json');

$admision = new ClaseComprobacionStockAdmision();

// Lo primero es comprobar que llegó algo con lo que trabajar, y ocurre antes de
// establecer el contexto: saber si hay fichero no necesita la sesión, ni el esquema,
// ni los parámetros, ni el bloque de lectura, y abrir todo eso para descubrir después
// que el operador no eligió fichero deja una transacción abierta para nada.
$subida = $admision->subidaAdmisible(
    isset($_FILES['ficheroComprobacionStock']) ? $_FILES['ficheroComprobacionStock'] : null,
    ini_get('upload_max_filesize')
);

if (!$subida['ok']) {
    echo json_encode(array('ok' => false, 'html' => htmlAlertaComprobacionStock($subida['motivo'])));
    exit;
}

$contextoClase = new ClaseComprobacionStockContexto();
$rutaSubida = null;

try {
    $apertura = $contextoClase->abrir();

    if (!$apertura['ok']) {
        $respuesta = array('ok' => false, 'html' => htmlAlertaComprobacionStock($apertura['motivo']));
    } else {
        $io = ClaseIOXML::desdeSubida('ficheroComprobacionStock', null, $RutaServidor . $rutatmp . '/');
        $rutaSubida = $io->getRutaArchivo();

        $admitido = $admision->admitir($rutaSubida, $apertura);

        if (!$admitido['ok']) {
            $respuesta = array('ok' => false, 'html' => htmlAlertaComprobacionStock($admitido['motivo']));
        } else {
            // La reconstrucción del mínimo es la última lectura de esta rama, y va dentro
            // del bloque de solo lectura como el resto: cerrarlo antes lo dejaría fuera
            // del límite.
            $minimo = new ClaseComprobacionStockMinimo();
            $conMinimo = $minimo->calcular($admitido['filas'], $apertura, $admitido['contexto']['proveedorCierre']);
            $contextoClase->cerrar();

            $clasificacion = new ClaseComprobacionStockClasificacion();
            $clasificado = $clasificacion->clasificar($conMinimo);

            $emision = new ClaseComprobacionStockEmision();
            $composicion = $emision->componer($clasificado, $apertura, false, null, $admitido['contexto']);

            // El resumen sale con la composición y vuelve con ella. Es lo único que
            // permitirá saber, en la petición siguiente, si lo que llega es esto
            // mismo: entre las dos no hay nada en servidor que lo recuerde.
            $respuesta = array(
                'ok' => true,
                'html' => htmlTablaComprobacionStock($composicion, 'anterior'),
                'composicion' => $composicion,
                'resumen' => $emision->resumenDeComposicion($composicion),
            );
        }
    }
} catch (Throwable $error) {
    // Este camino no reproduce el mensaje del motor. No es solo que nombre rutas del
    // servidor, el esquema o la consulta que falló: es que no dice nada que quien
    // admite pueda usar, porque el fichero no lo escribió él. Lo único cierto que se
    // le puede decir es que el fichero puede estar bien y que el fallo fue de aquí.
    // Queda registrado, que es donde sí sirve.
    registrarFalloComprobacionStock('admitirComprobacionStock', $error);
    $respuesta = array('ok' => false, 'html' => htmlAlertaComprobacionStock(
        'No se pudo completar la comprobación. El fichero puede ser correcto: inténtelo de nuevo y, si vuelve a ocurrir, avise de la incidencia.'
    ));
} finally {
    // Se salga por donde se salga, el bloque de lectura no queda pendiente y el fichero
    // subido no se queda en el servidor. Cerrarlo dos veces está previsto: solo la
    // primera hace algo.
    $contextoClase->cerrar();
    if ($rutaSubida !== null && file_exists($rutaSubida)) {
        unlink($rutaSubida);
    }
}

echo json_encode($respuesta);
exit;
