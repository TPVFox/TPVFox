<?php

include_once $RutaServidor . $HostNombre . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockConsulta.php';
include_once $RutaServidor . $HostNombre . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockCantidad.php';

// @ Objetivo
// Componer, para todo el catálogo, la trayectoria de existencias del producto en el
// ejercicio vigente y su estado frente al criterio: cruza el detector de existencias
// negativas y le aporta el saldo de partida ya calculado, y adjunta el marcado y las
// condiciones conocidas sin depender de que el detector haya emitido incidencia.
//
// No lee la base: todo lo que necesita se lo pide a la clase de consulta, y lo que
// hace con lo leído es la decisión. Sus propias lecturas van seguidas y terminan
// cerrando el bloque de solo lectura; el cruce al detector queda después, porque
// dentro del bloque no puede ejecutarse.
class ClaseComprobacionStockExtraccion
{
    private $consulta = null;

    public function extraer($contextoOperacion, $modoEstricto = false, $fechaCorte = null)
    {
        // @ Objetivo
        // Recorrer el catálogo completo, componer su trayectoria de existencias y
        // devolver el estado solo de los productos cuya trayectoria alcanzó negativo.
        // @ Parametros
        //      $contextoOperacion -> array, la salida de ClaseComprobacionStockContexto::abrir().
        //      $modoEstricto -> bool, opcional. Trunca a cero el saldo de partida.
        //      $fechaCorte -> string 'AAAA-MM-DD', opcional. Por defecto, hoy.
        // @ Devolvemos
        //      array de filas: idArticulo, saldoAlCorte, minimoAlcanzado,
        //      saldoDeApertura, marcado, tipoIncidencia (o null) y condicionesConocidas.
        $ano = (int) $contextoOperacion['ano'];
        $fiStock = ($ano - 1) . '-12-31';
        $ffStock = $ano . '-01-01';
        $fiMov = $ano . '-01-02';
        $ffMov = ($fechaCorte !== null) ? $fechaCorte : date('Y-m-d');
        // La regularización no es uno de los tres movimientos, así que el reparto entre
        // saldo de partida y recorrido no la alcanza: ninguna de las dos mitades la
        // absorbe. Por eso su periodo es el del ejercicio entero y arranca un día antes
        // que el recorrido; contándola desde el 2, una regularización del primer día del
        // año no la vería nadie.
        $fiEjercicio = $ano . '-01-01';

        $consulta = $this->consulta();

        $catalogoIds = $consulta->catalogoFisico();
        $stockBaseCache = $consulta->stockBase($fiStock, $ffStock, $catalogoIds);
        $movimientos = $consulta->movimientosDelPeriodo($fiMov, $ffMov);

        $trayectorias = $this->componerTrayectoria($catalogoIds, $stockBaseCache, $movimientos, $modoEstricto);
        $idsNegativos = $this->conTrayectoriaEnNegativo($trayectorias);

        if (empty($idsNegativos)) {
            $consulta->cerrarBloqueDeLectura();
            return array();
        }

        $familiaExcluidaDe = array_flip(
            $consulta->deFamiliasExcluidas($idsNegativos, $contextoOperacion['familiasExcluidas'])
        );
        $nuncaIncluidoEnCierre = array_flip($this->nuncaIncluidosEnElCierre(
            $idsNegativos,
            $consulta->conStockPositivoEnElCierre($idsNegativos)
        ));
        $conRegularizacion = array_flip($consulta->conRegularizacionEntre($idsNegativos, $fiEjercicio, $ffMov));

        // Aquí termina todo lo que este módulo lee, y con ello el bloque de solo
        // lectura. Lo que queda cruza al componente de existencias negativas, que crea
        // una tabla temporal para acotar la ventana de recepción y no puede hacerlo
        // dentro del bloque.
        $consulta->cerrarBloqueDeLectura();

        $tipoPorArticulo = $this->mapearIncidencias(
            $consulta->incidenciasC1($fiMov, $ffMov, $fiStock, $ffStock, $stockBaseCache, $contextoOperacion)
        );

        $ventanaDias = (int) $contextoOperacion['ventanaDias'];

        $resultado = array();
        foreach ($idsNegativos as $id) {
            $trayectoria = $trayectorias[$id];
            $condiciones = array();
            if (isset($familiaExcluidaDe[$id])) {
                $condiciones[] = 'familia_excluida';
            }
            if (isset($nuncaIncluidoEnCierre[$id])) {
                $condiciones[] = 'nunca_incluido_en_cierre';
            }
            if ($this->periodoNoConsolidado($trayectoria['fechaMinimo'], $ventanaDias, $ffMov)) {
                $condiciones[] = 'periodo_no_consolidado';
            }
            if (isset($conRegularizacion[$id])) {
                $condiciones[] = 'regularizacion_en_periodo';
            }

            $resultado[] = array(
                'idArticulo' => $id,
                'saldoAlCorte' => $trayectoria['saldoAlCorte'],
                'minimoAlcanzado' => $trayectoria['minimoAlcanzado'],
                'saldoDeApertura' => $trayectoria['saldoDeApertura'],
                'marcado' => $trayectoria['saldoAlCorte'] < 0,
                'tipoIncidencia' => isset($tipoPorArticulo[$id]) ? $tipoPorArticulo[$id] : null,
                'condicionesConocidas' => $condiciones,
            );
        }

        return $resultado;
    }

