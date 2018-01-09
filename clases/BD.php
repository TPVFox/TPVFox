<?php
class BD{
	private $server;
	private $base;
	private $usuario;
	private $contraseña;
	
	
	public static function conectar($servidorMysql, $nombrebdMysql, $usuarioMysq, $passwordMysql){
		try{

			$db= new mysqli($servidorMysql, $usuarioMysql,$passwordMysql, $nombrebdMysql)  ;
			return $db;
		}  catch (PDOException $e){
			echo "ERROR: No puedes conectarte a la base de datos";
	
		}
	
	}
	
	public static function cargar($servidorMysql, $nombrebdMysql, $usuarioMysq, $passwordMysql){
		$result=array($servidorMysql, $nombrebdMysql, $usuarioMysq, $passwordMysql);
		return $result;
	}
}
