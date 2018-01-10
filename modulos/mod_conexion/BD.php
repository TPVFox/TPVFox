<?php
class BD{
	private $server;
	private $base;
	private $usuario;
	private $contrasena;
	
	
	public static function conectar(){
		try{
			$db= new mysqli($server, $usuario,$contrasena, $base)  ;
			return $db;
		}  catch (PDOException $e){
			echo "ERROR: No puedes conectarte a la base de datos";
	
		}
	
	}
	
	public static function cargar(){
		include_once('router.php')
		$this->$server =$servidorMysql
		$this->$base = $nombrebdMysql
		$this->$usuario = $usuarioMysq
		$this->$contrasena = $passwordMysql;
		return;
	}
}
