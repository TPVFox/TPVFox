<?php

/**
 * InformesFiltros — Filtros reutilizables para informes de familias (Fase 6).
 *
 * Extraído de ClaseInformes::_buildFiltroOp4() para eliminar la duplicación
 * en ResumenFamilias, ResumenVentasFamilias y BeneficioFamilias.
 */
class InformesFiltros
{
    /**
     * Construye el filtro SQL y la jerarquía virtual para opción 4 (filtrado por familia).
     *
     * Si todos los IDs seleccionados son nivel=1, devuelve el filtro original con idN1.
     * Si alguno es nivel≥2, construye una jerarquía virtual donde el ID seleccionado actúa
     * como N1 y sus hijos directos como N2, filtrando por af.idFamilia IN (descendientes).
     *
     * @param mysqli $db    Conexión activa a la BD
     * @param int[]  $ids   IDs de familias seleccionadas (ya validados como int > 0)
     * @return array ['filtroSQL' => string, 'virtualHierarchy' => array|null, 'needsIdFamilia' => bool]
     *   virtualHierarchy es null cuando todos son nivel=1 (comportamiento original).
     *   virtualHierarchy[idFamilia] = ['vN1'=>int, 'vN1Name'=>string, 'vN2'=>int|null, 'vN2Name'=>string|null]
     */
    public static function buildFiltroOp4(mysqli $db, array $ids): array
    {
        if (empty($ids)) {
            return ['filtroSQL' => '', 'virtualHierarchy' => null, 'needsIdFamilia' => false];
        }

        $idsStr = implode(',', $ids);
        $rSel   = $db->query(
            "SELECT idFamilia, nivel, familiaNombre FROM vw_jerarquias_familias WHERE idFamilia IN ($idsStr)"
        );
        $selectedInfo = [];
        $allN1        = true;
        while ($r = $rSel->fetch_assoc()) {
            $selectedInfo[(int)$r['idFamilia']] = $r;
            if ((int)$r['nivel'] !== 1) {
                $allN1 = false;
            }
        }

        if ($allN1) {
            return [
                'filtroSQL'        => "AND vj.idN1 IN ($idsStr)",
                'virtualHierarchy' => null,
                'needsIdFamilia'   => false,
            ];
        }

        // Jerarquía virtual: construir descendientes y mapeo para cada ID seleccionado
        $virtualHierarchy = [];
        $allDescendants   = [];

        foreach ($ids as $selId) {
            if (!isset($selectedInfo[$selId])) {
                continue;
            }
            $selName = $selectedInfo[$selId]['familiaNombre'];

            // BFS para obtener todos los descendientes
            $descendants = [$selId];
            $famNames    = [$selId => $selName];
            $famParent   = [$selId => null];
            $queue       = [$selId];

            while (!empty($queue)) {
                $qStr = implode(',', $queue);
                $rCh  = $db->query(
                    "SELECT idFamilia, familiaNombre, familiaPadre
                     FROM vw_jerarquias_familias
                     WHERE familiaPadre IN ($qStr)"
                );
                $queue = [];
                while ($rc = $rCh->fetch_assoc()) {
                    $cId = (int)$rc['idFamilia'];
                    if (!in_array($cId, $descendants)) {
                        $descendants[]   = $cId;
                        $queue[]         = $cId;
                        $famNames[$cId]  = $rc['familiaNombre'];
                        $famParent[$cId] = (int)$rc['familiaPadre'];
                    }
                }
            }

            // Hijos directos del ID seleccionado (virtual N2)
            $directChildren = [];
            foreach ($descendants as $d) {
                if ($d !== $selId && isset($famParent[$d]) && $famParent[$d] === $selId) {
                    $directChildren[$d] = $famNames[$d];
                }
            }

            // Mapear cada descendiente a virtual N1 / N2
            foreach ($descendants as $descId) {
                if ($descId === $selId) {
                    $virtualHierarchy[$descId] = [
                        'vN1'     => $selId,
                        'vN1Name' => $selName,
                        'vN2'     => null,
                        'vN2Name' => null,
                    ];
                } elseif (isset($directChildren[$descId])) {
                    $virtualHierarchy[$descId] = [
                        'vN1'     => $selId,
                        'vN1Name' => $selName,
                        'vN2'     => $descId,
                        'vN2Name' => $famNames[$descId],
                    ];
                } else {
                    // Subir en el árbol hasta encontrar el hijo directo de $selId
                    $cur = $descId;
                    while (isset($famParent[$cur]) && $famParent[$cur] !== $selId && $famParent[$cur] !== null) {
                        $cur = $famParent[$cur];
                    }
                    $vN2 = (isset($famParent[$cur]) && $famParent[$cur] === $selId) ? $cur : null;
                    $virtualHierarchy[$descId] = [
                        'vN1'     => $selId,
                        'vN1Name' => $selName,
                        'vN2'     => $vN2,
                        'vN2Name' => $vN2 !== null ? ($famNames[$vN2] ?? '') : null,
                    ];
                }
                $allDescendants[] = $descId;
            }
        }

        $allDescStr = implode(',', array_unique($allDescendants));
        return [
            'filtroSQL'        => "AND af.idFamilia IN ($allDescStr)",
            'virtualHierarchy' => $virtualHierarchy,
            'needsIdFamilia'   => true,
        ];
    }
}
