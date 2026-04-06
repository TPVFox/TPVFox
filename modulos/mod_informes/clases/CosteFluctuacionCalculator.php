<?php

/**
 * Calcula fluctuación mensual de costes de compra por artículo.
 *
 * MVP anualizado: opera sobre el rango [fecha_inicio, fecha_final]
 * usando compras reales (albprot + albprolinea).
 */
class CosteFluctuacionCalculator
{
    public function __construct(private mysqli $db) {}

    public function calcular(array $params): array
    {
        $fechaInicio = $this->escaparFecha($params['fecha_inicio'] ?? date('Y-01-01'));
        $fechaFinal  = $this->escaparFecha($params['fecha_final'] ?? date('Y-m-d'));
        $filtroFechaCompras = $this->buildFiltroRangoFechaSQL('ap.Fecha', $fechaInicio, $fechaFinal);

        $minRecepciones = max(1, (int)($params['min_recepciones'] ?? 3));
        $minMeses       = max(1, (int)($params['min_meses'] ?? 3));
        $incluirEspecial = !empty($params['incluir_proveedor_especial']) && (int)$params['incluir_proveedor_especial'] === 1;
        $familiasIds = $this->parseIdsCsv((string)($params['familias'] ?? ''));
        $familiasFiltroSQL = $this->buildFamiliasFiltroSQL($familiasIds);
        $agrupacion = (string)($params['agrupacion'] ?? 'articulo');
        $virtualHierarchy = $params['virtualHierarchy'] ?? null; // para opcion 4 con N2+ seleccionadas
        if (!in_array($agrupacion, ['articulo', 'familia', 'subfamilia'], true)) {
            $agrupacion = 'articulo';
        }

        $filtroProveedorEspecial = $incluirEspecial ? '' : "AND pv.estado != 'Especial'";

        $sql = "
            SELECT
                lp.idArticulo,
                DATE_FORMAT(ap.Fecha, '%Y-%m') AS ym,
                COUNT(DISTINCT ap.id) AS recepciones,
                SUM(COALESCE(NULLIF(lp.nunidades, 0), lp.ncant, 0)) AS unidades_total,
                SUM(lp.costeSiva * COALESCE(NULLIF(lp.nunidades, 0), lp.ncant, 0))
                    / NULLIF(SUM(COALESCE(NULLIF(lp.nunidades, 0), lp.ncant, 0)), 0) AS coste_promedio,
                MAX(ar.articulo_name) AS articulo_name
            FROM albprolinea lp
            JOIN albprot ap ON ap.id = lp.idalbpro
            JOIN proveedores pv ON pv.idProveedor = ap.idProveedor
            JOIN articulos ar ON ar.idArticulo = lp.idArticulo
                        WHERE $filtroFechaCompras
              AND ap.estado IN ('Guardado', 'Procesado', 'Facturado', 'Exportado', 'Importado')
              AND lp.estadoLinea <> 'Eliminado'
              AND COALESCE(NULLIF(lp.nunidades, 0), lp.ncant, 0) > 0
              $filtroProveedorEspecial
                            $familiasFiltroSQL
            GROUP BY lp.idArticulo, DATE_FORMAT(ap.Fecha, '%Y-%m')
            ORDER BY lp.idArticulo, ym
        ";

        $res = $this->db->query($sql);
        if (!$res) {
            return ['error' => 'Error SQL en fluctuación de costes: ' . $this->db->error];
        }

        $mesesRango = $this->generarMesesRango($fechaInicio, $fechaFinal);
        $porArticulo = [];

        while ($fila = $res->fetch_assoc()) {
            $idArticulo = (int)$fila['idArticulo'];
            $ym = $fila['ym'];
            $recepciones = (int)$fila['recepciones'];
            $costePromedio = (float)$fila['coste_promedio'];

            if (!isset($porArticulo[$idArticulo])) {
                $porArticulo[$idArticulo] = [
                    'idArticulo' => $idArticulo,
                    'articulo_name' => $fila['articulo_name'],
                    'meses' => [],
                ];
            }

            $porArticulo[$idArticulo]['meses'][$ym] = [
                'ym' => $ym,
                'recepciones' => $recepciones,
                'unidades_total' => (float)$fila['unidades_total'],
                'coste_promedio' => $costePromedio,
                'cumple_min_recepciones' => $recepciones >= $minRecepciones,
            ];
        }

        // Para opcion 4 con familias N2+, filtrar el mapa a solo las familias del conjunto seleccionado
        // (descendientes de las familias seleccionadas), para que idFamiliaDirecta apunte
        // a la familia que pertenece al subárbol seleccionado y pueda resolverse con virtualHierarchy.
        $filtroMapFamilias = $virtualHierarchy !== null ? array_keys($virtualHierarchy) : [];
        $mapFamiliasPorArticulo = $this->obtenerMapaFamiliasPorArticulo(
            array_map('intval', array_keys($porArticulo)),
            $filtroMapFamilias
        );

        // Siempre calculamos resultados por artículo individual (con desviacion_pct).
        // Estos son los que pasan los filtros min_recepciones/min_meses por producto.
        // Son la base correcta para agregar por familia usando promedio simple de %.
        $articulosPorProducto = $this->construirResultadosFluctuacion($porArticulo, $mesesRango, $minRecepciones, $minMeses);

        // Enriquecer con jerarquía familiar (real o virtual según opcion 4)
        foreach ($articulosPorProducto as &$art) {
            $fam = $mapFamiliasPorArticulo[(int)$art['idArticulo']] ?? null;

            if ($virtualHierarchy !== null && $fam) {
                // Opcion 4 con N2+ seleccionadas: usar jerarquía virtual
                $famDirecta = (int)($fam['idFamiliaDirecta'] ?? 0);
                $vh = $virtualHierarchy[$famDirecta] ?? null;
                $art['idN1']     = $vh ? (int)$vh['vN1']                 : 0;
                $art['nombreN1'] = $vh ? (string)$vh['vN1Name']           : '(Sin familia)';
                $art['idN2']     = $vh ? (int)($vh['vN2'] ?? 0)           : 0;
                $art['nombreN2'] = $vh ? (string)($vh['vN2Name'] ?? '')   : '(Sin subfamilia)';
            } else {
                $art['idN1']     = $fam ? (int)$fam['idN1']       : 0;
                $art['nombreN1'] = $fam ? (string)$fam['nombreN1'] : '(Sin familia)';
                $art['idN2']     = $fam ? (int)$fam['idN2']       : 0;
                $art['nombreN2'] = $fam ? (string)$fam['nombreN2'] : '(Sin subfamilia)';
            }
        }
        unset($art);

        // Agregados familia/subfamilia usando promedio simple de % de desviación por producto.
        // Solo incluye productos que pasaron los filtros individuales (articulosPorProducto).
        // Los artículos ya llevan idN1/idN2 enriquecidos (incluyendo jerarquía virtual para opcion 4).
        $familias = $this->construirFamiliasConSubfamilias($articulosPorProducto, $mesesRango);

        // Datos para la vista principal según agrupacion
        $articulos   = $articulosPorProducto;
        $subfamilias = [];

        usort($articulos, static function (array $a, array $b): int {
            if ($b['cv_pct'] === $a['cv_pct']) {
                return $b['rango_pct'] <=> $a['rango_pct'];
            }
            return $b['cv_pct'] <=> $a['cv_pct'];
        });

        return [
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_final' => $fechaFinal,
                'min_recepciones' => $minRecepciones,
                'min_meses' => $minMeses,
                'incluir_proveedor_especial' => $incluirEspecial,
                'familias_count' => count($familiasIds),
                'familias' => $familiasIds,
                'agrupacion' => $agrupacion,
            ],
            'resumen' => [
                'articulos_total_evaluados' => count($porArticulo),
                'articulos_con_fluctuacion' => count($articulos),
                'meses_rango' => $mesesRango,
            ],
            'articulos' => $articulos,
            'familias' => $familias,
            'subfamilias' => $subfamilias,
        ];
    }

    private function escaparFecha(string $fecha): string
    {
        return $this->db->real_escape_string($fecha);
    }

    private function buildFiltroRangoFechaSQL(string $campoFecha, string $fechaInicio, string $fechaFinal): string
    {
        return "{$campoFecha} >= '{$fechaInicio}' AND {$campoFecha} < DATE_ADD('{$fechaFinal}', INTERVAL 1 DAY)";
    }

    private function generarMesesRango(string $fechaInicio, string $fechaFinal): array
    {
        $inicio = DateTime::createFromFormat('Y-m-d', $fechaInicio);
        $fin = DateTime::createFromFormat('Y-m-d', $fechaFinal);
        if (!$inicio || !$fin) {
            return [];
        }

        $inicio->modify('first day of this month');
        $fin->modify('first day of this month');

        $meses = [];
        while ($inicio <= $fin) {
            $meses[] = $inicio->format('Y-m');
            $inicio->modify('+1 month');
        }
        return $meses;
    }

    private function mean(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }
        return array_sum($values) / count($values);
    }

    private function stdDev(array $values, ?float $mean = null): float
    {
        $n = count($values);
        if ($n === 0) {
            return 0.0;
        }
        $m = $mean ?? $this->mean($values);
        $acc = 0.0;
        foreach ($values as $v) {
            $d = $v - $m;
            $acc += ($d * $d);
        }
        return sqrt($acc / $n);
    }

    private function construirResultadosFluctuacion(array $items, array $mesesRango, int $minRecepciones, int $minMeses): array
    {
        $rows = [];
        foreach ($items as $item) {
            $costesValidos = [];
            $mesesFormateados = [];
            $maxRecepcionesMes = 0;

            foreach ($mesesRango as $ym) {
                $m = $item['meses'][$ym] ?? [
                    'ym' => $ym,
                    'recepciones' => 0,
                    'unidades_total' => 0.0,
                    'coste_promedio' => null,
                ];

                $m['cumple_min_recepciones'] = ((int)$m['recepciones']) >= $minRecepciones;

                if ((int)$m['recepciones'] > $maxRecepcionesMes) {
                    $maxRecepcionesMes = (int)$m['recepciones'];
                }

                if ($m['cumple_min_recepciones'] && $m['coste_promedio'] !== null) {
                    $costesValidos[] = (float)$m['coste_promedio'];
                }
                $mesesFormateados[] = $m;
            }

            $mesesValidos = count($costesValidos);
            if ($mesesValidos < $minMeses) {
                continue;
            }

            $avg = $this->mean($costesValidos);
            $std = $this->stdDev($costesValidos, $avg);
            $min = min($costesValidos);
            $max = max($costesValidos);
            $cvPct = $avg > 0 ? ($std / $avg) * 100 : 0.0;
            $rangoPct = $avg > 0 ? (($max - $min) / $avg) * 100 : 0.0;

            // Excluir artículos/grupos sin varianza real (precio constante).
            if ($min >= $max) {
                continue;
            }

            // Añadir desviación % normalizada a cada mes:
            // desviacion_pct = (coste_mes - media_anual) / media_anual × 100
            // Esto hace comparables artículos de distinto tamaño/precio
            // (aceite 5L vs 1L muestran el mismo % si fluctúan igual).
            foreach ($mesesFormateados as &$mes) {
                if ($mes['cumple_min_recepciones'] && $mes['coste_promedio'] !== null && $avg > 0) {
                    $mes['desviacion_pct'] = (((float)$mes['coste_promedio'] - $avg) / $avg) * 100.0;
                } else {
                    $mes['desviacion_pct'] = null;
                }
            }
            unset($mes);

            $rows[] = [
                'idArticulo'          => $item['idArticulo'],
                'articulo_name'       => $item['articulo_name'],
                'meses_validos'       => $mesesValidos,
                'min_recepciones'     => $minRecepciones,
                'max_recepciones_mes' => $maxRecepcionesMes,
                'coste_media'         => $avg,
                'coste_min'           => $min,
                'coste_max'           => $max,
                'coste_stddev'        => $std,
                'cv_pct'              => $cvPct,
                'rango_pct'           => $rangoPct,
                'meses'               => $mesesFormateados,
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            if ($b['cv_pct'] === $a['cv_pct']) {
                return $b['rango_pct'] <=> $a['rango_pct'];
            }
            return $b['cv_pct'] <=> $a['cv_pct'];
        });

        return $rows;
    }

    /**
     * Agrega los artículos ya procesados (con desviacion_pct por mes) por familia o subfamilia.
     * Usa promedio simple de % de desviación de cada producto — no promedio ponderado por precio.
     * Esto asegura que Aceite Oliva (3.55€) y Aceite Girasol (1.41€) contribuyen igual.
     *
     * @param array  $articulos  Salida de construirResultadosFluctuacion() — cada artículo tiene meses[*]['desviacion_pct']
     * @param string $nivel      'familia' (N1) o 'subfamilia' (N2)
     * @param array  $map        mapFamiliasPorArticulo
     * @param array  $mesesRango Lista de 'Y-m' del rango
     * @return array  Grupos con idGrupo, nombre, coste_media, cv_pct, meses[*]['desviacion_pct']
     */
    /**
     * Agrega artículos ya enriquecidos (idN1/idN2 seteados) por familia o subfamilia.
     * Usa promedio simple de desviacion_pct — sin ponderar por precio.
     * Los artículos deben tener idN1, nombreN1, idN2, nombreN2 ya asignados
     * (incluyendo jerarquía virtual para opcion 4).
     */
    private function agregarArticulosPorNivel(array $articulos, string $nivel, array $mesesRango): array
    {
        // Paso 1: agrupar artículos por familia/subfamilia usando los campos ya enriquecidos
        $grupos = []; // [idGrupo => ['nombre'=>..., 'arts'=>[...]]]
        foreach ($articulos as $art) {
            if ($nivel === 'familia') {
                $idGrupo = (int)($art['idN1'] ?? 0);
                $nombre  = (string)($art['nombreN1'] ?? '(Sin familia)');
            } else {
                $idGrupo = (int)($art['idN2'] ?? 0);
                $nombre  = (string)($art['nombreN2'] ?? '(Sin subfamilia)');
            }

            if (!isset($grupos[$idGrupo])) {
                $grupos[$idGrupo] = ['nombre' => $nombre, 'arts' => []];
            }
            $grupos[$idGrupo]['arts'][] = $art;
        }

        // Paso 2: para cada grupo, calcular promedio de desviacion_pct por mes
        $resultado = [];
        foreach ($grupos as $idGrupo => $g) {
            $arts = $g['arts'];
            $mesesAgr = [];

            foreach ($mesesRango as $ym) {
                $pcts = [];
                foreach ($arts as $art) {
                    foreach ($art['meses'] as $mes) {
                        if ($mes['ym'] === $ym && $mes['desviacion_pct'] !== null) {
                            $pcts[] = (float)$mes['desviacion_pct'];
                            break;
                        }
                    }
                }
                $mesesAgr[] = [
                    'ym'            => $ym,
                    'desviacion_pct' => count($pcts) > 0 ? $this->mean($pcts) : null,
                    'n_articulos'   => count($pcts),
                ];
            }

            // coste_media = promedio simple de las medias anuales de los productos
            $mediasAnuales = array_column($arts, 'coste_media');
            $costMedia = $this->mean($mediasAnuales);

            // cv_pct del grupo: stddev / mean de las desviaciones promedio mensuales
            $pctsMensuales = array_filter(
                array_column($mesesAgr, 'desviacion_pct'),
                static fn($v) => $v !== null
            );
            $pctsMensuales = array_values($pctsMensuales);
            $meanPct = count($pctsMensuales) > 0 ? $this->mean($pctsMensuales) : 0.0;
            $stdPct  = count($pctsMensuales) > 0 ? $this->stdDev($pctsMensuales, $meanPct) : 0.0;
            $cvPct   = abs($meanPct) > 0.001 ? ($stdPct / abs($meanPct)) * 100.0 : 0.0;

            $resultado[$idGrupo] = [
                'idGrupo'     => $idGrupo,
                'nombre'      => $g['nombre'],
                'meses'       => $mesesAgr,
                'coste_media' => $costMedia,
                'cv_pct'      => $cvPct,
            ];
        }

        return $resultado;
    }

    private function construirFamiliasConSubfamilias(
        array $articulos,
        array $mesesRango
    ): array {
        $gruposFamilia    = $this->agregarArticulosPorNivel($articulos, 'familia', $mesesRango);
        $gruposSubfamilia = $this->agregarArticulosPorNivel($articulos, 'subfamilia', $mesesRango);

        // Mapa subfamilia → familia derivado de los propios artículos enriquecidos
        // (funciona tanto para jerarquía real como virtual de opcion 4)
        $subToFam = [];
        foreach ($articulos as $art) {
            $idN2 = (int)($art['idN2'] ?? 0);
            if ($idN2 <= 0 || isset($subToFam[$idN2])) {
                continue;
            }
            $subToFam[$idN2] = [
                'idN1'    => (int)($art['idN1'] ?? 0),
                'nombreN1' => (string)($art['nombreN1'] ?? '(Sin familia)'),
            ];
        }

        $familias = [];
        foreach ($gruposFamilia as $fid => $f) {
            $familias[$fid] = [
                'idN1'        => $fid,
                'nombreN1'    => $f['nombre'],
                'meses'       => $f['meses'],
                'cv_pct'      => $f['cv_pct'],
                'coste_media' => $f['coste_media'],
                'subfamilias' => [],
            ];
        }

        foreach ($gruposSubfamilia as $sid => $sf) {
            $infoFam = $subToFam[$sid] ?? ['idN1' => 0, 'nombreN1' => '(Sin familia)'];
            $fid = (int)$infoFam['idN1'];
            if (!isset($familias[$fid])) {
                $familias[$fid] = [
                    'idN1'        => $fid,
                    'nombreN1'    => (string)$infoFam['nombreN1'],
                    'meses'       => [],
                    'cv_pct'      => 0.0,
                    'coste_media' => 0.0,
                    'subfamilias' => [],
                ];
            }
            $familias[$fid]['subfamilias'][] = [
                'idN2'        => $sid,
                'nombreN2'    => $sf['nombre'],
                'meses'       => $sf['meses'],
                'cv_pct'      => $sf['cv_pct'],
                'coste_media' => $sf['coste_media'],
            ];
        }

        foreach ($familias as &$fam) {
            usort($fam['subfamilias'], static fn($a, $b) => strcmp($a['nombreN2'], $b['nombreN2']));
        }
        unset($fam);

        $salida = array_values($familias);
        usort($salida, static fn($a, $b) => strcmp($a['nombreN1'], $b['nombreN1']));
        return $salida;
    }

    /**
     * @param int[] $idsArticulo
     * @param int[] $filtroIdFamilias  Si no vacío, restringe el mapa a estas familias (para opcion 4 con N2+)
     * @return array<int,array{idN1:int,nombreN1:string,idN2:int,nombreN2:string,idFamiliaDirecta:int,nombreFamiliaDirecta:string}>
     */
    private function obtenerMapaFamiliasPorArticulo(array $idsArticulo, array $filtroIdFamilias = []): array
    {
        if (empty($idsArticulo)) {
            return [];
        }

        $idsStr = implode(',', array_map('intval', $idsArticulo));
        $filtroFam = '';
        if (!empty($filtroIdFamilias)) {
            $filtroFamStr = implode(',', array_map('intval', $filtroIdFamilias));
            $filtroFam = "AND af.idFamilia IN ($filtroFamStr)";
        }
        $sql = "
            SELECT
                af.idArticulo,
                COALESCE(MIN(vj.idN1), 0) AS idN1,
                COALESCE(MAX(n1.familiaNombre), '(Sin familia)') AS nombreN1,
                COALESCE(MIN(vj.idN2), 0) AS idN2,
                COALESCE(MAX(n2.familiaNombre), '(Sin subfamilia)') AS nombreN2,
                COALESCE(MIN(vj.idFamilia), 0) AS idFamiliaDirecta,
                COALESCE(MAX(vj.familiaNombre), '(Sin familia)') AS nombreFamiliaDirecta
            FROM articulosFamilias af
            LEFT JOIN vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            LEFT JOIN vw_jerarquias_familias n1 ON n1.idFamilia = vj.idN1
            LEFT JOIN vw_jerarquias_familias n2 ON n2.idFamilia = vj.idN2
            WHERE af.idArticulo IN ($idsStr) $filtroFam
            GROUP BY af.idArticulo
        ";

        $res = $this->db->query($sql);
        if (!$res) {
            return [];
        }

        $out = [];
        while ($r = $res->fetch_assoc()) {
            $out[(int)$r['idArticulo']] = [
                'idN1' => (int)$r['idN1'],
                'nombreN1' => (string)$r['nombreN1'],
                'idN2' => (int)$r['idN2'],
                'nombreN2' => (string)$r['nombreN2'],
                'idFamiliaDirecta' => (int)$r['idFamiliaDirecta'],
                'nombreFamiliaDirecta' => (string)$r['nombreFamiliaDirecta'],
            ];
        }

        return $out;
    }

    /** @return int[] */
    private function parseIdsCsv(string $idsCsv): array
    {
        if (trim($idsCsv) === '') {
            return [];
        }
        $parts = preg_split('/\s*,\s*/', $idsCsv) ?: [];
        $ids = [];
        foreach ($parts as $p) {
            if ($p !== '' && ctype_digit($p)) {
                $id = (int)$p;
                if ($id > 0) {
                    $ids[$id] = true;
                }
            }
        }
        return array_keys($ids);
    }

    private function buildFamiliasFiltroSQL(array $ids): string
    {
        if (empty($ids)) {
            return '';
        }

        $desc = $this->expandirDescendientesFamilias($ids);
        if (empty($desc)) {
            return '';
        }

        $idsStr = implode(',', array_map('intval', $desc));
        return "AND EXISTS (
            SELECT 1
            FROM articulosFamilias af
            WHERE af.idArticulo = lp.idArticulo
              AND af.idFamilia IN ($idsStr)
        )";
    }

    /** @return int[] */
    private function expandirDescendientesFamilias(array $ids): array
    {
        $vistos = [];
        $queue = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0 && !isset($vistos[$id])) {
                $vistos[$id] = true;
                $queue[] = $id;
            }
        }

        while (!empty($queue)) {
            $batch = $queue;
            $queue = [];
            $batchStr = implode(',', array_map('intval', $batch));

            $sql = "SELECT idFamilia FROM vw_jerarquias_familias WHERE familiaPadre IN ($batchStr)";
            $res = $this->db->query($sql);
            if (!$res) {
                break;
            }

            while ($row = $res->fetch_assoc()) {
                $idHijo = (int)$row['idFamilia'];
                if ($idHijo > 0 && !isset($vistos[$idHijo])) {
                    $vistos[$idHijo] = true;
                    $queue[] = $idHijo;
                }
            }
        }

        return array_keys($vistos);
    }
}
