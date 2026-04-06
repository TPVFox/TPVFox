<?php
// @ Objetivo:
// Devuelve el catálogo jerárquico de familias para el selector de informes.
// El botón de cada fila llama a agregarFamiliaInforme() en lugar de seleccionarFamilia()
// para permitir selección múltiple sin cerrar el modal.

include_once $URLCom . '/modulos/mod_familia/clases/ClaseFamilias.php';
$Cfamilias = new ClaseFamilias($BDTpv);
$rutaFamilias = $Cfamilias->getFamiliasJerarquizadas();
$familias = $rutaFamilias['datos'] ?? [];

$respuesta['encontrados'] = count($familias);

$respuesta['html'] = '
<div class="row" style="margin-bottom:10px;">
    <div class="col-md-6"><label>Selecciona una o más familias</label></div>
    <div class="col-md-6 text-right">
        <button type="button" class="btn btn-default btn-xs" onclick="colapsarTodoCatalogo()">
            <span class="glyphicon glyphicon-minus"></span> Contraer todo
        </button>
    </div>
</div>';

$respuesta['html'] .= '
<table class="table table-hover table-condensed" id="tablaFamiliasJerarquica">
    <thead>
        <tr>
            <th style="width:40px;"></th>
            <th style="width:60px;">Id</th>
            <th>Nombre</th>
            <th style="width:120px;">Acción</th>
        </tr>
    </thead>
    <tbody>';

if (count($familias) > 0) {
    foreach ($familias as $key => $familia) {
        $rutaLimpia = str_replace(' > ', '-', trim($familia['ruta'], '/'));
        $nivel      = (int)$familia['nivel'];

        $tieneHijos = false;
        if (isset($familias[$key + 1])) {
            $rutaSig = str_replace(' > ', '-', trim($familias[$key + 1]['ruta'], '/'));
            if (strpos($rutaSig, $rutaLimpia . '-') === 0) {
                $tieneHijos = true;
            }
        }

        $estiloOculto = ($nivel > 1) ? 'style="display:none;"' : '';
        $claseNivel   = 'nivel-' . $nivel;
        $claseCursor  = $tieneHijos
            ? 'style="cursor:pointer;" onclick="toggleHijosDirectosCat(this)"'
            : '';

        $respuesta['html'] .= '<tr class="FilaFamilia ' . $claseNivel . '" data-ruta="' . $rutaLimpia . '" ' . $estiloOculto . '>';

        // Columna expand
        $respuesta['html'] .= '<td class="text-center" ' . $claseCursor . '>';
        if ($tieneHijos) {
            $icono = ($nivel == 1) ? 'glyphicon-folder-close' : 'glyphicon-chevron-right';
            $respuesta['html'] .= '<span class="glyphicon ' . $icono . ' text-primary btn-desplegar-icono"></span>';
        } else {
            $respuesta['html'] .= '<span class="glyphicon glyphicon-stop text-muted" style="font-size:8px;opacity:0.5;"></span>';
        }
        $respuesta['html'] .= '</td>';

        // Columna ID
        $respuesta['html'] .= '<td ' . $claseCursor . '><span class="badge">' . $familia['idFamilia'] . '</span></td>';

        // Columna nombre
        $padding = ($nivel - 1) * 25;
        $respuesta['html'] .= '<td style="padding-left:' . $padding . 'px;" ' . $claseCursor . '>';
        $respuesta['html'] .= '<strong>' . htmlentities($familia['familiaNombre'], ENT_QUOTES) . '</strong></td>';

        // Columna acción — llama a agregarFamiliaInforme (multi-select)
        $respuesta['html'] .= '<td>
            <button class="btn btn-primary btn-sm btn-block"
                    onclick="event.stopPropagation(); agregarFamiliaInforme('
            . $familia['idFamilia'] . ', \''
            . addslashes($familia['familiaNombre']) . '\')">
                <span class="glyphicon glyphicon-plus"></span> Añadir
            </button>
        </td>';

        $respuesta['html'] .= '</tr>';
    }
} else {
    $respuesta['html'] .= '<tr><td colspan="4" class="alert alert-warning text-center">No se encontraron familias</td></tr>';
}

$respuesta['html'] .= '</tbody></table>';
