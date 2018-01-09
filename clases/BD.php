<?php
class BD{
	//~ public $dbserver = 'mysql:host=localhost';
	//~ public $dbname='mydblocal';
	//~ public $nombreusuario='root';
	//~ public $contraseña='renaido';
	public static function conectar(){
		try{
			$db= new mysqli("localhost", "usertpv","prueba", "tpv")  ;
			//~ $db= new PDO('mysql:dbname=tpv;host=localhost', 'usertpv', 'prueba')  ;
		//~ $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);*/
			//~ $acentos = $db->query("SET NAMES 'utf8'");
			return $db;
		}  catch (PDOException $e){
			
			echo "ERROR: No puedes conectarte a la base de datos";
	
		}
	
	}
}
