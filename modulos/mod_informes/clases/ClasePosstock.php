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
 * ─── TIPOS DE ARTÍCULO FÍSICO ────────────────────────────────────────────────
 *
 *   tipo IN ('unidad', 'peso')  — confirmado en BD/Update/install_update_v0.0.40.sql
 *   Si se añaden nuevos tipos físicos, actualizar la constante TIPOS_FISICOS.
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
class ClasePosstock
{
    private $db;

    // Tipos de artículo considerados físicos (confirmar en BD si se añaden nuevos tipos)
    const TIPOS_FISICOS = "'unidad', 'peso'";

    public function __construct($conexion)
    {
        $this->db = $conexion;
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
     *   idArticulo, ncant, fecha (DATE), idDocumento
     *   — o array con clave 'error' si falla la consulta.
     */
    /**
     * Expande una lista de idFamilia a todos sus descendientes usando
     * vw_jerarquias_familias (idN1, idN2 capturan hijos de nivel 1 y 2).
     * Devuelve el IN-clause listo para SQL, o '' si la lista está vacía.
     */
    private function expandirFamilias(array $ids): string
    {
        if (empty($ids)) return '';
        $in = implode(',', array_map('intval', $ids));
        $smt = $this->db->query("
            SELECT DISTINCT idFamilia
            FROM vw_jerarquias_familias
            WHERE idFamilia IN ($in)
               OR idN1      IN ($in)
               OR idN2      IN ($in)
        ");
        if (!$smt) return $in; // fallback: usar IDs originales
        $expanded = [];
        while ($r = $smt->fetch_assoc()) $expanded[] = (int)$r['idFamilia'];
        return implode(',', $expanded);
    }

    public function getMovimientosPeriodo($fecha_inicio, $fecha_fin, array $familias_incluir = [], array $familias_excluir = [])
    {
        $fi = $this->db->real_escape_string($fecha_inicio);
        $ff = $this->db->real_escape_string($fecha_fin);

        // Construir cláusula de filtro por familia con expansión jerárquica
        $where_familia = '';
        if (!empty($familias_incluir)) {
            $ids = $this->expandirFamilias($familias_incluir);
            if ($ids) $where_familia .= " AND l.idArticulo IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids))";
        }
        if (!empty($familias_excluir)) {
            $ids = $this->expandirFamilias($familias_excluir);
            if ($ids) $where_familia .= " AND l.idArticulo NOT IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids))";
        }

        $sql = "
            -- Entradas: albaranes de proveedor (estado Guardado)
            SELECT
                'entrada_proveedor'    AS tipo_movimiento,
                l.idArticulo,
                l.ncant,
                DATE(c.Fecha)          AS fecha,
                c.id                   AS idDocumento
            FROM albprolinea l
            INNER JOIN albprot      c ON c.id         = l.idalbpro
            INNER JOIN articulos    a ON a.idArticulo  = l.idArticulo
            WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
              AND c.estado       IN ('Guardado', 'Facturado')
              AND l.estadoLinea  = 'Activo'
              AND a.tipo         IN (" . self::TIPOS_FISICOS . ")
              $where_familia

            UNION ALL

            -- Salidas: tickets de venta (estado Cerrado)
            SELECT
                'salida_ticket'        AS tipo_movimiento,
                l.idArticulo,
                l.ncant,
                DATE(c.Fecha)          AS fecha,
                c.id                   AS idDocumento
            FROM ticketslinea l
            INNER JOIN ticketst     c ON c.id         = l.idticketst
            INNER JOIN articulos    a ON a.idArticulo  = l.idArticulo
            WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
              AND c.estado       = 'Cerrado'
              AND l.estadoLinea  = 'Activo'
              AND a.tipo         IN (" . self::TIPOS_FISICOS . ")
              $where_familia

            UNION ALL

            -- Salidas: albaranes de cliente (estados Guardado, Procesado)
            SELECT
                'salida_albcli'        AS tipo_movimiento,
                l.idArticulo,
                l.ncant,
                DATE(c.Fecha)          AS fecha,
                c.id                   AS idDocumento
            FROM albclilinea l
            INNER JOIN albclit      c ON c.id         = l.idalbcli
            INNER JOIN articulos    a ON a.idArticulo  = l.idArticulo
            WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
              AND c.estado       IN ('Guardado', 'Procesado')
              AND l.estadoLinea  = 'Activo'
              AND a.tipo         IN (" . self::TIPOS_FISICOS . ")
              $where_familia

            ORDER BY idArticulo, fecha
        ";

        $smt = $this->db->query($sql);
        if (!$smt) {
            return ['error' => $this->db->error, 'consulta' => $sql];
        }

        $resultado = [];
        while ($row = $smt->fetch_assoc()) {
            $resultado[] = $row;
        }
        return $resultado;
    }

    /**
     * T4.2 — Stock base acumulado desde inicio del ejercicio hasta fecha_fin_stock.
     *
     * Solo se calcula para los artículos que tuvieron movimiento en la ventana (T4.1),
     * reduciendo el coste de la consulta.
     *
     * Signos:
     *   entradas proveedor  → ncant positivo  (suma al stock)
     *   salidas ticket      → ncant negativo  (resta al stock)
     *   salidas albcli      → ncant negativo  (resta al stock)
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

        $sql = "
            SELECT
                idArticulo,
                SUM(ncant_signo)                        AS saldo_acumulado,
                MAX(CASE WHEN tipo_mov = 'entrada'
                         THEN fecha END)                AS ultima_compra,
                MAX(CASE WHEN tipo_mov = 'salida'
                         THEN fecha END)                AS ultima_venta
            FROM (

                -- Entradas proveedor (positivo)
                SELECT
                    l.idArticulo,
                     l.ncant                            AS ncant_signo,
                    'entrada'                           AS tipo_mov,
                    DATE(c.Fecha)                       AS fecha
                FROM albprolinea l
                INNER JOIN albprot c ON c.id = l.idalbpro
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado      IN ('Guardado', 'Facturado', 'Exportado', 'Importado')
                  AND l.estadoLinea = 'Activo'
                  AND l.idArticulo  IN ($ids)

                UNION ALL

                -- Salidas tickets (negativo)
                SELECT
                    l.idArticulo,
                    -l.ncant                            AS ncant_signo,
                    'salida'                            AS tipo_mov,
                    DATE(c.Fecha)                       AS fecha
                FROM ticketslinea l
                INNER JOIN ticketst c ON c.id = l.idticketst
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado      = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                  AND l.idArticulo  IN ($ids)

                UNION ALL

                -- Salidas albaranes cliente (negativo)
                SELECT
                    l.idArticulo,
                    -l.ncant                            AS ncant_signo,
                    'salida'                            AS tipo_mov,
                    DATE(c.Fecha)                       AS fecha
                FROM albclilinea l
                INNER JOIN albclit c ON c.id = l.idalbcli
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado      IN ('Guardado', 'Procesado')
                  AND l.estadoLinea = 'Activo'
                  AND l.idArticulo  IN ($ids)

            ) AS movimientos_stock
            GROUP BY idArticulo
        ";

        $smt = $this->db->query($sql);
        if (!$smt) {
            return ['error' => $this->db->error, 'consulta' => $sql];
        }

        $resultado = [];
        while ($row = $smt->fetch_assoc()) {
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
     *   idArticulo, idDocumento, fecha, ncant,
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
                    + $signo * (float)$m['ncant'];
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
                    'ncant'                    => (float)$entrada['ncant'],
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
     *   Claves opcionales (umbrales con defaults):
     *   umbral_sobrestock            (float, default 0.5)
     *   umbral_caducidad_semanas     (int,   default 24)
     *   umbral_sin_rotacion_semanas  (int,   default 12)
     *
     * @return array  Filas ordenadas CRITICA→MEDIA→BAJA, con clave 'error' si falla.
     */
    public function getIncidencias(array $params): array
    {
        $fi_mov   = $params['fecha_inicio_movimientos'];
        $ff_mov   = $params['fecha_fin_movimientos'];
        $fi_stock = $params['fecha_inicio_stock'];
        $ff_stock = $params['fecha_fin_stock'];

        $umbral_sobrestock      = (float)($params['umbral_sobrestock']           ?? 0.5);
        $umbral_caducidad       = (int)  ($params['umbral_caducidad_semanas']    ?? 24);
        $umbral_sin_rotacion    = (int)  ($params['umbral_sin_rotacion_semanas'] ?? 12);
        $incluir_stock_inactivo = (bool) ($params['incluir_stock_inactivo']      ?? false);
        $familias_incluir       = (array)($params['familias_incluir'] ?? []);
        $familias_excluir       = (array)($params['familias_excluir'] ?? []);

        // ── T4.1: movimientos en la ventana ──────────────────────────────────
        $movimientos = $this->getMovimientosPeriodo($fi_mov, $ff_mov, $familias_incluir, $familias_excluir);
        if (isset($movimientos['error'])) {
            return $movimientos;
        }

        $ids = array_unique(array_column($movimientos, 'idArticulo'));
        if (empty($ids)) {
            return [];
        }

        // ── T4.2: stock base por artículo ─────────────────────────────────────
        $stock_base = $this->getStockBase($ids, $fi_stock, $ff_stock);
        if (isset($stock_base['error'])) {
            return $stock_base;
        }

        // ── T4.3: stock_previo y stock_tras_ultimo_albaran ────────────────────
        $entradas_calc = $this->calcularStockPrevio($movimientos, $stock_base);

        // ── Balance mínimo intra-periodo por artículo (Caso 1) ────────────────
        $min_balances = $this->calcularMinimosBalance($movimientos, $stock_base);

        // ── Fechas de venta por artículo en la ventana (Caso 5) ──────────────
        $ventas_fechas = [];
        foreach ($movimientos as $m) {
            if ($m['tipo_movimiento'] === 'entrada_proveedor') continue;
            $ventas_fechas[(int)$m['idArticulo']][] = $m['fecha'];
        }

        // ── Estadísticas por artículo desde T4.1 ─────────────────────────────
        $stats = [];
        foreach ($movimientos as $m) {
            $id = (int)$m['idArticulo'];
            if (!isset($stats[$id])) {
                $stats[$id] = [
                    'saldo_ventana'         => 0.0,
                    'ultima_salida_ventana' => null,
                    'tiene_entrada'         => false,
                ];
            }
            $signo = ($m['tipo_movimiento'] === 'entrada_proveedor') ? 1.0 : -1.0;
            $stats[$id]['saldo_ventana'] += $signo * (float)$m['ncant'];

            if (in_array($m['tipo_movimiento'], ['salida_ticket', 'salida_albcli'])) {
                if (
                    $stats[$id]['ultima_salida_ventana'] === null
                    || $m['fecha'] > $stats[$id]['ultima_salida_ventana']
                ) {
                    $stats[$id]['ultima_salida_ventana'] = $m['fecha'];
                }
            }
            if ($m['tipo_movimiento'] === 'entrada_proveedor' && (float)$m['ncant'] > 0) {
                $stats[$id]['tiene_entrada'] = true;
            }
        }

        // stock_actual por artículo = saldo_base + saldo_ventana
        foreach ($ids as $id) {
            $id = (int)$id;
            $saldo_base_art             = isset($stock_base[$id]) ? $stock_base[$id]['saldo_acumulado'] : 0.0;
            $stats[$id]['stock_actual'] = $saldo_base_art + ($stats[$id]['saldo_ventana'] ?? 0.0);
            $stats[$id]['saldo_base']   = $saldo_base_art;
        }

        $fecha_fin_dt = new DateTime($ff_mov);
        $incidencias  = [];
        $orden_sev    = ['CRITICA' => 0, 'ALTA' => 1, 'MEDIA' => 2, 'BAJA' => 3];

        // ── Caso 1a/1b: Stock negativo y desajuste puntual (por artículo) ───────
        // 1a CRITICA — stock_actual < 0: el inventario cierra en negativo.
        // 1b MEDIA   — min_balance < 0 pero stock_actual >= 0: hubo un momento
        //              intra-periodo con balance negativo que luego se recuperó.
        //              Indica problema de orden de registro, no de stock real.
        foreach ($ids as $id) {
            $id           = (int)$id;
            $stock_actual = $stats[$id]['stock_actual'];
            $min_balance  = $min_balances[$id] ?? $stock_actual;

            if ($stock_actual < 0) {
                $incidencias[] = [
                    'idArticulo'    => $id,
                    'tipo'          => 'Stock Negativo',
                    'severidad'     => 'CRITICA',
                    'stock_actual'  => $stock_actual,
                    'min_balance'   => $min_balance,
                    'posible_causa' => 'Inventario negativo al cierre',
                ];
            } elseif ($min_balance < 0) {
                $incidencias[] = [
                    'idArticulo'    => $id,
                    'tipo'          => 'Desajuste Puntual de Stock',
                    'severidad'     => 'ALTA',
                    'stock_actual'  => $stock_actual,
                    'min_balance'   => $min_balance,
                    'posible_causa' => 'Negativo puntual, recuperado al cierre',
                ];
            }
        }

        // ── Caso 2: Entrada con stock alto (por entrada_proveedor, ncant > 0) ─
        foreach ($entradas_calc as $e) {
            if ($e['ncant'] <= 0) {
                continue; // devoluciones excluidas
            }
            if ($e['stock_previo'] >= $e['ncant'] * $umbral_sobrestock) {
                $incidencias[] = [
                    'idArticulo'               => (int)$e['idArticulo'],
                    'tipo'                     => 'Entrada con stock alto',
                    'severidad'                => 'MEDIA',
                    'ncant'                    => $e['ncant'],
                    'stock_previo'             => $e['stock_previo'],
                    'stock_tras_ultimo_albaran' => $e['stock_tras_ultimo_albaran'],
                    'idDocumento'              => $e['idDocumento'],
                    'fecha'                    => $e['fecha'],
                    'posible_causa'            => 'Duplicado de albarán o sobrecompra',
                ];
            }
        }

        // ── Casos 3a y 3b (solo artículos con entrada real en la ventana) ──────
        foreach ($ids as $id) {
            $id = (int)$id;
            if (empty($stats[$id]['tiene_entrada'])) {
                continue;
            }

            $ultima_salida_global = $this->maxFecha(
                $stock_base[$id]['ultima_venta']    ?? null,
                $stats[$id]['ultima_salida_ventana'] ?? null
            );

            // ── Caso 3a: Riesgo de caducidad teórica ─────────────────────────
            // [TODO T5.3] Si la familia tiene viabilidad_categoria, usar
            //             viabilidad_categoria * 0.75 como umbral en lugar de
            //             umbral_caducidad_semanas. Pendiente de identificar
            //             tabla/campo exacto en BD.
            if ($ultima_salida_global !== null) {
                $semanas_sin_venta = (new DateTime($ultima_salida_global))
                    ->diff($fecha_fin_dt)->days / 7.0;

                if ($semanas_sin_venta >= $umbral_caducidad) {
                    $incidencias[] = [
                        'idArticulo'                    => $id,
                        'tipo'                          => 'Riesgo de caducidad teórica',
                        'severidad'                     => 'MEDIA',
                        'ultima_venta'                  => $ultima_salida_global,
                        'semanas_desde_ultima_venta'    => round($semanas_sin_venta, 1),
                        'posible_causa'                 => "Sin ventas >{$umbral_caducidad} sem.",
                    ];
                }
            }

            // ── Caso 3b: Entrada sin rotación previa ──────────────────────────
            if ($ultima_salida_global !== null) {
                $semanas_sin_rot = (new DateTime($ultima_salida_global))
                    ->diff($fecha_fin_dt)->days / 7.0;

                if ($semanas_sin_rot >= $umbral_sin_rotacion) {
                    $incidencias[] = [
                        'idArticulo'                     => $id,
                        'tipo'                           => 'Entrada sin rotación previa',
                        'severidad'                      => 'BAJA',
                        'ultima_salida'                  => $ultima_salida_global,
                        'semanas_desde_ultima_salida'    => round($semanas_sin_rot, 1),
                        'posible_causa'                  => 'Sin rotación / error de unidad',
                    ];
                }
            } else {
                // Nunca ha tenido salidas en toda la historia conocida → Caso 3b absoluto
                $incidencias[] = [
                    'idArticulo'                  => $id,
                    'tipo'                        => 'Entrada sin rotación previa',
                    'severidad'                   => 'BAJA',
                    'ultima_salida'               => null,
                    'semanas_desde_ultima_salida' => null,
                    'posible_causa'               => 'Nunca ha tenido salidas',
                ];
            }
        }

        // ── Caso 5: Venta Cero con Stock Positivo ─────────────────────────────
        // Solo para artículos en T4.1 con stock_actual > 0 y al menos 2 días de
        // venta distintos en la ventana (necesarios para calcular el ciclo medio).
        // factor_ajuste = 1.5 (fijo por ahora).
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($stats[$id]['stock_actual'] <= 0) continue;

            $fechas = array_unique($ventas_fechas[$id] ?? []);
            sort($fechas);
            $n = count($fechas);
            if ($n < 3) continue; // mínimo 2 gaps para calcular media + 3σ

            // Intervalos entre días de venta consecutivos
            $gaps = [];
            for ($i = 1; $i < $n; $i++) {
                $gap = (new DateTime($fechas[$i - 1]))->diff(new DateTime($fechas[$i]))->days;
                if ($gap > 0) $gaps[] = $gap;
            }
            if (count($gaps) < 2) continue;

            // Media y desviación típica muestral (n-1) de los gaps
            $n_gaps  = count($gaps);
            $avg_gap = array_sum($gaps) / $n_gaps;
            $var     = array_sum(array_map(fn($g) => ($g - $avg_gap) ** 2, $gaps)) / ($n_gaps - 1);
            $sd      = sqrt($var);

            // Umbral: media + 3σ (cubre el 99.7 % de la distribución normal)
            $umbral_rotura  = $avg_gap + 3 * $sd;
            $ultima_venta   = end($fechas);
            $dias_sin_venta = (new DateTime($ultima_venta))->diff($fecha_fin_dt)->days;

            if ($dias_sin_venta > $umbral_rotura) {
                // fecha_inicio_rotura: día a partir del cual el silencio supera media+3σ
                $fecha_inicio_rotura = (new DateTime($ultima_venta))
                    ->modify('+' . (int)ceil($umbral_rotura) . ' days')
                    ->format('Y-m-d');

                $incidencias[] = [
                    'idArticulo'            => $id,
                    'tipo'                  => 'Venta Cero (Posible Rotura Física)',
                    'severidad'             => 'MEDIA',
                    'stock_actual'          => $stats[$id]['stock_actual'],
                    'ultima_venta'          => $ultima_venta,
                    'fecha_inicio_rotura'   => $fecha_inicio_rotura,
                    'dias_sin_venta'        => $dias_sin_venta,
                    'avg_dias_entre_ventas' => round($avg_gap, 1),
                    'sd_dias'               => round($sd, 1),
                    'umbral_dias'           => round($umbral_rotura, 1),
                    'posible_causa'         => 'Hueco en lineal o merma no registrada',
                ];
            }
        }

        // ── Caso 4: Stock Inactivo en Periodo (opcional) ──────────────────────
        // Artículos físicos con stock positivo al cierre del periodo pero sin
        // ningún movimiento en [fi_mov, ff_mov]. Solo se ejecuta si está habilitado.
        if ($incluir_stock_inactivo) {
            $articulos_sin_mov = $this->getArticulosSinMovimiento(
                $fi_mov,
                $ff_mov,
                $familias_incluir,
                $familias_excluir
            );
            if (isset($articulos_sin_mov['error'])) {
                return $articulos_sin_mov;
            }
            foreach ($articulos_sin_mov as $id => $art) {
                $incidencias[] = [
                    'idArticulo'    => $id,
                    'tipo'          => 'Stock Inactivo en Periodo',
                    'severidad'     => 'BAJA',
                    'stock_actual'  => $art['saldo_acumulado'],
                    'posible_causa' => 'Stock sin actividad en el periodo',
                ];
            }
        }

        // ── Ordenar: CRITICA → MEDIA → BAJA ──────────────────────────────────
        usort(
            $incidencias,
            fn($a, $b) =>
            $orden_sev[$a['severidad']] <=> $orden_sev[$b['severidad']]
        );

        // ── Añadir nombre de artículo a cada fila ─────────────────────────────
        if (!empty($incidencias)) {
            $ids_inc = implode(',', array_unique(array_column($incidencias, 'idArticulo')));
            $smt = $this->db->query(
                "SELECT idArticulo, articulo_name FROM articulos WHERE idArticulo IN ($ids_inc)"
            );
            $nombres = [];
            if ($smt) {
                while ($r = $smt->fetch_assoc()) {
                    $nombres[(int)$r['idArticulo']] = $r['articulo_name'];
                }
            }
            foreach ($incidencias as &$inc) {
                $inc['nombre'] = $nombres[$inc['idArticulo']] ?? '';
            }
            unset($inc);
        }

        return $incidencias;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Calcula el balance mínimo intra-periodo por artículo.
     *
     * Itera los deltas diarios en orden cronológico y registra el mínimo
     * del saldo acumulado TRAS aplicar cada día. Permite detectar momentos
     * en que el stock cayó a negativo aunque el saldo final sea positivo.
     *
     * @param array $movimientos  Resultado de getMovimientosPeriodo()
     * @param array $stock_base   Resultado de getStockBase(), indexado por idArticulo
     *
     * @return array  Indexado por idArticulo → min_balance (float)
     */
    private function calcularMinimosBalance(array $movimientos, array $stock_base): array
    {
        $por_articulo = [];
        foreach ($movimientos as $m) {
            $por_articulo[(int)$m['idArticulo']][] = $m;
        }

        $min_balances = [];
        foreach ($por_articulo as $idArticulo => $movs) {
            $saldo_base = isset($stock_base[$idArticulo])
                ? $stock_base[$idArticulo]['saldo_acumulado']
                : 0.0;

            $delta_por_fecha = [];
            foreach ($movs as $m) {
                $signo = ($m['tipo_movimiento'] === 'entrada_proveedor') ? 1.0 : -1.0;
                $delta_por_fecha[$m['fecha']] = ($delta_por_fecha[$m['fecha']] ?? 0.0)
                    + $signo * (float)$m['ncant'];
            }
            ksort($delta_por_fecha);

            $min  = $saldo_base;
            $acum = $saldo_base;
            foreach ($delta_por_fecha as $delta) {
                $acum += $delta;
                if ($acum < $min) $min = $acum;
            }
            $min_balances[$idArticulo] = $min;
        }

        return $min_balances;
    }

    /**
     * T5.5 — Artículos físicos con stock positivo pero sin movimiento en todo el año (Caso 4).
     *
     * Proceso en tres pasos para garantizar que "sin movimiento" significa cero filas
     * en cualquiera de los tres tipos de movimiento durante [fi_año, ff_mov]:
     *
     *   1. Obtener todos los idArticulo físicos que coincidan con el filtro de familias.
     *   2. Obtener todos los idArticulo con CUALQUIER movimiento (entrada, ticket o albcli)
     *      en el rango [fi_año, ff_mov]. Diff en PHP → sin_movimiento_año.
     *   3. Calcular el stock en el momento del análisis (ff_mov) rebobinando desde
     *      articulosStocks.stockOn: stock_en_ff = stockOn − net_movimientos_posteriores.
     *      Esto da el stock real en el periodo analizado independientemente de lo que
     *      haya pasado después. Solo los con stock_en_ff > 0 se reportan.
     *
     * @param string $fi_año           Primer día del ejercicio ('YYYY-01-01')
     * @param string $ff_mov           Último día del periodo analizado ('YYYY-MM-DD')
     * @param array  $familias_incluir
     * @param array  $familias_excluir
     *
     * @return array  Indexado por idArticulo con saldo_acumulado, ultima_compra, ultima_venta
     *                — o array con clave 'error' si falla alguna consulta.
     */
    public function getArticulosSinMovimiento(
        string $fi_año,
        string $ff_mov,
        array $familias_incluir = [],
        array $familias_excluir = []
    ): array {
        $fi    = $this->db->real_escape_string($fi_año);
        $ff    = $this->db->real_escape_string($ff_mov);
        $tipos = self::TIPOS_FISICOS;

        // Filtro de familias sobre la tabla articulos
        $where_familia = '';
        if (!empty($familias_incluir)) {
            $ids_fam = $this->expandirFamilias($familias_incluir);
            if ($ids_fam) $where_familia .= " AND a.idArticulo IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids_fam))";
        }
        if (!empty($familias_excluir)) {
            $ids_fam = $this->expandirFamilias($familias_excluir);
            if ($ids_fam) $where_familia .= " AND a.idArticulo NOT IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids_fam))";
        }

        // Paso 1: todos los artículos físicos (con filtro de familia)
        $smt = $this->db->query(
            "SELECT idArticulo FROM articulos a WHERE a.tipo IN ($tipos) $where_familia"
        );
        if (!$smt) return ['error' => $this->db->error];

        $todos_ids = [];
        while ($r = $smt->fetch_assoc()) $todos_ids[] = (int)$r['idArticulo'];
        if (empty($todos_ids)) return [];

        // Paso 2: artículos con cualquier movimiento (los 3 tipos) en [fi_año, ff_mov]
        $smt = $this->db->query("
            SELECT DISTINCT idArticulo FROM (
                SELECT l.idArticulo FROM albprolinea l
                INNER JOIN albprot c ON c.id = l.idalbpro
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado IN ('Guardado', 'Facturado', 'Exportado', 'Importado')
                  AND l.estadoLinea = 'Activo'
                UNION
                SELECT l.idArticulo FROM ticketslinea l
                INNER JOIN ticketst c ON c.id = l.idticketst
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                UNION
                SELECT l.idArticulo FROM albclilinea l
                INNER JOIN albclit c ON c.id = l.idalbcli
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado IN ('Guardado', 'Procesado')
                  AND l.estadoLinea = 'Activo'
            ) AS movs_año
        ");
        if (!$smt) return ['error' => $this->db->error];

        $con_movimiento = [];
        while ($r = $smt->fetch_assoc()) $con_movimiento[(int)$r['idArticulo']] = true;

        // Diff en PHP: artículos físicos (con familia) sin ningún movimiento en el año
        $sin_movimiento = array_values(array_filter($todos_ids, fn($id) => !isset($con_movimiento[$id])));
        if (empty($sin_movimiento)) return [];

        // Paso 3: stock en el momento del análisis (fecha ff_mov)
        //
        // stockOn refleja el stock a DÍA DE HOY, no al momento analizado.
        // Para obtener el stock en ff_mov "rebobinamos": restamos los movimientos
        // que ocurrieron DESPUÉS de ff_mov (que ya están incluidos en stockOn).
        //
        //   stock_en_ff = stockOn_hoy − net_posterior
        //   net_posterior = Σ entradas_post_ff − Σ salidas_post_ff
        //
        // Se agrega stockOn por idTienda para cubrir instalaciones multi-tienda.
        $ids_str = implode(',', array_map('intval', $sin_movimiento));
        $ff_esc  = $this->db->real_escape_string($ff_mov);
        $tipos   = self::TIPOS_FISICOS;

        $smt = $this->db->query("
            SELECT
                base.idArticulo,
                base.total_stockOn - COALESCE(post.net_posterior, 0) AS stock_en_periodo
            FROM (
                SELECT idArticulo, SUM(stockOn) AS total_stockOn
                FROM articulosStocks
                WHERE idArticulo IN ($ids_str)
                GROUP BY idArticulo
            ) AS base
            LEFT JOIN (
                SELECT idArticulo, SUM(ncant_signo) AS net_posterior
                FROM (
                    SELECT l.idArticulo,  l.ncant AS ncant_signo
                    FROM albprolinea l INNER JOIN albprot c ON c.id = l.idalbpro
                    WHERE DATE(c.Fecha) > '$ff_esc'
                      AND c.estado IN ('Guardado','Facturado','Exportado','Importado')
                      AND l.estadoLinea = 'Activo'
                      AND l.idArticulo IN ($ids_str)
                    UNION ALL
                    SELECT l.idArticulo, -l.ncant AS ncant_signo
                    FROM ticketslinea l INNER JOIN ticketst c ON c.id = l.idticketst
                    WHERE DATE(c.Fecha) > '$ff_esc'
                      AND c.estado = 'Cerrado'
                      AND l.estadoLinea = 'Activo'
                      AND l.idArticulo IN ($ids_str)
                    UNION ALL
                    SELECT l.idArticulo, -l.ncant AS ncant_signo
                    FROM albclilinea l INNER JOIN albclit c ON c.id = l.idalbcli
                    WHERE DATE(c.Fecha) > '$ff_esc'
                      AND c.estado IN ('Guardado','Procesado')
                      AND l.estadoLinea = 'Activo'
                      AND l.idArticulo IN ($ids_str)
                ) AS post_movs
                GROUP BY idArticulo
            ) AS post ON post.idArticulo = base.idArticulo
            HAVING stock_en_periodo > 0
        ");
        if (!$smt) return ['error' => $this->db->error];

        $resultado = [];
        while ($row = $smt->fetch_assoc()) {
            $resultado[(int)$row['idArticulo']] = [
                'saldo_acumulado' => (float)$row['stock_en_periodo'],
                'ultima_compra'   => null,
                'ultima_venta'    => null,
            ];
        }
        return $resultado;
    }

    private function maxFecha(?string $a, ?string $b): ?string
    {
        if ($a === null) return $b;
        if ($b === null) return $a;
        return ($a >= $b) ? $a : $b;
    }
}
