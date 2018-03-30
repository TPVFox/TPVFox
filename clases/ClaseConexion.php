<?php
/* Propiedades en minuscula.
 * Metodos en UpperCamelCase
 * */
class ClaseConexion{
	public $ruta_proyecto; //(String) Ruta del proyecto.
	public $conexion ; // (object) Con la conexion...
	private $server; 
	private $base;
	private $usuario;
	private $contrasena;
	
	
	public function __construct()
	{
		$ruta_clase = __FILE__;
		// Quitamos los /clases/ClaseConexion.php ( 26 caracteres),
		$ruta_proyecto = substr($ruta_clase,0,-25);
		$this->ruta_proyecto = $ruta_proyecto;
		$this->cargarConfiguracion();
		$this->conexion = $this->conectar();	
	}
	
	public function getConexion(){
		return $this->conexion;
	}
	
	public function conectar(){
		
		try{
			$db= new mysqli($this->server, $this->usuario,$this->contrasena, $this->base);
			return $db;
		}  catch (PDOException $e){
			echo "ERROR: No puedes conectarte a la base de datos";
	
		}
	
	}
	
	public function cargarConfiguracion(){
		include ($this->ruta_proyecto.'/configuracion.php');
		$this->server =$servidorMysql;
		$this->base = $nombrebdMysql;
		$this->usuario = $usuarioMysql;
		$this->contrasena = $passwordMysql;
		return;
	}
}
 

?>
