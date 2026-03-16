<?php

/**
 * Helper POSStock — parseo y validación de parámetros POST comunes.
 *
 * Se incluye en los archivos de tareas que necesitan los mismos parámetros:
 * getPOSStockData, getPOSStockBatch, exportarPOSStockCSV, imprimirPOSStockPDF.
 *
 * Requiere que $URLCom y $BDTpv estén definidos (desde inicial.php / tareas.php).
 * Requiere que ClaseParametros ya esté incluida.
 *
 * @param  array  &$respuesta  Array de respuesta donde se escribe 'error' si falla.
 * @param  string  $tipo_periodo  Tipo de periodo enviado por el cliente (semana, mes...).
 *                                Si se proporciona, se usa para calcular min_ventas_c5
 *                                desde el backend; si no, el POST 'min_ventas_c5' es el fallback.
 * @return array|null  Array $params listo para ClasePosstock, o null si hay error.
 */
function parsearParamsPosstock(array &$respuesta, string $tipo_periodo = ''): ?array
{
    $fi_mov   = $_POST['fecha_inicio_movimientos'] ?? '';
    $ff_mov   = $_POST['fecha_fin_movimientos']    ?? '';
    $fi_stock = $_POST['fecha_inicio_stock']       ?? '';
    $ff_stock = $_POST['fecha_fin_stock']          ?? '';

    $fecha_re = '/^\d{4}-\d{2}-\d{2}$/';
    if (
        !preg_match($fecha_re, $fi_mov)   || !preg_match($fecha_re, $ff_mov)
        || !preg_match($fecha_re, $fi_stock) || !preg_match($fecha_re, $ff_stock)
    ) {
        $respuesta['error'] = 'Fechas no válidas o incompletas.';
        return null;
    }

    $ClaseParametros = new ClaseParametros('parametros.xml');
    $posstock_node   = $ClaseParametros->getNode('configuracion/posstock');

    // Filtro de casos: lista de IDs separados por coma
    $casos_validos = ['caso1', 'caso2', 'caso3a', 'caso3b', 'caso4', 'caso5', 'caso6a', 'caso6b'];
    $casos_incluir = [];
    foreach (explode(',', $_POST['casos_incluir'] ?? '') as $c) {
        $c = trim($c);
        if (in_array($c, $casos_validos, true)) $casos_incluir[] = $c;
    }

    // Filtro de familias: dos listas independientes de IDs separados por coma
    $familias_incluir = [];
    $familias_excluir = [];
    foreach (explode(',', $_POST['familias_incluir'] ?? '') as $id) {
        $id = (int)trim($id);
        if ($id > 0) $familias_incluir[] = $id;
    }
    foreach (explode(',', $_POST['familias_excluir'] ?? '') as $id) {
        $id = (int)trim($id);
        if ($id > 0) $familias_excluir[] = $id;
    }

    // Filtro de proveedores: lista de idProveedor separados por coma (solo incluir)
    $proveedores_incluir = [];
    foreach (explode(',', $_POST['proveedores_incluir'] ?? '') as $id) {
        $id = (int)trim($id);
        if ($id > 0) $proveedores_incluir[] = $id;
    }
    $proveedor_todos_productos = isset($_POST['proveedor_todos_productos']) && $_POST['proveedor_todos_productos'] === '1';

    // min_ventas_c5: calculado en backend según tipo de periodo (evita lógica en JS).
    // Si el tipo de periodo está disponible, se usa la tabla del backend.
    // En último caso se toma el valor POST (compatibilidad) con mínimo 3.
    $min_ventas_c5_por_tipo = [
        'semana'       => 4,
        'quincena'     => 5,
        'mes'          => 7,
        'trimestre'    => 10,
        'cuatrimestre' => 15,
        'semestre'     => 21,
        'anual'        => 30,
    ];
    $tipo_periodo = $tipo_periodo ?: ($_POST['tipo_periodo'] ?? '');
    $min_ventas_c5 = isset($min_ventas_c5_por_tipo[$tipo_periodo])
        ? $min_ventas_c5_por_tipo[$tipo_periodo]
        : max(3, (int)($_POST['min_ventas_c5'] ?? 3));

    // ── Ventana estadística triple (semana / quincena / mes) ──────────────────
    // Para periodos cortos se amplía ±1 periodo (centrado) para aumentar la muestra
    // sin romper estacionalidad. Trimestre/semestre/anual tienen datos suficientes.
    $tipos_ventana_triple = ['semana', 'quincena', 'mes'];
    if (in_array($tipo_periodo, $tipos_ventana_triple, true)) {
        $dur_dias     = (int)round((strtotime($ff_mov) - strtotime($fi_mov)) / 86400) + 1;
        $anio_mov     = (int)substr($fi_mov, 0, 4);
        $fi_stats_raw = date('Y-m-d', strtotime("$fi_mov -{$dur_dias} days"));
        $ff_stats_raw = date('Y-m-d', strtotime("$ff_mov +{$dur_dias} days"));
        $fi_stats     = max($fi_stats_raw, "{$anio_mov}-01-01");
        $ff_stats     = min($ff_stats_raw, "{$anio_mov}-12-31");
    } else {
        $fi_stats = $fi_stock; // Jan 1 — ya tiene suficiente histórico
        $ff_stats = $ff_mov;   // sin extensión post-periodo
    }

    // ── Modelo estadístico ────────────────────────────────────────────────────
    // modelo_estadistico: 'automatico' | 'binomial' | 'poisson_bn' | 'gamma'
    // modelo_significancia: 0.90 | 0.95 | 0.99
    //   → umbral_confianza (C5 gap threshold) = 1 - significancia
    //   → nivel_servicio   (C6 ROP quantile)  = significancia
    // binomial_sigma_mult: 2.0–4.0 (solo modo binomial)
    $modelos_validos  = ['automatico', 'binomial', 'poisson_bn', 'gamma'];
    $sig_validas      = ['0.90', '0.95', '0.99'];

    $modelo_raw = (string)$posstock_node->modelo_estadistico;
    $modelo_estadistico = in_array($modelo_raw, $modelos_validos, true) ? $modelo_raw : 'automatico';

    $sig_raw = number_format((float)(string)$posstock_node->modelo_significancia, 2);
    $modelo_significancia = in_array($sig_raw, $sig_validas, true) ? (float)$sig_raw : 0.95;

    $binomial_sigma_mult = (float)(string)$posstock_node->binomial_sigma_mult;
    $binomial_sigma_mult = ($binomial_sigma_mult >= 2.0 && $binomial_sigma_mult <= 4.0)
        ? $binomial_sigma_mult : 3.0;

    return [
        'fecha_inicio_movimientos'    => $fi_mov,
        'fecha_fin_movimientos'       => $ff_mov,
        'fecha_inicio_stock'          => $fi_stock,
        'fecha_fin_stock'             => $ff_stock,
        // Ventana estadística (±1 periodo para semana/quincena/mes; igual a fi_stock/ff_mov en el resto)
        'fecha_inicio_stats'          => $fi_stats,
        'fecha_fin_stats'             => $ff_stats,
        // Umbral de sobrestock: de porcentaje a factor (50% → 0.5)
        'umbral_sobrestock'           => ((float)(string)$posstock_node->umbral_sobrestock) / 100.0,
        // C2: umbrales de clasificación derivados del umbral_sobrestock (escalan con él)
        // ratio ≤ factor×2 → posible duplicado   (umbral=50% → ≤1.0×; umbral=100% → ≤2.0×)
        // ratio ≥ factor×6 → sobrestock severo   (umbral=50% → ≥3.0×; umbral=100% → ≥6.0×)
        'c2_umbral_duplicado'         => (((float)(string)$posstock_node->umbral_sobrestock) / 100.0) * 2.0,
        'c2_umbral_sobrestock_severo' => (((float)(string)$posstock_node->umbral_sobrestock) / 100.0) * 6.0,
        // C2: días de cobertura mínimos (stock_previo / ventas_diarias) para filtrar falsos positivos
        'c2_umbral_cobertura_dias'    => max(7, (int)(string)($posstock_node->c2_umbral_cobertura_dias ?: '21')),
        'umbral_caducidad_semanas'    => (int)(string)$posstock_node->umbral_semanas_desde_ultima_venta,
        'umbral_sin_rotacion_semanas' => (int)(string)$posstock_node->umbral_semanas_sin_rotacion,
        'casos_incluir'               => $casos_incluir,
        'min_ventas_c5'               => $min_ventas_c5,
        // Modelo estadístico unificado C5+C6
        'modelo_estadistico'          => $modelo_estadistico,
        'modelo_significancia'        => $modelo_significancia,
        'binomial_sigma_mult'         => $binomial_sigma_mult,
        // Derivados para compatibilidad interna con ClasePosstock
        'modelo_rotura_c5'            => $modelo_estadistico,
        'umbral_confianza_poisson'    => round(1.0 - $modelo_significancia, 2),
        'c6_nivel_servicio'           => $modelo_significancia,
        'c5_incluir_stock_negativo'   => (string)$posstock_node->c5_incluir_stock_negativo === '1',
        'incluir_albcli_ventas'       => (string)$posstock_node->incluir_albcli_ventas === '1',
        'familias_incluir'            => $familias_incluir,
        'familias_excluir'            => $familias_excluir,
        'proveedores_incluir'         => $proveedores_incluir,
        'proveedor_todos_productos'   => $proveedor_todos_productos,
        // C6 — lead time por defecto
        'c6_lead_time_defecto'        => (int)(string)$posstock_node->c6_lead_time_defecto ?: 14,
        // C3b — días post-periodo para validar falsos positivos "nunca vendido"
        'c3b_dias_post_periodo'       => max(7, min(30, (int)(string)($posstock_node->c3b_dias_post_periodo ?: '14'))),
        // C6b — ventana histórica fija (días hacia atrás desde ff_mov) para ROP operacional
        'c6b_dias_historico'          => max(30, min(365, (int)(string)($posstock_node->c6b_dias_historico ?: '90'))),
        // C3a — multiplicador sobre la cadencia media histórica para el umbral dinámico de rotación
        'c3a_multiplicador_cadencia'  => max(2.0, min(6.0, (float)(string)($posstock_node->c3a_multiplicador_cadencia ?: '3.0'))),
    ];
}
