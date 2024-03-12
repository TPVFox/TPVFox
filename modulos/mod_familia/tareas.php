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
include_once $URLCom . '/modulos/mod_familia/clases/ClaseFamilias.php';
include_once $URLCom . '/modulos/mod_familia/funciones.php';
$Cfamilias = new ClaseFamilias();
$pulsado = $_POST['pulsado'];
switch ($pulsado) {
    case 'leerFamilias':
        $idpadre = $_POST['idpadre'];
        $resultado = leerFamilias($idpadre);
        break;

    case 'leerTodasFamilias':
        $familias = (new ClaseFamilias())->todoslosPadres('', true);
        $resultado = $familias['datos'];
        break;
    case 'grabarFamilia':
        // comprobar datos en el lado servidor    
        $idFamilia = $_POST['id'];
        $familiaNombre = $_POST['nombrefamilia'];
        $familiaPadre = $_POST['idpadre'];
        $beneficiomedio = $_POST['beneficiomedio'];
        $mostrar_tpv =  $_POST['mostrar_tpv'];
        // COMPROBAR:
        // Que no estan vacios
        // que idpadre es >= 0 y un id existente
        // generar $resultado['error']
        $camposfamilia = compact('idFamilia', 'familiaNombre', 'familiaPadre', 'beneficiomedio','mostrar_tpv');
        $resultado = [];
        if ($familiaPadre >= 0) {
            $familia = new ClaseFamilias($BDTpv);
            $resultado['insert'] = $familia->grabar($camposfamilia);
            $resultado['error'] = $familia->hayErrorConsulta() ? $familia->getErrorConsulta() : '0';
        }
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
        break;

    case 'borrarFamilias':
        $familias = $_POST['idsfamilias'];
        $listaError = [];
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
        $resultado = compact(['error', 'listaError']);
        break;
        
    case 'eliminarReferenciaFamiliaTienda':
        $resultado = $Cfamilias->BorrarRelacionFamiliasTiendas($_POST['idFamilia'],$_POST['idTienda']);
        break;
        
    case 'anhadirRefTiendaWebDirecta':
        $idFamilia = $_POST['idFamilia'];
        $idFamiliaWeb = $_POST['idFamiliaTienda'];
        $idTienda = $_POST['idTienda'];
        // Comprobamos si idFamiliaWeb realmente no tiene relacion
        $resultado['comprobacion']= 'KO';
        if ($ClasePermisos->getAccion('VerFamiliaWeb',array('modulo'=>'mod_familia','vista'=>'familia.php'))== 1 && $idFamilia > 0 ){
            $ObjVirtuemart = $Cfamilias->SetPlugin('ClaseVirtuemartFamilia');
            if($idFamiliaWeb>0){
                $t = $ObjVirtuemart->todasFamilias();
                if (isset($t['error'])){
                  $resultado['error']=$Cfamilias->montarAdvertencia('danger',
                                        'Error de conexion con el siguiente error:<br/>'.json_encode($t['error'])
                                        );
                } else {
                    if (isset($t['Datos']['item'])){
                        $r = $Cfamilias->anhadirRelacionArrayTiendaFamilia($t['Datos']['item'],$idTienda);
                        if ( isset($r['familiasWebSinRelacion'])){
                            foreach ($r['familiasWebSinRelacion'] as $familiaSinRelacion){
                                if ($idFamiliaWeb == $familiaSinRelacion['virtuemart_category_id']){
                                    $resultado['comprobacion']= 'OK';
                                }  
                            }
                        }
                    }
                }
            }
        }
        if ($resultado['comprobacion']=='OK'){
            // Ahora grabamos relacion de familia nueva
            $resultado['insert'] = $Cfamilias->guardarRelacionFamiliasTiendas($idFamilia,$idTienda,$idFamiliaWeb);
        }
        break;

}
echo json_encode($resultado);






