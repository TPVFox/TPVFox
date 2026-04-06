<?php

/**
 * PosstockC4Detector — Detector de Stock Sin Ventas en Periodo (Caso C4).
 *
 * C4 analiza los artículos que quedan FUERA de la ventana de análisis de C1-C9:
 * aquellos sin ventas en el periodo. Los demás detectores (C1, C3, C5...) solo
 * trabajan con artículos que tuvieron movimiento de venta.
 *
 * C4 subdivide en:
 *   - C4-A: Sin recepciones NI ventas en el periodo (stock totalmente muerto)
 *   - C4-B: Recepciones pero SIN ventas (se compra pero no se vende = dinero inmovilizado)
 *
 * C4-B es más grave que C4-A porque implica gasto activo sin retorno.
 *
 * Métodos públicos:
 *   detectar(...)             — obtiene artículos sin ventas en el periodo
 *   formatearIncidencias(...) — convierte en filas con tipo de inactividad
 */

class PosstockC4Detector
{
    public function __construct(
        private mysqli $db,
        private PosstockQueryRepository $repo
    ) {}

    /**
     * Detecta artículos físicos sin ventas en el periodo [fi_año, ff_mov].
     *
     * Flujo:
     * 1. Obtiene todos los artículos físicos (con filtros de familia/proveedor)
     * 2. Excluye los que tienen ventas en el periodo → candidatos C4
     * 3. Obtiene stock rebobinado a ff_mov
     * 4. Cuenta recepciones EN EL PERIODO para clasificar A vs B
     *
     * @param string $fi_año                      Inicio del periodo ('YYYY-MM-DD')
     * @param string $ff_mov                      Fin del periodo ('YYYY-MM-DD')
     * @param array  $familias_incluir
     * @param array  $familias_excluir
     * @param bool   $c4_incluir_stock_negativo   Si false, excluye artículos con stock <= 0
     * @param array  $ids_filter                  IDs pre-filtrados por proveedor (vacío = sin filtro)
     *
     * @return array  Indexado por idArticulo o ['error' => ...]
     */
    public function detectar(
        string $fi_año,
        string $ff_mov,
        array  $familias_incluir = [],
        array  $familias_excluir = [],
        bool   $c4_incluir_stock_negativo = false,
        array  $ids_filter = []
    ): array {
        $fechaInicio = $this->db->real_escape_string($fi_año);
        $fechaFin    = $this->db->real_escape_string($ff_mov);

        // Filtro de familias sobre la tabla articulos (alias 'a')
        $filtroFamiliasSql = '';
        if (!empty($familias_incluir)) {
            $idsFamilias = $this->repo->expandirFamilias($familias_incluir);
            if ($idsFamilias) $filtroFamiliasSql .= " AND a.idArticulo IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($idsFamilias))";
        }
        if (!empty($familias_excluir)) {
            $idsFamilias = $this->repo->expandirFamilias($familias_excluir);
            if ($idsFamilias) $filtroFamiliasSql .= " AND a.idArticulo NOT IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($idsFamilias))";
        }

        // Paso 1: todos los artículos físicos (con filtro de familia)
        $filasArticulosFisicos = $this->repo->queryArticulosFisicos($filtroFamiliasSql);
        if (isset($filasArticulosFisicos['error'])) return $filasArticulosFisicos;

        $idsArticulosFisicos = array_map(fn($f) => (int)$f['idArticulo'], $filasArticulosFisicos);
        if (!empty($ids_filter)) {
            $idsArticulosFisicos = array_values(array_intersect($idsArticulosFisicos, $ids_filter));
        }
        if (empty($idsArticulosFisicos)) return [];

        // Paso 2: artículos con VENTAS en el periodo → excluir (los cubren C1/C3/C5)
        $filasConVentas = $this->repo->queryIdsConVentasC4($fechaInicio, $fechaFin);
        if (isset($filasConVentas['error'])) return $filasConVentas;

        $idsConVentas = [];
        foreach ($filasConVentas as $fila) {
            $idsConVentas[(int)$fila['idArticulo']] = true;
        }

        $idsSinVentas = array_values(array_filter(
            $idsArticulosFisicos,
            fn($id) => !isset($idsConVentas[$id])
        ));
        if (empty($idsSinVentas)) return [];

        // Paso 3: stock rebobinado a ff_mov
        $idsCsv = implode(',', array_map('intval', $idsSinVentas));
        $filasStock = $this->repo->queryStockRebobinado($idsCsv, $fechaFin);
        if (isset($filasStock['error'])) return $filasStock;

        $stockMap = [];
        foreach ($filasStock as $fila) {
            $stockMap[(int)$fila['idArticulo']] = (float)$fila['stock_en_periodo'];
        }

        // Paso 4: recepciones EN EL PERIODO para candidatos → clasificar A vs B
        $recepcionesPeriodo = $this->repo->queryRecepcionesPeriodoC4($idsCsv, $fechaInicio, $fechaFin);
        if (isset($recepcionesPeriodo['error'])) return $recepcionesPeriodo;

        // Días del periodo para graduar severidad y causa
        $diasPeriodo = (int)(new DateTime($ff_mov))->diff(new DateTime($fi_año))->days + 1;

        $resultado = [];
        foreach ($idsSinVentas as $idArticulo) {
            $stock = $stockMap[$idArticulo] ?? 0.0;
            $rec = $recepcionesPeriodo[$idArticulo] ?? null;
            $tieneRecepciones = ($rec !== null && $rec['n_recepciones'] > 0);

            // C4-A (sin_movimiento): filtrar por stock > 0 (stock muerto en estante)
            // C4-B (compras_sin_ventas): siempre incluir (gasto activo sin retorno)
            if (!$tieneRecepciones && !$c4_incluir_stock_negativo && $stock <= 0.0) {
                continue;
            }

            $resultado[$idArticulo] = [
                'saldo_acumulado'    => $stock,
                'tipo_inactividad'   => $tieneRecepciones ? 'compras_sin_ventas' : 'sin_movimiento',
                'n_recepciones'      => $rec['n_recepciones'] ?? 0,
                'cantidad_recibida'  => $rec['cantidad_recibida'] ?? 0.0,
                'ultima_recepcion'   => $rec['ultima_recepcion'] ?? null,
                'dias_periodo'       => $diasPeriodo,
            ];
        }
        return $resultado;
    }

