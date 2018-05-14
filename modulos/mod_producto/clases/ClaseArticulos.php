<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';

/**
 * Description of ClaseArticulos
 *
 * @author alagoro
 */
class alArticulos extends Modelo { // hereda de clase modelo. Hay una clase articulos que hizo Ricardo & Co.

    public function leer($idArticulo) {
        $sql = 'SELECT *'
                . 'FROM articulos '
                . ' WHERE idArticulo =' . $idArticulo
                . ' LIMIT 1';
        return $this->consulta($sql);
    }

    public function existe($idArticulo) {
        $sql = 'SELECT COUNT(idArticulo) AS contador '
                . 'FROM articulos '
                . ' WHERE idArticulo =' . $idArticulo
                . ' LIMIT 1';

        $resultado = $this->consulta($sql);
        return $resultado['datos'][0]['contador']==1;
    }

    public function leerPrecio($idArticulo, $idTienda = 1) {
        $sql = 'SELECT pre.* '
                . ', art.iva as ivaArticulo '
                . ', art.articulo_name as descripcion '
                . 'FROM articulosPrecios AS pre '
                . ' LEFT OUTER JOIN articulos AS art ON (art.idArticulo=pre.idArticulo) '
                . ' WHERE pre.idArticulo =' . $idArticulo
                . ' AND pre.idTienda= ' . $idTienda
                . ' LIMIT 1';
        return $this->consulta($sql);
    }

    public function leerXCodBarras($codbarras, $idTienda = 1) {
        $sql = 'SELECT art.*, artcb.codBarras, artti.crefTienda as referencia '
                . 'FROM articulos AS art '
                . ' LEFT OUTER JOIN articulosCodigoBarras AS artcb ON (art.idArticulo=artcb.idArticulo) '
                . ' LEFT OUTER JOIN articulosTiendas AS artti ON (art.idArticulo=artti.idArticulo) '
                . ' WHERE artcb.codBarras =\'' . $codbarras . '\''
                . ' AND artti.idTienda= ' . $idTienda;
        return $this->consulta($sql);
    }

    public function contarLikeCodBarras($codbarras, $idTienda = 1) {
        $sql = 'SELECT count(art.idArticulo) as contador '
                . 'FROM articulos AS art '
                . ' LEFT OUTER JOIN articulosCodigoBarras AS artcb ON (art.idArticulo=artcb.idArticulo) '
                . ' LEFT OUTER JOIN articulosTiendas AS artti ON (art.idArticulo=artti.idArticulo) '
                . ' WHERE artcb.codBarras LIKE \'%' . $codbarras . '%\''
                . ' AND artti.idTienda= ' . $idTienda;
        $consulta = $this->consulta($sql);
        $resultado = false;
        if ($consulta['datos']) {
            $resultado = $consulta['datos'][0]['contador'];
        }
        return $resultado;
    }

    public function leerLikeCodBarras($codbarras, $pagina = 0, $idTienda = 1) {
        $sql = 'SELECT art.*, artcb.codBarras, artti.crefTienda as referencia '
                . 'FROM articulos AS art '
                . ' LEFT OUTER JOIN articulosCodigoBarras AS artcb ON (art.idArticulo=artcb.idArticulo) '
                . ' LEFT OUTER JOIN articulosTiendas AS artti ON (art.idArticulo=artti.idArticulo) '
                . ' WHERE artcb.codBarras LIKE \'%' . $codbarras . '%\''
                . ' AND artti.idTienda= ' . $idTienda;
        if ($pagina !== 0) {
            $inicio = (($pagina - 1) * ARTICULOS_MAXLINPAG) + 1;
            $sql .= ' LIMIT ' . $inicio . ', ' . ARTICULOS_MAXLINPAG;
        }
        return $this->consulta($sql);
    }

    public function leerXReferencia($referencia, $idTienda = 1) {
        $sql = 'SELECT art.*, artcb.codBarras, artti.crefTienda as referencia '
                . 'FROM articulos AS art '
                . ' LEFT OUTER JOIN articulosCodigoBarras AS artcb ON (art.idArticulo=artcb.idArticulo) '
                . ' LEFT OUTER JOIN articulosTiendas AS artti ON (art.idArticulo=artti.idArticulo) '
                . ' WHERE artti.crefTienda =\'' . $referencia . '\''
                . ' AND artti.idTienda= ' . $idTienda;
        return $this->consulta($sql);
    }

    public function contarLikeReferencia($referencia, $idTienda = 1) {
        $sql = 'SELECT count(art.idArticulo) as contador '
                . 'FROM articulos AS art '
                . ' LEFT OUTER JOIN articulosCodigoBarras AS artcb ON (art.idArticulo=artcb.idArticulo) '
                . ' LEFT OUTER JOIN articulosTiendas AS artti ON (art.idArticulo=artti.idArticulo) '
                . ' WHERE artti.crefTienda LIKE \'%' . $referencia . '%\''
                . ' AND artti.idTienda= ' . $idTienda;
        $consulta = $this->consulta($sql);
        $resultado = false;
        if ($consulta['datos']) {
            $resultado = $consulta['datos'][0]['contador'];
        }
        return $resultado;
    }

