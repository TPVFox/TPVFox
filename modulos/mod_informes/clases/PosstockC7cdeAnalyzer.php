<?php

/**
 * PosstockC7cdeAnalyzer — Análisis de cruces inter-artículo C7c, C7d y C7e.
 *
 * Extraído de ClasePosstock como parte de la Fase 5 de refactorización.
 *
 * C7c: Cruces de pares C7a ↔ C7b (confusión en balanza de autopesaje).
 * C7d: Tríos: dos artículos C7b explican la merma de uno C7a.
 * C7e: Múltiplos: k unidades de A escaneadas como 1 de B (k ∈ {2,3,4,5}).
 *
 * Requiere que las incidencias tengan el campo '_floors_raw' presente.
 * El campo se elimina en PosstockC7Detector tras ejecutar los tres analizadores.
 *
 * Métodos públicos:
 *   detectarCrucesPares(...)  — C7c
 *   detectarTrios(...)        — C7d
 *   detectarMultiplos(...)    — C7e
 */

require_once __DIR__ . '/PosstockStatistics.php';

class PosstockC7cdeAnalyzer
{
    /**
     * C7c — Detección de cruces de pares C7a ↔ C7b.
     *
     * Hipótesis: un cajero confunde artículo A (C7a, merge aparente) con artículo B
     * (C7b, déficit sistemático) al pesar en la balanza. Los offsets se cancelan: μ_A + μ_B ≈ 0.
     *
     * Filtros rígidos:
     *   · Mismo departamento N1
     *   · |μ_A + μ_B| / max(|μ_A|, |μ_B|) ≤ 0.45
     *   · ≥ 4 pares temporalmente solapados (±7 días)
     *   · CV < 0.70 en ambos
     *   · Pearson ≥ 0.50 si n ≥ 4
     *
     * Score = 0.50×s_mag + 0.25×s_corr + 0.15×s_disp + 0.10×s_nombre
     *
     * @param array  $incidencias  Por referencia; añade campos 'posible_cruce_con',
     *                             'cruce_score', 'cruce_nivel', 'posible_causa' en C7a.
     *                             Propaga cruce a la incidencia C7b correspondiente.
     * @param array  $meta         [idArticulo => ['nombre' => string, 'familias_n1' => int[]]]
     */
    public function detectarCrucesPares(array &$incidencias, array $meta): void
    {
        // Indexar C7b con sus floors (solo subcaso C7b confirmado, no C7b_posible/ruido)
        $c7b_data = [];
        foreach ($incidencias as $inc) {
            if (($inc['c7_subcaso'] ?? '') !== 'C7b') continue;
            $c7b_data[$inc['idArticulo']] = ['floors_raw' => $inc['_floors_raw']];
        }
        if (empty($c7b_data)) return;

        $cruces = [];

        foreach ($incidencias as &$inc) {
            if (($inc['c7_subcaso'] ?? '') !== 'C7a') continue;

            $mejor_score = 0.0;
            $mejor_id    = null;
            $mejor_nivel = '';
            $n1_a        = $meta[$inc['idArticulo']]['familias_n1'] ?? [];

            foreach ($c7b_data as $id_b => $cb) {
                // 1. Filtro duro N1
                $n1_b = $meta[$id_b]['familias_n1'] ?? [];
                if (!empty($n1_a) && !empty($n1_b) && empty(array_intersect($n1_a, $n1_b))) continue;

                // 2. Emparejar floors por fecha ±7 días
                [$pairs_a, $pairs_b] = $this->emparejarFloors($inc['_floors_raw'], $cb['floors_raw']);
                $n_pairs = count($pairs_a);
                if ($n_pairs < 4) continue;

                [$mu_a, $sd_a] = PosstockStatistics::statsFloors($pairs_a);
                [$mu_b, $sd_b] = PosstockStatistics::statsFloors($pairs_b);

                if ($mu_a < 1.5 || abs($mu_b) < 1.5) continue;

                // 3. Filtros rígidos
                $suma_mu     = abs($mu_a + $mu_b);
                $suma_mu_rel = $suma_mu / max(abs($mu_a), abs($mu_b));
                if ($suma_mu_rel > 0.45) continue;

                $cv_a = ($mu_a > 0.0) ? $sd_a / $mu_a : PHP_FLOAT_MAX;
                $cv_b = (abs($mu_b) > 0.0) ? $sd_b / abs($mu_b) : PHP_FLOAT_MAX;
                if ($cv_a > 0.70 || $cv_b > 0.70) continue;

                // 4. Correlación de Pearson (valores absolutos)
                $pairs_b_abs = array_map('abs', $pairs_b);
                $corr = ($n_pairs >= 4) ? PosstockStatistics::pearsonCorr($pairs_a, $pairs_b_abs) : 0.5;
                if ($n_pairs >= 4 && $corr < 0.50) continue;

                // 5. Score compuesto
                $s_mag    = 1.0 - $suma_mu_rel;
                $s_corr   = max(0.0, (float)$corr);
                $s_disp   = ($sd_a > 0.0 && $sd_b > 0.0)
                    ? max(0.0, 1.0 - abs(log($sd_a / $sd_b)) / 2.0)
                    : 0.5;
                $s_nombre = PosstockStatistics::jaccardNombre(
                    $meta[$inc['idArticulo']]['nombre'] ?? '',
                    $meta[$id_b]['nombre'] ?? ''
                );
                $score = $s_mag * 0.50 + $s_corr * 0.25 + $s_disp * 0.15 + $s_nombre * 0.10;

                if ($score < 0.50) continue;
                $nivel = ($score >= 0.80) ? 'confirmado' : (($score >= 0.65) ? 'probable' : 'posible');

                if ($score > $mejor_score) {
                    $mejor_score = $score;
                    $mejor_id    = $id_b;
                    $mejor_nivel = $nivel;
                }
            }

            if ($mejor_id !== null) {
                $inc['posible_cruce_con'] = $mejor_id;
                $inc['cruce_score']       = round($mejor_score, 2);
                $inc['cruce_nivel']       = $mejor_nivel;
                $cruces[(int)$inc['idArticulo']] = [
                    'id_b'  => $mejor_id,
                    'score' => round($mejor_score, 2),
                    'nivel' => $mejor_nivel,
                ];
                $inc['posible_causa'] = sprintf(
                    'Merma con patrón complementario a art. %d (cruce %s, score=%.2f): probable confusión en la balanza de autopesaje entre ambos artículos',
                    $mejor_id,
                    $mejor_nivel,
                    $mejor_score
                );
            }
        }
        unset($inc);

        // Propagar al lado C7b
        if (!empty($cruces)) {
            $c7b_cruce = [];
            foreach ($cruces as $id_a => $data) {
                $id_b = $data['id_b'];
                if (!isset($c7b_cruce[$id_b]) || $data['score'] > $c7b_cruce[$id_b]['score']) {
                    $c7b_cruce[$id_b] = ['id_a' => $id_a, 'score' => $data['score'], 'nivel' => $data['nivel']];
                }
            }
            foreach ($incidencias as &$inc) {
                if (($inc['c7_subcaso'] ?? '') !== 'C7b') continue;
                if (!isset($c7b_cruce[$inc['idArticulo']])) continue;
                $d = $c7b_cruce[$inc['idArticulo']];
                $inc['posible_cruce_con'] = $d['id_a'];
                $inc['cruce_score']       = $d['score'];
                $inc['cruce_nivel']       = $d['nivel'];
                $inc['posible_causa'] = sprintf(
                    'Déficit de ~%.0f ud. con patrón complementario a art. %d (cruce %s, score=%.2f): probable confusión en la balanza o recepción no registrada',
                    abs((float)$inc['offset_estimado']),
                    $d['id_a'],
                    $d['nivel'],
                    $d['score']
                );
            }
            unset($inc);
        }
    }

