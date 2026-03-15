<?php
// Devuelve la lista de proveedores activos (idProveedor, nombrecomercial)
// para el filtro de proveedor de POSStock.

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

$respuesta['proveedores'] = $proveedores;
