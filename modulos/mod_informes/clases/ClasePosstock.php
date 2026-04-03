<?php

/**
 * ClasePosstock — Lógica de datos para el informe POSStock.
 *
 * ─── ARQUITECTURA ────────────────────────────────────────────────────────────
 *
 * El cálculo se divide en tres fases que se encadenan:
 *
 *   1. getMovimientosPeriodo($fi, $ff)          [T4.1 — SQL]
 *      UNION ALL de los 3 bloques de movimientos físicos en la ventana:
 *        · entrada_proveedor : albprolinea + albprot
 *        · salida_ticket     : ticketslinea + ticketst
 *        · salida_albcli     : albclilinea + albclit
 *      Devuelve todas las filas individuales ordenadas por (idArticulo, fecha).
 *
 *   2. getStockBase($ids, $fi_stock, $ff_stock)  [T4.2 — SQL]
 *      Mismo UNION ALL pero sobre el rango 01-Ene → día anterior al periodo,
 *      filtrando solo los idArticulo encontrados en el paso 1.
 *      Devuelve saldo_acumulado + ultima_compra + ultima_venta por artículo.
 *
 *   3. calcularStockPrevio($movimientos, $stock_base)  [T4.3 — PHP]
 *      Combina los resultados de 1 y 2 en memoria para calcular, por cada
 *      entrada_proveedor de la ventana:
 *        · stock_previo             = saldo al inicio del día anterior a la entrada
 *        · stock_tras_ultimo_albaran = saldo acumulado tras la última entrada del periodo
 *
 * ─── DECISIÓN: T4.3 EN PHP, NO EN SQL ───────────────────────────────────────
 *
 * MariaDB 10.11 (verificado con SELECT VERSION()) soporta window functions
 * (SUM() OVER, ROW_NUMBER(), etc. disponibles desde 10.2).
 * Se eligió PHP porque T4.1 y T4.2 ya están en memoria y una tercera consulta
 * SQL con CTE + window frame habría replicado la misma lógica con más complejidad
 * y sin ganancia de rendimiento para el volumen de datos esperado (~4.000 filas).
 *
 * ─── ESTADOS DE DOCUMENTO VÁLIDOS ───────────────────────────────────────────
 *
 *   albprot  (entradas proveedor) : Guardado, Facturado, Exportado, Importado
 *     · Exportado: era Guardado y se exportó a XML (sigue siendo movimiento real)
 *     · Importado: creado por importación XML (ClaseAlbaranCompraXML)
 *     · Excluido : Sin Guardar (borrador)
 *
 *   ticketst (salidas ticket)     : Cerrado
 *     · Excluido : Abierto (en curso)
 *
 *   albclit  (salidas cliente)    : Guardado, Procesado
 *     · Excluido : Sin guardar (borrador)
 *
 *
 * ─── REGLA "DÍA ANTERIOR" PARA stock_previo ─────────────────────────────────
 *
 *   stock_previo de una entrada en fecha D = saldo_base + Σ movimientos con fecha < D
 *   Si varias entradas caen el mismo día D, todas comparten el mismo stock_previo
 *   (estado al inicio de D, antes de que llegue ningún albarán de ese día).
 *   Las ventas del mismo día D se excluyen del stock_previo (se contabilizarán
 *   en el stock_previo de entradas de días posteriores).
 *
 * ─── USO ─────────────────────────────────────────────────────────────────────
 *
 *   $p   = new ClasePosstock($BDTpv);
 *   $mov = $p->getMovimientosPeriodo('2025-02-01', '2025-02-28');
 *   $ids = array_unique(array_column($mov, 'idArticulo'));
 *   $base = $p->getStockBase($ids, '2025-01-01', '2025-01-31');
 *   $calc = $p->calcularStockPrevio($mov, $base);
 *   // $calc: array de entradas con stock_previo y stock_tras_ultimo_albaran
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */
require_once __DIR__ . '/PosstockStatistics.php';
require_once __DIR__ . '/PosstockQueryRepository.php';
require_once __DIR__ . '/PosstockC1Detector.php';
require_once __DIR__ . '/PosstockC2Detector.php';
require_once __DIR__ . '/PosstockC3Detector.php';
require_once __DIR__ . '/PosstockC4Detector.php';
require_once __DIR__ . '/PosstockC5Detector.php';
require_once __DIR__ . '/PosstockC6Detector.php';
require_once __DIR__ . '/PosstockC9Detector.php';

class ClasePosstock
{
    private $db;
    private PosstockQueryRepository $repo;
    private PosstockC1Detector $c1;
    private PosstockC2Detector $c2;
    private PosstockC3Detector $c3;
    private PosstockC4Detector $c4;
    private PosstockC5Detector $c5;
    private PosstockC6Detector $c6;
    private PosstockC9Detector $c9;

    // Umbral de stock negativo a partir del cual C6 reconstruye el stock
    // desde la última entrada de proveedor (más fiable que el stockOn acumulado).
    const STOCK_NEGATIVO_UMBRAL = -2.0;


