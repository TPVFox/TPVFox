<?php
$clasesDeclaradas = get_declared_classes();
if (!in_array("ClaseConexion", $clasesDeclaradas)) {
    echo "No declaraste Clase conexion";
    echo json_encode($claseDeclaradas);
    exit();
}



class PluginClaseVehiculos extends ClaseConexion{
	public $ruta_web; // (string) ruta que indica donde esta la web de donde obtenemos los datos.
	public $key_api; // (string) que es la llave para conectarse.. debemos obtenerla de la base de datos.
	public $HostNombre; // (string) Ruta desde servidor a proyecto..
	public function __construct() {
		parent::__construct(); // Inicializamos la conexion.
		$this->obtenerRutaProyecto();
		$tiendasWebs = $this->ObtenerTiendasWeb();
		if (count($tiendasWebs['items'])>1){
			// Quiere decir que hay mas de una tienda web,, no podemos continuar.
			exit();
		} else {
			$tiendaWeb = $tiendasWebs['items'][0];
			$this->ruta_web = $tiendaWeb['dominio'].'/administrator/apisv/tareas.php';
			$this->key_api 	= $tiendaWeb['key_api'];
		}
	}
	
	public function obtenerRutaProyecto(){
		// Objectivo
		// Obtener rutas del servidor y del proyecto.
		$this->RutaServidor 	= $_SERVER['DOCUMENT_ROOT']; // Sabemos donde esta el servidor.
		$RutaProyectoCompleta 	= $this->ruta_proyecto;
		$this->HostNombre		= str_replace($this->RutaServidor,'',$RutaProyectoCompleta);
		
	}
	
	public function htmlFormularioSeleccionVehiculo(){ 
		//@ Objetivo: 
		// Crear formulario html para selecciona Vehiculo
		$respuesta = array();
		$HostNombre = $this->HostNombre;
		$html	='<script src="'.$HostNombre.'/plugins/mod_producto/vehiculos/func_plg_producto_vehiculo.js"></script>'
				.'<div class="row" id="SeleccionarVersion">'
				.'	<!-- Presentacion de marca -->'
				.'		<div class="col-md-3 form-group marca">'
				.' 		<label class="marca">Marca</label>'
				.'			<!-- Cargamos select con marcas -->'
				.'			<select name="myMarca" id="myMarca" onchange="SeleccionMarca()">';
		$options = $this->ObtenerMarcasVehiculoWeb();
		$html .= $options;		
		$html .='			</select>'
				.'		</div>'
				.'		<!-- Presentacion de modelo -->'
				.'		<div class="col-md-3 form-group nodelo">'
				.'			<label class="nodelo">Modelo</label>'
				.'			<!-- Cargamos select con marcas -->'
				.'			<select disabled name="Minodelo" id="nodelo" onchange="CambioModelos()">'
				.'				<option value="0">Seleccione una modelo</option>'
				.'			</select>'
				.'		</div>'
				.'		<!-- Presentacion de version -->'
				.'		<div class="col-md-3 form-group versiones">'
				.'			<label class="versiones">Versiones</label>'
				.'			<!-- Cargamos select con marcas -->'
				.'			<select disabled name="MiVersiones" id="versiones">'
				.'				<option value="0">Seleccione una modelo</option>'
				.'			</select>'
				.'		</div>'
				.'		<div class="col-md-3 form-group enviar" style="margin-top:20px">'
				.'			<button class="btn btn-primary" type="submit">Seleccionar</button>'
				.'		</div>'
				.'</div>';
		$respuesta['html'] = $html;
		return $respuesta;
	}
	
	
	public function ObtenerMarcasVehiculoWeb(){
		// @Objetivo es obtener la marca que tenemos en el componente de la web. ('SELECT * FROM `prefijo_vehiculo_marcas` )
		// [VARIABLE DE CONEXION]
		$ruta =$this->ruta_web;
		$parametros = array('key' 		=>$this->key_api,
							'action'	=>'ObtenerMarcaVehiculos'
							//~ 'tablaTemporal' =>json_encode($tablasTemporales)
						);
		// [CONEXION CON SERVIDOR REMOTO] 
		// Primero comprobamos si existe curl en nuestro servidor.
		$existe_curl =function_exists('curl_version');
		if ($existe_curl === FALSE){
			echo '<pre>';
			print_r(' No exite curl');
			echo '</pre>';
			exit();
		}
		include ($this->ruta_proyecto.'/lib/curl/conexion_curl.php');
		//~ echo '<pre>';
		//~ print_r($respuesta);
		//~ echo '</pre>';
		return $respuesta['Datos']['options_html'];
	
	}
	
	public function ObtenerTiendasWeb(){
		// Objetivo obtener datos de la tabla tienda para poder cargar el select de tienda On Line.
		$BDTpv = parent::getConexion();
		$resultado = array();
		$sql = "SELECT * FROM `tiendas` WHERE `tipoTienda`='web'";
		$resultado['consulta'] = $sql;
		if ($consulta = $BDTpv->query($sql)){
			// Ahora debemos comprobar que cuantos registros obtenemos , si no hay ninguno
			// hay que indicar el error.
			if ($consulta->num_rows > 0) {
					while ($fila = $consulta->fetch_assoc()) {
					$resultado['items'][]= $fila;
					}
				
			} else {
				// Quiere decir que no hay tienda on-line (web) dada de alta.
				$resultado['error'] = 'No hay tienda on-line';
			}

		} else {
			// Quiere decir que hubo un error en la consulta.
			$resultado['error'] = 'Error en consulta';
			$resultado['numero_error_Mysql']= $BDTpv->errno;
		
		}
		
		return $resultado;
	}
	
	
	
}
?>
