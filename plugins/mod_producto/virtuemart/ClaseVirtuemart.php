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
    public function htmlOptionIvasWeb($ivas, $ivaProductoWeb){
        //@OBjetivo: crear el html con las opciones de iva
        //@Parametros: 
        //ivas: todos los ivas de la web
        //ivaProductoWeb: iva que tiene el producto en la web
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
    public function htmlDatosProductoSeleccionado($idProducto, $permiso){
        //@Objetivo
        // Mostrar el html de los datos de los productos de la web
        //@Parametros
        //  $idProducto: (int) id de virtuemart
        //  $ivas: (array) con todos los ivas ,para saber cuales tiene el id de virtuemart
        $respuesta=array();
        $HostNombre = $this->HostNombre;
        $datosProductoVirtual=$this->ObtenerDatosDeProducto($idProducto);
        $respuesta['datosProductoVirtual']=$datosProductoVirtual;
        $datosWeb=$datosProductoVirtual['Datos']['datosProducto']['item'];
        $respuesta['datosWeb']=$datosWeb;
         
        $ivasWeb=$datosProductoVirtual['Datos']['ivasWeb']['items'];
       
        $htmlIvasWeb=$this->htmlOptionIvasWeb($ivasWeb, $datosWeb['idIva']);
        $precioCivaWeb=$datosWeb['iva']/100*$datosWeb['precioSiva'];
        $precioCivaWeb=$precioCivaWeb+$datosWeb['precioSiva'];
        
        $html	='<script>var ruta_plg_virtuemart = "'.$this->Ruta_plugin.'"</script>'
				.'<script src="'.$HostNombre.'/plugins/mod_producto/virtuemart/func_plg_virtuemart.js"></script>';
        $html   .='<div class="col-xs-12 hrspacing"><hr class="hrcolor"></div>
        <h2 class="text-center">Datos Producto Web</h2>
        <div class="col-md-6">
                ';
        if($permiso==1){
        $html   .='      <div class="col-md-12">'
        .'          <input class="btn btn-primary" type="button" 
                        value="Modificar en Web" id="botonWeb" name="modifWeb" onclick="modificarProductoWeb()">'
        .'      </div>';
    }
        $html   .='      <div class="col-md-12" id="alertasWeb">'
        .'      </div>'
        .'      <div class="col-md-12">'
        .'          <div class="col-md-7">'
        .'                <h4> Datos del producto :</h4><p id="idWeb">'.$idProducto.'</p>'
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
        $respuesta['ivaProducto']=$datosWeb['iva'];
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
    public function htmlDatosVacios($idProducto, $idTienda, $permiso){
        // @ Objetivo :
        // Entiendo que es obtener los datos vacios.
        $respuesta=array();
        $HostNombre = $this->HostNombre;
        // Esto lo haces para obtener los ivas.
        $datosProductoVirtual=$this->ObtenerDatosDeProducto(0);
        $html	='<script>var ruta_plg_virtuemart = "'.$this->Ruta_plugin.'"</script>'
                    .'<script src="'.$HostNombre.'/plugins/mod_producto/virtuemart/func_plg_virtuemart.js"></script>';
        $html   .='<div class="col-xs-12 hrspacing">'
                .'<hr class="hrcolor"></div>'
                .'<h2 class="text-center">Datos Producto Web</h2>';
        if ($datosProductoVirtual['error_conexion']){
            // Quiere decir que hubo error de conexion
            // No permito continuar
            $html   .= '<div class="col-md-12">'
                    . 'Error de conesion ...'
                    .$datosProductoVirtual['error_conexion'].'</div>';
        } else {
    
            $ivasWeb=$datosProductoVirtual['Datos']['ivasWeb']['items'];
            
            $html   .= '<div class="col-md-6">';
            if($permiso==1){
            $html   .=' <div class="col-md-12">'
            .'          <input class="btn btn-primary" id="botonWeb" type="button" 
                            value="Añadir a la web" name="modifWeb" onclick="modificarProductoWeb('.$idProducto.', '.$idTienda.')">'
            .'          <a onclick="ObtenerDatosProducto()">Obtener datos producto</a>'
            .'      </div>';
            }
            $html   .='<div class="col-md-12" id="alertasWeb">'
            .'      </div>'
            .'      <div class="col-md-12">'
            .'          <div class="col-md-7">'
            .'                <h4> Datos del producto en la tienda Web </h4><p id="idWeb"></p>'
            .'           </div>'
            .'           <div class="col-md-5">';
            $html   .='            <label>Estado: <select name="estadosWeb" id="estadosWeb"><option value="1">Publicado</option>
                                        <option value="0">Sin publicar</option></select></label>';
           
            $html   .='    </div>'
            .'      </div>'
           
            .'       <div class="col-md-12">'
            .'           <div class="col-md-3 ">'
            .'               <label>Referencia</label>'
            .'               <input type="text" id="referenciaWeb" 
                                    name="cref_tienda_principal_web" size="10" 
                                    placeholder="referencia producto"
                                    value=""  >'
            .'          </div>'
            .'          <div class="col-md-8 ">'
            .'              <label>Nombre del producto</label>'
            .'              <input type="text" id="nombreWeb" 
                                    name="nombre_web"  size="50"
                                    placeholder="nombreWeb" 
                                    value=""  >
                                     <div class="invalid-tooltip-articulo_name" display="none">
                                        No permitimos la doble comilla (") 
                                    </div>'
            .'          </div>'
            .'      </div>'
             .'      <div class="col-md-12">'
            .'          <div class="col-md-5">'
            .'              <label>Alias de producto</label>'
            .'              <input type="text" id="alias" name="alias" value=""  disabled>
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
                                        value=""  >'
            .'          </div>'
            .'          <div class="col-md-4 ">'
            .'              <label>Precio Sin iva</label>'
            .'              <input type="text" id="precioSivaWeb" 
                                        name="PrecioSiva_web"  size="10"
                                        placeholder="precioSiva" data-obj= "cajaPrecioSivaWeb" 
                                        value="" 
                                        onkeydown="controlEventos(event)" onblur="controlEventos(event)" >'
            .'          </div>'
            .'          <div class="col-md-4 ">'
            .'              <label>Precio Con iva</label>'
            .'              <input type="text" id="precioCivaWeb" 
                                        name="PrecioCiva_web"  size="10"
                                        placeholder="precioCiva" data-obj= "cajaPrecioCivaWeb" 
                                        value="" onkeydown="controlEventos(event)" 
                                         onblur="controlEventos(event)">'
            .'          </div>'
            .'      </div>'
            .'      <div class="col-md-12">'
            .'          <div class="col-md-4 ">'
            .'              <label>IVA</label>'
            .'              <select name="ivasWeb" id="ivasWeb" onchange="modificarIvaWeb()">'
            .'                  ';
            
            foreach($ivasWeb as $iva){
                $html.='<option value="'.$iva['virtuemart_calc_id'].'">'.number_format($iva['calc_value'],2).'%</option>';
            }
             $html   .='      </select >'   
            .'          </div>'
            .'      </div>'
            .'  </div>';
        }
        return $html;
    }
   public function modificarNotificacion($idNotificacion){
        //@Objetivo: Modificar un producto en la web con los datos que el usuario 
        //añada en el tpv
        //@Parametros: datos principales del producto
        $ruta =$this->ruta_web;
		$parametros = array('key' 			=>$this->key_api,
							'action'		=>'modificarNotificacion',
							'idNotificacion'	=>$idNotificacion
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
      //@OBjetivo: comprobar el iva del producto en el tpv y en la web
      //Si no es el mismo muestra una alerta
        if($ivaProducto!=number_format($ivaWeb,2)){
                
                $comprobacionIva=array(
                'tipo'=>'warning',
                'mensaje'=>'El iva del producto TPVFox y del producto en la web NO COINCIDEN'
                );
                $resultado=array();
                $resultado['comprobaciones']= $comprobacionIva;
                return $resultado;
            }
   }
   
    public function datosTiendaWeb($idVirtuemart,  $ivaProducto, $permiso){
        // Objetivo
        // Es obtener los datos necesarios del producto web.
        
        $respuesta=array();
        $respuesta['htmlLinkVirtuemart']=$this->btnLinkProducto($idVirtuemart);
        $htmlnotificaciones=$this->htmlNotificacionesProducto($idVirtuemart);
        $respuesta['htmlnotificaciones']=$htmlnotificaciones;
        $respuesta['datosProductoWeb']=$this->htmlDatosProductoSeleccionado($idVirtuemart, $permiso);
        $respuesta['comprobarIvas']=$this->comprobarIvas($ivaProducto, $respuesta['datosProductoWeb']['datosWeb']['iva']);
      
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
    
}
?>
