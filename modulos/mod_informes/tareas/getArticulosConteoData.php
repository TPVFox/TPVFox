<?php
// @ Objetivo: devolver lista de artículos con nombre e idArticulo para la hoja de conteo.
// Parámetros POST:
//   ids_articulos  — string, IDs separados por comas (opcional)
//   id_familia     — int, filtro por familia (opcional)
//   id_subfamilia  — int, filtro por subfamilia (opcional)
//   solo_activos   — '1'|'0', default '1'

$ids_raw      = trim($_POST['ids_articulos'] ?? '');
$id_familia   = (int)($_POST['id_familia']   ?? 0);
$id_subfamilia = (int)($_POST['id_subfamilia'] ?? 0);
$solo_activos = ($_POST['solo_activos'] ?? '1') !== '0';

$where_parts = [];

// Filtro por IDs explícitos
if ($ids_raw !== '') {
    $ids_limpios = array_filter(
        array_map('intval', explode(',', $ids_raw)),
        fn($id) => $id > 0
    );
    if (empty($ids_limpios)) {
        $respuesta['error'] = 'IDs de artículos inválidos';
        return;
    }
    $where_parts[] = 'a.idArticulo IN (' . implode(',', $ids_limpios) . ')';
}

// Filtro por familia
if ($id_familia > 0) {
    $id_familia_esc = (int)$id_familia;
    $where_parts[] = "a.idArticulo IN (
        SELECT idArticulo FROM articulosFamilias WHERE idFamilia = $id_familia_esc
    )";
}

// Filtro por subfamilia
if ($id_subfamilia > 0) {
    $id_sf_esc = (int)$id_subfamilia;
    $where_parts[] = "a.idArticulo IN (
        SELECT idArticulo FROM articulosFamilias WHERE idSubFamilia = $id_sf_esc
    )";
}

if ($solo_activos) {
    $where_parts[] = "a.estado = 'Activo'";
}

$where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$sentencia = $BDTpv->query("
    SELECT
        a.idArticulo,
        a.articulo_name,
        COALESCE(SUM(s.stockOn), 0) AS stock_sistema
    FROM articulos a
    LEFT JOIN articulosStocks s ON s.idArticulo = a.idArticulo
    $where_sql
    GROUP BY a.idArticulo, a.articulo_name
    ORDER BY a.articulo_name
");

if (!$sentencia) {
    $respuesta['error'] = 'Error SQL: ' . $BDTpv->error;
    return;
}

$filas = [];
while ($fila = $sentencia->fetch_assoc()) {
    $filas[] = [
        'idArticulo'    => (int)$fila['idArticulo'],
        'nombre'        => $fila['articulo_name'],
        'stock_sistema' => (float)$fila['stock_sistema'],
    ];
}

$respuesta['filas'] = $filas;
$respuesta['total'] = count($filas);
