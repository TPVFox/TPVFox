<?php
  /*  Objetivo de este plugin:
   *  Es poder interacturar con los productos de la tienda Virtuemart.
  * */ 
class PluginClaseVirtuemart extends ClaseConexion{
    
	public $ruta_producto = '';// Es la ruta al producto de la tienda.

    public $TiendaWeb = array() ; // Datos de la tienda web .. solo puede haber una.
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
            echo '<pre>';
            print_r('Error hay mas de una empresa tipo web');
            echo '</pre>';
			exit();
		} else {
			$this->TiendaWeb = $tiendasWebs['items'][0];
            $tiendaWeb=$tiendasWebs['items'][0];
            // Esto no es correcto ya que si no es virtuemart, seguro que hay que poner otro link...  :-)
			$this->ruta_producto = $this->TiendaWeb['dominio']."/index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=";
            $this->key_api 	= $tiendaWeb['key_api'];
            $this->ruta_web = $tiendaWeb['dominio'].'/administrator/apisv/tareas.php';
        }
	}
	public function obtenerRutaProyecto(){
		// Objectivo
		// Obtener rutas del servidor y del proyecto.
		$this->RutaServidor 	= $_SERVER['DOCUMENT_ROOT']; // Sabemos donde esta el servidor.
		$RutaProyectoCompleta 	= $this->ruta_proyecto;
		$this->HostNombre		= str_replace($this->RutaServidor,'',$RutaProyectoCompleta);
		$this->Ruta_plugin 		= $this->HostNombre.'/plugins/mod_producto/virtuemart/';
	}
	
	public function getRutaPlugin(){
		return $this->Ruta_plugin; 
	}
    public function getTiendaWeb(){
        return $this->TiendaWeb;

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
	
	public function btnLinkProducto($idVirtuemart){
		// @ Objetivo :
		// Crear un link al pagina detalle del producto.
		$html = '<a target="_blank" href="'.$this->ruta_producto.$idVirtuemart.'">Link web del producto</a>';
		return $html;
		
	}

    public function obtenerIdVirtuemart($ref_tiendas){
        // @ Objetivo:
        // Obtener el idVirtuermart de la tienda web.
        // @ Parametros:
        //  Array de arrays donde tenemos [crefTienda],[idTienda],[idVirtuemart],[pvpCiva],[pvpSiva],[tipoTienda] ,[dominio]
        // Recorremos ese array buscando idTienda coincida con id de TiendaWeb y devolvemos id virtuemart.
        $respuesta = '';
        if ( gettype($ref_tiendas) === 'array'){
            foreach ($ref_tiendas as $tiendas){
                
                if ($tiendas['idTienda']  === $this->TiendaWeb['idTienda']){
                    // Existe tienda , obtenemos idVirtuemart
                    
                    $respuesta = $tiendas['idVirtuemart'] ;
                }
            }
        }
        return $respuesta ;
    }
      function ObtenerDatosDeProducto($idVirtuemart){
        $ruta =$this->ruta_web;
		$parametros = array('key' 			=>$this->key_api,
							'action'		=>'ObtenerProducto',
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
		return $respuesta;
    }
    
    function modificarProducto($datos){
        //@Objetivo: Modificar un producto en la web con los datos que el usuario 
        //añada en el tpv
        //@Parametros: datos principales del producto
        $ruta =$this->ruta_web;
		$parametros = array('key' 			=>$this->key_api,
							'action'		=>'ModificarProducto',
							'datos'	=>$datos
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
		return $respuesta;
    }
    public function htmlDatosProductoSeleccionado($idProducto, $ivas){
        //@Objetivo: Mostrar el html de los datos de los productos de la web
        //@Parametros: idProducto: id de virtuemart
        //ivas: todos los ivas los necesito para saber cuales tiene el id de virtuemart
        $respuesta=array();
        $HostNombre = $this->HostNombre;
        $datosProductoVirtual=$this->ObtenerDatosDeProducto($idProducto);
        $respuesta['datosProductoVirtual']=$datosProductoVirtual;
        $datosWeb=$datosProductoVirtual['Datos']['items']['item'][0];
        $htmlIvasWeb=htmlOptionIvasWeb($ivas, $datosWeb['iva']);
        $precioCivaWeb=$datosWeb['iva']/100*$datosWeb['precioSiva'];
        $precioCivaWeb=$precioCivaWeb+$datosWeb['precioSiva'];
        
         $html	='<script>var ruta_plg_virtuemart = "'.$this->Ruta_plugin.'"</script>'
				.'<script src="'.$HostNombre.'/plugins/mod_producto/virtuemart/func_plg_virtuemart.js"></script>';
        $html.='<div class="col-xs-12 hrspacing"><hr class="hrcolor"></div><div class="col-md-6">'
        .'      <div class="col-md-12">'
        .'          <input class="btn btn-primary" type="button" 
                        value="Modificar en Web" name="modifWeb" onclick="modificarProductoWeb()">'
        .'      </div>'
        .'      <div class="col-md-12">'
        .'          <div class="col-md-7">'
        .'                <h4> Datos del producto en la tienda Web </h4><p id="idWeb">'.$idProducto.'</p>'
        .'           </div>'
        .'           <div class="col-md-5">';
         if($datosWeb['estado']==1){
        $html.='            <label>Estado: <select name="estadosWeb" id="estadosWeb"><option value="1">Publicado</option>
                                    <option value="0">Sin publicar</option></select></label>';
        }else{
        $html.='            <label>Estado: <select name="estadosWeb" id="estadosWeb"><option value="0">Sin publicar</option>
                                    <option value="1">Publicado</option></select></label>';
        }
        $html.='    </div>'
        .'      </div>'
        .'       <div class="col-md-12">'
        .'           <div class="col-md-3 ">'
        .'               <label>Referencia</label>'
        .'               <input type="text" id="referenciaWeb" 
                                name="cref_tienda_principal_web" size="10" 
                                placeholder="referencia producto" data-obj= "cajaReferenciaWeb" 
                                value="'.$datosWeb['refTienda'].'" onkeydown="controlEventos(event)"  >'
        .'          </div>'
        .'          <div class="col-md-8 ">'
        .'              <label>Nombre del producto</label>'
        .'              <input type="text" id="nombreWeb" 
                                name="nombre_web"  size="50"
                                placeholder="nombreWeb" data-obj= "cajaNombreWeb" 
                                value="'. $datosWeb['articulo_name'].'" onkeydown="controlEventos(event)"  >
                                 <div class="invalid-tooltip-articulo_name" display="none">
                                    No permitimos la doble comilla (") 
                                </div>'
        .'          </div>'
        .'      </div>'
        .'      <div class="col-md-12">'
        .'          <h4> Precios de venta en Web </h4>'
        .'       </div>'
        .'       <div class="col-md-12">'
        .'           <div class="col-md-4 ">'
        .'               <label>Código de  barras</label>'
        .'               <input type="text" id="codBarrasWeb" 
                                    name="cod_barras_web"  size="10"
                                    placeholder="codBarrasWeb" data-obj= "cajaCodBarrasWeb" 
                                    value="'.$datosWeb['codBarra'].'" onkeydown="controlEventos(event)"  >'
        .'          </div>'
        .'          <div class="col-md-4 ">'
        .'              <label>Precio Sin iva</label>'
        .'              <input type="text" id="precioSivaWeb" 
                                    name="PrecioSiva_web"  size="10"
                                    placeholder="precioSiva" data-obj= "cajaPrecioSivaWeb" 
                                    value="'.round($datosWeb['precioSiva'],2).'" 
                                    onkeydown="controlEventos(event)" onblur="controlEventos(event)" >'
        .'          </div>'
        .'          <div class="col-md-4 ">'
        .'              <label>Precio Con iva</label>'
        .'              <input type="text" id="precioCivaWeb" 
                                    name="PrecioCiva_web"  size="10"
                                    placeholder="precioCiva" data-obj= "cajaPrecioCivaWeb" 
                                    value="'.round($precioCivaWeb,2).'" onkeydown="controlEventos(event)" 
                                     onblur="controlEventos(event)">'
        .'          </div>'
        .'      </div>'
        .'      <div class="col-md-12">'
        .'          <div class="col-md-4 ">'
        .'              <label>IVA</label>'
        .'              <select name="ivasWeb" id="ivasWeb" onchange="modificarIvaWeb()">'
        .'                  '.$htmlIvasWeb
        .'              </select >'   
        .'          </div>'
        .'      </div>'
        .'  </div>';
        
        $respuesta['html']=$html;
        return $respuesta;
        
    }
}
?>
