<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';
include_once 'ClaseArticulosStocks.php';

/**
 * Description of ClaseArticulos
 *
 * @author alagoro
 */
class alArticulos extends Modelo { // hereda de clase modelo. Hay una clase articulos que hizo Ricardo & Co.
//    Si no se lee articulo por id, se leen múltiples articulos $pagina o menos
// empezando en $inicio 

    public function leer($idArticulo = 0, $inicio = 1, $pagina = 100) {
        $sql = 'SELECT * '
                . 'FROM articulos ';
        if ($idArticulo != 0) {
            $sql .= ' WHERE idArticulo =' . $idArticulo
                    . ' LIMIT 1';
        } else {
            $sql .= ' LIMIT ' . $inicio . ', ' . $pagina;
        }
        return ModeloP::_consulta($sql);
    }

    public function existe($idArticulo) {
        $sql = 'SELECT COUNT(idArticulo) AS contador '
                . 'FROM articulos '
                . ' WHERE idArticulo =' . $idArticulo
                . ' LIMIT 1';

        $resultado = $this->consulta($sql);
        return $resultado['datos'][0]['contador'] == 1;
    }

    public function contar() {
        $sql = 'SELECT COUNT(idArticulo) AS contador '
                . 'FROM articulos ';
        $resultado = $this->consulta($sql);
        return $resultado['datos'][0]['contador'];
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

    public function calculaMayor($parametros) {
        // Campo tipodoc indica si es (C) Compra ,(T) Ticket o (V) albaran de venta 
        $sqlprepare = [];
        $sqlprepare['sqlAlbcli'] = 'SELECT alb.fecha'
                . ', "0" as entrega'
                . ', "0" as precioentrada'
                . ', linalb.nunidades as salida'
                . ', linalb.precioCiva as preciosalida'
                . ', "V" as tipodoc '
                . ', " " as serie'
                . ', alb.Numalbcli as numdocu '
                . ', alb.Numalbcli as numid'
                . ', cli.Nombre as nombre'
                . ', alb.estado as estado'
                . ' FROM albclit as alb '
                . ' JOIN albclilinea as linalb ON (alb.id=linalb.idalbcli) '
                . ' JOIN clientes as cli ON (alb.idCliente = cli.idClientes) '
                . ' WHERE DATE(alb.Fecha) >= "' . $parametros['fecha_inicial'] . '"'
                . ' AND DATE(alb.Fecha) <= "' . $parametros['fecha_final'] . '"'
                . ' AND linalb.idArticulo = ' . $parametros['idArticulo'];

        $sqlprepare['sqlTiccli'] = 'SELECT tic.fecha'
                . ', "0" as entrega'
                . ', "0" as precioentrada'
                . ', lintic.nunidades as salida'
                . ', lintic.precioCiva as preciosalida'
                . ', "T" as tipodoc '
                . ', CONCAT("T", tic.idTienda,"-",tic.idUsuario,"-") as serie '
                . ', tic.Numticket as numdocu '
                . ', tic.id as numid'
                . ', cli.Nombre as nombre'
                . ', tic.estado as estado'
                . ' FROM ticketst as tic '
                . ' JOIN ticketslinea as lintic ON (tic.id=lintic.idticketst) '
                . ' JOIN clientes as cli ON (tic.idCliente = cli.idClientes) '
                . ' WHERE DATE(tic.Fecha) >= "' . $parametros['fecha_inicial'] . '"'
                . ' AND DATE(tic.Fecha) <= "' . $parametros['fecha_final'] . '"'
                . ' AND lintic.idArticulo = ' . $parametros['idArticulo']
                . ' AND lintic.estadoLinea = "Activo"';

        $sqlprepare['sqlAlbpro'] = 'SELECT alb.fecha'
                . ', linalb.nunidades as entrega'
                . ', linalb.costeSiva as precioentrada'
                . ', "0" as salida'
                . ', "0" as preciosalida'
                . ', "C" as tipodoc '
                . ', " " as serie '
                . ', alb.Numalbpro as numdocu '
                . ', alb.Numalbpro as numid'
                . ', pro.razonsocial as nombre'
                . ', alb.estado as estado'
                . ' FROM albprot as alb '
                . ' JOIN albprolinea as linalb ON (alb.id=linalb.idalbpro) '
                . ' JOIN proveedores as pro ON (alb.idProveedor = pro.idProveedor) '
                . ' WHERE DATE(alb.Fecha) >= "' . $parametros['fecha_inicial'] . '"'
                . ' AND DATE(alb.Fecha) <= "' . $parametros['fecha_final'] . '"'
                . ' AND linalb.idArticulo = ' . $parametros['idArticulo'];
        $sql = implode(' UNION ALL ', $sqlprepare);
        $sql .= ' ORDER BY fecha ';
        $datos = $this->consulta($sql);
        return $datos;
    }

    public function calculaStock($idArticulo, $idTienda = 1) {
        $ventas = 0;
        $sql = 'SELECT SUM( linalb.nunidades) as ventas'
                . ' FROM albclit as alb '
                . ' JOIN albclilinea as linalb ON (alb.id=linalb.idalbcli) '
                . ' WHERE linalb.idArticulo = ' . $idArticulo
                . ' AND alb.idTienda = ' . $idTienda;
        $sqldata = $this->consulta($sql);

        if ($sqldata) {
            $ventasalbc = $sqldata['datos'][0]['ventas'];
        }

        $sql = 'SELECT SUM( lintic.nunidades) as ventas'
                . ' FROM ticketst as tic '
                . ' JOIN ticketslinea as lintic ON (tic.id=lintic.idticketst) '
                . ' WHERE lintic.idArticulo = ' . $idArticulo
                . ' AND lintic.estadoLinea = "Activo"'
                . ' AND tic.idTienda = ' . $idTienda;
        $sqldata = $this->consulta($sql);
        if ($sqldata) {
            $ventastick = $sqldata['datos'][0]['ventas'];
        }

        $sql = 'SELECT SUM( linalb.nunidades ) as compras'
                . ' FROM albprot as alb '
                . ' JOIN albprolinea as linalb ON (alb.id=linalb.idalbpro) '
                . ' WHERE linalb.idArticulo = ' . $idArticulo
                . ' AND alb.idTienda = ' . $idTienda;
        $sqldata = $this->consulta($sql);
        if ($sqldata) {
            $comprasalbp = $sqldata['datos'][0]['compras'];
        }

        $stock = $comprasalbp - $ventasalbc - $ventastick;

//        $sql = implode(' UNION ', $sqlprepare);
        return $stock;
    }

    public function getStock($idArticulo, $idTienda = 1) {
        return alArticulosStocks::leer($idArticulo, $idTienda, TRUE); //si no existe lo crea
    }

    public static function leerArticulosXFamilia($idfamilia) {
        $sql = 'SELECT art.idArticulo, art.articulo_name  '
                . ' FROM articulos as art '
                . ' JOIN articulosFamilias as artfam ON (art.idArticulo=artfam.idArticulo) '
                . ' WHERE artfam.idFamilia = ' . $idfamilia;

        $sqldata = self::_consulta($sql);

        return $sqldata;
    }

    public static function existeArticuloFamilia($idarticulo, $idfamilia) {
        $sql = 'SELECT count(idArticulo) as contador '
                . ' FROM articulosFamilias  '
                . ' WHERE idFamilia = ' . $idfamilia . ' AND idArticulo=' . $idarticulo;

        $sqldata = self::_consulta($sql);
        
        if ($sqldata) {
            $resultado = $sqldata[0]['contador'];
        } else {
            $resultado = -1;
        }
        
        return $resultado;
    }

    public static function grabarArticuloFamilia($idarticulo, $idfamilia) {
        $resultado = parent::_insert('articulosFamilias', ['idFamilia => ' . $idfamilia, 'idArticulo=>' . $idarticulo]);

        return $resultado;
    }

    public static function borrarArticuloFamilia($idarticulo, $idfamilia) {
        $resultado = parent::_delete('articulosFamilias', ['idFamilia = ' . $idfamilia, 'idArticulo=' . $idarticulo]);

        return $resultado;
    }

}
