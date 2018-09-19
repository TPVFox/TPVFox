<?php 
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_balanza/clases/ClaseBalanza.php';
$CBalanza=new ClaseBalanza($BDTpv);
$pulsado = $_POST['pulsado'];
$respuesta=array();
switch ($pulsado) {
    case 'addBalanza':
        $datos=array(
            'nombreBalanza'=>$_POST['nombreBalanza'],
            'modeloBalanza'=>$_POST['modeloBalanza'],
            'teclas'        =>$_POST['teclas']
        );
        $addBalanza=$CBalanza->addBalanza($datos);
        $respuesta['balanza']=$addBalanza;
    break;
}
echo json_encode($respuesta);
?>
