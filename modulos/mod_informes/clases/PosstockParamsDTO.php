<?php

/**
 * Contrato de parámetros de entrada para POSStock.
 *
 * Centraliza defaults y normalización para getIncidencias/getIncidenciasBatch.
 */
final class PosstockParamsDTO
{
    public function __construct(
        public string $fecha_inicio_movimientos,
        public string $fecha_fin_movimientos,
        public string $fecha_inicio_stock,
        public string $fecha_fin_stock,
        public string $fecha_inicio_stats,
        public string $fecha_fin_stats,
        public array $familias_incluir,
        public array $familias_excluir,
        public array $ids_filter,
        public array $casos_incluir,
        public array $proveedores_incluir,
        public array $ids_proveedor_filter,
        public bool $proveedor_todos_productos
    ) {}

    public static function fromArray(array $params): self
    {
        $fechaInicioMovimientos = (string)($params['fecha_inicio_movimientos'] ?? '');
        $fechaFinMovimientos = (string)($params['fecha_fin_movimientos'] ?? '');
        $fechaInicioStock = (string)($params['fecha_inicio_stock'] ?? '');
        $fechaFinStock = (string)($params['fecha_fin_stock'] ?? '');

        return new self(
            fecha_inicio_movimientos: $fechaInicioMovimientos,
            fecha_fin_movimientos: $fechaFinMovimientos,
            fecha_inicio_stock: $fechaInicioStock,
            fecha_fin_stock: $fechaFinStock,
            fecha_inicio_stats: (string)($params['fecha_inicio_stats'] ?? $fechaInicioStock),
            fecha_fin_stats: (string)($params['fecha_fin_stats'] ?? $fechaFinMovimientos),
            familias_incluir: (array)($params['familias_incluir'] ?? []),
            familias_excluir: (array)($params['familias_excluir'] ?? []),
            ids_filter: (array)($params['ids_filter'] ?? []),
            casos_incluir: (array)($params['casos_incluir'] ?? []),
            proveedores_incluir: (array)($params['proveedores_incluir'] ?? []),
            ids_proveedor_filter: (array)($params['ids_proveedor_filter'] ?? []),
            proveedor_todos_productos: (bool)($params['proveedor_todos_productos'] ?? false)
        );
    }
}
