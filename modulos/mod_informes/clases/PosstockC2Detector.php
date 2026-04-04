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

        // ── Paso 1: recoger candidatos (filtro ratio) ────────────────────────
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

        // ── Paso 2: filtro de cobertura ───────────────────────────────────────
        $idsCandidatos  = array_unique(array_column($candidatos, 'idArticulo'));
        $mapaVentas     = $this->repo->queryVentasC2(implode(',', $idsCandidatos), $fechaInicio, $fechaFin);
        $numeroDias     = max(1, (int)((strtotime($ff_mov) - strtotime($fi_mov)) / 86400) + 1);

        $incidencias = [];
        foreach ($candidatos as $candidato) {
            $ventas = $mapaVentas[$candidato['idArticulo']] ?? 0.0;
            if ($ventas > 0) {
                $cobertura = (int)round($candidato['stock_previo'] / ($ventas / $numeroDias));
                if ($cobertura <= $umbral_cobertura_dias) continue;  // rotación suficiente, falso positivo
                $candidato['cobertura_dias'] = $cobertura;
            } else {
                $candidato['cobertura_dias'] = null;  // sin ventas = cobertura infinita, siempre flagear
            }
            $incidencias[] = $candidato;
        }
        if (empty($incidencias)) return [];

        // ── Paso 3: enriquecer con recepción anterior ─────────────────────────
        $this->repo->queryDetalleC2($incidencias, $fi_mov, $ff_mov);

        // ── Paso 4: reasignar severidad y posible_causa con todas las señales ─
        foreach ($incidencias as &$inc) {
            $dias    = $inc['dias_desde_anterior'];
            $nunidades_a = $inc['nunidades_anterior'];
            $vtr     = $inc['ventas_entre_recepciones'];
            $cob     = $inc['cobertura_dias'];

            $es_duplicado_probable = $dias !== null && $dias <= 1 && $nunidades_a !== null
                && (abs($inc['nunidades'] - $nunidades_a) / max($inc['nunidades'], $nunidades_a)) < 0.15;

            $es_duplicado_posible = !$es_duplicado_probable && $dias !== null && $dias <= 3 && $nunidades_a !== null
                && (abs($inc['nunidades'] - $nunidades_a) / max($inc['nunidades'], $nunidades_a)) < 0.15;

            if ($cob === null || $es_duplicado_probable) {
                $inc['severidad'] = 'ALTA';
            }

            if ($es_duplicado_probable) {
                $inc['posible_causa'] = "Recepción de cantidad similar hace {$dias} día(s): probable albarán registrado dos veces";
            } elseif ($es_duplicado_posible) {
                $inc['posible_causa'] = "Recepción de cantidad similar hace {$dias} días: verificar si el albarán se registró dos veces";
            } elseif ($cob === null) {
                $inc['posible_causa'] = 'Sobrestock sin salida: el artículo no registra ventas en el periodo analizado';
            } elseif ($dias !== null && $dias <= 14 && $vtr !== null && $vtr < 1) {
                $inc['posible_causa'] = "Sobrestock por acumulación: no hubo ventas entre la recepción anterior y esta nueva entrada ({$dias} días)";
            } elseif ($cob > 180) {
                $inc['posible_causa'] = 'Sobrestock crónico: el stock disponible cubre más de 6 meses al ritmo de ventas actual';
            }
        }
        unset($inc);

        // ── Paso 5: consolidar secuencias de acumulación por artículo ─────────
        $grupos = [];
        foreach ($incidencias as $inc) {
            $grupos[$inc['idArticulo']][] = $inc;
        }

        $incidencias_final = [];

        foreach ($grupos as $idArt => $lista) {
            usort($lista, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));

            $n_total = count($lista);
            if ($n_total >= 3) {
                $n_acum = 0;
                foreach ($lista as $inc) {
                    $vtr = $inc['ventas_entre_recepciones'];
                    $cob = $inc['cobertura_dias'];
                    $es_acum = ($vtr !== null && $vtr < 1)
                        || ($vtr === null && $cob === null);
                    if ($es_acum) $n_acum++;
                }
                if ($n_acum / $n_total >= 0.70) {
                    $primero   = $lista[0];
                    $ultimo    = end($lista);
                    $stock_max = max(array_column($lista, 'stock_previo'));

                    $gaps = [];
                    foreach ($lista as $inc) {
                        if ($inc['dias_desde_anterior'] !== null) $gaps[] = $inc['dias_desde_anterior'];
                    }
                    $gap_medio = !empty($gaps) ? (int)round(array_sum($gaps) / count($gaps)) : null;

                    if ($gap_medio !== null && $gap_medio <= 3) {
                        $causa = "Verificar: merma no registrada · devoluciones no gestionadas · ventas no escaneadas en mostrador";
                    } elseif ($gap_medio !== null && $gap_medio <= 10) {
                        $causa = "(1) Merma no registrada — descartes sin movimiento de baja · (2) Devoluciones pendientes — {$n_total} recepciones semanales sin retorno · (3) Cruce de artículo — verificar si se vende bajo referencia similar";
                    } else {
                        $causa = "(1) Cruce de artículo — verificar si se vende bajo referencia similar · (2) Merma no registrada · (3) Stock inmovilizado — {$n_total} pedidos acumulados sin salida registrada";
                    }

                    $incidencias_final[] = [
                        'idArticulo'               => $idArt,
                        'tipo'                     => 'Entrada con stock alto',
                        'severidad'                => 'ALTA',
                        'nunidades'                => $ultimo['nunidades'],
                        'stock_previo'             => $stock_max,
                        'ratio'                    => $ultimo['ratio'],
                        'c2_categoria'             => 'acumulacion',
                        'fecha'                    => $ultimo['fecha'],
                        'fecha_inicio'             => $primero['fecha'],
                        'n_eventos'                => $n_total,
                        'gap_medio'                => $gap_medio,
                        'cobertura_dias'           => $ultimo['cobertura_dias'],
                        'dias_desde_anterior'      => $ultimo['dias_desde_anterior'],
                        'nunidades_anterior'       => $ultimo['nunidades_anterior'],
                        'ventas_entre_recepciones' => $ultimo['ventas_entre_recepciones'],
                        'posible_causa'            => $causa,
                    ];
                    continue;
                }
            }
            foreach ($lista as $inc) {
                $incidencias_final[] = $inc;
            }
        }

        // ── Paso 6: C2b — sobrestock progresivo (cobertura creciente con ventas) ─
        $consolidadas = [];
        $individuales = [];
        foreach ($incidencias_final as $inc) {
            if (($inc['c2_categoria'] ?? '') === 'acumulacion') {
                $consolidadas[] = $inc;
            } else {
                $individuales[] = $inc;
            }
        }

        $grupos_b = [];
        foreach ($individuales as $inc) {
            $grupos_b[$inc['idArticulo']][] = $inc;
        }

        $resultado_b = [];
        foreach ($grupos_b as $idArt => $lista) {
            usort($lista, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));
            $n = count($lista);

            if ($n >= 3) {
                $cobs = array_values(array_filter(
                    array_column($lista, 'cobertura_dias'),
                    fn($c) => $c !== null
                ));

                if (count($cobs) >= 3) {
                    $cob_ini = (int)$cobs[0];
                    $cob_fin = (int)end($cobs);

                    if ($cob_ini > 0 && ($cob_fin / $cob_ini) >= 1.5 && $cob_fin >= 45) {
                        $primero   = $lista[0];
                        $ultimo    = end($lista);
                        $stock_max = max(array_column($lista, 'stock_previo'));
                        $resultado_b[] = [
                            'idArticulo'               => $idArt,
                            'tipo'                     => 'Entrada con stock alto',
                            'severidad'                => 'ALTA',
                            'nunidades'                => $ultimo['nunidades'],
                            'stock_previo'             => $stock_max,
                            'ratio'                    => $ultimo['ratio'],
                            'c2_categoria'             => 'tendencia',
                            'fecha'                    => $ultimo['fecha'],
                            'fecha_inicio'             => $primero['fecha'],
                            'n_eventos'                => $n,
                            'cobertura_inicio'         => $cob_ini,
                            'cobertura_dias'           => $cob_fin,
                            'dias_desde_anterior'      => $ultimo['dias_desde_anterior'],
                            'nunidades_anterior'       => $ultimo['nunidades_anterior'],
                            'ventas_entre_recepciones' => $ultimo['ventas_entre_recepciones'],
                            'posible_causa'            => "Sobrestock progresivo: la cobertura creció de {$cob_ini} a {$cob_fin} días en {$n} entregas — el ritmo de pedidos supera sistemáticamente las ventas",
                        ];
                        continue;
                    }
                }
            }
            foreach ($lista as $inc) {
                $resultado_b[] = $inc;
            }
        }

        return array_merge($consolidadas, $resultado_b);
    }

    // ── Métodos algorítmicos puros (sin BD) — públicos para tests unitarios ──

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
        } elseif ($ratio >= $umbral_severo) {
            return [
                'categoria'     => 'severo',
                'posible_causa' => 'Sobrestock significativo: el stock previo superaba ampliamente la cantidad recibida',
            ];
        } else {
            return [
                'categoria'     => 'elevado',
                'posible_causa' => 'Sobrestock moderado: el stock disponible superaba el umbral establecido antes de recibir la entrada',
            ];
        }
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
        $n_total = count($lista);
        if ($n_total < 3) return false;

        $n_acum = 0;
        foreach ($lista as $inc) {
            $vtr = $inc['ventas_entre_recepciones'];
            $cob = $inc['cobertura_dias'];
            if (($vtr !== null && $vtr < 1) || ($vtr === null && $cob === null)) {
                $n_acum++;
            }
        }
        return ($n_acum / $n_total) >= $umbral_fraccion;
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
        $cobs = array_values(array_filter($coberturas, fn($c) => $c !== null));
        if (count($cobs) < 3) return false;

        $cob_ini = (int)$cobs[0];
        $cob_fin = (int)end($cobs);

        return $cob_ini > 0 && ($cob_fin / $cob_ini) >= $ratio_min && $cob_fin >= $cob_min_fin;
    }
}
