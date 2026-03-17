<?php
include_once './../../inicial.php';
//Carga de archivos php necesarios
include_once $URLCom . '/modulos/mod_compras/funciones.php';
include_once $URLCom . '/controllers/Controladores.php';
include_once $URLCom . '/clases/Proveedores.php';
include_once $URLCom . '/modulos/mod_compras/clases/albaranesCompras.php';
include_once $URLCom . '/modulos/mod_compras/clases/facturasCompras.php';
include_once $URLCom . '/controllers/parametros.php';
//Carga de clases necesarias
$ClasesParametros = new ClaseParametros('parametros.xml');
$Cproveedor = new Proveedores($BDTpv);
$CAlb = new AlbaranesCompras($BDTpv);
$CFac = new FacturasCompras($BDTpv);
$Controler = new ControladorComun;
$Controler->loadDbtpv($BDTpv);
//Inicializar las variables
$dedonde = "factura";
$titulo = "Factura De Proveedor";
// Valores por defecto de estado y accion.
// [estado] -> Nuevo,Sin Guardar,Guardado,Contabilizado.
// [accion] -> editar,ver
$estado = 'Nuevo';
// Si existe accion, variable es $accion , sino es "editar"
$accion = (isset($_GET['accion'])) ? $_GET['accion'] : 'ver';
$fecha = date('d-m-Y');
$fechaImporte = date('Y-d-m');
$idDocumentoTemporal = 0; // idDocumentoTemporal -> idAlbaranTemporal o idPedidoTemporal o idFacturaTemporal
$idDocumento = 0; // idDocumento -> idAlbaran o idPedido o idFactura
$idProveedor = "";
$nombreProveedor = "";
$Datostotales = array();
$ajustesGuardados = null;
$errores = array();
$creado_por = array();
$formaPago = 0;
$suNumero = "";
$fechaVencimiento = "";
$albaran_html_linea_producto = array();
$JS_datos_albaranes = '';
$html_adjuntos = '';
// --- Controlasmos POST de crearDesdeAlbaranes --- //
if ($_POST['action'] === 'crearDesdeAlbaranes') {
    $idProveedor = (int)$_POST['idProveedor'];
    $albaranes   = $_POST['albaranes'];
    $crearDesdeAlbaranes = true;
    $htmlScript = '<script>
        window.crearDesdeAlbaranes = true;
        window.proveedorInicial = ' . $idProveedor . ';
        window.albaranesIniciales = ' . json_encode($albaranes) . ';
    </script>';
}

//Cargamos la configuración por defecto y las acciones de las cajas
$parametros = $ClasesParametros->getRoot();
foreach ($parametros->cajas_input->caja_input as $caja) {
    // Ahora cambiamos el parametros por defecto que tiene dedonde = pedido y le ponemos albaran
    $caja->parametros->parametro[0] = $dedonde;
}
$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
$configuracion = $Controler->obtenerConfiguracion($conf_defecto, 'mod_compras', $Usuario['id']);
$configuracionArchivo = array();
foreach ($configuracion['incidencias'] as $config) {
    if (get_object_vars($config)['dedonde'] == $dedonde) {
        array_push($configuracionArchivo, $config);
    }
}
// --------------- Controlamos GET -------------------------  //
// Por GET podemos recibir uno o varios parametros:
//  [id] cuando editamos o vemos un albaran pulsando en listado.
//  [tActual] cuando pulsamos en cuadro albaranes temporales.
//  [accion] cuando indicamos que accion vamos hacer.
$contador_get = count($_GET);
if ($contador_get > 0) {
    // Si existe accion, variable es $accion , sino es "ver"
    if (isset($_GET['accion'])) {
        $accion = $_GET['accion'];
        $contador_get = $contador_get - 1;
    }
    if (isset($_GET['tActual'])) {
        $idDocumentoTemporal = $_GET['tActual']; // Id de albaran temporal
        // Si viene temporal, siempre es la accion es editar
        $accion = 'editar';
        $contador_get = $contador_get - 1;
    }
    if (isset($_GET['id'])) {
        $idDocumento = $_GET['id']; // Id real de factura
        $contador_get = $contador_get - 1;
    }
}
if ($contador_get > 0) {
    // Hay un parametro get, que no se controlo
    // deberíamos indicarlo.
    array_push(
        $errores,
        $CAlb->montarAdvertencia(
            'warning',
            'Hay parametro get,distinto a tActual,accion,id:' . json_encode($_GET)
        )
    );
}

