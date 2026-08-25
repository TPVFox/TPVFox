<?php

include_once $RutaServidor . $HostNombre . '/clases/ClaseTFModelo.php';
include_once $URLCom . '/modulos/mod_informes/clases/PosstockQueryRepository.php';
include_once $URLCom . '/modulos/mod_informes/clases/PosstockC1Detector.php';

// @ Objetivo
// Componer, para todo el catálogo, la trayectoria de existencias del producto en el
// ejercicio vigente y su estado frente al criterio: cruza el detector de existencias
// negativas y le aporta el saldo de partida ya calculado, y adjunta el marcado y las
// condiciones conocidas sin depender de que el detector haya emitido incidencia.
//
// Es la única clase del módulo que instancia el repositorio y el detector del
// informe de existencias negativas: el resto del módulo recibe de ella estructuras
// propias y no nombra ningún tipo suyo.
class ClaseComprobacionExtraccion extends TFModelo
{
    public function extraer($contextoOperacion, $modoEstricto = false, $fechaCorte = null)
    {
        // @ Objetivo
        // Recorrer el catálogo completo, componer su trayectoria de existencias y
        // devolver el estado solo de los productos cuya trayectoria alcanzó negativo.
        // @ Parametros
        //      $contextoOperacion -> array, la salida de ClaseComprobacionContexto::abrir().
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

        $repo = new PosstockQueryRepository($this->conexionBDTPV());

        $catalogoIds = array();
        foreach ($repo->queryArticulosFisicos('') as $fila) {
            $catalogoIds[] = (int) $fila['idArticulo'];
        }

        $stockBaseCache = array();
        if (!empty($catalogoIds)) {
            $idsCsv = implode(',', $catalogoIds);
            foreach ($repo->queryStockBase($fiStock, $ffStock, $idsCsv) as $fila) {
                $stockBaseCache[(int) $fila['idArticulo']] = array(
                    'saldo_acumulado' => (float) $fila['saldo_acumulado'],
                    'ultima_compra' => $fila['ultima_compra'],
                    'ultima_venta' => $fila['ultima_venta'],
                );
            }
        }

        $movimientos = $repo->queryMovimientosPeriodo($fiMov, $ffMov, '', '');

        $trayectorias = $this->componerTrayectoria($catalogoIds, $stockBaseCache, $movimientos, $modoEstricto);

        $idsNegativos = array();
        foreach ($trayectorias as $id => $trayectoria) {
            if ($trayectoria['minimoAlcanzado'] < 0) {
                $idsNegativos[] = $id;
            }
        }

        if (empty($idsNegativos)) {
            return array();
        }

        $detector = new PosstockC1Detector($this->conexionBDTPV(), $repo);
        $incidencias = $detector->detectar($fiMov, $ffMov, $fiStock, $ffStock, array(), array(), array(), $stockBaseCache);
        $tipoPorArticulo = $this->mapearIncidencias($incidencias);

        $familiaExcluidaDe = $this->familiasExcluidasDe($idsNegativos, $repo, $contextoOperacion['familiasExcluidas']);
        $nuncaIncluidoEnCierre = $this->nuncaIncluidosEnElCierre($idsNegativos);
        $conRegularizacion = $this->conRegularizacionEnElPeriodo($idsNegativos, $fiMov, $ffMov);
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

            $acumulado = 0.0;
            $minimo = null;
            $fechaMinimo = null;
            foreach ($dias as $fecha => $delta) {
                $acumulado += $delta;
                if ($minimo === null || $acumulado < $minimo) {
                    $minimo = $acumulado;
                    $fechaMinimo = $fecha;
                }
            }

            $trayectorias[$id] = array(
                'saldoAlCorte' => $saldoPartida + $acumulado,
                'minimoAlcanzado' => $saldoPartida + $minimo,
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
        // @ Devolvemos
        //      array [idArticulo => tipo].
        $resultado = array();
        foreach ($incidencias as $incidencia) {
            $resultado[(int) $incidencia['idArticulo']] = $incidencia['tipo'];
        }
        return $resultado;
    }

    private function familiasExcluidasDe($ids, $repo, $familiasExcluidasConfig)
    {
        // @ Objetivo
        // De los productos indicados, cuáles pertenecen a una familia excluida del
        // cierre (incluidas sus subfamilias).
        // @ Devolvemos
        //      array [idArticulo => true] de los que pertenecen a alguna.
        if (empty($familiasExcluidasConfig)) {
            return array();
        }

        $idsExpandido = $repo->expandirFamilias($familiasExcluidasConfig);
        if ($idsExpandido === '') {
            return array();
        }

        $idsCsv = implode(',', array_map('intval', $ids));
        $sql = "SELECT DISTINCT idArticulo FROM articulosFamilias "
            . "WHERE idArticulo IN ($idsCsv) AND idFamilia IN ($idsExpandido)";

        $resultado = array();
        $filas = $this->consulta($sql)['datos'];
        if (is_array($filas)) {
            foreach ($filas as $fila) {
                $resultado[(int) $fila['idArticulo']] = true;
            }
        }
        return $resultado;
    }

    private function nuncaIncluidosEnElCierre($ids)
    {
        // @ Objetivo
        // De los productos indicados, cuáles no cumplen el criterio de selección del
        // propio cierre: stockOn > 0 en idTienda = 1.
        // @ Devolvemos
        //      array [idArticulo => true] de los que el cierre nunca habría tomado.
        $idsCsv = implode(',', array_map('intval', $ids));
        $sql = "SELECT idArticulo FROM articulosStocks "
            . "WHERE idArticulo IN ($idsCsv) AND idTienda = 1 AND stockOn > 0";

        $incluidos = array();
        $filas = $this->consulta($sql)['datos'];
        if (is_array($filas)) {
            foreach ($filas as $fila) {
                $incluidos[(int) $fila['idArticulo']] = true;
            }
        }

        $resultado = array();
        foreach ($ids as $id) {
            if (!isset($incluidos[$id])) {
                $resultado[$id] = true;
            }
        }
        return $resultado;
    }

    private function conRegularizacionEnElPeriodo($ids, $fiMov, $ffMov)
    {
        // @ Objetivo
        // De los productos indicados, cuáles tienen una regularización activa fechada
        // dentro del periodo de movimientos.
        // @ Devolvemos
        //      array [idArticulo => true] de los que tienen alguna.
        $idsCsv = implode(',', array_map('intval', $ids));
        $sql = "SELECT DISTINCT idArticulo FROM stocksRegularizacion "
            . "WHERE idArticulo IN ($idsCsv) AND estado = 1 "
            . "AND fechaRegularizacion BETWEEN '$fiMov 00:00:00' AND '$ffMov 23:59:59'";

        $resultado = array();
        $filas = $this->consulta($sql)['datos'];
        if (is_array($filas)) {
            foreach ($filas as $fila) {
                $resultado[(int) $fila['idArticulo']] = true;
            }
        }
        return $resultado;
    }

    private function periodoNoConsolidado($fechaMinimo, $ventanaDias, $fechaCorte)
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
}
