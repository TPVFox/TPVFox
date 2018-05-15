<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once '../../clases/claseimprimir.php';

function datamayor2html($sqldata) {
    $resultado = '';
    if (count($sqldata) > 0) {
        $resultado .= '<table border="1px">';

        foreach ($sqldata as $linea) {
            $resultado .= '<tr border="1px"> ';
            $resultado .= ' <td>' . date('d-m-Y', $linea['fecha']) . ' </td>';
            $resultado .= ' <td>' . $linea['entrega'] . ' </td>';
            $resultado .= ' <td>' . $linea['precioentrada'] . ' </td>';
            $resultado .= ' <td>' . $linea['salida'] . ' </td>';
            $resultado .= ' <td>' . $linea['preciosalida'] . ' </td>';
            $resultado .= ' <td>' . $linea['stock'] . ' </td>';
            $resultado .= ' <td>' . $linea['tipodoc'] . ' </td>';
            $resultado .= ' <td>' . $linea['numdocu'] . ' </td>';
            $resultado .= ' <td>' . substr($linea['nombre'],0,15) . '</td>';
            $resultado .= ' </tr>';
        }
        $resultado .= ' </table>';
    }
    return $resultado;
}

function cabeceramayor2html($datoscabecera) {
    $resultado = '<div class="row text-center" >' . $datoscabecera['titulo'] . '</div>';
    $resultado .= '<table>';
    $resultado .= '<tr><td> <b>Empresa</b> ' . $datoscabecera['empresa'] . '</td>';
    $resultado .= '<td> ' . date('d/m/Y') . '</td>';
    $resultado .= '<tr><td colspan="2">' . $datoscabecera['condiciones'] . '</td></tr>';
    $resultado .= '</tr></table>';
    return $resultado;
}