// ---------- Posible errores o advertencias mostrar     ------------------- //
if ($idDocumento > 0) {
    // Comprobamos si existe temporal del idFactura
    $temporales = $CFac->comprobarTemporalIdFacpro($idDocumento);
    // Si existe numero albaran, comprobamos cuantos temporales tiene Factura y si tiene uno obtenemos el numero.
    if (isset($temporales['idTemporal']) && $temporales['idTemporal'] !== null) {
        // Existe un temporal de este pedido por lo que cargo ese temporal.
        $idDocumentoTemporal = $temporales['idTemporal'];
        if ($accion !== 'editar' && $accion !== 'ver') {
            $accion = 'ver';
            // Creo alert
            echo '<script>alert("No se permite editar, ya que alguien esta editandolo, ya que hay un temporal");</script>';
        }
    } else {
        if (count($temporales) > 0) {
            array_push(
                $errores,
                $CAlb->montarAdvertencia('danger', $temporales)
            );
        }
    }
}
if ($idDocumento > 0 && count($errores) === 0) {
    // Si existe id y no hay errores estamos modificando directamente un albaran.
    $datosDocumento = $CFac->GetFactura($idDocumento);
    if (isset($datosDocumento['error'])) {
        $errores = $datosDocumento['error'];
    } else {
        if (isset($datosDocumento['estado'])) {
            $estado = $datosDocumento['estado'];
            $idDocumento = $datosDocumento['id'];
            if ($datosDocumento['estado'] == "Contabilizado") {
                // Cambiamos accion, ya que solo puede ser ver.
                $accion = 'ver';
            }
        }
    }
}
if ($idDocumentoTemporal > 0 && count($errores) === 0) {
    // Puede entrar cuando :
    //   -Viene de albaran temporal
    //   -Se recargo mientras editamos.
    //   -Cuando pulsamos guardar.
    $datosDocumento = $CFac->buscarFacturaTemporal($idDocumentoTemporal);
    if (isset($datosDocumento['error'])) {
        array_push(
            $errores,
            $CFac->montarAdvertencia(
                'danger',
                'Error 1.1 en base datos.Consulta:' . json_encode($datosDocumento['consulta'])
            )
        );
    } else {
        // Preparamos datos que no viene o que vienen distintos cuando es un temporal.
        $datosDocumento['Productos'] = json_decode($datosDocumento['Productos'], true);
        $idDocumento = $datosDocumento['numfacpro'];
        $estado = $datosDocumento['estadoFacPro'];
        $datosDocumento['FechaVencimiento'] = '0000-00-00';
    }
}
if (count($errores) == 0) {
    // Si no hay errores graves continuamos.
    if ($estado == 'Nuevo' && !isset($datosDocumento)) {
        // SI es NUEVO.
        $datosDocumento = array();
        $datosDocumento['Fecha'] = "0000-00-00 00:00:00";
        $datosDocumento['Su_numero'] = '';
        $datosDocumento['idProveedor'] = 0;
        $accion = 'editar';
        $creado_por = $Usuario;
    } else {
        // No es NUEVO
        $idProveedor = $datosDocumento['idProveedor'];
        $proveedor = $Cproveedor->buscarProveedorId($idProveedor);
        $nombreProveedor = $proveedor['nombrecomercial'];
        $productos = $datosDocumento['Productos'];
        $fecha = ($datosDocumento['Fecha'] == "0000-00-00 00:00:00")
            ? date('d-m-Y') : date_format(date_create($datosDocumento['Fecha']), 'd-m-Y');
        $creado_por = $CFac->obtenerDatosUsuario($datosDocumento['idUsuario']);
        if (isset($datosDocumento['Albaranes'])) {
            if ($idDocumentoTemporal > 0) {
                // Cuando viene de tActual obtenemos .
                // Solo convertimos $idDocumentoTemporal >0 , ya que es cuando viene json
                $datosDocumento['Albaranes'] = json_decode($datosDocumento['Albaranes'], true);
            }
            if (count($datosDocumento['Albaranes']) > 0) {
                // Ahora obtengo todos los datos de ese albaran.
                foreach ($datosDocumento['Albaranes'] as $key => $albaran) {
                    // ========             Ahora obtenemos todos los datos         ======== //
                    if (isset($albaran['idAlbaran'])) {
                        $idAlbaran = $albaran['idAlbaran'];
                    } else {
                        // Entra aquí cuando se añadio facturatemporal un albaran , pero no se guardo, solo creo temporal.
                        $idAlbaran = $albaran['idAdjunto'];
                        $datosDocumento['Albaranes'][$key]['idAlbaran'] = $idAlbaran;
                    }
                    $e = $CAlb->DatosAlbaran($idAlbaran);
                    // El indice 'estado' es el estado del albaran puede ser "Sin Guardar", "Guardado","Facturado"
                    // Ahora vamos a crear el estado del adjunto, pero teniendo en cuenta
                    // Que si estado_albaran es "Sin Guardar" tenemos que enviar un error.
                    // Si estado_albaran es "Guardado" entonces el estado adjunto es 'Eliminado'.
                    // Si estado_albaran es "Facturado" entonces el estado ajunto es 'activo'.
                    if ($e['estado'] === 'Facturado') {
                        $estado_adjunto = 'activo';
                    } else {
                        $estado_adjunto = 'Eliminado';
                        if ($e['estado'] !== 'Guardado') {
                            // Informo posible error, ya que el estado pedido no es Guardado , ni Facturado..
                            array_push(
                                $errores,
                                $CFac->montarAdvertencia(
                                    'danger',
                                    'Posible error, el pedido con id:' . $albaran['idAdjunto'] . ' tiene estado ' . $e['estado']
                                )
                            );
                        }
                    }
                    $datosDocumento['Albaranes'][$key]['estado'] = $estado_adjunto;
                    $datosDocumento['Albaranes'][$key]['fecha'] = $e['Fecha'];
                    $datosDocumento['Albaranes'][$key]['totalSiva'] = $e['total_siniva'];
                    $datosDocumento['Albaranes'][$key]['total'] = $e['total'];
                    $datosDocumento['Albaranes'][$key]['NumAdjunto'] = $e['Numalbpro'];
                    $datosDocumento['Albaranes'][$key]['idAdjunto'] = $idAlbaran;
                    $datosDocumento['Albaranes'][$key]['Su_numero'] = $e['Su_numero'];
                    $datosDocumento['Albaranes'][$key]['nfila'] = $key + 1;
                    // ========                 JS_datos_pedidos                    ======== //
                    $JS_datos_albaranes .=  'datos=' . json_encode($datosDocumento['Albaranes'][$key]) . ';'
                        . 'albaranes.push(datos);';
                    // ========               $html_adjuntos                        ======== //
                    $h = lineaAdjunto($datosDocumento['Albaranes'][$key], "factura", $accion);
                    $html_adjuntos .= $h['html'];
                    // ========  Array para mostrar en lineas productos de adjuntos ======== //
                    $h = htmlDatosAdjuntoProductos($datosDocumento['Albaranes'][$key], $dedonde);
                    $albaran_html_linea_producto[$idAlbaran] = $h;
                }
            }
        }
        $formaPago = (isset($datosDocumento['formaPago'])) ? $datosDocumento['formaPago'] : 0;
        $fechaVencimiento = $datosDocumento['FechaVencimiento'];
        if (isset($datosDocumento['numfacpro'])) {
            $d = $CFac->buscarFacturaNumero($datosDocumento['numfacpro']);
            $idDocumento = $d['id'];
            // Debemos saber si debemos tener incidencias para ese albaran, ya que el boton incidencia es distinto.
            $incidencias = incidenciasAdjuntas($idDocumento, "mod_compras", $BDTpv, $dedonde);
        }
        if ($datosDocumento['Su_num_factura'] !== "") {
            $suNumero = $datosDocumento['Su_num_factura'];
        }
    }
    $textoFormaPago = htmlFormasVenci($formaPago, $BDTpv); // Generamos ya html.
    if (isset($datosDocumento['Productos'])) {
        // Siempre recalcular desde líneas: el recálculo sirve como baseline para data-original en el DOM.
        $p = (object) $productos;
        $Datostotales = $CFac->recalculoTotales($p);

        // Cargar ajustes de céntimos
        if ($idDocumentoTemporal > 0 && isset($datosDocumento['total_ivas']) && !empty($datosDocumento['total_ivas'])) {
            // Documento con temporal existente: recuperar ajustes del temporal
            $ajustesTmp = json_decode($datosDocumento['total_ivas'], true);
            if ($ajustesTmp !== null && isset($ajustesTmp['desglose'])) {
                $ajustesGuardados = $ajustesTmp;
            }
        } elseif ($idDocumentoTemporal === 0 && isset($datosDocumento['IvasGuardados']) && count($datosDocumento['IvasGuardados']) > 0) {
            // Documento guardado sin temporal: reconstruir ajustes como delta entre facproIva y recálculo de líneas.
            $desgloseAjuste = [];
            $hayDelta = false;
            foreach ($datosDocumento['IvasGuardados'] as $ivaRow) {
                $tipo      = intval($ivaRow['iva']);
                $savedBase = floatval($ivaRow['totalbase']);
                $savedIva  = floatval($ivaRow['importeIva']);
                $calcBase  = isset($Datostotales['desglose'][$tipo]) ? floatval($Datostotales['desglose'][$tipo]['base']) : 0;
                $calcIva   = isset($Datostotales['desglose'][$tipo]) ? floatval($Datostotales['desglose'][$tipo]['iva']) : 0;
                $desgloseAjuste[$tipo] = [
                    'base' => number_format($savedBase, 2, '.', ''),
                    'iva'  => number_format($savedIva, 2, '.', ''),
                ];
                if (abs($savedBase - $calcBase) > 0.001 || abs($savedIva - $calcIva) > 0.001) {
                    $hayDelta = true;
                }
            }
            $savedTotal = floatval($datosDocumento['total']);
            if (abs($savedTotal - floatval($Datostotales['total'])) > 0.001) {
                $hayDelta = true;
            }
            if ($hayDelta) {
                $ajustesGuardados = [
                    'total'    => $savedTotal,
                    'desglose' => $desgloseAjuste,
                ];
            }
        }
    }
}
//  ---------  Control y procesos para guardar el documento. ------------------ //
if (isset($_POST['Guardar'])) {
    //@Objetivo:
    // Guardar los datos que recibimos.
    // todo fue OK , pero sino mostramos el error.

    if ($_POST['fechaVenci'] === '') {
        $_POST['fechaVenci'] = '0000-00-00';
    }
    $guardar = $CFac->guardarFactura();

    if (count($guardar) == 0) {
        header('Location: facturasListado.php');
    } else {
        // Hubo errores o advertencias.
        foreach ($guardar as $error) {
            array_push($errores, $error);
        }
    }
}
// ============                 Montamos el titulo                      ==================== //
// Creo que este codigo no es necesario en facturas, de momento, ya que no tenemos relacion Contabilizado
$html_relacionado = '';
if (isset($numFactura)) {
    $html_relacionado = ' <span style="font-size: 0.55em;vertical-align: middle;" class="label label-default">';
    $html_relacionado .= 'factura:' . $numFactura['idFactura'];
    $html_relacionado .= '</span>';
}
$titulo .= ' ' . $idDocumento . $html_relacionado . ' - ' . $accion;
// ============= Creamos variables de estilos para cada estado y accion =================== //
$estilos = array(
    'readonly'       => '',
    'styleNo'        => 'style="display:none;"',
    'pro_readonly'   => '',
    'pro_styleNo'    => '',
    'btn_guardar'    => '',
    'btn_cancelar'   => '',
    'input_factur'   => '',
    'select_factur'  => '',
    'evento_cambio'  => ''
);
if (isset($_GET['id']) || isset($_GET['tActual'])) {
    // Quiere decir que ya inicio , ya tuvo que meter proveedor.
    // no se permite cambiar proveedor.
    $estilos['pro_readonly'] = ' readonly';
    $estilos['pro_styleNo'] = ' style="display:none;"';
    $estilos['styleNo'] = '';
    $estilos['evento_cambio'] = 'onchange ="addTemporal(' . "'" . $dedonde . "'" . ')"'; // Lo utilizo para crear temporal cuando cambia valor.

}
if ($accion === 'ver') {
    $estilos['readonly'] = ' readonly';
    $estilos['styleNo'] = ' style="display:none;"';
    $estilos['input_factur'] = ' readonly';
    $estilos['select_factur'] = 'disabled="true"';
    $estilos['evento_cambio'] = ''; // Sin eventos en modo visualización
}
if ($idDocumentoTemporal === 0) {
    // Solo se muestra cuando el idDocumentoTemporal es 0
    $estilos['btn_guardar'] = 'style="display:none;"';
    // Una vez se cree temporal, con javascript se quita style
}


