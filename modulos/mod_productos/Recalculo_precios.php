<?php
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_producto/funciones.php';
include_once $URLCom . '/controllers/Controladores.php';
include_once $URLCom . '/modulos/mod_compras/clases/albaranesCompras.php';
include_once $URLCom . '/clases/articulos.php';
include_once $URLCom . '/clases/Proveedores.php';
include_once $URLCom . '/controllers/parametros.php';

$Controler = new ControladorComun;
$Controler->loadDbtpv($BDTpv);

$ClasesParametros = new ClaseParametros($URLCom . '/modulos/mod_productos/parametros.xml');
$parametros       = $ClasesParametros->getRoot();
$VarJS            = $Controler->ObtenerCajasInputParametros($parametros);

$CProveedor = new Proveedores();
$CAlbaran   = new AlbaranesCompras($BDTpv);
$CArticulo  = new Articulos($BDTpv);

$ruta_volver = $HostNombre . '/modulos/mod_compras/albaranesListado.php';
$titulo      = 'Recalculo precios PVP';
$Usuario     = $_SESSION['usuarioTpv'];

if (!isset($_GET['id'])) {
    header('Location: ' . $ruta_volver);
    exit();
}

$id    = (int) $_GET['id'];
$dedonde   = 'albaran';
$subtitulo = 'de ' . $dedonde . ': ' . $id;
$titulo   .= ' ' . $subtitulo;

$datosAlbaran       = $CAlbaran->datosAlbaran($id);
$fecha              = date_format(date_create($datosAlbaran['Fecha']), 'Y-m-d');
$productosHistoricos = $CArticulo->historicoCompras($id, 'albaran', 'compras');
$productosHistoricos = comprobarRecalculosSuperiores($productosHistoricos, $CArticulo);
$datosProveedor     = $CProveedor->buscarProveedorId($datosAlbaran['idProveedor']);

// Comprobar si ya fue guardado antes de procesar el POST
$cacheDir    = __DIR__ . '/cache';
$cacheName   = 'recalculoprecios_' . $Usuario['id'] . '_' . $id . '.xml';
$cacheExists = file_exists($cacheDir . '/' . $cacheName);

