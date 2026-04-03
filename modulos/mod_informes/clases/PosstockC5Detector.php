<?php

/**
 * PosstockC5Detector — Detector de roturas físicas (Caso C5).
 *
 * Extraído de ClasePosstock como parte de la Fase 3 de refactorización.
 *
 * Métodos públicos:
 *   calcularRoturas(...)  — modelo clásico media + n·σ para detección de roturas
 *   detectar(...)         — orquestador C5 completo
 */

class PosstockC5Detector
{
    public function __construct(
        private mysqli $db,
        private PosstockQueryRepository $repo
    ) {}

    // ══════════════════════════════════════════════════════════════════════════
    // Algoritmos internos (públicos para testabilidad directa)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Modelo clásico media + n·σ para detección de roturas (C5).
     * $sigma_mult determina el número de desviaciones típicas del umbral (defecto: 3).
     */
    public function calcularRoturas(int $id, array $fechas_map, float $stock_actual, int $ff_ts, int $min_ventas, bool $incluir_stock_negativo = false, float $sigma_mult = 3.0): array
    {
        $fechas = array_keys($fechas_map);
        sort($fechas);
        $n = count($fechas);
        if ($n < $min_ventas) return [];
        $ts = array_map('strtotime', $fechas);
        $gaps = [];
        for ($i = 1; $i < $n; $i++) {
            $gap = (int)(($ts[$i] - $ts[$i - 1]) / 86400);
            if ($gap > 0) $gaps[] = $gap;
        }
        if (count($gaps) < 2) return [];
        $n_gaps  = count($gaps);
        $avg_gap = array_sum($gaps) / $n_gaps;
        $var     = array_sum(array_map(fn($g) => ($g - $avg_gap) ** 2, $gaps)) / ($n_gaps - 1);
        $sd      = sqrt($var);
        $umbral  = $avg_gap + $sigma_mult * $sd;
        $umbral_ceil = (int)ceil($umbral);
        $campos = [
            'idArticulo'            => $id,
            'tipo'                  => 'Venta Cero (Posible Rotura Física)',
            'stock_actual'          => $stock_actual,
            'avg_dias_entre_ventas' => round($avg_gap, 1),
            'sd_dias'               => round($sd, 1),
            'umbral_dias'           => round($umbral, 1),
            'posible_causa'         => 'Hueco en lineal o merma no registrada',
        ];
        $incidencias = [];
        for ($i = 1; $i < $n; $i++) {
            $gap = (int)(($ts[$i] - $ts[$i - 1]) / 86400);
            if ($gap > $umbral) {
                $inicio            = date('Y-m-d', $ts[$i - 1] + $umbral_ceil * 86400);
                $fin               = $fechas[$i];
                $dias_confirmados  = $gap - $umbral_ceil;
                $rotura_confirmada = $inicio < $fin;
                $cr                = $rotura_confirmada && ($dias_confirmados > $umbral_ceil);
                $dias_real         = (int)((strtotime($fin) - strtotime($inicio)) / 86400);
                $rk                = $rotura_confirmada && !$cr && $dias_real >= (int)ceil($avg_gap);
                // KO no aplica en roturas recuperadas (no disponemos del stock durante el gap)
                // Severidad: CR→ALTA; resto→MEDIA (RK y detecciones básicas se muestran igual)
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
        // Rotura en curso: desde última venta hasta ff_mov.
        // CR/RK capados a hoy para evitar marcar roturas que aún no han empezado.
        // El filtro de inclusión (c5_incluir_stock_negativo) ya se aplicó upstream.
        $dias_final   = (int)(($ff_ts - $ts[$n - 1]) / 86400);
        if ($dias_final > $umbral) {
            $inicio_ko    = date('Y-m-d', $ts[$n - 1] + $umbral_ceil * 86400);
            $hoy_ts       = time();
            $fin_virtual  = date('Y-m-d', min($ff_ts, $hoy_ts));
            $dias_conf_ko = $dias_final - $umbral_ceil;
            $rot_conf_ko  = $inicio_ko < $fin_virtual;
            $cr_ko        = $rot_conf_ko && ($dias_conf_ko > $umbral_ceil);
            $dias_real_ko = $rot_conf_ko
                ? (int)((min($ff_ts, $hoy_ts) - strtotime($inicio_ko)) / 86400)
                : 0;
            $rk_ko  = $rot_conf_ko && !$cr_ko && $dias_real_ko >= (int)ceil($avg_gap);
            // KO: stock agotado (≤ 0) durante rotura en curso → inventario en descubierto
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

    // ══════════════════════════════════════════════════════════════════════════
    // Orquestador principal
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Orquestador C5 — detecta roturas físicas (hueco en ventas + stock insuficiente).
     *
     * @return array  Incidencias C5 o ['error' => ...]
     */
    public function detectar(
        string $fi_mov,
        string $ff_mov,
        string $fi_stats,            // inicio de la ventana estadística (±1 periodo para semana/quincena/mes)
        float  $umbral_sobrestock,   // no usado en C5, recibido por firma uniforme
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter              = [],
        int    $min_ventas              = 3,
        string $modelo                    = 'automatico', // 'automatico' | 'binomial' | 'poisson_bn' | 'gamma'
        float  $umbral_prob               = 0.05,        // auto/poisson_bn/gamma: P(gap > umbral) < p
        bool   $c5_incluir_stock_negativo = false,       // si false, excluye stock_actual < 0
        float  $binomial_sigma_mult       = 3.0,         // multiplicador σ para modo binomial
        bool   $incluir_albcli            = false,        // incluir albaranes de cliente como ventas
        int    $dias_post               = 14,            // días post-periodo para validar stockouts en curso
        string $ff_stats                = ''             // fin de la ventana estadística; vacío = ff_mov
    ): array {
        // fi_stats/ff_stats: ventana de datos para estadísticas (gaps, distribución).
        // Para semana/quincena/mes se amplía ±1 periodo preservando estacionalidad.
        // ff_mov sigue siendo el límite de detección y rebobinado de stock.
        $ff_stats = $ff_stats ?: $ff_mov;
        $fi   = $this->db->real_escape_string($fi_stats);
        $ff   = $this->db->real_escape_string($ff_mov);
        $ff_stats_esc = $this->db->real_escape_string($ff_stats);

        $where_fam = $this->repo->familiaWhere($familias_incluir, $familias_excluir);
        $where_ids = $this->repo->idsWhere($ids_filter);

        // Paso 1: fechas de venta únicas por artículo físico (ventana estadística fi_stats→ff_stats)
        $rows_ventas = $this->repo->queryVentasFechasC5($fi, $ff_stats_esc, $where_fam, $where_ids, $incluir_albcli);
        if (isset($rows_ventas['error'])) return $rows_ventas;
        if (empty($rows_ventas)) return [];

        $ventas_fechas = [];
        foreach ($rows_ventas as $r) {
            $ventas_fechas[(int)$r['idArticulo']][$r['fecha']] = true;
        }

        // Paso 2: stock en ff_mov via rebobinado desde articulosStocks.stockOn
        $ids_str = implode(',', array_keys($ventas_fechas));
        $rows_stock = $this->repo->queryStockRebobinado($ids_str, $ff, false);
        if (isset($rows_stock['error'])) return $rows_stock;

        $stock_actual = [];
        foreach ($rows_stock as $r) {
            $stock_actual[(int)$r['idArticulo']] = (float)$r['stock_en_periodo'];
        }

        // Si no se incluye stock negativo, excluir artículos con stock_actual < 0
        // (ya aparecen en C1 como stock negativo; KO requiere stock > 0 de todas formas)
        if (!$c5_incluir_stock_negativo) {
            $ventas_fechas = array_filter(
                $ventas_fechas,
                function ($_, $id) use ($stock_actual) {
                    return ($stock_actual[$id] ?? 0.0) >= 0;
                },
                ARRAY_FILTER_USE_BOTH
            );
        }

        // Paso 3: lógica Caso 5 — detecta TODAS las roturas (binomial o Poisson)
        // periodo_dias usa la ventana estadística completa (fi_stats→ff_stats) para que
        // la media y σ sean representativos incluso en vistas cortas (semana/quincena/mes).
        $ff_ts        = strtotime($ff_mov);    // límite de detección (no cambia)
        $periodo_dias = max(1, (int)round((strtotime($ff_stats) - strtotime($fi_stats)) / 86400) + 1);
        $incidencias  = [];

        foreach ($ventas_fechas as $id => $fechas_map) {
            $sa = $stock_actual[$id] ?? 0.0;
            $ff_stats_ts = strtotime($ff_stats);
            $roturas = match ($modelo) {
                'automatico' => PosstockStatistics::autoDispatch(
                    $id,
                    $fechas_map,
                    $sa,
                    $ff_ts,
                    $min_ventas,
                    $umbral_prob,
                    $periodo_dias,
                    $c5_incluir_stock_negativo,
                    $ff_stats_ts
                ),
                'gamma' => PosstockStatistics::roturaGamma(
                    $id,
                    $fechas_map,
                    $sa,
                    $ff_ts,
                    $min_ventas,
                    $umbral_prob,
                    $c5_incluir_stock_negativo
                ),
                'poisson_bn', 'poisson' => PosstockStatistics::roturaPoisson(
                    $id,
                    $fechas_map,
                    $sa,
                    $ff_ts,
                    $min_ventas,
                    $umbral_prob,
                    $periodo_dias,
                    $c5_incluir_stock_negativo,
                    $ff_stats_ts
                ),
                default => $this->calcularRoturas(  // 'binomial'
                    $id,
                    $fechas_map,
                    $sa,
                    $ff_ts,
                    $min_ventas,
                    $c5_incluir_stock_negativo,
                    $binomial_sigma_mult
                ),
            };
            foreach ($roturas as $r) $incidencias[] = $r;
        }

        // Paso 4: anti-falso-positivo — validar stockouts en curso con ventas post-periodo.
        // Si el artículo vendió en los $dias_post días siguientes a ff_mov, la rotura
        // se considera recuperada (igual que el mecanismo C3b "primera_venta_post").
        if ($dias_post > 0 && !empty($incidencias)) {
            $ids_en_curso = [];
            foreach ($incidencias as $inc) {
                if ($inc['fecha_fin_rotura'] === null) {
                    $ids_en_curso[$inc['idArticulo']] = true;
                }
            }
            if (!empty($ids_en_curso)) {
                $ids_str_curso = implode(',', array_keys($ids_en_curso));
                $fi_post_esc   = $this->db->real_escape_string(
                    date('Y-m-d', strtotime($ff_mov . ' +1 day'))
                );
                $ff_post_esc   = $this->db->real_escape_string(
                    date('Y-m-d', strtotime($ff_mov . " +{$dias_post} days"))
                );
                $ventas_post = $this->repo->queryVentasPostPeriodoC5(
                    $ids_str_curso,
                    $fi_post_esc,
                    $ff_post_esc,
                    $incluir_albcli
                );
                foreach ($incidencias as &$inc) {
                    if ($inc['fecha_fin_rotura'] !== null) continue;
                    $fecha_post = $ventas_post[$inc['idArticulo']] ?? null;
                    if ($fecha_post === null) continue;
                    // Rotura recuperada: actualizar campos
                    $dias_total              = (int)((strtotime($fecha_post) - strtotime($inc['ultima_venta'])) / 86400);
                    $inc['fecha_fin_rotura'] = $fecha_post;
                    $inc['dias_rotura']      = $dias_total;
                    $inc['ko']               = false;
                    // Recalcular CR y RK con el gap real (ahora extendido al post-periodo)
                    $umbral_rec  = (float)($inc['umbral_dias'] ?? 0);
                    $dias_conf   = $dias_total - $umbral_rec;
                    $avg_gap_rec = (float)($inc['avg_dias_entre_ventas'] ?? PHP_INT_MAX);
                    $inc['cr']        = $umbral_rec > 0 && $dias_conf > $umbral_rec;
                    $inc['rk']        = !$inc['cr'] && $umbral_rec > 0 && $dias_total >= $avg_gap_rec;
                    $inc['severidad'] = $inc['cr'] ? 'ALTA' : 'MEDIA';
                }
                unset($inc);
            }
        }

        // Filtrar: solo reportar roturas cuyo inicio cae dentro del periodo seleccionado.
        // El histórico desde fi_stock se usó solo para el cálculo estadístico interno.
        $incidencias = array_values(array_filter(
            $incidencias,
            fn($inc) => isset($inc['fecha_inicio_rotura']) && $inc['fecha_inicio_rotura'] >= $fi_mov
        ));

        // Añadir nombres
        if (!empty($incidencias)) {
            $ids_inc = implode(',', array_unique(array_column($incidencias, 'idArticulo')));
            $smt = $this->db->query(
                "SELECT idArticulo, articulo_name FROM articulos WHERE idArticulo IN ($ids_inc)"
            );
            $nombres = [];
            if ($smt) {
                while ($r = $smt->fetch_assoc()) {
                    $nombres[(int)$r['idArticulo']] = $r['articulo_name'];
                }
            }
            foreach ($incidencias as &$inc) {
                $inc['nombre'] = $nombres[$inc['idArticulo']] ?? '';
            }
            unset($inc);
        }

        return $incidencias;
    }
}
