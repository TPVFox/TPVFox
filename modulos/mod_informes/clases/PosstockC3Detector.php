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

    // Algoritmos internos (públicos para testabilidad directa)

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
        $fechaInicioMovimientos = $this->db->real_escape_string($fi_mov);
        $fechaFinMovimientos    = $this->db->real_escape_string($ff_mov);
        $fechaInicioStock       = $this->db->real_escape_string($fi_stock);
        $filtroFamiliasSql      = $this->repo->familiaWhere($familias_incluir, $familias_excluir);
        $filtroArticulosSql     = $this->repo->idsWhere($ids_filter);
        $umbralMinimoSemanas    = min($umbral_caducidad, $umbral_sin_rotacion);

        $filasArticulos = $this->repo->queryUltimaVentaC3(
            $fechaInicioMovimientos,
            $fechaFinMovimientos,
            $fechaInicioStock,
            $filtroFamiliasSql,
            $filtroArticulosSql,
            $umbralMinimoSemanas,
            $dias_post,
            $multiplicador_cadencia
        );
        if (isset($filasArticulos['error'])) return $filasArticulos;

        // Stock al cierre del periodo para los artículos afectados
        $mapaStock = [];
        if (!empty($filasArticulos)) {
            $idsArticulosCsv = implode(',', array_unique(array_map(fn($filaArticulo) => (int)$filaArticulo['idArticulo'], $filasArticulos)));
            $filasStock = $this->repo->queryStockRebobinado($idsArticulosCsv, $fechaFinMovimientos, false);
            if (!isset($filasStock['error'])) {
                foreach ($filasStock as $filaStock) {
                    $mapaStock[(int)$filaStock['idArticulo']] = (float)$filaStock['stock_en_periodo'];
                }
            }
        }

        $fechaFinPeriodo = new DateTime($ff_mov);
        // Días totales del historial de ventas consultado (fi_stock → ff_mov)
        $periodoDias = (new DateTime($fi_stock))->diff(new DateTime($ff_mov))->days + 1;
        $incidencias = [];

        foreach ($filasArticulos as $filaArticulo) {
            $idArticulo           = (int)$filaArticulo['idArticulo'];
            $ultimaVenta          = $filaArticulo['ultima_venta'];
            $stockActual          = $mapaStock[$idArticulo] ?? null;
            $numeroEntradas       = (int)$filaArticulo['n_entradas'];
            $cantidadRecibida     = (float)$filaArticulo['cantidad_recibida'];
            $fechaPrimeraEntrada  = $filaArticulo['fecha_primera_entrada'];
            $numeroDevoluciones   = (int)$filaArticulo['n_devoluciones'];
            $cantidadDevuelta     = (float)$filaArticulo['cantidad_devuelta'];
            $tieneDevolucion      = $numeroDevoluciones > 0;

            if ($ultimaVenta !== null) {
                $semanasSinRotacion = (new DateTime($ultimaVenta))->diff($fechaFinPeriodo)->days / 7.0;
            } else {
                $semanasSinRotacion = null;
            }

            // C3a: caída de rotación (umbral dinámico: avg_cadencia × multiplicador)
            $numeroVentasHistorico = (int)$filaArticulo['n_ventas_historico'];
            $cadenciaMediaDias     = $numeroVentasHistorico > 0
                ? round($periodoDias / $numeroVentasHistorico, 1)
                : null;
            $umbralEfectivoDias = $cadenciaMediaDias !== null
                ? $cadenciaMediaDias * $multiplicador_cadencia
                : PHP_INT_MAX;

            // Referente de "días sin venta": si el pedido llegó DESPUÉS de la última venta
            // (el artículo se agotó y se repuso), contar desde fecha_primera_entrada.
            $desdeReposicion = $fechaPrimeraEntrada !== null
                && $ultimaVenta !== null
                && strcmp($fechaPrimeraEntrada, $ultimaVenta) > 0;
            $fechaReferenciaC3a = $desdeReposicion ? $fechaPrimeraEntrada : $ultimaVenta;
            $semanasC3a = $fechaReferenciaC3a !== null
                ? (new DateTime($fechaReferenciaC3a))->diff($fechaFinPeriodo)->days / 7.0
                : null;

            // Stock > 0 requerido + n_ventas_historico >= 3 para cadencia fiable
            $cumpleC3a = $semanasC3a !== null
                && $numeroVentasHistorico >= 3
                && ($stockActual === null || $stockActual > 0)
                && ($semanasC3a * 7) >= $umbralEfectivoDias;
            if ($cumpleC3a) {
                $ratioC3a   = ($semanasC3a * 7) / max(1, $umbralEfectivoDias);
                $severidad  = $this->calcularSeveridadC3a($ratioC3a);
                $semanasRedondeadasC3a = round($semanasC3a, 1);
                $esAltaRotacion        = $cadenciaMediaDias !== null && $cadenciaMediaDias <= 7.0;
                if ($desdeReposicion) {
                    if ($esAltaRotacion) {
                        if ($ratioC3a <= 1.5) {
                            $causa = "Recibido sin venta desde la última recepción — verificar ubicación en sala, EAN y precio";
                        } elseif ($ratioC3a <= 2.5) {
                            $causa = "Nuevo pedido sin rotación en artículo de alta rotación — posible merma no registrada o problema de EAN";
                        } else {
                            $causa = "Nuevo stock paralizado desde la recepción — revisión urgente: exposición, estado del producto y precio";
                        }
                    } else {
                        if ($ratioC3a <= 1.5) {
                            $causa = "Repuesto tras agotamiento sin rotación posterior — verificar si hay demanda activa antes del próximo pedido";
                        } elseif ($ratioC3a <= 2.5) {
                            $causa = "Artículo repuesto pero sin demanda activa — posible artículo estacional o referencia sustituida";
                        } else {
                            $causa = "Nuevo stock sin movimiento desde la recepción — valorar devolución al proveedor o liquidación";
                        }
                    }
                } elseif ($esAltaRotacion) {
                    if ($ratioC3a <= 1.5) {
                        $causa = "Artículo de alta rotación con caída reciente — verificar ubicación en sala, EAN y precio";
                    } elseif ($ratioC3a <= 2.5) {
                        $causa = "Alta rotación interrumpida — posible merma no registrada, problema de EAN o artículo agotado en lineal";
                    } else {
                        $causa = "Artículo de alta rotación sin ventas desde hace {$semanasRedondeadasC3a} sem. — revisión urgente de exposición y estado del producto";
                    }
                } else {
                    if ($ratioC3a <= 1.5) {
                        $causa = "Posible artículo estacional — revisar ventas en el mismo periodo del año anterior";
                    } elseif ($ratioC3a <= 2.5) {
                        $causa = "Posible referencia sustituida — verificar si hay artículo similar activo con rotación";
                    } else {
                        $causa = "Sin demanda desde hace {$semanasRedondeadasC3a} sem. — valorar liquidación o baja de referencia";
                    }
                }
                $incidencias[] = [
                    'idArticulo'                 => $idArticulo,
                    'tipo'                       => 'Caída de rotación',
                    'severidad'                  => $severidad,
                    'stock_actual'               => $stockActual,
                    'ultima_venta'               => $ultimaVenta,
                    'fecha_primera_entrada'      => $fechaPrimeraEntrada,
                    'desde_reposicion'           => $desdeReposicion,
                    'semanas_desde_ultima_venta' => $semanasRedondeadasC3a,
                    'avg_cadencia_dias'          => $cadenciaMediaDias,
                    'n_entradas'                 => $numeroEntradas,
                    'cantidad_recibida'          => $cantidadRecibida,
                    'posible_causa'              => $causa,
                ];
            }

            // C3b: entrada sin rotación previa
            if ($ultimaVenta === null) {
                if ($numeroEntradas <= 1 && $filaArticulo['primera_venta_post'] !== null) {
                    continue;
                }
                if ($tieneDevolucion) {
                    $ratioDevolucion = $cantidadRecibida > 0 ? $cantidadDevuelta / $cantidadRecibida : 0;
                    if ($ratioDevolucion >= 0.8) {
                        $causa_nunca = "Artículo recibido y devuelto casi en su totalidad — verificar si el pedido fue rechazado o si hubo un error en el albarán";
                    } else {
                        $causa_nunca = "Artículo con recepciones y devoluciones parciales sin ventas — posible problema de calidad o pedido incorrecto";
                    }
                } elseif ($numeroEntradas >= 3) {
                    $causa_nunca = "Recibido {$numeroEntradas} veces sin ninguna venta registrada — prioritario: verificar código de barras o referencia duplicada";
                } elseif ($numeroEntradas >= 2) {
                    $causa_nunca = "Recibido {$numeroEntradas} veces sin ninguna venta — verificar si el código de barras es correcto o si las ventas se registran bajo otra referencia";
                } else {
                    $causa_nunca = "Sin ventas registradas — verificar si el código de barras es correcto o si las ventas se registran bajo otra referencia";
                }
                $severidadSinRotacion = $this->calcularSeveridadC3b($numeroEntradas, $tieneDevolucion);
                $incidencias[] = [
                    'idArticulo'                  => $idArticulo,
                    'tipo'                        => 'Entrada sin rotación previa',
                    'severidad'                   => $severidadSinRotacion,
                    'stock_actual'                => $stockActual,
                    'ultima_salida'               => null,
                    'semanas_desde_ultima_salida' => null,
                    'n_entradas'                  => $numeroEntradas,
                    'cantidad_recibida'           => $cantidadRecibida,
                    'fecha_primera_entrada'       => $fechaPrimeraEntrada,
                    'n_devoluciones'              => $numeroDevoluciones,
                    'cantidad_devuelta'           => $cantidadDevuelta,
                    'posible_causa'               => $causa_nunca,
                ];
            } elseif ($semanasSinRotacion >= $umbral_sin_rotacion) {
                $ratioC3b  = $semanasSinRotacion / $umbral_sin_rotacion;
                $semanasRedondeadasC3b = round($semanasSinRotacion, 1);
                $severidadLarga = $this->calcularSeveridadC3b($numeroEntradas, false, $ratioC3b);
                if ($numeroEntradas >= 2) {
                    if ($ratioC3b <= 1.5) {
                        $causa_b = "Pedido {$numeroEntradas} veces sin rotación activa — verificar si el comprador tiene visibilidad del stock disponible";
                    } elseif ($ratioC3b <= 2.5) {
                        $causa_b = "Pedido {$numeroEntradas} veces pese a {$semanasRedondeadasC3b} sem. sin movimiento — posible referencia sustituida sin darse de baja";
                    } else {
                        $causa_b = "Artículo inmovilizado con reposición activa ({$numeroEntradas} pedidos, {$semanasRedondeadasC3b} sem. sin venta) — revisar proceso de compra";
                    }
                } else {
                    if ($ratioC3b <= 1.5) {
                        $causa_b = "Sin movimiento en {$semanasRedondeadasC3b} sem. — verificar si hay demanda estacional o si el artículo está bien ubicado en sala";
                    } elseif ($ratioC3b <= 2.5) {
                        $causa_b = "Posible referencia sustituida o sin demanda activa — revisar si las ventas se registran bajo otra referencia similar";
                    } else {
                        $causa_b = "Artículo inmovilizado ({$semanasRedondeadasC3b} sem. sin movimiento) — valorar eliminar del surtido activo o liquidar";
                    }
                }
                $incidencias[] = [
                    'idArticulo'                  => $idArticulo,
                    'tipo'                        => 'Entrada sin rotación previa',
                    'severidad'                   => $severidadLarga,
                    'stock_actual'                => $stockActual,
                    'ultima_salida'               => $ultimaVenta,
                    'semanas_desde_ultima_salida' => $semanasRedondeadasC3b,
                    'n_entradas'                  => $numeroEntradas,
                    'cantidad_recibida'           => $cantidadRecibida,
                    'fecha_primera_entrada'       => $fechaPrimeraEntrada,
                    'n_devoluciones'              => $numeroDevoluciones,
                    'cantidad_devuelta'           => $cantidadDevuelta,
                    'posible_causa'               => $causa_b,
                ];
            }
        }
        return $incidencias;
    }
}
