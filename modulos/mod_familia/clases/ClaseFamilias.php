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
		// Solo realizamos asignamos 
		//~ if (gettype($conexion) === 'object'){
			//~ parent::__construct($conexion);
			//~ $this->idTienda = parent::GetIdTienda();
		//~ }
		$this->view = str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['PHP_SELF']);
		$plugins = new ClasePlugins('mod_familia',$this->view);
		$this->plugins = $plugins->GetParametrosPlugins();
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
    public function buscarIdTiendaFamilia($idTienda, $idFamilia){
        $sql='SELECT  idFamilia_tienda FROM familiasTienda where idFamilia='.$idFamilia.' and idTienda='.$idTienda;
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
    
    public function comprobarPadreWeb($idTienda, $idPadre){
        $sql='SELECT idFamilia_tienda FROM `familiasTienda` WHERE idFamilia='.$idPadre.' and idTienda='.$idTienda;
        $resultado = $this->consulta($sql);
        return $resultado;
    }
    public function familiaDeProducto($idProducto){
        $sql='SELECT a.familiaNombre as nombreFamilia FROM `familias` as a inner join  
        articulosFamilias as b on b.idFamilia=a.idFamilia WHERE b.idArticulo='.$idProducto;
        $resultado = $this->consulta($sql);
        return $resultado;
        
    }
}
