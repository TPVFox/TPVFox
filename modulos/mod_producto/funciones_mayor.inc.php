<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once '../../clases/claseimprimir.php';

function datamayor2html($sqldata,$nombreArticulo) {
    $resultado = '';
    if (count($sqldata) > 0) {
        $resultado .= '<table border="1px" style="width:100%;">';
//        $resultado .= '<col style="width:29%" />';
//        $resultado .= '<col style="width:10%">';
//        $resultado .= '<col style="width:10%">';
//        $resultado .= '<col style="width:10%">';
//        $resultado .= '<col style="width:10%">';
//        $resultado .= '<col style="width:10%">';
//        $resultado .= '<col style="width:1%">';
//        $resultado .= '<col style="width:10%">';
//        $resultado .= '<col style="width:20%">';
        $resultado .= ' <thead>';
        $resultado .= '<tr > ';
        $resultado .= ' <th width="20%"><b>Fecha</b></th>';
        $resultado .= ' <th width="10%"> ent </th>';
        $resultado .= ' <th width="10%"> p.ent </th>';
        $resultado .= ' <th width="10%"> sal</th>';
        $resultado .= ' <th width="10%">p.sal </th>';
        $resultado .= ' <th width="10%"> Stock </th>';
//        $resultado .= ' <th>  </th>';
        $resultado .= ' <th width="10%"> doc </th>';
        $resultado .= ' <th width="20%"> nombre </th>';
        $resultado .= ' </tr>';
        $resultado .= ' </thead>';

        $resultado .= ' <tbody>';
    $resultado .= ' <tr><td colspan="9"><b>' . $nombreArticulo . '</b></td></tr>';

        foreach ($sqldata as $linea) {
            $resultado .= '<tr height="20px"> ';
            $resultado .= ' <td width="20%">' . $linea['fecha'] . ' </td>';
            $a = $linea['entrega'] != 0.0 ? number_format($linea['entrega'], 3) : ' ';
            $resultado .= ' <td width="10%">' . $a . ' </td>';
            $a = $linea['precioentrada'] != 0.0 ? number_format($linea['precioentrada'], 3) : ' ';
            $resultado .= ' <td width="10%">' . $a . ' </td>';

            $a = $linea['salida'] != 0.0 ? number_format($linea['salida'], 3) : ' ';
            $resultado .= ' <td width="10%">' . $a . ' </td>';
            $a = $linea['preciosalida'] != 0.0 ? number_format($linea['preciosalida'], 3) : ' ';
            $resultado .= ' <td width="10%">' . $a . '</td>';
            $resultado .= ' <td width="10%">' . number_format($linea['stock'], 3) . ' </td>';
            $resultado .= ' <td width="10%">' . $linea['tipodoc'] . ' ' . $linea['numdocu'] . ' </td>';
            $resultado .= ' <td width="20%">' . substr($linea['nombre'], 0, 15) . '</td>';
            $resultado .= ' </tr>';
        }
        $resultado .= ' </tbody>';

        $resultado .= ' </table>';
    }
    return $resultado;
}

function cabeceramayor2html($datoscabecera) {
    $resultado = '<p>' . $datoscabecera['titulo'] . '</p>';
    $resultado .= '<table width="100%" border="1px">';
    $resultado .= '<col style="width:80%">';
    $resultado .= '<col style="width:20%">';

    $resultado .= '<tr><td  colspan="2">' . $datoscabecera['titulo'] . '</td></tr>';
    $resultado .= '<tr><td> <b>Empresa</b> ' . $datoscabecera['empresa'] . '</td>';
    $resultado .= '<td> ' . date('d-m-Y') . '</td></tr>';
    $resultado .= '<tr><td colspan="2">' . $datoscabecera['condiciones'] . '</td></tr>';
    $resultado .= '</tr>';
    $resultado .= '</table>';
    return $resultado;
}
