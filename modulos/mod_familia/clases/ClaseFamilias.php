<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';

/**
 * Description of ClaseFamilias
 *
 * @author alagoro
 */
 include_once ($RutaServidor.$HostNombre.'/plugins/plugins.php');
class ClaseFamilias extends Modelo {

    protected $tabla = 'familias';
    public $plugins;
    public $view ; 
	public $idTienda ;
    public function __construct($conexion='')
	{
		$this->view = str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['PHP_SELF']);
		$plugins = new ClasePlugins('mod_familia',$this->view);
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


    public function buscardescendientes($idfamilia) {
        $resultado = [];
        $descs = $this->descendientes($idfamilia);
        if (isset($descs['datos'])) {
            foreach ($descs['datos'] as $descendiente) {
                $nuevo = $descendiente['idFamilia'];
                $resultado[] = $nuevo;
                $nuevos = $this->buscardescendientes($nuevo);
                foreach ($nuevos as $valor) {
                    $resultado[] = $valor;
                }
            }
        }

        return $resultado;
    }
   
    public function cuentaHijos($padres) {
        // Se puede optimizar con un group by ????
        
        $nuestros = $padres;
        $sql = 'SELECT count(idFamilia) as contador '
                . ' FROM familias as FAM '
                . ' WHERE FAM.familiaPadre = ';
        foreach ($padres as $indice => $padre) {
            $resultado = $this->consulta($sql . $padre['idFamilia']);
            $nuestros[$indice]['hijos'] = $resultado['datos'][0]['contador'];
        }
        return $nuestros;
    }

    public function cuentaProductos($padres) {
        $nuestros = $padres;
        $sql = 'SELECT count(idArticulo) AS contador '
                . 'FROM articulosFamilias where idFamilia=';
        foreach ($padres as $indice => $padre) {
            $resultado = $this->consulta($sql . $padre['idFamilia']);
            $nuestros[$indice]['productos'] = $resultado['datos'][0]['contador'];
        }

        return $nuestros;
    }

    public function leer($idfamilia) {
        $sql = 'SELECT FAM.*'
                . ' FROM familias as FAM '
                . ' WHERE FAM.idFamilia =' . $idfamilia;
        $resultado = $this->consulta($sql);
        $resultado['datos'] = $this->cuentaHijos($resultado['datos']);
        return $resultado;
    }

    public function leerUnPadre($idpadre) {
        $sql = 'SELECT FAM.*, FAMPAD.familiaNombre as nombrepadre '
                . ' FROM familias as FAM '
                . ' LEFT OUTER JOIN familias as FAMPAD'
                . ' ON (FAM.familiaPadre=FAMPAD.idFamilia)'
                . ' WHERE FAM.familiaPadre =' . $idpadre
                . ' ORDER BY FAM.familiaNombre';
        $resultado = $this->consulta($sql);
        return $resultado;
    }

    public function grabar($datos) {
        if (isset($datos['idFamilia']) && $datos['idFamilia'] != 0) {
            return $this->update($datos, ['idFamilia=' . $datos['idFamilia']]);
        } else {
            return $this->insert($datos);
        }
    }

    public function todoslosPadres($orden = '', $addRoot = false) {
        $sql = 'SELECT idFamilia, familiaNombre  FROM familias';
        if ($orden) {
            $sql .= ' ORDER BY ' . $orden;
        }
        $resultado = $this->consulta($sql);
        if ($resultado['datos']) {
            if ($addRoot) {
                array_unshift($resultado['datos'], ['idFamilia' => 0, 'familiaNombre' => 'Raíz: la madre de todas las familias', 'familiaPadre'=>'Raíz: el padre de las familias']);
            }
        }

        return $resultado;
    }

    public function guardarProductoFamilia($idProducto, $idFamilia) {
        $sql = 'INSERT INTO `articulosFamilias`(`idArticulo`, `idFamilia`) VALUES (' . $idProducto . ', ' . $idFamilia . ') ';
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }

    public function buscarPorId($idFamilia) {
        $sql = 'select familiaNombre from familias where idFamilia=' . $idFamilia;
        $resultado = $this->consulta($sql);
        return $resultado;
    }

    public function comprobarRegistro($idProducto, $idFamilia) {
        $sql = 'select idArticulo, idFamilia from articulosFamilias where idFamilia=' . $idFamilia . ' and idArticulo=' . $idProducto;
        $resultado = $this->consulta($sql);
        return $resultado;
    }

