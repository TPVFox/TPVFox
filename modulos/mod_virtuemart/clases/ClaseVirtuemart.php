<?php


include_once ($RutaServidor.$HostNombre.'/modulos/mod_producto/clases/ClaseProductos.php');
class ClaseVirtuemart extends ClaseProductos{
    
    public $idTiendaWeb = 0 ;
    // (int) Id tienda web .Por defecto es cero, se cubri al construir la clase.
    // Si se mantiene = , es que hubo error no podemos continuar (generamos un error_log)

    public $ObjVirtuemart ;  //(object) plugin virtuemart productos.
    
	public function __construct($conexion='')
	{
		// Solo realizamos asignamos 
		if (gettype($conexion) === 'object'){
			parent::__construct($conexion);
		}
        if (parent::SetPlugin('ClaseVirtuemart') !== false){
            $this->ObjVirtuemart = parent::SetPlugin('ClaseVirtuemart');
            $t = $this->ObjVirtuemart->getTiendaWeb();
            $this->idTiendaWeb = $t['idTienda'];

        }
        if (!isset ($this->idTiendaWeb)){
            // Tubo que haber un error..
            $this->idTiendaWeb = 0 ;
            error_log('En claseVirtuemart de modulo virtuemart hubo un error al cargar el plugin productos ');
        }
    
    }
    
	public function obtenerIdVirtuemartRelacionado($reg_inicial){
        // @ Objetivo
        // Obtener los registros articulosTienda
        $respuesta = array();
   		$sql='SELECT * FROM articulosTiendas WHERE  idTienda='.$this->idTiendaWeb.' limit '.$reg_inicial.',100';
        $respuesta = parent::GetConsulta($sql);
        $respuesta['consulta'] = $sql;
        
        return $respuesta;

    }
   
    public function objetoPlg(){
        // metodo creado solo de prueba para saber si obtengo el plugin.
        return $this->ObjVirtuemart;

    }


    public function buscarImagenesParaRelacionar($datos){
        // @ Objetivo : Enviar registros relaciones para buscar imagen y añadir a producto
        // @ Parametros:
        // Enviamos los datos de 100 registros como máximo para no saturar servidor.
        $ruta =$this->ObjVirtuemart->ruta_web;
        $ruta_proyecto = $this->ObjVirtuemart->ruta_proyecto;
        $parametros = array('key' 			=>$this->ObjVirtuemart->key_api,
							'action'		=>'buscarImagenesParaRelacionar',
                            'datos'         =>json_encode($datos)
							
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
		include ($ruta_proyecto.'/lib/curl/conexion_curl.php');
        $respuesta['parametros']=$parametros;
		return $respuesta;
    }
	
}



?>
