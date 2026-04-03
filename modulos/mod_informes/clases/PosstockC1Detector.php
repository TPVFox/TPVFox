<?php

/**
 * PosstockC1Detector — Detector de Inventario en Negativo / Desajuste Puntual de Stock (Caso C1).
 *
 * Extraído de ClasePosstock como parte de la Fase 4 de refactorización.
 *
 * Métodos públicos:
 *   detectar(...)  — orquestador C1 completo (C1a + C1b)
 */

class PosstockC1Detector
{
    public function __construct(
        private mysqli $db,
        private PosstockQueryRepository $repo
    ) {}

    // ══════════════════════════════════════════════════════════════════════════
    // Algoritmos internos (públicos para testabilidad directa)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Calcula las señales de badge C1a dados los valores de stock.
     *
     * @param float $stock_actual
     * @param float $min_balance
     * @param bool  $ya_negativo_inicio
     * @param float $umbral_fraccionado
     * @param float $umbral_magnitud
     *
     * @return array ['severidad' => string, 'fraccionado_es_causa' => bool]
     */
    public function calcularSignalesC1a(
        float $stock_actual,
        float $min_balance,
        bool  $ya_negativo_inicio,
        float $umbral_fraccionado = 0.05,
        float $umbral_magnitud    = 0.5
    ): array {
        $frac                 = abs($stock_actual - round($stock_actual));
        $es_fraccionado       = $frac > $umbral_fraccionado;
        $fraccionado_es_causa = $es_fraccionado
            && abs($stock_actual) < $umbral_magnitud
            && abs($min_balance)  < $umbral_magnitud;
        $severidad = ($fraccionado_es_causa || $ya_negativo_inicio) ? 'ALTA' : 'CRITICA';
        return ['severidad' => $severidad, 'fraccionado_es_causa' => $fraccionado_es_causa];
    }

