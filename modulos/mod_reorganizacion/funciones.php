<?php
// Archivo de funciones para el modulo de reorganizacion
// modulos/mod_reorganizacion/funciones.php


// Obtener datos de productos para albaran de cierre
function obtenerDatosProductoAlbaranCierre($arrayIdsArticulos, $idFamilia = null)
{
    global $CReorganizar;
    $productos = array();
    foreach ($arrayIdsArticulos as $key => $idArticulo) {
        $resultado = $CReorganizar->articulosAlbaranCierre($idArticulo['idArticulo']);
        if (empty($resultado)) {
            continue;
        }
        if ($idFamilia !== null) {
            // Maximo 100 caracters
            $resultado['cdetalle'] = substr($resultado['cdetalle'], 0, 80);
            $resultado['cdetalle'] .= ' CIERRE ID: ' . $idFamilia;
        }
        $datosProducto = [
            'idArticulo' => $idArticulo['idArticulo'],
            'ncant' => -$idArticulo['stockOn'],
            'nunidades' => -$idArticulo['stockOn'],
            'nfila' => $key,
            'ccodbar' => $resultado['ccodbar'],
            'cdetalle' => $resultado['cdetalle'],
            'ultimoCoste' => $resultado['costSiva'],
            'iva' => $resultado['iva'],
            'estado' => 'Activo',
            'cref' => '',
            'ref_prov' => '',
            'idPedido' => 0
        ];
        $productos[] = $datosProducto;
    }
    return $productos;
}

function generarCierreAlbaran($productos, $familia_id = null, $idProveedor = 56)
{
    $idTienda = $_SESSION['tiendaTpv']['idTienda'];
    $ano = $_SESSION['tiendaTpv']['ano'];
    $idUsuario = $_SESSION['usuarioTpv']['id'];

    $fechaCierre = $ano . '-12-31 00:00:00';

    $totalSinIva = 0;
    $totalIva = 0;
    $basesYivas = array();
    foreach ($productos as $producto) {
        $iva = $producto['iva'];
        $precioSinIva = $producto['ultimoCoste'] * $producto['ncant'];
        $ivaProducto = $precioSinIva * ($producto['iva'] / 100);
        $totalSinIva += $precioSinIva;
        $totalIva += $ivaProducto;
        if (!isset($basesYivas[$iva])) {
            $basesYivas[$iva] = array('base' => 0, 'iva' => 0);
        }
        $basesYivas[$iva]['base'] += $precioSinIva;
        $basesYivas[$iva]['iva'] += $ivaProducto;
    }

    $datosAlbaran = array(
        'Numalpro' => '',
        'fecha' => $fechaCierre,
        'idTienda' => $idTienda,
        'idUsuario' => $idUsuario,
        'idProveedor' => $idProveedor,
        'estado' => 'Guardado',
        'total_siniva' => $totalSinIva,
        'total' => $totalSinIva + $totalIva,
        'suNumero' => $familia_id !== null ? 'ID-' . $familia_id . '#C' : 'SINID#C',
        'formaPago' => '',
        'fechaVenci' => ''
    );

    $datosAlbaran['productos'] = json_encode($productos);
    $datosAlbaran['DatosTotales']['desglose'] = $basesYivas;
    global $BDTpv;
    include_once '../mod_compras/clases/albaranesCompras.php';
    $AlbaranesCompras = new AlbaranesCompras($BDTpv);
    $AlbaranesCompras->AddAlbaranGuardado($datosAlbaran, 0);
    error_log('La información de AlbaranesCompras es: ' . print_r($AlbaranesCompras, true));
}


function htmlFamilias($busqueda, $dedonde, $idcaja, $familias = array())
{
    // @ Objetivo:
    // Montar el hmtl para mostrar con los proveeodr si los hubiera.
    // @ parametros:
    //      $busqueda -> El valor a buscar,aunque puede venir vacio..
    //      $dedonde  -> Nos indica de donde viene. ()
    $resultado = array();
    $resultado['encontrados'] = count($familias);
    $resultado['html'] = '<label>Busqueda Familia en ' . $dedonde . '</label>'
        . '<input id="cajaBusquedafamilia" name="valorfamilia" placeholder="Buscar"'
        . 'size="13" data-obj="cajaBusquedafamilia" value="' . $busqueda
        . '" onkeydown="controlEventos(event)" type="text">';

    if (count($familias) > 10) {
        $resultado['html'] .= '<span> Se muestra 10 familias de ' . count($familias) . '</span>';
    }
    $resultado['html'] .= '<table class="table table-striped"><thead>'
        . ' <th></th> <th>Id</th><th>Nombre</th><th>Familia Padre</th><th>Beneficio</th></thead><tbody>';
    if (count($familias) > 0) {
        foreach ($familias as $key => $familia) {
            $resultado['html'] .= '<tr id="Fila_' . $key
                . '" class="FilaModal" onclick="buscarFamilia(' . "'" . $dedonde . "'" . ' , '
                . "'id_familia'" . ', ' . $familia['idFamilia'] . ', ' . "'popup'" . ');" >'
                . '<td id="C' . $key . '_Lin" >'
                . '<input id="N_'
                . $key . '" name="filafamilia" '
                . 'data-obj="idN" onkeydown="controlEventos(event)" type="image"  alt="">'
                . '<span  class="glyphicon glyphicon-plus-sign agregar"></span></td>'
                . '<td>' . htmlspecialchars($familia['idFamilia'], ENT_QUOTES) . '</td>'
                . '<td>' . htmlentities($familia['familiaNombre'], ENT_QUOTES) . '</td>'
                . '<td>' . $familia['familiaPadre'] . '</td>'
                . '<td>' . $familia['beneficiomedio'] . '</td>'
                . '</tr>';
            if ($key === 10) {
                // Solo mostramos 10 como máximo.
                break;
            }
        }
    } else {
        // No se encontro nada con esa busqueda.
        $resultado['html'] .= ' <div class="alert alert-warning">No se encontro ninguna familia, para esa busqueda</div> ';
    }
    $resultado['html'] .= '</tbody></table>';
    // Ahora generamos objetos de filas.
    // Objetos queremos controlar.
    return $resultado;
}