    public function __construct($conexion)
    {
        $this->db   = $conexion;
        $this->repo = new PosstockQueryRepository($conexion);
        $this->c1   = new PosstockC1Detector($conexion, $this->repo);
        $this->c2   = new PosstockC2Detector($conexion, $this->repo);
        $this->c3   = new PosstockC3Detector($conexion, $this->repo);
        $this->c4   = new PosstockC4Detector($conexion, $this->repo);
        $this->c5   = new PosstockC5Detector($conexion, $this->repo);
        $this->c6   = new PosstockC6Detector($conexion, $this->repo);
        $this->c9   = new PosstockC9Detector($conexion, $this->repo);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // LÓGICA — Métodos de negocio y helpers de transformación
    // (SQL queries desplazadas a PosstockQueryRepository — Fase 2)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * T4.1 — Movimientos en la ventana de análisis.
     *
     * Devuelve todos los movimientos (entradas de proveedor + salidas de ticket
     * + salidas de albarán cliente) de artículos físicos en el periodo indicado.
     *
     * @param string $fecha_inicio  'YYYY-MM-DD'
     * @param string $fecha_fin     'YYYY-MM-DD'
     *
     * @return array  Filas con:
     *   tipo_movimiento ('entrada_proveedor' | 'salida_ticket' | 'salida_albcli'),
     *   idArticulo, nunidades, fecha (DATE), idDocumento
     *   — o array con clave 'error' si falla la consulta.
     */
    public function getMovimientosPeriodo($fecha_inicio, $fecha_fin, array $familias_incluir = [], array $familias_excluir = [], array $ids_filter = [])
    {
        $fi = $this->db->real_escape_string($fecha_inicio);
        $ff = $this->db->real_escape_string($fecha_fin);

        $where_familia = '';
        if (!empty($familias_incluir)) {
            $ids = $this->repo->expandirFamilias($familias_incluir);
            if ($ids) $where_familia .= " AND l.idArticulo IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids))";
        }
        if (!empty($familias_excluir)) {
            $ids = $this->repo->expandirFamilias($familias_excluir);
            if ($ids) $where_familia .= " AND l.idArticulo NOT IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids))";
        }

        $where_ids = empty($ids_filter)
            ? ''
            : " AND l.idArticulo IN (" . implode(',', array_map('intval', $ids_filter)) . ")";

        return $this->repo->queryMovimientosPeriodo($fi, $ff, $where_familia, $where_ids);
    }

    /**
     * T4.2 — Stock base acumulado desde inicio del ejercicio hasta fecha_fin_stock.
     *
     * Solo se calcula para los artículos que tuvieron movimiento en la ventana (T4.1),
     * reduciendo el coste de la consulta.
     *
     * @param array  $ids_articulos   Array de idArticulo obtenidos en T4.1
     * @param string $fecha_inicio    'YYYY-MM-DD'  (01-Ene del ejercicio)
     * @param string $fecha_fin       'YYYY-MM-DD'  (día anterior al inicio de movimientos)
     *
     * @return array  Indexado por idArticulo con:
     *   saldo_acumulado (DECIMAL), ultima_compra (DATE|null), ultima_venta (DATE|null)
     *   — o array con clave 'error' si falla la consulta.
     */
    public function getStockBase(array $ids_articulos, $fecha_inicio, $fecha_fin)
    {
        if (empty($ids_articulos)) {
            return [];
        }

        $fi  = $this->db->real_escape_string($fecha_inicio);
        $ff  = $this->db->real_escape_string($fecha_fin);
        $ids = implode(',', array_map('intval', $ids_articulos));

        $rows = $this->repo->queryStockBase($fi, $ff, $ids);
        if (isset($rows['error'])) return $rows;

        $resultado = [];
        foreach ($rows as $row) {
            $resultado[(int)$row['idArticulo']] = [
                'saldo_acumulado' => (float)$row['saldo_acumulado'],
                'ultima_compra'   => $row['ultima_compra'],
                'ultima_venta'    => $row['ultima_venta'],
            ];
        }
        return $resultado;
    }

    /**
     * T4.3 — Calcula stock_previo y stock_tras_ultimo_albaran para cada entrada de proveedor.
     *
     * Combina en PHP los resultados de T4.1 y T4.2, evitando una consulta SQL adicional.
     * MariaDB 10.11 soporta window functions, pero los datos ya están en memoria.
     *
     * Regla "día anterior":
     *   stock_previo de una entrada en fecha D = saldo_base + suma de todos los
     *   movimientos (entradas y salidas) cuya fecha < D.
     *   Si hay varias entradas el mismo día, todas comparten el mismo stock_previo
     *   (estado al inicio del día, antes de que llegue ningún albarán de ese día).
     *
     * stock_tras_ultimo_albaran (por artículo, no por movimiento):
     *   saldo_base + suma de todos los movimientos con fecha <= fecha del último albaran
     *   de entrada en la ventana.
     *
     * @param array $movimientos  Resultado de getMovimientosPeriodo()
     * @param array $stock_base   Resultado de getStockBase(), indexado por idArticulo
     *
     * @return array  Una entrada por cada fila entrada_proveedor con:
     *   idArticulo, idDocumento, fecha, nunidades,
     *   stock_previo, stock_tras_ultimo_albaran
     */
    public function calcularStockPrevio(array $movimientos, array $stock_base): array
    {
        // Agrupar movimientos por idArticulo
        $por_articulo = [];
        foreach ($movimientos as $m) {
            $por_articulo[(int)$m['idArticulo']][] = $m;
        }

        $resultado = [];

        foreach ($por_articulo as $idArticulo => $movs) {
            // Filtrar solo las entradas de proveedor de este artículo
            $entradas = array_filter($movs, fn($m) => $m['tipo_movimiento'] === 'entrada_proveedor');
            if (empty($entradas)) {
                continue;
            }

            $saldo_base_art = isset($stock_base[$idArticulo])
                ? $stock_base[$idArticulo]['saldo_acumulado']
                : 0.0;

            // Acumular delta firmado por fecha (signo: entradas +, salidas -)
            $delta_por_fecha = [];
            foreach ($movs as $m) {
                $signo = ($m['tipo_movimiento'] === 'entrada_proveedor') ? 1.0 : -1.0;
                $delta_por_fecha[$m['fecha']] = ($delta_por_fecha[$m['fecha']] ?? 0.0)
                    + $signo * (float)$m['nunidades'];
            }
            ksort($delta_por_fecha); // orden cronológico

            // Para cada fecha, calcular el saldo AL INICIO del día (antes de sus movimientos)
            $saldo_inicio_dia = [];
            $acum = $saldo_base_art;
            foreach ($delta_por_fecha as $fecha => $delta) {
                $saldo_inicio_dia[$fecha] = $acum; // antes de aplicar el delta del día
                $acum += $delta;
            }

            // stock_tras_ultimo_albaran: acumulado hasta fin del día de la última entrada
            $ultima_fecha_entrada = max(array_column(array_values($entradas), 'fecha'));
            $saldo_tras_ultima = $saldo_base_art;
            foreach ($delta_por_fecha as $fecha => $delta) {
                if ($fecha <= $ultima_fecha_entrada) {
                    $saldo_tras_ultima += $delta;
                }
            }

            // Asignar stock_previo a cada entrada
            foreach ($entradas as $entrada) {
                $resultado[] = [
                    'idArticulo'               => $idArticulo,
                    'idDocumento'              => $entrada['idDocumento'],
                    'fecha'                    => $entrada['fecha'],
                    'nunidades'                    => (float)$entrada['nunidades'],
                    'stock_previo'             => $saldo_inicio_dia[$entrada['fecha']] ?? $saldo_base_art,
                    'stock_tras_ultimo_albaran' => $saldo_tras_ultima,
                ];
            }
        }

        return $resultado;
    }

    /**
     * T4.4 / T5 — Método principal: orquesta T4.1→T4.2→T4.3 y aplica reglas de detección.
     *
     * Estructura de resultados: UNA FILA POR CASO.
     * Un artículo puede generar múltiples filas si dispara varios casos.
     * Esto permite ordenar por severidad y filtrar por tipo independientemente.
     *
     * @param array $params  Claves requeridas:
     *   fecha_inicio_movimientos, fecha_fin_movimientos,
     *   fecha_inicio_stock,       fecha_fin_stock
     *   Claves opcionales:
     *   casos_incluir                (array,  default [] = todos excepto caso4)
     *   umbral_sobrestock            (float,  default 0.5)
     *   umbral_caducidad_semanas     (int,    default 24)
     *   umbral_sin_rotacion_semanas  (int,    default 12)
     *
     * casos_incluir acepta: 'caso1','caso2','caso3a','caso3b','caso4','caso5'.
     * [] o ausente = C1+C2+C3a+C3b+C5 (todos los activos; C4 excluido salvo indicación explícita).
     *
     * @return array  Filas ordenadas CRITICA→ALTA→MEDIA(C2→C5→C3a)→BAJA(C3b→C4),
     *               o array con clave 'error' si falla.
     */
    public function getIncidencias(array $params): array
    {
        $fi_mov   = $params['fecha_inicio_movimientos'];
        $ff_mov   = $params['fecha_fin_movimientos'];
        $fi_stock = $params['fecha_inicio_stock'];
        $ff_stock = $params['fecha_fin_stock'];
        // Ventana estadística ampliada para semana/quincena/mes (±1 periodo)
        $fi_stats = $params['fecha_inicio_stats'] ?? $fi_stock;
        $ff_stats = $params['fecha_fin_stats']    ?? $ff_mov;

        $umbral_sobrestock         = (float)  ($params['umbral_sobrestock']           ?? 0.5);
        $umbral_caducidad          = (int)    ($params['umbral_caducidad_semanas']    ?? 24);
        $umbral_sin_rotacion       = (int)    ($params['umbral_sin_rotacion_semanas'] ?? 12);
        $c3b_dias_post             = (int)    ($params['c3b_dias_post_periodo']       ?? 14);
        $c3a_multiplicador         = (float)  ($params['c3a_multiplicador_cadencia']  ?? 3.0);
        $umbral_rop_mult           = max(3.0, (float)($params['umbral_reconstituir_rop'] ?? 10.0));
        $umbral_stock_neg          = max(0.0, (float)($params['umbral_stock_negativo']   ?? 2.0));
        $min_ventas_c5          = (int)    ($params['min_ventas_c5']               ?? 3);
        $modelo_rotura_c5           = (string) ($params['modelo_rotura_c5']              ?? 'automatico');
        $umbral_confianza_c5        = (float)  ($params['umbral_confianza_poisson']      ?? 0.05);
        $c5_incluir_stock_negativo  = (bool)   ($params['c5_incluir_stock_negativo']     ?? false);
        $binomial_sigma_mult        = (float)  ($params['binomial_sigma_mult']           ?? 3.0);
        $c1_umbral_fraccionado      = (float)  ($params['c1_umbral_fraccionado']         ?? 0.05);
        $c1_umbral_magnitud         = (float)  ($params['c1_umbral_magnitud']            ?? 0.5);
        $c1_umbral_por_venta        = (float)  ($params['c1_umbral_por_venta']           ?? 0.010);
        $c1_timing_ventana_dias     = (int)    ($params['c1_timing_ventana_dias']        ?? 1);
        $c7b_min_recepciones        = (int)    ($params['c7b_min_recepciones']           ?? 3);
        $c7b_umbral_cv              = (float)  ($params['c7b_umbral_cv']                 ?? 0.5);
        $c7b_umbral_cv_peso         = (float)  ($params['c7b_umbral_cv_peso']            ?? 0.75);
        $c7b_umbral_iqr_peso        = (float)  ($params['c7b_umbral_iqr_peso']           ?? 2.0);
        $c7b_umbral_ruido_peso      = (float)  ($params['c7b_umbral_ruido_peso']         ?? 0.5);
        $c7b_umbral_severidad_unidad = (int)   ($params['c7b_umbral_severidad_unidad']   ?? 5);
        $c7b_umbral_severidad_peso  = (float)  ($params['c7b_umbral_severidad_peso']     ?? 2.5);
        $c7b_cascada_exhaustiva     = (bool)   ($params['c7b_cascada_exhaustiva']        ?? false);
        $c7a_umbral_delta_unidad    = (float)  ($params['c7a_umbral_delta_unidad']       ?? 2.0);
        $c7a_umbral_delta_peso      = (float)  ($params['c7a_umbral_delta_peso']         ?? 1.0);
        $c7a_umbral_pvalue          = (float)  ($params['c7a_umbral_pvalue']             ?? 0.10);
        $c7a_umbral_pvalue_alta     = (float)  ($params['c7a_umbral_pvalue_alta']        ?? 0.05);
        $c7a_umbral_alta_delta_unidad = (float)($params['c7a_umbral_alta_delta_unidad']  ?? 10.0);
        $c7a_umbral_alta_delta_peso = (float)  ($params['c7a_umbral_alta_delta_peso']    ?? 5.0);
        $c7a_umbral_alta_slope_unidad = (float)($params['c7a_umbral_alta_slope_unidad']  ?? 2.0);
        $c7a_umbral_alta_slope_peso = (float)  ($params['c7a_umbral_alta_slope_peso']    ?? 1.0);
        $c7a_umbral_snr             = (float)  ($params['c7a_umbral_snr']                ?? 0.15);
        $c7a_cascada_exhaustiva     = (bool)   ($params['c7a_cascada_exhaustiva']        ?? false);
        $familias_incluir    = (array) ($params['familias_incluir'] ?? []);
        $familias_excluir    = (array) ($params['familias_excluir'] ?? []);
        $ids_filter          = (array) ($params['ids_filter']       ?? []);

        // ── Filtro de proveedores → intersectar article IDs ───────────────────
        // ids_proveedor_filter: pre-resueltos por getIncidenciasBatch (evita doble query).
        // proveedores_incluir: IDs de proveedor raw (cuando se llama directamente).
        $ids_proveedor_filter = (array)($params['ids_proveedor_filter'] ?? []);
        if (empty($ids_proveedor_filter)) {
            $proveedores_incluir = (array)($params['proveedores_incluir'] ?? []);
            if (!empty($proveedores_incluir)) {
                $ids_str_prov = implode(',', array_map('intval', $proveedores_incluir));
                $rows_prov = $this->repo->queryIdsArticulosByProveedores($ids_str_prov);
                if (isset($rows_prov['error'])) return $rows_prov;
                $ids_proveedor_filter = array_column($rows_prov, 'idArticulo');
                if (empty($ids_proveedor_filter)) return []; // ningún artículo para esos proveedores
            }
        }
        if (!empty($ids_proveedor_filter)) {
            if (!empty($ids_filter)) {
                $ids_filter = array_values(array_intersect($ids_filter, $ids_proveedor_filter));
                if (empty($ids_filter)) return []; // intersección vacía
            } else {
                $ids_filter = $ids_proveedor_filter;
            }
        }

        // casos_incluir [] = todos los casos activos excepto C4
        $validos_todos = ['caso1', 'caso2', 'caso3a', 'caso3b', 'caso5', 'caso6a', 'caso6b', 'caso7a', 'caso7b', 'caso9'];
        $casos_raw     = (array)($params['casos_incluir'] ?? []);
        $casos_set     = array_flip(
            empty($casos_raw)
                ? $validos_todos
                : array_intersect($casos_raw, [...$validos_todos, 'caso4'])
        );

        $incidencias = [];

        // ── Precalcular stock_base compartido para C1, C2, C7 y C9 ──────────
        $sb_shared = [];
        if (!empty($ids_filter) && (isset($casos_set['caso1']) || isset($casos_set['caso2']) || isset($casos_set['caso7a']) || isset($casos_set['caso7b']) || isset($casos_set['caso9']))) {
            $sb_shared = $this->getStockBase($ids_filter, $fi_stock, $ff_stock);
            if (isset($sb_shared['error'])) return $sb_shared;
        }

        // ── C1 ───────────────────────────────────────────────────────────────
        if (isset($casos_set['caso1'])) {
            $c1 = $this->c1->detectar(
                $fi_mov,
                $ff_mov,
                $fi_stock,
                $ff_stock,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $sb_shared,
                $c1_umbral_fraccionado,
                $c1_umbral_magnitud,
                $c1_umbral_por_venta,
                $c1_timing_ventana_dias
            );
            if (isset($c1['error'])) return $c1;
            $incidencias = array_merge($incidencias, $c1);
        }

        // IDs con C1a activo — usados para marcar stock_no_fiable en C5/C6
        $ids_con_c1a = [];
        foreach ($incidencias as $inc) {
            if ($inc['tipo'] === 'Inventario en negativo') {
                $ids_con_c1a[$inc['idArticulo']] = true;
            }
        }

        // ── C2 ───────────────────────────────────────────────────────────────
        if (isset($casos_set['caso2'])) {
            $c2 = $this->c2->detectar(
                $fi_mov,
                $ff_mov,
                $fi_stock,
                $ff_stock,
                $umbral_sobrestock,
                $params['c2_umbral_duplicado']         ?? 1.0,
                $params['c2_umbral_sobrestock_severo'] ?? 6.0,
                $params['c2_umbral_cobertura_dias']    ?? 21,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $sb_shared
            );
            if (isset($c2['error'])) return $c2;
            $incidencias = array_merge($incidencias, $c2);
        }

        // ── C3 (3a y/o 3b — una sola query, filtrar resultado por sub-caso) ──
        if (isset($casos_set['caso3a']) || isset($casos_set['caso3b'])) {
            $c3 = $this->c3->detectar(
                $fi_mov,
                $ff_mov,
                $fi_stock,
                $umbral_caducidad,
                $umbral_sin_rotacion,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $c3b_dias_post,
                $c3a_multiplicador
            );
            if (isset($c3['error'])) return $c3;
            // Filtrar sub-casos si no se piden ambos
            if (!isset($casos_set['caso3a']) || !isset($casos_set['caso3b'])) {
                $tipos_c3 = [];
                if (isset($casos_set['caso3a'])) $tipos_c3[] = 'Caída de rotación';
                if (isset($casos_set['caso3b'])) $tipos_c3[] = 'Entrada sin rotación previa';
                $c3 = array_values(array_filter($c3, fn($inc) => in_array($inc['tipo'], $tipos_c3, true)));
            }
            $incidencias = array_merge($incidencias, $c3);
        }

        $incluir_albcli = (bool)($params['incluir_albcli_ventas'] ?? false);

        // ── C5 ───────────────────────────────────────────────────────────────
        if (isset($casos_set['caso5'])) {
            $c5 = $this->c5->detectar(
                $fi_mov,
                $ff_mov,
                $fi_stats,
                $umbral_sobrestock,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $min_ventas_c5,
                $modelo_rotura_c5,
                $umbral_confianza_c5,
                $c5_incluir_stock_negativo,
                $binomial_sigma_mult,
                $incluir_albcli,
                $c3b_dias_post,
                $ff_stats
            );
            if (isset($c5['error'])) return $c5;
            $incidencias = array_merge($incidencias, $c5);
        }

        $c6_proveedores  = (array)($params['proveedores_incluir']  ?? []);
        $c6_lead_time    = (int)  ($params['c6_lead_time_defecto'] ?? 14);
        $c6_nivel_serv   = (float)($params['c6_nivel_servicio']    ?? 0.95);

        // ── C6a — ROP estacional (ventana ligada al periodo ±1) ──────────────
        if (isset($casos_set['caso6a'])) {
            $c6a = $this->c6->detectar(
                $fi_mov,
                $ff_mov,
                $fi_stock,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $c6_proveedores,
                $c6_lead_time,
                $c6_nivel_serv,
                $min_ventas_c5,
                $modelo_rotura_c5,
                $umbral_confianza_c5,
                $binomial_sigma_mult,
                $incluir_albcli,
                $fi_stats,
                $ff_stats,
                'Agotamiento Estimado',
                '',
                $umbral_rop_mult,
                $umbral_stock_neg
            );
            if (isset($c6a['error'])) return $c6a;
            $incidencias = array_merge($incidencias, $c6a);
        }

        // ── C6b — ROP operacional (ventana histórica fija anclada en hoy) ───────
        // A diferencia de C6a (ligada al periodo analizado), C6b siempre mide
        // la demanda de los últimos N días desde hoy. Así:
        //   · El stock reflejado es el real actual (no rebobinado a ff_mov).
        //   · La muestra estadística corresponde a la demanda reciente real.
        //   · Productos de temporada no generan falsas alarmas fuera de su época.
        // Resultado: responde directamente a "¿debo pedir hoy?".
        //
        // IMPORTANTE: C6b resuelve sus propios IDs de artículo sin filtro de estado,
        // independientemente de cómo se haya llamado (batch, lote único o directo).
        // Un artículo con estado='Tarifa' o cualquier otro estado distinto de 'Activo'
        // en articulosProveedores puede seguir en stock y vendiendo; C6b debe evaluarlo.
        if (isset($casos_set['caso6b'])) {
            $c6b_dias    = (int)($params['c6b_dias_historico'] ?? 90);
            $ff_hoy      = date('Y-m-d');
            $anio_hoy    = (int)substr($ff_hoy, 0, 4);
            $fi_stats_6b = max(
                date('Y-m-d', strtotime("$ff_hoy -{$c6b_dias} days")),
                "{$anio_hoy}-01-01"   // límite BD anualizada; resoluble con mod_api
            );
            // min_ventas propio de C6b: anclado a su ventana temporal, no al periodo analizado.
            // Evita que un min_ventas_c5 alto (ej. 30 en anual) elimine productos válidos
            // que en 90 días solo registran 10-15 días con venta.
            $min_ventas_c6b = (int)($params['min_ventas_c6b'] ?? max(5, min(20, (int)round($c6b_dias * 0.15))));
            // Resolver IDs para C6b directamente, sin filtro de estado en articulosProveedores.
            // No se reutiliza ids_proveedor_filter (que sí filtra estado='Activo') ni se delega
            // en getIncidenciasBatch, ya que esa resolución ocurre antes de llegar aquí.
            // IDs activos del proveedor (estado='Activo'): para marcar proveedor_es_principal en cada incidencia.
            $ids_activos_c6b = array_flip($ids_proveedor_filter ?: []);
            if (!empty($proveedores_incluir)) {
                $ids_str_prov_c6b = implode(',', array_map('intval', $proveedores_incluir));
                $rows_c6b = $this->repo->queryIdsArticulosByProveedoresTodos($ids_str_prov_c6b);
                $ids_c6b  = isset($rows_c6b['error']) ? $ids_proveedor_filter : array_column($rows_c6b, 'idArticulo');
                // ids_proveedor_filter ya contiene solo estado='Activo' (resuelto al inicio de getIncidencias)
                $ids_activos_c6b = array_flip($ids_proveedor_filter ?: []);
            } else {
                $ids_c6b = $ids_proveedor_filter;  // sin filtro de proveedor: comportamiento normal
            }
            $c6b = $this->c6->detectar(
                $ff_hoy,
                $ff_hoy,
                $fi_stock,
                $familias_incluir,
                $familias_excluir,
                $ids_c6b,
                $c6_proveedores,
                $c6_lead_time,
                $c6_nivel_serv,
                $min_ventas_c6b,
                $modelo_rotura_c5,
                $umbral_confianza_c5,
                $binomial_sigma_mult,
                $incluir_albcli,
                $fi_stats_6b,
                $ff_hoy,
                'Punto de Pedido',
                $ff_hoy,
                $umbral_rop_mult,
                $umbral_stock_neg
            );
            if (isset($c6b['error'])) return $c6b;
            // Marcar si el proveedor seleccionado es el proveedor principal (estado='Activo') de cada artículo
            foreach ($c6b as &$inc_c6b) {
                $inc_c6b['proveedor_es_principal'] = isset($ids_activos_c6b[$inc_c6b['idArticulo']]);
            }
            unset($inc_c6b);
            $incidencias = array_merge($incidencias, $c6b);
        }

        // ── C7a / C7b — Offset sistemático de inventario ────────────────────
        // Se ejecutan con una sola pasada SQL si ambos están activos.
        $c7_subcasos = [];
        if (isset($casos_set['caso7a'])) $c7_subcasos[] = 'C7a';
        if (isset($casos_set['caso7b'])) $c7_subcasos[] = 'C7b';
        if (!empty($c7_subcasos)) {
            $c7 = $this->getIncidenciasC7(
                $fi_mov,
                $ff_mov,
                $fi_stock,
                $ff_stock,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $sb_shared,
                $c7_subcasos,
                $c7b_min_recepciones,
                $c7b_umbral_cv,
                $c7b_umbral_cv_peso,
                $c7b_umbral_iqr_peso,
                $c7b_umbral_ruido_peso,
                $c7b_umbral_severidad_unidad,
                $c7b_umbral_severidad_peso,
                $c7b_cascada_exhaustiva,
                (bool)($params['skip_c7_cde'] ?? false),
                $c7a_umbral_delta_unidad,
                $c7a_umbral_delta_peso,
                $c7a_umbral_pvalue,
                $c7a_umbral_pvalue_alta,
                $c7a_umbral_alta_delta_unidad,
                $c7a_umbral_alta_delta_peso,
                $c7a_umbral_alta_slope_unidad,
                $c7a_umbral_alta_slope_peso,
                $c7a_umbral_snr,
                $c7a_cascada_exhaustiva
            );
            if (isset($c7['error'])) return $c7;
            $incidencias = array_merge($incidencias, $c7);
        }

        // ── C9 — Merma por backstaging LIFO inverso ──────────────────────────
        if (isset($casos_set['caso9'])) {
            $c9 = $this->c9->detectar(
                $fi_mov,
                $ff_mov,
                $fi_stock,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $sb_shared,
                (int)  ($params['c9_profundidad_k']     ?? 4),
                (float)($params['c9_beta']              ?? 0.15),
                (float)($params['c9_lambda']            ?? 1.5),
                (float)($params['c9_epsilon']           ?? 1.0),
                (int)  ($params['c9_min_recepciones']   ?? 3),
                (float)($params['c9_umbral_merma_unidad'] ?? 2.0),
                (float)($params['c9_umbral_merma_peso']   ?? 1.0),
                (int)  ($params['c9_dias_post']         ?? 60)
            );
            if (isset($c9['error'])) return $c9;
            $incidencias = array_merge($incidencias, $c9);
        }

        // ── C4 — solo si solicitado explícitamente (no paginable por actividad) ─
        if (isset($casos_set['caso4'])) {
            $articulos_sin_mov = $this->c4->detectar(
                $fi_mov,
                $ff_mov,
                $familias_incluir,
                $familias_excluir,
                $c5_incluir_stock_negativo
            );
            if (isset($articulos_sin_mov['error'])) return $articulos_sin_mov;
            foreach ($this->c4->formatearIncidencias($articulos_sin_mov) as $inc) {
                $incidencias[] = $inc;
            }
        }

        // ── Marcar stock_no_fiable en C5/C6 cuando el artículo tiene C1a activo ──
        if (!empty($ids_con_c1a)) {
            foreach ($incidencias as &$inc) {
                if (
                    isset($ids_con_c1a[$inc['idArticulo']]) &&
                    in_array($inc['tipo'], ['Rotura de Stock', 'Agotamiento Estimado', 'Punto de Pedido'], true)
                ) {
                    $inc['stock_no_fiable'] = true;
                }
            }
            unset($inc);
        }

        // ── Ordenar: CRITICA → ALTA → MEDIA (C2→C5→C3a) → BAJA (C3b sin-rot→C3b nunca→C4) ─
        $orden_sev = ['CRITICA' => 0, 'ALTA' => 1, 'MEDIA' => 2, 'BAJA' => 3];

        $orden_tipo_media = [
            'Entrada con stock alto'             => 0,  // C2
            'Venta Cero (Posible Rotura Física)' => 1,  // C5
            'Agotamiento Estimado'               => 2,  // C6a MEDIA (BN, stock suficiente)
            'Punto de Pedido'                    => 2,  // C6b MEDIA (mismo nivel que C6a)
            'Riesgo de caducidad teórica'        => 3,  // C3a
            'Entrada no registrada'              => 4,  // C7b
            'Merma acumulada'                    => 4,  // C7a
            'Merma backstaging'                  => 4,  // C9
        ];

        // C3b con ultima_salida (Sin rotación) antes que sin ultima_salida (Nunca salidas)
        $subtipo_baja = static function (array $inc): int {
            if ($inc['tipo'] === 'Entrada sin rotación previa') {
                return (isset($inc['ultima_salida']) && $inc['ultima_salida'] !== null) ? 0 : 1;
            }
            return 2; // 'Stock Inactivo en Periodo' (C4)
        };

        usort($incidencias, static function ($a, $b) use ($orden_sev, $orden_tipo_media, $subtipo_baja) {
            $cmp = ($orden_sev[$a['severidad']] ?? 99) <=> ($orden_sev[$b['severidad']] ?? 99);
            if ($cmp !== 0) return $cmp;

            // Dentro del mismo nivel de severidad, ordenación específica por tipo
            if ($a['severidad'] === 'MEDIA') {
                $cmp = ($orden_tipo_media[$a['tipo']] ?? 99) <=> ($orden_tipo_media[$b['tipo']] ?? 99);
                if ($cmp !== 0) return $cmp;
            }
            if ($a['severidad'] === 'BAJA') {
                $cmp = $subtipo_baja($a) <=> $subtipo_baja($b);
                if ($cmp !== 0) return $cmp;
            }

            return 0;
        });

        // Añadir orden_clave lexicográfica para que el frontend reordene entre lotes
        // sin duplicar la lógica de negocio de la ordenación.
        $orden_tipo_media_clave = [
            'Entrada con stock alto'             => '0',
            'Venta Cero (Posible Rotura Física)' => '1',
            'Agotamiento Estimado'               => '2',  // C6a
            'Punto de Pedido'                    => '2',  // C6b
            'Riesgo de caducidad teórica'        => '3',
            'Entrada no registrada'              => '4',  // C7b
            'Merma acumulada'                    => '4',  // C7a
        ];

        // C5: pre-calcular la fecha de inicio de rotura más reciente por artículo
        // para ordenar los grupos de producto de más reciente a más antiguo.
        $c5_tipo = 'Venta Cero (Posible Rotura Física)';
        $max_fecha_c5 = [];
        foreach ($incidencias as $inc) {
            if ($inc['tipo'] === $c5_tipo) {
                $id = $inc['idArticulo'];
                $f  = $inc['fecha_inicio_rotura'] ?? '0000-00-00';
                if (!isset($max_fecha_c5[$id]) || $f > $max_fecha_c5[$id]) {
                    $max_fecha_c5[$id] = $f;
                }
            }
        }

        foreach ($incidencias as &$inc) {
            $sev_idx = $orden_sev[$inc['severidad']] ?? 9;
            if ($inc['tipo'] === $c5_tipo) {
                // Agrupar por artículo ordenando grupos por fecha de última rotura (asc: más antigua primero).
                // Dentro de cada artículo: orden cronológico de fecha_inicio_rotura (asc).
                $max_f = $max_fecha_c5[$inc['idArticulo']] ?? '0000-00-00';
                $fi_r  = $inc['fecha_inicio_rotura'] ?? '0000-00-00';
                $inc['orden_clave'] = $sev_idx . '1' . $max_f . sprintf('%08d', $inc['idArticulo']) . $fi_r;
            } elseif ($inc['tipo'] === 'Agotamiento Estimado' || $inc['tipo'] === 'Punto de Pedido') {
                // C6a/C6b CRITICA/ALTA: ordenar por dias_autonomia ascendente (más urgente primero).
                // C6a/C6b MEDIA: se ordena por tipo dentro del bloque MEDIA (ya cubierto por orden_tipo_media).
                // C6b (Punto de Pedido) se desplaza un sub-nivel respecto a C6a dentro de la misma severidad.
                $dias_pad = str_pad((int)($inc['dias_autonomia'] * 10), 8, '0', STR_PAD_LEFT);
                $c6b_shift = ($inc['tipo'] === 'Punto de Pedido') ? '1' : '0';
                $sub       = ($inc['severidad'] === 'MEDIA') ? '2' : '0';
                $inc['orden_clave'] = $sev_idx . $sub . $c6b_shift . $dias_pad . sprintf('%08d', $inc['idArticulo']);
            } elseif ($inc['severidad'] === 'MEDIA') {
                $sub = $orden_tipo_media_clave[$inc['tipo']] ?? '9';
                $inc['orden_clave'] = $sev_idx . $sub . sprintf('%08d', $inc['idArticulo']);
            } elseif ($inc['severidad'] === 'BAJA') {
                if ($inc['tipo'] === 'Entrada sin rotación previa') {
                    $sub = (isset($inc['ultima_salida']) && $inc['ultima_salida'] !== null) ? '0' : '1';
                } else {
                    $sub = '2'; // Stock Inactivo en Periodo (C4)
                }
                $inc['orden_clave'] = $sev_idx . $sub . sprintf('%08d', $inc['idArticulo']);
            } elseif ($inc['tipo'] === 'Inventario en negativo') {
                // C1a: stock_actual desc (más negativo primero), desempate por días en negativo desc
                $inv_stock = str_pad(max(0, 9999999999 - (int)(abs((float)($inc['stock_actual'] ?? 0)) * 100)), 10, '0', STR_PAD_LEFT);
                $inv_dias  = str_pad(max(0, 9999 - (int)($inc['dias_en_negativo'] ?? 0)), 4, '0', STR_PAD_LEFT);
                $inc['orden_clave'] = $sev_idx . '0' . $inv_stock . $inv_dias;
            } elseif ($inc['tipo'] === 'Desajuste Puntual de Stock') {
                // C1b: abs(min_balance) desc — el mínimo más profundo primero
                $inv_min = str_pad(max(0, 9999999999 - (int)(abs((float)($inc['min_balance'] ?? 0)) * 100)), 10, '0', STR_PAD_LEFT);
                $inc['orden_clave'] = $sev_idx . '0' . $inv_min;
            } elseif (in_array($inc['c7_subcaso'] ?? '', ['C7a', 'C7a_posible'], true)) {
                // C7a: coste_estimado_merma desc → delta_acumulado desc → tendencia desc
                $coste_inv  = str_pad(max(0, 9999999 - (int)(abs((float)($inc['coste_estimado_merma'] ?? 0)) * 100)), 7, '0', STR_PAD_LEFT);
                $delta_inv  = str_pad(max(0, 99999 - (int)(abs((float)($inc['delta_acumulado'] ?? 0)) * 10)), 5, '0', STR_PAD_LEFT);
                $slope_inv  = str_pad(max(0, 9999 - (int)(abs((float)($inc['tendencia'] ?? 0)) * 10)), 4, '0', STR_PAD_LEFT);
                $coste_null = ($inc['coste_estimado_merma'] ?? null) === null ? '1' : '0';
                $inc['orden_clave'] = $sev_idx . '0' . $coste_null . $coste_inv . $delta_inv . $slope_inv;
            } elseif (in_array($inc['c7_subcaso'] ?? '', ['C7b', 'C7b_posible', 'C7b_ruido_peso'], true)) {
                // C7b: coste_estimado desc → n_recepciones desc → déficit abs desc
                $coste_inv = str_pad(max(0, 9999999 - (int)(abs((float)($inc['coste_estimado'] ?? 0)) * 100)), 7, '0', STR_PAD_LEFT);
                $rec_inv   = str_pad(max(0, 9999 - (int)($inc['n_recepciones'] ?? 0)), 4, '0', STR_PAD_LEFT);
                $def_inv   = str_pad(max(0, 99999 - (int)(abs((float)($inc['offset_estimado'] ?? 0)) * 10)), 5, '0', STR_PAD_LEFT);
                // Nulls de coste al final
                $coste_null = ($inc['coste_estimado'] ?? null) === null ? '1' : '0';
                $inc['orden_clave'] = $sev_idx . '0' . $coste_null . $coste_inv . $rec_inv . $def_inv;
            } elseif ($inc['tipo'] === 'Merma backstaging') {
                // C9: pct_merma desc → merma_total_kg desc
                $pct_inv   = str_pad(max(0, 99999 - (int)(abs((float)($inc['pct_merma']     ?? 0)) * 100)), 5, '0', STR_PAD_LEFT);
                $merma_inv = str_pad(max(0, 9999999 - (int)(abs((float)($inc['merma_total_kg'] ?? 0)) * 100)), 7, '0', STR_PAD_LEFT);
                $inc['orden_clave'] = $sev_idx . '0' . $pct_inv . $merma_inv;
            } else {
                $inc['orden_clave'] = $sev_idx . '0' . sprintf('%08d', $inc['idArticulo']);
            }
        }
        unset($inc);

        return $this->repo->anadirNombres($incidencias);
    }

    /**
     * C7 — Offset sistemático de inventario.
     *
     * Detecta artículos cuyo suelo de stock post-recepción sigue un patrón consistente
     * a través de 3 o más recepciones en el periodo:
     *
     *  · C7b — Offset negativo sistemático: el suelo se estabiliza en un nivel negativo
     *    y estable (σ/|μ| < 0.5). Señal: el sistema lleva N unidades "de más" registradas
     *    que no existen físicamente (inventario inicial erróneo o devoluciones no descontadas).
     *
     *  · C7a — Merma sistemática no registrada: el suelo sube acumulativamente de recepción
     *    en recepción (pendiente positiva ≥ 0.5 ud/recepción, acumulado ≥ 2 ud.).
     *    Señal: merma/caducidad no registrada o ventas no escaneadas sistemáticamente.
     *
     * Algoritmo:
     *  1. Obtener fechas de recepción por artículo; descartar los que tienen < 3.
     *  2. Construir el timeline de stock (saldo_base + Σ deltas diarios) para los candidatos.
     *  3. Para cada intervalo [recepción_i, recepción_{i+1}) calcular el mínimo de stock.
     *  4. Analizar estadísticamente el array de suelos: media, desviación típica, pendiente
     *     de regresión lineal. Clasificar según los umbrales definidos.
     *
     * @param string $fi_mov
     * @param string $ff_mov
     * @param string $fi_stock        Inicio ventana stock base (Jan 1 del año del periodo)
     * @param string $ff_stock        Fin ventana stock base (día anterior al inicio del periodo)
     * @param array  $familias_incluir
     * @param array  $familias_excluir
     * @param array  $ids_filter
     * @param array  $stock_base_cache  Pre-calculado por getIncidencias si C1/C2 también activos
     *
     * @return array  Incidencias detectadas (sin campo 'nombre'; lo añade getIncidencias)
     */
    private function getIncidenciasC7(
        string $fi_mov,
        string $ff_mov,
        string $fi_stock,
        string $ff_stock,
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter            = [],
        array  $stock_base_cache      = [],
        array  $subcasos              = ['C7a', 'C7b'],
        int    $c7b_min_recepciones    = 3,
        float  $c7b_umbral_cv          = 0.5,
        float  $c7b_umbral_cv_peso     = 0.75,
        float  $c7b_umbral_iqr_peso    = 2.0,
        float  $c7b_umbral_ruido_peso  = 0.5,
        int    $c7b_umbral_sev_unidad  = 5,
        float  $c7b_umbral_sev_peso    = 2.5,
        bool   $c7b_cascada_exhaustiva = false,
        bool   $skip_cde              = false,   // true en lotes parciales; C7c/d/e requiere el conjunto completo
        float  $c7a_umbral_delta_unidad     = 2.0,
        float  $c7a_umbral_delta_peso       = 1.0,
        float  $c7a_umbral_pvalue           = 0.10,
        float  $c7a_umbral_pvalue_alta      = 0.05,
        float  $c7a_umbral_alta_delta_unidad = 10.0,
        float  $c7a_umbral_alta_delta_peso  = 5.0,
        float  $c7a_umbral_alta_slope_unidad = 2.0,
        float  $c7a_umbral_alta_slope_peso  = 1.0,
        float  $c7a_umbral_snr              = 0.15,
        bool   $c7a_cascada_exhaustiva      = false
    ): array {
        $subcasos_set    = array_flip($subcasos);
        $min_recepciones = 2;   // mínimo global para C7b_posible (2 floors análisis); el test IC95 requiere $c7b_min_recepciones

        $fi     = $this->db->real_escape_string($fi_mov);
        $ff     = $this->db->real_escape_string($ff_mov);
        $fi_stk = $this->db->real_escape_string($fi_stock);  // inicio ventana extendida (1-Ene)
        $wf     = $this->repo->familiaWhere($familias_incluir, $familias_excluir);
        $wi     = $this->repo->idsWhere($ids_filter);

        // ── Paso 1: recepciones en ventana extendida fi_stock→ff_mov ─────────
        // Se amplía el rango al periodo base (fi_stock→fi_mov-1) para disponer de
        // más floors históricos y poder confirmar el patrón antes del análisis.
        $rows_rec = $this->repo->queryRecepcionesFechasC7($fi_stk, $ff, $wf, $wi);
        if (isset($rows_rec['error'])) return $rows_rec;
        if (empty($rows_rec)) return [];

        // Agrupar por artículo; filtrar los que tienen ≥ $min_recepciones fechas distintas
        $recepciones_map = [];
        $cantidades_map  = [];   // [idArticulo][fecha] = cantidad recibida ese día
        foreach ($rows_rec as $r) {
            $aid = (int)$r['idArticulo'];
            $recepciones_map[$aid][]             = $r['fecha'];
            $cantidades_map[$aid][$r['fecha']]   = (float)$r['cantidad'];
        }

        $candidatos_ids = [];
        foreach ($recepciones_map as $id => $fechas) {
            $fechas_unicas = array_values(array_unique($fechas));
            sort($fechas_unicas);
            if (count($fechas_unicas) >= $min_recepciones) {
                $recepciones_map[$id] = $fechas_unicas;
                $candidatos_ids[]     = $id;
            } else {
                unset($recepciones_map[$id]);
            }
        }

        if (empty($candidatos_ids)) return [];

        // ── Paso 2: timeline de movimientos para los candidatos ──────────────
        $ids_str       = implode(',', array_map('intval', $candidatos_ids));

        // Tipo de artículo por ID (unidad | peso) — necesario para C7b-001
        // Umbrales de severidad diferenciados: peso tiene mayor tolerancia al ruido
        $tipos_map = [];
        $smt_tipo = $this->db->query(
            "SELECT idArticulo, tipo FROM articulos WHERE idArticulo IN ($ids_str)"
        );
        if ($smt_tipo) {
            while ($r = $smt_tipo->fetch_assoc()) {
                $tipos_map[(int)$r['idArticulo']] = (string)$r['tipo'];
            }
        }
        // ── Paso 2: timeline fi_stock→ff_mov (ventana extendida) ────────────
        // El sistema es anual (fi_stock = 1-Ene): stock(d) = Σ deltas(fi_stock→d).
        // Matemáticamente equivale al enfoque anterior (saldo_base + Σ deltas(fi_mov→d))
        // pero permite calcular floors también en el periodo base previo a fi_mov.
        // $stock_base_cache se conserva en la firma por compatibilidad pero no se usa.
        $rows_timeline = $this->repo->queryTimelineMovimientosC7($fi_stk, $ff, $ids_str);
        if (isset($rows_timeline['error'])) return $rows_timeline;

        // Indexar delta diario por [idArticulo][fecha]
        $daily_map = [];
        foreach ($rows_timeline as $r) {
            $daily_map[(int)$r['idArticulo']][$r['fecha']] = (float)$r['day_delta'];
        }

        // ── Artículos con actividad albcli en el período (C7a-025) ───────────
        // Set idArticulo → true para los que tienen al menos un albarán de cliente.
        // Una salida inter-tienda no registrada inflaría el floor artificialmente.
        $has_albcli_ids = $this->repo->queryHasAlbcliC7($fi_stk, $ff, $ids_str);
        if (isset($has_albcli_ids['error'])) $has_albcli_ids = []; // no bloquear si falla

        // ── Paso 3: calcular suelos inter-recepción y detectar patrón ────────
        $incidencias     = [];
        $ping_cada_n     = 20;   // hacer ping a MySQL cada N artículos para evitar wait_timeout
        $ping_contador   = 0;

        foreach ($candidatos_ids as $id) {
            // Keepalive: evitar que MySQL cierre la conexión durante el cálculo estadístico.
            // El bootstrap O(n²) puede tardar segundos por artículo; con muchos artículos
            // el tiempo total supera el wait_timeout del servidor.
            if (++$ping_contador % $ping_cada_n === 0) {
                try {
                    $this->db->ping();
                } catch (\mysqli_sql_exception $e) { /* ignorar */
                }
            }
            $fechas_rec = $recepciones_map[$id];
            $daily      = $daily_map[$id] ?? [];
            $n_rec      = count($fechas_rec);

            // Construir stock acumulado por fecha en la ventana extendida fi_stock→ff_mov.
            // stock(d) = Σ day_delta desde fi_stock hasta d (parte de 0 al inicio del año).
            $cum_delta     = 0.0;
            $stock_by_date = [];
            $all_dates     = array_keys($daily);
            sort($all_dates);
            foreach ($all_dates as $d) {
                $cum_delta         += $daily[$d];
                $stock_by_date[$d]  = $cum_delta;
            }

            if (empty($stock_by_date)) continue;   // sin movimientos registrados

            // Calcular suelo (mínimo de stock) para cada intervalo inter-recepción.
            // El intervalo i cubre [fecha_rec_i, fecha_rec_{i+1}), es decir:
            //   · incluye el día de la recepción i (post-recepción)
            //   · excluye el día de la recepción i+1 (se calcula en el intervalo i+1)
            // El último intervalo va hasta ff_mov (fin del periodo).
            //
            // C7b-002: se registra también la duración (días) de cada intervalo para
            // normalizar los floors antes del análisis estadístico.  Intervalos más
            // largos producen floors más negativos por simple acumulación de demanda;
            // sin normalización la media se sesga hacia los períodos de entregas espaciadas.
            $floors          = [];
            $dias_intervalos = [];
            $fechas_floors   = [];   // fecha de recepción correspondiente a cada floor
            for ($i = 0; $i < $n_rec; $i++) {
                $fecha_ini      = $fechas_rec[$i];
                $fecha_fin      = ($i + 1 < $n_rec) ? $fechas_rec[$i + 1] : null; // null = hasta ff
                $fecha_fin_real = $fecha_fin ?? $ff_mov;
                $dias_intervalo = max(1, (int)(
                    (strtotime($fecha_fin_real) - strtotime($fecha_ini)) / 86400
                ));

                $min_floor = null;
                foreach ($stock_by_date as $d => $stock) {
                    if ($d < $fecha_ini) continue;
                    if ($fecha_fin !== null && $d >= $fecha_fin) continue;
                    if ($min_floor === null || $stock < $min_floor) {
                        $min_floor = $stock;
                    }
                }

                if ($min_floor !== null) {
                    $floors[]          = $min_floor;
                    $dias_intervalos[] = $dias_intervalo;
                    $fechas_floors[]   = $fecha_ini;
                }
            }

            $n_floors = count($floors);

            if ($n_floors < $min_recepciones) continue;   // suelos insuficientes

            // Mapa fecha_recepción → suelo (para la validación con periodo común en C7c)
            $floors_map = [];
            for ($i = 0; $i < $n_floors; $i++) {
                $floors_map[$fechas_floors[$i]] = $floors[$i];
            }

            // ── Split base / análisis ─────────────────────────────────────────
            // Periodo base:     [fi_stock, fi_mov)  — histórico anual previo al análisis.
            // Periodo análisis: [fi_mov,   ff_mov]  — ventana de análisis solicitada.
            // Prioridad del test IC95: base (más largo, libre del evento analizado);
            // si base < 3 floors se usa el periodo de análisis.
            $floors_base     = [];
            $dias_base     = [];
            $floors_analysis = [];
            $dias_analysis = [];
            foreach ($fechas_floors as $idx => $fd) {
                if ($fd < $fi_mov) {
                    $floors_base[]    = $floors[$idx];
                    $dias_base[]      = $dias_intervalos[$idx];
                } else {
                    $floors_analysis[] = $floors[$idx];
                    $dias_analysis[]   = $dias_intervalos[$idx];
                }
            }
            $n_base     = count($floors_base);
            $n_analysis = count($floors_analysis);

            $tipo_art     = $tipos_map[$id] ?? 'unidad';
            $mean_raw     = array_sum($floors) / $n_floors;
            $abs_mean_raw = abs($mean_raw);

            // Regresión lineal sobre todos los floors (usada por C7a)
            // C7a-001: normalizar por duración del intervalo (igual que C7b-002) para que
            // la pendiente no mezcle señal real con efecto de escala del intervalo.
            $dias_intervalo_medio_all = array_sum($dias_intervalos) / $n_floors;
            $floors_norm_all = [];
            for ($i = 0; $i < $n_floors; $i++) {
                $floors_norm_all[] = $floors[$i] / $dias_intervalos[$i];
            }
            $reg_norm        = PosstockStatistics::regressionStats($floors_norm_all);
            $slope_norm      = $reg_norm['slope'];
            // tendencia_visible se calcula más abajo usando Theil-Sen (C7a-019/C7a-012)

            // C7a-011 + C7a-019: preparar r₁ y se_corr para la cascada estadística.
            // r₁ se usa en Nivel 1 (corrección Hamed & Rao de Mann-Kendall)
            // y en Nivel 4 (IC95 Newey-West como último recurso).
            $autocorr_lag1_c7a = 0.0;
            $se_corr_c7a       = $reg_norm['se'];   // se sin corregir; se actualiza si r₁ > 0
            if ($n_floors >= 4 && $reg_norm['se'] > 0.0) {
                $mean_norm_all = array_sum($floors_norm_all) / $n_floors;
                $cov_lag1_c7a  = 0.0;
                $var_norm_all  = 0.0;
                for ($i = 1; $i < $n_floors; $i++) {
                    $cov_lag1_c7a += ($floors_norm_all[$i] - $mean_norm_all) * ($floors_norm_all[$i - 1] - $mean_norm_all);
                }
                foreach ($floors_norm_all as $fnv) {
                    $var_norm_all += ($fnv - $mean_norm_all) ** 2;
                }
                $cov_lag1_c7a /= ($n_floors - 1);
                $var_norm_all /= ($n_floors - 1);
                if ($var_norm_all > 1e-12) {
                    $r1_c7a = max(-1.0, min(1.0, $cov_lag1_c7a / $var_norm_all));
                    if ($r1_c7a > 0.0) {
                        $autocorr_lag1_c7a = $r1_c7a;
                        $se_corr_c7a       = $reg_norm['se'] * sqrt(1.0 + 2.0 * $r1_c7a);
                    }
                }
            }

            // mantener reg/slope brutos por si otras partes los usan
            $reg   = PosstockStatistics::regressionStats($floors);
            $slope = $reg['slope'];

            // ── Tabla t_{df, 0.975} ──────────────────────────────────────────
            static $t_975_tab = [
                1 => 12.706,
                2 => 4.303,
                3 => 3.182,
                4 => 2.776,
                5 => 2.571,
                6 => 2.447,
                7 => 2.365,
                8 => 2.306,
                9 => 2.262,
                10 => 2.228,
                15 => 2.131,
                20 => 2.086,
                30 => 2.042,
                60 => 2.000,
                120 => 1.980,
            ];

            // ── Selección del conjunto de floors para el test IC95 ───────────
            if ($n_base >= $c7b_min_recepciones) {
                $test_floors = $floors_base;
                $test_dias   = $dias_base;
                $test_period = 'base';
            } elseif ($n_analysis >= $c7b_min_recepciones) {
                $test_floors = $floors_analysis;
                $test_dias   = $dias_analysis;
                $test_period = 'analysis';
            } else {
                $test_floors = null;
                $test_period = null;
            }

            // C7a — merma sistemática no registrada (suelos positivos en alza)
            // C7a-019: cascada de 5 niveles sobre floors normalizados.
            //
            // Pre-filtros globales:
            //   · mean_raw ≥ 0: suelos positivos en media (negativos → C7b)
            //   · delta_total ≥ umbral: variación acumulada mínima observable (configurable por tipo)
            //
            // NOTA: este bloque se ejecuta ANTES del bloque C7b para evitar que los
            // continue() internos de C7b (que descartan artículos no-C7b) salten
            // también C7a. C7a y C7b son mutuamente excluyentes por diseño:
            // C7a requiere mean_raw ≥ 0, C7b trabaja con floors negativos.
            // Nivel 5 (n_floors == 2): eliminado (C7a-020).
            // Niveles 1–4: requieren n_floors ≥ 3 y β_TS > 0.
            $delta_total = $floors[$n_floors - 1] - $floors[0];
            $c7a_umbral_delta = ($tipo_art === 'peso') ? $c7a_umbral_delta_peso : $c7a_umbral_delta_unidad;
            if (isset($subcasos_set['C7a']) && $mean_raw >= 0 && $delta_total >= $c7a_umbral_delta) {

                // ── Theil-Sen slope sobre floors_norm_all (C7a-012) ──────────
                // Estimador usado por la cascada estadística (test OLS, Bootstrap, MK).
                // Opera sobre floors normalizados (ud/día) para que la pendiente no se vea
                // afectada por la duración variable de los intervalos entre recepciones.
                // O(n²) — negligible para n típico 3-15.
                $ts_pairs = [];
                for ($i = 0; $i < $n_floors; $i++) {
                    for ($j = $i + 1; $j < $n_floors; $j++) {
                        $ts_pairs[] = ($floors_norm_all[$j] - $floors_norm_all[$i]) / ($j - $i);
                    }
                }
                sort($ts_pairs);
                $n_ts    = count($ts_pairs);
                $beta_ts = $n_ts > 0
                    ? ($n_ts % 2 === 1
                        ? $ts_pairs[intdiv($n_ts, 2)]
                        : ($ts_pairs[$n_ts / 2 - 1] + $ts_pairs[$n_ts / 2]) / 2.0)
                    : 0.0;

                // ── Theil-Sen sobre floors BRUTOS para el campo de visualización ──
                // Mide directamente la variación de suelo entre recepciones consecutivas
                // en las unidades originales (ud. o kg por paso de recepción).
                // NO se usa en la cascada estadística — solo para mostrar al usuario.
                $ts_pairs_raw = [];
                for ($i = 0; $i < $n_floors; $i++) {
                    for ($j = $i + 1; $j < $n_floors; $j++) {
                        $ts_pairs_raw[] = ($floors[$j] - $floors[$i]) / ($j - $i);
                    }
                }
                sort($ts_pairs_raw);
                $n_ts_raw         = count($ts_pairs_raw);
                $beta_ts_raw      = $n_ts_raw > 0
                    ? ($n_ts_raw % 2 === 1
                        ? $ts_pairs_raw[intdiv($n_ts_raw, 2)]
                        : ($ts_pairs_raw[$n_ts_raw / 2 - 1] + $ts_pairs_raw[$n_ts_raw / 2]) / 2.0)
                    : 0.0;
                $tendencia_visible = $beta_ts_raw;   // ud. o kg por recepción, sin conversión

                // dispersión bruta para el campo 'dispersion' del array de salida
                $variance_raw_c7a = 0.0;
                foreach ($floors as $f) {
                    $variance_raw_c7a += ($f - $mean_raw) ** 2;
                }
                $std_dev_raw_c7a = $n_floors > 1 ? sqrt($variance_raw_c7a / ($n_floors - 1)) : 0.0;

                $snr_c7a = ($std_dev_raw_c7a > 0.0)
                    ? abs($tendencia_visible) / $std_dev_raw_c7a
                    : PHP_FLOAT_MAX;

                // Nivel 5 eliminado (C7a-020): con n=2 floors positivos no hay test estadístico
                // válido porque el sobrestock es el estado normal del inventario. Dos floors
                // positivos en alza son compatibles con la variabilidad habitual del negocio.
                // La asimetría con C7b (donde n=2 negativos SÍ es evidencia) es intencional:
                // un floor negativo viola la hipótesis nula por definición; uno positivo no.
                if ($n_floors >= 3 && $beta_ts > 0.0) {
                    // Pre-filtro de dirección: β_TS > 0 (señal de alza)

                    $c7a_confianza        = null;
                    $c7a_cascade_nivel    = 0;
                    $p_mk_c7a             = null;
                    $c7a_fallback_reason  = '';
                    $cascade_nivel_act    = 1;

                    while ($cascade_nivel_act > 0 && $c7a_confianza === null) {

                        // ═══════════════════════════════════════════════════════
                        // NIVEL 1 · OLS + NEWEY-WEST IC95 (C7a-019a)
                        // Mayor potencia para floors aproximadamente normales con
                        // tendencia lineal (merma acumulada = δ constante/recepción).
                        // Gauss-Markov: BLUE bajo normalidad. NW corrige autocorr.
                        // y heterocedasticidad. CLT aplica para n ≥ 10.
                        // ═══════════════════════════════════════════════════════
                        if ($cascade_nivel_act === 1) {
                            if ($n_floors < 10 || $se_corr_c7a <= 0.0) {
                                // n < 10: pocos gl para NW; CLT no garantiza normalidad asintótica.
                                // se_corr = 0: OLS degenerado (datos colineales o constantes).
                                $c7a_fallback_reason = $n_floors < 10 ? 'n<10_skip_ols' : 'se_invalido_skip_ols';
                                $cascade_nivel_act = 2;
                            } else {
                                static $t_tab_nw = [
                                    1 => 6.314,
                                    2 => 2.920,
                                    3 => 2.353,
                                    4 => 2.132,
                                    5 => 2.015,
                                    6 => 1.943,
                                    7 => 1.895,
                                    8 => 1.860,
                                    9 => 1.833,
                                    10 => 1.812,
                                    15 => 1.753,
                                    20 => 1.725,
                                    30 => 1.697,
                                    60 => 1.671,
                                    120 => 1.658,
                                ];
                                $df_nw = max(1, $n_floors - 2);
                                if ($df_nw > 120) {
                                    $t_crit_nw = 1.645;
                                } elseif (isset($t_tab_nw[$df_nw])) {
                                    $t_crit_nw = $t_tab_nw[$df_nw];
                                } else {
                                    $keys_nw = array_keys($t_tab_nw);
                                    $lo_nw = $hi_nw = null;
                                    foreach ($keys_nw as $k) {
                                        if ($k <= $df_nw) $lo_nw = $k;
                                        if ($k >= $df_nw && $hi_nw === null) $hi_nw = $k;
                                    }
                                    $t_crit_nw = ($lo_nw !== null && $hi_nw !== null && $lo_nw !== $hi_nw)
                                        ? $t_tab_nw[$lo_nw] + ($df_nw - $lo_nw) / ($hi_nw - $lo_nw) * ($t_tab_nw[$hi_nw] - $t_tab_nw[$lo_nw])
                                        : ($lo_nw !== null ? $t_tab_nw[$lo_nw] : 6.314);
                                }
                                $ic95_low_nw = $slope_norm - $t_crit_nw * $se_corr_c7a;
                                if ($ic95_low_nw > 0.0) {
                                    $c7a_confianza = 'alta';
                                    $c7a_cascade_nivel = 1;
                                    $cascade_nivel_act = 0;
                                } else {
                                    // IC95 incluye 0 → Bootstrap (robusto a posible no-normalidad)
                                    if ($c7a_cascada_exhaustiva) {
                                        $c7a_fallback_reason = ($c7a_fallback_reason ? $c7a_fallback_reason . '; ' : '')
                                            . 'ols_nw_ns(ic95_low=' . round($ic95_low_nw, 3) . ')';
                                        $cascade_nivel_act = 2;
                                    } else {
                                        $cascade_nivel_act = 0;   // resultado válido negativo: NO C7a
                                    }
                                }
                            }

                            // ═══════════════════════════════════════════════════════
                            // NIVEL 2 · BOOTSTRAP THEIL-SEN IC99 (C7a-019b)
                            // No paramétrico; no asume distribución ni linealidad.
                            // Robusto a outliers. Requiere n ≥ 8 para IC estable.
                            // ═══════════════════════════════════════════════════════
                        } elseif ($cascade_nivel_act === 2) {
                            if ($n_floors < 8) {
                                $c7a_fallback_reason = ($c7a_fallback_reason ? $c7a_fallback_reason . '; ' : '')
                                    . 'n<8_skip_bootstrap';
                                $cascade_nivel_act = 3;
                            } else {
                                // Iteraciones adaptativas: reducir B para n grande ahorra tiempo
                                // sin perder precisión relevante (SE_boot ∝ 1/√B).
                                $B_boot     = $n_floors >= 30 ? 299 : ($n_floors >= 20 ? 499 : 999);
                                $boot_betas = [];
                                for ($b = 0; $b < $B_boot; $b++) {
                                    $idx_b = [];
                                    for ($k = 0; $k < $n_floors; $k++) {
                                        $idx_b[] = mt_rand(0, $n_floors - 1);
                                    }
                                    sort($idx_b);
                                    $f_b = [];
                                    foreach ($idx_b as $ki) {
                                        $f_b[] = $floors_norm_all[$ki];
                                    }
                                    $bp = [];
                                    $nb = count($f_b);
                                    for ($i = 0; $i < $nb; $i++) {
                                        for ($j = $i + 1; $j < $nb; $j++) {
                                            $bp[] = ($f_b[$j] - $f_b[$i]) / ($j - $i);
                                        }
                                    }
                                    if (!empty($bp)) {
                                        sort($bp);
                                        $np = count($bp);
                                        $boot_betas[] = $np % 2 === 1
                                            ? $bp[intdiv($np, 2)]
                                            : ($bp[$np / 2 - 1] + $bp[$np / 2]) / 2.0;
                                    }
                                }
                                if (!empty($boot_betas)) {
                                    sort($boot_betas);
                                    $nb_s     = count($boot_betas);
                                    $ic99_low = $boot_betas[(int)round(0.005 * ($nb_s - 1))];
                                    // detectar colapso de varianza (Tipo B)
                                    $mean_boot = array_sum($boot_betas) / $nb_s;
                                    $var_boot  = 0.0;
                                    foreach ($boot_betas as $bs) {
                                        $var_boot += ($bs - $mean_boot) ** 2;
                                    }
                                    $var_boot /= $nb_s;

                                    if ($var_boot < 1e-12) {
                                        $cascade_nivel_act = 3;   // Tipo B: varianza bootstrap colapsa
                                    } elseif ($ic99_low > 0.0) {
                                        $c7a_confianza = 'media';
                                        $c7a_cascade_nivel = 2;
                                        $cascade_nivel_act = 0;
                                    } else {
                                        if ($c7a_cascada_exhaustiva) {
                                            $c7a_fallback_reason = ($c7a_fallback_reason ? $c7a_fallback_reason . '; ' : '')
                                                . 'bootstrap_ic99_ns(ic99_low=' . round($ic99_low, 3) . ')';
                                            $cascade_nivel_act = 3;
                                        } else {
                                            $cascade_nivel_act = 0;   // resultado válido negativo: NO C7a
                                        }
                                    }
                                } else {
                                    $cascade_nivel_act = 3;       // sin slopes bootstrap → Tipo B
                                }
                            } // end else n >= 8

                            // ═══════════════════════════════════════════════════════
                            // NIVEL 3 · MANN-KENDALL con Hamed & Rao (C7a-019c)
                            // No paramétrico; corrige autocorrelación lag-1.
                            // Válido para n ∈ [4, 20]: fuera de ese rango MK pierde
                            // discriminación (n<4: p_mín=0.333; n>20: z_mk inflado O(n^1.5)).
                            // ═══════════════════════════════════════════════════════
                        } elseif ($cascade_nivel_act === 3) {
                            if ($n_floors < 4 || $n_floors > 20) {
                                $c7a_fallback_reason = ($c7a_fallback_reason ? $c7a_fallback_reason . '; ' : '')
                                    . ($n_floors < 4 ? 'n<4_skip_mk' : 'n>20_mk_overpowered');
                                $cascade_nivel_act = 4;
                            } else {
                                $S_mk = 0;
                                for ($i = 0; $i < $n_floors; $i++) {
                                    for ($j = $i + 1; $j < $n_floors; $j++) {
                                        $d = $floors_norm_all[$j] - $floors_norm_all[$i];
                                        if ($d > 0.0) $S_mk++;
                                        elseif ($d < 0.0) $S_mk--;
                                    }
                                }
                                // Var_MK estándar (sin empates)
                                $var_mk = (float)$n_floors * ($n_floors - 1) * (2 * $n_floors + 5) / 18.0;
                                // Corrección Hamed & Rao lag-1: Var_HR = Var_MK × (1 + 2r₁)
                                if ($autocorr_lag1_c7a > 0.0) {
                                    $var_mk *= (1.0 + 2.0 * $autocorr_lag1_c7a);
                                }
                                if ($var_mk > 0.0) {
                                    $S_adj = $S_mk > 0 ? $S_mk - 1 : ($S_mk < 0 ? $S_mk + 1 : 0);
                                    $z_mk  = $S_adj / sqrt($var_mk);
                                    // Φ(z) — Abramowitz & Stegun 26.2.17
                                    $az     = abs($z_mk);
                                    $t_phi  = 1.0 / (1.0 + 0.2316419 * $az);
                                    $p_tail = 0.3989423 * exp(-$az * $az / 2.0)
                                        * $t_phi * (0.3193815 + $t_phi * (-0.3565638
                                            + $t_phi * (1.7814779 + $t_phi * (-1.8212560
                                                + $t_phi * 1.3302744))));
                                    $p_mk_c7a = min(1.0, max(0.0, 2.0 * $p_tail));
                                } else {
                                    $p_mk_c7a = 1.0;
                                }
                                if ($p_mk_c7a < $c7a_umbral_pvalue_alta) {
                                    $c7a_confianza = 'posible';
                                    $c7a_cascade_nivel = 3;
                                    $cascade_nivel_act = 0;
                                } elseif ($p_mk_c7a >= $c7a_umbral_pvalue) {
                                    if ($c7a_cascada_exhaustiva) {
                                        $c7a_fallback_reason = ($c7a_fallback_reason ? $c7a_fallback_reason . '; ' : '')
                                            . 'mk_ns(p=' . round($p_mk_c7a, 3) . ')';
                                        $cascade_nivel_act = 4;
                                    } else {
                                        $cascade_nivel_act = 0;   // resultado válido negativo: NO C7a
                                    }
                                } else {
                                    $cascade_nivel_act = 4;   // Tipo B: p ∈ [pvalue_alta, pvalue)
                                }
                            } // end else n ∈ [4, 20]

                            // ═══════════════════════════════════════════════════════
                            // NIVEL 4 · TEST DE SIGNO BINOMIAL (C7a-019d)
                            // No paramétrico; prácticamente sin suposiciones distribucionales.
                            // Fallback final: baja potencia estadística → confianza='posible'.
                            // ═══════════════════════════════════════════════════════
                        } elseif ($cascade_nivel_act === 4) {
                            $n_diffs = $n_floors - 1;
                            if ($n_diffs > 0) {
                                $k_pos = 0;
                                for ($i = 0; $i < $n_diffs; $i++) {
                                    if ($floors_norm_all[$i + 1] > $floors_norm_all[$i]) $k_pos++;
                                }
                                // P(Bin(n_diffs, 0.5) ≥ k_pos) exacto
                                $p_signo  = 0.0;
                                $bcoef    = 1.0;
                                $pow_half = pow(0.5, $n_diffs);
                                for ($k = 0; $k <= $n_diffs; $k++) {
                                    if ($k > 0) $bcoef *= ($n_diffs - $k + 1) / $k;
                                    if ($k >= $k_pos) $p_signo += $bcoef * $pow_half;
                                }
                                $p_signo = min(1.0, $p_signo);
                                if ($p_signo < 0.05) {
                                    $c7a_confianza = 'posible';
                                    $c7a_cascade_nivel = 4;
                                }
                            }
                            $cascade_nivel_act = 0;   // nivel 4 siempre es terminal

                        } else {
                            $cascade_nivel_act = 0;
                        }
                    } // while cascade

                    // ── Emitir incidencia si la cascada confirmó C7a ──────────
                    if ($c7a_confianza !== null) {
                        // c7_subcaso: C7a_posible para niveles ≥ 3 (no alimenta C7c/C7d/C7e)
                        $subcaso_c7a = ($c7a_confianza === 'posible') ? 'C7a_posible' : 'C7a';

                        // ── C7a-015: cobertura temporal (base vs análisis) ────
                        // Theil-Sen sobre cada subperiodo para determinar si la tendencia
                        // es histórica (solo en base), nueva (solo en análisis) o persistente.
                        $beta_ts_base     = null;
                        $beta_ts_analysis = null;
                        $test_period      = null;

                        $fn_ts_median = function (array $vals): ?float {
                            if (empty($vals)) return null;
                            sort($vals);
                            $n = count($vals);
                            return $n % 2 === 1
                                ? $vals[intdiv($n, 2)]
                                : ($vals[$n / 2 - 1] + $vals[$n / 2]) / 2.0;
                        };

                        if ($n_base >= 2) {
                            $fn_base = [];
                            for ($i = 0; $i < $n_base; $i++) {
                                $fn_base[] = $floors_base[$i] / max(1.0, $dias_base[$i]);
                            }
                            $tsp_b = [];
                            for ($i = 0; $i < $n_base; $i++) {
                                for ($j = $i + 1; $j < $n_base; $j++) {
                                    $tsp_b[] = ($fn_base[$j] - $fn_base[$i]) / ($j - $i);
                                }
                            }
                            $beta_ts_base = $fn_ts_median($tsp_b);
                        }

                        if ($n_analysis >= 2) {
                            $fn_ana = [];
                            for ($i = 0; $i < $n_analysis; $i++) {
                                $fn_ana[] = $floors_analysis[$i] / max(1.0, $dias_analysis[$i]);
                            }
                            $tsp_a = [];
                            for ($i = 0; $i < $n_analysis; $i++) {
                                for ($j = $i + 1; $j < $n_analysis; $j++) {
                                    $tsp_a[] = ($fn_ana[$j] - $fn_ana[$i]) / ($j - $i);
                                }
                            }
                            $beta_ts_analysis = $fn_ts_median($tsp_a);
                        }

                        $base_up     = $beta_ts_base     !== null ? $beta_ts_base     > 0.0 : null;
                        $analysis_up = $beta_ts_analysis !== null ? $beta_ts_analysis > 0.0 : null;
                        if ($base_up !== null && $analysis_up !== null) {
                            if ($base_up  && $analysis_up)  $test_period = 'both';
                            elseif ($base_up  && !$analysis_up) $test_period = 'base';
                            elseif (!$base_up && $analysis_up)  $test_period = 'analysis';
                            // ninguno positivo → null (tendencia global sin subperiodo confirmado)
                        } elseif ($base_up !== null) {
                            $test_period = $base_up ? 'base' : null;
                        } elseif ($analysis_up !== null) {
                            $test_period = $analysis_up ? 'analysis' : null;
                        }

                        // ── C7a-021: analysis_consistent — contrasta la tendencia con el periodo base ──
                        $analysis_consistent_c7a = ($beta_ts_base !== null) ? ($beta_ts_base > 0.0) : null;

                        // ── Severidad: tabla 2D confianza × test_period + modulador magnitud ──
                        // Columna null/desconocida → 'analysis' (conservador: sin historial confirmado).
                        //
                        // confianza    | both  | analysis | base  | null(→analysis)
                        // 'alta'       | ALTA  | ALTA     | MEDIA | ALTA
                        // 'media'      | ALTA  | MEDIA    | MEDIA | MEDIA
                        // 'posible'    | MEDIA | MEDIA    | BAJA  | MEDIA
                        // C7a_posible  | BAJA  | BAJA     | BAJA  | BAJA  (siempre)
                        static $sev_tab_c7a = [
                            'alta'    => ['both' => 'ALTA',  'analysis' => 'ALTA',  'base' => 'MEDIA'],
                            'media'   => ['both' => 'ALTA',  'analysis' => 'MEDIA', 'base' => 'MEDIA'],
                            'posible' => ['both' => 'MEDIA', 'analysis' => 'MEDIA', 'base' => 'BAJA'],
                        ];
                        static $sev_num_c7a  = ['ALTA' => 2, 'MEDIA' => 1, 'BAJA' => 0];
                        static $sev_name_c7a = [2 => 'ALTA', 1 => 'MEDIA', 0 => 'BAJA'];

                        $c7a_umbral_alta_delta = ($tipo_art === 'peso') ? $c7a_umbral_alta_delta_peso : $c7a_umbral_alta_delta_unidad;
                        $c7a_umbral_alta_slope = ($tipo_art === 'peso') ? $c7a_umbral_alta_slope_peso : $c7a_umbral_alta_slope_unidad;

                        if ($subcaso_c7a === 'C7a_posible') {
                            $severidad = 'BAJA';
                        } else {
                            $col = $test_period ?? 'analysis'; // null → 'analysis' (sin historial confirmado)
                            $sev_base_c7a  = $sev_tab_c7a[$c7a_confianza][$col];
                            $sev_nivel_c7a = $sev_num_c7a[$sev_base_c7a];
                            // Modulador magnitud: si delta y slope están ambos bajo el umbral ALTA, bajar un nivel
                            if ($delta_total < $c7a_umbral_alta_delta && $tendencia_visible < $c7a_umbral_alta_slope) {
                                $sev_nivel_c7a--;
                            }
                            $severidad = $sev_name_c7a[max(0, $sev_nivel_c7a)];
                        }

                        // Cobertura: etiqueta legible para la vista
                        $cobertura_c7a = $test_period ?? 'analysis';

                        // ── C7a-010: tendencia_reciente — estado actual de la merma ────
                        // Análogo a C7b: 'activo', 'resuelto', 'mejorando', 'sin_datos'
                        if ($test_period === 'base') {
                            // La tendencia existe en el histórico; ¿continúa en el análisis?
                            if ($analysis_consistent_c7a === true) {
                                $tendencia_reciente_c7a = 'activo';    // pendiente positiva también en análisis
                            } elseif ($n_analysis === 0) {
                                $tendencia_reciente_c7a = 'sin_datos'; // sin recepciones en el análisis
                            } elseif ($beta_ts_analysis !== null && $beta_ts_analysis < 0.0) {
                                $tendencia_reciente_c7a = 'resuelto';  // pendiente negativa en análisis
                            } else {
                                $tendencia_reciente_c7a = 'mejorando'; // positiva pero no confirma C7a-015
                            }
                        } else {
                            // 'analysis' o 'both' o null → la merma está activa en el período reciente
                            $tendencia_reciente_c7a = 'activo';
                        }

                        // ── C7a-024/025: discriminar merma vs. compra con stock ──────────
                        // ratio_i = floor[i-1] / cantidad_recepción[i]: alto sistemáticamente → compra con stock
                        // Spearman(floor[i-1], cantidad[i]): negativo → comprador ajusta pedidos al stock
                        $tiene_albcli_c7a  = isset($has_albcli_ids[$id]);
                        $compra_con_stock  = false;
                        $comprador_ajusta  = false;
                        $r_spearman_c7a    = null;
                        $ratio_mediano_c7a = null;

                        // Cantidades por fecha de recepción (de $cantidades_map construido junto con $recepciones_map)
                        $cantidades_rec = [];
                        foreach ($fechas_floors as $fd) {
                            $cantidades_rec[] = $cantidades_map[$id][$fd] ?? 0.0;
                        }

                        if ($n_floors >= 3) {
                            $x_floors_prev = [];   // floor[i-1]
                            $y_quant       = [];   // cantidad[i]
                            $ratios_cob    = [];   // floor[i-1] / cantidad[i]
                            for ($i = 1; $i < $n_floors; $i++) {
                                $qty = $cantidades_rec[$i];
                                if ($qty > 0.0 && $floors[$i - 1] >= 0.0) {
                                    $x_floors_prev[] = $floors[$i - 1];
                                    $y_quant[]       = $qty;
                                    $ratios_cob[]    = $floors[$i - 1] / $qty;
                                }
                            }
                            $n_pares = count($ratios_cob);

                            if ($n_pares >= 2) {
                                // Mediana de ratios
                                $ratios_sorted     = $ratios_cob;
                                sort($ratios_sorted);
                                $ratio_mediano_c7a = $n_pares % 2 === 1
                                    ? $ratios_sorted[intdiv($n_pares, 2)]
                                    : ($ratios_sorted[$n_pares / 2 - 1] + $ratios_sorted[$n_pares / 2]) / 2.0;

                                // Test binomial one-sided: ¿mayoría de ratios > 0.3?
                                $umbral_ratio = 0.3;
                                $k_alto       = 0;
                                foreach ($ratios_cob as $rv) {
                                    if ($rv > $umbral_ratio) $k_alto++;
                                }
                                $p_ratio  = 0.0;
                                $bcoef    = 1.0;
                                $phalf    = pow(0.5, $n_pares);
                                for ($k = 0; $k <= $n_pares; $k++) {
                                    if ($k > 0) $bcoef *= ($n_pares - $k + 1) / $k;
                                    if ($k >= $k_alto) $p_ratio += $bcoef * $phalf;
                                }
                                $compra_con_stock = (min(1.0, $p_ratio) < 0.10);

                                // Spearman: ¿comprador reduce pedidos cuando el stock es alto?
                                if ($n_pares >= 3) {
                                    $fn_rank_c7a = static function (array $arr): array {
                                        $n   = count($arr);
                                        $idx = range(0, $n - 1);
                                        usort($idx, static fn($a, $b) => $arr[$a] <=> $arr[$b]);
                                        $ranks = array_fill(0, $n, 0.0);
                                        for ($i = 0; $i < $n;) {
                                            $j = $i;
                                            while ($j < $n && $arr[$idx[$j]] === $arr[$idx[$i]]) $j++;
                                            $avg = ($i + $j - 1) / 2.0 + 1.0;
                                            for ($k = $i; $k < $j; $k++) $ranks[$idx[$k]] = $avg;
                                            $i = $j;
                                        }
                                        return $ranks;
                                    };
                                    $rx       = $fn_rank_c7a($x_floors_prev);
                                    $ry       = $fn_rank_c7a($y_quant);
                                    $n_sp     = $n_pares;
                                    $mean_rx  = array_sum($rx) / $n_sp;
                                    $mean_ry  = array_sum($ry) / $n_sp;
                                    $cov_sp   = $var_rx_sp = $var_ry_sp = 0.0;
                                    for ($i = 0; $i < $n_sp; $i++) {
                                        $cov_sp    += ($rx[$i] - $mean_rx) * ($ry[$i] - $mean_ry);
                                        $var_rx_sp += ($rx[$i] - $mean_rx) ** 2;
                                        $var_ry_sp += ($ry[$i] - $mean_ry) ** 2;
                                    }
                                    $denom_sp       = sqrt($var_rx_sp * $var_ry_sp);
                                    $r_spearman_c7a = $denom_sp > 0.0 ? $cov_sp / $denom_sp : 0.0;
                                    $comprador_ajusta = ($r_spearman_c7a < -0.3);
                                }
                            }
                        }

                        // ── C7a-010: posible_causa accionable según estado ───────────
                        $unidad_c7a    = ($tipo_art === 'peso') ? 'kg' : 'ud.';
                        $slope_fmt_c7a = number_format(abs($tendencia_visible), 1, '.', '');
                        $delta_fmt_c7a = number_format(abs($delta_total),       1, '.', '');
                        $prefijo_c7a   = sprintf(
                            'El suelo mínimo sube ~%s %s por recepción (%s %s acumulados). ',
                            $slope_fmt_c7a,
                            $unidad_c7a,
                            $delta_fmt_c7a,
                            $unidad_c7a
                        );
                        switch ($tendencia_reciente_c7a) {
                            case 'resuelto':
                                $posible_causa_c7a = $prefijo_c7a . 'Tendencia histórica no confirmada en el período reciente. Verificar si se realizó un ajuste de inventario o corrección de merma.';
                                break;
                            case 'mejorando':
                                $posible_causa_c7a = $prefijo_c7a . 'La tendencia puede estar reduciéndose — pendiente reciente sin confirmación estadística. Monitorizar en el próximo informe.';
                                break;
                            case 'sin_datos':
                                $posible_causa_c7a = $prefijo_c7a . 'Sin recepciones en el período de análisis — no es posible confirmar si la merma sigue activa. Revisar entradas pendientes.';
                                break;
                            default: // 'activo'
                                if ($comprador_ajusta) {
                                    $posible_causa_c7a = $prefijo_c7a . 'El comprador reduce los pedidos cuando el stock es alto — menos probable merma sistemática. Posible política de buffer o entrega en calendario fijo.';
                                } elseif ($compra_con_stock) {
                                    $posible_causa_c7a = $prefijo_c7a . 'Stock sistemáticamente alto antes de cada recepción — posible compra anticipada o entrega en calendario fijo. Verificar si existe merma real no registrada.';
                                } else {
                                    $posible_causa_c7a = $prefijo_c7a . 'Posible merma, caducidad no registrada o salida sin documentar.';
                                }
                        }
                        // ── C7a-025: aviso condicional si hay albaranes de cliente ──────
                        if ($tiene_albcli_c7a) {
                            $posible_causa_c7a .= ' · Hay albaranes de cliente en el período: verificar consumo interno o albaranes pendientes.';
                        }

                        $incidencias[] = [
                            'idArticulo'       => $id,
                            'tipo'             => 'Merma acumulada',
                            'severidad'        => $severidad,
                            'c7_subcaso'       => $subcaso_c7a,
                            'confianza'        => $c7a_confianza,
                            'cascade_nivel'    => $c7a_cascade_nivel,
                            'n_recepciones'    => $n_rec,
                            'offset_estimado'  => round($mean_raw, 1),
                            'dispersion'       => round($std_dev_raw_c7a, 1),
                            'tendencia'        => round($tendencia_visible, 1),
                            'tendencia_norm'   => round($beta_ts, 4),
                            'autocorr_lag1'    => round($autocorr_lag1_c7a, 2),
                            'p_mk'             => $p_mk_c7a !== null ? round($p_mk_c7a, 4) : null,
                            'tau_mk'           => isset($tau_mk) ? round($tau_mk, 3) : null,
                            'snr'                  => round($snr_c7a, 3),
                            'cascade_fallback_reason' => $c7a_fallback_reason ?: null,
                            'test_period'         => $test_period,
                            'tendencia_reciente'  => $tendencia_reciente_c7a,
                            'analysis_consistent' => $analysis_consistent_c7a,
                            'cobertura'           => $cobertura_c7a,
                            'n_base'              => $n_base,
                            'n_analysis'          => $n_analysis,
                            'slope_base'       => $beta_ts_base     !== null ? round($beta_ts_base     * $dias_intervalo_medio_all, 1) : null,
                            'slope_analysis'   => $beta_ts_analysis !== null ? round($beta_ts_analysis * $dias_intervalo_medio_all, 1) : null,
                            'delta_acumulado'  => round($delta_total, 1),
                            'fecha_primera'    => $fechas_rec[0],
                            'fecha_ultima'     => $fechas_rec[$n_rec - 1],
                            '_floors_raw'      => $floors_map,
                            'posible_causa'    => $posible_causa_c7a,
                            'tiene_albcli'     => $tiene_albcli_c7a,
                            'r_spearman'       => $r_spearman_c7a !== null ? round($r_spearman_c7a, 3) : null,
                            'ratio_mediano'    => $ratio_mediano_c7a !== null ? round($ratio_mediano_c7a, 3) : null,
                        ];
                    }
                } // n_floors >= 3 && beta_ts > 0
            } // isset C7a && mean_raw >= 0 && delta_total >= 2

            if ($test_floors !== null && isset($subcasos_set['C7b'])) {
                // ── Estadísticos sobre el conjunto de test ───────────────────
                $n = count($test_floors);

                // C7b-002: normalizar floors por duración del intervalo (déficit/día).
                $floors_norm = [];
                for ($i = 0; $i < $n; $i++) {
                    $floors_norm[] = $test_floors[$i] / $test_dias[$i];
                }
                $mean = array_sum($floors_norm) / $n;

                $variance = 0.0;
                foreach ($floors_norm as $fnv) {
                    $variance += ($fnv - $mean) ** 2;
                }
                $std_dev = $n > 1 ? sqrt($variance / ($n - 1)) : 0.0;
                $cv      = $mean != 0.0 ? $std_dev / abs($mean) : PHP_FLOAT_MAX;

                $dias_intervalo_medio = (int)round(array_sum($test_dias) / $n);

                // C7b-025: heterogeneidad de duraciones
                $mean_dias  = array_sum($test_dias) / $n;
                $var_dias   = 0.0;
                foreach ($test_dias as $d) {
                    $var_dias += ($d - $mean_dias) ** 2;
                }
                $std_dias   = $n > 1 ? sqrt($var_dias / ($n - 1)) : 0.0;
                $cv_dias    = $mean_dias > 0.0 ? $std_dias / $mean_dias : 0.0;

                // C7b-024: guard varianza nula — IC95 colapsa con "certeza perfecta" falsa.
                // Nivel 5 de la cascada (C7b-026): determinista constante.
                // Umbral 1e-9 en lugar de == 0.0 para capturar near-zero por ruido float
                // (floors casi idénticos que muestran ±0.0 pero no son exactamente iguales).
                if ($std_dev < 1e-9) {
                    if ($mean < 0.0) {
                        $incidencias[] = [
                            'idArticulo'               => $id,
                            'tipo'                     => 'Entrada no registrada',
                            'severidad'                => 'BAJA',
                            'c7_subcaso'               => 'C7b',
                            'confianza'                => 'posible',
                            'test_period'              => $test_period,
                            'analysis_consistent'      => false,
                            'tipo_articulo'            => $tipo_art,
                            'n_recepciones'            => $n_rec,
                            'offset_estimado'          => round($mean_raw, 1),
                            'offset_norm'              => round($mean, 3),
                            'dispersion'               => 0.0,
                            'autocorr_lag1'            => 0.0,
                            'ic95_upper'               => round($mean, 4),
                            'n_intervalos_negativos'   => $n,
                            'pct_intervalos_negativos' => 100,
                            'dias_intervalo_medio'     => $dias_intervalo_medio,
                            'fecha_primera'            => $fechas_rec[0],
                            'fecha_ultima'             => $fechas_rec[$n_rec - 1],
                            '_floors_raw'              => $floors_map,
                            'test_type'                => 'determinista_constante',
                            'posible_causa'            => sprintf(
                                'El stock cae siempre exactamente %d %s entre recepciones (varianza cero). Patrón constante, pero con pocos datos para test estadístico.',
                                (int)round($abs_mean_raw),
                                $tipo_art === 'peso' ? 'kg' : 'ud.'
                            ),
                        ];
                    }
                    continue;
                }

                // ── Filtros de estabilidad — condición previa a todos los niveles (C7b-009) ──
                // Artículos de peso usan umbrales más tolerantes: la variabilidad de pesaje
                // y la irregularidad en cantidad por entrega elevan CV e IQR de forma legítima.
                $umbral_cv_efectivo  = ($tipo_art === 'peso') ? $c7b_umbral_cv_peso : $c7b_umbral_cv;
                $umbral_iqr_mult     = ($tipo_art === 'peso') ? $c7b_umbral_iqr_peso : 1.5;
                $sf = $floors_norm;
                sort($sf);
                $q1_idx = (int)floor(($n - 1) * 0.25);
                $q3_idx = (int)ceil(($n - 1) * 0.75);
                $iqr    = $sf[$q3_idx] - $sf[$q1_idx];
                if ($n === 3) {
                    $iqr *= 0.7;
                }
                if (!($cv < $umbral_cv_efectivo && $iqr < $umbral_iqr_mult * abs($mean))) {
                    continue;
                }

                // ── Estado de la cascada ─────────────────────────────────────
                $test_type            = null;
                $test_pvalue          = null;
                $test_fallback_reason = null;
                $c7b_confirmed        = false;
                $confianza_c7b        = 'posible';
                $ic95_upper           = null;
                $r1                   = 0.0;

                // ═══════════════════════════════════════════════════════════
                // NIVEL 1 · WILCOXON SIGNED-RANK (C7b-020)
                // Más potente para distribuciones asimétricas (Gumbel).
                // confianza si confirma: 'alta'
                // ═══════════════════════════════════════════════════════════
                $ratio_distinct  = count(array_unique($floors_norm)) / $n;
                $wilcoxon_tipo_a = ($n < 4 || $ratio_distinct < 0.75);
                if (!$wilcoxon_tipo_a) {
                    // Valores no-cero con sus signos
                    $nz_vals = [];
                    $nz_signs = [];
                    foreach ($floors_norm as $fv) {
                        if ($fv != 0.0) {
                            $nz_vals[] = abs($fv);
                            $nz_signs[] = ($fv < 0 ? -1 : 1);
                        }
                    }
                    $n_w = count($nz_vals);
                    if ($n_w >= 4) {
                        // Rangos con corrección de empates (rango promedio)
                        $order = range(0, $n_w - 1);
                        usort($order, fn($a, $b) => $nz_vals[$a] <=> $nz_vals[$b]);
                        $rnks = array_fill(0, $n_w, 0.0);
                        $i = 0;
                        while ($i < $n_w) {
                            $j = $i;
                            while ($j + 1 < $n_w && $nz_vals[$order[$j + 1]] == $nz_vals[$order[$i]]) {
                                $j++;
                            }
                            $avg_rank = ($i + $j + 2) / 2.0;
                            for ($k2 = $i; $k2 <= $j; $k2++) {
                                $rnks[$order[$k2]] = $avg_rank;
                            }
                            $i = $j + 1;
                        }
                        $n_ties_ranks = $n_w - count(array_unique($rnks));
                        if ($n_ties_ranks / $n_w > 0.5) {
                            // Tipo B: demasiados empates en rangos — resultado degradado
                            $test_fallback_reason = 'wilcoxon_tipo_b_empates';
                        } else {
                            // T+ = suma de rangos de valores positivos (H1: mediana < 0 → T+ pequeño)
                            $t_plus = 0.0;
                            for ($i = 0; $i < $n_w; $i++) {
                                if ($nz_signs[$i] > 0) {
                                    $t_plus += $rnks[$i];
                                }
                            }
                            // Aproximación normal con corrección de continuidad (Abramowitz & Stegun 7.1.26)
                            $mu_w  = $n_w * ($n_w + 1) / 4.0;
                            $var_w = $n_w * ($n_w + 1) * (2 * $n_w + 1) / 24.0;
                            $z_w   = ($t_plus + 0.5 - $mu_w) / sqrt($var_w);
                            $az    = abs($z_w);
                            $t_as  = 1.0 / (1.0 + 0.2316419 * $az);
                            $poly  = $t_as * (0.319381530 + $t_as * (-0.356563782 + $t_as * (1.781477937 + $t_as * (-1.821255978 + $t_as * 1.330274429))));
                            $ncdf  = 1.0 - exp(-$az * $az / 2.0) / sqrt(2.0 * M_PI) * $poly;
                            $p_wilcoxon  = $z_w < 0 ? 1.0 - $ncdf : $ncdf;
                            $test_type   = 'wilcoxon_signed_rank';
                            $test_pvalue = round($p_wilcoxon, 4);
                            if ($p_wilcoxon < 0.05) {
                                $c7b_confirmed = true;
                                $confianza_c7b = 'alta';
                            } elseif (!$c7b_cascada_exhaustiva) {
                                continue; // resultado válido: no C7b
                            } else {
                                $test_fallback_reason = ($test_fallback_reason ? $test_fallback_reason . '; ' : '')
                                    . 'wilcoxon_p=' . round($p_wilcoxon, 3) . '_ns';
                                $test_type = null;
                                $test_pvalue = null;
                            }
                        }
                    } else {
                        $wilcoxon_tipo_a = true; // n_w < 4 tras excluir ceros
                    }
                }
                if ($wilcoxon_tipo_a && $test_fallback_reason === null) {
                    $rs = [];
                    if ($n < 4)                 $rs[] = 'n<4';
                    if ($ratio_distinct < 0.75) $rs[] = 'ratio_distinct<0.75';
                    $test_fallback_reason = 'wilcoxon_tipo_a:' . implode(',', $rs);
                }

                // ═══════════════════════════════════════════════════════════
                // NIVEL 2 · BOOTSTRAP PERCENTILE (C7b-023)
                // Sin supuesto distribucional; especialmente útil para n=3.
                // confianza si confirma: 'media'
                // ═══════════════════════════════════════════════════════════
                if (!$c7b_confirmed) {
                    $n_distinct_boot = count(array_unique($floors_norm));
                    if ($std_dev > 0.0 && $n_distinct_boot > 1) {
                        $mu_boot = [];
                        for ($b = 0; $b < 999; $b++) {
                            $s = 0.0;
                            for ($j = 0; $j < $n; $j++) {
                                $s += $floors_norm[random_int(0, $n - 1)];
                            }
                            $mu_boot[] = $s / $n;
                        }
                        sort($mu_boot);
                        $ic95_sup_boot = $mu_boot[(int)(999 * 0.975)]; // índice 974 → percentil 97.5
                        $test_type = 'bootstrap';
                        if ($ic95_sup_boot < 0.0) {
                            $c7b_confirmed = true;
                            $confianza_c7b = 'media';
                        } elseif (!$c7b_cascada_exhaustiva) {
                            continue; // resultado válido: no C7b
                        } else {
                            $test_fallback_reason = ($test_fallback_reason ? $test_fallback_reason . '; ' : '')
                                . 'bootstrap_ic95_sup=' . round($ic95_sup_boot, 3) . '_ns';
                            $test_type = null;
                        }
                    } else {
                        $reason_boot = ($n_distinct_boot == 1) ? 'bootstrap_tipo_b_n_distinct_1' : 'bootstrap_tipo_a_std0';
                        $test_fallback_reason = ($test_fallback_reason ? $test_fallback_reason . '; ' : '') . $reason_boot;
                    }
                }

                // ═══════════════════════════════════════════════════════════
                // NIVEL 3 · TEST DE SIGNO BINOMIAL (C7b-021)
                // Válido para cualquier distribución, continua o discreta.
                // confianza si confirma: 'media' — test exacto sin supuestos distribucionales;
                // menor potencia que Wilcoxon pero p<0.05 tiene las mismas garantías cuando alcanza significancia.
                // ═══════════════════════════════════════════════════════════
                if (!$c7b_confirmed) {
                    $floors_eff = array_values(array_filter($floors_norm, fn($f) => $f != 0.0));
                    $n_eff      = count($floors_eff);
                    if ($n_eff >= 3) {
                        $b_minus = count(array_filter($floors_eff, fn($f) => $f < 0.0));
                        // p = P(Binom(n_eff, 0.5) >= b_minus) — cola superior exacta
                        $p_sign = 0.0;
                        $coeff = 1.0;
                        for ($k = 0; $k <= $n_eff; $k++) {
                            if ($k >= $b_minus) {
                                $p_sign += $coeff;
                            }
                            if ($k < $n_eff) {
                                $coeff *= ($n_eff - $k) / ($k + 1.0);
                            }
                        }
                        $p_sign /= pow(2.0, $n_eff);
                        $test_type   = 'sign_binomial';
                        $test_pvalue = round($p_sign, 4);
                        if ($p_sign < 0.05) {
                            $c7b_confirmed = true;
                            $confianza_c7b = 'media';
                        } elseif (!$c7b_cascada_exhaustiva) {
                            continue; // resultado válido: no C7b
                        } else {
                            $test_fallback_reason = ($test_fallback_reason ? $test_fallback_reason . '; ' : '')
                                . 'sign_p=' . round($p_sign, 3) . '_ns';
                            $test_type = null;
                            $test_pvalue = null;
                        }
                    } else {
                        $test_fallback_reason = ($test_fallback_reason ? $test_fallback_reason . '; ' : '') . 'sign_tipo_a_n_efectivo<3';
                    }
                }

                // ═══════════════════════════════════════════════════════════
                // NIVEL 4 · T-TEST + NEWEY-WEST (C7b-004, implementación original)
                // Último recurso paramétrico; asume normalidad (violada — Gumbel).
                // confianza si confirma: 'posible'
                // ═══════════════════════════════════════════════════════════
                if (!$c7b_confirmed) {
                    // std_dev > 0 garantizado (guard C7b-024 ya salió antes de este bloque)
                    $df_ic = $n - 1;
                    if ($df_ic > 120) {
                        $t_975 = 1.960;
                    } elseif (isset($t_975_tab[$df_ic])) {
                        $t_975 = $t_975_tab[$df_ic];
                    } else {
                        $keys_ic = array_keys($t_975_tab);
                        $lo_ic = $hi_ic = null;
                        foreach ($keys_ic as $k) {
                            if ($k <= $df_ic) $lo_ic = $k;
                            if ($k >= $df_ic && $hi_ic === null) $hi_ic = $k;
                        }
                        $t_975 = ($lo_ic !== null && $hi_ic !== null && $lo_ic !== $hi_ic)
                            ? $t_975_tab[$lo_ic] + ($df_ic - $lo_ic) / ($hi_ic - $lo_ic) * ($t_975_tab[$hi_ic] - $t_975_tab[$lo_ic])
                            : ($lo_ic !== null ? $t_975_tab[$lo_ic] : 1.960);
                    }
                    $se_base    = $std_dev / sqrt($n);
                    $ic95_upper = $mean + $t_975 * $se_base;
                    // C7b-004: corrección Newey-West para autocorrelación lag-1
                    if ($n >= 4) {
                        $cov_lag1 = 0.0;
                        for ($i = 1; $i < $n; $i++) {
                            $cov_lag1 += ($floors_norm[$i] - $mean) * ($floors_norm[$i - 1] - $mean);
                        }
                        $cov_lag1 /= ($n - 1);
                        $r1 = max(-1.0, min(1.0, $cov_lag1 / ($std_dev ** 2)));
                        if ($r1 > 0.0) {
                            $ic95_upper = $mean + $t_975 * $se_base * sqrt(1.0 + 2.0 * $r1);
                        }
                    }
                    $test_type = 't_student';
                    if ($ic95_upper < 0.0) {
                        $c7b_confirmed = true;
                        $confianza_c7b = 'posible';
                    } else {
                        continue;
                    } // resultado válido: no C7b
                }

                if (!$c7b_confirmed) {
                    continue;
                }

                // ── C7b-025: duraciones irregulares degradan confianza un nivel ──
                if ($cv_dias > 1.0) {
                    $confianza_c7b = ($confianza_c7b === 'alta') ? 'media' : 'posible';
                }

                // ── C7b-005: filtro ruido pesaje ──────────────────────────────
                if ($tipo_art === 'peso' && $abs_mean_raw < $c7b_umbral_ruido_peso) {
                    $incidencias[] = [
                        'idArticulo'           => $id,
                        'tipo'                 => 'Posible error de pesaje',
                        'severidad'            => 'BAJA',
                        'c7_subcaso'           => 'C7b_ruido_peso',
                        'tipo_articulo'        => $tipo_art,
                        'n_recepciones'        => $n_rec,
                        'offset_estimado'      => round($mean_raw, 2),
                        'dias_intervalo_medio' => $dias_intervalo_medio,
                        'fecha_primera'        => $fechas_rec[0],
                        'fecha_ultima'         => $fechas_rec[$n_rec - 1],
                        '_floors_raw'          => $floors_map,
                        'posible_causa'        => sprintf(
                            'El stock aparece %.2f kg en negativo, pero es demasiado pequeño para ser un error real — probablemente es acumulación de decimales de balanza.',
                            $abs_mean_raw
                        ),
                    ];
                    continue;
                }

                // ── C7b-006: cobertura del déficit en todos los floors del periodo ─
                $n_neg   = count(array_filter($floors, fn($f) => $f < 0.0));
                $pct_neg = (int)round($n_neg / $n_floors * 100);

                // ── Cobertura temporal + tendencia reciente (C7b-022) ────────
                if ($test_period === 'base') {
                    $n_analysis_neg      = count(array_filter($floors_analysis, fn($f) => $f < 0.0));
                    $analysis_consistent = $n_analysis >= 2 && $n_analysis_neg === $n_analysis;
                    $cobertura           = $analysis_consistent ? 'A' : 'B';

                    // C7b-022: distinguir déficit activo vs resuelto cuando el test corre sobre la base
                    if ($analysis_consistent) {
                        $tendencia_reciente = 'activo';     // análisis confirma
                    } elseif ($n_analysis === 0) {
                        $tendencia_reciente = 'sin_datos';  // sin recepciones en el período de análisis
                    } else {
                        $mean_analysis = array_sum($floors_analysis) / $n_analysis;
                        if ($mean_analysis > 0.0) {
                            $tendencia_reciente = 'resuelto';   // floors de análisis en positivo
                        } else {
                            $tendencia_reciente = 'mejorando';  // negativos pero no confirman
                        }
                    }
                } else {
                    $analysis_consistent = false;
                    $cobertura           = 'C';
                    $tendencia_reciente  = 'activo'; // el test corre sobre análisis → activo por definición
                }

                // ── Severidad: tabla 2D (confianza × cobertura) + moduladores ─
                // Tabla base:
                //   confianza \ cobertura |  A (base+análisis) | B (base solo) | C (análisis solo)
                //   'alta'  (Wilcoxon)    |  CRITICA           | ALTA          | ALTA
                //   'media' (Bootstrap)   |  ALTA              | ALTA          | MEDIA
                //   'posible' (sign/t/det)|  ALTA              | MEDIA         | MEDIA
                static $sev_tab = [
                    'alta'    => ['A' => 'CRITICA', 'B' => 'ALTA',  'C' => 'ALTA'],
                    'media'   => ['A' => 'ALTA',    'B' => 'ALTA',  'C' => 'MEDIA'],
                    'posible' => ['A' => 'ALTA',    'B' => 'MEDIA', 'C' => 'MEDIA'],
                ];
                static $sev_num = ['CRITICA' => 3, 'ALTA' => 2, 'MEDIA' => 1, 'BAJA' => 0];
                static $sev_name = [3 => 'CRITICA', 2 => 'ALTA', 1 => 'MEDIA', 0 => 'BAJA'];

                $sev_base = $sev_tab[$confianza_c7b][$cobertura];
                $mag_small = ($tipo_art === 'peso')
                    ? ($abs_mean_raw < $c7b_umbral_sev_peso)
                    : ($abs_mean_raw < $c7b_umbral_sev_unidad);

                $sev_nivel = $sev_num[$sev_base];
                if ($mag_small) {
                    $sev_nivel--;
                } // magnitud pequeña
                if ($pct_neg < 50) {
                    $sev_nivel--;
                } // déficit parcial
                $severidad = $sev_name[max(0, $sev_nivel)];

                // ── C7b-022: posible_causa accionable según tendencia ────────
                $unidad_causa = $tipo_art === 'peso' ? 'kg' : 'ud.';
                switch ($tendencia_reciente) {
                    case 'resuelto':
                        $causa_texto = sprintf(
                            'El stock caía ~%d %s en negativo de forma repetida, pero el período reciente no muestra el patrón. Verificar que el albarán pendiente fue registrado o que el error se corrigió.',
                            (int)round($abs_mean_raw),
                            $unidad_causa
                        );
                        break;
                    case 'mejorando':
                        $causa_texto = sprintf(
                            'El stock caía ~%d %s en negativo de forma repetida. El período reciente tiene floors negativos pero sin confirmación estadística — el problema puede estar reduciéndose. Monitorizar en próximo informe.',
                            (int)round($abs_mean_raw),
                            $unidad_causa
                        );
                        break;
                    case 'sin_datos':
                        $causa_texto = sprintf(
                            'El stock caía ~%d %s en negativo de forma repetida. Sin recepciones en el período reciente — no es posible confirmar si el problema sigue activo. Revisar albaranes pendientes.',
                            (int)round($abs_mean_raw),
                            $unidad_causa
                        );
                        break;
                    default: // 'activo'
                        $causa_texto = sprintf(
                            'El stock cae ~%d %s en negativo de forma repetida entre cada recepción. Revisar si hay albaranes pendientes de confirmar o si el stock inicial del artículo está bien introducido.',
                            (int)round($abs_mean_raw),
                            $unidad_causa
                        );
                }

                $incidencias[] = [
                    'idArticulo'               => $id,
                    'tipo'                     => 'Entrada no registrada',
                    'severidad'                => $severidad,
                    'c7_subcaso'               => 'C7b',
                    'confianza'                => $confianza_c7b,
                    'test_period'              => $test_period,
                    'analysis_consistent'      => $analysis_consistent ?? false,
                    'tendencia_reciente'       => $tendencia_reciente,
                    'tipo_articulo'            => $tipo_art,
                    'n_recepciones'            => $n_rec,
                    'offset_estimado'          => round($mean_raw, 1),
                    'offset_norm'              => round($mean, 3),
                    'dispersion'               => round($std_dev, 3),
                    'autocorr_lag1'            => round($r1, 2),
                    'ic95_upper'               => $ic95_upper !== null ? round($ic95_upper, 4) : null,
                    'cv_duraciones'            => round($cv_dias, 3),
                    'test_type'                => $test_type,
                    'test_pvalue'              => $test_pvalue,
                    'test_fallback_reason'     => $test_fallback_reason,
                    'n_intervalos_negativos'   => $n_neg,
                    'pct_intervalos_negativos' => $pct_neg,
                    'dias_intervalo_medio'     => $dias_intervalo_medio,
                    'fecha_primera'            => $fechas_rec[0],
                    'fecha_ultima'             => $fechas_rec[$n_rec - 1],
                    '_floors_raw'              => $floors_map,
                    'posible_causa'            => $causa_texto,
                ];
                continue;
            } elseif ($n_analysis === 2 && isset($subcasos_set['C7b'])) {
                // C7b-010: C7b_posible — solo 2 floors en el periodo de análisis,
                // sin base suficiente para test estadístico.
                // Condición: ambos negativos y CV < 0.15 (muy baja dispersión relativa).
                $f0 = $floors_analysis[0];
                $f1 = $floors_analysis[1];
                $mean_2 = ($f0 + $f1) / 2.0;
                $cv_2   = $mean_2 != 0.0 ? abs($f0 - $f1) / (2.0 * abs($mean_2)) : PHP_FLOAT_MAX;
                if ($f0 < 0 && $f1 < 0 && $cv_2 < 0.15) {
                    $abs_med_2 = abs($mean_2);
                    if (!($tipo_art === 'peso' && $abs_med_2 < 0.5)) {
                        $dias_med_2  = (int)round(array_sum($dias_analysis) / 2);
                        $date_keys_2 = array_slice(array_keys($floors_map), -2);
                        $incidencias[] = [
                            'idArticulo'               => $id,
                            'tipo'                     => 'Entrada no registrada',
                            'severidad'                => 'BAJA',
                            'c7_subcaso'               => 'C7b_posible',   // NO alimenta C7c/C7d/C7e
                            'confianza'                => 'posible',
                            'tipo_articulo'            => $tipo_art,
                            'n_recepciones'            => $n_rec,
                            'offset_estimado'          => round($mean_2, 1),
                            'offset_norm'              => round($mean_2 / max(1, $dias_med_2), 3),
                            'dispersion'               => round(abs($f0 - $f1) / 2.0, 3),
                            'autocorr_lag1'            => 0.0,
                            'ic95_upper'               => null,
                            'n_intervalos_negativos'   => 2,
                            'pct_intervalos_negativos' => 100,
                            'dias_intervalo_medio'     => $dias_med_2,
                            'fecha_primera'            => $date_keys_2[0] ?? $fechas_rec[0],
                            'fecha_ultima'             => $date_keys_2[1] ?? $fechas_rec[$n_rec - 1],
                            '_floors_raw'              => $floors_map,
                            'posible_causa'            => sprintf(
                                'El stock cae ~%d %s en negativo en las 2 recepciones del periodo. Podría faltar un albarán, aunque con solo 2 datos no es posible confirmarlo — conviene revisar manualmente.',
                                (int)round($abs_med_2),
                                $tipo_art === 'peso' ? 'kg' : 'ud.'
                            ),
                        ];
                    }
                }
            }
        }

        // ── C7a-004 + C7a-005: enriquecer C7a con coste estimado de merma y proveedor ──
        if (isset($subcasos_set['C7a'])) {
            $ids_c7a_enr = array_column(
                array_filter($incidencias, fn($inc) => ($inc['c7_subcaso'] ?? '') === 'C7a' || ($inc['c7_subcaso'] ?? '') === 'C7a_posible'),
                'idArticulo'
            );
            if (!empty($ids_c7a_enr)) {
                $fi_stock_esc_c7a = $this->db->real_escape_string($fi_stock);
                $ids_c7a_str      = implode(',', array_map('intval', $ids_c7a_enr));

                $prov_map_c7a   = $this->repo->queryProveedorArticulos($ids_c7a_str, $fi_stock_esc_c7a, $ff);
                $precio_map_c7a = $this->repo->queryPrecioMedioCompra($ids_c7a_str, $fi_stock_esc_c7a, $ff);

                foreach ($incidencias as &$inc) {
                    $sub = $inc['c7_subcaso'] ?? '';
                    if ($sub !== 'C7a' && $sub !== 'C7a_posible') continue;
                    $prov = $prov_map_c7a[$inc['idArticulo']] ?? null;
                    $inc['prov_habitual_nombre'] = $prov['prov_habitual_nombre'] ?? null;
                    $inc['prov_habitual_n']      = $prov['prov_habitual_n']      ?? null;
                    $inc['prov_ultimo_nombre']   = $prov['prov_ultimo_nombre']   ?? null;
                    $inc['prov_ultima_fecha']    = $prov['prov_ultima_fecha']    ?? null;
                    $inc['prov_es_mismo']        = $prov['prov_es_mismo']        ?? null;
                    // C7a-004: valor económico estimado de la merma acumulada
                    $precio = $precio_map_c7a[$inc['idArticulo']] ?? null;
                    $inc['precio_medio_compra']  = $precio;
                    $inc['coste_estimado_merma'] = ($precio !== null)
                        ? round((float)$inc['delta_acumulado'] * $precio, 2)
                        : null;
                }
                unset($inc);
            }
        }

        // ── C7b-007 + C7b-011: enriquecer C7b con proveedor y coste estimado ──
        // Aplica a todos los subcasos C7b (C7b, C7b_posible, C7b_ruido_peso).
        if (isset($subcasos_set['C7b'])) {
            $ids_c7b = array_column(
                array_filter($incidencias, fn($inc) => str_starts_with($inc['c7_subcaso'] ?? '', 'C7b')),
                'idArticulo'
            );
            if (!empty($ids_c7b)) {
                $fi_stock_esc = $this->db->real_escape_string($fi_stock);
                $ids_c7b_str  = implode(',', array_map('intval', $ids_c7b));

                $prov_map_c7b   = $this->repo->queryProveedorArticulos($ids_c7b_str, $fi_stock_esc, $ff);
                $precio_map_c7b = $this->repo->queryPrecioMedioCompra($ids_c7b_str, $fi_stock_esc, $ff);

                foreach ($incidencias as &$inc) {
                    if (!str_starts_with($inc['c7_subcaso'] ?? '', 'C7b')) continue;
                    $prov = $prov_map_c7b[$inc['idArticulo']] ?? null;
                    $inc['prov_habitual_nombre'] = $prov['prov_habitual_nombre'] ?? null;
                    $inc['prov_habitual_n']      = $prov['prov_habitual_n']      ?? null;
                    $inc['prov_ultimo_nombre']   = $prov['prov_ultimo_nombre']   ?? null;
                    $inc['prov_ultima_fecha']    = $prov['prov_ultima_fecha']    ?? null;
                    $inc['prov_es_mismo']        = $prov['prov_es_mismo']        ?? null;
                    // C7b-011: valor económico estimado del déficit
                    $precio = $precio_map_c7b[$inc['idArticulo']] ?? null;
                    $inc['precio_medio_compra'] = $precio;
                    $inc['coste_estimado']      = ($precio !== null)
                        ? round(abs((float)$inc['offset_estimado']) * $precio, 2)
                        : null;
                }
                unset($inc);
            }
        }

        // ── C7c: detección de cruces entre artículos (C7a + C7b activos) ─────
        // Se ejecuta solo cuando ambos subcasos están activos (sin checkbox propio)
        // Y cuando $skip_cde = false (fase final; con todos los artículos disponibles).
        // En lotes parciales se salta: los cruces solo tienen sentido sobre el conjunto
        // completo de artículos C7a+C7b; detectar dentro de un lote de 40 produce falsos
        // negativos (el artículo cruzado puede estar en otro lote).
        //
        // Hipótesis: un cajero confunde artículo A (C7a, merma aparente) con
        // artículo B (C7b, déficit sistemático) al pesar en la balanza. Los offsets
        // de A (positivos) y B (negativos) se cancelan: μ_A + μ_B ≈ 0.
        //
        // Filtros rígidos:
        //   · Mismo departamento N1 (distinto → imposible físicamente)
        //   · |μ_A + μ_B| / max(|μ_A|, |μ_B|) ≤ 0.45 (los offsets se cancelan, relativo)
        //   · ≥ 4 pares temporalmente solapados (±7 días)
        //   · CV < 0.70 en ambos (patrón regular)
        //   · Pearson ≥ 0.50 si n ≥ 4 (covarianza de los valores absolutos)
        //
        // Score = 0.50 × s_mag + 0.25 × correlación + 0.15 × s_disp + 0.10 × s_nombre
        //   · s_mag  = 1 − |μ_A + μ_B| / max(|μ_A|, |μ_B|)
        //   · s_disp = 1 − |log(σ_A / σ_B)| / 2  (similitud de dispersiones)
        //   · s_nombre: Jaccard sobre nombres
        //
        // Niveles: confirmado ≥ 0.80 | probable ≥ 0.65 | posible ≥ 0.50
        if (!$skip_cde && isset($subcasos_set['C7a']) && isset($subcasos_set['C7b'])) {

            // Indexar C7b con sus floors por fecha
            $c7b_data = [];
            foreach ($incidencias as $inc) {
                if ($inc['c7_subcaso'] !== 'C7b') continue;
                $c7b_data[$inc['idArticulo']] = [
                    'floors_raw' => $inc['_floors_raw'],
                ];
            }

            // Meta (nombre, familias N1) de todos los artículos C7 — una sola query
            $ids_c7   = implode(',', array_unique(array_column($incidencias, 'idArticulo')));
            $meta_c7c = $this->repo->queryMetaC7c($ids_c7);

            $cruces = [];   // id_c7a => ['id_b' => int, 'score' => float, 'nivel' => string]

            foreach ($incidencias as &$inc) {
                if ($inc['c7_subcaso'] !== 'C7a') continue;

                $mejor_score = 0.0;
                $mejor_id    = null;
                $mejor_nivel = '';

                $n1_a = $meta_c7c[$inc['idArticulo']]['familias_n1'] ?? [];

                foreach ($c7b_data as $id_b => $cb) {

                    // ── 1. Filtro duro N1: mismo departamento ────────────────────────
                    $n1_b = $meta_c7c[$id_b]['familias_n1'] ?? [];
                    if (!empty($n1_a) && !empty($n1_b) && empty(array_intersect($n1_a, $n1_b))) continue;

                    // ── 2. Emparejar suelos por fecha más próxima (±7 días) ──────────
                    // pairs_b guarda el valor negativo original del C7b
                    $pairs_a = [];
                    $pairs_b = [];
                    $ventana  = 7 * 86400;
                    foreach ($inc['_floors_raw'] as $da => $va) {
                        $ts_a      = strtotime($da);
                        $best_diff = $ventana + 1;
                        $best_vb   = null;
                        foreach ($cb['floors_raw'] as $db => $vb) {
                            $diff = abs($ts_a - strtotime($db));
                            if ($diff <= $ventana && $diff < $best_diff) {
                                $best_diff = $diff;
                                $best_vb   = $vb;
                            }
                        }
                        if ($best_vb !== null) {
                            $pairs_a[] = $va;
                            $pairs_b[] = $best_vb;   // valor negativo del C7b
                        }
                    }

                    $n_pairs = count($pairs_a);
                    if ($n_pairs < 4) continue;   // mínimo 4 pares solapados

                    [$mu_a, $sd_a] = PosstockStatistics::statsFloors($pairs_a);
                    [$mu_b, $sd_b] = PosstockStatistics::statsFloors($pairs_b);   // mu_b < 0

                    if ($mu_a < 1.5 || abs($mu_b) < 1.5) continue;   // offset pequeño → falsos positivos

                    // ── 3. Filtros rígidos ───────────────────────────────────────────
                    // 3a. |μ_A + μ_B| / max(|μ_A|, |μ_B|) ≤ 0.45: los offsets se cancelan
                    // (filtro relativo para que escale con la magnitud de los artículos)
                    $suma_mu     = abs($mu_a + $mu_b);
                    $suma_mu_rel = $suma_mu / max(abs($mu_a), abs($mu_b));
                    if ($suma_mu_rel > 0.45) continue;

                    // 3b. CV < 0.70 en ambos
                    $cv_a = ($mu_a > 0.0) ? $sd_a / $mu_a : PHP_FLOAT_MAX;
                    $cv_b = (abs($mu_b) > 0.0) ? $sd_b / abs($mu_b) : PHP_FLOAT_MAX;
                    if ($cv_a > 0.70 || $cv_b > 0.70) continue;

                    // ── 4. Correlación de Pearson (en valor absoluto) ────────────────
                    $pairs_b_abs = array_map('abs', $pairs_b);
                    $corr = ($n_pairs >= 4) ? PosstockStatistics::pearsonCorr($pairs_a, $pairs_b_abs) : 0.5;
                    if ($n_pairs >= 4 && $corr < 0.50) continue;

                    // ── 5. Score compuesto ───────────────────────────────────────────
                    $s_mag  = 1.0 - $suma_mu_rel;   // ya normalizado por max(|μ|)
                    $s_corr = max(0.0, (float)$corr);
                    $s_disp = ($sd_a > 0.0 && $sd_b > 0.0)
                        ? max(0.0, 1.0 - abs(log($sd_a / $sd_b)) / 2.0)
                        : 0.5;
                    $s_nombre = PosstockStatistics::jaccardNombre(
                        $meta_c7c[$inc['idArticulo']]['nombre'] ?? '',
                        $meta_c7c[$id_b]['nombre'] ?? ''
                    );

                    $score = $s_mag    * 0.50
                        + $s_corr   * 0.25
                        + $s_disp   * 0.15
                        + $s_nombre * 0.10;

                    if ($score < 0.50) continue;
                    $nivel = ($score >= 0.80) ? 'confirmado'
                        : (($score >= 0.65) ? 'probable' : 'posible');

                    if ($score > $mejor_score) {
                        $mejor_score = $score;
                        $mejor_id    = $id_b;
                        $mejor_nivel = $nivel;
                    }
                }

                if ($mejor_id !== null) {
                    $inc['posible_cruce_con'] = $mejor_id;
                    $inc['cruce_score']       = round($mejor_score, 2);
                    $inc['cruce_nivel']       = $mejor_nivel;
                    $cruces[$inc['idArticulo']] = [
                        'id_b'  => $mejor_id,
                        'score' => round($mejor_score, 2),
                        'nivel' => $mejor_nivel,
                    ];
                    $inc['posible_causa'] = sprintf(
                        'Merma con patrón complementario a art. %d (cruce %s, score=%.2f): probable confusión en la balanza de autopesaje entre ambos artículos',
                        $mejor_id,
                        $mejor_nivel,
                        $mejor_score
                    );
                }
            }
            unset($inc);

            // Propagar estado de cruce al lado C7b
            if (!empty($cruces)) {
                $c7b_cruce = [];   // id_c7b => [id_a, score, nivel] del mejor candidato C7a
                foreach ($cruces as $id_a => $data) {
                    $id_b = $data['id_b'];
                    if (!isset($c7b_cruce[$id_b]) || $data['score'] > $c7b_cruce[$id_b]['score']) {
                        $c7b_cruce[$id_b] = ['id_a' => $id_a, 'score' => $data['score'], 'nivel' => $data['nivel']];
                    }
                }
                foreach ($incidencias as &$inc) {
                    if ($inc['c7_subcaso'] !== 'C7b') continue;
                    if (!isset($c7b_cruce[$inc['idArticulo']])) continue;
                    $id_a  = $c7b_cruce[$inc['idArticulo']]['id_a'];
                    $score = $c7b_cruce[$inc['idArticulo']]['score'];
                    $nivel = $c7b_cruce[$inc['idArticulo']]['nivel'];
                    $inc['posible_cruce_con'] = $id_a;
                    $inc['cruce_score']       = $score;
                    $inc['cruce_nivel']       = $nivel;
                    $inc['posible_causa'] = sprintf(
                        'Déficit de ~%.0f ud. con patrón complementario a art. %d (cruce %s, score=%.2f): probable confusión en la balanza o recepción no registrada',
                        abs((float)$inc['offset_estimado']),
                        $id_a,
                        $nivel,
                        $score
                    );
                }
                unset($inc);
            }
        }

        // ── C7e / C7d — solo cuando ambos subcasos están activos ────────────
        // C7b-008: C7e y C7d se invocan aquí, con el mismo guard que C7c, para que
        // el ciclo de vida de _floors_raw quede completamente dentro de este método.
        if (!$skip_cde && isset($subcasos_set['C7a']) && isset($subcasos_set['C7b'])) {
            $this->getIncidenciasC7e($incidencias);
            $this->getIncidenciasC7d($incidencias);
        }

        // Limpiar _floors_raw siempre, independientemente de qué subcasos corrieron.
        // Este es el único punto donde se elimina el campo interno.
        foreach ($incidencias as &$inc) {
            unset($inc['_floors_raw']);
        }
        unset($inc);

        return $incidencias;
    }

    /**
     * C7d — Detección de tríos: dos artículos con déficit (C7b) explican
     * la merma de un tercer artículo (C7a).
     *
     * Hipótesis: μ_A + μ_B ≈ μ_C  (los déficits de A y B cancelan la merma de C).
     *
     * Criterios:
     *   · |μ_C − (μ_A + μ_B)| ≤ 0.5 ud  (suma casi exacta)
     *   · max(σ_A, σ_B, σ_C) / min(...) ≤ 2.0  (variabilidades similares)
     *   · Pearson(suma_AB, merma_C) ≥ 0.7
     *   · ≥ 6 triples solapados (ancla = intervalo de C, ±7 días para A y B)
     *   · Score = s_mag×0.60 + correlación×0.40; probable ≥ 0.75, posible ≥ 0.50
     *
     * Solo sustituye el match de C7c-par si el score del trío es estrictamente mayor.
     * Complejidad: O(N_c7a × N_c7b² × N_floors) — aceptable para N_c7b ≲ 100.
     *
     * @param array &$incidencias  Incidencias C7 (con _floors_raw presentes).
     */
    private function getIncidenciasC7d(array &$incidencias): void
    {
        // Activar solo si hay al menos un C7a y un C7b en esta pasada
        $has_c7a = $has_c7b = false;
        foreach ($incidencias as $inc) {
            if ($inc['c7_subcaso'] === 'C7a') $has_c7a = true;
            if ($inc['c7_subcaso'] === 'C7b') $has_c7b = true;
            if ($has_c7a && $has_c7b) break;
        }

        if ($has_c7a && $has_c7b) {

            // Indexar C7b (reutiliza la misma estructura que C7c)
            $c7b_data = [];
            foreach ($incidencias as $inc) {
                if ($inc['c7_subcaso'] !== 'C7b') continue;
                $c7b_data[$inc['idArticulo']] = [
                    'floors_raw' => $inc['_floors_raw'],
                    'ts_floors'  => $inc['_ts_floors']
                        ?? array_combine(
                            $keys = array_keys($inc['_floors_raw']),
                            array_map('strtotime', $keys)
                        ),
                ];
            }

            $c7b_ids = array_keys($c7b_data);
            $n_c7b   = count($c7b_ids);
            $trios   = [];   // id_c7a => ['id_a', 'id_b', 'score', 'nivel']

            // ── Pre-calcular emparejamientos (C7a, C7b) una sola vez ─────────
            // pair_match[id_c7a][id_c7b] = [floor_date_c7a => mejor_v_c7b]
            // Coste: O(N_a × N_b × F_c × F_b).  El triple loop interior pasa de
            // O(N_a × N_b² × F² ) a O(N_a × N_b² × F) con array_intersect_key.
            $ventana    = 7 * 86400;
            $pair_match = [];
            foreach ($incidencias as $inc_pm) {
                if ($inc_pm['c7_subcaso'] !== 'C7a') continue;
                $id_c     = $inc_pm['idArticulo'];
                $ts_c_map = $inc_pm['_ts_floors'] ?? null;
                $row      = [];
                foreach ($c7b_data as $id_b => $cb) {
                    $ts_b_map       = $cb['ts_floors'];
                    $best_per_floor = [];
                    foreach ($inc_pm['_floors_raw'] as $dc => $vc) {
                        $ts_c      = $ts_c_map ? $ts_c_map[$dc] : strtotime($dc);
                        $best_diff = $ventana + 1;
                        $best_v    = null;
                        foreach ($cb['floors_raw'] as $db => $vb) {
                            $diff = abs($ts_c - $ts_b_map[$db]);
                            if ($diff <= $ventana && $diff < $best_diff) {
                                $best_diff = $diff;
                                $best_v    = $vb;
                            }
                        }
                        if ($best_v !== null) $best_per_floor[$dc] = $best_v;
                    }
                    $row[$id_b] = $best_per_floor;
                }
                $pair_match[$id_c] = $row;
            }

            foreach ($incidencias as &$inc) {
                if ($inc['c7_subcaso'] !== 'C7a') continue;

                $matches_c   = $pair_match[$inc['idArticulo']] ?? [];
                // Superar el score del par (si C7c ya encontró algo)
                $mejor_score = isset($inc['cruce_score']) ? (float)$inc['cruce_score'] : 0.0;
                $mejor_trio  = null;

                for ($i = 0; $i < $n_c7b - 1; $i++) {
                    $id_a    = $c7b_ids[$i];
                    $match_a = $matches_c[$id_a] ?? [];
                    if (empty($match_a)) continue;

                    for ($j = $i + 1; $j < $n_c7b; $j++) {
                        $id_b    = $c7b_ids[$j];
                        $match_b = $matches_c[$id_b] ?? [];

                        // Solo fechas con match en ambos C7b
                        $common = array_intersect_key($match_a, $match_b);
                        if (count($common) < 6) continue;

                        $triples_c  = [];
                        $triples_a  = [];
                        $triples_b  = [];
                        $triples_ab = [];
                        foreach ($common as $dc => $_) {
                            $vc           = $inc['_floors_raw'][$dc];
                            $va           = abs($match_a[$dc]);
                            $vb           = abs($match_b[$dc]);
                            $triples_c[]  = $vc;
                            $triples_a[]  = $va;
                            $triples_b[]  = $vb;
                            $triples_ab[] = $va + $vb;
                        }

                        if (count($triples_c) < 6) continue;

                        [$mu_c,  $sd_c]  = PosstockStatistics::statsFloors($triples_c);
                        [$mu_ab] = PosstockStatistics::statsFloors($triples_ab);
                        [,       $sd_a_t] = PosstockStatistics::statsFloors($triples_a);
                        [,       $sd_b_t] = PosstockStatistics::statsFloors($triples_b);

                        if ($mu_c < 1.5 || $mu_ab < 1.5) continue;

                        // Criterio 1: |μ_C − (μ_A + μ_B)| ≤ 0.5 ud
                        $suma_neta = abs($mu_c - $mu_ab);
                        if ($suma_neta > 0.5) continue;

                        // Criterio 2: ratio dispersiones individuales ≤ 2.0
                        $sds = array_filter([$sd_a_t, $sd_b_t, $sd_c], fn($s) => $s > 0.0);
                        if (!empty($sds) && max($sds) / min($sds) > 2.0) continue;

                        // Criterio 3: Pearson(suma_AB, merma_C) ≥ 0.7
                        $corr = PosstockStatistics::pearsonCorr($triples_ab, $triples_c);
                        if ($corr < 0.7) continue;

                        // Score: magnitud (60 %) + correlación (40 %)
                        $s_mag = max(0.0, 1.0 - $suma_neta / max(0.5, $mu_c));
                        $score = $s_mag * 0.60 + max(0.0, $corr) * 0.40;
                        $nivel = ($score >= 0.75) ? 'probable' : 'posible';

                        if ($score > $mejor_score) {
                            $mejor_score = $score;
                            $mejor_trio  = [
                                'id_a'  => $id_a,
                                'id_b'  => $id_b,
                                'score' => round($score, 2),
                                'nivel' => $nivel,
                            ];
                        }
                    }
                }

                if ($mejor_trio !== null) {
                    $inc['posible_cruce_con'] = $mejor_trio['id_a'];
                    $inc['cruce_fuente_b']    = $mejor_trio['id_b'];
                    $inc['cruce_tipo']        = 'trio';
                    $inc['cruce_score']       = $mejor_trio['score'];
                    $inc['cruce_nivel']       = $mejor_trio['nivel'];
                    $trios[$inc['idArticulo']] = $mejor_trio;
                    $inc['posible_causa'] = sprintf(
                        'Merma con patrón trío art. %d + art. %d (%s, score=%.2f): posible confusión sistemática en la balanza de autopesaje',
                        $mejor_trio['id_a'],
                        $mejor_trio['id_b'],
                        $mejor_trio['nivel'],
                        $mejor_trio['score']
                    );
                }
            }
            unset($inc);

            // Propagar a los dos artículos C7b fuente
            foreach ($trios as $id_c => $trio) {
                foreach ($incidencias as &$inc) {
                    if ($inc['c7_subcaso'] !== 'C7b') continue;
                    $id_inc = $inc['idArticulo'];
                    if ($id_inc !== $trio['id_a'] && $id_inc !== $trio['id_b']) continue;
                    $otro = ($id_inc === $trio['id_a']) ? $trio['id_b'] : $trio['id_a'];
                    $inc['posible_cruce_con'] = $id_c;
                    $inc['cruce_fuente_b']    = $otro;
                    $inc['cruce_tipo']        = 'trio';
                    $inc['cruce_score']       = $trio['score'];
                    $inc['cruce_nivel']       = $trio['nivel'];
                    $inc['posible_causa'] = sprintf(
                        'Déficit de ~%.0f ud. incluido en trío junto a art. %d → art. %d (%s, score=%.2f): probable confusión en balanza o recepción no registrada',
                        abs((float)$inc['offset_estimado']),
                        $otro,
                        $id_c,
                        $trio['nivel'],
                        $trio['score']
                    );
                }
                unset($inc);
            }
        }
    }

    /**
     * C7e — Pérdidas fantasma por múltiplos: k unidades de A escaneadas como 1 de B
     * (o 1 de A como k de B), con k ∈ {2, 3, 4, 5}.
     *
     * Extensión de C7c para ratios k ≠ 1. Mientras C7c exige |μ_A + μ_B| ≤ 0.40,
     * C7e busca pares donde |μ_A| ≈ k × |μ_B| (dir A_por_B) o |μ_B| ≈ k × |μ_A|
     * (dir B_por_A), con k entero dentro del 20 % de tolerancia relativa.
     *
     * Casos típicos:
     *   · 2 barras de pan (A) cobradas como 1 paquete (B): k=2, dir=A_por_B
     *   · 1 kg de fruta (A) cobrado como 2 bandejas (B):  k=2, dir=B_por_A
     *
     * Criterios:
     *   · Mismo departamento N1 (filtro duro)
     *   · ≥ 5 pares solapados (±7 días)
     *   · Tolerancia: |k_raw − k| / k ≤ 0.20
     *   · Magnitud escalada: |μ_A + k×μ_B| ≤ 0.4×k  (dir A_por_B)
     *     o              |k×μ_A + μ_B| ≤ 0.4×k  (dir B_por_A)
     *   · Ratio de varianzas escaladas k²×σ²_A/σ²_B ∈ [0.5, 2]
     *   · CV < 0.70 en ambos
     *   · Pearson ≥ 0.50 sobre pares escalados (si n ≥ 4)
     *   · Score = 0.40×s_mag + 0.30×s_corr + 0.20×s_disp + 0.10×s_nombre
     *   · Solo anota si el score supera el match previo (C7c u otro C7e)
     *
     * Debe llamarse ANTES de getIncidenciasC7d (que borra _floors_raw).
     *
     * @param array &$incidencias  Incidencias C7 con _floors_raw presentes.
     */
    private function getIncidenciasC7e(array &$incidencias): void
    {
        $has_c7a = $has_c7b = false;
        foreach ($incidencias as $inc) {
            if ($inc['c7_subcaso'] === 'C7a') $has_c7a = true;
            if ($inc['c7_subcaso'] === 'C7b') $has_c7b = true;
            if ($has_c7a && $has_c7b) break;
        }
        if (!$has_c7a || !$has_c7b) return;

        // Indexar C7b con sus floors por fecha
        $c7b_data = [];
        foreach ($incidencias as $inc) {
            if ($inc['c7_subcaso'] !== 'C7b') continue;
            $c7b_data[$inc['idArticulo']] = [
                'floors_raw' => $inc['_floors_raw'],
                'ts_floors'  => $inc['_ts_floors']
                    ?? array_combine(
                        $keys = array_keys($inc['_floors_raw']),
                        array_map('strtotime', $keys)
                    ),
            ];
        }

        $ids_c7 = implode(',', array_unique(array_column($incidencias, 'idArticulo')));
        $meta   = $this->repo->queryMetaC7c($ids_c7);

        $cruces_e = [];   // id_c7a => ['id_b', 'score', 'nivel', 'k', 'dir']

        foreach ($incidencias as &$inc) {
            if ($inc['c7_subcaso'] !== 'C7a') continue;

            // Solo anotar si se supera el score previo (C7c u otro C7e)
            $mejor_score = $inc['cruce_score'] ?? 0.0;
            $mejor_id    = null;
            $mejor_nivel = '';
            $mejor_k     = 0;
            $mejor_dir   = '';

            $n1_a = $meta[$inc['idArticulo']]['familias_n1'] ?? [];

            foreach ($c7b_data as $id_b => $cb) {

                // ── Filtro duro N1 ────────────────────────────────────────────
                $n1_b = $meta[$id_b]['familias_n1'] ?? [];
                if (!empty($n1_a) && !empty($n1_b) && empty(array_intersect($n1_a, $n1_b))) continue;

                // ── Emparejamiento temporal ±7 días ───────────────────────────
                $pairs_a  = [];
                $pairs_b  = [];
                $ventana  = 7 * 86400;
                $ts_a_map = $inc['_ts_floors'] ?? null;
                $ts_b_map = $cb['ts_floors'];
                foreach ($inc['_floors_raw'] as $da => $va) {
                    $ts_a      = $ts_a_map ? $ts_a_map[$da] : strtotime($da);
                    $best_diff = $ventana + 1;
                    $best_vb   = null;
                    foreach ($cb['floors_raw'] as $db => $vb) {
                        $diff = abs($ts_a - $ts_b_map[$db]);
                        if ($diff <= $ventana && $diff < $best_diff) {
                            $best_diff = $diff;
                            $best_vb   = $vb;
                        }
                    }
                    if ($best_vb !== null) {
                        $pairs_a[] = $va;
                        $pairs_b[] = $best_vb;
                    }
                }

                $n_pairs = count($pairs_a);
                if ($n_pairs < 5) continue;

                [$mu_a, $sd_a] = PosstockStatistics::statsFloors($pairs_a);
                [$mu_b, $sd_b] = PosstockStatistics::statsFloors($pairs_b);   // mu_b < 0

                if ($mu_a < 1.5 || abs($mu_b) < 1.5) continue;

                // ── Determinar k y dirección ──────────────────────────────────
                $k_raw = $mu_a / abs($mu_b);   // > 0

                if ($k_raw >= 1.4) {
                    $k   = (int)round($k_raw);
                    $dir = 'A_por_B';   // k unidades de A por 1 de B
                } elseif ($k_raw <= 0.71) {
                    $k   = (int)round(1.0 / $k_raw);
                    $dir = 'B_por_A';   // 1 unidad de A por k de B
                } else {
                    continue;   // k ≈ 1: cubierto por C7c
                }

                if ($k < 2 || $k > 5) continue;

                // Tolerancia relativa ≤ 20 %
                $k_real  = ($dir === 'A_por_B') ? $k_raw : (1.0 / $k_raw);
                if (abs($k_real - $k) / $k > 0.20) continue;

                // ── Magnitud escalada ─────────────────────────────────────────
                $eps = 0.4 * $k;
                $suma_scaled = ($dir === 'A_por_B')
                    ? abs($mu_a + $k * $mu_b)    // μ_A + k×μ_B ≈ 0 (μ_b < 0)
                    : abs($k * $mu_a + $mu_b);   // k×μ_A + μ_B ≈ 0 (μ_b < 0)
                if ($suma_scaled > $eps) continue;

                // ── CV ────────────────────────────────────────────────────────
                $cv_a = ($mu_a > 0.0) ? $sd_a / $mu_a : PHP_FLOAT_MAX;
                $cv_b = (abs($mu_b) > 0.0) ? $sd_b / abs($mu_b) : PHP_FLOAT_MAX;
                if ($cv_a > 0.70 || $cv_b > 0.70) continue;

                // ── Ratio de varianzas escaladas k²×Var_A/Var_B ∈ [0.5, 2] ──
                $var_ratio = 1.0;
                if ($sd_a > 0.0 && $sd_b > 0.0) {
                    $var_ratio = ($dir === 'A_por_B')
                        ? ($k * $k * $sd_a * $sd_a) / ($sd_b * $sd_b)
                        : ($sd_a * $sd_a) / ($k * $k * $sd_b * $sd_b);
                    if ($var_ratio < 0.5 || $var_ratio > 2.0) continue;
                }

                // ── Pearson sobre pares escalados ─────────────────────────────
                if ($dir === 'A_por_B') {
                    $scaled_b = array_map(fn($v) => $k * abs($v), $pairs_b);
                    $corr = ($n_pairs >= 4) ? PosstockStatistics::pearsonCorr($pairs_a, $scaled_b) : 0.5;
                } else {
                    $scaled_a = array_map(fn($v) => $k * $v, $pairs_a);
                    $corr = ($n_pairs >= 4) ? PosstockStatistics::pearsonCorr($scaled_a, array_map('abs', $pairs_b)) : 0.5;
                }
                if ($n_pairs >= 4 && $corr < 0.50) continue;

                // ── Score ─────────────────────────────────────────────────────
                $s_mag    = max(0.0, 1.0 - $suma_scaled / $eps);
                $s_corr   = max(0.0, (float)$corr);
                $s_disp   = max(0.0, 1.0 - abs(log(sqrt($var_ratio))) / 2.0);
                $s_nombre = PosstockStatistics::jaccardNombre(
                    $meta[$inc['idArticulo']]['nombre'] ?? '',
                    $meta[$id_b]['nombre'] ?? ''
                );
                $score = $s_mag * 0.40 + $s_corr * 0.30 + $s_disp * 0.20 + $s_nombre * 0.10;

                if ($score < 0.50) continue;
                $nivel = ($score >= 0.85) ? 'confirmado' : (($score >= 0.70) ? 'probable' : 'posible');

                if ($score > $mejor_score) {
                    $mejor_score = $score;
                    $mejor_id    = $id_b;
                    $mejor_nivel = $nivel;
                    $mejor_k     = $k;
                    $mejor_dir   = $dir;
                }
            }

            if ($mejor_id !== null) {
                $causa = ($mejor_dir === 'A_por_B')
                    ? sprintf(
                        'Merma con patrón múltiplo k=%d respecto a art. %d (%s, score=%.2f): posible cobro de %d uds. de este artículo como 1 ud. de art. %d en la balanza',
                        $mejor_k,
                        $mejor_id,
                        $mejor_nivel,
                        $mejor_score,
                        $mejor_k,
                        $mejor_id
                    )
                    : sprintf(
                        'Merma con patrón múltiplo k=%d respecto a art. %d (%s, score=%.2f): posible cobro de 1 ud. de este artículo como %d uds. de art. %d en la balanza',
                        $mejor_k,
                        $mejor_id,
                        $mejor_nivel,
                        $mejor_score,
                        $mejor_k,
                        $mejor_id
                    );

                $inc['posible_cruce_con'] = $mejor_id;
                $inc['cruce_score']       = round($mejor_score, 2);
                $inc['cruce_nivel']       = $mejor_nivel;
                $inc['cruce_tipo']        = 'multiplo';
                $inc['cruce_ratio_k']     = $mejor_k;
                $inc['cruce_ratio_dir']   = $mejor_dir;
                $inc['posible_causa']     = $causa;
                $cruces_e[$inc['idArticulo']] = [
                    'id_b'  => $mejor_id,
                    'score' => round($mejor_score, 2),
                    'nivel' => $mejor_nivel,
                    'k'     => $mejor_k,
                    'dir'   => $mejor_dir,
                ];
            }
        }
        unset($inc);

        // Propagar al lado C7b (solo si mejora el match actual)
        if (!empty($cruces_e)) {
            $c7b_cruce = [];
            foreach ($cruces_e as $id_a => $data) {
                $id_b = $data['id_b'];
                if (!isset($c7b_cruce[$id_b]) || $data['score'] > $c7b_cruce[$id_b]['score']) {
                    $c7b_cruce[$id_b] = ['id_a' => $id_a] + $data;
                }
            }
            foreach ($incidencias as &$inc) {
                if ($inc['c7_subcaso'] !== 'C7b') continue;
                if (!isset($c7b_cruce[$inc['idArticulo']])) continue;
                $data = $c7b_cruce[$inc['idArticulo']];
                if (isset($inc['cruce_score']) && $data['score'] <= $inc['cruce_score']) continue;

                $k    = $data['k'];
                $dir  = $data['dir'];
                $id_a = $data['id_a'];

                $causa_b = sprintf(
                    'Déficit de ~%.0f ud. con patrón múltiplo k=%d respecto a art. %d (%s, score=%.2f): probable confusión en balanza o recepción no registrada',
                    abs((float)$inc['offset_estimado']),
                    $k,
                    $id_a,
                    $data['nivel'],
                    $data['score']
                );

                $inc['posible_cruce_con'] = $id_a;
                $inc['cruce_score']       = $data['score'];
                $inc['cruce_nivel']       = $data['nivel'];
                $inc['cruce_tipo']        = 'multiplo';
                $inc['cruce_ratio_k']     = $k;
                $inc['cruce_ratio_dir']   = $dir;
                $inc['posible_causa']     = $causa_b;
            }
            unset($inc);
        }
    }

    /**
     * Devuelve una página de idArticulo con actividad en el rango usando LIMIT/OFFSET.
     *
     * La paginación se hace directamente en SQL: nunca se cargan todos los IDs
     * en memoria PHP, lo que mantiene el coste constante por lote independientemente
     * del tamaño total del periodo.
     *
     * Fin de datos: cuando la query devuelva menos de $pagina filas.
     *
     * @param int $inicial  OFFSET SQL (0, 150, 300 …)
     * @param int $pagina   LIMIT SQL (tamaño del lote)
     */
    public function getArticulosConActividad(
        string $fi_mov,
        string $ff_mov,
        array  $familias_incluir = [],
        array  $familias_excluir = [],
        int    $inicial = 0,
        int    $pagina  = 0,       // 0 = sin límite (devuelve todos, solo para compatibilidad)
        array  $ids_proveedor_filter = []   // article IDs ya resueltos desde proveedores
    ): array {
        $fi = $this->db->real_escape_string($fi_mov);
        $ff = $this->db->real_escape_string($ff_mov);

        $where_fam = '';
        if (!empty($familias_incluir)) {
            $ids = $this->repo->expandirFamilias($familias_incluir);
            if ($ids) $where_fam .= " AND l.idArticulo IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids))";
        }
        if (!empty($familias_excluir)) {
            $ids = $this->repo->expandirFamilias($familias_excluir);
            if ($ids) $where_fam .= " AND l.idArticulo NOT IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids))";
        }

        $where_prov   = $this->repo->idsWhere($ids_proveedor_filter);
        $limit_clause = ($pagina > 0) ? "LIMIT $pagina OFFSET $inicial" : '';

        $rows = $this->repo->queryIdsConActividad($fi, $ff, $where_fam, $limit_clause, $where_prov);
        if (isset($rows['error'])) return [];

        $ids = [];
        foreach ($rows as $r) $ids[] = (int)$r['idArticulo'];
        return $ids;
    }

    public function getIncidenciasBatch(array $params, int $inicial, int $pagina): array
    {
        $fi_mov           = $params['fecha_inicio_movimientos'];
        $ff_mov           = $params['fecha_fin_movimientos'];
        $familias_incluir = (array)($params['familias_incluir'] ?? []);
        $familias_excluir = (array)($params['familias_excluir'] ?? []);
        $casos_incluir    = (array)($params['casos_incluir']    ?? []);

        // ── Resolver filtro de proveedores una sola vez para toda la paginación ──
        $proveedores_incluir      = (array)($params['proveedores_incluir']      ?? []);
        $proveedor_todos          = (bool)  ($params['proveedor_todos_productos'] ?? false);
        $ids_proveedor_filter     = [];
        $ids_proveedor_filter_c6b = [];  // C6b: todos los artículos del proveedor, sin filtro de estado
        $ids_str_prov             = '';
        if (!empty($proveedores_incluir)) {
            $ids_str_prov = implode(',', array_map('intval', $proveedores_incluir));
            if (!$proveedor_todos) {
                // Modo normal: solo artículos del proveedor con actividad en el periodo
                $rows_prov = $this->repo->queryIdsArticulosByProveedores($ids_str_prov);
                if (isset($rows_prov['error'])) return $rows_prov;
                $ids_proveedor_filter = array_column($rows_prov, 'idArticulo');
                if (empty($ids_proveedor_filter)) {
                    return ['filas' => [], 'actual' => $inicial, 'elementos' => 0];
                }
            }
            // C6b evalúa todos los artículos del proveedor independientemente del estado:
            // un artículo inactivo en articulosProveedores puede seguir en stock y vendiendo.
            $rows_prov_c6b = $this->repo->queryIdsArticulosByProveedoresTodos($ids_str_prov);
            if (!isset($rows_prov_c6b['error'])) {
                $ids_proveedor_filter_c6b = array_column($rows_prov_c6b, 'idArticulo');
            }
        }

        // C4 y C6b no son paginables por actividad en el periodo:
        //   · C4: artículos sin movimiento (nunca aparecerían en la paginación normal)
        //   · C6b: ventana fija anclada en hoy, independiente del periodo analizado
        // Ruta especial cuando son el único caso solicitado: lote único, elementos=0 → JS para
        $casos_no_paginables = ['caso4', 'caso6b'];
        $casos_sin_paginables = array_values(array_diff($casos_incluir, $casos_no_paginables));

        if (empty($casos_sin_paginables)) {
            $filas = $this->getIncidencias($params);
            if (isset($filas['error'])) return $filas;
            return ['filas' => $filas, 'actual' => count($filas), 'elementos' => 0, 'pagina_efectiva' => 0]; // elementos=0 → fin
        }

        // C7a y C7b requieren cálculo estadístico intensivo por artículo (bootstrap 999
        // iteraciones, Mann-Kendall O(n²), Theil-Sen O(n²)). Con lotes grandes el proceso
        // puede tardar varios minutos y provocar que MySQL cierre la conexión por wait_timeout.
        // Se reduce el tamaño del lote para que cada request tarde <30 s.
        // C7c/d/e se salta en estos lotes parciales: requiere el conjunto COMPLETO de
        // artículos C7a+C7b para detectar cruces; ver resolverPOSStockC7cde.php (fase 2).
        $casos_c7_activos = array_intersect($casos_incluir, ['caso7a', 'caso7b']);
        if (!empty($casos_c7_activos)) {
            // C7 usa bootstrap O(n²) por artículo — lotes más grandes que otros casos pero
            // razonables. C7cde ya corre aparte (fase 2), así que el riesgo de timeout
            // por lote es menor. 80 artículos ≈ 10–20 s de procesado estadístico.
            $pagina = min($pagina, 80);
        }

        // C9 es O(n) por artículo (backstaging lineal) pero ejecuta 5 queries extra
        // (recepciones, timeline, prov/cli especiales, stock rebobinado). Lotes de 150
        // son seguros; si se combina con C7 la reducción ya aplica.
        if (in_array('caso9', $casos_incluir, true) && !in_array('caso7a', $casos_incluir, true) && !in_array('caso7b', $casos_incluir, true)) {
            $pagina = min($pagina, 150);
        }

        // En batches mixtos, excluir C4 y C6b de la paginación;
        // C6b se añade al primer lote (inicial === 0) para que aparezca una sola vez.
        $params_batch = $params;
        $params_batch['casos_incluir'] = $casos_sin_paginables;
        // C7c/d/e necesita todos los artículos C7a+C7b: se salta en lotes parciales
        // y corre en una llamada final independiente (resolverPOSStockC7cde).
        if (!empty($casos_c7_activos)) {
            $params_batch['skip_c7_cde'] = true;
        }

        // ── Obtener IDs del lote según modo de proveedor ─────────────────────
        if ($proveedor_todos && $ids_str_prov !== '') {
            // Modo "todos los productos del proveedor": paginar directamente sobre
            // articulosProveedores, sin filtro de actividad en el periodo.
            $ids_batch = $this->repo->queryArticulosProveedorPaginados($ids_str_prov, $inicial, $pagina);
            if (isset($ids_batch['error'])) return $ids_batch;
            // El filtro de proveedor ya está embebido en ids_filter; no aplicar doble filtro
            $params_batch['ids_proveedor_filter'] = [];
        } else {
            // Modo normal: artículos con actividad en el periodo (intersectado con proveedor si aplica)
            $params_batch['ids_proveedor_filter'] = $ids_proveedor_filter;
            $ids_batch = $this->getArticulosConActividad(
                $fi_mov,
                $ff_mov,
                $familias_incluir,
                $familias_excluir,
                $inicial,
                $pagina,
                $ids_proveedor_filter
            );
        }

        $elementos = count($ids_batch);
        $actual    = $inicial + $elementos;

        if ($elementos === 0) {
            return ['filas' => [], 'actual' => $actual, 'elementos' => 0];
        }

        $params_batch['ids_filter'] = $ids_batch;

        $filas = $this->getIncidencias($params_batch);
        if (isset($filas['error'])) return $filas;

        // Añadir C4/C6b al primer lote únicamente (evita duplicados en lotes sucesivos)
        if ($inicial === 0) {
            $casos_extra = array_values(array_intersect($casos_incluir, $casos_no_paginables));
            if (!empty($casos_extra)) {
                $params_extra = $params;
                $params_extra['casos_incluir'] = $casos_extra;
                // C6b usa su propio conjunto de IDs (todos los artículos del proveedor, sin filtro de estado)
                if (!empty($ids_proveedor_filter_c6b)) {
                    $params_extra['ids_proveedor_filter_c6b'] = $ids_proveedor_filter_c6b;
                }
                $filas_extra = $this->getIncidencias($params_extra);
                if (!isset($filas_extra['error'])) {
                    $filas = array_merge($filas, $filas_extra);
                }
            }
        }

        return [
            'filas'          => $filas,
            'actual'         => $actual,
            'elementos'      => $elementos,
            'pagina_efectiva' => $pagina,   // el JS usa este valor para saber si hay más lotes
        ];
    }

    /**
     * Fase 2 de C7: resuelve los cruces C7c/d/e sobre el conjunto COMPLETO de artículos
     * C7a+C7b ya detectados en los lotes de la fase 1.
     *
     * OPTIMIZACIÓN: NO re-ejecuta la cascada estadística (Mann-Kendall, bootstrap,
     * Theil-Sen, Wilcoxon…). Solo necesita _floors_raw + offset + dispersión, que se
     * obtienen con 2 SQL + un bucle de cálculo de suelos. Para 5–50 artículos es
     * prácticamente instantáneo incluso en periodos trimestrales/anuales.
     *
     * @param  array  $params   Fechas + familias (fi_mov, ff_mov, fi_stock, familias_*).
     * @param  int[]  $ids_c7a  idArticulo de artículos detectados como C7a en fase 1.
     * @param  int[]  $ids_c7b  idArticulo de artículos detectados como C7b en fase 1.
     * @return array  ['cruces' => [idArticulo => [cruce fields]]] o ['error' => ...]
     */
    public function resolverC7cde(array $params, array $ids_c7a, array $ids_c7b): array
    {
        $ids_c7a = array_values(array_unique(array_map('intval', $ids_c7a)));
        $ids_c7b = array_values(array_unique(array_map('intval', $ids_c7b)));
        $ids_all = array_values(array_unique(array_merge($ids_c7a, $ids_c7b)));
        if (empty($ids_all)) return ['cruces' => []];

        $fi_mov  = $params['fecha_inicio_movimientos'];
        $ff_mov  = $params['fecha_fin_movimientos'];
        $fi_stock = $params['fecha_inicio_stock'];
        $familias_incluir = (array)($params['familias_incluir'] ?? []);
        $familias_excluir = (array)($params['familias_excluir'] ?? []);

        $fi_stk = $this->db->real_escape_string($fi_stock);
        $ff     = $this->db->real_escape_string($ff_mov);
        $wf     = $this->repo->familiaWhere($familias_incluir, $familias_excluir);
        $wi     = $this->repo->idsWhere($ids_all);

        // ── SQL 1: recepciones en la ventana fi_stock→ff_mov ─────────────────
        $rows_rec = $this->repo->queryRecepcionesFechasC7($fi_stk, $ff, $wf, $wi);
        if (isset($rows_rec['error'])) return $rows_rec;
        if (empty($rows_rec)) return ['cruces' => []];

        $recepciones_map = [];
        foreach ($rows_rec as $r) {
            $recepciones_map[(int)$r['idArticulo']][] = $r['fecha'];
        }
        foreach ($recepciones_map as $id => $fechas) {
            $u = array_values(array_unique($fechas));
            sort($u);
            $recepciones_map[$id] = $u;
        }

        // ── SQL 2: timeline de movimientos ───────────────────────────────────
        $ids_str = implode(',', $ids_all);
        $rows_tl = $this->repo->queryTimelineMovimientosC7($fi_stk, $ff, $ids_str);
        if (isset($rows_tl['error'])) return $rows_tl;

        $daily_map = [];
        foreach ($rows_tl as $r) {
            $daily_map[(int)$r['idArticulo']][$r['fecha']] = (float)$r['day_delta'];
        }

        // ── Construir incidencias mínimas para C7c/d/e ───────────────────────
        // Solo se necesita: idArticulo, c7_subcaso, _floors_raw, offset_estimado, dispersion.
        // No se ejecuta ninguna cascada estadística.
        $ids_c7a_set = array_flip($ids_c7a);
        $ids_c7b_set = array_flip($ids_c7b);
        $incidencias_cde = [];

        foreach ($ids_all as $id) {
            if (!isset($recepciones_map[$id])) continue;
            $fechas_rec = $recepciones_map[$id];
            $n_rec      = count($fechas_rec);
            $daily      = $daily_map[$id] ?? [];

            // Stock acumulado por fecha (mismo cálculo que getIncidenciasC7)
            $cum_delta     = 0.0;
            $stock_by_date = [];
            $all_dates     = array_keys($daily);
            sort($all_dates);
            foreach ($all_dates as $d) {
                $cum_delta        += $daily[$d];
                $stock_by_date[$d] = $cum_delta;
            }
            if (empty($stock_by_date)) continue;

            // Suelos inter-recepción
            $floors        = [];
            $fechas_floors = [];
            for ($i = 0; $i < $n_rec; $i++) {
                $fecha_ini = $fechas_rec[$i];
                $fecha_fin = ($i + 1 < $n_rec) ? $fechas_rec[$i + 1] : null;
                $min_floor = null;
                foreach ($stock_by_date as $d => $stock) {
                    if ($d < $fecha_ini) continue;
                    if ($fecha_fin !== null && $d >= $fecha_fin) continue;
                    if ($min_floor === null || $stock < $min_floor) $min_floor = $stock;
                }
                if ($min_floor !== null) {
                    $floors[]        = $min_floor;
                    $fechas_floors[] = $fecha_ini;
                }
            }
            $n_floors = count($floors);
            if ($n_floors < 2) continue;

            $floors_map = [];
            $ts_map     = [];
            for ($i = 0; $i < $n_floors; $i++) {
                $fecha = $fechas_floors[$i];
                $floors_map[$fecha] = $floors[$i];
                $ts_map[$fecha]     = strtotime($fecha);
            }

            $mean_raw = array_sum($floors) / $n_floors;
            $var_raw  = 0.0;
            foreach ($floors as $f) {
                $var_raw += ($f - $mean_raw) ** 2;
            }
            $std_raw  = $n_floors > 1 ? sqrt($var_raw / ($n_floors - 1)) : 0.0;

            // Determinar subcaso desde las listas de la fase 1
            if (isset($ids_c7a_set[$id])) {
                $subcaso = 'C7a';
            } elseif (isset($ids_c7b_set[$id])) {
                $subcaso = 'C7b';
            } else {
                continue; // no debería ocurrir
            }

            $incidencias_cde[] = [
                'idArticulo'      => $id,
                'c7_subcaso'      => $subcaso,
                '_floors_raw'     => $floors_map,
                '_ts_floors'      => $ts_map,
                'offset_estimado' => round($mean_raw, 1),
                'dispersion'      => round($std_raw,  1),
                'posible_causa'   => '',
            ];
        }

        if (empty($incidencias_cde)) return ['cruces' => []];

        // ── Ejecutar C7c / C7e / C7d sobre el conjunto completo ──────────────
        // C7c: cruces de pares C7a ↔ C7b
        $c7b_data   = [];
        foreach ($incidencias_cde as $inc) {
            if ($inc['c7_subcaso'] !== 'C7b') continue;
            $c7b_data[$inc['idArticulo']] = [
                'floors_raw' => $inc['_floors_raw'],
                'ts_floors'  => $inc['_ts_floors'],
            ];
        }
        $ids_str_cde = implode(',', array_unique(array_column($incidencias_cde, 'idArticulo')));
        $meta_c7c    = $this->repo->queryMetaC7c($ids_str_cde);
        $cruces_map  = [];

        // ── C7c inline (replicado del bloque en getIncidenciasC7) ────────────
        foreach ($incidencias_cde as &$inc) {
            if ($inc['c7_subcaso'] !== 'C7a') continue;
            $mejor_score = 0.0;
            $mejor_id    = null;
            $mejor_nivel = '';
            $n1_a = $meta_c7c[$inc['idArticulo']]['familias_n1'] ?? [];

            foreach ($c7b_data as $id_b => $cb) {
                $n1_b = $meta_c7c[$id_b]['familias_n1'] ?? [];
                if (!empty($n1_a) && !empty($n1_b) && empty(array_intersect($n1_a, $n1_b))) continue;

                // Emparejar suelos por fecha próxima (±7 días)
                $pairs_a  = [];
                $pairs_b = [];
                $ventana  = 7 * 86400;
                $ts_a_map = $inc['_ts_floors'];
                $ts_b_map = $cb['ts_floors'];
                foreach ($inc['_floors_raw'] as $da => $va) {
                    $ts_a    = $ts_a_map[$da];
                    $best_vb = null;
                    $best_diff = $ventana + 1;
                    foreach ($cb['floors_raw'] as $db => $vb) {
                        $diff = abs($ts_a - $ts_b_map[$db]);
                        if ($diff <= $ventana && $diff < $best_diff) {
                            $best_diff = $diff;
                            $best_vb = $vb;
                        }
                    }
                    if ($best_vb !== null) {
                        $pairs_a[] = $va;
                        $pairs_b[] = $best_vb;
                    }
                }
                $n_pairs = count($pairs_a);
                if ($n_pairs < 4) continue;

                // Media y CV de cada lado
                $mu_a = array_sum($pairs_a) / $n_pairs;
                $mu_b = array_sum($pairs_b) / $n_pairs;
                $max_abs = max(abs($mu_a), abs($mu_b));
                if ($max_abs < 1e-9) continue;
                if (abs($mu_a + $mu_b) / $max_abs > 0.45) continue;

                $std_a = 0.0;
                foreach ($pairs_a as $v) {
                    $std_a += ($v - $mu_a) ** 2;
                }
                $std_a = $n_pairs > 1 ? sqrt($std_a / ($n_pairs - 1)) : 0.0;
                $std_b = 0.0;
                foreach ($pairs_b as $v) {
                    $std_b += ($v - $mu_b) ** 2;
                }
                $std_b = $n_pairs > 1 ? sqrt($std_b / ($n_pairs - 1)) : 0.0;
                $cv_a  = $mu_a != 0.0 ? $std_a / abs($mu_a) : PHP_FLOAT_MAX;
                $cv_b  = $mu_b != 0.0 ? $std_b / abs($mu_b) : PHP_FLOAT_MAX;
                if ($cv_a >= 0.70 || $cv_b >= 0.70) continue;

                // Pearson sobre valores absolutos
                $pearson = 0.0;
                if ($n_pairs >= 4 && $std_a > 0.0 && $std_b > 0.0) {
                    $cov = 0.0;
                    for ($k = 0; $k < $n_pairs; $k++) {
                        $cov += (abs($pairs_a[$k]) - abs($mu_a)) * (abs($pairs_b[$k]) - abs($mu_b));
                    }
                    $pearson = $cov / ($n_pairs * $std_a * $std_b);
                }
                if ($pearson < 0.50) continue;

                // Score
                $s_mag   = 1.0 - abs($mu_a + $mu_b) / $max_abs;
                $s_disp  = ($std_a > 0.0 && $std_b > 0.0)
                    ? max(0.0, 1.0 - abs(log($std_a / $std_b)) / 2.0) : 0.0;
                $nom_a   = $meta_c7c[$inc['idArticulo']]['nombre'] ?? '';
                $nom_b   = $meta_c7c[$id_b]['nombre'] ?? '';
                $tok_a   = array_flip(preg_split('/\W+/u', mb_strtolower($nom_a), -1, PREG_SPLIT_NO_EMPTY));
                $tok_b   = array_flip(preg_split('/\W+/u', mb_strtolower($nom_b), -1, PREG_SPLIT_NO_EMPTY));
                $inter   = count(array_intersect_key($tok_a, $tok_b));
                $union   = count(array_merge($tok_a, $tok_b));
                $s_nom   = $union > 0 ? $inter / $union : 0.0;
                $score   = 0.50 * $s_mag + 0.25 * $pearson + 0.15 * $s_disp + 0.10 * $s_nom;

                $nivel = $score >= 0.80 ? 'confirmado' : ($score >= 0.65 ? 'probable' : ($score >= 0.50 ? 'posible' : ''));
                if ($nivel === '' || $score <= $mejor_score) continue;
                $mejor_score = $score;
                $mejor_id = $id_b;
                $mejor_nivel = $nivel;
            }

            if ($mejor_id !== null) {
                $inc['posible_cruce_con'] = $mejor_id;
                $inc['cruce_score']       = round($mejor_score, 2);
                $inc['cruce_nivel']       = $mejor_nivel;
                $cruces_map[$inc['idArticulo']] = ['id_b' => $mejor_id, 'score' => round($mejor_score, 2), 'nivel' => $mejor_nivel];
                $inc['posible_causa'] = sprintf(
                    'Merma con patrón complementario a art. %d (cruce %s, score=%.2f): probable confusión en la balanza de autopesaje entre ambos artículos',
                    $mejor_id,
                    $mejor_nivel,
                    $mejor_score
                );
            }
        }
        unset($inc);

        // C7e y C7d sobre el conjunto completo (después de C7c)
        $this->getIncidenciasC7e($incidencias_cde);
        $this->getIncidenciasC7d($incidencias_cde);

        // Propagar cruce al lado C7b
        if (!empty($cruces_map)) {
            $c7b_cruce = [];
            foreach ($cruces_map as $id_a => $data) {
                $id_b = $data['id_b'];
                if (!isset($c7b_cruce[$id_b]) || $data['score'] > $c7b_cruce[$id_b]['score']) {
                    $c7b_cruce[$id_b] = ['id_a' => $id_a, 'score' => $data['score'], 'nivel' => $data['nivel']];
                }
            }
            foreach ($incidencias_cde as &$inc) {
                if ($inc['c7_subcaso'] !== 'C7b') continue;
                if (!isset($c7b_cruce[$inc['idArticulo']])) continue;
                $d = $c7b_cruce[$inc['idArticulo']];
                $inc['posible_cruce_con'] = $d['id_a'];
                $inc['cruce_score']       = $d['score'];
                $inc['cruce_nivel']       = $d['nivel'];
                $inc['posible_causa'] = sprintf(
                    'Déficit de ~%.0f ud. con patrón complementario a art. %d (cruce %s, score=%.2f): probable confusión en la balanza o recepción no registrada',
                    abs((float)($inc['offset_estimado'] ?? 0)),
                    $d['id_a'],
                    $d['nivel'],
                    $d['score']
                );
            }
            unset($inc);
        }

        // C7e y C7d ya corrieron arriba; eliminar _floors_raw antes de devolver
        foreach ($incidencias_cde as &$inc) {
            unset($inc['_floors_raw']);
        }
        unset($inc);

        // Extraer solo las anotaciones de cruce
        $cruces = [];
        foreach ($incidencias_cde as $f) {
            if (!isset($f['posible_cruce_con'])) continue;
            $cruces[(int)$f['idArticulo']] = [
                'posible_cruce_con' => (int)$f['posible_cruce_con'],
                'cruce_score'       => $f['cruce_score']  ?? null,
                'cruce_nivel'       => $f['cruce_nivel']  ?? null,
                'posible_causa'     => $f['posible_causa'] ?? null,
            ];
        }

        return ['cruces' => $cruces];
    }
}
