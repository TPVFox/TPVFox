<?php
/**
 * Tarea: getVistaBarraPeriodos
 *
 * Devuelve el HTML de la barra de botones de navegación entre periodos.
 *
 * POST esperado:
 *   tipo                     string   semana | quincena | mes | … | anual
 *   anio                     int
 *   numero_activo            mixed    int (periodo) o string (tipo incidencia si anual)
 *   ventana_dias             int
 *   en_ventana               int      0 | 1 — si el periodo activo está en ventana
 *   incluir_stock_inactivo   int      0 | 1
 */

include_once $URLCom . '/modulos/mod_informes/tareas/vistas/vistaOpcionesPeriodo.php';
include_once $URLCom . '/modulos/mod_informes/tareas/vistas/vistaBarraPeriodos.php';

$tipo                 = trim($_POST['tipo']                   ?? '');
$anio                 = (int)($_POST['anio']                  ?? date('Y'));
$numeroActivo         = $_POST['numero_activo']               ?? '';
$ventanaDias          = (int)($_POST['ventana_dias']          ?? 0);
$enVentana            = (int)($_POST['en_ventana']            ?? 0) === 1;
$incluirStockInactivo = (int)($_POST['incluir_stock_inactivo'] ?? 0) === 1;

if ($tipo === '') {
    $respuesta['error'] = 'Tipo de periodo requerido.';
    return;
}

// Para tipo anual no necesitamos lista de periodos (se usan tipos de incidencia)
if ($tipo === 'anual') {
    $respuesta['html'] = renderBarraPeriodos(
        $tipo, $anio, $numeroActivo, $ventanaDias, $enVentana, $incluirStockInactivo, null
    );
    return;
}

// Obtener lista de periodos con estado en_ventana
$periodoRef = calcularPeriodoPosstock($tipo, 1, $anio);
if ($periodoRef === null) {
    $respuesta['error'] = 'Tipo de periodo no válido: ' . htmlspecialchars($tipo);
    return;
}

$totalPeriodos = $periodoRef['total_periodos'];
$hoyTs         = mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y'));
$limiteTs      = ($ventanaDias > 0) ? ($hoyTs - $ventanaDias * 86400) : null;
$anioActual    = (int)date('Y');

$periodos = [];
for ($n = 1; $n <= $totalPeriodos; $n++) {
    $p = calcularPeriodoPosstock($tipo, $n, $anio);
    if ($p === null) continue;

    // Año en curso: omitir periodos no iniciados aún
    if ($anio === $anioActual) {
        $fiTs = strtotime($p['fecha_inicio_movimientos']);
        if ($fiTs !== false && $fiTs > $hoyTs) break;
    }

    $enV = false;
    if ($limiteTs !== null) {
        $ffTs = strtotime($p['fecha_fin_movimientos']);
        $enV  = ($ffTs !== false && $ffTs >= $limiteTs);
    }

    $periodos[] = ['numero' => $n, 'en_ventana' => $enV];
}

$respuesta['html'] = renderBarraPeriodos(
    $tipo, $anio, $numeroActivo, $ventanaDias, $enVentana, $incluirStockInactivo, $periodos
);
