<?php
include_once $URLCom . '/clases/ClaseTFModelo.php';
include_once $URLCom . '/modulos/mod_proveedor/clases/ClaseProveedor.php';
include_once __DIR__ . '/InformesFiltros.php';
include_once __DIR__ . '/BeneficioCalculator.php';
include_once __DIR__ . '/CosteFluctuacionCalculator.php';
class ClaseInformes extends TFModelo
{
    public $informes = array(
        '1' => array(
            'Titulo' => 'Informe de Compras por Proveedores.',
            'opciones' => array(
                '1' => 'Todos',
                '2' => 'Facturados',
                '3' => 'Sin Facturar',
                '4' => 'Proveedores activos'
            )
        ),
        '2' => array(
            'Titulo' => 'Suma compras por familias',
            'opciones' => array(
                '1' => 'Solo familias',
                '2' => 'Familias y subfamilias',
                '3' => 'Familias, subfamilias y productos',
            )
        ),
        '4' => array(
            'Titulo' => 'Suma de ventas por familia',
            'opciones' => array(
                '1' => 'Solo familias',
                '2' => 'Familias y subfamilias',
                '3' => 'Familias, subfamilias y productos',
            )
        ),
        '6' => array(
            'Titulo' => 'Beneficio por familia',
            'opciones' => array(
                '1' => 'Solo familias',
                '2' => 'Familias y subfamilias',
                '3' => 'Familias, subfamilias y productos',
            )
        ),
        '7' => array(
            'Titulo' => 'Fluctuacion de coste mensual',
            'opciones' => array(
                'articulo' => 'Articulo',
                'familia' => 'Familia',
                'subfamilia' => 'Subfamilia',
            )
        )
    );
    public function ObtenerdatosInforme()
    {
        $parametros = $_GET;
        $parametros['familias'] = $parametros['familias'] ?? '';
        $id = $parametros['id'];

        if ($id == 1) {
            $parametros['filtroProveedores'] = 'todos';
            $datos = $this->ResumenProveedores($parametros);
        } elseif ($id == 2) {
            $datos = $this->ResumenFamilias($parametros);
        } elseif ($id == 4) {
            $datos = $this->ResumenVentasFamilias($parametros);
        } elseif ($id == 6) {
            $ret   = $this->BeneficioFamilias($parametros);
            $datos = $ret['familias'];
            $parametros['resumen_global'] = $ret['resumen'];
        } elseif ($id == 7) {
            $ret = $this->FluctuacionCosteMensual($parametros);
            $agr = $ret['filtros']['agrupacion'] ?? 'articulo';
            if ($agr === 'familia') {
                $datos = $ret['familias'] ?? [];
            } elseif ($agr === 'subfamilia') {
                $datos = $ret['subfamilias'] ?? [];
            } else {
                $datos = $ret['articulos'] ?? [];
            }
            $parametros['resumen_global'] = $ret['resumen'] ?? null;
            $parametros['filtros_fluctuacion'] = $ret['filtros'] ?? null;
        } else {
            $datos = [];
        }

        $cabecera = array(
            'id'             => $id,
            'titulo_informe' => $this->informes[$id]['Titulo'],
            'Fecha_Inicio'   => $parametros['Finicio'],
            'Fecha_Final'    => $parametros['Ffinal'],
            'opcion'         => $parametros['opcion'],
            'resumen_global' => $parametros['resumen_global'] ?? null,
            'filtros_fluctuacion' => $parametros['filtros_fluctuacion'] ?? null,
        );

        return array(
            'datos'    => $datos,
            'cabecera' => $cabecera
        );
    }

