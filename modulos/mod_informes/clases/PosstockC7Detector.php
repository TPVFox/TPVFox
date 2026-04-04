<?php

/**
 * PosstockC7Detector — Detector de offset sistemático de inventario (Caso C7).
 *
 * Extraído de ClasePosstock como parte de la Fase 5 de refactorización.
 *
 * Orquesta los tres sub-analizadores:
 *   · PosstockC7aAnalyzer  — cascada estadística C7a (merma sistemática, floors en alza)
 *   · PosstockC7bAnalyzer  — cascada estadística C7b (déficit constante, floors negativos)
 *   · PosstockC7cdeAnalyzer— cruces inter-artículo C7c (par), C7d (trío), C7e (múltiplo)
 *
 * Métodos públicos:
 *   detectar(...)      — orquestador C7 completo (C7a + C7b + C7c/d/e opcional)
 *   resolverC7cde(...) — fase 2: resuelve cruces sobre conjunto completo de artículos
 */

require_once __DIR__ . '/PosstockStatistics.php';
require_once __DIR__ . '/PosstockQueryRepository.php';
require_once __DIR__ . '/PosstockC7aAnalyzer.php';
require_once __DIR__ . '/PosstockC7bAnalyzer.php';
require_once __DIR__ . '/PosstockC7cdeAnalyzer.php';

class PosstockC7Detector
{
    private PosstockC7aAnalyzer   $c7a;
    private PosstockC7bAnalyzer   $c7b;
    private PosstockC7cdeAnalyzer $c7cde;

    public function __construct(
        private mysqli                  $db,
        private PosstockQueryRepository $repo,
        ?PosstockC7aAnalyzer            $c7a   = null,
        ?PosstockC7bAnalyzer            $c7b   = null,
        ?PosstockC7cdeAnalyzer          $c7cde = null
    ) {
        $this->c7a   = $c7a   ?? new PosstockC7aAnalyzer();
        $this->c7b   = $c7b   ?? new PosstockC7bAnalyzer();
        $this->c7cde = $c7cde ?? new PosstockC7cdeAnalyzer();
    }

