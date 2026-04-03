<?php

/**
 * PosstockC7bAnalyzer — Analizador estadístico de C7b: entrada no registrada (déficit).
 *
 * Extraído de ClasePosstock como parte de la Fase 5 de refactorización.
 *
 * Detecta artículos cuyo suelo de stock post-recepción se estabiliza en un nivel negativo
 * y constante (σ/|μ| < umbral), indicando un inventario inicial incorrecto o recepciones
 * no registradas.
 *
 * Métodos públicos:
 *   analizarCascada(...)  — normalización + guards + cascada 4 niveles
 *   calcularSeveridad(...)— tabla severidad 2D + moduladores magnitud/cobertura
 */

class PosstockC7bAnalyzer
{
    /**
     * Normaliza floors, aplica guards de estabilidad y corre la cascada estadística.
     *
     * Niveles:
     *   1. Wilcoxon Signed-Rank (n >= 4, ratio_distinct >= 0.75) → 'alta'
     *   2. Bootstrap Percentile (std > 0, n_distinct > 1)         → 'media'
     *   3. Test de signo binomial (n_eff >= 3)                    → 'media'
     *   4. T-test + Newey-West IC95  (último recurso)             → 'posible'
     *   5. Caso determinista (std ≈ 0, mean < 0)                  → 'posible'
     *
     * @param array  $test_floors    Floors del período estadístico (base o análisis)
     * @param array  $test_dias      Duración en días de cada intervalo correspondiente
     * @param float  $abs_mean_raw   |media| de ALL los floors (para figura económica)
     * @param string $tipo_art       'unidad' | 'peso'
     * @param float  $umbral_cv      Umbral CV artículos tipo unidad
     * @param float  $umbral_cv_peso Umbral CV artículos tipo peso
     * @param float  $umbral_iqr_peso Multiplicador IQR para artículos tipo peso
     * @param bool   $cascada_exhaustiva
     *
     * @return array{test_type: string|null, test_pvalue: float|null, confianza: string,
     *               ic95_upper: float|null, autocorr_lag1: float,
     *               cv_duraciones: float, es_determinista: bool}|null
     *         null si el artículo no cumple C7b (CV alto, floors positivos, etc.)
     */
    public function analizarCascada(
        array  $test_floors,
        array  $test_dias,
        float  $abs_mean_raw,
        string $tipo_art              = 'unidad',
        float  $umbral_cv             = 0.5,
        float  $umbral_cv_peso        = 0.75,
        float  $umbral_iqr_peso       = 2.0,
        bool   $cascada_exhaustiva    = false
    ): ?array {
        $n = count($test_floors);
        if ($n < 2) return null;

        // ── Normalizar floors por duración del intervalo (C7b-002) ────────────
        $floors_norm = [];
        for ($i = 0; $i < $n; $i++) {
            $floors_norm[] = $test_floors[$i] / max(1, $test_dias[$i]);
        }
        $mean = array_sum($floors_norm) / $n;

        $variance = 0.0;
        foreach ($floors_norm as $fnv) {
            $variance += ($fnv - $mean) ** 2;
        }
        $std_dev = $n > 1 ? sqrt($variance / ($n - 1)) : 0.0;

        // ── Heterogeneidad de duraciones (C7b-025) ────────────────────────────
        $mean_dias = array_sum($test_dias) / $n;
        $var_dias  = 0.0;
        foreach ($test_dias as $d) {
            $var_dias += ($d - $mean_dias) ** 2;
        }
        $std_dias   = $n > 1 ? sqrt($var_dias / ($n - 1)) : 0.0;
        $cv_dias    = $mean_dias > 0.0 ? $std_dias / $mean_dias : 0.0;

        // ── Caso determinista (C7b-024 / C7b-026): std ≈ 0 ──────────────────
        if ($std_dev < 1e-9) {
            if ($mean < 0.0) {
                return [
                    'test_type'       => 'determinista_constante',
                    'test_pvalue'     => null,
                    'confianza'       => 'posible',
                    'ic95_upper'      => round($mean, 4),
                    'autocorr_lag1'   => 0.0,
                    'cv_duraciones'   => round($cv_dias, 3),
                    'es_determinista' => true,
                ];
            }
            return null;
        }

        // ── Filtro de estabilidad — guards CV + IQR (C7b-009) ────────────────
        $umbral_cv_efectivo = ($tipo_art === 'peso') ? $umbral_cv_peso : $umbral_cv;
        $umbral_iqr_mult    = ($tipo_art === 'peso') ? $umbral_iqr_peso : 1.5;
        $sf = $floors_norm;
        sort($sf);
        $q1_idx = (int)floor(($n - 1) * 0.25);
        $q3_idx = (int)ceil(($n - 1) * 0.75);
        $iqr    = $sf[$q3_idx] - $sf[$q1_idx];
        if ($n === 3) {
            $iqr *= 0.7;
        }
        $cv = ($mean != 0.0) ? $std_dev / abs($mean) : PHP_FLOAT_MAX;
        if (!($cv < $umbral_cv_efectivo && $iqr < $umbral_iqr_mult * abs($mean))) {
            return null;
        }

        // ── Estado de la cascada ──────────────────────────────────────────────
        $test_type            = null;
        $test_pvalue          = null;
        $test_fallback_reason = null;
        $c7b_confirmed        = false;
        $confianza_c7b        = 'posible';
        $ic95_upper           = null;
        $r1                   = 0.0;

        static $t_975_tab = [
            1 => 12.706, 2 => 4.303, 3 => 3.182, 4 => 2.776,  5 => 2.571,
            6 =>  2.447, 7 => 2.365, 8 => 2.306, 9 => 2.262, 10 => 2.228,
            15 => 2.131, 20 => 2.086, 30 => 2.042, 60 => 2.000, 120 => 1.980,
        ];

        // ═══════════════════════════════════════════════════════════════════
        // NIVEL 1 · WILCOXON SIGNED-RANK (C7b-020)
        // ═══════════════════════════════════════════════════════════════════
        $ratio_distinct  = count(array_unique($floors_norm)) / $n;
        $wilcoxon_tipo_a = ($n < 4 || $ratio_distinct < 0.75);
        if (!$wilcoxon_tipo_a) {
            $nz_vals  = [];
            $nz_signs = [];
            foreach ($floors_norm as $fv) {
                if ($fv != 0.0) {
                    $nz_vals[]  = abs($fv);
                    $nz_signs[] = ($fv < 0 ? -1 : 1);
                }
            }
            $n_w = count($nz_vals);
            if ($n_w >= 4) {
                $order = range(0, $n_w - 1);
                usort($order, fn($a, $b) => $nz_vals[$a] <=> $nz_vals[$b]);
                $rnks = array_fill(0, $n_w, 0.0);
                $i = 0;
                while ($i < $n_w) {
                    $j = $i;
                    while ($j + 1 < $n_w && $nz_vals[$order[$j + 1]] == $nz_vals[$order[$i]]) {
                        $j++;
                    }
                    $avg_rank = ($i + $j + 2) / 2.0;
                    for ($k2 = $i; $k2 <= $j; $k2++) {
                        $rnks[$order[$k2]] = $avg_rank;
                    }
                    $i = $j + 1;
                }
                $n_ties_ranks = $n_w - count(array_unique($rnks));
                if ($n_ties_ranks / $n_w > 0.5) {
                    $test_fallback_reason = 'wilcoxon_tipo_b_empates';
                } else {
                    $t_plus = 0.0;
                    for ($i = 0; $i < $n_w; $i++) {
                        if ($nz_signs[$i] > 0) {
                            $t_plus += $rnks[$i];
                        }
                    }
                    $mu_w  = $n_w * ($n_w + 1) / 4.0;
                    $var_w = $n_w * ($n_w + 1) * (2 * $n_w + 1) / 24.0;
                    $z_w   = ($t_plus + 0.5 - $mu_w) / sqrt($var_w);
                    $az    = abs($z_w);
                    $t_as  = 1.0 / (1.0 + 0.2316419 * $az);
                    $poly  = $t_as * (0.319381530 + $t_as * (-0.356563782 + $t_as * (1.781477937 + $t_as * (-1.821255978 + $t_as * 1.330274429))));
                    $ncdf  = 1.0 - exp(-$az * $az / 2.0) / sqrt(2.0 * M_PI) * $poly;
                    $p_wilcoxon  = $z_w < 0 ? 1.0 - $ncdf : $ncdf;
                    $test_type   = 'wilcoxon_signed_rank';
                    $test_pvalue = round($p_wilcoxon, 4);
                    if ($p_wilcoxon < 0.05) {
                        $c7b_confirmed = true;
                        $confianza_c7b = 'alta';
                    } elseif (!$cascada_exhaustiva) {
                        return null;
                    } else {
                        $test_fallback_reason = ($test_fallback_reason ? $test_fallback_reason . '; ' : '')
                            . 'wilcoxon_p=' . round($p_wilcoxon, 3) . '_ns';
                        $test_type   = null;
                        $test_pvalue = null;
                    }
                }
            } else {
                $wilcoxon_tipo_a = true;
            }
        }
        if ($wilcoxon_tipo_a && $test_fallback_reason === null) {
            $rs = [];
            if ($n < 4)                 $rs[] = 'n<4';
            if ($ratio_distinct < 0.75) $rs[] = 'ratio_distinct<0.75';
            $test_fallback_reason = 'wilcoxon_tipo_a:' . implode(',', $rs);
        }

        // ═══════════════════════════════════════════════════════════════════
        // NIVEL 2 · BOOTSTRAP PERCENTILE (C7b-023)
        // ═══════════════════════════════════════════════════════════════════
        if (!$c7b_confirmed) {
            $n_distinct_boot = count(array_unique($floors_norm));
            if ($std_dev > 0.0 && $n_distinct_boot > 1) {
                $mu_boot = [];
                for ($b = 0; $b < 999; $b++) {
                    $s = 0.0;
                    for ($j = 0; $j < $n; $j++) {
                        $s += $floors_norm[random_int(0, $n - 1)];
                    }
                    $mu_boot[] = $s / $n;
                }
                sort($mu_boot);
                $ic95_sup_boot = $mu_boot[(int)(999 * 0.975)];
                $test_type     = 'bootstrap';
                if ($ic95_sup_boot < 0.0) {
                    $c7b_confirmed = true;
                    $confianza_c7b = 'media';
                } elseif (!$cascada_exhaustiva) {
                    return null;
                } else {
                    $test_fallback_reason = ($test_fallback_reason ? $test_fallback_reason . '; ' : '')
                        . 'bootstrap_ic95_sup=' . round($ic95_sup_boot, 3) . '_ns';
                    $test_type = null;
                }
            } else {
                $reason_boot = ($n_distinct_boot == 1) ? 'bootstrap_tipo_b_n_distinct_1' : 'bootstrap_tipo_a_std0';
                $test_fallback_reason = ($test_fallback_reason ? $test_fallback_reason . '; ' : '') . $reason_boot;
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // NIVEL 3 · TEST DE SIGNO BINOMIAL (C7b-021)
        // ═══════════════════════════════════════════════════════════════════
        if (!$c7b_confirmed) {
            $floors_eff = array_values(array_filter($floors_norm, fn($f) => $f != 0.0));
            $n_eff      = count($floors_eff);
            if ($n_eff >= 3) {
                $b_minus = count(array_filter($floors_eff, fn($f) => $f < 0.0));
                $p_sign  = 0.0;
                $coeff   = 1.0;
                for ($k = 0; $k <= $n_eff; $k++) {
                    if ($k >= $b_minus) {
                        $p_sign += $coeff;
                    }
                    if ($k < $n_eff) {
                        $coeff *= ($n_eff - $k) / ($k + 1.0);
                    }
                }
                $p_sign     /= pow(2.0, $n_eff);
                $test_type   = 'sign_binomial';
                $test_pvalue = round($p_sign, 4);
                if ($p_sign < 0.05) {
                    $c7b_confirmed = true;
                    $confianza_c7b = 'media';
                } elseif (!$cascada_exhaustiva) {
                    return null;
                } else {
                    $test_fallback_reason = ($test_fallback_reason ? $test_fallback_reason . '; ' : '')
                        . 'sign_p=' . round($p_sign, 3) . '_ns';
                    $test_type   = null;
                    $test_pvalue = null;
                }
            } else {
                $test_fallback_reason = ($test_fallback_reason ? $test_fallback_reason . '; ' : '')
                    . 'sign_tipo_a_n_efectivo<3';
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // NIVEL 4 · T-TEST + NEWEY-WEST (C7b-004)
        // ═══════════════════════════════════════════════════════════════════
        if (!$c7b_confirmed) {
            $df_ic = $n - 1;
            if ($df_ic > 120) {
                $t_975 = 1.960;
            } elseif (isset($t_975_tab[$df_ic])) {
                $t_975 = $t_975_tab[$df_ic];
            } else {
                $keys_ic = array_keys($t_975_tab);
                $lo_ic = $hi_ic = null;
                foreach ($keys_ic as $k) {
                    if ($k <= $df_ic) $lo_ic = $k;
                    if ($k >= $df_ic && $hi_ic === null) $hi_ic = $k;
                }
                $t_975 = ($lo_ic !== null && $hi_ic !== null && $lo_ic !== $hi_ic)
                    ? $t_975_tab[$lo_ic] + ($df_ic - $lo_ic) / ($hi_ic - $lo_ic) * ($t_975_tab[$hi_ic] - $t_975_tab[$lo_ic])
                    : ($lo_ic !== null ? $t_975_tab[$lo_ic] : 1.960);
            }
            $se_base    = $std_dev / sqrt($n);
            $ic95_upper = $mean + $t_975 * $se_base;
            if ($n >= 4) {
                $cov_lag1 = 0.0;
                for ($i = 1; $i < $n; $i++) {
                    $cov_lag1 += ($floors_norm[$i] - $mean) * ($floors_norm[$i - 1] - $mean);
                }
                $cov_lag1 /= ($n - 1);
                $r1 = max(-1.0, min(1.0, $cov_lag1 / ($std_dev ** 2)));
                if ($r1 > 0.0) {
                    $ic95_upper = $mean + $t_975 * $se_base * sqrt(1.0 + 2.0 * $r1);
                }
            }
            $test_type = 't_student';
            if ($ic95_upper < 0.0) {
                $c7b_confirmed = true;
                $confianza_c7b = 'posible';
            } else {
                return null;
            }
        }

        if (!$c7b_confirmed) return null;

        // Degradar confianza si duraciones muy irregulares (C7b-025)
        if ($cv_dias > 1.0) {
            $confianza_c7b = ($confianza_c7b === 'alta') ? 'media' : 'posible';
        }

        return [
            'test_type'       => $test_type,
            'test_pvalue'     => $test_pvalue,
            'confianza'       => $confianza_c7b,
            'ic95_upper'      => $ic95_upper !== null ? round($ic95_upper, 4) : null,
            'autocorr_lag1'   => round($r1, 2),
            'cv_duraciones'   => round($cv_dias, 3),
            'es_determinista' => false,
        ];
    }

    /**
     * Calcula la severidad de una incidencia C7b confirmada.
     *
     * Tabla base 2D (confianza × cobertura):
     *   confianza | A (base+análisis) | B (base solo) | C (análisis solo)
     *   'alta'    | CRITICA           | ALTA          | ALTA
     *   'media'   | ALTA              | ALTA          | MEDIA
     *   'posible' | ALTA              | MEDIA         | MEDIA
     *
     * Moduladores:
     *  · Magnitud pequeña: abs_mean < umbral → −1 nivel
     *  · Cobertura parcial: pct_intervalos_negativos < 50 % → −1 nivel
     *
     * @param string $confianza    'alta' | 'media' | 'posible'
     * @param string $cobertura    'A' (base+analysis) | 'B' (base) | 'C' (analysis)
     * @param float  $abs_mean_raw Valor absoluto de la media de RAW floors
     * @param int    $pct_neg      % de floors negativos
     * @param string $tipo_art     'unidad' | 'peso'
     * @param int    $umbral_sev_unidad
     * @param float  $umbral_sev_peso
     */
    public function calcularSeveridad(
        string $confianza,
        string $cobertura,
        float  $abs_mean_raw,
        int    $pct_neg,
        string $tipo_art          = 'unidad',
        int    $umbral_sev_unidad = 5,
        float  $umbral_sev_peso   = 2.5
    ): string {
        static $sev_tab = [
            'alta'    => ['A' => 'CRITICA', 'B' => 'ALTA',  'C' => 'ALTA'],
            'media'   => ['A' => 'ALTA',    'B' => 'ALTA',  'C' => 'MEDIA'],
            'posible' => ['A' => 'ALTA',    'B' => 'MEDIA', 'C' => 'MEDIA'],
        ];
        static $sev_num  = ['CRITICA' => 3, 'ALTA' => 2, 'MEDIA' => 1, 'BAJA' => 0];
        static $sev_name = [3 => 'CRITICA', 2 => 'ALTA', 1 => 'MEDIA', 0 => 'BAJA'];

        $sev_base = $sev_tab[$confianza][$cobertura] ?? 'BAJA';
        $nivel    = $sev_num[$sev_base];

        $mag_small = ($tipo_art === 'peso')
            ? ($abs_mean_raw < $umbral_sev_peso)
            : ($abs_mean_raw < $umbral_sev_unidad);

        if ($mag_small)    $nivel--;
        if ($pct_neg < 50) $nivel--;

        return $sev_name[max(0, $nivel)];
    }
}
