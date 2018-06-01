<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */


/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *
 *   
 */


/* ===============  REALIZAMOS CONEXIONES  =============== */


$pulsado = $_POST['pulsado'];

include_once ("./../../inicial.php");

// Crealizamos conexion a la BD Datos
include ("./../../controllers/Controladores.php");
$Controler = new ControladorComun;
// Añado la conexion a controlador.
$Controler->loadDbtpv($BDTpv);

include_once './clases/ClaseArticulos.php';
include_once './funciones_mayor.inc.php';

switch ($pulsado) {

    case 'imprimemayor':

        $idArticulo = $_POST['idproducto'];
        $stockinicial = $_POST['stockinicial'];
        $fechainicio = $_POST['fechainicio'];
        $fechafinal = $_POST['fechafin'];


        // Validar en el lado del servidor. Si no hay fechas o el stock es alfanumerico o el articulo no existe
        // mensaje de error

        $resultado = [];
        $articulo = new alArticulos();
        if ($articulo->existe($idArticulo)) {
            $miarticulo = $articulo->leer($idArticulo);
            $nombreArticulo = $miarticulo['datos'][0]['idArticulo'] . ' ' . $miarticulo['datos'][0]['articulo_name'];

            $fecha = explode('/', $fechainicio);
            $fechadesde = $fecha[2] . '/' . $fecha[1] . '/' . $fecha[0];

            $fecha = explode('/', $fechafinal);
            $fechahasta = $fecha[2] . '/' . $fecha[1] . '/' . $fecha[0];

            $sqldata = $articulo->calculaMayor(compact("fechadesde", "fechahasta", "idArticulo"));

            if ($sqldata['datos']) {
                $sumastock = $stockinicial;
                $totalEntrada = 0.0;
                $totalSalida = 0.0;
                foreach ($sqldata['datos'] as $indice => $linea) {
                    $totalEntrada += $linea['entrega'];
                    $totalSalida += $linea['salida'];
                    $sumastock += $linea['entrega'] - $linea['salida'];
                    $sqldata['datos'][$indice]['stock'] = $sumastock;
                }
                $sumas = compact('stockinicial', 'totalEntrada', 'totalSalida', 'sumastock');
                $cabecera = cabeceramayor2html(['titulo' => 'Mayor productos'
                    , 'empresa' => '01 Alimentaria Longueicap 2018'
                    , 'condiciones' => 'Periódo: ' . $fechainicio . ' / ' . $fechafinal
                    , 'producto' => $nombreArticulo
                ]);
//                $cuerpo = datamayor2html($sqldata['datos'], $sumas);
                $pdf = new imprimirPDF();
                $pdf->SetFont(PDF_FONT_NAME_MAIN, '', 8);
                $pdf->SetMargins(7, 30, 7);
                $pdf->setCabecera($cabecera);
                $anchoCeldas = [16, 7, 10, 7, 10, 8, 7, 15, 10];
                $pdf->AddPage();
                $anchoCeldasPagina = [];
                $anchoPagina = $pdf->getPageWidth();
                foreach ($anchoCeldas as $anchoPorcentaje) {
                    $anchoCeldasPagina[] = anchoCelda($anchoPagina, $anchoPorcentaje);
                }
                
                $pdf->MultiCell($anchoCeldasPagina[0], 5, '<b>Fecha</b>', 1, 'C', 0, 0, '', '', true, 0, true);
                $pdf->MultiCell($anchoCeldasPagina[1], 5, 'entrada', 1, 'C', 0, 0, '', '', true, 0, false);
                $pdf->MultiCell($anchoCeldasPagina[2], 5, 'coste', 1, 'C', 0, 0, '', '', true, 0, false);
                $pdf->MultiCell($anchoCeldasPagina[3], 5, 'salida', 1, 'C', 0, 0, '', '', true, 0, false);
                $pdf->MultiCell($anchoCeldasPagina[4], 5, 'PVP', 1, 'C', 0, 0, '', '', true, 0, false);
                $pdf->MultiCell($anchoCeldasPagina[5], 5, 'Stock', 1, 'C', 0, 0, '', '', true, 0, false);
                $pdf->MultiCell($anchoCeldasPagina[6], 5, 'doc', 1, 'C', 0, 0, '', '', true, 0, false);
                $pdf->MultiCell($anchoCeldasPagina[7], 5, 'nombre', 1, 'C', 0, 0, '', '', true, 0, false);
                $pdf->MultiCell($anchoCeldasPagina[8], 5, 'estado', 1, 'C', 0, 0, '', '', true, 0, false);
                $pdf->Ln();

                foreach ($sqldata['datos'] as $linea) {
                    $pdf->MultiCell($anchoCeldasPagina[0], 5
                            , $linea['fecha'], 1, 'L', 0, 0, '', '', true, 0, false);
                    $pdf->MultiCell($anchoCeldasPagina[1], 5
                            , $linea['entrega'] != 0.0 ? number_format($linea['entrega'], 3) : ' '
                            , 1, 'R', 0, 0, '', '', true, 0, false);
                    $pdf->MultiCell($anchoCeldasPagina[2], 5
                            , $linea['precioentrada'] != 0.0 ? number_format($linea['precioentrada'], 3) : ' '
                            , 1, 'R', 0, 0, '', '', true, 0, false);
                    $pdf->MultiCell($anchoCeldasPagina[3], 5
                            , $linea['salida'] != 0.0 ? number_format($linea['salida'], 3) : ' '
                            , 1, 'R', 0, 0, '', '', true, 0, false);
                    $pdf->MultiCell($anchoCeldasPagina[4], 5
                            , $linea['preciosalida'] != 0.0 ? number_format($linea['preciosalida'], 3) : ' '
                            , 1, 'R', 0, 0, '', '', true, 0, false);
                    $pdf->MultiCell($anchoCeldasPagina[5], 5
                            , number_format($linea['stock'], 3)
                            , 1, 'R', 0, 0, '', '', true, 0, false);
                    $pdf->MultiCell($anchoCeldasPagina[6], 5
                            , $linea['tipodoc'] . ' ' . $linea['numdocu']
                            , 1, 'C', 0, 0, '', '', true, 0, false);
                    $pdf->MultiCell($anchoCeldasPagina[7], 5
                            , substr($linea['nombre'], 0, 15)
                            , 1, 'R', 0, 0, '', '', true, 0, false);
//                    $a = $linea['estado'] == 'Sin Guardar' ? ' style="background-color:red;color:white">' : '>';
                    $pdf->MultiCell($anchoCeldasPagina[8], 5
                            , $linea['estado']
                            , 1, 'R', 0, 0, '', '', true, 0, true);
                    $pdf->Ln();
                }

                $pdf->MultiCell(0, 2, ' ', 1, 'L', 0, 0, '', '', true, 0, false);
                $pdf->Ln();
//    $pdf->MultiCell($anchoCeldasPagina[0], 5
//                            , $linea['fecha'], 1, 'L', 0, 0, '', '', true, 0, true);
//    $resultado .= ' <td align="right" style="background-color:black"> </td>';
//    $resultado .= ' <td> </td>';
//    $resultado .= ' <td align="right" style="background-color:black"> </td>';
//    $resultado .= ' <td> </td>';
//    $resultado .= ' <td align="right" style="background-color:black"> </td>';
//    $resultado .= ' <td> </td>';
//    $resultado .= ' <td> </td>';
//    $resultado .= ' <td> </td>';
//    $resultado .= ' </tr>';
//    $resultado .= '<tr > ';
//    $resultado .= ' <td align="right"><b>TOTALES:</b></td>';
//    $resultado .= ' <td align="right">' . number_format($sumas['totalEntrada'], 3) . '</td>';
//    $resultado .= ' <td> </td>';
//    $resultado .= ' <td align="right">' . number_format($sumas['totalSalida'], 3) . '</td>';
//    $resultado .= ' <td> </td>';
//    $resultado .= ' <td align="right">' . number_format($sumas['sumastock'], 3) . '</td>';
//    $resultado .= ' <td> </td>';
//    $resultado .= ' <td> </td>';
//    $resultado .= ' </tr>';
//    $resultado .= ' </tbody>';
//
//    $resultado .= ' </table>';

                $fichero = 'mayor' . $idArticulo . '.pdf';
                $filename = $RutaServidor . $rutatmp . '/' . $fichero;
                $pdf->Output($filename, 'F');

                $resultado['html'] = $cabecera . ' ' . $cuerpo;
                $resultado['idproducto'] = $idArticulo;
                $resultado['datos'] = $sqldata['datos'];
                $resultado['fichero'] = '<a href="' . $rutatmp . '/' . $fichero . '" target="_blank">'
                        . '<span class="glyphicon glyphicon-print"></span> </a>';
            } else {
                if ($sqldata['error']) {
                    $resultado['error'] = $sqldata['error'];
                } else {
                    $resultado['error'] = 'No existen datos en el intervalo de fechas seleccionado';
                }
            }
            $resultado['consulta'] = $sqldata['consulta'];
            $resultado['idproducto'] = $idArticulo;
        } else {
            $resultado = 'No existe articulo';
        }
        echo json_encode($resultado);
        break;
}
