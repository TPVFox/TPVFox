<?php
function verSelec($BDTpv, $idSelec, $tabla)
{
    //ver seleccionado en check listado
    // Obtener datos de un id de usuario.
    $where = 'idProveedor = ' . $idSelec;
    $consulta = 'SELECT * FROM ' . $tabla . ' WHERE ' . $where;

    $unaOpc = $BDTpv->query($consulta);
    if (mysqli_error($BDTpv)) {
        $fila['error'] = 'Error en la consulta ' . $BDTpv->errno;
    } else {
        if ($unaOpc->num_rows > 0) {
            $fila = $unaOpc->fetch_assoc();
        } else {
            $fila['error'] = ' No se a encontrado proveedor';
        }
    }
    $fila['Nrow'] = $unaOpc->num_rows;
    $fila['sql'] = $consulta;
    return $fila;
}




function htmlTablaGeneral($datos, $HostNombre, $tipoDoc)
{
    if (count($datos) > 0) {
        switch ($tipoDoc) {
            case 'facturas':
                $url = $HostNombre . '/modulos/mod_compras/factura.php?id=';
                $resumen = "";
                break;
            case 'albaranes':
                $url = $HostNombre . '/modulos/mod_compras/albaran.php?id=';
                $resumen = '<input type="text" class="btn btn-info" onclick="abrirResumen(' . $datos[0]['idProveedor'] . ",'" . $tipoDoc . "'" . ')" value="Resumen" name="Resumen" >';
                break;
            case 'pedidos':
                $url = $HostNombre . '/modulos/mod_compras/pedido.php?id=';
                $resumen = "";
                break;
        }
        $html = $resumen . '<table class="table table-striped">
            <thead>
                <tr>
                    <td>Fecha</td>
                    <td>Número</td>
                    <td>Total</td>
                </tr>
            </thead>
            <tbody>';

        foreach ($datos as $dato) {
            $html .= '<tr>' .
                '<td>' . $dato['fecha'] . '</td>' .
                '<td><a href="' . $url . $dato['id'] . '&accion=ver">' . $dato['num'] . '</a></td>' .
                '<td>' . $dato['total'] . '</td>' .
                '</tr>';
        }
        $html .= '</tbody></table>';
    } else {
        $html = '<div class="alert alert-info">Este proveedor no tiene ' . $tipoDoc . '</div>';
    }

    return $html;
}
function htmlPanelDesplegable($num_desplegable, $titulo, $body)
{
    // @ Objetivo:
    // Montar html de desplegable.
    // @ Parametros:
    //      $num_desplegable -> (int) que indica el numero deplegable para un correcto funcionamiento.
    //      $titulo-> (string) El titulo que se muestra en desplegable
    //      $body-> (String) lo que contiene el desplegable.
    // Ejemplo tomado de:
    // https://www.w3schools.com/bootstrap/tryit.asp?filename=trybs_collapsible_panel&stacked=h

    $collapse = 'collapse' . $num_desplegable;
    $html = '<div class="panel panel-default">'
        .       '<div class="panel-heading">'
        .           '<h2 class="panel-title">'
        .           '<a data-toggle="collapse" href="#' . $collapse . '">'
        .           $titulo . '</a>'
        .           '</h2>'
        .       '</div>'
        .       '<div id="' . $collapse . '" class="panel-collapse collapse">'
        .           '<div class="panel-body">'
        .               $body
        .           '</div>'
        .       '</div>'
        . '</div>';
    return $html;
}

function htmlNombreClasesLinea($ids, $clase)
{
    // @ Objetivo
    // Montar nombres seguidor "Nombre_N " de clases de  un array
    // @ Parametros
    $clase_linea = '';
    foreach ($ids as $id) {
        if ($id > 0) {
            $clase_linea .= $clase . '_' . $id . ' ';
        }
    }
    return $clase_linea;
}



