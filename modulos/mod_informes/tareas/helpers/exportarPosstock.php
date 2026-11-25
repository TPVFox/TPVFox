<?php
// @ Objetivo: las columnas y las filas del CSV de POSStock. Las seis columnas de siempre —el detalle, como
// resumen— y detrás una columna fija por cada dato, vacía cuando la incidencia no lo tiene, para poder
// filtrar y ordenar en la hoja de cálculo.

function columnasCsvPosstock()
{
    // @ Devolvemos: (array) los nombres de las columnas, en su orden
    return array_merge(
        array('Articulo', 'Nombre', 'Tipo incidencia', 'Severidad', 'Detalle', 'Posible causa'),
        array_keys(camposCsvPosstock())
    );
}

function camposCsvPosstock()
{
    // @ Objetivo: cada columna fija, con el campo del motor del que sale y cómo se escribe
    // ('2' -> dos decimales, '1' -> un decimal, 'texto' -> tal cual, 'pedir' -> lo que hay que pedir)
    return array(
        'Stock' => array('stock_actual', '2'),
        'Mín. en el periodo' => array('min_balance', '2'),
        'Stock previo' => array('stock_previo', '2'),
        'Entrada' => array('nunidades', '2'),
        'Fecha de entrada' => array('fecha', 'texto'),
        'Última venta' => array('ultima_venta', 'texto'),
        'Semanas en almacén' => array('semanas_en_almacen', 'texto'),
        'Rotura desde' => array('fecha_inicio_rotura', 'texto'),
        'Rotura hasta' => array('fecha_fin_rotura', 'texto'),
        'Días de rotura' => array('dias_rotura', 'texto'),
        'Última salida' => array('ultima_salida', 'texto'),
        'Semanas desde la última salida' => array('semanas_desde_ultima_salida', 'texto'),
        'ROP' => array('rop', '2'),
        'Stock de seguridad' => array('stock_seguridad', '2'),
        'Días de autonomía' => array('dias_autonomia', '1'),
        'Plazo de entrega (días)' => array('lead_time_dias', 'texto'),
        'A pedir' => array('rop', 'pedir'),
        'Modelo' => array('modelo_usado', 'texto'),
        'Desfase estimado' => array('offset_estimado', '2'),
        'Confianza' => array('confianza', 'texto'),
        'Recepciones' => array('n_recepciones', 'texto'),
        'Merma total (kg)' => array('merma_total_kg', '2'),
        '% de merma' => array('pct_merma', '2'),
        'Merma declarada (kg)' => array('merma_declarada_kg', '2'),
    );
}

function aPedirPosstock($f)
{
    // @ Objetivo: lo que hay que pedir en el punto de pedido, como lo enseña la tabla: el ROP menos el stock
    // —desde cero si el stock es reconstruido y negativo—, más el stock de seguridad con el modelo BN
    $rop = (float) ($f['rop'] ?? 0);
    $stock = (float) ($f['stock_actual'] ?? 0);
    if (!empty($f['stock_reconstituido']) && $stock < 0) {
        $stock = 0.0;
    }
    $pedir = max(0, $rop - $stock);
    if (($f['modelo_usado'] ?? '') === 'BN') {
        $pedir += (float) ($f['stock_seguridad'] ?? 0);
    }
    return (int) ceil($pedir);
}

