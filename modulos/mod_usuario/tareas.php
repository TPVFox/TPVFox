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
		$id_array = array('id' => $usuarioNuevo);

        $permisosUsuario=$ClasePermisos->getPermisosUsuario($id_array);
        $respuesta['permisosUsuario']=$permisosUsuario;
    break;
	case 'accionesMultiplesUsuarios':
		include_once $URLCom.'/modulos/mod_usuario/tareas/AccionesMultiplesUsuarios.php';
	break;
	case 'cambiarAnoUsuarios':
		$html = '';
		if (isset($_POST['idsSeleccionados'])){
		    $idsUsuarios = $_POST['idsSeleccionados'];
		} else {
		    $idsUsuarios = array();
		}
		$html = htmlCambiarAnoUsuarios($idsUsuarios);
		$respuesta['html']=$html;
	break;
	case 'confirmarCambiarAnoUsuarios':
		$idsUsuarios = $_POST['idsSeleccionados'];
		$CUsuarios = new ClaseUsuarios();
		$nuevaContraseña = $_POST['nuevaContrasena'];
		$respuesta['inactivarUsuarios'] = $CUsuarios->inactivarRestoUsuarios($idsUsuarios);
		$respuesta['cambiarContraseñaUsuarios'] = $CUsuarios->cambiarContraseñaUsuarios($idsUsuarios, $nuevaContraseña);
		$respuesta['activarUsuarios'] = $CUsuarios->activarUsuarios($idsUsuarios);
		if ($respuesta['inactivarUsuarios']['error'] == '0' && $respuesta['cambiarContraseñaUsuarios']['error'] == '0' && $respuesta['activarUsuarios']['error'] == '0') {
		    $respuesta['mensaje'] = 'Operación realizada con éxito.';
		} else {
		    $respuesta['mensaje'] = 'Se han producido errores en la operación.';
		}
	break;
	case 'inactivarUsuarios':
		$html = '';
		if (isset($_POST['idsSeleccionados'])){
		    $idsUsuarios = $_POST['idsSeleccionados'];
		} else {
		    $idsUsuarios = array();
		}
		$html = htmlInactivarUsuarios($idsUsuarios);
		$respuesta['html']=$html;
	break;
	case 'confirmarInactivarUsuarios':
		$idsUsuarios = $_POST['idsSeleccionados'];
		$CUsuarios = new ClaseUsuarios();
		$respuesta['inactivarUsuarios'] = $CUsuarios->inactivarUsuarios($idsUsuarios);
		if ($respuesta['inactivarUsuarios']['error'] == '0') {
		    $respuesta['mensaje'] = 'Operación realizada con éxito.';
		} else {
		    $respuesta['mensaje'] = 'Se han producido errores en la operación.';
		}
	break;
}
echo json_encode($respuesta);
return $respuesta;
?>
