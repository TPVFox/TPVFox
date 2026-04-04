<?php

/**
 * PosstockC9Detector — Detector de merma por backstaging LIFO ponderado (Caso C9).
 *
 * Extraído de ClasePosstock como parte de la Fase 3 de refactorización.
 *
 * Métodos públicos:
 *   calcularLotesC9(...)         — construye los lotes inter-recepción
 *   backstagingExponencial(...)  — redistribuye déficits con pesos exponenciales
 *   clasificarMermaC9(...)       — clasifica merma por lote y calcula severidad/confianza
 *   detectar(...)                — orquestador C9 completo
 */

class PosstockC9Detector
{
    public function __construct(
        private mysqli $db,
        private PosstockQueryRepository $repo
    ) {}

    // ══════════════════════════════════════════════════════════════════════════
    // Algoritmos internos (públicos para testabilidad directa)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * C9 — Construye los lotes (intervalos inter-recepción) para un artículo.
     *
     * Lote 0: desde fi_mov hasta la primera recepción -1 día; E_0 = stock_base.
     * Lotes 1..n-1: intervalos completos entre recepciones consecutivas del periodo.
     * Último lote (n-1 en periodo): incluido solo si hay primera recepción post-periodo
     *   que actúe como cierre; su V_t se limita a ff_mov.
     *
     * @param  array  $recs_art      [{fecha, cantidad, es_post_periodo}] para el artículo
     * @param  array  $timeline_art  [{fecha, day_delta}] para el artículo (timeline limpio)
     * @param  string $fi_mov        Inicio del periodo analizado
     * @param  string $ff_mov        Fin del periodo analizado
     * @param  array  $devs_art      Devoluciones ordinarias [{fecha, devolucion}]
     * @return array  [{idx, fecha_ini, fecha_fin, E_t, V_t, S_t, dias, es_lote0, v_t_parcial}]
     */
    public function calcularLotesC9(
        array  $recs_art,
        array  $timeline_art,
        string $fi_mov,
        string $ff_mov,
        array  $devs_art = []   // devoluciones ordinarias [{fecha, devolucion}]
    ): array {
        // Separar recepciones en-periodo vs post-periodo
        $rec_periodo  = [];
        $primera_post = null;
        foreach ($recs_art as $fila) {
            if ($fila['es_post_periodo']) {
                if ($primera_post === null) $primera_post = $fila;
            } else {
                $rec_periodo[] = $fila;
            }
        }
        if (empty($rec_periodo)) return [];

        // Indexar timeline por fecha para acceso O(1)
        $delta_by_fecha = [];
        foreach ($timeline_art as $t) {
            $delta_by_fecha[$t['fecha']] = (float)$t['day_delta'];
        }
        ksort($delta_by_fecha);

        // Indexar devoluciones ordinarias por fecha
        $dev_by_fecha = [];
        foreach ($devs_art as $d) {
            $dev_by_fecha[$d['fecha']] = ($dev_by_fecha[$d['fecha']] ?? 0.0) + (float)$d['devolucion'];
        }

        // Suma de salidas entre dos fechas inclusive (day_delta siempre positivo)
        $sum_salidas = static function (array $deltas, string $fechaInicio, string $fechaFin): float {
            $total = 0.0;
            foreach ($deltas as $fecha => $delta) {
                if ($fecha >= $fechaInicio && $fecha <= $fechaFin) {
                    $total += $delta;
                }
            }
            return $total;
        };

        // Suma de devoluciones (ABS) en intervalo inclusive
        $sum_devs = static function (array $devs, string $fechaInicio, string $fechaFin): float {
            $total = 0.0;
            foreach ($devs as $fecha => $dev) {
                if ($fecha >= $fechaInicio && $fecha <= $fechaFin) {
                    $total += $dev;
                }
            }
            return $total;
        };

        // El análisis empieza en la primera recepción del periodo.
        // Las ventas anteriores a esa fecha son consumo del stock heredado y no
        // pertenecen a ningún lote analizable — se excluyen del modelo.
        // El stock inicial (rebobinado) no entra en los cálculos de merma:
        // cualquier merma de ese stock ya ocurrió en periodos anteriores.

        $lotes = [];
        $n     = count($rec_periodo);

        // ── Lotes 0..n-1 del periodo: intervalos entre recepciones ─────────
        for ($i = 0; $i < $n - 1; $i++) {
            $fi_lot   = $rec_periodo[$i]['fecha'];
            $ff_lot   = date('Y-m-d', strtotime($rec_periodo[$i + 1]['fecha'] . ' -1 day'));
            $e_t_bruto = (float)$rec_periodo[$i]['cantidad'];
            $dev_t    = $sum_devs($dev_by_fecha, $fi_lot, $ff_lot);
            $e_t      = max(0.0, $e_t_bruto - $dev_t);
            $v_t      = $sum_salidas($delta_by_fecha, $fi_lot, $ff_lot);
            // Cruce de ciclo: devolución cubre ≥90% de la recepción y sin ventas significativas
            $cruce_ciclo = ($e_t_bruto > 0.0 && $dev_t / $e_t_bruto >= 0.9 && $v_t < 0.001);
            $lotes[] = [
                'idx'          => $i,
                'fecha_ini'    => $fi_lot,
                'fecha_fin'    => $ff_lot,
                'E_t'          => $e_t,
                'E_t_bruto'    => $e_t_bruto,
                'dev_t'        => round($dev_t, 4),
                'dev_carryback' => 0.0,
                'V_t'          => $v_t,
                'S_t'          => $e_t - $v_t,
                'dias'         => (int)(strtotime($ff_lot) - strtotime($fi_lot)) / 86400 + 1,
                'cruce_ciclo'  => $cruce_ciclo,
                'v_t_parcial'  => false,
            ];
        }

        // ── Último lote del periodo: siempre incluir ────────────────────────
        // Con primera_post: V_t hasta primera_post-1 (ciclo cerrado, v_t_parcial=false).
        // Sin primera_post: V_t hasta ff_mov (ciclo abierto, v_t_parcial=true).
        //   El sobrante positivo del ciclo abierto es carryover al siguiente periodo,
        //   no merma; se excluye de merma_total. Los déficits (S_t<0) sí participan
        //   en backstaging hacia lotes anteriores del mismo periodo.
        {
            $i         = $n - 1;
            $fi_lot    = $rec_periodo[$i]['fecha'];
            $e_t_bruto = (float)$rec_periodo[$i]['cantidad'];

            if ($primera_post !== null) {
                $ff_ciclo         = date('Y-m-d', strtotime($primera_post['fecha'] . ' -1 day'));
                $dev_t            = $sum_devs($dev_by_fecha, $fi_lot, $ff_ciclo);
                $v_t              = $sum_salidas($delta_by_fecha, $fi_lot, $ff_ciclo);
                $v_t_parcial      = false;
                $es_ultimo_abierto = false;
            } else {
                $dev_t            = $sum_devs($dev_by_fecha, $fi_lot, $ff_mov);
                $v_t              = $sum_salidas($delta_by_fecha, $fi_lot, $ff_mov);
                $v_t_parcial      = true;
                $es_ultimo_abierto = true;
            }
            $e_t         = max(0.0, $e_t_bruto - $dev_t);
            $cruce_ciclo = ($e_t_bruto > 0.0 && $dev_t / $e_t_bruto >= 0.9 && $v_t < 0.001);
            $lotes[] = [
                'idx'              => $i,
                'fecha_ini'        => $fi_lot,
                'fecha_fin'        => $ff_mov,
                'E_t'              => $e_t,
                'E_t_bruto'        => $e_t_bruto,
                'dev_t'            => round($dev_t, 4),
                'dev_carryback'    => 0.0,
                'V_t'              => $v_t,
                'S_t'              => $e_t - $v_t,
                'dias'             => (int)(strtotime($ff_mov) - strtotime($fi_lot)) / 86400 + 1,
                'es_ultimo_abierto' => $es_ultimo_abierto,
                'cruce_ciclo'      => $cruce_ciclo,
                'v_t_parcial'      => $v_t_parcial,
            ];
        }

        // ── Devolucion carryback: propagar exceso de devolución al lote anterior ─────
        // Cuando dev_t > E_t_bruto (devolución posterior mayor que la entrada del mismo
        // lote), el exceso de devolución se traslada al lote inmediatamente anterior para
        // reajustar su E_t. Esto cubre el escenario de albaranes erróneos que se registran
        // en un periodo y se devuelven creando un lote nuevo:
        //   ej: entrada 04-26 (+5.2) + entrada 04-30 (+5.5) + devolución 05-02 (−11.2).
        //   Sin carryback: lote 04-30 queda E_t=0, exceso 5.7 kg se pierde → lotes 04-25
        //   y 04-26 mantienen S_t inflados (~9.6 kg) que aparecen como merma falsa.
        //   Con carryback: exceso 5.7 se traslada a lote 04-26 → exceso 0.5 a lote 04-25
        //   → E_t_04-25 se reduce 0.5 → S_t_04-25 baja a 3.945, lote 04-26 queda E_t=0.
        for ($i = count($lotes) - 1; $i > 0; $i--) {
            $exceso = $lotes[$i]['dev_t'] - $lotes[$i]['E_t_bruto'];
            if ($exceso <= 0.001) continue;
            // Acumular carryback en el lote anterior
            $lotes[$i - 1]['dev_t']       += $exceso;
            $lotes[$i - 1]['dev_carryback'] = ($lotes[$i - 1]['dev_carryback'] ?? 0.0) + $exceso;
            // Recalcular E_t y S_t del lote anterior
            $e_nuevo = max(0.0, $lotes[$i - 1]['E_t_bruto'] - $lotes[$i - 1]['dev_t']);
            $lotes[$i - 1]['E_t']  = $e_nuevo;
            $lotes[$i - 1]['S_t']  = $e_nuevo - $lotes[$i - 1]['V_t'];
            // Recalcular cruce_ciclo del lote anterior
            $lotes[$i - 1]['cruce_ciclo'] = (
                $lotes[$i - 1]['E_t_bruto'] > 0.0 &&
                $lotes[$i - 1]['dev_t'] / $lotes[$i - 1]['E_t_bruto'] >= 0.9 &&
                $lotes[$i - 1]['V_t'] < 0.001
            );
        }

        // ── Merge lotes vacíos: E_t=0 + V_t≈0 → extender lote anterior ─────────────
        // Tras el carryback, un lote puede quedar con E_t=0 y sin ventas (V_t≈0):
        // es una recepción errónea completamente anulada por devolución posterior.
        // Si se deja como lote separado, el backstaging lo trata como "sumidero":
        // absorbe parte del déficit del siguiente lote, pero su S_t negativo resultante
        // no se procesa (no estaba en lotes_deficit al inicio) → la merma de lotes
        // anteriores queda sobreestimada. Eliminarlo como frontera de lote y extender
        // el anterior corrige el flujo del backstaging.
        $lotes_merged = [];
        foreach ($lotes as $lot) {
            if (
                !empty($lotes_merged)
                && $lot['E_t'] < 0.001
                && $lot['V_t'] < 0.001
                && !($lot['es_ultimo_abierto'] ?? false)
            ) {
                // Extender el lote anterior en fecha y días; E_t/V_t/S_t no cambian
                $prev = &$lotes_merged[count($lotes_merged) - 1];
                $prev['fecha_fin'] = $lot['fecha_fin'];
                $prev['dias']     += $lot['dias'];
            } else {
                $lotes_merged[] = $lot;
            }
        }
        unset($prev);
        foreach ($lotes_merged as $k => &$linea) {
            $linea['idx'] = $k;
        }
        unset($linea);
        $lotes = $lotes_merged;

        return $lotes;
    }

