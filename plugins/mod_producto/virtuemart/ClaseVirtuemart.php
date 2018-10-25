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

    public function ObtenerDatosDeProducto($idVirtuemart){
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
       
      
        if (!isset($respuesta['error_conexion'])){
            // La respuesta curl (http-code = 200) 
            if(isset($respuesta['Datos']['ivasWeb']['error'])){
               echo '<pre>';
               print_r($respuesta['Datos']['ivasWeb']['error']);
               echo '</pre>';
            }
        }
        return $respuesta;
    }
    
    public function modificarStock($productos){
        $ruta =$this->ruta_web;
        $produc="'".json_encode($productos, true)."'";
		$parametros = array('key' 			=>$this->key_api,
							'action'		=>'descontarStock',
							'productos'	=>$produc
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
    
    
    public function modificarProducto($datos){
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
    public function addProducto($datos){
        //@Objetivo: Modificar un producto en la web con los datos que el usuario 
        //añada en el tpv
        //@Parametros: datos principales del producto
        $ruta =$this->ruta_web;
		$parametros = array('key' 			=>$this->key_api,
							'action'		=>'AddProducto',
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
        //~ echo '<pre>';
        //~ print_r($respuesta);
        //~ echo '</pre>';
		return $respuesta;
    }
    public function contarProductos(){
        //@Objetivo: Modificar un producto en la web con los datos que el usuario 
        //añada en el tpv
        //@Parametros: datos principales del producto
        $ruta =$this->ruta_web;
		$parametros = array('key' 			=>$this->key_api,
							'action'		=>'contarProductos'
						
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
    public function htmlOptionIvasWeb($ivas, $ivaProductoWeb='0'){
        //@OBjetivo: crear el html con las opciones de iva
        //@Parametros: 
        //ivas: (array) todos los ivas de la web
        //ivaProductoWeb: (int) iva que tiene el producto en la web, si No envia entonces 0
        //@Return: html con la estructura de las opciones de iva
        $htmlIvas = '';
        foreach ($ivas as $item){
                $es_seleccionado = '';
                
                if ($ivaProductoWeb == $item['virtuemart_calc_id']){
                    
                    $es_seleccionado = ' selected';
                }
                $htmlIvas .= '<option value="'.$item['virtuemart_calc_id'].'" '.$es_seleccionado.'>'.number_format ($item['calc_value'],2).'%'.'</option>';
		}
        return $htmlIvas;	
    }
    public function htmlJava(){
        //@Objetivo: imprimir el js con los datos del plugin
           $html	='<script>var ruta_plg_virtuemart = "'.$this->Ruta_plugin.'"</script>'
				.'<script src="'.$this->HostNombre.'/plugins/mod_producto/virtuemart/func_plg_virtuemart.js"></script>';
            return $html;
    }
    public function htmlDatosProductoSeleccionado($datosWeb,$ivasWeb,$idProducto,$idTienda,$ivaProducto){
        //@Objetivo
        // Mostrar el html de los datos de los productos de la web
        //@Parametros
        //  $datosWeb: (array) Con los datos del producto en la Web
        //  $ivas: (array) con todos los ivas ,para saber cuales tiene el id de virtuemart
        //  $idProducto : (int) El id del producto en tpv
        //  $idTienda:  (int) El id de... 
        $respuesta=array();
        $HostNombre = $this->HostNombre;
        // Obtenemos html de ivas.
        $htmlIvasWeb=$this->htmlOptionIvasWeb($ivasWeb, $datosWeb['idIva']);
        // Calculamos precios con iva si tiene idVirtual
        if ($datosWeb['idVirtual'] >0 ){
            $precioCivaWeb=$datosWeb['iva']/100*$datosWeb['precioSiva'];
            $precioCivaWeb=$precioCivaWeb+$datosWeb['precioSiva'];
          
        } else {
            $precioCivaWeb=0;
        }
        // Montamos html ( formulario de productos de la web. )
        
        $html	='<script>var ruta_plg_virtuemart = "'.$this->Ruta_plugin.'"</script>'
				.'<script src="'.$HostNombre.'/plugins/mod_producto/virtuemart/func_plg_virtuemart.js"></script>';
        $html   .='<div class="col-xs-12 hrspacing"><hr class="hrcolor"></div>
        <h2 class="text-center">Datos del Producto en la Web</h2>
        <div class="col-md-6">
                ';
        if ($datosWeb['idVirtual'] >0 ){
            $html   .='      <div class="col-md-12">'
            .'          <input class="btn btn-primary" type="button" 
                        value="Modificar en Web" id="botonWeb" name="modifWeb" onclick="modificarProductoWeb('.$idProducto.', '.$idTienda.')">';
        } else {
            $html   .=' <div class="col-md-12">'
            .'          <input class="btn btn-primary" id="botonWeb" type="button" 
                            value="Añadir a la web" name="modifWeb" onclick="modificarProductoWeb('.$idProducto.', '.$idTienda.')">';
        }
        $html   .='          <a onclick="ObtenerDatosProducto()">Obtener datos producto</a>'
            .'      </div>';




        $html   .='      <div class="col-md-12" id="alertasWeb">'
        .'      </div>'
        .'      <div class="col-md-12">'
        .'          <div class="col-md-7">'
        .'                <h4> Id del producto en Web :</h4><p id="idWeb">'.$datosWeb['idVirtual'].'</p>'
        .'           </div>'
        .'           <div class="col-md-5">';
         if($datosWeb['estado']==1){
        $html   .='            <label>Estado: <select name="estadosWeb" id="estadosWeb"><option value="1">Publicado</option>
                                    <option value="0">Sin publicar</option></select></label>';
        }else{
        $html   .='            <label>Estado: <select name="estadosWeb" id="estadosWeb"><option value="0">Sin publicar</option>
                                    <option value="1">Publicado</option></select></label>';
        }
        $html   .='    </div>'
        .'      </div>'
       
        .'       <div class="col-md-12">'
        .'           <div class="col-md-3 ">'
        .'               <label>Referencia</label>'
        .'               <input type="text" id="referenciaWeb" 
                                name="cref_tienda_principal_web" size="10" 
                                placeholder="referencia producto"
                                value="'.$datosWeb['refTienda'].'"  >'
        .'          </div>'
        .'          <div class="col-md-8 ">'
        .'              <label>Nombre del producto</label>'
        .'              <input type="text" id="nombreWeb" 
                                name="nombre_web"  size="50"
                                placeholder="nombreWeb" 
                                value="'. $datosWeb['articulo_name'].'"  >
                                 <div class="invalid-tooltip-articulo_name" display="none">
                                    No permitimos la doble comilla (") 
                                </div>'
        .'          </div>'
        .'      </div>'
         .'      <div class="col-md-12">'
        .'          <div class="col-md-5">'
        .'              <label>Alias de producto</label>'
        .'              <input type="text" id="alias" name="alias" value="'.$datosWeb['alias'].'">
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
                                    placeholder="codBarrasWeb" 
                                    value="'.$datosWeb['codBarra'].'"  >'
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

    public function ObtenerNotificacionesProducto($idProducto){
        $ruta =$this->ruta_web;
		$parametros = array('key' 			=>$this->key_api,
							'action'		=>'ObtenerNotificacionesProducto',
							'idProducto'	=>$idProducto
						);
		// [CONEXION CON SERVIDOR REMOTO] 
		// Primero comprobamos si existe curl en nuestro servidor.
		$existe_curl =function_exists('curl_version');
		if ($existe_curl === FALSE){
			echo '<pre>';
			print_r(' No existe curl');
			echo '</pre>';
			exit();
		}
		include ($this->ruta_proyecto.'/lib/curl/conexion_curl.php');
		return $respuesta;
    }

    public function htmlNotificacionesProducto($idProducto){
        //@Objetivo: MOstrar una tabla con las notificaciones del producto en la web
        //@Parametros:
        //idProducto: id del producto
        //Return: html con las tabla de notificaciones
        $datosNotificaciones=$this->ObtenerNotificacionesProducto($idProducto);
       
        $resultado=array();
        if (isset ($datosNotificaciones['Datos']['items'])){
            // Si existe es que fue correcta consulta.
            if(count($datosNotificaciones['Datos']['items'])==0){
               $html='<div class="alert alert-info">Este producto no tiene notificaciones de Clientes</div>';
            }else{
                 $datos=$datosNotificaciones['Datos']['items'];
                 $html='<table class="table table-striped">
                    <thead>
                        <tr>
                            <td>Nombre</td>
                            <td>Correo</td>
                            <td></td>
                            <td>Enviar</td>
                            <td>Cantidad</td>
                        </tr>
                    </thead>
                    <tbody>';
                   $i=1;
                   foreach($datos as $dato){
                        $html.='<tr id="Linea_'.$i.'">
                            <td id="nombre_'.$i.'">'.$dato['nombreUsuario'].'</td>
                            <td id="mail_'.$i.'">'.$dato['email'].'</td>
                             <td><input type="text" id="idNotificacion_'.$i.'" value="'.$dato['idNotificacion'].'" style="display:none"></td>
                            <td> <a  onclick="ModalNotificacion('.$i.')">
                                <span class="glyphicon glyphicon-envelope"></span>
                            </a></td>
                            <td>'.$dato['cant'].'</td>
                           
                        </tr>';
                        $i++;
                    }
                    
                    $html.='</tbody>
                 </table>';
            }
            
        }else{
             $html='<div class="alert alert-danger">Error de SQL: '.$datosNotificaciones['Datos']['error'].'</div>';
        }
        $resultado['html']=$html;
        return $resultado;
       
    }
    
   public function modificarNotificacion($idProducto, $email){
        //@Objetivo: Modificar un producto en la web con los datos que el usuario 
        //añada en el tpv
        //@Parametros: datos principales del producto
        $ruta =$this->ruta_web;
		$parametros = array('key' 			=>$this->key_api,
							'action'		=>'modificarNotificacion',
							'idProducto'	=>$idProducto,
                            'email'=>$email
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

    public function comprobarIvas($ivaProducto, $ivaWeb){
        //@OBjetivo:
        // Comprobar el iva del producto en el tpv y en la web
        // Si no es el mismo enviamo array con error para mostra como alerta
        
        if(number_format($ivaProducto,2)!=number_format($ivaWeb,2)){
            $resultado=array();
            $comprobacionIva=array(
            'tipo'=>'warning',
            'mensaje'=>'El iva del producto TPVFox y del producto en la web NO COINCIDEN'
            );
            $resultado['comprobaciones']= $comprobacionIva;
            return $resultado;
        }
       
   }
   
    public function datosCompletosTiendaWeb($idVirtuemart,$ivaProducto,$idProducto,$idTiendaWeb){
        // Objetivo
        // Es obtener todos los datos del producto en la web.
        // Los html necesarios para mostrar en ficha de producto.
        // Hacer comprobaciones iva.
        // A este modulo solo se llama desde vista productos... una vez :-)

        $respuesta=array();
        // Ahora obtengo datos del producto de la web.
        $datosProductoVirtual=$this->ObtenerDatosDeProducto($idVirtuemart);
        // Tambien obtenemos todos los ivas de la  web.
        $ivasWeb=$datosProductoVirtual['Datos']['ivasWeb']['items'];
        $respuesta['ivasWeb'] = $ivasWeb; 
        if ($idVirtuemart == 0) {
            // Cuando no existe la relacion en tpv con la tienda (articuloTienda)
            $id_iva_web= 0;
            $iva = 0;
            foreach ($ivasWeb as $iva_web){
                // buscamos el iva que tiene el producto, para enviar id del iva de la web.
                if ( number_format($iva_web['calc_value'],2) == number_format($ivaProducto,2)){
                    $id_iva_web = $iva_web['virtuemart_calc_id'];
                    $iva = number_format($ivaProducto,2);
                }
            }

            $datosWeb = array(
                            'idVirtual'     => 0,
                            'estado'        => 1,
                            'articulo_name' => "",
                            'refTienda'     => "",
                            'codBarra'      => "",
                            'precioSiva'    => 0,
                            'idIva'         => $id_iva_web,
                            'alias'         => "",
                            'iva'           =>".$iva."
                        );
        } else {
            $datosWeb=$datosProductoVirtual['Datos']['datosProducto']['item'];
            // Comprobamos si el iva del producto es el mismo en tpv que en la web
            // Asi advertimos al usuario que algo esta mal..
            $respuesta['comprobarIvas']=$this->comprobarIvas($ivaProducto, $datosWeb['iva']);
        }
        $respuesta['datosWeb'] = $datosWeb; // Nos lo devolvemos.
        
        
        $respuesta['htmlLinkVirtuemart']=$this->btnLinkProducto($idVirtuemart);
        $htmlnotificaciones=$this->htmlNotificacionesProducto($idVirtuemart);
        $respuesta['htmlnotificaciones']=$htmlnotificaciones;
        $respuesta['htmlproducto']=$this->htmlDatosProductoSeleccionado($datosWeb,$ivasWeb,$idProducto,$idTiendaWeb,$ivaProducto);
        
      
       return $respuesta;
    }
    
   
    public function enviarCorreo($datos){
        //@Objetivo : conextarnos a la api para enviar correo al usuario que envió
        //una notificación
        $ruta =$this->ruta_web;
		$parametros = array('key' 			=>$this->key_api,
							'action'		=>'enviarCorreo',
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
		include ($this->ruta_proyecto.'/lib/curl/conexion_curl.php');
         $respuesta['parametros']=$parametros;
		return $respuesta;
    }
    public function productosInicioFinal($inicio, $final){
        $ruta =$this->ruta_web;
		$parametros = array('key' 			=>$this->key_api,
							'action'		=>'productosInicioFinal',
							'inicio'	    =>$inicio,
                            'final'         =>$final
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
       
      
        if (!isset($respuesta['error_conexion'])){
            // La respuesta curl (http-code = 200) 
            if(isset($respuesta['Datos']['ivasWeb']['error'])){
               echo '<pre>';
               print_r($respuesta['Datos']['ivasWeb']['error']);
               echo '</pre>';
            }
        }
        return $respuesta;
    }
    public function error_string($datos){
        // @ Objetivo
        // Comprobamos que a laoras Obtener datos no se produce un error y si es asi devolvemos el string del error
        // @ Parametros
        //      $datos -> (array) respuesta de conexion
        // @ Devolvemos:
        //   (string) Errores...
        $error = '';
        if ( isset($datos['error_conexion'])){
            // Quiere decir que hubo error de conexion
            // Este no se si realmente llega alguna vex...
            $error  = $datos['error_conexion'];
            return $error;
        }
        if (  isset($datosProductoVirtual['error']) ){
            // Hubo error en la conexion.
            $error  .= $datosProductoVirtual['error'];
            return $error ; // No hice pruebas de esto...
        }
        if ( isset ($datosProductoVirtual['Datos']['ivasWeb']['items'] )){
            if (count($datosProductoVirtual['Datos']['ivasWeb']['items']) === 0){
                // Quiere decir que hizo la consulta pero no hay ivas en la web.
                $error .=  ' No hay ivas en la web';
                return $error;
            }
        }

    }
}
?>
