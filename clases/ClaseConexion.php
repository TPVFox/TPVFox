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
        $db= new mysqli($this->server, $this->usuario,$this->contrasena, $this->base);
        if ($db->connect_error) {
            die('Error de Conexión (' . $mysqli->connect_errno . ') '
            . $mysqli->connect_error);
        } else {
            return $db;

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

    public function getRutaUpload(){
        include ($this->ruta_proyecto.'/configuracion.php');
        return $ruta_upload;
    }

    public function getRutaSegura(){
        include ($this->ruta_proyecto.'/configuracion.php');
        return $ruta_segura;
    }

    public function getNombreFichero($ruta){
        //@ Objetivo
        // Obtener de string del el ultimo / y le quitamos extension.
        $array = explode("/", $ruta);
        $i = count($array);
        $nombre_extension = $array[$i-1];
        $desglose_nombre = explode(".",$nombre_extension);
        return $desglose_nombre[0];
    }
}
 

?>
