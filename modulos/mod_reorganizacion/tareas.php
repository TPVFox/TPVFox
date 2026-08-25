<?php

/*
 * @Copyright 2018, Alagoro Software.
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción
 */


/* Fichero de tareas a realizar.
 *
 *
 * Con el switch al final y variable $pulsado
 *
 *
 *
 */


/* ===============  REALIZAMOS CONEXIONES  =============== */


$pulsado = $_POST['pulsado'];

include_once("./../../inicial.php");

// Crealizamos conexion a la BD Datos
include_once './clases/ClaseReorganizar.php';
include_once '../mod_producto/clases/ClaseArticulos.php';
include_once '../mod_producto/clases/ClaseArticulosStocks.php';
include_once 'funciones.php';

// Comprobación de existencias en el cambio de año.
include_once './clases/ClaseComprobacionStockContexto.php';
include_once './clases/ClaseComprobacionStockExtraccion.php';
include_once './clases/ClaseComprobacionStockEmision.php';
include_once './clases/ClaseComprobacionStockAdmision.php';
include_once './clases/ClaseComprobacionStockMinimo.php';
include_once './clases/ClaseComprobacionStockClasificacion.php';

switch ($pulsado) {

    case 'contarproductos':
        $tipo = $_POST['tipo'];
        $CReorganizar = new ClaseReorganizar();
        if (count($CReorganizar->SetPlugin('ClaseVirtuemart')->TiendaWeb) > 0) {
            $TiendaWeb = $CReorganizar->SetPlugin('ClaseVirtuemart')->TiendaWeb;
            $CReorganizar->setIdTiendaWeb($TiendaWeb['idTienda']);
        }
        $totalProductos = $CReorganizar->contar($tipo);
        echo json_encode(compact('totalProductos'));
        break;
    case 'generastock':
        $inicial = $_POST['inicial'];
        $pagina = $_POST['pagina'];
        $totalProductos = $_POST['totalProductos'];

        if ($inicial == 0) {
            alArticulosStocks::limpiaStock(); //idTienda = 1
            $resultado['stocks'][] = ['id' => 0, 'stock' => '000'];
        }

        $resultado = ['totalProductos' => $totalProductos, 'pagina' => $pagina];
        $articulo = new alArticulos();
        $seleccionArticulos = $articulo->leer(0, $inicial, $pagina);
        if ($seleccionArticulos) {
            $resultado['elementos'] = count($seleccionArticulos);
            $resultado['actual'] = $inicial + $resultado['elementos'];
            $idTienda = 1;
            foreach ($seleccionArticulos as $seleccionado) {

                $idArticulo = $seleccionado['idArticulo'];
                if ($articulo->existe($idArticulo)) {
                    $stock = $articulo->calculaStock($idArticulo);
                    if ($stock != 0) {
                        alArticulosStocks::actualizarStock($idArticulo, $idTienda, $stock, K_STOCKARTICULO_SUMA);
                        $resultado['stocks'][] = ['id' => $idArticulo, 'stock' => $stock];
                    }
                } else {
                    $resultado = 'No existe articulo';
                }
            }
        }
        echo json_encode($resultado);
        break;

    case 'subirStockYPrecio':
        $inicial = $_POST['inicial'];
        $cantidad = $_POST['cantidad'];
        $totalProductos = $_POST['totalProductos'];
        $CReorganizar = new ClaseReorganizar();
        if (isset($CReorganizar->SetPlugin('ClaseVirtuemart')->TiendaWeb)) {
            $CVirtuemart = $CReorganizar->SetPlugin('ClaseVirtuemart');
            $TiendaWeb = $CVirtuemart->TiendaWeb;
            $CReorganizar->setIdTiendaWeb($TiendaWeb['idTienda']);
        }
        // Ahora obtenemos los ids productos de la web
        $idsWeb = $CReorganizar->obtenerIdsWeb($inicial, $cantidad);
        // Ahora enviamos a la web.
        $productos = json_encode($idsWeb['datos']);
        $r = $CVirtuemart->enviarStockYPrecio($productos);
        $resultado = array(
            'elementos' => $r['Datos']['consulta1'],
            'elementos_precios' => $r['Datos']['consulta2'],
            'actual' => $inicial + count($idsWeb['datos']) + 1,
            'totalProductos' => $totalProductos
        );
        // Si hubo un error deberías añadirlo.
        if (isset($r['Datos']['error'])) {
            $resultado['error'] = $r;
        }

        echo json_encode($resultado);
        break;

    case 'reorganizarPermisosModulos':
        // Objetivo limpiar los permisos de modulos que no existen.
        $registro = $_POST['inicial'];
        $total_usuario = $_POST['total']; // total usuarios
        $usuario_default = array('id' => '0', 'group_id' => '0');
        if ($registro === '0') {
            // Eliminamos los permiso del usuario 0 , que son los permisos por defecto.
            $borrado = $ClasePermisos->borrarPermisosUsuario(0);
            $resultado['borrado_default'] = $borrado;
        }
        $permisos_usuario_actual = $ClasePermisos->permisos;
        // Ahora obtenemos los permisos por defecto.
        $permisos_defecto = $ClasePermisos->getPermisosUsuario($usuario_default); // Agray con todos los permisos.
        $ClasePermisos->permisos = $permisos_defecto; // La instancia de permisos tiene los permisos por defecto.

        // Ahora obtenemos todos los usuarios, para enviar .. el registro actual
        $CReorganizar = new ClaseReorganizar();
        $usuarios = $CReorganizar->obtenerUsuarios();
        $permisos_usuario_analizar = $ClasePermisos->getPermisosUsuario($usuarios[$registro]);
        // Ahora comprobamos los permisos del usuario analizar y vemos si existe en permisos default, si no existe los eliminamos BD
        $eliminamos = 0;
        foreach ($permisos_usuario_analizar['resultado'] as $k => $permiso) {
            $valor = ''; // valor por defecto.
            if ($permiso['accion'] === '') {
                if ($permiso['vista'] === '') {
                    // Es el permiso de un modulo
                    $valor = $ClasePermisos->getModulo($permiso['modulo']);
                } else {
                    // Es el permiso de una vista
                    $valor = $ClasePermisos->getVista($permiso['vista'], $permiso['modulo']);
                }
            } else {
                // Es el permiso de una accion
                $valor = $ClasePermisos->getAccion($permiso['accion'], array('modulo' => $permiso['modulo'], 'vista' => $permiso['vista']));
            }
            if ($valor === '') {
                // No existe default , por debemos eliminar registro de permiso de ese usuario en Base de datos.
                $eliminamos += $ClasePermisos->borrarPermisosUsuario($permiso['idUsuario'], $permiso['id']);
            }
        }
        // Ahora hay que comprobar en permisos default los que no existen en usuario y crearlos.
        $ClasePermisos->permisos = $permisos_usuario_analizar; // La instancia de clase permisos le ponemos los permisos por usuario analizar
        $creados = 0;
        foreach ($permisos_defecto['resultado'] as $k => $permiso) {
            $valor = ''; // valor por defecto.
            if ($permiso['accion'] === '') {
                if ($permiso['vista'] === '') {
                    // Es el permiso de un modulo
                    $valor = $ClasePermisos->getModulo($permiso['modulo']);
                } else {
                    // Es el permiso de una vista
                    $valor = $ClasePermisos->getVista($permiso['vista'], $permiso['modulo']);
                }
            } else {
                // Es el permiso de una accion
                $valor = $ClasePermisos->getAccion($permiso['accion'], array('modulo' => $permiso['modulo'], 'vista' => $permiso['vista']));
            }
            if ($valor === '') {
                // No existe permiso en usuario , por lo que debemos crearlo para ese usuario.
                unset($permiso['id']);
                $permiso['idUsuario'] = $usuarios[$registro]['id'];
                if ($usuarios[$registro]['group_id'] == 9) {
                    // Entonces es administrador y el permiso es 1
                    $permiso['permiso'] = 1;
                }
                $creados += $ClasePermisos->crearUnPermisoUsuario($permiso);
            }
        }
        // Volvemos a poner en la instancia de permisos tiene los permisos del usuario actual.
        $ClasePermisos->permisos = $permisos_usuario_actual;
        $resultado['usuario'] = $usuarios[$registro];
        $resultado['eliminado'] =  $eliminamos;
        $resultado['creados'] =  $creados;

        echo json_encode($resultado);

        break;
    case 'contarfamilias':
        $idFamiliaCierreStock = json_decode($_POST['idFamiliaCierreStock'], true);
        // Si este valor viene vacio se buscaran todas las familias, si viene con ids se excluiran esas familias del cierre de stock anual.
        if ($idFamiliaCierreStock == '') {
            $CReorganizar = new ClaseReorganizar();
            $ClasesParametros = new ClaseParametros('parametros.xml');
            $xml = $ClasesParametros->getNodeInternBySection('configuracion', 'cierre_stock_anual');
            // Obtenemos el array de ids del xml attibuto familias dentro de familias_excluidas
            foreach ($xml->familias_excluidas->familia as $familia) {
                $idsFamiliaExcluidas[] = (string)$familia['id'];
            }
            $totalFamilias = $CReorganizar->contarFamilias($idsFamiliaExcluidas);
        } else {
            // Transformar string a array de ids familias a excluir del cierre de stock anual.
            $totalFamilias = explode(',', $idFamiliaCierreStock);
        }
        // devolver array con los ids familias No cuenta array
        echo json_encode($totalFamilias);
        break;
    case 'cerrarStockAnoActual':
        // Obtenemos los datos de Ajax via POST
        $inicial = $_POST['inicial'];
        $pagina = $_POST['pagina'];
        $familias = json_decode($_POST['familias'], true);
        $idProveedor = $_POST['idProveedor'];
        $configuracion = json_decode($_POST['configuracion'], true);
        // De configuración solo nos interesa el modo: manual o automatico.
        $modo = $configuracion['modo'];

        // Parámetros globales desde XML
        $ClasesParametros = new ClaseParametros('parametros.xml');
        $xml = $ClasesParametros->getNodeInternBySection('configuracion', 'cierre_stock_anual');
        $limiteProductosAlbaran = (int)$xml->ajustes_globales->num_productos;
        $serieAlbaranCierre = (string)$xml->ajustes_globales->serie_albaran->cierre;


        $familiasExcluidas = array();
        foreach ($xml->familias_excluidas->familia as $familia) {
            $familiasExcluidas[] = (string)$familia['id'];
        }

        // Modo manual: se cierra una familia concreta idN1 o idN5. El cierre no se fragmenta por subfamilia, se cierra toda la familia y si tiene más productos de los permitidos se generan varios albaranes.
        // Modo automático: se cierra por familias idN1, pero si una familia tiene más productos de los permitidos se fragmenta por subfamilias idN2, y si una subfamilia tiene más productos de los permitidos se generan varios albaranes.
        // En el futuro se podria añadir un metodo enfocado a que se automatico pero por proveedor en lugar de por familia.
        $familia_id = $familias[$inicial]; // Se toma la familia a cerrar

        $CReorganizar = new ClaseReorganizar();
        if ($modo === 'manual') {
            // Si es modo manual solo llega 1 familia a cerrar.
            // Proceso:
            // 1. Contar el número de productos que hay en la familia a cerrar.
            // 2. Si el número de productos es menor o igual al limite de productos por albarán, se cierra la familia en un solo albarán.
            // 3. Si el número de productos es mayor al limite de productos por albarán, se cierra la familia en varios albaranes.
            $idNivel = $CReorganizar->obtenerNivelFamilia($familia_id);
            if ($idNivel == 1) {
                $idsProductos = $CReorganizar->obtenerProductosPorFamilia($familia_id);
            } else if ($idNivel == 2) {
                $idsProductos = $CReorganizar->obtenerProductosPorSubfamilia($familia_id);
            } else {
                $idsProductos = $CReorganizar->obtenerProductosPorIdFamilia($familia_id);
            }
            $idsProductosUnicos = normalizarProductos($idsProductos);
            $subfamiliasProcesar = [$familia_id];
            if (count($idsProductosUnicos) > 0) {
                $productos = obtenerDatosProductoAlbaranCierre($idsProductosUnicos, $familia_id);
                generarAlbaranesConLimite($productos, $familia_id, $limiteProductosAlbaran, $idProveedor, $serieAlbaranCierre);
            }
        } else {
            $subfamilias = $CReorganizar->contarProductosSubfamilias($familia_id, $familiasExcluidas);

            $subfamiliasProcesar = [];
            $totalProductosFamilia = 0;
            foreach ($subfamilias as $subfamilia) {
                $totalProductosFamilia += $subfamilia['total_articulos'];
            }
            // Si la familia es demasiado grande, separamos el cierre por subfamilias
            // para evitar generar albaranes con más productos de los permitidos
            if ($totalProductosFamilia > $limiteProductosAlbaran) {
                $productosAcumulados = 0;
                foreach ($subfamilias as $subfamilia) {
                    $productosAcumulados += $subfamilia['total_articulos'];

                    // Cuando el resto de productos cabe en un solo albarán,
                    // dejamos de dividir
                    $subfamiliasProcesar[] = $subfamilia['idN2'];
                    if (($totalProductosFamilia - $productosAcumulados) <= $limiteProductosAlbaran) {
                        break;
                    }
                }
            }

            $numeroAlbaranes = array();
            // Procesamos las subfamilias que hemos decidido cerrar por separado
            if (count($subfamiliasProcesar) > 0) {
                // Si hay subfamilias para procesar, las mostramos
                foreach ($subfamiliasProcesar as $subfamilia_id) {
                    $idsProductos = $CReorganizar->obtenerProductosPorSubfamilia($subfamilia_id, $familiasExcluidas);
                    // Simplificamos el array para eliminar productos duplicados
                    $idsProductosUnicos =  normalizarProductos($idsProductos);
                    $productos = obtenerDatosProductoAlbaranCierre($idsProductosUnicos, $subfamilia_id);
                    generarAlbaranesConLimite($productos, $subfamilia_id, $limiteProductosAlbaran, $idProveedor, $serieAlbaranCierre);
                }
            }

            $idsProductos = $CReorganizar->obtenerProductosPorFamilia($familia_id, $subfamiliasProcesar, $familiasExcluidas);
            // Simplificamos el array para eliminar productos duplicados

            $idsProductosUnicos = normalizarProductos($idsProductos);
            if (count($idsProductosUnicos) > 0) {
                $productos = obtenerDatosProductoAlbaranCierre($idsProductosUnicos, $familia_id);
                generarCierreAlbaran($productos, $familia_id, $idProveedor);
            }

            // Si es la ultima familia hacemos una revisión final
            if (($inicial + $pagina) >= count($familias)) {
                $idsProductosPendientes = $CReorganizar->obtenerProductosPendientesCierre($familiasExcluidas);
                // Simplificamos el array para eliminar productos duplicados
                $idsProductosPendientes = normalizarProductos($idsProductosPendientes);
                if (count($idsProductosPendientes) > 0) {
                    $productosPendientes = obtenerDatosProductoAlbaranCierre($idsProductosPendientes, "SINID");
                    generarAlbaranesConLimite($productosPendientes, "SINID", $limiteProductosAlbaran, $idProveedor, $serieAlbaranCierre);
                }
            }
        }

        $resultado['elementos'] = count($subfamiliasProcesar) > 0 ? count($subfamiliasProcesar) + 1 : 1;
        $resultado['actual'] = $inicial + $pagina;
        $resultado['totalFamilias'] = $subfamiliasProcesar;
        $resultado['pagina'] = $pagina;
        $resultado['idProveedor'] = $_POST['idProveedor'];

        echo json_encode($resultado);
        break;
    // Modal para ampliar la configuración del cierre de stock anual
    case 'modalCerrarStock':
        $ClasesParametros = new ClaseParametros('parametros.xml');
        $titulo = $_POST['titulo'];
        $dedonde = $_POST['dedonde'];
        $xml = $ClasesParametros->getNodeInternBySection('configuracion', 'cierre_stock_anual');
        include_once 'tareas/modalCerrarStock.php';
        echo json_encode($respuesta);
        return $respuesta;
        break;
    case 'abrirConfiguracionXML':
        $ClasesParametros = new ClaseParametros('parametros.xml');
        $seccion = $_POST['seccion'];
        $dedonde = $_POST['dedonde'];
        $xml = $ClasesParametros->getNodeInternBySection('configuracion', $seccion);
        $vistaUrl = 'tareas/modalConfigXML' . $seccion . '.php';
        include_once $vistaUrl;
        // $respuesta['html'] se genera dentro del archivo incluido
        echo json_encode($respuesta);
        return $respuesta;
        break;
    case 'buscarFamilias':
        include_once 'tareas/buscarFamilias.php';
        break;
    case 'catalogoFamilias':
        include_once 'tareas/catalogoFamilias.php';
        break;
    case 'guardarConfiguracionXML':
        $seccion = $_POST['seccion'];
        $datos = json_decode($_POST['datos'], true);
        $ClasesParametros = new ClaseParametros('parametros.xml');
        $xml = $ClasesParametros->getNodeInternBySection('configuracion', $seccion);
        guardarConfiguracionXML($xml, $datos, $seccion);
        $ClasesParametros->save();
        echo json_encode(['guardado' => true]);
        break;
    case 'validarConfiguracionXML':
        $seccion = $_POST['seccion'];
        $ClasesParametros = new ClaseParametros('parametros.xml');
        $xml = $ClasesParametros->getNodeInternBySection('configuracion', $seccion);
        $validacion = validarDatosXML($xml, $seccion);
        echo json_encode($validacion);
        return $validacion;
        break;
    case 'validarDatosFormulario':
        $seccion = $_POST['seccion'];
        $datos = json_decode($_POST['datos'], true);
        $validacion = validarDatosFormulario($datos, $seccion);
        echo json_encode($validacion);
        return $validacion;
        break;

    // Comprobación de existencias — ejercicio vigente.
    case 'obtenerComprobacionStockVigente':
        include_once 'tareas/obtenerComprobacionStockVigente.php';
        echo json_encode($respuesta);
        break;
    case 'exportarComprobacionStockXML':
        // El fichero include emite cabeceras HTTP + XML y llama a exit(), por lo
        // que no llega a haber ningún json_encode que ejecutar aquí.
        include_once 'tareas/exportarComprobacionStockXML.php';
        break;

    // Comprobación de existencias — ejercicio anterior.
    case 'admitirComprobacionStock':
        // El fichero include hace su propio json_encode y exit().
        include_once 'tareas/admitirComprobacionStock.php';
        break;
    case 'exportarInformeComprobacionStock':
        // El fichero include emite cabeceras HTTP + CSV y llama a exit().
        include_once 'tareas/exportarInformeComprobacionStock.php';
        break;
}
