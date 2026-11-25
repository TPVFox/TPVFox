<?php
include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';

class ClaseBalanza  extends Modelo
{

    public $idTienda;

    public function __construct($conexion = '')
    {
        $this->ObtenerTiendaPrincipal();
    }

    public function ObtenerTiendaPrincipal()
    {
        $Sql = "SELECT idTienda FROM `tiendas` WHERE `tipoTienda`='Principal'";
        $respuesta = $this->consulta($Sql);
        if (count($respuesta['datos']) === 1) {
            $this->idTienda = $respuesta['datos'][0]['idTienda'];
        }
    }

    public function addBalanza($datos)
    {
        // Ahora se esperan los nuevos campos en $datos
        $sql = 'INSERT INTO `modulo_balanza`
            (`nombreBalanza`, `modelo`, `conSeccion`, `Grupo`, `Dirección`, `IP`, `soloPLUS`)
            VALUES (?, ?, ?, ?, ?, ?, ?)';
        $params = array(
            $datos['nombreBalanza'],
            $datos['modeloBalanza'],
            $datos['secciones'],
            intval($datos['Grupo']),
            intval($datos['Direccion']),
            $datos['IP'],
            (isset($datos['soloPLUS']) ? intval($datos['soloPLUS']) : 1)
        );
        $consulta = $this->consultaDML($sql, $params);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }

    public function productosEnBalanzas($filtro, $params = array())
    {
        // @ Objetivo:
        // Los productos de todas las balanzas, una fila por producto y balanza: con tecla, principal;
        // y en las que no son «solo PLUs», los productos al peso sin tecla, secundario.
        // @ Parametros:
        // $filtro -> (string) El WHERE, el orden y el límite del plugin de paginación, con un ? por valor.
        // $params -> (array) Los valores de $filtro.
        // @ Devolvemos:
        // (array) Las filas en ['datos'], vacío si no hay ninguna.
        $sql = 'SELECT * FROM (' . $this->consultaProductosEnBalanzas() . ') AS lineas ' . $filtro;
        $respuesta = $this->consulta($sql, $params);
        if (!isset($respuesta['datos'])) {
            $respuesta['datos'] = array();
        }
        return $respuesta;
    }

    public function contarProductosEnBalanzas($filtro, $params = array())
    {
        // @ Objetivo:
        // Contar las filas de productosEnBalanzas() con el mismo filtro, para la paginación.
        // @ Devolvemos:
        // (int) Las filas.
        $sql = 'SELECT COUNT(*) AS total FROM (' . $this->consultaProductosEnBalanzas() . ') AS lineas ' . $filtro;
        $respuesta = $this->consulta($sql, $params);
        $total = 0;
        if (isset($respuesta['datos'][0]['total'])) {
            $total = (int) $respuesta['datos'][0]['total'];
        }
        return $total;
    }

    private function consultaProductosEnBalanzas()
    {
        // @ Objetivo:
        // La consulta que junta las teclas de todas las balanzas y los productos al peso sin tecla de las
        // que no son «solo PLUs», de la tienda principal, para paginarlas juntas.
        $idTienda = intval($this->idTienda);
        $sql = "SELECT a.idArticulo, a.articulo_name, t.crefTienda, a.tipo, p.pvpCiva, b.idBalanza, b.nombreBalanza,
                b.modelo, b.conSeccion, b.soloPLUS, 'Principal' AS relacion, k.plu, k.seccion
                FROM modulo_balanza_plus AS k
                INNER JOIN modulo_balanza AS b ON b.idBalanza = k.idBalanza
                INNER JOIN articulos AS a ON a.idArticulo = k.idArticulo
                INNER JOIN articulosTiendas AS t ON t.idArticulo = a.idArticulo AND t.idTienda = " . $idTienda . "
                LEFT JOIN articulosPrecios AS p ON p.idArticulo = a.idArticulo AND p.idTienda = " . $idTienda . "
                UNION ALL
                SELECT a.idArticulo, a.articulo_name, t.crefTienda, a.tipo, p.pvpCiva, b.idBalanza, b.nombreBalanza,
                b.modelo, b.conSeccion, b.soloPLUS, 'Secundario' AS relacion, NULL AS plu, NULL AS seccion
                FROM articulos AS a
                INNER JOIN articulosTiendas AS t ON t.idArticulo = a.idArticulo AND t.idTienda = " . $idTienda . "
                INNER JOIN modulo_balanza AS b ON b.soloPLUS = 0
                LEFT JOIN articulosPrecios AS p ON p.idArticulo = a.idArticulo AND p.idTienda = " . $idTienda . "
                WHERE a.tipo = 'peso'
                AND NOT EXISTS (SELECT 1 FROM modulo_balanza_plus AS k WHERE k.idBalanza = b.idBalanza AND k.idArticulo = a.idArticulo)";
        return $sql;
    }