?>

<!DOCTYPE html>
<html>

<head>
    <?php include_once $URLCom . '/head.php'; ?>

    <script type="text/javascript">
        // Esta variable global la necesita para montar la lineas.
        // En configuracion podemos definir SI / NO
        <?php echo 'var configuracion=' . json_encode($configuracionArchivo) . ';'; ?>
        var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
        cabecera['idUsuario'] = <?php echo $creado_por['id']; ?>; // Tuve que adelantar la carga, sino funcionaria js.
        cabecera['idTienda'] = <?php echo $Tienda['idTienda']; ?>;
        cabecera['estado'] = '<?php echo $estado; ?>'; // Si no hay datos GET es 'Nuevo'
        cabecera['idTemporal'] = '<?php echo $idDocumentoTemporal; ?>';
        cabecera['idReal'] = '<?php echo $idDocumento; ?>';
        cabecera['idProveedor'] = '<?php echo $idProveedor; ?>';
        cabecera['fecha'] = '<?php echo $fecha; ?>';
        cabecera['suNumero'] = '<?php echo $suNumero; ?>';
        var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array
        var albaranes = [];
        var salto_linea = 'ReferenciaPro'; // Valor por defecto
        // Ajuste de céntimos - parámetros y permisos
        window.ajusteCentimosHabilitado = <?php echo (isset($conf_defecto['ajuste_centimos']) && $conf_defecto['ajuste_centimos'] === 'Si') ? 'true' : 'false'; ?>;
        window.ajusteCentimosDesglose = <?php echo (isset($conf_defecto['ajuste_centimos_desglose']) && $conf_defecto['ajuste_centimos_desglose'] === 'Si') ? 'true' : 'false'; ?>;
        window.maxAjusteCentimos = <?php echo isset($conf_defecto['max_ajuste_centimos']) ? intval($conf_defecto['max_ajuste_centimos']) : 1; ?>;
        window.permisoAjusteCentimos = <?php echo ($ClasePermisos->getAccion('AjusteCentimos') == 1) ? 'true' : 'false'; ?>;
        window.decimalesCantidad = <?php echo isset($conf_defecto['decimales_cantidad']) ? intval($conf_defecto['decimales_cantidad']) : 3; ?>;
        window.decimalesCoste = <?php echo isset($conf_defecto['decimales_coste']) ? intval($conf_defecto['decimales_coste']) : 4; ?>;
        window.modoVisualizacion = <?php echo ($accion === 'ver') ? 'true' : 'false'; ?>;
        window.dedonde = '<?php echo $dedonde; ?>';
        <?php
        if (isset($idDocumentoTemporal) || isset($idDocumento)) {
            if (isset($productos)) {
                foreach ($productos as $k => $product) {
        ?>
                    datos = <?php echo json_encode($product); ?>;
                    productos.push(datos);
        <?php
                    // --- Creo que esto no hace falta ya , pienso que es un codigo innecesario ---
                    // cambiamos estado y cantidad de producto creado si fuera necesario.
                    // if ($product['estado'] !== 'Activo') {
                    //    echo 'productos['.$k.'].estado = '.'"' . $product['estado'] . '";';
                    //}
                }
            }
            if (isset($datosDocumento['Albaranes'])) {
                if ($JS_datos_albaranes != '') {
                    echo $JS_datos_albaranes;
                }
            }
        }
        ?>
    </script>
