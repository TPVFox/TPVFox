<?php

#include_once ("../../configuracion.php");

// Crealizamos conexion a la BD Datos
//~ include_once ("./../../mod_conexion/conexionBaseDatos.php");
//include ("./plugins/controlUser/modalUsuario.php");

class ComprobarSession {
	function recibir($BDTpv, $rootUrl) {
		// La voy utilizar para recibir session y datos formulario.
		$respuesta=array();
		session_start();
		if (!isset($_POST['pwd'])){
			if (!isset($_SESSION['usuario'])){
				
				//include_once("./plugins/controlUser/modalUsuario.php");
				header("location:". $rootUrl . "/plugins/controlUser/modalUsuario.php");
				$respuesta['estado'] = 'Incorrecto';
			}
		} else {
			//~ echo 'estado correcto';
			//header("location:./index.php");
			//~ print_r($BDTpv);
		
		
			$respuesta['dato']= $this->comprobarUser($BDTpv,$_POST['usr'],$_POST['pwd']);
			
			// Comprobar si fue correcta contraseña
			if ($respuesta['dato'] === 'invalidopsw'){
				header("location:" . $rootUrl . "plugins/controlUser/modalUsuario.php?respuesta=invalidopsw");
				$respuesta['estado'] = 'Incorrecto';
			}
			
			$respuesta['estado'] ='Correcto';
			
		}
		return $respuesta;
	}

	function mostrarPopup(){
		
	}

	//comparar usuario y password con bbdd
	function comprobarUser($BDTpv,$usuario,$pwd){
		//~ echo '<br/>Entre<br/>';
		//~ echo $usuario;
		//~ echo $pwd;
		$resultado = array();
		$encriptada = md5($pwd);
		//echo $encriptada;
		$sql = 'SELECT password FROM usuarios WHERE username="'.$usuario.'"';
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
			session_start();
			$_SESSION['usuario']=$userBD;
			$resultado ='correcto';
			
		}else {
			$resultado = 'invalidopsw';
		}
		//~ print_r($res->fetch_row());
		return $resultado;
	 } 
}
?>
