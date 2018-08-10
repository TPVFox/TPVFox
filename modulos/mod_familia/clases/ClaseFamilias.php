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
class ClaseFamilias extends Modelo {

    protected $tabla = 'familias';

    public function buscardescendientes($idfamilia) {
        $resultado = [];
        $descs = $this->descendientes($idfamilia);
        if (isset($descs['datos'])) {
            foreach ($descs['datos'] as $descendiente) {
                $nuevo = $descendiente['idFamilia'];
                $resultado[] = $nuevo;
                $nuevos = $this->buscardescendientes($nuevo);
//            if ($nuevos ) {
                foreach ($nuevos as $valor) {
                    $resultado[] = $valor;
                }
//            }
            }
        }
//        return implode(', ',$resultado). count($nuevos>0)? implode(', ',$nuevos):'';
        return $resultado;
    }

    public function cuentaHijos($padres) {        
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
//        $resultado['datos'] = $this->cuentaHijos($resultado['datos']);
        return $resultado;
    }

    public function buscaXNombre($nombre) {
        $sql = 'SELECT FAM.* '
                . ' FROM familias as FAM '
                . ' WHERE FAM.familiaNombre LIKE "%' . $nombre . '%"'
                . ' ORDER BY FAM.familiaNombre';
        $resultado = $this->consulta($sql);
        return $resultado;
    }

    public function grabar($datos) {
        return $this->insert($datos);
    }

    public function actualizarpadre($idpadre, $idfamilias) {
        $datos = [];
        foreach ($idfamilias as $idfamilia) {
            $datos[] = $this->update(['familiaPadre' => $idpadre], ['idFamilia=' . $idfamilia]);
        }

        return $datos;
    }

    public function todoslosPadres($orden = '', $addRoot = false) {
        $sql = 'SELECT idFamilia, familiaNombre FROM familias';
        if ($orden) {
            $sql .= ' ORDER BY ' . $orden;
        }
        $resultado = $this->consulta($sql);
        if ($resultado['datos']) {
            if ($addRoot) {
                array_unshift($resultado['datos'], ['idFamilia' => 0, 'familiaNombre' => 'Raíz: la madre de todas las familias']);
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

    public function contarProductos($idfamilia){
        $sql = 'SELECT count(idArticulo) AS contador FROM articulosFamilias where idFamilia=' . $idfamilia; 
        $resultado = $this->consulta($sql);
        if($resultado['datos']){
            $resultado = $resultado['datos'][0]['contador'];
        }
        return $resultado;
    }
    
}
