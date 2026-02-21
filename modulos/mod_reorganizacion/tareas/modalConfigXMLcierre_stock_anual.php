<?php
// @ Objetivo
// Modal para configurar el cierre de stock anual, se lanza al hacer click en el boton configuración del modal de cierre de stock anual, sirve para configurar el proveedor del albaran de cierre, las familias que se incluiran y las que se excluiran del albaran de cierre.
// @ Parámetros
// ajustes_globales -> array con los ajustes globales de la aplicación, se utiliza para obtener el proveedor por defecto del albaran de cierre y las familias por defecto incluidas y excluidas del albaran de cierre.
//     proveedor -> string nombre del proveedor que se asignara al albaran de cierre
//     proveedor (atributo id) ->  int id del proveedor que se asignara al albaran de cierre
//     num_productos -> int numero de productos que se incluiran en el albaran de cierre, se muestra como referencia pero no es un valor que se guarde, se calcula en base a las familias incluidas y excluidas.
//     reescribir_albaran -> boolean que indica si se debe reescribir el albaran de cierre si ya existe uno con el mismo año, se muestra como referencia pero no es un valor que se guarde, se utiliza para mostrar un mensaje de advertencia al usuario en caso de que ya exista un albaran de cierre para el año actual.
//     serie_albaran -> cierre: string serie del albaran de cierre, se muestra como referencia pero no es un valor que se guarde, se utiliza para mostrar un mensaje de advertencia al usuario en caso de que ya exista un albaran de cierre para el año actual con la misma serie.
//     serie_albaran -> apertura: string serie del albaran de apertura, se muestra como referencia pero no es un valor que se guarde, se utiliza para mostrar un mensaje de advertencia al usuario en caso de que ya exista un albaran de apertura para el año siguiente con la misma serie.
// familias_excluidas -> array con las familias que se excluiran en el albaran de cierre, se muestra como referencia pero no es un valor que se guarde, se utiliza para mostrar un mensaje de advertencia al usuario en caso de que ya exista un albaran de cierre para el año actual con las mismas familias excluidas.
//     familia -> string nombre de la familia que se excluirá en el albaran de cierre
//     familia (atributo id) -> int id de la familia que se excluirá en el albaran de cierre
// Este modal debe permitir modificar todos los parametros de parametros xml den la sección cierre_stock_anual.
// Los ajustes globales los nodos son estaticos
// familias_excluidas se permite añadir y borrar nodos de tipo familia, cada nodo familia tiene un atributo id que se corresponde con el id de la familia en la base de datos, el valor del nodo familia es el nombre de la familia, al guardar los cambios se deben guardar los nodos familia con su atributo id correspondiente, si se borra un nodo familia se debe eliminar del xml, si se añade un nodo familia se debe añadir al xml con su atributo id correspondiente.

$html = '<div class="container-fluid">';
$html .= '<form id="formConfigXML" class="form-horizontal">';

// --- SECCIÓN 1: AJUSTES GLOBALES ---
$html .= '<div class="panel panel-default">';
$html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-cog"></i> Ajustes Generales</div>';
$html .= '  <div class="panel-body">';
$html .= '    <div class="row">';

// Proveedor
$html .= '      <div class="col-xs-12 col-sm-8">';
$html .= '        <div class="form-group" style="margin:0 0 15px 0;">';
$html .= '          <label class="small">Proveedor por Defecto (Albarán):</label>';
$html .= '          <div class="row">'; // Usamos una fila interna para dividir ID y Nombre
$html .= '            <div class="col-xs-8" style="padding-right:5px;">';
$html .= '              <div class="input-group input-group-sm">';
$html .= '                <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>';
$html .= '                <input type="text" class="form-control" name="Proveedor" id="Proveedor" data-obj= "cajaProveedor" value="' . $xml->ajustes_globales->proveedor . '" placeholder="Nombre..." onkeydown="controlEventos(event)">';
$html .= '              </div>';
$html .= '            </div>';
$html .= '            <div class="col-xs-4" style="padding-left:0;">';
$html .= '              <div class="input-group input-group-sm">';
$html .= '                <input type="text" class="form-control" name="id_proveedor" id="id_proveedor" data-obj= "cajaIdProveedor" value="' . $xml->ajustes_globales->proveedor['id'] . '" placeholder="ID" onkeydown="controlEventos(event)">';
$html .= '                <span class="input-group-btn">';
$html .= '                  <button type="button" class="btn btn-default" onclick="buscarProveedor(\'' . $dedonde . '\',\'Proveedor.value\')">';
$html .= '                    <i class="glyphicon glyphicon-search"></i>';
$html .= '                  </button>';
$html .= '                </span>';
$html .= '              </div>';
$html .= '            </div>';
$html .= '          </div>'; // Cierre Row interno
$html .= '        </div>';
$html .= '      </div>';

// Reescribir
$html .= '      <div class="col-xs-12 col-sm-4">';
$html .= '        <div class="checkbox" style="margin-top: 25px;">';
$html .= '          <label class="fw-bold"><input type="checkbox" name="reescribir_albaran" ' . ($xml->ajustes_globales->reescribir_albaran == "true" ? 'checked' : '') . '> Reescribir si existe</label>';
$html .= '        </div>';
$html .= '      </div>';

$html .= '    </div>'; // Cierre Row

