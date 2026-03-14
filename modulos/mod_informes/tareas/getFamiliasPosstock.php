<?php
// Devuelve todas las familias de la vista vw_jerarquias_familias
// ordenadas por ruta, para el filtro de POSStock.

$smt = $BDTpv->query("
    SELECT idFamilia, familiaNombre, ruta, nivel
    FROM vw_jerarquias_familias
    ORDER BY ruta
");

if (!$smt) {
    $respuesta['error'] = 'No se pudo cargar la lista de familias.';
    return;
}

$familias = [];
while ($row = $smt->fetch_assoc()) {
    $familias[] = [
        'id'     => (int)$row['idFamilia'],
        'nombre' => $row['familiaNombre'],
        'ruta'   => $row['ruta'],
        'nivel'  => (int)$row['nivel'],
    ];
}

$respuesta['familias'] = $familias;