    // ═══════════════════════════════════════════════════════════════════
    // API Pública
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Detecta incidencias C7a y/o C7b para los artículos candidatos.
     *
     * Parámetros $subcasos: subset activo, e.g. ['C7a'], ['C7b'], ['C7a','C7b'].
     * $skip_cde: true en lotes parciales; C7c/d/e requiere el conjunto completo.
     *
     * @return array  Incidencias C7 (sin campo 'nombre')
     */
    public function detectar(
        string $fi_mov,
        string $ff_mov,
        string $fi_stock,
        string $ff_stock,
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter            = [],
        array  $stock_base_cache      = [],
        array  $subcasos              = ['C7a', 'C7b'],
        int    $c7b_min_recepciones    = 3,
        float  $c7b_umbral_cv          = 0.5,
        float  $c7b_umbral_cv_peso     = 0.75,
        float  $c7b_umbral_iqr_peso    = 2.0,
        float  $c7b_umbral_ruido_peso  = 0.5,
        int    $c7b_umbral_sev_unidad  = 5,
        float  $c7b_umbral_sev_peso    = 2.5,
        bool   $c7b_cascada_exhaustiva = false,
        bool   $skip_cde              = false,
        float  $c7a_umbral_delta_unidad     = 2.0,
        float  $c7a_umbral_delta_peso       = 1.0,
        float  $c7a_umbral_pvalue           = 0.10,
        float  $c7a_umbral_pvalue_alta      = 0.05,
        float  $c7a_umbral_alta_delta_unidad = 10.0,
        float  $c7a_umbral_alta_delta_peso  = 5.0,
        float  $c7a_umbral_alta_slope_unidad = 2.0,
        float  $c7a_umbral_alta_slope_peso  = 1.0,
        float  $c7a_umbral_snr              = 0.15,
        bool   $c7a_cascada_exhaustiva      = false
    ): array {
        $subcasos_set    = array_flip($subcasos);
        $min_recepciones = 2;

        $fi     = $this->db->real_escape_string($fi_mov);
        $ff     = $this->db->real_escape_string($ff_mov);
        $fechaInicioStockEsc = $this->db->real_escape_string($fi_stock);
        $filtroFamiliasSql     = $this->repo->familiaWhere($familias_incluir, $familias_excluir);
        $filtroArticulosSql     = $this->repo->idsWhere($ids_filter);

        // ── Paso 1: recepciones en ventana extendida fi_stock→ff_mov ─────────
        $filasRecepciones = $this->repo->queryRecepcionesFechasC7($fechaInicioStockEsc, $ff, $filtroFamiliasSql, $filtroArticulosSql);
        if (isset($filasRecepciones['error'])) return $filasRecepciones;
        if (empty($filasRecepciones)) return [];

        $recepciones_map = [];
        $cantidades_map  = [];
        foreach ($filasRecepciones as $fila) {
            $aid = (int)$fila['idArticulo'];
            $recepciones_map[$aid][]             = $fila['fecha'];
            $cantidades_map[$aid][$fila['fecha']]   = (float)$fila['cantidad'];
        }

        $candidatos_ids = [];
        foreach ($recepciones_map as $id => $fechas) {
            $fechas_unicas = array_values(array_unique($fechas));
            sort($fechas_unicas);
            if (count($fechas_unicas) >= $min_recepciones) {
                $recepciones_map[$id] = $fechas_unicas;
                $candidatos_ids[]     = $id;
            } else {
                unset($recepciones_map[$id]);
            }
        }
        if (empty($candidatos_ids)) return [];

        // ── Paso 2: timeline + tipos de artículo ─────────────────────────────
        $idsArticulosCsv = implode(',', array_map('intval', $candidatos_ids));

        $tipos_map = [];
        $smt_tipo = $this->db->query(
            "SELECT idArticulo, tipo FROM articulos WHERE idArticulo IN ($idsArticulosCsv)"
        );
        if ($smt_tipo) {
            while ($fila = $smt_tipo->fetch_assoc()) {
                $tipos_map[(int)$fila['idArticulo']] = (string)$fila['tipo'];
            }
        }

        $filasTimeline = $this->repo->queryTimelineMovimientosC7($fechaInicioStockEsc, $ff, $idsArticulosCsv);
        if (isset($filasTimeline['error'])) return $filasTimeline;

        $daily_map = [];
        foreach ($filasTimeline as $fila) {
            $daily_map[(int)$fila['idArticulo']][$fila['fecha']] = (float)$fila['day_delta'];
        }

        $has_albcli_ids = $this->repo->queryHasAlbcliC7($fechaInicioStockEsc, $ff, $idsArticulosCsv);
        if (isset($has_albcli_ids['error'])) $has_albcli_ids = [];

        // ── Paso 3: analizar por artículo ─────────────────────────────────────
        $incidencias   = [];
        $ping_cada_n   = 20;
        $ping_contador = 0;

        foreach ($candidatos_ids as $id) {
            if (++$ping_contador % $ping_cada_n === 0) {
                try { $this->db->ping(); } catch (\mysqli_sql_exception $e) {}
            }

            $fechas_rec = $recepciones_map[$id];
            $daily      = $daily_map[$id] ?? [];
            $n_rec      = count($fechas_rec);

            // Stock acumulado fi_stock→ff_mov
            $cum_delta     = 0.0;
            $stock_by_date = [];
            $all_dates     = array_keys($daily);
            sort($all_dates);
            foreach ($all_dates as $d) {
                $cum_delta        += $daily[$d];
                $stock_by_date[$d] = $cum_delta;
            }
            if (empty($stock_by_date)) continue;

            // Suelos inter-recepción y duraciones
            $floors          = [];
            $dias_intervalos = [];
            $fechas_floors   = [];
            for ($i = 0; $i < $n_rec; $i++) {
                $fecha_ini      = $fechas_rec[$i];
                $fecha_fin      = ($i + 1 < $n_rec) ? $fechas_rec[$i + 1] : null;
                $fecha_fin_real = $fecha_fin ?? $ff_mov;
                $dias_intervalo = max(1, (int)(
                    (strtotime($fecha_fin_real) - strtotime($fecha_ini)) / 86400
                ));
                $min_floor = null;
                foreach ($stock_by_date as $d => $stock) {
                    if ($d < $fecha_ini) continue;
                    if ($fecha_fin !== null && $d >= $fecha_fin) continue;
                    if ($min_floor === null || $stock < $min_floor) $min_floor = $stock;
                }
                if ($min_floor !== null) {
                    $floors[]          = $min_floor;
                    $dias_intervalos[] = $dias_intervalo;
                    $fechas_floors[]   = $fecha_ini;
                }
            }
            $n_floors = count($floors);
            if ($n_floors < $min_recepciones) continue;

            $floors_map = [];
            $ts_map     = [];
            for ($i = 0; $i < $n_floors; $i++) {
                $floors_map[$fechas_floors[$i]] = $floors[$i];
                $ts_map[$fechas_floors[$i]]     = strtotime($fechas_floors[$i]);
            }

            // Split base / análisis
            $floors_base     = [];
            $dias_base       = [];
            $floors_analysis = [];
            $dias_analysis   = [];
            foreach ($fechas_floors as $idx => $fd) {
                if ($fd < $fi_mov) {
                    $floors_base[]   = $floors[$idx];
                    $dias_base[]     = $dias_intervalos[$idx];
                } else {
                    $floors_analysis[] = $floors[$idx];
                    $dias_analysis[]   = $dias_intervalos[$idx];
                }
            }
            $n_base     = count($floors_base);
            $n_analysis = count($floors_analysis);

            $tipo_art = $tipos_map[$id] ?? 'unidad';
            $mean_raw = array_sum($floors) / $n_floors;

            // Normalizar todos los floors (C7a-001 / C7b-002)
            $dias_intervalo_medio_all = array_sum($dias_intervalos) / $n_floors;
            $floors_norm_all          = [];
            for ($i = 0; $i < $n_floors; $i++) {
                $floors_norm_all[] = $floors[$i] / $dias_intervalos[$i];
            }

            $delta_total = $floors[$n_floors - 1] - $floors[0];

            // ── C7a ───────────────────────────────────────────────────────────
            if (isset($subcasos_set['C7a']) && $mean_raw >= 0 && $delta_total >= (($tipo_art === 'peso') ? $c7a_umbral_delta_peso : $c7a_umbral_delta_unidad)) {
                $result_c7a = $this->c7a->analizarCascada(
                    $floors_norm_all,
                    $delta_total,
                    $tipo_art,
                    $c7a_umbral_delta_unidad,
                    $c7a_umbral_delta_peso,
                    $c7a_umbral_pvalue,
                    $c7a_umbral_pvalue_alta,
                    $c7a_cascada_exhaustiva
                );

                if ($result_c7a !== null) {
                    $confianza      = $result_c7a['confianza'];
                    $subcaso_c7a    = ($confianza === 'posible') ? 'C7a_posible' : 'C7a';

                    // Theil-Sen sobre floors brutos (para tendencia_visible en la UI)
                    $ts_raw = [];
                    for ($i = 0; $i < $n_floors; $i++) {
                        for ($j = $i + 1; $j < $n_floors; $j++) {
                            $ts_raw[] = ($floors[$j] - $floors[$i]) / ($j - $i);
                        }
                    }
                    sort($ts_raw);
                    $n_ts_r           = count($ts_raw);
                    $beta_ts_raw      = $n_ts_r > 0
                        ? ($n_ts_r % 2 === 1 ? $ts_raw[intdiv($n_ts_r, 2)] : ($ts_raw[$n_ts_r / 2 - 1] + $ts_raw[$n_ts_r / 2]) / 2.0)
                        : 0.0;
                    $tendencia_visible = $beta_ts_raw;

                    // Dispersión bruta
                    $variance_raw = 0.0;
                    foreach ($floors as $f) $variance_raw += ($f - $mean_raw) ** 2;
                    $std_dev_raw  = $n_floors > 1 ? sqrt($variance_raw / ($n_floors - 1)) : 0.0;
                    $snr_c7a      = ($std_dev_raw > 0.0) ? abs($tendencia_visible) / $std_dev_raw : PHP_FLOAT_MAX;

                    // Cobertura temporal (base vs análisis)
                    $test_period  = $this->calcularTestPeriodC7a($floors_base, $dias_base, $floors_analysis, $dias_analysis, $n_base, $n_analysis);
                    $analysis_consistent = ($n_base > 0)
                        ? ($this->theilSenSlope($floors_base, $dias_base) > 0.0)
                        : null;

                    $tendencia_reciente = $this->calcularTendenciaRecienteC7a(
                        $test_period, $n_analysis, $floors_analysis, $dias_analysis, $analysis_consistent
                    );

                    // Severidad
                    $severidad = $this->c7a->calcularSeveridad(
                        $confianza,
                        $test_period,
                        $delta_total,
                        $tendencia_visible,
                        ($subcaso_c7a === 'C7a_posible'),
                        $tipo_art,
                        $c7a_umbral_alta_delta_unidad,
                        $c7a_umbral_alta_delta_peso,
                        $c7a_umbral_alta_slope_unidad,
                        $c7a_umbral_alta_slope_peso
                    );

                    // Análisis Spearman / ratio para detectar compra con stock
                    [$compra_con_stock, $comprador_ajusta, $r_spearman, $ratio_mediano] =
                        $this->analizarEsperaC7a($floors, $fechas_floors, $cantidades_map[$id] ?? [], $n_floors);

                    $tiene_albcli = isset($has_albcli_ids[$id]);
                    $posible_causa = $this->causaC7a(
                        $tendencia_reciente, $comprador_ajusta, $compra_con_stock,
                        $tiene_albcli, $tendencia_visible, $delta_total, $tipo_art
                    );

                    $beta_ts_base_val     = $this->theilSenSlope($floors_base, $dias_base);
                    $beta_ts_analysis_val = $this->theilSenSlope($floors_analysis, $dias_analysis);

                    $incidencias[] = [
                        'idArticulo'       => $id,
                        'tipo'             => 'Merma acumulada',
                        'severidad'        => $severidad,
                        'c7_subcaso'       => $subcaso_c7a,
                        'confianza'        => $confianza,
                        'cascade_nivel'    => $result_c7a['cascade_nivel'],
                        'n_recepciones'    => $n_rec,
                        'offset_estimado'  => round($mean_raw, 1),
                        'dispersion'       => round($std_dev_raw, 1),
                        'tendencia'        => round($tendencia_visible, 1),
                        'tendencia_norm'   => round($result_c7a['beta_ts'], 4),
                        'autocorr_lag1'    => round($result_c7a['autocorr_lag1'], 2),
                        'p_mk'             => $result_c7a['p_mk'] !== null ? round($result_c7a['p_mk'], 4) : null,
                        'tau_mk'           => null,
                        'snr'              => round($snr_c7a, 3),
                        'cascade_fallback_reason' => $result_c7a['fallback_reason'],
                        'test_period'         => $test_period,
                        'tendencia_reciente'  => $tendencia_reciente,
                        'analysis_consistent' => $analysis_consistent,
                        'cobertura'           => $test_period ?? 'analysis',
                        'n_base'              => $n_base,
                        'n_analysis'          => $n_analysis,
                        'slope_base'       => $beta_ts_base_val     !== null ? round($beta_ts_base_val     * $dias_intervalo_medio_all, 1) : null,
                        'slope_analysis'   => $beta_ts_analysis_val !== null ? round($beta_ts_analysis_val * $dias_intervalo_medio_all, 1) : null,
                        'delta_acumulado'  => round($delta_total, 1),
                        'fecha_primera'    => $fechas_rec[0],
                        'fecha_ultima'     => $fechas_rec[$n_rec - 1],
                        '_floors_raw'      => $floors_map,
                        '_ts_floors'       => $ts_map,
                        'posible_causa'    => $posible_causa,
                        'tiene_albcli'     => $tiene_albcli,
                        'r_spearman'       => $r_spearman !== null ? round($r_spearman, 3) : null,
                        'ratio_mediano'    => $ratio_mediano !== null ? round($ratio_mediano, 3) : null,
                    ];
                }
            } // end C7a

            // ── C7b ───────────────────────────────────────────────────────────
            // Seleccionar conjunto de test (base o análisis)
            if (isset($subcasos_set['C7b'])) {
                $test_floors = null;
                $test_dias   = null;
                $test_period = null;
                if ($n_base >= $c7b_min_recepciones) {
                    $test_floors = $floors_base;
                    $test_dias   = $dias_base;
                    $test_period = 'base';
                } elseif ($n_analysis >= $c7b_min_recepciones) {
                    $test_floors = $floors_analysis;
                    $test_dias   = $dias_analysis;
                    $test_period = 'analysis';
                }

                $abs_mean_raw = abs($mean_raw);

                if ($test_floors !== null) {
                    // Filtro ruido pesaje (antes de la cascada para artículos tipo peso)
                    if ($tipo_art === 'peso' && $abs_mean_raw < $c7b_umbral_ruido_peso) {
                        $incidencias[] = [
                            'idArticulo'           => $id,
                            'tipo'                 => 'Posible error de pesaje',
                            'severidad'            => 'BAJA',
                            'c7_subcaso'           => 'C7b_ruido_peso',
                            'tipo_articulo'        => $tipo_art,
                            'n_recepciones'        => $n_rec,
                            'offset_estimado'      => round($mean_raw, 2),
                            'dias_intervalo_medio' => (int)round(array_sum($dias_intervalos) / $n_floors),
                            'fecha_primera'        => $fechas_rec[0],
                            'fecha_ultima'         => $fechas_rec[$n_rec - 1],
                            '_floors_raw'          => $floors_map,
                            '_ts_floors'           => $ts_map,
                            'posible_causa'        => sprintf(
                                'El stock aparece %.2f kg en negativo, pero es demasiado pequeño para ser un error real — probablemente es acumulación de decimales de balanza.',
                                $abs_mean_raw
                            ),
                        ];
                        continue;
                    }

                    $result_c7b = $this->c7b->analizarCascada(
                        $test_floors,
                        $test_dias,
                        $abs_mean_raw,
                        $tipo_art,
                        $c7b_umbral_cv,
                        $c7b_umbral_cv_peso,
                        $c7b_umbral_iqr_peso,
                        $c7b_cascada_exhaustiva
                    );

                    if ($result_c7b !== null) {
                        $confianza_c7b = $result_c7b['confianza'];

                        $n_neg   = count(array_filter($floors, fn($f) => $f < 0.0));
                        $pct_neg = (int)round($n_neg / $n_floors * 100);

                        $cobertura = 'C';
                        if ($test_period === 'base') {
                            $n_analysis_neg      = count(array_filter($floors_analysis, fn($f) => $f < 0.0));
                            $analysis_consistent = $n_analysis >= 2 && $n_analysis_neg === $n_analysis;
                            $cobertura           = $analysis_consistent ? 'A' : 'B';
                        } else {
                            $analysis_consistent = false;
                        }

                        $tendencia_reciente = $this->calcularTendenciaRecienteC7b(
                            $test_period, $n_analysis, $floors_analysis, $analysis_consistent ?? false
                        );

                        $severidad = $this->c7b->calcularSeveridad(
                            $confianza_c7b,
                            $cobertura,
                            $abs_mean_raw,
                            $pct_neg,
                            $tipo_art,
                            $c7b_umbral_sev_unidad,
                            $c7b_umbral_sev_peso
                        );

                        $causa_texto = $this->causaC7b($tendencia_reciente, $abs_mean_raw, $tipo_art);

                        $incidencias[] = [
                            'idArticulo'               => $id,
                            'tipo'                     => 'Entrada no registrada',
                            'severidad'                => $severidad,
                            'c7_subcaso'               => 'C7b',
                            'confianza'                => $confianza_c7b,
                            'test_period'              => $test_period,
                            'analysis_consistent'      => $analysis_consistent ?? false,
                            'tendencia_reciente'       => $tendencia_reciente,
                            'tipo_articulo'            => $tipo_art,
                            'n_recepciones'            => $n_rec,
                            'offset_estimado'          => round($mean_raw, 1),
                            'offset_norm'              => round(array_sum($test_floors) / count($test_floors) / max(1, (int)round(array_sum($test_dias) / count($test_dias))), 3),
                            'dispersion'               => round(0.0, 3), // overwritten below if needed
                            'autocorr_lag1'            => $result_c7b['autocorr_lag1'],
                            'ic95_upper'               => $result_c7b['ic95_upper'],
                            'cv_duraciones'            => $result_c7b['cv_duraciones'],
                            'test_type'                => $result_c7b['test_type'],
                            'test_pvalue'              => $result_c7b['test_pvalue'],
                            'test_fallback_reason'     => null,
                            'n_intervalos_negativos'   => $n_neg,
                            'pct_intervalos_negativos' => $pct_neg,
                            'dias_intervalo_medio'     => (int)round(array_sum($dias_intervalos) / $n_floors),
                            'fecha_primera'            => $fechas_rec[0],
                            'fecha_ultima'             => $fechas_rec[$n_rec - 1],
                            '_floors_raw'              => $floors_map,
                            '_ts_floors'               => $ts_map,
                            'posible_causa'            => $causa_texto,
                        ];
                    }
                } elseif ($n_analysis === 2 && $n_analysis > 0) {
                    // C7b-010: C7b_posible — solo 2 floors en análisis
                    $f0     = $floors_analysis[0];
                    $f1     = $floors_analysis[1];
                    $mean_2 = ($f0 + $f1) / 2.0;
                    $cv_2   = $mean_2 != 0.0 ? abs($f0 - $f1) / (2.0 * abs($mean_2)) : PHP_FLOAT_MAX;
                    if ($f0 < 0 && $f1 < 0 && $cv_2 < 0.15) {
                        $abs_med_2 = abs($mean_2);
                        if (!($tipo_art === 'peso' && $abs_med_2 < 0.5)) {
                            $dias_med_2  = (int)round(array_sum($dias_analysis) / 2);
                            $date_keys_2 = array_slice(array_keys($floors_map), -2);
                            $incidencias[] = [
                                'idArticulo'               => $id,
                                'tipo'                     => 'Entrada no registrada',
                                'severidad'                => 'BAJA',
                                'c7_subcaso'               => 'C7b_posible',
                                'confianza'                => 'posible',
                                'tipo_articulo'            => $tipo_art,
                                'n_recepciones'            => $n_rec,
                                'offset_estimado'          => round($mean_2, 1),
                                'offset_norm'              => round($mean_2 / max(1, $dias_med_2), 3),
                                'dispersion'               => round(abs($f0 - $f1) / 2.0, 3),
                                'autocorr_lag1'            => 0.0,
                                'ic95_upper'               => null,
                                'n_intervalos_negativos'   => 2,
                                'pct_intervalos_negativos' => 100,
                                'dias_intervalo_medio'     => $dias_med_2,
                                'fecha_primera'            => $date_keys_2[0] ?? $fechas_rec[0],
                                'fecha_ultima'             => $date_keys_2[1] ?? $fechas_rec[$n_rec - 1],
                                '_floors_raw'              => $floors_map,
                                '_ts_floors'               => $ts_map,
                                'posible_causa'            => sprintf(
                                    'El stock cae ~%d %s en negativo en las 2 recepciones del periodo. Podría faltar un albarán, aunque con solo 2 datos no es posible confirmarlo — conviene revisar manualmente.',
                                    (int)round($abs_med_2),
                                    $tipo_art === 'peso' ? 'kg' : 'ud.'
                                ),
                            ];
                        }
                    }
                }
            } // end C7b
        } // foreach candidatos

        // ── C7a-004: enriquecer C7a con coste y proveedor ────────────────────
        if (isset($subcasos_set['C7a'])) {
            $idsC7aEnriquecer = array_column(
                array_filter($incidencias, fn($inc) => in_array($inc['c7_subcaso'] ?? '', ['C7a','C7a_posible'], true)),
                'idArticulo'
            );
            if (!empty($idsC7aEnriquecer)) {
                $fechaInicioStockEsc = $this->db->real_escape_string($fi_stock);
                $idsC7aCsv  = implode(',', array_map('intval', $idsC7aEnriquecer));
                $prov_map     = $this->repo->queryProveedorArticulos($idsC7aCsv, $fechaInicioStockEsc, $ff);
                $precio_map   = $this->repo->queryPrecioMedioCompra($idsC7aCsv, $fechaInicioStockEsc, $ff);
                foreach ($incidencias as &$inc) {
                    $sub = $inc['c7_subcaso'] ?? '';
                    if ($sub !== 'C7a' && $sub !== 'C7a_posible') continue;
                    $prov = $prov_map[$inc['idArticulo']] ?? null;
                    $inc['prov_habitual_nombre'] = $prov['prov_habitual_nombre'] ?? null;
                    $inc['prov_habitual_n']      = $prov['prov_habitual_n']      ?? null;
                    $inc['prov_ultimo_nombre']   = $prov['prov_ultimo_nombre']   ?? null;
                    $inc['prov_ultima_fecha']    = $prov['prov_ultima_fecha']    ?? null;
                    $inc['prov_es_mismo']        = $prov['prov_es_mismo']        ?? null;
                    $precio = $precio_map[$inc['idArticulo']] ?? null;
                    $inc['precio_medio_compra']  = $precio;
                    $inc['coste_estimado_merma'] = ($precio !== null)
                        ? round((float)$inc['delta_acumulado'] * $precio, 2)
                        : null;
                }
                unset($inc);
            }
        }

        // ── C7b-007: enriquecer C7b con coste y proveedor ────────────────────
        if (isset($subcasos_set['C7b'])) {
            $ids_c7b = array_column(
                array_filter($incidencias, fn($inc) => str_starts_with($inc['c7_subcaso'] ?? '', 'C7b')),
                'idArticulo'
            );
            if (!empty($ids_c7b)) {
                $fechaInicioStockEsc = $this->db->real_escape_string($fi_stock);
                $idsC7bCsv  = implode(',', array_map('intval', $ids_c7b));
                $prov_map     = $this->repo->queryProveedorArticulos($idsC7bCsv, $fechaInicioStockEsc, $ff);
                $precio_map   = $this->repo->queryPrecioMedioCompra($idsC7bCsv, $fechaInicioStockEsc, $ff);
                foreach ($incidencias as &$inc) {
                    if (!str_starts_with($inc['c7_subcaso'] ?? '', 'C7b')) continue;
                    $prov = $prov_map[$inc['idArticulo']] ?? null;
                    $inc['prov_habitual_nombre'] = $prov['prov_habitual_nombre'] ?? null;
                    $inc['prov_habitual_n']      = $prov['prov_habitual_n']      ?? null;
                    $inc['prov_ultimo_nombre']   = $prov['prov_ultimo_nombre']   ?? null;
                    $inc['prov_ultima_fecha']    = $prov['prov_ultima_fecha']    ?? null;
                    $inc['prov_es_mismo']        = $prov['prov_es_mismo']        ?? null;
                    $precio = $precio_map[$inc['idArticulo']] ?? null;
                    $inc['precio_medio_compra'] = $precio;
                    $inc['coste_estimado']      = ($precio !== null)
                        ? round(abs((float)$inc['offset_estimado']) * $precio, 2)
                        : null;
                }
                unset($inc);
            }
        }

        // ── C7c/d/e ───────────────────────────────────────────────────────────
        if (!$skip_cde && isset($subcasos_set['C7a']) && isset($subcasos_set['C7b'])) {
            $idsC7TodosCsv  = implode(',', array_unique(array_column($incidencias, 'idArticulo')));
            if (!empty($idsC7TodosCsv)) {
                $meta_c7c = $this->repo->queryMetaC7c($idsC7TodosCsv);
                $this->c7cde->detectarCrucesPares($incidencias, $meta_c7c);
                $this->c7cde->detectarMultiplos($incidencias, $meta_c7c);
                $this->c7cde->detectarTrios($incidencias);
            }
        }

        // Limpiar campos internos
        foreach ($incidencias as &$inc) {
            unset($inc['_floors_raw'], $inc['_ts_floors']);
        }
        unset($inc);

        return $incidencias;
    }

