<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once '../../clases/claseimprimir.php';

function datamayor2html($sqldata,$nombreArticulo, $sumas) {
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
    $resultado .= ' <tr>';
    $resultado .= '<td colspan="4"><b>' . $nombreArticulo . '</b></td>';
    $resultado .= '<td align="right"><b>Stock inicial</b></td>';
    $resultado .= '<td align="right">'.$sumas['stockinicial'].'</td>';
    $resultado .= '<td colspan="2"></td>';
    $resultado .= ' </tr>';

        foreach ($sqldata as $linea) {
            $resultado .= '<tr height="20px"> ';
            $resultado .= ' <td width="20%">' . $linea['fecha'] . ' </td>';
            $a = $linea['entrega'] != 0.0 ? number_format($linea['entrega'], 3) : ' ';
            $resultado .= ' <td width="10%"  align="right">' . $a . ' </td>';
            $a = $linea['precioentrada'] != 0.0 ? number_format($linea['precioentrada'], 3) : ' ';
            $resultado .= ' <td width="10%"  align="right">' . $a . ' </td>';

            $a = $linea['salida'] != 0.0 ? number_format($linea['salida'], 3) : ' ';
            $resultado .= ' <td width="10%" align="right">' . $a . ' </td>';
            $a = $linea['preciosalida'] != 0.0 ? number_format($linea['preciosalida'], 3) : ' ';
            $resultado .= ' <td width="10%" align="right">' . $a . '</td>';
            $resultado .= ' <td width="10%" align="right">' . number_format($linea['stock'], 3) . ' </td>';
            $resultado .= ' <td width="10%" align="right">' . $linea['tipodoc'] . ' ' . $linea['numdocu'] . ' </td>';
            $resultado .= ' <td width="20%">' . substr($linea['nombre'], 0, 15) . '</td>';
            $resultado .= ' </tr>';
        }
            $resultado .= '<tr height="5px" > ';
            $resultado .= ' <td width="20%" > </td>';
            $resultado .= ' <td width="10%" align="right" style="background-color:black"> </td>';
            $resultado .= ' <td width="10%"> </td>';
            $resultado .= ' <td width="10%" align="right" style="background-color:black"> </td>';
            $resultado .= ' <td width="10%"> </td>';
            $resultado .= ' <td width="10%" align="right" style="background-color:black"> </td>';
            $resultado .= ' <td width="10%"> </td>';
            $resultado .= ' <td width="20%"> </td>';
            $resultado .= ' </tr>';
            $resultado .= '<tr > ';
            $resultado .= ' <td width="20%" align="right"><b>TOTALES:</b></td>';
            $resultado .= ' <td width="10%" align="right">'.number_format($sumas['totalEntrada'], 3).'</td>';
            $resultado .= ' <td width="10%"> </td>';
            $resultado .= ' <td width="10%" align="right">'.number_format($sumas['totalSalida'], 3).'</td>';
            $resultado .= ' <td width="10%"> </td>';
            $resultado .= ' <td width="10%" align="right">'.number_format($sumas['sumastock'], 3).'</td>';
            $resultado .= ' <td width="10%"> </td>';
            $resultado .= ' <td width="20%"> </td>';
            $resultado .= ' </tr>';
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
