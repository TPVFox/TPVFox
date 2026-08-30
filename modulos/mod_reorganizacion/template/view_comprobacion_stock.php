<?php

require_once __DIR__ . '/../clases/ClaseComprobacionStockCantidad.php';

// @ Objetivo
// Montar la tabla de resultados de la comprobación de existencias. Es una sola
// tabla para los dos ejercicios: lo único que los separa son las columnas, y cada
// rama declara las suyas aquí abajo como dato —campo, título y formato—, nunca
// como código de pintado propio. Ninguna decisión de cálculo vive aquí: todo lo
// que aparece lo trae ya resuelto la composición.
//
// La tabla nunca sale sola: encima va con qué se calculó lo que muestra. Quien
// mira esta pantalla decide sobre ella —qué productos marca y se lleva—, y esa
// decisión depende de umbrales y de un momento que no están en ninguna celda.
// Cuando lo que se mira llegó en un fichero de otro ejercicio, va además con qué
// se calculó allí y de qué ejecución vino: dos ficheros del mismo ejercicio y la
// misma tienda son indistinguibles por su contenido, y quien los admite es el
// único que puede saber si el que tiene delante es el último.
//
// Y una columna puede no ser de esta comprobación: hay datos que llegan de otro
// informe, calculados con su propio criterio. Esos se pintan distintos y se
// explican debajo, porque presentarlos como propios los convertiría en una
// conclusión de aquí que nadie ha sacado. Una columna que sí es de aquí también
// lleva nota cuando su número dice menos de lo que parece.
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
            array('campo' => 'saldoAlCorte',         'titulo' => 'Saldo al corte',        'formato' => 'cantidad'),
            array('campo' => 'minimoAlcanzado',      'titulo' => 'Mínimo alcanzado',      'formato' => 'cantidad'),
            array('campo' => 'saldoDeApertura',      'titulo' => 'Saldo de apertura',     'formato' => 'cantidad'),
            array('campo' => 'marcado',              'titulo' => 'Marcado',               'formato' => 'booleano'),
            array('campo' => 'tipoIncidencia',       'titulo' => 'Incidencia',            'formato' => 'ajena',
                  'procedencia' => 'Lo señala el informe de incidencias de existencias, que lo calcula con su propio criterio de movimiento. No es una conclusión de esta comprobación, y puede no coincidir con los números de su misma fila.'),
            array('campo' => 'condicionesConocidas', 'titulo' => 'Condiciones conocidas', 'formato' => 'lista'),
        ),
    ),
    'anterior' => array(
        'preambulo' => '%d producto(s) admitido(s).',
        'columnas' => array(
            array('campo' => 'idArticulo',           'titulo' => 'Artículo',              'formato' => 'entero'),
            array('campo' => 'estado',               'titulo' => 'Estado',                'formato' => 'etiqueta'),
            array('campo' => 'marcado',              'titulo' => 'Marcado',               'formato' => 'booleano'),
            array('campo' => 'condicionesConocidas', 'titulo' => 'Condiciones de este ejercicio', 'formato' => 'lista'),
            array('campo' => 'condicionesDelVigente', 'titulo' => 'Condiciones del ejercicio vigente', 'formato' => 'lista',
                  'nota' => 'Se marcaron en el ejercicio vigente y con los umbrales de allí, que constan arriba junto al origen del fichero. Hablan de aquel periodo, no de este.'),
            array('campo' => 'existenciaExigida',    'titulo' => 'Existencia exigida',    'formato' => 'cantidad'),
            array('campo' => 'stockJustificado',     'titulo' => 'Mínimo necesario justificado', 'formato' => 'cantidad',
                  'nota' => 'Es la cantidad más pequeña que basta para explicar los movimientos reconstruidos, no un recuento ni una existencia comprobada. La verdadera puede ser mayor, y nunca es menor.'),
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
$contexto = $composicion['contexto'];

// Con qué se calculó lo que se ve. Va arriba y no al pie porque se lee antes de
// decidir, no después: el momento importa porque entre esta pantalla y la descarga
// la base sigue recibiendo movimientos, y los umbrales porque no aparecen en
// ninguna celda. Los mismos campos que arrastra el informe, y en el mismo orden:
// que una salida llevara menos que la otra volvería a hacer depender del camino lo
// que la composición ya tiene resuelto una sola vez.
$listaDeContexto = function ($id, $rotulo, $campos) {
    $html = '';
    if ($rotulo !== null) {
        $html .= '<p class="small text-muted" style="margin-bottom:0"><strong>'
            . htmlspecialchars($rotulo) . '</strong></p>';
    }
    $html .= '<ul class="list-inline small text-muted" id="' . $id . '">';
    foreach ($campos as $etiqueta => $valor) {
        $html .= '<li><strong>' . htmlspecialchars($etiqueta) . ':</strong> '
            . htmlspecialchars(($valor === null || $valor === '') ? '—' : (string) $valor) . '</li>';
    }
    return $html . '</ul>';
};

$camposContexto = array(
    'Ejercicio' => $contexto['ano'],
    'Tienda' => $contexto['idTienda'],
    'Calculado el' => $contexto['momento'],
    'Autor' => $contexto['autor'],
    'Trayectoria' => $contexto['modoTrayectoria'],
    'Ventana de consolidación' => $contexto['ventanaDias'] . ' día(s)',
    'Ventana de registro tardío' => $contexto['timingVentanaDias'] . ' día(s)',
    'Umbral de fraccionado' => $contexto['umbralFraccionado'],
    'Umbral de magnitud' => $contexto['umbralMagnitud'],
    'Umbral por venta' => $contexto['umbralPorVenta'],
);

// En el ejercicio anterior lo que se mira no se calculó todo aquí: la mitad llegó
// en un fichero, y de esa mitad hay que decir de dónde vino. El ejercicio y la
// tienda ya los comprobó la admisión; el momento y el autor no los puede comprobar
// nadie desde dentro, porque el sistema no guarda qué fichero admitió antes. Dos
// emisiones del mismo ejercicio y la misma tienda llevan lo mismo salvo esos dos
// campos, así que enseñarlos es todo el control que hay contra clasificar sobre un
// resultado ya sustituido.
$hayContextoVigente = ($rama === 'anterior' && !empty($composicion['contextoVigente']));

$html = $listaDeContexto(
    'contextoComprobacionStock' . $sufijo,
    $hayContextoVigente ? 'Calculado en este ejercicio' : null,
    $camposContexto
);

if ($hayContextoVigente) {
    $contextoVigente = $composicion['contextoVigente'];
    $html .= $listaDeContexto(
        'contextoComprobacionStockAnteriorOrigen',
        'El fichero admitido se emitió en el ejercicio vigente',
        array(
            'Ejercicio' => $contextoVigente['ano'],
            'Tienda' => $contextoVigente['idTienda'],
            'Emitido el' => $contextoVigente['momento'],
            'Autor' => $contextoVigente['autor'],
            'Trayectoria' => $contextoVigente['modoTrayectoria'],
            'Ventana de consolidación' => $contextoVigente['ventanaDias'] . ' día(s)',
            'Ventana de registro tardío' => $contextoVigente['timingVentanaDias'] . ' día(s)',
            'Umbral de fraccionado' => $contextoVigente['umbralFraccionado'],
            'Umbral de magnitud' => $contextoVigente['umbralMagnitud'],
            'Umbral por venta' => $contextoVigente['umbralPorVenta'],
        )
    );
}

$html .= '<p>' . htmlspecialchars(sprintf($especificaciones[$rama]['preambulo'], count($composicion['filas']))) . '</p>';
$html .= '<table class="table table-bordered table-hover" id="tablaComprobacionStock' . $sufijo . '">';

// Las columnas que llevan nota se numeran antes de pintar la cabecera, para que la
// marca de arriba y el texto de abajo se correspondan cuando haya más de una.
$notas = array();
foreach ($columnas as $columna) {
    if (isset($columna['procedencia']) || isset($columna['nota'])) {
        $notas[$columna['titulo']] = str_repeat('*', count($notas) + 1);
    }
}

$html .= '<thead><tr>';
foreach ($columnas as $columna) {
    if ($columna['formato'] === 'seleccion') {
        $html .= '<th><input type="checkbox" id="chkComprobacionStock' . $sufijo . 'Todos" checked></th>';
    } else {
        // La marca de la cabecera es lo que hace visible que la columna dice menos de
        // lo que parece, sin depender de que nadie pase el ratón por encima.
        $html .= '<th>' . htmlspecialchars($columna['titulo'])
            . (isset($notas[$columna['titulo']]) ? ' <sup>' . $notas[$columna['titulo']] . '</sup>' : '')
            . '</th>';
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
            case 'cantidad':
                // Una cantidad se escribe aquí con la misma regla que en el fichero y
                // en el informe. Sin ella, el lenguaje pasa a notación científica por
                // debajo de una cienmilésima: una millonésima es una cantidad legítima
                // —el esquema guarda seis decimales— y saldría en pantalla «1.0E-6»
                // mientras el informe de la misma composición escribe «0.000001».
                // Poder comparar fila a fila las dos salidas es para lo que existe el
                // informe, y dos escrituras distintas del mismo número lo impiden.
                $celda = htmlspecialchars(
                    $valor !== null ? ClaseComprobacionStockCantidad::comoTexto($valor) : '—'
                );
                break;
            case 'ajena':
                // Distinta de la etiqueta propia a propósito: si se pintaran igual,
                // el lector no tendría cómo saber cuál de las dos sostiene esta
                // comprobación y cuál viene resuelta de otro sitio.
                $celda = ($valor !== null && $valor !== '')
                    ? '<span class="label label-info">' . htmlspecialchars((string) $valor) . '</span>'
                    : '—';
                break;
            default:
                $celda = htmlspecialchars((string) $valor);
        }
        $html .= '<td>' . $celda . '</td>';
    }
    $html .= '</tr>';
}

$html .= '</tbody></table>';

foreach ($columnas as $columna) {
    $texto = isset($columna['procedencia']) ? $columna['procedencia']
        : (isset($columna['nota']) ? $columna['nota'] : null);
    if ($texto !== null) {
        $html .= '<p class="small text-muted"><sup>' . $notas[$columna['titulo']] . '</sup> <strong>'
            . htmlspecialchars($columna['titulo']) . ':</strong> '
            . htmlspecialchars($texto) . '</p>';
    }
}

return $html;
