<?php
/**
 * Vista: modal de filtro de proveedores POSStock.
 *
 * @param array $allProveedores    Lista completa [{id, nombre}]
 * @param array $incluir           Proveedores ya seleccionados [{id, nombre}]
 * @param bool  $todosProductos    Estado del checkbox "todos los productos"
 * @return string  HTML del cuerpo del modal
 */
function renderModalProveedores(array $allProveedores, array $incluir, bool $todosProductos): string
{
    $html = '';

    // Datalist para autocompletado
    $html .= '<datalist id="posstockProveedoresDatalist">';
    foreach ($allProveedores as $p) {
        $html .= '<option value="' . htmlspecialchars($p['nombre']) . '">';
    }
    $html .= '</datalist>';

    // Tabla de proveedores seleccionados
    $html .= '<table class="table table-condensed table-bordered small" id="posstockTablaProveedores">';
    $html .= '<thead><tr><th>Proveedor</th><th style="width:32px;"></th></tr></thead><tbody>';
    foreach ($incluir as $p) {
        $html .= _filaProveedor((int)$p['id'], $p['nombre']);
    }
    $html .= '</tbody></table>';

    $html .= '<div id="posstockProveedorError" class="text-danger small" style="min-height:18px;"></div>';
    $html .= '<div class="input-group input-group-sm" style="margin-top:4px;">'
        . '<input type="text" class="form-control" list="posstockProveedoresDatalist"'
        . ' id="posstockBuscarProveedor" placeholder="Buscar proveedor…">'
        . '<span class="input-group-btn">'
        . '<button type="button" class="btn btn-default" onclick="posstockAgregarProveedor()">'
        . '<i class="glyphicon glyphicon-plus"></i></button>'
        . '</span></div>';

    $chk = $todosProductos ? ' checked' : '';
    $html .= '<div class="checkbox" style="margin-top:10px;">'
        . '<label><input type="checkbox" id="posstockChkTodosProductos"' . $chk . '>'
        . ' Mostrar todos los artículos del proveedor (aunque no tengan incidencias)</label>'
        . '</div>';

    $html .= '<div class="text-right" style="margin-top:8px;">'
        . '<button type="button" class="btn btn-primary btn-sm"'
        . ' onclick="posstockAplicarFiltroProveedores()">Aplicar filtro</button>'
        . '</div>';

    return $html;
}

/**
 * Genera una <tr> de proveedor seleccionado.
 */
function _filaProveedor(int $id, string $nombre): string
{
    return '<tr data-id="' . $id . '" data-nombre="' . htmlspecialchars($nombre) . '">'
        . '<td>' . htmlspecialchars($nombre) . '</td>'
        . '<td><button type="button" class="btn btn-xs btn-danger"'
        . ' onclick="posstockEliminarProveedor(this)">'
        . '<i class="glyphicon glyphicon-remove"></i></button></td>'
        . '</tr>';
}
