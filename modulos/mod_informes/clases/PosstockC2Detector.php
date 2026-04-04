<?php

/**
 * PosstockC2Detector — Detector de Entrada con Stock Alto (Caso C2).
 *
 * Extraído de ClasePosstock como parte de la Fase 4 de refactorización.
 *
 * Métodos públicos:
 *   detectar(...)  — orquestador C2 completo (individual + acumulación + tendencia)
 */

class PosstockC2Detector
{
    public function __construct(
        private mysqli $db,
        private PosstockQueryRepository $repo
    ) {}

    /**
     * Caso 2 — Entrada con stock alto.
     *
     * C2 MEDIA — stock_previo >= nunidades * umbral_sobrestock
     *
     * @param string $fi_mov
     * @param string $ff_mov
     * @param string $fi_stock
     * @param string $ff_stock
     * @param float  $umbral_sobrestock
     * @param float  $umbral_duplicado
     * @param float  $umbral_severo
     * @param int    $umbral_cobertura_dias
     * @param array  $familias_incluir
     * @param array  $familias_excluir
     * @param array  $ids_filter
     * @param array  $stock_base_cache   Pre-calculado por el caller para evitar doble consulta
     *
     * @return array  Filas de incidencia o ['error' => ...]
     */
    public function detectar(
        string $fi_mov,
        string $ff_mov,
        string $fi_stock,
        string $ff_stock,
        float  $umbral_sobrestock,
        float  $umbral_duplicado,
        float  $umbral_severo,
        int    $umbral_cobertura_dias,
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter = [],
        array  $stock_base_cache = []
    ): array {
        $fechaInicio       = $this->db->real_escape_string($fi_mov);
        $fechaFin          = $this->db->real_escape_string($ff_mov);
        $filtroFamiliasSql = $this->repo->familiaWhere($familias_incluir, $familias_excluir);
        $filtroArticulosSql = $this->repo->idsWhere($ids_filter);

        $filasEntradas = $this->repo->queryEntradasC2($fechaInicio, $fechaFin, $filtroFamiliasSql, $filtroArticulosSql);
        if (isset($filasEntradas['error'])) return $filasEntradas;
        if (empty($filasEntradas)) return [];

        $idsArticulos = [];
        foreach ($filasEntradas as $filaEntrada) {
            $idsArticulos[(int)$filaEntrada['idArticulo']] = true;
        }
        $idsArticulos = array_keys($idsArticulos);

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

        // Paso 1: recoger candidatos (filtro ratio)
        $candidatos = [];
        foreach ($filasEntradas as $filaEntrada) {
            $idArticulo   = (int)$filaEntrada['idArticulo'];
            $saldo_base   = $stock_base[$idArticulo]['saldo_acumulado'] ?? 0.0;
            $stock_previo = $saldo_base + (float)$filaEntrada['cum_before'];
            $nunidades    = (float)$filaEntrada['nunidades'];
            if ($nunidades <= 0 || $stock_previo < $nunidades * $umbral_sobrestock) continue;

            $ratio = $stock_previo / $nunidades;
            $clasificacion = $this->clasificarRatio($ratio, $umbral_duplicado, $umbral_severo);

            $candidatos[] = [
                'idArticulo'    => $idArticulo,
                'tipo'          => 'Entrada con stock alto',
                'severidad'     => 'MEDIA',
                'nunidades'     => $nunidades,
                'stock_previo'  => $stock_previo,
                'ratio'         => round($ratio, 2),
                'c2_categoria'  => $clasificacion['categoria'],
                'fecha'         => $filaEntrada['fecha'],
                'posible_causa' => $clasificacion['posible_causa'],
            ];
        }
        if (empty($candidatos)) return [];

        // Paso 2: filtro de cobertura
        $idsCandidatos  = array_unique(array_column($candidatos, 'idArticulo'));
        $mapaVentas     = $this->repo->queryVentasC2(implode(',', $idsCandidatos), $fechaInicio, $fechaFin);
        $numeroDias     = max(1, (int)((strtotime($ff_mov) - strtotime($fi_mov)) / 86400) + 1);

        $incidencias = [];
        foreach ($candidatos as $candidato) {
            $ventas = $mapaVentas[$candidato['idArticulo']] ?? 0.0;
            if ($ventas > 0) {
                $ventasDiarias = $ventas / $numeroDias;

                // En periodos de 1-2 dias con ventas residuales la extrapolacion puede
                // inflar cobertura_dias de forma no representativa (falso positivo C2).
                if ($numeroDias <= 2 && $ventasDiarias < 1.0) {
                    continue;
                }

                $cobertura = (int)round($candidato['stock_previo'] / $ventasDiarias);
                if ($cobertura <= $umbral_cobertura_dias) continue;  // rotación suficiente, falso positivo
                $candidato['cobertura_dias'] = $cobertura;
            } else {
                $candidato['cobertura_dias'] = null;  // sin ventas = cobertura infinita, siempre flagear
            }
            $incidencias[] = $candidato;
        }
        if (empty($incidencias)) return [];

        // Paso 3: enriquecer con recepción anterior
        $this->repo->queryDetalleC2($incidencias, $fi_mov, $ff_mov);

        // Paso 4: reasignar severidad y posible_causa con todas las señales.
        foreach ($incidencias as &$incidencia) {
            $diasDesdeAnterior      = $incidencia['dias_desde_anterior'];
            $nunidadesAnteriores    = $incidencia['nunidades_anterior'];
            $ventasEntreRecepciones = $incidencia['ventas_entre_recepciones'];
            $coberturaDias          = $incidencia['cobertura_dias'];

            $esDuplicadoProbable = $diasDesdeAnterior !== null && $diasDesdeAnterior <= 1 && $nunidadesAnteriores !== null
                && (abs($incidencia['nunidades'] - $nunidadesAnteriores) / max($incidencia['nunidades'], $nunidadesAnteriores)) < 0.15;

            $esDuplicadoPosible = !$esDuplicadoProbable && $diasDesdeAnterior !== null && $diasDesdeAnterior <= 3 && $nunidadesAnteriores !== null
                && (abs($incidencia['nunidades'] - $nunidadesAnteriores) / max($incidencia['nunidades'], $nunidadesAnteriores)) < 0.15;

            if ($coberturaDias === null || $esDuplicadoProbable) {
                $incidencia['severidad'] = 'ALTA';
            }

            if ($esDuplicadoProbable) {
                $incidencia['posible_causa'] = "Recepción de cantidad similar hace {$diasDesdeAnterior} día(s): probable albarán registrado dos veces";
            } elseif ($esDuplicadoPosible) {
                $incidencia['posible_causa'] = "Recepción de cantidad similar hace {$diasDesdeAnterior} días: verificar si el albarán se registró dos veces";
            } elseif ($coberturaDias === null) {
                $incidencia['posible_causa'] = 'Sobrestock sin salida: el artículo no registra ventas en el periodo analizado';
            } elseif ($diasDesdeAnterior !== null && $diasDesdeAnterior <= 14 && $ventasEntreRecepciones !== null && $ventasEntreRecepciones < 1) {
                $incidencia['posible_causa'] = "Sobrestock por acumulación: no hubo ventas entre la recepción anterior y esta nueva entrada ({$diasDesdeAnterior} días)";
            } elseif ($coberturaDias > 180) {
                $incidencia['posible_causa'] = 'Sobrestock crónico: el stock disponible cubre más de 6 meses al ritmo de ventas actual';
            }
        }
        unset($incidencia);

        // Paso 5: consolidar secuencias de acumulación por artículo
        $incidenciasPorArticulo = [];
        foreach ($incidencias as $incidencia) {
            $incidenciasPorArticulo[$incidencia['idArticulo']][] = $incidencia;
        }

        $incidenciasFinales = [];

        foreach ($incidenciasPorArticulo as $idArticulo => $incidenciasArticulo) {
            usort($incidenciasArticulo, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));

            $numeroEventos = count($incidenciasArticulo);
            if ($this->esAcumulacion($incidenciasArticulo)) {
                $primeraIncidencia = $incidenciasArticulo[0];
                $ultimaIncidencia  = end($incidenciasArticulo);
                $stockMaximoPrevio = max(array_column($incidenciasArticulo, 'stock_previo'));

                $intervalosDias = [];
                foreach ($incidenciasArticulo as $incidencia) {
                    if ($incidencia['dias_desde_anterior'] !== null) $intervalosDias[] = $incidencia['dias_desde_anterior'];
                }
                $gapMedio = !empty($intervalosDias) ? (int)round(array_sum($intervalosDias) / count($intervalosDias)) : null;

                if ($gapMedio !== null && $gapMedio <= 3) {
                    $causa = "Verificar: merma no registrada · devoluciones no gestionadas · ventas no escaneadas en mostrador";
                } elseif ($gapMedio !== null && $gapMedio <= 10) {
                    $causa = "(1) Merma no registrada — descartes sin movimiento de baja · (2) Devoluciones pendientes — {$numeroEventos} recepciones semanales sin retorno · (3) Cruce de artículo — verificar si se vende bajo referencia similar";
                } else {
                    $causa = "(1) Cruce de artículo — verificar si se vende bajo referencia similar · (2) Merma no registrada · (3) Stock inmovilizado — {$numeroEventos} pedidos acumulados sin salida registrada";
                }

                $incidenciasFinales[] = [
                    'idArticulo'               => $idArticulo,
                    'tipo'                     => 'Entrada con stock alto',
                    'severidad'                => 'ALTA',
                    'nunidades'                => $ultimaIncidencia['nunidades'],
                    'stock_previo'             => $stockMaximoPrevio,
                    'ratio'                    => $ultimaIncidencia['ratio'],
                    'c2_categoria'             => 'acumulacion',
                    'fecha'                    => $ultimaIncidencia['fecha'],
                    'fecha_inicio'             => $primeraIncidencia['fecha'],
                    'n_eventos'                => $numeroEventos,
                    'gap_medio'                => $gapMedio,
                    'cobertura_dias'           => $ultimaIncidencia['cobertura_dias'],
                    'dias_desde_anterior'      => $ultimaIncidencia['dias_desde_anterior'],
                    'nunidades_anterior'       => $ultimaIncidencia['nunidades_anterior'],
                    'ventas_entre_recepciones' => $ultimaIncidencia['ventas_entre_recepciones'],
                    'posible_causa'            => $causa,
                ];
                continue;
            }
            foreach ($incidenciasArticulo as $incidencia) {
                $incidenciasFinales[] = $incidencia;
            }
        }

