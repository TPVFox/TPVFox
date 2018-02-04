<?php
// ||-------------------------------- Obtenemos  los arrays de Xml ----------------------------------------------||	// 
//				1.- datos_tablas:  Obtenemos datos parametros y montamos array 								 		//
// 					Ejemplo estructura:																				//
//					Array(																							//
// 						tablas = array(...)	Nombres tablas implicadas en importacion y tpv.							//
//						importar = array(...)																		//
//						tpv = array(...)																			//
// 						acciones = array (...)	Campos y acciones de BDFImportar									//
// 						comprobaciones = array (...)																//
// ||------------------------------------------------------------------------------------------------------------||	//
include_once ('parametros.php');


class ClaseArrayParametrosTabla extends ClaseParametros
{
	private $nombre; // Nombre de la tabla BDImportar
	private $parametros;
	private $parametros_tabla;
	private $campos_importar;  // Array que contiene campos de las tablas importar.
	private $acciones_importar;  // Array que contiene las acciones  de cada campo de tablas importar.
	private $tablas = array(); // Array que guardo nombre tablas de importar y tpv
	private $comprobaciones = array(); // Array de guardo comprobaciones de la tabla.
	public function __construct($nombre_tabla,$fichero){
		$this->nombre = $nombre_tabla;
		parent::__construct($fichero);
		$this->parametros = $this->getRoot();
		$this->parametros_tabla = $this->TpvXMLtablaImportar();
		$parametros_tabla = $this->getParametrosTabla();
		$this->ObtenerCamposAcciones($parametros_tabla);
		// Ahora obtenemos los nombres de las tablas de tvp del nombre_tabla indicada.
		parent::setRoot($parametros_tabla); // Cambios root de clase parametros padre.
		$tablas_tpv=parent::Xpath('tpv/tabla/nombre','Valores'); 
		$this->tablas['tpv'] = parent::Xpath('tpv/tabla/nombre','Valores');// Obtengo array de tablas de tpv
		$this->comprobaciones['Mismo'] = parent::Xpath('comprobaciones//comprobacion[@nombre="Mismo"]');
		$this->comprobaciones['Similar'] = parent::Xpath('comprobaciones//comprobacion[@nombre="Similar"]');
		$this->comprobaciones['NoEncontrado'] = parent::Xpath('comprobaciones//comprobacion[@nombre="NoEncontrado"]');


		//~ echo '<pre>';
		//~ print_r($tablas_tpv);
		//~ echo '</pre>';
		// Ahora volvemos a poner como raiz parametros.
		$this->root = parent::crearXml();
		$this->raiz = 'Si';
		//~ error_log('Fin de inicializar instancia de objeto');

	}
	
	public function TpvXMLtablaImportar(){
		// @Objetivo.
		// Obtener objeto SimpleXML dentro de parametros
		$parametros = $this->parametros;
		$respuesta = array();
		foreach ($parametros->tablas as $tabla_importar){
			if (htmlentities((string)$tabla_importar->tabla->nombre) === $this->nombre){
				$respuesta = $tabla_importar->tabla;
			}
		}
		
		//~ echo  $this->parametros;
		
		return $respuesta;
	}

	public function ObtenerCamposAcciones($p){
		$respuesta = array();
		foreach ($p->campos->children() as $campo){;
			$n =(string) $campo['nombre'];
			if (isset($campo->tipo)){
				if ((string) $campo->tipo === 'Unico'){
					$respuesta['importar']['campos'][]=$n;
				}
			}
			// Creamos array campos que utilizamos para BuscarIgualSimilar
			$respuesta['acciones'][$n] = $this->CamposAccionesImportar($campo); // Los campos (BDImport) y las acciones de la tabla
		}
		$this->campos_importar = $respuesta['importar'];
		$this->acciones_importar = $respuesta['acciones'];			
	}
	
	public function CamposAccionesImportar($campo){
		$campos = array();
		$x = 0;
		foreach ($campo->action as $action) {
				// obtenemos las acciones para encontrar
				$campos['acciones_buscar'][$x]['funcion'] = (string) $action['funcion'];
				$campos['acciones_buscar'][$x]['tabla_cruce'] =(string) $action['tabla_cruce'];
				$campos['acciones_buscar'][$x]['campo_cruce'] =(string) $action['campo_cruce'];
				$campos['acciones_buscar'][$x]['description'] =(string) $action['description'];
				$x++;
			}
		return $campos;
	}
	
	
	
	
	public function getNombre(){
		return $this->nombre;
	}
	public function getParametros(){
		return $this->parametros;
	}
	public function getParametrosTabla(){
		//~ $this->parametros_tabla = $this->TpvXMLtablaImportar();
		return $this->parametros_tabla;
	}
	public function getCamposImportar(){
		return $this->campos_importar;
	}
	public function getAccionesImportar(){
		return $this->acciones_importar;
	}
	public function getTablas($elemento =''){
		if ($elemento === ''){
			return $this->tablas;
		} else {		
			return $this->tablas[$elemento];
		}
	}
	public function getComprobaciones($elemento =''){
		if ($elemento === ''){
			return $this->comprobaciones;
		} else {		
			return $this->comprobaciones[$elemento];
		}
	}
}

?>
