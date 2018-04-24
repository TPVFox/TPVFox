<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once './../../inicial.php';

require_once './../../lib/btemplate/bTemplate.php';

include_once './../../clases/cliente.php';

require_once 'claseTarifaCliente.php';

$idcliente = $_GET['id'];

$cliente = new Cliente($BDTpv);
$uncliente = $cliente->DatosClientePorId($idcliente);

$tarifaCliente = (new TarifaCliente($BDTpv))->leer($uncliente['idClientes']);

if (isset($tarifaCliente['error'])) {
    // hay un error en la consulta
    // ¿ que hacemos?
    echo $tarifaCliente['error'] . '<--<br>-->' . $tarifaCliente['consulta'];
} else {
    $datos = $tarifaCliente['datos'];
    var_dump($datos);
// Mostramos formulario si no tiene acceso.
// Bloqueamos si 	
    if ($_SESSION['estadoTpv'] != "Correcto") {
        // Mostramos modal de usuario.
        include_once ($URLCom . "/plugins/controlUser/modalUsuario.php");
    }
    $tpl = new bTemplate();
    $tpl->set('HostNombre', $HostNombre);
    $tpl->set('cliente', ['id' => $uncliente['idClientes'], 'nombre' => $uncliente['Nombre']]);
    $tpl->set('tarifa', $datos);
    echo $tpl->fetch('./templates/tarifacliente.tpl');
}