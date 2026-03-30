<?php
include_once $URLCom . '/clases/ClaseTFModelo.php';
include_once $URLCom . '/modulos/mod_proveedor/clases/ClaseProveedor.php';
class ClaseInformes extends TFModelo
{
    public $informes = array(
        '1' => array(
            'Titulo' => 'Informe de Compras por Proveedores.',
            'opciones' => array(
                '1' => 'Todos', // Todos los provedores y albaranes (esten o no facturados.)
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
        } else {
            $datos = [];
        }

        $cabecera = array(
            'id'             => $id,
            'titulo_informe' => $this->informes[$id]['Titulo'],
            'Fecha_Inicio'   => $parametros['Finicio'],
            'Fecha_Final'    => $parametros['Ffinal'],
            'opcion'         => $parametros['opcion'],
            'resumen_global' => $parametros['resumen_global'] ?? null
        );

        return array(
            'datos'    => $datos,
            'cabecera' => $cabecera
        );
    }

    public function ResumenProveedores($parametros = array())
    {
        // @Objetivo:
        // Sumas los albaranes de los proveedores y por las fechas que indiquemos en los parametros.
        // @ Parametros
        // Array (  [id] => (int) Indica el Informe que vamos hacer.
        //          [titulo_informe] => (String) Nombre del informe ,
        //          [Fecha_Inicio] => (fecha Y-m-d),
        //          [Fecha_Final] => (fecha Y-m-d),
        //          [opcion] => (int) Indica el la opcion seleccionada para realizar filtros.
        // @ Devolvemos
        $BDTpv = $this->conexionBDTPV();
        $CProveedor = new ClaseProveedor($BDTpv);
        // Tratamos parametros para añadir ids_proveedores y tratarlos
        $opcion = $parametros['opcion'];
        $id_informe = $parametros['id'];

        // Cargamos los ids de los proveedores que indicamos.
        if ($this->informes[$id_informe]['opciones'][$opcion] == 'Todos') {
            $todosProveedores = $CProveedor->obtenerProveedores();
            $ids = $this->ObtenerIdsArray($todosProveedores, 'idProveedor');
        }
        // ----     Cargamos los albaranes de todos los proveedores   ------ //
        foreach ($ids as $key => $idProveedor) {
            $errores = array();
            $fechaInicial = $parametros['Finicio'];
            $fechaFinal = $parametros['Ffinal'];
            $datosProveedor = $CProveedor->getProveedor($idProveedor);
            if (!isset($datosProveedor['datos'])) {
                $errores[1] = array(
                    'tipo' => 'DANGER!',
                    'dato' => $datosProveedor['consulta'],
                    'class' => 'alert alert-danger',
                    'mensaje' => 'Error al obtener datos proveedor ' . $idProveedor . ',No debe existir proveedor'
                );
            } else {
                $resumenProveedor = $CProveedor->albaranesProveedoresFechas($idProveedor, $fechaInicial, $fechaFinal);
                if (isset($resumenProveedor['error'])) {
                    // Puede ser varios error, trae un array(tipo,mensaje)
                    $errores[2] = $resumenProveedor['error'];
                }
            }
            if (count($errores) > 0) {
                // error_log('***************************************');
                // error_log('Error en ResumenProveedores de ClaseInformes:'.json_encode($errores));
                $todosProveedores[$key]['errores'] = $resumenProveedor;
            } else {
                // Creamos la propiedad de albaranes y cant_albaranes
                $todosProveedores[$key]['cant_albaranes'] = count($resumenProveedor);
                $todosProveedores[$key]['albaranes'] = $resumenProveedor;
            }
        }
        // ----     Fin obtener los albaranes de todos los proveedores   ------ //

        // Ahora tenemos ordenar y hacer las sumas de lineas albaranes por producto y totales por proveedor.
        $ArrayProductos = [];
        $SumaAlbaranes = [];
        $DesgloseAlbaranes = [];
        foreach ($todosProveedores as $key => $proveedor) {
            if (isset($proveedor['albaranes']['productos'])) {
                $p = $CProveedor->SumaLineasAlbaranesProveedores($proveedor['albaranes']['productos']);
                // Ahora montamos array con todos los productos comprado de cada proveedor para luego sumarlos,
                // es decir tener un array con la suma de todos los productos, de todos los proveedores en el intervalo de
                // tiempo que indicamos.
                $ArrayProductos[] = $p;
                // Ahora añadimo propiedad "cant_referencias", que indica (int) la cantidad de referencias compradas
                // en esos albaranes.
                $todosProveedores[$key]['referencias_productos'] = count($p);
                // Ahora sumar el desglose .


                $SumaAlbaranes[] = $proveedor['albaranes']['resumenBases'];
                $SumaAlbaranes[] = $todosProveedores[$key];

                $DesgloseAlbaranes[] = $proveedor['albaranes']['resumenBases'];
            }
        }

        /* Queda pendiente sumar los albaranes y los desglose.
         * y ver como controlar cuando queremos filtrar algun proveedor o albaran no facturado.
         * Aquí en el proceso anterior, añadimos [referencias_productos]
         * */
        $Productos = [];
        // Esto es necesario ya que tenemos varios array con productos, uno por cada Proveedor.
        foreach ($ArrayProductos as $P) {
            foreach ($P as $producto) {
                $Productos[] = $producto;
            }
        }
        // Ahora sumamos todos los productos ( deberíamos controlar si hay mas de un proveedor), ya que no tiene sentido, si es uno

        $Productos = $this->SumaProductosTodosProveedores($Productos);
        // Ahora tenemos los productos sumados de todos los albaranes de todos los proveedores..

        // Montamos lo que devolvemos..
        // Hay que tener en cuenta que la memoria es limitada, por a lo mejor sería bueno devolver solo informe , no los datos, deberíamos
        // utilizar uset si lo quisieramos hacer.
        // ya todosProveedores no hacen falta para obtener resto datos :
        // Nombre
        // idProveedor
        // cant_albaranes  ( este dato en esta metodo)
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
        // @ Objetivo
        // Obtener un array con la suma de productos comprados con su precio coste medio del array que recibimos.
        // @ Parametros:
        // $productos -> Es un array que puede trae :Array
        //(
        //    [idalbpro] => int
        //    [idArticulo] => int
        //    [totalUnidades] => float
        //    [costeSiva] =>float
        //    [coste_medio] => float
        //    [num_compras] => int
        //    [total_linea] => float
        //)

        $totalProductos = 0;
        $totalLineas = 0;
        /* $cdetalleArray = $this->ObtenerIdsArray($LineasProductos,'cdetalle');
        array_multisort($cdetalleArray, SORT_ASC, $LineasProductos); */

        $Productos = []; // inicializa tabla que aparece como resumen productos
        foreach ($LineasProductos as $producto) {
            $id_producto = $producto['idArticulo'];
            // Eliminamos propiedad de idalbpro ya que no es necesario.
            unset($producto['idalbpro']);
            if (array_key_exists($id_producto, $Productos) == false) { // busca el indice. Si no existe lo crea con $producto
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
            } else {  // Si ya existe suma las unidades y calcula el precio medio
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
        // Una vez terminado, Volvemos a recorrer el array para quitar indice que pusimos como el idArticulo.
        $respuesta = [];
        foreach ($Productos  as $producto) {
            $respuesta[] = $producto;
        }
        return $respuesta;
    }

    public function ObtenerIdsArray($datos, $campo)
    {
        // @ Objetivo
        // Obtener un array con los datos de un campo determinado.
        $valores = [];
        foreach ($datos as $dato) {
            $valores[] = $dato[$campo];
        }
        return $valores;
    }

    public function ResumenFamilias($parametros = array())
    {
        // @ Objetivo
        // Suma las líneas de albaranes de proveedor agrupadas por la jerarquía de familias
        // (N1 = familia raíz, N2 = subfamilia) usando la vista vw_jerarquias_familias.
        // @ Parámetros
        //   Finicio  (Y-m-d)
        //   Ffinal   (Y-m-d)
        // @ Devuelve  array indexado de familias N1:
        //   [ idN1, nombreN1, total_linea, num_referencias,
        //     subfamilias => [ idN2, nombreN2, total_linea, num_referencias,
        //                      articulos => [ idArticulo, totalUnidades, costeSiva,
        //                                     coste_medio, num_compras, total_linea ] ] ]

        $BDTpv       = $this->conexionBDTPV();
        $fechaInicio = $parametros['Finicio'];
        $fechaFinal  = $parametros['Ffinal'];

        $filtroN1         = '';
        $virtualHierarchy = null;
        $needsIdFamilia   = false;
        if ((int)$parametros['opcion'] === 4) {
            $ids = array_values(array_filter(array_map('intval', explode(',', $parametros['familias'] ?? ''))));
            if (!empty($ids)) {
                $fop4             = $this->_buildFiltroOp4($BDTpv, $ids);
                $filtroN1         = $fop4['filtroSQL'];
                $virtualHierarchy = $fop4['virtualHierarchy'];
                $needsIdFamilia   = $fop4['needsIdFamilia'];
            }
        }

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
            WHERE a.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND l.estadoLinea <> 'Eliminado'
              $filtroN1
            GROUP BY vj.idN1, vj.idN2, l.idArticulo, l.costeSiva $groupByFamId
            ORDER BY nombreN1, nombreN2, l.idArticulo
        ";

        $smt    = $BDTpv->query($sql);
        $lineas = [];
        while ($row = $smt->fetch_assoc()) {
            $lineas[] = $row;
        }

        // ── Construir estructura jerárquica ──────────────────────────────────
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
                // Coste medio ponderado si el precio varió entre albaranes
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

        // ── Calcular subtotales y reindexar ──────────────────────────────────
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
        // @ Objetivo
        // Suma las líneas de ventas (albaranes + tickets) agrupadas por jerarquía de familias.
        // Misma estructura de respuesta que ResumenFamilias pero sobre ventas.
        // @ Parámetros: Finicio (Y-m-d), Ffinal (Y-m-d)

        $BDTpv       = $this->conexionBDTPV();
        $fechaInicio = $BDTpv->real_escape_string($parametros['Finicio']);
        $fechaFinal  = $BDTpv->real_escape_string($parametros['Ffinal']);

        $filtroN1         = '';
        $virtualHierarchy = null;
        $needsIdFamilia   = false;
        if ((int)($parametros['opcion'] ?? 0) === 4) {
            $ids = array_values(array_filter(array_map('intval', explode(',', $parametros['familias'] ?? ''))));
            if (!empty($ids)) {
                $fop4             = $this->_buildFiltroOp4($BDTpv, $ids);
                $filtroN1         = $fop4['filtroSQL'];
                $virtualHierarchy = $fop4['virtualHierarchy'];
                $needsIdFamilia   = $fop4['needsIdFamilia'];
            }
        }

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
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
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
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND h.estado IN ('Cobrado', 'Cerrado')
              AND l.estadoLinea = 'Activo'
              AND (h.idCliente = 0 OR cl.estado != 'Especial')
              $filtroN1
            GROUP BY vj.idN1, vj.idN2, l.idArticulo, l.precioCiva, l.iva $groupByFamId
            ORDER BY nombreN1, nombreN2, idArticulo
        ";

        $smt    = $BDTpv->query($sql);
        $lineas = [];
        while ($row = $smt->fetch_assoc()) {
            $lineas[] = $row;
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
        // @ Objetivo
        // Suma ventas y costes (ultimoCoste) por jerarquía de familias para calcular
        // beneficio bruto y margen porcentual en el período.
        // AVISO: el coste es ultimoCoste en el momento de ejecutar el informe, no histórico.
        // @ Parámetros: Finicio (Y-m-d), Ffinal (Y-m-d)

        $BDTpv       = $this->conexionBDTPV();
        $fechaInicio = $BDTpv->real_escape_string($parametros['Finicio']);
        $fechaFinal  = $BDTpv->real_escape_string($parametros['Ffinal']);

        $filtroN1         = '';
        $virtualHierarchy = null;
        $needsIdFamilia   = false;
        if ((int)($parametros['opcion'] ?? 0) === 4) {
            $ids = array_values(array_filter(array_map('intval', explode(',', $parametros['familias'] ?? ''))));
            if (!empty($ids)) {
                $fop4             = $this->_buildFiltroOp4($BDTpv, $ids);
                $filtroN1         = $fop4['filtroSQL'];
                $virtualHierarchy = $fop4['virtualHierarchy'];
                $needsIdFamilia   = $fop4['needsIdFamilia'];
            }
        }

        // Coste medio ponderado de compra en el período por artículo (excluye proveedores Especial).
        // Si no hubo compra en el período se usa ultimoCoste como fallback (ver COALESCE en el UNION).
        // Solo líneas con nunidades > 0 (excluye devoluciones/abonos que distorsionan el PMP).
        $sqlCoste = "
            SELECT lp.idArticulo,
                   SUM(lp.costeSiva * lp.nunidades) / SUM(lp.nunidades) AS coste_periodo
            FROM albprolinea lp
            JOIN albprot ap ON ap.id = lp.idalbpro
            JOIN proveedores pv ON pv.idProveedor = ap.idProveedor
            WHERE ap.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND ap.estado IN ('Guardado', 'Procesado', 'Facturado')
              AND lp.estadoLinea <> 'Eliminado'
              AND lp.nunidades > 0
              AND pv.estado != 'Especial'
            GROUP BY lp.idArticulo
        ";
        $smtC = $BDTpv->query($sqlCoste);
        $costePeriodo = [];
        while ($rc = $smtC->fetch_assoc()) {
            $costePeriodo[(int)$rc['idArticulo']] = (float)$rc['coste_periodo'];
        }

        // Mermas declaradas: albaranes de clientes Especial en el período.
        // Representan unidades que salieron del inventario sin generar ingreso (caducados,
        // descartes, tirados). Su coste se resta del beneficio.
        // Cuando hay filtro de familias (opción 4) la merma también debe limitarse
        // a los artículos de esas familias, igual que las ventas.
        $mermaFamiliaJoin  = '';
        $mermaFamiliaWhere = '';
        if ($filtroN1 !== '') {
            if ($virtualHierarchy === null) {
                // filtro por idN1
                $mermaFamiliaJoin  = 'LEFT JOIN articulosFamilias afM ON afM.idArticulo = l.idArticulo
            LEFT JOIN vw_jerarquias_familias vjM ON vjM.idFamilia = afM.idFamilia';
                $mermaFamiliaWhere = str_replace('vj.idN1', 'vjM.idN1', $filtroN1);
            } else {
                // filtro por idFamilia descendiente
                preg_match('/IN\s*\(([^)]+)\)/', $filtroN1, $mMerma);
                $mermaIds          = $mMerma[1] ?? '0';
                $mermaFamiliaJoin  = 'LEFT JOIN articulosFamilias afM ON afM.idArticulo = l.idArticulo';
                $mermaFamiliaWhere = "AND afM.idFamilia IN ($mermaIds)";
            }
        }

        $sqlMerma = "
            SELECT
                l.idArticulo,
                SUM(l.nunidades) AS unidades_merma
            FROM albclilinea l
            JOIN albclit h ON h.id = l.idalbcli
            JOIN clientes cl ON cl.idClientes = h.idCliente
            $mermaFamiliaJoin
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND h.estado IN ('Guardado', 'Procesado')
              AND l.estadoLinea = 'Activo'
              AND cl.estado = 'Especial'
              $mermaFamiliaWhere
            GROUP BY l.idArticulo
        ";
        $smtM = $BDTpv->query($sqlMerma);
        $mermasPorArticulo = [];
        while ($rm = $smtM->fetch_assoc()) {
            $mermasPorArticulo[(int)$rm['idArticulo']] = (float)$rm['unidades_merma'];
        }

        // Cuando se usa jerarquía virtual (familia no-N1 seleccionada) necesitamos af.idFamilia
        // en el SELECT y GROUP BY para poder asignar la fila al virtual N1/N2 correcto.
        $selectFamId  = $needsIdFamilia ? ', af.idFamilia AS familiaDirecta' : ', NULL AS familiaDirecta';
        $groupByFamId = $needsIdFamilia ? ', af.idFamilia'                   : '';

        $sql = "
            SELECT
                vj.idN1,
                MAX(n1.familiaNombre)                                        AS nombreN1,
                vj.idN2,
                MAX(n2.familiaNombre)                                        AS nombreN2,
                l.idArticulo,
                MAX(ar.articulo_name)                                        AS articulo_name,
                MAX(ar.tipo)                                                 AS tipo,
                l.precioCiva / (1 + l.iva / 100)                            AS pvpSiva,
                MAX(ar.ultimoCoste)                                          AS ultimoCoste,
                SUM(l.nunidades)                                             AS totalUnidades,
                SUM(l.precioCiva / (1 + l.iva / 100) * l.nunidades)         AS totalVenta,
                COUNT(DISTINCT l.idalbcli)                                   AS num_documentos
                $selectFamId
            FROM albclilinea l
            JOIN albclit h ON h.id = l.idalbcli
            JOIN articulos ar ON ar.idArticulo = l.idArticulo
            LEFT JOIN clientes cl ON cl.idClientes = h.idCliente
            LEFT JOIN articulosFamilias af ON af.idArticulo = l.idArticulo
            LEFT JOIN vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            LEFT JOIN vw_jerarquias_familias n1 ON n1.idFamilia = vj.idN1
            LEFT JOIN vw_jerarquias_familias n2 ON n2.idFamilia = vj.idN2
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND h.estado IN ('Guardado', 'Procesado')
              AND l.estadoLinea = 'Activo'
              AND (h.idCliente = 0 OR cl.estado != 'Especial')
              $filtroN1
            GROUP BY vj.idN1, vj.idN2, l.idArticulo, l.precioCiva, l.iva $groupByFamId

            UNION ALL

            SELECT
                vj.idN1,
                MAX(n1.familiaNombre)                                        AS nombreN1,
                vj.idN2,
                MAX(n2.familiaNombre)                                        AS nombreN2,
                l.idArticulo,
                MAX(ar.articulo_name)                                        AS articulo_name,
                MAX(ar.tipo)                                                 AS tipo,
                l.precioCiva / (1 + l.iva / 100)                            AS pvpSiva,
                MAX(ar.ultimoCoste)                                          AS ultimoCoste,
                SUM(l.nunidades)                                             AS totalUnidades,
                SUM(l.precioCiva / (1 + l.iva / 100) * l.nunidades)         AS totalVenta,
                COUNT(DISTINCT l.idticketst)                                 AS num_documentos
                $selectFamId
            FROM ticketslinea l
            JOIN ticketst h ON h.id = l.idticketst
            JOIN articulos ar ON ar.idArticulo = l.idArticulo
            LEFT JOIN clientes cl ON cl.idClientes = h.idCliente
            LEFT JOIN articulosFamilias af ON af.idArticulo = l.idArticulo
            LEFT JOIN vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            LEFT JOIN vw_jerarquias_familias n1 ON n1.idFamilia = vj.idN1
            LEFT JOIN vw_jerarquias_familias n2 ON n2.idFamilia = vj.idN2
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND h.estado IN ('Cobrado', 'Cerrado')
              AND l.estadoLinea = 'Activo'
              AND (h.idCliente = 0 OR cl.estado != 'Especial')
              $filtroN1
            GROUP BY vj.idN1, vj.idN2, l.idArticulo, l.precioCiva, l.iva $groupByFamId
            ORDER BY nombreN1, nombreN2, idArticulo
        ";

        $smt    = $BDTpv->query($sql);
        $lineas = [];
        while ($row = $smt->fetch_assoc()) {
            $lineas[] = $row;
        }

        $familias = [];

        foreach ($lineas as $fila) {
            // Si se usa jerarquía virtual, remapear N1/N2 a partir del idFamilia real del artículo
            if ($virtualHierarchy !== null && isset($fila['familiaDirecta'])) {
                $famId = (int)$fila['familiaDirecta'];
                $vh    = $virtualHierarchy[$famId] ?? null;
                if ($vh !== null) {
                    $idN1     = $vh['vN1'];
                    $nombreN1 = $vh['vN1Name'];
                    $idN2     = $vh['vN2'];
                    $keyN2    = $idN2 !== null ? $idN2 : '__sin_n2__';
                    $labelN2  = $idN2 !== null ? ($vh['vN2Name'] ?? 'Sin subfamilia') : '(Sin subfamilia)';
                } else {
                    // Familia fuera del virtual map (no debería ocurrir con el filtro SQL)
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
            // Coste medio ponderado del período si hubo compra; si no, ultimoCoste actual
            $costeUsar = isset($costePeriodo[$idArt]) ? $costePeriodo[$idArt] : (float)$fila['ultimoCoste'];
            $tv       = (float)$fila['totalVenta'];
            $tc       = $costeUsar * (float)$fila['totalUnidades'];

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

            $udsMerma   = $mermasPorArticulo[$idArt] ?? 0.0;
            $valorMerma = $udsMerma * $costeUsar;

            if (!isset($art['idArticulo'])) {
                $art = [
                    'idArticulo'       => $idArt,
                    'articulo_name'    => $fila['articulo_name'],
                    'tipo'             => $fila['tipo'],
                    'totalUnidades'    => (float)$fila['totalUnidades'],
                    'pvpSiva'          => (float)$fila['pvpSiva'],
                    'costeUsado'       => $costeUsar,
                    'coste_es_periodo' => isset($costePeriodo[$idArt]),
                    'totalVenta'       => $tv,
                    'totalCoste'       => $tc,
                    'udsMerma'         => $udsMerma,
                    'valorMerma'       => $valorMerma,
                    'num_ventas'       => (int)$fila['num_documentos']
                ];
            } else {
                $art['totalUnidades'] += (float)$fila['totalUnidades'];
                $art['totalVenta']    += $tv;
                $art['totalCoste']    += $costeUsar * (float)$fila['totalUnidades'];
                $art['num_ventas']    += (int)$fila['num_documentos'];
                // udsMerma y valorMerma ya están fijados por idArticulo, no acumulan por precio
            }
            $art['pvpSiva']    = $art['totalUnidades'] > 0
                ? $art['totalVenta'] / $art['totalUnidades']
                : 0;
            $art['beneficio']  = $art['totalVenta'] - $art['totalCoste'] - $art['valorMerma'];
            $art['margen_pct'] = $art['totalVenta'] > 0
                ? round($art['beneficio'] / $art['totalVenta'] * 100, 2)
                : 0;

            unset($art);
        }

        $resultado = [];
        foreach ($familias as $familia) {
            $tvN1    = 0;
            $tcN1    = 0;
            $tmN1    = 0;
            $refsN1  = [];
            $sfs     = [];

            foreach ($familia['subfamilias'] as $sf) {
                $arts  = array_values($sf['articulos']);
                $tvN2  = 0;
                $tcN2  = 0;
                $tmN2  = 0;
                foreach ($arts as $art) {
                    $tvN2 += $art['totalVenta'];
                    $tcN2 += $art['totalCoste'];
                    $tmN2 += $art['valorMerma'];
                    $refsN1[$art['idArticulo']] = true;
                }
                $tvN1 += $tvN2;
                $tcN1 += $tcN2;
                $tmN1 += $tmN2;

                $benN2 = $tvN2 - $tcN2 - $tmN2;
                $arts_sorted = $arts;
                usort($arts_sorted, fn($a, $b) => $b['totalVenta'] <=> $a['totalVenta']);
                $sfs[] = [
                    'idN2'            => $sf['idN2'],
                    'nombreN2'        => $sf['nombreN2'],
                    'totalVenta'      => $tvN2,
                    'totalCoste'      => $tcN2,
                    'totalMerma'      => $tmN2,
                    'beneficio'       => $benN2,
                    'margen_pct'      => $tvN2 > 0 ? round($benN2 / $tvN2 * 100, 2) : 0,
                    'num_referencias' => count($arts_sorted),
                    'articulos'       => $arts_sorted
                ];
            }
            usort($sfs, fn($a, $b) => $b['totalVenta'] <=> $a['totalVenta']);

            $benN1 = $tvN1 - $tcN1 - $tmN1;
            $resultado[] = [
                'idN1'            => $familia['idN1'],
                'nombreN1'        => $familia['nombreN1'],
                'totalVenta'      => $tvN1,
                'totalCoste'      => $tcN1,
                'totalMerma'      => $tmN1,
                'beneficio'       => $benN1,
                'margen_pct'      => $tvN1 > 0 ? round($benN1 / $tvN1 * 100, 2) : 0,
                'num_referencias' => count($refsN1),
                'subfamilias'     => $sfs
            ];
        }
        usort($resultado, fn($a, $b) => $b['totalVenta'] <=> $a['totalVenta']);

        // ── Resumen global ─────────────────────────────────────────────────────
        // Iteramos por artículo único para evitar duplicar artículos en varias familias.
        $gtVenta = 0;
        $gtCoste = 0;
        $gtMerma = 0;
        $articulosConVentas = [];
        foreach ($resultado as $fam) {
            foreach ($fam['subfamilias'] as $sf) {
                foreach ($sf['articulos'] as $art) {
                    $idArt = $art['idArticulo'];
                    if (isset($articulosConVentas[$idArt])) continue;
                    $articulosConVentas[$idArt] = true;
                    $gtVenta += $art['totalVenta'];
                    $gtCoste += $art['totalCoste'];
                    $gtMerma += $art['valorMerma'];
                }
            }
        }

        // Artículos con merma en el período pero sin ninguna venta capturada
        // (no aparecen en la tabla de familias)
        $mermaSinVentas = [];
        $gtMermaSinVentas = 0;
        foreach ($mermasPorArticulo as $idArt => $uds) {
            if (isset($articulosConVentas[$idArt])) continue;
            // Obtener nombre y ultimoCoste
            $rArt = $BDTpv->query(
                "SELECT articulo_name, ultimoCoste FROM articulos WHERE idArticulo = $idArt LIMIT 1"
            );
            if (!$rArt || $rArt->num_rows === 0) continue;
            $datoArt = $rArt->fetch_assoc();
            $costeArt = isset($costePeriodo[$idArt]) ? $costePeriodo[$idArt] : (float)$datoArt['ultimoCoste'];
            $valor = $uds * $costeArt;
            $gtMermaSinVentas += $valor;
            $mermaSinVentas[] = [
                'idArticulo'    => $idArt,
                'articulo_name' => $datoArt['articulo_name'],
                'udsMerma'      => $uds,
                'costeUsar'     => $costeArt,
                'valorMerma'    => $valor,
            ];
        }
        usort($mermaSinVentas, fn($a, $b) => $b['valorMerma'] <=> $a['valorMerma']);

        $gtMermaTotal  = $gtMerma + $gtMermaSinVentas;
        $gtBeneficio   = $gtVenta - $gtCoste - $gtMermaTotal;
        $gtMargen      = $gtVenta > 0 ? round($gtBeneficio / $gtVenta * 100, 2) : 0;

        // ── Vista de flujo: compras reales del período ─────────────────────
        // Suma directa de albaranes de compra (sin estimaciones de PMP).
        // Cuando hay filtro de familias (opción 4), se aplica también aquí para coherencia.

        // Fragmentos SQL para aplicar el filtro de familia a las consultas de flujo.
        // Las consultas globales no tienen JOIN de familias, hay que añadirlos cuando se filtra.
        // Las consultas per-N1 ya tienen los JOINs af/vj, solo hay que añadir el WHERE.
        // Con jerarquía virtual, agrupamos por af.idFamilia y mapeamos al virtual N1 en PHP.
        $filtroFlujoGlobalJoinC  = ''; // JOIN extra para compras globales (alias afF/vjF)
        $filtroFlujoGlobalWhereC = ''; // WHERE extra para compras globales
        $filtroFlujoGlobalJoinV  = ''; // JOIN extra para ventas globales (alias afFv/vjFv)
        $filtroFlujoGlobalWhereV = ''; // WHERE extra para ventas globales
        $filtroFlujoN1Where      = ''; // WHERE extra para consultas per-N1 (ya tienen af/vj)
        $flujoGroupByKey         = 'vj.idN1'; // columna de agrupación per-N1

        if ($filtroN1 !== '') {
            if ($virtualHierarchy === null) {
                // Filtro normal por idN1.
                // Usamos EXISTS en lugar de JOIN para evitar multiplicar filas cuando un artículo
                // tiene varias entradas en articulosFamilias que mapean al mismo idN1
                // (ej: artículo asignado a 2 subfamilias del mismo N1 → JOIN generaría 2 filas).
                preg_match('/IN\s*\(([^)]+)\)/', $filtroN1, $mN1);
                $n1Str = $mN1[1] ?? '0';
                $filtroFlujoGlobalJoinC  = '';
                $filtroFlujoGlobalWhereC = "AND EXISTS (
                    SELECT 1 FROM articulosFamilias afF
                    JOIN vw_jerarquias_familias vjF ON vjF.idFamilia = afF.idFamilia
                    WHERE afF.idArticulo = lp.idArticulo AND vjF.idN1 IN ($n1Str)
                )";
                $filtroFlujoGlobalJoinV  = '';
                $filtroFlujoGlobalWhereV = "AND EXISTS (
                    SELECT 1 FROM articulosFamilias afFv
                    JOIN vw_jerarquias_familias vjFv ON vjFv.idFamilia = afFv.idFamilia
                    WHERE afFv.idArticulo = l.idArticulo AND vjFv.idN1 IN ($n1Str)
                )";
                $filtroFlujoN1Where      = $filtroN1;
                $flujoGroupByKey         = 'vj.idN1';
            } else {
                // Filtro por idFamilia descendiente: solo necesita JOIN articulosFamilias
                // Extraemos la lista de IDs del fragmento "AND af.idFamilia IN (...)"
                preg_match('/IN\s*\(([^)]+)\)/', $filtroN1, $m);
                $descStr = $m[1] ?? '0';
                $filtroFlujoGlobalJoinC  = '';
                $filtroFlujoGlobalWhereC = "AND EXISTS (SELECT 1 FROM articulosFamilias afF WHERE afF.idArticulo = lp.idArticulo AND afF.idFamilia IN ($descStr))";
                $filtroFlujoGlobalJoinV  = '';
                $filtroFlujoGlobalWhereV = "AND EXISTS (SELECT 1 FROM articulosFamilias afFv WHERE afFv.idArticulo = l.idArticulo AND afFv.idFamilia IN ($descStr))";
                $filtroFlujoN1Where      = $filtroN1;   // ya tiene af JOIN
                $flujoGroupByKey         = 'af.idFamilia'; // agrupar por familia real; mapear a virtual N1 en PHP
            }
        }

        // Compras globales (sin JOIN de familia — fuente canónica sin duplicados cuando no se filtra)
        $sqlFlujoComprasGlobal = "
            SELECT
                SUM(lp.costeSiva * lp.nunidades)                        AS compras_siva,
                SUM(lp.costeSiva * (1 + ar.iva/100) * lp.nunidades)    AS compras_civa
            FROM albprolinea lp
            JOIN albprot ap     ON ap.id           = lp.idalbpro
            JOIN proveedores pv ON pv.idProveedor  = ap.idProveedor
            JOIN articulos ar   ON ar.idArticulo   = lp.idArticulo
            $filtroFlujoGlobalJoinC
            WHERE ap.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND ap.estado IN ('Guardado','Procesado','Facturado')
              AND lp.estadoLinea <> 'Eliminado'
              AND pv.estado != 'Especial'
              $filtroFlujoGlobalWhereC
        ";
        $smtFCG = $BDTpv->query($sqlFlujoComprasGlobal);
        $rowFCG = $smtFCG->fetch_assoc();
        $gtComprasSiva = (float)($rowFCG['compras_siva'] ?? 0);
        $gtComprasCiva = (float)($rowFCG['compras_civa'] ?? 0);

        // Compras por familia: agrupa por $flujoGroupByKey para soportar jerarquía virtual
        $sqlFlujoCompras = "
            SELECT
                $flujoGroupByKey AS flujo_key,
                SUM(lp.costeSiva * lp.nunidades)                        AS compras_siva,
                SUM(lp.costeSiva * (1 + ar.iva/100) * lp.nunidades)    AS compras_civa
            FROM albprolinea lp
            JOIN albprot ap        ON ap.id          = lp.idalbpro
            JOIN proveedores pv    ON pv.idProveedor = ap.idProveedor
            JOIN articulos ar      ON ar.idArticulo  = lp.idArticulo
            LEFT JOIN articulosFamilias af  ON af.idArticulo = lp.idArticulo
            LEFT JOIN vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            WHERE ap.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND ap.estado IN ('Guardado','Procesado','Facturado')
              AND lp.estadoLinea <> 'Eliminado'
              AND pv.estado != 'Especial'
              $filtroFlujoN1Where
            GROUP BY $flujoGroupByKey
        ";
        $smtFC = $BDTpv->query($sqlFlujoCompras);
        $flujoComprasPorN1 = [];
        while ($rfc = $smtFC->fetch_assoc()) {
            $rawKey = $rfc['flujo_key'];
            // Con jerarquía virtual, mapear idFamilia real → virtual N1
            if ($virtualHierarchy !== null && $rawKey !== null) {
                $vN1 = $virtualHierarchy[(int)$rawKey]['vN1'] ?? null;
                $idN1key = $vN1 !== null ? $vN1 : '__sin_familia__';
            } else {
                $idN1key = $rawKey !== null ? (int)$rawKey : '__sin_familia__';
            }
            $flujoComprasPorN1[$idN1key]['compras_siva'] = ($flujoComprasPorN1[$idN1key]['compras_siva'] ?? 0) + (float)$rfc['compras_siva'];
            $flujoComprasPorN1[$idN1key]['compras_civa'] = ($flujoComprasPorN1[$idN1key]['compras_civa'] ?? 0) + (float)$rfc['compras_civa'];
        }

        // Ventas globales para el flujo
        $sqlFlujoVentas = "
            SELECT
                SUM(l.precioCiva * l.nunidades) AS ventas_civa,
                SUM(l.precioCiva / (1 + l.iva/100) * l.nunidades) AS ventas_siva
            FROM albclilinea l
            JOIN albclit h ON h.id = l.idalbcli
            LEFT JOIN clientes cl ON cl.idClientes = h.idCliente
            $filtroFlujoGlobalJoinV
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND h.estado IN ('Guardado','Procesado')
              AND l.estadoLinea = 'Activo'
              AND (h.idCliente = 0 OR cl.estado != 'Especial')
              $filtroFlujoGlobalWhereV
            UNION ALL
            SELECT
                SUM(l.precioCiva * l.nunidades) AS ventas_civa,
                SUM(l.precioCiva / (1 + l.iva/100) * l.nunidades) AS ventas_siva
            FROM ticketslinea l
            JOIN ticketst h ON h.id = l.idticketst
            LEFT JOIN clientes cl ON cl.idClientes = h.idCliente
            $filtroFlujoGlobalJoinV
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND h.estado IN ('Cobrado','Cerrado')
              AND l.estadoLinea = 'Activo'
              AND (h.idCliente = 0 OR cl.estado != 'Especial')
              $filtroFlujoGlobalWhereV
        ";
        $smtFV = $BDTpv->query($sqlFlujoVentas);
        $gtVentasCiva = 0;
        $gtVentasSiva = 0;
        while ($rfv = $smtFV->fetch_assoc()) {
            $gtVentasCiva += (float)$rfv['ventas_civa'];
            $gtVentasSiva += (float)$rfv['ventas_siva'];
        }

        // Ventas por familia para el panel por familia
        $sqlFlujoVentasN1 = "
            SELECT $flujoGroupByKey AS flujo_key,
                SUM(l.precioCiva * l.nunidades)                      AS ventas_civa,
                SUM(l.precioCiva / (1 + l.iva/100) * l.nunidades)   AS ventas_siva
            FROM albclilinea l
            JOIN albclit h ON h.id = l.idalbcli
            LEFT JOIN clientes cl ON cl.idClientes = h.idCliente
            LEFT JOIN articulosFamilias af ON af.idArticulo = l.idArticulo
            LEFT JOIN vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND h.estado IN ('Guardado','Procesado')
              AND l.estadoLinea = 'Activo'
              AND (h.idCliente = 0 OR cl.estado != 'Especial')
              $filtroFlujoN1Where
            GROUP BY $flujoGroupByKey
            UNION ALL
            SELECT $flujoGroupByKey AS flujo_key,
                SUM(l.precioCiva * l.nunidades)                      AS ventas_civa,
                SUM(l.precioCiva / (1 + l.iva/100) * l.nunidades)   AS ventas_siva
            FROM ticketslinea l
            JOIN ticketst h ON h.id = l.idticketst
            LEFT JOIN clientes cl ON cl.idClientes = h.idCliente
            LEFT JOIN articulosFamilias af ON af.idArticulo = l.idArticulo
            LEFT JOIN vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND h.estado IN ('Cobrado','Cerrado')
              AND l.estadoLinea = 'Activo'
              AND (h.idCliente = 0 OR cl.estado != 'Especial')
              $filtroFlujoN1Where
            GROUP BY $flujoGroupByKey
        ";
        $smtFVN1 = $BDTpv->query($sqlFlujoVentasN1);
        $flujoVentasPorN1 = [];
        while ($rfvn1 = $smtFVN1->fetch_assoc()) {
            $rawKey = $rfvn1['flujo_key'];
            if ($virtualHierarchy !== null && $rawKey !== null) {
                $vN1 = $virtualHierarchy[(int)$rawKey]['vN1'] ?? null;
                $idN1key = $vN1 !== null ? $vN1 : '__sin_familia__';
            } else {
                $idN1key = $rawKey !== null ? (int)$rawKey : '__sin_familia__';
            }
            $flujoVentasPorN1[$idN1key]['ventas_civa'] = ($flujoVentasPorN1[$idN1key]['ventas_civa'] ?? 0) + (float)$rfvn1['ventas_civa'];
            $flujoVentasPorN1[$idN1key]['ventas_siva'] = ($flujoVentasPorN1[$idN1key]['ventas_siva'] ?? 0) + (float)$rfvn1['ventas_siva'];
        }

        // ── Stock medio del período para Rotación y GMROI ────────────────
        // La BD es anualizada: toda la información de stock está en los movimientos del año.
        // Reconstrucción pura desde movimientos (sin articulosStocks):
        //   stock_en_D = SUM(entradas − salidas desde 1ene hasta D)
        //   stock_fin    = reconstruido(1ene → fechaFinal)
        //   stock_inicio = reconstruido(1ene → fechaInicio−1)   [0 si fechaInicio=1ene]
        //   stock_medio  = (stock_inicio + stock_fin) / 2
        $inicioAno = date('Y', strtotime($fechaFinal)) . '-01-01';
        $vispera   = date('Y-m-d', strtotime($fechaInicio . ' -1 day'));

        // Reconstruye unidades netas acumuladas desde 1ene hasta $hasta (inclusive)
        $fnStockEn = function(string $hasta) use ($BDTpv, $inicioAno): array {
            // Si $hasta < $inicioAno (período empieza el 1 ene → víspera = 31 dic año anterior)
            // devolvemos array vacío → stock inicio = 0
            if ($hasta < $inicioAno) return [];
            $smt = $BDTpv->query("
                SELECT idArticulo, SUM(delta) AS neto
                FROM (
                    SELECT l.idArticulo,  l.nunidades AS delta
                    FROM albprolinea l
                    JOIN albprot c ON c.id = l.idalbpro
                    WHERE DATE(c.Fecha) BETWEEN '$inicioAno' AND '$hasta'
                      AND c.estado IN ('Guardado','Facturado','Exportado','Importado')
                      AND l.estadoLinea = 'Activo'
                    UNION ALL
                    SELECT l.idArticulo, -l.nunidades AS delta
                    FROM ticketslinea l
                    JOIN ticketst c ON c.id = l.idticketst
                    WHERE DATE(c.Fecha) BETWEEN '$inicioAno' AND '$hasta'
                      AND c.estado = 'Cerrado'
                      AND l.estadoLinea = 'Activo'
                    UNION ALL
                    SELECT l.idArticulo, -l.nunidades AS delta
                    FROM albclilinea l
                    JOIN albclit c ON c.id = l.idalbcli
                    LEFT JOIN clientes cl ON cl.idClientes = c.idCliente
                    WHERE DATE(c.Fecha) BETWEEN '$inicioAno' AND '$hasta'
                      AND c.estado IN ('Guardado','Procesado')
                      AND l.estadoLinea = 'Activo'
                      AND (c.idCliente = 0 OR cl.estado != 'Especial')
                ) AS movs
                GROUP BY idArticulo
            ");
            $result = [];
            while ($r = $smt->fetch_assoc()) {
                $result[(int)$r['idArticulo']] = (float)$r['neto'];
            }
            return $result;
        };

        $stockFinPorArt    = $fnStockEn($fechaFinal);  // stock al cierre del período
        $stockInicioPorArt = $fnStockEn($vispera);     // stock en la víspera (= inicio del período)

        // Obtener ultimoCoste para el fallback de valoración
        $smtUC = $BDTpv->query("SELECT idArticulo, ultimoCoste FROM articulos WHERE ultimoCoste > 0");
        $ultimoCostePorArt = [];
        while ($ruc = $smtUC->fetch_assoc()) {
            $ultimoCostePorArt[(int)$ruc['idArticulo']] = (float)$ruc['ultimoCoste'];
        }

        // stock_medio por artículo = (unidades_inicio + unidades_fin) / 2
        $stockMedioPorArt = [];
        $todosIds = array_unique(array_merge(
            array_keys($stockFinPorArt),
            array_keys($stockInicioPorArt)
        ));
        foreach ($todosIds as $idA) {
            $fin    = $stockFinPorArt[$idA]    ?? 0;
            $inicio = $stockInicioPorArt[$idA] ?? 0;
            $medio  = ($inicio + $fin) / 2;
            if ($medio > 0) {
                $stockMedioPorArt[$idA] = $medio;
            }
        }

        // Calcular valor stock por artículo usando PMP del período o fallback ultimoCoste
        // y acumular por virtual N1 (mismo mapeo que ventas/compras)
        // Para el global: iterar artículos únicos del resultado (evita duplicados multi-familia)
        $stockPorN1  = [];
        $gtValorStock = 0;
        $articulosContadosStock = []; // para el global, contar cada artículo una sola vez

        foreach ($resultado as $fam) {
            $key = $fam['idN1'];
            foreach ($fam['subfamilias'] as $sf) {
                foreach ($sf['articulos'] as $art) {
                    $idA = $art['idArticulo'];
                    if (!isset($stockMedioPorArt[$idA])) continue;
                    $costeArt = $costePeriodo[$idA] ?? $ultimoCostePorArt[$idA] ?? 0;
                    if ($costeArt <= 0) continue;
                    $valorArt = $stockMedioPorArt[$idA] * $costeArt;
                    // Acumular por familia (acepta duplicados multi-familia, igual que tabla)
                    $stockPorN1[$key] = ($stockPorN1[$key] ?? 0) + $valorArt;
                    // Acumular global solo una vez por artículo
                    if (!isset($articulosContadosStock[$idA])) {
                        $articulosContadosStock[$idA] = true;
                        $gtValorStock += $valorArt;
                    }
                }
            }
        }

        // Añadir flujo + stock + GMROI por familia a cada elemento del resultado
        foreach ($resultado as &$fam) {
            $key = $fam['idN1'];
            $vSiva = $flujoVentasPorN1[$key]['ventas_siva'] ?? 0;
            $vCiva = $flujoVentasPorN1[$key]['ventas_civa'] ?? 0;
            $cSiva = $flujoComprasPorN1[$key]['compras_siva'] ?? 0;
            $cCiva = $flujoComprasPorN1[$key]['compras_civa'] ?? 0;
            $fam['flujo'] = [
                'ventas_siva'    => $vSiva,
                'ventas_civa'    => $vCiva,
                'compras_siva'   => $cSiva,
                'compras_civa'   => $cCiva,
                'resultado_siva' => $vSiva - $cSiva,
                'resultado_civa' => $vCiva - $cCiva,
            ];
            $stockFam = $stockPorN1[$key] ?? 0;
            $margenFam = $fam['totalVenta'] - $fam['totalCoste'];
            $fam['valor_stock'] = $stockFam;
            $fam['gmroi']       = ($stockFam > 0) ? round($margenFam / $stockFam, 2) : null;
        }
        unset($fam);

        $gtMargenBruto = $gtVenta - $gtCoste;
        $gtGmroi       = ($gtValorStock > 0) ? round($gtMargenBruto / $gtValorStock, 2) : null;

        return [
            'familias' => $resultado,
            'resumen'  => [
                'totalVenta'        => $gtVenta,
                'totalCoste'        => $gtCoste,
                'totalMerma'        => $gtMerma,
                'mermaSinVentas'    => $gtMermaSinVentas,
                'totalMermaGlobal'  => $gtMermaTotal,
                'beneficio'         => $gtBeneficio,
                'margen_pct'        => $gtMargen,
                'articulos_merma_sin_ventas' => $mermaSinVentas,
                'valor_stock'       => $gtValorStock,
                'gmroi'             => $gtGmroi,
                'flujo' => [
                    'ventas_siva'    => $gtVentasSiva,
                    'ventas_civa'    => $gtVentasCiva,
                    'compras_siva'   => $gtComprasSiva,
                    'compras_civa'   => $gtComprasCiva,
                    'resultado_siva' => $gtVentasSiva - $gtComprasSiva,
                    'resultado_civa' => $gtVentasCiva - $gtComprasCiva,
                ],
            ]
        ];
    }

    /**
     * Construye el filtro SQL y la jerarquía virtual para opción 4 (filtrado por familia).
     *
     * Si todos los IDs seleccionados son nivel=1, devuelve el filtro original con idN1.
     * Si alguno es nivel≥2, construye una jerarquía virtual donde el ID seleccionado actúa
     * como N1 y sus hijos directos como N2, filtrando por af.idFamilia IN (descendientes).
     *
     * @param mysqli $BDTpv
     * @param int[]  $ids    IDs de familias seleccionadas (ya validados como int > 0)
     * @return array ['filtroSQL' => string, 'virtualHierarchy' => array|null, 'needsIdFamilia' => bool]
     *   virtualHierarchy es null cuando todos son nivel=1 (comportamiento original).
     *   virtualHierarchy[idFamilia] = ['vN1'=>int, 'vN1Name'=>string, 'vN2'=>int|null, 'vN2Name'=>string|null]
     */
    private function _buildFiltroOp4($BDTpv, array $ids): array
    {
        if (empty($ids)) {
            return ['filtroSQL' => '', 'virtualHierarchy' => null, 'needsIdFamilia' => false];
        }

        $idsStr = implode(',', $ids);
        $rSel   = $BDTpv->query(
            "SELECT idFamilia, nivel, familiaNombre FROM vw_jerarquias_familias WHERE idFamilia IN ($idsStr)"
        );
        $selectedInfo = [];
        $allN1        = true;
        while ($r = $rSel->fetch_assoc()) {
            $selectedInfo[(int)$r['idFamilia']] = $r;
            if ((int)$r['nivel'] !== 1) {
                $allN1 = false;
            }
        }

        if ($allN1) {
            return [
                'filtroSQL'       => "AND vj.idN1 IN ($idsStr)",
                'virtualHierarchy' => null,
                'needsIdFamilia'  => false,
            ];
        }

        // Jerarquía virtual: construir descendientes y mapeo para cada ID seleccionado
        $virtualHierarchy = [];
        $allDescendants   = [];

        foreach ($ids as $selId) {
            if (!isset($selectedInfo[$selId])) {
                continue;
            }
            $selName = $selectedInfo[$selId]['familiaNombre'];

            // BFS para obtener todos los descendientes
            $descendants = [$selId];
            $famNames    = [$selId => $selName];
            $famParent   = [$selId => null];
            $queue       = [$selId];

            while (!empty($queue)) {
                $qStr = implode(',', $queue);
                $rCh  = $BDTpv->query(
                    "SELECT idFamilia, familiaNombre, familiaPadre
                     FROM vw_jerarquias_familias
                     WHERE familiaPadre IN ($qStr)"
                );
                $queue = [];
                while ($rc = $rCh->fetch_assoc()) {
                    $cId = (int)$rc['idFamilia'];
                    if (!in_array($cId, $descendants)) {
                        $descendants[]   = $cId;
                        $queue[]         = $cId;
                        $famNames[$cId]  = $rc['familiaNombre'];
                        $famParent[$cId] = (int)$rc['familiaPadre'];
                    }
                }
            }

            // Hijos directos del ID seleccionado (virtual N2)
            $directChildren = [];
            foreach ($descendants as $d) {
                if ($d !== $selId && isset($famParent[$d]) && $famParent[$d] === $selId) {
                    $directChildren[$d] = $famNames[$d];
                }
            }

            // Mapear cada descendiente a virtual N1 / N2
            foreach ($descendants as $descId) {
                if ($descId === $selId) {
                    $virtualHierarchy[$descId] = [
                        'vN1'     => $selId,
                        'vN1Name' => $selName,
                        'vN2'     => null,
                        'vN2Name' => null,
                    ];
                } elseif (isset($directChildren[$descId])) {
                    $virtualHierarchy[$descId] = [
                        'vN1'     => $selId,
                        'vN1Name' => $selName,
                        'vN2'     => $descId,
                        'vN2Name' => $famNames[$descId],
                    ];
                } else {
                    // Subir en el árbol hasta encontrar el hijo directo de $selId
                    $cur = $descId;
                    while (isset($famParent[$cur]) && $famParent[$cur] !== $selId && $famParent[$cur] !== null) {
                        $cur = $famParent[$cur];
                    }
                    $vN2 = (isset($famParent[$cur]) && $famParent[$cur] === $selId) ? $cur : null;
                    $virtualHierarchy[$descId] = [
                        'vN1'     => $selId,
                        'vN1Name' => $selName,
                        'vN2'     => $vN2,
                        'vN2Name' => $vN2 !== null ? ($famNames[$vN2] ?? '') : null,
                    ];
                }
                $allDescendants[] = $descId;
            }
        }

        $allDescStr = implode(',', array_unique($allDescendants));
        return [
            'filtroSQL'        => "AND af.idFamilia IN ($allDescStr)",
            'virtualHierarchy' => $virtualHierarchy,
            'needsIdFamilia'   => true,
        ];
    }
}