    public function componerTrayectoria($catalogoIds, $stockBaseCache, $movimientos, $modoEstricto)
    {
        // @ Objetivo
        // Para cada producto del catálogo, componer el saldo al corte y el mínimo
        // alcanzado desde el saldo de partida y el recorrido del periodo. No toca la
        // base: opera sobre lo que ya se ha leído.
        // @ Parametros
        //      $catalogoIds -> array de int, idArticulo del catálogo completo.
        //      $stockBaseCache -> array [idArticulo => ['saldo_acumulado' => float, ...]].
        //      $movimientos -> array de filas ['tipo_movimiento','idArticulo','nunidades','fecha'].
        //      $modoEstricto -> bool. Si es true, el saldo de partida se trunca a cero.
        // @ Devolvemos
        //      array [idArticulo => ['saldoAlCorte','minimoAlcanzado','saldoDeApertura','fechaMinimo']].
        $deltasPorDia = array();
        foreach ($movimientos as $fila) {
            $id = (int) $fila['idArticulo'];
            $signo = ($fila['tipo_movimiento'] === 'entrada_proveedor') ? 1 : -1;
            $fecha = $fila['fecha'];
            if (!isset($deltasPorDia[$id])) {
                $deltasPorDia[$id] = array();
            }
            if (!isset($deltasPorDia[$id][$fecha])) {
                $deltasPorDia[$id][$fecha] = 0.0;
            }
            $deltasPorDia[$id][$fecha] += $signo * (float) $fila['nunidades'];
        }

        $trayectorias = array();
        foreach ($catalogoIds as $id) {
            $saldoPartida = $modoEstricto ? 0.0 : (isset($stockBaseCache[$id]['saldo_acumulado']) ? (float) $stockBaseCache[$id]['saldo_acumulado'] : 0.0);
            $saldoPartida = ClaseComprobacionStockCantidad::normalizar($saldoPartida);

            if (!isset($deltasPorDia[$id])) {
                $trayectorias[$id] = array(
                    'saldoAlCorte' => $saldoPartida,
                    'minimoAlcanzado' => $saldoPartida,
                    'saldoDeApertura' => $saldoPartida,
                    'fechaMinimo' => null,
                );
                continue;
            }

            $dias = $deltasPorDia[$id];
            ksort($dias);

            // El mínimo arranca en cero, que es el recorrido antes del primer
            // movimiento: el punto de partida forma parte de la trayectoria. Arrancando
            // en el primer movimiento, un producto que abre el ejercicio en negativo y
            // solo recibe mercancía daría un mínimo por encima de su propia apertura y
            // se quedaría sin examinar. La fecha queda en nulo mientras la curva no baje
            // de donde empezó: entonces no hay ningún movimiento que explique el mínimo.
            // El acumulado se deja en la precisión en que la cantidad existe después de
            // cada día, y no solo al final: el mínimo se decide comparando aquí dentro, y
            // un acumulado con residuo daría por rebasado el mínimo en un día en que la
            // curva no bajó, dejando además fechado un mínimo que no ocurrió.
            $acumulado = 0.0;
            $minimo = 0.0;
            $fechaMinimo = null;
            foreach ($dias as $fecha => $delta) {
                $acumulado = ClaseComprobacionStockCantidad::normalizar($acumulado + $delta);
                if ($acumulado < $minimo) {
                    $minimo = $acumulado;
                    $fechaMinimo = $fecha;
                }
            }

            $trayectorias[$id] = array(
                'saldoAlCorte' => ClaseComprobacionStockCantidad::normalizar($saldoPartida + $acumulado),
                'minimoAlcanzado' => ClaseComprobacionStockCantidad::normalizar($saldoPartida + $minimo),
                'saldoDeApertura' => $saldoPartida,
                'fechaMinimo' => $fechaMinimo,
            );
        }

        return $trayectorias;
    }

