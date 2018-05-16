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
include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');


class ClaseArrayParametrosTabla extends ClaseParametros
{
	private $nombre; // Nombre de la tabla BDImportar
	private $parametros;
	private $parametros_tabla;
	private $campos_importar;  // Array que contiene campos de las tablas importar.
	private $acciones_importar;  // Array que contiene las acciones  de cada campo de tablas importar.
	private $campos_tpv = array(); // Array de campos unicos de las tablas tpv
	private $tablas = array(); // Array que guardo nombre tablas de importar y tpv
	private $comprobaciones = array(); // Array de guardo comprobaciones de la tabla.
	private $consultas;
	private $after= array() ; // Array guardamos funciones que ejecutamos despues de elemento array
	public function __construct($nombre_tabla,$fichero){
		$this->nombre = $nombre_tabla;
		parent::__construct($fichero);
		$this->parametros = $this->getRoot();
		$this->parametros_tabla = $this->TpvXMLtablaImportar();
		$parametros_tabla = $this->getParametrosTabla();
		// Crea metodos para crear las propiedades campos_importar, acciones_importar, campos_tpv y acciones tpv
		$this->ObtenerCamposAcciones($parametros_tabla); 
		// Ahora obtenemos los datos clasificados.
		parent::setRoot($parametros_tabla); // Cambios root de clase parametros padre.
		$this->tablas['importar']			= parent::Xpath('nombre','Valores');
		$this->tablas['tpv'] 				= parent::Xpath('tpv/tabla/nombre','Valores');// Obtengo array de tablas de tpv
		// Obtenemos las propiedades de campos_tpv para cada tabla tpv.
		if (count($this->tablas['tpv'])>0){
			foreach ($this->tablas['tpv'] as $t){
				$this->ObtenerCamposTpv($t);
				
			}
		}
		// Obtenemos mas propiedades...
		$this->comprobaciones['Mismo'] 		= parent::Xpath('comprobaciones//comprobacion[@nombre="Mismo"]');
		$this->comprobaciones['Similar'] 	= parent::Xpath('comprobaciones//comprobacion[@nombre="Similar"]');
		$this->comprobaciones['NoEncontrado'] = parent::Xpath('comprobaciones//comprobacion[@nombre="NoEncontrado"]');
		$this->consultas['Obtener'] 		= parent::Xpath('consultas//campos[@tipo="obtener"]','Valores');
		$this->consultas['anhadir'] 		= $this->ObtenerConsultasAnhadir();
		// Ahora obtenemos si hay funciones despues de obtener y insert.
		$this->before				 		= $this->ObtenerBefore();
		
		
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
		
		return $respuesta;
	}

	public function ObtenerCamposAcciones($p){
		// Objetivo: 
		// Obtenemos propiedades campos_importar y acciones_importar
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
	
	
	public function ObtenerCamposTpv($t){
		//Objetivo:
		//Obtener propiedade campos_tpv para tabla t ( ya que en tpv puede haber varias tablas
		$parametros = $this->getParametrosTabla();
		$respuesta = array();
		$x = 'tpv//tabla[nombre="'.$t.'"]';
		$XmlTpvTabla = parent::Xpath($x);
		foreach ($XmlTpvTabla[0]->cruces as $campos){;
			foreach ( $campos as $campo){
				$n =(string) $campo['nombre'];
				if (isset($campo->tipo)){
					if ((string) $campo->tipo === 'Unico'){
						$respuesta[$t][]=$n;
					}
				}
			}
		}
		// Añadimos los campos unicos de la tabla tpv a la propiedad campos_tpv.
		array_push($this->campos_tpv,$respuesta);
	}
	
	
	
	public function ObtenerCrucesTpv($nombre_tabla){
		// @ Objetivo:
		// Obtener los campos de la tabla de tpv y sus cruces con BDimportar
		
		parent::setRoot($this->parametros_tabla); // Cambios root de clase parametros padre.
		$tablas_tpv=parent::Xpath('tpv/tabla'); //Obtenemos Xml de tabla.
		foreach ( $tablas_tpv as $tabla){
			if ( (string) $tabla->nombre === $nombre_tabla){
				foreach ($tabla->cruces->campo as $campo){
					if ( (string)$campo->cruce !== '' ){
						$n = (string) $campo['nombre'];
						$resultado[$n]=(string) $campo->cruce;
					}
				}
			}
		}
		
		
		// Ahora volvemos a poner como raiz parametros.
		$this->root = parent::crearXml();
		$this->raiz = 'Si';
		return $resultado;
	}
	
	public function ObtenerConsultasAnhadir(){
		// Recuerda que tiene que root de xml en parametros_tabla.
		$resultado = array();
		$c = parent::Xpath('consultas//campos[@tipo="anhadir"]');
		foreach ($c as $anhadir){
			$t = (string) $anhadir['tabla'];
			$resultado[$t] = trim((string)$anhadir);
		}
		return $resultado;
	}
	
	public function ObtenerBefore(){
		// Recuerda que tiene que tener root de xml en parametros_tabla para funcionamiento correcto.
		// Tambien entiendo que hay una funcion por tabla despues de obtener.
		$resultado = array();
		$c = parent::Xpath('consultas//before[@tipo="anhadir"]');
		foreach ($c as $anhadir){
			$t = (string) $anhadir['tabla'];
			$resultado[$t] = trim((string)$anhadir);
		}
		return $resultado;
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
	public function getCamposTpv(){
		return $this->campos_tpv;
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
	public function getConsultas($tipo =''){
		if ($tipo === ''){
			return $this->consultas;
		} else {
			return $this->consultas[$tipo];
		}
	}
	
	public function getBeforeAnhadir(){
		return $this->before;
	}
	
	
}

?>
