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
        $articulo = new alArticulos($BDTpv);
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
                $cuerpo = datamayor2html($sqldata['datos'], $sumas);
                $pdf = new imprimirPDF();
                $pdf->SetFont(PDF_FONT_NAME_MAIN, '', 8);
                $pdf->SetMargins(10, 60, 10);
                $pdf->setCabecera($cabecera);
                $pdf->AddPage();
                $pdf->writeHTML($cuerpo);
                $fichero = 'mayor' . $idArticulo . '.pdf';
                $filename = $RutaServidor . $rutatmp . '/' . $fichero;
                $pdf->Output($filename, 'F');

                $resultado['html'] = $cabecera . ' ' . $cuerpo;
                $resultado['idproducto'] = $idArticulo;
                $resultado['datos'] = $sqldata['datos'];
                $resultado['fichero'] = '<a href="' . $rutatmp . '/' . $fichero . '" target="_blank">'
                        . '<span class="glyphicon glyphicon-print"></span> </a>';
            } else {
                $resultado['error'] = 'Error en "sqldata"';
            }
            $resultado['consulta'] = $sqldata['consulta'];
            $resultado['idproducto'] = $idArticulo;
        } else {
            $resultado = 'No existe articulo';
        }
        echo json_encode($resultado);
        break;
}
