<?php
// https://diego.com.es/tutorial-de-simplexml

class ClaseParametros 
{
	private $ficheros; // array de ficheros, con rutaruta fichero de parametros del modulo
	private $root; // Por defecto contiene el objeto XMl de  los ficheros enviados, aunque podríamos mandarle uno.
				   // Esto implica que cambiaría el valor de raiz = No
	private $raiz = 'Si';
	private $Array_Elementos;  // 
	
	public function __construct($fichero){
		$this->ficheros = array($fichero);
		$this->root = $this->crearXml();
		// Ahora cargamos los ficheros que ponemos en include.
		$includes = $this->Xpath('includes/fichero','Valores');
		if (count($includes)>0){
			// Quiere decir que encontro includes.
			// Ejecutamos de nuevos crearXml pero añadiendo esos ficheros.
			foreach ($includes as $num_ficher =>$Nuevo_fichero){
				//~ error_log(' Anhadimos a parametros el fichero '.$fichero. ' tipo '.gettype($Nuevo_fichero));
				$this->root = $this->crearXml($Nuevo_fichero);

			}
		}
		
		error_log('Fin de inicializar instancia de objeto');

	}
	
	public function crearXml($Nuevo_fichero=''){
		// Comprobamos si tenemos todos los ficheros que ponemos en elemento include.
		
		if ($Nuevo_fichero !== ''){
			if (!in_array($Nuevo_fichero,$this->ficheros)){
				// Quiere decir que se envio fichero
				$this->ficheros[] =$Nuevo_fichero;
			}
		} 
		$objetoXml = array();
		// Informacion encontrada en https://stackoverflow.com/questions/3418019/simplexml-append-one-tree-to-another
		$objetoDom = array(); // Necesito crear un DOM por cada fichero para añadir al principal.
		
		foreach ($this->ficheros as  $Num_fichero =>$fichero) {
			// Ahora cremos objXML de fichero
			//~ error_log('Agremamos objeto de fichero '.$fichero);
			$objetoXml[$Num_fichero]= simplexml_load_file($fichero);
			$objetoDOM[$Num_fichero]= dom_import_simplexml($objetoXml[$Num_fichero]);
			if (empty($objetoXml[$Num_fichero])){
				echo ' Error en carga de fichero '.$fichero;
				exit;
			}
		}
		if (count($objetoXml) >1 ){
			// Quiere decir que hay mas de un fichero por lo que añadimos datos al primero objeto que siempre es parametros.
			foreach ($this->ficheros as $Num_fichero =>$fichero){
				
				if ($Num_fichero > 0){
					// Ahora debería añadir el fichero.
					$domXml = $objetoDOM[0]->ownerDocument->importNode($objetoDOM[$Num_fichero], TRUE);
					$objetoDOM[0]->appendChild($domXml);

					//~ error_log('Acabamos de añadir XML de parametros el fichero '.$fichero);
				}
			}
		} 
		$respuesta = $objetoXml[0]; 
		
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
	
	public function Xpath($elementos,$tipo_respuesta=''){
		// Mas info en : http://php.net/manual/es/simplexmlelement.xpath.php
		// @ Objetivo.
		// Obtener objeto SimpleXML dentro de parametros que contenga ese elemento.
		// @ Parametros:
		// 		$elementos -> string del nombre del grupo de elementos queremos obtener, podemos en raizar 
		//						ejemplo :  padre/hijo/nieto
		// 		$tipo_respuesta-> string 
		//				$tipo_respuesta = 'Objetos'; Es el valor por defecto si no lo recibe.
		//											Devuelve array objetos de XML del elemento indicados
		//				$tipo_respuesta = 'Valores'; Devuelve array de valores de un elemento
		$respuesta = array();
		$respuesta = $this->root->xpath($elementos);
		if ($tipo_respuesta === '' || $tipo_respuesta ==='Objetos'){
			// Devolvemos objeto de elementos indicado
			return $respuesta;
		} else {
			// Devolvemos valor de elementos indicados.
			if ($tipo_respuesta === 'Valores'){
			return $this->Valor($respuesta);
			} else {
				echo ' Error tipo_respuesta a devolver';
				exit;
			}
		}
	}
	public function Valor($elementos){
		// @ Objetivo
		// Obtener array de valores de unos elementos.
		// @ Paramentros:
		//   $elementos -> array de objetos
		$respuesta = array();
		foreach ($elementos as $elemento){ 
			$respuesta[] = trim((string) $elemento);
		}
		return $respuesta;
	}
	
	public function getFicheros(){
		return $this->ficheros;
	}
	
	public function getRoot(){
		
		return $this->root;
	}
	
	public function setRoot($Nuevo_XML){
		// La utilizo para cambiar objeto XML que tengo por defecto y indico que no esta en raiz.
		$this->root = $Nuevo_XML;
		$this->raiz = 'No';
	}
	public function comprobarIncludeFicheros($ficherosInclude){
		
	}
}
?>
