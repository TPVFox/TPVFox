<?php

include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesModelo.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/Relaciones.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesTipos.php';
include_once $URLCom . '/clases/Proveedores.php';

// @ Objetivo
// Aplicar una relación a lo que entró de su producto en un periodo, para preparar el albarán
// que lo registra. Cada línea lleva el signo que le dan su sentido y el documento: en un
// albarán de proveedor, las entradas restan y las salidas suman; en uno de cliente, al revés.
// La forma pone su regla de cantidades y de coste; hoy se aplica la de uno a varios, el
// escandallo de un despiece. No escribe en ninguna tabla: lo calculado lo usa quien prepara
// el albarán.
class RelacionesAplicacion extends RelacionesModelo
{
    const DOCUMENTO_PROVEEDOR = 'proveedor';
    const DOCUMENTO_CLIENTE = 'cliente';
    const AJUSTE_MINIMO = 0;
    const AJUSTE_MAXIMO = 2;

    public function calcular($peticion, $consultaDeExistencias)
    {
        // @ Objetivo
        // Todo lo que se enseña antes de preparar el albarán: el periodo, la versión que regía,
        // lo que hay que despiezar según los movimientos, el coste de partida, el aviso de
        // proceso duplicado, el escandallo y las líneas del albarán.
        // @ Parametros
        //      $peticion -> (array) idProveedor, numRelacion, tipoPeriodo, periodo, version
        //                   ('periodo', la que regía en la fecha del albarán, o 'vigente'),
        //                   idProveedorTraspaso, precios (por idArticulo, como se escribieron),
        //                   ajustePeso y ajusteValor.
        //      $consultaDeExistencias -> el objeto que lee los movimientos de un producto en un
        //                   periodo (ClaseComprobacionStockConsulta de mod_reorganizacion).
        // @ Devolvemos
        //      ['ok'] (bool), ['datos'] con lo calculado hasta donde se pudo, y ['avisos'].
        $respuesta = array('ok' => false, 'datos' => array(), 'avisos' => array());
        $proveedor = null;
        $periodo = null;
        $version = null;
        $entrada = null;

        if (!ctype_digit((string) $peticion['idProveedor']) || !ctype_digit((string) $peticion['numRelacion'])
            || ($peticion['idProveedorTraspaso'] !== '' && !ctype_digit((string) $peticion['idProveedorTraspaso']))) {
            $respuesta['avisos'][] = $this->montarAdvertencia('warning', 'Falta elegir el proveedor o la relación: elígelos en la lista y vuelve a calcular.');
        } else {
            $proveedor = $this->proveedorEspecial((int) $peticion['idProveedor']);
            if (isset($proveedor['error'])) {
                $respuesta['avisos'][] = $proveedor['error'];
                $proveedor = null;
            }
        }

        if ($proveedor !== null) {
            $periodo = $this->periodo($peticion['tipoPeriodo'], $peticion['periodo']);
            if (isset($periodo['error'])) {
                $respuesta['avisos'][] = $periodo['error'];
                $periodo = null;
            } else {
                $periodo = $periodo['datos'];
                $respuesta['datos']['periodo'] = $periodo;
            }
        }

        if ($periodo !== null) {
            $cualVersion = 'periodo';
            if (isset($peticion['version'])) {
                $cualVersion = $peticion['version'];
            }
            if ($cualVersion !== 'periodo' && $cualVersion !== 'vigente') {
                $respuesta['avisos'][] = $this->montarAdvertencia('warning', 'Falta elegir qué versión del despiece se aplica: la que regía en el periodo o la vigente.');
            } else {
                // La que regía se busca en la fecha del albarán; la vigente, en la de hoy.
                $fechaDeLaVersion = $periodo['fecha'];
                if ($cualVersion === 'vigente') {
                    $fechaDeLaVersion = date('Y-m-d');
                }
                $version = $this->versionAplicable((int) $peticion['numRelacion'], $fechaDeLaVersion);
                if (isset($version['error'])) {
                    $respuesta['avisos'][] = $version['error'];
                    $version = null;
                } elseif ($cualVersion === 'vigente' && $version['datos']['estado'] !== 'Activo') {
                    $respuesta['avisos'][] = $this->montarAdvertencia('warning', 'La relación está de baja: no tiene una versión vigente que aplicar.');
                    $version = null;
                } else {
                    $version = $version['datos'];
                    $respuesta['datos']['version'] = $version;
                    $respuesta['datos']['cualVersion'] = $cualVersion;
                    $aviso = $this->avisoDeVersion((int) $peticion['numRelacion'], $version, $periodo['fecha'], $cualVersion);
                    if ($aviso !== null) {
                        $respuesta['avisos'][] = $aviso;
                    }
                    foreach ($version['lineas'] as $linea) {
                        if ($linea['sentido'] === 'entrada') {
                            $entrada = $linea;
                        }
                    }
                }
            }
        }

        $cantidad = null;
        if ($version !== null) {
            $traspaso = 0;
            if ($peticion['idProveedorTraspaso'] !== '') {
                $traspaso = (int) $peticion['idProveedorTraspaso'];
            }
            $movimientos = $this->cantidadADespiezar(
                $consultaDeExistencias->lineasDeAlbaranDeProveedor($entrada['idArticulo'], $periodo['desde'], $periodo['hasta'], $traspaso),
                $consultaDeExistencias->lineasDeTicket($entrada['idArticulo'], $periodo['desde'], $periodo['hasta']),
                $consultaDeExistencias->lineasDeAlbaranDeCliente($entrada['idArticulo'], $periodo['desde'], $periodo['hasta'])
            );
            $respuesta['datos']['cantidadADespiezar'] = $movimientos['cantidad'];
            $respuesta['datos']['primeraEntrada'] = $movimientos['primeraEntrada'];
            if ($movimientos['cantidad'] <= 0) {
                $respuesta['avisos'][] = $this->montarAdvertencia('warning', 'No hay nada que despiezar de «' . $entrada['articulo'] . '» del '
                    . $this->fechaLegible($periodo['desde']) . ' al ' . $this->fechaLegible($periodo['hasta'])
                    . ': no entró nada o se vendió todo entero. Elige otro periodo.');
            } else {
                $cantidad = $movimientos['cantidad'];
            }
        }

        $costeDePartida = null;
        if ($cantidad !== null) {
            $coste = $this->costeDePartida($entrada['idArticulo']);
            if (isset($coste['error'])) {
                $respuesta['avisos'][] = $coste['error'];
            } else {
                $costeDePartida = $coste['datos'];
                $respuesta['datos']['costeDePartida'] = $costeDePartida;
            }
        }

        $sinDuplicado = false;
        if ($costeDePartida !== null) {
            $aviso = $this->avisoDeDuplicado(array($entrada['idArticulo']), (int) $peticion['idProveedor'], $periodo['desde'], $periodo['hasta']);
            if (isset($aviso['error'])) {
                $respuesta['avisos'][] = $aviso['error'];
            } else {
                $respuesta['datos']['aviso'] = $aviso['datos'];
                $respuesta['avisos'][] = $this->advertenciaDeDuplicado($aviso['datos'], $entrada['articulo'], $periodo['desde']);
                if ($aviso['datos']['color'] !== 'rojo') {
                    $sinDuplicado = true;
                }
            }
        }

        if ($sinDuplicado) {
            $salidas = array();
            foreach ($version['lineas'] as $linea) {
                if ($linea['sentido'] === 'salida') {
                    $salidas[] = (int) $linea['idArticulo'];
                }
            }
            $medias = $this->preciosDeMercado($salidas);
            $precios = array();
            if (is_array($peticion['precios'])) {
                $precios = $peticion['precios'];
            }
            $escandallo = $medias;
            if (!isset($medias['error'])) {
                $escandallo = $this->escandallo($version, $cantidad, $costeDePartida['coste'], $precios, $medias['datos'], $peticion['ajustePeso'], $peticion['ajusteValor']);
            }
            if (isset($escandallo['error'])) {
                $respuesta['avisos'][] = $escandallo['error'];
            } else {
                $articulos = $this->datosDeLineas(array_merge(array((int) $entrada['idArticulo']), $salidas));
                if (isset($articulos['error'])) {
                    $respuesta['avisos'][] = $articulos['error'];
                } else {
                    $etiqueta = ' · REL ' . $version['numRelacion'] . ' v' . $version['version'];
                    $lineas = $this->lineasDelAlbaran($escandallo['datos'], $articulos['datos'], self::DOCUMENTO_PROVEEDOR, $etiqueta);
                    $respuesta['datos']['escandallo'] = $escandallo['datos'];
                    $respuesta['datos']['lineas'] = $lineas;
                    $respuesta['datos']['proveedor'] = $proveedor['datos'];
                    foreach ($escandallo['avisos'] as $avisoDelEscandallo) {
                        $respuesta['avisos'][] = $avisoDelEscandallo;
                    }
                    $conOtroIva = $this->ivaDistinto($lineas);
                    if (count($conOtroIva) > 0) {
                        $respuesta['avisos'][] = $this->montarAdvertencia('warning', 'Otro IVA que el de «' . $entrada['articulo'] . '» en: '
                            . implode(', ', $conOtroIva) . '. El albarán queda a 0,00 € sin IVA, pero su IVA no se compensa: revísalo antes de guardarlo.');
                    }
                    $respuesta['ok'] = true;
                }
            }
        }

        return $respuesta;
    }

