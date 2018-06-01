<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

require_once $RutaServidor . $HostNombre . '/modulos/claseModeloP.php';

/**
 * Description of claseModelo
 *
 * @author alagoro
 */
class Modelo extends ModeloP {

    protected function consulta($sql) {
        // Realizamos la consulta.
        $smt = parent::consulta($sql);
        $respuesta = [];
        $respuesta['consulta'] = $this->getSQLConsulta();
        if ($smt) {
            $respuesta['datos'] = $smt;
        } else {
            $respuesta['error'] = $this->getErrorConsulta();
        }
        return $respuesta;
    }

    protected function consultaDML($sql) {
        // Realizamos la consulta.
        $smt = parent::consultaDML($sql);
        $respuesta = [];
        $respuesta['consulta'] = $sql;

        $respuesta['error'] = $smt ? '0' : $this->getErrorConsulta();

        return $respuesta;
    }

    protected function insert($datos, $soloSQL = false) {

        parent::insert($datos, $soloSQL);

        return $this->getSQLConsulta();
    }

    protected function update($datos, $condicion, $soloSQL = false) {
        parent::update($datos, $condicion, $soloSQL);

        return $this->getSQLConsulta();
    }

}
