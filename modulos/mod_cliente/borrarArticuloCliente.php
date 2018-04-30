<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */


include_once './../../inicial.php';

require_once 'claseTarifaCliente.php';

$idarticulo = $_POST['idarticulo'];
$idcliente = $_POST['idcliente'];

// validar datos
//if( datos validados y ok)
$resultado = [];
$tarifaCliente = new TarifaCliente($BDTpv);
$existetarifa = $tarifaCliente->existeArticulo($idcliente, $idarticulo);

if ($existetarifa) {
    $resultado = $tarifaCliente->update($idcliente, $idarticulo, [
        'fechaActualizacion' => '"'. date(FORMATO_FECHA_MYSQL).'"',
        'estado'=>'2'
    ]);
}

echo json_encode($resultado);


