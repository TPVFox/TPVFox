<?php

/**
 * PosstockC7aAnalyzer — Analizador estadístico de C7a: merma sistemática no registrada.
 *
 * Extraído de ClasePosstock como parte de la Fase 5 de refactorización.
 *
 * Detecta artículos cuyo suelo de stock post-recepción muestra una pendiente positiva
 * acumulada (floors en alza), indicando merma o caducidad no registrada.
 *
 * Métodos públicos:
 *   analizarCascada(...)  — cascada 4 niveles sobre floors normalizados
 *   calcularSeveridad(...)— tabla severidad 2D + modulador magnitud
 */

require_once __DIR__ . '/PosstockStatistics.php';

class PosstockC7aAnalyzer
{
    /**
     * Corre la cascada estadística de 4 niveles sobre floors normalizados (ud./día).
     *
     * Pre-condición del caller:
     *   · mean_raw >= 0  (floors positivos en media → C7a; negativos → C7b)
     *   · floors_norm contiene al menos los valores de todos los intervalos
     *
     * Niveles:
     *   1. OLS + Newey-West IC95  (n >= 10, se > 0)  → 'alta'
     *   2. Bootstrap Theil-Sen IC99 (n >= 8)          → 'media'
     *   3. Mann-Kendall + Hamed & Rao (n ∈ [4,20])    → 'posible'
     *   4. Test de signo binomial (fallback)           → 'posible'
     *
     * @param array  $floors_norm         Floors normalizados por duración (floor / dias_intervalo)
     * @param float  $delta_total         floors_raw[-1] − floors_raw[0]  (de floors sin normalizar)
     * @param string $tipo_art            'unidad' | 'peso'
     * @param float  $umbral_delta_unidad Variación acumulada mínima para artículos unidad
     * @param float  $umbral_delta_peso   Variación acumulada mínima para artículos peso
     * @param float  $umbral_pvalue       p-valor máximo para Nivel 3 (MK); por encima → no C7a
     * @param float  $umbral_pvalue_alta  p-valor para confianza 'alta' en Nivel 3
     * @param bool   $cascada_exhaustiva  true = continuar a niveles inferiores aunque fallen
     *
     * @return array{confianza: 'alta'|'media'|'posible', cascade_nivel: int,
     *               p_mk: float|null, fallback_reason: string, beta_ts: float,
     *               autocorr_lag1: float}|null
     *         null si ningún nivel confirma C7a (incluye pre-filtros fallidos)
     */
    public function analizarCascada(
        array  $floors_norm,
        float  $delta_total,
        string $tipo_art              = 'unidad',
        float  $umbral_delta_unidad   = 2.0,
        float  $umbral_delta_peso     = 1.0,
        float  $umbral_pvalue         = 0.10,
        float  $umbral_pvalue_alta    = 0.05,
        bool   $cascada_exhaustiva    = false
    ): ?array {
        $n_floors = count($floors_norm);
        if ($n_floors < 3) return null;

        $umbral_delta = ($tipo_art === 'peso') ? $umbral_delta_peso : $umbral_delta_unidad;
        if ($delta_total < $umbral_delta) return null;

        // ── Theil-Sen slope sobre floors normalizados ─────────────────────────
        $ts_pairs = [];
        for ($i = 0; $i < $n_floors; $i++) {
            for ($j = $i + 1; $j < $n_floors; $j++) {
                $ts_pairs[] = ($floors_norm[$j] - $floors_norm[$i]) / ($j - $i);
            }
        }
        sort($ts_pairs);
        $n_ts    = count($ts_pairs);
        $beta_ts = $n_ts > 0
            ? ($n_ts % 2 === 1
                ? $ts_pairs[intdiv($n_ts, 2)]
                : ($ts_pairs[$n_ts / 2 - 1] + $ts_pairs[$n_ts / 2]) / 2.0)
            : 0.0;

        if ($beta_ts <= 0.0) return null;

        // ── Pre-calcular OLS y autocorrelación lag-1 ──────────────────────────
        $reg        = PosstockStatistics::regressionStats($floors_norm);
        $slope_norm = $reg['slope'];
        $se_corr    = $reg['se'];
        $autocorr_lag1 = 0.0;

        if ($n_floors >= 4 && $reg['se'] > 0.0) {
            $mean_n   = array_sum($floors_norm) / $n_floors;
            $cov_lag1 = 0.0;
            $var_n    = 0.0;
            for ($i = 1; $i < $n_floors; $i++) {
                $cov_lag1 += ($floors_norm[$i] - $mean_n) * ($floors_norm[$i - 1] - $mean_n);
            }
            foreach ($floors_norm as $fnv) {
                $var_n += ($fnv - $mean_n) ** 2;
            }
            $cov_lag1 /= ($n_floors - 1);
            $var_n    /= ($n_floors - 1);
            if ($var_n > 1e-12) {
                $r1 = max(-1.0, min(1.0, $cov_lag1 / $var_n));
                if ($r1 > 0.0) {
                    $autocorr_lag1 = $r1;
                    $se_corr       = $reg['se'] * sqrt(1.0 + 2.0 * $r1);
                }
            }
        }

        // ── Estado de la cascada ──────────────────────────────────────────────
        $c7a_confianza       = null;
        $c7a_cascade_nivel   = 0;
        $p_mk_c7a            = null;
        $fallback_reason     = '';
        $cascade_nivel_act   = 1;

        static $t_tab_nw = [
            1 => 6.314,
            2 => 2.920,
            3 => 2.353,
            4 => 2.132,
            5 => 2.015,
            6 => 1.943,
            7 => 1.895,
            8 => 1.860,
            9 => 1.833,
            10 => 1.812,
            15 => 1.753,
            20 => 1.725,
            30 => 1.697,
            60 => 1.671,
            120 => 1.658,
        ];

        while ($cascade_nivel_act > 0 && $c7a_confianza === null) {

            // ═══════════════════════════════════════════════════════════════
            // NIVEL 1 · OLS + NEWEY-WEST IC95
            // ═══════════════════════════════════════════════════════════════
            if ($cascade_nivel_act === 1) {
                if ($n_floors < 10 || $se_corr <= 0.0) {
                    $fallback_reason    = $n_floors < 10 ? 'n<10_skip_ols' : 'se_invalido_skip_ols';
                    $cascade_nivel_act  = 2;
                } else {
                    $df_nw = max(1, $n_floors - 2);
                    if ($df_nw > 120) {
                        $t_crit_nw = 1.645;
                    } elseif (isset($t_tab_nw[$df_nw])) {
                        $t_crit_nw = $t_tab_nw[$df_nw];
                    } else {
                        $keys_nw = array_keys($t_tab_nw);
                        $lo_nw = $hi_nw = null;
                        foreach ($keys_nw as $k) {
                            if ($k <= $df_nw) $lo_nw = $k;
                            if ($k >= $df_nw && $hi_nw === null) $hi_nw = $k;
                        }
                        $t_crit_nw = ($lo_nw !== null && $hi_nw !== null && $lo_nw !== $hi_nw)
                            ? $t_tab_nw[$lo_nw] + ($df_nw - $lo_nw) / ($hi_nw - $lo_nw) * ($t_tab_nw[$hi_nw] - $t_tab_nw[$lo_nw])
                            : ($lo_nw !== null ? $t_tab_nw[$lo_nw] : 6.314);
                    }
                    $ic95_low_nw = $slope_norm - $t_crit_nw * $se_corr;
                    if ($ic95_low_nw > 0.0) {
                        $c7a_confianza     = 'alta';
                        $c7a_cascade_nivel = 1;
                        $cascade_nivel_act = 0;
                    } else {
                        if ($cascada_exhaustiva) {
                            $fallback_reason   = ($fallback_reason ? $fallback_reason . '; ' : '')
                                . 'ols_nw_ns(ic95_low=' . round($ic95_low_nw, 3) . ')';
                            $cascade_nivel_act = 2;
                        } else {
                            $cascade_nivel_act = 0;   // resultado válido negativo
                        }
                    }
                }

                // ═══════════════════════════════════════════════════════════════
                // NIVEL 2 · BOOTSTRAP THEIL-SEN IC99
                // ═══════════════════════════════════════════════════════════════
            } elseif ($cascade_nivel_act === 2) {
                if ($n_floors < 8) {
                    $fallback_reason   = ($fallback_reason ? $fallback_reason . '; ' : '')
                        . 'n<8_skip_bootstrap';
                    $cascade_nivel_act = 3;
                } else {
                    $B_boot     = $n_floors >= 30 ? 299 : ($n_floors >= 20 ? 499 : 999);
                    $boot_betas = [];
                    for ($b = 0; $b < $B_boot; $b++) {
                        $idx_b = [];
                        for ($k = 0; $k < $n_floors; $k++) {
                            $idx_b[] = mt_rand(0, $n_floors - 1);
                        }
                        sort($idx_b);
                        $f_b = [];
                        foreach ($idx_b as $ki) {
                            $f_b[] = $floors_norm[$ki];
                        }
                        $bp = [];
                        $nb = count($f_b);
                        for ($i = 0; $i < $nb; $i++) {
                            for ($j = $i + 1; $j < $nb; $j++) {
                                $bp[] = ($f_b[$j] - $f_b[$i]) / ($j - $i);
                            }
                        }
                        if (!empty($bp)) {
                            sort($bp);
                            $np           = count($bp);
                            $boot_betas[] = $np % 2 === 1
                                ? $bp[intdiv($np, 2)]
                                : ($bp[$np / 2 - 1] + $bp[$np / 2]) / 2.0;
                        }
                    }
                    if (!empty($boot_betas)) {
                        sort($boot_betas);
                        $nb_s      = count($boot_betas);
                        $ic99_low  = $boot_betas[(int)round(0.005 * ($nb_s - 1))];
                        $mean_boot = array_sum($boot_betas) / $nb_s;
                        $var_boot  = 0.0;
                        foreach ($boot_betas as $bs) {
                            $var_boot += ($bs - $mean_boot) ** 2;
                        }
                        $var_boot /= $nb_s;

                        if ($var_boot < 1e-12) {
                            $cascade_nivel_act = 3;   // Tipo B: varianza bootstrap colapsa
                        } elseif ($ic99_low > 0.0) {
                            $c7a_confianza     = 'media';
                            $c7a_cascade_nivel = 2;
                            $cascade_nivel_act = 0;
                        } else {
                            if ($cascada_exhaustiva) {
                                $fallback_reason   = ($fallback_reason ? $fallback_reason . '; ' : '')
                                    . 'bootstrap_ic99_ns(ic99_low=' . round($ic99_low, 3) . ')';
                                $cascade_nivel_act = 3;
                            } else {
                                $cascade_nivel_act = 0;   // resultado válido negativo
                            }
                        }
                    } else {
                        $cascade_nivel_act = 3;
                    }
                }

                // ═══════════════════════════════════════════════════════════════
                // NIVEL 3 · MANN-KENDALL + HAMED & RAO
                // ═══════════════════════════════════════════════════════════════
            } elseif ($cascade_nivel_act === 3) {
                if ($n_floors < 4 || $n_floors > 20) {
                    $fallback_reason   = ($fallback_reason ? $fallback_reason . '; ' : '')
                        . ($n_floors < 4 ? 'n<4_skip_mk' : 'n>20_mk_overpowered');
                    $cascade_nivel_act = 4;
                } else {
                    $S_mk = 0;
                    for ($i = 0; $i < $n_floors; $i++) {
                        for ($j = $i + 1; $j < $n_floors; $j++) {
                            $d = $floors_norm[$j] - $floors_norm[$i];
                            if ($d > 0.0)      $S_mk++;
                            elseif ($d < 0.0)  $S_mk--;
                        }
                    }
                    $var_mk = (float)$n_floors * ($n_floors - 1) * (2 * $n_floors + 5) / 18.0;
                    if ($autocorr_lag1 > 0.0) {
                        $var_mk *= (1.0 + 2.0 * $autocorr_lag1);
                    }
                    if ($var_mk > 0.0) {
                        $S_adj = $S_mk > 0 ? $S_mk - 1 : ($S_mk < 0 ? $S_mk + 1 : 0);
                        $z_mk  = $S_adj / sqrt($var_mk);
                        $az    = abs($z_mk);
                        $t_phi = 1.0 / (1.0 + 0.2316419 * $az);
                        $p_tail = 0.3989423 * exp(-$az * $az / 2.0)
                            * $t_phi * (0.3193815 + $t_phi * (-0.3565638
                                + $t_phi * (1.7814779 + $t_phi * (-1.8212560
                                    + $t_phi * 1.3302744))));
                        $p_mk_c7a = min(1.0, max(0.0, 2.0 * $p_tail));
                    } else {
                        $p_mk_c7a = 1.0;
                    }

                    if ($p_mk_c7a < $umbral_pvalue_alta) {
                        $c7a_confianza     = 'posible';
                        $c7a_cascade_nivel = 3;
                        $cascade_nivel_act = 0;
                    } elseif ($p_mk_c7a >= $umbral_pvalue) {
                        if ($cascada_exhaustiva) {
                            $fallback_reason   = ($fallback_reason ? $fallback_reason . '; ' : '')
                                . 'mk_ns(p=' . round($p_mk_c7a, 3) . ')';
                            $cascade_nivel_act = 4;
                        } else {
                            $cascade_nivel_act = 0;   // resultado válido negativo
                        }
                    } else {
                        $cascade_nivel_act = 4;   // Tipo B: p ∈ [pvalue_alta, pvalue)
                    }
                }

                // ═══════════════════════════════════════════════════════════════
                // NIVEL 4 · TEST DE SIGNO BINOMIAL
                // ═══════════════════════════════════════════════════════════════
            } elseif ($cascade_nivel_act === 4) {
                $n_diffs = $n_floors - 1;
                if ($n_diffs > 0) {
                    $k_pos = 0;
                    for ($i = 0; $i < $n_diffs; $i++) {
                        if ($floors_norm[$i + 1] > $floors_norm[$i]) $k_pos++;
                    }
                    $p_signo  = 0.0;
                    $bcoef    = 1.0;
                    $pow_half = pow(0.5, $n_diffs);
                    for ($k = 0; $k <= $n_diffs; $k++) {
                        if ($k > 0) $bcoef *= ($n_diffs - $k + 1) / $k;
                        if ($k >= $k_pos) $p_signo += $bcoef * $pow_half;
                    }
                    $p_signo = min(1.0, $p_signo);
                    if ($p_signo < 0.05) {
                        $c7a_confianza     = 'posible';
                        $c7a_cascade_nivel = 4;
                    }
                }
                $cascade_nivel_act = 0;   // nivel 4 siempre es terminal

            } else {
                $cascade_nivel_act = 0;
            }
        } // while

        if ($c7a_confianza === null) return null;

        return [
            'confianza'        => $c7a_confianza,
            'cascade_nivel'    => $c7a_cascade_nivel,
            'p_mk'             => $p_mk_c7a,
            'fallback_reason'  => $fallback_reason ?: null,
            'beta_ts'          => $beta_ts,
            'autocorr_lag1'    => $autocorr_lag1,
        ];
    }

