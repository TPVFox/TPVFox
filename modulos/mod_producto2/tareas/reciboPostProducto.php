<?php
/* Preparar y grabar datos del formulario de producto.
 * Se incluye desde producto.php donde ya existe $CTArticulos, $id y $Usuario.
 */
$preparados = [];

if (!isset($_POST['id'])) {
    exit();
}
$id = (int) $_POST['id'];

// --- Preparar y validar datos del POST ---
$DatosPostProducto = prepararandoPostProducto($_POST, $CTArticulos);

if (isset($DatosPostProducto['comprobaciones'])) {
    foreach ($DatosPostProducto['comprobaciones'] as $comprobacion) {
        $preparados['comprobaciones'][] = $comprobacion;
        if ($comprobacion['tipo'] === 'danger') {
            // Error grave: detenemos el guardado
            $preparados['error_grave'] = $comprobacion;
            return;
        }
    }
}

if ($id > 0) {
    // ===== MODIFICAR =====

    // Datos generales
    $comprobaciones = $CTArticulos->ComprobarNuevosDatosProducto($id, $DatosPostProducto);
    foreach ($comprobaciones as $c) {
        if (isset($c['NAfectados'])) {
            $preparados['comprobaciones'][] = [
                'tipo'    => 'success',
                'mensaje' => 'Datos generales grabados correctamente.',
                'dato'    => '',
            ];
        }
        if (isset($c['error'])) {
            $preparados['comprobaciones'][] = [
                'tipo'    => 'danger',
                'mensaje' => 'Error al grabar datos generales: ' . $c['error'],
                'dato'    => $c,
            ];
            return;
        }
    }

    // Precios
    $comprobaciones = $CTArticulos->ComprobarNuevosPreciosProducto($id, $DatosPostProducto, $Usuario['id']);
    $pvpCiva_antes  = number_format((float) $comprobaciones['pvpCiva_antes'], 2, '.', '');
    $pvpCiva_nuevo  = number_format((float) $comprobaciones['pvpCiva_nuevo'], 2, '.', '');
    $precioNuevo    = $pvpCiva_antes !== $pvpCiva_nuevo;
    foreach ($comprobaciones['mensajes'] as $mensaje) {
        $preparados['comprobaciones'][] = $mensaje;
    }

    // Codigos de barras
    $preparados['codbarras'] = $CTArticulos->ComprobarCodbarrasUnProducto($id, $DatosPostProducto['codBarras']);

    // Familias
    $preparados['familias'] = $CTArticulos->ComprobarFamiliasProducto($id, $DatosPostProducto['familias']);

    // Proveedores / costes
    $comprobaciones = $CTArticulos->ComprobarProveedoresCostes($id, $DatosPostProducto['proveedores_costes']);
    foreach ($comprobaciones as $clave => $detalle) {
        $tipo_msg = $clave === 'nuevo' ? 'añadido' : 'modificado';
        foreach ($detalle as $item) {
            $preparados['comprobaciones']['proveedor_' . $clave] = [
                'tipo'    => isset($item['error']) ? 'danger' : 'success',
                'mensaje' => isset($item['error']) ? 'Error al ' . $tipo_msg . ' proveedor.' : 'Proveedor ' . $tipo_msg . ' correctamente.',
                'dato'    => $item,
            ];
        }
    }

    // Referencia tienda
    $CTArticulos->ComprobarReferenciaProductoTienda($id, $DatosPostProducto['refProducto']);

} else {
    // ===== NUEVO =====

    $comprobaciones = $CTArticulos->comprobacionCamposObligatoriosProducto($DatosPostProducto);
    if (count($comprobaciones) > 0) {
        $preparados['comprobaciones'][] = $comprobaciones;
        return;
    }

    $anhadir = $CTArticulos->AnhadirProductoNuevo($DatosPostProducto);
    $DatosPostProducto['Sqls']['NuevoProducto'] = $anhadir;

    if (isset($anhadir['insert_articulos']['id_producto_nuevo'])) {
        $id = $anhadir['insert_articulos']['id_producto_nuevo'];
        $preparados['comprobaciones'][] = [
            'tipo'    => 'success',
            'mensaje' => 'Producto creado con ID ' . $id,
            'dato'    => json_encode($anhadir),
        ];
        if (isset($anhadir['insert_articulos_precios'])) {
            if (isset($anhadir['insert_articulos_precios']['Afectados'])) {
                $preparados['comprobaciones'][] = [
                    'tipo'    => 'success',
                    'mensaje' => 'Precios añadidos en ' . $anhadir['insert_articulos_precios']['Afectados'] . ' registros.',
                    'dato'    => '',
                ];
            } else {
                $preparados['comprobaciones'][] = $anhadir['insert_articulos_precios'];
            }
        }
        if (isset($anhadir['codbarras'])) {
            $preparados['codbarras'] = $anhadir['codbarras'];
        }
        if (isset($anhadir['RefTienda'])) {
            $preparados['RefTienda'] = $anhadir['RefTienda'];
        }
    } else {
        $preparados['comprobaciones'][] = $anhadir['insert_articulos'];
    }
}
