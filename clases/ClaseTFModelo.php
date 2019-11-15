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
class TFModelo extends ModeloP {

    protected $tabla;

    protected function consulta($sql) {
        // Realizamos la consulta.
        $smt = parent::consulta($sql);
        $respuesta = [];
        $respuesta['consulta'] = $this->getSQLConsulta();
        if ($smt) {
            $respuesta['datos'] = $smt;
        } else {
            if ($this->getErrorConsulta() != '0') {
                $respuesta['error'] = $this->getErrorConsulta();
            }
        }
        return $respuesta;
    }

    protected function consultaDML($sql) {
        // Realizamos la consulta.
        // Aquí la diferencia que hay con el anterior modelo es que no
        // devuelve error , si no hay.
        $smt = parent::consultaDML($sql);
        $respuesta = [];
        $respuesta['consulta'] = $sql;
        if ($this->getErrorConsulta() != '0') {
                $respuesta['error'] = $this->getErrorConsulta();
        }
        return $respuesta;
    }

    protected function insert($datos, $soloSQL = false) {

        parent::_insert($this->tabla, $datos, $soloSQL);

        return $this->getSQLConsulta();
    }

    protected function update($datos, $condicion, $soloSQL = false) {
        parent::_update($this->tabla, $datos, $condicion, $soloSQL);

        return $this->getSQLConsulta();
    }

}
