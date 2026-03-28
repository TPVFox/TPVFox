<?php

/**
 * Vista: opciones <option> para el selector de periodo y para el selector anual
 *        de tipos de incidencia.
 *
 * Tipos de incidencia disponibles (constante compartida con vistaBarraPeriodos).
 */

const POSSTOCK_TIPOS_INCIDENCIA = [
    ['v' => 'caso1',  't' => 'Stock Negativo / Desajuste',              'short' => 'C1',  'soloAnual' => false],
    ['v' => 'caso2',  't' => 'Entrada con stock alto',                  'short' => 'C2',  'soloAnual' => false],
    ['v' => 'caso3a', 't' => 'Caída de rotación',                       'short' => 'C3a', 'soloAnual' => false],
    ['v' => 'caso3b', 't' => 'Entrada sin rotación previa',             'short' => 'C3b', 'soloAnual' => false],
    ['v' => 'caso5',  't' => 'Venta Cero (Rotura física)',              'short' => 'C5',  'soloAnual' => false],
    ['v' => 'caso6a', 't' => 'Agotamiento Estimado (ROP estacional)',   'short' => 'C6a', 'soloAnual' => false],
    ['v' => 'caso6b', 't' => 'Punto de Pedido (ROP histórico fijo)',    'short' => 'C6b', 'soloAnual' => true],
    ['v' => 'caso7a', 't' => 'Merma no registrada',                     'short' => 'C7a', 'soloAnual' => false],
    ['v' => 'caso7b', 't' => 'Recepción no registrada',                 'short' => 'C7b', 'soloAnual' => false],
    ['v' => 'caso9',  't' => 'Merma por backstaging',                   'short' => 'C9',  'soloAnual' => false],
];

/**
 * Devuelve los tipos de incidencia activos (incluyendo caso4 si está habilitado).
 */
function posstockTiposActivos(bool $incluirStockInactivo): array
{
    $tipos = POSSTOCK_TIPOS_INCIDENCIA;
    if ($incluirStockInactivo) {
        $tipos[] = ['v' => 'caso4', 't' => 'Stock Inactivo en Periodo', 'short' => 'C4', 'soloAnual' => false];
    }
    return $tipos;
}

/**
 * Genera el HTML de los <option> para el selector de periodo.
 *
 * @param string $tipo                Tipo de periodo
 * @param int    $anio                Año seleccionado
 * @param int    $ventanaDias         0 = sin restricción; 7 o 14 = días de ventana
 * @param bool   $incluirStockInactivo
 * @param int    $anioActual          Año actual del servidor
 * @param array  $periodosConVentana  Array de ['numero' => n, 'en_ventana' => bool]
 *                                   para el año en curso (desde getInfoPeriodos).
 *                                   Null para años pasados/futuros.
 * @return string  HTML de <option> elementos (sin el primer <option> de placeholder).
 */
