<?php

// @ Objetivo
// Montar la tabla de resultados de la comprobación de existencias. Es una sola
// tabla para los dos ejercicios: lo único que los separa son las columnas, y cada
// rama declara las suyas aquí abajo como dato —campo, título y formato—, nunca
// como código de pintado propio. Ninguna decisión de cálculo vive aquí: todo lo
// que aparece lo trae ya resuelto la composición.
// @ Parametros
//      $composicion -> array, la salida de ClaseComprobacionStockEmision::componer().
//      $rama -> string, 'vigente' o 'anterior'.
// @ Devolvemos
//      string, la tabla montada, para que la tarea que la incluye la lleve en su
//      respuesta.

$especificaciones = array(
    'vigente' => array(
        'preambulo' => '%d producto(s) con existencia negativa en algún punto del ejercicio.',
        'columnas' => array(
            array('campo' => 'idArticulo',           'titulo' => '',                      'formato' => 'seleccion'),
            array('campo' => 'idArticulo',           'titulo' => 'Artículo',              'formato' => 'entero'),
            array('campo' => 'saldoAlCorte',         'titulo' => 'Saldo al corte',        'formato' => 'texto'),
            array('campo' => 'minimoAlcanzado',      'titulo' => 'Mínimo alcanzado',      'formato' => 'texto'),
            array('campo' => 'saldoDeApertura',      'titulo' => 'Saldo de apertura',     'formato' => 'texto'),
            array('campo' => 'marcado',              'titulo' => 'Marcado',               'formato' => 'booleano'),
            array('campo' => 'tipoIncidencia',       'titulo' => 'Incidencia',            'formato' => 'opcional'),
            array('campo' => 'condicionesConocidas', 'titulo' => 'Condiciones conocidas', 'formato' => 'lista'),
        ),
    ),
    'anterior' => array(
        'preambulo' => '%d producto(s) admitido(s).',
        'columnas' => array(
            array('campo' => 'idArticulo',           'titulo' => 'Artículo',              'formato' => 'entero'),
            array('campo' => 'estado',               'titulo' => 'Estado',                'formato' => 'etiqueta'),
            array('campo' => 'marcado',              'titulo' => 'Marcado',               'formato' => 'booleano'),
            array('campo' => 'condicionesConocidas', 'titulo' => 'Condiciones conocidas', 'formato' => 'lista'),
            array('campo' => 'existenciaExigida',    'titulo' => 'Existencia exigida',    'formato' => 'texto'),
            array('campo' => 'stockJustificado',     'titulo' => 'Stock justificado',     'formato' => 'opcional'),
        ),
    ),
);

if (!isset($especificaciones[$rama])) {
    throw new InvalidArgumentException('Rama de comprobación desconocida: ' . $rama);
}

// El estado se traduce a texto aquí y no en el cálculo: es presentación, no
// criterio. Y nunca viaja solo: la fila lo acompaña siempre de los números que lo
// justifican, así que no se etiqueta como error.
$etiquetasEstado = array(
    'seguro' => 'Seguro',
    'no_seguro' => 'No seguro',
    'dudoso' => 'Dudoso',
    'no_comparable' => 'No comparable',
);

$columnas = $especificaciones[$rama]['columnas'];
$sufijo = ucfirst($rama);

$html = '<p>' . htmlspecialchars(sprintf($especificaciones[$rama]['preambulo'], count($composicion['filas']))) . '</p>';
$html .= '<table class="table table-bordered table-hover" id="tablaComprobacionStock' . $sufijo . '">';

$html .= '<thead><tr>';
foreach ($columnas as $columna) {
    if ($columna['formato'] === 'seleccion') {
        $html .= '<th><input type="checkbox" id="chkComprobacionStock' . $sufijo . 'Todos" checked></th>';
    } else {
        $html .= '<th>' . htmlspecialchars($columna['titulo']) . '</th>';
    }
}
$html .= '</tr></thead><tbody>';

foreach ($composicion['filas'] as $fila) {
    $html .= '<tr>';
    foreach ($columnas as $columna) {
        $valor = $fila[$columna['campo']];
        switch ($columna['formato']) {
            case 'seleccion':
                $celda = '<input type="checkbox" class="chkComprobacionStock' . $sufijo . 'Articulo" value="' . (int) $valor . '" checked>';
                break;
            case 'entero':
                $celda = (string) (int) $valor;
                break;
            case 'booleano':
                $celda = $valor ? 'Sí' : 'No';
                break;
            case 'etiqueta':
                $celda = '<span class="label label-default">'
                    . htmlspecialchars(isset($etiquetasEstado[$valor]) ? $etiquetasEstado[$valor] : (string) $valor)
                    . '</span>';
                break;
            case 'lista':
                // Una lista vacía se dice, no se deja en blanco: un hueco no distingue
                // «sin condiciones» de «no se llegó a mirar».
                $celda = htmlspecialchars(empty($valor) ? '—' : implode(', ', $valor));
                break;
            case 'opcional':
                $celda = htmlspecialchars($valor !== null ? (string) $valor : '—');
                break;
            default:
                $celda = htmlspecialchars((string) $valor);
        }
        $html .= '<td>' . $celda . '</td>';
    }
    $html .= '</tr>';
}

$html .= '</tbody></table>';

return $html;