    /**
     * C7d — Detección de tríos: dos artículos C7b explican la merma de uno C7a.
     *
     * Hipótesis: μ_A + μ_B ≈ μ_C (los déficits de A y B cancelan la merma de C).
     *
     * Criterios:
     *   · ≥ 6 triples solapados (±7 días)
     *   · |μ_C − (μ_A + μ_B)| ≤ 0.5 ud
     *   · max(σ) / min(σ) ≤ 2.0
     *   · Pearson(suma_AB, merma_C) ≥ 0.7
     *   · Score = s_mag×0.60 + correlación×0.40; solo supera si > cruce_score actual
     *
     * @param array $incidencias  Por referencia; modifica campos de cruce si score superior.
     */
    public function detectarTrios(array &$incidencias): void
    {
        $has_c7a = $has_c7b = false;
        foreach ($incidencias as $inc) {
            if (($inc['c7_subcaso'] ?? '') === 'C7a') $has_c7a = true;
            if (($inc['c7_subcaso'] ?? '') === 'C7b') $has_c7b = true;
            if ($has_c7a && $has_c7b) break;
        }
        if (!$has_c7a || !$has_c7b) return;

        $c7b_data = [];
        foreach ($incidencias as $inc) {
            if (($inc['c7_subcaso'] ?? '') !== 'C7b') continue;
            $keys  = array_keys($inc['_floors_raw']);
            $c7b_data[$inc['idArticulo']] = [
                'floors_raw' => $inc['_floors_raw'],
                'ts_floors'  => $inc['_ts_floors']
                    ?? array_combine($keys, array_map('strtotime', $keys)),
            ];
        }
        $c7b_ids = array_keys($c7b_data);
        $n_c7b   = count($c7b_ids);
        $ventana = 7 * 86400;

        // Pre-calcular emparejamientos C7a ↔ cada C7b
        $pair_match = [];
        foreach ($incidencias as $inc_pm) {
            if (($inc_pm['c7_subcaso'] ?? '') !== 'C7a') continue;
            $id_c     = $inc_pm['idArticulo'];
            $ts_c_map = $inc_pm['_ts_floors'] ?? null;
            $row      = [];
            foreach ($c7b_data as $id_b => $cb) {
                $ts_b_map       = $cb['ts_floors'];
                $best_per_floor = [];
                foreach ($inc_pm['_floors_raw'] as $dc => $vc) {
                    $ts_c = $ts_c_map ? $ts_c_map[$dc] : strtotime($dc);
                    $best_diff = $ventana + 1;
                    $best_v    = null;
                    foreach ($cb['floors_raw'] as $db => $vb) {
                        $diff = abs($ts_c - $ts_b_map[$db]);
                        if ($diff <= $ventana && $diff < $best_diff) {
                            $best_diff = $diff;
                            $best_v    = $vb;
                        }
                    }
                    if ($best_v !== null) $best_per_floor[$dc] = $best_v;
                }
                $row[$id_b] = $best_per_floor;
            }
            $pair_match[$id_c] = $row;
        }

        $trios = [];
        foreach ($incidencias as &$inc) {
            if (($inc['c7_subcaso'] ?? '') !== 'C7a') continue;
            $matches_c   = $pair_match[$inc['idArticulo']] ?? [];
            $mejor_score = isset($inc['cruce_score']) ? (float)$inc['cruce_score'] : 0.0;
            $mejor_trio  = null;

            for ($i = 0; $i < $n_c7b - 1; $i++) {
                $id_a    = $c7b_ids[$i];
                $match_a = $matches_c[$id_a] ?? [];
                if (empty($match_a)) continue;

                for ($j = $i + 1; $j < $n_c7b; $j++) {
                    $id_b    = $c7b_ids[$j];
                    $match_b = $matches_c[$id_b] ?? [];

                    $common = array_intersect_key($match_a, $match_b);
                    if (count($common) < 6) continue;

                    $triples_c = $triples_a = $triples_b = $triples_ab = [];
                    foreach ($common as $dc => $_) {
                        $va           = abs($match_a[$dc]);
                        $vb           = abs($match_b[$dc]);
                        $triples_c[]  = $inc['_floors_raw'][$dc];
                        $triples_a[]  = $va;
                        $triples_b[]  = $vb;
                        $triples_ab[] = $va + $vb;
                    }
                    if (count($triples_c) < 6) continue;

                    [$mu_c,  $sd_c]  = PosstockStatistics::statsFloors($triples_c);
                    [$mu_ab]         = PosstockStatistics::statsFloors($triples_ab);
                    [, $sd_a_t]      = PosstockStatistics::statsFloors($triples_a);
                    [, $sd_b_t]      = PosstockStatistics::statsFloors($triples_b);

                    if ($mu_c < 1.5 || $mu_ab < 1.5) continue;
                    if (abs($mu_c - $mu_ab) > 0.5) continue;

                    $sds = array_filter([$sd_a_t, $sd_b_t, $sd_c], fn($s) => $s > 0.0);
                    if (!empty($sds) && max($sds) / min($sds) > 2.0) continue;

                    $corr = PosstockStatistics::pearsonCorr($triples_ab, $triples_c);
                    if ($corr < 0.7) continue;

                    $s_mag = max(0.0, 1.0 - abs($mu_c - $mu_ab) / max(0.5, $mu_c));
                    $score = $s_mag * 0.60 + max(0.0, $corr) * 0.40;
                    $nivel = ($score >= 0.75) ? 'probable' : 'posible';

                    if ($score > $mejor_score) {
                        $mejor_score = $score;
                        $mejor_trio  = [
                            'id_a'  => $id_a,
                            'id_b'  => $id_b,
                            'score' => round($score, 2),
                            'nivel' => $nivel,
                        ];
                    }
                }
            }

            if ($mejor_trio !== null) {
                $inc['posible_cruce_con'] = $mejor_trio['id_a'];
                $inc['cruce_fuente_b']    = $mejor_trio['id_b'];
                $inc['cruce_tipo']        = 'trio';
                $inc['cruce_score']       = $mejor_trio['score'];
                $inc['cruce_nivel']       = $mejor_trio['nivel'];
                $trios[$inc['idArticulo']] = $mejor_trio;
                $inc['posible_causa'] = sprintf(
                    'Merma con patrón trío art. %d + art. %d (%s, score=%.2f): posible confusión sistemática en la balanza de autopesaje',
                    $mejor_trio['id_a'],
                    $mejor_trio['id_b'],
                    $mejor_trio['nivel'],
                    $mejor_trio['score']
                );
            }
        }
        unset($inc);

        // Propagar a los dos artículos C7b fuente
        foreach ($trios as $id_c => $trio) {
            foreach ($incidencias as &$inc) {
                if (($inc['c7_subcaso'] ?? '') !== 'C7b') continue;
                $id_inc = $inc['idArticulo'];
                if ($id_inc !== $trio['id_a'] && $id_inc !== $trio['id_b']) continue;
                $otro = ($id_inc === $trio['id_a']) ? $trio['id_b'] : $trio['id_a'];
                $inc['posible_cruce_con'] = $id_c;
                $inc['cruce_fuente_b']    = $otro;
                $inc['cruce_tipo']        = 'trio';
                $inc['cruce_score']       = $trio['score'];
                $inc['cruce_nivel']       = $trio['nivel'];
                $inc['posible_causa'] = sprintf(
                    'Déficit de ~%.0f ud. incluido en trío junto a art. %d → art. %d (%s, score=%.2f): probable confusión en balanza o recepción no registrada',
                    abs((float)$inc['offset_estimado']),
                    $otro,
                    $id_c,
                    $trio['nivel'],
                    $trio['score']
                );
            }
            unset($inc);
        }
    }

