<?php

/**
 * PosstockC3Detector — Detector de Caída de Rotación / Entrada sin Rotación Previa (Caso C3).
 *
 * Extraído de ClasePosstock como parte de la Fase 4 de refactorización.
 *
 * Métodos públicos:
 *   detectar(...)  — orquestador C3 completo (C3a + C3b mezclados)
 */

class PosstockC3Detector
{
    public function __construct(
        private mysqli $db,
        private PosstockQueryRepository $repo
    ) {}

    // ══════════════════════════════════════════════════════════════════════════
    // Algoritmos internos (públicos para testabilidad directa)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Determina la severidad C3a a partir del ratio de caída de rotación.
     *
     * @param float $ratio_a  (semanas_c3a * 7) / umbral_efectivo_dias
     *
     * @return string 'ALTA' | 'MEDIA'
     */
    public function calcularSeveridadC3a(float $ratio_a): string
    {
        return $ratio_a >= 2.0 ? 'ALTA' : 'MEDIA';
    }

    /**
     * Determina la severidad C3b a partir del número de entradas y la presencia de devoluciones.
     *
     * @param int  $n_entradas
     * @param bool $tiene_devolucion
     * @param float $ratio_b  semanas / umbral_sin_rotacion (solo aplica a la segunda rama C3b)
     *
     * @return string 'MEDIA' | 'BAJA'
     */
    public function calcularSeveridadC3b(int $n_entradas, bool $tiene_devolucion, float $ratio_b = 0.0): string
    {
        return ($n_entradas >= 2 || $tiene_devolucion || $ratio_b >= 2.0) ? 'MEDIA' : 'BAJA';
    }

