<?php
/**
 * Vista: tabla de incidencias POSStock.
 *
 * @param array $filas  Array de incidencias devuelto por getIncidencias().
 * @param array $cfg    Parámetros de contexto:
 *                      - fecha_fin_movimientos  string  YYYY-MM-DD
 *                      - fecha_inicio_stock     string  YYYY-MM-DD
 *                      - anio                   int
 *                      - c3b_dias_post          int
 *                      - c6b_dias_historico     int
 * @return string  HTML de la tabla.
 */
function renderTablaPosstock(array $filas, array $cfg): string
{
    if (empty($filas)) {
        return '<div class="alert alert-success">Sin incidencias detectadas para este periodo.</div>';
    }

    $ffMov          = $cfg['fecha_fin_movimientos'] ?? '';
    $fiStock        = $cfg['fecha_inicio_stock']    ?? '';
    $anio           = (int)($cfg['anio']             ?? date('Y'));
    $diasPost       = (int)($cfg['c3b_dias_post']    ?? 14);
    $c6bDias        = (int)($cfg['c6b_dias_historico'] ?? 90);
    $mostrarTecnico = !empty($cfg['mostrar_tecnico']);

    $fiInicio = $anio . '-01-01';

    $badgeSev = [
        'CRITICA' => '<span class="label label-danger">Crítica</span>',
        'ALTA'    => '<span class="label label-danger" style="background-color:#e8600a;">Alta</span>',
        'MEDIA'   => '<span class="label label-warning">Media</span>',
        'BAJA'    => '<span class="label label-info">Baja</span>',
    ];

    $html  = '<style>#posstockTabla .label{margin-right:3px;margin-bottom:3px;display:inline-block;}</style>'
           . '<table class="table table-condensed table-hover table-bordered" id="posstockTabla">';
    $html .= '<thead><tr>'
        . '<th>Artículo</th>'
        . '<th>Nombre</th>'
        . '<th>Tipo incidencia</th>'
        . '<th>Severidad</th>'
        . '<th>Detalle</th>'
        . '<th>Posible causa</th>'
        . '<th>Listado mayor</th>'
        . '</tr></thead><tbody>';

    foreach ($filas as $f) {
        $detalle          = '';
        $tipoLabel        = '';
        $posibleCausaHtml = null;   // null = usar htmlspecialchars($f['posible_causa']); set HTML directamente para tipos enriquecidos
        $tipo             = $f['tipo'] ?? '';

        // ── Helpers ───────────────────────────────────────────────────────────
        $fmtF = static function (?string $d): string {
            if (!$d) return '—';
            $p = explode('-', $d);
            return (count($p) === 3) ? ($p[2] . '/' . $p[1]) : $d;
        };

        $fmtN = static function ($v, int $dec = 2): string {
            return ($v !== null && $v !== '') ? number_format((float)$v, $dec, '.', '') : '—';
        };

        // ── C1a / C1b ─────────────────────────────────────────────────────────
        if ($tipo === 'Inventario en negativo') {
            $badges = '';
            $nEnt   = isset($f['n_entradas']) && $f['n_entradas'] !== null ? (int)$f['n_entradas'] : null;

            if ($nEnt !== null && $nEnt === 0) {
                $badges .= ' <span class="label label-danger"'
                    . ' title="No se registró ninguna recepción de proveedor en el periodo.'
                    . ' Probable recepción sin registrar.">Sin recepciones</span>';
            }
            if (!empty($f['fraccionado_es_causa'])) {
                $badges .= ' <span class="label label-info"'
                    . ' title="El stock negativo se explica por acumulación de imprecisiones en ventas por peso o fraccionado.">'
                    . 'Stock decimal</span>';
            }
            if (!empty($f['ya_negativo_inicio'])) {
                $badges .= ' <span class="label label-warning"'
                    . ' title="El inventario ya estaba en negativo al inicio del periodo.'
                    . ' El problema viene de un rango anterior.">Arrastrado</span>';
            }
            if (!empty($f['c7b_activo'])) {
                $c7bOff  = $f['c7b_offset_estimado'] ?? null;
                $c7bUnit = ($f['c7b_tipo_articulo'] ?? 'unidad') === 'peso' ? 'kg' : 'ud.';
                $c7bTip  = 'El patrón de stock negativo es sistemático entre recepciones'
                    . ($c7bOff !== null ? ' (~' . abs((float)$c7bOff) . ' ' . $c7bUnit . ' de déficit medio)' : '')
                    . '. Causa probable: recepción no registrada. Ver detalle en C7b.';
                $badges .= ' <span class="label label-danger" title="' . htmlspecialchars($c7bTip) . '">Recepción no registrada</span>';
            }

            // Línea 1: badges
            $detalle = $badges;

            // Línea 2: trayectoria del stock (información principal)
            $linea2 = '';
            if (isset($f['saldo_base'])) {
                $linea2 .= '<span title="Stock al inicio del periodo">Inicio: ' . $fmtN($f['saldo_base']) . '</span> →';
            }
            $linea2 .= ' Stock: <strong>' . $fmtN($f['stock_actual'] ?? null) . '</strong>';
            if (isset($f['dias_en_negativo']) && $f['dias_en_negativo'] > 0) {
                $linea2 .= ' | <span title="Días del periodo en que el stock acumulado fue negativo">'
                    . $f['dias_en_negativo'] . ' d en negativo</span>';
            }
            if (isset($f['min_balance'])) {
                $linea2 .= ' | Mín. período: ' . $fmtN($f['min_balance']);
            }
            $detalle .= '<br>' . $linea2;

            // Línea 3: contexto complementario
            $linea3 = '';
            if (isset($f['n_ventas']) && $f['n_ventas'] !== null) {
                $linea3 .= 'Ventas: ' . (int)$f['n_ventas'];
            }
            if ($nEnt !== null && $nEnt > 0) {
                $linea3 .= ($linea3 ? ' | ' : '') . 'Últ. recepción: ' . $fmtF($f['ultima_entrada'] ?? null);
            }
            if (!empty($f['prov_habitual_nombre'])) {
                if (!empty($f['prov_es_mismo'])) {
                    $linea3 .= ($linea3 ? ' | ' : '')
                        . '<span title="Proveedor con más compras del artículo en el año en curso">Prov: '
                        . htmlspecialchars($f['prov_habitual_nombre']) . '</span>';
                } else {
                    $linea3 .= ($linea3 ? ' | ' : '')
                        . '<span title="Proveedor con más compras del artículo en el año en curso">Prov. habitual: '
                        . htmlspecialchars($f['prov_habitual_nombre']) . '</span>';
                    if (!empty($f['prov_ultimo_nombre'])) {
                        $linea3 .= ' | <span title="Proveedor del último albarán recibido ('
                            . htmlspecialchars($f['prov_ultima_fecha'] ?? '') . ')">Último: '
                            . htmlspecialchars($f['prov_ultimo_nombre']) . '</span>';
                    }
                }
            }
            if ($linea3) {
                $detalle .= '<br><small class="text-muted">' . $linea3 . '</small>';
            }

        // ── C1b ───────────────────────────────────────────────────────────────
        } elseif ($tipo === 'Desajuste Puntual de Stock') {
            $badges1b = '';
            $nEnt1b   = isset($f['n_entradas']) && $f['n_entradas'] !== null ? (int)$f['n_entradas'] : null;

            if (!empty($f['timing_proximo'])) {
                $badges1b .= ' <span class="label label-info" title="Entrada de proveedor registrada en los 3 días siguientes al momento del negativo: probable venta registrada antes que la recepción.">Timing recepción</span>';
            }
            if (!empty($f['fraccionado_es_causa'])) {
                $badges1b .= ' <span class="label label-info" title="El mínimo negativo se explica por acumulación de imprecisiones en ventas por peso o fraccionado.">Stock decimal</span>';
            }
            if ($nEnt1b !== null && $nEnt1b === 0 && empty($f['fraccionado_es_causa'])) {
                $badges1b .= ' <span class="label label-warning" title="Sin recepciones que justifiquen la recuperación. Revisar posibles movimientos duplicados, devoluciones o ajustes manuales.">Sin entradas</span>';
            }

            // Línea 1: badges
            $detalle = $badges1b;

            // Línea 2: trayectoria del mínimo (información principal)
            $linea2b = 'Mín.: <strong>' . $fmtN($f['min_balance'] ?? null) . '</strong>'
                . (!empty($f['fecha_minimo']) ? ' (' . date('d/m', strtotime($f['fecha_minimo']))
                    . (isset($f['dias_en_minimo']) && $f['dias_en_minimo'] > 1 ? ', ' . $f['dias_en_minimo'] . ' d' : '')
                    . ')' : '')
                . ' → Cierre: <strong>' . $fmtN($f['stock_actual'] ?? null) . '</strong>';
            $detalle .= '<br>' . $linea2b;

            // Línea 3: contexto complementario
            $linea3b = '';
            if (isset($f['n_ventas']) && $f['n_ventas'] !== null) {
                $linea3b .= 'Ventas: ' . (int)$f['n_ventas'];
            }
            if ($nEnt1b !== null && $nEnt1b > 0) {
                $linea3b .= ($linea3b ? ' | ' : '') . 'Últ. recepción: ' . $fmtF($f['ultima_entrada'] ?? null);
            }
            if ($linea3b) {
                $detalle .= '<br><small class="text-muted">' . $linea3b . '</small>';
            }

        // ── C2 ────────────────────────────────────────────────────────────────
        } elseif ($tipo === 'Entrada con stock alto') {
            // C7a-007: badge merma acumulada si el artículo tiene C7a activo
            $badgeC7aMerma = '';
            if (!empty($f['c7a_activo'])) {
                $c7aDelta = isset($f['c7a_delta_acumulado']) ? number_format((float)$f['c7a_delta_acumulado'], 0, '.', '') : '?';
                $c7aTipo  = ($f['c7a_tipo_articulo'] ?? '') === 'peso' ? 'kg' : 'ud.';
                $badgeC7aMerma = '<span class="label label-danger" title="Este artículo tiene merma acumulada activa (~' . $c7aDelta . ' ' . $c7aTipo . ' de pérdida detectada). Posible salida sin documentar.">'
                    . 'Merma acumulada</span> ';
            }
            $badgeC2  = $badgeC7aMerma;
            $ncant    = (float)($f['ncant'] ?? 0);
            $ncantA   = isset($f['ncant_anterior']) && $f['ncant_anterior'] !== null ? (float)$f['ncant_anterior'] : null;
            $dias     = isset($f['dias_desde_anterior']) && $f['dias_desde_anterior'] !== null ? (int)$f['dias_desde_anterior'] : null;
            $vtr      = isset($f['ventas_entre_recepciones']) && $f['ventas_entre_recepciones'] !== null ? (float)$f['ventas_entre_recepciones'] : null;
            $cob      = isset($f['cobertura_dias']) && $f['cobertura_dias'] !== null ? (int)$f['cobertura_dias'] : null;
            $c2Cat    = $f['c2_categoria'] ?? '';

            if ($c2Cat === 'tendencia') {
                $nEvt   = (int)($f['n_eventos'] ?? 1);
                $cobIni = isset($f['cobertura_inicio']) && $f['cobertura_inicio'] !== null ? (int)$f['cobertura_inicio'] . ' días' : '—';
                $cobFin = $cob !== null ? $cob . ' días' : 'sin ventas';
                $fInicio = $f['fecha_inicio'] ?? '—';
                $fFin    = $f['fecha'] ?? '—';
                $stockMax = (float)($f['stock_previo'] ?? 0);
                $badgeC2 = '<span class="label label-warning" title="La cobertura crece de ' . $cobIni . ' a ' . $cobFin
                    . ' en ' . $nEvt . ' entregas: las compras superan el ritmo de ventas de forma sistemática.">Tendencia creciente</span>';
                $detalle = $badgeC2
                    . ' <strong>' . $nEvt . ' entregas</strong>'
                    . ' | Cobertura: ' . $cobIni . ' → <strong>' . $cobFin . '</strong>'
                    . ' | Periodo: ' . $fInicio . ' → ' . $fFin
                    . ' | Stock máx.: ' . number_format($stockMax, 2, '.', '');

            } elseif ($c2Cat === 'acumulacion') {
                $nEvt    = (int)($f['n_eventos'] ?? 1);
                $cobFin  = $cob !== null ? $cob . ' días' : 'sin ventas';
                $fInicio = $f['fecha_inicio'] ?? '—';
                $fFin    = $f['fecha'] ?? '—';
                $stockMax = (float)($f['stock_previo'] ?? 0);
                $badgeC2 = '<span class="label label-danger" title="' . $nEvt
                    . ' recepciones consecutivas sin retorno: el stock se acumula sin salida. Revisar gestión de devoluciones.">Acumulación crónica</span>';
                $detalle = $badgeC2
                    . ' <strong>' . $nEvt . ' recepciones</strong> consolidadas'
                    . ' | Periodo: ' . $fInicio . ' → ' . $fFin
                    . ' | Stock máx.: ' . number_format($stockMax, 2, '.', '')
                    . ' | Cobertura final: <strong>' . $cobFin . '</strong>';

            } else {
                $esDupProbable = $dias !== null && $dias <= 1 && $ncantA !== null
                    && (max($ncant, $ncantA) > 0) && (abs($ncant - $ncantA) / max($ncant, $ncantA)) < 0.15;
                $esDupPosible  = !$esDupProbable && $dias !== null && $dias <= 3 && $ncantA !== null
                    && (max($ncant, $ncantA) > 0) && (abs($ncant - $ncantA) / max($ncant, $ncantA)) < 0.15;

                if ($esDupProbable) {
                    $badgeC2 .= ' <span class="label label-danger" title="Cantidad similar recibida hace ' . $dias
                        . ' día(s): muy probable albarán registrado dos veces.">Duplicado probable</span>';
                } elseif ($esDupPosible) {
                    $badgeC2 .= ' <span class="label label-warning" title="Cantidad similar recibida hace ' . $dias
                        . ' días: verificar si el albarán se registró dos veces.">Posible duplicado</span>';
                }
                if ($cob === null) {
                    $badgeC2 .= ' <span class="label label-danger" title="El artículo no registra ventas en el periodo analizado.">Sin ventas</span>';
                }
                if ($dias !== null && $dias <= 14 && $vtr !== null && $vtr < 1) {
                    $badgeC2 .= ' <span class="label label-warning" title="No hubo ventas entre la recepción anterior (' . $dias
                        . ' días antes) y esta: el pedido anterior no había rotado.">Pedido prematuro</span>';
                }
                if (!$esDupProbable && $c2Cat === 'severo') {
                    $badgeC2 .= ' <span class="label label-warning" title="El stock previo supera ampliamente la entrada recibida.">Sobrestock severo</span>';
                }

                $previo = (float)($f['stock_previo'] ?? 0);
                if (isset($f['ratio']) && $f['ratio'] !== null) {
                    $ratio = number_format((float)$f['ratio'], 1, '.', '') . '×';
                } elseif ($ncant > 0) {
                    $ratio = number_format($previo / $ncant, 1, '.', '') . '×';
                } else {
                    $ratio = '—';
                }
                $cobStr = $cob !== null ? $cob . ' días' : 'sin ventas';

                $detalle = $badgeC2
                    . ' Previo: ' . number_format($previo, 2, '.', '')
                    . ' | Entrada: <strong>' . number_format($ncant, 2, '.', '') . ' ud. (' . $ratio . ' previo)</strong>'
                    . ' | Cobertura: <strong>' . $cobStr . '</strong>';

                if ($dias !== null) {
                    $detalle .= ' | Anter.: ' . $dias . ' días';
                }
                $detalle .= ' | Fecha: ' . ($f['fecha'] ?? '—');
            }

        // ── C3a ───────────────────────────────────────────────────────────────
        } elseif ($tipo === 'Caída de rotación') {
            $avgCad      = (float)($f['avg_cadencia_dias'] ?? 0);
            $semVal      = (float)($f['semanas_desde_ultima_venta'] ?? 0);
            $diasSV      = $semVal > 0 ? (int)round($semVal * 7) : null;
            $desdeRep    = !empty($f['desde_reposicion']);
            $stkRaw      = isset($f['stock_actual']) && $f['stock_actual'] !== null ? (float)$f['stock_actual'] : null;
            $stkStr      = $stkRaw !== null ? (($stkRaw == (int)$stkRaw) ? (string)(int)$stkRaw : number_format($stkRaw, 2, '.', '')) : '—';
            $esFast      = $avgCad > 0 && $avgCad <= 7 && !$desdeRep;
            $sev         = $f['severidad'] ?? '';

            if ($esFast) {
                $badgeC3a = $sev === 'ALTA'
                    ? '<span class="label label-danger" title="Alta rotación y más del doble del umbral sin venta: riesgo de caducidad o merma">Riesgo caducidad</span>'
                    : '<span class="label label-warning" title="Artículo de alta rotación por encima del umbral dinámico sin venta">Riesgo caducidad</span>';
            } else {
                $badgeC3a = $sev === 'ALTA'
                    ? '<span class="label label-danger" title="Más del doble del umbral dinámico sin venta: rotación caída drásticamente">Caída severa</span>'
                    : '<span class="label label-warning" title="Por encima del umbral dinámico de rotación">Rotación caída</span>';
            }

            $refFechaStr = $desdeRep
                ? 'Desde recep.: ' . $fmtF($f['fecha_primera_entrada'] ?? null)
                : 'Últ. venta: ' . $fmtF($f['ultima_venta'] ?? null);

            if ($diasSV !== null && $diasSV > 0) {
                $sinVentaStr = '<strong>' . $diasSV . ' d</strong> sin venta';
                if ($avgCad > 0) {
                    $sinVentaStr .= ' <small class="text-muted">(normal ' . number_format($avgCad, 1, '.', '') . ' d)</small>';
                }
            } else {
                $sinVentaStr = '<strong>—</strong>';
            }

            $detalle = $badgeC3a . ' ' . $sinVentaStr
                . ' | Stock: <strong>' . $stkStr . '</strong>'
                . ' | ' . $refFechaStr;

        // ── C5 ────────────────────────────────────────────────────────────────
        } elseif ($tipo === 'Venta Cero (Posible Rotura Física)') {
            $badgeStockNoFiable = !empty($f['stock_no_fiable'])
                ? ' <span class="label label-warning" title="Este artículo tiene stock negativo activo (C1a). Los datos de stock usados en este análisis pueden no ser fiables.">Stock no fiable</span>'
                : '';
            $badgeKO = !empty($f['ko'])
                ? ' <span class="label label-danger" title="Stock negativo durante la rotura en curso: inventario en descubierto">KO</span>'
                : '';
            $badgeCR = !empty($f['cr'])
                ? ' <span class="label" style="background:#e67e22;" title="Rotura crítica: duración confirmada supera el umbral">CR</span>'
                : '';
            $badgeRK = !empty($f['rk'])
                ? ' <span class="label label-warning" title="Rotura confirmada significativa (≥ cadencia media)">RK</span>'
                : '';
            $badgeEstado = isset($f['fecha_fin_rotura']) && $f['fecha_fin_rotura']
                ? '<span class="label label-success">Recuperada ' . $fmtF($f['fecha_fin_rotura']) . '</span>'
                : '<span class="label label-warning">En curso</span>';

            $diasRoturaStr = isset($f['dias_rotura'])
                ? '<strong>' . $f['dias_rotura'] . ' d</strong>'
                : '<strong>—</strong>';

            $avgC5 = (float)($f['avg_dias_entre_ventas'] ?? 0);
            $c5mCfg = [
                'GammaReg' => ['lbl' => 'Γ', 'cls' => 'label-success',  'comp' => 'Alta rotación, patrón muy regular',     'tip' => 'Gamma — alta rotación con intervalos regulares. Umbral = cuantil Gamma ajustado a los gaps.'],
                'Gamma'    => ['lbl' => 'Γ', 'cls' => 'label-default',  'comp' => 'Rotación moderada, gaps ajustados a Gamma', 'tip' => 'Gamma — ajuste directo a la distribución de gaps histórica. Umbral = cuantil de probabilidad.'],
                'Normal'   => ['lbl' => 'N', 'cls' => 'label-default',  'comp' => 'Intervalos muy uniformes (aproximación Normal)', 'tip' => 'Normal — gaps prácticamente constantes (varianza ≈ 0). Umbral = media + 3σ.'],
                'BN'       => ['lbl' => 'BN','cls' => 'label-info',     'comp' => 'Demanda en rachas o lotes',               'tip' => 'Binomial Negativa — sobredispersión detectada (ventas agrupadas por periodos). Umbral ajustado a la variabilidad extra.'],
                'Poisson'  => $avgC5 > 0 && $avgC5 < 4
                    ? ['lbl' => 'Poi', 'cls' => 'label-primary', 'comp' => 'Alta rotación, gaps ~ exponencial',   'tip' => 'Poisson — alta rotación con dispersión normal. Umbral: gap > −ln(p)/λ.']
                    : ['lbl' => 'Poi', 'cls' => 'label-default', 'comp' => 'Demanda esporádica, baja frecuencia', 'tip' => 'Poisson — artículo de venta poco frecuente. Umbral conservador para eventos raros.'],
            ];
            $modelo   = $f['modelo_usado'] ?? 'Poisson';
            $c5m      = $c5mCfg[$modelo] ?? $c5mCfg['Poisson'];
            $badgeMod = ' <span class="label ' . $c5m['cls'] . '" title="' . htmlspecialchars($c5m['tip']) . '">' . $c5m['lbl'] . '</span>';
            $sdStr    = (isset($f['sd_dias']) && $f['sd_dias'] !== null) ? ' σ=' . $f['sd_dias'] . ' d' : '';

            $detalle = $badgeStockNoFiable . $badgeKO . $badgeCR . $badgeRK . ' ' . $badgeEstado . ' ' . $diasRoturaStr
                . ' | Desde: ' . $fmtF($f['fecha_inicio_rotura'] ?? null)
                . ' | Últ. venta: ' . $fmtF($f['ultima_venta'] ?? null)
                . ' | Cadencia: ' . (isset($f['avg_dias_entre_ventas']) ? $f['avg_dias_entre_ventas'] . ' d' : '—')
                . $sdStr
                . ' (umbral ' . (isset($f['umbral_dias']) ? $f['umbral_dias'] . ' d' : '—') . ')'
                . $badgeMod
                . ' <small class="text-muted">· ' . htmlspecialchars($c5m['comp']) . '</small>';

        // ── C3b ───────────────────────────────────────────────────────────────
        } elseif ($tipo === 'Entrada sin rotación previa') {
            $stockC3b = isset($f['stock_actual']) && $f['stock_actual'] !== null ? number_format((float)$f['stock_actual'], 2, '.', '') : '—';
            $nEnt3b   = (int)($f['n_entradas'] ?? 1) ?: 1;
            $qty3b    = isset($f['cantidad_recibida']) && $f['cantidad_recibida'] !== null ? number_format((float)$f['cantidad_recibida'], 2, '.', '') : null;

            if (empty($f['ultima_salida'])) {
                $badgeC3b = $nEnt3b >= 3
                    ? '<span class="label label-danger" title="Pedido ' . $nEnt3b . ' veces sin ninguna venta registrada">Pedidos repetidos sin venta</span>'
                    : '<span class="label label-danger" title="Artículo que nunca ha tenido ventas registradas">Sin ventas</span>';
            } else {
                $badgeC3b = $nEnt3b >= 2
                    ? '<span class="label label-warning" title="Se repone ' . $nEnt3b . ' veces pese a no tener rotación activa">Reposición sin rotación</span>'
                    : '<span class="label label-warning" title="Artículo con rotación muy baja o nula">Sin rotación</span>';
            }

            $nDev3b  = (int)($f['n_devoluciones'] ?? 0);
            $qDev3b  = isset($f['cantidad_devuelta']) && $f['cantidad_devuelta'] !== null ? (float)$f['cantidad_devuelta'] : 0.0;
            $badgeDev3b = '';
            if ($nDev3b > 0) {
                $qQty = $qty3b !== null ? (float)$qty3b : 0.0;
                $ratioDevStr = ($qQty > 0) ? ' (' . (int)round(($qDev3b / $qQty) * 100) . '%)' : '';
                $devTip = ($qQty > 0 && $qDev3b >= $qQty * 0.8)
                    ? 'Devuelto casi en su totalidad al proveedor'
                    : 'Devolución parcial al proveedor';
                $badgeDev3b = ' <span class="label label-default" title="' . htmlspecialchars($devTip) . '">Dev.' . $ratioDevStr . '</span>';
            }

            $entStr3b = 'Recepciones: ' . $nEnt3b . ($qty3b !== null ? ' (' . $qty3b . ' ud.)' : '');
            if (!empty($f['ultima_salida'])) {
                $movStr3b = 'Últ. venta: ' . $fmtF($f['ultima_salida'])
                    . ' | <strong>' . ($f['semanas_desde_ultima_salida'] ?? '—') . ' sem.</strong> sin movimiento';
            } elseif (!empty($f['fecha_primera_entrada'])) {
                $movStr3b = 'Primera recepción: ' . $fmtF($f['fecha_primera_entrada']) . ' | <strong>Sin ventas en historial</strong>';
            } else {
                $movStr3b = '<strong>Sin ventas en historial</strong>';
            }

            $detalle = $badgeC3b . $badgeDev3b
                . ' Stock: <strong>' . $stockC3b . '</strong>'
                . ' | ' . $entStr3b
                . ' | ' . $movStr3b;

        // ── C4 ────────────────────────────────────────────────────────────────
        } elseif ($tipo === 'Stock Inactivo en Periodo') {
            $detalle = 'Stock: ' . (isset($f['stock_actual']) ? number_format((float)$f['stock_actual'], 2, '.', '') : '—');

        // ── C7b / C7b_posible ─────────────────────────────────────────────────
        } elseif ($tipo === 'Entrada no registrada') {
            $offsetB    = isset($f['offset_estimado']) ? (float)$f['offset_estimado'] : null;
            $dispB      = isset($f['dispersion'])       ? (float)$f['dispersion']      : null;
            $nRecB      = (int)($f['n_recepciones'] ?? 0);
            $subcasoB   = $f['c7_subcaso'] ?? 'C7b';

            $badgeCruceB  = '';
            $cruceCls     = _posstockCruceCls($f['cruce_nivel'] ?? '');
            $cruceScore   = isset($f['cruce_score']) ? 'score=' . number_format((float)$f['cruce_score'], 2, '.', '') . ' — ' : '';
            $cruceNivel   = _posstockCruceNivel($f['cruce_nivel'] ?? '');

            if (!empty($f['posible_cruce_con'])) {
                $badgeCruceB = _posstockBadgeCruce($f, $cruceCls, $cruceScore, $cruceNivel, false);
            }

            $deficitB = $offsetB !== null ? abs($offsetB) : null;
            $tipoArtB = $f['tipo_articulo'] ?? 'unidad';
            $unidadB  = $tipoArtB === 'peso' ? 'kg' : 'ud.';
            $pctNegB  = isset($f['pct_intervalos_negativos']) ? (int)$f['pct_intervalos_negativos'] : null;

            // Badge principal
            // 'Déficit posible' cuando la evidencia estadística es débil (confianza='posible')
            // o la muestra es insuficiente para test (C7b_posible, n=2).
            $confianzaB = $f['confianza'] ?? 'posible';
            if ($subcasoB === 'C7b_posible') {
                $badgePrincipalB = '<span class="label label-warning" title="Solo 2 recepciones en el periodo: déficit consistente en ambas, pero sin muestra suficiente para confirmarlo estadísticamente. Verificar manualmente.">Déficit posible</span>';
            } elseif ($confianzaB === 'posible') {
                $badgePrincipalB = '<span class="label label-warning" title="El patrón de déficit existe pero la evidencia estadística no es concluyente (t-test sobre distribución no gaussiana o floors con varianza nula). Puede ser real — revisar manualmente.">Déficit posible</span>';
            } else {
                $badgePrincipalB = '<span class="label label-danger" title="El stock cae a valores negativos de forma repetida entre cada recepción. Confirmado estadísticamente.">Déficit estable</span>';
            }

            // Badge nueva aparición: el patrón solo existe en el período de análisis, no en la base anual
            $testPeriodB      = $f['test_period'] ?? null;
            $tendenciaB       = $f['tendencia_reciente'] ?? 'activo';
            $badgeNuevo  = ($testPeriodB === 'analysis')
                ? ' <span class="label label-info" title="Este déficit solo aparece en el período analizado, no en el historial anual base. Incidencia reciente — puede ser un problema nuevo o una primera detección.">Nuevo</span>'
                : '';

            // Badge tendencia histórica (C7b-022): déficit en base pero período reciente no confirma
            $badgeTendencia = '';
            if ($tendenciaB === 'resuelto') {
                $badgeTendencia = ' <span class="label label-default" title="El período reciente no muestra el patrón de déficit — puede haberse resuelto. Verificar que el albarán pendiente fue registrado.">Déficit histórico</span>';
            } elseif ($tendenciaB === 'mejorando') {
                $badgeTendencia = ' <span class="label label-warning" title="El período reciente tiene floors negativos pero sin confirmación estadística — el déficit puede estar reduciéndose. Monitorizar en el próximo informe.">Mejorando</span>';
            } elseif ($tendenciaB === 'sin_datos') {
                $badgeTendencia = ' <span class="label label-default" title="Sin recepciones en el período reciente — no es posible confirmar si el problema sigue activo.">Sin datos recientes</span>';
            }

            // Línea 1: badges
            $detalle = $badgePrincipalB . $badgeNuevo . $badgeTendencia . $badgeCruceB;

            // Línea 2: info principal
            $diasMedB = isset($f['dias_intervalo_medio']) ? (int)$f['dias_intervalo_medio'] : null;
            $detalle .= '<br>'
                . 'Déficit: <strong>~' . ($deficitB !== null ? number_format($deficitB, 1, '.', '') : '—') . ' ' . $unidadB . '</strong>'
                . ($dispB !== null ? ' <span class="text-muted">(±' . number_format($dispB, 1, '.', '') . ')</span>' : '')
                . ' | ' . $nRecB . ' rec.'
                . ' | ' . $fmtF($f['fecha_primera'] ?? null) . '–' . $fmtF($f['fecha_ultima'] ?? null);

            // Línea 3: info complementaria
            $costeB     = isset($f['coste_estimado']) && $f['coste_estimado'] !== null ? (float)$f['coste_estimado'] : null;
            $lineaCompB = '';
            if ($pctNegB !== null) {
                $lineaCompB .= $pctNegB . '% intervalos negativos';
            }
            if ($diasMedB !== null) {
                $lineaCompB .= ($lineaCompB ? ' · ' : '') . 'Intervalo medio: ' . $diasMedB . ' días';
            }
            if ($costeB !== null) {
                $lineaCompB .= ($lineaCompB ? ' · ' : '')
                    . '<span title="Valor estimado del déficit: déficit medio × precio medio de compra">Valor est.: <strong>~'
                    . number_format($costeB, 0, ',', '.') . ' €</strong></span>';
            }
            if (!empty($f['prov_habitual_nombre'])) {
                $lineaCompB .= ($lineaCompB ? ' · ' : '')
                    . '<span title="Proveedor principal: el que más albaranes tiene del artículo en el año en curso">Prov. principal: '
                    . htmlspecialchars($f['prov_habitual_nombre']) . '</span>';
                if (!empty($f['prov_ultimo_nombre'])) {
                    $ultimoLabel = !empty($f['prov_es_mismo']) ? '(mismo)' : htmlspecialchars($f['prov_ultimo_nombre']);
                    $lineaCompB .= ' | <span title="Último proveedor que sirvió el artículo ('
                        . htmlspecialchars($f['prov_ultima_fecha'] ?? '') . ')">Último: '
                        . $ultimoLabel . '</span>';
                }
            }
            if ($lineaCompB) {
                $detalle .= '<br><small class="text-muted">' . $lineaCompB . '</small>';
            }

        // ── C7b_ruido_peso ────────────────────────────────────────────────────
        } elseif ($tipo === 'Posible error de pesaje') {
            $offsetP  = isset($f['offset_estimado']) ? (float)$f['offset_estimado'] : null;
            $nRecP    = (int)($f['n_recepciones'] ?? 0);
            $diasMedP = isset($f['dias_intervalo_medio']) ? (int)$f['dias_intervalo_medio'] : null;
            $deficitP = $offsetP !== null ? abs($offsetP) : null;

            // Línea 1: badge
            $detalle = '<span class="label label-default" title="El stock aparece en negativo, pero el valor es tan pequeño que probablemente es acumulación de errores de pesaje, no una recepción faltante.">Error de pesaje</span>';

            // Línea 2: info principal
            $detalle .= '<br>'
                . 'Stock: <strong>~' . ($deficitP !== null ? number_format($deficitP, 2, ',', '') : '—') . ' kg</strong> en negativo'
                . ' | ' . $nRecP . ' rec.'
                . ' | ' . $fmtF($f['fecha_primera'] ?? null) . '–' . $fmtF($f['fecha_ultima'] ?? null);

            // Línea 3: complementaria
            if ($diasMedP !== null) {
                $detalle .= '<br><small class="text-muted">Intervalo medio: ' . $diasMedP . ' días</small>';
            }

        // ── C7a ───────────────────────────────────────────────────────────────
        } elseif ($tipo === 'Merma acumulada') {
            $deltaA    = isset($f['delta_acumulado']) ? (float)$f['delta_acumulado'] : null;
            $slopeA    = isset($f['tendencia'])        ? (float)$f['tendencia']       : null;
            $dispA     = isset($f['dispersion'])       ? (float)$f['dispersion']      : null;
            $nRecA     = (int)($f['n_recepciones'] ?? 0);
            $unidadA   = ($f['tipo_articulo'] ?? 'unidad') === 'peso' ? 'kg' : 'ud.';
            $subcasoA  = $f['c7_subcaso'] ?? 'C7a';
            $confianzaA = $f['confianza'] ?? null;
            $cascadeNA  = (int)($f['cascade_nivel'] ?? 0);
            $costeA    = isset($f['coste_estimado_merma']) && $f['coste_estimado_merma'] !== null ? (float)$f['coste_estimado_merma'] : null;

            // Línea 1: badges
            $testPeriodA      = $f['test_period'] ?? null;
            $tendenciaRecA    = $f['tendencia_reciente'] ?? 'activo';
            $sevA             = $f['severidad'] ?? 'MEDIA';

            // Badge 1 (principal): confianza estadística — igual que C7b "Déficit posible" / "Déficit estable"
            // Cascade: 1=OLS+NW (alta), 2=Bootstrap TS (media), 3=Mann-Kendall (posible), 4=Sign test (posible)
            $testNombreA = [1 => 'OLS + Newey-West', 2 => 'Bootstrap Theil-Sen', 3 => 'Mann-Kendall', 4 => 'Test de signo'][$cascadeNA] ?? 'desconocido';
            if ($subcasoA === 'C7a_posible' || $confianzaA === 'posible') {
                $badgeTipA = ($subcasoA === 'C7a_posible')
                    ? 'Evidencia estadística débil (' . $testNombreA . ' · nivel ' . $cascadeNA . '). Requiere revisión manual.'
                    : 'El patrón de merma existe pero la evidencia estadística no es concluyente (' . $testNombreA . ' · nivel ' . $cascadeNA . '). Puede ser real — revisar manualmente.';
                $badgePrincipalA = '<span class="label label-warning" title="' . $badgeTipA . '">Merma posible</span>';
            } else {
                $badgePrincipalA = '<span class="label label-danger" title="El suelo mínimo de stock sube recepción a recepción de forma confirmada estadísticamente. Test: ' . $testNombreA . ' · nivel ' . $cascadeNA . '">Merma estable</span>';
            }

            // Badge 2: estado temporal — igual que C7b "Nuevo" / "Déficit histórico" / "Mejorando" / "Sin datos recientes"
            $badgeEstadoA = '';
            if ($testPeriodA === 'analysis') {
                $badgeEstadoA = ' <span class="label label-info" title="Esta tendencia solo aparece en el período analizado, no en el historial anual base. Incidencia reciente — puede ser un problema nuevo o una primera detección.">Nuevo</span>';
            } elseif ($tendenciaRecA === 'resuelto') {
                $badgeEstadoA = ' <span class="label label-default" title="La tendencia histórica no se confirma en el período reciente — la merma puede haberse corregido. Verificar si se realizó ajuste de inventario.">Merma histórica</span>';
            } elseif ($tendenciaRecA === 'mejorando') {
                $badgeEstadoA = ' <span class="label label-warning" title="La tendencia histórica existe pero el período reciente no la confirma estadísticamente — puede estar reduciéndose. Monitorizar en próximo informe.">Mejorando</span>';
            } elseif ($tendenciaRecA === 'sin_datos') {
                $badgeEstadoA = ' <span class="label label-default" title="Sin recepciones en el período de análisis — no es posible confirmar si la merma sigue activa.">Sin datos recientes</span>';
            }

            // Badge advertencia SNR bajo: el ruido supera a la señal — puede ser devoluciones u otros artefactos
            $snrA = isset($f['snr']) ? (float)$f['snr'] : null;
            $badgeSnrA = '';
            if ($snrA !== null && $snrA < 0.15) {
                $badgeSnrA = ' <span class="label label-default" title="La dispersión de los suelos es ' . number_format(1.0 / max($snrA, 0.001), 0) . '× mayor que la pendiente detectada (SNR=' . number_format($snrA, 2) . '). La merma detectada puede ser igualmente real — la alta varianza suele deberse a devoluciones a proveedor intercaladas entre recepciones, múltiples proveedores activos o alta variabilidad intrínseca del producto. Verificar el listado mayor para contextualizar.">Alta varianza</span>';
            }

            $cruceCls   = _posstockCruceCls($f['cruce_nivel'] ?? '');
            $cruceScore = isset($f['cruce_score']) ? 'score=' . number_format((float)$f['cruce_score'], 2, '.', '') . ' — ' : '';
            $cruceNivel = _posstockCruceNivel($f['cruce_nivel'] ?? '');
            $badgeCruceA = '';
            if (!empty($f['posible_cruce_con'])) {
                $badgeCruceA = _posstockBadgeCruce($f, $cruceCls, $cruceScore, $cruceNivel, true);
            }
            $detalle = $badgePrincipalA . $badgeEstadoA . $badgeSnrA . $badgeCruceA;

            // Línea 2: info principal
            $detalle .= '<br>'
                . 'Pérdida acum.: <strong>~' . ($deltaA !== null ? number_format($deltaA, 1, ',', '.') : '—') . ' ' . $unidadA . '</strong>'
                . ($slopeA !== null ? ' · <strong>+' . number_format($slopeA, 1, ',', '.') . ' ' . $unidadA . '/rec.</strong>' : '')
                . ($dispA !== null ? ' <span class="text-muted">(±' . number_format($dispA, 1, ',', '.') . ')</span>' : '')
                . ' · ' . $nRecA . ' rec.'
                . ' · ' . $fmtF($f['fecha_primera'] ?? null) . '–' . $fmtF($f['fecha_ultima'] ?? null);

            // Línea 3: info complementaria
            $lineaCompA = '';
            if ($costeA !== null) {
                $lineaCompA .= '<span title="Valor estimado de la merma acumulada: pérdida total × precio medio de compra en el periodo">Valor merma est.: <strong>~'
                    . number_format($costeA, 0, ',', '.') . ' €</strong></span>';
            }
            if (!empty($f['prov_habitual_nombre'])) {
                $lineaCompA .= ($lineaCompA ? ' · ' : '')
                    . '<span title="Proveedor principal: el que más albaranes tiene del artículo en el año en curso">Prov. principal: '
                    . htmlspecialchars($f['prov_habitual_nombre']) . '</span>';
                if (!empty($f['prov_ultimo_nombre'])) {
                    $ultimoLabelA = !empty($f['prov_es_mismo']) ? '(mismo)' : htmlspecialchars($f['prov_ultimo_nombre']);
                    $lineaCompA .= ' | <span title="Último proveedor que sirvió el artículo ('
                        . htmlspecialchars($f['prov_ultima_fecha'] ?? '') . ')">Último: '
                        . $ultimoLabelA . '</span>';
                }
            }
            // Indicadores compra con stock (C7a-024)
            $rSpearA    = isset($f['r_spearman'])   && $f['r_spearman']   !== null ? (float)$f['r_spearman']   : null;
            $ratioMedA  = isset($f['ratio_mediano']) && $f['ratio_mediano'] !== null ? (float)$f['ratio_mediano'] : null;
            $tieneAlbcliA = !empty($f['tiene_albcli']);
            if ($rSpearA !== null) {
                $rSpearLbl = $rSpearA < -0.3
                    ? '<span class="text-success" title="Correlación Spearman entre stock previo y cantidad recibida: ' . number_format($rSpearA, 2, ',', '') . '. El comprador reduce los pedidos cuando hay más stock — comportamiento de reposición adaptativo.">r=' . number_format($rSpearA, 2, ',', '') . ' ↓ ajusta</span>'
                    : '<span class="text-muted" title="Correlación Spearman entre stock previo y cantidad recibida: ' . number_format($rSpearA, 2, ',', '') . '. Sin ajuste claro del pedido al nivel de stock.">r=' . number_format($rSpearA, 2, ',', '') . '</span>';
                $lineaCompA .= ($lineaCompA ? ' · ' : '') . $rSpearLbl;
            }
            if ($ratioMedA !== null) {
                $ratioLbl = $ratioMedA > 0.3
                    ? '<span class="text-warning" title="Ratio mediano stock-previo/cantidad-recibida: ' . number_format($ratioMedA, 2, ',', '') . '. El stock antes de cada recepción es alto en relación al pedido — posible compra anticipada.">ratio=' . number_format($ratioMedA, 2, ',', '') . ' ⚠</span>'
                    : '<span class="text-muted" title="Ratio mediano stock-previo/cantidad-recibida: ' . number_format($ratioMedA, 2, ',', '') . '.">ratio=' . number_format($ratioMedA, 2, ',', '') . '</span>';
                $lineaCompA .= ($lineaCompA ? ' · ' : '') . $ratioLbl;
            }
            if ($tieneAlbcliA) {
                $lineaCompA .= ($lineaCompA ? ' · ' : '')
                    . '<span class="text-info" title="El artículo tiene albaranes de cliente en el período. Verificar consumo interno o albaranes pendientes de marcar.">Albcli activo</span>';
            }
            if ($lineaCompA) {
                $detalle .= '<br><small class="text-muted">' . $lineaCompA . '</small>';
            }

        // ── C9 ────────────────────────────────────────────────────────────────
        } elseif ($tipo === 'Merma backstaging') {
            $mermaKg      = isset($f['merma_total_kg'])     ? (float)$f['merma_total_kg']     : null;
            $mermaCarryKg = isset($f['merma_carryover_kg']) ? (float)$f['merma_carryover_kg']  : 0.0;
            $nInciertos9  = (int)($f['n_lotes_inciertos'] ?? ($mermaCarryKg > 0.001 ? 1 : 0));
            $mermaDeclKg  = isset($f['merma_declarada_kg']) ? (float)$f['merma_declarada_kg']  : null;
            $pctMerma     = isset($f['pct_merma'])          ? (float)$f['pct_merma']           : null;
            $nLotes       = (int)($f['n_lotes']         ?? 0);
            $nDeficit     = (int)($f['n_lotes_deficit'] ?? 0);
            $nMerma       = (int)($f['n_lotes_merma']   ?? 0);
            $nRec         = (int)($f['n_recepciones']   ?? 0);
            $confianza9   = $f['confianza']    ?? 'posible';
            $consOk9      = !empty($f['conservation_ok']);
            $consDelta9   = isset($f['conservation_delta']) ? (float)$f['conservation_delta'] : null;
            $tipoArt9     = $f['tipo_articulo'] ?? 'peso';
            $unidad9      = $tipoArt9 === 'peso' ? 'kg' : 'ud.';
            $betaUsado    = isset($f['beta_usado']) ? (float)$f['beta_usado'] : null;
            $modo9        = $f['modo'] ?? 'continuo';
            $totalE9         = isset($f['total_E'])           ? (float)$f['total_E']           : null;
            $stockFinal9     = isset($f['stock_final'])       ? (float)$f['stock_final']       : null;
            $deficitBloq9    = isset($f['deficit_bloqueado_kg']) ? (float)$f['deficit_bloqueado_kg'] : 0.0;

            // ── Pre-calcular agregado mensual (necesario para badge pico) ────
            $_mc9     = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            $_mmAgg   = [];
            $_mmCarry = [];
            foreach (($f['merma_por_lote'] ?? []) as $_lot) {
                $_mt       = (float)($_lot['merma_t'] ?? 0.0);
                $_incierto = !empty($_lot['es_lote_incierto']) || !empty($_lot['v_t_parcial']);
                $_mes      = (int)substr($_lot['fecha_ini'] ?? '', 5, 2);
                if ($_mes < 1 || $_mes > 12) continue;
                if ($_incierto && $_mt > 0.001) {
                    $_mmCarry[$_mes] = ($_mmCarry[$_mes] ?? 0.0) + $_mt;
                } elseif (!$_incierto && $_mt > 0.001) {
                    $_mmAgg[$_mes] = ($_mmAgg[$_mes] ?? 0.0) + $_mt;
                }
            }
            ksort($_mmAgg);
            $_nMesesConMerma9 = count($_mmAgg);
            $_mesMaxVal9      = !empty($_mmAgg) ? max($_mmAgg) : 0.0;
            $_mesMaxIdx9      = !empty($_mmAgg) ? array_search($_mesMaxVal9, $_mmAgg) : 0;

            // ── Badge confianza ──────────────────────────────────────────────
            if ($confianza9 === 'alta' && $consOk9) {
                $badgeConf9 = '<span class="label label-danger"'
                    . ' title="Merma estimada con alta confianza. El balance de entradas y salidas cuadra con el stock contable al cierre del periodo.">'
                    . 'Merma confirmada</span>';
            } elseif ($confianza9 === 'media' || $consOk9) {
                $badgeConf9 = '<span class="label label-warning"'
                    . ' title="Merma estimada con confianza media. El patrón es claro pero el balance stock puede tener pequeños desajustes. Revisar si hay albaranes sin registrar.">'
                    . 'Merma probable</span>';
            } else {
                $badgeConf9 = '<span class="label label-default"'
                    . ' title="Señal de merma detectada pero la evidencia es limitada (pocas recepciones o stock contable desajustado). Revisar manualmente.">'
                    . 'Merma posible</span>';
            }

            // ── Badge lotes inciertos ────────────────────────────────────────
            $badgeIncierto9 = '';
            if ($nInciertos9 > 0) {
                $tooltipInc = $nInciertos9 === 1
                    ? 'El último lote del periodo puede no estar cerrado (sin recepción posterior dentro del umbral de continuidad). Su sobrante se muestra entre paréntesis pero NO se suma a la merma confirmada.'
                    : $nInciertos9 . ' lotes finales no han recibido recepción posterior dentro del umbral de continuidad. Sus sobrantes se muestran como horquilla superior, no como merma confirmada.';
                $badgeIncierto9 = ' <span class="label label-info"'
                    . ($nInciertos9 > 1 ? ' data-nofiltro="1"' : '')
                    . ' title="' . htmlspecialchars($tooltipInc) . '">'
                    . ($nInciertos9 === 1 ? 'Lote abierto' : $nInciertos9 . ' lotes inciertos')
                    . '</span>';
            }

            // ── Badge déficit bloqueado (sobreventa: posible albarán faltante) ──
            $badgeDeficitBloq9 = '';
            if ($deficitBloq9 > 0.001) {
                $badgeDeficitBloq9 = ' <span class="label label-warning"'
                    . ' title="Se detectó sobreventa de '
                    . number_format($deficitBloq9, 3, ',', '.') . ' ' . $unidad9
                    . ' que no pudo redistribuirse hacia lotes anteriores.'
                    . ' Esto indica un posible albarán de compra sin registrar o stock heredado de periodos anteriores sin regularizar.'
                    . ' Este déficit NO está incluido en la merma estimada.">'
                    . 'Sobreventa no compensada</span>';
            }

            // ── Badge merma declarada (data-nofiltro: valor único por artículo, no filtrable) ──
            $badgeDecl9 = '';
            if ($mermaDeclKg !== null && $mermaDeclKg > 0.0) {
                $badgeDecl9 = ' <span class="label label-info" data-nofiltro="1"'
                    . ' title="Merma declarada explícitamente en albaranes de regularización o proveedores especiales: '
                    . number_format($mermaDeclKg, 3, ',', '.') . ' ' . $unidad9 . '. Esta cifra está separada de la merma estimada por el modelo.">'
                    . 'Declarada: ' . number_format($mermaDeclKg, 3, ',', '.') . ' ' . $unidad9 . '</span>';
            }

            // ── Badge pico de merma mensual ──────────────────────────────────
            // Se activa cuando un mes concentra ≥25% del total Y supera ≥2,5× la media
            // de los demás meses. Indica posible regularización puntual o cruce sin conciliar.
            $badgePico9 = '';
            if ($_nMesesConMerma9 >= 3 && $mermaKg !== null && $mermaKg > 0.001 && $_mesMaxVal9 / $mermaKg >= 0.25) {
                $_avgOtros9 = ($_mesMaxVal9 < $mermaKg)
                    ? ($mermaKg - $_mesMaxVal9) / ($_nMesesConMerma9 - 1)
                    : 0.0;
                if ($_avgOtros9 > 0.001 && $_mesMaxVal9 / $_avgOtros9 >= 2.5) {
                    $_pctPico9 = number_format($_mesMaxVal9 / $mermaKg * 100, 1, ',', '.');
                    $_nomPico9 = $_mesMaxIdx9 ? $_mc9[$_mesMaxIdx9] : '?';
                    $badgePico9 = ' <span class="label label-warning"'
                        . ' title="Pico en ' . $_nomPico9 . ': concentra el ' . $_pctPico9 . '% de la merma total ('
                        . number_format($_mesMaxVal9, 2, ',', '.') . '&nbsp;' . $unidad9 . '), superando ' . number_format($_mesMaxVal9 / $_avgOtros9, 1, ',', '.') . '× la media del resto de meses ('
                        . number_format($_avgOtros9, 2, ',', '.') . '&nbsp;' . $unidad9 . '). Puede indicar una regularización puntual, un cruce sin conciliar o una pérdida excepcional. Revisar albaranes de ese mes.">'
                        . 'Pico de merma</span>';
                }
            }

            // ── Badge conservación de masa (solo técnico) ────────────────────
            $badgeCons9 = '';
            if ($mostrarTecnico) {
                $badgeCons9 = $consOk9
                    ? ' <span class="label label-success" title="Conservación OK: sum(S_t) coincide con el stock contable al cierre (Δ=' . ($f['conservation_delta'] ?? '?') . '). El modelo explica correctamente la trayectoria.">Conservación OK</span>'
                    : ' <span class="label label-default" title="El modelo no cierra masa exactamente (Δ=' . ($f['conservation_delta'] ?? '?') . '). El stock contable puede tener desajustes históricos no cubiertos por el periodo analizado.">Stock desajustado</span>';
            }

            // ── Línea 1: badges ──────────────────────────────────────────────
            $detalle = $badgeConf9 . $badgeIncierto9 . $badgeDeficitBloq9 . $badgePico9 . $badgeDecl9 . $badgeCons9;

            // ── Línea 2: información básica — merma + horquilla + % ──────────
            if ($mermaKg !== null) {
                $mermaStr9 = '<strong>' . number_format($mermaKg, 3, ',', '.') . ' ' . $unidad9 . '</strong>';
                if ($mermaCarryKg > 0.001) {
                    $mermaMax9  = $mermaKg + $mermaCarryKg;
                    $mermaStr9 .= ' <span class="text-muted"'
                        . ' title="Horquilla superior: si todo el sobrante de los lotes inciertos fuera pérdida real, la merma máxima sería '
                        . number_format($mermaMax9, 3, ',', '.') . ' ' . $unidad9 . '. El valor confirmado es el que aparece antes del paréntesis.">'
                        . '(hasta ' . number_format($mermaMax9, 3, ',', '.') . ' ' . $unidad9 . ')</span>';
                }
            } else {
                $mermaStr9 = '—';
            }
            $linea2_9 = 'Merma estimada: ' . $mermaStr9;
            if ($pctMerma !== null) {
                $linea2_9 .= ' <span class="text-muted">(' . number_format($pctMerma, 1, ',', '.') . '% s/entradas)</span>';
            }
            $detalle .= '<br>' . $linea2_9;

            // ── Línea 3: información adicional ───────────────────────────────
            // Siempre: nº recepciones.
            // Técnico: lotes/déficit/merma + entradas totales + stock cierre + parámetros modelo.
            $linea3_9 = $nRec . ' rec. en periodo';
            if ($mostrarTecnico) {
                $linea3_9 .= ' · ' . $nLotes . ' lotes | ' . $nDeficit . ' c/déficit · ' . $nMerma . ' c/merma';
                if ($totalE9 !== null) {
                    $linea3_9 .= ' | Σ&nbsp;E=' . number_format($totalE9, 3, ',', '.');
                    if ($unidad9 === 'kg') $linea3_9 .= '&nbsp;kg';
                }
                if ($stockFinal9 !== null) {
                    $linea3_9 .= ' · S<sub>f</sub>=' . number_format($stockFinal9, 3, ',', '.');
                    if ($unidad9 === 'kg') $linea3_9 .= '&nbsp;kg';
                }
                if ($betaUsado !== null) {
                    $linea3_9 .= ' | ' . $modo9 . ' β=' . number_format($betaUsado, 2, '.', '');
                    if (isset($f['k_usado']))      $linea3_9 .= ' k=' . (int)$f['k_usado'];
                    if (isset($f['lambda_usado'])) $linea3_9 .= ' λ=' . number_format((float)$f['lambda_usado'], 1, '.', '');
                }
                if ($consDelta9 !== null) {
                    $consDeltaFmt = number_format($consDelta9, 4, '.', '');
                    $consColor    = $consOk9 ? 'color:#3c763d' : 'color:#a94442';
                    $linea3_9 .= ' | <span style="' . $consColor . '" title="Δ conservación de masa: |sum(S_t) − (S_f − S_0)|. Debe ser ≤ ε=' . ($f['c9_epsilon'] ?? '?') . '.">'
                        . 'Δcons=' . $consDeltaFmt . '</span>';
                }
            }
            $detalle .= '<br><small class="text-muted">' . $linea3_9 . '</small>';

            // ── Posible causa: patrón + desglose mensual ─────────────────────
            // $_mc9, $_mmAgg, $_mmCarry ya calculados arriba para el badge pico.

            if (!empty($_mmAgg) || !empty($_mmCarry)) {
                // Patrón: concentrada (1-2 meses) vs continuada (≥3 meses)
                $_nMesesConMerma = $_nMesesConMerma9;
                $_mesMaxVal  = $_mesMaxVal9;
                $_mesMaxIdx  = $_mesMaxIdx9;
                $_causaIntro = '';
                if ($_nMesesConMerma === 0) {
                    $_causaIntro = 'Merma pendiente de confirmar (solo lotes inciertos). ';
                } elseif ($_nMesesConMerma <= 2) {
                    $_nombMes = $_mesMaxIdx ? $_mc9[$_mesMaxIdx] : '';
                    $_causaIntro = 'Pérdida concentrada'
                        . ($_nombMes ? ' en ' . $_nombMes : '')
                        . '. Revisar recepciones y manipulación de ese periodo. ';
                } elseif ($_nMesesConMerma >= 6) {
                    $_causaIntro = 'Pérdida continuada durante el periodo. Posible merma estructural por manipulación o condiciones de almacenamiento. ';
                } else {
                    $_causaIntro = 'Pérdida intermitente en ' . $_nMesesConMerma . ' meses. Revisar patrones de recepción y almacenamiento. ';
                }
                if ($pctMerma !== null && $pctMerma >= 15.0) {
                    $_causaIntro .= 'Tasa alta (' . number_format($pctMerma, 1, ',', '.') . '%). ';
                }

                // Desglose mensual.
                // En modo técnico: añadir % sobre merma_total junto a los kg.
                $_causaParts = [];
                foreach ($_mmAgg as $_mes => $_merma_m) {
                    $_pctMes = ($mermaKg !== null && $mermaKg > 0.001)
                        ? ' <span class="text-muted" title="% sobre merma confirmada total">(' . number_format($_merma_m / $mermaKg * 100, 0) . '%)</span>'
                        : '';
                    $_pctMes = $mostrarTecnico ? $_pctMes : '';
                    if (isset($_mmCarry[$_mes])) {
                        $_causaParts[] = $_mc9[$_mes] . ': ~' . number_format($_merma_m, 2, ',', '.') . '&nbsp;' . $unidad9 . $_pctMes
                            . ' <span class="text-muted">(hasta ' . number_format($_merma_m + $_mmCarry[$_mes], 2, ',', '.') . ')</span>';
                        unset($_mmCarry[$_mes]);
                    } else {
                        $_causaParts[] = $_mc9[$_mes] . ': ~' . number_format($_merma_m, 2, ',', '.') . '&nbsp;' . $unidad9 . $_pctMes;
                    }
                }
                ksort($_mmCarry);
                foreach ($_mmCarry as $_mes => $_carry_m) {
                    $_causaParts[] = $_mc9[$_mes] . ': <span class="text-muted">~(hasta ' . number_format($_carry_m, 2, ',', '.') . '&nbsp;' . $unidad9 . ')</span>';
                }
                $posibleCausaHtml = $_causaIntro . implode(' · ', $_causaParts);

                // En modo técnico: añadir línea con stock ancla + conservación de masa.
                if ($mostrarTecnico) {
                    $_tecLine = [];
                    if ($totalE9 !== null) {
                        $_tecLine[] = 'Σ&nbsp;entradas: ' . number_format($totalE9, 3, ',', '.') . '&nbsp;' . $unidad9;
                    }
                    if ($stockFinal9 !== null) {
                        $_tecLine[] = 'Stock cierre: ' . number_format($stockFinal9, 3, ',', '.') . '&nbsp;' . $unidad9;
                    }
                    if ($consDelta9 !== null) {
                        $_consStr = 'Δcons=' . number_format($consDelta9, 4, '.', '');
                        $_consStyle = $consOk9 ? 'color:#3c763d' : 'color:#a94442';
                        $_tecLine[] = '<span style="' . $_consStyle . '">' . $_consStr . ($consOk9 ? ' ✓' : ' ✗') . '</span>';
                    }
                    if (!empty($_tecLine)) {
                        $posibleCausaHtml .= '<br><small class="text-muted">' . implode(' · ', $_tecLine) . '</small>';
                    }
                }
            }

            // ── Línea complementaria C9: coste estimado + proveedor ──────────
            $coste9    = isset($f['coste_estimado_merma']) && $f['coste_estimado_merma'] !== null
                ? (float)$f['coste_estimado_merma'] : null;
            $lineaComp9 = '';
            if ($coste9 !== null) {
                $lineaComp9 .= '<span title="Valor estimado de la merma: merma total × precio medio de compra en el periodo">Valor merma est.: <strong>~'
                    . number_format($coste9, 0, ',', '.') . ' €</strong></span>';
            }
            if (!empty($f['prov_habitual_nombre'])) {
                $lineaComp9 .= ($lineaComp9 ? ' · ' : '')
                    . '<span title="Proveedor principal: el que más albaranes tiene del artículo en el año en curso">Prov. principal: '
                    . htmlspecialchars($f['prov_habitual_nombre']) . '</span>';
                if (!empty($f['prov_ultimo_nombre'])) {
                    $ultimoLabel9 = !empty($f['prov_es_mismo']) ? '(mismo)' : htmlspecialchars($f['prov_ultimo_nombre']);
                    $lineaComp9 .= ' | <span title="Último proveedor que sirvió el artículo ('
                        . htmlspecialchars($f['prov_ultima_fecha'] ?? '') . ')">Último: '
                        . $ultimoLabel9 . '</span>';
                }
            }
            if ($lineaComp9) {
                $detalle .= '<br><small class="text-muted">' . $lineaComp9 . '</small>';
            }

        // ── C6a / C6b ─────────────────────────────────────────────────────────
        } elseif ($tipo === 'Agotamiento Estimado' || $tipo === 'Punto de Pedido') {
            $badgeStockNoFiable = !empty($f['stock_no_fiable'])
                ? ' <span class="label label-warning" title="Este artículo tiene stock negativo activo (C1a). Los datos de stock usados en este análisis pueden no ser fiables.">Stock no fiable</span>'
                : '';
            $stockC6     = (float)($f['stock_actual'] ?? 0);
            $recNegativo = !empty($f['stock_reconstituido']) && $stockC6 < 0;
            $ropC6       = (float)($f['rop'] ?? 0);
            // Para el cálculo del pedido, el déficit se acota a -ROP como máximo.
            // Un stock más negativo que -ROP es casi siempre un error de stockOn
            // (sin entradas registradas, pesajes incorrectos…) y no debe inflar el pedido.
            // Si fue reconstruido y sigue negativo se usa 0 (déficit irrecuperable).
            $stockCalc = $recNegativo ? 0.0 : max($stockC6, -$ropC6);
            $dC6         = (float)($f['d_diaria'] ?? 0);
            $esPeso      = ($f['tipo_articulo'] ?? '') === 'peso';
            $unidad      = $esPeso ? 'kg' : 'ud.';

            $c6mCfg = [
                'Normal'   => ['lbl' => 'N',   'cls' => 'label-success', 'comp' => 'Rotación regular',        'tip' => 'Normal — demanda estable y regular (gran consumo). ROP calculado con varianza empírica de la demanda.',    'qTip' => 'Llevar stock hasta el ROP (demanda predecible).'],
                'Gamma'    => ['lbl' => 'Γ',   'cls' => 'label-warning', 'comp' => 'Demanda asimétrica',      'tip' => 'Gamma — demanda asimétrica o lead time variable. ROP = cuantil Gamma directo sobre la demanda en L.',     'qTip' => 'Demanda asimétrica: cuantil Gamma ya incluye el SS necesario.'],
                'BN'       => ['lbl' => 'BN',  'cls' => 'label-info',    'comp' => 'Compras en rachas',       'tip' => 'Binomial Negativa — demanda con sobredispersión (ventas agrupadas en lotes o rachas). SS ampliado.',      'qTip' => 'Demanda en rachas: se añade SS extra sobre el ROP por mayor incertidumbre.'],
                'Binomial' => ['lbl' => 'Bin', 'cls' => 'label-primary', 'comp' => 'Rotación acotada',        'tip' => 'Binomial compuesta — distribución acotada (configurada por el usuario). SS según multiplicador σ.',       'qTip' => 'Binomial: llevar stock hasta el ROP con SS por multiplicador σ.'],
                'Poisson'  => ['lbl' => 'Poi', 'cls' => 'label-default', 'comp' => 'Artículo esporádico',     'tip' => 'Poisson — demanda baja o poco frecuente. ROP conservador para artículos de baja rotación.',             'qTip' => 'Poisson: llevar stock hasta el ROP (varianza ≈ media).'],
            ];
            $c6m = $c6mCfg[$f['modelo_usado'] ?? ''] ?? $c6mCfg['Poisson'];

            $qBase       = max(0.0, $ropC6 - $stockCalc);
            $qRecomendada = (int)ceil($qBase);
            $diasTrasPedido = $dC6 > 0 ? (int)round(($stockCalc + $qRecomendada) / $dC6) : null;

            $badgeC6Modelo = ' <span class="label ' . $c6m['cls'] . '" title="' . htmlspecialchars($c6m['tip']) . '">' . $c6m['lbl'] . '</span>';

            $badgeC6Fuente = '';
            if ($tipo === 'Punto de Pedido') {
                $aviso = $c6bDias < 60 ? ' ⚠ ventana corta — puede no reflejar estacionalidad' : '';
                $badgeC6Fuente = ' <span class="label label-default" title="ROP calculado sobre los últimos ' . $c6bDias . ' días desde hoy (C6b)' . $aviso . '">' . $c6bDias . 'd</span>';
            }

            $badgeC6LT = (($f['lead_time_fuente'] ?? '') === 'proveedor')
                ? ' <span class="label label-success" title="Lead time calculado desde intervalo entre albaranes del proveedor">LT prov.</span>'
                : '';

            $recMotivo = $f['stock_rec_motivo'] ?? '';
            $recTipBase = ($recMotivo === 'negativo')
                ? 'stockOn muy negativo (&lt; -2) — estimado desde última entrada de proveedor menos ventas posteriores'
                : 'stockOn sospechosamente alto (&gt; N×ROP) — estimado desde última entrada de proveedor menos ventas posteriores';
            $recTip = $recNegativo
                ? $recTipBase . '. Stock estimado negativo: se asume stock=0 para el cálculo del pedido (posible cruce de albarán o entrada sin registrar)'
                : $recTipBase;

            $badgeC6Rec = !empty($f['stock_reconstituido'])
                ? ' <span class="label ' . ($recNegativo ? 'label-danger' : 'label-warning') . '" title="' . htmlspecialchars($recTip) . '">~stk</span>'
                : '';

            $badgeC6Q = $qRecomendada > 0
                ? ' <span class="label label-warning" title="' . htmlspecialchars($c6m['qTip']) . '">Pedir ~' . ($esPeso ? number_format($qRecomendada, 2, '.', '') : $qRecomendada) . ' ' . $unidad . '</span>'
                : ' <span class="label label-success">Stock OK</span>';

            $stockLabel = !empty($f['stock_reconstituido'])
                ? ' | <span title="' . htmlspecialchars($recTip) . '">Stock ~est: <strong>' . number_format($stockC6, 2, '.', '') . ' ' . $unidad . '</strong>'
                    . ($recNegativo ? ' <em class="text-muted">(pedido calc. desde 0)</em>' : '') . '</span>'
                : ' | Stock: <strong>' . number_format($stockC6, 2, '.', '') . ' ' . $unidad . '</strong>';

            $detalle = $badgeStockNoFiable . $badgeC6Modelo . $badgeC6Fuente . $badgeC6LT . $badgeC6Rec . $badgeC6Q
                . ' <small class="text-muted">· ' . htmlspecialchars($c6m['comp']) . '</small>'
                . $stockLabel
                . ' | Autonomía: <strong>' . (isset($f['dias_autonomia']) ? $f['dias_autonomia'] . ' d' : '—') . '</strong>'
                . ' | LT: ' . (isset($f['lead_time_dias']) ? $f['lead_time_dias'] . ' d' : '—')
                . ' | ROP: ' . number_format($ropC6, 2, '.', '') . ' ' . $unidad
                . ($diasTrasPedido !== null ? ' | Cobertura tras pedido: <strong>' . $diasTrasPedido . ' d</strong>' : '');
        }

        // ── tipoLabel ──────────────────────────────────────────────────────────
        switch ($tipo) {
            case 'Inventario en negativo':
                $tipoLabel = 'Stock negativo'; break;
            case 'Desajuste Puntual de Stock':
                $tipoLabel = 'Descuadre temporal'; break;
            case 'Caída de rotación':
                $avgCadTipo = (float)($f['avg_cadencia_dias'] ?? 0);
                $tipoLabel = ($avgCadTipo > 0 && $avgCadTipo <= 7 && empty($f['desde_reposicion']))
                    ? 'Riesgo caducidad' : 'Rotación caída';
                break;
            case 'Entrada con stock alto':
                $c2CatTL = $f['c2_categoria'] ?? '';
                if ($c2CatTL === 'acumulacion') {
                    $tipoLabel = 'Acumulación crónica';
                } elseif ($c2CatTL === 'tendencia') {
                    $tipoLabel = 'Pedidos excesivos';
                } else {
                    $_ncant   = (float)($f['ncant'] ?? 0);
                    $_ncantA  = isset($f['ncant_anterior']) && $f['ncant_anterior'] !== null ? (float)$f['ncant_anterior'] : null;
                    $_dias    = isset($f['dias_desde_anterior']) && $f['dias_desde_anterior'] !== null ? (int)$f['dias_desde_anterior'] : null;
                    $_dupP    = $_dias !== null && $_dias <= 1 && $_ncantA !== null && (max($_ncant, $_ncantA) > 0) && (abs($_ncant - $_ncantA) / max($_ncant, $_ncantA)) < 0.15;
                    $_dupPos  = !$_dupP && $_dias !== null && $_dias <= 3 && $_ncantA !== null && (max($_ncant, $_ncantA) > 0) && (abs($_ncant - $_ncantA) / max($_ncant, $_ncantA)) < 0.15;
                    $tipoLabel = $_dupP ? 'Duplicado probable' : ($_dupPos ? 'Posible duplicado' : 'Sobrestock entrada');
                }
                break;
            case 'Venta Cero (Posible Rotura Física)':
                $tipoLabel = 'Rotura de stock'; break;
            case 'Entrada sin rotación previa':
                $tipoLabel = 'Pedido sin rotación'; break;
            case 'Agotamiento Estimado':
            case 'Punto de Pedido':
                $_ropTL   = (float)($f['rop'] ?? 0);
                $_stTLRaw = (float)($f['stock_actual'] ?? 0);
                $_stTL    = (!empty($f['stock_reconstituido']) && $_stTLRaw < 0) ? 0.0 : $_stTLRaw;
                $_ssTL    = (float)($f['stock_seguridad'] ?? 0);
                $_qTL     = (int)ceil((($f['modelo_usado'] ?? '') === 'BN')
                    ? max(0, $_ropTL - $_stTL) + $_ssTL
                    : max(0, $_ropTL - $_stTL));
                $tipoLabel = $_qTL > 0 ? 'Reponer ahora' : 'ROP alcanzado';
                break;
            default:
                $tipoLabel = $tipo;
        }

        // ── URL Listado Mayor ──────────────────────────────────────────────────
        $fiMayor = $fiInicio;
        $ffMayor = $ffMov;
        $needsPostWindow =
            ($tipo === 'Entrada sin rotación previa' && empty($f['ultima_salida'])) ||
            ($tipo === 'Venta Cero (Posible Rotura Física)' && empty($f['fecha_fin_rotura']));

        if ($needsPostWindow && $ffMov) {
            $fiMayor = $fiStock ?: $fiInicio;
            $dtFin   = new DateTime($ffMov);
            $dtFin->modify('+' . $diasPost . ' days');
            $ffMayor = $dtFin->format('Y-m-d');
        }

        $urlMayor = '../../modulos/mod_producto/DetalleMayor.php'
            . '?idArticulo=' . (int)($f['idArticulo'] ?? 0)
            . '&fecha_inicial=' . urlencode($fiMayor)
            . '&fecha_final='   . urlencode($ffMayor);

        // ── Fila HTML ─────────────────────────────────────────────────────────
        $sevBadge = $badgeSev[$f['severidad'] ?? ''] ?? htmlspecialchars($f['severidad'] ?? '');

        $badgeNombrePrincipal = ($tipo === 'Punto de Pedido' && !empty($f['proveedor_es_principal']))
            ? ' <span class="label label-success" title="Este proveedor es el proveedor principal (Activo) de este artículo">principal</span>'
            : '';

        // Extraer textos de badges del detalle para el atributo data-badges (filtro JS).
        // Se excluyen los spans con data-nofiltro="1" (valores únicos por artículo como "Declarada: X kg").
        preg_match_all('/<span\s[^>]*class="label[^"]*"[^>]*>([^<]+)<\/span>/u', $detalle, $_bm_all);
        preg_match_all('/<span\s[^>]*data-nofiltro="1"[^>]*>([^<]+)<\/span>/u', $detalle, $_bm_excl);
        $dataBadges = implode('|', array_unique(array_diff(
            array_map('trim', $_bm_all[1] ?? []),
            array_map('trim', $_bm_excl[1] ?? [])
        )));

        // posible_causa: si el tipo genera HTML enriquecido, $posibleCausaHtml ya está asignado;
        // en caso contrario se escapa el texto plano de $f['posible_causa'].
        if ($posibleCausaHtml === null) {
            $posibleCausaHtml = htmlspecialchars($f['posible_causa'] ?? '');
        }

        $ordenClave  = htmlspecialchars($f['orden_clave'] ?? '');
        $provNombre  = htmlspecialchars($f['prov_habitual_nombre'] ?? '');
        $coste_any   = $f['coste_estimado'] ?? $f['coste_estimado_merma'] ?? null;
        $costeData   = $coste_any !== null ? (float)$coste_any : 0;
        $html .= '<tr data-tipo="' . htmlspecialchars($tipo) . '" data-badges="' . htmlspecialchars($dataBadges) . '"'
            . ' data-orden="' . $ordenClave . '" data-prov="' . $provNombre . '" data-coste="' . $costeData . '"'
            . ' data-idarticulo="' . (int)($f['idArticulo'] ?? 0) . '">'
            . '<td>' . (int)($f['idArticulo'] ?? 0) . '</td>'
            . '<td>' . htmlspecialchars($f['nombre'] ?? '—') . $badgeNombrePrincipal . '</td>'
            . '<td>' . htmlspecialchars($tipoLabel) . '</td>'
            . '<td>' . $sevBadge . '</td>'
            . '<td>' . $detalle . '</td>'
            . '<td>' . $posibleCausaHtml . '</td>'
            . '<td><a href="' . $urlMayor . '" target="_blank">'
            . '<i class="glyphicon glyphicon-list-alt"></i> Ver mayor'
            . '</a></td>'
            . '</tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}

// ── Helpers privados de cruce ─────────────────────────────────────────────────

function _posstockCruceCls(string $nivel): string
{
    if ($nivel === 'confirmado') return 'label-success';
    if ($nivel === 'probable')   return 'label-danger';
    return 'label-warning';
}

function _posstockCruceNivel(string $nivel): string
{
    if ($nivel === 'confirmado') return 'cruce confirmado';
    if ($nivel === 'probable')   return 'cruce probable';
    return 'posible cruce';
}

/**
 * Genera el badge de cruce para C7a / C7b.
 * @param bool $esDest  true = artículo destino (C7a), false = fuente (C7b)
 */
function _posstockBadgeCruce(array $f, string $cls, string $score, string $nivel, bool $esDest): string
{
    $con = $f['posible_cruce_con'] ?? '';
    $tipo = $f['cruce_tipo'] ?? '';
    $fuenteB = $f['cruce_fuente_b'] ?? '';
    $ratioK  = $f['cruce_ratio_k']  ?? '';

    if ($tipo === 'trio' && $fuenteB) {
        if ($esDest) {
            $tip   = $score . $nivel . ': art. ' . $con . ' y art. ' . $fuenteB . ' escaneados como este producto';
            $label = 'Trío art. ' . $con . '+' . $fuenteB;
        } else {
            $tip   = $score . $nivel . ': este art. y art. ' . $fuenteB . ' escaneados como art. ' . $con;
            $label = 'Trío →art. ' . $con;
        }
    } elseif ($tipo === 'multiplo' && $ratioK) {
        $tip   = $score . $nivel . ' con art. ' . $con . ' (ratio ×' . $ratioK . '; pérdida fantasma por múltiplo)';
        $label = '×' . $ratioK . ' art. ' . $con;
    } else {
        $tip   = $score . $nivel . ' con art. ' . $con . ' — posible error';
        $label = ($esDest ? 'Balanza: art. ' : 'Art. ') . $con;
    }

    return ' <span class="label ' . $cls . '" title="' . htmlspecialchars($tip) . '">' . htmlspecialchars($label) . '</span>';
}
