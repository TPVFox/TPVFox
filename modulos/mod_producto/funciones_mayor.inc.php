<?php

/* 
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

function datamayor2html($sqldata){
        $resultado = '';
    if (count($sqldata) > 0) {
        $resultado .= '<table>';

        foreach ($sqldata as $linea) {
            $resultado .= '<tr> ';
            $resultado .= ' <td>'.$linea['fecha'].' </td>';
            $resultado .= ' <td>'.$linea['entrega'].' </td>';
            $resultado .= ' <td>'.$linea['precioentrada'].' </td>';
            $resultado .= ' <td>'.$linea['salida'].' </td>';
            $resultado .= ' <td>'.$linea['preciosalida'].' </td>';
            $resultado .= ' <td>'.$linea['stock'].' </td>';
            $resultado .= ' <td>'.$linea['tipodoc'].' </td>';
            $resultado .= ' <td>'.$linea['numdocu'].' </td>';
            $resultado .= ' <td>'.$linea['nombre'].'</td>';
            $resultado .= ' </tr>';            
        }
            $resultado .= ' </table>';                    
    }
return $resultado;
}