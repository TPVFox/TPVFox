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

        $mapFamiliasPorArticulo = $this->obtenerMapaFamiliasPorArticulo(array_map('intval', array_keys($porArticulo)));

        $items = $porArticulo;
        if ($agrupacion !== 'articulo') {
            $items = $this->agruparSeriesPorNivel($porArticulo, $agrupacion, $mapFamiliasPorArticulo);
        }
        $articulos = $this->construirResultadosFluctuacion($items, $mesesRango, $minRecepciones, $minMeses);
        $familias = [];
        $subfamilias = [];
        if ($agrupacion === 'familia') {
            $familias = $this->construirFamiliasConSubfamilias(
                $porArticulo,
                $mapFamiliasPorArticulo,
                $mesesRango,
                $minRecepciones,
                $minMeses
            );
        } elseif ($agrupacion === 'subfamilia') {
            $subfamilias = $this->construirSubfamiliasConFamiliasHijas(
                $porArticulo,
                $mapFamiliasPorArticulo,
                $mesesRango,
                $minRecepciones,
                $minMeses
            );
        }

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
                'articulos_total_evaluados' => $agrupacion === 'familia'
                    ? count($familias)
                    : ($agrupacion === 'subfamilia' ? count($subfamilias) : count($items)),
                'articulos_con_fluctuacion' => $agrupacion === 'familia'
                    ? count($familias)
                    : ($agrupacion === 'subfamilia' ? count($subfamilias) : count($articulos)),
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

            $rows[] = [
                'idArticulo' => $item['idArticulo'],
                'articulo_name' => $item['articulo_name'],
                'meses_validos' => $mesesValidos,
                'min_recepciones' => $minRecepciones,
                'max_recepciones_mes' => $maxRecepcionesMes,
                'coste_media' => $avg,
                'coste_min' => $min,
                'coste_max' => $max,
                'coste_stddev' => $std,
                'cv_pct' => $cvPct,
                'rango_pct' => $rangoPct,
                'meses' => $mesesFormateados,
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

    private function agruparSeriesPorNivel(array $porArticulo, string $agrupacion, array $map): array
    {
        if (empty($porArticulo)) {
            return [];
        }

        $grupos = [];
        foreach ($porArticulo as $idArticulo => $item) {
            $fam = $map[(int)$idArticulo] ?? [
                'idN1' => 0,
                'nombreN1' => '(Sin familia)',
                'idN2' => 0,
                'nombreN2' => '(Sin subfamilia)',
            ];

            if ($agrupacion === 'familia') {
                $gid = (int)$fam['idN1'];
                $gname = (string)$fam['nombreN1'];
                if ($gid <= 0) {
                    $gid = 0;
                    $gname = '(Sin familia)';
                }
            } else {
                $gid = (int)$fam['idN2'];
                $gname = (string)$fam['nombreN2'];
                if ($gid <= 0) {
                    $gid = 0;
                    $gname = '(Sin subfamilia)';
                }
            }

            $key = $agrupacion . ':' . $gid;
            if (!isset($grupos[$key])) {
                $grupos[$key] = [
                    'idArticulo' => $gid,
                    'articulo_name' => $gname,
                    'meses' => [],
                ];
            }

            foreach (($item['meses'] ?? []) as $ym => $m) {
                if (!isset($grupos[$key]['meses'][$ym])) {
                    $grupos[$key]['meses'][$ym] = [
                        'ym' => $ym,
                        'recepciones' => 0,
                        'unidades_total' => 0.0,
                        '_coste_x_unidades' => 0.0,
                    ];
                }

                $u = (float)($m['unidades_total'] ?? 0);
                $c = $m['coste_promedio'];
                // En agrupaciones no debemos sumar recepciones entre artículos,
                // porque puede inflar el conteo del mes en la familia/subfamilia.
                // Usamos el máximo mensual observado para mantener un umbral estable.
                $grupos[$key]['meses'][$ym]['recepciones'] = max(
                    (int)$grupos[$key]['meses'][$ym]['recepciones'],
                    (int)($m['recepciones'] ?? 0)
                );
                $grupos[$key]['meses'][$ym]['unidades_total'] += $u;
                if ($c !== null) {
                    $grupos[$key]['meses'][$ym]['_coste_x_unidades'] += ((float)$c * $u);
                }
            }
        }

        foreach ($grupos as &$g) {
            foreach ($g['meses'] as &$m) {
                $u = (float)$m['unidades_total'];
                if ($u > 0) {
                    $m['coste_promedio'] = (float)$m['_coste_x_unidades'] / $u;
                } else {
                    $m['coste_promedio'] = null;
                }
                unset($m['_coste_x_unidades']);
            }
            unset($m);
        }
        unset($g);

        return $grupos;
    }

    private function construirFamiliasConSubfamilias(
        array $porArticulo,
        array $mapFamiliasPorArticulo,
        array $mesesRango,
        int $minRecepciones,
        int $minMeses
    ): array {
        $seriesFamilia = $this->agruparSeriesPorNivel($porArticulo, 'familia', $mapFamiliasPorArticulo);
        $filasFamilia = $this->construirResultadosFluctuacion($seriesFamilia, $mesesRango, $minRecepciones, $minMeses);

        $seriesSubfamilia = $this->agruparSeriesPorNivel($porArticulo, 'subfamilia', $mapFamiliasPorArticulo);
        $filasSubfamilia = $this->construirResultadosFluctuacion($seriesSubfamilia, $mesesRango, $minRecepciones, $minMeses);

        $subToFam = [];
        foreach ($mapFamiliasPorArticulo as $m) {
            $idN2 = (int)($m['idN2'] ?? 0);
            if ($idN2 <= 0 || isset($subToFam[$idN2])) {
                continue;
            }
            $subToFam[$idN2] = [
                'idN1' => (int)($m['idN1'] ?? 0),
                'nombreN1' => (string)($m['nombreN1'] ?? '(Sin familia)'),
            ];
        }

        $familias = [];
        foreach ($filasFamilia as $f) {
            $fid = (int)($f['idArticulo'] ?? 0);
            $familias[$fid] = [
                'idN1' => $fid,
                'nombreN1' => (string)($f['articulo_name'] ?? '(Sin familia)'),
                'meses' => $f['meses'] ?? [],
                'subfamilias' => [],
            ];
        }

        foreach ($filasSubfamilia as $sf) {
            $sid = (int)($sf['idArticulo'] ?? 0);
            $infoFam = $subToFam[$sid] ?? ['idN1' => 0, 'nombreN1' => '(Sin familia)'];
            $fid = (int)$infoFam['idN1'];
            if (!isset($familias[$fid])) {
                $familias[$fid] = [
                    'idN1' => $fid,
                    'nombreN1' => (string)$infoFam['nombreN1'],
                    'meses' => [],
                    'subfamilias' => [],
                ];
            }
            $familias[$fid]['subfamilias'][] = [
                'idN2' => $sid,
                'nombreN2' => (string)($sf['articulo_name'] ?? '(Sin subfamilia)'),
                'meses' => $sf['meses'] ?? [],
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

    private function construirSubfamiliasConFamiliasHijas(
        array $porArticulo,
        array $mapFamiliasPorArticulo,
        array $mesesRango,
        int $minRecepciones,
        int $minMeses
    ): array {
        $seriesSubfamilia = $this->agruparSeriesPorNivel($porArticulo, 'subfamilia', $mapFamiliasPorArticulo);
        $filasSubfamilia = $this->construirResultadosFluctuacion($seriesSubfamilia, $mesesRango, $minRecepciones, $minMeses);

        $seriesFamiliaDirecta = [];
        foreach ($porArticulo as $idArticulo => $item) {
            $fam = $mapFamiliasPorArticulo[(int)$idArticulo] ?? [
                'idN2' => 0,
                'nombreN2' => '(Sin subfamilia)',
                'idFamiliaDirecta' => 0,
                'nombreFamiliaDirecta' => '(Sin familia)',
            ];

            $idN2 = (int)($fam['idN2'] ?? 0);
            $nombreN2 = (string)($fam['nombreN2'] ?? '(Sin subfamilia)');
            if ($idN2 <= 0) {
                $idN2 = 0;
                $nombreN2 = '(Sin subfamilia)';
            }

            $idFam = (int)($fam['idFamiliaDirecta'] ?? 0);
            $nombreFam = (string)($fam['nombreFamiliaDirecta'] ?? '(Sin familia)');
            if ($idFam <= 0) {
                $idFam = 0;
                $nombreFam = '(Sin familia)';
            }

            $key = $idN2 . ':' . $idFam;
            if (!isset($seriesFamiliaDirecta[$key])) {
                $seriesFamiliaDirecta[$key] = [
                    'idArticulo' => $idFam,
                    'articulo_name' => $nombreFam,
                    'idN2' => $idN2,
                    'nombreN2' => $nombreN2,
                    'meses' => [],
                ];
            }

            foreach (($item['meses'] ?? []) as $ym => $m) {
                if (!isset($seriesFamiliaDirecta[$key]['meses'][$ym])) {
                    $seriesFamiliaDirecta[$key]['meses'][$ym] = [
                        'ym' => $ym,
                        'recepciones' => 0,
                        'unidades_total' => 0.0,
                        '_coste_x_unidades' => 0.0,
                    ];
                }

                $u = (float)($m['unidades_total'] ?? 0);
                $c = $m['coste_promedio'];
                $seriesFamiliaDirecta[$key]['meses'][$ym]['recepciones'] = max(
                    (int)$seriesFamiliaDirecta[$key]['meses'][$ym]['recepciones'],
                    (int)($m['recepciones'] ?? 0)
                );
                $seriesFamiliaDirecta[$key]['meses'][$ym]['unidades_total'] += $u;
                if ($c !== null) {
                    $seriesFamiliaDirecta[$key]['meses'][$ym]['_coste_x_unidades'] += ((float)$c * $u);
                }
            }
        }

        foreach ($seriesFamiliaDirecta as &$g) {
            foreach ($g['meses'] as &$m) {
                $u = (float)$m['unidades_total'];
                $m['coste_promedio'] = $u > 0 ? (float)$m['_coste_x_unidades'] / $u : null;
                unset($m['_coste_x_unidades']);
            }
            unset($m);
        }
        unset($g);

        $filasFamiliaDirecta = $this->construirResultadosFluctuacion(
            $seriesFamiliaDirecta,
            $mesesRango,
            $minRecepciones,
            $minMeses
        );

        $subfamilias = [];
        foreach ($filasSubfamilia as $sf) {
            $sid = (int)($sf['idArticulo'] ?? 0);
            $subfamilias[$sid] = [
                'idN2' => $sid,
                'nombreN2' => (string)($sf['articulo_name'] ?? '(Sin subfamilia)'),
                'meses' => $sf['meses'] ?? [],
                'familias_hijas' => [],
            ];
        }

        foreach ($filasFamiliaDirecta as $ff) {
            $nombreFam = (string)($ff['articulo_name'] ?? '(Sin familia)');
            $sid = null;
            foreach ($seriesFamiliaDirecta as $raw) {
                if (
                    (int)$raw['idArticulo'] === (int)($ff['idArticulo'] ?? 0)
                    && (string)$raw['articulo_name'] === $nombreFam
                ) {
                    $sid = (int)($raw['idN2'] ?? 0);
                    break;
                }
            }
            if ($sid === null) {
                continue;
            }
            if (!isset($subfamilias[$sid])) {
                $subfamilias[$sid] = [
                    'idN2' => $sid,
                    'nombreN2' => '(Sin subfamilia)',
                    'meses' => [],
                    'familias_hijas' => [],
                ];
            }
            $subfamilias[$sid]['familias_hijas'][] = [
                'idFamilia' => (int)($ff['idArticulo'] ?? 0),
                'familiaNombre' => $nombreFam,
                'meses' => $ff['meses'] ?? [],
            ];
        }

        foreach ($subfamilias as &$sf) {
            usort($sf['familias_hijas'], static fn($a, $b) => strcmp($a['familiaNombre'], $b['familiaNombre']));
        }
        unset($sf);

        $out = array_values($subfamilias);
        usort($out, static fn($a, $b) => strcmp($a['nombreN2'], $b['nombreN2']));
        return $out;
    }

    /** @return array<int,array{idN1:int,nombreN1:string,idN2:int,nombreN2:string,idFamiliaDirecta:int,nombreFamiliaDirecta:string}> */
    private function obtenerMapaFamiliasPorArticulo(array $idsArticulo): array
    {
        if (empty($idsArticulo)) {
            return [];
        }

        $idsStr = implode(',', array_map('intval', $idsArticulo));
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
            WHERE af.idArticulo IN ($idsStr)
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
