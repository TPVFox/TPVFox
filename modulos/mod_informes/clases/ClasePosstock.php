<?php

/**
 * ClasePosstock — Lógica de datos para el informe POSStock.
 *
 * ARQUITECTURA *
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
 * DECISIÓN: T4.3 EN PHP, NO EN SQL *
 * MariaDB 10.11 (verificado con SELECT VERSION()) soporta window functions
 * (SUM() OVER, ROW_NUMBER(), etc. disponibles desde 10.2).
 * Se eligió PHP porque T4.1 y T4.2 ya están en memoria y una tercera consulta
 * SQL con CTE + window frame habría replicado la misma lógica con más complejidad
 * y sin ganancia de rendimiento para el volumen de datos esperado (~4.000 filas).
 *
 * ESTADOS DE DOCUMENTO VÁLIDOS *
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
 * REGLA "DÍA ANTERIOR" PARA stock_previo *
 *   stock_previo de una entrada en fecha D = saldo_base + Σ movimientos con fecha < D
 *   Si varias entradas caen el mismo día D, todas comparten el mismo stock_previo
 *   (estado al inicio de D, antes de que llegue ningún albarán de ese día).
 *   Las ventas del mismo día D se excluyen del stock_previo (se contabilizarán
 *   en el stock_previo de entradas de días posteriores).
 *
 * USO *
 *   $p   = new ClasePosstock($BDTpv);
 *   $mov = $p->getMovimientosPeriodo('2025-02-01', '2025-02-28');
 *   $ids = array_unique(array_column($mov, 'idArticulo'));
 *   $base = $p->getStockBase($ids, '2025-01-01', '2025-01-31');
 *   $calc = $p->calcularStockPrevio($mov, $base);
 *   // $calc: array de entradas con stock_previo y stock_tras_ultimo_albaran
 *
 */