    /**
     * Fase 2 de C7: resuelve cruces C7c/d/e sobre el conjunto completo.
     */
    public function resolverC7cde(array $params, array $ids_c7a, array $ids_c7b): array
    {
        $ids_c7a = array_values(array_unique(array_map('intval', $ids_c7a)));
        $ids_c7b = array_values(array_unique(array_map('intval', $ids_c7b)));
        $ids_all = array_values(array_unique(array_merge($ids_c7a, $ids_c7b)));
        if (empty($ids_all)) return ['cruces' => []];

        $fi_mov   = $params['fecha_inicio_movimientos'];
        $ff_mov   = $params['fecha_fin_movimientos'];
        $fi_stock = $params['fecha_inicio_stock'];
        $familias_incluir = (array)($params['familias_incluir'] ?? []);
        $familias_excluir = (array)($params['familias_excluir'] ?? []);

        $fechaInicioStockEsc = $this->db->real_escape_string($fi_stock);
        $ff     = $this->db->real_escape_string($ff_mov);
        $filtroFamiliasSql     = $this->repo->familiaWhere($familias_incluir, $familias_excluir);
        $filtroArticulosSql     = $this->repo->idsWhere($ids_all);

        $filasRecepciones = $this->repo->queryRecepcionesFechasC7($fechaInicioStockEsc, $ff, $filtroFamiliasSql, $filtroArticulosSql);
        if (isset($filasRecepciones['error'])) return $filasRecepciones;
        if (empty($filasRecepciones)) return ['cruces' => []];

        $recepciones_map = [];
        foreach ($filasRecepciones as $fila) {
            $recepciones_map[(int)$fila['idArticulo']][] = $fila['fecha'];
        }
        foreach ($recepciones_map as $id => $fechas) {
            $fechasUnicas = array_values(array_unique($fechas));
            sort($fechasUnicas);
            $recepciones_map[$id] = $fechasUnicas;
        }

        $idsArticulosCsv   = implode(',', $ids_all);
        $filasTimeline   = $this->repo->queryTimelineMovimientosC7($fechaInicioStockEsc, $ff, $idsArticulosCsv);
        if (isset($filasTimeline['error'])) return $filasTimeline;

        $daily_map = [];
        foreach ($filasTimeline as $fila) {
            $daily_map[(int)$fila['idArticulo']][$fila['fecha']] = (float)$fila['day_delta'];
        }

        // Construir incidencias mínimas para C7c/d/e (sin cascada estadística)
        $ids_c7a_set     = array_flip($ids_c7a);
        $ids_c7b_set     = array_flip($ids_c7b);
        $incidencias_cde = [];

        foreach ($ids_all as $id) {
            if (!isset($recepciones_map[$id])) continue;
            $fechas_rec = $recepciones_map[$id];
            $n_rec      = count($fechas_rec);
            $daily      = $daily_map[$id] ?? [];

            $cum_delta     = 0.0;
            $stock_by_date = [];
            $all_dates     = array_keys($daily);
            sort($all_dates);
            foreach ($all_dates as $d) {
                $cum_delta        += $daily[$d];
                $stock_by_date[$d] = $cum_delta;
            }
            if (empty($stock_by_date)) continue;

            $floors         = [];
            $fechas_floors  = [];
            for ($i = 0; $i < $n_rec; $i++) {
                $fecha_ini = $fechas_rec[$i];
                $fecha_fin = ($i + 1 < $n_rec) ? $fechas_rec[$i + 1] : null;
                $min_floor = null;
                foreach ($stock_by_date as $d => $stock) {
                    if ($d < $fecha_ini) continue;
                    if ($fecha_fin !== null && $d >= $fecha_fin) continue;
                    if ($min_floor === null || $stock < $min_floor) $min_floor = $stock;
                }
                if ($min_floor !== null) {
                    $floors[]        = $min_floor;
                    $fechas_floors[] = $fecha_ini;
                }
            }
            $n_floors = count($floors);
            if ($n_floors < 2) continue;

            $floors_map = [];
            $ts_map     = [];
            for ($i = 0; $i < $n_floors; $i++) {
                $fecha = $fechas_floors[$i];
                $floors_map[$fecha] = $floors[$i];
                $ts_map[$fecha]     = strtotime($fecha);
            }

            $mean_raw = array_sum($floors) / $n_floors;
            $var_raw  = 0.0;
            foreach ($floors as $f) $var_raw += ($f - $mean_raw) ** 2;
            $std_raw  = $n_floors > 1 ? sqrt($var_raw / ($n_floors - 1)) : 0.0;

            if (isset($ids_c7a_set[$id])) {
                $subcaso = 'C7a';
            } elseif (isset($ids_c7b_set[$id])) {
                $subcaso = 'C7b';
            } else {
                continue;
            }

            $incidencias_cde[] = [
                'idArticulo'      => $id,
                'c7_subcaso'      => $subcaso,
                '_floors_raw'     => $floors_map,
                '_ts_floors'      => $ts_map,
                'offset_estimado' => round($mean_raw, 1),
                'dispersion'      => round($std_raw, 1),
                'posible_causa'   => '',
            ];
        }

        if (empty($incidencias_cde)) return ['cruces' => []];

        $idsC7cdeCsv = implode(',', array_unique(array_column($incidencias_cde, 'idArticulo')));
        $meta_c7c    = $this->repo->queryMetaC7c($idsC7cdeCsv);

        $this->c7cde->detectarCrucesPares($incidencias_cde, $meta_c7c);
        $this->c7cde->detectarMultiplos($incidencias_cde, $meta_c7c);
        $this->c7cde->detectarTrios($incidencias_cde);

        // Eliminar campos internos
        foreach ($incidencias_cde as &$inc) {
            unset($inc['_floors_raw'], $inc['_ts_floors']);
        }
        unset($inc);

        // Extraer solo las anotaciones de cruce
        $cruces = [];
        foreach ($incidencias_cde as $f) {
            if (!isset($f['posible_cruce_con'])) continue;
            $cruces[(int)$f['idArticulo']] = [
                'posible_cruce_con' => (int)$f['posible_cruce_con'],
                'cruce_score'       => $f['cruce_score']  ?? null,
                'cruce_nivel'       => $f['cruce_nivel']  ?? null,
                'posible_causa'     => $f['posible_causa'] ?? null,
            ];
        }

        return ['cruces' => $cruces];
    }

