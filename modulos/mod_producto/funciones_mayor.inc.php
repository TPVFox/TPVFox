<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */
include_once './../../inicial.php';
include $URLCom.'/clases/claseimprimir.php';


function datamayor2html($sqldata, $sumas) {
    $resultado = '';
    if (count($sqldata) > 0) {
        $resultado .= '<table border="1px" style="width:100%;">'
                    . ' <thead>'
                    . '<tr > '
                    . '  <th width="16%"><b>Fecha</b></th>'
                    . '  <th width="7%"> entrada </th>'
                    . '  <th width="10%"> coste </th>'
                    . '  <th width="7%"> salida </th>'
                    . '  <th width="10%"> PVP </th>'
                    . '  <th width="10%"> Stock </th>'
                    . '  <th width="10%"> doc </th>'
                    . '  <th width="20%"> nombre </th>'
                    . '  <th width="10%"> estado </th>'
                    . ' </tr>'
                    . '</thead>'
                    . '<tbody>'
                    . ' <tr>'
                    . '  <td style="width:16%;"><b>Stock inicial</b></td>'
                    . '  <td style="width:7%;"></td>'
                    . '  <td style="width:10%;"></td>'
                    . '  <td style="width:7%;"></td>'
                    . '  <td style="width:10%;"></td>'
                    . '  <td align="right" style="width:10%;">' . $sumas['stockinicial'] . '</td>'
                    . '  <td style="width:10%;"></td>'
                    . '  <td style="width:20%;" ></td>'
                    . '  <td style="width:10%;"></td>'
                    . ' </tr>';

        foreach ($sqldata as $linea) {
            // Modifico array evitar errores ydevolverloque buscamos.
            $linea['entrega'] = $linea['entrega'] != 0.0 ? number_format($linea['entrega'], 3) : ' ';
            $linea['precioentrada'] = $linea['precioentrada'] != 0.0 ? number_format($linea['precioentrada'], 3) : ' ';
            $linea['salida'] = $linea['salida'] != 0.0 ? number_format($linea['salida'], 3) : ' ';
            $linea['preciosalida'] = $linea['preciosalida'] != 0.0 ? number_format($linea['preciosalida'], 3) : ' ';
            $stylo = '';
            if ( $linea['estado']=='Sin Guardar'){
                $stylo = ' style="background-color:red;color:white">';
            }
            // Continuamos montando resultado (Html de lineas )
            $resultado .= '<tr height="20px"> '
                        . ' <td>' . $linea['fecha'] . ' </td>'
                        . ' <td  align="right">' . $linea['entrega'] . ' </td>'
                        . ' <td  align="right">' . $linea['precioentrada'] . ' </td>'
                        . ' <td width="7%" align="right">' . $linea['salida'] . ' </td>'
                        . ' <td  align="right">' . $linea['preciosalida'] . '</td>'
                        . ' <td>' . number_format($linea['stock'], 3) . ' </td>'
                        . ' <td  align="right">' . $linea['tipodoc'] . ' ' . $linea['numdocu'] . ' </td>'
                        . ' <td>' . mb_substr($linea['nombre'], 0, 15) . '</td>'
                        . ' <td align="right"'.$stylo. '>' . $linea['estado'] . ' </td>'
                        . '</tr>';
        }
        $resultado .= ' <tr height="2px" > '
                    . '   <td > </td>'
                    . '   <td align="right" style="background-color:black"> </td>'
                    . '   <td> </td>'
                    . '   <td align="right" style="background-color:black"> </td>'
                    . '   <td> </td>'
                    . '   <td align="right" style="background-color:black"> </td>'
                    . '   <td> </td>'
                    . '   <td> </td>'
                    . '   <td> </td>'
                    .  '</tr>'
                    . ' <tr > '
                    . '   <td align="right"><b>TOTALES:</b></td>'
                    . '   <td align="right">' . number_format($sumas['totalEntrada'], 3) . '</td>'
                    . '   <td> </td>'
                    . '   <td align="right">' . number_format($sumas['totalSalida'], 3) . '</td>'
                    . '   <td> </td>'
                    . '   <td align="right">' . number_format($sumas['sumastock'], 3) . '</td>'
                    . '   <td> </td>'
                    . '   <td> </td>'
                    . ' </tr>'
                    . '</tbody>'
                    . '</table>';
    }
    return $resultado;
}

function cabeceramayor2html($datoscabecera) {
    $resultado = '<h2 align="center">' . $datoscabecera['titulo'] . '</h2>'
                .'<table width="100%" border="1px">'
                .'<col style="width:80%">'
                .'<col style="width:20%">'
                .'<tr><td> <b>Empresa</b> ' . $datoscabecera['empresa'] . '</td>'
                .'<td> Fecha informe:' . date('d-m-Y') . '</td></tr>'
                .' <tr>'
                .'<td colspan="2">Producto: <b>' . $datoscabecera['producto'] . '</b></td>'
                .'</tr>'
                .'<tr><td colspan="2">' . $datoscabecera['condiciones'] . '</td></tr>'
                .'</table>';
    return $resultado;
}