    public function descendientes($idfamilia) {
        $ascendientes = ($idfamilia);
        $sql = 'SELECT idFamilia FROM familias where familiaPadre = ' . $idfamilia;
        $resultado = $this->consulta($sql);
        return $resultado;
    }

    public function familiasSinDescendientes($idfamilia, $addRoot = false) {
        // @ Objetivo:
        // Es obtener un array con las familias posibles, menos las que son hijas de esta familia.
        // ya esas familias no pueden ser padres del padre.
        $resultado = $this->buscardescendientes($idfamilia);
        $resultado[] = $idfamilia;
        $descendientes = implode(',', $resultado);
        $sql = 'SELECT idFamilia, familiaNombre FROM familias WHERE idfamilia not IN (' . $descendientes . ')';
        $resultado = $this->consulta($sql);

        if ($resultado['datos']) {
            if ($addRoot) {
                array_unshift($resultado['datos'], ['idFamilia' => 0, 'familiaNombre' => 'Raíz: la madre de todas las familias']);
            }
        }
        return $resultado;
    }

    public function contarProductos($idfamilia) {
        $sql = 'SELECT count(idArticulo) AS contador FROM articulosFamilias where idFamilia=' . $idfamilia;
        $resultado = $this->consulta($sql);
        if ($resultado['datos']) {
            $resultado = $resultado['datos'][0]['contador'];
        }
        return $resultado;
    }

    public function contarHijos($idfamilia) {
        $sql = 'SELECT count(idFamilia) as contador '
                . ' FROM familias as FAM '
                . ' WHERE FAM.familiaPadre = '. $idfamilia;
            $resultado = $this->consulta($sql);
            return $resultado['datos'][0]['contador'];
    }
    
    public function Borrar($idfamilia) {
        $sql = 'DELETE FROM familias '
                . ' WHERE idFamilia = ' . $idfamilia;
            return $this->consultaDML($sql);
    }
    
    public function buscarProductosFamilias($idFamilia) {
        $sql = 'SELECT idArticulo, idFamilia FROM articulosFamilias where idFamilia=' . $idFamilia;
        $resultado = $this->consulta($sql);

        return $resultado;
    }
    public function buscarProductosSinFamilias(){
        $sql='SELECT idArticulo FROM articulos WHERE idArticulo NOT IN (SELECT idArticulo  FROM articulosFamilias)';
        $resultado = $this->consulta($sql);
        return $resultado;
    }
   
    public function addFamiliaTiendaWeb($idTienda, $idFamilia, $idWeb){
        $sql='INSERT INTO `familiasTienda`(`idFamilia`, `idTienda`, `idFamilia_tienda`) 
        VALUES ('.$idFamilia.','.$idTienda.','.$idWeb.')';
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }
   
    public function familiaDeProducto($idProducto){
        $sql='SELECT a.familiaNombre as nombreFamilia FROM `familias` as a inner join  
        articulosFamilias as b on b.idFamilia=a.idFamilia WHERE b.idArticulo='.$idProducto;
        $resultado = $this->consulta($sql);
        return $resultado;
        
    }


    public function regRelacionFamiliaTienda ($idFamilia){
        // @ Objetivo
        // Obtener registros tabla familaTienda donde nos indica la relacion idFamilia con familias otras tiendas.
        // @ Parametros
        //   $idFamilia-> Entero que es el id de Familia a buscar    
        // @ Devuelve:
        //      Array   ( 'datos' -> respuesta
        //                'error' -> array ( el que utilizamos errores para indicar que hay registros tienda repetido)
        //              )
        $resultado = array();
        $sql='SELECT * FROM `familiasTienda` WHERE idFamilia='.$idFamilia;
        $resultado = $this->consulta($sql);
        $r = $this->consulta($sql);
        // Ahora comprobamos que solo hay un registro por tienda.
        if ( isset( $r['datos']) && count($r['datos'])>1){
            // creo array solo con idTienda para luego comprobar si esta repetido.
            $ref_tiendas = array_column($r['datos'],'idTienda');
            // Ahora creamos un array sin duplicados.
            $ref_tiendas_unicas = array_unique($ref_tiendas);
            // Ahora vemos diferencias.
            $dif_ref_tiendas = array_diff_assoc($ref_tiendas,$ref_tiendas_unicas);
            if (count($dif_ref_tiendas) >0 ){
                // Entonces encontro diferencias por lo que alguno esta repetido..
                $resultado['error'][] = array ( 'tipo'=>'danger',
								 'mensaje' =>'La familia '.$idFamilia.' tiene duplicado una relacion en las siguiente tiendas:'.implode(',',$dif_ref_tiendas),
								 'dato' =>$dif_ref_tiendas
								);
            }
            
        }
        return $resultado;
    }
   
