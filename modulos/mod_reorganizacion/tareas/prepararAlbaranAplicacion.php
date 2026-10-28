<?php

// @ Objetivo
// Preparar el albarán de la aplicación de una relación: vuelve a calcularla aquí, con lo que se
// eligió —los costes no se reciben de la pantalla, y la cantidad se vuelve a contrastar con lo
// disponible—, y deja un albarán temporal de compras con sus líneas —por entradas, uno por cada
// albarán de entrada del mes—, que el usuario revisa y guarda en la pantalla de albaranes. Preparar
// no mueve existencias ni costes. Exige el permiso «Preparar» de la pantalla de aplicar relación.

include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesAplicacion.php';
include_once $URLCom . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockConsulta.php';
// Como el cierre anual: el albarán se deja con la función de compras, que no se modifica.
include_once $URLCom . '/modulos/mod_compras/clases/albaranesCompras.php';

$CAplicacion = new RelacionesAplicacion();
$respuesta = array('ok' => false, 'datos' => array(), 'avisos' => array());

// Desde tareas.php hay que nombrar la vista: sin ella, el permiso se buscaría en tareas.php.
if ($ClasePermisos->getAccion('Preparar', array('modulo' => 'mod_reorganizacion', 'vista' => 'AplicarRelacion.php')) != 1) {
    $respuesta['avisos'][] = $CAplicacion->montarAdvertencia('warning', 'No tienes permiso para preparar el albarán: pídeselo al administrador.');
} else {
    $peticion = array(
        'idProveedor' => '',
        'numRelacion' => '',
        'tipoPeriodo' => '',
        'periodo' => '',
        'version' => 'periodo',
        'idProveedorTraspaso' => '',
        'precios' => array(),
        'ajustePeso' => '1',
        'ajusteValor' => '1',
        'cantidad' => '',
        'hermanas' => array(),
        'porEntradas' => '',
    );
    foreach (array('idProveedor', 'numRelacion', 'tipoPeriodo', 'periodo', 'version', 'idProveedorTraspaso', 'ajustePeso', 'ajusteValor', 'cantidad', 'porEntradas') as $campo) {
        if (isset($_POST[$campo])) {
            $peticion[$campo] = (string) $_POST[$campo];
        }
    }
    if (isset($_POST['precios'])) {
        $precios = json_decode($_POST['precios'], true);
        if (is_array($precios)) {
            $peticion['precios'] = $precios;
        }
    }
    // Las recetas hermanas: una lista que no se puede leer no se ignora, se rechaza al calcular.
    if (isset($_POST['hermanas']) && $_POST['hermanas'] !== '') {
        $peticion['hermanas'] = json_decode($_POST['hermanas'], true);
        if (!is_array($peticion['hermanas'])) {
            $peticion['hermanas'] = false;
        }
    }

    // A dónde se prepara: un albarán nuevo, por defecto, o uno de los que se ofrecen al calcular un mes.
    $destino = 'nuevo';
    if (isset($_POST['destino']) && $_POST['destino'] !== '') {
        $destino = (string) $_POST['destino'];
    }

    if ($peticion['porEntradas'] === '1') {
        // Un albarán por cada albarán de entrada del mes: se calculan todos antes de escribir el primero.
        $respuesta = $CAplicacion->albaranesPorEntradas($peticion, new ClaseComprobacionStockConsulta());
        if ($respuesta['ok'] === true && $destino !== 'nuevo') {
            $respuesta['ok'] = false;
            $respuesta['avisos'] = array($CAplicacion->montarAdvertencia('warning', 'Por entradas no se reúne en otro albarán: cada entrada tiene el suyo. Elige «Albarán nuevo» o quita la opción.'));
        }
        if ($respuesta['ok'] === true) {
            foreach ($respuesta['datos']['porEntrada'] as $deLaEntrada) {
                $textos = $CAplicacion->textosAdmitidos($deLaEntrada['lineas']);
                if (isset($textos['error']) && $respuesta['ok'] === true) {
                    $respuesta['ok'] = false;
                    array_unshift($respuesta['avisos'], $textos['error']);
                }
            }
        }
        if ($respuesta['ok'] === true) {
            $CAlbaranes = new AlbaranesCompras($BDTpv);
            $idTemporales = array();
            foreach ($respuesta['datos']['porEntrada'] as $deLaEntrada) {
                $alta = $CAlbaranes->insertarDatosAlbaranTemporal($Usuario['id'], $Tienda['idTienda'], 'Nuevo', $deLaEntrada['fecha'], $deLaEntrada['lineas'],
                    (int) $peticion['idProveedor'], array(), $respuesta['datos']['numeroDelAlbaran']);
                if (isset($alta['id'])) {
                    $idTemporales[] = (int) $alta['id'];
                } else {
                    error_log('Aplicar relación: no se pudo preparar el albarán de la entrada ' . $deLaEntrada['numero'] . ': ' . json_encode($alta));
                }
            }
            if (count($idTemporales) === count($respuesta['datos']['porEntrada'])) {
                $respuesta['datos']['idTemporales'] = $idTemporales;
                $respuesta['datos']['idTemporal'] = $idTemporales[0];
            } else {
                $respuesta['ok'] = false;
                $respuesta['avisos'] = array($CAplicacion->montarAdvertencia('danger', 'No se pudieron preparar todos los albaranes de las entradas: revisa los temporales de compras y avisa al administrador.'));
            }
        }
    } else {
        $respuesta = $CAplicacion->calcular($peticion, new ClaseComprobacionStockConsulta());
        if ($respuesta['ok'] === true) {
            $textos = $CAplicacion->textosAdmitidos($respuesta['datos']['lineas']);
            if (isset($textos['error'])) {
                $respuesta['ok'] = false;
                array_unshift($respuesta['avisos'], $textos['error']);
            }
        }
        // El destino se vuelve a buscar aquí: tiene que seguir entre los albaranes con los que se puede reunir.
        $elegido = null;
        if ($respuesta['ok'] === true && $destino !== 'nuevo') {
            foreach ($respuesta['datos']['destinos'] as $posible) {
                if ($posible['destino'] === $destino) {
                    $elegido = $posible;
                }
            }
            if ($elegido === null) {
                $respuesta['ok'] = false;
                $respuesta['avisos'] = array($CAplicacion->montarAdvertencia('warning', 'El albarán elegido ya no se puede usar para reunir este despiece: vuelve a calcular y elige otro, o crea uno nuevo.'));
            }
        }
        if ($respuesta['ok'] === true) {
            $fecha = $respuesta['datos']['periodo']['fecha'];
            $suNumero = $respuesta['datos']['numeroDelAlbaran'];
            $lineas = $respuesta['datos']['lineas'];
            $idProveedor = (int) $peticion['idProveedor'];
            $CAlbaranes = new AlbaranesCompras($BDTpv);
            $idTemporal = 0;
            $fallo = '';

            if ($elegido === null) {
                $alta = $CAlbaranes->insertarDatosAlbaranTemporal($Usuario['id'], $Tienda['idTienda'], 'Nuevo', $fecha, $lineas, $idProveedor, array(), $suNumero);
                if (isset($alta['id'])) {
                    $idTemporal = (int) $alta['id'];
                } else {
                    $fallo = json_encode($alta);
                }
            } else {
                $partes = explode(':', $destino);
                $idDestino = (int) $partes[1];
                $existentes = array();
                $pedidos = array();
                $estado = 'Sin Guardar';
                $fechaDelAlbaran = $fecha;
                if ($partes[0] === 'temporal') {
                    $temporal = $CAlbaranes->buscarAlbaranTemporal($idDestino);
                    if (isset($temporal['Productos'])) {
                        $existentes = json_decode($temporal['Productos'], true);
                        $pedidos = json_decode($temporal['Pedidos'], true);
                        $estado = $temporal['estadoAlbPro'];
                        $fechaDelAlbaran = $temporal['Fecha'];
                        $idTemporal = $idDestino;
                    }
                } else {
                    $existentes = $CAlbaranes->ProductosAlbaranFormulario($idDestino);
                    $datos = $CAlbaranes->datosAlbaran($idDestino);
                    if (isset($datos['Fecha']) && !isset($existentes['error'])) {
                        $fechaDelAlbaran = $datos['Fecha'];
                        foreach ($existentes as $clave => $linea) {
                            $existentes[$clave]['importe'] = $linea['nunidades'] * $linea['ultimoCoste'];
                        }
                        $alta = $CAlbaranes->insertarDatosAlbaranTemporal($Usuario['id'], $Tienda['idTienda'], 'Sin Guardar', $fechaDelAlbaran, $existentes, $idProveedor, array(), $datos['Su_numero']);
                        if (isset($alta['id'])) {
                            $idTemporal = (int) $alta['id'];
                            $CAlbaranes->addNumRealTemporal($idTemporal, $idDestino);
                            $CAlbaranes->modEstadoAlbaran($idDestino, 'Sin Guardar');
                        }
                    }
                }
                if ($idTemporal > 0 && is_array($existentes)) {
                    // Las líneas del despiece van detrás de las del albarán, con la fila siguiente a la última.
                    $ultimaFila = 0;
                    foreach ($existentes as $linea) {
                        if ($linea['nfila'] > $ultimaFila) {
                            $ultimaFila = (int) $linea['nfila'];
                        }
                    }
                    foreach ($lineas as $linea) {
                        $linea['nfila'] = $ultimaFila + (int) $linea['nfila'];
                        $existentes[] = $linea;
                    }
                    if (!is_array($pedidos)) {
                        $pedidos = array();
                    }
                    $modificado = $CAlbaranes->modificarDatosAlbaranTemporal($Usuario['id'], $Tienda['idTienda'], $estado, $fechaDelAlbaran, $idTemporal, $existentes, $pedidos, $suNumero);
                    if (isset($modificado['error'])) {
                        $fallo = json_encode($modificado);
                        $idTemporal = 0;
                    }
                } elseif ($fallo === '') {
                    $fallo = 'no se pudo leer el albarán ' . $destino;
                    $idTemporal = 0;
                }
            }

            if ($idTemporal > 0) {
                $respuesta['datos']['idTemporal'] = $idTemporal;
            } else {
                error_log('Aplicar relación: no se pudo preparar el albarán: ' . $fallo);
                $respuesta['ok'] = false;
                $respuesta['avisos'] = array($CAplicacion->montarAdvertencia('danger', 'No se pudo preparar el albarán: vuelve a intentarlo y, si se repite, avisa al administrador.'));
            }
        }
    }
}
