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
	public function ObtenerIdProductoPorTipo($tipo){
		// @ Objectivo: 
		// Obtener los datos del proveedor seleccionado y el ultimo coste.()...
		// @ Parametros:
		// 	  $id -> (int) Id del producto a buscar.
		// 	  $idProveedor-> (int) Id de proveedor
		$respuesta = array();
		$Sql= 'SELECT idArticulo  FROM articulos WHERE tipo ="'.$tipo.'"';
		$resp = $this->Consulta($Sql);
		if ($resp['NItems'] > 0){
			// Solo puede obtener un proveedor.
            foreach ($resp['Items'] as $r){
                $respuesta['Items'][] = $r['idArticulo'];
            }
		} else {
			// Hubo error - No encontro
			$error = array ( 'tipo'=>'success',
							 'dato' => $Sql,
							 'mensaje' => 'No encontro ningún producto de tipo peso.'
							 );
			$respuesta['error'] = $error;
		}
		return $respuesta;
	}

    public function GetIdVirtuemartRefTiendas($ref_tiendas){
        // Objetivo:
        // Es devolver el idVirtuemart de la tienda web que tenemos
        $idVirtuemart = 0 ;
        foreach ($ref_tiendas as $ref){
            if ($ref['idTienda'] == $this->idTiendaWeb){
                $idVirtuemart = $ref['idVirtuemart'];
            }
        }
        return $idVirtuemart;


    }

    public function AnhadirCamposPersonalizadosIdVirtuemart($idVirtuemart){
        // @ Objetivo
        // Añadimos los campos personalizados de 100grs, 200grs y 500grs  al idvirtuemar que nos indica, pero
        // solo si no tiene ningun campo personalizado peso ya creado para ese id.
        // NOTA 1:
        // Hay que tener en cuenta que id del campo personalizado ahora lo pongo por defecto el 3, pero esto
        // tendría que ser un parametro de configuracion ,sino no tiene sentido..
        // NOTA 2:
        // El codigo de llamar CURL podría ser una funcion asi no tendríamos que repetirlo tanto.. jejej

        $ruta =$this->ObjVirtuemart->ruta_web;
        $ruta_proyecto = $this->ObjVirtuemart->ruta_proyecto;
        $datos = array();
        $parametros = array('key' 			=>$this->ObjVirtuemart->key_api,
							'action'		=>'AnadirCamposPersonalizado',
                            'idVirtuemart'         =>$idVirtuemart
							
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