    // ═══════════════════════════════════════════════════════════════════
    // Helpers privados
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Calcula la mediana Theil-Sen de la pendiente sobre floors (normalizados o brutos).
     * Devuelve null si hay menos de 2 floors.
     */
    private function theilSenSlope(array $floors, array $dias): ?float
    {
        $n = count($floors);
        if ($n < 2) return null;
        // Normalizar por días
        $floors_n = [];
        for ($i = 0; $i < $n; $i++) {
            $floors_n[] = $floors[$i] / max(1.0, $dias[$i]);
        }
        $pairs = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $pairs[] = ($floors_n[$j] - $floors_n[$i]) / ($j - $i);
            }
        }
        if (empty($pairs)) return null;
        sort($pairs);
        $np = count($pairs);
        return $np % 2 === 1
            ? $pairs[intdiv($np, 2)]
            : ($pairs[$np / 2 - 1] + $pairs[$np / 2]) / 2.0;
    }

    /** Determina test_period C7a según pendentes en base vs análisis */
    private function calcularTestPeriodC7a(array $floorsBase, array $diasBase, array $floorsAnalysis, array $diasAnalysis, int $nBase, int $nAnalysis): ?string
    {
        $base_up     = ($nBase >= 2) ? ($this->theilSenSlope($floorsBase, $diasBase) > 0.0) : null;
        $analysis_up = ($nAnalysis >= 2) ? ($this->theilSenSlope($floorsAnalysis, $diasAnalysis) > 0.0) : null;
        if ($base_up !== null && $analysis_up !== null) {
            if ($base_up && $analysis_up)   return 'both';
            if ($base_up && !$analysis_up)  return 'base';
            if (!$base_up && $analysis_up)  return 'analysis';
        } elseif ($base_up !== null) {
            return $base_up ? 'base' : null;
        } elseif ($analysis_up !== null) {
            return $analysis_up ? 'analysis' : null;
        }
        return null;
    }

    /** Determina tendencia_reciente C7a */
    private function calcularTendenciaRecienteC7a(?string $test_period, int $n_analysis, array $floors_analysis, array $dias_analysis, ?bool $analysis_consistent): string
    {
        if ($test_period === 'base') {
            if ($analysis_consistent === true)  return 'activo';
            if ($n_analysis === 0)              return 'sin_datos';
            if (!empty($floors_analysis)) {
                $beta = $this->theilSenSlope($floors_analysis, $dias_analysis);
                if ($beta !== null && $beta < 0.0) return 'resuelto';
            }
            return 'mejorando';
        }
        return 'activo';
    }

    /** Determina tendencia_reciente C7b */
    private function calcularTendenciaRecienteC7b(?string $test_period, int $n_analysis, array $floors_analysis, bool $analysis_consistent): string
    {
        if ($test_period === 'base') {
            if ($analysis_consistent)   return 'activo';
            if ($n_analysis === 0)      return 'sin_datos';
            if (!empty($floors_analysis)) {
                $mean_analysis = array_sum($floors_analysis) / count($floors_analysis);
                if ($mean_analysis > 0.0) return 'resuelto';
            }
            return 'mejorando';
        }
        return 'activo';
    }

    /**
     * Análisis de correlación Spearman para detectar "compra con stock" en C7a.
     * @return array  [compra_con_stock, comprador_ajusta, r_spearman, ratio_mediano]
     */
    private function analizarEsperaC7a(array $floors, array $fechas_floors, array $cantidades_map, int $n_floors): array
    {
        if ($n_floors < 3) return [false, false, null, null];
        $cantidades_rec = [];
        foreach ($fechas_floors as $fd) $cantidades_rec[] = $cantidades_map[$fd] ?? 0.0;

        $x_floors_prev = [];
        $y_quant       = [];
        $ratios_cob    = [];
        for ($i = 1; $i < $n_floors; $i++) {
            $qty = $cantidades_rec[$i];
            if ($qty > 0.0 && $floors[$i - 1] >= 0.0) {
                $x_floors_prev[] = $floors[$i - 1];
                $y_quant[]       = $qty;
                $ratios_cob[]    = $floors[$i - 1] / $qty;
            }
        }
        $n_pares = count($ratios_cob);
        if ($n_pares < 2) return [false, false, null, null];

        $ratios_sorted = $ratios_cob;
        sort($ratios_sorted);
        $ratio_mediano = $n_pares % 2 === 1
            ? $ratios_sorted[intdiv($n_pares, 2)]
            : ($ratios_sorted[$n_pares / 2 - 1] + $ratios_sorted[$n_pares / 2]) / 2.0;

        $umbral_ratio = 0.3;
        $k_alto       = 0;
        foreach ($ratios_cob as $rv) { if ($rv > $umbral_ratio) $k_alto++; }
        $p_ratio  = 0.0;
        $bcoef    = 1.0;
        $phalf    = pow(0.5, $n_pares);
        for ($k = 0; $k <= $n_pares; $k++) {
            if ($k > 0) $bcoef *= ($n_pares - $k + 1) / $k;
            if ($k >= $k_alto) $p_ratio += $bcoef * $phalf;
        }
        $compra_con_stock = (min(1.0, $p_ratio) < 0.10);

        $r_spearman       = null;
        $comprador_ajusta = false;
        if ($n_pares >= 3) {
            $fn_rank = static function (array $arr): array {
                $n   = count($arr);
                $idx = range(0, $n - 1);
                usort($idx, static fn($a, $b) => $arr[$a] <=> $arr[$b]);
                $ranks = array_fill(0, $n, 0.0);
                for ($i = 0; $i < $n;) {
                    $j = $i;
                    while ($j < $n && $arr[$idx[$j]] === $arr[$idx[$i]]) $j++;
                    $avg = ($i + $j - 1) / 2.0 + 1.0;
                    for ($k = $i; $k < $j; $k++) $ranks[$idx[$k]] = $avg;
                    $i = $j;
                }
                return $ranks;
            };
            $rx = $fn_rank($x_floors_prev);
            $ry = $fn_rank($y_quant);
            $n_ = $n_pares;
            $mx = array_sum($rx) / $n_;
            $my = array_sum($ry) / $n_;
            $cov_sp = $vx_sp = $vy_sp = 0.0;
            for ($i = 0; $i < $n_; $i++) {
                $cov_sp += ($rx[$i] - $mx) * ($ry[$i] - $my);
                $vx_sp  += ($rx[$i] - $mx) ** 2;
                $vy_sp  += ($ry[$i] - $my) ** 2;
            }
            $denom = sqrt($vx_sp * $vy_sp);
            $r_spearman = $denom > 0.0 ? $cov_sp / $denom : 0.0;
            $comprador_ajusta = ($r_spearman < -0.3);
        }

        return [$compra_con_stock, $comprador_ajusta, $r_spearman, $ratio_mediano];
    }

    /** Genera posible_causa para C7a según tendencia_reciente y contexto */
    private function causaC7a(string $tr, bool $comprador_ajusta, bool $compra_con_stock, bool $tiene_albcli, float $tendencia_visible, float $delta_total, string $tipo_art): string
    {
        $unidad = ($tipo_art === 'peso') ? 'kg' : 'ud.';
        $prefijo = sprintf(
            'El suelo mínimo sube ~%s %s por recepción (%s %s acumulados). ',
            number_format(abs($tendencia_visible), 1, '.', ''),
            $unidad,
            number_format(abs($delta_total), 1, '.', ''),
            $unidad
        );
        switch ($tr) {
            case 'resuelto':
                $causa = $prefijo . 'Tendencia histórica no confirmada en el período reciente. Verificar si se realizó un ajuste de inventario o corrección de merma.';
                break;
            case 'mejorando':
                $causa = $prefijo . 'La tendencia puede estar reduciéndose — pendiente reciente sin confirmación estadística. Monitorizar en el próximo informe.';
                break;
            case 'sin_datos':
                $causa = $prefijo . 'Sin recepciones en el período de análisis — no es posible confirmar si la merma sigue activa. Revisar entradas pendientes.';
                break;
            default: // activo
                if ($comprador_ajusta) {
                    $causa = $prefijo . 'El comprador reduce los pedidos cuando el stock es alto — menos probable merma sistemática. Posible política de buffer o entrega en calendario fijo.';
                } elseif ($compra_con_stock) {
                    $causa = $prefijo . 'Stock sistemáticamente alto antes de cada recepción — posible compra anticipada o entrega en calendario fijo. Verificar si existe merma real no registrada.';
                } else {
                    $causa = $prefijo . 'Posible merma, caducidad no registrada o salida sin documentar.';
                }
        }
        if ($tiene_albcli) {
            $causa .= ' · Hay albaranes de cliente en el período: verificar consumo interno o albaranes pendientes.';
        }
        return $causa;
    }

    /** Genera posible_causa para C7b según tendencia_reciente */
    private function causaC7b(string $tr, float $abs_mean_raw, string $tipo_art): string
    {
        $unidad = $tipo_art === 'peso' ? 'kg' : 'ud.';
        switch ($tr) {
            case 'resuelto':
                return sprintf(
                    'El stock caía ~%d %s en negativo de forma repetida, pero el período reciente no muestra el patrón. Verificar que el albarán pendiente fue registrado o que el error se corrigió.',
                    (int)round($abs_mean_raw), $unidad
                );
            case 'mejorando':
                return sprintf(
                    'El stock caía ~%d %s en negativo de forma repetida. El período reciente tiene floors negativos pero sin confirmación estadística — el problema puede estar reduciéndose. Monitorizar en próximo informe.',
                    (int)round($abs_mean_raw), $unidad
                );
            case 'sin_datos':
                return sprintf(
                    'El stock caía ~%d %s en negativo de forma repetida. Sin recepciones en el período reciente — no es posible confirmar si el problema sigue activo. Revisar albaranes pendientes.',
                    (int)round($abs_mean_raw), $unidad
                );
            default:
                return sprintf(
                    'El stock cae ~%d %s en negativo de forma repetida entre cada recepción. Revisar si hay albaranes pendientes de confirmar o si el stock inicial del artículo está bien introducido.',
                    (int)round($abs_mean_raw), $unidad
                );
        }
    }
}
