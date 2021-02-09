<?php 
include_once './../../inicial.php';
$pulsado = $_POST['pulsado'];
include_once $URLCom.'/modulos/mod_configuracion/clases/ClaseIva.php';
include_once $URLCom.'/modulos/mod_configuracion/clases/ClaseFormasPago.php';
include_once $URLCom.'/modulos/mod_configuracion/clases/ClaseVencimiento.php';
include_once $URLCom.'/modulos/mod_configuracion/funciones.php';
switch ($pulsado) {
	case 'abrirModalModificar':
		$html=abrirModal($_POST['id'], $_POST['dedonde'], $BDTpv);
		$respuesta=$html;
	break;
	case 'ModificarTabla':
		$datos=array();
		$datos['id']=$_POST['id'];
		$datos['descripcion']=$_POST['descripcion'];
		$datos['iva']=$_POST['iva'];
		$datos['recargo']=$_POST['recargo'];
		if(isset($_POST['dias'])){
			$datos['dias']=$_POST['dias'];
		}else{
			$datos['dias']=0;
		}
		
		$dedonde=$_POST['dedonde'];
		switch($dedonde){
			case 'iva':
				$iva=new ClaseIva($BDTpv);
				if($_POST['id']>0){
					$modificar=$iva->modificarTabla($datos);
				}else{
					$modificar=$iva->insertarRegistro($datos);
				}
			break;
			case 'forma':
				$formas=new ClaseFormasPago($BDTpv);
				if($_POST['id']>0){
					$modificar=$formas->modificarTabla($datos);
				}else{
					$modificar=$formas->insertarRegistro($datos);
				}
			break;
			case 'vencimiento':
				$Vencimiento=new ClaseVencimiento($BDTpv);
				if($_POST['id']>0){
					$modificar=$Vencimiento->modificarTabla($datos);
				}else{
					$modificar=$Vencimiento->insertarRegistro($datos);
				}
			break;
		}
		$respuesta=$modificar;
	break;
	
	
	
}

echo json_encode($respuesta);
return $respuesta;
?>
