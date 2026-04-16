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
     * Determina la severidad C3a.
     *
     * La severidad base es MEDIA (caducidad teórica sin historial de ventas fiable).
     * Sube a ALTA si el artículo tenía ventas regulares y las ha perdido (caída de rotación
     * confirmada): la hipótesis de caducidad física no registrada es más sólida.
     *
     * @param bool $caida_rotacion  true si hay caída de rotación confirmada
     *
     * @return string 'ALTA' | 'MEDIA'
     */
    public function calcularSeveridadC3a(bool $caida_rotacion): string
    {
        return $caida_rotacion ? 'ALTA' : 'MEDIA';
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
        string $fi_periodo,
        int    $umbral_caducidad,
        int    $umbral_sin_rotacion,
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter              = [],
        int    $dias_post               = 14,
        float  $multiplicador_cadencia  = 3.0,
        bool   $incluir_stock_negativo  = false
    ): array {
        $fechaInicioMovimientos = $this->db->real_escape_string($fi_mov);
        $fechaFinMovimientos    = $this->db->real_escape_string($ff_mov);
        $fechaInicioStock       = $this->db->real_escape_string($fi_stock);
        $fechaInicioPeriodo     = $this->db->real_escape_string($fi_periodo);
        $filtroFamiliasSql      = $this->repo->familiaWhere($familias_incluir, $familias_excluir);
        $filtroArticulosSql     = $this->repo->idsWhere($ids_filter);
        $umbralMinimoSemanas    = min($umbral_caducidad, $umbral_sin_rotacion);

        $filasArticulos = $this->repo->queryUltimaVentaC3(
            $fechaInicioMovimientos,
            $fechaFinMovimientos,
            $fechaInicioStock,
            $fechaInicioPeriodo,
            $filtroFamiliasSql,
            $filtroArticulosSql,
            $umbralMinimoSemanas,
            $dias_post,
            $multiplicador_cadencia,
            $umbral_caducidad
        );
        if (isset($filasArticulos['error'])) return $filasArticulos;

        // Stock al cierre del periodo para los artículos afectados
        $mapaStock = [];
        if (!empty($filasArticulos)) {
            $idsArticulosCsv = implode(',', array_unique(array_map(fn($filaArticulo) => (int)$filaArticulo['idArticulo'], $filasArticulos)));
            $filasStock = $this->repo->queryStockRebobinado($idsArticulosCsv, $fechaFinMovimientos);
            if (!isset($filasStock['error'])) {
                foreach ($filasStock as $filaStock) {
                    $mapaStock[(int)$filaStock['idArticulo']] = (float)$filaStock['stock_en_periodo'];
                }
            }
        }

        // Si el período aún no ha terminado, usar hoy como referencia para evitar inflar semanas.
        $hoy = new DateTime('today');
        $fechaFinPeriodo = new DateTime($ff_mov);
        if ($fechaFinPeriodo > $hoy) {
            $fechaFinPeriodo = $hoy;
        }
        // Días totales del historial de ventas consultado (fi_stock → referencia efectiva)
        $periodoDias = (new DateTime($fi_stock))->diff($fechaFinPeriodo)->days + 1;
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

            // C3a: caducidad teórica — stock > 0 y el lote lleva demasiado tiempo en almacén.
            // La caída de rotación histórica actúa como modificador de severidad (MEDIA → ALTA),
            // no como condición de activación.
            $semanas_desde_entrada = $fechaPrimeraEntrada !== null
                ? (new DateTime($fechaPrimeraEntrada))->diff($fechaFinPeriodo)->days / 7.0
                : null;

            $stockOk = $stockActual === null
                || $stockActual > 0
                || ($incluir_stock_negativo && $stockActual < 0);
            $cumpleC3a = $semanas_desde_entrada !== null
                && $stockOk
                && $semanas_desde_entrada >= $umbral_caducidad;

            if ($cumpleC3a) {
                // Calcular caída de rotación como modificador de severidad
                $numeroVentasHistorico = (int)$filaArticulo['n_ventas_historico'];
                $cadenciaMediaDias     = $numeroVentasHistorico > 0
                    ? round($periodoDias / $numeroVentasHistorico, 1)
                    : null;
                $umbralEfectivoDias = $cadenciaMediaDias !== null
                    ? $cadenciaMediaDias * $multiplicador_cadencia
                    : PHP_INT_MAX;

                $desdeReposicion = $fechaPrimeraEntrada !== null
                    && $ultimaVenta !== null
                    && strcmp($fechaPrimeraEntrada, $ultimaVenta) > 0;

                // Para caída de rotación siempre se mide desde la última venta real:
                // lo que importa es cuánto tiempo lleva el artículo sin venderse,
                // independientemente de si fue repuesto después.
                $semanasDesdeUltimaVenta = $ultimaVenta !== null
                    ? (new DateTime($ultimaVenta))->diff($fechaFinPeriodo)->days / 7.0
                    : null;

                $caida_rotacion = $semanasDesdeUltimaVenta !== null
                    && $numeroVentasHistorico >= 3
                    && ($semanasDesdeUltimaVenta * 7) >= $umbralEfectivoDias;

                $severidad = $this->calcularSeveridadC3a($caida_rotacion);

                $semanasRedondeadasEntrada = round($semanas_desde_entrada, 1);
                $esAltaRotacion            = $cadenciaMediaDias !== null && $cadenciaMediaDias <= 7.0;

                if ($caida_rotacion && $esAltaRotacion) {
                    $causa = "Stock inmovilizado {$semanasRedondeadasEntrada} sem. en artículo de alta rotación — revisar estado del producto, EAN y exposición en sala";
                } elseif ($caida_rotacion) {
                    $causa = "Stock inmovilizado {$semanasRedondeadasEntrada} sem. con caída de ventas confirmada — posible caducidad, referencia sustituida o falta de demanda";
                } elseif ($ultimaVenta === null) {
                    $causa = "Stock en almacén {$semanasRedondeadasEntrada} sem. sin ninguna venta registrada — verificar si las ventas se registran bajo otra referencia";
                } elseif ($numeroVentasHistorico < 3) {
                    $causa = "Stock en almacén {$semanasRedondeadasEntrada} sem. con historial de ventas insuficiente para valorar la demanda";
                } else {
                    $causa = "Stock en almacén {$semanasRedondeadasEntrada} sem. — revisar estado del producto y si hay demanda activa";
                }

                $incidencias[] = [
                    'idArticulo'              => $idArticulo,
                    'tipo'                    => 'Caducidad teórica',
                    'severidad'               => $severidad,
                    'stock_actual'            => $stockActual,
                    'ultima_venta'            => $ultimaVenta,
                    'fecha_primera_entrada'   => $fechaPrimeraEntrada,
                    'desde_reposicion'        => $desdeReposicion,
                    'semanas_en_almacen'      => $semanasRedondeadasEntrada,
                    'caida_rotacion'          => $caida_rotacion,
                    'avg_cadencia_dias'       => $cadenciaMediaDias,
                    'n_entradas'              => $numeroEntradas,
                    'cantidad_recibida'       => $cantidadRecibida,
                    'n_ventas_historico'      => $numeroVentasHistorico,
                    'posible_causa'           => $causa,
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

        // Enriquecer C3a y C3b con proveedor habitual y coste estimado del stock inmovilizado
        if (!empty($incidencias)) {
            $idsC3csv   = implode(',', array_unique(array_map(fn($inc) => (int)$inc['idArticulo'], $incidencias)));
            $prov_map   = $this->repo->queryProveedorArticulos($idsC3csv, $fechaInicioStock, $fechaFinMovimientos);
            $precio_map = $this->repo->queryPrecioMedioCompra($idsC3csv, $fechaInicioStock, $fechaFinMovimientos);
            foreach ($incidencias as &$inc) {
                $prov = $prov_map[$inc['idArticulo']] ?? null;
                $inc['prov_habitual_nombre'] = $prov['prov_habitual_nombre'] ?? null;
                $inc['prov_habitual_n']      = $prov['prov_habitual_n']      ?? null;
                $inc['prov_ultimo_nombre']   = $prov['prov_ultimo_nombre']   ?? null;
                $inc['prov_ultima_fecha']    = $prov['prov_ultima_fecha']    ?? null;
                $inc['prov_es_mismo']        = $prov['prov_es_mismo']        ?? null;
                $precio = $precio_map[$inc['idArticulo']] ?? null;
                $inc['precio_medio_compra'] = $precio;
                $stockInc = $inc['stock_actual'] ?? null;
                $inc['coste_estimado'] = ($precio !== null && $stockInc !== null && $stockInc > 0)
                    ? round($stockInc * $precio, 2)
                    : null;
            }
            unset($inc);
        }

        return $incidencias;
    }
}