        // Paso 6: C2b, sobrestock progresivo (cobertura creciente con ventas).
        $incidenciasConsolidadas = [];
        $incidenciasIndividuales = [];
        foreach ($incidenciasFinales as $incidencia) {
            if (($incidencia['c2_categoria'] ?? '') === 'acumulacion') {
                $incidenciasConsolidadas[] = $incidencia;
            } else {
                $incidenciasIndividuales[] = $incidencia;
            }
        }

        $incidenciasIndividualesPorArticulo = [];
        foreach ($incidenciasIndividuales as $incidencia) {
            $incidenciasIndividualesPorArticulo[$incidencia['idArticulo']][] = $incidencia;
        }

        $resultadoTendencia = [];
        foreach ($incidenciasIndividualesPorArticulo as $idArticulo => $incidenciasArticulo) {
            usort($incidenciasArticulo, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));
            $numeroEventos = count($incidenciasArticulo);

            if ($numeroEventos >= 3) {
                $coberturas = array_values(array_filter(
                    array_column($incidenciasArticulo, 'cobertura_dias'),
                    fn($c) => $c !== null
                ));

                if ($this->esTendenciaCreciente($coberturas)) {
                    $coberturaInicio = (int)$coberturas[0];
                    $coberturaFinal  = (int)end($coberturas);
                    $primeraIncidencia = $incidenciasArticulo[0];
                    $ultimaIncidencia  = end($incidenciasArticulo);
                    $stockMaximoPrevio = max(array_column($incidenciasArticulo, 'stock_previo'));
                    $resultadoTendencia[] = [
                        'idArticulo'               => $idArticulo,
                        'tipo'                     => 'Entrada con stock alto',
                        'severidad'                => 'ALTA',
                        'nunidades'                => $ultimaIncidencia['nunidades'],
                        'stock_previo'             => $stockMaximoPrevio,
                        'ratio'                    => $ultimaIncidencia['ratio'],
                        'c2_categoria'             => 'tendencia',
                        'fecha'                    => $ultimaIncidencia['fecha'],
                        'fecha_inicio'             => $primeraIncidencia['fecha'],
                        'n_eventos'                => $numeroEventos,
                        'cobertura_inicio'         => $coberturaInicio,
                        'cobertura_dias'           => $coberturaFinal,
                        'dias_desde_anterior'      => $ultimaIncidencia['dias_desde_anterior'],
                        'nunidades_anterior'       => $ultimaIncidencia['nunidades_anterior'],
                        'ventas_entre_recepciones' => $ultimaIncidencia['ventas_entre_recepciones'],
                        'posible_causa'            => "Sobrestock progresivo: la cobertura creció de {$coberturaInicio} a {$coberturaFinal} días en {$numeroEventos} entregas — el ritmo de pedidos supera sistemáticamente las ventas",
                    ];
                    continue;
                }
            }
            foreach ($incidenciasArticulo as $incidencia) {
                $resultadoTendencia[] = $incidencia;
            }
        }

        return array_merge($incidenciasConsolidadas, $resultadoTendencia);
    }

    // Métodos algorítmicos puros (sin BD) — públicos para tests unitarios
    /**
     * Clasifica un ratio stock_previo/nunidades en categoría C2.
     *
     * @return array{categoria: string, posible_causa: string}
     */
    public function clasificarRatio(float $ratio, float $umbral_duplicado, float $umbral_severo): array
    {
        if ($ratio <= $umbral_duplicado) {
            return [
                'categoria'     => 'duplicado',
                'posible_causa' => 'Stock disponible similar a la entrada recibida: el pedido podría no estar justificado',
            ];
        }

        if ($ratio >= $umbral_severo) {
            return [
                'categoria'     => 'severo',
                'posible_causa' => 'Sobrestock significativo: el stock previo superaba ampliamente la cantidad recibida',
            ];
        }

        return [
            'categoria'     => 'elevado',
            'posible_causa' => 'Sobrestock moderado: el stock disponible superaba el umbral establecido antes de recibir la entrada',
        ];
    }

    /**
     * Determina si un grupo de incidencias del mismo artículo constituye una
     * secuencia de acumulación (≥ 3 entradas con ≥ 70 % de eventos sin salida).
     *
     * @param array $lista  Array de incidencias; cada elemento debe tener
     *                      'ventas_entre_recepciones' (float|null) y 'cobertura_dias' (int|null).
     */
    public function esAcumulacion(array $lista, float $umbral_fraccion = 0.70): bool
    {
        $numeroEventos = count($lista);
        if ($numeroEventos < 3) return false;

        $eventosAcumulacion = 0;
        foreach ($lista as $incidencia) {
            $ventasEntreRecepciones = $incidencia['ventas_entre_recepciones'];
            $coberturaDias = $incidencia['cobertura_dias'];
            if (($ventasEntreRecepciones !== null && $ventasEntreRecepciones < 1) || ($ventasEntreRecepciones === null && $coberturaDias === null)) {
                $eventosAcumulacion++;
            }
        }

        return ($eventosAcumulacion / $numeroEventos) >= $umbral_fraccion;
    }

    /**
     * Determina si un array de coberturas (días) muestra tendencia creciente significativa.
     *
     * Requisitos: al menos 3 valores no-null, cob_ini > 0,
     * cob_fin/cob_ini >= $ratio_min y cob_fin >= $cob_min_fin.
     *
     * @param array<int|null> $coberturas  Coberturas en días ordenadas cronológicamente.
     */
    public function esTendenciaCreciente(array $coberturas, float $ratio_min = 1.5, int $cob_min_fin = 45): bool
    {
        $coberturasValidas = array_values(array_filter($coberturas, fn($cobertura) => $cobertura !== null));
        if (count($coberturasValidas) < 3) return false;

        $coberturaInicio = (int)$coberturasValidas[0];
        $coberturaFin = (int)end($coberturasValidas);

        return $coberturaInicio > 0
            && ($coberturaFin / $coberturaInicio) >= $ratio_min
            && $coberturaFin >= $cob_min_fin;
    }
}
