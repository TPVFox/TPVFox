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
            $datos = $this->BeneficioFamilias($parametros);
        } else {
            $datos = [];
        }

        $cabecera = array(
            'id'             => $id,
            'titulo_informe' => $this->informes[$id]['Titulo'],
            'Fecha_Inicio'   => $parametros['Finicio'],
            'Fecha_Final'    => $parametros['Ffinal'],
            'opcion'         => $parametros['opcion']
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

        $filtroN1 = '';
        if ((int)$parametros['opcion'] === 4) {
            $ids = array_filter(array_map('intval', explode(',', $parametros['familias'] ?? '')));
            if (!empty($ids)) {
                $filtroN1 = 'AND vj.idN1 IN (' . implode(',', $ids) . ')';
            }
        }

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
            FROM albprolinea l
            JOIN albprot a ON a.id = l.idalbpro
            LEFT JOIN articulosFamilias af ON af.idArticulo = l.idArticulo
            LEFT JOIN vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            LEFT JOIN vw_jerarquias_familias n1 ON n1.idFamilia = vj.idN1
            LEFT JOIN vw_jerarquias_familias n2 ON n2.idFamilia = vj.idN2
            WHERE a.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND l.estadoLinea <> 'Eliminado'
              $filtroN1
            GROUP BY vj.idN1, vj.idN2, l.idArticulo, l.costeSiva
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
            $idN1     = $fila['idN1'] !== null ? (int)$fila['idN1'] : '__sin_familia__';
            $nombreN1 = $fila['nombreN1'] !== null ? $fila['nombreN1'] : 'Sin familia';
            $idN2     = $fila['idN2'] !== null ? (int)$fila['idN2'] : null;
            $keyN2    = $idN2 !== null ? $idN2 : '__sin_n2__';
            $labelN2  = $idN2 !== null ? ($fila['nombreN2'] ?? 'Sin subfamilia') : '(Sin subfamilia)';
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

            $resultado[] = [
                'idN1'            => $familia['idN1'],
                'nombreN1'        => $familia['nombreN1'],
                'total_linea'     => $totalN1,
                'num_referencias' => count($refsN1),
                'subfamilias'     => $sfs
            ];
        }

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

        $filtroN1 = '';
        if ((int)($parametros['opcion'] ?? 0) === 4) {
            $ids = array_filter(array_map('intval', explode(',', $parametros['familias'] ?? '')));
            if (!empty($ids)) {
                $filtroN1 = 'AND vj.idN1 IN (' . implode(',', $ids) . ')';
            }
        }

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
            GROUP BY vj.idN1, vj.idN2, l.idArticulo, l.precioCiva, l.iva

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
            GROUP BY vj.idN1, vj.idN2, l.idArticulo, l.precioCiva, l.iva
            ORDER BY nombreN1, nombreN2, idArticulo
        ";

        $smt    = $BDTpv->query($sql);
        $lineas = [];
        while ($row = $smt->fetch_assoc()) {
            $lineas[] = $row;
        }

        $familias = [];

        foreach ($lineas as $fila) {
            $idN1     = $fila['idN1'] !== null ? (int)$fila['idN1'] : '__sin_familia__';
            $nombreN1 = $fila['nombreN1'] !== null ? $fila['nombreN1'] : 'Sin familia';
            $idN2     = $fila['idN2'] !== null ? (int)$fila['idN2'] : null;
            $keyN2    = $idN2 !== null ? $idN2 : '__sin_n2__';
            $labelN2  = $idN2 !== null ? ($fila['nombreN2'] ?? 'Sin subfamilia') : '(Sin subfamilia)';
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

            $resultado[] = [
                'idN1'            => $familia['idN1'],
                'nombreN1'        => $familia['nombreN1'],
                'total_linea'     => $totalN1,
                'num_referencias' => count($refsN1),
                'subfamilias'     => $sfs
            ];
        }

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

        $filtroN1 = '';
        if ((int)($parametros['opcion'] ?? 0) === 4) {
            $ids = array_filter(array_map('intval', explode(',', $parametros['familias'] ?? '')));
            if (!empty($ids)) {
                $filtroN1 = 'AND vj.idN1 IN (' . implode(',', $ids) . ')';
            }
        }

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
                SUM(ar.ultimoCoste * l.nunidades)                            AS totalCoste,
                COUNT(DISTINCT l.idalbcli)                                   AS num_documentos
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
            GROUP BY vj.idN1, vj.idN2, l.idArticulo, l.precioCiva, l.iva

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
                SUM(ar.ultimoCoste * l.nunidades)                            AS totalCoste,
                COUNT(DISTINCT l.idticketst)                                 AS num_documentos
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
            GROUP BY vj.idN1, vj.idN2, l.idArticulo, l.precioCiva, l.iva
            ORDER BY nombreN1, nombreN2, idArticulo
        ";

        $smt    = $BDTpv->query($sql);
        $lineas = [];
        while ($row = $smt->fetch_assoc()) {
            $lineas[] = $row;
        }

        $familias = [];

        foreach ($lineas as $fila) {
            $idN1     = $fila['idN1'] !== null ? (int)$fila['idN1'] : '__sin_familia__';
            $nombreN1 = $fila['nombreN1'] !== null ? $fila['nombreN1'] : 'Sin familia';
            $idN2     = $fila['idN2'] !== null ? (int)$fila['idN2'] : null;
            $keyN2    = $idN2 !== null ? $idN2 : '__sin_n2__';
            $labelN2  = $idN2 !== null ? ($fila['nombreN2'] ?? 'Sin subfamilia') : '(Sin subfamilia)';
            $idArt    = (int)$fila['idArticulo'];
            $tv       = (float)$fila['totalVenta'];
            $tc       = (float)$fila['totalCoste'];

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
                    'totalUnidades' => (float)$fila['totalUnidades'],
                    'pvpSiva'       => (float)$fila['pvpSiva'],
                    'ultimoCoste'   => (float)$fila['ultimoCoste'],
                    'totalVenta'    => $tv,
                    'totalCoste'    => $tc,
                    'num_ventas'    => (int)$fila['num_documentos']
                ];
            } else {
                $art['totalUnidades'] += (float)$fila['totalUnidades'];
                $art['totalVenta']    += $tv;
                $art['totalCoste']    += $tc;
                $art['num_ventas']    += (int)$fila['num_documentos'];
            }
            $art['beneficio']  = $art['totalVenta'] - $art['totalCoste'];
            $art['margen_pct'] = $art['totalVenta'] > 0
                ? round($art['beneficio'] / $art['totalVenta'] * 100, 2)
                : 0;

            unset($art);
        }

        $resultado = [];
        foreach ($familias as $familia) {
            $tvN1    = 0;
            $tcN1    = 0;
            $refsN1  = [];
            $sfs     = [];

            foreach ($familia['subfamilias'] as $sf) {
                $arts  = array_values($sf['articulos']);
                $tvN2  = 0;
                $tcN2  = 0;
                foreach ($arts as $art) {
                    $tvN2 += $art['totalVenta'];
                    $tcN2 += $art['totalCoste'];
                    $refsN1[$art['idArticulo']] = true;
                }
                $tvN1 += $tvN2;
                $tcN1 += $tcN2;

                $benN2  = $tvN2 - $tcN2;
                $sfs[] = [
                    'idN2'            => $sf['idN2'],
                    'nombreN2'        => $sf['nombreN2'],
                    'totalVenta'      => $tvN2,
                    'totalCoste'      => $tcN2,
                    'beneficio'       => $benN2,
                    'margen_pct'      => $tvN2 > 0 ? round($benN2 / $tvN2 * 100, 2) : 0,
                    'num_referencias' => count($arts),
                    'articulos'       => $arts
                ];
            }

            $benN1 = $tvN1 - $tcN1;
            $resultado[] = [
                'idN1'            => $familia['idN1'],
                'nombreN1'        => $familia['nombreN1'],
                'totalVenta'      => $tvN1,
                'totalCoste'      => $tcN1,
                'beneficio'       => $benN1,
                'margen_pct'      => $tvN1 > 0 ? round($benN1 / $tvN1 * 100, 2) : 0,
                'num_referencias' => count($refsN1),
                'subfamilias'     => $sfs
            ];
        }

        return $resultado;
    }
}