    public function ResumenProveedores($parametros = array())
    {
        $db = $this->conexionBDTPV();
        $CProveedor = new ClaseProveedor($db);
        $opcion = $parametros['opcion'];
        $id_informe = $parametros['id'];

        $todosProveedores = [];
        $ids              = [];
        if ($this->informes[$id_informe]['opciones'][$opcion] == 'Todos') {
            $todosProveedores = $CProveedor->obtenerProveedores();
            $ids = $this->ObtenerIdsArray($todosProveedores, 'idProveedor');
        }

        if (empty($ids)) {
            return [
                'datos'   => $todosProveedores,
                'informe' => ['productos' => [], 'suma_albaranes' => [], 'suma_desgloseIvas' => []]
            ];
        }

        $fechaInicial = $db->real_escape_string($parametros['Finicio']);
        $fechaFinal   = $db->real_escape_string($parametros['Ffinal']);
        $filtroFechaAlbprot = $this->buildFiltroRangoFechaSQL('Fecha', $fechaInicial, $fechaFinal);
        $idsStr       = implode(',', array_map('intval', $ids));

        // Consulta masiva de cabeceras de albarán para evitar consultas por proveedor.
        $sentenciaAlbaranes = $db->query("
            SELECT id AS idalbpro, idProveedor
            FROM albprot
            WHERE idProveedor IN ($idsStr)
                            AND $filtroFechaAlbprot
        ");
        $albIdsByProveedor = [];
        $allAlbIds         = [];
        while ($fila = $sentenciaAlbaranes->fetch_assoc()) {
            $idProveedor = (int)$fila['idProveedor'];
            $idAlbaran = (int)$fila['idalbpro'];
            $albIdsByProveedor[$idProveedor][] = $idAlbaran;
            $allAlbIds[]               = $idAlbaran;
        }

        $productosByAlb = [];
        $resumenByAlb   = [];

        if (!empty($allAlbIds)) {
            $albStr = implode(',', $allAlbIds);

            $sentenciaLineas = $db->query("
                SELECT idalbpro, idArticulo, costeSiva,
                       SUM(nunidades) AS totalUnidades
                FROM albprolinea
                WHERE idalbpro IN ($albStr)
                  AND estadoLinea <> 'Eliminado'
                GROUP BY idalbpro, idArticulo, costeSiva
            ");
            while ($fila = $sentenciaLineas->fetch_assoc()) {
                $productosByAlb[(int)$fila['idalbpro']][] = $fila;
            }

            $sentenciaResumen = $db->query("
                SELECT i.iva, i.totalbase, i.importeIva,
                       t.id AS idalbpro, t.Su_numero, t.idTienda, t.estado,
                       t.idProveedor, t.idUsuario,
                       SUM(i.totalbase)  AS sumabase,
                       SUM(i.importeIva) AS sumarIva,
                       t.Fecha           AS fecha
                FROM albproIva i
                LEFT JOIN albprot t ON t.id = i.idalbpro
                WHERE i.idalbpro IN ($albStr)
                GROUP BY i.idalbpro
                ORDER BY t.idProveedor, t.Fecha
            ");
            while ($fila = $sentenciaResumen->fetch_assoc()) {
                $resumenByAlb[(int)$fila['idalbpro']] = $fila;
            }
        }

        foreach ($todosProveedores as $key => $proveedor) {
            $idProveedor    = (int)$proveedor['idProveedor'];
            $albIds = $albIdsByProveedor[$idProveedor] ?? [];

            if (empty($albIds)) {
                continue;
            }

            $productos    = [];
            $resumenBases = [];
            foreach ($albIds as $idAlbaran) {
                foreach ($productosByAlb[$idAlbaran] ?? [] as $linea) {
                    $productos[] = $linea;
                }
                if (isset($resumenByAlb[$idAlbaran])) {
                    $resumenBases[] = $resumenByAlb[$idAlbaran];
                }
            }

            $todosProveedores[$key]['cant_albaranes'] = count($albIds);
            $todosProveedores[$key]['albaranes']      = [
                'productos'    => $productos,
                'resumenBases' => $resumenBases,
            ];
        }

        $ArrayProductos = [];
        $SumaAlbaranes = [];
        $DesgloseAlbaranes = [];
        foreach ($todosProveedores as $key => $proveedor) {
            if (isset($proveedor['albaranes']['productos'])) {
                $p = $CProveedor->SumaLineasAlbaranesProveedores($proveedor['albaranes']['productos']);
                $ArrayProductos[] = $p;
                $todosProveedores[$key]['referencias_productos'] = count($p);

                $SumaAlbaranes[] = $proveedor['albaranes']['resumenBases'];
                $SumaAlbaranes[] = $todosProveedores[$key];

                $DesgloseAlbaranes[] = $proveedor['albaranes']['resumenBases'];
            }
        }

        $Productos = [];
        foreach ($ArrayProductos as $P) {
            foreach ($P as $producto) {
                $Productos[] = $producto;
            }
        }

        $Productos = $this->SumaProductosTodosProveedores($Productos);
        $respuesta = array(
            'datos'     => $todosProveedores,

            'informe'   => array(
                'productos'         => $Productos,
                'suma_albaranes'    => $SumaAlbaranes,
                'suma_desgloseIvas' => $DesgloseAlbaranes
            )
        );
        return $respuesta;
    }




    public function SumaProductosTodosProveedores($LineasProductos)
    {
        $Productos = [];
        foreach ($LineasProductos as $producto) {
            $id_producto = $producto['idArticulo'];
            unset($producto['idalbpro']);
            if (array_key_exists($id_producto, $Productos) == false) {
                $Productos[$id_producto] = $producto;
                $Productos[$id_producto]['costeSiva'] = $producto['costeSiva'];
                $Productos[$id_producto]['coste_medio'] = 'KO';
                if ($producto['coste_medio'] === 'OK') {
                    $Productos[$id_producto]['coste_medio'] = 'OK';
                }
                $Productos[$id_producto]['totalUnidades'] = $producto['totalUnidades'];
                $Productos[$id_producto]['num_compras'] = 1;
                if ($producto['num_compras'] > 0) {
                    $Productos[$id_producto]['num_compras'] = $producto['num_compras'];
                }
            } else {
                $total_producto = $producto['totalUnidades'] * $producto['costeSiva'];
                if ($Productos[$id_producto]['costeSiva'] !== $producto['costeSiva']) {
                    $Productos[$id_producto]['coste_medio'] = 'OK';
                    $suma = $Productos[$id_producto]['totalUnidades'] + $producto['totalUnidades'];
                    if ($suma != 0) {
                        $Productos[$id_producto]['costeSiva'] = ($Productos[$id_producto]['total_linea'] + $total_producto) / $suma;
                    }
                }
                $Productos[$id_producto]['totalUnidades'] += $producto['totalUnidades'];
                if ($producto['num_compras'] > 0) {
                    $Productos[$id_producto]['num_compras'] =  $Productos[$id_producto]['num_compras'] + $producto['num_compras'];
                } else {
                    $Productos[$id_producto]['num_compras'] += 1;
                }
            }
            $Productos[$id_producto]['total_linea'] = $Productos[$id_producto]['totalUnidades'] * $Productos[$id_producto]['costeSiva'];
        }
        $respuesta = [];
        foreach ($Productos  as $producto) {
            $respuesta[] = $producto;
        }
        return $respuesta;
    }

    public function ObtenerIdsArray($datos, $campo)
    {
        $valores = [];
        foreach ($datos as $dato) {
            $valores[] = $dato[$campo];
        }
        return $valores;
    }

    public function ResumenFamilias($parametros = array())
    {
        $db       = $this->conexionBDTPV();
        $fechaInicio = $db->real_escape_string($parametros['Finicio']);
        $fechaFinal  = $db->real_escape_string($parametros['Ffinal']);
        $filtroFechaAlbprot = $this->buildFiltroRangoFechaSQL('a.Fecha', $fechaInicio, $fechaFinal);

        $filtroN1         = '';
        $virtualHierarchy = null;
        $needsIdFamilia   = false;
        if ((int)$parametros['opcion'] === 4) {
            $ids = array_values(array_filter(array_map('intval', explode(',', $parametros['familias'] ?? ''))));
            if (!empty($ids)) {
                $fop4             = InformesFiltros::buildFiltroOp4($db, $ids);
                $filtroN1         = $fop4['filtroSQL'];
                $virtualHierarchy = $fop4['virtualHierarchy'];
                $needsIdFamilia   = $fop4['needsIdFamilia'];
            }
        }

        // Se añade familiaDirecta solo en opcion=4 para remapear N1/N2 virtual en PHP
        // sin introducir consultas adicionales por artículo (evita patrón N+1).
        $selectFamId  = $needsIdFamilia ? ', af.idFamilia AS familiaDirecta' : ', NULL AS familiaDirecta';
        $groupByFamId = $needsIdFamilia ? ', af.idFamilia'                   : '';

        $sql = "
            SELECT
                vj.idN1,
                MAX(n1.familiaNombre)      AS nombreN1,
                vj.idN2,
                MAX(n2.familiaNombre)      AS nombreN2,
                l.idArticulo,
                l.costeSiva,
                SUM(l.nunidades)           AS totalUnidades,
                COUNT(DISTINCT l.idalbpro) AS num_albaranes
                $selectFamId
            FROM albprolinea l
            JOIN albprot a ON a.id = l.idalbpro
            LEFT JOIN articulosFamilias af ON af.idArticulo = l.idArticulo
            LEFT JOIN vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            LEFT JOIN vw_jerarquias_familias n1 ON n1.idFamilia = vj.idN1
            LEFT JOIN vw_jerarquias_familias n2 ON n2.idFamilia = vj.idN2
                        WHERE $filtroFechaAlbprot
              AND l.estadoLinea <> 'Eliminado'
              $filtroN1
            GROUP BY vj.idN1, vj.idN2, l.idArticulo, l.costeSiva $groupByFamId
            ORDER BY nombreN1, nombreN2, l.idArticulo
        ";

        $sentencia    = $db->query($sql);
        $lineas = [];
        while ($fila = $sentencia->fetch_assoc()) {
            $lineas[] = $fila;
        }

        $familias = [];

        foreach ($lineas as $fila) {
            if ($virtualHierarchy !== null && isset($fila['familiaDirecta'])) {
                $famId = (int)$fila['familiaDirecta'];
                $vh    = $virtualHierarchy[$famId] ?? null;
                if ($vh !== null) {
                    $idN1   = $vh['vN1'];
                    $nombreN1 = $vh['vN1Name'];
                    $idN2   = $vh['vN2'];
                    $keyN2    = $idN2 !== null ? $idN2 : '__sin_n2__';
                    $labelN2 = $idN2 !== null ? ($vh['vN2Name'] ?? 'Sin subfamilia') : '(Sin subfamilia)';
                } else {
                    $idN1 = '__sin_familia__';
                    $nombreN1 = 'Sin familia';
                    $idN2 = null;
                    $keyN2 = '__sin_n2__';
                    $labelN2 = '(Sin subfamilia)';
                }
            } else {
                $idN1     = $fila['idN1'] !== null ? (int)$fila['idN1'] : '__sin_familia__';
                $nombreN1 = $fila['nombreN1'] !== null ? $fila['nombreN1'] : 'Sin familia';
                $idN2     = $fila['idN2'] !== null ? (int)$fila['idN2'] : null;
                $keyN2    = $idN2 !== null ? $idN2 : '__sin_n2__';
                $labelN2  = $idN2 !== null ? ($fila['nombreN2'] ?? 'Sin subfamilia') : '(Sin subfamilia)';
            }
            $idArt    = (int)$fila['idArticulo'];
            $coste    = (float)$fila['costeSiva'];
            $unidades = (float)$fila['totalUnidades'];

            if (!isset($familias[$idN1])) {
                $familias[$idN1] = [
                    'idN1'        => $idN1,
                    'nombreN1'    => $nombreN1,
                    'subfamilias' => []
                ];
            }

            if (!isset($familias[$idN1]['subfamilias'][$keyN2])) {
                $familias[$idN1]['subfamilias'][$keyN2] = [
                    'idN2'      => $idN2,
                    'nombreN2'  => $labelN2,
                    'articulos' => []
                ];
            }

            $art = &$familias[$idN1]['subfamilias'][$keyN2]['articulos'][$idArt];

            if (!isset($art['idArticulo'])) {
                $art = [
                    'idArticulo'    => $idArt,
                    'totalUnidades' => $unidades,
                    'costeSiva'     => $coste,
                    'coste_medio'   => 'KO',
                    'num_compras'   => (int)$fila['num_albaranes'],
                    'total_linea'   => $unidades * $coste
                ];
            } else {
                if ($art['costeSiva'] != $coste) {
                    $art['coste_medio'] = 'OK';
                    $suma_unidades      = $art['totalUnidades'] + $unidades;
                    if ($suma_unidades > 0) {
                        $art['costeSiva'] = (
                            ($art['totalUnidades'] * $art['costeSiva']) + ($unidades * $coste)
                        ) / $suma_unidades;
                    }
                }
                $art['totalUnidades'] += $unidades;
                $art['num_compras']   += (int)$fila['num_albaranes'];
                $art['total_linea']    = $art['totalUnidades'] * $art['costeSiva'];
            }

            unset($art);
        }

        $resultado = [];
        foreach ($familias as $familia) {
            $totalN1 = 0;
            $refsN1  = [];
            $sfs     = [];

            foreach ($familia['subfamilias'] as $sf) {
                $arts    = array_values($sf['articulos']);
                $totalN2 = 0;
                foreach ($arts as $art) {
                    $totalN2 += $art['total_linea'];
                    $refsN1[$art['idArticulo']] = true;
                }
                $totalN1 += $totalN2;

                $sfs[] = [
                    'idN2'            => $sf['idN2'],
                    'nombreN2'        => $sf['nombreN2'],
                    'total_linea'     => $totalN2,
                    'num_referencias' => count($arts),
                    'articulos'       => $arts
                ];
            }

            usort($sfs, fn($a, $b) => $b['total_linea'] <=> $a['total_linea']);
            $resultado[] = [
                'idN1'            => $familia['idN1'],
                'nombreN1'        => $familia['nombreN1'],
                'total_linea'     => $totalN1,
                'num_referencias' => count($refsN1),
                'subfamilias'     => $sfs
            ];
        }
        usort($resultado, fn($a, $b) => $b['total_linea'] <=> $a['total_linea']);

        return $resultado;
    }

    public function ResumenVentasFamilias($parametros = array())
    {
        // Se mantiene UNION ALL entre albaranes y tickets para consolidar ventas en
        // una sola agregación SQL y evitar combinaciones en memoria por artículo/familia.

        $db       = $this->conexionBDTPV();
        $fechaInicio = $db->real_escape_string($parametros['Finicio']);
        $fechaFinal  = $db->real_escape_string($parametros['Ffinal']);
        $filtroFechaVentas = $this->buildFiltroRangoFechaSQL('h.Fecha', $fechaInicio, $fechaFinal);

        $filtroN1         = '';
        $virtualHierarchy = null;
        $needsIdFamilia   = false;
        if ((int)($parametros['opcion'] ?? 0) === 4) {
            $ids = array_values(array_filter(array_map('intval', explode(',', $parametros['familias'] ?? ''))));
            if (!empty($ids)) {
                $fop4             = InformesFiltros::buildFiltroOp4($db, $ids);
                $filtroN1         = $fop4['filtroSQL'];
                $virtualHierarchy = $fop4['virtualHierarchy'];
                $needsIdFamilia   = $fop4['needsIdFamilia'];
            }
        }

        // Para opcion=4 se conserva idFamilia y se agrupa también por él, porque la
        // jerarquía virtual se resuelve en memoria y una familia real puede mapear a
        // un nodo virtual distinto al de la jerarquía estándar.
        $selectFamId  = $needsIdFamilia ? ', af.idFamilia AS familiaDirecta' : ', NULL AS familiaDirecta';
        $groupByFamId = $needsIdFamilia ? ', af.idFamilia'                   : '';

        $sql = "
            SELECT
                vj.idN1,
                MAX(n1.familiaNombre)                             AS nombreN1,
                vj.idN2,
                MAX(n2.familiaNombre)                             AS nombreN2,
                l.idArticulo,
                MAX(ar.articulo_name)                             AS articulo_name,
                MAX(ar.tipo)                                      AS tipo,
                l.precioCiva / (1 + l.iva / 100)                 AS pvpSiva,
                SUM(l.nunidades)                                  AS totalUnidades,
                COUNT(DISTINCT l.idalbcli)                        AS num_documentos
                $selectFamId
            FROM albclilinea l
            JOIN albclit h ON h.id = l.idalbcli
            JOIN articulos ar ON ar.idArticulo = l.idArticulo
            LEFT JOIN clientes cl ON cl.idClientes = h.idCliente
            LEFT JOIN articulosFamilias af ON af.idArticulo = l.idArticulo
            LEFT JOIN vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            LEFT JOIN vw_jerarquias_familias n1 ON n1.idFamilia = vj.idN1
            LEFT JOIN vw_jerarquias_familias n2 ON n2.idFamilia = vj.idN2
                        WHERE $filtroFechaVentas
              AND h.estado IN ('Guardado', 'Procesado')
              AND l.estadoLinea = 'Activo'
              AND (h.idCliente = 0 OR cl.estado != 'Especial')
              $filtroN1
            GROUP BY vj.idN1, vj.idN2, l.idArticulo, l.precioCiva, l.iva $groupByFamId

            UNION ALL

            SELECT
                vj.idN1,
                MAX(n1.familiaNombre)                             AS nombreN1,
                vj.idN2,
                MAX(n2.familiaNombre)                             AS nombreN2,
                l.idArticulo,
                MAX(ar.articulo_name)                             AS articulo_name,
                MAX(ar.tipo)                                      AS tipo,
                l.precioCiva / (1 + l.iva / 100)                 AS pvpSiva,
                SUM(l.nunidades)                                  AS totalUnidades,
                COUNT(DISTINCT l.idticketst)                      AS num_documentos
                $selectFamId
            FROM ticketslinea l
            JOIN ticketst h ON h.id = l.idticketst
            JOIN articulos ar ON ar.idArticulo = l.idArticulo
            LEFT JOIN clientes cl ON cl.idClientes = h.idCliente
            LEFT JOIN articulosFamilias af ON af.idArticulo = l.idArticulo
            LEFT JOIN vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            LEFT JOIN vw_jerarquias_familias n1 ON n1.idFamilia = vj.idN1
            LEFT JOIN vw_jerarquias_familias n2 ON n2.idFamilia = vj.idN2
                        WHERE $filtroFechaVentas
              AND h.estado IN ('Cobrado', 'Cerrado')
              AND l.estadoLinea = 'Activo'
              AND (h.idCliente = 0 OR cl.estado != 'Especial')
              $filtroN1
            GROUP BY vj.idN1, vj.idN2, l.idArticulo, l.precioCiva, l.iva $groupByFamId
            ORDER BY nombreN1, nombreN2, idArticulo
        ";

        $sentencia    = $db->query($sql);
        $lineas = [];
        while ($fila = $sentencia->fetch_assoc()) {
            $lineas[] = $fila;
        }

        $familias = [];

        foreach ($lineas as $fila) {
            if ($virtualHierarchy !== null && isset($fila['familiaDirecta'])) {
                $famId = (int)$fila['familiaDirecta'];
                $vh    = $virtualHierarchy[$famId] ?? null;
                if ($vh !== null) {
                    $idN1    = $vh['vN1'];
                    $nombreN1 = $vh['vN1Name'];
                    $idN2    = $vh['vN2'];
                    $keyN2    = $idN2 !== null ? $idN2 : '__sin_n2__';
                    $labelN2 = $idN2 !== null ? ($vh['vN2Name'] ?? 'Sin subfamilia') : '(Sin subfamilia)';
                } else {
                    $idN1 = '__sin_familia__';
                    $nombreN1 = 'Sin familia';
                    $idN2 = null;
                    $keyN2 = '__sin_n2__';
                    $labelN2 = '(Sin subfamilia)';
                }
            } else {
                $idN1     = $fila['idN1'] !== null ? (int)$fila['idN1'] : '__sin_familia__';
                $nombreN1 = $fila['nombreN1'] !== null ? $fila['nombreN1'] : 'Sin familia';
                $idN2     = $fila['idN2'] !== null ? (int)$fila['idN2'] : null;
                $keyN2    = $idN2 !== null ? $idN2 : '__sin_n2__';
                $labelN2  = $idN2 !== null ? ($fila['nombreN2'] ?? 'Sin subfamilia') : '(Sin subfamilia)';
            }
            $idArt    = (int)$fila['idArticulo'];
            $pvp      = (float)$fila['pvpSiva'];
            $unidades = (float)$fila['totalUnidades'];

            if (!isset($familias[$idN1])) {
                $familias[$idN1] = [
                    'idN1'        => $idN1,
                    'nombreN1'    => $nombreN1,
                    'subfamilias' => []
                ];
            }

            if (!isset($familias[$idN1]['subfamilias'][$keyN2])) {
                $familias[$idN1]['subfamilias'][$keyN2] = [
                    'idN2'      => $idN2,
                    'nombreN2'  => $labelN2,
                    'articulos' => []
                ];
            }

            $art = &$familias[$idN1]['subfamilias'][$keyN2]['articulos'][$idArt];

            if (!isset($art['idArticulo'])) {
                $art = [
                    'idArticulo'    => $idArt,
                    'articulo_name' => $fila['articulo_name'],
                    'tipo'          => $fila['tipo'],
                    'totalUnidades' => $unidades,
                    'pvpSiva'       => $pvp,
                    'precio_medio'  => 'KO',
                    'num_ventas'    => (int)$fila['num_documentos'],
                    'total_linea'   => $unidades * $pvp
                ];
            } else {
                if ($art['pvpSiva'] != $pvp) {
                    $art['precio_medio'] = 'OK';
                    $suma_unidades       = $art['totalUnidades'] + $unidades;
                    if ($suma_unidades > 0) {
                        $art['pvpSiva'] = (
                            ($art['totalUnidades'] * $art['pvpSiva']) + ($unidades * $pvp)
                        ) / $suma_unidades;
                    }
                }
                $art['totalUnidades'] += $unidades;
                $art['num_ventas']    += (int)$fila['num_documentos'];
                $art['total_linea']    = $art['totalUnidades'] * $art['pvpSiva'];
            }

            unset($art);
        }

        $resultado = [];
        foreach ($familias as $familia) {
            $totalN1 = 0;
            $refsN1  = [];
            $sfs     = [];

            foreach ($familia['subfamilias'] as $sf) {
                $arts    = array_values($sf['articulos']);
                $totalN2 = 0;
                foreach ($arts as $art) {
                    $totalN2 += $art['total_linea'];
                    $refsN1[$art['idArticulo']] = true;
                }
                $totalN1 += $totalN2;

                $sfs[] = [
                    'idN2'            => $sf['idN2'],
                    'nombreN2'        => $sf['nombreN2'],
                    'total_linea'     => $totalN2,
                    'num_referencias' => count($arts),
                    'articulos'       => $arts
                ];
            }

            usort($sfs, fn($a, $b) => $b['total_linea'] <=> $a['total_linea']);
            $resultado[] = [
                'idN1'            => $familia['idN1'],
                'nombreN1'        => $familia['nombreN1'],
                'total_linea'     => $totalN1,
                'num_referencias' => count($refsN1),
                'subfamilias'     => $sfs
            ];
        }
        usort($resultado, fn($a, $b) => $b['total_linea'] <=> $a['total_linea']);

        return $resultado;
    }

    public function BeneficioFamilias($parametros = array())
    {
        return (new BeneficioCalculator($this->conexionBDTPV()))->calcular($parametros);
    }

    public function FluctuacionCosteMensual($parametros = array())
    {
        $calc = new CosteFluctuacionCalculator($this->conexionBDTPV());
        $agrupacion = $parametros['opcion'] ?? ($parametros['agrupacion'] ?? 'articulo');

        return $calc->calcular([
            'fecha_inicio' => $parametros['Finicio'] ?? date('Y-01-01'),
            'fecha_final' => $parametros['Ffinal'] ?? date('Y-m-d'),
            'min_recepciones' => (int)($parametros['min_recepciones'] ?? 3),
            'min_meses' => (int)($parametros['min_meses'] ?? 3),
            'incluir_proveedor_especial' => (int)($parametros['incluir_proveedor_especial'] ?? 0),
            'familias' => (string)($parametros['familias'] ?? ''),
            'agrupacion' => (string)$agrupacion,
        ]);
    }

    private function buildFiltroRangoFechaSQL(string $campoFecha, string $fechaInicio, string $fechaFinal): string
    {
        return "{$campoFecha} >= '{$fechaInicio}' AND {$campoFecha} < DATE_ADD('{$fechaFinal}', INTERVAL 1 DAY)";
    }
}