    public function obtenerRelacionFamilia ($idTienda, $idFamilia){
        // Objetivo
        // Obtener registro donde nos indica el idFamilia y idFamilia_tienda buscando por idFamilia de tpv.
        //   $idTienda -> Entero que es el id de Tienda que buscamos.
        //   $idFamilia-> Entero que es el id de Familia a buscar    
        // Error posible: Solo puede haber uno, si hay mas es un error [PENDIENTE].
        $sql='SELECT idFamilia,idFamilia_tienda FROM `familiasTienda` WHERE idFamilia='.$idFamilia.' and idTienda='.$idTienda;
        $resultado = $this->consulta($sql);

        return $resultado;
    }

    public function obtenerRelacionFamilia_tienda ($idTienda, $idFamilia_tienda){
        // Objetivo
        // Obtener registro donde nos indica el idFamilia y idFamilia_tienda buscando por idFamilia de tpv.
        //   $idTienda -> Entero que es el id de Tienda que buscamos.
        //   $idFamilia-> Entero que es el id de Familia a buscar    
        // Error posible: Solo puede haber uno, si hay mas es un error [PENDIENTE].
        
        $sql='SELECT idFamilia,idFamilia_tienda FROM `familiasTienda` WHERE idFamilia_tienda='.$idFamilia_tienda.' and idTienda='.$idTienda;
        $resultado = $this->consulta($sql);
        return $resultado;
    }

    public function datosFamilia($idFamilia){
        // @ Objetivo:
        // Obtener un array con todos los datos de esa familia.
        // @ Parametros:
        //      $idFamilia: Id de la familia queremos obtener datos.
        // @ Devuelve:
        //      array(  idFamilia       -> (int) id  
        //              familiaNombre   -> (varchar) Nombre de la familia
        //              familiaPadre    -> (int) id de padre si lo tuviera, sino devuelve 0
        //              beneficiomedio  -> (float) Indica el porcentaje medio de la familia .. puede devolver NULL
        //              nombrePadre     -> (varchar) Nombre del padre.
        //              hijos           -> (array) Los que tiene con datos y sino tiene entonces devuele array vacio
        //              productos       -> (int) Cantidad de productos que tiene esta familia.
        //              familiaTienda   -> (array) Con las relaciones con otras tiendas.
        $datosFamilia = array();
        $datosFamilia['idFamilia']= $idFamilia; 
        $f = $this->leer($idFamilia);
        // habría que controlar si solo devuelve un registro, ya que si devuelve mas.. es un error
        $datosFamilia['familiaNombre'] = $f['datos'][0]['familiaNombre'];
        $datosFamilia['familiaPadre'] = $f['datos'][0]['familiaPadre'];
        $datosFamilia['beneficiomedio'] = $f['datos'][0]['beneficiomedio'];

        $datosFamilia['productos'] = $this->contarProductos($idFamilia);
        $r= $this->regRelacionFamiliaTienda($idFamilia);
        if (isset($r['error'])){
            $datosFamilia['errores'] = $r['error'];
        }
        $datosFamilia['familiaTienda'] = (isset($r['datos'])) ? $r['datos'] : array();

        // Obtenemos array de hijos.
        $t =$this->leerUnPadre($idFamilia);
        
        if (isset($t['datos'])){
            // Si existe hijos
            $datosFamilia['hijos'] = $t['datos'];
            // Añadimos a hijos si cuantos hijos y productos tiene, esto es necesario para saber si se puede eliminar una
            // familia.
            $datosFamilia['hijos'] = $this->cuentaHijos($t['datos']);
            $datosFamilia['hijos'] = $this->cuentaProductos($datosFamilia['hijos']);
            // Ahora añadimos a cada hijo la relacion familia tienda. Es necesario para controlar si tiene tienda web
            foreach ($datosFamilia['hijos'] as $key=>$hijo){
                $rH  =  $this->regRelacionFamiliaTienda($hijo['idFamilia']);
                if (isset($r['error'])){
                    $datosFamilia['errores'] = $r['error'];
                }
                $relacionFamiliaHijo = array( 'familiaTienda' => (isset($rH['datos'])) ? $rH['datos'] : array());
                $datosFamilia['hijos'][$key]= $datosFamilia['hijos'][$key]+$relacionFamiliaHijo;
            }
        }
        return  $datosFamilia;
    }
    
}