    public function esAplicable($forma, $llevaCantidad, $lineas)
    {
        // @ Objetivo
        // Decir si una relación se puede aplicar: hoy, un despiece —forma de uno a varios— con
        // cantidad y todas sus líneas en la misma unidad.
        // @ Devolvemos
        //      ['aplicable'] (bool) y ['motivo'], que dice por qué no.
        $respuesta = array('aplicable' => true, 'motivo' => '');
        if ($forma !== '1:N') {
            $respuesta = array('aplicable' => false, 'motivo' => 'Por ahora solo se aplican despieces: relaciones de un producto que entra y varios que salen.');
        } elseif ((int) $llevaCantidad !== 1) {
            $respuesta = array('aplicable' => false, 'motivo' => 'Sus líneas no llevan cantidad, y sin cantidades no hay escandallo.');
        } elseif ($this->unidadComun($lineas) === '') {
            $respuesta = array('aplicable' => false, 'motivo' => 'Tiene líneas en kg y en ud: solo se aplica si todas están en la misma unidad.');
        }

        return $respuesta;
    }

    public function signo($sentido, $documento)
    {
        // @ Objetivo
        // El signo de una línea en el albarán, según su sentido y el documento. Es la única
        // regla de signo: no depende de la forma ni del nombre del tipo.
        // @ Devolvemos
        //      (int) -1 o 1; 0 si el sentido o el documento no se conocen.
        $signo = 0;
        if ($documento === self::DOCUMENTO_PROVEEDOR) {
            if ($sentido === 'entrada') {
                $signo = -1;
            } elseif ($sentido === 'salida') {
                $signo = 1;
            }
        } elseif ($documento === self::DOCUMENTO_CLIENTE) {
            if ($sentido === 'entrada') {
                $signo = 1;
            } elseif ($sentido === 'salida') {
                $signo = -1;
            }
        }

        return $signo;
    }

