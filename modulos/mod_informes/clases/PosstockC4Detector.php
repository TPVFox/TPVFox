<?php

/**
 * PosstockC4Detector — Detector de Stock Inactivo en Periodo (Caso C4).
 *
 * Extraído de ClasePosstock como parte de la Fase 4 de refactorización.
 *
 * Métodos públicos:
 *   detectar(...)      — obtiene artículos sin movimiento en el año
 *   formatearIncidencias(...) — convierte el resultado en filas de incidencia
 */

class PosstockC4Detector
{
    public function __construct(
        private mysqli $db,
        private PosstockQueryRepository $repo
    ) {}

    /**
     * Obtiene artículos físicos sin ningún movimiento (entrada o venta) en el periodo.
     *
     * @param string $fi_año                   Inicio del año analizado ('YYYY-MM-DD')
     * @param string $ff_mov                   Fin del periodo analizado ('YYYY-MM-DD')
     * @param array  $familias_incluir
     * @param array  $familias_excluir
     * @param bool   $c5_incluir_stock_negativo Incluir artículos con stock negativo
     *
     * @return array  Indexado por idArticulo con saldo_acumulado — o ['error' => ...]
     */
    public function detectar(
        string $fi_año,
        string $ff_mov,
        array  $familias_incluir = [],
        array  $familias_excluir = [],
        bool   $c5_incluir_stock_negativo = false
    ): array {
        $fi    = $this->db->real_escape_string($fi_año);
        $ff    = $this->db->real_escape_string($ff_mov);

        // Filtro de familias sobre la tabla articulos (alias 'a')
        $where_familia = '';
        if (!empty($familias_incluir)) {
            $ids_fam = $this->repo->expandirFamilias($familias_incluir);
            if ($ids_fam) $where_familia .= " AND a.idArticulo IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids_fam))";
        }
        if (!empty($familias_excluir)) {
            $ids_fam = $this->repo->expandirFamilias($familias_excluir);
            if ($ids_fam) $where_familia .= " AND a.idArticulo NOT IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids_fam))";
        }

        // Paso 1: todos los artículos físicos (con filtro de familia)
        $rows_fisicos = $this->repo->queryArticulosFisicos($where_familia);
        if (isset($rows_fisicos['error'])) return $rows_fisicos;

        $todos_ids = array_map(fn($r) => (int)$r['idArticulo'], $rows_fisicos);
        if (empty($todos_ids)) return [];

        // Paso 2: artículos con cualquier movimiento (los 3 tipos) en [fi_año, ff_mov]
        $rows_con_mov = $this->repo->queryIdsConMovimientoC4($fi, $ff);
        if (isset($rows_con_mov['error'])) return $rows_con_mov;

        $con_movimiento = [];
        foreach ($rows_con_mov as $r) $con_movimiento[(int)$r['idArticulo']] = true;

        // Diff en PHP: artículos físicos (con familia) sin ningún movimiento en el año
        $sin_movimiento = array_values(array_filter($todos_ids, fn($id) => !isset($con_movimiento[$id])));
        if (empty($sin_movimiento)) return [];

        // Paso 3: stock en el momento del análisis (fecha ff_mov) via rebobinado
        $ids_str    = implode(',', array_map('intval', $sin_movimiento));
        $rows_stock = $this->repo->queryStockRebobinado($ids_str, $ff, $c5_incluir_stock_negativo);
        if (isset($rows_stock['error'])) return $rows_stock;

        $resultado = [];
        foreach ($rows_stock as $row) {
            $resultado[(int)$row['idArticulo']] = [
                'saldo_acumulado' => (float)$row['stock_en_periodo'],
                'ultima_compra'   => null,
                'ultima_venta'    => null,
            ];
        }
        return $resultado;
    }

    /**
     * Convierte el resultado de detectar() en filas de incidencia caso4.
     *
     * @param array $articulos  Output de detectar(), indexado por idArticulo
     *
     * @return array  Filas de incidencia
     */
    public function formatearIncidencias(array $articulos): array
    {
        $rows = [];
        foreach ($articulos as $id => $art) {
            $rows[] = [
                'idArticulo'    => (int)$id,
                'tipo'          => 'Stock Inactivo en Periodo',
                'severidad'     => 'BAJA',
                'stock_actual'  => $art['saldo_acumulado'],
                'posible_causa' => 'Stock sin actividad en el periodo',
            ];
        }
        return $rows;
    }
}
