<?php 

include_once $RutaServidor.$HostNombre.'/clases/ClaseTFModelo.php';
require_once ($RutaServidor.$HostNombre.'/plugins/plugins.php');
include_once $URLCom.'/modulos/mod_usuario/clases/claseUsuarios.php';

class ClaseReorganizar extends TFModelo  {
    public $plugins; // (array) de objectos que son los plugins que vamos tener para este modulo.
    public $idTiendaWeb =0;
    public function __construct()
	{
        // Cargamos plugin si hay para este modulo.
   		$this->view = str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['PHP_SELF']);
        $plugins = new ClasePlugins('mod_reorganizacion',$this->view);
		$this->plugins = $plugins->GetParametrosPlugins();
    }
    public function SetPlugin($nombre_plugin){
        // @ Objetivo
        // Devolver el Object del plugin en cuestion.
        // @ nombre_plugin -> (string) Es el nombre del plugin que hay parametros de este.
        // Devuelve:
        // Puede devolcer Objeto  o boreano false.
        $Obj = false;
        if (count($this->plugins)>0){
            foreach ($this->plugins as $plugin){
                if ($plugin['datos_generales']['nombre_fichero_clase'] === $nombre_plugin){
                    $Obj = $plugin['clase'];
                }
            }
        }
        return $Obj;

    }

    public function contar($tipo='') {
           // Contar articulos de web o de tpv
            if ($tipo === 'web'){
                $tabla = ' articulosTiendas where idTienda='.$this->idTiendaWeb; // espacios es importante.
            } else {
                $tabla = ' articulos ';
            }
            $sql = 'SELECT COUNT(idArticulo) AS contador '
                    . 'FROM'.$tabla;
            $resultado = $this->consulta($sql);
            return $resultado['datos'][0]['contador'];
    }

    public function setIdTiendaWeb($id){
        $this->idTiendaWeb = $id;
    }

    public function obtenerIdsWeb($inicio,$cantidad){

        $sql = 'SELECT T.idArticulo, T.idVirtuemart, (' .
                    'SELECT IFNULL( stockOn, 0 ) ' .
                    'FROM articulosStocks ' .
                    'WHERE T.idArticulo = idArticulo ' .
               ') as stockOn, ( ' .
                    'SELECT pvpSiva ' .
                    'FROM `articulosPrecios` ' .
                    'WHERE T.idArticulo = idArticulo ) as pvpSiva ' .
               'FROM `articulosTiendas` AS T ' .
               'WHERE T.idTienda = ' . $this->idTiendaWeb .
               ' LIMIT ' . $inicio . ',' . $cantidad;

        $resultado = $this->consulta($sql);
        return $resultado;


    }
    public function obtenerUsuarios(){
        // @ Objetivo:
        // Obtener todos los usuarios
        $BDTpv = parent::getDbo();
        $CUsuario=new ClaseUsuarios($BDTpv);
        $usuarios=$CUsuario->todosUsuarios();
        $respuesta = array();
        $respuesta = $usuarios['datos']; // array con los datos usuario.
        return $respuesta;

    }
    
   
}



?>
