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
        $html="";
        $addBalanza=$CBalanza->addBalanza($datos);
        if(isset($addBalanza['error'])){
            $html='<div class="alert alert-danger">
                <strong>Danger!</strong> Error de sql: '.$addBalanza['consulta'].'.
                </div>';
        }else{
            $html='<div class="alert alert-success">
                  <strong>Success!</strong> Balanza registrada.
                </div>';
        }
        $respuesta['html']=$html;
        $respuesta['balanza']=$addBalanza;
    break;
}
echo json_encode($respuesta);
?>
