<?php
require_once __DIR__ . '/../../clases/DB.php';
function verSelec($BDTpv, $idSelec, $tabla)
{
    //ver seleccionado en check listado
    // Obtener datos de un id de usuario.
    $where = 'idProveedor = ?';
    // $tabla es un identificador (nombre de tabla): no se liga con ?, se valida con DB::ident().
    $consulta = 'SELECT * FROM ' . DB::ident($tabla) . ' WHERE ' . $where;

    $unaOpc = (new DB($BDTpv))->pquery($consulta, array($idSelec));
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
                $resumen = '<input type="text" class="btn btn-info" onclick="descargarFacturasCsv(' . $datos[0]['idProveedor'] . ')" value="Descargar CSV" name="DescargarFacturas" >';
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
        $inicio = date_create($fechaIni);
        $fin = date_create($fechaFin);
        if ($inicio === false || $fin === false) {
            // Una fecha que no se entiende se avisa, no se consulta
            $resultado['error'] = 'Error';
            $resultado['consulta'] = 'Una de las fechas no está bien escrita';
        } else {
            $resultado['fechaIni'] = date_format($inicio, 'Y-m-d');
            $resultado['fechaFin'] = date_format($fin, 'Y-m-d');
        }
    }
    return $resultado;
}

function validarCortesAbc($corteA, $corteB)
{
    // @ Objetivo:
    // Los dos cortes de las clases A, B y C, en porcentaje acumulado. Si no valen, los de partida.
    // @ Parametros:
    // $corteA -> (string) Hasta qué porcentaje acumulado es clase A.
    // $corteB -> (string) Hasta qué porcentaje acumulado es clase B.
    // @ Devolvemos:
    // (array) Los dos cortes, como números.
    $cortes = array(80.0, 95.0);
    if (is_numeric($corteA) && is_numeric($corteB)) {
        $a = floatval($corteA);
        $b = floatval($corteB);
        if ($a >= 1 && $b <= 100 && $a < $b) {
            $cortes = array($a, $b);
        }
    }
    return $cortes;
}

function cortesAbcCantidad($URLCom)
{
    // @ Objetivo:
    // Los cortes de las clases por cantidad del resumen de albaranes, de los parámetros del módulo.
    // @ Devolvemos:
    // (array) Los dos cortes; 80 y 95 si el parámetro no está o no vale.
    include_once $URLCom . '/controllers/parametros.php';
    $parametros = new ClaseParametros($URLCom . '/modulos/mod_proveedor/parametros.xml');
    $nodo = $parametros->getNode('configuracion/abc_cantidad');
    $corteA = '';
    $corteB = '';
    if ($nodo !== null && isset($nodo['corte_a']) && isset($nodo['corte_b'])) {
        $corteA = (string) $nodo['corte_a'];
        $corteB = (string) $nodo['corte_b'];
    }
    return validarCortesAbc($corteA, $corteB);
}

function ordenarPorCantidad($a, $b)
{
    // @ Objetivo: comparar dos productos para ordenarlos por cantidad, de más a menos
    return $b['totalUnidades'] <=> $a['totalUnidades'];
}

