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
        //Objetivo: buscar todos los datos de las tarifas que tiene un cliente
        //Parametros:
        //-idCliente: id del cliente
        $sql = 'SELECT *'
            . 'FROM ' . $tabla
            . ' WHERE id=' . $id;
        return $this->consulta($sql);
    }

    public function leerCliente($idcliente, $filtros = [])
    {
        //Objetivo: leer los descuentos mensuales de un cliente
        //Parametros:
        //-idcliente: id del cliente
        //-idarticulo: id del articulo
        $sql = 'SELECT *'
            . 'FROM ' . $tabla
            . 'WHERE idCliente= '.$idcliente;

        if (count($filtros) > 0) {
            $indice = 0;
            foreach ($filtros as $columna => $valor) {
                $filtro .= ' AND '. $columna . ' = "' . $valor . '"';
            }
        }
        return $this->consulta($sql);
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