    public function todasBalanzas()
    {
        $sql = 'SELECT * from modulo_balanza';
        $resultado = $this->consulta($sql);
        return $resultado;
    }

    public function datosBalanza($idBalanza)
    {
        $sql = 'SELECT * from modulo_balanza where idBalanza = ?';
        $resultado = $this->consulta($sql, array(intval($idBalanza)));
        return $resultado;
    }

    public function pluDeBalanza($idBalanza, $filtro)
    {
        // Las teclas se ordenan por la tecla o por la sección
        if ($filtro !== 'a.seccion') {
            $filtro = 'a.plu';
        }
        $sql = 'Select a.*, t.crefTienda,b.articulo_name ,b.tipo, p.pvpCiva,pro.nombrecomercial from modulo_balanza_plus as a
         inner join articulos as b on a.idArticulo=b.idArticulo  INNER JOIN articulosTiendas as t
         on t.idArticulo=b.idArticulo and t.idTienda =' . intval($this->idTienda) . ' inner join articulosPrecios as p on p.idArticulo=a.idArticulo
         left join proveedores as pro on pro.idProveedor=b.idProveedor
         where a.idBalanza = ?
         order by ' . $filtro . ' asc, a.plu asc';

        $plus = $this->consulta($sql, array(intval($idBalanza)));

        // Controlar si no hay plus para la balanza
        if (empty($plus['datos'])) {
            $plus['datos'] = [];
            $plus['mensaje'] = 'No hay PLUs para esta balanza.';
            return $plus;
        }

        $idsProductos = array_column($plus['datos'], 'idArticulo');
        $idsProductosUnicos = array_unique($idsProductos);
        $duplicado = array();
        if (count($idsProductos) !== count($idsProductosUnicos)) {
            $duplicado = array_diff_assoc($idsProductos, $idsProductosUnicos);
        }
        if (count($duplicado) > 0) {
            foreach ($duplicado as $key => $valor) {
                $plus['datos'][$key]['duplicado'] = 'KO';
            }
        }
        $resultado = $plus;
        return $resultado;
    }

    public function buscarArticuloCampo($busqueda, $params = array())
    {
        $sql = 'SELECT a.idArticulo, a.articulo_name, b.crefTienda, c.codBarras,p.pvpCiva
                from articulos as a LEFT JOIN articulosTiendas as b on a.idArticulo=b.idArticulo LEFT JOIN
                articulosCodigoBarras as c on a.idArticulo=c.idArticulo LEFT join tiendas as d on
                b.idTienda=d.idTienda  left join articulosPrecios as p on p.idArticulo=a.idArticulo
                where d.tipoTienda="principal" and ' . $busqueda;
        $resultado = $this->consulta($sql, $params);
        return $resultado;
    }

    public function buscarPluEnBalanza($plu, $idBalanza)
    {
        $sql = 'select * from modulo_balanza_plus where idBalanza = ? and plu = ?';
        $resultado = $this->consulta($sql, array(intval($idBalanza), $plu));
        return $resultado;
    }

    public function addPlu($plu, $idBalanza, $seccion, $idArticulo)
    {
        $seccion = intval($seccion);
        $sql = 'INSERT INTO `modulo_balanza_plus`(`idBalanza`, `plu`, `seccion`, `idArticulo`)
         VALUES (?, ?, ?, ?)';
        $consulta = $this->consultaDML($sql, array(intval($idBalanza), $plu, $seccion, intval($idArticulo)));
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }

    public function updatePlu($idArticulo, $idBalanza, $plu, $seccion)
    {
        $seccion = intval($seccion);
        // La tecla tiene que estar libre en su sección, como al añadirla
        $ocupada = $this->consulta('SELECT idArticulo FROM modulo_balanza_plus WHERE idBalanza = ? AND plu = ? AND seccion = ? AND idArticulo <> ?', array($idBalanza, $plu, $seccion, $idArticulo));
        if (!empty($ocupada['datos'])) {
            return ['error' => 'La tecla ' . $plu . ' ya la tiene el producto con id: ' . $ocupada['datos'][0]['idArticulo']];
        }
        $sql = 'UPDATE `modulo_balanza_plus`
                SET `plu` = ?, `seccion` = ?
                WHERE `idArticulo` = ? AND `idBalanza` = ?';
        $consulta = $this->consultaDML($sql, array($plu, $seccion, intval($idArticulo), intval($idBalanza)));
        // La sentencia responde «0» si fue bien
        if ($consulta['error'] !== '0') {
            return $consulta;
        }
        return ['success' => true];
    }

    public function obtenerPluActual($idBalanza, $idArticulo)
    {
        $sql = 'SELECT plu, seccion FROM modulo_balanza_plus WHERE idBalanza = ' . intval($idBalanza) . ' AND idArticulo = ' . intval($idArticulo);
        $resultado = $this->consulta($sql);
        if (!empty($resultado['datos'][0])) {
            return $resultado['datos'][0];
        }
        return null;
    }

    public function eliminarplu($idBalanza, $plu, $idArticulo)
    {
        // Con el producto se quita la tecla de ese producto: con secciones, el mismo número puede ser de otro
        $sql = 'DELETE FROM `modulo_balanza_plus` WHERE idBalanza = ? and plu = ?';
        $params = array(intval($idBalanza), $plu);
        if (intval($idArticulo) > 0) {
            $sql .= ' and idArticulo = ?';
            $params[] = intval($idArticulo);
        }
        $consulta = $this->consultaDML($sql, $params);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }
    public function modificarBalanza($idBalanza, $datos)
    {
        // Obtener datos actuales de la balanza
        $balanzaActual = $this->datosBalanza(intval($idBalanza));
        if (empty($balanzaActual['datos'][0])) {
            return ['error' => 'Balanza no encontrada'];
        }
        $actual = $balanzaActual['datos'][0];

        // Solo los campos solicitados
        $campos = [
            'nombreBalanza'   => 'nombreBalanza',
            'modeloBalanza'   => 'modelo',
            'secciones'       => 'conSeccion'
        ];

        $set = [];
        $params = [];
        foreach ($campos as $key => $columna) {
            $nuevoValor = isset($datos[$key]) ? $datos[$key] : null;
            $valorActual = isset($actual[$columna]) ? $actual[$columna] : null;

            if ($nuevoValor !== null && $nuevoValor !== $valorActual) {
                $set[] = "`$columna` = ?";
                $params[] = $nuevoValor;
            }
        }

        if (empty($set)) {
            return ['mensaje' => 'No hay cambios para actualizar'];
        }

        $sql = 'UPDATE `modulo_balanza` SET ' . implode(', ', $set) . ' WHERE `idBalanza` = ' . intval($idBalanza);
        $consulta = $this->consultaDML($sql, $params);
        if (isset($consulta['error'])) {
            return $consulta;
        }
        return ['success' => true];
    }

    // Funcion para saber si una balanza usa secciones
    public function usaSecciones($idBalanza)
    {
        $sql = 'SELECT conSeccion FROM `modulo_balanza` WHERE idBalanza = ' . intval($idBalanza);
        $resultado = $this->consulta($sql);
        if (isset($resultado['datos'][0]['conSeccion'])) {
            return strtolower($resultado['datos'][0]['conSeccion']) === 'si';
        }
        return false; // Si no se encuentra la balanza, asumimos que no usa secciones
    }

