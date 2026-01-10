<?php

include_once $RutaServidor . $HostNombre . '/clases/ClaseTFModelo.php';
require_once($RutaServidor . $HostNombre . '/plugins/plugins.php');
include_once $URLCom . '/modulos/mod_usuario/clases/claseUsuarios.php';

class ClaseReorganizar extends TFModelo
{
    public $plugins; // (array) de objectos que son los plugins que vamos tener para este modulo.
    public $idTiendaWeb = 0;
    public function __construct()
    {
        // Cargamos plugin si hay para este modulo.
        $this->view = str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['PHP_SELF']);
        $plugins = new ClasePlugins('mod_reorganizacion', $this->view);
        $this->plugins = $plugins->GetParametrosPlugins();
    }
    public function SetPlugin($nombre_plugin)
    {
        // @ Objetivo
        // Devolver el Object del plugin en cuestion.
        // @ nombre_plugin -> (string) Es el nombre del plugin que hay parametros de este.
        // Devuelve:
        // Puede devolcer Objeto  o boreano false.
        $Obj = false;
        if (count($this->plugins) > 0) {
            foreach ($this->plugins as $plugin) {
                if ($plugin['datos_generales']['nombre_fichero_clase'] === $nombre_plugin) {
                    $Obj = $plugin['clase'];
                }
            }
        }
        return $Obj;
    }

    public function contar($tipo = '')
    {
        // Contar articulos de web o de tpv
        if ($tipo === 'web') {
            $tabla = ' articulosTiendas where idTienda=' . $this->idTiendaWeb; // espacios es importante.
        } else {
            $tabla = ' articulos ';
        }
        $sql = 'SELECT COUNT(idArticulo) AS contador '
            . 'FROM' . $tabla;
        $resultado = $this->consulta($sql);
        return $resultado['datos'][0]['contador'];
    }

    public function setIdTiendaWeb($id)
    {
        $this->idTiendaWeb = $id;
    }

    public function obtenerIdsWeb($inicio, $cantidad)
    {

        $sql = 'SELECT T.idArticulo, T.idVirtuemart, (' .
            'SELECT IFNULL( stockOn, 0 ) ' .
            'FROM articulosStocks ' .
            'WHERE T.idArticulo = idArticulo ' .
            ') as stockOn, ( ' .
            'SELECT pvpSiva ' .
            'FROM `articulosPrecios` ' .
            'WHERE T.idArticulo = idArticulo ) as pvpSiva ' .
            'FROM `articulosTiendas` AS T ' .
            'WHERE T.idTienda = ' . $this->idTiendaWeb .
            ' LIMIT ' . $inicio . ',' . $cantidad;

        $resultado = $this->consulta($sql);
        return $resultado;
    }
    public function obtenerUsuarios()
    {
        // @ Objetivo:
        // Obtener todos los usuarios
        $BDTpv = parent::getDbo();
        $CUsuario = new ClaseUsuarios($BDTpv);
        $usuarios = $CUsuario->todosUsuarios();
        $respuesta = array();
        $respuesta = $usuarios['datos']; // array con los datos usuario.
        return $respuesta;
    }

    public function contarFamilias($idFamilia = '')
    {
        $sql = 'SELECT
                    DISTINCT v.idN1
                FROM articulosStocks s
                JOIN articulosFamilias f ON s.idArticulo = f.idArticulo
                JOIN vw_jerarquias_familias v ON v.idFamilia = f.idFamilia
                WHERE v.idN1 <> 458
                AND s.stockOn > 0
                AND s.idTienda = 1;';
        $resultado = $this->consulta($sql);
        // Devolver array de los ids familias
        $familias = array();
        foreach ($resultado['datos'] as $fila) {
            $familias[] = $fila['idN1'];
        }
        return $familias;
    }

    public function contarSubfamilias($idFamilia = '')
    {
        $sql = 'SELECT
                    v.idN2,
                    COUNT(*) AS total_articulos
                FROM articulosStocks s
                JOIN articulosFamilias f ON s.idArticulo = f.idArticulo
                JOIN vw_jerarquias_familias v ON v.idFamilia = f.idFamilia
                WHERE v.idN1 = ' . $idFamilia . '
                AND s.stockOn > 0
                AND s.idTienda = 1
                GROUP BY v.idN2
                ORDER BY total_articulos DESC;';
        $resultado = $this->consulta($sql);
        // Devolver el array de subfamilias y total articulos
        $subfamilias = array();
        //Si hay resultados
        if (isset($resultado['datos']) && count($resultado['datos']) > 0) {
            foreach ($resultado['datos'] as $fila) {
                $subfamilias[] = array(
                    'idN2' => $fila['idN2'],
                    'total_articulos' => $fila['total_articulos']
                );
            }
        }
        return $subfamilias;
    }

    public function obtenerProductosPorFamilia($idFamilia, $subfamiliasProcesar = array())
    {
        $filtroSubfamilias = '';
        if (count($subfamiliasProcesar) > 0) {
            $filtroSubfamilias = ' AND v.idN2 NOT IN (' . implode(',', $subfamiliasProcesar) . ') ';
        }
        $sql = 'SELECT
                    s.idArticulo,
                    s.stockOn
                FROM articulosStocks s
                JOIN articulosFamilias f ON s.idArticulo = f.idArticulo
                JOIN vw_jerarquias_familias v ON v.idFamilia = f.idFamilia
                WHERE v.idN1 = ' . $idFamilia . '
                ' . $filtroSubfamilias . '
                AND s.stockOn > 0
                AND s.idTienda = 1;';
        $resultado = $this->consulta($sql);
        // Devolver array de ids articulos
        $articulos = array();
        if (isset($resultado['datos']) && count($resultado['datos']) > 0) {
            foreach ($resultado['datos'] as $fila) {
                $articulos[] = array(
                    'idArticulo' => $fila['idArticulo'],
                    'stockOn' => $fila['stockOn']
                );
            }
        }
        return $articulos;
    }

    // Obtener los productos restantes:
    public function obtenerProductosPendientesCierre()
    {
        $sql = 'SELECT
                    s.idArticulo,
                    s.stockOn
                FROM articulosStocks s
                WHERE s.stockOn > 0
                AND s.idTienda = 1;';
        $resultado = $this->consulta($sql);
        // Devolver array de ids articulos
        $articulos = array();
        if (isset($resultado['datos']) && count($resultado['datos']) > 0) {
            foreach ($resultado['datos'] as $fila) {
                $articulos[] = array(
                    'idArticulo' => $fila['idArticulo'],
                    'stockOn' => $fila['stockOn']
                );
            }
        }
        return $articulos;
    }

    public function obtenerProductosPorSubfamilia($idSubfamilia)
    {
        $sql = 'SELECT
                    s.idArticulo,
                    s.stockOn
                FROM articulosStocks s
                JOIN articulosFamilias f ON s.idArticulo = f.idArticulo
                JOIN vw_jerarquias_familias v ON v.idFamilia = f.idFamilia
                WHERE v.idN2 = ' . $idSubfamilia . '
                AND s.stockOn > 0
                AND s.idTienda = 1;';
        $resultado = $this->consulta($sql);
        // Devolver array de ids articulos
        $articulos = array();
        if (isset($resultado['datos']) && count($resultado['datos']) > 0) {
            foreach ($resultado['datos'] as $fila) {
                $articulos[] = array(
                    'idArticulo' => $fila['idArticulo'],
                    'stockOn' => $fila['stockOn']
                );
            }
        }
        return $articulos;
    }
    public function articulosAlbaranCierre($idArticulo)
    {
        $sql = 'SELECT acb.codBarras AS ccodbar, a.articulo_name AS cdetalle, a.ultimoCoste AS costSiva, a.iva AS iva
                FROM articulos a
                    LEFT JOIN articulosCodigoBarras acb ON a.idArticulo = acb.idArticulo
                WHERE a.idArticulo = ' . $idArticulo;;
        $resultado = $this->consulta($sql);
        if (isset($resultado['datos'][0])) {
            return $resultado['datos'][0];
        } else {
            return array();
        }
    }
}
