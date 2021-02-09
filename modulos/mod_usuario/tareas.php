<?php 
include_once ("./../../inicial.php");
include_once ("./../../configuracion.php");
// Incluimos funciones
include_once $URLCom.'/modulos/mod_usuario/funciones.php';
include_once $URLCom.'/modulos/mod_usuario/clases/claseUsuarios.php';

$Cusuario=new ClaseUsuarios($BDTpv);
// Obtenemos funcion que nos envia... 

$pulsado = $_POST['pulsado'];

 switch ($pulsado) {

	case 'CopiarDescripcion':
	
	if (isset($_POST['id'])){
		$id = $_POST['id'];
		$DatosRefCruzadas= $_POST['DatosRefCruzadas'];
	}
	
	$respuesta = CopiarDescripcion($id,$DatosRefCruzadas,$prefijoJoomla,$BDWebJoomla);
	header("Content-Type: application/json;charset=utf-8");
	
	break;
	case 'eliminarConfigModulo':
		$idUsuario=$_POST['idUsuario'];
		$modulo=$_POST['modulo'];
		$eliminar=$Cusuario->eliminarConfiguracionUsuario($idUsuario, $modulo);
		if($eliminar['error']!='0'){
			$respuesta['error']=$eliminar['error'];
			$respuesta['consulta']=$eliminar['consulta'];
		}else{
			$respuesta=array();
		}
	break;
    case 'copiarPermisosUsuario':
        $usuarioNuevo=$_POST['usuarioNuevo'];
       
        $permisosUsuario=$ClasePermisos->getPermisosUsuario($usuarioNuevo);
        $respuesta['permisosUsuario']=$permisosUsuario;
    break;
}
echo json_encode($respuesta);
return $respuesta;
?>
