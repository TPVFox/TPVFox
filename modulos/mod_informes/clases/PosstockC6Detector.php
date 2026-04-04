<?php

/**
 * PosstockC6Detector — Detector de Agotamiento Estimado / Punto de Pedido (Caso C6).
 *
 * Extraído de ClasePosstock como parte de la Fase 4 de refactorización.
 *
 * Métodos públicos:
 *   detectar(...)  — orquestador C6 completo (C6a y C6b comparten esta implementación)
 */

class PosstockC6Detector
{
    public function __construct(
        private mysqli $db,
        private PosstockQueryRepository $repo
    ) {}

    // ══════════════════════════════════════════════════════════════════════════
    // Algoritmos internos (públicos para testabilidad directa)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Determina la severidad de una incidencia C6 dados los parámetros de stock y ROP.
     *
     * @param float $dias_autonomia Días de stock disponible al ritmo de demanda actual
     * @param float $stock          Stock actual
     * @param float $ROP            Punto de pedido calculado
     * @param int   $L              Lead time en días
     *
     * @return string 'CRITICA' | 'ALTA' | 'MEDIA'
     */
    public function calcularSeveridadC6(float $dias_autonomia, float $stock, float $ROP, int $L): string
    {
        if ($dias_autonomia < $L) return 'CRITICA';
        if ($stock < $ROP)       return 'ALTA';
        return 'MEDIA';
    }

