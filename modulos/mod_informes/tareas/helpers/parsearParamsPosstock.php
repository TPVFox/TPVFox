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
    $casos_validos = ['caso1', 'caso2', 'caso3a', 'caso3b', 'caso4', 'caso5', 'caso6'];
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
        'c5_incluir_stock_negativo'   => (string)$posstock_node->c5_incluir_stock_negativo === '1',
        'familias_incluir'            => $familias_incluir,
        'familias_excluir'            => $familias_excluir,
        'proveedores_incluir'         => $proveedores_incluir,
        'proveedor_todos_productos'   => $proveedor_todos_productos,
        // C6 — Agotamiento Estimado / Punto de Pedido
        'c6_lead_time_defecto'        => (int)(string)$posstock_node->c6_lead_time_defecto ?: 14,
        'c6_nivel_servicio'           => (float)(string)$posstock_node->c6_nivel_servicio ?: 0.95,
    ];
}