    /**
     * Caso 1 — Inventario en Negativo (C1a) y Desajuste Puntual de Stock (C1b).
     *
     * @param string $fi_mov
     * @param string $ff_mov
     * @param string $fi_stock
     * @param string $ff_stock
     * @param array  $familias_incluir
     * @param array  $familias_excluir
     * @param array  $ids_filter
     * @param array  $stock_base_cache    Pre-calculado por el caller para evitar doble consulta
     * @param float  $umbral_fraccionado  Umbral de parte fraccionaria para badge "Stock decimal"
     * @param float  $umbral_magnitud     Magnitud máxima para considerar drift de pesaje
     * @param float  $umbral_por_venta    Umbral dinámico por operación de pesaje
     * @param int    $timing_ventana_dias Ventana en días para confirmar timing de entrada
     *
     * @return array  Filas de incidencia o ['error' => ...]
     */
    public function detectar(
        string $fi_mov,
        string $ff_mov,
        string $fi_stock,
        string $ff_stock,
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter          = [],
        array  $stock_base_cache    = [],
        float  $umbral_fraccionado  = 0.05,
        float  $umbral_magnitud     = 0.5,
        float  $umbral_por_venta    = 0.010,
        int    $timing_ventana_dias = 1
    ): array {
        $fi     = $this->db->real_escape_string($fi_mov);
        $ff     = $this->db->real_escape_string($ff_mov);
        $fi_stk = $this->db->real_escape_string($fi_stock);
        $wf     = $this->repo->familiaWhere($familias_incluir, $familias_excluir);
        $wi     = $this->repo->idsWhere($ids_filter);

        $rows = $this->repo->queryDeltasC1($fi, $ff, $wf, $wi);
        if (isset($rows['error'])) return $rows;
        if (empty($rows)) return [];

        $delta_map = [];
        $ids       = [];
        foreach ($rows as $r) {
            $delta_map[(int)$r['idArticulo']] = [
                'delta_total'      => (float)$r['delta_total'],
                'min_running'      => (float)$r['min_running'],
                'fecha_minimo'     => $r['fecha_minimo'],
                'dias_en_minimo'   => (int)$r['dias_en_minimo'],
                'dias_en_negativo' => (int)$r['dias_en_negativo'],
            ];
            $ids[] = (int)$r['idArticulo'];
        }

        // Obtener stock base: usar cache si disponible, si no consultar
        if (!empty($stock_base_cache)) {
            $stock_base = $stock_base_cache;
        } else {
            $fi_sb   = $this->db->real_escape_string($fi_stock);
            $ff_sb   = $this->db->real_escape_string($ff_stock);
            $ids_sb  = implode(',', array_map('intval', $ids));
            $rows_sb = $this->repo->queryStockBase($fi_sb, $ff_sb, $ids_sb);
            if (isset($rows_sb['error'])) return $rows_sb;
            $stock_base = [];
            foreach ($rows_sb as $row) {
                $stock_base[(int)$row['idArticulo']] = [
                    'saldo_acumulado' => (float)$row['saldo_acumulado'],
                    'ultima_compra'   => $row['ultima_compra'],
                    'ultima_venta'    => $row['ultima_venta'],
                ];
            }
        }

        $incidencias    = [];
        $ids_c1a        = [];   // negativos al cierre (detalle enriquecido)
        $ids_c1b        = [];   // negativos puntuales recuperados (detalle enriquecido)

        foreach ($delta_map as $id => $data) {
            $saldo_base   = $stock_base[$id]['saldo_acumulado'] ?? 0.0;
            $stock_actual = $saldo_base + $data['delta_total'];
            $min_balance  = $saldo_base + $data['min_running'];

            if ($stock_actual < 0) {
                $ya_negativo_inicio  = $saldo_base < 0;
                $frac                = abs($stock_actual - round($stock_actual));
                $es_fraccionado      = $frac > $umbral_fraccionado;
                $fraccionado_es_causa = $es_fraccionado && abs($stock_actual) < $umbral_magnitud && abs($min_balance) < $umbral_magnitud;

                $incidencias[] = [
                    'idArticulo'           => $id,
                    'tipo'                 => 'Inventario en negativo',
                    'severidad'            => ($fraccionado_es_causa || $ya_negativo_inicio) ? 'ALTA' : 'CRITICA',
                    'stock_actual'         => $stock_actual,
                    'min_balance'          => $min_balance,
                    'ya_negativo_inicio'   => $ya_negativo_inicio,
                    'fraccionado_es_causa' => $fraccionado_es_causa,
                    'saldo_base'           => $ya_negativo_inicio ? $saldo_base : null,
                    'dias_en_negativo'     => $data['dias_en_negativo'],
                    'posible_causa'        => '',   // sobreescrito en el bloque de enriquecimiento
                ];
                $ids_c1a[] = $id;
            } elseif ($min_balance < 0) {
                $frac_c1b             = abs($min_balance - round($min_balance));
                $es_fraccionado       = $frac_c1b > $umbral_fraccionado;
                $fraccionado_es_causa = $es_fraccionado && abs($min_balance) < $umbral_magnitud;

                $incidencias[] = [
                    'idArticulo'           => $id,
                    'tipo'                 => 'Desajuste Puntual de Stock',
                    'severidad'            => 'ALTA',
                    'stock_actual'         => $stock_actual,
                    'min_balance'          => $min_balance,
                    'fraccionado_es_causa' => $fraccionado_es_causa,
                    'fecha_minimo'         => $data['fecha_minimo'],
                    'dias_en_minimo'       => $data['dias_en_minimo'],
                    'posible_causa'        => 'Negativo puntual, recuperado al cierre',
                ];
                $ids_c1b[] = $id;
            }
        }

        // Enriquecer C1a y C1b con actividad del periodo (una sola consulta compartida)
        $ids_todos = array_merge($ids_c1a, $ids_c1b);
        $detalle   = !empty($ids_todos)
            ? $this->repo->queryDetalleC1(implode(',', $ids_todos), $fi, $ff)
            : [];

        // Proveedor habitual y último para C1a (rango anual para tener datos suficientes)
        $prov_map = !empty($ids_c1a)
            ? $this->repo->queryProveedorArticulos(implode(',', $ids_c1a), $fi_stk, $ff)
            : [];

        if (!empty($ids_c1a)) {
            foreach ($incidencias as &$inc) {
                if ($inc['tipo'] !== 'Inventario en negativo') continue;
                $d     = $detalle[$inc['idArticulo']] ?? null;
                $n_ent = $d['n_entradas'] ?? 0;
                $inc['n_entradas']     = $n_ent;
                $inc['ultima_entrada'] = $d['ultima_entrada'] ?? null;
                $inc['n_ventas']       = $d['n_ventas']       ?? 0;

                $prov = $prov_map[$inc['idArticulo']] ?? null;
                $inc['prov_habitual_nombre'] = $prov['prov_habitual_nombre'] ?? null;
                $inc['prov_habitual_n']      = $prov['prov_habitual_n']      ?? null;
                $inc['prov_ultimo_nombre']   = $prov['prov_ultimo_nombre']   ?? null;
                $inc['prov_ultima_fecha']    = $prov['prov_ultima_fecha']    ?? null;
                $inc['prov_es_mismo']        = $prov['prov_es_mismo']        ?? null;

                // Refinar fraccionado_es_causa con n_ventas: umbral dinámico por operación de pesaje
                if ($inc['fraccionado_es_causa']) {
                    $umbral_frac = $umbral_por_venta * max(1, $inc['n_ventas']);
                    if (abs($inc['stock_actual']) > $umbral_frac || abs($inc['min_balance']) > $umbral_frac) {
                        $inc['fraccionado_es_causa'] = false;
                        $inc['severidad']            = $inc['ya_negativo_inicio'] ? 'ALTA' : 'CRITICA';
                    }
                }

                if ($inc['ya_negativo_inicio']) {
                    $inc['posible_causa'] = 'Stock ya negativo al inicio del periodo: el problema viene de antes, revisar inventario anterior';
                } elseif (!empty($inc['fraccionado_es_causa'])) {
                    $inc['posible_causa'] = 'Stock decimal dentro del margen de pesaje: probable venta de últimos restos en balanza o imprecisión acumulada';
                    $inc['severidad']     = 'MEDIA';
                } elseif ($n_ent === 0 && $inc['n_ventas'] > 0) {
                    $inc['posible_causa'] = 'Ventas registradas sin ninguna recepción en el periodo: comprobar si falta dar entrada de mercancía';
                } elseif ($n_ent > 0) {
                    $inc['posible_causa'] = 'Entradas registradas pero el stock sigue negativo: revisar si falta alguna recepción o si hay ventas duplicadas';
                } else {
                    $inc['posible_causa'] = 'Stock negativo sin movimientos en el periodo: revisar el saldo inicial del artículo o si hay ajustes no registrados';
                }
            }
            unset($inc);
        }

        // Enriquecer C1b con causa dinámica + timing
        if (!empty($ids_c1b)) {
            $id_fecha_map = [];
            foreach ($incidencias as $inc) {
                if ($inc['tipo'] === 'Desajuste Puntual de Stock' && !empty($inc['fecha_minimo'])) {
                    $id_fecha_map[$inc['idArticulo']] = $inc['fecha_minimo'];
                }
            }
            $timing_set = $this->repo->queryTimingC1b($id_fecha_map, $timing_ventana_dias);

            foreach ($incidencias as &$inc) {
                if ($inc['tipo'] !== 'Desajuste Puntual de Stock') continue;
                $d     = $detalle[$inc['idArticulo']] ?? null;
                $n_ent = $d['n_entradas'] ?? 0;
                $inc['n_entradas']     = $n_ent;
                $inc['ultima_entrada'] = $d['ultima_entrada'] ?? null;
                $inc['n_ventas']       = $d['n_ventas']       ?? 0;
                // Refinar fraccionado_es_causa con n_ventas
                if ($inc['fraccionado_es_causa']) {
                    $umbral_frac = $umbral_por_venta * max(1, $inc['n_ventas']);
                    if (abs($inc['min_balance']) > $umbral_frac) {
                        $inc['fraccionado_es_causa'] = false;
                    }
                }

                if (!empty($inc['fraccionado_es_causa'])) {
                    $inc['timing_proximo'] = false;
                    $inc['severidad']      = 'MEDIA';
                    $inc['posible_causa']  = 'Mínimo negativo dentro del margen de pesaje: probable venta de últimos restos en balanza';
                } else {
                    $inc['timing_proximo'] = isset($timing_set[$inc['idArticulo']]);
                    if ($inc['timing_proximo']) {
                        $inc['posible_causa'] = 'Probable venta registrada antes que la recepción (timing de entrada)';
                    } elseif ($n_ent > 0) {
                        $inc['posible_causa'] = 'Entradas en el periodo pero no coinciden con el momento del negativo: revisar si hay un desajuste de inventario puntual';
                    } else {
                        $inc['posible_causa'] = 'Sin recepciones en el periodo: revisar movimientos duplicados o ajustes manuales';
                    }
                }
            }
            unset($inc);
        }

        return $incidencias;
    }
}
