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
    $casos_validos = ['caso1', 'caso2', 'caso3a', 'caso3b', 'caso4', 'caso5'];
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

    // min_ventas_c5: calculado en backend según tipo de periodo (evita lógica en JS).
    // Si el tipo de periodo está disponible, se usa la tabla del backend.
    // En último caso se toma el valor POST (compatibilidad) con mínimo 3.
    $min_ventas_c5_por_tipo = [
        'semana'       => 4,
        'quincena'     => 7,
        'mes'          => 10,
        'trimestre'    => 16,
        'cuatrimestre' => 25,
        'semestre'     => 40,
        'anual'        => 64,
    ];
    $tipo_periodo = $tipo_periodo ?: ($_POST['tipo_periodo'] ?? '');
    $min_ventas_c5 = isset($min_ventas_c5_por_tipo[$tipo_periodo])
        ? $min_ventas_c5_por_tipo[$tipo_periodo]
        : max(3, (int)($_POST['min_ventas_c5'] ?? 3));

    return [
        'fecha_inicio_movimientos'    => $fi_mov,
        'fecha_fin_movimientos'       => $ff_mov,
        'fecha_inicio_stock'          => $fi_stock,
        'fecha_fin_stock'             => $ff_stock,
        // Umbral de sobrestock: de porcentaje a factor (50% → 0.5)
        'umbral_sobrestock'           => ((float)(string)$posstock_node->umbral_sobrestock) / 100.0,
        'umbral_caducidad_semanas'    => (int)(string)$posstock_node->umbral_semanas_desde_ultima_venta,
        'umbral_sin_rotacion_semanas' => (int)(string)$posstock_node->umbral_semanas_sin_rotacion,
        'casos_incluir'               => $casos_incluir,
        'min_ventas_c5'               => $min_ventas_c5,
        'modelo_rotura_c5'            => (string)$posstock_node->modelo_rotura_c5 ?: 'binomial',
        'umbral_confianza_poisson'    => (float)(string)$posstock_node->umbral_confianza_poisson ?: 0.05,
        'familias_incluir'            => $familias_incluir,
        'familias_excluir'            => $familias_excluir,
    ];
}
