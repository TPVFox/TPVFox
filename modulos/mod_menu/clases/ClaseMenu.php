<?php 

class ClaseMenu {
    public $items = array() ; // (array) Un array de arrays con los items de menu

    function __construct(){
        $this->obtenerRutaProyecto();
        $this->items = simplexml_load_file($this->RutaServidor.$this->HostNombre.'/modulos/mod_menu/parametrosMenu.xml');
    }
    
    public function obtenerRutaProyecto(){
		// Objectivo
		// Obtener rutas del servidor y del proyecto.
		$this->ruta 			=  __DIR__; // Sabemos el directorio donde esta fichero plugins
		$this->RutaServidor 	= $_SERVER['DOCUMENT_ROOT']; // Sabemos donde esta el servidor.
		$RutaProyectoCompleta 	= str_replace('modulos/mod_menu/clases','', __DIR__);
		$this->HostNombre		= str_replace($this->RutaServidor,'',$RutaProyectoCompleta);
		
	}
    
}
