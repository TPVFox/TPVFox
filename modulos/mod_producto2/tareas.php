<?php
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_producto2/clases/ClaseSeleccionProductos.php';
include_once $URLCom . '/modulos/mod_producto/clases/ClaseProductos.php';
include_once $URLCom . '/modulos/mod_producto/funciones.php';
include_once $URLCom . '/clases/Proveedores.php';
include_once $URLCom . '/modulos/mod_familia/clases/ClaseFamilias.php';
include_once $URLCom . '/controllers/Controladores.php';
include_once $URLCom . '/modulos/mod_producto/clases/ClaseArticulosStocks.php';
$Controler = new ControladorComun();
$Controler->loadDbtpv($BDTpv);

$respuesta = ['ok' => false, 'mensaje' => ''];
// El JS antiguo usa 'pulsado', el nuevo usa 'accion'
$accion    = $_POST['accion'] ?? $_POST['pulsado'] ?? '';

// Solo enviamos JSON header para acciones nuevas (accion), no para las antiguas (pulsado)
// ya que el JS antiguo usa $.parseJSON(response) esperando texto, no objeto auto-parseado.
if (isset($_POST['accion'])) {
    header('Content-Type: application/json');
}
$dirCache  = __DIR__ . '/cache';
$idUsuario = (int) $Usuario['id'];

$CSeleccion = new ClaseSeleccionProductos($idUsuario, $dirCache);

