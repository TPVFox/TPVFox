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
    $c6bDias        = (int)($cfg['c6b_dias_historico'] ?? 90);
    $mostrarTecnico = !empty($cfg['mostrar_tecnico']);

    $fiInicio = $anio . '-01-01';
    // "Ver mayor" siempre abarca el año completo:
    // - Año en curso → 1 enero … hoy
    // - Año cerrado  → 1 enero … 31 diciembre
    $ffAnioMayor = ($anio < (int)date('Y')) ? $anio . '-12-31' : date('Y-m-d');

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
            $costeC1 = isset($f['coste_estimado']) && $f['coste_estimado'] !== null ? (float)$f['coste_estimado'] : null;
            if ($costeC1 !== null) {
                $linea3 .= ($linea3 ? ' | ' : '')
                    . '<span title="Valor estimado del stock en descubierto: déficit × precio medio de compra">Coste est.: <strong>~'
                    . number_format($costeC1, 0, ',', '.') . ' €</strong></span>';
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
                $badges1b .= ' <span class="label label-info" title="Entrada de proveedor registrada poco después del momento del negativo: probable venta registrada antes que la recepción.">Timing recepción</span>';
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
            $costeC1b = isset($f['coste_estimado']) && $f['coste_estimado'] !== null ? (float)$f['coste_estimado'] : null;
            if ($costeC1b !== null) {
                $linea3b .= ($linea3b ? ' | ' : '')
                    . '<span title="Valor estimado del stock en descubierto: déficit × precio medio de compra">Coste est.: <strong>~'
                    . number_format($costeC1b, 0, ',', '.') . ' €</strong></span>';
            }
            if (!empty($f['prov_habitual_nombre'])) {
                if (!empty($f['prov_es_mismo'])) {
                    $linea3b .= ($linea3b ? ' | ' : '')
                        . '<span title="Proveedor con más compras del artículo en el año en curso">Prov: '
                        . htmlspecialchars($f['prov_habitual_nombre']) . '</span>';
                } else {
                    $linea3b .= ($linea3b ? ' | ' : '')
                        . '<span title="Proveedor con más compras del artículo en el año en curso">Prov. habitual: '
                        . htmlspecialchars($f['prov_habitual_nombre']) . '</span>';
                    if (!empty($f['prov_ultimo_nombre'])) {
                        $linea3b .= ' | <span title="Proveedor del último albarán recibido ('
                            . htmlspecialchars($f['prov_ultima_fecha'] ?? '') . ')">Último: '
                            . htmlspecialchars($f['prov_ultimo_nombre']) . '</span>';
                    }
                }
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
                $nEvt    = (int)($f['n_eventos'] ?? 1);
                $cobIni  = isset($f['cobertura_inicio']) && $f['cobertura_inicio'] !== null ? (int)$f['cobertura_inicio'] . ' días' : '—';
                $cobFin  = $cob !== null ? $cob . ' días' : 'sin ventas';
                $fInicio = $f['fecha_inicio'] ?? '—';
                $fFin    = $f['fecha'] ?? '—';
                $stockMax = (float)($f['stock_previo'] ?? 0);
                $badgeC2 = '<span class="label label-warning" title="La cobertura crece de ' . $cobIni . ' a ' . $cobFin
                    . ' en ' . $nEvt . ' entregas: las compras superan el ritmo de ventas de forma sistemática.">Tendencia creciente</span>';

                // L1: badges
                $detalle = $badgeC2;
                // L2: dato operativo principal
                $detalle .= '<br><strong>' . $nEvt . ' entregas</strong>'
                    . ' | Cobertura: ' . $cobIni . ' → <strong>' . $cobFin . '</strong>';
                // L3: contexto complementario
                $linea3C2t = 'Periodo: ' . $fInicio . ' → ' . $fFin
                    . ' | Stock máx.: ' . number_format($stockMax, 2, '.', '');
                $costeC2t = isset($f['coste_estimado']) && $f['coste_estimado'] !== null ? (float)$f['coste_estimado'] : null;
                if ($costeC2t !== null) {
                    $linea3C2t .= ' | <span title="Valor estimado del exceso de stock acumulado: stock máx. × precio medio de compra">Coste est.: <strong>~'
                        . number_format($costeC2t, 0, ',', '.') . ' €</strong></span>';
                }
                if (!empty($f['prov_habitual_nombre'])) {
                    $linea3C2t .= ' | <span title="Proveedor principal del artículo en el año en curso">Prov.: '
                        . htmlspecialchars($f['prov_habitual_nombre']) . '</span>';
                }
                $detalle .= '<br><small class="text-muted">' . $linea3C2t . '</small>';
            } elseif ($c2Cat === 'acumulacion') {
                $nEvt    = (int)($f['n_eventos'] ?? 1);
                $cobFin  = $cob !== null ? $cob . ' días' : 'sin ventas';
                $fInicio = $f['fecha_inicio'] ?? '—';
                $fFin    = $f['fecha'] ?? '—';
                $stockMax = (float)($f['stock_previo'] ?? 0);
                $badgeC2 = '<span class="label label-danger" title="' . $nEvt
                    . ' recepciones consecutivas sin retorno: el stock se acumula sin salida. Revisar gestión de devoluciones.">Acumulación crónica</span>';

                // L1: badges
                $detalle = $badgeC2;
                // L2: dato operativo principal
                $detalle .= '<br><strong>' . $nEvt . ' recepciones</strong> sin salida'
                    . ' | Cobertura final: <strong>' . $cobFin . '</strong>';
                // L3: contexto complementario
                $linea3C2a = 'Periodo: ' . $fInicio . ' → ' . $fFin
                    . ' | Stock máx.: ' . number_format($stockMax, 2, '.', '');
                $costeC2a = isset($f['coste_estimado']) && $f['coste_estimado'] !== null ? (float)$f['coste_estimado'] : null;
                if ($costeC2a !== null) {
                    $linea3C2a .= ' | <span title="Valor estimado del stock acumulado sin salida: stock máx. × precio medio de compra">Coste est.: <strong>~'
                        . number_format($costeC2a, 0, ',', '.') . ' €</strong></span>';
                }
                if (!empty($f['prov_habitual_nombre'])) {
                    $linea3C2a .= ' | <span title="Proveedor principal del artículo en el año en curso">Prov.: '
                        . htmlspecialchars($f['prov_habitual_nombre']) . '</span>';
                }
                $detalle .= '<br><small class="text-muted">' . $linea3C2a . '</small>';
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

                // L1: badges
                $detalle = $badgeC2;
                // L2: dato operativo principal
                $detalle .= '<br>Entrada: <strong>' . number_format($ncant, 2, '.', '') . ' ud.'
                    . ' (' . $ratio . ' sobre previo)</strong>'
                    . ' | Cobertura: <strong>' . $cobStr . '</strong>';
                // L3: contexto complementario
                $linea3C2i = 'Previo: ' . number_format($previo, 2, '.', '');
                if ($dias !== null) {
                    $linea3C2i .= ' | Anter.: ' . $dias . ' días';
                }
                $linea3C2i .= ' | Fecha: ' . ($f['fecha'] ?? '—');
                $costeC2i = isset($f['coste_estimado']) && $f['coste_estimado'] !== null ? (float)$f['coste_estimado'] : null;
                if ($costeC2i !== null) {
                    $linea3C2i .= ' | <span title="Valor estimado del exceso de stock: stock previo × precio medio de compra">Coste est.: <strong>~'
                        . number_format($costeC2i, 0, ',', '.') . ' €</strong></span>';
                }
                if (!empty($f['prov_habitual_nombre'])) {
                    $provC2iLabel = !empty($f['prov_es_mismo']) ? 'Prov.' : 'Prov. habitual';
                    $linea3C2i .= ' | <span title="Proveedor principal del artículo en el año en curso">' . $provC2iLabel . ': '
                        . htmlspecialchars($f['prov_habitual_nombre']) . '</span>';
                    if (!empty($f['prov_ultimo_nombre']) && empty($f['prov_es_mismo'])) {
                        $linea3C2i .= ' | <span title="Proveedor del último albarán recibido ('
                            . htmlspecialchars($f['prov_ultima_fecha'] ?? '') . ')">Último: '
                            . htmlspecialchars($f['prov_ultimo_nombre']) . '</span>';
                    }
                }
                $detalle .= '<br><small class="text-muted">' . $linea3C2i . '</small>';
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

            // L1: badges
            $detalle = $badgeC3a;
            // L2: dato operativo principal
            $detalle .= '<br>' . $sinVentaStr
                . ' | Stock: <strong>' . $stkStr . '</strong>';
            // L3: contexto complementario
            $linea3C3a = $refFechaStr;
            $costeC3a = isset($f['coste_estimado']) && $f['coste_estimado'] !== null ? (float)$f['coste_estimado'] : null;
            if ($costeC3a !== null) {
                $linea3C3a .= ' | <span title="Valor estimado del stock en riesgo de caducidad o deterioro: stock × precio medio de compra">Coste est.: <strong>~'
                    . number_format($costeC3a, 0, ',', '.') . ' €</strong></span>';
            }
            if (!empty($f['prov_habitual_nombre'])) {
                $linea3C3a .= ' | <span title="Proveedor principal del artículo en el año en curso">Prov.: '
                    . htmlspecialchars($f['prov_habitual_nombre']) . '</span>';
                if (!empty($f['prov_ultimo_nombre']) && empty($f['prov_es_mismo'])) {
                    $linea3C3a .= ' | <span title="Proveedor del último albarán recibido ('
                        . htmlspecialchars($f['prov_ultima_fecha'] ?? '') . ')">Último: '
                        . htmlspecialchars($f['prov_ultimo_nombre']) . '</span>';
                }
            }
            $detalle .= '<br><small class="text-muted">' . $linea3C3a . '</small>';

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
                'BN'       => ['lbl' => 'BN', 'cls' => 'label-info',     'comp' => 'Demanda en rachas o lotes',               'tip' => 'Binomial Negativa — sobredispersión detectada (ventas agrupadas por periodos). Umbral ajustado a la variabilidad extra.'],
                'Poisson'  => $avgC5 > 0 && $avgC5 < 4
                    ? ['lbl' => 'Poi', 'cls' => 'label-primary', 'comp' => 'Alta rotación, gaps ~ exponencial',   'tip' => 'Poisson — alta rotación con dispersión normal. Umbral: gap > −ln(p)/λ.']
                    : ['lbl' => 'Poi', 'cls' => 'label-default', 'comp' => 'Demanda esporádica, baja frecuencia', 'tip' => 'Poisson — artículo de venta poco frecuente. Umbral conservador para eventos raros.'],
            ];
            $modelo   = $f['modelo_usado'] ?? 'Poisson';
            $c5m      = $c5mCfg[$modelo] ?? $c5mCfg['Poisson'];
            $badgeMod = ' <span class="label ' . $c5m['cls'] . '" title="' . htmlspecialchars($c5m['tip']) . '">' . $c5m['lbl'] . '</span>';
            $sdStr    = (isset($f['sd_dias']) && $f['sd_dias'] !== null) ? ' σ=' . $f['sd_dias'] . ' d' : '';

            // L1: badges de diagnóstico
            $detalle = $badgeStockNoFiable . $badgeKO . $badgeCR . $badgeRK . ' ' . $badgeEstado;
            if ($mostrarTecnico) {
                $detalle .= ' ' . $badgeMod;
            }
            // L2: dato operativo principal
            $detalle .= '<br>' . $diasRoturaStr
                . ' | Desde: ' . $fmtF($f['fecha_inicio_rotura'] ?? null)
                . ' | Últ. venta: ' . $fmtF($f['ultima_venta'] ?? null);
            // L3: contexto complementario
            $linea3C5 = 'Cadencia: ' . (isset($f['avg_dias_entre_ventas']) ? $f['avg_dias_entre_ventas'] . ' d' : '—')
                . ' (umbral ' . (isset($f['umbral_dias']) ? $f['umbral_dias'] . ' d' : '—') . ')';
            if ($mostrarTecnico) {
                $linea3C5 .= $sdStr . ' · ' . htmlspecialchars($c5m['comp']);
            }
            $costeC5 = isset($f['coste_estimado']) && $f['coste_estimado'] !== null ? (float)$f['coste_estimado'] : null;
            if ($costeC5 !== null) {
                $linea3C5 .= ' | <span title="Valor estimado de la rotura: ventas perdidas estimadas × precio medio de venta">Coste est.: <strong>~'
                    . number_format($costeC5, 0, ',', '.') . ' €</strong></span>';
            }
            if (!empty($f['prov_habitual_nombre'])) {
                $linea3C5 .= ' | <span title="Proveedor principal del artículo en el año en curso">Prov.: '
                    . htmlspecialchars($f['prov_habitual_nombre']) . '</span>';
                if (!empty($f['prov_ultimo_nombre']) && empty($f['prov_es_mismo'])) {
                    $linea3C5 .= ' | <span title="Proveedor del último albarán recibido ('
                        . htmlspecialchars($f['prov_ultima_fecha'] ?? '') . ')">Último: '
                        . htmlspecialchars($f['prov_ultimo_nombre']) . '</span>';
                }
            }
            $detalle .= '<br><small class="text-muted">' . $linea3C5 . '</small>';

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

            // L1: badges
            $detalle = $badgeC3b . $badgeDev3b;
            // L2: dato operativo principal
            $detalle .= '<br>Stock: <strong>' . $stockC3b . '</strong>'
                . ' | ' . $entStr3b;
            // L3: contexto complementario
            $linea3C3b = $movStr3b;
            $costeC3b = isset($f['coste_estimado']) && $f['coste_estimado'] !== null ? (float)$f['coste_estimado'] : null;
            if ($costeC3b !== null) {
                $linea3C3b .= ' | <span title="Valor estimado del stock sin rotación: stock × precio medio de compra">Coste est.: <strong>~'
                    . number_format($costeC3b, 0, ',', '.') . ' €</strong></span>';
            }
            if (!empty($f['prov_habitual_nombre'])) {
                $linea3C3b .= ' | <span title="Proveedor principal del artículo en el año en curso">Prov.: '
                    . htmlspecialchars($f['prov_habitual_nombre']) . '</span>';
                if (!empty($f['prov_ultimo_nombre']) && empty($f['prov_es_mismo'])) {
                    $linea3C3b .= ' | <span title="Proveedor del último albarán recibido ('
                        . htmlspecialchars($f['prov_ultima_fecha'] ?? '') . ')">Último: '
                        . htmlspecialchars($f['prov_ultimo_nombre']) . '</span>';
                }
            }
            $detalle .= '<br><small class="text-muted">' . $linea3C3b . '</small>';

            // ── C4 ────────────────────────────────────────────────────────────────
        } elseif ($tipo === 'Stock Inactivo en Periodo') {
            // L1: badge indicativo
            $detalle = '<span class="label label-default" title="El artículo tiene stock pero no registró ningún movimiento de entrada ni salida en el periodo analizado.">Sin movimiento</span>';
            // L2: dato operativo principal
            $detalle .= '<br>Stock: <strong>' . (isset($f['stock_actual']) ? number_format((float)$f['stock_actual'], 2, '.', '') : '—') . '</strong>';
            // L3: contexto complementario
            $linea3C4 = '';
            $costeC4 = isset($f['coste_estimado']) && $f['coste_estimado'] !== null ? (float)$f['coste_estimado'] : null;
            if ($costeC4 !== null) {
                $linea3C4 .= '<span title="Valor estimado del stock inmovilizado: stock × precio medio de compra">Coste est.: <strong>~'
                    . number_format($costeC4, 0, ',', '.') . ' €</strong></span>';
            }
            if (!empty($f['prov_habitual_nombre'])) {
                $linea3C4 .= ($linea3C4 ? ' | ' : '')
                    . '<span title="Proveedor principal del artículo en el año en curso">Prov.: '
                    . htmlspecialchars($f['prov_habitual_nombre']) . '</span>';
                if (!empty($f['prov_ultimo_nombre']) && empty($f['prov_es_mismo'])) {
                    $linea3C4 .= ' | <span title="Proveedor del último albarán recibido ('
                        . htmlspecialchars($f['prov_ultima_fecha'] ?? '') . ')">Último: '
                        . htmlspecialchars($f['prov_ultimo_nombre']) . '</span>';
                }
            }
            if ($linea3C4) {
                $detalle .= '<br><small class="text-muted">' . $linea3C4 . '</small>';
            }

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
            $mermaKg      = isset($f['merma_total_kg'])      ? (float)$f['merma_total_kg']      : null;
            $mermaPendKg  = isset($f['merma_pendiente_kg'])  ? (float)$f['merma_pendiente_kg']  : 0.0;
            $mermaCarryKg = isset($f['merma_carryover_kg'])  ? (float)$f['merma_carryover_kg']  : 0.0;
            $nInciertos9  = (int)($f['n_lotes_inciertos'] ?? ($mermaPendKg > 0.001 ? 1 : 0));
            $mermaDeclKg  = isset($f['merma_declarada_kg'])  ? (float)$f['merma_declarada_kg']  : null;
            $mermaDeclMes = !empty($f['merma_decl_por_mes']) ? $f['merma_decl_por_mes'] : [];
            $pctMerma     = isset($f['pct_merma'])           ? (float)$f['pct_merma']           : null;
            $nLotes       = (int)($f['n_lotes']         ?? 0);
            $nDeficit     = (int)($f['n_lotes_deficit'] ?? 0);
            $nMerma       = (int)($f['n_lotes_merma']   ?? 0);
            $nRec         = (int)($f['n_recepciones']   ?? 0);
            $confianza9   = $f['confianza']    ?? 'posible';
            $consOk9      = !empty($f['conservation_ok']);
            $consDelta9   = isset($f['conservation_delta']) ? (float)$f['conservation_delta'] : null;
            $tipoArt9     = $f['tipo_articulo'] ?? 'peso';
            $unidad9      = $tipoArt9 === 'peso' ? 'kg' : 'ud.';
            $_dec9        = $tipoArt9 === 'peso' ? 2 : 0;  // decimales para formatear cantidades
            $betaUsado    = isset($f['beta_usado'])    ? (float)$f['beta_usado']    : null;
            $modo9        = $f['modo'] ?? 'continuo';
            $totalE9      = isset($f['total_E'])       ? (float)$f['total_E']       : null;
            $stockFinal9  = isset($f['stock_final'])   ? (float)$f['stock_final']   : null;
            $deficitBloq9 = isset($f['deficit_bloqueado_kg']) ? (float)$f['deficit_bloqueado_kg'] : 0.0;
            $deficitMomentos9 = !empty($f['deficit_bloqueado_momentos']) ? $f['deficit_bloqueado_momentos'] : [];
            $deficitEstable9 = !empty($f['deficit_bloqueado_patron_estable']);
            $deficitIntTipico9 = isset($f['deficit_bloqueado_intervalo_tipico_dias']) ? (int)$f['deficit_bloqueado_intervalo_tipico_dias'] : null;
            $deficitCvInt9 = isset($f['deficit_bloqueado_cv_intervalos']) ? (float)$f['deficit_bloqueado_cv_intervalos'] : null;
            $deficitEstimaciones9 = !empty($f['deficit_bloqueado_estimaciones']) ? $f['deficit_bloqueado_estimaciones'] : [];
            $deficitAbsHered9 = isset($f['deficit_bloqueado_absorbido_heredado_kg']) ? (float)$f['deficit_bloqueado_absorbido_heredado_kg'] : 0.0;
            $coste9       = isset($f['coste_estimado_merma']) && $f['coste_estimado_merma'] !== null
                ? (float)$f['coste_estimado_merma'] : null;

            // Plan mensual: distribución contable de la merma mes a mes
            $planMensual9      = !empty($f['plan_mensual']) ? $f['plan_mensual'] : [];
            $planArrastre9     = isset($f['plan_arrastre_final']) ? (float)$f['plan_arrastre_final'] : 0.0;
            $planTotalAplicable9 = isset($f['plan_total_aplicable']) ? (float)$f['plan_total_aplicable'] : 0.0;

            // Etiquetas de meses
            $_mc9 = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

            // Mes de referencia del plan: mes actual si cae en el periodo, si no el último mes del plan
            $_mesHoy = date('Y-m');
            if (!empty($planMensual9)) {
                $_mesesPlan = array_keys($planMensual9);
                if (in_array($_mesHoy, $_mesesPlan, true)) {
                    $_mesRefPlan = $_mesHoy;
                } else {
                    $_mesRefPlan = end($_mesesPlan);
                }
            } else {
                $_mesRefPlan = null;
            }

            // ── Pre-calcular merma pendiente mensual (lotes inciertos) ───────
            // Solo para la posible causa: saber si hay meses con pendiente sin confirmar
            $_mmPend = [];
            foreach (($f['merma_por_lote'] ?? []) as $_lot) {
                $_mt = (float)($_lot['merma_t'] ?? 0.0);
                if ($_mt < 0.001) continue;
                if (empty($_lot['es_lote_incierto']) && empty($_lot['v_t_parcial'])) continue;
                $_mes = (int)substr($_lot['fecha_ini'] ?? '', 5, 2);
                if ($_mes < 1 || $_mes > 12) continue;
                $_mmPend[$_mes] = ($_mmPend[$_mes] ?? 0.0) + $_mt;
            }

            // ── Badge de acción principal (qué debe hacer el operador) ───────
            if ($confianza9 === 'alta' && $consOk9) {
                // Severidad 4-5: danger; 2-3: warning; 1: default
                $sevNum9 = (int)($f['severidad_num'] ?? 1);
                $_clsBadgeAccion = $sevNum9 >= 4 ? 'label-danger' : ($sevNum9 >= 2 ? 'label-warning' : 'label-default');
                $badgeConf9 = '<span class="label ' . $_clsBadgeAccion . '"'
                    . ' title="Merma confirmada: el modelo tiene alta confianza y el balance de entradas/salidas cuadra con el stock contable. '
                    . 'Registrar como ajuste de inventario: ~' . ($mermaKg !== null ? number_format($mermaKg, $_dec9, ',', '.') . ' ' . $unidad9 : '—') . '.">'
                    . 'Registrar merma</span>';
            } elseif ($confianza9 === 'media' || ($confianza9 === 'alta' && !$consOk9)) {
                $badgeConf9 = '<span class="label label-warning"'
                    . ' title="Merma probable: el patrón es claro pero hay pequeños desajustes en el balance de stock. Revisar albaranes del periodo y, si todo es correcto, registrar el ajuste de inventario.">'
                    . 'Revisar y registrar</span>';
            } else {
                $badgeConf9 = '<span class="label label-default"'
                    . ' title="Señal de merma detectada con evidencia limitada (pocas recepciones o balance de stock desajustado). Esperar a acumular más datos o hacer recuento físico antes de registrar.">'
                    . 'Pendiente de confirmar</span>';
            }

            // ── Badge lotes inciertos ────────────────────────────────────────
            $badgeIncierto9 = '';
            if ($nInciertos9 > 0) {
                $_provLabel9 = !empty($f['prov_habitual_nombre'])
                    ? ' Proveedor habitual: ' . htmlspecialchars($f['prov_habitual_nombre']) . '.'
                    : '';
                $_pendFmt9 = $mermaPendKg > 0.001 ? number_format($mermaPendKg, $_dec9, ',', '.') . ' ' . $unidad9 : '?';
                $tooltipInc = $nInciertos9 === 1
                    ? 'Ciclo en curso: el último lote no tiene recepción posterior dentro del periodo. '
                    . 'La merma pendiente (' . $_pendFmt9 . ') se confirmará cuando llegue la próxima recepción.' . $_provLabel9
                    : $nInciertos9 . ' lotes finales sin recepción posterior. '
                    . 'Merma pendiente (' . $_pendFmt9 . '): se confirmará con las próximas recepciones.' . $_provLabel9;
                $badgeIncierto9 = ' <span class="label label-info"'
                    . ($nInciertos9 > 1 ? ' data-nofiltro="1"' : '')
                    . ' title="' . htmlspecialchars($tooltipInc) . '">'
                    . ($nInciertos9 === 1 ? 'Ciclo en curso' : $nInciertos9 . ' lotes en curso')
                    . '</span>';
            }

            // ── Badge déficit bloqueado (sobreventa: acción clara) ───────────
            $badgeDeficitBloq9 = '';
            if ($deficitBloq9 > 0.001) {
                $_momentosTxt = [];
                foreach ($deficitMomentos9 as $_mom) {
                    $_fi = $_mom['fecha_ini'] ?? '';
                    $_ff = $_mom['fecha_fin'] ?? '';
                    $_kg = isset($_mom['kg']) ? (float)$_mom['kg'] : 0.0;
                    if (!$_fi) continue;
                    $_momLabel = $_fi;
                    if (!empty($_ff) && $_ff !== $_fi) {
                        $_momLabel .= '→' . $_ff;
                    }
                    $_momLabel .= ': ' . number_format($_kg, $_dec9, ',', '.') . ' ' . $unidad9;
                    $_momentosTxt[] = $_momLabel;
                }
                $_estTxt = '';
                if ($deficitEstable9 && !empty($deficitEstimaciones9)) {
                    $_fechasEst = array_values(array_filter(array_map(fn($_e) => $_e['fecha_estimada'] ?? null, $deficitEstimaciones9)));
                    $_fechasEst = array_unique($_fechasEst);
                    if (!empty($_fechasEst)) {
                        $_estTxt = ' Patrón de entradas estable';
                        if ($deficitIntTipico9 !== null && $deficitIntTipico9 > 0) {
                            $_estTxt .= ' (cadencia ~' . $deficitIntTipico9 . ' días';
                            if ($deficitCvInt9 !== null) {
                                $_estTxt .= ', CV=' . number_format($deficitCvInt9, 2, ',', '.');
                            }
                            $_estTxt .= ')';
                        }
                        $_estTxt .= '. Fecha estimada de albarán faltante: ' . implode(' | ', $_fechasEst) . '.';
                    }
                }
                $_momentoDetalle = !empty($_momentosTxt)
                    ? ' Momentos de déficit: ' . implode(' | ', $_momentosTxt) . '.'
                    : '';
                $badgeDeficitBloq9 = ' <span class="label label-warning"'
                    . ' title="Hay ' . number_format($deficitBloq9, $_dec9, ',', '.') . ' ' . $unidad9
                    . ' de sobreventa que el modelo no puede asignar a ningún lote anterior.'
                    . ' Acción: comprobar si falta un albarán de compra sin registrar en este periodo.'
                    . $_momentoDetalle
                    . $_estTxt
                    . ' Este importe NO está incluido en la merma estimada.">'
                    . 'Revisar albarán</span>';
            }

            // ── Badge arrastre del plan (stock insuficiente para regularizar) ──
            $badgeArrastre9 = '';
            if ($planArrastre9 > 0.001) {
                $badgeArrastre9 = ' <span class="label label-warning" data-nofiltro="1"'
                    . ' title="La merma supera el stock disponible en algún mes. '
                    . number_format($planArrastre9, $_dec9, ',', '.') . ' ' . $unidad9
                    . ' no se pueden regularizar sin generar stock negativo. '
                    . 'Considerar diferir al siguiente periodo o hacer recuento físico.">'
                    . 'Arrastre ' . number_format($planArrastre9, $_dec9, ',', '.') . '&nbsp;' . $unidad9 . '</span>';
            }

            // ── Badge merma declarada ────────────────────────────────────────
            $badgeDecl9 = '';
            if ($mermaDeclKg !== null && $mermaDeclKg > 0.0) {
                $_declTooltip = 'Ya registrada en albaranes de regularización: '
                    . number_format($mermaDeclKg, $_dec9, ',', '.') . ' ' . $unidad9 . '.';
                if (!empty($mermaDeclMes)) {
                    $_declParts = [];
                    foreach ($mermaDeclMes as $_dm => $_dv) {
                        $_dmNum = is_int($_dm) ? $_dm : (int)substr($_dm, 5, 2);
                        $_dmLbl = ($_dmNum >= 1 && $_dmNum <= 12) ? $_mc9[$_dmNum] : $_dm;
                        $_declParts[] = $_dmLbl . ': ' . number_format($_dv, $_dec9, ',', '.') . ' ' . $unidad9;
                    }
                    $_declTooltip .= ' Desglose: ' . implode(' | ', $_declParts) . '.';
                }
                $_declTooltip .= ' La merma estimada ya incluye la declarada; la pérdida no documentada es la diferencia.';
                $badgeDecl9 = ' <span class="label label-info" data-nofiltro="1"'
                    . ' title="' . htmlspecialchars($_declTooltip) . '">'
                    . 'Ya declarada: ' . number_format($mermaDeclKg, $_dec9, ',', '.') . '&nbsp;' . $unidad9 . '</span>';
            }

            // ── Badges solo técnico ──────────────────────────────────────────
            $badgeTec9 = '';
            if ($mostrarTecnico) {
                // Conservación de masa
                $badgeTec9 .= $consOk9
                    ? ' <span class="label label-success" title="Conservación OK: sum(S_t) coincide con el stock contable al cierre (Δ=' . ($f['conservation_delta'] ?? '?') . '). El modelo explica la trayectoria correctamente.">Cons. OK</span>'
                    : ' <span class="label label-default" title="El modelo no cierra masa (Δ=' . ($f['conservation_delta'] ?? '?') . '). Posibles desajustes históricos o albaranes sin registrar.">Stock desaj.</span>';
                // Pico de merma mensual (solo técnico: indica outlier estadístico)
                if (!empty($planMensual9) && $mermaKg !== null && $mermaKg > 0.001) {
                    $_planVals = array_column($planMensual9, 'propuesto');
                    $_planMax  = !empty($_planVals) ? max($_planVals) : 0.0;
                    $_nMesesPlan = count($_planVals);
                    if ($_nMesesPlan >= 3 && $_planMax / $mermaKg >= 0.25) {
                        $_avgOtros9 = ($_nMesesPlan > 1) ? ($mermaKg - $_planMax) / ($_nMesesPlan - 1) : 0.0;
                        if ($_avgOtros9 > 0.001 && $_planMax / $_avgOtros9 >= 2.5) {
                            $_mesMaxKey9 = array_search($_planMax, array_column($planMensual9, 'propuesto', null));
                            // array_search sobre associative: buscar la clave correcta
                            foreach ($planMensual9 as $_mk => $_mv) {
                                if (abs($_mv['propuesto'] - $_planMax) < 0.001) {
                                    $_mesMaxKey9 = $_mk;
                                    break;
                                }
                            }
                            $_mesMaxNum9 = $_mesMaxKey9 ? (int)substr($_mesMaxKey9, 5, 2) : 0;
                            $_nomPico9   = ($_mesMaxNum9 >= 1 && $_mesMaxNum9 <= 12) ? $_mc9[$_mesMaxNum9] : '?';
                            $badgeTec9  .= ' <span class="label label-warning"'
                                . ' title="Outlier: ' . $_nomPico9 . ' concentra el ' . number_format($_planMax / $mermaKg * 100, 1, ',', '.') . '% de la merma ('
                                . number_format($_planMax, $_dec9, ',', '.') . '&nbsp;' . $unidad9 . '), superando '
                                . number_format($_planMax / $_avgOtros9, 1, ',', '.') . '× la media del resto. Puede indicar regularización puntual o cruce sin conciliar.">'
                                . 'Pico ' . $_nomPico9 . '</span>';
                        }
                    }
                }
            }

            // ── Línea 1: badges ──────────────────────────────────────────────
            $detalle = $badgeConf9 . $badgeIncierto9 . $badgeDeficitBloq9 . $badgeArrastre9 . $badgeDecl9 . $badgeTec9;

            // ── Línea 2: dato operativo — "a registrar este mes" o total ─────
            // Si hay plan mensual: mostrar el aplicable del mes de referencia
            // como dato principal. Si no hay plan: mostrar merma total del periodo.
            if (
                $_mesRefPlan !== null && !empty($planMensual9[$_mesRefPlan])
                && $planMensual9[$_mesRefPlan]['aplicable'] > 0.001
            ) {
                $_mesRefNum9  = (int)substr($_mesRefPlan, 5, 2);
                $_mesRefLabel9 = ($_mesRefNum9 >= 1 && $_mesRefNum9 <= 12) ? $_mc9[$_mesRefNum9] : $_mesRefPlan;
                $_aplicable9  = $planMensual9[$_mesRefPlan]['aplicable'];
                $_arrastre9   = $planMensual9[$_mesRefPlan]['arrastre'];
                $linea2_9 = 'A registrar en ' . $_mesRefLabel9 . ': <strong>~'
                    . number_format($_aplicable9, $_dec9, ',', '.') . '&nbsp;' . $unidad9 . '</strong>';
                if ($planTotalAplicable9 > 0.001 && abs($planTotalAplicable9 - $_aplicable9) > 0.01) {
                    $linea2_9 .= ' <span class="text-muted" title="Merma confirmada total del periodo, distribuida entre los meses según el plan">'
                        . '(total periodo: ~' . number_format($planTotalAplicable9, $_dec9, ',', '.') . '&nbsp;' . $unidad9 . ')</span>';
                }
                if ($_arrastre9 > 0.001) {
                    $linea2_9 .= ' <span class="text-muted" title="El stock de ' . $_mesRefLabel9 . ' no tiene margen suficiente para absorber toda la merma. '
                        . number_format($_arrastre9, $_dec9, ',', '.') . '&nbsp;' . $unidad9 . ' se trasladan al mes siguiente.">'
                        . '· arrastre ' . number_format($_arrastre9, $_dec9, ',', '.') . '&nbsp;' . $unidad9 . '</span>';
                }
            } else {
                // Sin plan o mes sin aplicable: mostrar merma total estimada
                $linea2_9 = 'Merma est.: ';
                if ($mermaKg !== null) {
                    $linea2_9 .= '<strong>~' . number_format($mermaKg, $_dec9, ',', '.') . '&nbsp;' . $unidad9 . '</strong>';
                } else {
                    $linea2_9 .= '—';
                }
                if ($pctMerma !== null) {
                    $linea2_9 .= ' <span class="text-muted">(' . number_format($pctMerma, 1, ',', '.') . '% s/entradas)</span>';
                }
            }
            // Siempre: si hay pendiente, añadir nota
            if ($mermaPendKg > 0.001) {
                $linea2_9 .= ' <span class="text-muted"'
                    . ' title="Merma en ciclos no cerrados (el artículo sigue vendiéndose). Se confirmará con la próxima recepción. NO registrar aún.">'
                    . '+ pendiente ~' . number_format($mermaPendKg, $_dec9, ',', '.') . '&nbsp;' . $unidad9 . '</span>';
            }
            $detalle .= '<br>' . $linea2_9;

            // ── Línea 3: distribución mensual del plan ────────────────────────
            // Operador: lista de "Mes: X kg a registrar" usando plan_mensual.aplicable
            // Técnico: añade arrastre por mes y parámetros del modelo
            if (!empty($planMensual9)) {
                $_planPartes = [];
                foreach ($planMensual9 as $_pmk => $_pmv) {
                    $_pmNum = (int)substr($_pmk, 5, 2);
                    $_pmLbl = ($_pmNum >= 1 && $_pmNum <= 12) ? $_mc9[$_pmNum] : $_pmk;
                    $_aplic = (float)$_pmv['propuesto'];  // propuesto = a registrar sin tope
                    $_apliReal = (float)$_pmv['aplicable']; // aplicable = con tope de stock
                    if ($_aplic < 0.001 && $_apliReal < 0.001) continue;

                    if ($mostrarTecnico && abs($_aplic - $_apliReal) > 0.01) {
                        // Mes con tope: mostrar propuesto vs aplicable
                        $_planPartes[] = $_pmLbl . ':&nbsp;~' . number_format($_apliReal, $_dec9, ',', '.') . '&nbsp;'
                            . $unidad9 . ' <span class="text-muted" title="Limitado por stock disponible. Propuesto: '
                            . number_format($_aplic, $_dec9, ',', '.') . '">(' . number_format($_aplic, $_dec9, ',', '.') . ' prop.)</span>';
                    } else {
                        $_planPartes[] = $_pmLbl . ':&nbsp;~' . number_format($_apliReal > 0.001 ? $_apliReal : $_aplic, $_dec9, ',', '.') . '&nbsp;' . $unidad9;
                    }
                    // Mes con pendiente solapado: añadir nota
                    if (!empty($_mmPend)) {
                        if (isset($_mmPend[$_pmNum])) {
                            $_planPartes[count($_planPartes) - 1] .= ' <span class="text-muted">(+' . number_format($_mmPend[$_pmNum], $_dec9, ',', '.') . '&nbsp;pend.)</span>';
                        }
                    }
                }
                $linea3_9 = implode(' · ', $_planPartes);
                // Técnico: parámetros del modelo al final
                if ($mostrarTecnico && $betaUsado !== null) {
                    $linea3_9 .= ' <span class="text-muted"> | ' . $modo9 . ' β=' . number_format($betaUsado, 2, '.', '');
                    if (isset($f['k_usado']))      $linea3_9 .= ' k=' . (int)$f['k_usado'];
                    if (isset($f['lambda_usado'])) $linea3_9 .= ' λ=' . number_format((float)$f['lambda_usado'], 1, '.', '');
                    $linea3_9 .= '</span>';
                }
            } else {
                // Sin plan: fallback — nº recepciones (informativo)
                $linea3_9 = $nRec . ' rec. en periodo';
                if ($mostrarTecnico) {
                    $linea3_9 .= ' · ' . $nLotes . ' lotes | ' . $nDeficit . ' c/déficit · ' . $nMerma . ' c/merma';
                    if ($betaUsado !== null) {
                        $linea3_9 .= ' | ' . $modo9 . ' β=' . number_format($betaUsado, 2, '.', '');
                        if (isset($f['k_usado']))      $linea3_9 .= ' k=' . (int)$f['k_usado'];
                        if (isset($f['lambda_usado'])) $linea3_9 .= ' λ=' . number_format((float)$f['lambda_usado'], 1, '.', '');
                    }
                }
            }
            $detalle .= '<br><small class="text-muted">' . $linea3_9 . '</small>';

            // ── Posible causa: orientada a la acción del operador ────────────
            // Estructura: [acción principal] + [desglose del plan] + [contexto técnico]

            // 1. Determinar acción principal según patrón
            $_nMesesPlan9 = count($planMensual9);
            $_mesesConMerma9 = 0;
            foreach ($planMensual9 as $_pv) {
                if ((float)$_pv['propuesto'] > 0.001) $_mesesConMerma9++;
            }

            $_accionPrincipal = '';
            if ($deficitBloq9 > 0.001) {
                // Prioridad 1: albarán faltante
                $_accionPrincipal = 'Comprobar si falta un albarán de compra sin registrar: hay '
                    . number_format($deficitBloq9, 2, ',', '.') . '&nbsp;' . $unidad9
                    . ' de sobreventa que el modelo no puede explicar con el historial de recepciones. ';

                if (!empty($deficitMomentos9)) {
                    $_mTxt = [];
                    foreach ($deficitMomentos9 as $_m) {
                        $_fi = $_m['fecha_ini'] ?? '';
                        $_ff = $_m['fecha_fin'] ?? '';
                        $_kg = isset($_m['kg']) ? (float)$_m['kg'] : 0.0;
                        if (!$_fi) continue;
                        $_label = $_fi;
                        if (!empty($_ff) && $_ff !== $_fi) {
                            $_label .= '→' . $_ff;
                        }
                        $_label .= ' (~' . number_format($_kg, $_dec9, ',', '.') . '&nbsp;' . $unidad9 . ')';
                        $_mTxt[] = $_label;
                    }
                    if (!empty($_mTxt)) {
                        $_accionPrincipal .= 'Momentos detectados: ' . implode(' · ', $_mTxt) . '. ';
                    }
                }

                if ($deficitEstable9 && !empty($deficitEstimaciones9)) {
                    $_fEst = array_values(array_filter(array_map(fn($_e) => $_e['fecha_estimada'] ?? null, $deficitEstimaciones9)));
                    $_fEst = array_unique($_fEst);
                    if (!empty($_fEst)) {
                        $_accionPrincipal .= 'Con patrón de entradas estable';
                        if ($deficitIntTipico9 !== null && $deficitIntTipico9 > 0) {
                            $_accionPrincipal .= ' (cadencia ~' . $deficitIntTipico9 . ' días';
                            if ($deficitCvInt9 !== null) {
                                $_accionPrincipal .= ', CV=' . number_format($deficitCvInt9, 2, ',', '.');
                            }
                            $_accionPrincipal .= ')';
                        }
                        $_accionPrincipal .= ', fecha estimada de albarán faltante: ' . implode(' · ', $_fEst) . '. ';
                    }
                }
            } elseif ($nInciertos9 > 0 && ($mermaKg === null || $mermaKg < 0.5)) {
                // Prioridad 2: ciclo en curso sin merma confirmada aún
                $_accionPrincipal = 'Ciclo en curso. Esperar a la próxima recepción'
                    . (!empty($f['prov_habitual_nombre']) ? ' de ' . htmlspecialchars($f['prov_habitual_nombre']) : '')
                    . ' para confirmar la merma. ';
            } elseif ($_mesesConMerma9 === 0 && $mermaPendKg > 0.001) {
                // Solo pendiente, nada confirmado
                $_accionPrincipal = 'Merma pendiente de confirmar (ciclo no cerrado). No registrar hasta recibir la próxima recepción. ';
            } elseif ($_mesesConMerma9 <= 2) {
                $_accionPrincipal = 'Pérdida concentrada'
                    . ($_mesRefPlan ? ' en ' . ($_mc9[(int)substr($_mesRefPlan, 5, 2)] ?? '') : '')
                    . '. Revisar recepciones y manipulación de ese periodo. '
                    . ($confianza9 === 'alta' ? 'Registrar ajuste de inventario. ' : 'Verificar y registrar ajuste. ');
            } elseif ($_mesesConMerma9 >= 5) {
                $_accionPrincipal = 'Pérdida continuada (' . $_mesesConMerma9 . ' meses). '
                    . 'Plantear ajuste de inventario periódico o revisar condiciones de almacenamiento y manipulación. ';
            } else {
                $_accionPrincipal = 'Pérdida intermitente en ' . $_mesesConMerma9 . ' meses. '
                    . 'Revisar patrones de recepción y almacenamiento. ';
            }
            if ($pctMerma !== null && $pctMerma >= 15.0) {
                $_accionPrincipal .= 'Tasa de merma alta (' . number_format($pctMerma, 1, ',', '.') . '%). ';
            }

            // 2. Desglose del plan mensual (lo que hay que registrar)
            $_planDesglose = [];
            foreach ($planMensual9 as $_pmk => $_pmv) {
                $_pmNum = (int)substr($_pmk, 5, 2);
                $_pmLbl = ($_pmNum >= 1 && $_pmNum <= 12) ? $_mc9[$_pmNum] : $_pmk;
                $_aplic = (float)$_pmv['aplicable'];
                if ($_aplic < 0.001) continue;
                $_planDesglose[] = $_pmLbl . ': ~' . number_format($_aplic, $_dec9, ',', '.') . '&nbsp;' . $unidad9;
            }

            // Pendiente sin plan: meses con merma en lotes inciertos
            foreach ($_mmPend as $_pmNum => $_pendVal) {
                $_pmLbl = ($_pmNum >= 1 && $_pmNum <= 12) ? $_mc9[$_pmNum] : '?';
                $_planDesglose[] = $_pmLbl . ': <span class="text-muted">~' . number_format($_pendVal, $_dec9, ',', '.') . '&nbsp;' . $unidad9 . ' (pendiente)</span>';
            }

            // 3. Contexto de proveedor + coste
            $_contextoOp = [];
            if ($coste9 !== null) {
                $_contextoOp[] = '<strong title="Valor económico estimado de la merma: merma total × precio medio de compra del periodo">~'
                    . number_format($coste9, 0, ',', '.') . '&nbsp;€</strong>';
            }
            if (!empty($f['prov_habitual_nombre'])) {
                $_contextoOp[] = 'Prov.: ' . htmlspecialchars($f['prov_habitual_nombre']);
            }

            $posibleCausaHtml = $_accionPrincipal;
            if (!empty($_planDesglose)) {
                $posibleCausaHtml .= implode(' · ', $_planDesglose) . '.';
            }
            if (!empty($_contextoOp)) {
                $posibleCausaHtml .= ' <span class="text-muted">· ' . implode(' · ', $_contextoOp) . '</span>';
            }

            // 4. Sección técnica: validación estadística del modelo
            if ($mostrarTecnico) {
                $_tecLines = [];
                // Línea de anclas de conservación
                $_anclas = [];
                if ($totalE9 !== null) $_anclas[] = 'Σ&nbsp;E=' . number_format($totalE9, $_dec9, ',', '.') . '&nbsp;' . $unidad9;
                if ($stockFinal9 !== null) $_anclas[] = 'S<sub>f</sub>=' . number_format($stockFinal9, $_dec9, ',', '.') . '&nbsp;' . $unidad9;
                if ($consDelta9 !== null) {
                    $_consStyle = $consOk9 ? 'color:#3c763d' : 'color:#a94442';
                    $_anclas[] = '<span style="' . $_consStyle . '">Δcons=' . number_format($consDelta9, 4, '.', '') . ($consOk9 ? ' ✓' : ' ✗') . '</span>';
                }
                if (!empty($_anclas)) $_tecLines[] = implode(' · ', $_anclas);
                // Lotes
                $_lotesInfo = $nLotes . ' lotes | ' . $nDeficit . ' c/déficit · ' . $nMerma . ' c/merma';
                if ($mermaCarryKg > 0.001) {
                    $_lotesInfo .= ' · carryover=' . number_format($mermaCarryKg, $_dec9, ',', '.') . '&nbsp;' . $unidad9;
                }
                $_lotesInfo .= ' | ' . $nRec . ' rec.';
                $_tecLines[] = $_lotesInfo;
                // Plan arrastre
                if ($planArrastre9 > 0.001) {
                    $_tecLines[] = '<span style="color:#a94442">Arrastre sin aplicar: '
                        . number_format($planArrastre9, $_dec9, ',', '.') . '&nbsp;' . $unidad9
                        . ' (stock insuficiente para absorber toda la merma este periodo)</span>';
                }
                if ($deficitAbsHered9 > 0.001) {
                    $_tecLines[] = '<span class="text-info">Déficit bloqueado absorbido por stock heredado: '
                        . number_format($deficitAbsHered9, $_dec9, ',', '.') . '&nbsp;' . $unidad9
                        . ' (evita falso "Revisar albarán" en el arranque del periodo)</span>';
                }
                if (!empty($_tecLines)) {
                    $posibleCausaHtml .= '<br><small class="text-muted">' . implode('<br>', $_tecLines) . '</small>';
                }
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

            // L1: badges de diagnóstico (acción principal + alertas)
            $detalle = $badgeStockNoFiable . $badgeC6Q . $badgeC6Rec;
            if ($mostrarTecnico) {
                $detalle .= ' ' . $badgeC6Modelo . $badgeC6Fuente . $badgeC6LT;
                $detalle .= ' <small class="text-muted">· ' . htmlspecialchars($c6m['comp']) . '</small>';
            }
            // L2: dato operativo principal
            $detalle .= '<br>' . $stockLabel
                . ' | Autonomía: <strong>' . (isset($f['dias_autonomia']) ? $f['dias_autonomia'] . ' d' : '—') . '</strong>';
            // L3: contexto complementario
            $linea3C6 = 'LT: ' . (isset($f['lead_time_dias']) ? $f['lead_time_dias'] . ' d' : '—')
                . ' | ROP: ' . number_format($ropC6, 2, '.', '') . ' ' . $unidad;
            if ($diasTrasPedido !== null) {
                $linea3C6 .= ' | Cobertura tras pedido: <strong>' . $diasTrasPedido . ' d</strong>';
            }
            $costeC6 = isset($f['coste_estimado']) && $f['coste_estimado'] !== null ? (float)$f['coste_estimado'] : null;
            if ($costeC6 !== null) {
                $linea3C6 .= ' | <span title="Valor estimado del pedido recomendado: cantidad × precio medio de compra">Coste est.: <strong>~'
                    . number_format($costeC6, 0, ',', '.') . ' €</strong></span>';
            }
            if (!empty($f['prov_habitual_nombre'])) {
                $linea3C6 .= ' | <span title="Proveedor principal del artículo en el año en curso">Prov.: '
                    . htmlspecialchars($f['prov_habitual_nombre']) . '</span>';
                if (!empty($f['prov_ultimo_nombre']) && empty($f['prov_es_mismo'])) {
                    $linea3C6 .= ' | <span title="Proveedor del último albarán recibido ('
                        . htmlspecialchars($f['prov_ultima_fecha'] ?? '') . ')">Último: '
                        . htmlspecialchars($f['prov_ultimo_nombre']) . '</span>';
                }
            }
            $detalle .= '<br><small class="text-muted">' . $linea3C6 . '</small>';
        }

        // ── tipoLabel ──────────────────────────────────────────────────────────
        switch ($tipo) {
            case 'Inventario en negativo':
                $tipoLabel = 'Stock negativo';
                break;
            case 'Desajuste Puntual de Stock':
                $tipoLabel = 'Descuadre temporal';
                break;
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
                $tipoLabel = 'Rotura de stock';
                break;
            case 'Entrada sin rotación previa':
                $tipoLabel = 'Pedido sin rotación';
                break;
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
        // Siempre año completo: 1-ene … 31-dic (año cerrado) o 1-ene … hoy (año en curso).
        $urlMayor = '../../modulos/mod_producto/DetalleMayor.php'
            . '?idArticulo=' . (int)($f['idArticulo'] ?? 0)
            . '&fecha_inicial=' . urlencode($fiInicio)
            . '&fecha_final='   . urlencode($ffAnioMayor);

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

// ── Renderizado en lotes (modo batch) ────────────────────────────────────────

/**
 * Devuelve únicamente los elementos <tr> para las $filas indicadas,
 * sin los envoltorios <style>, <table>, <thead> ni <tbody>.
 *
 * Diseñado para el renderizado en lotes desde JavaScript:
 *   Primer lote  → renderTablaPosstock($batch, $cfg)   (tabla completa con cabecera)
 *   Lotes 2..N   → renderFilasTablaPosstock($batch, $cfg)  (solo <tr> para append)
 *
 * Así ninguna respuesta HTTP individual supera los ~200 KB, lo que evita el
 * desbordamiento de los buffers FastCGI de nginx con datasets grandes.
 *
 * @param array $filas  Subconjunto de incidencias del lote.
 * @param array $cfg    Mismos parámetros de contexto que renderTablaPosstock().
 * @return string       Concatenación de elementos <tr>…</tr>; vacío si no hay filas.
 */
function renderFilasTablaPosstock(array $filas, array $cfg): string
{
    if (empty($filas)) return '';

    // Reutilizar renderTablaPosstock() para no duplicar la lógica de renderizado.
    // Solo se extrae el contenido del <tbody> (los <tr> ya generados).
    $html = renderTablaPosstock($filas, $cfg);
    preg_match('/<tbody>(.*?)<\/tbody>/s', $html, $m);
    return $m[1] ?? '';
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