$html .= '    <div class="row">';
// Serie Apertura
$html .= '      <div class="col-xs-6 col-sm-4">';
$html .= '        <label class="small">Serie Apertura:</label>';
$html .= '        <input type="text" class="form-control input-sm text-center" name="serie_apertura" value="' . $xml->ajustes_globales->serie_albaran->apertura . '">';
$html .= '      </div>';
// Serie Cierre
$html .= '      <div class="col-xs-6 col-sm-4">';
$html .= '        <label class="small">Serie Cierre:</label>';
$html .= '        <input type="text" class="form-control input-sm text-center" name="serie_cierre" value="' . $xml->ajustes_globales->serie_albaran->cierre . '">';
$html .= '      </div>';
// Info Productos (Readonly)
$html .= '      <div class="col-xs-12 col-sm-4">';
$html .= '        <label class="small">Productos por Albarán:</label>';
$html .= '        <input type="number" class="form-control text-primary fw-bold" value="' . number_format((float)$xml->ajustes_globales->num_productos, 0, ',', '.') . '" >';
$html .= '      </div>';
$html .= '    </div>';

$html .= '  </div>'; // Cierre Panel Body
$html .= '</div>'; // Cierre Panel

// --- SECCIÓN 2: GESTIÓN DE FAMILIAS EXCLUIDAS ---
$html .= '<div class="panel panel-danger" style="border-color: #ebccd1;">';
$html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-ban-circle"></i> Familias Excluidas del Cierre</div>';
$html .= '  <div class="panel-body" style="background-color: #fff5f5;">';
$html .= '    <p class="text-muted small">Las familias listadas aquí no se pondrán a cero durante el proceso masivo.</p>';

$html .= '    <div class="table-responsive" style="max-height: 200px; overflow-y: auto; background: #fff; border: 1px solid #ddd; border-radius: 4px;">';
$html .= '      <table class="table table-condensed table-hover mb-0" id="tablaFamiliasExcluidas">';
$html .= '        <thead><tr class="active"><th class="small">ID</th><th class="small">Nombre de Familia</th><th width="40"></th></tr></thead>';
$html .= '        <tbody>';
foreach ($xml->familias_excluidas->familia as $familia) {
    $html .= '      <tr data-id="' . $familia['id'] . '">';
    $html .= '        <td class="v-align-middle">' . $familia['id'] . '</td>';
    $html .= '        <td class="v-align-middle">' . $familia . '</td>';
    $html .= '        <td><button type="button" class="btn btn-xs btn-link text-danger btnEliminarFamilia" onclick="eliminarFamilia(this)"><i class="glyphicon glyphicon-trash"></i></button></td>';
    $html .= '      </tr>';
}
$html .= '        </tbody>';
$html .= '      </table>';
$html .= '    </div>';

// Agregar Nueva Familia
$html .= '<div class="row" style="margin-top:10px; display: flex; align-items: center;">';

// 1. Bloque de Identificación y Búsqueda (9 columnas)
$html .= '<div class="col-xs-9">';
$html .= '    <div class="row">';
// ID
$html .= '        <div class="col-xs-3" style="padding-right:5px;">';
$html .= '            <input type="text" id="id_familia" name="id_familia" data-obj="cajaIdFamilia" class="form-control input-sm" placeholder="ID" onkeydown="controlEventos(event)">';
$html .= '        </div>';

// Nombre + Botones de búsqueda (agrupados para que queden pegados)
$html .= '        <div class="col-xs-9">';
$html .= '            <div class="input-group">';
$html .= '                <input type="text" id="Familia" name="Familia" data-obj="cajaFamilia" class="form-control input-sm" placeholder="Nombre Familia..." onkeydown="controlEventos(event)">';
$html .= '                <span class="input-group-btn">';
$html .= '                    <button type="button" class="btn btn-sm btn-default" title="Buscar" onclick="buscarFamilia(\'' . $dedonde . '\',\'Familia.value\')">';
$html .= '                        <i class="glyphicon glyphicon-search"></i>';
$html .= '                    </button>';
$html .= '                    <button type="button" class="btn btn-sm btn-default" title="Catálogo" onclick="catalogoFamilias()">';
$html .= '                        <i class="glyphicon glyphicon-book"></i>';
$html .= '                    </button>';
$html .= '                </span>';
$html .= '            </div>';
$html .= '        </div>';
$html .= '    </div>';
$html .= '</div>';

// 2. Bloque de Acción Separado (3 columnas)
$html .= '<div class="col-xs-3">';
$html .= '    <button type="button" id="btnAgregarFamilia" class="btn btn-sm btn-block btn-success" title="Agregar Nueva Familia" onclick="agregarFamilia()">';
$html .= '        <i class="glyphicon glyphicon-plus"></i> AGREGAR';
$html .= '    </button>';
$html .= '</div>';

$html .= '</div>';

$html .= '  </div>';
$html .= '</div>';

$html .= '</form>';
$html .= '</div>';

// Footer separado del body para el modal
$html .= '<div class="modal-footer" style="margin-top:15px; padding:15px 0 0 0;">';
$html .= '  <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>';
$html .= '  <button type="button" onclick="guardarConfiguracionXML()" class="btn btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar Configuración</button>';
$html .= '</div>';

$respuesta['html'] = $html;