function renderOpcionesPeriodo(
    string $tipo,
    int    $anio,
    int    $ventanaDias,
    bool   $incluirStockInactivo,
    int    $anioActual,
    ?array $periodosConVentana = null
): string {
    $html = '';

    if ($tipo === 'anual') {
        // Para tipo anual las opciones son los tipos de incidencia
        $anualEnVentana = ($anio === $anioActual && $ventanaDias > 0);
        foreach (posstockTiposActivos($incluirStockInactivo) as $ti) {
            $bloqueado = $anualEnVentana && $ti['v'] !== 'caso6b';
            $texto     = htmlspecialchars($ti['t']) . ($bloqueado ? ' ⚠ (ventana)' : '');
            $disabled  = $bloqueado ? ' disabled' : '';
            $title     = $bloqueado
                ? ' title="Periodo dentro de la ventana de consolidación (' . $ventanaDias . ' días)"'
                : '';
            $html .= '<option value="' . htmlspecialchars($ti['v']) . '"' . $disabled . $title . '>' . $texto . '</option>';
        }
        return $html;
    }

    // Año en curso con ventana: usar datos del backend (periodos con en_ventana)
    if ($anio === $anioActual && $periodosConVentana !== null) {
        foreach ($periodosConVentana as $p) {
            $etiqueta = _etiquetaOpcion($tipo, (int)$p['numero'], $anio);
            $enV      = !empty($p['en_ventana']);
            $texto    = htmlspecialchars($etiqueta) . ($enV ? ' ⚠ (ventana)' : '');
            $disabled = $enV ? ' disabled' : '';
            $title    = $enV ? ' title="Dentro de la ventana de consolidación (' . $ventanaDias . ' días)"' : '';
            $html .= '<option value="' . (int)$p['numero'] . '"' . $disabled . $title . '>' . $texto . '</option>';
        }
        return $html;
    }

    // Año pasado/futuro: todas las opciones habilitadas
    foreach (_opcionesPeriodo($tipo, $anio) as $o) {
        $html .= '<option value="' . htmlspecialchars((string)$o['v']) . '">' . htmlspecialchars($o['t']) . '</option>';
    }
    return $html;
}

/**
 * Genera la lista de {v, t} para un tipo y año dados (para años no actuales).
 */
function _opcionesPeriodo(string $tipo, int $anio): array
{
    $meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $opts  = [];

    switch ($tipo) {
        case 'semana':
            $totalSem = _totalSemanasAnio($anio);
            for ($s = 1; $s <= $totalSem; $s++) {
                $opts[] = ['v' => $s, 't' => 'Semana ' . $s];
            }
            break;
        case 'quincena':
            for ($q = 1; $q <= 24; $q++) {
                $mes   = (int)ceil($q / 2);
                $mitad = ($q % 2 === 1) ? '1ª' : '2ª';
                $opts[] = ['v' => $q, 't' => $mitad . ' quincena ' . $meses[$mes]];
            }
            break;
        case 'mes':
            for ($m = 1; $m <= 12; $m++) {
                $opts[] = ['v' => $m, 't' => $meses[$m] . ' ' . $anio];
            }
            break;
        case 'trimestre':
            $opts = [
                ['v' => 1, 't' => 'T1 (Ene–Mar)'],
                ['v' => 2, 't' => 'T2 (Abr–Jun)'],
                ['v' => 3, 't' => 'T3 (Jul–Sep)'],
                ['v' => 4, 't' => 'T4 (Oct–Dic)'],
            ];
            break;
        case 'cuatrimestre':
            $opts = [
                ['v' => 1, 't' => 'C1 (Ene–Abr)'],
                ['v' => 2, 't' => 'C2 (May–Ago)'],
                ['v' => 3, 't' => 'C3 (Sep–Dic)'],
            ];
            break;
        case 'semestre':
            $opts = [
                ['v' => 1, 't' => '1er semestre (Ene–Jun)'],
                ['v' => 2, 't' => '2º semestre (Jul–Dic)'],
            ];
            break;
    }
    return $opts;
}

/** Etiqueta de dropdown para un periodo concreto. */
function _etiquetaOpcion(string $tipo, int $n, int $anio): string
{
    $lista = _opcionesPeriodo($tipo, $anio);
    foreach ($lista as $o) {
        if ((int)$o['v'] === $n) return $o['t'];
    }
    return $tipo . ' ' . $n;
}

/** Calcula el número total de semanas del año. */
function _totalSemanasAnio(int $anio): int
{
    $jan1 = new DateTime(sprintf('%04d-01-01', $anio));
    $dow  = (int)$jan1->format('N'); // 1=lun … 7=dom

    $finSem1 = clone $jan1;
    if ($dow !== 7) {
        $finSem1->modify('+' . (7 - $dow) . ' days');
    }

    $dec31      = new DateTime(sprintf('%04d-12-31', $anio));
    $diasRest   = (int)$finSem1->diff($dec31)->days;
    return 1 + (int)ceil($diasRest / 7);
}
