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

    // Algoritmos internos (públicos para testabilidad directa)

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

        // Lotes 0..n-1 del periodo: intervalos entre recepciones
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

        // Último lote del periodo: siempre incluir        // Con primera_post: V_t hasta primera_post-1 (ciclo cerrado, v_t_parcial=false).
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

        // Devolucion carryback: propagar exceso de devolución al lote anterior        // Cuando dev_t > E_t_bruto (devolución posterior mayor que la entrada del mismo
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

        // Merge lotes vacíos: E_t=0 + V_t≈0 → extender lote anterior        // Tras el carryback, un lote puede quedar con E_t=0 y sin ventas (V_t≈0):
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
     * Para productos discretos (no peso), la redistribución mantiene integridad de enteros,
     * redondea deficits a unidades completas y ajusta por diferencia de redondeo.
     *
     * @param  array  $lotes   Salida de calcularLotesC9 (S_t mutable)
     * @param  int    $k       Profundidad de backstaging (nº de lotes previos)
     * @param  float  $beta    Tasa de decaimiento exponencial
     * @param  float  $lambda  Multiplicador para umbral de continuidad temporal
     * @param  string $tipo_fisico  Tipo de artículo (para redondeos discretos)
     * @return array  ['lotes' => array, 'trace' => array, 'n_deficit' => int]
     */
    public function backstagingExponencial(
        array $lotes,
        int   $k,
        float $beta,
        float $lambda,
        string $tipo_fisico = 'peso'  // Para redondeos discretos en redistribución
    ): array {
        $n = count($lotes);
        if ($n === 0) return ['lotes' => [], 'trace' => [], 'n_deficit' => 0];
        $es_discreto = ($tipo_fisico !== 'peso');

        // Intervalos entre inicios de lotes consecutivos (días)
        $deltas = [];
        for ($i = 1; $i < $n; $i++) {
            $deltas[$i] = (int)((strtotime($lotes[$i]['fecha_ini']) - strtotime($lotes[$i - 1]['fecha_ini'])) / 86400);
        }

        $trace     = [];
        $n_deficit = 0;

        // Pre-computar qué lotes tienen déficit inicial (antes de cualquier redistribución)
        $lotes_deficit = $this->obtenerIndicesLotesDeficitInicial($lotes);

        // LIFO inverso: solo procesar lotes con déficit original (no cascadas)
        foreach ($lotes_deficit as $t) {
            if ($lotes[$t]['S_t'] >= 0.0) continue; // ya neutralizado por redistribución anterior

            $deficit = abs($lotes[$t]['S_t']);
            $n_deficit++;

            // Threshold local: mu/sigma de la ventana de k+1 intervalos antes del lote t
            $st = $this->calcularEstadisticasVentanaDeltas($deltas, $t, $k);
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
                // Para DISCRETOS: redondear como entero
                $deficit_redondeado = $es_discreto ? round($deficit, 0) : $deficit;
                $lotes[$t]['merma_bloqueada'] = round($deficit_redondeado, $es_discreto ? 0 : 4);
                $lotes[$t]['S_t'] = 0.0;
                $trace[] = ['lote_origen' => $t, 'deficit' => round($deficit_redondeado, $es_discreto ? 0 : 4), 'bloqueado' => true, 'lotes_destino' => []];
                continue;
            }

            // Redistribuir déficit con pesos normalizados
            // Para DISCRETOS: los deficits redistribuidos deben ser enteros (o fracciones minimales)
            $destinos = [];
            if ($es_discreto) {
                // Para discretos, hacer redistribución proporcional manteniendo integridad de enteros
                $deficit_redistribuido = 0.0;
                $prev_indices = array_keys($pesos);

                foreach ($prev_indices as $prev) {
                    $w = $pesos[$prev];
                    $alpha = $w / $sum_w;
                    $redistrib = $alpha * $deficit;

                    // Redondear pero asegurar que el total en discretos sea el déficit completo
                    $redistrib_redondeado = round($redistrib, 0);
                    $deficit_redistribuido += $redistrib_redondeado;

                    $lotes[$prev]['S_t'] -= $redistrib_redondeado;
                    $destinos[] = ['idx' => $prev, 'alpha' => round($alpha, 4), 'cantidad' => (float)$redistrib_redondeado];
                }
                // Ajustar el último destino si hay diferencia por redondeo
                if (!empty($destinos) && abs($deficit_redistribuido - $deficit) > 0.001) {
                    $delta = $deficit - $deficit_redistribuido;
                    $last_prev = end($prev_indices);
                    $lotes[$last_prev]['S_t'] -= $delta;
                    $destinos[count($destinos) - 1]['cantidad'] += $delta;
                }
            } else {
                // Para continuos, redistribución flotante normal
                foreach ($pesos as $prev => $w) {
                    $alpha              = $w / $sum_w;
                    $redistrib          = $alpha * $deficit;
                    $lotes[$prev]['S_t'] -= $redistrib;
                    $destinos[]         = ['idx' => $prev, 'alpha' => round($alpha, 4), 'cantidad' => round($redistrib, 4)];
                }
            }
            $lotes[$t]['S_t'] = 0.0;
            $trace[] = ['lote_origen' => $t, 'deficit' => round($deficit, 4), 'bloqueado' => false, 'lotes_destino' => $destinos];
        }

        return ['lotes' => $lotes, 'trace' => $trace, 'n_deficit' => $n_deficit];
    }

    private function obtenerIndicesLotesDeficitInicial(array $lotes): array
    {
        $indicesDeficit = [];
        for ($i = count($lotes) - 1; $i >= 0; $i--) {
            if ($lotes[$i]['S_t'] < 0.0) $indicesDeficit[] = $i;
        }
        return $indicesDeficit;
    }

    private function calcularEstadisticasVentanaDeltas(array $deltas, int $indiceLote, int $profundidad): array
    {
        $ventanaInicio = max(1, $indiceLote - $profundidad - 1);
        $ventanaFin    = $indiceLote; // deltas[$indiceLote] = intervalo entre lote indiceLote-1 y indiceLote
        $ventana = [];

        for ($i = $ventanaInicio; $i <= $ventanaFin; $i++) {
            if (isset($deltas[$i])) $ventana[] = $deltas[$i];
        }

        if (empty($ventana)) {
            return ['mu' => PHP_INT_MAX, 'sigma' => 0.0];
        }

        $media = array_sum($ventana) / count($ventana);
        $cuadrados = array_map(fn($delta) => ($delta - $media) ** 2, $ventana);
        $sigma = count($ventana) > 1 ? sqrt(array_sum($cuadrados) / count($ventana)) : 0.0;

        return ['mu' => $media, 'sigma' => $sigma];
    }

    /**
     * Evalúa si la cadencia de recepciones es estable para estimar fecha de albarán faltante.
     *
     * Se considera estable cuando hay suficientes intervalos y su variabilidad relativa
     * (CV) es baja.
     *
     * @param  array  $recs_art  recepciones del artículo [{fecha, cantidad, es_post_periodo}]
     * @return array  ['estable'=>bool,'intervalo_tipico_dias'=>?int,'cv'=>?float,'n_intervalos'=>int]
     */
    private function evaluarEstabilidadRecepciones(array $recs_art): array
    {
        $recs_periodo = [];
        foreach ($recs_art as $rec) {
            if (!($rec['es_post_periodo'] ?? false)) {
                $recs_periodo[] = $rec;
            }
        }
        usort($recs_periodo, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));

        if (count($recs_periodo) < 5) {
            return ['estable' => false, 'intervalo_tipico_dias' => null, 'cv' => null, 'n_intervalos' => 0];
        }

        $intervalos = [];
        for ($i = 1; $i < count($recs_periodo); $i++) {
            $dias = (int)round((strtotime($recs_periodo[$i]['fecha']) - strtotime($recs_periodo[$i - 1]['fecha'])) / 86400);
            if ($dias > 0) $intervalos[] = $dias;
        }

        $n = count($intervalos);
        if ($n < 4) {
            return ['estable' => false, 'intervalo_tipico_dias' => null, 'cv' => null, 'n_intervalos' => $n];
        }

        sort($intervalos);
        $medio = (int)round($intervalos[(int)floor(($n - 1) / 2)]);
        $media = array_sum($intervalos) / $n;
        $var = 0.0;
        foreach ($intervalos as $d) {
            $var += ($d - $media) ** 2;
        }
        $sigma = $n > 1 ? sqrt($var / ($n - 1)) : 0.0;
        $cv = $media > 0 ? $sigma / $media : INF;

        // Estabilidad operativa: dispersión moderada en intervalos de recepción
        $estable = $cv <= 0.35;

        return [
            'estable'              => $estable,
            'intervalo_tipico_dias' => max(1, $medio),
            'cv'                   => round($cv, 3),
            'n_intervalos'         => $n,
        ];
    }

    /**
     * Construye diagnóstico de déficit bloqueado para la badge "Revisar albarán".
     *
     * Incluye momentos donde aparece sobreventa no explicada y, si la recepción es
     * estable, una fecha estimada de entrada faltante.
     *
     * @param  array $lotes_post_backstaging  lotes resultantes del backstaging
     * @param  array $recs_art                recepciones del artículo
     * @return array
     */
    private function construirDiagnosticoDeficitBloqueado(array $lotes_post_backstaging, array $recs_art): array
    {
        $momentos = [];
        foreach ($lotes_post_backstaging as $lot) {
            $bloq = (float)($lot['merma_bloqueada'] ?? 0.0);
            if ($bloq <= 0.001) continue;
            $momentos[] = [
                'idx'      => (int)($lot['idx'] ?? 0),
                'fecha_ini' => $lot['fecha_ini'] ?? null,
                'fecha_fin' => $lot['fecha_fin'] ?? null,
                'kg'       => round($bloq, 3),
            ];
        }

        $patron = $this->evaluarEstabilidadRecepciones($recs_art);
        $estimaciones = [];

        if (!empty($momentos) && !empty($patron['estable']) && !empty($patron['intervalo_tipico_dias'])) {
            $intervalo = (int)$patron['intervalo_tipico_dias'];
            foreach ($momentos as $m) {
                if (empty($m['fecha_ini'])) continue;
                $ts_ini = strtotime($m['fecha_ini']);
                $ts_fin = !empty($m['fecha_fin']) ? strtotime($m['fecha_fin']) : $ts_ini;
                $ts_est = $ts_ini + ($intervalo * 86400);
                if ($ts_est > $ts_fin) $ts_est = $ts_fin;
                $estimaciones[] = [
                    'idx' => $m['idx'],
                    'fecha_estimada' => date('Y-m-d', $ts_est),
                ];
            }
        }

        return [
            'momentos'                => $momentos,
            'patron_entradas_estable' => (bool)($patron['estable'] ?? false),
            'intervalo_tipico_dias'   => $patron['intervalo_tipico_dias'] ?? null,
            'cv_intervalos'           => $patron['cv'] ?? null,
            'n_intervalos'            => (int)($patron['n_intervalos'] ?? 0),
            'estimaciones'            => $estimaciones,
        ];
    }

    /**
     * Aplica stock heredado al inicio de periodo para absorber déficit bloqueado temprano.
     *
     * Si el artículo ya tenía stock disponible en la primera recepción del periodo,
     * una parte del déficit "no redistribuible" puede explicarse por ese stock heredado
     * (fuera de los lotes C9 del periodo) y no por albarán faltante.
     *
     * @param  array &$lotes                lotes post-backstaging (mutables)
     * @param  float $stock_at_first_rec    stock disponible al inicio de la 1ª recepción
     * @return float kg absorbidos desde stock heredado
     */
    private function absorberDeficitBloqueadoConStockHeredado(array &$lotes, float $stock_at_first_rec): float
    {
        $stock_heredado = max(0.0, $stock_at_first_rec);
        if ($stock_heredado <= 0.001) return 0.0;

        $absorbido = 0.0;
        foreach ($lotes as &$lote) {
            $bloq = (float)($lote['merma_bloqueada'] ?? 0.0);
            if ($bloq <= 0.001 || $stock_heredado <= 0.001) continue;

            $usa = min($bloq, $stock_heredado);
            $lote['merma_bloqueada'] = round(max(0.0, $bloq - $usa), 4);
            $stock_heredado -= $usa;
            $absorbido      += $usa;
        }
        unset($lote);

        return round($absorbido, 4);
    }

    /**
     * C9 — Clasifica merma por lote, calcula severidad/confianza y valida conservación.
     *
     * Para productos discretos (no peso), aplica redondeo a enteros en merma y deficits,
     * epsilon más estricto (0.5 vs 1.0 kg), y degrada confianza más fácilmente.
     *
     * @param  array  $lotes              Lotes post-backstaging
     * @param  float  $stock_final        Stock contable al ff_mov (_queryStockRebobinado)
     * @param  float  $stock_at_first_rec Stock al inicio de la primera recepción del periodo
     * @param  string $tipo_fisico        Tipo de artículo (para ajustes discretos)
     * @param  float  $epsilon            Tolerancia conservación de masa (kg), puede ser ajustada por tipo
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
        $es_discreto = ($tipo_fisico !== 'peso');
        $precision = $es_discreto ? 0 : 3;  // 0 decimales para unidades, 3 para peso
        // Epsilon más estricto para discretos: 0.5 unidades vs 1.0 kg
        if ($es_discreto && $epsilon >= 1.0) {
            $epsilon = 0.5;
        }

        $merma_total       = 0.0;
        // Carryover: arrastre técnico de déficit (no merma pendiente positiva).
        $merma_carryover   = 0.0;
        // Merma pendiente: sobrante positivo en lotes inciertos aún no cerrados.
        $merma_pendiente   = 0.0;
        $deficit_bloqueado = 0.0;  // Déficits no redistribuibles (sobreventa): NOT merma.
        // Indica albarán faltante, stock sin regularizar o cruce pendiente.
        $total_E           = 0.0;  // E_t solo de lotes cerrados (base del pct_merma)
        $n_merma           = 0;
        $n_inciertos       = 0;
        $detalle           = [];

        foreach ($lotes as $lote) {
            // es_lote_incierto: marcado por detectar() según umbral de continuidad.
            // Incluye siempre es_ultimo_abierto. Fallback a es_ultimo_abierto para compatibilidad
            // con tests unitarios que no pasan por detectar().
            $es_incierto        = ($lote['es_lote_incierto'] ?? false)
                || ($lote['es_ultimo_abierto'] ?? false);
            $merma_t           = max($lote['S_t'], 0.0);
            // merma_bloqueada: déficit no redistribuible (sobreventa sin lotes previos).
            // NO es merma física — indica albarán faltante, stock no regularizado del
            // periodo anterior o cruce pendiente. Se acumula en deficit_bloqueado separado.
            $merma_bloqueada_t = $lote['merma_bloqueada'] ?? 0.0;
            // Acumular déficit bloqueado independientemente de si el lote es incierto o no
            $deficit_bloqueado += $merma_bloqueada_t;
            // Lotes inciertos:
            // - El sobrante positivo es merma pendiente (no confirmada)
            // - El déficit se mantiene como arrastre técnico (carryover)
            if (!$es_incierto) {
                $merma_total += $merma_t;   // ← sin merma_bloqueada_t: déficit ≠ merma
                $total_E     += $lote['E_t'];
                if ($merma_t > 0.0) $n_merma++;
            } else {
                $n_inciertos++;
                $merma_pendiente += $merma_t;
                $carry_deficit_t = max(0.0, -$lote['S_t']) + $merma_bloqueada_t;
                $merma_carryover += $carry_deficit_t;
            }
            $detalle[] = [
                'idx'              => $lote['idx'],
                'fecha_ini'        => $lote['fecha_ini'],
                'fecha_fin'        => $lote['fecha_fin'],
                'E_t'              => $es_discreto ? (float)intval($lote['E_t']) : round($lote['E_t'], 3),
                'V_t'              => $es_discreto ? (float)intval($lote['V_t']) : round($lote['V_t'], 3),
                'S_t'              => $es_discreto ? (float)intval($lote['S_t']) : round($lote['S_t'], 3),
                'merma_t'          => round($merma_t, $precision),
                'merma_pendiente_t' => round($es_incierto ? $merma_t : 0.0, $precision),
                'deficit_bloqueado' => round($merma_bloqueada_t, $precision),
                'v_t_parcial'      => $lote['v_t_parcial'] ?? false,
                'es_lote_incierto' => $es_incierto,
            ];
        }

        // Conservación de masa ajustada:
        //   sum(S_t_after) + merma_bloqueada_total − merma_declarada ≈ Sf − Si
        //
        // Para PRODUCTOS DISCRETOS: la tolerancia es mucho menor porque el conteo de unidades
        // debe ser exacto. Un error de 1 unidad en 100 es un 1% de error relativo.
        // Los productos discretos se redondean a enteros, así que la discrepancia aceptable
        // es menor.
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

        // Severidad: se calcula únicamente con merma confirmada.
        // La merma pendiente en lotes inciertos no eleva severidad hasta cierre de ciclo.

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
        $sev = $calcularSeveridad($merma_total, $pct, $tipo_fisico);

        // C9 no usa etiqueta CRITICA: nivel 5 se mapea a ALTA.
        $sev_labels = [1 => 'BAJA', 2 => 'BAJA', 3 => 'MEDIA', 4 => 'ALTA', 5 => 'ALTA'];

        // Confianza
        // delta_critico: la confianza cae a 'posible' cuando:
        //   a) Δcons ajustado > 5×epsilon → datos base inconsistentes (rebobinado incorrecto);
        //      para DISCRETOS, usar 3×epsilon (más estricto) porque el error debe ser menor
        //   b) stock_final < 0 → físicamente imposible, indica rebobinado erróneo
        $delta_umbral = $es_discreto ? $epsilon * 3.0 : $epsilon * 5.0;
        $delta_critico = $conservation_delta > $delta_umbral || $stock_final < 0.0;

        // Para productos DISCRETOS, ser más conservador con la confianza:
        // - Requerir conservation_ok + n >= 5 para 'alta' (igual que continuos)
        // - Pero degradar a 'posible' si hay alguna incertidumbre (n_inciertos > 0 para discretos)
        // - No permitir n < 4 para discretos (vs n < 3 para continuos)
        if (!$delta_critico && $conservation_ok && $n >= 5) {
            if ($es_discreto && $n_inciertos > 0) {
                $confianza = 'media';  // Si hay lotes abiertos, bajar a media incluso sin delta_critico
            } else {
                $confianza = 'alta';
            }
        } elseif (!$delta_critico && ($conservation_ok || ($n >= 4 && !$es_discreto))) {
            $confianza = 'media';
        } else {
            $confianza = 'posible';
        }

        return [
            'merma_total'        => round($merma_total, $precision),
            'merma_carryover'    => round($merma_carryover, $precision),  // arrastre técnico de déficit
            'merma_pendiente'    => round($merma_pendiente, $precision),  // sobrante en lotes inciertos (no confirmado)
            'deficit_bloqueado'  => round($deficit_bloqueado, $precision), // sobreventa no redistribuible: albarán faltante / stock no regularizado
            'n_lotes_inciertos'  => $n_inciertos,
            'pct_merma'          => round($pct, 2),
            'n_merma'            => $n_merma,
            'detalle_lotes'      => $detalle,
            'conservation_ok'    => $conservation_ok,
            'conservation_delta' => round($conservation_delta, $es_discreto ? 1 : 4),
            'severidad'          => $sev,
            'severidad_label'    => $sev_labels[$sev],
            'confianza'          => $confianza,
            'total_E'            => $es_discreto ? (float)intval($total_E) : round($total_E, 3),
        ];
    }

    /**
     * Calcula el stock seguro por mes para registrar un ajuste contable de merma.
     *
     * A diferencia del cierre mensual bruto, este valor mira hacia delante desde el
     * cierre de cada mes y toma el mínimo stock proyectado con los movimientos ya
     * conocidos (ventas, recepciones y devoluciones). Así, si se registra la merma
     * al final de mes, el ajuste no debería provocar un C1a/C1b en el horizonte ya
     * visible para C9.
     *
     * @param  array  $stock_mes_cierre  stock rebobinado al cierre de cada mes [YYYY-MM => float]
     * @param  array  $recs_art          recepciones del artículo [{fecha, cantidad, es_post_periodo}]
     * @param  array  $timeline_art      ventas netas del artículo [{fecha, day_delta}]
     * @param  array  $devs_art          devoluciones proveedor [{fecha, devolucion}]
     * @param  string $ff_mov            fin del periodo analizado
     * @return array  ['YYYY-MM' => float] stock máximo ajustable sin provocar negativos posteriores
     */
    private function calcularStockSeguroMes(
        array  $stock_mes_cierre,
        array  $recs_art,
        array  $timeline_art,
        array  $devs_art,
        string $ff_mov,
        string $tipo_fisico = 'peso'  // Para redondeo conservador en discretos
    ): array {
        if (empty($stock_mes_cierre)) return [];

        $neto_por_fecha = [];

        foreach ($recs_art as $rec) {
            $fecha = $rec['fecha'];
            $neto_por_fecha[$fecha] = ($neto_por_fecha[$fecha] ?? 0.0) + (float)$rec['cantidad'];
        }
        foreach ($timeline_art as $mov) {
            $fecha = $mov['fecha'];
            $neto_por_fecha[$fecha] = ($neto_por_fecha[$fecha] ?? 0.0) - (float)$mov['day_delta'];
        }
        foreach ($devs_art as $dev) {
            $fecha = $dev['fecha'];
            $neto_por_fecha[$fecha] = ($neto_por_fecha[$fecha] ?? 0.0) - (float)$dev['devolucion'];
        }
        ksort($neto_por_fecha);

        $stock_seguro_mes = [];
        $es_discreto = ($tipo_fisico !== 'peso');

        foreach ($stock_mes_cierre as $mes => $stock_cierre) {
            $fecha_cierre = $mes === substr($ff_mov, 0, 7)
                ? $ff_mov
                : (new \DateTimeImmutable($mes . '-01'))->modify('last day of this month')->format('Y-m-d');

            $stock_cursor = (float)$stock_cierre;
            $stock_minimo = $stock_cursor;

            foreach ($neto_por_fecha as $fecha => $neto) {
                if ($fecha <= $fecha_cierre) continue;
                $stock_cursor += $neto;
                if ($stock_cursor < $stock_minimo) {
                    $stock_minimo = $stock_cursor;
                }
            }

            // Para discretos, usar piso (floor) para ser conservador: si el stock mínimo es 10.3,
            // el tope aplicable es 10 (no 10.3) para evitar negativos por redondeo de enteros.
            $stock_final = max(0.0, $stock_minimo);
            if ($es_discreto) {
                $stock_seguro_mes[$mes] = (float)floor($stock_final);
            } else {
                $stock_seguro_mes[$mes] = round($stock_final, 4);
            }
        }

        return $stock_seguro_mes;
    }

    // ───────────────────────────────────────────────────────────────────────
    //  Capa operativa: distribución mensual de merma con tope de stock
    // ───────────────────────────────────────────────────────────────────────

    /**
     * Genera un plan de aplicación mensual para la merma C9 de un artículo.
     *
     * Distribuye la merma de cada lote entre los meses calendario que abarca
     * (prorrateo por días), aplica un tope de stock para evitar negativos y
     * arrastra el remanente no aplicable al mes siguiente.
     *
     * @param  array  $detalle_lotes       merma_por_lote de la incidencia C9
     * @param  array  $stock_mes             stock seguro de cada mes [YYYY-MM => float]
     *                                       tras mirar los movimientos posteriores ya conocidos.
     *                                       Representa cuánto puede ajustarse al cierre del mes
     *                                       sin provocar stock negativo posterior en el horizonte.
     * @param  float  $stock_minimo          suelo de stock (≥0); no se aplica merma si el
     *                                       stock quedaría por debajo de este valor.
     * @param  array  $merma_declarada_mes   merma ya declarada por mes [YYYY-MM => float].
     *                                       Se resta de la merma prorrateada: la merma estimada
     *                                       ya incluye la declarada (los albaranes especiales se
     *                                       excluyen de V_t), así que el plan solo propone la
     *                                       parte no documentada.
     * @return array  [
     *   'plan'      => [YYYY-MM => ['propuesto'=>f,'aplicable'=>f,'arrastre'=>f]],
     *   'total_propuesto' => float,
     *   'total_aplicable' => float,
     *   'arrastre_final'  => float,   // merma no aplicada al cierre del último mes
     * ]
     */
    public function calcularPlanMensual(
        array  $detalle_lotes,
        array  $stock_mes,
        float  $stock_minimo = 0.0,
        array  $merma_declarada_mes = [],
        string $tipo_fisico = 'peso'
    ): array {
        $es_discreto = ($tipo_fisico !== 'peso');

        // ── Paso 1: prorratear merma de cada lote a meses calendario ─────
        $merma_por_mes = [];  // [YYYY-MM => float]

        foreach ($detalle_lotes as $lote) {
            $merma_t = $lote['merma_t'] ?? 0.0;
            if ($merma_t <= 0.0) continue;
            if ($lote['es_lote_incierto'] ?? false) continue;  // solo merma confirmada

            $ini = new \DateTimeImmutable($lote['fecha_ini']);
            $fin = new \DateTimeImmutable($lote['fecha_fin']);
            $dias_total = max(1, (int)$ini->diff($fin)->days + 1);

            // Recorrer cada mes que abarca el lote
            $cursor = $ini;
            while ($cursor <= $fin) {
                $mesKey   = $cursor->format('Y-m');
                $fin_mes  = $cursor->modify('last day of this month');
                $tope     = ($fin_mes > $fin) ? $fin : $fin_mes;
                $dias_mes = (int)$cursor->diff($tope)->days + 1;

                $proporcion = $dias_mes / $dias_total;
                $merma_por_mes[$mesKey] = ($merma_por_mes[$mesKey] ?? 0.0)
                    + round($merma_t * $proporcion, 4);

                // Avanzar al primer día del mes siguiente
                $cursor = $tope->modify('+1 day');
            }
        }
        ksort($merma_por_mes);

        // ── Paso 1b: descontar merma declarada ──────────────────────────
        // La merma estimada (sum S_t) ya incluye la merma declarada porque los
        // albaranes especiales se excluyen de V_t. La parte declarada ya está
        // registrada → solo proponer la diferencia no documentada.
        foreach ($merma_declarada_mes as $mesDecl => $montoDecl) {
            if (isset($merma_por_mes[$mesDecl])) {
                $merma_por_mes[$mesDecl] = max(0.0, $merma_por_mes[$mesDecl] - $montoDecl);
            }
        }

        // ── Paso 1c: para productos discretos, redistribuir a enteros ────
        // Cuando un lote abarca varios meses el prorrateo produce fracciones
        // (ej. 3 uds × 20/31 = 1,935). Usamos el algoritmo de "largest remainder"
        // para convertir el mapa a enteros conservando la suma total exacta.
        if ($es_discreto && !empty($merma_por_mes)) {
            $total_uds  = (int)round(array_sum($merma_por_mes));
            $floors     = [];
            $fracciones = [];
            foreach ($merma_por_mes as $mes => $val) {
                $floors[$mes]     = (int)floor($val);
                $fracciones[$mes] = $val - $floors[$mes];
            }
            $remainder = $total_uds - (int)array_sum($floors);
            // Asignar unidades sobrantes a los meses con mayor parte fraccionaria
            arsort($fracciones);
            foreach (array_keys($fracciones) as $mes) {
                if ($remainder <= 0) break;
                $floors[$mes]++;
                $remainder--;
            }
            // Restaurar en merma_por_mes como enteros
            foreach ($merma_por_mes as $mes => $_) {
                $merma_por_mes[$mes] = (float)$floors[$mes];
            }
        }

        // ── Paso 2: aplicar tope de stock mes a mes con arrastre ─────────
        $plan            = [];
        $arrastre        = 0.0;
        $total_propuesto = 0.0;
        $total_aplicable = 0.0;
        $prec = $es_discreto ? 0 : 4;

        foreach ($merma_por_mes as $mes => $prorrateo) {
            $propuesto = round($prorrateo + $arrastre, $prec);
            $stock     = $stock_mes[$mes] ?? 0.0;
            $margen    = max(0.0, round($stock - $stock_minimo, $prec));
            $aplicable = round(min($propuesto, $margen), $prec);
            $arrastre  = round($propuesto - $aplicable, $prec);

            $plan[$mes] = [
                'propuesto' => $propuesto,
                'aplicable' => $aplicable,
                'arrastre'  => $arrastre,
            ];
            $total_propuesto += $prorrateo;
            $total_aplicable += $aplicable;
        }

        $precTot = $es_discreto ? 0 : 3;
        return [
            'plan'             => $plan,
            'total_propuesto'  => round($total_propuesto, $precTot),
            'total_aplicable'  => round($total_aplicable, $precTot),
            'arrastre_final'   => round($arrastre, $precTot),
        ];
    }

    // Orquestador principal

    /**
     * C9 — Merma por backstaging LIFO inverso con ponderación exponencial temporal.
     *
     * Orquesta: Q1 (recepciones sin proveedores especiales) → Q2 (timeline limpio) →
     * Q5a (prov especiales: cruce vs merma declarada) → Q5b (cli especiales: merma
     * declarada) → lotes → backstaging → clasificación.
     *
     * Aplica a todos los artículos candidatos por recepción y filtros de entrada.
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
        // Paso 1: recepciones (Q1)
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

        $idsArticulosCsv = $this->convertirIdsACsv($ids_candidatos);
        if ($idsArticulosCsv === '') return [];

        // Paso 2: obtener tipos para umbrales de severidad/visualización (sin filtrar artículos)
        $sentenciaArticulos = $this->db->query(
            "SELECT idArticulo, tipo FROM articulos
              WHERE idArticulo IN ($idsArticulosCsv)"
        );
        if (!$sentenciaArticulos) return ['error' => 'C9 tipos: ' . $this->db->error];
        $tipos_map = [];
        while ($fila = $sentenciaArticulos->fetch_assoc()) $tipos_map[(int)$fila['idArticulo']] = $fila['tipo'];
        $sentenciaArticulos->free();
        // No se excluyen artículos por tipo físico.
        $idsArticulosCsv = $this->convertirIdsACsv($ids_candidatos);
        if ($idsArticulosCsv === '') return [];

        // Paso 1b: devoluciones ordinarias (nunidades < 0, proveedor no especial)
        $ff_post_dev = $this->db->real_escape_string(
            date('Y-m-d', strtotime("$ff_mov +$c9_dias_post days"))
        );
        $filasDevolucionesProv = $this->repo->queryDevolucionesProvC9($fechaInicioEsc, $ff_post_dev, $idsArticulosCsv);
        if (isset($filasDevolucionesProv['error'])) return $filasDevolucionesProv;
        $devs_map = [];
        foreach ($filasDevolucionesProv as $fila) {
            $devs_map[(int)$fila['idArticulo']][] = ['fecha' => $fila['fecha'], 'devolucion' => (float)$fila['devolucion']];
        }

        // Paso 3: timeline limpio (Q2), extendido hasta ff_post para capturar
        // ventas post-periodo del ultimo lote (hasta primera_post - 1).
        $ff_post_tl = $this->db->real_escape_string(
            date('Y-m-d', strtotime("$ff_mov +$c9_dias_post days"))
        );
        $filasTimeline = $this->repo->queryTimelineC9($fechaInicioEsc, $ff_post_tl, $idsArticulosCsv);
        if (isset($filasTimeline['error'])) return $filasTimeline;
        $timeline_map = [];
        foreach ($filasTimeline as $fila) {
            $timeline_map[(int)$fila['idArticulo']][] = ['fecha' => $fila['fecha'], 'day_delta' => (float)$fila['day_delta']];
        }

        // Paso 4: albaranes especiales (Q5a + Q5b)
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
                    $mesProvDecl = substr($fecha, 0, 7);
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
                    $mesCliDecl = substr($fecha, 0, 7);
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

        // Paso 5: stock base (compartido con C7 en batch)
        if (empty($stock_base_cache)) {
            $fechaInicioStockBaseEsc   = $this->db->real_escape_string($fi_stock);
            $fechaFinStockBaseEsc   = $this->db->real_escape_string($fi_mov);
            $idsStockBaseCsv = $this->convertirIdsACsv($ids_candidatos);
            if ($idsStockBaseCsv === '') return [];
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

        // Paso 5b: stock real al inicio del periodo (rebobinado)        // Se usa como E_lote0 en lugar del saldo acumulado desde fi_stock.
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

        // Paso 6: stock rebobinado al ff_mov (ancla conservación)
        $filasStockFinal = $this->repo->queryStockRebobinado($idsArticulosCsv, $fechaFinEsc);
        if (isset($filasStockFinal['error'])) return $filasStockFinal;
        $stock_final_map = [];
        foreach ($filasStockFinal as $fila) $stock_final_map[(int)$fila['idArticulo']] = (float)$fila['stock_en_periodo'];

        // Paso 7: loop por artículo
        $incidencias = [];

        foreach ($ids_candidatos as $idArticulo) {
            $tipo_fisico  = $tipos_map[$idArticulo] ?? 'unidad';
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

            // Marcar lotes inciertos al final del periodo.
            // Solo aplica cuando hay lotes realmente abiertos (sin cierre por recepción posterior),
            // lo que cubre tanto periodo en curso como BD anualizada sin datos de enero.
            $hay_abierto     = !empty(array_filter($lotes, fn($linea) => !empty($linea['es_ultimo_abierto'])));
            $aplicar_umbral  = $hay_abierto;

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

            $resultado = $this->backstagingExponencial($lotes, $c9_k, $c9_beta, $c9_lambda, $tipo_fisico);
            $deficitAbsorbidoHeredado = $this->absorberDeficitBloqueadoConStockHeredado(
                $resultado['lotes'],
                $stock_at_first_rec
            );

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
            if ($clasif['merma_total'] < $umbral) continue;
            $n_en_periodo = count(array_filter($recs_art, fn($fila) => !$fila['es_post_periodo']));

            // Plan mensual: distribuir merma confirmada entre meses sin generar stock negativo.
            // El tope usa stock "seguro" mirando meses posteriores ya visibles para que
            // el asiento de merma no induzca C1a/C1b tras el cierre mensual.
            $stock_mes_cierre = $this->repo->queryStockCierreMes($idArticulo, $fi_mov, $ff_mov);
            $stock_mes_seguro = $this->calcularStockSeguroMes(
                $stock_mes_cierre,
                $recs_art,
                $timeline_art,
                $devs_art,
                $ff_mov,
                $tipo_fisico  // Pasar tipo para redondeo conservador en discretos
            );
            $plan = $this->calcularPlanMensual($clasif['detalle_lotes'], $stock_mes_seguro, 0.0, $mermaDeclaradaPorMes, $tipo_fisico);
            $diagDeficitBloq = $this->construirDiagnosticoDeficitBloqueado($resultado['lotes'], $recs_art);

            $incidencias[] = [
                'caso'                => 'C9',
                'tipo'                => 'Merma backstaging',
                'idArticulo'          => $idArticulo,
                'merma_total_kg'      => $clasif['merma_total'],
                'merma_carryover_kg'  => $clasif['merma_carryover'],   // arrastre técnico de déficit
                'merma_pendiente_kg'  => $clasif['merma_pendiente'],   // sobrante incierto no confirmado
                'deficit_bloqueado_kg' => $clasif['deficit_bloqueado'], // sobreventa no redistribuible
                'deficit_bloqueado_absorbido_heredado_kg' => round($deficitAbsorbidoHeredado, 3),
                'deficit_bloqueado_momentos' => $diagDeficitBloq['momentos'],
                'deficit_bloqueado_patron_estable' => $diagDeficitBloq['patron_entradas_estable'],
                'deficit_bloqueado_intervalo_tipico_dias' => $diagDeficitBloq['intervalo_tipico_dias'],
                'deficit_bloqueado_cv_intervalos' => $diagDeficitBloq['cv_intervalos'],
                'deficit_bloqueado_n_intervalos' => $diagDeficitBloq['n_intervalos'],
                'deficit_bloqueado_estimaciones' => $diagDeficitBloq['estimaciones'],
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
                'plan_mensual'        => $plan['plan'],           // [YYYY-MM => {propuesto,aplicable,arrastre}]
                'plan_total_propuesto' => $plan['total_propuesto'],
                'plan_total_aplicable' => $plan['total_aplicable'],
                'plan_arrastre_final'  => $plan['arrastre_final'],
                'backstaging_trace'   => $resultado['trace'],
                'beta_usado'          => $c9_beta,
                'k_usado'             => $c9_k,
                'lambda_usado'        => $c9_lambda,
                'modo'                => ($tipo_fisico === 'peso') ? 'continuo' : 'discreto',
                'total_E'             => $clasif['total_E'],
            ];
        }

        // C9: enriquecer con proveedor habitual y coste estimado de la merma
        if (!empty($incidencias)) {
            $ids_c9     = array_column($incidencias, 'idArticulo');
            $idsC9Csv = $this->convertirIdsACsv($ids_c9);
            if ($idsC9Csv === '') return $incidencias;
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

    private function convertirIdsACsv(array $ids): string
    {
        $idsEnteros = [];
        foreach ($ids as $id) {
            $idsEnteros[] = (int)$id;
        }

        if (empty($idsEnteros)) {
            return '';
        }

        return implode(',', $idsEnteros);
    }
}