    /**
     * C9 — LIFO inverso con backstaging exponencial ponderado por distancia temporal.
     *
     * Para cada lote con S_t < 0 (sobreventa), redistribuye el déficit hacia los k
     * lotes anteriores con pesos exponenciales w_i = exp(-beta * delta_t_i), bloqueando
     * lotes cuyo intervalo supera mu + lambda*sigma de los intervalos del periodo.
     *
     * @param  array  $lotes   Salida de calcularLotesC9 (S_t mutable)
     * @param  int    $k       Profundidad de backstaging (nº de lotes previos)
     * @param  float  $beta    Tasa de decaimiento exponencial
     * @param  float  $lambda  Multiplicador para umbral de continuidad temporal
     * @return array  ['lotes' => array, 'trace' => array, 'n_deficit' => int]
     */
    public function backstagingExponencial(
        array $lotes,
        int   $k,
        float $beta,
        float $lambda
    ): array {
        $n = count($lotes);
        if ($n === 0) return ['lotes' => [], 'trace' => [], 'n_deficit' => 0];

        // Intervalos entre inicios de lotes consecutivos (días)
        $deltas = [];
        for ($i = 1; $i < $n; $i++) {
            $deltas[$i] = (int)((strtotime($lotes[$i]['fecha_ini']) - strtotime($lotes[$i - 1]['fecha_ini'])) / 86400);
        }

        $trace     = [];
        $n_deficit = 0;

        // Pre-computar qué lotes tienen déficit inicial (antes de cualquier redistribución)
        $lotes_deficit = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            if ($lotes[$i]['S_t'] < 0.0) $lotes_deficit[] = $i;
        }

