<?php
/* Comunicacion con balanza al cambiar el precio de un producto de tipo peso.
 * Se incluye desde producto.php cuando $precioNuevo === true y $Producto['tipo'] === 'peso'.
 * Requiere: $Producto, $ComunicacionBalanza, $relacion_balanza, $RutaServidor, $rutatmp, $URLCom
 */

$faltanDatos = [];
if (empty($Producto['cref_tienda_principal']) || !is_numeric($Producto['cref_tienda_principal'])) {
    $faltanDatos[] = 'Referencia principal numérica (cref_tienda_principal)';
    if (!empty($Producto['cref_tienda_principal'])) {
        $ComunicacionBalanza['Comprobaciones'][] = [
            'tipo'    => 'warning',
            'mensaje' => 'La referencia principal debe ser numérica. Valor: ' . $Producto['cref_tienda_principal'],
            'dato'    => [$Producto['cref_tienda_principal']],
        ];
    }
}
if (empty($Producto['articulo_name'])) {
    $faltanDatos[] = 'Nombre producto (articulo_name)';
}
if (!isset($Producto['pvpCiva'])) {
    $faltanDatos[] = 'Precio con IVA (pvpCiva)';
}
if (empty($Producto['tipo'])) {
    $faltanDatos[] = 'Tipo de producto (tipo)';
}
if (!isset($Producto['iva'])) {
    $faltanDatos[] = 'IVA (iva)';
}

if (!empty($faltanDatos)) {
    $ComunicacionBalanza['Comprobaciones'][] = [
        'tipo'    => 'warning',
        'mensaje' => 'Faltan datos para comunicar con balanza: ' . implode(', ', $faltanDatos),
        'dato'    => $faltanDatos,
    ];
    return;
}

include_once $URLCom . '/modulos/mod_balanza/clases/ClaseBalanza.php';
include_once $URLCom . '/modulos/mod_balanza/clases/ClaseComunicacionBalanza.php';

$traductorBalanza = new ClaseComunicacionBalanza();
$CBalanza         = new ClaseBalanza();
$balanzas         = $CBalanza->obtenerBalanzasEnvio();

$datosH2 = [
    'codigo' => $Producto['cref_tienda_principal'],
    'nombre' => $Producto['articulo_name'],
    'precio' => $Producto['pvpCiva'],
    'PLU'    => '',
];
$datosH3 = [
    'codigo'         => $Producto['cref_tienda_principal'],
    'tipoProducto'   => $Producto['tipo'],
    'iva'            => $Producto['iva'],
    'seccion'        => '',
];

// Añadir balanzas relacionadas con el producto
if (isset($relacion_balanza) && !isset($relacion_balanza['error'])) {
    foreach ($relacion_balanza as $relacion) {
        if ($relacion['idBalanza'] > 0) {
            $balanzaProducto = $CBalanza->datosBalanza($relacion['idBalanza'])['datos'][0];
            $existe = false;
            foreach ($balanzas as &$b) {
                if ($b['idBalanza'] == $balanzaProducto['idBalanza']) {
                    $b['relacionada'] = true;
                    $existe = true;
                    break;
                }
            }
            unset($b);
            if (!$existe) {
                $balanzaProducto['relacionada'] = true;
                $balanzas[] = $balanzaProducto;
            }
        }
    }
}

// Mostar relación de balanzas a comunicar
error_log('Balanzas relacionadas con producto ID ' . $Producto['idArticulo'] . ':');
foreach ($balanzas as $balanza) {
    error_log(' - ID ' . $balanza['idBalanza'] . ': ' . $balanza['nombreBalanza'] . (isset($balanza['relacionada']) && $balanza['relacionada'] ? ' (relacionada)' : ''));
}
error_log('Comunicación con balanza: ' . count($balanzas) . ' balanza(s) a comunicar para producto ID ' . $Producto['idArticulo']);

foreach ($balanzas as $balanza) {
    if (!isset($balanza['relacionada'])) {
        $balanza['relacionada'] = false;
    }

    $ruta_balanza_actual = '/' . str_replace(' ', '', $balanza['nombreBalanza']) . $balanza['idBalanza'];
    $directorioBalanza   = $RutaServidor . $rutatmp . $ruta_balanza_actual;

    $traductorBalanza->setGrupo($balanza['Grupo']);
    $traductorBalanza->setDireccion($balanza['Dirección']);

    // Asignar PLU y seccion si hay relacion
    $datosH2['PLU']    = '';
    $datosH3['seccion'] = '';
    if (!empty($relacion_balanza) && is_array($relacion_balanza)) {
        foreach ($relacion_balanza as $relacion) {
            if ($relacion['idBalanza'] == $balanza['idBalanza']) {
                $datosH2['PLU'] = $relacion['plu'];
                if (isset($balanza['conSeccion']) && strtolower($balanza['conSeccion']) === 'si') {
                    $datosH3['seccion'] = $relacion['seccion'];
                }
                break;
            }
        }
    }

    $traductorBalanza->setModoComunicacion(strtolower($balanza['conSeccion']) === 'si' ? 'H' : 'L');
    $traductorBalanza->setH2Data($datosH2);
    $traductorBalanza->setH3Data($datosH3);

    $salida = $traductorBalanza->traducirH2() . $traductorBalanza->traducirH3();

    $resultado = @file_put_contents($directorioBalanza . '/filetx', $salida);
    if ($resultado === false) {
        $ComunicacionBalanza['Comprobaciones'][] = [
            'tipo'    => 'warning',
            'mensaje' => 'No se pudo escribir fichero de comunicación en ' . $directorioBalanza . '/filetx',
            'dato'    => [],
        ];
    } else {
        $traductorBalanza->setRutaBalanza($directorioBalanza);
        $ejecucion = $traductorBalanza->ejecutarDriverBalanza();
        // El filetx fue escrito. Si el driver falla es porque la balanza no está
        // conectada — se registra como 'info', no como error de comunicación.
        $ComunicacionBalanza['Comprobaciones'][] = [
            'tipo'    => $ejecucion === false ? 'info' : 'success',
            'mensaje' => $ejecucion === false
                ? 'Fichero enviado a balanza (ID ' . $balanza['idBalanza'] . ') — driver no disponible (balanza no conectada).'
                : 'Comunicación con balanza (ID ' . $balanza['idBalanza'] . ') correcta.',
            'dato'    => [],
        ];
    }
}
