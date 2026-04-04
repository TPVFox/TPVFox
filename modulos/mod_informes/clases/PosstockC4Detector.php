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

        $idsArticulosFisicos = array_map(fn($filaArticulo) => (int)$filaArticulo['idArticulo'], $filasArticulosFisicos);
        if (empty($idsArticulosFisicos)) return [];

        // Paso 2: artículos con cualquier movimiento (los 3 tipos) en [fi_año, ff_mov]
        $filasArticulosConMovimiento = $this->repo->queryIdsConMovimientoC4($fechaInicio, $fechaFin);
        if (isset($filasArticulosConMovimiento['error'])) return $filasArticulosConMovimiento;

        $articulosConMovimiento = [];
        foreach ($filasArticulosConMovimiento as $filaMovimiento) {
            $articulosConMovimiento[(int)$filaMovimiento['idArticulo']] = true;
        }

        // Diff en PHP: artículos físicos (con familia) sin ningún movimiento en el año
        $idsSinMovimiento = array_values(array_filter(
            $idsArticulosFisicos,
            fn($idArticulo) => !isset($articulosConMovimiento[$idArticulo])
        ));
        if (empty($idsSinMovimiento)) return [];

        // Paso 3: stock en el momento del análisis (fecha ff_mov) via rebobinado
        $idsArticulosCsv = implode(',', array_map('intval', $idsSinMovimiento));
        $filasStock = $this->repo->queryStockRebobinado($idsArticulosCsv, $fechaFin, $c5_incluir_stock_negativo);
        if (isset($filasStock['error'])) return $filasStock;

        $resultado = [];
        foreach ($filasStock as $filaStock) {
            $resultado[(int)$filaStock['idArticulo']] = [
                'saldo_acumulado' => (float)$filaStock['stock_en_periodo'],
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
        $filasIncidencia = [];
        foreach ($articulos as $idArticulo => $articulo) {
            $filasIncidencia[] = [
                'idArticulo'    => (int)$idArticulo,
                'tipo'          => 'Stock Inactivo en Periodo',
                'severidad'     => 'BAJA',
                'stock_actual'  => $articulo['saldo_acumulado'],
                'posible_causa' => 'Stock sin actividad en el periodo',
            ];
        }
        return $filasIncidencia;
    }
}
