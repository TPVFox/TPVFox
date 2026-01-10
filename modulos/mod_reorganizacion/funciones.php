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

function generarCierreAlbaran($productos)
{
    $idTienda = $_SESSION['tiendaTpv']['idTienda'];
    $ano = $_SESSION['tiendaTpv']['ano'];
    $idUsuario = $_SESSION['usuarioTpv']['id'];
    $idProveedor = 56; // Proveedor por defecto para albaranes de cierre de stock

    $fechaCierre = $ano . '-12-31 23:59:59';

    $totalSinIva = 0;
    $totalIva = 0;
    foreach ($productos as $producto) {
        $precioSinIva = $producto['ultimoCoste'] * $producto['ncant'];
        $ivaProducto = $precioSinIva * ($producto['iva'] / 100);
        $totalSinIva += $precioSinIva;
        $totalIva += $ivaProducto;
    }

    $datosAlbaran = array(
        'Numalpro' => '',
        'fecha' => $fechaCierre,
        'idTienda' => $idTienda,
        'idUsuario' => $idUsuario,
        'idProveedor' => $idProveedor,
        'estado' => 'Guardado',
        'total_siniva' => $totalSinIva,
        'total' => $totalIva,
        'suNumero' => '',
        'formaPago' => '',
        'fechaVenci' => ''
    );

    $datosAlbaran['productos'] = json_encode($productos);
    global $BDTpv;
    include_once '../mod_compras/clases/albaranesCompras.php';
    $AlbaranesCompras = new AlbaranesCompras($BDTpv);
    $AlbaranesCompras->AddAlbaranGuardado($datosAlbaran, 0);
    error_log('La información de AlbaranesCompras es: ' . print_r($AlbaranesCompras, true));
}
