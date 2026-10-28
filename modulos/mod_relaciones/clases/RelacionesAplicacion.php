<?php

include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesModelo.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/Relaciones.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesTipos.php';
include_once $URLCom . '/clases/Proveedores.php';

// @ Objetivo
// Aplicar una relación a lo que queda de su producto en un periodo, o a la parte que se elija,
// para preparar el albarán que lo registra. Cada línea lleva el signo que le dan su sentido y el
// documento: en un albarán de proveedor, las entradas restan y las salidas suman; en uno de
// cliente, al revés.
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
        // lo disponible según los movimientos y las aplicaciones anteriores, el coste de partida,
        // el aviso de proceso duplicado, la cantidad que entra en la receta, el escandallo, lo que
        // quedaría de cada pieza al final del periodo y las líneas del albarán.
        // @ Parametros
        //      $peticion -> (array) idProveedor, numRelacion, tipoPeriodo, periodo, version
        //                   ('periodo', la que regía en la fecha del albarán, o 'vigente'),
        //                   idProveedorTraspaso, precios (por idArticulo, como se escribieron),
        //                   ajustePeso, ajusteValor, cantidad (la que entra en la receta, como se
        //                   escribió; vacía, lo disponible menos lo de las hermanas) y hermanas (por
        //                   receta hermana, numRelacion, cantidad, precios, ajustePeso y ajusteValor;
        //                   false si la lista llegó mal).
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

        $hermanas = array();
        if ($version !== null) {
            $leidas = $this->hermanasDeLaPeticion($peticion, $version, $entrada, $fechaDeLaVersion);
            if (isset($leidas['error'])) {
                $respuesta['avisos'][] = $leidas['error'];
                $version = null;
            } else {
                $hermanas = $leidas['datos'];
            }
        }

        $disponible = null;
        if ($version !== null) {
            $traspaso = 0;
            if ($peticion['idProveedorTraspaso'] !== '') {
                $traspaso = (int) $peticion['idProveedorTraspaso'];
            }
            $lectura = $this->loDisponible($entrada['idArticulo'], $periodo['desde'], $periodo['hasta'], $traspaso, $consultaDeExistencias);
            if (isset($lectura['error'])) {
                $respuesta['avisos'][] = $lectura['error'];
            } else {
                $respuesta['datos']['cantidadADespiezar'] = $lectura['datos']['existencias'];
                $respuesta['datos']['primeraEntrada'] = $lectura['datos']['primeraEntrada'];
                $respuesta['datos']['mermaAnterior'] = $lectura['datos']['mermaAnterior'];
                $respuesta['datos']['enTemporales'] = $lectura['datos']['enTemporales'];
                $respuesta['datos']['disponible'] = $lectura['datos']['disponible'];
                if ($lectura['datos']['existencias'] <= 0) {
                    $respuesta['avisos'][] = $this->montarAdvertencia('warning', 'No hay nada que despiezar de «' . $entrada['articulo'] . '» del '
                        . $this->fechaLegible($periodo['desde']) . ' al ' . $this->fechaLegible($periodo['hasta'])
                        . ': no entró nada o se vendió todo entero. Elige otro periodo.');
                } else {
                    $disponible = $lectura['datos']['disponible'];
                }
            }
        }

        $costeDePartida = null;
        if ($disponible !== null) {
            $coste = $this->costeDePartida($entrada['idArticulo']);
            if (isset($coste['error'])) {
                $respuesta['avisos'][] = $coste['error'];
            } else {
                $costeDePartida = $coste['datos'];
                $respuesta['datos']['costeDePartida'] = $costeDePartida;
            }
        }

        // El aviso va antes que la cantidad: sin nada disponible, dice qué documentos lo consumieron.
        $cantidad = null;
        if ($costeDePartida !== null) {
            $aviso = $this->avisoDeDuplicado(array($entrada['idArticulo']), (int) $peticion['idProveedor'], $periodo['desde'], $periodo['hasta']);
            if (isset($aviso['error'])) {
                $respuesta['avisos'][] = $aviso['error'];
            } else {
                $respuesta['datos']['aviso'] = $aviso['datos'];
                $respuesta['avisos'][] = $this->advertenciaDeDuplicado($aviso['datos'], $entrada['articulo'], $periodo['desde']);
                $escrita = '';
                if (isset($peticion['cantidad'])) {
                    $escrita = $peticion['cantidad'];
                }
                $elegida = $this->cantidadAplicada($escrita, $disponible, $entrada, $periodo, $hermanas);
                if (isset($elegida['error'])) {
                    $respuesta['avisos'][] = $elegida['error'];
                } else {
                    $cantidad = $elegida['datos']['principal'];
                    $respuesta['datos']['cantidad'] = $cantidad;
                }
            }
        }

        if ($cantidad !== null) {
            // La elegida primero y las hermanas detrás, en el orden en que llegan.
            $precios = array();
            if (is_array($peticion['precios'])) {
                $precios = $peticion['precios'];
            }
            $recetas = array(array('numRelacion' => (int) $version['numRelacion'], 'nombre' => $this->nombreDeLaReceta($version), 'version' => $version,
                'cantidad' => $cantidad, 'precios' => $precios, 'ajustePeso' => $peticion['ajustePeso'], 'ajusteValor' => $peticion['ajusteValor']));
            foreach ($hermanas as $posicion => $hermana) {
                $hermana['cantidad'] = $elegida['datos']['hermanas'][$posicion];
                $recetas[] = $hermana;
            }
            $calculadas = $this->recetasCalculadas($recetas, $costeDePartida['coste']);
            if (isset($calculadas['error'])) {
                $respuesta['avisos'][] = $calculadas['error'];
            } else {
                $recetas = $calculadas['datos'];
                $idArticulos = array((int) $entrada['idArticulo']);
                foreach ($recetas as $receta) {
                    foreach ($receta['version']['lineas'] as $linea) {
                        if ($linea['sentido'] === 'salida') {
                            $idArticulos[] = (int) $linea['idArticulo'];
                        }
                    }
                }
                $articulos = $this->datosDeLineas(array_values(array_unique($idArticulos)));
                if (isset($articulos['error'])) {
                    $respuesta['avisos'][] = $articulos['error'];
                } else {
                    // Las líneas de cada receta van juntas, numeradas seguidas.
                    $lineas = array();
                    foreach ($recetas as $receta) {
                        if ($receta['escandallo'] !== null) {
                            $etiqueta = ' · REL ' . $receta['version']['numRelacion'] . ' v' . $receta['version']['version'];
                            foreach ($this->lineasDelAlbaran($receta['escandallo'], $articulos['datos'], self::DOCUMENTO_PROVEEDOR, $etiqueta) as $linea) {
                                $linea['nfila'] = count($lineas) + 1;
                                $lineas[] = $linea;
                            }
                        }
                    }
                    $respuesta['datos']['escandallo'] = $recetas[0]['escandallo'];
                    $respuesta['datos']['lineas'] = $lineas;
                    $respuesta['datos']['proveedor'] = $proveedor['datos'];
                    foreach ($calculadas['avisos'] as $avisoDelEscandallo) {
                        $respuesta['avisos'][] = $avisoDelEscandallo;
                    }
                    $conOtroIva = $this->ivaDistinto($lineas);
                    if (count($conOtroIva) > 0) {
                        $respuesta['avisos'][] = $this->montarAdvertencia('warning', 'Otro IVA que el de «' . $entrada['articulo'] . '» en: '
                            . implode(', ', $conOtroIva) . '. El albarán queda a 0,00 € sin IVA, pero su IVA no se compensa: revísalo antes de guardarlo.');
                    }
                    $desajuste = $this->desajusteDeLasRecetas($recetas, $periodo['desde'], $periodo['hasta'], $traspaso, $consultaDeExistencias);
                    $desajuste['datos']['minimo'] = $desajuste['datos']['minimos'][$recetas[0]['numRelacion']];
                    $respuesta['datos']['desajuste'] = $desajuste['datos'];
                    $respuesta['datos']['recetas'] = array();
                    foreach ($recetas as $receta) {
                        $respuesta['datos']['recetas'][] = array('numRelacion' => $receta['numRelacion'], 'nombre' => $receta['nombre'], 'version' => $receta['version']['version'],
                            'cantidad' => $receta['cantidad'], 'escandallo' => $receta['escandallo'], 'minimo' => $desajuste['datos']['minimos'][$receta['numRelacion']]);
                    }
                    $avisoDeDesajuste = $this->advertenciaDeDesajuste($desajuste['datos'], $entrada, $recetas);
                    if ($avisoDeDesajuste !== null) {
                        $respuesta['avisos'][] = $avisoDeDesajuste;
                    }
                    $respuesta['datos']['numeroDelAlbaran'] = $this->numeroDelAlbaran($version['tipo']);
                    $respuesta['datos']['destinos'] = array();
                    if ($peticion['tipoPeriodo'] === 'mes') {
                        $destinos = $this->albaranesParaReunir((int) $peticion['idProveedor'], $periodo['fecha'], $respuesta['datos']['numeroDelAlbaran']);
                        if (isset($destinos['error'])) {
                            $respuesta['avisos'][] = $destinos['error'];
                        } else {
                            $respuesta['datos']['destinos'] = $destinos['datos'];
                        }
                    }
                    $respuesta['ok'] = true;
                }
            }
        }

        return $respuesta;
    }

    public function numeroDelAlbaran($nombreDelTipo)
    {
        // @ Objetivo
        // El número con que se prepara el albarán: el nombre del tipo de relación, cortado a los
        // 20 caracteres de la columna del número, que no admite más.
        // @ Devolvemos
        //      (string) el número.
        return mb_substr($nombreDelTipo, 0, 20);
    }

    public function albaranesParaReunir($idProveedor, $fecha, $numero)
    {
        // @ Objetivo
        // Los albaranes de compras con los que se pueden reunir las líneas de un despiece: los
        // del mismo proveedor, de la misma fecha y con el mismo número, sean temporales abiertos o
        // guardados. Un guardado con un temporal abierto sale por su temporal; no salen los
        // facturados ni los que llevan pedidos adjuntos.
        // @ Parametros
        //      $idProveedor -> (int) proveedor elegido.
        //      $fecha -> (string) AAAA-MM-DD, la fecha del albarán.
        //      $numero -> (string) el número del albarán (numeroDelAlbaran).
        // @ Devolvemos
        //      ['datos'] con una lista de destino ('temporal:<id>' o 'albaran:<id>'), numero, fecha,
        //      proveedor y productos (los nombres de sus líneas); o ['error'].
        $destinos = array();
        $sql = "SELECT t.id, t.Fecha, t.Su_numero, t.Productos, p.nombrecomercial FROM albproltemporales t"
            . " JOIN proveedores p ON p.idProveedor = t.idProveedor"
            . " LEFT JOIN albprot a ON a.id = t.Numalbpro"
            . " WHERE t.idProveedor = ? AND DATE(t.Fecha) = ? AND t.Su_numero = ?"
            . " AND (t.Numalbpro IS NULL OR t.Numalbpro = 0 OR a.estado = 'Guardado' OR a.estado = 'Sin Guardar')"
            . " ORDER BY t.id";
        $temporales = $this->consultaPreparada($sql, array($idProveedor, $fecha, $numero));
        if (isset($temporales['error'])) {
            return $temporales;
        }
        foreach ($temporales['datos'] as $temporal) {
            $nombres = array();
            $lineas = json_decode($temporal['Productos'], true);
            if (is_array($lineas)) {
                foreach ($lineas as $linea) {
                    if (isset($linea['estado']) && strtolower($linea['estado']) === 'activo') {
                        $nombres[] = $linea['cdetalle'];
                    }
                }
            }
            $destinos[] = array('destino' => 'temporal:' . $temporal['id'], 'numero' => $temporal['Su_numero'], 'fecha' => $temporal['Fecha'],
                'proveedor' => $temporal['nombrecomercial'], 'productos' => array_values(array_unique($nombres)));
        }

        $sql = "SELECT a.id, a.Fecha, a.Su_numero, p.nombrecomercial FROM albprot a"
            . " JOIN proveedores p ON p.idProveedor = a.idProveedor"
            . " WHERE a.idProveedor = ? AND DATE(a.Fecha) = ? AND a.Su_numero = ? AND a.estado = 'Guardado'"
            . " AND NOT EXISTS (SELECT 1 FROM albproltemporales t WHERE t.Numalbpro = a.id)"
            . " AND NOT EXISTS (SELECT 1 FROM pedproAlb pa WHERE pa.idAlbaran = a.id)"
            . " ORDER BY a.id";
        $guardados = $this->consultaPreparada($sql, array($idProveedor, $fecha, $numero));
        if (isset($guardados['error'])) {
            return $guardados;
        }
        foreach ($guardados['datos'] as $guardado) {
            $lineas = $this->consultaPreparada("SELECT cdetalle FROM albprolinea WHERE idalbpro = ? AND estadoLinea = 'Activo' ORDER BY nfila", array($guardado['id']));
            if (isset($lineas['error'])) {
                return $lineas;
            }
            $destinos[] = array('destino' => 'albaran:' . $guardado['id'], 'numero' => $guardado['Su_numero'], 'fecha' => $guardado['Fecha'],
                'proveedor' => $guardado['nombrecomercial'], 'productos' => array_values(array_unique(array_column($lineas['datos'], 'cdetalle'))));
        }

        return array('datos' => $destinos);
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

    public function loDisponible($idArticulo, $desde, $hasta, $idProveedorTraspaso, $consultaDeExistencias)
    {
        // @ Objetivo
        // Lo que queda del producto en el periodo para aplicarlo: los movimientos desde la primera
        // entrada, con las entradas de un albarán guardado que compras ha reabierto —mientras se
        // edita, la comprobación de existencias no lo cuenta—; menos la merma que dejó en las
        // existencias cada aplicación guardada; y menos lo que sacan los temporales sin guardar de
        // proveedores «Especial» y el de un albarán reabierto. Lo guardado y los temporales cuentan
        // con los mismos días y el mismo proveedor de traspaso que los movimientos: así, guardar o
        // reabrir un albarán no cambia lo disponible.
        // @ Parametros
        //      $idArticulo -> el producto de entrada de la relación.
        //      $desde, $hasta -> 'AAAA-MM-DD', el primer y el último día del periodo.
        //      $idProveedorTraspaso -> (int) el proveedor de traspaso elegido, o 0.
        //      $consultaDeExistencias -> ClaseComprobacionStockConsulta de mod_reorganizacion.
        // @ Devolvemos
        //      ['datos'] con existencias, primeraEntrada, mermaAnterior, enTemporales y
        //      disponible; o ['error'].
        $idArticulo = (int) $idArticulo;
        $traspaso = (int) $idProveedorTraspaso;
        $sql = 'SELECT DATE(c.Fecha) AS fecha, l.nunidades FROM albprolinea l'
            . ' JOIN albprot c ON c.id = l.idalbpro'
            . " WHERE l.idArticulo = ? AND l.estadoLinea = 'Activo' AND l.nunidades > 0 AND c.estado = 'Sin Guardar'"
            . ' AND DATE(c.Fecha) BETWEEN ? AND ? AND NOT (c.idProveedor = ? AND DATE(c.Fecha) IN (?, ?))'
            . ' AND EXISTS (SELECT 1 FROM albproltemporales t WHERE t.Numalbpro = c.id)';
        $reabiertas = $this->consultaPreparada($sql, array($idArticulo, $desde, $hasta, $traspaso, $desde, $hasta));
        if (isset($reabiertas['error'])) {
            return $reabiertas;
        }
        $deProveedor = $consultaDeExistencias->lineasDeAlbaranDeProveedor($idArticulo, $desde, $hasta, $traspaso);
        foreach ($reabiertas['datos'] as $linea) {
            $deProveedor[] = array('fecha' => $linea['fecha'], 'nunidades' => (float) $linea['nunidades']);
        }
        $movimientos = $this->cantidadADespiezar(
            $deProveedor,
            $consultaDeExistencias->lineasDeTicket($idArticulo, $desde, $hasta),
            $consultaDeExistencias->lineasDeAlbaranDeCliente($idArticulo, $desde, $hasta)
        );

        $merma = 0.0;
        $enTemporales = 0.0;
        if ($movimientos['primeraEntrada'] !== null) {
            $primera = $movimientos['primeraEntrada'];
            $sql = 'SELECT l.nunidades, l.cdetalle FROM albprolinea l'
                . ' JOIN albprot c ON c.id = l.idalbpro'
                . ' JOIN proveedores p ON p.idProveedor = c.idProveedor'
                . " WHERE p.estado = 'Especial' AND l.idArticulo = ? AND l.estadoLinea = 'Activo' AND l.nunidades < 0"
                . " AND c.estado IN ('Guardado', 'Facturado', 'Exportado', 'Importado')"
                . ' AND DATE(c.Fecha) BETWEEN ? AND ? AND NOT (c.idProveedor = ? AND DATE(c.Fecha) IN (?, ?))';
            $guardadas = $this->consultaPreparada($sql, array($idArticulo, $primera, $hasta, $traspaso, $desde, $hasta));
            if (isset($guardadas['error'])) {
                return $guardadas;
            }
            foreach ($guardadas['datos'] as $linea) {
                $lectura = $this->lecturaDeLaLinea($idArticulo, (string) $linea['cdetalle']);
                if (isset($lectura['error'])) {
                    return $lectura;
                }
                // Los movimientos ya restan lo despiezado: lo que falta es la merma.
                $despiezado = -(float) $linea['nunidades'];
                $merma += $despiezado / $lectura['datos']['rendimiento'] - $despiezado;
            }

            $sql = 'SELECT t.Productos FROM albproltemporales t'
                . ' JOIN proveedores p ON p.idProveedor = t.idProveedor'
                . ' LEFT JOIN albprot a ON a.id = t.Numalbpro'
                . " WHERE (p.estado = 'Especial' OR a.estado = 'Sin Guardar')"
                . ' AND DATE(t.Fecha) BETWEEN ? AND ? AND NOT (t.idProveedor = ? AND DATE(t.Fecha) IN (?, ?))';
            $temporales = $this->consultaPreparada($sql, array($primera, $hasta, $traspaso, $desde, $hasta));
            if (isset($temporales['error'])) {
                return $temporales;
            }
            foreach ($temporales['datos'] as $temporal) {
                $productos = json_decode((string) $temporal['Productos'], true);
                if (is_array($productos)) {
                    foreach ($productos as $producto) {
                        // Solo saca lo que va en negativo: una entrada no cuenta hasta que se guarda.
                        if (isset($producto['idArticulo']) && isset($producto['nunidades']) && (int) $producto['idArticulo'] === $idArticulo
                            && (!isset($producto['estado']) || strtolower($producto['estado']) === 'activo') && (float) $producto['nunidades'] < 0) {
                            $lectura = $this->lecturaDeLaLinea($idArticulo, (string) ($producto['cdetalle'] ?? ''));
                            if (isset($lectura['error'])) {
                                return $lectura;
                            }
                            $enTemporales += -(float) $producto['nunidades'] / $lectura['datos']['rendimiento'];
                        }
                    }
                }
            }
        }
        $merma = round($merma, 3);
        $enTemporales = round($enTemporales, 3);

        return array('datos' => array(
            'existencias' => $movimientos['cantidad'],
            'primeraEntrada' => $movimientos['primeraEntrada'],
            'mermaAnterior' => $merma,
            'enTemporales' => $enTemporales,
            'disponible' => round($movimientos['cantidad'] - $merma - $enTemporales, 3),
        ));
    }

    public function rendimiento($lineas)
    {
        // @ Objetivo
        // Lo que sale de cada unidad que entra en una versión: la suma de las cantidades de sus
        // salidas entre la de su entrada, con las líneas activas. Sin entrada o sin salidas es 1, y
        // la línea saca su cantidad.
        // @ Devolvemos
        //      (float) el rendimiento.
        $entra = 0.0;
        $sale = 0.0;
        foreach ($lineas as $linea) {
            if (!isset($linea['estado']) || $linea['estado'] === 'Activo') {
                if ($linea['sentido'] === 'entrada') {
                    $entra += (float) $linea['cantidad'];
                } elseif ($linea['sentido'] === 'salida') {
                    $sale += (float) $linea['cantidad'];
                }
            }
        }
        $rendimiento = 1.0;
        if ($entra > 0 && $sale > 0) {
            $rendimiento = $sale / $entra;
        }

        return $rendimiento;
    }

    public function desajusteDeLasPiezas($version, $escandallo, $desde, $hasta, $idProveedorTraspaso, $consultaDeExistencias)
    {
        // @ Objetivo
        // Lo que quedaría de cada pieza de una sola receta al final del periodo, con el mínimo de cada
        // pieza: desajusteDeLasRecetas() con una receta.
        // @ Devolvemos
        //      ['datos'] con lo que devuelve desajuste().
        $respuesta = $this->desajusteDeLasRecetas(array(array('numRelacion' => 0, 'version' => $version, 'escandallo' => $escandallo)), $desde, $hasta, $idProveedorTraspaso, $consultaDeExistencias);
        foreach ($respuesta['datos']['piezas'] as $posicion => $pieza) {
            $respuesta['datos']['piezas'][$posicion]['minimo'] = $pieza['minimos'][0];
        }
        $respuesta['datos']['minimo'] = $respuesta['datos']['minimos'][0];

        return $respuesta;
    }

    public function desajusteDeLasRecetas($recetas, $desde, $hasta, $idProveedorTraspaso, $consultaDeExistencias)
    {
        // @ Objetivo
        // Lo que quedaría al final del periodo de cada pieza de las recetas de una operación, con lo
        // que le dan todas: su saldo al empezar el periodo, más sus líneas de proveedor del periodo,
        // menos sus tickets y sus albaranes de cliente del periodo, más lo que genera cada receta.
        // @ Parametros
        //      $recetas -> por receta: numRelacion, version, con sus líneas, y escandallo, el de
        //                  escandallo(), o null si no entra.
        //      $desde, $hasta -> 'AAAA-MM-DD', el primer y el último día del periodo.
        //      $idProveedorTraspaso -> (int) el proveedor de traspaso elegido, o 0.
        //      $consultaDeExistencias -> ClaseComprobacionStockConsulta de mod_reorganizacion.
        // @ Devolvemos
        //      ['datos'] con lo que devuelve desajusteDeLaOperacion().
        $piezas = array();
        $numeros = array();
        foreach ($recetas as $receta) {
            $numero = $receta['numRelacion'];
            $numeros[] = $numero;
            $entra = 0.0;
            foreach ($receta['version']['lineas'] as $linea) {
                if ($linea['sentido'] === 'entrada' && (!isset($linea['estado']) || $linea['estado'] === 'Activo')) {
                    $entra = (float) $linea['cantidad'];
                }
            }
            $genera = array();
            if ($receta['escandallo'] !== null) {
                foreach ($receta['escandallo']['piezas'] as $pieza) {
                    $genera[(int) $pieza['idArticulo']] = (float) $pieza['cantidad'];
                }
            }
            foreach ($receta['version']['lineas'] as $linea) {
                if ($linea['sentido'] === 'salida' && (!isset($linea['estado']) || $linea['estado'] === 'Activo')) {
                    $id = (int) $linea['idArticulo'];
                    if (!isset($piezas[$id])) {
                        $movimientos = $this->movimientosDeLaPieza($id, $desde, $hasta, $idProveedorTraspaso, $consultaDeExistencias);
                        $piezas[$id] = array(
                            'idArticulo' => $id,
                            'articulo' => $linea['articulo'],
                            'unidad' => $linea['unidad'],
                            'saldoInicial' => $movimientos['saldoInicial'],
                            'entradas' => $movimientos['entradas'],
                            'salidas' => $movimientos['salidas'],
                            'generaPorReceta' => array(),
                            'porUnidadPorReceta' => array(),
                        );
                    }
                    $piezas[$id]['generaPorReceta'][$numero] = 0.0;
                    if (isset($genera[$id])) {
                        $piezas[$id]['generaPorReceta'][$numero] = $genera[$id];
                    }
                    $piezas[$id]['porUnidadPorReceta'][$numero] = 0.0;
                    if ($entra > 0) {
                        $piezas[$id]['porUnidadPorReceta'][$numero] = (float) $linea['cantidad'] / $entra;
                    }
                }
            }
        }

        return array('datos' => $this->desajusteDeLaOperacion(array_values($piezas), $numeros));
    }

    public function desajuste($piezas)
    {
        // @ Objetivo
        // Lo que queda de cada pieza de una sola receta al final del periodo y su mínimo:
        // desajusteDeLaOperacion() con una receta. No consulta la base.
        // @ Parametros
        //      $piezas -> por pieza: idArticulo, articulo, unidad, porUnidad, saldoInicial, entradas,
        //                 salidas y genera.
        // @ Devolvemos
        //      (array) piezas, cada una con queda y minimo, y minimo, el de la receta.
        foreach ($piezas as $posicion => $pieza) {
            $piezas[$posicion]['generaPorReceta'] = array(0 => $pieza['genera']);
            $piezas[$posicion]['porUnidadPorReceta'] = array(0 => $pieza['porUnidad']);
        }
        $desajuste = $this->desajusteDeLaOperacion($piezas, array(0));
        foreach ($desajuste['piezas'] as $posicion => $pieza) {
            $desajuste['piezas'][$posicion]['minimo'] = $pieza['minimos'][0];
        }

        return array('piezas' => $desajuste['piezas'], 'minimo' => $desajuste['minimos'][0]);
    }

    public function desajusteDeLaOperacion($piezas, $recetas)
    {
        // @ Objetivo
        // Lo que queda de cada pieza al final del periodo —su saldo inicial, más lo que entró, menos
        // lo que salió, más lo que generan todas las recetas— y el mínimo de cada receta para que
        // ninguna quede en negativo, con las demás como están: el mayor, entre sus piezas, de lo que
        // les falta sin lo que da esa receta, entre lo que da por unidad de la entrada, hacia arriba
        // al gramo, o a la unidad si va por unidades. No consulta la base.
        // @ Parametros
        //      $piezas -> por pieza: idArticulo, articulo, unidad, saldoInicial, entradas, salidas y,
        //                 por receta, lo que genera (generaPorReceta) y lo que da por unidad
        //                 (porUnidadPorReceta).
        //      $recetas -> los números de las recetas de la operación.
        // @ Devolvemos
        //      (array) piezas, cada una con genera, queda y su mínimo por receta, y minimos, el de
        //      cada receta.
        $minimos = array();
        foreach ($recetas as $numero) {
            $minimos[$numero] = 0.0;
        }
        foreach ($piezas as $posicion => $pieza) {
            $genera = array_sum($pieza['generaPorReceta']);
            $piezas[$posicion]['genera'] = round($genera, 3);
            $piezas[$posicion]['queda'] = round($pieza['saldoInicial'] + $pieza['entradas'] - $pieza['salidas'] + $genera, 3);
            $piezas[$posicion]['minimos'] = array();
            foreach ($pieza['porUnidadPorReceta'] as $numero => $porUnidad) {
                $deLasDemas = $genera - $pieza['generaPorReceta'][$numero];
                $suyo = $this->minimoRedondeado($pieza['salidas'] - $pieza['saldoInicial'] - $pieza['entradas'] - $deLasDemas, $porUnidad, $pieza['unidad']);
                $piezas[$posicion]['minimos'][$numero] = $suyo;
                if ($suyo > $minimos[$numero]) {
                    $minimos[$numero] = $suyo;
                }
            }
        }

        return array('piezas' => $piezas, 'minimos' => $minimos);
    }

    public function repartoDeLaCantidad($principal, $hermanas, $disponible, $entrada)
    {
        // @ Objetivo
        // Lo que entra en cada receta: en las hermanas, lo escrito, y vacía, nada; en la elegida, lo
        // escrito, y vacía, lo disponible menos lo de las hermanas. La suma no pasa de lo disponible.
        // No consulta la base.
        // @ Parametros
        //      $principal -> la cantidad escrita de la elegida, o null.
        //      $hermanas -> la de cada hermana, o null.
        //      $disponible -> lo disponible del producto de partida.
        //      $entrada -> la línea de entrada de la elegida, con su artículo y su unidad.
        // @ Devolvemos
        //      ['datos'] con principal y hermanas; o ['error'].
        $suma = 0.0;
        $cantidades = array();
        foreach ($hermanas as $cantidad) {
            $valor = 0.0;
            if ($cantidad !== null) {
                $valor = (float) $cantidad;
            }
            $cantidades[] = $valor;
            $suma += $valor;
        }
        $total = $suma;
        if ($principal !== null) {
            $total += $principal;
        }
        if ($total > $disponible + 0.0000001) {
            $mensaje = 'La cantidad (' . $this->numeroLegible($total) . ' ' . $entrada['unidad'] . ') pasa de lo disponible (' . $this->numeroLegible($disponible) . ' '
                . $entrada['unidad'] . '): escribe una menor, o deja el campo vacío para aplicar todo lo disponible.';
            if (count($hermanas) > 0) {
                $mensaje = 'Lo que entra en las recetas (' . $this->numeroLegible($total) . ' ' . $entrada['unidad'] . ') pasa de lo disponible ('
                    . $this->numeroLegible($disponible) . ' ' . $entrada['unidad'] . '): baja la cantidad de alguna, o deja vacía la de la elegida para que tome lo que sobra.';
            }
            return array('error' => $this->montarAdvertencia('warning', $mensaje));
        }
        if ($principal === null) {
            $principal = round($disponible - $suma, 3);
        }

        return array('datos' => array('principal' => (float) $principal, 'hermanas' => $cantidades));
    }

    public function hermanas($numRelacion)
    {
        // @ Objetivo
        // Las recetas hermanas de una relación: las demás que rigen y se pueden aplicar, de su mismo
        // tipo y con el mismo producto de entrada. Se aplican a la vez que ella, cada una con su
        // cantidad.
        // @ Devolvemos
        //      ['datos'] con numRelacion, nombre y el producto que entra; o ['error'].
        $aplicables = $this->relacionesAplicables();
        if (isset($aplicables['error'])) {
            return $aplicables;
        }
        $elegida = null;
        foreach ($aplicables['datos'] as $relacion) {
            if ((string) $relacion['numRelacion'] === (string) $numRelacion) {
                $elegida = $relacion;
            }
        }
        if ($elegida === null) {
            return array('error' => $this->montarAdvertencia('warning', 'La relación elegida no se puede aplicar: elige una de la lista.'));
        }
        $respuesta = array('datos' => array());
        foreach ($aplicables['datos'] as $relacion) {
            if ($relacion['numRelacion'] !== $elegida['numRelacion'] && $relacion['idTipo'] === $elegida['idTipo'] && $relacion['idEntrada'] === $elegida['idEntrada']) {
                $nombre = $relacion['entrada'];
                if ($relacion['nombre'] !== null && $relacion['nombre'] !== '') {
                    $nombre = $relacion['nombre'];
                }
                $respuesta['datos'][] = array('numRelacion' => $relacion['numRelacion'], 'nombre' => $nombre, 'entrada' => $relacion['entrada']);
            }
        }

        return $respuesta;
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

    public function cantidadEscrita($texto, $unidad)
    {
        // @ Objetivo
        // Leer la cantidad que entra en la receta, escrita a mano con coma o con punto decimal: un
        // número mayor que 0 con hasta tres decimales, o entero si el despiece va por unidades.
        // @ Devolvemos
        //      ['datos'] con la cantidad, o null si no se escribió; o ['error'].
        $limpio = str_replace(',', '.', trim((string) $texto));
        $respuesta = array('datos' => null);
        if ($limpio !== '') {
            $patron = '/^[0-9]+(\.[0-9]{1,3})?$/';
            $como = 'con hasta tres decimales';
            if ($unidad === 'ud') {
                $patron = '/^[0-9]+$/';
                $como = 'sin decimales, porque el despiece va por unidades';
            }
            if (preg_match($patron, $limpio) === 1 && (float) $limpio > 0) {
                $respuesta = array('datos' => (float) $limpio);
            } else {
                $respuesta = array('error' => $this->montarAdvertencia('warning', 'La cantidad «' . $texto
                    . '» no vale: escríbela con cifras, mayor que 0 y ' . $como . '.'));
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
        //      ['datos'] con numRelacion, nombre, tipo y el producto que entra, y los id del tipo y
        //      del producto, con los que se buscan las hermanas; o ['error'].
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
                    $idEntra = 0;
                    foreach ($relacion['lineas'] as $linea) {
                        if ($linea['sentido'] === 'entrada') {
                            $entra = $linea['articulo'];
                            $idEntra = (int) $linea['idArticulo'];
                        }
                    }
                    $respuesta['datos'][] = array('numRelacion' => (int) $relacion['numRelacion'], 'nombre' => $relacion['nombre'], 'tipo' => $relacion['tipo'], 'entrada' => $entra,
                        'idTipo' => $idTipo, 'idEntrada' => $idEntra);
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
        // Avisar de los albaranes y los temporales de compras de proveedores «Especial» con alguno
        // de los productos de entrada, desde el primer día del periodo, y de si lo llevan aplicado
        // o como derivado de otra aplicación —una pieza que salió de otra relación—. Todos salen
        // en amarillo, y sin ninguno, verde. El aviso no impide aplicar: lo que se aplica no pasa
        // de lo disponible.
        // @ Parametros
        //      $idArticulos -> los productos de entrada.
        //      $idProveedor, $hasta -> el proveedor elegido y el último día del periodo; no cambian
        //                   el aviso.
        //      $desde -> el primer día del periodo.
        // @ Devolvemos
        //      ['datos'] con color y documentos —albarán o temporal, número, proveedor, fecha y
        //      color, y 'derivado' en los que solo lo llevan así—; o ['error'].
        $ids = array();
        foreach ($idArticulos as $idArticulo) {
            $ids[] = (int) $idArticulo;
        }
        $documentos = array();
        $sql = 'SELECT c.id, c.Numalbpro, DATE(c.Fecha) AS fecha, c.idProveedor, p.nombrecomercial, l.idArticulo, l.cdetalle FROM albprot c'
            . ' JOIN proveedores p ON p.idProveedor = c.idProveedor'
            . ' JOIN albprolinea l ON l.idalbpro = c.id'
            . " WHERE p.estado = 'Especial' AND DATE(c.Fecha) >= ? AND l.estadoLinea = 'Activo' AND l.idArticulo IN ("
            . implode(', ', array_fill(0, count($ids), '?')) . ')'
            . ' ORDER BY c.Fecha, c.id';
        $respuesta = $this->consultaPreparada($sql, array_merge(array($desde), $ids));
        if (!isset($respuesta['error'])) {
            // Las líneas llegan una por fila: se juntan por albarán, que es derivado solo si todas
            // sus líneas del producto lo son.
            $albaranes = array();
            foreach ($respuesta['datos'] as $fila) {
                if (!isset($albaranes[$fila['id']])) {
                    $albaranes[$fila['id']] = array('fila' => $fila, 'derivado' => true);
                }
                $lectura = $this->lecturaDeLaLinea((int) $fila['idArticulo'], (string) $fila['cdetalle']);
                if (isset($lectura['error'])) {
                    $respuesta = $lectura;
                    break;
                }
                if (!$lectura['datos']['derivado']) {
                    $albaranes[$fila['id']]['derivado'] = false;
                }
            }
            if (!isset($respuesta['error'])) {
                foreach ($albaranes as $albaran) {
                    $documentos[] = $this->documentoDelAviso('albarán', (int) $albaran['fila']['Numalbpro'], $albaran['fila'], $albaran['derivado']);
                }
                $sql = 'SELECT t.id, DATE(t.Fecha) AS fecha, t.idProveedor, p.nombrecomercial, t.Productos FROM albproltemporales t'
                    . ' JOIN proveedores p ON p.idProveedor = t.idProveedor'
                    . " WHERE p.estado = 'Especial' AND DATE(t.Fecha) >= ?"
                    . ' ORDER BY t.Fecha, t.id';
                $respuesta = $this->consultaPreparada($sql, array($desde));
            }
        }
        if (!isset($respuesta['error'])) {
            foreach ($respuesta['datos'] as $fila) {
                $productos = json_decode((string) $fila['Productos'], true);
                $loLleva = false;
                $derivado = true;
                if (is_array($productos)) {
                    foreach ($productos as $producto) {
                        if (isset($producto['idArticulo']) && in_array((int) $producto['idArticulo'], $ids, true)
                            && (!isset($producto['estado']) || strtolower($producto['estado']) === 'activo')) {
                            $loLleva = true;
                            $lectura = $this->lecturaDeLaLinea((int) $producto['idArticulo'], (string) ($producto['cdetalle'] ?? ''));
                            if (isset($lectura['error'])) {
                                return $lectura;
                            }
                            if (!$lectura['datos']['derivado']) {
                                $derivado = false;
                            }
                        }
                    }
                }
                if ($loLleva) {
                    $documentos[] = $this->documentoDelAviso('temporal', (int) $fila['id'], $fila, $derivado);
                }
            }
            $color = 'verde';
            if (count($documentos) > 0) {
                $color = 'amarillo';
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
        // Una relación que hoy está de baja no se aplica, con la versión que fuera.
        // @ Devolvemos
        //      ['datos'] con la versión y sus líneas; o ['error'].
        $CRelaciones = new Relaciones();
        $CTipos = new RelacionesTipos();
        $hoy = $CRelaciones->estadoEnFecha($numRelacion, date('Y-m-d'));
        $estado = $hoy;
        if (!isset($hoy['error']) && $hoy['datos']['situacion'] === 'regia' && $hoy['datos']['version']['estado'] === 'Baja') {
            $estado = array('error' => $this->montarAdvertencia('warning', 'La relación está de baja: reactívala desde el listado de relaciones para poder aplicarla.'));
        } elseif (!isset($hoy['error'])) {
            $estado = $CRelaciones->estadoEnFecha($numRelacion, $fecha);
        }
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
        // El aviso de proceso duplicado: cada albarán o temporal, con cómo lleva el producto.
        $partes = array();
        foreach ($aviso['documentos'] as $documento) {
            $parte = $documento['documento'] . ' ' . $documento['numero'] . ' de ' . $documento['proveedor']
                . ', del ' . $this->fechaLegible($documento['fecha']);
            if (isset($documento['derivado'])) {
                $parte .= ', que lo lleva como derivado de otra aplicación';
            } else {
                $parte .= ', que lo lleva aplicado';
            }
            $partes[] = $parte;
        }
        if ($aviso['color'] === 'amarillo') {
            $advertencia = $this->montarAdvertencia('warning', 'Hay albaranes de proveedores especiales con «' . $producto . '» en el periodo o después: '
                . implode('; ', $partes) . '. Lo disponible ya descuenta lo que sacan los del periodo: compruébalos antes de preparar.');
        } else {
            $advertencia = $this->montarAdvertencia('success', 'Ningún albarán de un proveedor especial lleva «' . $producto . '» desde el '
                . $this->fechaLegible($desde) . ': se puede preparar.');
        }

        return $advertencia;
    }

    private function advertenciaDeDesajuste($desajuste, $entrada, $recetas)
    {
        // @ Objetivo
        // El aviso de las piezas que quedarían en negativo al final del periodo, con el mínimo de cada
        // receta. No impide preparar.
        // @ Devolvemos
        //      el aviso, o null si ninguna queda en negativo.
        $partes = array();
        foreach ($desajuste['piezas'] as $pieza) {
            if ($pieza['queda'] < 0) {
                $partes[] = '«' . $pieza['articulo'] . '», ' . $this->numeroLegible($pieza['queda']) . ' ' . $pieza['unidad'];
            }
        }
        $aviso = null;
        if (count($partes) > 0) {
            $cantidad = 0.0;
            $minimos = array();
            foreach ($recetas as $receta) {
                $cantidad += $receta['cantidad'];
                $minimos[] = $this->numeroLegible($desajuste['minimos'][$receta['numRelacion']]) . ' ' . $entrada['unidad'] . ' en «' . $receta['nombre'] . '»';
            }
            $minimo = $this->numeroLegible($desajuste['minimo']) . ' ' . $entrada['unidad'];
            if (count($recetas) > 1) {
                $minimo = implode(' o ', $minimos) . ', cada una con las demás como están';
            }
            $aviso = $this->montarAdvertencia('warning', 'Con ' . $this->numeroLegible($cantidad) . ' ' . $entrada['unidad'] . ' de «' . $entrada['articulo']
                . '» quedan en negativo al final del periodo: ' . implode('; ', $partes) . '. Para que ninguna quede en negativo tendrían que entrar al menos '
                . $minimo . '. No impide preparar: puede faltar una entrada sin registrar, '
                . 'haber producto de un periodo anterior o no responder el reparto a lo que se hizo.');
        }

        return $aviso;
    }

    private function documentoDelAviso($documento, $numero, $fila, $derivado)
    {
        // @ Objetivo
        // Un albarán o un temporal del aviso, en amarillo, con la marca de los que solo llevan el
        // producto como derivado.
        $respuesta = array('documento' => $documento, 'numero' => $numero, 'proveedor' => $fila['nombrecomercial'], 'fecha' => $fila['fecha'], 'color' => 'amarillo');
        // Solo los derivados llevan la marca: los demás documentos quedan como estaban.
        if ($derivado) {
            $respuesta['derivado'] = true;
        }

        return $respuesta;
    }

    private function cantidadAplicada($escrita, $disponible, $entrada, $periodo, $hermanas)
    {
        // @ Objetivo
        // Lo que entra en la receta elegida y en cada hermana: lo escrito, con su suma dentro de lo
        // disponible; la elegida sin escribir toma lo que sobra. Sin nada disponible no se aplica
        // nada.
        // @ Devolvemos
        //      ['datos'] con principal y hermanas; o ['error'], que dice por qué no.
        if ($disponible <= 0) {
            return array('error' => $this->montarAdvertencia('danger', 'No queda nada disponible de «' . $entrada['articulo'] . '» del '
                . $this->fechaLegible($periodo['desde']) . ' al ' . $this->fechaLegible($periodo['hasta'])
                . ': lo que entró ya está aplicado, en albaranes guardados o en temporales sin guardar. Revísalos en compras o elige otro periodo.'));
        }
        $principal = $this->cantidadEscrita($escrita, $entrada['unidad']);
        if (isset($principal['error'])) {
            return $principal;
        }
        $deLasHermanas = array();
        foreach ($hermanas as $hermana) {
            $suya = $this->cantidadEscrita($hermana['escrita'], $entrada['unidad']);
            if (isset($suya['error'])) {
                return $suya;
            }
            $deLasHermanas[] = $suya['datos'];
        }

        return $this->repartoDeLaCantidad($principal['datos'], $deLasHermanas, $disponible, $entrada);
    }

    private function hermanasDeLaPeticion($peticion, $version, $entrada, $fechaDeLaVersion)
    {
        // @ Objetivo
        // Las recetas hermanas que llegan en la petición, cada una con la versión que se aplica, si de
        // verdad lo son: se vuelven a buscar las de la elegida, y no se admite una que no está entre
        // ellas, repetida o la propia elegida, ni una cuya unidad no es la de la elegida.
        // @ Devolvemos
        //      ['datos'] con, por hermana, numRelacion, nombre, version, la cantidad como se escribió
        //      (escrita), precios, ajustePeso y ajusteValor; o ['error'].
        $pedidas = array();
        if (isset($peticion['hermanas'])) {
            $pedidas = $peticion['hermanas'];
        }
        $noVale = array('error' => $this->montarAdvertencia('warning', 'Alguna receta añadida no es hermana de «' . $this->nombreDeLaReceta($version)
            . '», está repetida o no es válida: quítala del árbol y vuelve a calcular.'));
        if (!is_array($pedidas)) {
            return $noVale;
        }
        $respuesta = array('datos' => array());
        if (count($pedidas) > 0) {
            $validas = $this->hermanas($version['numRelacion']);
            if (isset($validas['error'])) {
                return $validas;
            }
            $nombres = array();
            foreach ($validas['datos'] as $hermana) {
                $nombres[$hermana['numRelacion']] = $hermana['nombre'];
            }
            foreach ($pedidas as $pedida) {
                $numero = '';
                if (is_array($pedida) && isset($pedida['numRelacion'])) {
                    $numero = (string) $pedida['numRelacion'];
                }
                if (!ctype_digit($numero) || !isset($nombres[(int) $numero])) {
                    return $noVale;
                }
                foreach ($respuesta['datos'] as $yaLeida) {
                    if ($yaLeida['numRelacion'] === (int) $numero) {
                        return $noVale;
                    }
                }
                $suVersion = $this->versionAplicable((int) $numero, $fechaDeLaVersion);
                if (isset($suVersion['error'])) {
                    return $suVersion;
                }
                foreach ($suVersion['datos']['lineas'] as $linea) {
                    if ($linea['sentido'] === 'entrada' && $linea['unidad'] !== $entrada['unidad']) {
                        return $noVale;
                    }
                }
                $leida = array('numRelacion' => (int) $numero, 'nombre' => $nombres[(int) $numero], 'version' => $suVersion['datos'],
                    'escrita' => '', 'precios' => array(), 'ajustePeso' => '1', 'ajusteValor' => '1');
                foreach (array('cantidad' => 'escrita', 'ajustePeso' => 'ajustePeso', 'ajusteValor' => 'ajusteValor') as $campo => $clave) {
                    if (isset($pedida[$campo]) && !is_array($pedida[$campo])) {
                        $leida[$clave] = (string) $pedida[$campo];
                    }
                }
                if (isset($pedida['precios']) && is_array($pedida['precios'])) {
                    $leida['precios'] = $pedida['precios'];
                }
                $respuesta['datos'][] = $leida;
            }
        }

        return $respuesta;
    }

    private function recetasCalculadas($recetas, $costeDePartida)
    {
        // @ Objetivo
        // El escandallo de cada receta que entra, con sus precios escritos y su ajuste; la que no
        // entra queda con el escandallo vacío.
        // @ Devolvemos
        //      ['datos'] con las recetas y su escandallo, y ['avisos'] de los escandallos; o ['error'].
        $avisos = array();
        foreach ($recetas as $posicion => $receta) {
            $recetas[$posicion]['escandallo'] = null;
            if ($receta['cantidad'] > 0) {
                $salidas = array();
                foreach ($receta['version']['lineas'] as $linea) {
                    if ($linea['sentido'] === 'salida') {
                        $salidas[] = (int) $linea['idArticulo'];
                    }
                }
                $medias = $this->preciosDeMercado($salidas);
                if (isset($medias['error'])) {
                    return $medias;
                }
                $escandallo = $this->escandallo($receta['version'], $receta['cantidad'], $costeDePartida, $receta['precios'], $medias['datos'], $receta['ajustePeso'], $receta['ajusteValor']);
                if (isset($escandallo['error'])) {
                    if ($posicion > 0) {
                        $escandallo['error']['mensaje'] = '«' . $receta['nombre'] . '»: ' . $escandallo['error']['mensaje'];
                    }
                    return $escandallo;
                }
                $recetas[$posicion]['escandallo'] = $escandallo['datos'];
                foreach ($escandallo['avisos'] as $aviso) {
                    if ($posicion > 0) {
                        $aviso['mensaje'] = '«' . $receta['nombre'] . '»: ' . $aviso['mensaje'];
                    }
                    $avisos[] = $aviso;
                }
            }
        }

        return array('datos' => $recetas, 'avisos' => $avisos);
    }

    private function nombreDeLaReceta($version)
    {
        // @ Objetivo
        // El nombre con que se enseña una receta: el de la relación o, sin él, el del producto que
        // entra.
        $nombre = '';
        foreach ($version['lineas'] as $linea) {
            if ($linea['sentido'] === 'entrada') {
                $nombre = $linea['articulo'];
            }
        }
        if (isset($version['nombre']) && $version['nombre'] !== null && $version['nombre'] !== '') {
            $nombre = $version['nombre'];
        }

        return $nombre;
    }

    private function lecturaDeLaLinea($idArticulo, $detalle)
    {
        // @ Objetivo
        // Leer en el detalle de una línea la aplicación que la dejó. El de las líneas que deja la
        // aplicación termina en « · REL <número> v<versión>»: si el producto sale en esa versión de
        // la relación, es un derivado; si entra, se aplicó con ella y sacó lo despiezado entre el
        // rendimiento de esa versión. Una línea sin esa etiqueta, o con una relación que no lleva el
        // producto, cuenta como aplicada y saca su cantidad.
        // @ Devolvemos
        //      ['datos'] con derivado (bool) y rendimiento; o ['error'].
        $respuesta = array('datos' => array('derivado' => false, 'rendimiento' => 1.0));
        if (preg_match('/ · REL (\d+) v(\d+\.\d+)$/u', $detalle, $partes)) {
            $sql = 'SELECT l.idArticulo, l.sentido, l.cantidad FROM relaciones r JOIN relacionesLineas l ON l.idRelacion = r.id'
                . " WHERE r.numRelacion = ? AND r.version = ? AND l.estado = 'Activo'";
            $leido = $this->consultaPreparada($sql, array((int) $partes[1], $partes[2]));
            if (isset($leido['error'])) {
                $respuesta = $leido;
            } else {
                $sentido = '';
                foreach ($leido['datos'] as $linea) {
                    if ((int) $linea['idArticulo'] === (int) $idArticulo) {
                        $sentido = $linea['sentido'];
                    }
                }
                if ($sentido === 'salida') {
                    $respuesta['datos']['derivado'] = true;
                } elseif ($sentido === 'entrada') {
                    $respuesta['datos']['rendimiento'] = $this->rendimiento($leido['datos']);
                }
            }
        }

        return $respuesta;
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

    private function movimientosDeLaPieza($idArticulo, $desde, $hasta, $idProveedorTraspaso, $consultaDeExistencias)
    {
        // @ Objetivo
        // Lo que tenía una pieza al empezar el periodo y lo que entró y salió de ella en él. El saldo
        // inicial son sus movimientos desde el 1 de enero hasta la víspera, sin proveedor de traspaso;
        // si el periodo empieza el 1 de enero con proveedor de traspaso, la apertura, que el periodo
        // deja fuera.
        // @ Devolvemos
        //      (array) saldoInicial, entradas y salidas.
        $traspaso = (int) $idProveedorTraspaso;
        $inicio = substr($desde, 0, 4) . '-01-01';
        $vispera = date('Y-m-d', strtotime($desde . ' -1 day'));
        $saldoInicial = 0.0;
        if ($desde > $inicio) {
            $saldoInicial = $this->sumaDeUnidades($consultaDeExistencias->lineasDeAlbaranDeProveedor($idArticulo, $inicio, $vispera, 0))
                - $this->sumaDeUnidades($consultaDeExistencias->lineasDeTicket($idArticulo, $inicio, $vispera))
                - $this->sumaDeUnidades($consultaDeExistencias->lineasDeAlbaranDeCliente($idArticulo, $inicio, $vispera));
        } elseif ($traspaso > 0) {
            // La apertura del ejercicio: lo que el periodo deja fuera de su primer día.
            $saldoInicial = $this->sumaDeUnidades($consultaDeExistencias->lineasDeAlbaranDeProveedor($idArticulo, $desde, $desde, 0))
                - $this->sumaDeUnidades($consultaDeExistencias->lineasDeAlbaranDeProveedor($idArticulo, $desde, $desde, $traspaso));
        }

        return array(
            'saldoInicial' => round($saldoInicial, 3),
            'entradas' => round($this->sumaDeUnidades($consultaDeExistencias->lineasDeAlbaranDeProveedor($idArticulo, $desde, $hasta, $traspaso)), 3),
            'salidas' => round($this->sumaDeUnidades($consultaDeExistencias->lineasDeTicket($idArticulo, $desde, $hasta))
                + $this->sumaDeUnidades($consultaDeExistencias->lineasDeAlbaranDeCliente($idArticulo, $desde, $hasta)), 3),
        );
    }

    private function minimoRedondeado($falta, $porUnidad, $unidad)
    {
        // @ Objetivo
        // Lo que tendría que entrar en una receta para cubrir lo que le falta a una pieza: hacia
        // arriba, al gramo, o a la unidad si va por unidades; 0 si no le falta nada.
        $minimo = 0.0;
        if ($falta > 0 && $porUnidad > 0) {
            if ($unidad === 'ud') {
                $minimo = ceil($falta / $porUnidad - 0.000001);
            } else {
                $minimo = ceil($falta / $porUnidad * 1000 - 0.000001) / 1000;
            }
        }

        return (float) $minimo;
    }

    private function sumaDeUnidades($lineas)
    {
        // @ Objetivo
        // La suma con signo de las unidades de unas líneas de la comprobación de existencias.
        $suma = 0.0;
        foreach ($lineas as $linea) {
            $suma += (float) $linea['nunidades'];
        }

        return $suma;
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
