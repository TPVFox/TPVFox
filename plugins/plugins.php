<?php 
/*  Objetivo de esta clase es poder leer que plugin hay 
 *  y donde se van aplicar.
 *  
 * */
$RutaProyectoCompleta 	= str_replace('plugins','', __DIR__); // Obtenermos la ruta del proyecto
include ($RutaProyectoCompleta.'/controllers/parametros.php'); 
class ClasePlugins{
	 public $ruta 					= '' ;		// (string) ruta completa a plugins
	 public $RutaServidor 			= '' ;		// (string) ruta del servidor
	 public $HostNombre				= '' ;		// (string) ruta desde el servidor al proyecto.
	 public $parametros_plugins		= array(); 	// (array) de varios objetos
	 
	public function __construct($modulo=''){
		// Obtenemos la ruta del proyecto
		$this->obtenerRutaProyecto();
		// Obtenermos los directorios ( plugins de ese modulo);
		if ($modulo === ''){
			$ruta = $this->ruta;
		} else {
			$ruta = $this->ruta.'/'.$modulo;
		}
		$this->parametros_plugins = $this->ObtenerPlugins($ruta);
	}
	public function ObtenerPlugins($ruta){
		$respuesta = array();
		$plugins = $this->ObtenerDir($ruta);
		$contador = 0;
		foreach ($plugins as $plugin){
			// Recorremos los directorios de la ruta indicada.
			$fichero_parametro = $ruta.'/'.$plugin.'/parametros.xml';
			// Instanciamos el fichero
			$parametros = new ClaseParametros($fichero_parametro);
			$respuesta[$contador]['datos_generales'] =$parametros->ArrayElementos('datos_generales'); 
			$contador++;
			
		} 
		return $respuesta;
		
	}
	
	public function ObtenerDir($ruta){
		// Objetivo scanear directorio y cuales son directorios
		$respuesta = array();
		$scans = scandir($ruta);
		foreach ( $scans as $scan){
			$ruta_completa = $ruta.'/'.$scan;
			if (filetype($ruta_completa) === 'dir'){
				if (($scan === '.') || ($scan === '..')){ 
					// Descartamos los directorios . y ..
				} else {	
					$respuesta[] =$scan;
				}
			}
		}
		return $respuesta;
	}
	
	
	public function GetParametrosPlugins(){
		// Objetivo devolver los directorios.
		return $this->parametros_plugins;
	}

	public function obtenerRutaProyecto(){
		// Objectivo
		// Obtener rutas del servidor y del proyecto.
		$this->ruta 			=  __DIR__; // Sabemos el directorio donde esta fichero plugins
		$this->RutaServidor 	= $_SERVER['DOCUMENT_ROOT']; // Sabemos donde esta el servidor.
		$RutaProyectoCompleta 	= str_replace('plugins','', __DIR__);
		$this->HostNombre		= str_replace($this->RutaServidor,'',$RutaProyectoCompleta);
		
	}




}
