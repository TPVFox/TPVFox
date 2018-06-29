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

    protected function cuentaHijos($padres) {
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
        $resultado['datos'] = $this->cuentaHijos($resultado['datos']);
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
    public function todoslosPadres(){
        $sql = 'SELECT idFamilia, familiaNombre FROM familias';
        $resultado = $this->consulta($sql);
        return $resultado;
    }

}
