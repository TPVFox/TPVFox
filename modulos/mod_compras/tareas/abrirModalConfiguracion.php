<?php
$ClasesParametros = new ClaseParametros('parametros.xml');
$xml = $ClasesParametros->getNodeIntern('configuracion');

// @ Objetivo: crear el contenido del modal de configuración de compras y devolverlo al cliente para mostrarlo
// Puede ser llamado desde cualquiera de las vistas de compras (albaranes, facturas, pedidos) para mostrar la misma configuración
// @ Parámetros a mostrar:  decimales_cantidad, decimales_coste, ajuste_centimos, ajuste_centimos_desglose, max_ajuste_centimos. Añadir tambien asunto y cuerpo del email para permitir personalizar el email que se envia al proveedor cuando se genera un pedido de compra automatico

function abrirModalConfiguracion()
{
    global $xml;
    $html = '<div class="container-fluid">';
    $html .= '<form id="formConfiguracionCompras" name="formConfiguracionCompras" class="form-horizontal">';

    // --- BLOQUE 1: PRECISIÓN NUMÉRICA ---
    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-th"></i> Configuración de Precisión</div>';
    $html .= '  <div class="panel-body">';
    $html .= '    <div class="row">';
    $html .= '      <div class="col-xs-6">';
    $html .= '        <label class="control-label small">Decimales Cantidad</label>';
    $html .= '        <input type="number" class="form-control input-sm" name="inputDecimalesCantidad" value="' . $xml->decimales_cantidad . '" required>';
    $html .= '      </div>';
    $html .= '      <div class="col-xs-6">';
    $html .= '        <label class="control-label small">Decimales Coste</label>';
    $html .= '        <input type="number" class="form-control input-sm" name="inputDecimalesCoste" value="' . $xml->decimales_coste . '" required>';
    $html .= '      </div>';
    $html .= '    </div>';
    $html .= '  </div>';
    $html .= '</div>';

    // --- BLOQUE 2: AJUSTES DE CÉNTIMOS ---
    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-scissors"></i> Ajustes de Céntimos</div>';
    $html .= '  <div class="panel-body">';
    $html .= '    <div class="row">';
    $html .= '      <div class="col-xs-12 col-sm-8">';
    $html .= '        <div class="checkbox">';
    $html .= '          <label><input type="checkbox" id="inputAjusteCentimos" name="inputAjusteCentimos"' . ($xml->ajuste_centimos == 'Si' ? ' checked' : '') . '> Activar ajuste de céntimos</label>';
    $html .= '        </div>';
    $html .= '        <div class="checkbox">';
    $html .= '          <label><input type="checkbox" id="inputAjusteCentimosDesglose" name="inputAjusteCentimosDesglose"' . ($xml->ajuste_centimos_desglose == 'Si' ? ' checked' : '') . '> Ajuste en desglose de líneas</label>';
    $html .= '        </div>';
    $html .= '      </div>';
    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small">Máximo Ajuste</label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="0" max="2" class="form-control text-right" name="inputMaxAjusteCentimos" value="' . $xml->max_ajuste_centimos . '" required>';
    $html .= '          <span class="input-group-addon">€</span>';
    $html .= '        </div>';
    $html .= '      </div>';
    $html .= '    </div>';
    $html .= '  </div>';
    $html .= '</div>';

    // --- BLOQUE 3: COMUNICACIÓN ---
    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-envelope"></i> Plantilla Email Pedido Automático</div>';
    $html .= '  <div class="panel-body">';
    $html .= '    <div class="form-group" style="margin:0 10px 15px 10px;">';
    $html .= '      <label class="small">Asunto del mensaje:</label>';
    $html .= '      <input type="text" class="form-control input-sm" name="inputAsuntoEmail" value="' . $xml->email[0] . '" required>';
    $html .= '    </div>';
    $html .= '    <div class="form-group" style="margin:0 10px 0 10px;">';
    $html .= '      <label class="small">Cuerpo del email:</label>';
    $html .= '      <textarea class="form-control" name="inputCuerpoEmail" rows="4" style="resize:vertical;" required>' . $xml->email[1] . '</textarea>';
    $html .= '      <p class="help-block small">Puede usar etiquetas dinámicas según la configuración del servidor.</p>';
    $html .= '    </div>';
    $html .= '  </div>';
    $html .= '</div>';

    $html .= '</form>';
    $html .= '</div>';

    // Footer para el modal (si lo integras en tu ventanaModal)
    $html .= '<div class="modal-footer" style="border-top:0;">';
    $html .= '  <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>';
    $html .= '  <button type="button" class="btn btn-primary" id="btnGuardarConfiguracionCompras" onclick="guardarConfiguracionCompras()">';
    $html .= '    <i class="glyphicon glyphicon-floppy-disk"></i> Guardar configuración';
    $html .= '  </button>';
    $html .= '</div>';
    return $html;
}

$respuesta['titulo'] = 'Configuración de compras';
$respuesta['html'] = abrirModalConfiguracion();
