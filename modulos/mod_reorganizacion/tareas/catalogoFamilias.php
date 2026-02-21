
<?php
//@Objetivo:
//Crear un catalogo de familias con relacion padre-hija para mostrarlo y facilitar ciertas selecciones.
// Contiene el control de errores de las funciones que llama a la clase familia
// Diferenciar area de despligue del modal de area de selección para el catalogo.


include_once $URLCom . '/configuracion.php';
include_once $URLCom . '/modulos/mod_familia/clases/ClaseFamilias.php';
include_once $URLCom . '/modulos/mod_familia/funciones.php';
$Cfamilias = new ClaseFamilias($BDTpv);

$rutaFamilias = $Cfamilias->getFamiliasJerarquizadas();
if (isset($rutaFamilias['error'])) {
    $respuesta['advertencia'] = $rutaFamilias;
} else {
    $respuesta['datos'] = $rutaFamilias;
}

$familias = $rutaFamilias['datos'];
$respuesta = array();
$respuesta['encontrados'] = count($familias);

// Cabecera con botón de "Limpiar/Colapsar"
$respuesta['html'] = '
<div class="row" style="margin-bottom:10px;">
    <div class="col-md-6"><label>Catálogo de Familias</label></div>
    <div class="col-md-6 text-right">
        <button type="button" class="btn btn-default btn-xs" onclick="colapsarTodo()">
            <span class="glyphicon glyphicon-minus"></span> Contraer Todo
        </button>
    </div>
</div>';

$respuesta['html'] .= '
<table class="table table-hover table-condensed" id="tablaFamiliasJerarquica">
    <thead>
        <tr>
            <th style="width:40px;"></th>
            <th style="width:60px;">Id</th>
            <th>Nombre de Familia</th>
            <th style="width:120px;">Acción</th>
        </tr>
    </thead>
    <tbody>';

if (count($familias) > 0) {
    foreach ($familias as $key => $familia) {
        $rutaLimpia = str_replace(' > ', '-', trim($familia['ruta'], '/'));
        $nivel = (int)$familia['nivel'];
        // DETERMINAR SI TIENE HIJOS:
        // Miramos si existe el siguiente elemento y si su ruta empieza por la ruta actual
        $tieneHijos = false;
        if (isset($familias[$key + 1])) {
            $rutaSiguiente = str_replace(' > ', '-', trim($familias[$key + 1]['ruta'], '/'));
            if (strpos($rutaSiguiente, $rutaLimpia . '-') === 0) {
                $tieneHijos = true;
            }
        }

        // Estilo: Nivel 1 visible, el resto oculto
        $estiloOculto = ($nivel > 1) ? 'style="display: none;"' : '';
        $claseNivel = "nivel-" . $nivel;

        $claseCursor = $tieneHijos ? 'style="cursor:pointer;" onclick="toggleHijosDirectos(\'' . $rutaLimpia . '\', this)"' : '';

        $respuesta['html'] .= '<tr class="FilaFamilia ' . $claseNivel . '" data-ruta="' . $rutaLimpia . '" ' . $estiloOculto . '>';

        // Columna 1: Botón Condicional
        $respuesta['html'] .= '<td class="text-center" ' . $claseCursor . '>';
        if ($tieneHijos) {
            $iconoBtn = ($nivel == 1) ? 'glyphicon-folder-close' : 'glyphicon-chevron-right';
            $respuesta['html'] .= '<span class="glyphicon ' . $iconoBtn . ' text-primary btn-desplegar-icono"></span>';
        } else {
            $respuesta['html'] .= '<span class="glyphicon glyphicon-stop text-muted" style="font-size:8px; opacity:0.5;"></span>';
        }
        $respuesta['html'] .= '</td>';

        // Columna 2: ID
        $respuesta['html'] .= '<td ' . $claseCursor . '><span class="badge">' . $familia['idFamilia'] . '</span></td>';

        // Columna 3: Nombre con Indentación
        $padding = ($nivel - 1) * 25;
        $respuesta['html'] .= '<td style="padding-left: ' . ($padding) . 'px;" ' . $claseCursor . '>';
        if ($nivel > 1) {
            $respuesta['html'] .= '<span class="text-muted" style="margin-right:5px;"></span>';
        }
        $respuesta['html'] .= '<strong>' . htmlentities($familia['familiaNombre'], ENT_QUOTES) . '</strong></td>';

        // Columna 4: Acción (SIN el evento de toggle para evitar doble acción)
        $respuesta['html'] .= '<td>
                <button class="btn btn-primary btn-sm btn-block" onclick="event.stopPropagation(); seleccionarFamilia(' . $familia['idFamilia'] . ', \'' . addslashes($familia['familiaNombre']) . '\')">
                    <span class="glyphicon glyphicon-ok"></span> Seleccionar
                </button>
            </td>';

        $respuesta['html'] .= '</tr>';
    }
} else {
    $respuesta['html'] .= '<tr><td colspan="4" class="alert alert-warning text-center">No se encontraron familias</td></tr>';
}

$respuesta['html'] .= '</tbody></table>';

echo json_encode($respuesta);
return $respuesta;
