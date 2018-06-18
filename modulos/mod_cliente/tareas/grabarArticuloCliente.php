<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

require_once $URLCom.'/modulos/mod_clientes/clases/claseTarifaCliente.php';

$idarticulo = $_POST['idarticulo'];
$pvpSiva = $_POST['pvpSiva'];
$pvpCiva = $_POST['pvpCiva'];
$idcliente = $_POST['idcliente'];

// validar datos
//if( datos validados y ok)
$resultado = [];
$tarifaCliente = new TarifaCliente($BDTpv);
$existetarifa = $tarifaCliente->existeArticulo($idcliente, $idarticulo);

if ($existetarifa) {
    $resultado = $tarifaCliente->update([
        'estado' => '1'
        , 'pvpSiva' => $pvpSiva
        , 'pvpCiva' => $pvpCiva
        , 'fechaActualizacion' => '"'. date(FORMATO_FECHA_MYSQL).'"',
        'estado'=>K_TARIFACLIENTE_ESTADO_ACTIVO
    ], ['idArticulo= ' . $idarticulo, 'idClientes= ' . $idcliente]);
} else {
    $resultado = $tarifaCliente->insert( [
        'idArticulo' => $idarticulo,
        'idClientes' => $idcliente,
        'pvpSiva' => $pvpSiva,
        'pvpCiva' => $pvpCiva,
        'fechaActualizacion' => '"'. date(FORMATO_FECHA_MYSQL).'"',
        'estado' => K_TARIFACLIENTE_ESTADO_ACTIVO
    ]);
}


