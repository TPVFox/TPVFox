<?php

#include_once ("../../configuracion.php");

// Crealizamos conexion a la BD Datos
//~ include_once ("./../../mod_conexion/conexionBaseDatos.php");
//include ("./plugins/controlUser/modalUsuario.php");

class ComprobarSession {
	function recibir($BDTpv, $rootUrl) {
		// La voy utilizar para recibir session y datos formulario.
		$respuesta=array();
		//~ error_log('Entro1');
		if (!isset($_SESSION)){
			session_start();
			//~ error_log('Entro2');

		}
		$_SESSION['estado']= 'sinactivo';
		if (!isset($_SESSION['usuario'])){
			if (!isset($_POST['pwd'])){
				//~ error_log('Entro3');

				include_once($rootUrl."/plugins/controlUser/modalUsuario.php");
				$respuesta['estado'] = 'Incorrecto';
			} else {
				$this->comprobarUser($BDTpv,$_POST['usr'],$_POST['pwd']);
				if ($_SESSION['estado'] === 'incorrecto'){
					include_once($rootUrl."/plugins/controlUser/modalUsuario.php");
					$respuesta['estado'] = 'Incorrecto';
					$respuesta['error'] = 'usuario incorrecto';
				}
			}
		} else { 		
			if (isset($_SESSION['usuario'])){
				//~ $respuesta['usuario'] = $_SESSION['usuario'];
				$_SESSION['estado']= 'activo';
				$respuesta['estado'] ='Correcto';
				$respuesta['nombre'] =$_SESSION['nombre'];
				
				$datos = $this->comprobarTienda($BDTpv);
				if (!isset($datos)){
					$respuesta['estado'] = 'Incorrecto';
					$respuesta['error'] = 'Tienda activa no existe';
				} else {				
					$respuesta['idTienda'] = $datos['idTienda'];
					$respuesta['razonsocial'] = $datos['razonsocial'];
				}
			}
		}
		
		return $respuesta;
	}

	

	//comparar usuario y password con bbdd
	function comprobarUser($BDTpv,$usuario,$pwd){
		//~ echo '<br/>Entre<br/>';
		//~ echo $usuario;
		//~ echo $pwd;
		$resultado = array();
		$encriptada = md5($pwd);
		//echo $encriptada;
		$sql = 'SELECT password,nombre FROM usuarios WHERE username="'.$usuario.'"';
		$res = $BDTpv->query($sql);
		//echo $res;
		
		//compruebo error en consulta
		if (mysqli_error($BDTpv)){
			$resultado['consulta'] = $sql;
			$resultado['error'] = $BDTpv->error_list;
			return $resultado;
		} 
		$pwdBD = $res->fetch_row();
			
		if ($encriptada === $pwdBD[0]){
			$_SESSION['usuario']=$usuario;
			$_SESSION['estado']= 'activo';
			$_SESSION['nombre']= $pwdBD[1];
			
		} else {
			$_SESSION['estado']= 'incorrecto';
		}
		//~ print_r($res->fetch_row());
		return $resultado;
	 } 
	 
	 function comprobarTienda($BDTpv){
		$resultado = array();
		$sql = 'SELECT idTienda,razonsocial FROM tiendas WHERE estado="activo"';
		$res = $BDTpv->query($sql);
		//compruebo error en consulta
		if (mysqli_error($BDTpv)){
			$resultado['consulta'] = $sql;
			$resultado['error'] = $BDTpv->error_list;
			return $resultado;
		} 
		$datos = $res->fetch_row();
		
		//print_r($datos);
		$resultado['idTienda']=$datos[0];
		$resultado['razonsocial']=$datos[1];
		 		 
		return $resultado;
	 }
}
?>
