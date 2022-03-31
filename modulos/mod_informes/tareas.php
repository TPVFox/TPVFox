<?php 
include_once './../../inicial.php';

$pulsado = $_POST['pulsado'];
include_once $URLCom.'/configuracion.php';



$respuesta=array();
switch ($pulsado) {
	case 'obtenerLoading':
        $html = '<img src="'.$HostNombre.'/css/img/loading.gif" alt="Esperando">';
        $respuesta['html'] = $html;
}
echo json_encode($respuesta);
return $respuesta;
?>