</head>

<body>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>
    <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_compras/js/AccionesDirectas.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js?v=0431-48"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
    <?php
    if (isset($htmlScript)) {
        echo $htmlScript;
    }
    include_once $URLCom . '/modulos/mod_menu/menu.php';
    ?>
    <script type="text/javascript">
        <?php
        if (isset($_POST['Cancelar'])) {
        ?>
            mensajeCancelar(<?php echo $idDocumentoTemporal; ?>, <?php echo "'" . $dedonde . "'"; ?>);
        <?php
        }
        echo $VarJS;
        ?>

        function anular(e) {
            tecla = (document.all) ? e.keyCode : e.which;
            return (tecla != 13);
        }
    </script>
    <div class="container">
        <?php
        if (isset($errores)) {
            foreach ($errores as $comprobaciones) {
                echo $CFac->montarAdvertencia($comprobaciones['tipo'], $comprobaciones['mensaje'], 'OK');
                if ($comprobaciones['tipo'] === 'danger') {
                    exit; // No continuo.
                }
            }
        }
        ?>
        <form action="" method="post" name="formProducto" onkeypress="return anular(event)" onsubmit="prepararAjustesParaGuardar()">
            <input type="hidden" name="ajustesCentimos" id="ajustesCentimosHidden" value="">
            <?php
            echo '<h3 class="text-center">' . $titulo . '</h3>';
            //Botones avanzar y retroceder generales
            if ($idDocumento > 0 && $accion != "editar") {
                $primeraF = $CFac->getPrimeraFactura();
                $ultimaF  = $CFac->getUltimaFactura();
                $primeraP = $CFac->getPrimeraFactura($idProveedor);
                $ultimaP = $CFac->getUltimaFactura($idProveedor);

                $botones = '';
                if ($idDocumento != $primeraF) {
                    // Botón retroceder (más pequeño)
                    $botones .= '<a class="nohistory" href="factura.php?id='
                        . ($idDocumento - 1) . '&accion=ver" title="Factura Anterior">'
                        . '<span class="glyphicon glyphicon-step-backward"></span></a> ';
                }
                $botones .= ' Global ';
                if ($idDocumento != $ultimaF) {
                    // Botón avanzar (más pequeño)
                    $botones .= '<a class="nohistory" href="./factura.php?id='
                        . ($idDocumento + 1) . '&accion=ver" title="Factura Siguiente">'
                        . '<span class="glyphicon glyphicon-step-forward"></span></a>';
                }
                $botones .= ' |';
                if ($idDocumento != $primeraP) {
                    //obtener la anterior factura de ese proveedor
                    $anteriorP = $CFac->getFacturaAnteriorSiguiente($idDocumento, $idProveedor, 'anterior');
                    if ($anteriorP !== false) {
                        $botones .= ' <a class="nohistory" href="factura.php?id='
                            . $anteriorP . '&accion=ver" title="Factura Anterior de este proveedor">'
                            . '<span class="glyphicon glyphicon-backward"></span></a> ';
                    }
                }
                $botones .= ' Proveedor ';

                if ($idDocumento != $ultimaP) {
                    //obtener la siguiente factura de ese proveedor
                    $siguienteP = $CFac->getFacturaAnteriorSiguiente($idDocumento, $idProveedor, 'siguiente');
                    if ($siguienteP !== false) {
                        $botones .= ' <a class="nohistory" href="factura.php?id='
                            . $siguienteP . '&accion=ver" title="Factura Siguiente de este proveedor">'
                            . '<span class="glyphicon glyphicon-forward"></span></a> ';
                    }
                }

                // Centrar los botones respecto al título
                if ($botones !== '') {
                    echo '<div class="text-center" style="margin-top:6px;"><div style="display:inline-block;">'
                        . $botones
                        . '</div></div>';
                }
            }


            ?>

            <div class="col-md-12">
                <div class="col-md-8">
                    <?php echo $Controler->getHtmlLinkVolver('Volver');
                    // Botones de incidencias.
                    if ($idDocumento > 0) {
                        echo '<input class="btn btn-warning" size="12" onclick="abrirModalIndicencia(' . "'" . $dedonde
                            . "'" . ' , configuracion, 0 ,' . $idDocumento
                            . ');" value="Añadir incidencia " name="addIncidencia" id="addIncidencia">';
                    }
                    if (isset($incidencias) && count($incidencias) > 0) {
                        echo ' <input class="btn btn-info" size="15" onclick="abrirIncidenciasAdjuntas('
                            . $idDocumento . " ,'mod_compras', '" . $dedonde . "'"
                            . ')" value="Incidencias Adjuntas " name="incidenciasAdj" id="incidenciasAdj">';
                    }
                    if ($estado != "Contabilizado" || $accion != "ver") {
                        // El btn guardar solo se crea si el estado es "Nuevo","Sin Guardar","Guardado"
                        echo '<input class="btn btn-primary" ' . $estilos['btn_guardar']
                            . ' type="submit" value="Guardar  (Alt+G)" name="Guardar" id="bGuardar" accesskey="G">';
                    }
                    if (isset($ClasePermisos) && $ClasePermisos->getAccion("AjusteCentimos") == 1) {
                        // Boton con engranaje para abrir el ajuste de configuración de parametros.xml
                        echo ' <a class="btn btn-default" onclick="abrirModalConfig()" title="Ajuste de configuración"><span class="glyphicon glyphicon-cog"></span></a>';
                    }
                    ?>
                </div>
                <div class="col-md-4 text-right">
                    <?php
                    if ($estado != "Contabilizado" && $accion != "ver") {
                        // Mostramos input temporal
                        echo ' temporal:' . '<input type="text" readonly size ="4" name="idTemporal" value="' . $idDocumentoTemporal . '">';
                    }



                    if ($estado === "Nuevo") {
                        // El btn cancelar solo se crea si el estado es "Nuevo"
                        // pero solo se muestra cuando hay un temporal, ya que no tiene sentido mostrarlo si no hay temporal
                        echo '<input type="submit" class="btn btn-danger" value="Borrar Temporal" ' . $estilos['btn_cancelar'] . ' name="Cancelar" id="bCancelar">';
                    }
                    ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-7">

                    <div class="col-md-12">
                        <div class="col-md-4">
                            <label>Fecha:</label>
                            <?php $pattern_numerico = ' pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" ';
                            $title_fecha = ' placeholder="dd-mm-yyyy" title=" Formato de entrada dd-mm-yyyy"';
                            echo '<input type="text" name="fecha" id="fecha" size="8" data-obj= "cajaFecha" '
                                . $estilos['input_factur'] . ' value="' . $fecha . '" ' . $estilos['evento_cambio'] . ' onkeydown="controlEventos(event)" '
                                . $pattern_numerico . $title_fecha . (($estado === "Nuevo") ? '  autofocus' : '') . ' />';
                            ?>
                        </div>
                        <div class="col-md-4">
                            <label>Estado:</label>
                            <input type="text" id="estado" name="estado" size="9" value="<?php echo $estado; ?>"
                                readonly>
                        </div>
                        <div class="col-md-4">
                            <label>Creado por:</label>
                            <input type="text" id="Usuario" name="Usuario" value="<?php echo $creado_por['nombre']; ?>"
                                size="8" readonly>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="text-center">Proveedor</label>
                        <?php
                        echo '<div class="col-md-2">
                                                <input type="text" id="id_proveedor" name="id_proveedor" data-obj= "cajaIdProveedor" value="'
                            . $idProveedor . '" ' . $estilos['pro_readonly'] . ' size="2" onkeydown="controlEventos(event)" placeholder="id">
                                            </div>';
                        echo '<div class="col-md-10">
                                                <input type="text" id="Proveedor" name="Proveedor" data-obj= "cajaProveedor" '
                            . 'placeholder="Nombre de proveedor (Alt+P)" onkeydown="controlEventos(event)" value="'
                            . $nombreProveedor . '" ' . $estilos['pro_readonly'] . ' size="60" accesskey="P" />'
                            . ' <a id="buscar" ' . $estilos['pro_styleNo'] . ' class="btn glyphicon glyphicon-search buscar"'
                            . ' onclick="buscarProveedor(' . "'" . $dedonde . "'" . ',Proveedor.value)"></a>
                                            </div>';
                        ?>
                    </div>

                    <div class="col-md-12">
                        <div class="col-md-4">
                            <label>Su número:</label>
                            <input type="text" id="suNumero" name="suNumero" value="<?php echo $suNumero; ?>" size="10"
                                <?php echo $estilos['evento_cambio']; ?> onkeydown="controlEventos(event)"
                                data-obj="CajaSuNumero" <?php echo $estilos['input_factur']; ?> />
                        </div>
                        <div class="col-md-4">
                            <label>Fecha vencimiento:</label>
                            <?php
                            echo '<input type="date" name="fechaVenci" id="fechaVenci" size="8" data-obj= "cajafechaVenci"'
                                . $estilos['input_factur'] . ' value="' . $fechaVencimiento . '" onkeydown="controlEventos(event)" '
                                . $pattern_numerico . $title_fecha . '>';
                            ?>
                        </div>
                        <div class="col-md-4">
                            <label>Forma de pago:</label>
                            <div id="formaspago">
                                <select name='formaVenci' id='formaVenci' <?php echo   $estilos['select_factur']; ?>>
                                    <?php
                                    if (isset($textoFormaPago)) {
                                        echo $textoFormaPago['html'];
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 bg-info div_adjunto">
                    <?php
                    if ($accion !== 'ver') {
                    ?>
                        <label id="numPedidoT">Número del albarán:</label>
                        <input type="text" id="numPedido" name="numPedido" value="" size="5" placeholder='Num'
                            data-obj="numPedido" onkeydown="controlEventos(event)">
                        <a id="buscarPedido" class="glyphicon glyphicon-search buscar"
                            onclick="buscarAdjunto('factura')"></a>
                    <?php
                    } ?>
                    <table class="table" id="tablaPedidos">
                        <thead>
                            <tr>
                                <td><b>Número</b></td>
                                <td><b>Su Número</b></td>
                                <td><b>Fecha</b></td>
                                <td><b>Sin Iva</b></td>
                                <td><b>Total</b></td>
                                <td></td>
                            </tr>
                        </thead>
                        <?php
                        if (isset($datosDocumento['Albaranes'])) {
                            if ($html_adjuntos != '') {
                                echo  $html_adjuntos;
                            }
                        }
                        ?>
                    </table>
                </div>
                <!-- Tabla de lineas de productos -->
                <div class="col-md-12">
                    <div class="col-md-12 form-inline bg-info" id="Row0" <?php echo $estilos['styleNo']; ?>>
                        <div class="form-group">
                            <input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo"
                                data-obj="cajaidArticulo" size="4" value="" onkeydown="controlEventos(event)">
                        </div>
                        <div class="form-group">
                            <input id="Referencia" type="text" name="Referencia" placeholder="Referencia"
                                data-obj="cajaReferencia" size="8" value="" onkeydown="controlEventos(event)">
                        </div>
                        <div class="form-group">
                            <input id="ReferenciaPro" type="text" name="ReferenciaPro" placeholder="Ref_proveedor"
                                data-obj="cajaReferenciaPro" size="10" value="" onkeydown="controlEventos(event)"
                                <?php echo (($estado != "Nuevo") ? '  autofocus' : ''); ?>>
                        </div>
                        <div class="form-group">
                            <input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras"
                                data-obj="cajaCodBarras" size="12" value="" data-objeto="cajaCodBarras"
                                onkeydown="controlEventos(event)">
                        </div>
                        <div class="form-group">
                            <input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion"
                                data-obj="cajaDescripcion" size="17" value="" onkeydown="controlEventos(event)">
                        </div>
                    </div>
                    <div class="col-lg-9">
                        <table id="tabla" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>L</th>
                                    <th>Num<span class="glyphicon glyphicon-info-sign" title="Numero de adjunto"></span>
                                    </th>
                                    <th>Id Articulo</th>
                                    <th>Referencia</th>
                                    <th>Referencia Proveedor</th>
                                    <th>Cod Barras</th>
                                    <th>Descripcion</th>
                                    <th>Unid</th>
                                    <th>Coste</th>
                                    <th>Iva</th>
                                    <th>Importe</th>
                                    <th></th>
                                </tr>

                            </thead>
                            <tbody>
                                <?php
                                //Recorremos los productos y vamos escribiendo las lineas.
                                if (isset($productos)) {
                                    $numAdjunto = 0;
                                    foreach (array_reverse($productos) as $producto) {
                                        // Ahora tengo que controlar si son lineas de adjunto, para añadir linea de adjunto.
                                        $numeroDoc = 0;
                                        if (isset($producto['idalbpro']) && $producto['idalbpro'] > 0) {
                                            $numeroDoc = $producto['idalbpro'];
                                        }
                                        if (isset($producto['numAlbaran']) && $producto['numAlbaran'] > 0) {
                                            $numeroDoc = $producto['numAlbaran'];
                                        }

                                        if ($numeroDoc <> $numAdjunto) {
                                            // Si numero documento es distinto a numerAdjunto,
                                            // entonces debemos obtener linea de adjunto para poner en productos.
                                            $numAdjunto = $numeroDoc;
                                            echo $albaran_html_linea_producto[$numeroDoc];
                                        }

                                        $decCant = isset($conf_defecto['decimales_cantidad']) ? intval($conf_defecto['decimales_cantidad']) : 3;
                                        $decCost = isset($conf_defecto['decimales_coste']) ? intval($conf_defecto['decimales_coste']) : 4;
                                        $html = htmlLineaProducto($producto, "factura", $estilos['input_factur'], $decCant, $decCost);
                                        echo $html['html'];
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="col-lg-3 pie-ticket">
                        <table id="tabla-pie" class="table">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Base</th>
                                    <th>IVA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (isset($Datostotales)) {
                                    $htmlIvas = htmlTotales($Datostotales, $ajustesGuardados);
                                    echo $htmlIvas['html'];
                                }
                                ?>

                            </tbody>
                        </table>
                        <?php
                        if (
                            $accion !== 'ver' && $ClasePermisos->getAccion('AjusteCentimos') == 1
                            && isset($conf_defecto['ajuste_centimos']) && $conf_defecto['ajuste_centimos'] === 'Si'
                        ) {
                            echo '<button type="button" id="btnAjusteCentimos" class="btn btn-default btn-sm" onclick="toggleAjusteCentimos()">';
                            echo '<span class="glyphicon glyphicon-pencil"></span> Ajuste céntimos';
                            echo '</button>';
                        }
                        ?>
                        <script>
                            <?php
                            if (isset($ajustesGuardados)) {
                                echo 'cargarAjustesCentimos(' . json_encode($ajustesGuardados) . ');';
                            }
                            ?>
                        </script>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php // Incluimos paginas modales
    echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>';
    include $RutaServidor . '/' . $HostNombre . '/plugins/modal/ventanaModal.php';
    ?>
</body>
<!-- solo si es ver -->
<?php
if ($accion === 'ver') {
?>
    <script>
        // Elimina el parámetro historyJS de la URL actual
        function removeHistoryJSParam() {
            const url = new URL(window.location.href);
            url.searchParams.delete("historyJS");
            window.history.replaceState({}, document.title, url.toString());
        }

        // Llamar a esta función después de cada navegación
        removeHistoryJSParam();
    </script>
<?php
}
?>

<?php
if (isset($crearDesdeAlbaranes) && $crearDesdeAlbaranes === true) {
?>
    <script>
        $(document).ready(function() {

            if (window.crearDesdeAlbaranes === true &&
                Array.isArray(window.albaranesIniciales) &&
                window.proveedorInicial > 0
            ) {
                // 1. Validar proveedor
                buscarProveedor('factura', 'id_proveedor', window.proveedorInicial);

                // 2. Cargar cada albarán como si el usuario hiciera Enter
                agregarAlbaranesSecuencial(window.albaranesIniciales);
            }

        });
    </script>
<?php
} ?>

</html>
