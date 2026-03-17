<?php
// Devuelve todas las familias y el HTML del modal de filtro.
//
// POST opcional:
//   familias_incluir_json  string  JSON [{id, nombre}] ya seleccionados para incluir
//   familias_excluir_json  string  JSON [{id, nombre}] ya seleccionados para excluir

include_once $URLCom . '/modulos/mod_informes/tareas/vistas/vistaModalFamilias.php';

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

$incluir = json_decode($_POST['familias_incluir_json'] ?? '[]', true) ?: [];
$excluir = json_decode($_POST['familias_excluir_json'] ?? '[]', true) ?: [];

$respuesta['familias'] = $familias;
$respuesta['html']     = renderModalFamilias($familias, $incluir, $excluir);