        // Helper: estadísticas de una ventana de intervalos (mu, sigma)
        $stats_ventana = static function (array $deltas, int $t, int $k): array {
            $win_ini = max(1, $t - $k - 1);
            $win_fin = $t; // deltas[$t] = intervalo entre lote t-1 y t
            $window  = [];
            for ($i = $win_ini; $i <= $win_fin; $i++) {
                if (isset($deltas[$i])) $window[] = $deltas[$i];
            }
            if (empty($window)) return ['mu' => PHP_INT_MAX, 'sigma' => 0.0];
            $mu  = array_sum($window) / count($window);
            $sq  = array_map(fn($d) => ($d - $mu) ** 2, $window);
            $sig = count($window) > 1 ? sqrt(array_sum($sq) / count($window)) : 0.0;
            return ['mu' => $mu, 'sigma' => $sig];
        };

        // LIFO inverso: solo procesar lotes con déficit original (no cascadas)
        foreach ($lotes_deficit as $t) {
            if ($lotes[$t]['S_t'] >= 0.0) continue; // ya neutralizado por redistribución anterior

            $deficit = abs($lotes[$t]['S_t']);
            $n_deficit++;

            // Threshold local: mu/sigma de la ventana de k+1 intervalos antes del lote t
            $st = $stats_ventana($deltas, $t, $k);
            $threshold_local = $st['mu'] + $lambda * $st['sigma'];

            // Calcular pesos para los k lotes anteriores
            $pesos    = [];
            $sum_w    = 0.0;
            for ($j = 1; $j <= $k; $j++) {
                $prev = $t - $j;
                if ($prev < 0) break;
                // Restricción temporal: bloquear si la distancia total al lote candidato
                // supera el umbral estadístico local de continuidad
                $dist         = (int)((strtotime($lotes[$t]['fecha_ini']) - strtotime($lotes[$prev]['fecha_ini'])) / 86400);
                if ($dist > $threshold_local) continue; // bloqueado
                $w            = exp(-$beta * $dist);
                $pesos[$prev] = $w;
                $sum_w       += $w;
            }

            if ($sum_w <= 0.0) {
                // Déficit no redistribuible: merma local confirmada (no hay inventario previo).
                // Se registra en merma_bloqueada para que clasificarMermaC9 la incluya en merma_total.
                $lotes[$t]['merma_bloqueada'] = round($deficit, 4);
                $lotes[$t]['S_t'] = 0.0;
                $trace[] = ['lote_origen' => $t, 'deficit' => round($deficit, 4), 'bloqueado' => true, 'lotes_destino' => []];
                continue;
            }

            // Redistribuir déficit con pesos normalizados
            $destinos = [];
            foreach ($pesos as $prev => $w) {
                $alpha              = $w / $sum_w;
                $redistrib          = $alpha * $deficit;
                $lotes[$prev]['S_t'] -= $redistrib;
                $destinos[]         = ['idx' => $prev, 'alpha' => round($alpha, 4), 'cantidad' => round($redistrib, 4)];
            }
            $lotes[$t]['S_t'] = 0.0;
            $trace[] = ['lote_origen' => $t, 'deficit' => round($deficit, 4), 'bloqueado' => false, 'lotes_destino' => $destinos];
        }

