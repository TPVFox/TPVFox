<?php
// Devuelve la lista de proveedores activos y el HTML del modal de filtro.
//
// POST opcional:
//   proveedores_incluir_json    string  JSON [{id, nombre}] ya seleccionados
//   proveedor_todos_productos   string  '1' | '0'

include_once $URLCom . '/modulos/mod_informes/tareas/vistas/vistaModalProveedores.php';

$smt = $BDTpv->query("
    SELECT idProveedor, nombrecomercial
    FROM proveedores
    WHERE estado = 'Activo'
    ORDER BY nombrecomercial
");

if (!$smt) {
    $respuesta['error'] = 'No se pudo cargar la lista de proveedores.';
    return;
}

$proveedores = [];
while ($row = $smt->fetch_assoc()) {
    $proveedores[] = [
        'id'     => (int)$row['idProveedor'],
        'nombre' => $row['nombrecomercial'],
    ];
}

$incluir       = json_decode($_POST['proveedores_incluir_json']  ?? '[]', true) ?: [];
$todosProductos = (($_POST['proveedor_todos_productos'] ?? '0') === '1');

$respuesta['proveedores'] = $proveedores;
$respuesta['html']        = renderModalProveedores($proveedores, $incluir, $todosProductos);
