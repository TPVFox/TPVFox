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
        $fechaInicio       = $this->db->real_escape_string($fi_mov);
        $fechaFin          = $this->db->real_escape_string($ff_mov);
        $fechaInicioStock  = $this->db->real_escape_string($fi_stock);
        $filtroFamiliasSql = $this->repo->familiaWhere($familias_incluir, $familias_excluir);
        $filtroArticulosSql = $this->repo->idsWhere($ids_filter);

        $filasDeltas = $this->repo->queryDeltasC1($fechaInicio, $fechaFin, $filtroFamiliasSql, $filtroArticulosSql);
        if (isset($filasDeltas['error'])) return $filasDeltas;
        if (empty($filasDeltas)) return [];

        $mapaDeltas = [];
        $idsArticulos = [];
        foreach ($filasDeltas as $filaDelta) {
            $mapaDeltas[(int)$filaDelta['idArticulo']] = [
                'delta_total'      => (float)$filaDelta['delta_total'],
                'min_running'      => (float)$filaDelta['min_running'],
                'fecha_minimo'     => $filaDelta['fecha_minimo'],
                'dias_en_minimo'   => (int)$filaDelta['dias_en_minimo'],
                'dias_en_negativo' => (int)$filaDelta['dias_en_negativo'],
            ];
            $idsArticulos[] = (int)$filaDelta['idArticulo'];
        }

        // Obtener stock base: usar cache si disponible, si no consultar
        if (!empty($stock_base_cache)) {
            $stock_base = $stock_base_cache;
        } else {
            $fechaInicioStockBase = $this->db->real_escape_string($fi_stock);
            $fechaFinStockBase    = $this->db->real_escape_string($ff_stock);
            $idsArticulosCsv      = implode(',', array_map('intval', $idsArticulos));
            $filasStockBase       = $this->repo->queryStockBase($fechaInicioStockBase, $fechaFinStockBase, $idsArticulosCsv);
            if (isset($filasStockBase['error'])) return $filasStockBase;
            $stock_base = [];
            foreach ($filasStockBase as $filaStockBase) {
                $stock_base[(int)$filaStockBase['idArticulo']] = [
                    'saldo_acumulado' => (float)$filaStockBase['saldo_acumulado'],
                    'ultima_compra'   => $filaStockBase['ultima_compra'],
                    'ultima_venta'    => $filaStockBase['ultima_venta'],
                ];
            }
        }

        $incidencias    = [];
        $idsC1a         = [];   // negativos al cierre (detalle enriquecido)
        $idsC1b         = [];   // negativos puntuales recuperados (detalle enriquecido)

        foreach ($mapaDeltas as $idArticulo => $datosDelta) {
            $saldo_base   = $stock_base[$idArticulo]['saldo_acumulado'] ?? 0.0;
            $stock_actual = $saldo_base + $datosDelta['delta_total'];
            $min_balance  = $saldo_base + $datosDelta['min_running'];

            if ($stock_actual < 0) {
                $ya_negativo_inicio = $saldo_base < 0;
                $senalesC1a         = $this->calcularSignalesC1a(
                    $stock_actual,
                    $min_balance,
                    $ya_negativo_inicio,
                    $umbral_fraccionado,
                    $umbral_magnitud
                );

                $incidencias[] = [
                    'idArticulo'           => $idArticulo,
                    'tipo'                 => 'Inventario en negativo',
                    'severidad'            => $senalesC1a['severidad'],
                    'stock_actual'         => $stock_actual,
                    'min_balance'          => $min_balance,
                    'ya_negativo_inicio'   => $ya_negativo_inicio,
                    'fraccionado_es_causa' => $senalesC1a['fraccionado_es_causa'],
                    'saldo_base'           => $ya_negativo_inicio ? $saldo_base : null,
                    'dias_en_negativo'     => $datosDelta['dias_en_negativo'],
                    'posible_causa'        => '',   // sobreescrito en el bloque de enriquecimiento
                ];
                $idsC1a[] = $idArticulo;
            } elseif ($min_balance < 0) {
                $frac_c1b             = abs($min_balance - round($min_balance));
                $es_fraccionado       = $frac_c1b > $umbral_fraccionado;
                $fraccionado_es_causa = $es_fraccionado && abs($min_balance) < $umbral_magnitud;

                $incidencias[] = [
                    'idArticulo'           => $idArticulo,
                    'tipo'                 => 'Desajuste Puntual de Stock',
                    'severidad'            => 'ALTA',
                    'stock_actual'         => $stock_actual,
                    'min_balance'          => $min_balance,
                    'fraccionado_es_causa' => $fraccionado_es_causa,
                    'fecha_minimo'         => $datosDelta['fecha_minimo'],
                    'dias_en_minimo'       => $datosDelta['dias_en_minimo'],
                    'posible_causa'        => 'Negativo puntual, recuperado al cierre',
                ];
                $idsC1b[] = $idArticulo;
            }
        }

        // Enriquecer C1a y C1b con actividad del periodo (una sola consulta compartida)
        $idsIncidencias = array_merge($idsC1a, $idsC1b);
        $detalle        = !empty($idsIncidencias)
            ? $this->repo->queryDetalleC1(implode(',', $idsIncidencias), $fechaInicio, $fechaFin)
            : [];

        // Proveedor habitual y último para C1a (rango anual para tener datos suficientes)
        $mapaProveedores = !empty($idsC1a)
            ? $this->repo->queryProveedorArticulos(implode(',', $idsC1a), $fechaInicioStock, $fechaFin)
            : [];

        if (!empty($idsC1a)) {
            foreach ($incidencias as &$incidencia) {
                if ($incidencia['tipo'] !== 'Inventario en negativo') continue;
                $detalleArticulo = $detalle[$incidencia['idArticulo']] ?? null;
                $numeroEntradas  = $detalleArticulo['n_entradas'] ?? 0;
                $incidencia['n_entradas']     = $numeroEntradas;
                $incidencia['ultima_entrada'] = $detalleArticulo['ultima_entrada'] ?? null;
                $incidencia['n_ventas']       = $detalleArticulo['n_ventas']       ?? 0;

                $datosProveedor = $mapaProveedores[$incidencia['idArticulo']] ?? null;
                $incidencia['prov_habitual_nombre'] = $datosProveedor['prov_habitual_nombre'] ?? null;
                $incidencia['prov_habitual_n']      = $datosProveedor['prov_habitual_n']      ?? null;
                $incidencia['prov_ultimo_nombre']   = $datosProveedor['prov_ultimo_nombre']   ?? null;
                $incidencia['prov_ultima_fecha']    = $datosProveedor['prov_ultima_fecha']    ?? null;
                $incidencia['prov_es_mismo']        = $datosProveedor['prov_es_mismo']        ?? null;

                // Refinar fraccionado_es_causa con n_ventas: umbral dinámico por operación de pesaje
                if ($incidencia['fraccionado_es_causa']) {
                    $umbralFraccionado = $umbral_por_venta * max(1, $incidencia['n_ventas']);
                    if (abs($incidencia['stock_actual']) > $umbralFraccionado || abs($incidencia['min_balance']) > $umbralFraccionado) {
                        $incidencia['fraccionado_es_causa'] = false;
                        $incidencia['severidad']            = $incidencia['ya_negativo_inicio'] ? 'ALTA' : 'CRITICA';
                    }
                }

                if ($incidencia['ya_negativo_inicio']) {
                    $incidencia['posible_causa'] = 'Stock ya negativo al inicio del periodo: el problema viene de antes, revisar inventario anterior';
                } elseif (!empty($incidencia['fraccionado_es_causa'])) {
                    $incidencia['posible_causa'] = 'Stock decimal dentro del margen de pesaje: probable venta de últimos restos en balanza o imprecisión acumulada';
                    $incidencia['severidad']     = 'MEDIA';
                } elseif ($numeroEntradas === 0 && $incidencia['n_ventas'] > 0) {
                    $incidencia['posible_causa'] = 'Ventas registradas sin ninguna recepción en el periodo: comprobar si falta dar entrada de mercancía';
                } elseif ($numeroEntradas > 0) {
                    $incidencia['posible_causa'] = 'Entradas registradas pero el stock sigue negativo: revisar si falta alguna recepción o si hay ventas duplicadas';
                } else {
                    $incidencia['posible_causa'] = 'Stock negativo sin movimientos en el periodo: revisar el saldo inicial del artículo o si hay ajustes no registrados';
                }
            }
            unset($incidencia);
        }

        // Enriquecer C1b con causa dinámica + timing
        if (!empty($idsC1b)) {
            $mapaIdFechaMinimo = [];
            foreach ($incidencias as $incidencia) {
                if ($incidencia['tipo'] === 'Desajuste Puntual de Stock' && !empty($incidencia['fecha_minimo'])) {
                    $mapaIdFechaMinimo[$incidencia['idArticulo']] = $incidencia['fecha_minimo'];
                }
            }
            $articulosConTimingProximo = $this->repo->queryTimingC1b($mapaIdFechaMinimo, $timing_ventana_dias);

            foreach ($incidencias as &$incidencia) {
                if ($incidencia['tipo'] !== 'Desajuste Puntual de Stock') continue;
                $detalleArticulo = $detalle[$incidencia['idArticulo']] ?? null;
                $numeroEntradas  = $detalleArticulo['n_entradas'] ?? 0;
                $incidencia['n_entradas']     = $numeroEntradas;
                $incidencia['ultima_entrada'] = $detalleArticulo['ultima_entrada'] ?? null;
                $incidencia['n_ventas']       = $detalleArticulo['n_ventas']       ?? 0;
                // Refinar fraccionado_es_causa con n_ventas
                if ($incidencia['fraccionado_es_causa']) {
                    $umbralFraccionado = $umbral_por_venta * max(1, $incidencia['n_ventas']);
                    if (abs($incidencia['min_balance']) > $umbralFraccionado) {
                        $incidencia['fraccionado_es_causa'] = false;
                    }
                }

                if (!empty($incidencia['fraccionado_es_causa'])) {
                    $incidencia['timing_proximo'] = false;
                    $incidencia['severidad']      = 'MEDIA';
                    $incidencia['posible_causa']  = 'Mínimo negativo dentro del margen de pesaje: probable venta de últimos restos en balanza';
                } else {
                    $incidencia['timing_proximo'] = isset($articulosConTimingProximo[$incidencia['idArticulo']]);
                    if ($incidencia['timing_proximo']) {
                        $incidencia['posible_causa'] = 'Probable venta registrada antes que la recepción (timing de entrada)';
                    } elseif ($numeroEntradas > 0) {
                        $incidencia['posible_causa'] = 'Entradas en el periodo pero no coinciden con el momento del negativo: revisar si hay un desajuste de inventario puntual';
                    } else {
                        $incidencia['posible_causa'] = 'Sin recepciones en el periodo: revisar movimientos duplicados o ajustes manuales';
                    }
                }
            }
            unset($incidencia);
        }

        return $incidencias;
    }
}
