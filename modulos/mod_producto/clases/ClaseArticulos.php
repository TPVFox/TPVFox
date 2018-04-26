<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once $RutaServidor.$HostNombre.'/modulos/claseModelo.php';

/**
 * Description of ClaseArticulos
 *
 * @author alagoro
 */
class Articulos extends Modelo {

    public function leer($idArticulo) {
        $sql = 'SELECT *'
                . 'FROM articulos '
                . ' WHERE idArticulo =' . $idArticulo
                . ' LIMIT 1';
        return $this->consulta($sql);               
    }

    public function leerPrecio($idArticulo,$idTienda=1) {
        $sql = 'SELECT pre.* '
                . ', art.iva as ivaArticulo '
                . ', art.articulo_name as descripcion '
                . 'FROM articulosPrecios AS pre '
                . ' LEFT OUTER JOIN articulos AS art ON (art.idArticulo=pre.idArticulo) '
                . ' WHERE pre.idArticulo =' . $idArticulo
                . ' AND pre.idTienda= '.$idTienda 
                . ' LIMIT 1';
        return $this->consulta($sql);               
    }
}
