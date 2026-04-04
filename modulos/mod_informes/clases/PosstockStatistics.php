<?php

/**
 * PosstockStatistics — Funciones matemáticas y estadísticas puras de POSStock.
 *
 * Extraído de ClasePosstock en la Fase 1 del Plan de Refactorización (3-abr-2026).
 *
 * Contiene únicamente funciones puras (sin dependencia de BD ni de estado):
 *
 *   Distribuciones:
 *     normalQuantile($p)                     — cuantil distribución normal estándar
 *     gammaQuantileWH($alpha, $theta, $p)    — cuantil Gamma via Wilson-Hilferty
 *
 *   Modelos de rotura C5:
 *     roturaGamma(...)                       — detector Gamma (ex _calcularRoturasC5Gamma)
 *     roturaPoisson(...)                     — detector Poisson/BN (ex _calcularRoturasC5Poisson)
 *     autoDispatch(...)                      — selector de modelo (ex _autoDispatchC5)
 *
 *   Estadística descriptiva:
 *     statsFloors($floors)                   — [mean, sd, n]
 *     pearsonCorr($x, $y)                    — coeficiente de Pearson
 *     regressionStats($y)                    — OLS simple: slope, r², p_value, se
 *     jaccardNombre($a, $b)                  — similitud Jaccard sobre bolsa de palabras
 *
 * Todos los métodos son public static para facilitar el testing sin instanciar la clase.
 * ClasePosstock delega a esta clase y conserva los métodos privados originales como proxies
 * (véase ClasePosstock — comentarios FASE 1) hasta que la refactorización esté completa.
 */

declare(strict_types=1);

class PosstockStatistics
{
    // Distribuciones

