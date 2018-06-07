<?php 

$pulsado = $_POST['pulsado'];
include_once 'clases/ClaseIva.php';
include_once 'clases/ClaseFormasPago.php';
include_once 'clases/ClaseVencimiento.php';
include_once 'funciones.php';
include_once ("./../../inicial.php");
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
		$dedonde=$_POST['dedonde'];
		switch($dedonde){
			case 'iva':
				$iva=new ClaseIva($BDTpv);
				$modificar=$iva->modificarTabla($datos);
			break;
			case 'forma':
				$formas=new ClaseFormasPago($BDTpv);
				$modificar=$formas->modificarTabla($datos);
			break;
			case 'vencimiento':
				$Vencimiento=new ClaseVencimiento($BDTpv);
				$modificar=$Vencimiento->modificarTabla($datos);
			break;
		}
		$respuesta=$modificar;
	break;
	
	
	
}

echo json_encode($respuesta);
return $respuesta;
?>
