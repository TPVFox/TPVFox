<?php
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_productos/clases/ClaseSeleccionProductos.php';
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

        case 'buscarProductosProveedor':
            $idProveedor  = (int) ($_POST['idProveedor'] ?? 0);
            $CProveedor2  = new Proveedores();
            $productos    = $CProveedor2->buscarProductosProveedor($idProveedor);
            $idsProductos = [];
            if (is_array($productos)) {
                foreach ($productos as $producto) {
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

        case 'establecerSeleccion':
            $CSeleccion->limpiar();
            foreach ((array) ($_POST['ids'] ?? []) as $id) {
                $CSeleccion->agregar((int) $id);
            }
            $respuesta['ok']    = true;
            $respuesta['total'] = $CSeleccion->contar();
            break;

        case 'agregarASeleccion':
            foreach ((array) ($_POST['ids'] ?? []) as $id) {
                $CSeleccion->agregar((int) $id);
            }
            $respuesta['ok']    = true;
            $respuesta['total'] = $CSeleccion->contar();
            break;

        case 'obtener':
            $respuesta['ok']   = true;
            $respuesta['ids']  = $CSeleccion->getIds();
            $respuesta['total'] = $CSeleccion->contar();
            break;

        case 'cambiarEstadoRecalculo':
            $idArticulo = $_POST['idArticulo'];
            $dedonde    = $_POST['dedonde'];
            $idDoc      = $_POST['id'];
            $tipo       = $_POST['tipo'];
            $accionRec  = $_POST['operacion'];
            $estado     = ($accionRec === 'eliminar') ? 'Sin Cambios' : 'Pendiente';
            include_once $URLCom . '/clases/articulos.php';
            $CArticuloRec = new Articulos($BDTpv);
            $mod = $CArticuloRec->modEstadoArticuloHistorico($idArticulo, $idDoc, $dedonde, $tipo, $estado);
            $respuesta['accion'] = $accionRec;
            $respuesta['sql']    = $mod;
            break;

        case 'imprimirRecalculo':
            $idAlbaran = (int) ($_POST['id'] ?? 0);
            $cacheFile = __DIR__ . '/cache/recalculoprecios_' . $idUsuario . '_' . $idAlbaran . '.xml';
            if (!file_exists($cacheFile)) {
                $respuesta['error'] = 'No hay datos guardados para imprimir. Guarda primero el recalculo.';
                break;
            }
            $xmlData = simplexml_load_file($cacheFile);
            $html  = '<p>ALBARÁN NÚMERO: ' . $xmlData->idAlbaran . '</p>';
            $html .= '<p>FECHA: ' . $xmlData->fecha . '</p>';
            $html .= '<p>PROVEEDOR: ' . $xmlData->nombreProveedor . '</p><br>';
            $html .= '<table width="100%" border="0" cellpadding="3">';
            foreach ($xmlData->productos->producto as $prod) {
                $costeInfo = '<font color="#666666"><small>Coste '
                    . number_format((float) $prod->costeAnterior, 2) . ' -> '
                    . number_format((float) $prod->costeNuevo,    2)
                    . '&nbsp;&nbsp;PVP ant.: ' . number_format((float) $prod->pvpAnterior, 2)
                    . '</small></font>';
                $html .= '<tr valign="top">';
                $html .= '<td width="8%"><strong>' . (int) $prod->idArticulo . '</strong></td>';
                $html .= '<td width="56%"><strong>' . htmlspecialchars((string) $prod->nombre) . '</strong><br>' . $costeInfo . '</td>';
                $html .= '<td width="18%" align="right">PVP nuevo:</td>';
                $html .= '<td width="18%" align="right"><strong>' . number_format((float) $prod->pvpNuevo, 2) . '</strong></td>';
                $html .= '</tr>';
                $html .= '<tr><td colspan="4"><hr style="border-top:1px solid #ccc; margin:1px 0;"></td></tr>';
            }
            $html    .= '</table>';
            $cabecera = '';
            $nombreTmp = 'Recalculorecalculo.pdf';
            include_once $URLCom . '/clases/imprimir.php';
            include_once $URLCom . '/controllers/planImprimirRe.php';
            $respuesta['fichero'] = $rutatmp . '/' . $nombreTmp;
            break;

        case 'imprimir':
            $id      = $_POST['id'];
            $dedonde = 'Recalculo';
            $nombreTmp = $dedonde . 'recalculo.pdf';
            include_once $URLCom . '/modulos/mod_compras/clases/albaranesCompras.php';
            include_once $URLCom . '/clases/articulos.php';
            $CArticulo = new Articulos($BDTpv);
            $CAlbaran  = new AlbaranesCompras($BDTpv);
            $CProveedor = new Proveedores();
            include_once $URLCom . '/modulos/mod_producto/tareas/imprimirRecalculo.php';
            $cabecera = $htmlImprimir['cabecera'];
            $html     = $htmlImprimir['html'];
            include_once $URLCom . '/clases/imprimir.php';
            include_once $URLCom . '/controllers/planImprimirRe.php';
            $ficheroCompleto = $rutatmp . '/' . $nombreTmp;
            $respuesta['fichero'] = $ficheroCompleto;
            break;

        case 'imprimirEtiquetas':
            $NCArticulo   = new ClaseProductos($BDTpv);
            $rutaCompleta = $RutaServidor . $HostNombre;
            include_once $URLCom . '/modulos/mod_balanza/clases/ClaseBalanza.php';
            $CBalanza = new ClaseBalanza($BDTpv);
            include $URLCom . '/modulos/mod_producto/tareas/imprimirEtiquetas.php';
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
