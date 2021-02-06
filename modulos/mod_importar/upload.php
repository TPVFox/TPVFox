<?php
/* Ahora vamos mover el fichero subido, crear la tabla y importar los datos */
include_once './../../inicial.php';
include_once './clases/ClaseImportarDbf.php';
$importarDbf        = new ImportarDbf();
$mensajes           = array();
$errores            = array();
$btn_eliminar_fichero = '';
$btn_eliminar_registro= '';
$btn_eliminar_tabla = '';
// El btn_importarDBF tiene do textos aunque es un link que va al mismo sitio siempre.
$btn_importarDBF    = '';
$estado             = '';
$fichero_subido     = '';
$sum_errores        = 0; // Pongo este valor para que no se ejecute, solo se ejecuta si es 0

// ====================== Montamos array de posibles errores ========================== //
// En metodo getAvisoHtml hay indice de errores que son los que utilizamos.
// del 4 al 11 son las respuesta de $_FILES['error'] que pudieran tener.
$errores = array (  'ejecutando_php'    => array ('indice' =>0,'tipo'=>'warning','mas_info'=>''),
                    'envio_fichero'     => array ('indice' =>0,'tipo'=>'danger','mas_info'=>''),
                    'fichero_subido'    => array ('indice' =>0,'tipo'=>'danger','mas_info'=>''),
                    'existe_fichero'    => array ('indice' =>0,'tipo'=>'warning','mas_info'=>''),
                    'tabla_registro'    => array ('indice' =>0,'tipo'=>'danger','mas_info'=>''),
                    'tabla_importar'    => array ('indice' =>0,'tipo'=>'warning','mas_info'=>'')
        );
// Compruebo que no se este ejecutando en segundo_plano
if ($importarDbf->comprobarSiEjecutaSegundoplano() !== 0){
    // Se esta ejecutando.
    $errores['ejecutando_php']['indice'] = 16 ;
    // Montamos el boton para se pueda ver el proceso
    $btn_importarDBF = $importarDbf->htmlBtnImportarDBF('Ver como va Importacion','info');
}
// Existe tabla de modulo_importar_ARTICULO
$existe_tabla = $importarDbf->InfoTabla('modulo_importar_ARTICULO');
if (!isset($existe_tabla['error'])){
    // Existe tabla marcamos el error y btn para eliminar o pasar importarDBF.
    $errores['tabla_importar']['indice'] = 20;
    $btn_eliminar_tabla ='<button class="btn btn-warning" onclick="metodoClick('."'EliminarTabla'".')">Borrar tabla</button>';
    if ($btn_importarDBF === ''){
        // Es que existe tabla modulo_importar_ARTICULO y el proceso segundo plano esta parado.
        $btn_importarDBF = $importarDbf->htmlBtnImportarDBF('Reinicia o ver ultima Importacion','info');
    }
} 
// Se envio fichero ?
$errores['envio_fichero']['indice'] = (isset($_FILES['fichero']))? 0 : 4; // Si envio fichero el valor es 0 y si no 4 
// Ahora compruebo si no hubo error fichero subido y si se puede mover
if (isset($_FILES['fichero']['error']) && $_FILES['fichero']['error'] >0){
    $errores['fichero_subido']['indice'] = $_FILES['fichero']['error']+4;
} else {
    $errores['fichero_subido']['indice'] = 0;
    // Se subio fichero sin errores y es dbf
    $dir_subida = $thisTpv->getRutaUpload();
    $fichero_subido = $dir_subida .'/'. basename($_FILES['fichero']['name']);
    // Ahora comprobamos que no existe el fichero que acabos de subir en directorio Upload de configuracion
    if (file_exists($fichero_subido)){
        $errores['existe_fichero']['indice'] = 2;
    }
}
// Obtengo ultimo registro y su estado.
$dregistro = $importarDbf->ultimoRegistro();
if (isset($dregistro['datos'])){
    // Tiene datos, tomamos el ultimo registro
    $datos_registro_estado  = $dregistro['datos'][0]['estado'];
    $id_ultimo_registro     = $dregistro['datos'][0]['id'];
    if ( $datos_registro_estado !== 'Fusionado'){
        // Nos indica que la ultima importacion no se termino correctamente, o esta en proceso.
        // obtenemos btn para eliminar ultimo registro
        $btn_eliminar_registro = $importarDbf->htmlBtnEliminarUltimoRegistro($id_ultimo_registro);
        $error['tabla_registro']['indice'] = 15;
        $error['tabla_registro']['mas_info'] = $datos_registro_estado ;
    }

} else {
    // O hubo un error o no hay registros.
    if (!isset($dregistro['error'])){
        // No fue un error, No hay datos
        $datos_registro_estado = 'Nuevo';
        $mensajes[] = $importarDbf->getAvisosHtml(17,'info',',tiene 0 registros');
    } else {
        $error['tabla_registro']['indice'] = 19;
        $error['tabla_registro']['mas_info'] = $dregistro['error'];
    }
}
// Ahora sumamos errores , si no hay entonces será 0 , valido para continuar.
foreach ($errores as $error){
    $sum_errores += $error['indice'];
}
// ====================== Fin montar array de posibles errores ========================== //

