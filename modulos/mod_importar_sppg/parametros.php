<?php
// https://diego.com.es/tutorial-de-simplexml

class ClaseParametros 
{
	private $ficheros; // array de ficheros, con rutaruta fichero de parametros del modulo
	private $root; // Contiene el objeto XMl de  los ficheros enviados.
	private $Array_Elementos;  // 
	
	public function __construct($fichero){
		$this->ficheros = array($fichero);
		$this->root = $this->crearXml();
	}
	
	private function crearXml(){
		foreach ($this->ficheros as $fichero) {
			$respuesta=	simplexml_load_file($fichero);
			if (empty($respuesta)){
				echo ' Error en carga de fichero';
				exit;
			}
		}
		return $respuesta;
	}
	
	public function ArrayElementos($elementos,$atributo=''){
		// @ Objetivo.
		// Obtener objeto SimpleXML dentro de parametros que contenga ese elemento.
		// @ Parametros:
		// 		$elementos -> string del nombre del grupo de elementos queremos obtener
		// 		$atributo -> string del atributo del elemento queremos si lo 
		// @ Devolvemos 
		//  Array con objetos.
		$respuesta = array();
		$obj = $elementos;
		
		foreach ($this->root->$obj as $elemento){
			if ($atributo ===''){
				// Si no mandamos atributo.
				$respuesta[] = $elemento;
			} else {
				// Si mandamos atributo entonces solo metemos los que tiene ese atributo.
				if (isset($elemento[$atributo])){
					$respuesta[] = $elemento;
				}
			}
		}
		$this->Array_Elementos = $respuesta;
		return $respuesta;
	}
	
	public function Xpath($elementos){
		// Mas info en : http://php.net/manual/es/simplexmlelement.xpath.php
		// @ Objetivo.
		// Obtener objeto SimpleXML dentro de parametros que contenga ese elemento.
		// @ Parametros:
		// 		$elementos -> string del nombre del grupo de elementos queremos obtener, podemos en raizar 
		//						ejemplo :  padre/hijo/nieto
		
		$respuesta = array();
		$respuesta = $this->root->xpath($elementos);
		
		
		return $respuesta;
	}



	public function getfichero(){
		
		return $this->ficheros;
	}
	
	public function getRoot(){
		
		return $this->root;
	}
}
?>
