<?php

/*
 * @Copyright 2018, Alagoro Software.
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. informatica arroba alagoro punto com
 * @Descripción
 */
//include_once './../../inicial.php';

include_once $URLCom . '/modulos/claseModelo.php';

class ClaseDescuentosTicket extends modelo
{

    protected $tabla = 'descuentos_tickets';

    public function leer($id)
    {
        return parent::_leer('id='.$id);
    }

    public function leerCliente($idcliente, $filtros = [])
    {
        //Objetivo: leer los descuentos mensuales de un cliente
        //Parametros:
        //-idcliente: id del cliente

        $filtros[]='idCliente='.$idcliente;
        return $this->_leer($this->tabla, $filtros); //, [], [], 0, 0, true);
        //return $this->getSQLConsulta();

    }


    public function update($datos, $condicion, $soloSQL = false)
    {
        return parent::update($datos, $condicion, $soloSQL);
    }

    public function insert($datos, $soloSQL = false)
    {
        return parent::insert($datos, $soloSQL);
    }

}
