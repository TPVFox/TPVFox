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

include_once ("./../../configuracion.php");

// Crealizamos conexion a la BD Datos
include_once ("./../mod_conexion/conexionBaseDatos.php");


$rutaCompleta = $RutaServidor . $HostNombre;
include_once($rutaCompleta . '/clases/ClaseSession.php');

$CSession = new ClaseSession();

// Incluimos controlador.
include ("./../../controllers/Controladores.php");
$Controler = new ControladorComun;
// Incluimos funciones
include_once ("./funciones.php");
// Añado la conexion a controlador.
$Controler->loadDbtpv($BDTpv);

include_once './clases/ClaseFamilias.php';
include_once './clases/ClaseArticulos.php';
include_once './funciones_familia.inc.php';
include_once './funciones_mayor.inc.php';

switch ($pulsado) {

    case 'leerFamilias':
        $idpadre = $_POST['idpadre'];
        $resultado = [];
        $resultado['padre'] = $idpadre;
        if ($idpadre >= 0) {
            $familias = (new ClaseFamilias($BDTpv))->leerUnPadre($idpadre);
        } else {
            $familias['datos'] = [];
        }
        $resultado['datos'] = $familias['datos'];
        $resultado['html'] = familias2Html($familias['datos']);
        echo json_encode($resultado);
        break;

    case 'BuscaNombreFamilia':
        $nombre = $_POST['nombre'];

        $resultado = [];
        $familias = (new ClaseFamilias($BDTpv))->buscaXNombre($nombre);
        if (!isset($familias['error'])) {
            $datos = $familias['datos'];
            foreach ($datos as $dato) {
                $resultado [] = ['label' => $dato['familiaNombre'], 'valor' => $dato['idFamilia']];
            }
        } else {
            $resultado = $familias['error'];
        }
        echo json_encode($resultado);
        break;

    case 'grabarFamilia':

        // comprobar datos en el lado servidor    
        $idpadre = $_POST['idpadre'];
        $nombre = $_POST['nombre'];
        // Que no estan vacios
        // que idpadre es >= 0 y un id existente
        // generar $resultado['error']

        $resultado = [];
        $resultado['familiaPadre'] = $idpadre;
        $resultado['familiaNombre'] = $nombre;
        if ($idpadre >= 0) {
            $familia = new ClaseFamilias($BDTpv);
            $resultado['insert'] = $familia->grabar($resultado);
        }
        echo json_encode($resultado);
        break;

    case 'grabarPadres':
        $idpadre = $_POST['idpadre'];
        $idsfamilia = $_POST['idsfamilia'];
        $resultado = (new ClaseFamilias($BDTpv))->actualizarpadre($idpadre, $idsfamilia);
        echo json_encode($resultado);
        break;

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
            $nombreArticulo = $miarticulo['datos'][0]['articulo_name'];

            $sqldata = $articulo->calculaMayor(compact("fechainicio", "fechafinal", "idArticulo"));

            if ($sqldata['datos']) {
                $sumastock = $stockinicial;
                foreach ($sqldata['datos'] as $indice => $linea) {
                    $sumastock += $linea['entrega'] - $linea['salida'];
                    $sqldata['datos'][$indice]['stock'] = $sumastock;
                }
                $cabecera = cabeceramayor2html(['titulo' => 'Mayor productos'
                    , 'empresa' => '01 Alimentaria Longueicap 2018'
                    , 'condiciones' => 'Periódo: ' . $fechainicio . ' / ' . $fechafinal
                ]);
                $cuerpo = datamayor2html($sqldata['datos']);
                $pdf = new imprimirPDF();
                $pdf->SetFont(PDF_FONT_NAME_MAIN, '', 8);
                $pdf->SetMargins(10, 70, 10);
                $pdf->setCabecera($cabecera);
                $pdf->AddPage();
                $pdf->writeHTML($cuerpo);
                $fichero = 'mayor'.time().'.pdf';
                $filename = $RutaServidor.$rutatmp.'/'.$fichero;
                $pdf->Output($filename, 'F');

                $resultado['html'] = $cuerpo;
                $resultado['fichero'] = $rutatmp.'/'.$fichero;
            } else {
                $resultado = 'Error en "sqldata"';
            }
        } else {
            $resultado = 'No existe articulo';
        }
         echo json_encode($resultado);
       break;
}