    public function leerLikeReferencia($referencia, $pagina = 0, $idTienda = 1) {
        $sql = 'SELECT art.*, artcb.codBarras, artti.crefTienda as referencia '
                . 'FROM articulos AS art '
                . ' LEFT OUTER JOIN articulosCodigoBarras AS artcb ON (art.idArticulo=artcb.idArticulo) '
                . ' LEFT OUTER JOIN articulosTiendas AS artti ON (art.idArticulo=artti.idArticulo) '
                . ' WHERE artti.crefTienda LIKE \'%' . $referencia . '%\''
                . ' AND artti.idTienda= ' . $idTienda;
        if ($pagina !== 0) {
            $inicio = (($pagina - 1) * ARTICULOS_MAXLINPAG) + 1;
            $sql .= ' LIMIT ' . $inicio . ', ' . ARTICULOS_MAXLINPAG;
        }
        return $this->consulta($sql);
    }

    public function contarLikeDescripcion($descripcion) {
        $sql = 'SELECT count(art.idArticulo) as contador '
                . 'FROM articulos AS art '
                . ' WHERE art.articulo_name LIKE \'%' . $descripcion . '%\'';
        $consulta = $this->consulta($sql);
        $resultado = false;
        if ($consulta['datos']) {
            $resultado = $consulta['datos'][0]['contador'];
        }
        return $resultado;
    }

    public function leerLikeDescripcion($descripcion, $pagina = 0, $idTienda = 1) {
        $sql = 'SELECT art.*, artcb.codBarras, artti.crefTienda as referencia '
                . 'FROM articulos AS art '
                . ' LEFT OUTER JOIN articulosCodigoBarras AS artcb ON (art.idArticulo=artcb.idArticulo) '
                . ' LEFT OUTER JOIN articulosTiendas AS artti ON (art.idArticulo=artti.idArticulo) '
                . ' WHERE art.articulo_name LIKE \'%' . $descripcion . '%\''
                . ' AND artti.idTienda= ' . $idTienda;
        if ($pagina !== 0) {
            $inicio = (($pagina - 1) * ARTICULOS_MAXLINPAG) + 1;
            $sql .= ' LIMIT ' . $inicio . ', ' . ARTICULOS_MAXLINPAG;
        }
        return $this->consulta($sql);
    }

    public function calculaMayor($parametros){
        $sqlprepare = [];
        $sqlprepare['sqlAlbcli'] = 'SELECT alb.fecha'
                . ', "0" as entrega'
                . ', "0" as precioentrada'
                . ', linalb.nunidades as salida'
                . ', linalb.precioCiva as preciosalida'
                . ', " " as tipodoc '
                . ', alb.Numalbcli as numdocu '
                . ', cli.Nombre as nombre'
                . ' FROM albclit as alb '
                . ' JOIN albclilinea as linalb ON (alb.id=linalb.idalbcli) '
                . ' JOIN clientes as cli ON (alb.idCliente = cli.idClientes) '
                . ' WHERE alb.Fecha >= "'.$parametros['fechainicio'].'"'
                . ' AND alb.Fecha <= "'.$parametros['fechafinal'].'"'
                . ' AND linalb.idArticulo = '.$parametros['idArticulo'];
        
        $sqlprepare['sqlTiccli'] = 'SELECT tic.fecha'
                . ', "0" as entrega'
                . ', "0" as precioentrada'
                . ', lintic.nunidades as salida'
                . ', lintic.precioCiva as preciosalida'
                . ', "T" as tipodoc '
                . ', tic.Numticket as numdocu '
                . ', cli.Nombre as nombre'
                . ' FROM ticketst as tic '
                . ' JOIN ticketslinea as lintic ON (tic.id=lintic.idticketst) '
                . ' JOIN clientes as cli ON (tic.idCliente = cli.idClientes) '
                . ' WHERE tic.Fecha >= "'.$parametros['fechainicio'].'"'
                . ' AND tic.Fecha <= "'.$parametros['fechafinal'].'"'
                . ' AND lintic.idArticulo = '.$parametros['idArticulo'];

        $sqlprepare['sqlAlbpro'] = 'SELECT alb.fecha'
                . ', linalb.nunidades as entrega'
                . ', linalb.costeSiva as precioentrada'
                . ', "0" as salida'
                . ', "0" as preciosalida'
                . ', " " as tipodoc '
                . ', alb.Numalbpro as numdocu '
                . ', pro.razonsocial as nombre'
                . ' FROM albprot as alb '
                . ' JOIN albprolinea as linalb ON (alb.id=linalb.idalbpro) '
                . ' JOIN proveedores as pro ON (alb.idProveedor = pro.idProveedor) '
                . ' WHERE alb.Fecha >= "'.$parametros['fechainicio'].'"'
                . ' AND alb.Fecha <= "'.$parametros['fechafinal'].'"'
                . ' AND linalb.idArticulo = '.$parametros['idArticulo'];
        $sql = implode(' UNION ', $sqlprepare);
        $sql .= ' ORDER BY fecha ';
        $sqldata = $this->consulta($sql);
        return $sqldata;
                
    }
    
    
}
