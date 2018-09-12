<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
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


include_once './../../inicial.php';
include_once $URLCom . '/configuracion.php';
include_once $URLCom . '/modulos/mod_conexion/conexionBaseDatos.php';
include_once $URLCom . '/controllers/Controladores.php';


$rutaCompleta = $RutaServidor . $HostNombre;
//include_once($rutaCompleta . '/clases/ClaseSession.php');
//$CSession = new ClaseSession();
// Incluimos controlador.
$Controler = new ControladorComun;
// Incluimos funciones
//include_once ("./funciones.php");
// Añado la conexion a controlador.
$Controler->loadDbtpv($BDTpv);
// Nueva clase 
//include ("./clases/ClaseProductos.php");
//$NCArticulo = new ClaseProductos($BDTpv);
//include_once('../../clases/articulos.php');
//$CArticulo = new Articulos($BDTpv);
//
//include_once ('../mod_compras/clases/albaranesCompras.php');
//$CAlbaran = new AlbaranesCompras($BDTpv);
//
//include_once('../../clases/Proveedores.php');
//$CProveedor = new Proveedores($BDTpv);
include_once $URLCom . '/modulos/mod_familia/clases/ClaseFamilias.php';
include_once $URLCom . '/modulos/mod_familia/funciones_familia.inc.php';

//include_once './clases/ClaseArticulos.php';
//include_once './funciones_mayor.inc.php';


$pulsado = $_POST['pulsado'];

switch ($pulsado) {
    case 'leerFamilias':
        $idpadre = $_POST['idpadre'];
        $resultado = leerFamilias($idpadre);
        echo json_encode($resultado);
        break;

    case 'leerTodasFamilias':
        $familias = (new ClaseFamilias())->todoslosPadres('', true);
        echo json_encode($familias['datos']);
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
        $idFamilia = $_POST['id'];
        $familiaNombre = $_POST['nombrefamilia'];
        $familiaPadre = $_POST['idpadre'];
        $beneficiomedio = $_POST['beneficiomedio'];


// COMPROBAR:
// Que no estan vacios
// que idpadre es >= 0 y un id existente
// generar $resultado['error']

        $camposfamilia = compact('idFamilia', 'familiaNombre', 'familiaPadre', 'beneficiomedio');
        $resultado = [];
        $resultado['href'] = $_POST['href'];
        if ($familiaPadre >= 0) {
            $familia = new ClaseFamilias($BDTpv);
            $resultado['insert'] = $familia->grabar($camposfamilia);
            $resultado['error'] = $familia->hayErrorConsulta() ? $familia->getErrorConsulta() : '0';
        }
        echo json_encode($resultado);
        break;

    case 'grabarPadres':
        $idpadre = $_POST['idpadre'];
        $idsfamilia = $_POST['idsfamilia'];
        $familia = (new ClaseFamilias($BDTpv))->actualizarpadre($idpadre, $idsfamilia);
        echo json_encode($familia);
        break;

    case 'descendientes':
        $idfamilia = $_POST['idfamilia'];
        $familia = new ClaseFamilias($BDTpv);
        $resultado = $familia->familiasSinDescendientes($idfamilia);
        echo json_encode($resultado);
        break;

    case 'cambiarFamiliaProducto':
        $idfamilia = $_POST['idfamilia'];
        $idnuevafamilia = $_POST['idnuevafamilia'];
        $idsproductos = $_POST['idsproductos'];

//        $productos = explode(',', $idsproductos);
        $resultado = [];
        $listaerror = [];
        foreach ($idsproductos as $idproducto) {
            $todobien = alArticulos::borrarArticuloFamilia($idproducto, $idfamilia);
            if (($todobien) && (!alArticulos::existeArticuloFamilia($idproducto, $idnuevafamilia))) {
                $todobien = alArticulos::grabarArticuloFamilia($idproducto, $idnuevafamilia);
                if (!$todobien) {
                    $listaerror[] = [$idproducto, $idnuevafamilia];
                }
            } else {
                $listaerror[] = 'existe???' . $idproducto;
            }
        }

        $resultado['html'] = htmlTablaFamiliaProductos($idfamilia);
        $resultado['error'] = count($listaerror) > 0; // no hay errores. ¿Para que devolver la lista de errores? :-)
        echo json_encode($resultado);
        break;

    case 'borrarFamiliaProducto':
        $idfamilia = $_POST['idfamilia'];
        $idsproductos = $_POST['idsproductos'];

        $listaerror = [];
        $resultado = [];
        foreach ($idsproductos as $idproducto) {
            $todobien = alArticulos::borrarArticuloFamilia($idproducto, $idfamilia);
            if (!$todobien) {
                $listaerror[] = $idproducto;
            }
        }
        $resultado['html'] = htmlTablaFamiliaProductos($idfamilia);
        $resultado['error'] = count($listaerror) > 0; // no hay errores. ¿Para que devolver la lista de errores? :-)
        echo json_encode($resultado);
        break;

    case 'borrarFamilias':
        $familias = $_POST['idsfamilias'];
        $listaError = [];
//        $familias = explode(',', $idsfamilias);

        $familia = new ClaseFamilias();
        foreach ($familias as $idfamilia) {
            $productos = $familia->contarProductos($idfamilia);
            $descendientes = $familia->contarHijos($idfamilia);
            if (($productos == 0) && ($descendientes == 0)) {
                $resultado = $familia->Borrar($idfamilia);
                if ($resultado['error'] <> 0) {
                    $listaError[] = $idfamilia;
                    $listaError[] = $resultado;
                }
            } else {
                $listaError[] = [$idfamilia, $productos, $descendientes];
            }
        }
        $error = count($listaError) > 0;
        echo json_encode(compact(['error', 'listaError']));
        break;
}