// --- Guardar ---
if (isset($_POST['Guardar']) && !$cacheExists) {
    $fechaCreacion = date('Y-m-d');
    $i = 1;
    $pvpGuardados  = [];

    // Detectar productos de tipo peso
    $productosPeso = [];
    foreach ($productosHistoricos as $producto) {
        $tipo = $CArticulo->getTipoArticulo($producto['idArticulo']);
        $productosPeso[$producto['idArticulo']] = $tipo[$producto['idArticulo']];
    }

    $hayPeso = in_array('peso', $productosPeso, true);
    if ($hayPeso) {
        include_once $URLCom . '/modulos/mod_balanza/clases/ClaseComunicacionBalanza.php';
        include_once $URLCom . '/modulos/mod_balanza/clases/ClaseBalanza.php';
        $traductorBalanza = new ClaseComunicacionBalanza();
        $CBalanza         = new ClaseBalanza($BDTpv);
        $balanzas         = $CBalanza->obtenerBalanzasEnvio();
        $salidaBalanza    = [];
        $traductorBalanza->setModoComunicacion('L');
        $traductorBalanza->setGrupo(0);
        $traductorBalanza->setDireccion(50);
    }

    foreach ($productosHistoricos as $producto) {
        if ($producto['estado'] === 'Pendiente') {
            $idArticulo          = $producto['idArticulo'];
            $pvpRecomendadoCiva  = (float) ($_POST['pvpRecomendado_' . $i] ?? 0);
            $datosArticulo       = $CArticulo->datosPrincipalesArticulo($idArticulo);
            $datosPrecios        = $CArticulo->articulosPrecio($idArticulo);
            $articuloPrecioAnt   = $datosPrecios['pvpCiva'];
            $pvpGuardados[$idArticulo] = [
                'pvpAnterior' => number_format($articuloPrecioAnt, 4),
                'pvpNuevo'    => number_format($pvpRecomendadoCiva, 4),
            ];

            if ($pvpRecomendadoCiva != $articuloPrecioAnt) {
                $ivaPrecio        = $datosArticulo['iva'] / 100;
                $precioProducto   = $producto['Nuevo'] * (1 + $ivaPrecio);
                $pvpRecomendado   = $precioProducto * (1 + $datosArticulo['beneficio'] / 100);
                $estado           = ($pvpRecomendado != $pvpRecomendadoCiva) ? 'A mano' : 'Recomendado';

                $nuevoSiva = number_format($pvpRecomendadoCiva / (1 + $ivaPrecio), 6);

                $datosHistorico = [
                    'idArticulo'    => $idArticulo,
                    'antes'         => $articuloPrecioAnt,
                    'nuevo'         => $pvpRecomendadoCiva,
                    'fechaCreacion' => $fechaCreacion,
                    'numDoc'        => $id,
                    'dedonde'       => 'Recalculo',
                    'tipo'          => 'Productos',
                    'estado'        => $estado,
                    'idUsuario'     => $Usuario['id'],
                ];
                $CArticulo->addHistorico($datosHistorico);
                $CArticulo->modArticulosPrecio($pvpRecomendadoCiva, $nuevoSiva, $idArticulo);

                if ($hayPeso && ($productosPeso[$idArticulo] ?? '') === 'peso' && !empty($datosArticulo['crefTienda'])) {
                    $balanzasProducto = $CBalanza->obtenerBalanzaPorIdArticulo($idArticulo);
                    // Unificar balanzas relacionadas
                    foreach ($balanzasProducto as $bp) {
                        $existe = false;
                        foreach ($balanzas as &$be) {
                            if ($be['idBalanza'] == $bp['idBalanza']) {
                                $be['relacionada'] = true;
                                $existe = true;
                                break;
                            }
                        }
                        unset($be);
                        if (!$existe) {
                            $bp['relacionada'] = true;
                            $balanzas[] = $bp;
                        }
                    }

                    $datosH2 = [
                        'codigo' => $datosArticulo['crefTienda'],
                        'nombre' => $datosArticulo['articulo_name'],
                        'precio' => $pvpRecomendadoCiva,
                        'PLU'    => '',
                    ];
                    $datosH3 = [
                        'codigo'       => $producto['id'],
                        'tipoProducto' => $productosPeso[$idArticulo],
                        'iva'          => $datosArticulo['iva'],
                        'seccion'      => '',
                    ];

                    foreach ($balanzas as $balanza) {
                        // Resetear PLU y sección en cada iteración para evitar que el valor
                        // de una balanza específica se filtre a las balanzas broadcast.
                        $datosH2['PLU']     = '';
                        $datosH3['seccion'] = '';
                        if (!empty($balanza['relacionada'])) {
                            $relacion = $CBalanza->obtenerPluActual($balanza['idBalanza'], $idArticulo);
                            $datosH2['PLU'] = $relacion['plu'] ?? '';
                            if (isset($balanza['conSeccion']) && strtolower($balanza['conSeccion']) === 'si') {
                                $datosH3['seccion'] = $relacion['seccion'] ?? '';
                            }
                        }
                        $traductorBalanza->setGrupo($balanza['Grupo']);
                        $traductorBalanza->setDireccion($balanza['Dirección']);
                        $modoCom = (strtolower($balanza['conSeccion'] ?? '') === 'si') ? 'H' : 'L';
                        $traductorBalanza->setModoComunicacion($modoCom);
                        $traductorBalanza->setH2Data($datosH2);
                        $traductorBalanza->setH3Data($datosH3);
                        // error_log('[' . $Usuario['nombre'] . "] Recalculo balanza ID {$balanza['idBalanza']}: " . json_encode($datosH2) . json_encode($datosH3)); // TRACE
                        if (!isset($salidaBalanza[$balanza['idBalanza']])) {
                            $salidaBalanza[$balanza['idBalanza']] = '';
                        }
                        $salidaBalanza[$balanza['idBalanza']] .= (string) $traductorBalanza->traducirH2();
                        $salidaBalanza[$balanza['idBalanza']] .= (string) $traductorBalanza->traducirH3();
                    }
                }
            }
        }

        if (in_array($producto['estado'], ['Pendiente', 'Sin revisar', 'Sin Cambios'], true)) {
            $i++;
        }
    }

    $CArticulo->modificarEstadosHistorico($id, $dedonde);

    $mensajeBalanza = '';
    if ($hayPeso) {
        foreach ($balanzas as $balanza) {
            $ruta_balanza      = '/' . str_replace(' ', '', $balanza['nombreBalanza']) . $balanza['idBalanza'];
            $directorioBalanza = $RutaServidor . $rutatmp . $ruta_balanza;
            $traductorBalanza->setRutaBalanza($ruta_balanza);
            $salida  = $salidaBalanza[$balanza['idBalanza']] ?? '';
            $resultado = @file_put_contents($directorioBalanza . '/filetx', $salida);
            if ($resultado === false) {
                $mensajeBalanza = 'Error grave de Comunicación: No se pudo escribir el fichero de comunicación con la balanza.';
                $ComunicacionBalanza['Comprobaciones'][] = ['tipo' => 'warning', 'mensaje' => $mensajeBalanza, 'dato' => []];
            } else {
                $traductorBalanza->setRutaBalanza($directorioBalanza);
                $ejecucion = $traductorBalanza->ejecutarDriverBalanza();
                if ($ejecucion === false) {
                    // El filetx se escribió correctamente; el driver falla si la balanza
                    // no está conectada. Es un aviso, no un error de comunicación.
                    $mensajeBalanza = 'Fichero enviado a balanza ID ' . $balanza['idBalanza'] . ' (driver no ejecutado — balanza no conectada o no disponible).';
                    $ComunicacionBalanza['Comprobaciones'][] = ['tipo' => 'info', 'mensaje' => $mensajeBalanza, 'dato' => []];
                } else {
                    $mensajeBalanza = 'Comunicación con la balanza ID ' . $balanza['idBalanza'] . ' realizada correctamente.';
                    $ComunicacionBalanza['Comprobaciones'][] = ['tipo' => 'success', 'mensaje' => $mensajeBalanza, 'dato' => [$datosH2, $datosH3]];
                }
            }
        }
    }

    // Guardar datos en cache XML para impresión posterior
    $cacheDir  = __DIR__ . '/cache';
    $cacheName = 'recalculoprecios_' . $Usuario['id'] . '_' . $id . '.xml';
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><recalculo/>');
    $xml->addChild('idAlbaran', $id);
    $xml->addChild('fecha', $fecha);
    $xml->addChild('idProveedor', $datosAlbaran['idProveedor']);
    $xml->addChild('nombreProveedor', $datosProveedor['nombrecomercial']);
    $xProds = $xml->addChild('productos');
    foreach ($productosHistoricos as $prod) {
        if ($prod['estado'] !== 'Pendiente') {
            continue;
        }
        $art = $CArticulo->datosPrincipalesArticulo($prod['idArticulo']);
        $ref = $CArticulo->buscarReferencia($prod['idArticulo'], $datosAlbaran['idProveedor']);
        $xP  = $xProds->addChild('producto');
        $xP->addChild('idArticulo',    (int)    $prod['idArticulo']);
        $xP->addChild('nombre',        htmlspecialchars($art['articulo_name'] ?? '', ENT_XML1, 'UTF-8'));
        $xP->addChild('referencia',    htmlspecialchars($ref['crefTienda']    ?? '', ENT_XML1, 'UTF-8'));
        $xP->addChild('costeAnterior', $prod['Antes']);
        $xP->addChild('costeNuevo',    $prod['Nuevo']);
        $xP->addChild('pvpAnterior',   $pvpGuardados[$prod['idArticulo']]['pvpAnterior'] ?? '');
        $xP->addChild('pvpNuevo',      $pvpGuardados[$prod['idArticulo']]['pvpNuevo']    ?? '');
    }
    $xml->asXML($cacheDir . '/' . $cacheName);
    $guardadoOk = true;
}

// Recomprobar tras posible guardado en este mismo request
$cacheExists = file_exists($cacheDir . '/' . $cacheName);

// Cargar productos desde XML si ya fue guardado
$productosXml = [];
if ($cacheExists) {
    $xmlData = simplexml_load_file($cacheDir . '/' . $cacheName);
    foreach ($xmlData->productos->producto as $prod) {
        $productosXml[] = [
            'idArticulo'    => (int)    $prod->idArticulo,
            'nombre'        => (string) $prod->nombre,
            'referencia'    => (string) $prod->referencia,
            'costeAnterior' => (string) $prod->costeAnterior,
            'costeNuevo'    => (string) $prod->costeNuevo,
            'pvpAnterior'   => (string) $prod->pvpAnterior,
            'pvpNuevo'      => (string) $prod->pvpNuevo,
        ];
    }
}

include __DIR__ . '/template/view_recalculo.php';
