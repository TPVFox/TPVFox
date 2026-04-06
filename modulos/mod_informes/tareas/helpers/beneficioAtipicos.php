<?php

/**
 * Utilidades para detectar productos atipicos en Beneficio por familia.
 *
 * Criterio por subfamilia:
 * - margen_pct fuera del rango [media - 3*sd, media + 3*sd]
 * - y/o margen_pct negativo
 */

function normalizarClaveFamilia($idN1): string
{
    return (string)$idN1;
}

function buscarFamiliaBeneficio(array $familias, $idN1): ?array
{
    $objetivo = normalizarClaveFamilia($idN1);
    foreach ($familias as $familia) {
        if (normalizarClaveFamilia($familia['idN1'] ?? '') === $objetivo) {
            return $familia;
        }
    }
    return null;
}

function calcularMedia(array $valores): float
{
    if (count($valores) === 0) {
        return 0.0;
    }
    return array_sum($valores) / count($valores);
}

function calcularDesviacionTipicaPoblacional(array $valores, float $media): float
{
    $n = count($valores);
    if ($n === 0) {
        return 0.0;
    }

    $suma = 0.0;
    foreach ($valores as $v) {
        $d = (float)$v - $media;
        $suma += $d * $d;
    }

    return sqrt($suma / $n);
}

function detectarAtipicosFamilia(array $familia): array
{
    $filas = [];
    $totalArticulos = 0;

    foreach (($familia['subfamilias'] ?? []) as $subfamilia) {
        $articulos = $subfamilia['articulos'] ?? [];
        $totalArticulos += count($articulos);

        if (count($articulos) === 0) {
            continue;
        }

        $margenes = array_map(static fn($a) => (float)($a['margen_pct'] ?? 0), $articulos);
        $media = calcularMedia($margenes);
        $sd = calcularDesviacionTipicaPoblacional($margenes, $media);
        $limiteInferior = $media - 3 * $sd;
        $limiteSuperior = $media + 3 * $sd;

        foreach ($articulos as $art) {
            $margen = (float)($art['margen_pct'] ?? 0);
            $negativo = $margen < 0;
            $fuera3sd = ($margen < $limiteInferior) || ($margen > $limiteSuperior);

            if (!$negativo && !$fuera3sd) {
                continue;
            }

            $motivos = [];
            if ($negativo) {
                $motivos[] = 'margen_negativo';
            }
            if ($fuera3sd) {
                $motivos[] = 'fuera_3sd';
            }

            $filas[] = [
                'idN1' => $familia['idN1'] ?? null,
                'familia' => $familia['nombreN1'] ?? 'Sin familia',
                'idN2' => $subfamilia['idN2'] ?? null,
                'subfamilia' => $subfamilia['nombreN2'] ?? 'Sin subfamilia',
                'idArticulo' => (int)($art['idArticulo'] ?? 0),
                'articulo_name' => $art['articulo_name'] ?? '',
                'margen_pct' => $margen,
                'beneficio' => (float)($art['beneficio'] ?? 0),
                'totalVenta' => (float)($art['totalVenta'] ?? 0),
                'totalCoste' => (float)($art['totalCoste'] ?? 0),
                'valorMerma' => (float)($art['valorMerma'] ?? 0),
                'totalUnidades' => (float)($art['totalUnidades'] ?? 0),
                'pvpSiva' => (float)($art['pvpSiva'] ?? 0),
                'costeUsado' => (float)($art['costeUsado'] ?? 0),
                'media_subfamilia' => $media,
                'sd_subfamilia' => $sd,
                'limite_inferior' => $limiteInferior,
                'limite_superior' => $limiteSuperior,
                'motivo' => implode('|', $motivos),
            ];
        }
    }

    usort($filas, static function ($a, $b) {
        if ($a['margen_pct'] === $b['margen_pct']) {
            return $a['idArticulo'] <=> $b['idArticulo'];
        }
        return $a['margen_pct'] <=> $b['margen_pct'];
    });

    return [
        'filas' => $filas,
        'total_articulos' => $totalArticulos,
        'total_atipicos' => count($filas),
    ];
}

function detectarAtipicosGlobal(array $familias): array
{
    $filas = [];
    $totalArticulos = 0;

    foreach ($familias as $familia) {
        $atipicosFamilia = detectarAtipicosFamilia($familia);
        $totalArticulos += (int)($atipicosFamilia['total_articulos'] ?? 0);

        foreach (($atipicosFamilia['filas'] ?? []) as $fila) {
            $filas[] = $fila;
        }
    }

    usort($filas, static function ($a, $b) {
        if ($a['margen_pct'] === $b['margen_pct']) {
            return $a['idArticulo'] <=> $b['idArticulo'];
        }
        return $a['margen_pct'] <=> $b['margen_pct'];
    });

    return [
        'filas' => $filas,
        'total_articulos' => $totalArticulos,
        'total_atipicos' => count($filas),
    ];
}