function detalleCsvPosstock($f)
{
    // @ Objetivo: el resumen de la incidencia, con los tipos y los campos del motor
    $tipo = $f['tipo'] ?? '';
    $numero = function ($campo, $decimales = 2) use ($f) {
        return isset($f[$campo]) && $f[$campo] !== null ? number_format((float) $f[$campo], $decimales, '.', '') : '-';
    };
    $detalle = '';
    if ($tipo === 'Inventario en negativo') {
        $detalle = 'Stock actual: ' . $numero('stock_actual');
    } elseif ($tipo === 'Desajuste Puntual de Stock') {
        $detalle = 'Stock final: ' . $numero('stock_actual') . ' | Min. intra-periodo: ' . $numero('min_balance');
    } elseif ($tipo === 'Entrada con stock alto') {
        $detalle = 'Stock previo: ' . $numero('stock_previo') . ' | Entrada: ' . $numero('nunidades') . ' | Fecha: ' . ($f['fecha'] ?? '-');
    } elseif ($tipo === 'Caducidad teórica') {
        $detalle = 'Ult. venta: ' . ($f['ultima_venta'] ?? '-') . ' | ' . ($f['semanas_en_almacen'] ?? '-') . ' sem. en almacén';
    } elseif ($tipo === 'Venta Cero (Posible Rotura Física)') {
        $estado = !empty($f['fecha_fin_rotura']) ? 'Recuperada ' . $f['fecha_fin_rotura'] : 'En curso';
        $detalle = 'Ult. venta: ' . ($f['ultima_venta'] ?? '-') . ' | Rotura desde: ' . ($f['fecha_inicio_rotura'] ?? '-')
            . ' | ' . $estado . ' | ' . ($f['dias_rotura'] ?? '-') . ' d';
    } elseif ($tipo === 'Entrada sin rotación previa') {
        $detalle = !empty($f['ultima_salida'])
            ? 'Ult. salida: ' . $f['ultima_salida'] . ' | ' . ($f['semanas_desde_ultima_salida'] ?? '-') . ' sem.'
            : 'Sin salidas registradas';
    } elseif ($tipo === 'Stock Inactivo en Periodo') {
        $detalle = 'Stock en periodo: ' . $numero('stock_actual');
    } elseif ($tipo === 'Agotamiento Estimado' || $tipo === 'Punto de Pedido') {
        $detalle = 'Stock: ' . $numero('stock_actual') . ' | ROP: ' . $numero('rop') . ' | SS: ' . $numero('stock_seguridad')
            . ' | Dias aut.: ' . $numero('dias_autonomia', 1) . ' | LT: ' . ($f['lead_time_dias'] ?? '-') . ' d'
            . ' | A pedir: ' . aPedirPosstock($f) . ' | Modelo: ' . ($f['modelo_usado'] ?? '-');
    } elseif ($tipo === 'Merma acumulada' || $tipo === 'Entrada no registrada' || $tipo === 'Posible error de pesaje') {
        $detalle = 'Desfase estimado: ' . $numero('offset_estimado') . ' | Recepciones: ' . ($f['n_recepciones'] ?? '-')
            . (isset($f['confianza']) ? ' | Confianza: ' . $f['confianza'] : '');
    } elseif ($tipo === 'Merma backstaging') {
        $detalle = 'Merma: ' . $numero('merma_total_kg') . ' kg | ' . $numero('pct_merma') . ' %'
            . ' | Declarada: ' . $numero('merma_declarada_kg') . ' kg';
    }
    return $detalle;
}

function filaCsvPosstock($f)
{
    // @ Objetivo: la fila del CSV de una incidencia, con tantas columnas como la cabecera
    $fila = array(
        $f['idArticulo'] ?? '',
        $f['nombre'] ?? '',
        $f['tipo'] ?? '',
        $f['severidad'] ?? '',
        detalleCsvPosstock($f),
        $f['posible_causa'] ?? '',
    );
    $esPedido = in_array($f['tipo'] ?? '', array('Agotamiento Estimado', 'Punto de Pedido'), true);
    // El C9 da su stock como stock final
    if (!isset($f['stock_actual']) && isset($f['stock_final'])) {
        $f['stock_actual'] = $f['stock_final'];
    }
    foreach (camposCsvPosstock() as $columna => $campo) {
        list($nombre, $forma) = $campo;
        if ($forma === 'pedir') {
            $fila[] = $esPedido ? (string) aPedirPosstock($f) : '';
        } elseif (!isset($f[$nombre]) || $f[$nombre] === null || $f[$nombre] === '') {
            $fila[] = '';
        } elseif ($forma === 'texto') {
            $fila[] = (string) $f[$nombre];
        } else {
            $fila[] = number_format((float) $f[$nombre], (int) $forma, '.', '');
        }
    }
    return $fila;
}
