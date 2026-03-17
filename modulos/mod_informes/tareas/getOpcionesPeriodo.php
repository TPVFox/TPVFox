<?php
/**
 * Tarea: getOpcionesPeriodo
 *
 * Devuelve el HTML de los elementos <option> para el selector de periodo.
 * Para el año en curso consulta getInfoPeriodos para obtener el estado en_ventana.
 * Para años pasados/futuros genera las opciones directamente.
 *
 * POST esperado:
 *   tipo                     string  semana | quincena | mes | trimestre | cuatrimestre | semestre | anual
 *   anio                     int
 *   ventana_dias             int     0 = sin restricción; 7 o 14
 *   incluir_stock_inactivo   int     0 | 1
 */

include_once $URLCom . '/modulos/mod_informes/tareas/vistas/vistaOpcionesPeriodo.php';

$tipo                 = trim($_POST['tipo']                  ?? '');
$anio                 = (int)($_POST['anio']                 ?? date('Y'));
$ventanaDias          = (int)($_POST['ventana_dias']         ?? 0);
$incluirStockInactivo = (int)($_POST['incluir_stock_inactivo'] ?? 0) === 1;
$anioActual           = (int)date('Y');

if ($tipo === '') {
    $respuesta['error'] = 'Tipo de periodo requerido.';
    return;
}

// Para el año en curso (no anual) obtener lista de periodos con en_ventana
$periodosConVentana = null;
if ($tipo !== 'anual' && $anio === $anioActual) {
    $periodoRef = calcularPeriodoPosstock($tipo, 1, $anio);
    if ($periodoRef !== null) {
        $totalPeriodos = $periodoRef['total_periodos'];
        $hoyTs         = mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y'));
        $limiteTs      = ($ventanaDias > 0) ? ($hoyTs - $ventanaDias * 86400) : null;

        $periodosConVentana = [];
        for ($n = 1; $n <= $totalPeriodos; $n++) {
            $p = calcularPeriodoPosstock($tipo, $n, $anio);
            if ($p === null) continue;
            $fiTs = strtotime($p['fecha_inicio_movimientos']);
            if ($fiTs !== false && $fiTs > $hoyTs) break;

            $enVentana = false;
            if ($limiteTs !== null) {
                $ffTs      = strtotime($p['fecha_fin_movimientos']);
                $enVentana = ($ffTs !== false && $ffTs >= $limiteTs);
            }
            $periodosConVentana[] = ['numero' => $n, 'en_ventana' => $enVentana];
        }
    }
}

$respuesta['html'] = renderOpcionesPeriodo(
    $tipo,
    $anio,
    $ventanaDias,
    $incluirStockInactivo,
    $anioActual,
    $periodosConVentana
);
