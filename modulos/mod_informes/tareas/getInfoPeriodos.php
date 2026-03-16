<?php
// @ Objetivo: devolver la lista de periodos de un tipo y año con su estado respecto
// a la ventana de consolidación. Elimina la necesidad de duplicar esa lógica en el JS.
//
// POST esperado:
//   tipo         (string) — semana | quincena | mes | trimestre | cuatrimestre | semestre | anual
//   anio         (int)    — año del ejercicio
//   ventana_dias (int)    — 0 = sin restricción; 7 o 14 = días hacia atrás de consolidación

$tipo        = trim($_POST['tipo']        ?? '');
$anio        = (int)($_POST['anio']        ?? date('Y'));
$ventana_dias = (int)($_POST['ventana_dias'] ?? 0);

// Calcular un periodo de referencia solo para obtener total_periodos y etiquetas
$periodo_ref = calcularPeriodoPosstock($tipo, 1, $anio);
if ($periodo_ref === null) {
    $respuesta['error'] = 'Tipo de periodo no válido: ' . htmlspecialchars($tipo);
    return;
}

$total_periodos = $periodo_ref['total_periodos'];

// Fecha límite: los periodos cuya fecha_fin_movimientos >= límite están en ventana
$hoy_ts  = mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y'));
$limite_ts = ($ventana_dias > 0) ? $hoy_ts - ($ventana_dias * 86400) : null;

$periodos = [];
$anio_actual = (int)date('Y');
for ($n = 1; $n <= $total_periodos; $n++) {
    $p = calcularPeriodoPosstock($tipo, $n, $anio);
    if ($p === null) continue;

    // Para el año en curso omitir periodos que aún no han comenzado
    if ($anio === $anio_actual) {
        $fi_ts = strtotime($p['fecha_inicio_movimientos']);
        if ($fi_ts !== false && $fi_ts > $hoy_ts) break; // periodos son consecutivos
    }

    $en_ventana = false;
    if ($limite_ts !== null) {
        $ff_ts = strtotime($p['fecha_fin_movimientos']);
        $en_ventana = ($ff_ts !== false && $ff_ts >= $limite_ts);
    }

    $periodos[] = [
        'numero'     => $n,
        'en_ventana' => $en_ventana,
        'label'      => $p['label_movimientos'],
    ];
}

$respuesta['periodos']       = $periodos;
$respuesta['total_periodos'] = $total_periodos;