try {
    switch ($accion) {

        case 'Grabar_configuracion':
            $configuracion  = $_POST['configuracion'];
            $nombre_modulo  = $configuracion['nombre_modulo'];
            $idUsuario      = $configuracion['idUsuario'];
            unset($configuracion['nombre_modulo'], $configuracion['idUsuario']);
            $respuesta = $Controler->GrabarConfiguracionModulo($nombre_modulo, $idUsuario, $configuracion);
            $respuesta['configuracion'] = $configuracion;
            break;

        case 'modalEstadoProductos':
            $NCArticulo       = new ClaseProductos($BDTpv);
            $productos        = $CSeleccion->getIds();
            $posibles_estados = $NCArticulo->posiblesEstados('articulos');
            $respuesta['html'] = modalAutocompleteEstadoProductos($productos, $posibles_estados);
            break;

        case 'cambiarEstadoProductos':
            $NCArticulo = new ClaseProductos($BDTpv);
            $productos  = $_POST['productos'];
            $estado     = $_POST['estado'];
            $modEstado  = $NCArticulo->modificarVariosEstados($estado, $productos);
            $respuesta['consulta']  = $modEstado;
            $respuesta['productos'] = $productos;
            $respuesta['estado']    = $estado;
            $respuesta['ok']        = true;
            break;

        case 'datosRegularizar':
            $NCArticulo = new ClaseProductos($BDTpv);
            include $URLCom . '/modulos/mod_producto/tareas/htmlModalRegularizacionStock.php';
            break;

        case 'grabarRegularizacion':
            $NCArticulo  = new ClaseProductos($BDTpv);
            $idArticulo  = $_POST['idarticulo'];
            $stockReal   = $_POST['stockReal'];
            $Producto    = $NCArticulo->GetProducto($idArticulo);
            $stocksumar  = floatval($stockReal) - $Producto['stocks']['stockOn'];
            $idTienda    = isset($_SESSION['tiendaTpv']) ? $_SESSION['tiendaTpv']['idTienda'] : 1;
            $idUsuario   = isset($_SESSION['usuarioTpv']) ? $_SESSION['usuarioTpv']['id'] : 0;
            $respuesta['cambioStock'] = alArticulosStocks::regularizaStock($idArticulo, $idTienda, $stocksumar, K_STOCKARTICULO_SUMA);
            $datos = [
                'idArticulo'      => $idArticulo,
                'idTienda'        => $idTienda,
                'stockActual'     => $Producto['stocks']['stockOn'],
                'stockModif'      => $stocksumar,
                'stockFinal'      => $stockReal,
                'stockOperacion'  => K_STOCKARTICULO_SUMA,
                'idUsuario'       => $idUsuario,
            ];
            $respuesta['registroRegularizacionStock'] = alArticulosStocks::grabarRegularizacion($datos);
            $respuesta['ok'] = true;
            break;

        case 'HtmlCajaBuscarProveedor':
            $CProveedor = new Proveedores();
            include $URLCom . '/modulos/mod_producto/tareas/htmlCajaBuscarProveedor.php';
            break;

        case 'guardarFamiliaProductos':
            $idFamilia  = $_POST['idfamilia'];
            $idProducto = $_POST['idProducto'];
            $CFamilia   = new ClaseFamilias($BDTpv);
            if (isset($_POST['dedonde']) && $_POST['dedonde'] === 'ListadoProductos') {
                $productos = $CSeleccion->getIds();
                $productosEnFamilia = [];
                $contadorProductos  = 0;
                foreach ($productos as $idProd) {
                    $comprobar = $CFamilia->comprobarRegistro($idProd, $idFamilia);
                    if (isset($comprobar['datos'])) {
                        $productosEnFamilia[] = $idProd;
                    } else {
                        $add = $CFamilia->guardarProductoFamilia($idProd, $idFamilia);
                        if ($add['error']) {
                            $respuesta['error'] = $add;
                        } else {
                            $contadorProductos++;
                        }
                    }
                }
                $respuesta['contadorProductos']  = $contadorProductos;
                $respuesta['productosEnFamilia'] = $productosEnFamilia;
            } else {
                $comprobar = $CFamilia->comprobarRegistro($idProducto, $idFamilia);
                if (isset($comprobar['datos'])) {
                    $respuesta['error'] = 1;
                } else {
                    $nombreFamilia = $CFamilia->buscarPorId($idFamilia);
                    $respuesta['html'] = '<tr>'
                        . '<td><input type="hidden" id="idFamilias_' . $idFamilia
                        . '" name="idFamilias_' . $idFamilia . '" value="' . $idFamilia . '">'
                        . $idFamilia . '</td>'
                        . '<td>' . $nombreFamilia['datos'][0]['familiaNombre'] . '</td>'
                        . '<td><a id="eliminar_' . $idFamilia
                        . '" class="glyphicon glyphicon-trash" onclick="eliminarFamiliaProducto(this)"></a>'
                        . '</td></tr>';
                    $respuesta['nombre'] = $nombreFamilia;
                }
            }
            break;

        case 'modalFamiliaProducto':
            $idProducto = ($_POST['idProducto'] !== '') ? $_POST['idProducto'] : 0;
            $CFamilia   = new ClaseFamilias($BDTpv);
            $familias   = $CFamilia->todoslosPadres();
            $respuesta['html'] = modalAutocompleteFamilias($familias['datos'], $idProducto, $_POST['dedonde']);
            break;

        case 'HtmlLineaCodigoBarras':
            $respuesta['html'] = htmlLineaCodigoBarras($_POST['fila']);
            break;

        case 'obtenerCostesProveedor':
            $NCArticulo = new ClaseProductos($BDTpv);
            $CProveedor = new Proveedores();
            include $URLCom . '/modulos/mod_producto/tareas/obtenerCostesProveedor.php';
            break;

        case 'eliminarReferenciaTienda':
            $NCArticulo = new ClaseProductos($BDTpv);
            $respuesta  = $NCArticulo->EliminarCruceTienda($_POST['idCruce']);
            break;

        case 'eliminarRefProveedor':
            $idProveedor = (int) ($_POST['idProveedor'] ?? 0);
            $idArticulo  = (int) ($_POST['idArticulo'] ?? 0);
            if ($idProveedor <= 0 || $idArticulo <= 0) {
                $respuesta['mensaje'] = 'Parámetros no válidos';
                break;
            }
            $NCArticulo = new ClaseProductos($BDTpv);
            $resultado  = $NCArticulo->EliminarRefProveedor($idArticulo, $idProveedor);
            $respuesta  = $resultado;
            break;

        case 'agregar':
            $idArticulo = (int) ($_POST['idArticulo'] ?? 0);
            if ($idArticulo <= 0) {
                $respuesta['mensaje'] = 'idArticulo no válido';
                break;
            }
            $CSeleccion->agregar($idArticulo);
            $respuesta['ok']     = true;
            $respuesta['total']  = $CSeleccion->contar();
            break;

        case 'quitar':
            $idArticulo = (int) ($_POST['idArticulo'] ?? 0);
            if ($idArticulo <= 0) {
                $respuesta['mensaje'] = 'idArticulo no válido';
                break;
            }
            $CSeleccion->quitar($idArticulo);
            $respuesta['ok']    = true;
            $respuesta['total'] = $CSeleccion->contar();
            break;

        case 'limpiar':
            $CSeleccion->limpiar();
            $respuesta['ok']    = true;
            $respuesta['total'] = 0;
            break;

        case 'obtener':
            $respuesta['ok']   = true;
            $respuesta['ids']  = $CSeleccion->getIds();
            $respuesta['total'] = $CSeleccion->contar();
            break;

        case 'buscarProductosDeFamilia':
            $idFamilia = (int) ($_POST['idfamilia'] ?? 0);
            $CFamilia  = new ClaseFamilias($BDTpv);
            if ($idFamilia === 0) {
                $productos = $CFamilia->buscarProductosSinFamilias();
            } else {
                $productos = $CFamilia->buscarProductosFamilias($idFamilia);
            }
            $idsProductos = [];
            if (isset($productos['datos'])) {
                foreach ($productos['datos'] as $producto) {
                    $id = (int) $producto['idArticulo'];
                    $CSeleccion->agregar($id);
                    $idsProductos[] = $id;
                }
            }
            if (empty($idsProductos)) {
                $respuesta['ok'] = false;
            } else {
                $respuesta['ok']    = true;
                $respuesta['ids']   = $idsProductos;
                $respuesta['total'] = $CSeleccion->contar();
            }
            break;

        default:
            $respuesta['mensaje'] = 'Accion no reconocida: ' . htmlspecialchars($accion);
    }
} catch (Exception $e) {
    $respuesta['mensaje'] = $e->getMessage();
}

echo json_encode($respuesta);
