
<?php
/* Fichero de tareas a realizar.
 *
 *
 * Con el switch al final y variable $pulsado
 *
 *  */
/* ===============  REALIZAMOS CONEXIONES  ===============*/

$pulsado = $_POST['pulsado'];
include_once './../../inicial.php';
require_once $URLCom . '/clases/auth_guard.php';
tpvfox_require_auth(); // Cierra el bypass: exige sesion valida en el endpoint AJAX.
include_once './clases/ClaseImportarDbf.php';
$ruta_segura = $thisTpv->getRutaSegura();
$importarDbf = new ImportarDbf($ruta_segura);


switch ($pulsado) {

    case 'borrar_tabla':
        $respuesta = $importarDbf->EliminarTabla();
        error_log('Borrado' . json_encode($respuesta));
        break;

    case 'borrar_fichero':

        break;

    case 'EliminarUltimoRegistro':
        $id = $_POST['id_ultimo_registro'];
        $respuesta = $importarDbf->EliminarRegistroTabla($id);
        error_log('Borrado' . json_encode($respuesta));
        break;
}

/* ===============  CERRAMOS CONEXIONES  ===============*/

echo json_encode($respuesta)


?>