    /**
     * Cuantil de la distribución normal estándar mediante la aproximación racional
     * de Peter Acklam (error máximo < 1.15×10⁻⁹).
     */
    public static function normalQuantile(float $p): float
    {
        $p = max(1e-15, min(1 - 1e-15, $p));
        $a = [
            -3.969683028665376e+01,
            2.209460984245205e+02,
            -2.759285104469687e+02,
            1.383577518672690e+02,
            -3.066479806614716e+01,
            2.506628277459239e+00
        ];
        $b = [
            -5.447609879822406e+01,
            1.615858368580409e+02,
            -1.556989798598866e+02,
            6.680131188771972e+01,
            -1.328068155288572e+01
        ];
        $c = [
            -7.784894002430293e-03,
            -3.223964580411365e-01,
            -2.400758277161838e+00,
            -2.549732539343734e+00,
            4.374664141464968e+00,
            2.938163982698783e+00
        ];
        $d = [
            7.784695709041462e-03,
            3.224671290700398e-01,
            2.445134137142996e+00,
            3.754408661907416e+00
        ];

        $p_low = 0.02425;
        if ($p < $p_low) {
            $q = sqrt(-2.0 * log($p));
            return (((((($c[0] * $q + $c[1]) * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) /
                ((((($d[0] * $q + $d[1]) * $q + $d[2]) * $q + $d[3]) * $q + 1.0)));
        } elseif ($p <= 1.0 - $p_low) {
            $q = $p - 0.5;
            $r = $q * $q;
            return (((((($a[0] * $r + $a[1]) * $r + $a[2]) * $r + $a[3]) * $r + $a[4]) * $r + $a[5]) * $q /
                (((((($b[0] * $r + $b[1]) * $r + $b[2]) * $r + $b[3]) * $r + $b[4]) * $r + 1.0)));
        } else {
            $q = sqrt(-2.0 * log(1.0 - $p));
            return - (((((($c[0] * $q + $c[1]) * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) /
                ((((($d[0] * $q + $d[1]) * $q + $d[2]) * $q + $d[3]) * $q + 1.0)));
        }
    }

    /**
     * Cuantil de la distribución Gamma(α, θ) mediante la aproximación Wilson-Hilferty.
     *
     * X ~ Gamma(α, scale=θ)  →  E[X] = α·θ,  Var[X] = α·θ²
     * Cuantil_p ≈ α·θ · max(0, 1 − 1/(9α) + z_p/√(9α))³
     *
     * Error < 1 % para α > 0.5; exacto en el límite α → ∞ (normal).
     */
    public static function gammaQuantileWH(float $alpha, float $theta, float $p): float
    {
        if ($alpha <= 0.0 || $theta <= 0.0) return 0.0;
        $z    = self::normalQuantile($p);
        $term = 1.0 - 1.0 / (9.0 * $alpha) + $z / sqrt(9.0 * $alpha);
        if ($term <= 0.0) return 0.0;
        return $alpha * $theta * ($term ** 3);
    }

    // Modelos de rotura C5

    /**
     * Modelo Gamma para detección de roturas (C5).
     *
     * Ajusta una distribución Gamma a los gaps observados entre días de venta
     * y usa el cuantil (1 − umbral_prob) como umbral de anomalía.
     */
    public static function roturaGamma(
        int    $id,
        array  $fechas_map,
        float  $stock_actual,
        int    $ff_ts,
        int    $min_ventas,
        float  $umbral_prob,
        bool   $incluir_stock_negativo = false,
        string $label = 'Gamma'
    ): array {
        $fechas = array_keys($fechas_map);
        sort($fechas);
        $n = count($fechas);
        if ($n < $min_ventas) return [];

        $ts = array_map('strtotime', $fechas);

        $gaps = [];
        for ($i = 1; $i < $n; $i++) {
            $gap = (int)(($ts[$i] - $ts[$i - 1]) / 86400);
            if ($gap > 0) $gaps[] = (float)$gap;
        }
        if (count($gaps) < 2) return [];

        $n_gaps  = count($gaps);
        $avg_gap = array_sum($gaps) / $n_gaps;
        $var_gap = array_sum(array_map(fn($g) => ($g - $avg_gap) ** 2, $gaps)) / ($n_gaps - 1);
        $sd_gap  = sqrt(max(0.0, $var_gap));

        // Ajuste gamma; si la varianza es prácticamente nula, usar media+3σ como fallback
        if ($var_gap > 1e-9 && $avg_gap > 1e-9) {
            $alpha_gap    = ($avg_gap ** 2) / $var_gap;
            $theta_gap    = $var_gap / $avg_gap;
            $umbral_gap   = self::gammaQuantileWH($alpha_gap, $theta_gap, 1.0 - $umbral_prob);
            $modelo_usado = $label;          // 'Gamma' | 'GammaReg' según el contexto de llamada
        } else {
            $umbral_gap   = $avg_gap + 3.0 * $sd_gap;
            $modelo_usado = 'Normal';        // varianza ≈ 0: umbral = media + 3σ (Normal)
        }

        $umbral_ceil  = (int)ceil($umbral_gap);
        $avg_gap_real = $avg_gap;

        $campos = [
            'idArticulo'            => $id,
            'tipo'                  => 'Venta Cero (Posible Rotura Física)',
            'stock_actual'          => $stock_actual,
            'avg_dias_entre_ventas' => round($avg_gap, 1),
            'sd_dias'               => round($sd_gap, 1),
            'umbral_dias'           => round($umbral_gap, 1),
            'posible_causa'         => 'Hueco en lineal o merma no registrada',
            'modelo_usado'          => $modelo_usado,
        ];

        $incidencias = [];
        for ($i = 1; $i < $n; $i++) {
            $gap = (int)(($ts[$i] - $ts[$i - 1]) / 86400);
            if ($gap > $umbral_gap) {
                $inicio            = date('Y-m-d', $ts[$i - 1] + $umbral_ceil * 86400);
                $fin               = $fechas[$i];
                $dias_confirmados  = $gap - $umbral_ceil;
                $rotura_confirmada = $inicio < $fin;
                $cr                = $rotura_confirmada && ($dias_confirmados > $umbral_ceil);
                $dias_real         = (int)((strtotime($fin) - strtotime($inicio)) / 86400);
                $rk                = $rotura_confirmada && !$cr && $dias_real >= (int)ceil($avg_gap_real);
                $sev               = $cr ? 'ALTA' : 'MEDIA';
                $incidencias[] = $campos + [
                    'severidad'           => $sev,
                    'ultima_venta'        => $fechas[$i - 1],
                    'fecha_inicio_rotura' => $inicio,
                    'fecha_fin_rotura'    => $fin,
                    'dias_rotura'         => $gap,
                    'rotura_confirmada'   => $rotura_confirmada,
                    'cr'                  => $cr,
                    'rk'                  => $rk,
                    'ko'                  => false,
                ];
            }
        }

        // Rotura en curso: desde última venta hasta ff_mov.
        $dias_final = (int)(($ff_ts - $ts[$n - 1]) / 86400);
        if ($dias_final > $umbral_gap) {
            $inicio_ko    = date('Y-m-d', $ts[$n - 1] + $umbral_ceil * 86400);
            $hoy_ts       = time();
            $fin_virtual  = date('Y-m-d', min($ff_ts, $hoy_ts));
            $dias_conf_ko = $dias_final - $umbral_ceil;
            $rot_conf_ko  = $inicio_ko < $fin_virtual;
            $cr_ko        = $rot_conf_ko && ($dias_conf_ko > $umbral_ceil);
            $dias_real_ko = $rot_conf_ko
                ? (int)((min($ff_ts, $hoy_ts) - strtotime($inicio_ko)) / 86400)
                : 0;
            $rk_ko  = $rot_conf_ko && !$cr_ko && $dias_real_ko >= (int)ceil($avg_gap_real);
            $ko_val = $stock_actual <= 0;
            $sev_ko = $ko_val ? 'CRITICA' : ($cr_ko ? 'ALTA' : 'MEDIA');
            $incidencias[] = $campos + [
                'severidad'           => $sev_ko,
                'ultima_venta'        => $fechas[$n - 1],
                'fecha_inicio_rotura' => $inicio_ko,
                'fecha_fin_rotura'    => null,
                'dias_rotura'         => $dias_final,
                'rotura_confirmada'   => false,
                'cr'                  => $cr_ko,
                'rk'                  => $rk_ko,
                'ko'                  => $ko_val,
            ];
        }
        return $incidencias;
    }

    /**
     * Modelo Poisson / Binomial Negativa adaptativo para detección de roturas (C5).
     *
     * El modelo se selecciona automáticamente en función de la dispersión real
     * de las ventas del artículo dentro del periodo.
     *
     * Poisson (umbral de gap):  gap > −ln(p) / λ_día
     * Binomial Negativa:        r = μ² / (s² − μ), umbral via fórmula BN
     */
    public static function roturaPoisson(
        int   $id,
        array $fechas_map,
        float $stock_actual,
        int   $ff_ts,
        int   $min_ventas,
        float $umbral_prob,
        int   $periodo_dias,
        bool  $incluir_stock_negativo = false,
        int   $ff_stats_ts = 0
    ): array {
        $fechas = array_keys($fechas_map);
        sort($fechas);
        $n = count($fechas);
        if ($n < $min_ventas) return [];

        $ts         = array_map('strtotime', $fechas);
        $lambda_dia = $n / max(1, $periodo_dias);
        if ($lambda_dia <= 0) return [];

        // Detectar sobredispersión mediante sub-ventanas
        $fi_period_ts = ($ff_stats_ts ?: $ff_ts) - ($periodo_dias - 1) * 86400;
        if ($periodo_dias < 40) {
            $chunk_days = 1;
        } elseif ($periodo_dias <= 130) {
            $chunk_days = 5;
        } else {
            $chunk_days = 10;
        }
        $n_chunks     = (int)ceil($periodo_dias / $chunk_days);

        $chunk_counts = array_fill(0, $n_chunks, 0);
        foreach ($ts as $t) {
            $offset = (int)(($t - $fi_period_ts) / 86400);
            $chunk  = min($n_chunks - 1, max(0, (int)floor($offset / $chunk_days)));
            $chunk_counts[$chunk]++;
        }

        $mu_chunk = $n / $n_chunks;
        $s2_chunk = 0.0;
        if ($n_chunks > 1) {
            foreach ($chunk_counts as $c) {
                $s2_chunk += ($c - $mu_chunk) ** 2;
            }
            $s2_chunk /= ($n_chunks - 1);   // varianza muestral insesgada
        }

        // Selección del modelo y cálculo del umbral de gap
        $modelo_usado = 'Poisson';
        if ($n_chunks >= 4 && $s2_chunk > $mu_chunk + 1e-9 && $mu_chunk > 1e-9) {
            // Sobredispersión confirmada → Binomial Negativa
            $r        = max(0.001, ($mu_chunk ** 2) / ($s2_chunk - $mu_chunk));
            $ln_ratio = log($r / ($r + $mu_chunk));   // siempre < 0
            $umbral_gap   = log($umbral_prob) * $chunk_days / ($r * $ln_ratio);
            $modelo_usado = 'BN';
        } else {
            // Poisson puro: gap > −ln(p) / λ_día
            $umbral_gap = -log($umbral_prob) / $lambda_dia;
        }

        $umbral_ceil = (int)ceil($umbral_gap);

        $campos = [
            'idArticulo'            => $id,
            'tipo'                  => 'Venta Cero (Posible Rotura Física)',
            'stock_actual'          => $stock_actual,
            'avg_dias_entre_ventas' => round($periodo_dias / $n, 1),
            'sd_dias'               => null,
            'umbral_dias'           => round($umbral_gap, 1),
            'posible_causa'         => 'Hueco en lineal o merma no registrada',
            'modelo_usado'          => $modelo_usado,
        ];

        $avg_gap_real = $n > 1 ? $periodo_dias / $n : 1.0;
        $incidencias  = [];
        for ($i = 1; $i < $n; $i++) {
            $gap = (int)(($ts[$i] - $ts[$i - 1]) / 86400);
            if ($gap > $umbral_gap) {
                $inicio            = date('Y-m-d', $ts[$i - 1] + $umbral_ceil * 86400);
                $fin               = $fechas[$i];
                $dias_confirmados  = $gap - $umbral_ceil;
                $rotura_confirmada = $inicio < $fin;
                $cr                = $rotura_confirmada && ($dias_confirmados > $umbral_ceil);
                $dias_real         = (int)((strtotime($fin) - strtotime($inicio)) / 86400);
                $rk                = $rotura_confirmada && !$cr && $dias_real >= (int)ceil($avg_gap_real);
                $sev = $cr ? 'ALTA' : 'MEDIA';
                $incidencias[] = $campos + [
                    'severidad'           => $sev,
                    'ultima_venta'        => $fechas[$i - 1],
                    'fecha_inicio_rotura' => $inicio,
                    'fecha_fin_rotura'    => $fin,
                    'dias_rotura'         => $gap,
                    'rotura_confirmada'   => $rotura_confirmada,
                    'cr'                  => $cr,
                    'rk'                  => $rk,
                    'ko'                  => false,
                ];
            }
        }
        $dias_final = (int)(($ff_ts - $ts[$n - 1]) / 86400);
        if ($dias_final > $umbral_gap) {
            $inicio_ko    = date('Y-m-d', $ts[$n - 1] + $umbral_ceil * 86400);
            $hoy_ts       = time();
            $fin_virtual  = date('Y-m-d', min($ff_ts, $hoy_ts));
            $dias_conf_ko = $dias_final - $umbral_ceil;
            $rot_conf_ko  = $inicio_ko < $fin_virtual;
            $cr_ko        = $rot_conf_ko && ($dias_conf_ko > $umbral_ceil);
            $dias_real_ko = $rot_conf_ko
                ? (int)((min($ff_ts, $hoy_ts) - strtotime($inicio_ko)) / 86400)
                : 0;
            $rk_ko  = $rot_conf_ko && !$cr_ko && $dias_real_ko >= (int)ceil($avg_gap_real);
            $ko_val = $stock_actual <= 0;
            $sev_ko = $ko_val ? 'CRITICA' : ($cr_ko ? 'ALTA' : 'MEDIA');
            $incidencias[] = $campos + [
                'severidad'           => $sev_ko,
                'ultima_venta'        => $fechas[$n - 1],
                'fecha_inicio_rotura' => $inicio_ko,
                'fecha_fin_rotura'    => null,
                'dias_rotura'         => $dias_final,
                'rotura_confirmada'   => false,
                'cr'                  => $cr_ko,
                'rk'                  => $rk_ko,
                'ko'                  => $ko_val,
            ];
        }
        return $incidencias;
    }

    /**
     * Modo automático C5: selecciona el modelo estadístico más adecuado para cada artículo.
     *
     * Árbol de decisión:
     *   RAMA 1 — Alta rotación (>80 % de días con venta):
     *     · Gaps muy regulares (CV < umbral) → Gamma
     *     · Gaps más variables              → Poisson
     *   RAMA 2 — Rotación media/baja (≤80 %):
     *     · Demanda en rachas (sobredispersión) → BN via roturaPoisson
     *     · Muy esporádico (rotación < 15 %)   → Poisson
     *     · General o tipo-peso                → Gamma
     */
    public static function autoDispatch(
        int   $id,
        array $fechas_map,
        float $stock_actual,
        int   $ff_ts,
        int   $min_ventas,
        float $umbral_prob,
        int   $periodo_dias,
        bool  $incluir_stock_negativo,
        int   $ff_stats_ts = 0
    ): array {
        $fechas = array_keys($fechas_map);
        sort($fechas);
        $ts  = array_map('strtotime', $fechas);
        $n   = count($ts);
        if ($n < $min_ventas) return [];

        $rotation = $n / max(1, $periodo_dias);   // fracción de días con venta

        // Gaps entre ventas consecutivas
        $gaps = [];
        for ($i = 1; $i < $n; $i++) {
            $g = (int)(($ts[$i] - $ts[$i - 1]) / 86400);
            if ($g > 0) $gaps[] = (float)$g;
        }

        $cv_g = 1.0;
        if (count($gaps) >= 2) {
            $n_g   = count($gaps);
            $mu_g  = array_sum($gaps) / $n_g;
            $var_g = array_sum(array_map(fn($g) => ($g - $mu_g) ** 2, $gaps)) / ($n_g - 1);
            $cv_g  = $mu_g > 1e-9 ? sqrt(max(0.0, $var_g)) / $mu_g : 1.0;
        }

        // Sobredispersión de chunks (ventas en rachas)
        $ff_for_chunks = $ff_stats_ts ?: $ff_ts;
        $fi_period_ts = $ff_for_chunks - ($periodo_dias - 1) * 86400;
        if ($periodo_dias < 40)       $chunk_days = 1;
        elseif ($periodo_dias <= 130) $chunk_days = 5;
        else                          $chunk_days = 10;
        $n_chunks     = (int)ceil($periodo_dias / $chunk_days);
        $chunk_counts = array_fill(0, $n_chunks, 0);
        foreach ($ts as $t) {
            $offset = (int)(($t - $fi_period_ts) / 86400);
            $chunk  = min($n_chunks - 1, max(0, (int)floor($offset / $chunk_days)));
            $chunk_counts[$chunk]++;
        }
        $mu_chunk = $n / $n_chunks;
        $s2_chunk = 0.0;
        if ($n_chunks > 1) {
            foreach ($chunk_counts as $c) $s2_chunk += ($c - $mu_chunk) ** 2;
            $s2_chunk /= ($n_chunks - 1);
        }
        $overdispersed = $n_chunks >= 4 && $mu_chunk > 1e-9 && $s2_chunk > $mu_chunk + 1e-9;

        // Heurística "tipo peso"
        $is_peso = false;
        if (count($gaps) >= 2) {
            $n_g      = count($gaps);
            $frac_sum = 0.0;
            $frac_sq  = 0.0;
            foreach ($gaps as $g) {
                $f = $g - floor($g);
                $frac_sum += $f;
                $frac_sq  += $f * $f;
            }
            $frac_mean = $frac_sum / $n_g;
            $frac_var  = $n_g > 1 ? ($frac_sq - $n_g * $frac_mean ** 2) / ($n_g - 1) : 0.0;
            $is_peso   = $frac_mean > 0.05 && $frac_var > 0.001;
        }

        // Árbol de decisión        // RAMA 1: Alta rotación (>80 % días con venta)
        if ($rotation > 0.80) {
            // Tipo peso o gaps muy regulares → Gamma (cuantil directo)
            $cv_th = $is_peso ? 0.65 : 0.45;
            if ($cv_g < $cv_th) {
                return self::roturaGamma(
                    $id,
                    $fechas_map,
                    $stock_actual,
                    $ff_ts,
                    $min_ventas,
                    $umbral_prob,
                    $incluir_stock_negativo,
                    'GammaReg'
                );
            }
            // Alta rotación, gaps más variables → Poisson (inter-arrivals ~ Exponencial)
            return self::roturaPoisson(
                $id,
                $fechas_map,
                $stock_actual,
                $ff_ts,
                $min_ventas,
                $umbral_prob,
                $periodo_dias,
                $incluir_stock_negativo,
                $ff_stats_ts
            );
        }

        // RAMA 2: Rotación media/baja (≤80 %)
        // 2a. Demanda en rachas → Binomial Negativa (activa internamente en roturaPoisson)
        if ($overdispersed) {
            return self::roturaPoisson(
                $id,
                $fechas_map,
                $stock_actual,
                $ff_ts,
                $min_ventas,
                $umbral_prob,
                $periodo_dias,
                $incluir_stock_negativo,
                $ff_stats_ts
            );
        }

        // 2b. Muy esporádico (no tipo peso) → Poisson (proceso de eventos raros)
        if (!$is_peso && $rotation < 0.15) {
            return self::roturaPoisson(
                $id,
                $fechas_map,
                $stock_actual,
                $ff_ts,
                $min_ventas,
                $umbral_prob,
                $periodo_dias,
                $incluir_stock_negativo,
                $ff_stats_ts
            );
        }

        // 2c. General / tipo peso → Gamma (ajuste a distribución de gaps, cuantil de rotura)
        return self::roturaGamma(
            $id,
            $fechas_map,
            $stock_actual,
            $ff_ts,
            $min_ventas,
            $umbral_prob,
            $incluir_stock_negativo
        );
    }

    // Estadística descriptiva

    /**
     * Media, desviación típica muestral y tamaño de un array de suelos de stock.
     * Devuelve [mean, sd, n].
     */
    public static function statsFloors(array $floors): array
    {
        $n = count($floors);
        if ($n === 0) return [0.0, 0.0, 0];
        $mean = array_sum($floors) / $n;
        $var  = 0.0;
        foreach ($floors as $v) $var += ($v - $mean) ** 2;
        $sd = $n > 1 ? sqrt($var / ($n - 1)) : 0.0;
        return [$mean, $sd, $n];
    }

    /**
     * Correlación de Pearson entre dos arrays de igual longitud.
     * Devuelve 0.0 si n < 2 o si alguna serie es constante.
     */
    public static function pearsonCorr(array $x, array $y): float
    {
        $n = count($x);
        if ($n < 2 || $n !== count($y)) return 0.0;
        $mx = array_sum($x) / $n;
        $my = array_sum($y) / $n;
        $num = 0.0;
        $dx2 = 0.0;
        $dy2 = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $dx  = $x[$i] - $mx;
            $dy  = $y[$i] - $my;
            $num += $dx * $dy;
            $dx2 += $dx * $dx;
            $dy2 += $dy * $dy;
        }
        $denom = sqrt($dx2 * $dy2);
        return $denom > 0.0 ? $num / $denom : 0.0;
    }

    /**
     * Regresión lineal simple OLS sobre y[0..n-1] con x = 0, 1, …, n-1.
     *
     * Devuelve:
     *   'slope'   — pendiente β̂₁  (ud/recepción)
     *   'r2'      — coeficiente de determinación R²
     *   'p_value' — p-valor bilateral aproximado para el test t sobre β̂₁
     *               (0.01 si ajuste perfecto positivo, 0.05 si significativa al 10 %,
     *                0.15 si no lo es, 1.0 si no calculable)
     *   'se'      — error estándar de β̂₁
     */
    public static function regressionStats(array $y): array
    {
        $n = count($y);
        if ($n < 3) return ['slope' => 0.0, 'r2' => 0.0, 'p_value' => 1.0, 'se' => 0.0];

        $sum_x  = 0;
        $sum_y  = 0.0;
        $sum_xy = 0.0;
        $sum_xx = 0;
        for ($i = 0; $i < $n; $i++) {
            $sum_x  += $i;
            $sum_y  += $y[$i];
            $sum_xy += $i * $y[$i];
            $sum_xx += $i * $i;
        }
        $denom = $n * $sum_xx - $sum_x * $sum_x;
        if ($denom == 0.0) return ['slope' => 0.0, 'r2' => 0.0, 'p_value' => 1.0, 'se' => 0.0];

        $slope     = ($n * $sum_xy - $sum_x * $sum_y) / $denom;
        $intercept = ($sum_y - $slope * $sum_x) / $n;
        $mean_y    = $sum_y / $n;

        $ss_tot = 0.0;
        $ss_res = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $y_hat   = $intercept + $slope * $i;
            $ss_res += ($y[$i] - $y_hat) ** 2;
            $ss_tot += ($y[$i] - $mean_y) ** 2;
        }
        $r2 = ($ss_tot > 0.0) ? max(0.0, 1.0 - $ss_res / $ss_tot) : 0.0;

        // t-estadístico: β̂₁ / se(β̂₁), df = n-2
        $sxx = (float)$sum_xx - (float)($sum_x * $sum_x) / $n;
        // C7a-014: guard near-zero SSE (floors perfectamente colineales o casi).
        // se=0.0 hace que el Nivel 4 de la cascada C7a no ejecute.
        // p_value=0.01 evita la "certeza perfecta" artificial: Mann-Kendall tomará la decisión.
        if ($ss_res < 1e-9 || $sxx <= 0.0) {
            return ['slope' => $slope, 'r2' => 1.0, 'p_value' => ($slope > 0.0 ? 0.01 : 1.0), 'se' => 0.0];
        }
        $se = sqrt(($ss_res / ($n - 2)) / $sxx);
        $t  = abs($slope / $se);

        // Valores críticos t_{df, 0.95} (bilateral α = 0.10) — lookup + interpolación lineal
        static $t_tab = [
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
            11 => 1.796,
            12 => 1.782,
            13 => 1.771,
            14 => 1.761,
            15 => 1.753,
            16 => 1.746,
            17 => 1.740,
            18 => 1.734,
            19 => 1.729,
            20 => 1.725,
            25 => 1.708,
            30 => 1.697,
            40 => 1.684,
            60 => 1.671,
            120 => 1.658,
        ];
        $df = $n - 2;
        if ($df > 120) {
            $t_crit = 1.645;
        } elseif (isset($t_tab[$df])) {
            $t_crit = $t_tab[$df];
        } else {
            $keys = array_keys($t_tab);
            $lo = $hi = null;
            foreach ($keys as $k) {
                if ($k <= $df) $lo = $k;
                if ($k >= $df && $hi === null) $hi = $k;
            }
            $t_crit = ($lo !== null && $hi !== null && $lo !== $hi)
                ? $t_tab[$lo] + ($df - $lo) / ($hi - $lo) * ($t_tab[$hi] - $t_tab[$lo])
                : ($lo !== null ? $t_tab[$lo] : 1.645);
        }
        return ['slope' => $slope, 'r2' => $r2, 'p_value' => ($t >= $t_crit ? 0.05 : 0.15), 'se' => $se];
    }

    /**
     * Similitud de Jaccard sobre bolsa de palabras (insensible a mayúsculas).
     * Devuelve 0.0–1.0; 1.0 = mismas palabras exactas.
     */
    public static function jaccardNombre(string $a, string $b): float
    {
        $wa = array_filter(preg_split('/\s+/', mb_strtolower($a)));
        $wb = array_filter(preg_split('/\s+/', mb_strtolower($b)));
        if (empty($wa) || empty($wb)) return 0.0;
        $inter = count(array_intersect($wa, $wb));
        $union = count(array_unique(array_merge($wa, $wb)));
        return $union > 0 ? $inter / $union : 0.0;
    }
}
