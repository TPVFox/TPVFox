<?php 
/*  Objetivo de esta clase es poder leer que plugin hay 
 *  y donde se van aplicar.
 *  
 * */

class ClasePlugins {
	 public $ruta = __DIR__ ;
	 public $dir; // Directorios y ficheros.
	 public function __construct($ruta=''){
		if ($ruta === ''){
			$ruta = $this->ruta;
		}
		$this->dir = $this->ObtenerDir($ruta);
	}
	public function ObtenerDir($ruta){
		// Objetivo scanear directorio y cuales son directorios
		$respuesta = array();
		$scans = scandir($ruta);
		foreach ( $scans as $scan){
			$ruta_completa = $ruta.'/'.$scan;
			if (filetype($ruta_completa) === 'dir'){
			 $respuesta[] =$scan;
			}
		}
		return $respuesta;
	
	}
	
	
	public function GetDir(){
		// Objetivo devolver los directorios.
		return $this->dir;
	}






}