    public function getArticulosPesoSinPLU($idBalanza)
    {
        // Devuelve artículos de tipo 'peso' que NO están en modulo_balanza_plus para esta balanza y solo de la tienda principal
        $sql = "SELECT a.idArticulo, a.articulo_name, t.crefTienda, c.codBarras, p.pvpCiva, pro.nombrecomercial
                FROM articulos a
                INNER JOIN articulosTiendas t ON t.idArticulo = a.idArticulo
                INNER JOIN tiendas d ON t.idTienda = d.idTienda AND d.tipoTienda = 'principal'
                LEFT JOIN articulosCodigoBarras c ON c.idArticulo = a.idArticulo
                LEFT JOIN articulosPrecios p ON p.idArticulo = a.idArticulo
                LEFT JOIN proveedores pro ON pro.idProveedor = a.idProveedor
                WHERE a.tipo = 'peso'
                AND a.idArticulo NOT IN (
                    SELECT idArticulo FROM modulo_balanza_plus WHERE idBalanza = ?
                )
                GROUP BY a.idArticulo";
        $res = $this->consulta($sql, array(intval($idBalanza)));
        return $res['datos'] ?? [];
    }

    public function tienePlusAsociados($idBalanza)
    {
        // Verifica si hay PLUs asociados a la balanza
        $sql = 'SELECT COUNT(*) as total FROM modulo_balanza_plus WHERE idBalanza = ' . intval($idBalanza);
        $resultado = $this->consulta($sql);
        if (isset($resultado['datos'][0]['total']) && $resultado['datos'][0]['total'] > 0) {
            return true; // Tiene PLUs asociados
        }
        return false; // No tiene PLUs asociados
    }

    public function eliminarBalanza($idBalanza)
    {
        // Eliminar la balanza
        $sql = 'DELETE FROM `modulo_balanza` WHERE idBalanza = ' . intval($idBalanza);
        $consulta = $this->consultaDML($sql);
        // La sentencia responde «0» si fue bien
        if ($consulta['error'] !== '0') {
            return $consulta;
        }
        return ['success' => true];
    }

    // Función para obtener todas las balanzas 0 soloPlus
    public function obtenerBalanzasEnvio()
    {
        $sql = 'SELECT * FROM `modulo_balanza` WHERE soloPLUS = 0';
        $resultado = $this->consulta($sql);
        if (isset($resultado['datos']) && count($resultado['datos']) > 0) {
            return $resultado['datos']; // Retorna las balanzas encontradas
        }
        return []; // Retorna un array vacío si no hay balanzas
    }

    // Función para obtener una balanza a partir de un ID de Articulo
    public function obtenerBalanzaPorIdArticulo($idArticulo)
    {
        $sql = 'SELECT b.* FROM `modulo_balanza_plus` p
                INNER JOIN `modulo_balanza` b ON p.idBalanza = b.idBalanza
                WHERE p.idArticulo = ' . intval($idArticulo);
        $resultado = $this->consulta($sql);
        if (isset($resultado['datos']) && count($resultado['datos']) > 0) {
            return $resultado['datos']; // Devuelve todas las valanzas encontradas
        }
        return null; // Retorna null si no se encuentra la balanza
    }

    public function guardarConfigAvanzada($idBalanza, $datos)
    {
        // Mapear los nombres recibidos a los nombres de columna reales
        $map = [
            'ipBalanza'      => 'IP',
            'grupoBalanza'   => 'Grupo',
            'direccionBalanza' => 'Dirección',
            'soloPLUS'       => 'soloPLUS'
        ];

        $set = [];
        $params = [];
        foreach ($map as $key => $columna) {
            if (isset($datos[$key])) {
                $set[] = "`$columna` = ?";
                if (in_array($columna, ['Grupo', 'Dirección', 'soloPLUS'])) {
                    $params[] = intval($datos[$key]);
                } else {
                    $params[] = $datos[$key];
                }
            }
        }

        if (empty($set)) {
            return ['mensaje' => 'No hay cambios para actualizar'];
        }

        $sql = 'UPDATE `modulo_balanza` SET ' . implode(', ', $set) . ' WHERE `idBalanza` = ' . intval($idBalanza);
        $consulta = $this->consultaDML($sql, $params);
        // La sentencia responde «0» si fue bien
        if ($consulta['error'] !== '0') {
            return $consulta;
        }
        return ['success' => true];
    }
}