        return ['lotes' => $lotes, 'trace' => $trace, 'n_deficit' => $n_deficit];
    }

    /**
     * C9 — Clasifica merma por lote, calcula severidad/confianza y valida conservación.
     *
     * @param  array  $lotes              Lotes post-backstaging
     * @param  float  $stock_final        Stock contable al ff_mov (_queryStockRebobinado)
     * @param  float  $stock_at_first_rec Stock al inicio de la primera recepción del periodo
     * @param  string $tipo_fisico        'peso' o 'unidad'
     * @param  float  $epsilon            Tolerancia conservación de masa (kg)
     * @param  float  $merma_declarada    merma_prov_decl + merma_cli_decl: salidas declaradas
     *                                    que no están en V_t del timeline → ajuste de conservación
     * @return array  Métricas de merma + detalle por lote
     */
    public function clasificarMermaC9(
        array  $lotes,
        float  $stock_final,
        float  $stock_at_first_rec,
        string $tipo_fisico,
        float  $epsilon,
        float  $merma_declarada = 0.0
    ): array {
        $merma_total       = 0.0;
        $merma_carryover   = 0.0;  // S_t positivo de lotes inciertos al final (horquilla superior)
        $deficit_bloqueado = 0.0;  // Déficits no redistribuibles (sobreventa): NOT merma.
        // Indica albarán faltante, stock sin regularizar o cruce pendiente.
        $total_E           = 0.0;  // E_t solo de lotes cerrados (base del pct_merma)
        $total_E_all       = 0.0;  // E_t de todos los lotes (para pct de la horquilla)
        $n_merma           = 0;
        $n_inciertos       = 0;
        $detalle           = [];

        foreach ($lotes as $lote) {
            // es_lote_incierto: marcado por detectar() según umbral de continuidad vivo.
            // Incluye siempre es_ultimo_abierto. Fallback a es_ultimo_abierto para compatibilidad
            // con tests unitarios que no pasan por detectar().
            $es_incierto        = ($lote['es_lote_incierto'] ?? false)
                || ($lote['es_ultimo_abierto'] ?? false);
            $merma_t           = max($lote['S_t'], 0.0);
            // merma_bloqueada: déficit no redistribuible (sobreventa sin lotes previos).
            // NO es merma física — indica albarán faltante, stock no regularizado del
            // periodo anterior o cruce pendiente. Se acumula en deficit_bloqueado separado.
            $merma_bloqueada_t = $lote['merma_bloqueada'] ?? 0.0;
            $total_E_all      += $lote['E_t'];
            // Acumular déficit bloqueado independientemente de si el lote es incierto o no
            $deficit_bloqueado += $merma_bloqueada_t;
            // Lotes inciertos: carryover al siguiente periodo — sobrante no es merma confirmada.
            if (!$es_incierto) {
                $merma_total += $merma_t;   // ← sin merma_bloqueada_t: déficit ≠ merma
                $total_E     += $lote['E_t'];
                if ($merma_t > 0.0) $n_merma++;
            } else {
                $n_inciertos++;
                $merma_carryover += $merma_t;  // ← sin merma_bloqueada_t
            }
            $detalle[] = [
                'idx'              => $lote['idx'],
                'fecha_ini'        => $lote['fecha_ini'],
                'fecha_fin'        => $lote['fecha_fin'],
                'E_t'              => round($lote['E_t'], 3),
                'V_t'              => round($lote['V_t'], 3),
                'S_t'              => round($lote['S_t'], 3),
                'merma_t'          => round($merma_t, 3),
                'deficit_bloqueado' => round($merma_bloqueada_t, 3),
                'v_t_parcial'      => $lote['v_t_parcial'] ?? false,
                'es_lote_incierto' => $es_incierto,
            ];
        }

        // Conservación de masa ajustada:
        //   sum(S_t_after) + merma_bloqueada_total − merma_declarada ≈ Sf − Si
        //
        // Dos correcciones necesarias respecto al Δcons ingenuo:
        //
        // 1. merma_bloqueada: déficits de lote 0 sin lotes previos que los absorban.
        //    Tras el backstaging su S_t pasa a 0 (se mueve a 'merma_bloqueada'), por lo que
        //    sum(S_t_after) sube artificialmente en ese importe. Sumarlo restaura la masa.
        //
        // 2. merma_declarada (REGULARIZACION, merma especial): salidas físicas de stock
        //    registradas en albaranes especiales que NO aparecen en el timeline V_t.
        //    El modelo las detecta como S_t > 0 en el lote correspondiente (la entrada
        //    no se consumió por ventas regulares, sino por la regularización), por lo que
        //    sum(S_t_before) = Sf − Si + merma_declarada.  Restándola el Δcons cae a ~0
        //    cuando la única "anomalía" son regularizaciones documentadas.
        //
        // stock_final < 0 NO se clampea: el Δcons resultante será grande y delta_critico=true
        // lo captura, degradando la confianza a 'posible'.
        // Corrección 1 — merma_bloqueada:
        //   Cuando un déficit de lote 0 no tiene lotes previos que lo absorban (BUG-H fix),
        //   S_t pasa de −D a 0 y se registra en 'merma_bloqueada'. Eso infla sum(S_t_after)
        //   en D. Restarlo restaura la conservación.
        // Corrección 2 — merma_declarada:
        //   Las regularizaciones (REGULARIZACION FRUTERIA, etc.) son salidas físicas de stock
        //   que NO pasan por el timeline V_t. Aparecen como S_t positivo en el lote correspondiente
        //   (la entrada no se consumió por ventas regulares, sino por la declaración de merma).
        //   Esto infla sum(S_t) en merma_declarada. Restándolo el Δcons cae a ~0 cuando la
        //   única "anomalía" son regularizaciones perfectamente documentadas.
        $merma_bloqueada_total = array_sum(array_column($lotes, 'merma_bloqueada'));
        $sum_s              = array_sum(array_column($lotes, 'S_t'));
        $sum_s_ajustado     = $sum_s - $merma_bloqueada_total - $merma_declarada;
        $conservation_delta = abs($sum_s_ajustado - ($stock_final - $stock_at_first_rec));
        $conservation_ok    = $conservation_delta <= $epsilon;

        $pct = ($total_E > 0.0) ? ($merma_total / $total_E * 100.0) : 0.0;
        $n   = count($lotes);

        // Severidad base: calculada con merma confirmada (lotes cerrados) y su pct.
        // Severidad horquilla: calculada con merma_total + merma_carryover y pct sobre total_E_all.
        // Se usa el mayor nivel entre ambas para no subestimar mermas estacionales cuyo
        // ciclo no cerró todavía (e.g., merma concentrada en los últimos meses del periodo).
        $merma_max  = $merma_total + $merma_carryover;
        $pct_max    = ($total_E_all > 0.0) ? ($merma_max / $total_E_all * 100.0) : 0.0;

        $calcularSeveridad = static function (float $merma, float $porcentajeMerma, string $tipoFisico): int {
            if ($tipoFisico === 'peso') {
                if ($merma >= 15.0 && $porcentajeMerma >= 15.0) return 5;
                elseif ($merma >=  8.0 && $porcentajeMerma >= 10.0) return 4;
                elseif ($merma >=  3.0 && $porcentajeMerma >=  5.0) return 3;
                elseif ($merma >=  1.0 && $porcentajeMerma >=  2.0) return 2;
                else                                return 1;
            } else {
                if ($merma >= 20.0 && $porcentajeMerma >= 15.0) return 5;
                elseif ($merma >= 10.0 && $porcentajeMerma >= 10.0) return 4;
                elseif ($merma >=  5.0 && $porcentajeMerma >=  5.0) return 3;
                elseif ($merma >=  2.0 && $porcentajeMerma >=  2.0) return 2;
                else                                return 1;
            }
        };
        $severidadBase = $calcularSeveridad($merma_total, $pct, $tipo_fisico);
        $severidadMaximaHorquilla = $calcularSeveridad($merma_max, $pct_max, $tipo_fisico);
        $sev = max($severidadBase, $severidadMaximaHorquilla);

        $sev_labels = [1 => 'BAJA', 2 => 'BAJA', 3 => 'MEDIA', 4 => 'ALTA', 5 => 'CRITICA'];

        // Confianza
        // delta_critico: la confianza cae a 'posible' cuando:
        //   a) Δcons ajustado > 5×epsilon → datos base inconsistentes (rebobinado incorrecto)
        //   b) stock_final < 0 → físicamente imposible, indica rebobinado erróneo
        $delta_critico = $conservation_delta > $epsilon * 5.0 || $stock_final < 0.0;
        if (!$delta_critico && $conservation_ok && $n >= 5) $confianza = 'alta';
        elseif (!$delta_critico && ($conservation_ok || $n >= 3)) $confianza = 'media';
        else                                                        $confianza = 'posible';

        return [
            'merma_total'        => round($merma_total, 3),
            'merma_carryover'    => round($merma_carryover, 3),  // carryover lotes inciertos (horquilla superior)
            'deficit_bloqueado'  => round($deficit_bloqueado, 3), // sobreventa no redistribuible: albarán faltante / stock no regularizado
            'n_lotes_inciertos'  => $n_inciertos,
            'pct_merma'          => round($pct, 2),
            'n_merma'            => $n_merma,
            'detalle_lotes'      => $detalle,
            'conservation_ok'    => $conservation_ok,
            'conservation_delta' => round($conservation_delta, 4),
            'severidad'          => $sev,
            'severidad_label'    => $sev_labels[$sev],
            'confianza'          => $confianza,
            'total_E'            => round($total_E, 3),
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Orquestador principal
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * C9 — Merma por backstaging LIFO inverso con ponderación exponencial temporal.
     *
     * Orquesta: Q1 (recepciones sin proveedores especiales) → Q2 (timeline limpio) →
     * Q5a (prov especiales: cruce vs merma declarada) → Q5b (cli especiales: merma
     * declarada) → lotes → backstaging → clasificación.
     *
     * Solo aplica a artículos con tipo_fisico IN ('unidad','peso').
     *
     * @return array  Incidencias C9 o ['error' => ...]
     */
    public function detectar(
        string $fi_mov,
        string $ff_mov,
        string $fi_stock,
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter        = [],
        array  $stock_base_cache  = [],
        int    $c9_k              = 4,
        float  $c9_beta           = 0.15,
        float  $c9_lambda         = 1.5,
        float  $c9_epsilon        = 1.0,
        int    $c9_min_rec        = 3,
        float  $c9_umbral_unidad  = 2.0,
        float  $c9_umbral_peso    = 1.0,
        int    $c9_dias_post      = 60
    ): array {
        $fechaInicioEsc   = $this->db->real_escape_string($fi_mov);
        $fechaFinEsc   = $this->db->real_escape_string($ff_mov);
        $filtroFamiliasSql   = $this->repo->familiaWhere($familias_incluir, $familias_excluir);
        $filtroArticulosSql   = $this->repo->idsWhere($ids_filter);
        // ── Paso 1: recepciones (Q1) ────────────────────────────────────────
        $filasRecepciones = $this->repo->queryRecepcionesC9($fechaInicioEsc, $ff_mov, $filtroFamiliasSql, $filtroArticulosSql, $c9_dias_post);
        if (isset($filasRecepciones['error'])) return $filasRecepciones;
        if (empty($filasRecepciones)) return [];

        // Agrupar por artículo; filtrar por n_recepciones mínimo en-periodo
        $recepciones_map = [];
        foreach ($filasRecepciones as $fila) {
            $aid = (int)$fila['idArticulo'];
            $recepciones_map[$aid][] = [
                'fecha'           => $fila['fecha'],
                'cantidad'        => (float)$fila['cantidad'],
                'es_post_periodo' => (bool)$fila['es_post_periodo'],
            ];
        }
        $ids_candidatos = [];
        foreach ($recepciones_map as $aid => $recs) {
            $n_periodo = count(array_filter($recs, fn($fila) => !$fila['es_post_periodo']));
            if ($n_periodo >= $c9_min_rec) $ids_candidatos[] = $aid;
        }
        if (empty($ids_candidatos)) return [];

        $idsArticulosCsv = implode(',', array_map('intval', $ids_candidatos));

        // ── Paso 2: filtrar solo artículos físicos ──────────────────────────
        $sentenciaArticulos = $this->db->query(
            "SELECT idArticulo, tipo FROM articulos
              WHERE idArticulo IN ($idsArticulosCsv)"
        );
        if (!$sentenciaArticulos) return ['error' => 'C9 tipos: ' . $this->db->error];
        $tipos_map = [];
        while ($fila = $sentenciaArticulos->fetch_assoc()) $tipos_map[(int)$fila['idArticulo']] = $fila['tipo'];
        $sentenciaArticulos->free();
        $ids_candidatos = array_values(array_filter($ids_candidatos, fn($id) => isset($tipos_map[$id])));
        if (empty($ids_candidatos)) return [];
        $idsArticulosCsv = implode(',', array_map('intval', $ids_candidatos));

        // ── Paso 1b: devoluciones ordinarias (nunidades < 0, proveedor no especial) ──
        $ff_post_dev = $this->db->real_escape_string(
            date('Y-m-d', strtotime("$ff_mov +$c9_dias_post days"))
        );
        $filasDevolucionesProv = $this->repo->queryDevolucionesProvC9($fechaInicioEsc, $ff_post_dev, $idsArticulosCsv);
        if (isset($filasDevolucionesProv['error'])) return $filasDevolucionesProv;
        $devs_map = [];
        foreach ($filasDevolucionesProv as $fila) {
            $devs_map[(int)$fila['idArticulo']][] = ['fecha' => $fila['fecha'], 'devolucion' => (float)$fila['devolucion']];
        }

        // ── Paso 3: timeline limpio (Q2) — extendido hasta ff_post para capturar
        //    ventas post-periodo del último lote (hasta primera_post - 1) ────────
        $ff_post_tl = $this->db->real_escape_string(
            date('Y-m-d', strtotime("$ff_mov +$c9_dias_post days"))
        );
        $filasTimeline = $this->repo->queryTimelineC9($fechaInicioEsc, $ff_post_tl, $idsArticulosCsv);
        if (isset($filasTimeline['error'])) return $filasTimeline;
        $timeline_map = [];
        foreach ($filasTimeline as $fila) {
            $timeline_map[(int)$fila['idArticulo']][] = ['fecha' => $fila['fecha'], 'day_delta' => (float)$fila['day_delta']];
        }

        // ── Paso 4: albaranes especiales (Q5a + Q5b) ───────────────────────
        $ff_post = $this->db->real_escape_string(
            date('Y-m-d', strtotime("$ff_mov +$c9_dias_post days"))
        );
        // Los albaranes especiales (merma declarada) se acotan al periodo fi_mov..ff_mov,
        // no a ff_post: las regularizaciones post-periodo pertenecen al siguiente análisis.
        $filasAlbaranesProv = $this->repo->queryAlbaranesProvEspecialesC9($fechaInicioEsc, $fechaFinEsc, $idsArticulosCsv);
        if (isset($filasAlbaranesProv['error'])) return $filasAlbaranesProv;
        $filasAlbaranesCliEsp  = $this->repo->queryAlbaranesCliEspecialesC9($fechaInicioEsc, $fechaFinEsc, $idsArticulosCsv);
        if (isset($filasAlbaranesCliEsp['error'])) return $filasAlbaranesCliEsp;

        // Clasificar albaranes proveedor especial: cruce intra-albarán vs merma declarada
        // Paso A: separar intra-albarán cruces (signos mixtos POR ARTÍCULO) de candidatos negativos.
        // Se evalúa por artículo, no por albarán global: un albarán puede tener líneas positivas
        // para otros artículos (p.e. regularizaciones de múltiples artículos) sin que eso invalide
        // las líneas negativas del artículo analizado.
        $alb_prov = [];
        foreach ($filasAlbaranesProv as $fila) $alb_prov[(int)$fila['idAlbaran']][] = $fila;
        $candidatos_decl = []; // [aid][fecha] => monto absoluto
        foreach ($alb_prov as $lineas) {
            // Detectar signos mixtos por artículo dentro del albarán
            $signos_art = [];
            foreach ($lineas as $linea) {
                $aid = (int)$linea['idArticulo'];
                $nunidades = (float)$linea['nunidades'];
                if ($nunidades > 0) $signos_art[$aid]['pos'] = true;
                if ($nunidades < 0) $signos_art[$aid]['neg'] = true;
            }
            foreach ($lineas as $linea) {
                $aid   = (int)$linea['idArticulo'];
                $nunidades = (float)$linea['nunidades'];
                // Cruce intra-albarán: este artículo tiene signos mixtos en el mismo albarán → ignorar
                if (!empty($signos_art[$aid]['pos']) && !empty($signos_art[$aid]['neg'])) continue;
                $fecha = $linea['fecha'];
                if ($nunidades > 0) {
                    // Proveedor especial positivo = entrada normal: añadir a recepciones_map
                    // como si fuera un proveedor ordinario.
                    $ya_existe = false;
                    if (isset($recepciones_map[$aid])) {
                        foreach ($recepciones_map[$aid] as &$recepcionRef) {
                            if ($recepcionRef['fecha'] === $fecha) {
                                $recepcionRef['cantidad'] += $nunidades;
                                $ya_existe = true;
                                break;
                            }
                        }
                        unset($recepcionRef);
                    }
                    if (!$ya_existe) {
                        $recepciones_map[$aid][] = [
                            'fecha'           => $fecha,
                            'cantidad'        => $nunidades,
                            'es_post_periodo' => false,
                        ];
                        // Re-ordenar por fecha para mantener coherencia
                        usort($recepciones_map[$aid], fn($a, $b) => strcmp($a['fecha'], $b['fecha']));
                    }
                } else {
                    // Proveedor especial negativo = merma declarada o cruce (Paso B)
                    $candidatos_decl[$aid][$fecha] = ($candidatos_decl[$aid][$fecha] ?? 0.0)
                        + abs($nunidades);
                }
            }
        }

        // Paso B: detectar cruces cross-albarán.
        // Si en la misma fecha hay una recepción regular para el mismo artículo,
        // el negativo especial es una devolución del ciclo anterior al proveedor
        // que trae stock nuevo: netear E_t de la recepción, NO merma_declarada.
        $merma_prov_decl     = [];
        $merma_prov_decl_mes = []; // [aid][mes(1-12)] => kg acumulado
        foreach ($candidatos_decl as $aid => $fechas) {
            foreach ($fechas as $fecha => $monto) {
                $recs_aid = $recepciones_map[$aid] ?? [];
                $rec_idx  = null;
                foreach ($recs_aid as $idx => $rec) {
                    if ($rec['fecha'] === $fecha) {
                        $rec_idx = $idx;
                        break;
                    }
                }
                if ($rec_idx !== null) {
                    // Cross-albarán cruce: reducir E_t neto de la recepción
                    $recepciones_map[$aid][$rec_idx]['cantidad']   -= $monto;
                    $recepciones_map[$aid][$rec_idx]['cruce_cross'] = true;
                    if ($recepciones_map[$aid][$rec_idx]['cantidad'] <= 0.0) {
                        unset($recepciones_map[$aid][$rec_idx]);
                        $recepciones_map[$aid] = array_values($recepciones_map[$aid]);
                    }
                } else {
                    // Sin recepción regular ese día → merma declarada normal
                    $merma_prov_decl[$aid] = ($merma_prov_decl[$aid] ?? 0.0) + $monto;
                    $mesProvDecl = (int)substr($fecha, 5, 2);
                    $merma_prov_decl_mes[$aid][$mesProvDecl] = ($merma_prov_decl_mes[$aid][$mesProvDecl] ?? 0.0) + $monto;
                }
            }
        }
        // Clasificar albaranes cliente especial: misma lógica Paso A/B que proveedor.
        // En albaranes cliente: nunidades > 0 = salida de stock (merma/venta especial),
        //                       nunidades < 0 = entrada de stock (devolución/regularización).
        $alb_cli = [];
        foreach ($filasAlbaranesCliEsp as $fila) $alb_cli[(int)$fila['idAlbaran']][] = $fila;
        $merma_cli_decl      = [];
        $merma_cli_decl_mes  = []; // [aid][mes(1-12)] => kg acumulado
        $entradas_cli_esp = []; // [aid][fecha] => monto absoluto (nunidades < 0 sin cruce)
        foreach ($alb_cli as $lineas) {
            // Paso A: detectar signos mixtos por artículo dentro del albarán
            $signos_art = [];
            foreach ($lineas as $linea) {
                $aid   = (int)$linea['idArticulo'];
                $nunidades = (float)$linea['nunidades'];
                if ($nunidades > 0) $signos_art[$aid]['pos'] = true;
                if ($nunidades < 0) $signos_art[$aid]['neg'] = true;
            }
            foreach ($lineas as $linea) {
                $aid   = (int)$linea['idArticulo'];
                $nunidades = (float)$linea['nunidades'];
                if (!empty($signos_art[$aid]['pos']) && !empty($signos_art[$aid]['neg'])) continue;
                $fecha = $linea['fecha'];
                if ($nunidades > 0) {
                    // Salida especial → merma declarada
                    $merma_cli_decl[$aid] = ($merma_cli_decl[$aid] ?? 0.0) + $nunidades;
                    $mesCliDecl = (int)substr($fecha, 5, 2);
                    $merma_cli_decl_mes[$aid][$mesCliDecl] = ($merma_cli_decl_mes[$aid][$mesCliDecl] ?? 0.0) + $nunidades;
                } else {
                    // Entrada especial → candidato a neta V_t o entrada directa
                    $entradas_cli_esp[$aid][$fecha] = ($entradas_cli_esp[$aid][$fecha] ?? 0.0)
                        + abs($nunidades);
                }
            }
        }
        // Paso B cliente: si hay ventas regulares el mismo día en timeline, neta V_t;
        // si no, añadir como entrada a recepciones_map.
        foreach ($entradas_cli_esp as $aid => $fechas) {
            foreach ($fechas as $fecha => $monto) {
                $tl_aid = $timeline_map[$aid] ?? [];
                $tl_idx = null;
                foreach ($tl_aid as $idx => $tl) {
                    if ($tl['fecha'] === $fecha) {
                        $tl_idx = $idx;
                        break;
                    }
                }
                if ($tl_idx !== null) {
                    // Cross-albarán: neta V_t del timeline
                    $timeline_map[$aid][$tl_idx]['day_delta'] -= $monto;
                    if ($timeline_map[$aid][$tl_idx]['day_delta'] <= 0.0) {
                        unset($timeline_map[$aid][$tl_idx]);
                        $timeline_map[$aid] = array_values($timeline_map[$aid]);
                    }
                } else {
                    // Sin ventas regulares ese día.
                    //
                    // Los albaranes especiales negativos de clientes son mayoritariamente
                    // conciliaciones de un periodo ya pasado (corrección de stock, devolución
                    // tardía, ajuste de inventario). Si se tratan como nueva recepción crean
                    // un lote ficticio con E_t=monto y V_t≈0, lo que genera merma artificial.
                    //
                    // Estrategia: si ya existe una recepción ese mismo día (normal o especial),
                    // añadir al E_t de esa recepción. Si no existe, buscar la recepción más
                    // reciente anterior a $fecha y añadir ahí (conciliación del lote activo).
                    // Solo si no hay ninguna recepción previa en el periodo se añade como nueva
                    // entrada (inicio de periodo sin historial).
                    $ya_existe    = false;
                    $prev_rec_idx = null;
                    if (isset($recepciones_map[$aid])) {
                        foreach ($recepciones_map[$aid] as $ridx => &$recepcionRef) {
                            if ($recepcionRef['fecha'] === $fecha && !($recepcionRef['es_post_periodo'] ?? false)) {
                                // Recepción en la misma fecha: añadir directamente
                                $recepcionRef['cantidad'] += $monto;
                                $ya_existe = true;
                                break;
                            }
                            if ($recepcionRef['fecha'] <= $fecha && !($recepcionRef['es_post_periodo'] ?? false)) {
                                // Candidato a recepción previa (la más reciente gana)
                                if (
                                    $prev_rec_idx === null
                                    || $recepcionRef['fecha'] >= $recepciones_map[$aid][$prev_rec_idx]['fecha']
                                ) {
                                    $prev_rec_idx = $ridx;
                                }
                            }
                        }
                        unset($recepcionRef);
                    }
                    if (!$ya_existe) {
                        if ($prev_rec_idx !== null) {
                            // Conciliación: añadir al E_t del lote activo en $fecha
                            $recepciones_map[$aid][$prev_rec_idx]['cantidad'] += $monto;
                        } elseif (isset($recepciones_map[$aid])) {
                            // Sin recepción previa en el periodo: añadir como nueva entrada
                            $recepciones_map[$aid][] = [
                                'fecha'           => $fecha,
                                'cantidad'        => $monto,
                                'es_post_periodo' => false,
                            ];
                            usort($recepciones_map[$aid], fn($a, $b) => strcmp($a['fecha'], $b['fecha']));
                        }
                    }
                }
            }
        }

        // ── Paso 5: stock base (compartido con C7 en batch) ────────────────
        if (empty($stock_base_cache)) {
            $fechaInicioStockBaseEsc   = $this->db->real_escape_string($fi_stock);
            $fechaFinStockBaseEsc   = $this->db->real_escape_string($fi_mov);
            $idsStockBaseCsv  = implode(',', array_map('intval', $ids_candidatos));
            $filasStockBase = $this->repo->queryStockBase($fechaInicioStockBaseEsc, $fechaFinStockBaseEsc, $idsStockBaseCsv);
            if (isset($filasStockBase['error'])) return $filasStockBase;
            $stock_base_cache = [];
            foreach ($filasStockBase as $fila) {
                $stock_base_cache[(int)$fila['idArticulo']] = [
                    'saldo_acumulado' => (float)$fila['saldo_acumulado'],
                    'ultima_compra'   => $fila['ultima_compra'],
                    'ultima_venta'    => $fila['ultima_venta'],
                ];
            }
        }
        $cacheStockBase = $stock_base_cache;

        // ── Paso 5b: stock real al inicio del periodo (rebobinado) ──────────
        // Se usa como E_lote0 en lugar del saldo acumulado desde fi_stock.
        // _queryStockRebobinado incluye TODO el histórico (no solo desde fi_stock),
        // capturando inventario de años anteriores no reflejado en getStockBase.
        $fechaRebobinadoEsc = $this->db->real_escape_string(
            date('Y-m-d', strtotime("$fi_mov -1 day"))
        );
        $filasStockInicial = $this->repo->queryStockRebobinado($idsArticulosCsv, $fechaRebobinadoEsc);
        if (isset($filasStockInicial['error'])) return $filasStockInicial;
        $stock_inicial_map = [];
        foreach ($filasStockInicial as $fila) {
            $stock_inicial_map[(int)$fila['idArticulo']] = (float)$fila['stock_en_periodo'];
        }

        // ── Paso 6: stock rebobinado al ff_mov (ancla conservación) ────────
        $filasStockFinal = $this->repo->queryStockRebobinado($idsArticulosCsv, $fechaFinEsc);
        if (isset($filasStockFinal['error'])) return $filasStockFinal;
        $stock_final_map = [];
        foreach ($filasStockFinal as $fila) $stock_final_map[(int)$fila['idArticulo']] = (float)$fila['stock_en_periodo'];

        // ── Paso 7: loop por artículo ───────────────────────────────────────
        $incidencias = [];

        foreach ($ids_candidatos as $idArticulo) {
            $tipo_fisico  = $tipos_map[$idArticulo];
            // E_lote0: stock real al inicio del periodo (rebobinado historial completo).
            // Fallback al saldo acumulado desde fi_stock si el rebobinado no devuelve fila.
            $stock_base   = $stock_inicial_map[$idArticulo]
                ?? (float)($cacheStockBase[$idArticulo]['saldo_acumulado'] ?? 0.0);
            $stock_final  = $stock_final_map[$idArticulo] ?? 0.0;
            $timeline_art = $timeline_map[$idArticulo] ?? [];
            $recs_art     = $recepciones_map[$idArticulo] ?? [];

            $devs_art = $devs_map[$idArticulo] ?? [];
            $lotes = $this->calcularLotesC9($recs_art, $timeline_art, $fi_mov, $ff_mov, $devs_art);
            if (empty($lotes)) continue;

            // Stock en el momento de la primera recepción del periodo.
            // Las ventas entre fi_mov y esa fecha son consumo del stock heredado
            // y no forman parte de ningún lote — se descuentan del ancla inicial
            // para que la conservación de masa sea coherente con el modelo sin lote 0.
            $first_rec_date    = $lotes[0]['fecha_ini'];
            $stock_at_first_rec = $stock_base;
            if ($first_rec_date > $fi_mov) {
                $pre_end = date('Y-m-d', strtotime($first_rec_date . ' -1 day'));
                foreach ($timeline_art as $filaTimelineRef) {
                    if ($filaTimelineRef['fecha'] >= $fi_mov && $filaTimelineRef['fecha'] <= $pre_end) {
                        $stock_at_first_rec -= (float)$filaTimelineRef['day_delta'];
                    }
                }
            }

            // ── Marcar lotes inciertos al final del periodo ──────────────────
            // Un lote es incierto si su ciclo puede no haber cerrado todavía.
            // Se aplica el umbral de continuidad local (mu + lambda * sigma) en DOS casos:
            //
            //   A) Corte de DB / fin de periodo sin datos posteriores:
            //      existe al menos un lote con es_ultimo_abierto=true.
            //      Justificación: si la DB termina en ff_mov, los lotes cuya fecha_ini
            //      cae dentro del umbral de continuidad ANTES de ff_mov tuvieron su ciclo
            //      natural cortado por el límite del periodo, no por una recepción real.
            //      Se aplica siempre, incluso para análisis históricos.
            //
            //   B) Análisis "en vivo" (hoy dentro de la ventana dias_post):
            //      la primera recepción que cerraría los últimos lotes aún no ha llegado.
            //      Se aplica aunque todos los lotes parezcan cerrados.
            $hoy_str  = date('Y-m-d');
            $ff_post_continuidad = date('Y-m-d', strtotime("$ff_mov +$c9_dias_post days"));
            $es_vivo         = ($hoy_str <= $ff_post_continuidad);
            $hay_abierto     = !empty(array_filter($lotes, fn($linea) => !empty($linea['es_ultimo_abierto'])));
            $aplicar_umbral  = $es_vivo || $hay_abierto;

            if ($aplicar_umbral && count($lotes) >= 2) {
                $n_lots = count($lotes);
                // Intervalos inter-lote (días entre fechas_ini consecutivas)
                $intervals = [];
                for ($i = 1; $i < $n_lots; $i++) {
                    $intervals[] = (int)round(
                        (strtotime($lotes[$i]['fecha_ini']) - strtotime($lotes[$i - 1]['fecha_ini'])) / 86400
                    );
                }
                // Ventana local: últimos min(k+1, n-1) intervalos
                $ventana = array_slice($intervals, -min($c9_k + 1, count($intervals)));
                $n_v     = count($ventana);
                $mu_v    = $n_v > 0 ? array_sum($ventana) / $n_v : 0.0;
                $var_v   = 0.0;
                foreach ($ventana as $d) $var_v += ($d - $mu_v) ** 2;
                $sigma_v         = $n_v > 1 ? sqrt($var_v / ($n_v - 1)) : 0.0;
                $umbral_cont     = max(1.0, $mu_v + $c9_lambda * $sigma_v);
                $ff_ts           = strtotime($ff_mov);
                // Recorrer desde el último lote hacia atrás
                for ($i = $n_lots - 1; $i >= 0; $i--) {
                    $gap = ($ff_ts - strtotime($lotes[$i]['fecha_ini'])) / 86400;
                    if ($gap <= $umbral_cont) {
                        $lotes[$i]['es_lote_incierto'] = true;
                    } else {
                        break; // ya fuera del umbral de continuidad
                    }
                }
            }
            // Propagar: es_lote_incierto incluye siempre es_ultimo_abierto
            foreach ($lotes as &$loteRef) {
                if (!isset($loteRef['es_lote_incierto'])) {
                    $loteRef['es_lote_incierto'] = !empty($loteRef['es_ultimo_abierto']);
                }
            }
            unset($loteRef);

            $resultado = $this->backstagingExponencial($lotes, $c9_k, $c9_beta, $c9_lambda);

            $umbral     = ($tipo_fisico === 'peso') ? $c9_umbral_peso : $c9_umbral_unidad;
            $merma_decl = ($merma_prov_decl[$idArticulo] ?? 0.0) + ($merma_cli_decl[$idArticulo] ?? 0.0);
            // Construir desglose mensual de la merma declarada (proveedor + cliente especiales)
            $mermaDeclaradaPorMes = [];
            foreach (($merma_prov_decl_mes[$idArticulo] ?? []) as $mesProvD => $valorProvDecl) {
                $mermaDeclaradaPorMes[$mesProvD] = ($mermaDeclaradaPorMes[$mesProvD] ?? 0.0) + $valorProvDecl;
            }
            foreach (($merma_cli_decl_mes[$idArticulo] ?? []) as $mesCliD => $valorCliDecl) {
                $mermaDeclaradaPorMes[$mesCliD] = ($mermaDeclaradaPorMes[$mesCliD] ?? 0.0) + $valorCliDecl;
            }
            ksort($mermaDeclaradaPorMes);
            $clasif = $this->clasificarMermaC9(
                $resultado['lotes'],
                $stock_final,
                $stock_at_first_rec,
                $tipo_fisico,
                $c9_epsilon,
                $merma_decl
            );
            if (($clasif['merma_total'] + $clasif['merma_carryover']) < $umbral) continue;
            $n_en_periodo = count(array_filter($recs_art, fn($fila) => !$fila['es_post_periodo']));

            $incidencias[] = [
                'caso'                => 'C9',
                'tipo'                => 'Merma backstaging',
                'idArticulo'          => $idArticulo,
                'merma_total_kg'      => $clasif['merma_total'],
                'merma_carryover_kg'  => $clasif['merma_carryover'],   // lotes inciertos: horquilla superior
                'deficit_bloqueado_kg' => $clasif['deficit_bloqueado'], // sobreventa no redistribuible
                'n_lotes_inciertos'   => $clasif['n_lotes_inciertos'],
                'merma_declarada_kg'  => round($merma_decl, 3),
                'merma_decl_por_mes'  => !empty($mermaDeclaradaPorMes) ? array_map(fn($v) => round($v, 3), $mermaDeclaradaPorMes) : [],
                'pct_merma'           => $clasif['pct_merma'],
                'n_lotes'             => count($resultado['lotes']),
                'n_recepciones'       => $n_en_periodo,
                'n_lotes_deficit'     => $resultado['n_deficit'],
                'n_lotes_merma'       => $clasif['n_merma'],
                'stock_final'         => round($stock_final, 3),
                'ancla_tipo'          => 'contable',
                'conservation_ok'     => $clasif['conservation_ok'],
                'conservation_delta'  => $clasif['conservation_delta'],
                'confianza'           => $clasif['confianza'],
                'severidad'           => $clasif['severidad_label'],
                'severidad_num'       => $clasif['severidad'],
                'merma_por_lote'      => $clasif['detalle_lotes'],
                'backstaging_trace'   => $resultado['trace'],
                'beta_usado'          => $c9_beta,
                'k_usado'             => $c9_k,
                'lambda_usado'        => $c9_lambda,
                'modo'                => ($tipo_fisico === 'peso') ? 'continuo' : 'discreto',
                'total_E'             => $clasif['total_E'],
            ];
        }

        // ── C9: enriquecer con proveedor habitual y coste estimado de la merma ──
        if (!empty($incidencias)) {
            $ids_c9     = array_column($incidencias, 'idArticulo');
            $idsC9Csv = implode(',', array_map('intval', $ids_c9));
            $prov_map_c9   = $this->repo->queryProveedorArticulos($idsC9Csv, $fechaInicioEsc, $fechaFinEsc);
            $precio_map_c9 = $this->repo->queryPrecioMedioCompra($idsC9Csv, $fechaInicioEsc, $fechaFinEsc);

            foreach ($incidencias as &$inc) {
                $prov = $prov_map_c9[$inc['idArticulo']] ?? null;
                $inc['prov_habitual_nombre'] = $prov['prov_habitual_nombre'] ?? null;
                $inc['prov_habitual_n']      = $prov['prov_habitual_n']      ?? null;
                $inc['prov_ultimo_nombre']   = $prov['prov_ultimo_nombre']   ?? null;
                $inc['prov_ultima_fecha']    = $prov['prov_ultima_fecha']    ?? null;
                $inc['prov_es_mismo']        = $prov['prov_es_mismo']        ?? null;
                // Coste estimado de la merma: merma_total_kg × precio medio de compra
                $precio = $precio_map_c9[$inc['idArticulo']] ?? null;
                $inc['precio_medio_compra']  = $precio;
                $inc['coste_estimado_merma'] = ($precio !== null && ($inc['merma_total_kg'] ?? 0) > 0)
                    ? round((float)$inc['merma_total_kg'] * $precio, 2)
                    : null;
            }
            unset($inc);
        }

        return $incidencias;
    }
}
