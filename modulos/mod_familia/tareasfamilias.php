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


// Ya cargamos todo Usuario, tienda y configuracion
include_once ("./../../inicial.php");


//~ $rutaCompleta = $RutaServidor . $HostNombre;
//~ include_once($rutaCompleta . '/clases/ClaseSession.php');

//~ $CSession = new ClaseSession();

// Incluimos controlador.
include ("./../../controllers/Controladores.php");
$Controler = new ControladorComun;
// Incluimos funciones
include_once ("./funciones.php");
// Añado la conexion a controlador.
$Controler->loadDbtpv($BDTpv);

include_once './clases/ClaseFamilias.php';
include_once './funciones_familia.inc.php';

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
        break;

    case 'grabarPadres':
        $idpadre = $_POST['idpadre'];
        $idsfamilia = $_POST['idsfamilia'];
        $resultado = (new ClaseFamilias($BDTpv))->actualizarpadre($idpadre, $idsfamilia);
        break;
 
}
echo json_encode($resultado);

