<?php
  /*  Objetivo de este plugin:
   *  Es poder interacturar con los productos de la tienda Virtuemart.
  * */ 
class PluginClaseVirtuemart extends ClaseConexion{
    
	public $ruta_producto = '';// Es la ruta al producto de la tienda.

    public $TiendaWeb = array() ; // Datos de la tienda web .. solo puede haber una.
	
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
            // Esto no es correcto ya que si no es virtuemart, seguro que hay que poner otro link...  :-)
			$this->ruta_producto = $this->TiendaWeb['dominio']."/index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=";
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
}
?>