    public function mapearIncidencias($incidencias)
    {
        // @ Objetivo
        // Quedarse solo con artículo y tipo de incidencia. severidad,
        // fraccionado_es_causa y posible_causa se descartan aquí.
        //
        // El tipo es el hallazgo del componente que se cruza, no una conclusión de este
        // módulo: lo compone con su propia definición de movimiento —dos estados de
        // albarán de proveedor donde aquí se admiten cuatro, y un mínimo que arranca en
        // el primer movimiento y no en el punto de partida—, de modo que puede no
        // coincidir con los extremos que la fila lleva. Por eso viaja como lo que es y
        // nunca decide quién se examina: eso lo decide la trayectoria de aquí.
        // @ Devolvemos
        //      array [idArticulo => tipo].
        $resultado = array();
        foreach ($incidencias as $incidencia) {
            $resultado[(int) $incidencia['idArticulo']] = $incidencia['tipo'];
        }
        return $resultado;
    }

    public function conTrayectoriaEnNegativo($trayectorias)
    {
        // @ Objetivo
        // De todas las trayectorias compuestas, cuáles alcanzaron valor negativo en
        // algún momento del periodo. Es el conjunto que se examina y el que viaja al
        // ejercicio anterior: la trayectoria se compone sobre el catálogo entero, pero
        // solo estos salen.
        // @ Parametros
        //      $trayectorias -> array [idArticulo => ['minimoAlcanzado' => float, ..]],
        //          la salida de componerTrayectoria().
        // @ Devolvemos
        //      array de int, idArticulo.
        $ids = array();
        foreach ($trayectorias as $id => $trayectoria) {
            if ($trayectoria['minimoAlcanzado'] < 0) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    public function nuncaIncluidosEnElCierre($ids, $conStockPositivo)
    {
        // @ Objetivo
        // De los productos indicados, cuáles no cumplen el criterio de selección del
        // propio cierre. El cierre toma los que tienen existencias positivas; los que
        // no aparecen entre ellos son los que nunca habría tomado.
        // @ Parametros
        //      $ids -> array de int, los productos examinados.
        //      $conStockPositivo -> array de int, los que el cierre sí habría tomado
        //          (ClaseComprobacionStockConsulta::conStockPositivoEnElCierre()).
        // @ Devolvemos
        //      array de int, idArticulo de los que el cierre nunca habría tomado.
        $incluidos = array_flip($conStockPositivo);

        $resultado = array();
        foreach ($ids as $id) {
            if (!isset($incluidos[$id])) {
                $resultado[] = $id;
            }
        }
        return $resultado;
    }

    public function periodoNoConsolidado($fechaMinimo, $ventanaDias, $fechaCorte)
    {
        // @ Objetivo
        // Si el mínimo de la trayectoria cae dentro de la ventana de consolidación
        // contada hacia atrás desde la fecha de corte, el periodo aún puede cambiar.
        // ventana_dias = 0 significa sin restricción: la condición nunca se marca.
        // @ Devolvemos
        //      bool.
        if ($ventanaDias <= 0 || $fechaMinimo === null) {
            return false;
        }

        $limite = date('Y-m-d', strtotime($fechaCorte . " -{$ventanaDias} days"));
        return $fechaMinimo >= $limite;
    }

    private function consulta()
    {
        // @ Objetivo
        // La clase de consulta del módulo, una sola vez por instancia.
        // @ Devolvemos
        //      ClaseComprobacionStockConsulta.
        if ($this->consulta === null) {
            $this->consulta = new ClaseComprobacionStockConsulta();
        }
        return $this->consulta;
    }
}