    /**
     * Calcula la severidad de una incidencia C7a confirmada.
     *
     * Tabla 2D (confianza × cobertura temporal):
     *   confianza | both  | analysis | base  | null(→analysis)
     *   'alta'    | ALTA  | ALTA     | MEDIA | ALTA
     *   'media'   | ALTA  | MEDIA    | MEDIA | MEDIA
     *   'posible' | MEDIA | MEDIA    | BAJA  | MEDIA
     *   C7a_posible (confianza='posible' subcaso): siempre BAJA
     *
     * Modulador: si delta < umbral_alta_delta Y tendencia < umbral_alta_slope → bajar 1 nivel.
     *
     * @param string  $confianza          'alta' | 'media' | 'posible' (C7a_posible usa 'posible')
     * @param ?string $test_period        'both' | 'analysis' | 'base' | null
     * @param float   $delta_total        Δ acumulado (floors[-1] − floors[0])
     * @param float   $tendencia_visible  β_ts_raw (Theil-Sen sobre floors brutos)
     * @param bool    $es_posible_subcaso true si el subcaso es 'C7a_posible' (siempre BAJA)
     * @param string  $tipo_art           'unidad' | 'peso'
     * @param float   $umbral_alta_delta_unidad
     * @param float   $umbral_alta_delta_peso
     * @param float   $umbral_alta_slope_unidad
     * @param float   $umbral_alta_slope_peso
     */
    public function calcularSeveridad(
        string  $confianza,
        ?string $test_period,
        float   $delta_total,
        float   $tendencia_visible,
        bool    $es_posible_subcaso    = false,
        string  $tipo_art              = 'unidad',
        float   $umbral_alta_delta_unidad = 10.0,
        float   $umbral_alta_delta_peso   = 5.0,
        float   $umbral_alta_slope_unidad = 2.0,
        float   $umbral_alta_slope_peso   = 1.0
    ): string {
        if ($es_posible_subcaso) return 'BAJA';   // C7a_posible siempre BAJA

        static $tablaSeveridad = [
            'alta'    => ['both' => 'ALTA',  'analysis' => 'ALTA',  'base' => 'MEDIA'],
            'media'   => ['both' => 'ALTA',  'analysis' => 'MEDIA', 'base' => 'MEDIA'],
            'posible' => ['both' => 'MEDIA', 'analysis' => 'MEDIA', 'base' => 'BAJA'],
        ];
        static $severidadANivel = ['ALTA' => 2, 'MEDIA' => 1, 'BAJA' => 0];
        static $nivelASeveridad = [2 => 'ALTA', 1 => 'MEDIA', 0 => 'BAJA'];

        $periodoEvaluado = $test_period ?? 'analysis';
        $severidadBase   = $tablaSeveridad[$confianza][$periodoEvaluado] ?? 'BAJA';
        $nivelSeveridad  = $severidadANivel[$severidadBase];

        $umbralDeltaAlto = ($tipo_art === 'peso') ? $umbral_alta_delta_peso : $umbral_alta_delta_unidad;
        $umbralSlopeAlto = ($tipo_art === 'peso') ? $umbral_alta_slope_peso : $umbral_alta_slope_unidad;

        if ($delta_total < $umbralDeltaAlto && $tendencia_visible < $umbralSlopeAlto) {
            $nivelSeveridad--;
        }

        return $nivelASeveridad[max(0, $nivelSeveridad)];
    }
}
