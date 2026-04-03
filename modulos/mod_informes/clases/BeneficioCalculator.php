<?php

/**
 * BeneficioCalculator — extracción de BeneficioFamilias (Informe 6).
 *
 * Extraído de ClaseInformes::BeneficioFamilias en Fase 6 del plan de refactorización.
 * Recibe la conexión mysqli y los parámetros, devuelve la estructura completa
 * de familias + resumen global para el informe de beneficio por familia.
 */
class BeneficioCalculator
{
    public function __construct(private mysqli $db) {}

    public function calcular(array $parametros): array
    {
        $BDTpv = $this->db;
        // @ Objetivo
        // Suma ventas y costes (ultimoCoste) por jerarquía de familias para calcular
        // beneficio bruto y margen porcentual en el período.
        // AVISO: el coste es ultimoCoste en el momento de ejecutar el informe, no histórico.
        // @ Parámetros: Finicio (Y-m-d), Ffinal (Y-m-d)

        
        $fechaInicio = $BDTpv->real_escape_string($parametros['Finicio']);
        $fechaFinal  = $BDTpv->real_escape_string($parametros['Ffinal']);

        $filtroN1         = '';
        $virtualHierarchy = null;
        $needsIdFamilia   = false;
        if ((int)($parametros['opcion'] ?? 0) === 4) {
            $ids = array_values(array_filter(array_map('intval', explode(',', $parametros['familias'] ?? ''))));
            if (!empty($ids)) {
                $fop4             = InformesFiltros::buildFiltroOp4($BDTpv, $ids);
                $filtroN1         = $fop4['filtroSQL'];
                $virtualHierarchy = $fop4['virtualHierarchy'];
                $needsIdFamilia   = $fop4['needsIdFamilia'];
            }
        }

        // Coste medio ponderado de compra en el período por artículo (excluye proveedores Especial).
        // Si no hubo compra en el período se usa ultimoCoste como fallback (ver COALESCE en el UNION).
        // Solo líneas con nunidades > 0 (excluye devoluciones/abonos que distorsionan el PMP).
        $sqlCoste = "
            SELECT lp.idArticulo,
                   SUM(lp.costeSiva * lp.nunidades) / SUM(lp.nunidades) AS coste_periodo
            FROM albprolinea lp
            JOIN albprot ap ON ap.id = lp.idalbpro
            JOIN proveedores pv ON pv.idProveedor = ap.idProveedor
            WHERE ap.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND ap.estado IN ('Guardado', 'Procesado', 'Facturado')
              AND lp.estadoLinea <> 'Eliminado'
              AND lp.nunidades > 0
              AND pv.estado != 'Especial'
            GROUP BY lp.idArticulo
        ";
        $smtC = $BDTpv->query($sqlCoste);
        $costePeriodo = [];
        while ($rc = $smtC->fetch_assoc()) {
            $costePeriodo[(int)$rc['idArticulo']] = (float)$rc['coste_periodo'];
        }

        // Mermas declaradas: albaranes de clientes Especial en el período.
        // Representan unidades que salieron del inventario sin generar ingreso (caducados,
        // descartes, tirados). Su coste se resta del beneficio.
        // Cuando hay filtro de familias (opción 4) la merma también debe limitarse
        // a los artículos de esas familias, igual que las ventas.
        $mermaFamiliaJoin  = '';
        $mermaFamiliaWhere = '';
        if ($filtroN1 !== '') {
            if ($virtualHierarchy === null) {
                // filtro por idN1
                $mermaFamiliaJoin  = 'LEFT JOIN articulosFamilias afM ON afM.idArticulo = l.idArticulo
            LEFT JOIN vw_jerarquias_familias vjM ON vjM.idFamilia = afM.idFamilia';
                $mermaFamiliaWhere = str_replace('vj.idN1', 'vjM.idN1', $filtroN1);
            } else {
                // filtro por idFamilia descendiente
                preg_match('/IN\s*\(([^)]+)\)/', $filtroN1, $mMerma);
                $mermaIds          = $mMerma[1] ?? '0';
                $mermaFamiliaJoin  = 'LEFT JOIN articulosFamilias afM ON afM.idArticulo = l.idArticulo';
                $mermaFamiliaWhere = "AND afM.idFamilia IN ($mermaIds)";
            }
        }

        $sqlMerma = "
            SELECT
                l.idArticulo,
                SUM(l.nunidades) AS unidades_merma
            FROM albclilinea l
            JOIN albclit h ON h.id = l.idalbcli
            JOIN clientes cl ON cl.idClientes = h.idCliente
            $mermaFamiliaJoin
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND h.estado IN ('Guardado', 'Procesado')
              AND l.estadoLinea = 'Activo'
              AND cl.estado = 'Especial'
              $mermaFamiliaWhere
            GROUP BY l.idArticulo
        ";
        $smtM = $BDTpv->query($sqlMerma);
        $mermasPorArticulo = [];
        while ($rm = $smtM->fetch_assoc()) {
            $mermasPorArticulo[(int)$rm['idArticulo']] = (float)$rm['unidades_merma'];
        }

        // Cuando se usa jerarquía virtual (familia no-N1 seleccionada) necesitamos af.idFamilia
        // en el SELECT y GROUP BY para poder asignar la fila al virtual N1/N2 correcto.
        $selectFamId  = $needsIdFamilia ? ', af.idFamilia AS familiaDirecta' : ', NULL AS familiaDirecta';
        $groupByFamId = $needsIdFamilia ? ', af.idFamilia'                   : '';

        $sql = "
            SELECT
                vj.idN1,
                MAX(n1.familiaNombre)                                        AS nombreN1,
                vj.idN2,
                MAX(n2.familiaNombre)                                        AS nombreN2,
                l.idArticulo,
                MAX(ar.articulo_name)                                        AS articulo_name,
                MAX(ar.tipo)                                                 AS tipo,
                l.precioCiva / (1 + l.iva / 100)                            AS pvpSiva,
                MAX(ar.ultimoCoste)                                          AS ultimoCoste,
                SUM(l.nunidades)                                             AS totalUnidades,
                SUM(l.precioCiva / (1 + l.iva / 100) * l.nunidades)         AS totalVenta,
                COUNT(DISTINCT l.idalbcli)                                   AS num_documentos
                $selectFamId
            FROM albclilinea l
            JOIN albclit h ON h.id = l.idalbcli
            JOIN articulos ar ON ar.idArticulo = l.idArticulo
            LEFT JOIN clientes cl ON cl.idClientes = h.idCliente
            LEFT JOIN articulosFamilias af ON af.idArticulo = l.idArticulo
            LEFT JOIN vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            LEFT JOIN vw_jerarquias_familias n1 ON n1.idFamilia = vj.idN1
            LEFT JOIN vw_jerarquias_familias n2 ON n2.idFamilia = vj.idN2
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND h.estado IN ('Guardado', 'Procesado')
              AND l.estadoLinea = 'Activo'
              AND (h.idCliente = 0 OR cl.estado != 'Especial')
              $filtroN1
            GROUP BY vj.idN1, vj.idN2, l.idArticulo, l.precioCiva, l.iva $groupByFamId

            UNION ALL

            SELECT
                vj.idN1,
                MAX(n1.familiaNombre)                                        AS nombreN1,
                vj.idN2,
                MAX(n2.familiaNombre)                                        AS nombreN2,
                l.idArticulo,
                MAX(ar.articulo_name)                                        AS articulo_name,
                MAX(ar.tipo)                                                 AS tipo,
                l.precioCiva / (1 + l.iva / 100)                            AS pvpSiva,
                MAX(ar.ultimoCoste)                                          AS ultimoCoste,
                SUM(l.nunidades)                                             AS totalUnidades,
                SUM(l.precioCiva / (1 + l.iva / 100) * l.nunidades)         AS totalVenta,
                COUNT(DISTINCT l.idticketst)                                 AS num_documentos
                $selectFamId
            FROM ticketslinea l
            JOIN ticketst h ON h.id = l.idticketst
            JOIN articulos ar ON ar.idArticulo = l.idArticulo
            LEFT JOIN clientes cl ON cl.idClientes = h.idCliente
            LEFT JOIN articulosFamilias af ON af.idArticulo = l.idArticulo
            LEFT JOIN vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            LEFT JOIN vw_jerarquias_familias n1 ON n1.idFamilia = vj.idN1
            LEFT JOIN vw_jerarquias_familias n2 ON n2.idFamilia = vj.idN2
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND h.estado IN ('Cobrado', 'Cerrado')
              AND l.estadoLinea = 'Activo'
              AND (h.idCliente = 0 OR cl.estado != 'Especial')
              $filtroN1
            GROUP BY vj.idN1, vj.idN2, l.idArticulo, l.precioCiva, l.iva $groupByFamId
            ORDER BY nombreN1, nombreN2, idArticulo
        ";

        $smt    = $BDTpv->query($sql);
        $lineas = [];
        while ($row = $smt->fetch_assoc()) {
            $lineas[] = $row;
        }

        $familias = [];

        foreach ($lineas as $fila) {
            // Si se usa jerarquía virtual, remapear N1/N2 a partir del idFamilia real del artículo
            if ($virtualHierarchy !== null && isset($fila['familiaDirecta'])) {
                $famId = (int)$fila['familiaDirecta'];
                $vh    = $virtualHierarchy[$famId] ?? null;
                if ($vh !== null) {
                    $idN1     = $vh['vN1'];
                    $nombreN1 = $vh['vN1Name'];
                    $idN2     = $vh['vN2'];
                    $keyN2    = $idN2 !== null ? $idN2 : '__sin_n2__';
                    $labelN2  = $idN2 !== null ? ($vh['vN2Name'] ?? 'Sin subfamilia') : '(Sin subfamilia)';
                } else {
                    // Familia fuera del virtual map (no debería ocurrir con el filtro SQL)
                    $idN1 = '__sin_familia__';
                    $nombreN1 = 'Sin familia';
                    $idN2 = null;
                    $keyN2 = '__sin_n2__';
                    $labelN2 = '(Sin subfamilia)';
                }
            } else {
                $idN1     = $fila['idN1'] !== null ? (int)$fila['idN1'] : '__sin_familia__';
                $nombreN1 = $fila['nombreN1'] !== null ? $fila['nombreN1'] : 'Sin familia';
                $idN2     = $fila['idN2'] !== null ? (int)$fila['idN2'] : null;
                $keyN2    = $idN2 !== null ? $idN2 : '__sin_n2__';
                $labelN2  = $idN2 !== null ? ($fila['nombreN2'] ?? 'Sin subfamilia') : '(Sin subfamilia)';
            }
            $idArt    = (int)$fila['idArticulo'];
            // Coste medio ponderado del período si hubo compra; si no, ultimoCoste actual
            $costeUsar = isset($costePeriodo[$idArt]) ? $costePeriodo[$idArt] : (float)$fila['ultimoCoste'];
            $tv       = (float)$fila['totalVenta'];
            $tc       = $costeUsar * (float)$fila['totalUnidades'];

            if (!isset($familias[$idN1])) {
                $familias[$idN1] = [
                    'idN1'        => $idN1,
                    'nombreN1'    => $nombreN1,
                    'subfamilias' => []
                ];
            }

            if (!isset($familias[$idN1]['subfamilias'][$keyN2])) {
                $familias[$idN1]['subfamilias'][$keyN2] = [
                    'idN2'      => $idN2,
                    'nombreN2'  => $labelN2,
                    'articulos' => []
                ];
            }

            $art = &$familias[$idN1]['subfamilias'][$keyN2]['articulos'][$idArt];

            $udsMerma   = $mermasPorArticulo[$idArt] ?? 0.0;
            $valorMerma = $udsMerma * $costeUsar;

            if (!isset($art['idArticulo'])) {
                $art = [
                    'idArticulo'       => $idArt,
                    'articulo_name'    => $fila['articulo_name'],
                    'tipo'             => $fila['tipo'],
                    'totalUnidades'    => (float)$fila['totalUnidades'],
                    'pvpSiva'          => (float)$fila['pvpSiva'],
                    'costeUsado'       => $costeUsar,
                    'coste_es_periodo' => isset($costePeriodo[$idArt]),
                    'totalVenta'       => $tv,
                    'totalCoste'       => $tc,
                    'udsMerma'         => $udsMerma,
                    'valorMerma'       => $valorMerma,
                    'num_ventas'       => (int)$fila['num_documentos']
                ];
            } else {
                $art['totalUnidades'] += (float)$fila['totalUnidades'];
                $art['totalVenta']    += $tv;
                $art['totalCoste']    += $costeUsar * (float)$fila['totalUnidades'];
                $art['num_ventas']    += (int)$fila['num_documentos'];
                // udsMerma y valorMerma ya están fijados por idArticulo, no acumulan por precio
            }
            $art['pvpSiva']    = $art['totalUnidades'] > 0
                ? $art['totalVenta'] / $art['totalUnidades']
                : 0;
            $art['beneficio']  = $art['totalVenta'] - $art['totalCoste'] - $art['valorMerma'];
            $art['margen_pct'] = $art['totalVenta'] > 0
                ? round($art['beneficio'] / $art['totalVenta'] * 100, 2)
                : 0;

            unset($art);
        }

        $resultado = [];
        foreach ($familias as $familia) {
            $tvN1    = 0;
            $tcN1    = 0;
            $tmN1    = 0;
            $refsN1  = [];
            $sfs     = [];

            foreach ($familia['subfamilias'] as $sf) {
                $arts  = array_values($sf['articulos']);
                $tvN2  = 0;
                $tcN2  = 0;
                $tmN2  = 0;
                foreach ($arts as $art) {
                    $tvN2 += $art['totalVenta'];
                    $tcN2 += $art['totalCoste'];
                    $tmN2 += $art['valorMerma'];
                    $refsN1[$art['idArticulo']] = true;
                }
                $tvN1 += $tvN2;
                $tcN1 += $tcN2;
                $tmN1 += $tmN2;

                $benN2 = $tvN2 - $tcN2 - $tmN2;
                $arts_sorted = $arts;
                usort($arts_sorted, fn($a, $b) => $b['totalVenta'] <=> $a['totalVenta']);
                $sfs[] = [
                    'idN2'            => $sf['idN2'],
                    'nombreN2'        => $sf['nombreN2'],
                    'totalVenta'      => $tvN2,
                    'totalCoste'      => $tcN2,
                    'totalMerma'      => $tmN2,
                    'beneficio'       => $benN2,
                    'margen_pct'      => $tvN2 > 0 ? round($benN2 / $tvN2 * 100, 2) : 0,
                    'num_referencias' => count($arts_sorted),
                    'articulos'       => $arts_sorted
                ];
            }
            usort($sfs, fn($a, $b) => $b['totalVenta'] <=> $a['totalVenta']);

            $benN1 = $tvN1 - $tcN1 - $tmN1;
            $resultado[] = [
                'idN1'            => $familia['idN1'],
                'nombreN1'        => $familia['nombreN1'],
                'totalVenta'      => $tvN1,
                'totalCoste'      => $tcN1,
                'totalMerma'      => $tmN1,
                'beneficio'       => $benN1,
                'margen_pct'      => $tvN1 > 0 ? round($benN1 / $tvN1 * 100, 2) : 0,
                'num_referencias' => count($refsN1),
                'subfamilias'     => $sfs
            ];
        }
        usort($resultado, fn($a, $b) => $b['totalVenta'] <=> $a['totalVenta']);

        // ── Resumen global ─────────────────────────────────────────────────────
        // Iteramos por artículo único para evitar duplicar artículos en varias familias.
        $gtVenta = 0;
        $gtCoste = 0;
        $gtMerma = 0;
        $articulosConVentas = [];
        foreach ($resultado as $fam) {
            foreach ($fam['subfamilias'] as $sf) {
                foreach ($sf['articulos'] as $art) {
                    $idArt = $art['idArticulo'];
                    if (isset($articulosConVentas[$idArt])) continue;
                    $articulosConVentas[$idArt] = true;
                    $gtVenta += $art['totalVenta'];
                    $gtCoste += $art['totalCoste'];
                    $gtMerma += $art['valorMerma'];
                }
            }
        }

        // Artículos con merma en el período pero sin ninguna venta capturada
        // (no aparecen en la tabla de familias)
        $mermaSinVentas = [];
        $gtMermaSinVentas = 0;
        foreach ($mermasPorArticulo as $idArt => $uds) {
            if (isset($articulosConVentas[$idArt])) continue;
            // Obtener nombre y ultimoCoste
            $rArt = $BDTpv->query(
                "SELECT articulo_name, ultimoCoste FROM articulos WHERE idArticulo = $idArt LIMIT 1"
            );
            if (!$rArt || $rArt->num_rows === 0) continue;
            $datoArt = $rArt->fetch_assoc();
            $costeArt = isset($costePeriodo[$idArt]) ? $costePeriodo[$idArt] : (float)$datoArt['ultimoCoste'];
            $valor = $uds * $costeArt;
            $gtMermaSinVentas += $valor;
            $mermaSinVentas[] = [
                'idArticulo'    => $idArt,
                'articulo_name' => $datoArt['articulo_name'],
                'udsMerma'      => $uds,
                'costeUsar'     => $costeArt,
                'valorMerma'    => $valor,
            ];
        }
        usort($mermaSinVentas, fn($a, $b) => $b['valorMerma'] <=> $a['valorMerma']);

        $gtMermaTotal  = $gtMerma + $gtMermaSinVentas;
        $gtBeneficio   = $gtVenta - $gtCoste - $gtMermaTotal;
        $gtMargen      = $gtVenta > 0 ? round($gtBeneficio / $gtVenta * 100, 2) : 0;

        // ── Vista de flujo: compras reales del período ─────────────────────
        // Suma directa de albaranes de compra (sin estimaciones de PMP).
        // Cuando hay filtro de familias (opción 4), se aplica también aquí para coherencia.

        // Fragmentos SQL para aplicar el filtro de familia a las consultas de flujo.
        // Las consultas globales no tienen JOIN de familias, hay que añadirlos cuando se filtra.
        // Las consultas per-N1 ya tienen los JOINs af/vj, solo hay que añadir el WHERE.
        // Con jerarquía virtual, agrupamos por af.idFamilia y mapeamos al virtual N1 en PHP.
        $filtroFlujoGlobalJoinC  = ''; // JOIN extra para compras globales (alias afF/vjF)
        $filtroFlujoGlobalWhereC = ''; // WHERE extra para compras globales
        $filtroFlujoGlobalJoinV  = ''; // JOIN extra para ventas globales (alias afFv/vjFv)
        $filtroFlujoGlobalWhereV = ''; // WHERE extra para ventas globales
        $filtroFlujoN1Where      = ''; // WHERE extra para consultas per-N1 (ya tienen af/vj)
        $flujoGroupByKey         = 'vj.idN1'; // columna de agrupación per-N1

        if ($filtroN1 !== '') {
            if ($virtualHierarchy === null) {
                // Filtro normal por idN1.
                // Usamos EXISTS en lugar de JOIN para evitar multiplicar filas cuando un artículo
                // tiene varias entradas en articulosFamilias que mapean al mismo idN1
                // (ej: artículo asignado a 2 subfamilias del mismo N1 → JOIN generaría 2 filas).
                preg_match('/IN\s*\(([^)]+)\)/', $filtroN1, $mN1);
                $n1Str = $mN1[1] ?? '0';
                $filtroFlujoGlobalJoinC  = '';
                $filtroFlujoGlobalWhereC = "AND EXISTS (
                    SELECT 1 FROM articulosFamilias afF
                    JOIN vw_jerarquias_familias vjF ON vjF.idFamilia = afF.idFamilia
                    WHERE afF.idArticulo = lp.idArticulo AND vjF.idN1 IN ($n1Str)
                )";
                $filtroFlujoGlobalJoinV  = '';
                $filtroFlujoGlobalWhereV = "AND EXISTS (
                    SELECT 1 FROM articulosFamilias afFv
                    JOIN vw_jerarquias_familias vjFv ON vjFv.idFamilia = afFv.idFamilia
                    WHERE afFv.idArticulo = l.idArticulo AND vjFv.idN1 IN ($n1Str)
                )";
                $filtroFlujoN1Where      = $filtroN1;
                $flujoGroupByKey         = 'vj.idN1';
            } else {
                // Filtro por idFamilia descendiente: solo necesita JOIN articulosFamilias
                // Extraemos la lista de IDs del fragmento "AND af.idFamilia IN (...)"
                preg_match('/IN\s*\(([^)]+)\)/', $filtroN1, $m);
                $descStr = $m[1] ?? '0';
                $filtroFlujoGlobalJoinC  = '';
                $filtroFlujoGlobalWhereC = "AND EXISTS (SELECT 1 FROM articulosFamilias afF WHERE afF.idArticulo = lp.idArticulo AND afF.idFamilia IN ($descStr))";
                $filtroFlujoGlobalJoinV  = '';
                $filtroFlujoGlobalWhereV = "AND EXISTS (SELECT 1 FROM articulosFamilias afFv WHERE afFv.idArticulo = l.idArticulo AND afFv.idFamilia IN ($descStr))";
                $filtroFlujoN1Where      = $filtroN1;   // ya tiene af JOIN
                $flujoGroupByKey         = 'af.idFamilia'; // agrupar por familia real; mapear a virtual N1 en PHP
            }
        }

        // Compras globales (sin JOIN de familia — fuente canónica sin duplicados cuando no se filtra)
        $sqlFlujoComprasGlobal = "
            SELECT
                SUM(lp.costeSiva * lp.nunidades)                        AS compras_siva,
                SUM(lp.costeSiva * (1 + ar.iva/100) * lp.nunidades)    AS compras_civa
            FROM albprolinea lp
            JOIN albprot ap     ON ap.id           = lp.idalbpro
            JOIN proveedores pv ON pv.idProveedor  = ap.idProveedor
            JOIN articulos ar   ON ar.idArticulo   = lp.idArticulo
            $filtroFlujoGlobalJoinC
            WHERE ap.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND ap.estado IN ('Guardado','Procesado','Facturado')
              AND lp.estadoLinea <> 'Eliminado'
              AND pv.estado != 'Especial'
              $filtroFlujoGlobalWhereC
        ";
        $smtFCG = $BDTpv->query($sqlFlujoComprasGlobal);
        $rowFCG = $smtFCG->fetch_assoc();
        $gtComprasSiva = (float)($rowFCG['compras_siva'] ?? 0);
        $gtComprasCiva = (float)($rowFCG['compras_civa'] ?? 0);

        // Compras por familia: agrupa por $flujoGroupByKey para soportar jerarquía virtual
        $sqlFlujoCompras = "
            SELECT
                $flujoGroupByKey AS flujo_key,
                SUM(lp.costeSiva * lp.nunidades)                        AS compras_siva,
                SUM(lp.costeSiva * (1 + ar.iva/100) * lp.nunidades)    AS compras_civa
            FROM albprolinea lp
            JOIN albprot ap        ON ap.id          = lp.idalbpro
            JOIN proveedores pv    ON pv.idProveedor = ap.idProveedor
            JOIN articulos ar      ON ar.idArticulo  = lp.idArticulo
            LEFT JOIN articulosFamilias af  ON af.idArticulo = lp.idArticulo
            LEFT JOIN vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            WHERE ap.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND ap.estado IN ('Guardado','Procesado','Facturado')
              AND lp.estadoLinea <> 'Eliminado'
              AND pv.estado != 'Especial'
              $filtroFlujoN1Where
            GROUP BY $flujoGroupByKey
        ";
        $smtFC = $BDTpv->query($sqlFlujoCompras);
        $flujoComprasPorN1 = [];
        while ($rfc = $smtFC->fetch_assoc()) {
            $rawKey = $rfc['flujo_key'];
            // Con jerarquía virtual, mapear idFamilia real → virtual N1
            if ($virtualHierarchy !== null && $rawKey !== null) {
                $vN1 = $virtualHierarchy[(int)$rawKey]['vN1'] ?? null;
                $idN1key = $vN1 !== null ? $vN1 : '__sin_familia__';
            } else {
                $idN1key = $rawKey !== null ? (int)$rawKey : '__sin_familia__';
            }
            $flujoComprasPorN1[$idN1key]['compras_siva'] = ($flujoComprasPorN1[$idN1key]['compras_siva'] ?? 0) + (float)$rfc['compras_siva'];
            $flujoComprasPorN1[$idN1key]['compras_civa'] = ($flujoComprasPorN1[$idN1key]['compras_civa'] ?? 0) + (float)$rfc['compras_civa'];
        }

        // Ventas globales para el flujo
        $sqlFlujoVentas = "
            SELECT
                SUM(l.precioCiva * l.nunidades) AS ventas_civa,
                SUM(l.precioCiva / (1 + l.iva/100) * l.nunidades) AS ventas_siva
            FROM albclilinea l
            JOIN albclit h ON h.id = l.idalbcli
            LEFT JOIN clientes cl ON cl.idClientes = h.idCliente
            $filtroFlujoGlobalJoinV
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND h.estado IN ('Guardado','Procesado')
              AND l.estadoLinea = 'Activo'
              AND (h.idCliente = 0 OR cl.estado != 'Especial')
              $filtroFlujoGlobalWhereV
            UNION ALL
            SELECT
                SUM(l.precioCiva * l.nunidades) AS ventas_civa,
                SUM(l.precioCiva / (1 + l.iva/100) * l.nunidades) AS ventas_siva
            FROM ticketslinea l
            JOIN ticketst h ON h.id = l.idticketst
            LEFT JOIN clientes cl ON cl.idClientes = h.idCliente
            $filtroFlujoGlobalJoinV
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND h.estado IN ('Cobrado','Cerrado')
              AND l.estadoLinea = 'Activo'
              AND (h.idCliente = 0 OR cl.estado != 'Especial')
              $filtroFlujoGlobalWhereV
        ";
        $smtFV = $BDTpv->query($sqlFlujoVentas);
        $gtVentasCiva = 0;
        $gtVentasSiva = 0;
        while ($rfv = $smtFV->fetch_assoc()) {
            $gtVentasCiva += (float)$rfv['ventas_civa'];
            $gtVentasSiva += (float)$rfv['ventas_siva'];
        }

        // Ventas por familia para el panel por familia
        $sqlFlujoVentasN1 = "
            SELECT $flujoGroupByKey AS flujo_key,
                SUM(l.precioCiva * l.nunidades)                      AS ventas_civa,
                SUM(l.precioCiva / (1 + l.iva/100) * l.nunidades)   AS ventas_siva
            FROM albclilinea l
            JOIN albclit h ON h.id = l.idalbcli
            LEFT JOIN clientes cl ON cl.idClientes = h.idCliente
            LEFT JOIN articulosFamilias af ON af.idArticulo = l.idArticulo
            LEFT JOIN vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND h.estado IN ('Guardado','Procesado')
              AND l.estadoLinea = 'Activo'
              AND (h.idCliente = 0 OR cl.estado != 'Especial')
              $filtroFlujoN1Where
            GROUP BY $flujoGroupByKey
            UNION ALL
            SELECT $flujoGroupByKey AS flujo_key,
                SUM(l.precioCiva * l.nunidades)                      AS ventas_civa,
                SUM(l.precioCiva / (1 + l.iva/100) * l.nunidades)   AS ventas_siva
            FROM ticketslinea l
            JOIN ticketst h ON h.id = l.idticketst
            LEFT JOIN clientes cl ON cl.idClientes = h.idCliente
            LEFT JOIN articulosFamilias af ON af.idArticulo = l.idArticulo
            LEFT JOIN vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            WHERE h.Fecha BETWEEN '$fechaInicio' AND '$fechaFinal'
              AND h.estado IN ('Cobrado','Cerrado')
              AND l.estadoLinea = 'Activo'
              AND (h.idCliente = 0 OR cl.estado != 'Especial')
              $filtroFlujoN1Where
            GROUP BY $flujoGroupByKey
        ";
        $smtFVN1 = $BDTpv->query($sqlFlujoVentasN1);
        $flujoVentasPorN1 = [];
        while ($rfvn1 = $smtFVN1->fetch_assoc()) {
            $rawKey = $rfvn1['flujo_key'];
            if ($virtualHierarchy !== null && $rawKey !== null) {
                $vN1 = $virtualHierarchy[(int)$rawKey]['vN1'] ?? null;
                $idN1key = $vN1 !== null ? $vN1 : '__sin_familia__';
            } else {
                $idN1key = $rawKey !== null ? (int)$rawKey : '__sin_familia__';
            }
            $flujoVentasPorN1[$idN1key]['ventas_civa'] = ($flujoVentasPorN1[$idN1key]['ventas_civa'] ?? 0) + (float)$rfvn1['ventas_civa'];
            $flujoVentasPorN1[$idN1key]['ventas_siva'] = ($flujoVentasPorN1[$idN1key]['ventas_siva'] ?? 0) + (float)$rfvn1['ventas_siva'];
        }

        // ── Stock medio del período para Rotación y GMROI ────────────────
        // La BD es anualizada: toda la información de stock está en los movimientos del año.
        // Reconstrucción pura desde movimientos (sin articulosStocks):
        //   stock_en_D = SUM(entradas − salidas desde 1ene hasta D)
        //   stock_fin    = reconstruido(1ene → fechaFinal)
        //   stock_inicio = reconstruido(1ene → fechaInicio−1)   [0 si fechaInicio=1ene]
        //   stock_medio  = (stock_inicio + stock_fin) / 2
        $inicioAno = date('Y', strtotime($fechaFinal)) . '-01-01';
        $vispera   = date('Y-m-d', strtotime($fechaInicio . ' -1 day'));

        // Reconstruye unidades netas acumuladas desde 1ene hasta $hasta (inclusive)
        $fnStockEn = function (string $hasta) use ($BDTpv, $inicioAno): array {
            // Si $hasta < $inicioAno (período empieza el 1 ene → víspera = 31 dic año anterior)
            // devolvemos array vacío → stock inicio = 0
            if ($hasta < $inicioAno) return [];
            $smt = $BDTpv->query("
                SELECT idArticulo, SUM(delta) AS neto
                FROM (
                    SELECT l.idArticulo,  l.nunidades AS delta
                    FROM albprolinea l
                    JOIN albprot c ON c.id = l.idalbpro
                    WHERE DATE(c.Fecha) BETWEEN '$inicioAno' AND '$hasta'
                      AND c.estado IN ('Guardado','Facturado','Exportado','Importado')
                      AND l.estadoLinea = 'Activo'
                    UNION ALL
                    SELECT l.idArticulo, -l.nunidades AS delta
                    FROM ticketslinea l
                    JOIN ticketst c ON c.id = l.idticketst
                    WHERE DATE(c.Fecha) BETWEEN '$inicioAno' AND '$hasta'
                      AND c.estado = 'Cerrado'
                      AND l.estadoLinea = 'Activo'
                    UNION ALL
                    SELECT l.idArticulo, -l.nunidades AS delta
                    FROM albclilinea l
                    JOIN albclit c ON c.id = l.idalbcli
                    LEFT JOIN clientes cl ON cl.idClientes = c.idCliente
                    WHERE DATE(c.Fecha) BETWEEN '$inicioAno' AND '$hasta'
                      AND c.estado IN ('Guardado','Procesado')
                      AND l.estadoLinea = 'Activo'
                      AND (c.idCliente = 0 OR cl.estado != 'Especial')
                ) AS movs
                GROUP BY idArticulo
            ");
            $result = [];
            while ($r = $smt->fetch_assoc()) {
                $result[(int)$r['idArticulo']] = (float)$r['neto'];
            }
            return $result;
        };

        $stockFinPorArt    = $fnStockEn($fechaFinal);  // stock al cierre del período
        $stockInicioPorArt = $fnStockEn($vispera);     // stock en la víspera (= inicio del período)

        // Obtener ultimoCoste para el fallback de valoración
        $smtUC = $BDTpv->query("SELECT idArticulo, ultimoCoste FROM articulos WHERE ultimoCoste > 0");
        $ultimoCostePorArt = [];
        while ($ruc = $smtUC->fetch_assoc()) {
            $ultimoCostePorArt[(int)$ruc['idArticulo']] = (float)$ruc['ultimoCoste'];
        }

        // stock_medio por artículo = (unidades_inicio + unidades_fin) / 2
        $stockMedioPorArt = [];
        $todosIds = array_unique(array_merge(
            array_keys($stockFinPorArt),
            array_keys($stockInicioPorArt)
        ));
        foreach ($todosIds as $idA) {
            $fin    = $stockFinPorArt[$idA]    ?? 0;
            $inicio = $stockInicioPorArt[$idA] ?? 0;
            $medio  = ($inicio + $fin) / 2;
            if ($medio > 0) {
                $stockMedioPorArt[$idA] = $medio;
            }
        }

        // Calcular valor stock por artículo usando PMP del período o fallback ultimoCoste
        // y acumular por virtual N1 (mismo mapeo que ventas/compras)
        // Para el global: iterar artículos únicos del resultado (evita duplicados multi-familia)
        $stockPorN1  = [];
        $gtValorStock = 0;
        $articulosContadosStock = []; // para el global, contar cada artículo una sola vez

        foreach ($resultado as $fam) {
            $key = $fam['idN1'];
            foreach ($fam['subfamilias'] as $sf) {
                foreach ($sf['articulos'] as $art) {
                    $idA = $art['idArticulo'];
                    if (!isset($stockMedioPorArt[$idA])) continue;
                    $costeArt = $costePeriodo[$idA] ?? $ultimoCostePorArt[$idA] ?? 0;
                    if ($costeArt <= 0) continue;
                    $valorArt = $stockMedioPorArt[$idA] * $costeArt;
                    // Acumular por familia (acepta duplicados multi-familia, igual que tabla)
                    $stockPorN1[$key] = ($stockPorN1[$key] ?? 0) + $valorArt;
                    // Acumular global solo una vez por artículo
                    if (!isset($articulosContadosStock[$idA])) {
                        $articulosContadosStock[$idA] = true;
                        $gtValorStock += $valorArt;
                    }
                }
            }
        }

        // Añadir flujo + stock + GMROI por familia a cada elemento del resultado
        foreach ($resultado as &$fam) {
            $key = $fam['idN1'];
            $vSiva = $flujoVentasPorN1[$key]['ventas_siva'] ?? 0;
            $vCiva = $flujoVentasPorN1[$key]['ventas_civa'] ?? 0;
            $cSiva = $flujoComprasPorN1[$key]['compras_siva'] ?? 0;
            $cCiva = $flujoComprasPorN1[$key]['compras_civa'] ?? 0;
            $fam['flujo'] = [
                'ventas_siva'    => $vSiva,
                'ventas_civa'    => $vCiva,
                'compras_siva'   => $cSiva,
                'compras_civa'   => $cCiva,
                'resultado_siva' => $vSiva - $cSiva,
                'resultado_civa' => $vCiva - $cCiva,
            ];
            $stockFam = $stockPorN1[$key] ?? 0;
            $margenFam = $fam['totalVenta'] - $fam['totalCoste'];
            $fam['valor_stock'] = $stockFam;
            $fam['gmroi']       = ($stockFam > 0) ? round($margenFam / $stockFam, 2) : null;
        }
        unset($fam);

        $gtMargenBruto = $gtVenta - $gtCoste;
        $gtGmroi       = ($gtValorStock > 0) ? round($gtMargenBruto / $gtValorStock, 2) : null;

        return [
            'familias' => $resultado,
            'resumen'  => [
                'totalVenta'        => $gtVenta,
                'totalCoste'        => $gtCoste,
                'totalMerma'        => $gtMerma,
                'mermaSinVentas'    => $gtMermaSinVentas,
                'totalMermaGlobal'  => $gtMermaTotal,
                'beneficio'         => $gtBeneficio,
                'margen_pct'        => $gtMargen,
                'articulos_merma_sin_ventas' => $mermaSinVentas,
                'valor_stock'       => $gtValorStock,
                'gmroi'             => $gtGmroi,
                'flujo' => [
                    'ventas_siva'    => $gtVentasSiva,
                    'ventas_civa'    => $gtVentasCiva,
                    'compras_siva'   => $gtComprasSiva,
                    'compras_civa'   => $gtComprasCiva,
                    'resultado_siva' => $gtVentasSiva - $gtComprasSiva,
                    'resultado_civa' => $gtVentasCiva - $gtComprasCiva,
                ],
            ]
        ];
    }
}
