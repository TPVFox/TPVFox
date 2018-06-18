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

    protected $tabla = 'articulosClientes';
    
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

}