require_once __DIR__ . '/PosstockStatistics.php';
require_once __DIR__ . '/PosstockQueryRepository.php';
require_once __DIR__ . '/PosstockParamsDTO.php';
require_once __DIR__ . '/PosstockC1Detector.php';
require_once __DIR__ . '/PosstockC2Detector.php';
require_once __DIR__ . '/PosstockC3Detector.php';
require_once __DIR__ . '/PosstockC4Detector.php';
require_once __DIR__ . '/PosstockC5Detector.php';
require_once __DIR__ . '/PosstockC6Detector.php';
require_once __DIR__ . '/PosstockC9Detector.php';
require_once __DIR__ . '/PosstockC7Detector.php';

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
    private PosstockC7Detector  $c7;

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
        $this->c7   = new PosstockC7Detector($conexion, $this->repo);
    }

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

        $idsArticulosCsv = $this->convertirIdsACsv($ids_filter);
        $where_ids = $idsArticulosCsv !== ''
            ? " AND l.idArticulo IN ($idsArticulosCsv)"
            : '';

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
        $ids = $this->convertirIdsACsv($ids_articulos);
        if ($ids === '') {
            return [];
        }

        $filas = $this->repo->queryStockBase($fi, $ff, $ids);
        if (isset($filas['error'])) return $filas;

        $resultado = [];
        foreach ($filas as $fila) {
            $resultado[(int)$fila['idArticulo']] = [
                'saldo_acumulado' => (float)$fila['saldo_acumulado'],
                'ultima_compra'   => $fila['ultima_compra'],
                'ultima_venta'    => $fila['ultima_venta'],
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
                $signo = $this->resolverSignoMovimiento($m['tipo_movimiento'] ?? '');
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
            $ultima_fecha_entrada = $this->obtenerUltimaFechaEntradas($entradas);
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
        $paramsDto = PosstockParamsDTO::fromArray($params);

        $fi_mov      = $paramsDto->fecha_inicio_movimientos;
        $ff_mov      = $paramsDto->fecha_fin_movimientos;
        $fi_stock    = $paramsDto->fecha_inicio_stock;
        $ff_stock    = $paramsDto->fecha_fin_stock;
        $fi_periodo  = $paramsDto->fecha_inicio_periodo;
        // Ventana estadística ampliada para semana/quincena/mes (±1 periodo)
        $fi_stats = $paramsDto->fecha_inicio_stats;
        $ff_stats = $paramsDto->fecha_fin_stats;

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
        $familias_incluir    = $paramsDto->familias_incluir;
        $familias_excluir    = $paramsDto->familias_excluir;
        $ids_filter          = $paramsDto->ids_filter;

        $filtroProveedor = $this->resolverIdsFiltroConProveedores($params, $ids_filter);
        if (isset($filtroProveedor['error'])) return $filtroProveedor;
        if (!empty($filtroProveedor['sin_resultados'])) return [];
        $ids_filter = $filtroProveedor['ids_filter'];

        $proveedores_incluir = $paramsDto->proveedores_incluir;
        if (!empty($filtroProveedor['ids_proveedor_filter'])) {
            $ids_proveedor_filter = $filtroProveedor['ids_proveedor_filter'];
        } else {
            $ids_proveedor_filter = [];
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

        // Precalcular stock_base compartido para C1, C2, C7 y C9
        $sb_shared = [];
        if (!empty($ids_filter) && (isset($casos_set['caso1']) || isset($casos_set['caso2']) || isset($casos_set['caso7a']) || isset($casos_set['caso7b']) || isset($casos_set['caso9']))) {
            $sb_shared = $this->getStockBase($ids_filter, $fi_stock, $ff_stock);
            if (isset($sb_shared['error'])) return $sb_shared;
        }

        // C1
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
        foreach ($incidencias as $incidencia) {
            if ($incidencia['tipo'] === 'Inventario en negativo') {
                $ids_con_c1a[$incidencia['idArticulo']] = true;
            }
        }

        // C2
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

        // C3 (3a y/o 3b — una sola query, filtrar resultado por sub-caso)
        if (isset($casos_set['caso3a']) || isset($casos_set['caso3b'])) {
            $c3 = $this->c3->detectar(
                $fi_mov,
                $ff_mov,
                $fi_stock,
                $fi_periodo,
                $umbral_caducidad,
                $umbral_sin_rotacion,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $c3b_dias_post,
                $c3a_multiplicador,
                $c5_incluir_stock_negativo
            );
            if (isset($c3['error'])) return $c3;
            // Filtrar sub-casos si no se piden ambos
            if (!isset($casos_set['caso3a']) || !isset($casos_set['caso3b'])) {
                $tipos_c3 = [];
                if (isset($casos_set['caso3a'])) $tipos_c3[] = 'Caducidad teórica';
                if (isset($casos_set['caso3b'])) $tipos_c3[] = 'Entrada sin rotación previa';
                $c3 = array_values(array_filter($c3, fn($incidencia) => in_array($incidencia['tipo'], $tipos_c3, true)));
            }
            $incidencias = array_merge($incidencias, $c3);
        }

        $incluir_albcli = (bool)($params['incluir_albcli_ventas'] ?? false);

        // C5
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

        // C6a — ROP estacional (ventana ligada al periodo ±1)
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

        // C6b — ROP operacional (ventana histórica fija anclada en hoy)        // A diferencia de C6a (ligada al periodo analizado), C6b siempre mide
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
            // Un artículo con cualquier estado en articulosProveedores puede seguir en stock y vendiendo.
            if (!empty($proveedores_incluir)) {
                $idsProvC6bCsv = implode(',', array_map('intval', $proveedores_incluir));
                $filasC6b = $this->repo->queryIdsArticulosByProveedoresTodos($idsProvC6bCsv);
                $ids_c6b  = isset($filasC6b['error']) ? $ids_proveedor_filter : array_column($filasC6b, 'idArticulo');
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
            // Marcar si el proveedor seleccionado es el proveedor principal del artículo
            // según articulos.idProveedor (no el estado en articulosProveedores).
            $proveedoresSet = array_flip(array_map('strval', $proveedores_incluir ?: []));
            foreach ($c6b as &$incidenciaC6b) {
                $idProvArticulo = (string)($incidenciaC6b['articulo_idProveedor'] ?? '');
                $incidenciaC6b['proveedor_es_principal'] = isset($proveedoresSet[$idProvArticulo]);
            }
            unset($incidenciaC6b);
            $incidencias = array_merge($incidencias, $c6b);
        }

        // C7a / C7b — Offset sistemático de inventario        // Se ejecutan con una sola pasada SQL si ambos están activos.
        $c7_subcasos = [];
        if (isset($casos_set['caso7a'])) $c7_subcasos[] = 'C7a';
        if (isset($casos_set['caso7b'])) $c7_subcasos[] = 'C7b';
        if (!empty($c7_subcasos)) {
            $c7 = $this->c7->detectar(
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

        // C9 — Merma por backstaging LIFO inverso
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

        // C4: solo si solicitado explícitamente (no paginable por actividad).
        if (isset($casos_set['caso4'])) {
            $articulos_sin_mov = $this->c4->detectar(
                $fi_mov,
                $ff_mov,
                $familias_incluir,
                $familias_excluir,
                $c5_incluir_stock_negativo,
                $ids_filter
            );
            if (isset($articulos_sin_mov['error'])) return $articulos_sin_mov;
            foreach ($this->c4->formatearIncidencias($articulos_sin_mov) as $incidencia) {
                $incidencias[] = $incidencia;
            }
        }

        // Marcar stock_no_fiable en C5/C6 cuando el artículo tiene C1a activo
        if (!empty($ids_con_c1a)) {
            foreach ($incidencias as &$incidencia) {
                if (
                    isset($ids_con_c1a[$incidencia['idArticulo']]) &&
                    in_array($incidencia['tipo'], ['Rotura de Stock', 'Agotamiento Estimado', 'Punto de Pedido'], true)
                ) {
                    $incidencia['stock_no_fiable'] = true;
                }
            }
            unset($incidencia);
        }

        // Ordenar: CRITICA -> ALTA -> MEDIA (C2->C5->C3a) -> BAJA (C3b sin-rot->C3b nunca->C4).
        $orden_sev = ['CRITICA' => 0, 'ALTA' => 1, 'MEDIA' => 2, 'BAJA' => 3];

        $orden_tipo_media = [
            'Entrada con stock alto'             => 0,  // C2
            'Venta Cero (Posible Rotura Física)' => 1,  // C5
            'Agotamiento Estimado'               => 2,  // C6a MEDIA (BN, stock suficiente)
            'Punto de Pedido'                    => 2,  // C6b MEDIA (mismo nivel que C6a)
            'Caducidad teórica'                  => 3,  // C3a
            'Entrada no registrada'              => 4,  // C7b
            'Merma acumulada'                    => 4,  // C7a
            'Merma backstaging'                  => 4,  // C9
        ];

        // C3b con ultima_salida (Sin rotación) antes que sin ultima_salida (Nunca salidas)
        $subtipo_baja = static function (array $incidencia): int {
            if ($incidencia['tipo'] === 'Entrada sin rotación previa') {
                return (isset($incidencia['ultima_salida'])) ? 0 : 1;
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
        foreach ($incidencias as $incidencia) {
            if ($incidencia['tipo'] === $c5_tipo) {
                $id = $incidencia['idArticulo'];
                $fechaRotura  = $incidencia['fecha_inicio_rotura'] ?? '0000-00-00';
                if (!isset($max_fecha_c5[$id]) || $fechaRotura > $max_fecha_c5[$id]) {
                    $max_fecha_c5[$id] = $fechaRotura;
                }
            }
        }

        foreach ($incidencias as &$incidencia) {
            $sev_idx = $orden_sev[$incidencia['severidad']] ?? 9;
            if ($incidencia['tipo'] === $c5_tipo) {
                // Agrupar por artículo ordenando grupos por fecha de última rotura (asc: más antigua primero).
                // Dentro de cada artículo: orden cronológico de fecha_inicio_rotura (asc).
                $maxFechaRotura = $max_fecha_c5[$incidencia['idArticulo']] ?? '0000-00-00';
                $fechaInicioRotura  = $incidencia['fecha_inicio_rotura'] ?? '0000-00-00';
                $incidencia['orden_clave'] = $sev_idx . '1' . $maxFechaRotura . sprintf('%08d', $incidencia['idArticulo']) . $fechaInicioRotura;
            } elseif ($incidencia['tipo'] === 'Agotamiento Estimado' || $incidencia['tipo'] === 'Punto de Pedido') {
                // C6a/C6b CRITICA/ALTA: ordenar por dias_autonomia ascendente (más urgente primero).
                // C6a/C6b MEDIA: se ordena por tipo dentro del bloque MEDIA (ya cubierto por orden_tipo_media).
                // C6b (Punto de Pedido) se desplaza un sub-nivel respecto a C6a dentro de la misma severidad.
                $dias_pad = str_pad((int)($incidencia['dias_autonomia'] * 10), 8, '0', STR_PAD_LEFT);
                $c6b_shift = ($incidencia['tipo'] === 'Punto de Pedido') ? '1' : '0';
                $sub       = ($incidencia['severidad'] === 'MEDIA') ? '2' : '0';
                $incidencia['orden_clave'] = $sev_idx . $sub . $c6b_shift . $dias_pad . sprintf('%08d', $incidencia['idArticulo']);
            } elseif ($incidencia['severidad'] === 'MEDIA') {
                $sub = $orden_tipo_media_clave[$incidencia['tipo']] ?? '9';
                $incidencia['orden_clave'] = $sev_idx . $sub . sprintf('%08d', $incidencia['idArticulo']);
            } elseif ($incidencia['severidad'] === 'BAJA') {
                if ($incidencia['tipo'] === 'Entrada sin rotación previa') {
                    $sub = isset($incidencia['ultima_salida']) ? '0' : '1';
                } else {
                    $sub = '2'; // Stock Inactivo en Periodo (C4)
                }
                $incidencia['orden_clave'] = $sev_idx . $sub . sprintf('%08d', $incidencia['idArticulo']);
            } elseif ($incidencia['tipo'] === 'Inventario en negativo') {
                // C1a: stock_actual desc (más negativo primero), desempate por días en negativo desc
                $inv_stock = $this->construirClaveDescendenteDecimal($incidencia['stock_actual'] ?? 0, 100, 9999999999, 10);
                $inv_dias  = $this->construirClaveDescendenteEntera($incidencia['dias_en_negativo'] ?? 0, 9999, 4);
                $incidencia['orden_clave'] = $sev_idx . '0' . $inv_stock . $inv_dias;
            } elseif ($incidencia['tipo'] === 'Desajuste Puntual de Stock') {
                // C1b: abs(min_balance) desc — el mínimo más profundo primero
                $inv_min = $this->construirClaveDescendenteDecimal($incidencia['min_balance'] ?? 0, 100, 9999999999, 10);
                $incidencia['orden_clave'] = $sev_idx . '0' . $inv_min;
            } elseif (in_array($incidencia['c7_subcaso'] ?? '', ['C7a', 'C7a_posible'], true)) {
                // C7a: coste_estimado_merma desc → delta_acumulado desc → tendencia desc
                $coste_inv  = $this->construirClaveDescendenteDecimal($incidencia['coste_estimado_merma'] ?? 0, 100, 9999999, 7);
                $delta_inv  = $this->construirClaveDescendenteDecimal($incidencia['delta_acumulado'] ?? 0, 10, 99999, 5);
                $slope_inv  = $this->construirClaveDescendenteDecimal($incidencia['tendencia'] ?? 0, 10, 9999, 4);
                $coste_null = ($incidencia['coste_estimado_merma'] ?? null) === null ? '1' : '0';
                $incidencia['orden_clave'] = $sev_idx . '0' . $coste_null . $coste_inv . $delta_inv . $slope_inv;
            } elseif (in_array($incidencia['c7_subcaso'] ?? '', ['C7b', 'C7b_posible', 'C7b_ruido_peso'], true)) {
                // C7b: coste_estimado desc → n_recepciones desc → déficit abs desc
                $coste_inv = $this->construirClaveDescendenteDecimal($incidencia['coste_estimado'] ?? 0, 100, 9999999, 7);
                $rec_inv   = $this->construirClaveDescendenteEntera($incidencia['n_recepciones'] ?? 0, 9999, 4);
                $def_inv   = $this->construirClaveDescendenteDecimal($incidencia['offset_estimado'] ?? 0, 10, 99999, 5);
                // Nulls de coste al final
                $coste_null = ($incidencia['coste_estimado'] ?? null) === null ? '1' : '0';
                $incidencia['orden_clave'] = $sev_idx . '0' . $coste_null . $coste_inv . $rec_inv . $def_inv;
            } elseif ($incidencia['tipo'] === 'Merma backstaging') {
                // C9: pct_merma desc → merma_total_kg desc
                $pct_inv   = $this->construirClaveDescendenteDecimal($incidencia['pct_merma'] ?? 0, 100, 99999, 5);
                $merma_inv = $this->construirClaveDescendenteDecimal($incidencia['merma_total_kg'] ?? 0, 100, 9999999, 7);
                $incidencia['orden_clave'] = $sev_idx . '0' . $pct_inv . $merma_inv;
            } else {
                $incidencia['orden_clave'] = $sev_idx . '0' . sprintf('%08d', $incidencia['idArticulo']);
            }
        }
        unset($incidencia);

        return $this->repo->anadirNombres($incidencias);
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

        $filas = $this->repo->queryIdsConActividad($fi, $ff, $where_fam, $limit_clause, $where_prov);
        if (isset($filas['error'])) return [];

        $ids = [];
        foreach ($filas as $fila) $ids[] = (int)$fila['idArticulo'];
        return $ids;
    }

    public function getIncidenciasBatch(array $params, int $inicial, int $pagina): array
    {
        $paramsDto = PosstockParamsDTO::fromArray($params);

        $fi_mov           = $paramsDto->fecha_inicio_movimientos;
        $ff_mov           = $paramsDto->fecha_fin_movimientos;
        $familias_incluir = $paramsDto->familias_incluir;
        $familias_excluir = $paramsDto->familias_excluir;
        $casos_incluir    = $paramsDto->casos_incluir;

        $contextoProveedor = $this->resolverContextoProveedorBatch($params);
        if (isset($contextoProveedor['error'])) return $contextoProveedor;

        $proveedor_todos = $contextoProveedor['proveedor_todos'];
        $idsProveedoresCsv = $contextoProveedor['idsProveedoresCsv'];
        $ids_proveedor_filter = $contextoProveedor['ids_proveedor_filter'];
        $ids_proveedor_filter_c6b = $contextoProveedor['ids_proveedor_filter_c6b'];

        if (!empty($contextoProveedor['sin_articulos'])) {
            return ['filas' => [], 'actual' => $inicial, 'elementos' => 0];
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

        $casos_c7_activos = array_intersect($casos_incluir, ['caso7a', 'caso7b']);
        $pagina = $this->ajustarPaginaSegunCasos($pagina, $casos_incluir);

        // En batches mixtos, excluir C4 y C6b de la paginación;
        // C6b se añade al primer lote (inicial === 0) para que aparezca una sola vez.
        $params_batch = $params;
        $params_batch['casos_incluir'] = $casos_sin_paginables;
        // C7c/d/e necesita todos los artículos C7a+C7b: se salta en lotes parciales
        // y corre en una llamada final independiente (resolverPOSStockC7cde).
        if (!empty($casos_c7_activos)) {
            $params_batch['skip_c7_cde'] = true;
        }

        // Obtener IDs del lote según modo de proveedor
        if ($proveedor_todos && $idsProveedoresCsv !== '') {
            // Modo "todos los productos del proveedor": paginar directamente sobre
            // articulosProveedores, sin filtro de actividad en el periodo.
            $ids_batch = $this->repo->queryArticulosProveedorPaginados($idsProveedoresCsv, $inicial, $pagina);
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

    private function resolverContextoProveedorBatch(array $params): array
    {
        $proveedores_incluir = (array)($params['proveedores_incluir'] ?? []);
        $proveedor_todos = (bool)($params['proveedor_todos_productos'] ?? false);

        $ids_proveedor_filter = [];
        $ids_proveedor_filter_c6b = []; // C6b: todos los artículos del proveedor, sin filtro de estado
        $idsProveedoresCsv = '';
        $sin_articulos = false;

        if (!empty($proveedores_incluir)) {
            $idsProveedoresCsv = implode(',', array_map('intval', $proveedores_incluir));
            if (!$proveedor_todos) {
                // Modo normal: todos los artículos vinculados al proveedor
                $filasArticulosProv = $this->repo->queryIdsArticulosByProveedoresTodos($idsProveedoresCsv);
                if (isset($filasArticulosProv['error'])) return $filasArticulosProv;

                $ids_proveedor_filter = array_column($filasArticulosProv, 'idArticulo');
                if (empty($ids_proveedor_filter)) {
                    $sin_articulos = true;
                }
            }

            // C6b evalúa todos los artículos del proveedor independientemente del estado:
            // un artículo inactivo en articulosProveedores puede seguir en stock y vendiendo.
            $filasArticulosProvC6b = $this->repo->queryIdsArticulosByProveedoresTodos($idsProveedoresCsv);
            if (!isset($filasArticulosProvC6b['error'])) {
                $ids_proveedor_filter_c6b = array_column($filasArticulosProvC6b, 'idArticulo');
            }
        }

        return [
            'proveedor_todos' => $proveedor_todos,
            'idsProveedoresCsv' => $idsProveedoresCsv,
            'ids_proveedor_filter' => $ids_proveedor_filter,
            'ids_proveedor_filter_c6b' => $ids_proveedor_filter_c6b,
            'sin_articulos' => $sin_articulos,
        ];
    }

    private function resolverIdsFiltroConProveedores(array $params, array $idsFilterInicial): array
    {
        // ids_proveedor_filter: pre-resueltos por getIncidenciasBatch (evita doble query).
        // proveedores_incluir: IDs de proveedor raw (cuando se llama directamente).
        $idsProveedorFilter = (array)($params['ids_proveedor_filter'] ?? []);
        if (empty($idsProveedorFilter)) {
            $proveedoresIncluir = (array)($params['proveedores_incluir'] ?? []);
            if (!empty($proveedoresIncluir)) {
                $idsProveedoresCsv = implode(',', array_map('intval', $proveedoresIncluir));
                $filasArticulosProv = $this->repo->queryIdsArticulosByProveedoresTodos($idsProveedoresCsv);
                if (isset($filasArticulosProv['error'])) return $filasArticulosProv;
                $idsProveedorFilter = array_column($filasArticulosProv, 'idArticulo');
                if (empty($idsProveedorFilter)) {
                    return ['ids_filter' => [], 'ids_proveedor_filter' => [], 'sin_resultados' => true];
                }
            }
        }

        $idsFilterFinal = $idsFilterInicial;
        if (!empty($idsProveedorFilter)) {
            if (!empty($idsFilterFinal)) {
                $idsFilterFinal = array_values(array_intersect($idsFilterFinal, $idsProveedorFilter));
                if (empty($idsFilterFinal)) {
                    return ['ids_filter' => [], 'ids_proveedor_filter' => $idsProveedorFilter, 'sin_resultados' => true];
                }
            } else {
                $idsFilterFinal = $idsProveedorFilter;
            }
        }

        return [
            'ids_filter' => $idsFilterFinal,
            'ids_proveedor_filter' => $idsProveedorFilter,
            'sin_resultados' => false,
        ];
    }

    private function ajustarPaginaSegunCasos(int $pagina, array $casos_incluir): int
    {
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

        return $pagina;
    }

    private function construirClaveDescendenteDecimal(mixed $valor, int $escala, int $maximo, int $ancho): string
    {
        $valorAbsoluto = abs((float)$valor);
        $valorEscalado = (int)($valorAbsoluto * $escala);
        $valorInvertido = $maximo - $valorEscalado;
        if ($valorInvertido < 0) {
            $valorInvertido = 0;
        }

        // Se mantiene esta codificación por rendimiento: el frontend ordena por orden_clave
        // sin recomputar reglas de prioridad ni abrir nuevas ramas condicionales por columna.
        return str_pad((string)$valorInvertido, $ancho, '0', STR_PAD_LEFT);
    }

    private function construirClaveDescendenteEntera(mixed $valor, int $maximo, int $ancho): string
    {
        $valorEntero = (int)$valor;
        $valorInvertido = $maximo - $valorEntero;
        if ($valorInvertido < 0) {
            $valorInvertido = 0;
        }

        return str_pad((string)$valorInvertido, $ancho, '0', STR_PAD_LEFT);
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

    private function resolverSignoMovimiento(string $tipoMovimiento): float
    {
        return $tipoMovimiento === 'entrada_proveedor' ? 1.0 : -1.0;
    }

    private function obtenerUltimaFechaEntradas(array $entradas): string
    {
        $ultimaFecha = '';
        foreach ($entradas as $entrada) {
            $fechaEntrada = (string)($entrada['fecha'] ?? '');
            if ($fechaEntrada > $ultimaFecha) {
                $ultimaFecha = $fechaEntrada;
            }
        }

        return $ultimaFecha;
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
        return $this->c7->resolverC7cde($params, $ids_c7a, $ids_c7b);
    }
}
