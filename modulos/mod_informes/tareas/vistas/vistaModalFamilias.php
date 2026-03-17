<?php
/**
 * Vista: modal de filtro de familias POSStock.
 *
 * @param array $allFamilias  Lista completa [{id, nombre, ruta, nivel}]
 * @param array $incluir      IDs/nombres ya seleccionados para incluir [{id, nombre}]
 * @param array $excluir      IDs/nombres ya seleccionados para excluir [{id, nombre}]
 * @return string  HTML del cuerpo del modal (sin cabecera/pie — se pasa a abrirModal())
 */
function renderModalFamilias(array $allFamilias, array $incluir, array $excluir): string
{
    $html = '';

    // Datalist para autocompletado
    $html .= '<datalist id="posstockFamiliasDatalist">';
    foreach ($allFamilias as $f) {
        $html .= '<option value="' . htmlspecialchars($f['nombre']) . '">';
    }
    $html .= '</datalist>';

    // Dos paneles: incluir y excluir
    $html .= '<div class="row">';
    $html .= _panelFamilias('incluir', 'Incluir solo estas familias', $incluir, 'success');
    $html .= _panelFamilias('excluir', 'Excluir estas familias', $excluir, 'warning');
    $html .= '</div>';

    $html .= '<div class="row" style="margin-top:12px;">'
        . '<div class="col-xs-12 text-right">'
        . '<button type="button" class="btn btn-primary btn-sm"'
        . ' onclick="posstockAplicarFiltroFamilias()">Aplicar filtro</button>'
        . '</div></div>';

    return $html;
}

/**
 * Renderiza un panel (tabla + buscador) para la lista incluir o excluir.
 */
function _panelFamilias(string $lista, string $titulo, array $seleccionadas, string $estilo): string
{
    $html  = '<div class="col-xs-12 col-sm-6">';
    $html .= '<h5 class="text-' . $estilo . '">' . htmlspecialchars($titulo) . '</h5>';
    $html .= '<table class="table table-condensed table-bordered small" id="posstockTabla_' . $lista . '">';
    $html .= '<thead><tr><th>Familia</th><th style="width:32px;"></th></tr></thead><tbody>';
    foreach ($seleccionadas as $s) {
        $html .= _filaFamilia((int)$s['id'], $s['nombre']);
    }
    $html .= '</tbody></table>';
    $html .= '<div id="posstockFamiliaError_' . $lista . '" class="text-danger small" style="min-height:18px;"></div>';
    $html .= '<div class="input-group input-group-sm" style="margin-top:4px;">'
        . '<input type="text" class="form-control" list="posstockFamiliasDatalist"'
        . ' id="posstockBuscar_' . $lista . '" placeholder="Buscar familia…">'
        . '<span class="input-group-btn">'
        . '<button type="button" class="btn btn-default" onclick="posstockAgregarFamilia(\'' . $lista . '\')">'
        . '<i class="glyphicon glyphicon-plus"></i></button>'
        . '</span></div>';
    $html .= '</div>';
    return $html;
}

/**
 * Genera una <tr> de familia seleccionada (usada también por JS para agregar filas).
 */
function _filaFamilia(int $id, string $nombre): string
{
    return '<tr data-id="' . $id . '" data-nombre="' . htmlspecialchars($nombre) . '">'
        . '<td>' . htmlspecialchars($nombre) . '</td>'
        . '<td><button type="button" class="btn btn-xs btn-danger"'
        . ' onclick="posstockEliminarFamilia(this)">'
        . '<i class="glyphicon glyphicon-remove"></i></button></td>'
        . '</tr>';
}
