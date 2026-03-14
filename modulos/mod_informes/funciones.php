<?php

/**
 * Calcula las fechas y etiquetas de un periodo POSStock.
 *
 * @param string $tipo    'semana' | 'quincena' | 'mes' | 'trimestre'
 * @param int    $numero  Número del periodo dentro del año:
 *                        semana   : 1–52/53 según ISO 8601 (lunes–domingo).
 *                                   La semana 1 es la que contiene el primer jueves del año.
 *                                   El total de semanas varía: 52 la mayoría de años, 53 algunos.
 *                                   Se usa numeración ISO (no semana natural 1=1Ene) porque
 *                                   es estándar en sistemas de compras y logística.
 *                        quincena : 1–24 (impar = 1ª quincena del mes, par = 2ª quincena).
 *                                   Ej: 1=1ªEne, 2=2ªEne, 3=1ªFeb, 4=2ªFeb …
 *                        mes      : 1–12
 *                        trimestre: 1–4 (T1=Ene-Mar, T2=Abr-Jun, T3=Jul-Sep, T4=Oct-Dic)
 * @param int    $anio    Año del ejercicio
 *
 * @return array|null  Con las claves:
 *   fecha_inicio_movimientos, fecha_fin_movimientos,
 *   fecha_inicio_stock (siempre 01-Ene del ejercicio),
 *   fecha_fin_stock    (día anterior al inicio de movimientos),
 *   label_movimientos, label_stock, total_periodos
 */
function calcularPeriodoPosstock($tipo, $numero, $anio)
{
    $anio   = (int)$anio;
    $numero = (int)$numero;

    $meses_cortos = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
                         'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $meses_largos = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                         'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    switch ($tipo) {

        case 'semana':
            // Semana anclada al 01-Ene del ejercicio (sistema POS anual).
            // Semana 1: 01-Ene → primer domingo del año.
            // Semanas 2+: lunes → domingo (o 31-Dic para la última, posiblemente corta).
            $jan1 = new DateTime(sprintf('%04d-01-01', $anio));
            $dow  = (int)$jan1->format('N'); // 1=lun … 7=dom

            // Fin de semana 1: el domingo de la semana que contiene Jan 1
            $fin_sem1 = clone $jan1;
            if ($dow !== 7) {
                $fin_sem1->modify('+' . (7 - $dow) . ' days');
            }

            $dec31 = new DateTime(sprintf('%04d-12-31', $anio));

            if ($numero === 1) {
                $inicio = clone $jan1;
                $fin    = clone $fin_sem1;
            } else {
                $inicio = clone $fin_sem1;
                $inicio->modify('+' . (($numero - 1) * 7 - 6) . ' days');
                $fin = clone $inicio;
                $fin->modify('+6 days');
                if ($fin > $dec31) $fin = clone $dec31; // truncar al 31-Dic
            }

            // Total de semanas del año con este sistema
            $dias_restantes = (int)$fin_sem1->diff($dec31)->days;
            $total_periodos = 1 + (int)ceil($dias_restantes / 7);

            $label_mov = 'Semana ' . $numero . ' ('
                . $inicio->format('d ') . $meses_cortos[(int)$inicio->format('n')]
                . ' – '
                . $fin->format('d ') . $meses_cortos[(int)$fin->format('n')]
                . ' ' . $anio . ')';
            break;

        case 'quincena':
            // numero 1–24: impar = 1ª quincena del mes, par = 2ª quincena
            $mes   = (int)ceil($numero / 2);
            $mitad = ($numero % 2 === 1) ? 1 : 2;

            if ($mitad === 1) {
                $inicio = new DateTime(sprintf('%04d-%02d-01', $anio, $mes));
                $fin    = new DateTime(sprintf('%04d-%02d-15', $anio, $mes));
            } else {
                $inicio = new DateTime(sprintf('%04d-%02d-16', $anio, $mes));
                $fin    = new DateTime(sprintf('%04d-%02d-01', $anio, $mes));
                $fin->modify('last day of this month'); // último día del mes
            }

            $total_periodos = 24;
            $label_mov = ($mitad === 1 ? '1ª' : '2ª') . ' quincena '
                . $meses_cortos[$mes] . ' ' . $anio;
            break;

        case 'mes':
            $inicio = new DateTime(sprintf('%04d-%02d-01', $anio, $numero));
            $fin    = clone $inicio;
            $fin->modify('last day of this month'); // último día del mes

            $total_periodos = 12;
            $label_mov = $meses_largos[$numero] . ' ' . $anio;
            break;

        case 'trimestre':
            $mes_ini = ($numero - 1) * 3 + 1;
            $mes_fin = $mes_ini + 2;
            $inicio  = new DateTime(sprintf('%04d-%02d-01', $anio, $mes_ini));
            $fin     = new DateTime(sprintf('%04d-%02d-01', $anio, $mes_fin));
            $fin->modify('last day of this month'); // último día del mes del trimestre

            $total_periodos = 4;
            $label_mov = 'T' . $numero . ' ' . $anio;
            break;

        default:
            return null;
    }

    // ── Stock base ────────────────────────────────────────────────────────────
    //
    // Regla general: stock base = movimientos desde 01-Ene del ejercicio
    //                hasta el día anterior al inicio del periodo.
    //
    // Caso especial — primer periodo del año (inicio = 01-Ene):
    //   Jan 1 es festivo con importación de stock de apertura.
    //   Algunos sistemas registran la apertura el 31-Dic del año anterior;
    //   otros el 01-Ene. Para capturar ambos casos:
    //   · stock base: 31-Dic anterior → 01-Ene (ambos inclusive)
    //   · movimientos: arrancan desde Jan 2

    $fecha_inicio_mov_dt = clone $inicio;
    $fi_stock_dt         = new DateTime(sprintf('%04d-01-01', $anio));

    if ($inicio->format('m-d') === '01-01') {
        $fi_stock_dt = new DateTime(sprintf('%04d-12-31', $anio - 1));
        $fecha_inicio_mov_dt->modify('+1 day'); // movimientos desde Jan 2
    }

    $fecha_fin_stock_dt = clone $fecha_inicio_mov_dt;
    $fecha_fin_stock_dt->modify('-1 day'); // día anterior al inicio real de movimientos

    $label_stock = 'Stock base: '
        . $fi_stock_dt->format('d ')
        . $meses_cortos[(int)$fi_stock_dt->format('n')]
        . ' ' . $fi_stock_dt->format('Y') . ' – '
        . $fecha_fin_stock_dt->format('d ')
        . $meses_cortos[(int)$fecha_fin_stock_dt->format('n')]
        . ' ' . $fecha_fin_stock_dt->format('Y');

    return [
        'fecha_inicio_movimientos' => $fecha_inicio_mov_dt->format('Y-m-d'),
        'fecha_fin_movimientos'    => $fin->format('Y-m-d'),
        'fecha_inicio_stock'       => $fi_stock_dt->format('Y-m-d'),
        'fecha_fin_stock'          => $fecha_fin_stock_dt->format('Y-m-d'),
        'label_movimientos'        => $label_mov,
        'label_stock'              => $label_stock,
        'total_periodos'           => $total_periodos,
    ];
}

function getHtmlTitulo($cabecera)
{
    $html = '<h1>' . $cabecera['titulo_informe'] . '<h1>';
    return $html;
}
function getRangoFechas($cabecera)
{
    $html = 'Listado entre las fechas :' . $cabecera['Fecha_Inicio'] . '-----' . $cabecera['Fecha_Final'];
    return $html;
}
