<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */
//include_once './../../inicial.php';

include_once $RutaServidor.$HostNombre.'/modulos/claseModelo.php';


class TarifaCliente extends modelo {

    public function leer($idcliente) {
		//Objetivo: buscar todos los datos de las tarifas que tiene un cliente
		//Parametros: 
		//-idCliente: id del cliente
        $sql = 'SELECT artcli.*'
                . ', art.iva as ivaArticulo '
                . ', art.articulo_name as descripcion '
                . 'FROM articulosClientes AS artcli'
                . ' LEFT OUTER JOIN articulos AS art ON (artcli.idArticulo = art.idArticulo)'
                . ' WHERE artcli.idClientes=' . $idcliente .' AND artcli.estado= '.K_TARIFACLIENTE_ESTADO_ACTIVO
                . ' ORDER BY art.articulo_name ASC';
        return $this->consulta($sql);
    }

    public function leerPrecio($idcliente, $idarticulo) {
		//Objetivo: leer los datos de un articulo de las tarifas de los clientes
		//Parametros:
		//-idcliente: id del cliente
		//-idarticulo: id del articulo
        $sql = 'SELECT artcli.*'
                . ', art.iva as ivaArticulo '
                . ', art.articulo_name as descripcion '
                . 'FROM articulosClientes AS artcli'
                . ' LEFT OUTER JOIN articulos AS art ON (artcli.idArticulo = art.idArticulo)'
                . ' WHERE artcli.idClientes=' . $idcliente .' AND artcli.idArticulo=' . $idarticulo
                . ' LIMIT 1 ';
        return $this->consulta($sql);
    }

    public function existeArticulo($idcliente, $idarticulo) {
		//Objetivo: comprobas que un cliente tiene tarifa con un articulo determinado
		//Parametros:
		//-idcliente: id del cliente
		//-idarticulo: id del articulo
        $sql = 'SELECT count(*) as contador '
                . 'FROM articulosClientes AS artcli'
                . ' WHERE artcli.idClientes=' . $idcliente
                . ' AND artcli.idArticulo =' . $idarticulo;

        $consulta = $this->consulta($sql);
        if (isset($consulta['error'])) {
            $resultado = -1;
        } else {
            $obj = $consulta['datos'];
            $resultado = $obj[0]['contador'];
        }
        return $resultado;
    }

    public function update($idcliente, $idarticulo, $modificaciones) {
		//@Objetivo: modificar los datos de una tarifa
		//@Parametros: 
		//idcliente: id del cliente
		//idarticulo: id del articulo al que se le va a modificar la tarifa
		//modificaciones: array con las modificaciones a la tarifa

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

        return $consulta; //['error'];
    }

    public function insert($datos) {
		//@Objetivo: insertar una nueva tarifa
		//@Parametros:
		//Datos: array de datos, con todos los datos de la tarifa nueva

        $updateStr = [];
        if (is_array($datos)) {
            foreach ($datos as $key => $value) {
                $updateStr[] = $key . ' = ' . $value;
            }
        } else {
            $updateStr[] = $datos;
        }
        $updateString = implode(', ', $updateStr);

        $sql = 'INSERT articulosClientes '
                . ' SET ' . $updateString;

        $consulta = $this->consultaDML($sql);

        return $consulta['consulta'];
    }

    public function replace($idcliente, $idarticulo, $datos) {
//queda esto por comentar
        $updateStr = [];
        $updateStr[] = ' idArticulo= ' . $idarticulo;
        $updateStr[] = ' idClientes= ' . $idcliente;
        if (is_array($datos)) {
            foreach ($datos as $key => $value) {
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
