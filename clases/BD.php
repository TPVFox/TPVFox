<?php
class BD{
	private $server;
	private $base;
	private $usuario;
	private $contraseña;
	
	
	public static function conectar(){
		try{
			$db= new mysqli("localhost", "usertpv","prueba", "tpv")  ;
			//~ $db= new PDO('mysql:dbname=;host=', '', '')  ;
		//~ $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);*/
			//~ $acentos = $db->query("SET NAMES 'utf8'");
			return $db;
		}  catch (PDOException $e){
			
			echo "ERROR: No puedes conectarte a la base de datos";
	
		}
	
	}
}
