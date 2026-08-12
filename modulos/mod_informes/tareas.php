<?php
include_once './../../inicial.php';
require_once $URLCom . '/clases/auth_guard.php';
tpvfox_require_auth(); // Cierra el bypass: exige sesion valida en el endpoint AJAX.

$pulsado = $_POST['pulsado'];
include_once $URLCom . '/configuracion.php';



$respuesta = array();
switch ($pulsado) {
        case 'obtenerLoading':
                $html = '<img src="' . $HostNombre . '/css/img/loading.gif" alt="Esperando">';
                $respuesta['html'] = $html;
}
echo json_encode($respuesta);
return $respuesta;
