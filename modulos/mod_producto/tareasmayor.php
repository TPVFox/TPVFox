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
include_once $URLCom.'/controllers/Controladores.php';
// Crealizamos conexion a la BD Datos

$Controler = new ControladorComun;
// Añado la conexion a controlador.
$Controler->loadDbtpv($BDTpv);

include_once $URLCom.'/modulos/mod_producto/clases/ClaseArticulos.php';
include_once $URLCom.'/modulos/mod_producto/funciones_mayor.inc.php';

$inicio = microtime(true);
switch ($pulsado) {

    case 'imprimePDFMayor':
        $idArticulo = $_POST['idproducto'];
        $ficheroCa = $RutaServidor . $rutatmp . '/' . 'cabecera_' . $idArticulo . '.htmp';
        if (file_exists($ficheroCa)) {
            $fp = file_get_contents($ficheroCa);
            $cabecera = json_decode($fp);
            $ficheroCu = $RutaServidor . $rutatmp . '/' . 'cuerpo_' . $idArticulo . '.htmp';
            if (file_exists($ficheroCu)) {
                $fp = file_get_contents($ficheroCu);
                $cuerpo = json_decode($fp);

                $pdf = new imprimirPDF();
                $pdf->SetFont(PDF_FONT_NAME_MAIN, '', 8);
                $pdf->SetMargins(10, 30, 10);
                $pdf->setCabecera($cabecera);
                $pdf->AddPage();
                $pdf->writeHTML($cuerpo);
                $fichero = 'mayor' . $idArticulo . '.pdf';
                $filename = $RutaServidor . $rutatmp . '/' . $fichero;
                $pdf->Output($filename, 'F');
            }
        }
        $resultado['idproducto'] = $idArticulo;
        $resultado['fichero'] = '<a href="' . $rutatmp . '/' . $fichero . '" target="_blank">'
                . '<span class="glyphicon glyphicon-print"></span> </a>';
        $resultado['tiempo'] = microtime(true) - $inicio;

        echo json_encode($resultado);
        break;

    case 'imprimemayor':
        // Aqui venía cuando pulsabamos en generar mayor despues escoger la fechas.
        
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
            
            $nombreArticulo = $miarticulo[0]['idArticulo'] . ' ' . $miarticulo[0]['articulo_name'];

            $fecha = explode('/', $fechainicio);
            $fechadesde = $fecha[2] . '/' . $fecha[1] . '/' . $fecha[0];

            $fecha = explode('/', $fechafinal);
            $fechahasta = $fecha[2] . '/' . $fecha[1] . '/' . $fecha[0];

            $Tienda = $_SESSION['tiendaTpv'];
            $Usuario = $_SESSION['usuarioTpv'];
            $sqldata = $articulo->calculaMayor(compact("fechadesde", "fechahasta", "idArticulo", "Tienda", "Usuario"));

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
                $empresa = $Tienda['idTienda'].$Tienda['razonsocial'];
                $cabecera = cabeceramayor2html(['titulo' => 'Mayor productos'
                    , 'empresa' => $empresa
                    , 'condiciones' => 'Periódo: ' . $fechainicio . ' / ' . $fechafinal
                    , 'producto' => $nombreArticulo
                ]);
                $cuerpo = datamayor2html($sqldata['datos'], $sumas);

                $resultado['filecabecera'] = file_put_contents($RutaServidor . $rutatmp . '/' . 'cabecera_' . $idArticulo . '.htmp', json_encode($cabecera), LOCK_EX);
                $resultado['filecuerpo'] = file_put_contents($RutaServidor . $rutatmp . '/' . 'cuerpo_' . $idArticulo . '.htmp', json_encode($cuerpo), LOCK_EX);
                $resultado['fileca'] = 'cabecera_' . $idArticulo . '.htmp';
                $resultado['tiempo'] = microtime(true) - $inicio;
//                $pdf = new imprimirPDF();
//                $pdf->SetFont(PDF_FONT_NAME_MAIN, '', 8);
//                $pdf->SetMargins(10, 30, 10);
//                $pdf->setCabecera($cabecera);
//                $pdf->AddPage();
//                $pdf->writeHTML($cuerpo);
//                $fichero = 'mayor' . $idArticulo . '.pdf';
//                $filename = $RutaServidor . $rutatmp . '/' . $fichero;
//                $pdf->Output($filename, 'F');

                $resultado['html'] = $cabecera . ' ' . $cuerpo;
//                $resultado['idproducto'] = $idArticulo;   //Está repe
//                $resultado['datos'] = $sqldata['datos'];
//                $resultado['fichero'] = '<a href="' . $rutatmp . '/' . $fichero . '" target="_blank">'
//                        . '<span class="glyphicon glyphicon-print"></span> </a>';
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