    /**
     * Caso 6 — Agotamiento Estimado / Punto de Pedido (ROP).
     *
     * @param string $fi_mov              Inicio del periodo de análisis de ventas
     * @param string $ff_mov              Fin del periodo de análisis (= fecha actual del informe)
     * @param string $fi_stock            Inicio del rango anual (para calcular LT desde albaranes)
     * @param array  $familias_incluir
     * @param array  $familias_excluir
     * @param array  $ids_filter
     * @param array  $proveedores_incluir IDs de proveedor seleccionados ([] = sin filtro)
     * @param int    $lead_time_defecto   Días usados si no hay proveedor o datos insuficientes
     * @param float  $nivel_servicio      0.90 | 0.95 | 0.99
     * @param int    $min_ventas          Mínimo de días únicos con venta
     * @param string $modelo              'binomial' | 'gamma' | 'poisson' | 'automatico'
     * @param float  $umbral_prob         Reservado (compatibilidad de firma)
     * @param float  $binomial_sigma_mult Multiplicador σ para modelo binomial (defecto 3.0)
     * @param bool   $incluir_albcli      Incluir albaranes de cliente como ventas
     * @param string $fi_stats            Inicio ventana estadística; vacío = fi_mov
     * @param string $ff_stats            Fin ventana estadística; vacío = ff_mov
     * @param string $tipo_override       Tipo de incidencia ('Agotamiento Estimado' | 'Punto de Pedido')
     * @param string $stock_anchor        Fecha de rebobinado de stock; vacío = ff_mov
     * @param float  $umbral_rop_mult     Umbral: stock > N×ROP → reconstruir (defecto 10.0)
     * @param float  $umbral_stock_neg    Umbral: stock < -N → reconstruir (defecto 2.0)
     *
     * @return array  Filas de incidencia o ['error' => ...]
     */
    public function detectar(
        string $fi_mov,
        string $ff_mov,
        string $fi_stock,
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter,
        array  $proveedores_incluir,
        int    $lead_time_defecto,
        float  $nivel_servicio,
        int    $min_ventas,
        string $modelo,
        float  $umbral_prob,
        float  $binomial_sigma_mult = 3.0,
        bool   $incluir_albcli      = false,
        string $fi_stats            = '',
        string $ff_stats            = '',
        string $tipo_override       = '',
        string $stock_anchor        = '',
        float  $umbral_rop_mult     = 10.0,
        float  $umbral_stock_neg    = 2.0
    ): array {
        $fi_stats = $fi_stats ?: $fi_mov;
        $ff_stats = $ff_stats ?: $ff_mov;
        $fechaInicioEstadisticaEsc = $this->db->real_escape_string($fi_stats);
        $fechaAnclaStockEsc = $this->db->real_escape_string($stock_anchor ?: $ff_mov);  // rebobinar a hoy (C6b) o ff_mov (C6a)
        $fechaFinEstadisticaEsc = $this->db->real_escape_string($ff_stats);

        $filtroFamiliasSql = $this->repo->familiaWhere($familias_incluir, $familias_excluir);
        $filtroArticulosSql = $this->repo->idsWhere($ids_filter);

        // Umbrales de reconstrucción (recibidos como parámetros)
        $umbral_rop_mult  = max(3.0, $umbral_rop_mult);
        $umbral_stock_neg = max(0.0, $umbral_stock_neg);

        // Paso 1 — Cantidades vendidas por día y artículo en el periodo
        $incluir_todos_tipos = !empty($proveedores_incluir);
        $filasVentas = $this->repo->queryVentasCantidadesC6(
            $fechaInicioEstadisticaEsc,
            $fechaFinEstadisticaEsc,
            $filtroFamiliasSql,
            $filtroArticulosSql,
            $incluir_albcli,
            $incluir_todos_tipos
        );
        if (isset($filasVentas['error'])) return $filasVentas;
        if (empty($filasVentas)) return [];

        // $ventas_cant[idArticulo][fecha] = nunidades_dia  (float)
        $ventas_cant = [];
        foreach ($filasVentas as $filaVenta) {
            $ventas_cant[(int)$filaVenta['idArticulo']][$filaVenta['fecha']] = (float)$filaVenta['nunidades_dia'];
        }

        // Paso 2 — Stock actual en ff_mov por rebobinado (igual que C5)
        $idsArticulosCsv = implode(',', array_keys($ventas_cant));
        $filasStock = $this->repo->queryStockRebobinado($idsArticulosCsv, $fechaAnclaStockEsc, false);
        if (isset($filasStock['error'])) return $filasStock;

        $stock_actual = [];
        foreach ($filasStock as $filaStock) {
            $stock_actual[(int)$filaStock['idArticulo']] = (float)$filaStock['stock_en_periodo'];
        }

        // ── Stock reconstruido para artículos muy negativos ───────────────────
        $ids_muy_negativos = array_keys(
            array_filter($stock_actual, fn($s) => $s < -$umbral_stock_neg)
        );
        $stock_reconstituido_set = [];
        if (!empty($ids_muy_negativos)) {
            $idsMuyNegativosCsv = implode(',', $ids_muy_negativos);
            $filasStockReconstituido = $this->repo->queryStockReconstituido($idsMuyNegativosCsv, $fechaAnclaStockEsc);
            if (!isset($filasStockReconstituido['error'])) {
                foreach ($filasStockReconstituido as $id_rec => $stock_rec) {
                    $stock_actual[$id_rec]             = $stock_rec;
                    $stock_reconstituido_set[$id_rec]  = 'negativo';
                }
            }
        }

        // Lead time: desde albaranes del proveedor si hay selección; defecto en caso contrario
        $lead_fuente = 'defecto';
        $L           = max(1, $lead_time_defecto);
        if (!empty($proveedores_incluir)) {
            $lt_prov = $this->repo->calcularLeadTimeProveedores($fi_stock, $ff_mov, $proveedores_incluir);
            if ($lt_prov > 0) {
                $L           = $lt_prov;
                $lead_fuente = 'proveedor';
            }
        }

        // Z-score según nivel de servicio
        $z_map = ['0.90' => 1.28, '0.95' => 1.65, '0.99' => 2.33];
        $z     = $z_map[number_format($nivel_servicio, 2)] ?? 1.65;

        // Periodos no finalizados: usar solo los días transcurridos hasta hoy
        $ff_stats_ts  = strtotime($ff_stats);
        $ff_efectivo  = min($ff_stats_ts, time());
        $periodo_dias = max(1, (int)round(($ff_efectivo - strtotime($fi_stats)) / 86400) + 1);

        // Granularidad de sub-ventanas
        if ($periodo_dias < 40)       $chunk_days = 1;
        elseif ($periodo_dias <= 130) $chunk_days = 5;
        else                          $chunk_days = 10;

        $fi_period_ts = $ff_stats_ts - ($periodo_dias - 1) * 86400;

        $incidencias          = [];
        $pending_reconstruction = [];

        // En C6b (filtrado por proveedor) no aplicar el umbral mínimo de días con venta
        $min_ventas_effective = !empty($proveedores_incluir) ? 0 : $min_ventas;

        foreach ($ventas_cant as $id => $fechas_map) {
            $n           = count($fechas_map);
            $total_units = array_sum($fechas_map);
            if ($n < $min_ventas_effective) continue;

            $d = $total_units / $periodo_dias;   // demanda diaria en unidades
            if ($d <= 0) continue;

            // ── Parámetros estadísticos sobre cantidades por sub-ventana ──────────
            $n_chunks     = (int)ceil($periodo_dias / $chunk_days);
            $chunk_counts = array_fill(0, $n_chunks, 0.0);
            foreach ($fechas_map as $fecha => $qty) {
                $t      = strtotime($fecha);
                $offset = (int)(($t - $fi_period_ts) / 86400);
                $chunk  = min($n_chunks - 1, max(0, (int)floor($offset / $chunk_days)));
                $chunk_counts[$chunk] += $qty;
            }

            $mu_chunk = $total_units / $n_chunks;   // unidades medias por chunk
            $s2_chunk = 0.0;
            if ($n_chunks > 1) {
                foreach ($chunk_counts as $c) $s2_chunk += ($c - $mu_chunk) ** 2;
                $s2_chunk /= ($n_chunks - 1);
            }

            // ── σ_d / ROP según modelo seleccionado ──────────────────────────
            $sigma_d      = null;
            $SS           = null;
            $ROP          = null;
            $modelo_usado = $modelo;

            if ($modelo === 'gamma') {
                $sum_sq = array_sum(array_map(fn($q) => $q ** 2, $fechas_map));
                $var_d  = $periodo_dias > 1
                    ? max(0.0, ($sum_sq - $periodo_dias * $d * $d) / ($periodo_dias - 1))
                    : 0.0;

                if ($var_d > 1e-9 && $d > 1e-9) {
                    $alpha_dia    = ($d ** 2) / $var_d;
                    $theta_dia    = $var_d / $d;
                    $ROP          = PosstockStatistics::gammaQuantileWH((float)$L * $alpha_dia, $theta_dia, $nivel_servicio);
                    $SS           = max(0.0, $ROP - $d * $L);
                    $modelo_usado = 'Gamma';
                } else {
                    $sigma_d      = sqrt($d);
                    $modelo_usado = 'Poisson';
                }
            } elseif ($modelo === 'binomial') {
                $p_sale = $n / $periodo_dias;
                $q_mean = $n > 0 ? $total_units / $n : 0.0;
                $s2_q   = 0.0;
                if ($n > 1) {
                    foreach ($fechas_map as $qty) $s2_q += ($qty - $q_mean) ** 2;
                    $s2_q /= ($n - 1);
                }
                $sigma_d      = sqrt(max(0.0, $p_sale * (1.0 - $p_sale) * $q_mean ** 2 + $p_sale * $s2_q));
                $modelo_usado = 'Binomial';
            } elseif ($modelo === 'automatico') {
                // ── Árbol de decisión C6 ─────────────────────────────────────────
                $sum_sq_auto   = array_sum(array_map(fn($q) => $q ** 2, $fechas_map));
                $var_d_auto    = $periodo_dias > 1
                    ? max(0.0, ($sum_sq_auto - $periodo_dias * $d * $d) / ($periodo_dias - 1))
                    : 0.0;
                $cv_d_auto     = $d > 1e-9 && $var_d_auto > 0 ? sqrt($var_d_auto) / $d : 1.0;
                $rotation_auto = $n / max(1, $periodo_dias);

                $overdispersed_auto = $n_chunks >= 4 && $mu_chunk > 1e-9
                    && $s2_chunk > $mu_chunk * 1.5;

                // RAMA 1: Rotación media/alta + demanda regular
                if ($rotation_auto > 0.40 && $cv_d_auto < 0.70) {
                    $sigma_d      = sqrt(max(0.0, $var_d_auto));
                    $modelo_usado = 'Normal';

                    // RAMA 2: Demanda en rachas/lotes (sobredispersión fuerte)
                } elseif ($overdispersed_auto) {
                    $r_bn         = max(0.001, ($mu_chunk ** 2) / ($s2_chunk - $mu_chunk));
                    $sigma_d      = sqrt($d * (1.0 + $d / $r_bn));
                    $modelo_usado = 'BN';

                    // RAMA 3: Demanda muy baja o esporádica
                } elseif ($rotation_auto < 0.10 || $d < 0.3) {
                    $sigma_d      = sqrt($d);
                    $modelo_usado = 'Poisson';

                    // RAMA 4: Demanda muy asimétrica (CV alto)
                } elseif ($cv_d_auto > 1.0 && $var_d_auto > 1e-9 && $d > 1e-9) {
                    $alpha_auto   = ($d ** 2) / $var_d_auto;
                    $theta_auto   = $var_d_auto / $d;
                    $ROP          = PosstockStatistics::gammaQuantileWH((float)$L * $alpha_auto, $theta_auto, $nivel_servicio);
                    $SS           = max(0.0, $ROP - $d * $L);
                    $modelo_usado = 'Gamma';

                    // RAMA 5: General
                } else {
                    if ($d >= 1.0) {
                        $sigma_d      = sqrt(max(0.0, $var_d_auto));
                        $modelo_usado = 'Normal';
                    } else {
                        $sigma_d      = sqrt($d);
                        $modelo_usado = 'Poisson';
                    }
                }
            } else {
                // 'poisson_bn' / 'poisson' — BN si sobredispersado, Poisson si no
                if ($n_chunks >= 4 && $s2_chunk > $mu_chunk + 1e-9 && $mu_chunk > 1e-9) {
                    $r_bn         = max(0.001, ($mu_chunk ** 2) / ($s2_chunk - $mu_chunk));
                    $sigma_d      = sqrt($d * (1.0 + $d / $r_bn));
                    $modelo_usado = 'BN';
                } else {
                    $sigma_d      = sqrt($d);
                    $modelo_usado = 'Poisson';
                }
            }

            // Si el modelo devolvió σ_d (no cuantil directo), calcular SS y ROP
            if ($sigma_d !== null) {
                $z_eff = ($modelo_usado === 'Binomial') ? $binomial_sigma_mult : $z;
                $SS    = $z_eff * $sigma_d * sqrt((float)$L);
                $ROP   = $d * $L + $SS;
            }
            if ($ROP === null || $SS === null) continue;

            $stock = $stock_actual[$id] ?? 0.0;
            $dias_autonomia = $d > 0 ? max(0.0, $stock / $d) : PHP_FLOAT_MAX;

            $alta_variabilidad = in_array($modelo_usado, ['BN', 'Gamma', 'Binomial']);

            // Stock muy superior al ROP (> umbral_rop_mult×ROP): posible error en stockOn
            if ($ROP > 0 && $stock > $umbral_rop_mult * $ROP) {
                $pending_reconstruction[$id] = [
                    'd'                => $d,
                    'ROP'              => $ROP,
                    'SS'               => $SS,
                    'modelo_usado'     => $modelo_usado,
                    'alta_variabilidad' => $alta_variabilidad,
                ];
                continue;
            }

            // Solo artículos accionables: bajo ROP, o modelo de alta variabilidad
            if ($stock >= $ROP && !$alta_variabilidad) continue;

            // ── Severidad ─────────────────────────────────────────────────────
            $severidad = $this->calcularSeveridadC6($dias_autonomia, $stock, $ROP, $L);
            if ($severidad === 'CRITICA') {
                $posible_causa = 'Agotamiento estimado antes del próximo pedido';
            } elseif ($severidad === 'ALTA') {
                $posible_causa = 'Stock por debajo del punto de pedido (ROP)';
            } else {
                $posible_causa = 'Stock suficiente pero con alta variabilidad de demanda';
            }

            $incidencias[] = [
                'idArticulo'          => $id,
                'tipo'                => $tipo_override ?: 'Agotamiento Estimado',
                'severidad'           => $severidad,
                'stock_actual'        => $stock,
                'dias_autonomia'      => round($dias_autonomia, 1),
                'lead_time_dias'      => $L,
                'lead_time_fuente'    => $lead_fuente,
                'd_diaria'            => round($d, 4),
                'stock_seguridad'     => round($SS, 2),
                'rop'                 => round($ROP, 2),
                'modelo_usado'        => $modelo_usado,
                'posible_causa'       => $posible_causa,
                'stock_reconstituido' => isset($stock_reconstituido_set[$id]),
                'stock_rec_motivo'    => $stock_reconstituido_set[$id] ?? null,
            ];
        }

        // ── Reconstrucción post-bucle para sobrestock sospechoso (> umbral_rop_mult×ROP) ─────
        if (!empty($pending_reconstruction)) {
            $idsPendientesCsv = implode(',', array_keys($pending_reconstruction));
            $filasStockReconstituidoAlt = $this->repo->queryStockReconstituido($idsPendientesCsv, $fechaAnclaStockEsc);
            if (!isset($filasStockReconstituidoAlt['error'])) {
                foreach ($filasStockReconstituidoAlt as $id_rec => $stock_rec) {
                    if (!isset($pending_reconstruction[$id_rec])) continue;
                    $pr            = $pending_reconstruction[$id_rec];
                    $d_rec         = (float)$pr['d'];
                    $ROP_rec       = (float)$pr['ROP'];
                    $SS_rec        = (float)$pr['SS'];
                    $modelo_rec    = (string)$pr['modelo_usado'];
                    $alta_var_rec  = (bool)$pr['alta_variabilidad'];

                    if ($stock_rec >= $ROP_rec && !$alta_var_rec) continue;

                    $dias_auto_rec = $d_rec > 0 ? max(0.0, $stock_rec / $d_rec) : PHP_FLOAT_MAX;

                    $sev_rec = $this->calcularSeveridadC6($dias_auto_rec, $stock_rec, $ROP_rec, $L);
                    if ($sev_rec === 'CRITICA') {
                        $causa_rec = 'Agotamiento estimado antes del próximo pedido';
                    } elseif ($sev_rec === 'ALTA') {
                        $causa_rec = 'Stock por debajo del punto de pedido (ROP)';
                    } else {
                        $causa_rec = 'Stock suficiente pero con alta variabilidad de demanda';
                    }

                    $incidencias[] = [
                        'idArticulo'          => $id_rec,
                        'tipo'                => $tipo_override ?: 'Agotamiento Estimado',
                        'severidad'           => $sev_rec,
                        'stock_actual'        => $stock_rec,
                        'dias_autonomia'      => round($dias_auto_rec, 1),
                        'lead_time_dias'      => $L,
                        'lead_time_fuente'    => $lead_fuente,
                        'd_diaria'            => round($d_rec, 4),
                        'stock_seguridad'     => round($SS_rec, 2),
                        'rop'                 => round($ROP_rec, 2),
                        'modelo_usado'        => $modelo_rec,
                        'posible_causa'       => $causa_rec,
                        'stock_reconstituido' => true,
                    ];
                }
            }
        }

        // Añadir nombres y tipo (peso / unidad)
        if (!empty($incidencias)) {
            $idsIncidenciasCsv = implode(',', array_unique(array_column($incidencias, 'idArticulo')));
            $resultadoConsulta = $this->db->query(
                "SELECT idArticulo, articulo_name, tipo FROM articulos WHERE idArticulo IN ($idsIncidenciasCsv)"
            );
            $meta = [];
            if ($resultadoConsulta) {
                while ($filaMeta = $resultadoConsulta->fetch_assoc()) {
                    $meta[(int)$filaMeta['idArticulo']] = ['nombre' => $filaMeta['articulo_name'], 'tipo' => $filaMeta['tipo']];
                }
            }
            foreach ($incidencias as &$inc) {
                $inc['nombre']        = $meta[$inc['idArticulo']]['nombre'] ?? '';
                $inc['tipo_articulo'] = $meta[$inc['idArticulo']]['tipo']   ?? 'unidad';
            }
            unset($inc);
        }

        return $incidencias;
    }
}
