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
            // Semana ISO: lunes a domingo
            $inicio = new DateTime();
            $inicio->setISODate($anio, $numero, 1); // lunes de la semana ISO $numero
            $fin = clone $inicio;
            $fin->modify('+6 days');                // domingo

            // Total de semanas ISO del año (28-Dic siempre cae en la última semana ISO)
            $dec28 = new DateTime("$anio-12-28");
            $total_periodos = (int)$dec28->format('W');

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

    $fecha_fin_stock_dt = clone $inicio;
    $fecha_fin_stock_dt->modify('-1 day'); // día anterior al inicio de movimientos

    $label_stock = 'Stock base: 01 Ene – '
        . $fecha_fin_stock_dt->format('d ')
        . $meses_cortos[(int)$fecha_fin_stock_dt->format('n')]
        . ' ' . $anio;

    return [
        'fecha_inicio_movimientos' => $inicio->format('Y-m-d'),
        'fecha_fin_movimientos'    => $fin->format('Y-m-d'),
        'fecha_inicio_stock'       => sprintf('%04d-01-01', $anio),
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
