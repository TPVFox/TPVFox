<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once '/var/www/html/tpvfox/modulos/claseModelo.php';

class TarifaCliente extends modelo {

    public function leer($idcliente) {
        $sql = 'SELECT artcli.*'
                . ', art.iva as ivaArticulo '
                . ', art.articulo_name as descripcion '
                . 'FROM articulosClientes AS artcli'
                . ' LEFT OUTER JOIN articulos AS art ON (artcli.idArticulo = art.idArticulo)'
                . ' WHERE artcli.idClientes=' . $idcliente
                . ' ORDER BY artcli.fechaActualizacion, art.articulo_name';
        return $this->consulta($sql);
    }

    public function existeArticulo($idcliente, $idarticulo) {
        $sql = 'SELECT artcli.estado '
                . 'FROM articulosClientes AS artcli'
                . ' WHERE artcli.idClientes=' . $idcliente
                . ' AND artcli.idArticulo =' . $idarticulo;

        $consulta = $this->consulta($sql);
        if (isset($consulta['error'])) {
            $resultado = -1;
        } else {

            $resultado = count($consulta['datos']);
        }
        return [$resultado,$consulta['consulta']];
    }

    public function update($idcliente, $idarticulo, $modificaciones) {

        $updateStr = [];
        if (is_array($modificaciones)) {
            foreach ($modificaciones as $key => $value) {
                $updateStr[] = $key . ' = ' . $value;
            }
        } else {
            $updateStr[] = $modificaciones;
        }
        $updateString = implode(', ', $updateStr);

        $sql = 'UPDATE articulosClientes '
                . ' SET ' . $updateString
                . ' WHERE idArticulo= ' . $idarticulo . ' AND idClientes= ' . $idcliente;

        $consulta = $this->consultaDML($sql);

        return $consulta['error'];
    }

    public function insert($idcliente, $idarticulo, $datos) {

        $updateStr = [];
        $updateStr[] = ' idArticulo= ' . $idarticulo;
        $updateStr[] = ' idClientes= ' . $idcliente;
        if (is_array($datos)) {
            foreach (datos as $key => $value) {
                $updateStr[] = $key . ' = ' . $value;
            }
        } else {
            $updateStr[] = $datos;
        }
        $updateString = implode(', ', $updateStr);

        $sql = 'INSERT articulosClientes '
                . ' SET ' . $updateString;

        $consulta = $this->consultaDML($sql);

        return $consulta['error'];
    }

    public function replace($idcliente, $idarticulo, $datos) {

        $updateStr = [];
        $updateStr[] = ' idArticulo= ' . $idarticulo;
        $updateStr[] = ' idClientes= ' . $idcliente;
        if (is_array($datos)) {
            foreach (datos as $key => $value) {
                $updateStr[] = $key . ' = ' . $value;
            }
        } else {
            $updateStr[] = $datos;
        }
        $updateString = implode(', ', $updateStr);

        $sql = 'REPLACE articulosClientes '
                . ' SET ' . $updateString;

        $consulta = $this->consultaDML($sql);

        return $consulta['error'];
    }

}
