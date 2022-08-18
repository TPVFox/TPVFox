<?php 
include_once './../../inicial.php';

$pulsado = $_POST['pulsado'];
include_once $URLCom.'/configuracion.php';
include_once $URLCom.'/modulos/mod_proveedor/funciones.php';
include_once $URLCom.'/modulos/mod_incidencias/clases/ClaseIncidencia.php';
include_once ($URLCom ."/modulos/mod_proveedor/clases/ClaseProveedor.php");

$CProveedor= new ClaseProveedor($BDTpv);
$CIncidencia=new ClaseIncidencia($BDTpv);
$respuesta=array();
switch ($pulsado) {
	case 'abririncidencia':
		$dedonde=$_POST['dedonde'];
		$usuario=$_POST['usuario'];
		$idReal=0;
		if(isset($_POST['idReal'])){
			$idReal=$_POST['idReal'];
		}
		
		$configuracion=$_POST['configuracion'];
		$numInicidencia=0;
		$tipo="mod_proveedor";
		$datos=array(
		'vista'=>$dedonde,
		'idReal'=>$idReal
		);
		$datos=json_encode($datos);
		$estado="No resuelto";
		$html=$CIncidencia->htmlModalIncidencia($datos, $dedonde, $configuracion, $estado, $numIncidencia);
		$respuesta['html']=$html;
		$respuesta['datos']=$datos;
	
		break;
	
		case 'nuevaIncidencia':
		$usuario= $_POST['usuario'];
		$fecha= $_POST['fecha'];
		$datos= $_POST['datos'];
	
		$dedonde="mod_proveedores";
		$estado= $_POST['estado'];
		$mensaje= $_POST['mensaje'];
		$usuarioSelect=0;
		if(isset($_POST['usuarioSelec'])){
		$usuarioSelect=$_POST['usuarioSelec'];
		}
		if($usuarioSelect>0){
			$datos=json_decode($datos);
			
			$datos->usuarioSelec=$usuarioSelect;
			$datos=json_encode($datos);
		}
		$numInicidencia=0;
		if($mensaje){
			$nuevo=$CIncidencia->addIncidencia($dedonde, $datos, $mensaje, $estado, $numInicidencia);
			$respuesta=$nuevo;
		}
	
	
	break;
	case 'imprimirResumenAlbaran':
        include_once $URLCom.'/modulos/mod_proveedor/Tareas/imprimirResumenAlbaran.php';
		$respuesta=$resultado;
	break;
    case 'imprimirListadoProductos':
       
        include_once $URLCom.'/modulos/mod_proveedor/Tareas/imprimirListadoProductos.php';
		$respuesta=$resultado;
    break;
}
echo json_encode($respuesta);
return $respuesta;
?>