    /**
     * Casos 3a y 3b — Riesgo de caducidad teórica / Entrada sin rotación previa.
     *
     * Devuelve incidencias C3a y C3b mezcladas (una fila por caso detectado).
     * Usa una sola query SQL: artículos con entrada en la ventana + última venta conocida.
     *
     * C3a MEDIA — tiene última venta Y semanas_sin_venta >= umbral_caducidad
     * C3b BAJA  — no tiene última venta  OR semanas_sin_rot >= umbral_sin_rotacion
     *
     * @param string $fi_mov
     * @param string $ff_mov
     * @param string $fi_stock
     * @param int    $umbral_caducidad
     * @param int    $umbral_sin_rotacion
     * @param array  $familias_incluir
     * @param array  $familias_excluir
     * @param array  $ids_filter
     * @param int    $dias_post
     * @param float  $multiplicador_cadencia
     *
     * @return array  Filas de incidencia o ['error' => ...]
     */
    public function detectar(
        string $fi_mov,
        string $ff_mov,
        string $fi_stock,
        int    $umbral_caducidad,
        int    $umbral_sin_rotacion,
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter           = [],
        int    $dias_post            = 14,
        float  $multiplicador_cadencia = 3.0
    ): array {
        $fi_m  = $this->db->real_escape_string($fi_mov);
        $ff_m  = $this->db->real_escape_string($ff_mov);
        $fi_s  = $this->db->real_escape_string($fi_stock);
        $wf    = $this->repo->familiaWhere($familias_incluir, $familias_excluir);
        $wi    = $this->repo->idsWhere($ids_filter);
        $min_u = min($umbral_caducidad, $umbral_sin_rotacion);

        $rows = $this->repo->queryUltimaVentaC3($fi_m, $ff_m, $fi_s, $wf, $wi, $min_u, $dias_post, $multiplicador_cadencia);
        if (isset($rows['error'])) return $rows;

        // Stock al cierre del periodo para los artículos afectados
        $stock_map = [];
        if (!empty($rows)) {
            $ids_c3    = implode(',', array_unique(array_map(fn($r) => (int)$r['idArticulo'], $rows)));
            $rows_stk  = $this->repo->queryStockRebobinado($ids_c3, $ff_m, false);
            if (!isset($rows_stk['error'])) {
                foreach ($rows_stk as $rs) {
                    $stock_map[(int)$rs['idArticulo']] = (float)$rs['stock_en_periodo'];
                }
            }
        }

        $fecha_fin_dt = new DateTime($ff_mov);
        // Días totales del historial de ventas consultado (fi_stock → ff_mov)
        $periodo_dias = (new DateTime($fi_stock))->diff(new DateTime($ff_mov))->days + 1;
        $incidencias  = [];

        foreach ($rows as $r) {
            $id                    = (int)$r['idArticulo'];
            $ultima_venta          = $r['ultima_venta'];
            $stock_actual          = $stock_map[$id] ?? null;
            $n_entradas            = (int)$r['n_entradas'];
            $cantidad_recibida     = (float)$r['cantidad_recibida'];
            $fecha_primera_entrada = $r['fecha_primera_entrada'];
            $n_devoluciones        = (int)$r['n_devoluciones'];
            $cantidad_devuelta     = (float)$r['cantidad_devuelta'];
            $tiene_devolucion      = $n_devoluciones > 0;

            if ($ultima_venta !== null) {
                $semanas = (new DateTime($ultima_venta))->diff($fecha_fin_dt)->days / 7.0;
            } else {
                $semanas = null;
            }

            // C3a: caída de rotación (umbral dinámico: avg_cadencia × multiplicador)
            $n_ventas_historico = (int)$r['n_ventas_historico'];
            $avg_cadencia_dias  = $n_ventas_historico > 0
                ? round($periodo_dias / $n_ventas_historico, 1)
                : null;
            $umbral_efectivo_dias = $avg_cadencia_dias !== null
                ? $avg_cadencia_dias * $multiplicador_cadencia
                : PHP_INT_MAX;

            // Referente de "días sin venta": si el pedido llegó DESPUÉS de la última venta
            // (el artículo se agotó y se repuso), contar desde fecha_primera_entrada.
            $desde_reposicion = $fecha_primera_entrada !== null
                && $ultima_venta !== null
                && strcmp($fecha_primera_entrada, $ultima_venta) > 0;
            $ref_c3a     = $desde_reposicion ? $fecha_primera_entrada : $ultima_venta;
            $semanas_c3a = $ref_c3a !== null
                ? (new DateTime($ref_c3a))->diff($fecha_fin_dt)->days / 7.0
                : null;

            // Stock > 0 requerido + n_ventas_historico >= 3 para cadencia fiable
            $c3a_ok = $semanas_c3a !== null
                && $n_ventas_historico >= 3
                && ($stock_actual === null || $stock_actual > 0)
                && ($semanas_c3a * 7) >= $umbral_efectivo_dias;
            if ($c3a_ok) {
                $ratio_a   = ($semanas_c3a * 7) / max(1, $umbral_efectivo_dias);
                $severidad = $ratio_a >= 2.0 ? 'ALTA' : 'MEDIA';
                $wsem_c3a  = round($semanas_c3a, 1);
                $es_fast   = $avg_cadencia_dias !== null && $avg_cadencia_dias <= 7.0;
                if ($desde_reposicion) {
                    if ($es_fast) {
                        if ($ratio_a <= 1.5) {
                            $causa = "Recibido sin venta desde la última recepción — verificar ubicación en sala, EAN y precio";
                        } elseif ($ratio_a <= 2.5) {
                            $causa = "Nuevo pedido sin rotación en artículo de alta rotación — posible merma no registrada o problema de EAN";
                        } else {
                            $causa = "Nuevo stock paralizado desde la recepción — revisión urgente: exposición, estado del producto y precio";
                        }
                    } else {
                        if ($ratio_a <= 1.5) {
                            $causa = "Repuesto tras agotamiento sin rotación posterior — verificar si hay demanda activa antes del próximo pedido";
                        } elseif ($ratio_a <= 2.5) {
                            $causa = "Artículo repuesto pero sin demanda activa — posible artículo estacional o referencia sustituida";
                        } else {
                            $causa = "Nuevo stock sin movimiento desde la recepción — valorar devolución al proveedor o liquidación";
                        }
                    }
                } elseif ($es_fast) {
                    if ($ratio_a <= 1.5) {
                        $causa = "Artículo de alta rotación con caída reciente — verificar ubicación en sala, EAN y precio";
                    } elseif ($ratio_a <= 2.5) {
                        $causa = "Alta rotación interrumpida — posible merma no registrada, problema de EAN o artículo agotado en lineal";
                    } else {
                        $causa = "Artículo de alta rotación sin ventas desde hace {$wsem_c3a} sem. — revisión urgente de exposición y estado del producto";
                    }
                } else {
                    if ($ratio_a <= 1.5) {
                        $causa = "Posible artículo estacional — revisar ventas en el mismo periodo del año anterior";
                    } elseif ($ratio_a <= 2.5) {
                        $causa = "Posible referencia sustituida — verificar si hay artículo similar activo con rotación";
                    } else {
                        $causa = "Sin demanda desde hace {$wsem_c3a} sem. — valorar liquidación o baja de referencia";
                    }
                }
                $incidencias[] = [
                    'idArticulo'                 => $id,
                    'tipo'                       => 'Caída de rotación',
                    'severidad'                  => $severidad,
                    'stock_actual'               => $stock_actual,
                    'ultima_venta'               => $ultima_venta,
                    'fecha_primera_entrada'      => $fecha_primera_entrada,
                    'desde_reposicion'           => $desde_reposicion,
                    'semanas_desde_ultima_venta' => $wsem_c3a,
                    'avg_cadencia_dias'          => $avg_cadencia_dias,
                    'n_entradas'                 => $n_entradas,
                    'cantidad_recibida'          => $cantidad_recibida,
                    'posible_causa'              => $causa,
                ];
            }

            // C3b: entrada sin rotación previa
            if ($ultima_venta === null) {
                if ($n_entradas <= 1 && $r['primera_venta_post'] !== null) {
                    continue;
                }
                if ($tiene_devolucion) {
                    $ratio_dev = $cantidad_recibida > 0 ? $cantidad_devuelta / $cantidad_recibida : 0;
                    if ($ratio_dev >= 0.8) {
                        $causa_nunca = "Artículo recibido y devuelto casi en su totalidad — verificar si el pedido fue rechazado o si hubo un error en el albarán";
                    } else {
                        $causa_nunca = "Artículo con recepciones y devoluciones parciales sin ventas — posible problema de calidad o pedido incorrecto";
                    }
                } elseif ($n_entradas >= 3) {
                    $causa_nunca = "Recibido {$n_entradas} veces sin ninguna venta registrada — prioritario: verificar código de barras o referencia duplicada";
                } elseif ($n_entradas >= 2) {
                    $causa_nunca = "Recibido {$n_entradas} veces sin ninguna venta — verificar si el código de barras es correcto o si las ventas se registran bajo otra referencia";
                } else {
                    $causa_nunca = "Sin ventas registradas — verificar si el código de barras es correcto o si las ventas se registran bajo otra referencia";
                }
                $sev_nunca = ($n_entradas >= 2 || $tiene_devolucion) ? 'MEDIA' : 'BAJA';
                $incidencias[] = [
                    'idArticulo'                  => $id,
                    'tipo'                        => 'Entrada sin rotación previa',
                    'severidad'                   => $sev_nunca,
                    'stock_actual'                => $stock_actual,
                    'ultima_salida'               => null,
                    'semanas_desde_ultima_salida' => null,
                    'n_entradas'                  => $n_entradas,
                    'cantidad_recibida'           => $cantidad_recibida,
                    'fecha_primera_entrada'       => $fecha_primera_entrada,
                    'n_devoluciones'              => $n_devoluciones,
                    'cantidad_devuelta'           => $cantidad_devuelta,
                    'posible_causa'               => $causa_nunca,
                ];
            } elseif ($semanas >= $umbral_sin_rotacion) {
                $ratio_b  = $semanas / $umbral_sin_rotacion;
                $wsem     = round($semanas, 1);
                $sev_largo = ($n_entradas >= 2 || $ratio_b >= 2.0) ? 'MEDIA' : 'BAJA';
                if ($n_entradas >= 2) {
                    if ($ratio_b <= 1.5) {
                        $causa_b = "Pedido {$n_entradas} veces sin rotación activa — verificar si el comprador tiene visibilidad del stock disponible";
                    } elseif ($ratio_b <= 2.5) {
                        $causa_b = "Pedido {$n_entradas} veces pese a {$wsem} sem. sin movimiento — posible referencia sustituida sin darse de baja";
                    } else {
                        $causa_b = "Artículo inmovilizado con reposición activa ({$n_entradas} pedidos, {$wsem} sem. sin venta) — revisar proceso de compra";
                    }
                } else {
                    if ($ratio_b <= 1.5) {
                        $causa_b = "Sin movimiento en {$wsem} sem. — verificar si hay demanda estacional o si el artículo está bien ubicado en sala";
                    } elseif ($ratio_b <= 2.5) {
                        $causa_b = "Posible referencia sustituida o sin demanda activa — revisar si las ventas se registran bajo otra referencia similar";
                    } else {
                        $causa_b = "Artículo inmovilizado ({$wsem} sem. sin movimiento) — valorar eliminar del surtido activo o liquidar";
                    }
                }
                $incidencias[] = [
                    'idArticulo'                  => $id,
                    'tipo'                        => 'Entrada sin rotación previa',
                    'severidad'                   => $sev_largo,
                    'stock_actual'                => $stock_actual,
                    'ultima_salida'               => $ultima_venta,
                    'semanas_desde_ultima_salida' => round($semanas, 1),
                    'n_entradas'                  => $n_entradas,
                    'cantidad_recibida'           => $cantidad_recibida,
                    'fecha_primera_entrada'       => $fecha_primera_entrada,
                    'n_devoluciones'              => $n_devoluciones,
                    'cantidad_devuelta'           => $cantidad_devuelta,
                    'posible_causa'               => $causa_b,
                ];
            }
        }
        return $incidencias;
    }
}
