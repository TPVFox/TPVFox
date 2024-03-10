<?php 

class PluginClaseVirtuemartFamilia extends ClaseConexion{
    
    public $ruta_categoria = '';// Es la ruta de la categoria de la tienda web.
    public $TiendaWeb = array() ; // Datos de la tienda web .. solo puede haber una.
    public $ruta_web; // (string) ruta que indica donde esta la web de donde obtenemos los datos.
    public $key_api; // (string) que es la llave para conectarse.. debemos obtenerla de la base de datos.
    public $HostNombre; // (string) Ruta desde servidor a proyecto..
    public $Ruta_plugin; // (string) Ruta desde servidor a plugin.
    public $dedonde; 
    
    public function __construct($dedonde ='') {
        parent::__construct(); // Inicializamos la conexion.
        $this->dedonde = $dedonde;
        //~ error_log($dedonde);
        $this->obtenerRutaProyecto();
        $tiendasWebs = $this->ObtenerTiendasWeb();
      
        if (isset($tiendasWebs['items']) && count($tiendasWebs['items'])>1){
            // Quiere decir que hay mas de una tienda web,, no podemos continuar.
            echo '<pre>';
            print_r('Error hay mas de una empresa tipo web');
            echo '</pre>';
            exit();
        } else {
             if ( isset($tiendasWebs['items'][0])){
                $this->TiendaWeb = $tiendasWebs['items'][0];
                $tiendaWeb=$tiendasWebs['items'][0];
                // Esto no es correcto ya que si no es virtuemart, seguro que hay que poner otro link...  :-)
                $this->ruta_categoria = $this->TiendaWeb['dominio']."index.php?option=com_virtuemart&view=category&virtuemart_category_id=";
                $this->key_api  = $tiendaWeb['key_api'];
                $this->ruta_web = $tiendaWeb['dominio'].'/administrator/apisv/tareas.php';
            }
        }
    }
    public function obtenerRutaProyecto(){
        // Objectivo
        // Obtener rutas del servidor y del proyecto.
        $this->RutaServidor     = $_SERVER['DOCUMENT_ROOT']; // Sabemos donde esta el servidor.
        $RutaProyectoCompleta   = $this->ruta_proyecto;
        $this->HostNombre       = str_replace($this->RutaServidor,'',$RutaProyectoCompleta);
        $this->Ruta_plugin      = $this->HostNombre.'/plugins/mod_familia/virtuemart/';
        //~ error_log($this->Ruta_plugin);
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
    public function btnLinkCategoria($idFamiliaWeb){
        // @ Objetivo :
        // Crear un link al pagina detalle del producto.
        $html = '<a target="_blank" href="'.$this->ruta_categoria.$idFamiliaWeb.'">Link web del producto</a>';
        return $html;
        
    }
    public function htmlDatosFamiliaWeb($datos){
        $respuesta=array();
        $HostNombre = $this->HostNombre;
        if ($datos['accion']==='edit'){
            $textobtn = 'Modificar familia en Web';
            $colorbtn = 'primary';
            $idFamilia_tienda = $datos['idFamilia_tienda'];
            $nombre = $datos['nombre_familia_tienda'];
        } else {
            // El valor de accion deberia ser "add".
            $textobtn = 'Añadir familia en Web';
            $colorbtn = 'success';
            $idFamilia_tienda = '';
            $nombre = $datos['nombre'];

        }
        $html   ='<script>var ruta_plg_virtuemart = "'.$this->Ruta_plugin.'"</script>';
                    
        $html   .='<div class="col-xs-12 hrspacing">'
                .'<hr class="hrcolor"></div>'
                .'<h2 class="text-center">Datos Familia Web</h2>';
        $html   .= '<div class="col-md-6">';
        $html   .=' <div class="col-md-12">'
            .'          <input class="btn btn-'.$colorbtn.'" id="botonWeb" type="button" 
                            value="'.$textobtn.'" name="modifWeb" onclick="modificarFamiliaWeb('.$datos['idFamilia'].', '
                            .$this->TiendaWeb['idTienda'].')">'
            .'          <a onclick="ObtenerDatosFamilia()">Obtener datos familia</a>'
            .'          <input type="text" id="idFamiliaweb" value="'. $idFamilia_tienda.'" style="visibility:hidden">
                   '
            .'      </div>';
                
         $html   .='<div class="col-md-12" id="alertasWeb">'
            .'      </div>'
            .'      <div class="col-md-12">'
            .'          <div class="col-md-7">'
            .'                <h4> Datos de la familia con id en Web:'.$datos['idFamilia_tienda'].' </h4>'
            .'           </div>'
            .'           
                    </div>
                    <div class="col-md-12">
                    <div class="col-md-5">
                    <label for="inputnombre">Nombre: </label>
                            <input type="text" nombre="nombreFamilia" id="nombreFamilia" value="'.$nombre.'"/>
                        </div>
                   
                      <div class="col-md-6">
                             <label for="inputnombre">Nombre: </label>
                            <input type="text" name="idFamiliaPadreWeb" id="inputidpadreWeb" value="'.$datos['id_padre_web'].'" size=4/>
                            <input type="text" name="nombre_padre_web" id="nombre_padre_web" value="'.$datos['nombre_padre_web'].'"/>

                            </div>
                        </div>
                    </div>
                    </div>
            '.'<script src="'.$HostNombre.'/plugins/mod_familia/virtuemart/func_plg_virtuemart.js"></script>';
            return $html;
     }
     
     
     
    public function obtenerDatosDeFamilia($idWeb){
        //@Objetivo: OBtener los datos de la familia
        //añada en el tpv
        //@Parametros: id de la familia
        $ruta =$this->ruta_web;
        $parametros = array('key'           =>$this->key_api,
                            'action'        =>'datosFamilia',
                            'idWeb' =>$idWeb
                        );
        //Curl
        include ($this->ruta_proyecto.'/lib/curl/conexion_curl.php');

        return $respuesta;
     }
     
    public function addFamilia($datos){
        //@Objetivo: Modificar un producto en la web con los datos que el usuario 
        //añada en el tpv
        //@Parametros: datos principales del producto
        $ruta =$this->ruta_web;
        $parametros = array('key'           =>$this->key_api,
                            'action'        =>'AddFamilia',
                            'datos' =>$datos
                        );
        //Curl
        include ($this->ruta_proyecto.'/lib/curl/conexion_curl.php');
        return $respuesta;
    }
    
    
    
    public function modificarFamiliaWeb($datos){
        //@Objetivo: Modificar una familia en la web con los datos que el usuario 
        //añada en el tpv
        //@Parametros: datos principales dela familia
        $ruta =$this->ruta_web;
        $parametros = array('key'           =>$this->key_api,
                            'action'        =>'modificarFamilia',
                            'datos' =>$datos
                        );
        //Curl
        include ($this->ruta_proyecto.'/lib/curl/conexion_curl.php');
        return $respuesta;
    }

     public function todasFamilias(){
        //@Objetivo:
        // Obtener los ids y los nombres de todas las familias de la web
        $ruta =$this->ruta_web;
        $parametros = array('key'           =>$this->key_api,
                            'action'        =>'todasFamilias'
                        );
        // [CONEXION CON SERVIDOR REMOTO] 
        // Primero comprobamos si existe curl en nuestro servidor.
        $existe_curl =function_exists('curl_version');
        include ($this->ruta_proyecto.'/lib/curl/conexion_curl.php');
        if (isset($respuesta['error_conexion'])){
            // Ya que la respuesta APi es un indice error, generamos el mismo, para tratar el mismo
            $respuesta['error']=$respuesta['error_conexion'];
        }
        return $respuesta;
     }

     



}



?>
