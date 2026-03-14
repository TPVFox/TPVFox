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

    public function getMovimientosPeriodo($fecha_inicio, $fecha_fin, array $familias_incluir = [], array $familias_excluir = [], array $ids_filter = [])
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

        // Filtro por lote de artículos (batch)
        $where_ids = empty($ids_filter)
            ? ''
            : " AND l.idArticulo IN (" . implode(',', array_map('intval', $ids_filter)) . ")";

        // Agrupamos por (tipo, artículo, fecha) para reducir el número de filas en memoria.
        // Para periodos largos (semestral, anual) esto puede pasar de cientos de miles de
        // líneas de ticket a decenas de miles de grupos (artículo × tipo × día).
        // idDocumento se pierde por el GROUP BY; la columna se mantiene a NULL para
        // no romper la interfaz de calcularStockPrevio (el campo no se muestra en el UI).
        $sql = "
            SELECT tipo_movimiento, idArticulo, SUM(ncant) AS ncant, fecha, NULL AS idDocumento
            FROM (
                -- Entradas: albaranes de proveedor
                SELECT
                    'entrada_proveedor'    AS tipo_movimiento,
                    l.idArticulo,
                    l.ncant,
                    DATE(c.Fecha)          AS fecha
                FROM albprolinea l
                INNER JOIN albprot      c ON c.id         = l.idalbpro
                INNER JOIN articulos    a ON a.idArticulo  = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado       IN ('Guardado', 'Facturado')
                  AND l.estadoLinea  = 'Activo'
                  AND a.tipo         IN (" . self::TIPOS_FISICOS . ")
                  $where_familia
                  $where_ids

                UNION ALL

                -- Salidas: tickets de venta
                SELECT
                    'salida_ticket'        AS tipo_movimiento,
                    l.idArticulo,
                    l.ncant,
                    DATE(c.Fecha)          AS fecha
                FROM ticketslinea l
                INNER JOIN ticketst     c ON c.id         = l.idticketst
                INNER JOIN articulos    a ON a.idArticulo  = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado       = 'Cerrado'
                  AND l.estadoLinea  = 'Activo'
                  AND a.tipo         IN (" . self::TIPOS_FISICOS . ")
                  $where_familia
                  $where_ids

                UNION ALL

                -- Salidas: albaranes de cliente
                SELECT
                    'salida_albcli'        AS tipo_movimiento,
                    l.idArticulo,
                    l.ncant,
                    DATE(c.Fecha)          AS fecha
                FROM albclilinea l
                INNER JOIN albclit      c ON c.id         = l.idalbcli
                INNER JOIN articulos    a ON a.idArticulo  = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado       IN ('Guardado', 'Procesado')
                  AND l.estadoLinea  = 'Activo'
                  AND a.tipo         IN (" . self::TIPOS_FISICOS . ")
                  $where_familia
                  $where_ids
            ) AS all_movs
            GROUP BY tipo_movimiento, idArticulo, fecha
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

        $umbral_sobrestock      = (float) ($params['umbral_sobrestock']           ?? 0.5);
        $umbral_caducidad       = (int)   ($params['umbral_caducidad_semanas']    ?? 24);
        $umbral_sin_rotacion    = (int)   ($params['umbral_sin_rotacion_semanas'] ?? 12);
        $incluir_stock_inactivo = (bool)  ($params['incluir_stock_inactivo']      ?? false);
        $tipo_incidencia        = (string)($params['tipo_incidencia']             ?? '');
        $familias_incluir       = (array) ($params['familias_incluir'] ?? []);
        $familias_excluir       = (array) ($params['familias_excluir'] ?? []);
        $ids_filter             = (array) ($params['ids_filter']       ?? []);

        // caso4 solo necesita getArticulosSinMovimiento; forzar el flag si se solicita
        if ($tipo_incidencia === 'caso4') {
            $incluir_stock_inactivo = true;
        }

        // ── Caso 5 optimizado: query ligera, sin cargar todos los movimientos ──
        // getCaso5Data solo trae fechas de venta únicas + rebobinado de stock.
        // Se usa siempre que tipo_incidencia = 'caso5' (cualquier periodo).
        if ($tipo_incidencia === 'caso5') {
            return $this->getIncidenciasCaso5($fi_mov, $ff_mov, $umbral_sobrestock,
                $familias_incluir, $familias_excluir, $ids_filter);
        }

        // ── T4.1: movimientos en la ventana ──────────────────────────────────
        $movimientos = $this->getMovimientosPeriodo($fi_mov, $ff_mov, $familias_incluir, $familias_excluir, $ids_filter);
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
        // Usamos la fecha como clave para deduplicar en el momento de inserción,
        // evitando array_unique posterior sobre arrays potencialmente enormes.
        $ventas_fechas = [];
        foreach ($movimientos as $m) {
            if ($m['tipo_movimiento'] === 'entrada_proveedor') continue;
            $ventas_fechas[(int)$m['idArticulo']][$m['fecha']] = true;
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
        // stock_actual se pasa a _calcularRoturasC5 para que lo use solo en el
        // gap "en curso" (final → ff_mov). Las roturas recuperadas no requieren
        // stock > 0 al cierre y se procesan aunque stock_actual sea 0.
        $ff_ts_c5 = $fecha_fin_dt->getTimestamp();
        foreach ($ids as $id) {
            $id = (int)$id;
            foreach ($this->_calcularRoturasC5(
                $id,
                $ventas_fechas[$id] ?? [],
                $stats[$id]['stock_actual'],
                $ff_ts_c5
            ) as $inc) {
                $incidencias[] = $inc;
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

        // ── Filtrar por tipo de incidencia (vista anual) ──────────────────────
        if ($tipo_incidencia !== '') {
            $tipo_map = [
                'caso1'  => ['Stock Negativo', 'Desajuste Puntual de Stock'],
                'caso2'  => ['Entrada con stock alto'],
                'caso3a' => ['Riesgo de caducidad teórica'],
                'caso3b' => ['Entrada sin rotación previa'],
                'caso4'  => ['Stock Inactivo en Periodo'],
            ];
            if (isset($tipo_map[$tipo_incidencia])) {
                $tipos_ok = $tipo_map[$tipo_incidencia];
                $incidencias = array_values(array_filter(
                    $incidencias,
                    fn($inc) => in_array($inc['tipo'], $tipos_ok, true)
                ));
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
     * Detecta todos los gaps anómalos (> media+3σ) para un artículo.
     *
     * Recibe la fecha_map (date => true), calcula estadísticas de intervalos
     * y devuelve 0 o más incidencias — una por brecha anómala detectada.
     * 'fecha_fin_rotura' null = rotura en curso; string = rotura recuperada.
     *
     * IMPORTANTE — comprobación de stock:
     *   · Roturas RECUPERADAS (gap entre dos ventas consecutivas): no necesitan
     *     stock > 0 al cierre. Si hubo una segunda venta, había stock durante
     *     el hueco. Se reportan siempre → visibles en vistas anuales aunque el
     *     artículo se haya agotado posteriormente.
     *   · Rotura EN CURSO (desde última venta hasta ff_mov): solo se reporta
     *     si stock_actual > 0 al cierre del periodo — confirma que el artículo
     *     sigue en el sistema y la brecha no es por discontinuación.
     *
     * @param int   $id           idArticulo
     * @param array $fechas_map   [date => true]
     * @param float $stock_actual Stock al cierre del periodo (solo para gap en curso)
     * @param int   $ff_ts        Timestamp de fecha_fin_movimientos
     *
     * @return array  Filas de incidencia (sin campo 'nombre')
     */
    private function _calcularRoturasC5(int $id, array $fechas_map, float $stock_actual, int $ff_ts): array
    {
        $fechas = array_keys($fechas_map);
        sort($fechas);
        $n = count($fechas);
        if ($n < 3) return [];
        $ts = array_map('strtotime', $fechas);
        $gaps = [];
        for ($i = 1; $i < $n; $i++) {
            $gap = (int)(($ts[$i] - $ts[$i - 1]) / 86400);
            if ($gap > 0) $gaps[] = $gap;
        }
        if (count($gaps) < 2) return [];
        $n_gaps  = count($gaps);
        $avg_gap = array_sum($gaps) / $n_gaps;
        $var     = array_sum(array_map(fn($g) => ($g - $avg_gap) ** 2, $gaps)) / ($n_gaps - 1);
        $sd      = sqrt($var);
        $umbral  = $avg_gap + 3 * $sd;
        $umbral_ceil = (int)ceil($umbral);
        $campos = [
            'idArticulo'            => $id,
            'tipo'                  => 'Venta Cero (Posible Rotura Física)',
            'severidad'             => 'MEDIA',
            'stock_actual'          => $stock_actual,
            'avg_dias_entre_ventas' => round($avg_gap, 1),
            'sd_dias'               => round($sd, 1),
            'umbral_dias'           => round($umbral, 1),
            'posible_causa'         => 'Hueco en lineal o merma no registrada',
        ];
        $incidencias = [];
        // Roturas recuperadas: gaps entre ventas consecutivas.
        // No se comprueba stock — si hubo segunda venta hubo stock durante el hueco.
        for ($i = 1; $i < $n; $i++) {
            $gap = (int)(($ts[$i] - $ts[$i - 1]) / 86400);
            if ($gap > $umbral) {
                $incidencias[] = $campos + [
                    'ultima_venta'        => $fechas[$i - 1],
                    'fecha_inicio_rotura' => date('Y-m-d', $ts[$i - 1] + $umbral_ceil * 86400),
                    'fecha_fin_rotura'    => $fechas[$i],
                    'dias_rotura'         => $gap,
                ];
            }
        }
        // Rotura en curso: desde última venta hasta ff_mov.
        // Solo se reporta si el artículo aún tiene stock al cierre del periodo.
        $dias_final = (int)(($ff_ts - $ts[$n - 1]) / 86400);
        if ($dias_final > $umbral && $stock_actual > 0) {
            $incidencias[] = $campos + [
                'ultima_venta'        => $fechas[$n - 1],
                'fecha_inicio_rotura' => date('Y-m-d', $ts[$n - 1] + $umbral_ceil * 86400),
                'fecha_fin_rotura'    => null,
                'dias_rotura'         => $dias_final,
            ];
        }
        return $incidencias;
    }

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

    /**
     * Ruta optimizada para Caso 5 (Venta Cero / Posible Rotura Física).
     *
     * Evita cargar getMovimientosPeriodo (que para periodos largos puede agotar
     * la memoria). En su lugar:
     *   1. Query DISTINCT (idArticulo, fecha) solo de ventas en [fi_mov, ff_mov].
     *   2. Rebobinado de stockOn para obtener stock en ff_mov por artículo.
     *   3. Cálculo de media+3σ sobre los gaps entre días de venta.
     *
     * Funciona para cualquier longitud de periodo (semana, mes, anual…).
     */
    private function getIncidenciasCaso5(
        string $fi_mov,
        string $ff_mov,
        float  $umbral_sobrestock,   // no usado en C5, recibido por firma uniforme
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter = []
    ): array {
        $fi   = $this->db->real_escape_string($fi_mov);
        $ff   = $this->db->real_escape_string($ff_mov);
        $tipos = self::TIPOS_FISICOS;

        $where_fam = '';
        if (!empty($familias_incluir)) {
            $ids_fam = $this->expandirFamilias($familias_incluir);
            if ($ids_fam) $where_fam .= " AND l.idArticulo IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids_fam))";
        }
        if (!empty($familias_excluir)) {
            $ids_fam = $this->expandirFamilias($familias_excluir);
            if ($ids_fam) $where_fam .= " AND l.idArticulo NOT IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids_fam))";
        }

        $where_ids = empty($ids_filter)
            ? ''
            : " AND l.idArticulo IN (" . implode(',', array_map('intval', $ids_filter)) . ")";

        // Paso 1: fechas de venta únicas (ticket + albcli) por artículo físico
        $smt = $this->db->query("
            SELECT idArticulo, fecha FROM (
                SELECT DISTINCT l.idArticulo, DATE(c.Fecha) AS fecha
                FROM ticketslinea l
                INNER JOIN ticketst  c ON c.id = l.idticketst
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                  AND a.tipo IN ($tipos)
                  $where_fam
                  $where_ids
                UNION
                SELECT DISTINCT l.idArticulo, DATE(c.Fecha) AS fecha
                FROM albclilinea l
                INNER JOIN albclit   c ON c.id = l.idalbcli
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado IN ('Guardado','Procesado')
                  AND l.estadoLinea = 'Activo'
                  AND a.tipo IN ($tipos)
                  $where_fam
                  $where_ids
            ) AS ventas
            ORDER BY idArticulo, fecha
        ");
        if (!$smt) return ['error' => $this->db->error];

        $ventas_fechas = [];
        while ($r = $smt->fetch_assoc()) {
            $ventas_fechas[(int)$r['idArticulo']][$r['fecha']] = true;
        }
        if (empty($ventas_fechas)) return [];

        // Paso 2: stock en ff_mov via rebobinado desde articulosStocks.stockOn
        $ids_str = implode(',', array_keys($ventas_fechas));
        $smt = $this->db->query("
            SELECT base.idArticulo,
                   base.total_stockOn - COALESCE(post.net_posterior, 0) AS stock_actual
            FROM (
                SELECT idArticulo, SUM(stockOn) AS total_stockOn
                FROM articulosStocks WHERE idArticulo IN ($ids_str)
                GROUP BY idArticulo
            ) AS base
            LEFT JOIN (
                SELECT idArticulo, SUM(ncant_signo) AS net_posterior FROM (
                    SELECT l.idArticulo,  l.ncant AS ncant_signo
                    FROM albprolinea l INNER JOIN albprot c ON c.id = l.idalbpro
                    WHERE DATE(c.Fecha) > '$ff'
                      AND c.estado IN ('Guardado','Facturado','Exportado','Importado')
                      AND l.estadoLinea = 'Activo' AND l.idArticulo IN ($ids_str)
                    UNION ALL
                    SELECT l.idArticulo, -l.ncant
                    FROM ticketslinea l INNER JOIN ticketst c ON c.id = l.idticketst
                    WHERE DATE(c.Fecha) > '$ff' AND c.estado = 'Cerrado'
                      AND l.estadoLinea = 'Activo' AND l.idArticulo IN ($ids_str)
                    UNION ALL
                    SELECT l.idArticulo, -l.ncant
                    FROM albclilinea l INNER JOIN albclit c ON c.id = l.idalbcli
                    WHERE DATE(c.Fecha) > '$ff'
                      AND c.estado IN ('Guardado','Procesado')
                      AND l.estadoLinea = 'Activo' AND l.idArticulo IN ($ids_str)
                ) AS post_movs GROUP BY idArticulo
            ) AS post ON post.idArticulo = base.idArticulo
        ");
        if (!$smt) return ['error' => $this->db->error];

        $stock_actual = [];
        while ($r = $smt->fetch_assoc()) {
            $stock_actual[(int)$r['idArticulo']] = (float)$r['stock_actual'];
        }

        // Paso 3: lógica Caso 5 (media + 3σ) — detecta TODAS las roturas
        $ff_ts       = strtotime($ff_mov);
        $incidencias = [];

        foreach ($ventas_fechas as $id => $fechas_map) {
            $roturas = $this->_calcularRoturasC5(
                $id,
                $fechas_map,
                $stock_actual[$id] ?? 0.0,
                $ff_ts
            );
            foreach ($roturas as $r) $incidencias[] = $r;
        }

        // Añadir nombres
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

    private function maxFecha(?string $a, ?string $b): ?string
    {
        if ($a === null) return $b;
        if ($b === null) return $a;
        return ($a >= $b) ? $a : $b;
    }

    /**
     * Devuelve la lista ordenada de idArticulo que tienen algún movimiento
     * (entrada, ticket o albcli) en el rango [fi, ff], aplicando el filtro
     * de familias. Usada para paginar getIncidenciasBatch.
     */
    public function getArticulosConActividad(
        string $fi_mov,
        string $ff_mov,
        array  $familias_incluir = [],
        array  $familias_excluir = []
    ): array {
        $fi    = $this->db->real_escape_string($fi_mov);
        $ff    = $this->db->real_escape_string($ff_mov);
        $tipos = self::TIPOS_FISICOS;

        $where_fam = '';
        if (!empty($familias_incluir)) {
            $ids = $this->expandirFamilias($familias_incluir);
            if ($ids) $where_fam .= " AND l.idArticulo IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids))";
        }
        if (!empty($familias_excluir)) {
            $ids = $this->expandirFamilias($familias_excluir);
            if ($ids) $where_fam .= " AND l.idArticulo NOT IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids))";
        }

        $smt = $this->db->query("
            SELECT DISTINCT idArticulo FROM (
                SELECT l.idArticulo FROM albprolinea l
                INNER JOIN albprot c ON c.id = l.idalbpro
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado IN ('Guardado','Facturado','Exportado','Importado')
                  AND l.estadoLinea = 'Activo'
                  AND a.tipo IN ($tipos)
                  $where_fam
                UNION
                SELECT l.idArticulo FROM ticketslinea l
                INNER JOIN ticketst c ON c.id = l.idticketst
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                  AND a.tipo IN ($tipos)
                  $where_fam
                UNION
                SELECT l.idArticulo FROM albclilinea l
                INNER JOIN albclit c ON c.id = l.idalbcli
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado IN ('Guardado','Procesado')
                  AND l.estadoLinea = 'Activo'
                  AND a.tipo IN ($tipos)
                  $where_fam
            ) AS sub
            ORDER BY idArticulo
        ");
        if (!$smt) return [];
        $ids = [];
        while ($r = $smt->fetch_assoc()) $ids[] = (int)$r['idArticulo'];
        return $ids;
    }

    /**
     * Procesa las incidencias en lotes de $pagina artículos.
     *
     * Patrón idéntico a mod_reorganizacion:
     *   JS envía inicial=0  → PHP devuelve { filas, actual, total, elementos }
     *   JS envía inicial=N  → sigue hasta que actual >= total
     *
     * Para caso4 se ejecuta como lote único (no tiene artículos "con actividad").
     *
     * @return array  { filas: array, actual: int, total: int, elementos: int }
     *                — o array con clave 'error'
     */
    public function getIncidenciasBatch(array $params, int $inicial, int $pagina): array
    {
        $fi_mov          = $params['fecha_inicio_movimientos'];
        $ff_mov          = $params['fecha_fin_movimientos'];
        $familias_incluir = (array)($params['familias_incluir'] ?? []);
        $familias_excluir = (array)($params['familias_excluir'] ?? []);
        $tipo_incidencia  = (string)($params['tipo_incidencia'] ?? '');

        // caso4: lote único (artículos sin movimiento, no paginables por actividad)
        if ($tipo_incidencia === 'caso4') {
            $filas = $this->getIncidencias($params);
            if (isset($filas['error'])) return $filas;
            $total = count($filas);
            return ['filas' => $filas, 'actual' => $total, 'total' => $total, 'elementos' => $total];
        }

        $ids_todos = $this->getArticulosConActividad($fi_mov, $ff_mov, $familias_incluir, $familias_excluir);
        $total     = count($ids_todos);

        if ($total === 0) {
            return ['filas' => [], 'actual' => 0, 'total' => 0, 'elementos' => 0];
        }

        $ids_batch = array_slice($ids_todos, $inicial, $pagina);
        $elementos = count($ids_batch);
        $actual    = $inicial + $elementos;

        $params_batch              = $params;
        $params_batch['ids_filter'] = $ids_batch;

        $filas = $this->getIncidencias($params_batch);
        if (isset($filas['error'])) return $filas;

        return [
            'filas'     => $filas,
            'actual'    => $actual,
            'total'     => $total,
            'elementos' => $elementos,
        ];
    }
}