    public function periodo($tipo, $valor)
    {
        // @ Objetivo
        // Los días que abarca un periodo y la fecha de su albarán: un mes va del día 1 a su
        // último día, que es la fecha del albarán; un lote es un día.
        // @ Parametros
        //      $tipo -> 'mes' o 'lote'.
        //      $valor -> 'AAAA-MM' para un mes, 'AAAA-MM-DD' para un lote.
        // @ Devolvemos
        //      ['datos'] con desde, hasta y fecha, en 'AAAA-MM-DD'; o ['error'].
        $respuesta = array('error' => $this->montarAdvertencia('warning', 'El periodo no es válido: elige un mes, o el día del lote.'));
        $partes = array();
        if ($tipo === 'mes' && preg_match('/^(\d{4})-(\d{2})$/', (string) $valor, $partes) === 1 && (int) $partes[2] >= 1 && (int) $partes[2] <= 12) {
            $desde = $valor . '-01';
            $hasta = date('Y-m-t', strtotime($desde));
            $respuesta = array('datos' => array('desde' => $desde, 'hasta' => $hasta, 'fecha' => $hasta));
        } elseif ($tipo === 'lote' && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) $valor, $partes) === 1
            && checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1])) {
            $respuesta = array('datos' => array('desde' => $valor, 'hasta' => $valor, 'fecha' => $valor));
        }

        return $respuesta;
    }

    public function periodosConEntradas($numRelacion, $tipo, $consultaDeExistencias)
    {
        // @ Objetivo
        // Los meses o los días en que entró el producto de la relación, para elegir el periodo.
        // Lee las entradas con la misma consulta que el cálculo, sin proveedor de traspaso: la
        // apertura del ejercicio cuenta como entrada, porque aún no se sabe cuál se elegirá.
        // @ Parametros
        //      $numRelacion -> número de la relación; se toma la versión que rige hoy.
        //      $tipo -> 'mes' o 'lote'.
        //      $consultaDeExistencias -> ClaseComprobacionStockConsulta de mod_reorganizacion.
        // @ Devolvemos
        //      ['datos'] con valor ('AAAA-MM' o 'AAAA-MM-DD'), cantidad y unidad, del más
        //      reciente al más antiguo; o ['error'].
        $respuesta = array('error' => $this->montarAdvertencia('warning', 'Falta elegir la relación o el tipo de periodo: elígelos en la lista.'));
        if (ctype_digit((string) $numRelacion) && ($tipo === 'mes' || $tipo === 'lote')) {
            $version = $this->versionAplicable((int) $numRelacion, date('Y-m-d'));
            if (isset($version['error'])) {
                $respuesta = $version;
            } else {
                $entrada = null;
                foreach ($version['datos']['lineas'] as $linea) {
                    if ($linea['sentido'] === 'entrada') {
                        $entrada = $linea;
                    }
                }
                $largo = 7;
                if ($tipo === 'lote') {
                    $largo = 10;
                }
                $cantidades = array();
                foreach ($consultaDeExistencias->lineasDeAlbaranDeProveedor($entrada['idArticulo'], '1900-01-01', '2999-12-31', 0) as $linea) {
                    if ($linea['nunidades'] > 0) {
                        $valor = substr($linea['fecha'], 0, $largo);
                        if (!isset($cantidades[$valor])) {
                            $cantidades[$valor] = 0;
                        }
                        $cantidades[$valor] += $linea['nunidades'];
                    }
                }
                krsort($cantidades);
                $periodos = array();
                foreach ($cantidades as $valor => $cantidad) {
                    $periodos[] = array('valor' => (string) $valor, 'cantidad' => round($cantidad, 3), 'unidad' => $entrada['unidad']);
                }
                $respuesta = array('datos' => $periodos);
            }
        }

        return $respuesta;
    }

    public function cantidadADespiezar($lineasDeProveedor, $lineasDeTicket, $lineasDeCliente)
    {
        // @ Objetivo
        // Lo que hay que despiezar en el periodo: lo que entra desde la primera entrada, menos
        // lo que sale desde ese día. Las líneas son las que da la comprobación de existencias,
        // con las unidades con su signo: una devolución al proveedor resta.
        // @ Devolvemos
        //      ['cantidad'] (float) y ['primeraEntrada'], la fecha, o null si no hubo entradas.
        $primera = null;
        foreach ($lineasDeProveedor as $linea) {
            if ($linea['nunidades'] > 0 && ($primera === null || $linea['fecha'] < $primera)) {
                $primera = $linea['fecha'];
            }
        }

        $cantidad = 0.0;
        if ($primera !== null) {
            foreach ($lineasDeProveedor as $linea) {
                if ($linea['fecha'] >= $primera) {
                    $cantidad += $linea['nunidades'];
                }
            }
            foreach (array($lineasDeTicket, $lineasDeCliente) as $salidas) {
                foreach ($salidas as $linea) {
                    if ($linea['fecha'] >= $primera) {
                        $cantidad -= $linea['nunidades'];
                    }
                }
            }
        }

        return array('cantidad' => round($cantidad, 3), 'primeraEntrada' => $primera);
    }

    public function precioEscrito($texto)
    {
        // @ Objetivo
        // Leer un precio de mercado escrito a mano, con coma o con punto decimal.
        // @ Devolvemos
        //      ['datos'] con el precio, o null si no se escribió; o ['error'].
        $limpio = str_replace(',', '.', trim((string) $texto));
        $respuesta = array('datos' => null);
        if ($limpio !== '') {
            if (preg_match('/^[0-9]+(\.[0-9]+)?$/', $limpio) === 1) {
                $respuesta = array('datos' => (float) $limpio);
            } else {
                $respuesta = array('error' => $this->montarAdvertencia('warning', 'El precio de mercado «' . $texto
                    . '» no es un número: escríbelo con cifras, 0 o mayor, con coma decimal.'));
            }
        }

        return $respuesta;
    }

    public function escandallo($version, $cantidad, $costeDePartida, $escritos, $medias, $ajustePeso, $ajusteValor)
    {
        // @ Objetivo
        // La regla de la forma 1:N: cuánto sale de cada pieza y a qué coste. Se reparte el coste
        // de lo despiezado mezclando el reparto por peso y el reparto por valor de mercado con
        // el ajuste; la diferencia entre lo que entra y lo que sale queda como merma del
        // producto despiezado y no se carga a las piezas. No consulta la base.
        // @ Parametros
        //      $version -> la versión que regía, con sus líneas.
        //      $cantidad -> lo que hay que despiezar.
        //      $costeDePartida -> el coste por unidad del producto despiezado.
        //      $escritos -> precios de mercado escritos a mano, por idArticulo.
        //      $medias -> la media de los proveedores de cada pieza, por idArticulo, o null.
        //      $ajustePeso, $ajusteValor -> entre 0 y 2, y suman 2. Con 2 y 0 todo se reparte por
        //                   peso; con 0 y 2, todo por valor de mercado.
        // @ Devolvemos
        //      ['datos'] con la entrada, las piezas, lo despiezado, la merma y el coste
        //      repartido, y ['avisos']; o ['error'].
        $respuesta = array();
        $entrada = null;
        $salidas = array();
        foreach ($version['lineas'] as $linea) {
            if (!isset($linea['estado']) || $linea['estado'] === 'Activo') {
                if ($linea['sentido'] === 'entrada') {
                    $entrada = $linea;
                } else {
                    $salidas[] = $linea;
                }
            }
        }

        $sumaDeSalidas = 0.0;
        foreach ($salidas as $salida) {
            $sumaDeSalidas += (float) $salida['cantidad'];
        }

        if (!is_numeric($ajustePeso) || !is_numeric($ajusteValor)
            || $ajustePeso < self::AJUSTE_MINIMO || $ajustePeso > self::AJUSTE_MAXIMO
            || $ajusteValor < self::AJUSTE_MINIMO || $ajusteValor > self::AJUSTE_MAXIMO
            || abs($ajustePeso + $ajusteValor - 2) > 0.000001) {
            $respuesta['error'] = $this->montarAdvertencia('warning', 'El ajuste del reparto va de 0 a 2 en cada parte, y las dos suman 2: corrige el ajuste.');
        } elseif ($entrada === null || count($salidas) === 0) {
            $respuesta['error'] = $this->montarAdvertencia('warning', 'La relación no tiene un producto que entra y piezas que salen: revísala en «Productos relacionados».');
        } elseif ($this->unidadComun($version['lineas']) === '') {
            $respuesta['error'] = $this->montarAdvertencia('warning', 'Tiene líneas en kg y en ud: solo se aplica si todas están en la misma unidad.');
        } elseif ($sumaDeSalidas > (float) $entrada['cantidad'] + 0.000001) {
            $respuesta['error'] = $this->montarAdvertencia('warning', 'La salida pesa más que la entrada (' . $this->numeroLegible($sumaDeSalidas)
                . ' frente a ' . $this->numeroLegible((float) $entrada['cantidad']) . '): revisa las cantidades del despiece en «Productos relacionados».');
        }

        $piezas = array();
        if (!isset($respuesta['error'])) {
            foreach ($salidas as $salida) {
                $sale = $cantidad * (float) $salida['cantidad'] / (float) $entrada['cantidad'];
                if ($salida['unidad'] === 'ud') {
                    $sale = floor($sale + 0.000001);
                } else {
                    $sale = round($sale, 3);
                }
                $idArticulo = (int) $salida['idArticulo'];
                $precio = $this->precioEscrito('');
                if (isset($escritos[$idArticulo])) {
                    $precio = $this->precioEscrito($escritos[$idArticulo]);
                }
                if (isset($precio['error'])) {
                    $respuesta['error'] = $precio['error'];
                } elseif ($precio['datos'] !== null) {
                    $piezas[] = array('idArticulo' => $idArticulo, 'articulo' => $salida['articulo'], 'cantidad' => (float) $sale, 'unidad' => $salida['unidad'], 'precio' => $precio['datos'], 'origenPrecio' => 'escrito');
                } elseif (isset($medias[$idArticulo]) && $medias[$idArticulo] !== null) {
                    $piezas[] = array('idArticulo' => $idArticulo, 'articulo' => $salida['articulo'], 'cantidad' => (float) $sale, 'unidad' => $salida['unidad'], 'precio' => (float) $medias[$idArticulo], 'origenPrecio' => 'media');
                } elseif ($ajusteValor == 0) {
                    // Todo por peso: el precio de mercado no se usa y no hace falta escribirlo.
                    $piezas[] = array('idArticulo' => $idArticulo, 'articulo' => $salida['articulo'], 'cantidad' => (float) $sale, 'unidad' => $salida['unidad'], 'precio' => 0.0, 'origenPrecio' => 'sinUso');
                } elseif (!isset($respuesta['error'])) {
                    $respuesta['error'] = $this->montarAdvertencia('warning', 'Falta el precio de mercado de «' . $salida['articulo']
                        . '»: no tiene proveedores con coste. Escríbelo en su línea y vuelve a calcular.');
                }
            }
        }

        if (!isset($respuesta['error'])) {
            $despiezado = 0.0;
            $valor = 0.0;
            foreach ($piezas as $pieza) {
                $despiezado += $pieza['cantidad'];
                $valor += $pieza['cantidad'] * $pieza['precio'];
            }
            $despiezado = round($despiezado, 3);
            if ($despiezado <= 0) {
                $respuesta['error'] = $this->montarAdvertencia('warning', 'No sale ninguna pieza entera de lo que hay que despiezar: elige un periodo más largo.');
            }
        }

        if (!isset($respuesta['error'])) {
            $costeUnidadDePartida = round((float) $costeDePartida, 4);
            $coste = $despiezado * $costeUnidadDePartida;
            // Con todo por peso el valor no cuenta; si no hay ningún precio mayor que 0, tampoco.
            $soloPeso = $ajusteValor > 0 && $valor <= 0;
            $porPeso = $ajusteValor == 0 || $soloPeso;

            // Lo que le toca a cada pieza, antes de redondear.
            $teorico = array();
            foreach ($piezas as $posicion => $pieza) {
                $teorico[$posicion] = 0.0;
                if ($pieza['cantidad'] > 0) {
                    if ($porPeso) {
                        $teorico[$posicion] = $coste * $pieza['cantidad'] / $despiezado;
                    } else {
                        $teorico[$posicion] = $coste * ($ajustePeso * $pieza['cantidad'] / $despiezado + $ajusteValor * $pieza['cantidad'] * $pieza['precio'] / $valor) / 2;
                    }
                }
            }
            // La pieza con menos cantidad, entre las que reciben coste, cierra el total: el
            // redondeo de su coste es el que menos mueve el importe, y así el albarán queda en
            // 0,00 € sin IVA. Una pieza que no recibe coste sigue en 0.
            $cierra = null;
            foreach ($piezas as $posicion => $pieza) {
                if ($teorico[$posicion] > 0 && ($cierra === null || $pieza['cantidad'] < $piezas[$cierra]['cantidad'])) {
                    $cierra = $posicion;
                }
            }
            $repartido = 0.0;
            foreach ($piezas as $posicion => $pieza) {
                $piezas[$posicion]['costeUnidad'] = 0.0;
                if ($teorico[$posicion] > 0 && $posicion !== $cierra) {
                    $piezas[$posicion]['costeUnidad'] = round($teorico[$posicion] / $pieza['cantidad'], 4);
                    $repartido += $pieza['cantidad'] * $piezas[$posicion]['costeUnidad'];
                }
            }
            $piezas[$cierra]['costeUnidad'] = round(($coste - $repartido) / $piezas[$cierra]['cantidad'], 4);
            foreach ($piezas as $posicion => $pieza) {
                $piezas[$posicion]['coste'] = round($pieza['cantidad'] * $pieza['costeUnidad'], 4);
            }

            $avisos = array();
            $sinCoste = array();
            foreach ($piezas as $pieza) {
                if ($pieza['costeUnidad'] == 0 && $pieza['cantidad'] > 0) {
                    $sinCoste[] = '«' . $pieza['articulo'] . '»';
                }
            }
            if (count($sinCoste) > 0) {
                $avisos[] = $this->montarAdvertencia('info', 'Con este ajuste no reciben coste y salen a 0 €: ' . implode(', ', $sinCoste)
                    . '. Su precio de mercado es 0 y todo el coste se reparte por valor.');
            }
            if ($soloPeso) {
                $avisos[] = $this->montarAdvertencia('info', 'Ninguna pieza tiene precio de mercado mayor que 0: el coste se reparte solo por peso.');
            }
            $respuesta = array(
                'datos' => array(
                    'entrada' => array('idArticulo' => (int) $entrada['idArticulo'], 'articulo' => $entrada['articulo'], 'cantidad' => $despiezado, 'unidad' => $entrada['unidad'], 'costeUnidad' => $costeUnidadDePartida),
                    'piezas' => $piezas,
                    'cantidadADespiezar' => round((float) $cantidad, 3),
                    'despiezado' => $despiezado,
                    'merma' => round($cantidad - $despiezado, 3),
                    'coste' => round($coste, 4),
                    'soloPeso' => $soloPeso,
                ),
                'avisos' => $avisos,
            );
        }

        return $respuesta;
    }

    public function lineasDelAlbaran($escandallo, $articulos, $documento, $etiqueta = '')
    {
        // @ Objetivo
        // Las líneas del albarán con la forma de las de compras: la entrada por lo despiezado a
        // su coste de partida, y cada pieza a su coste, con el signo de su sentido en el
        // documento. Sin coste anterior: el guardado de compras no reescribe así el coste de
        // ningún proveedor.
        // @ Parametros
        //      $escandallo -> los datos de escandallo().
        //      $articulos -> nombre, referencia, código de barras e IVA, por idArticulo.
        //      $documento -> DOCUMENTO_PROVEEDOR o DOCUMENTO_CLIENTE.
        //      $etiqueta -> texto que se añade al detalle de cada línea, con la relación y la
        //                   versión aplicadas; el nombre se recorta para que todo quepa en 100.
        // @ Devolvemos
        //      (array) las líneas, numeradas desde 1.
        $lineas = array();
        $entrada = $escandallo['entrada'];
        $lineas[] = $this->lineaDeCompras($entrada['idArticulo'], $this->signo('entrada', $documento) * $entrada['cantidad'], $entrada['costeUnidad'], $articulos, 1, $etiqueta);
        foreach ($escandallo['piezas'] as $pieza) {
            $lineas[] = $this->lineaDeCompras($pieza['idArticulo'], $this->signo('salida', $documento) * $pieza['cantidad'], $pieza['costeUnidad'], $articulos, count($lineas) + 1, $etiqueta);
        }

        return $lineas;
    }

    public function textosAdmitidos($lineas)
    {
        // @ Objetivo
        // Comprobar que compras puede guardar los textos de las líneas: su guardado compone las
        // sentencias con el nombre, la referencia y el código de barras sin escaparlos, y con
        // comillas o barra invertida deja el albarán sin líneas y las existencias movidas.
        // @ Devolvemos
        //      ['datos'] true; o ['error'] con el primer artículo que no se puede guardar.
        $respuesta = array('datos' => true);
        foreach ($lineas as $linea) {
            if (!isset($respuesta['error'])) {
                foreach (array($linea['cdetalle'], $linea['cref'], $linea['ccodbar']) as $texto) {
                    if (strpbrk((string) $texto, "\"'\\") !== false && !isset($respuesta['error'])) {
                        $respuesta = array('error' => $this->montarAdvertencia('warning', 'El artículo «' . $linea['cdetalle']
                            . '» lleva comillas o barra invertida en su nombre, su referencia o su código de barras, y compras no puede guardar así el albarán: quítalas en la ficha del producto y vuelve a calcular.'));
                    }
                }
            }
        }

        return $respuesta;
    }

    public function ivaDistinto($lineas)
    {
        // @ Objetivo
        // Los artículos que tienen otro IVA que el de la primera línea, la del producto
        // despiezado: con ellos, el albarán queda a 0 sin IVA pero con IVA.
        // @ Devolvemos
        //      (array) sus nombres.
        $nombres = array();
        if (count($lineas) > 0) {
            $iva = (float) $lineas[0]['iva'];
            foreach ($lineas as $linea) {
                if ((float) $linea['iva'] !== $iva) {
                    $nombres[] = $linea['cdetalle'];
                }
            }
        }

        return $nombres;
    }

    public function relacionesAplicables()
    {
        // @ Objetivo
        // Las relaciones que rigen y se pueden aplicar, para elegirlas en la pantalla.
        // @ Devolvemos
        //      ['datos'] con numRelacion, nombre, tipo y el producto que entra; o ['error'].
        $CRelaciones = new Relaciones();
        $CTipos = new RelacionesTipos();
        $vigentes = $CRelaciones->vigentes();
        $tipos = $CTipos->listarTipos(false);
        $respuesta = array('datos' => array());
        if (isset($vigentes['error'])) {
            $respuesta = $vigentes;
        } elseif (isset($tipos['error'])) {
            $respuesta = $tipos;
        } else {
            $llevaCantidad = array();
            foreach ($tipos['datos'] as $tipo) {
                $llevaCantidad[(int) $tipo['id']] = (int) $tipo['llevaCantidad'];
            }
            foreach ($vigentes['datos'] as $relacion) {
                $idTipo = (int) $relacion['idTipo'];
                if (isset($llevaCantidad[$idTipo]) && $this->esAplicable($relacion['forma'], $llevaCantidad[$idTipo], $relacion['lineas'])['aplicable']) {
                    $entra = '';
                    foreach ($relacion['lineas'] as $linea) {
                        if ($linea['sentido'] === 'entrada') {
                            $entra = $linea['articulo'];
                        }
                    }
                    $respuesta['datos'][] = array('numRelacion' => (int) $relacion['numRelacion'], 'nombre' => $relacion['nombre'], 'tipo' => $relacion['tipo'], 'entrada' => $entra);
                }
            }
        }

        return $respuesta;
    }

    public function costeDePartida($idArticulo)
    {
        // @ Objetivo
        // El coste del producto despiezado: el de su proveedor principal, el que enseña su
        // ficha, con la fecha en que se actualizó.
        // @ Devolvemos
        //      ['datos'] con coste y fecha; o ['error'] que dice qué producto y dónde corregirlo.
        $sql = 'SELECT a.articulo_name, ap.coste, ap.fechaActualizacion FROM articulos a'
            . ' LEFT JOIN articulosProveedores ap ON ap.idArticulo = a.idArticulo AND ap.idProveedor = a.idProveedor'
            . ' WHERE a.idArticulo = ?';
        $respuesta = $this->consultaPreparada($sql, array((int) $idArticulo));
        if (!isset($respuesta['error'])) {
            if (count($respuesta['datos']) === 0) {
                $respuesta = array('error' => $this->montarAdvertencia('warning', 'El producto que se despieza ya no existe en el catálogo.'));
            } elseif ($respuesta['datos'][0]['coste'] === null) {
                $respuesta = array('error' => $this->montarAdvertencia('warning', 'El producto «' . $respuesta['datos'][0]['articulo_name']
                    . '» no tiene coste de su proveedor principal: escríbelo en su ficha y vuelve a calcular.'));
            } else {
                $respuesta = array('datos' => array('coste' => (float) $respuesta['datos'][0]['coste'], 'fecha' => (string) $respuesta['datos'][0]['fechaActualizacion']));
            }
        }

        return $respuesta;
    }

    public function preciosDeMercado($idArticulos)
    {
        // @ Objetivo
        // El precio de mercado de cada pieza: la media del coste de sus proveedores, sin contar
        // los «Especial» —el del despiece deja allí sus filas al guardar— ni las filas
        // inactivas.
        // @ Devolvemos
        //      ['datos'] con la media por idArticulo, o null si no tiene; o ['error'].
        $ids = array();
        foreach ($idArticulos as $idArticulo) {
            $ids[] = (int) $idArticulo;
        }
        $respuesta = array('datos' => array());
        if (count($ids) > 0) {
            $sql = 'SELECT ap.idArticulo, AVG(ap.coste) AS media FROM articulosProveedores ap'
                . ' JOIN proveedores p ON p.idProveedor = ap.idProveedor'
                . ' WHERE ap.idArticulo IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')'
                . " AND LOWER(ap.estado) = 'activo' AND p.estado <> 'Especial'"
                . ' GROUP BY ap.idArticulo';
            $respuesta = $this->consultaPreparada($sql, $ids);
            if (!isset($respuesta['error'])) {
                $medias = array();
                foreach ($ids as $id) {
                    $medias[$id] = null;
                }
                foreach ($respuesta['datos'] as $fila) {
                    $medias[(int) $fila['idArticulo']] = (float) $fila['media'];
                }
                $respuesta = array('datos' => $medias);
            }
        }

        return $respuesta;
    }

    public function avisoDeDuplicado($idArticulos, $idProveedor, $desde, $hasta)
    {
        // @ Objetivo
        // Avisar si el periodo puede estar ya registrado: los albaranes y los temporales de
        // compras de proveedores «Especial» con alguno de los productos de entrada, desde el
        // primer día del periodo. Del proveedor elegido y dentro del periodo, rojo; los demás,
        // amarillo. Sin ninguno, verde.
        // @ Devolvemos
        //      ['datos'] con color y documentos —albarán o temporal, número, proveedor, fecha y
        //      color—; o ['error'].
        $ids = array();
        foreach ($idArticulos as $idArticulo) {
            $ids[] = (int) $idArticulo;
        }
        $documentos = array();
        $sql = 'SELECT c.id, c.Numalbpro, DATE(c.Fecha) AS fecha, c.idProveedor, p.nombrecomercial FROM albprot c'
            . ' JOIN proveedores p ON p.idProveedor = c.idProveedor'
            . " WHERE p.estado = 'Especial' AND DATE(c.Fecha) >= ?"
            . " AND c.id IN (SELECT l.idalbpro FROM albprolinea l WHERE l.estadoLinea = 'Activo' AND l.idArticulo IN ("
            . implode(', ', array_fill(0, count($ids), '?')) . '))'
            . ' ORDER BY c.Fecha, c.id';
        $respuesta = $this->consultaPreparada($sql, array_merge(array($desde), $ids));
        if (!isset($respuesta['error'])) {
            foreach ($respuesta['datos'] as $fila) {
                $documentos[] = $this->documentoDelAviso('albarán', (int) $fila['Numalbpro'], $fila, $idProveedor, $hasta);
            }
            $sql = 'SELECT t.id, DATE(t.Fecha) AS fecha, t.idProveedor, p.nombrecomercial, t.Productos FROM albproltemporales t'
                . ' JOIN proveedores p ON p.idProveedor = t.idProveedor'
                . " WHERE p.estado = 'Especial' AND DATE(t.Fecha) >= ?"
                . ' ORDER BY t.Fecha, t.id';
            $respuesta = $this->consultaPreparada($sql, array($desde));
        }
        if (!isset($respuesta['error'])) {
            foreach ($respuesta['datos'] as $fila) {
                $productos = json_decode((string) $fila['Productos'], true);
                $loLleva = false;
                if (is_array($productos)) {
                    foreach ($productos as $producto) {
                        if (isset($producto['idArticulo']) && in_array((int) $producto['idArticulo'], $ids, true)
                            && (!isset($producto['estado']) || strtolower($producto['estado']) === 'activo')) {
                            $loLleva = true;
                        }
                    }
                }
                if ($loLleva) {
                    $documentos[] = $this->documentoDelAviso('temporal', (int) $fila['id'], $fila, $idProveedor, $hasta);
                }
            }
            $color = 'verde';
            foreach ($documentos as $documento) {
                if ($documento['color'] === 'rojo') {
                    $color = 'rojo';
                } elseif ($color === 'verde') {
                    $color = 'amarillo';
                }
            }
            $respuesta = array('datos' => array('color' => $color, 'documentos' => $documentos));
        }

        return $respuesta;
    }

    public function datosDeLineas($idArticulos)
    {
        // @ Objetivo
        // Lo que lleva cada línea del albarán de cada artículo: nombre, referencia de la tienda
        // principal, código de barras e IVA.
        // @ Devolvemos
        //      ['datos'] por idArticulo; o ['error'].
        $CRelaciones = new Relaciones();
        $respuesta = $CRelaciones->datosDeArticulos($idArticulos);
        if (!isset($respuesta['error']) && count($respuesta['datos']) > 0) {
            $articulos = $respuesta['datos'];
            $ids = array_keys($articulos);
            $sql = 'SELECT idArticulo, iva FROM articulos WHERE idArticulo IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')';
            $respuesta = $this->consultaPreparada($sql, $ids);
            if (!isset($respuesta['error'])) {
                foreach ($respuesta['datos'] as $fila) {
                    $articulos[$fila['idArticulo']]['iva'] = (float) $fila['iva'];
                }
                $respuesta = array('datos' => $articulos);
            }
        }

        return $respuesta;
    }

    private function proveedorEspecial($idProveedor)
    {
        // @ Objetivo
        // El proveedor elegido, si es de estado «Especial».
        // @ Devolvemos
        //      ['datos'] con su id y su nombre; o ['error'].
        $CProveedores = new Proveedores();
        $respuesta = array('error' => $this->montarAdvertencia('warning', 'El proveedor elegido no es «Especial»: elige uno de la lista.'));
        foreach ($CProveedores->todosProveedores() as $proveedor) {
            if (is_array($proveedor) && (int) $proveedor['idProveedor'] === $idProveedor && $proveedor['estado'] === 'Especial') {
                $respuesta = array('datos' => array('idProveedor' => $idProveedor, 'nombre' => $proveedor['nombrecomercial']));
            }
        }

        return $respuesta;
    }

    private function avisoDeVersion($numRelacion, $version, $fechaDelPeriodo, $cualVersion)
    {
        // @ Objetivo
        // Si se aplica la versión vigente, dice cuál es y qué regía en el periodo, para que quien
        // aplica sepa con qué proporciones se prepara el albarán.
        // @ Devolvemos
        //      un aviso, o null si se aplica la que regía o la vigente es la que regía.
        $aviso = null;
        if ($cualVersion === 'vigente') {
            $CRelaciones = new Relaciones();
            $estado = $CRelaciones->estadoEnFecha($numRelacion, $fechaDelPeriodo);
            $vigente = 'la versión ' . $version['version'] . ', vigente desde el ' . $this->fechaLegible($version['fechaCreacion']);
            if (isset($estado['error'])) {
                $aviso = $this->montarAdvertencia('info', 'Se aplica ' . $vigente . '.');
            } elseif ($estado['datos']['situacion'] !== 'regia') {
                $aviso = $this->montarAdvertencia('info', 'Se aplica ' . $vigente . ': la relación no existía el ' . $this->fechaLegible($fechaDelPeriodo) . '.');
            } elseif ((int) $estado['datos']['version']['id'] !== (int) $version['id']) {
                $aviso = $this->montarAdvertencia('info', 'Se aplica ' . $vigente . ', no la versión ' . $estado['datos']['version']['version']
                    . ' que regía el ' . $this->fechaLegible($fechaDelPeriodo) . '.');
            }
        }

        return $aviso;
    }

    private function versionAplicable($numRelacion, $fecha)
    {
        // @ Objetivo
        // La versión de la relación que regía en la fecha del albarán, si se puede aplicar.
        // @ Devolvemos
        //      ['datos'] con la versión y sus líneas; o ['error'].
        $CRelaciones = new Relaciones();
        $CTipos = new RelacionesTipos();
        $estado = $CRelaciones->estadoEnFecha($numRelacion, $fecha);
        $respuesta = $estado;
        if (!isset($estado['error'])) {
            if ($estado['datos']['situacion'] !== 'regia') {
                $respuesta = array('error' => $this->montarAdvertencia('warning', 'La relación no regía el ' . $this->fechaLegible($fecha)
                    . ': elige un periodo en que ya existiera, o aplica la versión vigente.'));
            } else {
                $version = $estado['datos']['version'];
                $tipo = $CTipos->leerTipo($version['idTipo']);
                if (isset($tipo['error'])) {
                    $respuesta = $tipo;
                } else {
                    $aplicable = $this->esAplicable($version['forma'], $tipo['datos']['llevaCantidad'], $version['lineas']);
                    $respuesta = array('datos' => $version);
                    if (!$aplicable['aplicable']) {
                        $respuesta = array('error' => $this->montarAdvertencia('warning', 'La relación no se puede aplicar. ' . $aplicable['motivo']));
                    }
                }
            }
        }

        return $respuesta;
    }

    private function advertenciaDeDuplicado($aviso, $producto, $desde)
    {
        // @ Objetivo
        // El aviso de proceso duplicado, con el color de su gravedad y lo que hay que mirar.
        $partes = array();
        foreach ($aviso['documentos'] as $documento) {
            $partes[] = $documento['documento'] . ' ' . $documento['numero'] . ' de ' . $documento['proveedor']
                . ', del ' . $this->fechaLegible($documento['fecha']);
        }
        if ($aviso['color'] === 'rojo') {
            $advertencia = $this->montarAdvertencia('danger', 'El periodo de «' . $producto . '» ya puede estar registrado con este proveedor: '
                . implode('; ', $partes) . '. Consúltalo en compras antes de volver a aplicarlo.');
        } elseif ($aviso['color'] === 'amarillo') {
            $advertencia = $this->montarAdvertencia('warning', 'Hay albaranes de proveedores especiales con «' . $producto . '» en el periodo o después: '
                . implode('; ', $partes) . '. Compruébalos antes de preparar.');
        } else {
            $advertencia = $this->montarAdvertencia('success', 'Ningún albarán de un proveedor especial lleva «' . $producto . '» desde el '
                . $this->fechaLegible($desde) . ': se puede preparar.');
        }

        return $advertencia;
    }

    private function documentoDelAviso($documento, $numero, $fila, $idProveedor, $hasta)
    {
        // @ Objetivo
        // Un albarán o un temporal del aviso, con su color: rojo si es del proveedor elegido y
        // cae en el periodo.
        $color = 'amarillo';
        if ((int) $fila['idProveedor'] === (int) $idProveedor && $fila['fecha'] <= $hasta) {
            $color = 'rojo';
        }

        return array('documento' => $documento, 'numero' => $numero, 'proveedor' => $fila['nombrecomercial'], 'fecha' => $fila['fecha'], 'color' => $color);
    }

    private function lineaDeCompras($idArticulo, $unidades, $coste, $articulos, $fila, $etiqueta = '')
    {
        // @ Objetivo
        // Una línea con los campos con que compras guarda las suyas.
        $nombre = '';
        $referencia = '';
        $codigo = '';
        $iva = 0.0;
        if (isset($articulos[$idArticulo])) {
            $nombre = (string) $articulos[$idArticulo]['articulo_name'];
            $referencia = (string) $articulos[$idArticulo]['crefTienda'];
            $codigo = (string) $articulos[$idArticulo]['codBarras'];
            $iva = (float) $articulos[$idArticulo]['iva'];
        }

        return array(
            'ccodbar' => $codigo,
            'cdetalle' => mb_substr($nombre, 0, 100 - mb_strlen($etiqueta)) . $etiqueta,
            'cref' => $referencia,
            'ref_prov' => '',
            'estado' => 'Activo',
            'idArticulo' => (int) $idArticulo,
            'importe' => $unidades * $coste,
            'iva' => $iva,
            'ncant' => $unidades,
            'nfila' => $fila,
            'nunidades' => $unidades,
            'ultimoCoste' => $coste,
        );
    }

    private function unidadComun($lineas)
    {
        // @ Objetivo
        // La unidad de todas las líneas activas, o cadena vacía si no es la misma en todas.
        $unidad = null;
        foreach ($lineas as $linea) {
            if (!isset($linea['estado']) || $linea['estado'] === 'Activo') {
                if ($unidad === null) {
                    $unidad = (string) $linea['unidad'];
                } elseif ($unidad !== (string) $linea['unidad']) {
                    $unidad = '';
                }
            }
        }

        return (string) $unidad;
    }

    private function fechaLegible($fecha)
    {
        return date('d-m-Y', strtotime($fecha));
    }

    private function numeroLegible($numero)
    {
        return str_replace('.', ',', (string) round($numero, 3));
    }
}