// ====== Empezasmos mover fichero y crear tabla ========= //
//~ echo '<pre>';
//~ print_r($errores);
//~ echo '</pre>';
if ($sum_errores === 0 )
{
    $estado = 'Error'; // Por defecto indico que el estado es error.
    if (move_uploaded_file($_FILES['fichero']['tmp_name'], $fichero_subido))
    {
        // Se movio y subio correctamente , por lo que  creamos y registramos.
        $mensajes[]=$importarDbf->getAvisosHtml(0,'info');
        $respuesta = $importarDbf->crearEstructura($fichero_subido,$URLCom);
        if (gettype($respuesta) === 'integer'){
            // Ahora registramos... fichero y estado .
            $datos = array( 'datos_fichero' =>json_encode($_FILES['fichero']),
                            'token'=>$thisTpv->getTokenUsuario($Usuario),
                            'type' =>$_FILES['fichero']['type'],
                            'name' =>$_FILES['fichero']['name'],
                            'fecha_inicio' => date("Y-m-d H:i:s"),
                            'estado' => 'Creado',
                            'Registros_originales' => $importarDbf->Num_registros,
                            'campos' => json_encode($importarDbf->campos)
                        );
            $id = $importarDbf->registroImportar($datos);
            if (gettype($id) === 'integer'){
                $mensajes[] = $importarDbf->getAvisosHtml(12,'info',$id);
                // Como fue correcto el crear y registrar.
                $estado = 'Creado';
            } else {
                // Hubo un error al insertar resgistro importar.
                 $mensajes[] = $importarDbf->getAvisosHtml(14,'danger',$respuesta['descripcion']);
            }
        } else {
            // Fallo al crear tabla modulo_importar_ARTICULO
            $mensajes[] =  $importarDbf->getAvisosHtml(13,'warning',$respuesta['descripcion']);
        }
        
    } else {
        // Fallo al mover fichero.
        $mensajes[] = $importarDbf->getAvisosHtml(1,'dannger');
    }
    
} else {
    // Hubo errores o se está ejecutando
    // Ahora montamo los avisos para mostrarlos.
    foreach ($errores as $error){
        if ($error['indice']>0){
            $mensajes[] = $importarDbf->getAvisosHtml($error['indice'],$error['tipo'],$error['mas_info']);
        }
    }
    
}
?>
<!DOCTYPE html>
<html>
<head>
<?php
    include_once $URLCom.'/head.php';
?>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_importar/funciones.js"></script>

</head>
<body>
<?php 
     include_once $URLCom.'/modulos/mod_menu/menu.php';
?>
<div class="container">

    <div class="col-md-12">
        <h1> Analizamos Fichero subido y creando tabla.</h1>
        <?php
            foreach ($mensajes as $mensaje){
                echo $mensaje;
            }
        if ($errores['ejecutando_php']['indice'] === 0){
                echo $btn_eliminar_fichero;
                echo $btn_eliminar_registro;
                echo $btn_eliminar_tabla;
        }
        if ($estado === 'Creado'){
            $btn_importarDBF = $importarDbf->htmlBtnImportarDBF('Iniciar Importacion','primary');
        }
        echo $btn_importarDBF;
        ?>
    </div>
</div>
 </body>
</html>