    /**
     * Convierte el resultado de detectar() en filas de incidencia caso4.
     *
     * Subtipos:
     *   - sin_movimiento: C4-A — stock muerto, ni compras ni ventas
     *   - compras_sin_ventas: C4-B — se compra pero no se vende (más grave)
     *
     * Severidad graduada por ventana temporal:
     *   C4-A: BAJA (≤30d) → MEDIA (31-120d) → ALTA (>120d)
     *   C4-B: MEDIA (≤30d) → ALTA (>30d)
     *
     * @param array $articulos  Output de detectar(), indexado por idArticulo
     * @return array  Filas de incidencia
     */
    public function formatearIncidencias(array $articulos): array
    {
        $filasIncidencia = [];
        foreach ($articulos as $idArticulo => $articulo) {
            $tipo = $articulo['tipo_inactividad'];
            $esB  = ($tipo === 'compras_sin_ventas');
            $dias = $articulo['dias_periodo'] ?? 0;

            $filasIncidencia[] = [
                'idArticulo'        => (int)$idArticulo,
                'tipo'              => 'Stock Inactivo en Periodo',
                'subtipo'           => $tipo,
                'severidad'         => $this->calcularSeveridad($esB, $dias),
                'dias_periodo'      => $dias,
                'stock_actual'      => $articulo['saldo_acumulado'],
                'n_recepciones'     => $articulo['n_recepciones'],
                'cantidad_recibida' => $articulo['cantidad_recibida'],
                'ultima_recepcion'  => $articulo['ultima_recepcion'],
                'posible_causa'     => $esB
                    ? $this->causaC4B($dias, $articulo['n_recepciones'], $articulo['cantidad_recibida'])
                    : $this->causaC4A($dias),
            ];
        }
        return $filasIncidencia;
    }

    // ── Severidad graduada por ventana temporal ───────────────────────────

    private function calcularSeveridad(bool $esB, int $dias): string
    {
        if ($esB) {
            return $dias > 30 ? 'ALTA' : 'MEDIA';
        }
        if ($dias > 120) return 'ALTA';
        if ($dias > 30)  return 'MEDIA';
        return 'BAJA';
    }

    // ── Posible causa contextualizada ─────────────────────────────────────

    private function causaC4A(int $dias): string
    {
        if ($dias > 120) {
            return "Stock muerto: $dias días sin ningún movimiento — valorar liquidación o baja de referencia";
        }
        if ($dias > 30) {
            return "Sin compras ni ventas en $dias días — valorar si el artículo sigue en el surtido activo";
        }
        return "Sin actividad en $dias días — puede ser normal si la rotación del artículo es baja o estacional";
    }

    private function causaC4B(int $dias, int $nRec, float $cantRecibida): string
    {
        $q = number_format($cantRecibida, 1, ',', '.');
        if ($dias > 120) {
            return "$nRec recepciones ($q uds.) en $dias días sin salida — fallo sistemático: se sigue comprando un artículo sin demanda";
        }
        if ($dias > 30) {
            return "$nRec recepciones ($q uds.) en $dias días sin ninguna venta — dinero inmovilizado, revisar proceso de pedido";
        }
        return "$nRec recepciones ($q uds.) en $dias días sin ventas — verificar ubicación, EAN y presencia en sala";
    }
}