function clasificarProductosAbc($productos, $corteA, $corteB)
{
    // @ Objetivo:
    // Ordenar los productos comprados por cantidad, de más a menos, y poner a cada uno su clase por la
    // cantidad acumulada: A hasta el primer corte, B hasta el segundo y C el resto.
    // @ Parametros:
    // $productos -> (array) Los productos, cada uno con su totalUnidades.
    // $corteA, $corteB -> (float) Los cortes, en porcentaje acumulado.
    // @ Devolvemos:
    // (array) Los mismos productos, ordenados y con su 'clase'.
    usort($productos, 'ordenarPorCantidad');
    $total = 0;
    foreach ($productos as $producto) {
        $total += $producto['totalUnidades'];
    }
    $acumulado = 0;
    foreach ($productos as $indice => $producto) {
        $acumulado += $producto['totalUnidades'];
        $porcentaje = $total > 0 ? $acumulado * 100 / $total : 100;
        if ($indice === 0 || $porcentaje <= $corteA) {
            $productos[$indice]['clase'] = 'A';
        } elseif ($porcentaje <= $corteB) {
            $productos[$indice]['clase'] = 'B';
        } else {
            $productos[$indice]['clase'] = 'C';
        }
    }
    return $productos;
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
        foreach ($resumenAnual['resumen_facturas'] as $datos) {
            if ($ano == $datos['ejercicio']) {
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
        . '<tr>'
        . '<td>Total</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q1'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q2'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q3'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['q4'], 2) . '</td>'
        . '<td>' . number_format($resumenAnual['facturas']['total'], 2) . '</td>'
        . '</tr>'
        . '</tbody></table>';
    return $html;
}

function validarResumenAnualTodosProveedores($resumenAnual, $ano)
{
    // Objetivo:
    // Validar que el resumen anual tiene datos del año seleccionado.
    // Si no es así crear array con meses y totales a 0.
    $respuesta = array();
    // Sin facturas de ningún proveedor no hay filas: es una lista vacía
    if (isset($resumenAnual['resumen_facturas']) && is_array($resumenAnual['resumen_facturas'])) {
        $resumenAnual = $resumenAnual['resumen_facturas'];
    } else {
        $resumenAnual = array();
    }
    foreach ($resumenAnual as $key => $resumenProveedor) {
        $idProveedor = $resumenAnual[$key]['idProveedor'];
        $resumen = array('resumen_facturas' => array($idProveedor => $resumenProveedor));
        $respuesta[$idProveedor] = validarResumenAnual($resumen, $ano);
    }
    return $respuesta;
}

function mostrarResumenProveedor($proveedor, $resumen, $bgclass)
{
    // Objetivo:
    // Mostrar resumen anual de un proveedor.
    $html = '<h2>Resumen Anual de ' . htmlspecialchars($proveedor['nombrecomercial'], ENT_QUOTES) . '</h2>';
    $html .= '<div class="col-md-12 ' . $bgclass . '">';
    $html .= '<div class="col-md-4">';
    $html .= '<h3>Datos del Proveedor</h3>';
    $html .= '<p><strong>ID Proveedor: </strong>' . htmlspecialchars($proveedor['idProveedor'], ENT_QUOTES) . '</p>';
    $html .= '<p><strong>Nombre: </strong>' . htmlspecialchars($proveedor['nombrecomercial'], ENT_QUOTES) . '</p>';
    $html .= '<p><strong>NIF: </strong>' . htmlspecialchars($proveedor['nif'], ENT_QUOTES) . '</p>';
    $html .= '<p><strong>Dirección: </strong>' . htmlspecialchars($proveedor['direccion'], ENT_QUOTES) . '</p>';
    $html .= '<p><strong>Teléfono: </strong>' . htmlspecialchars($proveedor['telefono'], ENT_QUOTES) . '</p>';
    $html .= '<p><strong>Email: </strong>' . htmlspecialchars($proveedor['email'], ENT_QUOTES) . '</p>';
    $html .= '<a class="btn btn-primary" target="_blank" href="./../proveedor.php?id=' . urlencode($proveedor['idProveedor']) . '&accion=ver" role="button">Ver Proveedor</a> ';
    $html .= '<a class="btn  btn-info" target="_blank" href="./resumenAlbaranes.php?id=' . urlencode($proveedor['idProveedor']) . '&historyJS=1" role="button">Resumen Albaranes</a> ';
    $html .= '<a class="btn  btn-info" target="_blank" href="../../mod_compras/facturasListado.php?buscar=' . urlencode($proveedor['nombrecomercial']) . '" role="button">Facturas Proveedor</a> ';
    $html .= '</div>';
    $html .= '<div class="col-md-8">';
    $html .= htmlTablaResumenAnual($resumen);
    $html .= '</div>';
    $html .= '</div>';
    return $html;
}

function puedeLimpiarProveedor($ClasePermisos, $Usuario)
{
    // @ Objetivo:
    // Limpiar un proveedor es solo del administrador: el permiso «Limpiar» y, además, el grupo 9, así
    // concederlo a otro usuario no le sirve.
    $mod_vista_proveedor = array('modulo' => 'mod_proveedor', 'vista' => 'ListaProveedores.php');
    return isset($ClasePermisos) && $ClasePermisos->getAccion('Limpiar', $mod_vista_proveedor) == 1
        && isset($Usuario['group_id']) && $Usuario['group_id'] == 9;
}

function textoDocumentosProveedor($documentos)
{
    // @ Objetivo:
    // El aviso de los documentos del ejercicio que impiden limpiar un proveedor.
    $partes = array();
    foreach ($documentos as $nombre => $cantidad) {
        $partes[] = $cantidad . ' ' . $nombre;
    }
    return 'Tiene documentos en el ejercicio: ' . implode(', ', $partes) . '. No se puede limpiar.';
}

function comprobarLimpiezaProveedor($CProveedor, $idProveedor, $idCierreStock)
{
    // @ Objetivo:
    // Comprobar si un proveedor se puede limpiar —existe, no es el del cierre de stock y no tiene
    // documentos del ejercicio— y decir cuántos productos lo tienen como principal y como asociado.
    // @ Devolvemos:
    // (array) 'principales' y 'asociados', o 'error'.
    $proveedor = $CProveedor->consulta('SELECT idProveedor FROM proveedores WHERE idProveedor = ?', array(intval($idProveedor)));
    if (empty($proveedor['datos'])) {
        return array('error' => 'El proveedor no existe.');
    }
    if (intval($idProveedor) === intval($idCierreStock)) {
        return array('error' => 'Es el proveedor del cierre de stock anual de la configuración: no se puede limpiar.');
    }
    $documentos = $CProveedor->documentosDelEjercicio($idProveedor);
    if (count($documentos) > 0) {
        return array('error' => textoDocumentosProveedor($documentos));
    }
    $relaciones = $CProveedor->relacionesConProductos($idProveedor);
    $principales = array_column($relaciones['principales'], 'idArticulo');
    $asociados = 0;
    foreach ($relaciones['relaciones'] as $relacion) {
        if (!in_array($relacion['idArticulo'], $principales)) {
            $asociados++;
        }
    }
    return array('principales' => count($principales), 'asociados' => $asociados);
}

function proveedorDelCierreDeStock($URLCom)
{
    // @ Objetivo:
    // El proveedor del cierre de stock anual, como lo lee el cierre: la configuración de mod_reorganizacion,
    // con la copia de la instalación delante.
    $parametros = new ClaseParametros($URLCom . '/modulos/mod_reorganizacion/parametros.xml');
    $cierre = $parametros->getNodeInternBySection('configuracion', 'cierre_stock_anual');
    if ($cierre === null || !isset($cierre->ajustes_globales->proveedor['id'])) {
        return 0;
    }
    return intval($cierre->ajustes_globales->proveedor['id']);
}

function productosDelProveedor($CTArticulos, $items, $idProveedor)
{
    // @ Objetivo:
    // Componer los productos de un proveedor como los enseña su listado: los datos de cada producto, si el
    // proveedor es su principal, y el coste, la referencia y la fecha de ese proveedor. Lo usan el listado
    // y su descarga en CSV.
    // @ Parametros:
    // $items -> (array) los de ClaseProductos::GetProductosProveedor(), con idArticulo y es_principal.
    $productos = array();
    foreach ($items as $key => $item) {
        // Obtenemos datos producto, para añadir nombre Codbarras.
        $productos[$key] = $CTArticulos->GetProducto($item['idArticulo']);
        $productos[$key]['es_principal'] = (int)$item['es_principal'];
        //Costes proveedor.
        foreach ($productos[$key]['proveedores_costes'] as $pc) {
            if ($pc['idProveedor'] == $idProveedor) {
                $productos[$key]['costeProveedor'] = $pc['coste'];
                $productos[$key]['Ref_proveedor'] = $pc['crefProveedor'];
                $productos[$key]['fecha_actualizacion_proveedor'] = $pc['fechaActualizacion'];
            }
        }
        // Eliminamos atributo array
        unset($productos[$key]['proveedores_costes']);
    }
    return $productos;
}