    /**
     * C7e — Pérdidas fantasma por múltiplos: k unidades de A escaneadas como 1 de B.
     *
     * @param array $incidencias  Por referencia; añade/actualiza cruce si score superior.
     * @param array $meta         [idArticulo => ['nombre' => string, 'familias_n1' => int[]]]
     */
    public function detectarMultiplos(array &$incidencias, array $meta): void
    {
        $has_c7a = $has_c7b = false;
        foreach ($incidencias as $inc) {
            if (($inc['c7_subcaso'] ?? '') === 'C7a') $has_c7a = true;
            if (($inc['c7_subcaso'] ?? '') === 'C7b') $has_c7b = true;
            if ($has_c7a && $has_c7b) break;
        }
        if (!$has_c7a || !$has_c7b) return;

        $c7b_data = [];
        foreach ($incidencias as $inc) {
            if (($inc['c7_subcaso'] ?? '') !== 'C7b') continue;
            $keys = array_keys($inc['_floors_raw']);
            $c7b_data[$inc['idArticulo']] = [
                'floors_raw' => $inc['_floors_raw'],
                'ts_floors'  => $inc['_ts_floors']
                    ?? array_combine($keys, array_map('strtotime', $keys)),
            ];
        }

        $cruces_e = [];
        $ventana  = 7 * 86400;

        foreach ($incidencias as &$inc) {
            if (($inc['c7_subcaso'] ?? '') !== 'C7a') continue;

            $mejor_score = $inc['cruce_score'] ?? 0.0;
            $mejor_id    = null;
            $mejor_nivel = '';
            $mejor_k     = 0;
            $mejor_dir   = '';
            $n1_a        = $meta[$inc['idArticulo']]['familias_n1'] ?? [];
            $ts_a_map    = $inc['_ts_floors'] ?? null;

            foreach ($c7b_data as $id_b => $cb) {
                // Filtro duro N1
                $n1_b = $meta[$id_b]['familias_n1'] ?? [];
                if (!empty($n1_a) && !empty($n1_b) && empty(array_intersect($n1_a, $n1_b))) continue;

                // Emparejamiento ±7 días
                $pairs_a  = [];
                $pairs_b  = [];
                $ts_b_map = $cb['ts_floors'];
                foreach ($inc['_floors_raw'] as $da => $va) {
                    $ts_a = $ts_a_map ? $ts_a_map[$da] : strtotime($da);
                    $best_diff = $ventana + 1;
                    $best_vb   = null;
                    foreach ($cb['floors_raw'] as $db => $vb) {
                        $diff = abs($ts_a - $ts_b_map[$db]);
                        if ($diff <= $ventana && $diff < $best_diff) {
                            $best_diff = $diff;
                            $best_vb   = $vb;
                        }
                    }
                    if ($best_vb !== null) {
                        $pairs_a[] = $va;
                        $pairs_b[] = $best_vb;
                    }
                }
                $n_pairs = count($pairs_a);
                if ($n_pairs < 5) continue;

                [$mu_a, $sd_a] = PosstockStatistics::statsFloors($pairs_a);
                [$mu_b, $sd_b] = PosstockStatistics::statsFloors($pairs_b);

                if ($mu_a < 1.5 || abs($mu_b) < 1.5) continue;

                $k_raw = $mu_a / abs($mu_b);
                if ($k_raw >= 1.4) {
                    $k   = (int)round($k_raw);
                    $dir = 'A_por_B';
                } elseif ($k_raw <= 0.71) {
                    $k   = (int)round(1.0 / $k_raw);
                    $dir = 'B_por_A';
                } else {
                    continue;   // k ≈ 1: cubierto por C7c
                }
                if ($k < 2 || $k > 5) continue;

                $k_real = ($dir === 'A_por_B') ? $k_raw : (1.0 / $k_raw);
                if (abs($k_real - $k) / $k > 0.20) continue;

                $eps          = 0.4 * $k;
                $suma_scaled  = ($dir === 'A_por_B')
                    ? abs($mu_a + $k * $mu_b)
                    : abs($k * $mu_a + $mu_b);
                if ($suma_scaled > $eps) continue;

                $cv_a = ($mu_a > 0.0) ? $sd_a / $mu_a : PHP_FLOAT_MAX;
                $cv_b = (abs($mu_b) > 0.0) ? $sd_b / abs($mu_b) : PHP_FLOAT_MAX;
                if ($cv_a > 0.70 || $cv_b > 0.70) continue;

                $var_ratio = 1.0;
                if ($sd_a > 0.0 && $sd_b > 0.0) {
                    $var_ratio = ($dir === 'A_por_B')
                        ? ($k * $k * $sd_a * $sd_a) / ($sd_b * $sd_b)
                        : ($sd_a * $sd_a) / ($k * $k * $sd_b * $sd_b);
                    if ($var_ratio < 0.5 || $var_ratio > 2.0) continue;
                }

                if ($dir === 'A_por_B') {
                    $scaled_b = array_map(fn($v) => $k * abs($v), $pairs_b);
                    $corr = ($n_pairs >= 4) ? PosstockStatistics::pearsonCorr($pairs_a, $scaled_b) : 0.5;
                } else {
                    $scaled_a = array_map(fn($v) => $k * $v, $pairs_a);
                    $corr = ($n_pairs >= 4) ? PosstockStatistics::pearsonCorr($scaled_a, array_map('abs', $pairs_b)) : 0.5;
                }
                if ($n_pairs >= 4 && $corr < 0.50) continue;

                $s_mag    = max(0.0, 1.0 - $suma_scaled / $eps);
                $s_corr   = max(0.0, (float)$corr);
                $s_disp   = max(0.0, 1.0 - abs(log(sqrt($var_ratio))) / 2.0);
                $s_nombre = PosstockStatistics::jaccardNombre(
                    $meta[$inc['idArticulo']]['nombre'] ?? '',
                    $meta[$id_b]['nombre'] ?? ''
                );
                $score = $s_mag * 0.40 + $s_corr * 0.30 + $s_disp * 0.20 + $s_nombre * 0.10;

                if ($score < 0.50) continue;
                $nivel = ($score >= 0.85) ? 'confirmado' : (($score >= 0.70) ? 'probable' : 'posible');

                if ($score > $mejor_score) {
                    $mejor_score = $score;
                    $mejor_id    = $id_b;
                    $mejor_nivel = $nivel;
                    $mejor_k     = $k;
                    $mejor_dir   = $dir;
                }
            }

            if ($mejor_id !== null) {
                $causa = ($mejor_dir === 'A_por_B')
                    ? sprintf(
                        'Merma con patrón múltiplo k=%d respecto a art. %d (%s, score=%.2f): posible cobro de %d uds. de este artículo como 1 ud. de art. %d en la balanza',
                        $mejor_k, $mejor_id, $mejor_nivel, $mejor_score, $mejor_k, $mejor_id
                    )
                    : sprintf(
                        'Merma con patrón múltiplo k=%d respecto a art. %d (%s, score=%.2f): posible cobro de 1 ud. de este artículo como %d uds. de art. %d en la balanza',
                        $mejor_k, $mejor_id, $mejor_nivel, $mejor_score, $mejor_k, $mejor_id
                    );

                $inc['posible_cruce_con'] = $mejor_id;
                $inc['cruce_score']       = round($mejor_score, 2);
                $inc['cruce_nivel']       = $mejor_nivel;
                $inc['cruce_tipo']        = 'multiplo';
                $inc['cruce_ratio_k']     = $mejor_k;
                $inc['cruce_ratio_dir']   = $mejor_dir;
                $inc['posible_causa']     = $causa;
                $cruces_e[$inc['idArticulo']] = [
                    'id_b'  => $mejor_id,
                    'score' => round($mejor_score, 2),
                    'nivel' => $mejor_nivel,
                    'k'     => $mejor_k,
                    'dir'   => $mejor_dir,
                ];
            }
        }
        unset($inc);

        // Propagar al lado C7b
        if (!empty($cruces_e)) {
            $c7b_cruce = [];
            foreach ($cruces_e as $id_a => $data) {
                $id_b = $data['id_b'];
                if (!isset($c7b_cruce[$id_b]) || $data['score'] > $c7b_cruce[$id_b]['score']) {
                    $c7b_cruce[$id_b] = ['id_a' => $id_a] + $data;
                }
            }
            foreach ($incidencias as &$inc) {
                if (($inc['c7_subcaso'] ?? '') !== 'C7b') continue;
                if (!isset($c7b_cruce[$inc['idArticulo']])) continue;
                $data = $c7b_cruce[$inc['idArticulo']];
                if (isset($inc['cruce_score']) && $data['score'] <= $inc['cruce_score']) continue;
                $k    = $data['k'];
                $dir  = $data['dir'];
                $id_a = $data['id_a'];
                $inc['posible_cruce_con'] = $id_a;
                $inc['cruce_score']       = $data['score'];
                $inc['cruce_nivel']       = $data['nivel'];
                $inc['cruce_tipo']        = 'multiplo';
                $inc['cruce_ratio_k']     = $k;
                $inc['cruce_ratio_dir']   = $dir;
                $inc['posible_causa'] = sprintf(
                    'Déficit de ~%.0f ud. con patrón múltiplo k=%d respecto a art. %d (%s, score=%.2f): probable confusión en balanza o recepción no registrada',
                    abs((float)$inc['offset_estimado']),
                    $k,
                    $id_a,
                    $data['nivel'],
                    $data['score']
                );
            }
            unset($inc);
        }
    }

    // ── Helpers privados ────────────────────────────────────────────────────

    /**
     * Empareja suelos de dos artículos por fecha con ventana de ±7 días.
     *
     * @return array  [pairs_a, pairs_b]  Arrays de floats emparejados
     */
    private function emparejarFloors(array $floors_a, array $floors_b): array
    {
        $ventana = 7 * 86400;
        $pairs_a = [];
        $pairs_b = [];
        foreach ($floors_a as $da => $va) {
            $ts_a      = strtotime($da);
            $best_diff = $ventana + 1;
            $best_vb   = null;
            foreach ($floors_b as $db => $vb) {
                $diff = abs($ts_a - strtotime($db));
                if ($diff <= $ventana && $diff < $best_diff) {
                    $best_diff = $diff;
                    $best_vb   = $vb;
                }
            }
            if ($best_vb !== null) {
                $pairs_a[] = $va;
                $pairs_b[] = $best_vb;
            }
        }
        return [$pairs_a, $pairs_b];
    }
}