function comprobarFechas($fechaIni, $fechaFin)
{
    //@Objetivo: comprobar las fechas de busqueda de resumen
    //@Comprobaciones:
    //comprobar si las dos fechas están cubiertas
    //comprobar el formato de las fechas de año mes y dia
    $resultado = array();
    if ($fechaIni == "" || $fechaFin == "") {
        $resultado['error'] = 'Error';
        $resultado['consulta'] = 'Una de las fechas está sin cubrir';
    } else {
        $fechaIni = date_format(date_create($fechaIni), 'Y-m-d');
        $fechaFin = date_format(date_create($fechaFin), 'Y-m-d');
        $resultado['fechaIni'] = $fechaIni;
        $resultado['fechaFin'] = $fechaFin;
    }
    return $resultado;
}

function obtenerIndexEstado($estados, $nombre_estado)
{
    $index = '';
    if (count($estados) > 0) {
        foreach ($estados as $k => $estado) {
            if ($nombre_estado == $estado['nombre']) {
                $index = $k;
            }
        }
    }
    return $index;
}


function obtenerIconoOrden($campoOrden, $sentidoOrden, $campo)
{
    // Objetivo:
    // Obtener string con icono de orden.
    // Parametros:
    // $campoOrden -> Nombre de campo por el que esta ordenado.
    // $sentidoOrden -> ASC o DESC el orden.
    // $campo -> Actual , para comparar.
    $icon = '<span class="glyphicon glyphicon-sort';
    if ($campoOrden == $campo) {
        if ($sentidoOrden == 'ASC') {
            $icon .= '-by-attributes-alt"></span>';
        } else {
            $icon .= '-by-attributes"></span>';
        }
    } else {
        $icon .= '"></span>';
    }
    return $icon;
}

function validarResumenAnual($resumenAnual, $ano)
{
    // Objetivo:
    // Validar que el resumen anual tiene datos del año seleccionado.
    // Si no es así crear array con meses y totales a 0.
    $respuesta = array();
    $resumen_facturas = array(
        'q1Iva' => 0,
        'q1' => 0,
        'q2Iva' => 0,
        'q2' => 0,
        'q3Iva' => 0,
        'q3' => 0,
        'q4Iva' => 0,
        'q4' => 0,
        'totalIva' => 0,
        'total' => 0
    );
    if (isset($resumenAnual['resumen_facturas'])) {
        // Validamos cuantos años fiscales hay.
        foreach ($resumenAnual['resumen_facturas'] as $ano => $datos) {
            if ($ano == $ano) {
                $resumen_facturas = $datos;
            }
        }
    }
    $respuesta['facturas'] = $resumen_facturas;
    return $respuesta;
}

function htmlTablaResumenAnual($resumenAnual)
{
    // Objetivo:
    // Montar tabla html con resumen anual.
    $html = '<h4>Facturas</h4>'
        . '<table class="table table-striped"><thead>'
        . '<tr>'
        . '<th>Concepto</th>'
        . '<th>1er Trimestre</th>'
        . '<th>2º Trimestre</th>'
        . '<th>3er Trimestre</th>'
        . '<th>4º Trimestre</th>'
        . '<th>Total Anual</th>'
        . '</tr>'
        . '</thead><tbody>'
        . '<tr>'
        . '<td>Base imponible</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q1'] - $resumenAnual['facturas']['q1Iva'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q2'] - $resumenAnual['facturas']['q2Iva'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q3'] - $resumenAnual['facturas']['q3Iva'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q4'] - $resumenAnual['facturas']['q4Iva'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['total'] - $resumenAnual['facturas']['totalIva'], 2) . '</td>'
        . '</tr>'
        . '<tr>'
        . '<td>IVA</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q1Iva'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q2Iva'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q3Iva'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q4Iva'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['totalIva'], 2) . '</td>'
        . '</tr>'
        . '<td>Total</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q1'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q2'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q3'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q4'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['total'], 2) . '</td>'
        . '</tbody></table>';
    return $html;
}
