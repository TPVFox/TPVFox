<?php 
/*  Objetivo de esta clase es poder leer que plugin hay 
 *  y donde se van aplicar.
 *  
 * */
$RutaProyectoCompleta 	= str_replace('plugins','', __DIR__); // Obtenermos la ruta del proyecto
include ($RutaProyectoCompleta.'/controllers/parametros.php'); 
class ClasePlugins{
	 public $dedonde;							// (String) ruta desde servidor del fichero que lo llama , no la clase, el fichero.
	 public $ruta 					= '' ;		// (string) ruta completa a plugins
	 public $RutaServidor 			= '' ;		// (string) ruta del servidor
	 public $HostNombre				= '' ;		// (string) ruta desde el servidor al proyecto.
	 public $parametros_plugins		= array(); 	// (array) de varios objetos

	public function __construct($modulo,$dedonde){
		// Obtenemos la ruta del proyecto
		$this->obtenerRutaProyecto();
		$this->dedonde = $dedonde;
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
			$ruta_plugin = $ruta.'/'.$plugin;
			$fichero_parametro = $ruta_plugin.'/parametros.xml';
			// Instanciamos el fichero
			$parametros = new ClaseParametros($fichero_parametro);
			// Ahora añadimos la ruta del plugin.
			$p = $parametros->getRoot();
			$p->datos_generales->addChild("ruta",$ruta_plugin);
			// Ahora obtenemos array con los datos de 
			$respuesta[$contador]['datos_generales'] =$parametros->ArrayElementos('datos_generales'); 
			// Ahora creamos la clase para cada plugin.
			$ruta_clase = $ruta_plugin.'/'.$respuesta[$contador]['datos_generales']['nombre_fichero_clase'].'.php';
			include_once ($ruta_clase);
           
			$nombre_clase = 'Plugin'.$respuesta[$contador]['datos_generales']['nombre_fichero_clase'];
            
			$clase = new $nombre_clase($this->dedonde);
			$respuesta[$contador]['clase']= $clase;
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
