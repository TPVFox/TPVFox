
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
include_once './clases/ClaseImportarDbf.php';
$importarDbf = new ImportarDbf();


 switch ($pulsado) {
     
    case 'borrar_tabla':
        $respuesta = $importarDbf->EliminarTabla();
        error_log('Borrado'.json_encode($respuesta));
	break;

    case 'borrar_fichero':
            
    break;

    case 'EliminarUltimoRegistro':
        $id = $_POST['id_ultimo_registro'];
        $respuesta = $importarDbf->EliminarRegistroTabla($id);
        error_log('Borrado'.json_encode($respuesta));
    break;

	
}
 
/* ===============  CERRAMOS CONEXIONES  ===============*/

echo json_encode($respuesta)
 
 
?>
