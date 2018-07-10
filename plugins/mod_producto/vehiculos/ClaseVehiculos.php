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
	public $Ruta_plugin; // (string) Ruta desde servidor a plugin.
	public $dedonde;  
	public function __construct($dedonde ='') {
		parent::__construct(); // Inicializamos la conexion.
		$this->dedonde = $dedonde;
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
		$this->Ruta_plugin 		= $this->HostNombre.'/plugins/mod_producto/vehiculos/';
	}
	
	public function getRutaPlugin(){
		return $this->Ruta_plugin; 
	}
	
	public function htmlFormularioSeleccionVehiculo(){ 
        $respuesta = array();
        $HostNombre = $this->HostNombre;
        $html	='<script>var ruta_plg_vehiculos = "'.$this->Ruta_plugin.'"</script>'
				.'<script src="'.$HostNombre.'/plugins/mod_producto/vehiculos/func_plg_producto_vehiculo.js"></script>
                <style>#enlaceIcon{ height: 2.2em;}</style>'
                .'<div class="row" id="SeleccionarVersion">'
				.'<div id="vehiculos_seleccionados">';
		if (isset($_SESSION['coches_seleccionados'])){
			foreach ($_SESSION['coches_seleccionados'] as $key=>$coche){
				$html.= $this->HtmlVehiculo($coche,$key);

			}
		}
		$html  .='</div>'
                .'<div class="col-md-3">'
                .   '<div class="ui-widget" id="divmarca">';
        $options = $this->ObtenerMarcasVehiculoWeb();
        $cantidad=count($options['items']['items']);
        $html.='<label for="tags">Marca: '.$cantidad.'</label>'
                .'<select id="combobox" class="marca">'
                .'  <option value="0"></option>';
        foreach ($options['items']['items'] as $marca){
                $html.= '<option value="'.$marca['id'].'">'.$marca['nombre'].'</option>';
        }
        $html.='</select>'
            .'</div>'
            .' </div>'
            .'<div class="col-md-3"  id="divModelo">'
            .'   <div class="ui-widget">'
            .'      <label for="tags" id="modeloLabel">Modelo: </label>'
            .'      <select id="combobox" class="modelo">'
            .'       </select>'
            .'   </div>'
            .'</div>'
            .'<div class="col-md-6"  id="divVersion">'
            .'  <div class="ui-widget">'
            .'      <label for="tags" id="versionesLabel">Versiones: </label>'
            .'      <select id="combobox" class="version">'
            .'       </select>'
            .'  </div>'
            .'  <p id="botonVer"></p>'
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
		return $respuesta['Datos'];
	
	}
		
	
	public function ObtenerModelosUnaMarcaWeb($idMarca){
		// @Objetivo es obtener los mosdelos de una marca que tenemos en el componente de la web. ('SELECT * FROM `prefijo_vehiculo_modelos` where idmarca = ? )
		// [VARIABLE DE CONEXION]
		$ruta =$this->ruta_web;
		$parametros = array('key' 		=>$this->key_api,
							'action'	=>'ObtenerModelosUnaMarca',
							'idMarca' 	=>$idMarca
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
		return $respuesta;
	
	}
	
	public function ObtenerVersionesUnModeloWeb($idModelo){
		// @Objetivo es obtener las versiones de un modelo de la web.
		// [VARIABLE DE CONEXION]
		$ruta =$this->ruta_web;
		$parametros = array('key' 		=>$this->key_api,
							'action'	=>'ObtenerVersionesUnModelo',
							'idModelo' 	=>$idModelo
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
		return $respuesta;
	
	}
	
	
	
	public function ObtenerUnVehiculo($idVersion){
		// @Objetivo es obtener los datos de un vehiculo enviando version de la web.
		// [VARIABLE DE CONEXION]
		$ruta =$this->ruta_web;
		$parametros = array('key' 		=>$this->key_api,
							'action'	=>'ObtenerUnVehiculo',
							'idVersion' 	=>$idVersion
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
		return $respuesta;
	
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
	
	public function HtmlVehiculo($vehiculo,$item){
		$html = '<div class="col-md-12">'
				.'<div class="alert alert-success">'
				.'<span>'
				.' '.$vehiculo['id']
				.'</span>'
				.'<span>'
				.' '.$vehiculo['marca']
				.'</span>'
				.'<span>'
				.' '.$vehiculo['modelo']
				.'</span>'
				.'<span>'
				.' Version:'.$vehiculo['nombre']
				.'</span> '
				.'<span class="label label-success">'
				.' CV/KW:'.$vehiculo['cv'].'/'.$vehiculo['kw']
				.'</span> '
				.'<span class="label label-success">'
				.' cm3:'.$vehiculo['cm3']
				.'</span> '
				.'<span class="label label-success">'
				.' combustible:'.$vehiculo['combustible']
				.'</span> '
				.'<span class="label label-success">'
				.' Inicio Fabricacion:'.$vehiculo['fecha_inicial']
				.'</span> '
				.'<span class="label label-success">'
				.' Fin Fabricacion:'.$vehiculo['fecha_final']
				.'</span> '
				.'<span>'
				.'----> Numero recambios('.count($vehiculo['Recambios']).') '
				.'</span>'
				.'<button class="btn btn-primary eliminar_item" onclick="EliminarVehiculoSeleccionado(event,'."'".$item."'".','."'".$this->dedonde."'".')"><span class="glyphicon glyphicon-trash"></span></button>'
				.'</div>'
				.'</div>';
		return $html;
	}
	
	
	function ObtenerVehiculosUnProducto ($idVirtuemart) {
		// @Objetivo es obtener todos lo vehiculos de un producto y html
		// [VARIABLE DE CONEXION]
		$ruta =$this->ruta_web;
		$parametros = array('key' 			=>$this->key_api,
							'action'		=>'ObtenerVehiculosUnProducto',
							'id_virtuemart'	=>$idVirtuemart
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
		return $respuesta;
		
	}
    
  
	
	
}
?>
