<?php
/**
 * Vista: barra de botones de navegación entre periodos.
 *
 * @param string     $tipo           Tipo de periodo activo
 * @param int        $anio           Año activo
 * @param mixed      $numeroActivo   Número de periodo activo (int) o tipo de incidencia (string) si anual
 * @param int        $ventanaDias    Días de ventana de consolidación
 * @param bool       $enVentana      true si el periodo activo está dentro de la ventana
 * @param bool       $incluirStockInactivo
 * @param array|null $periodos       [{numero, en_ventana, label}] — precalculado por getInfoPeriodos;
 *                                   null = se calculará internamente (para tipos no actuales)
 * @return string  HTML de los botones
 */
function renderBarraPeriodos(
    string $tipo,
    int    $anio,
    $numeroActivo,
    int    $ventanaDias,
    bool   $enVentana,
    bool   $incluirStockInactivo,
    ?array $periodos = null
): string {
    $html = '';

    if ($tipo === 'anual') {
        foreach (posstockTiposActivos($incluirStockInactivo) as $ti) {
            if ($enVentana && $ti['v'] !== 'caso6b') {
                $cls    = 'btn btn-default btn-xs disabled';
                $extras = 'disabled title="Periodo en ventana de consolidación — solo C6b disponible"';
            } else {
                $cls    = ($ti['v'] === $numeroActivo) ? 'btn btn-primary btn-xs' : 'btn btn-default btn-xs';
                $extras = '';
            }
            $html .= '<button type="button" class="' . $cls . '" ' . $extras
                . ' onclick="posstockNavegar(\'' . htmlspecialchars($ti['v']) . '\')"'
                . ' id="posstockBtn_' . htmlspecialchars($ti['v']) . '">'
                . htmlspecialchars($ti['short']) . ' ' . htmlspecialchars($ti['t'])
                . '</button> ';
        }
        return $html;
    }

    // Para tipos no-anuales
    if ($periodos === null) {
        return ''; // El handler debe pasar los periodos
    }

    foreach ($periodos as $p) {
        $n         = (int)$p['numero'];
        $enV       = !empty($p['en_ventana']);
        $etiqueta  = _etiquetaBoton($tipo, $n);

        if ($enV) {
            $cls    = 'btn btn-default btn-xs disabled';
            $extras = 'disabled title="Dentro de la ventana de consolidación (' . $ventanaDias . ' días)"';
        } elseif ($n === (int)$numeroActivo) {
            $cls    = 'btn btn-primary btn-xs';
            $extras = '';
        } else {
            $cls    = 'btn btn-default btn-xs';
            $extras = '';
        }

        $html .= '<button type="button" class="' . $cls . '" ' . $extras
            . ' onclick="posstockNavegar(' . $n . ')"'
            . ' id="posstockBtn_' . $n . '">'
            . htmlspecialchars($etiqueta)
            . '</button> ';
    }

    return $html;
}

/**
 * Etiqueta corta de un botón de la barra según tipo y número.
 * (Equivalente PHP de posstockEtiquetaBoton en JS)
 */
function _etiquetaBoton(string $tipo, int $n): string
{
    $meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    switch ($tipo) {
        case 'semana':       return 'S' . $n;
        case 'mes':          return $meses[$n] ?? ('M' . $n);
        case 'trimestre':    return 'T' . $n;
        case 'cuatrimestre': return 'C' . $n;
        case 'semestre':     return $n === 1 ? '1S' : '2S';
        case 'anual':        return 'Año';
        case 'quincena':
            $mes   = (int)ceil($n / 2);
            $mitad = ($n % 2 === 1) ? 'a' : 'b';
            return ($meses[$mes] ?? ('M' . $mes)) . $mitad;
        default:
            return (string)$n;
    }
}
