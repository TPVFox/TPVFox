<?php
/* Ahora vamos mover el fichero subido, crear la tabla y importar los datos */
include_once './../../inicial.php';
include_once './clases/ClaseImportarDbf.php';
$importarDbf = new ImportarDbf();
$mensajes=array();
$btn = '';
$btn_eliminar= '';

// Lo primero obtenemos ultimo registro y vemos si esta fusionado, sino no podemos continuar.
// Obtenemos el ultimo registro
    $dregistro = $importarDbf->ultimoRegistro();
    $datos_registro =$dregistro['datos'][0];
    $estado = $datos_registro['estado'];
if ($estado === 'Fusionado')
{
    // Entonces continuamos 
    $estado = '';
    if (isset($_POST['uploadBtn']) && $_POST['uploadBtn'] == 'Enviar')
    {
        // Mas bien esto debería controlar que es el mismo token
        if (isset($_FILES['fichero']))
        {
            if ($_FILES['fichero']['error']){
                // El numero que indice le sumamos 4 para que nos de el texto correcto.
                $i = $_FILES['fichero']['error']+4;
                $mensajes[] = $importarDbf->getAvisosHtml($i,'dander');
            } else {
                // Subio ficheros ahora comprobamos formato.
                if ($_FILES['fichero']['type']==="application/x-dbf"){
                    // El formato de fichero es correcto.
                    $dir_subida = $thisTpv->getRutaUpload();
                    // Comprobamos si existe la ruta donde guardar el fichero subido.
                    if (file_exists($dir_subida)) {
                        $fichero_subido = $dir_subida .'/'. basename($_FILES['fichero']['name']);
                        // Ahora comprobamos que ya no exista el fichero , para que evitar que lo sobreescriba
                        if (!file_exists($fichero_subido)){
                            // Ahora lo movemos.
                            if (move_uploaded_file($_FILES['fichero']['tmp_name'], $fichero_subido)) {
                                // Se movio y subio correctamente , por lo que  creamos y registramos.
                                $mensajes[]=$importarDbf->getAvisosHtml(0,'info');
                                $respuesta = $importarDbf->crearEstructura($fichero_subido,$URLCom);
                                if (gettype($respuesta) === 'integer'){
                                    // Ahora registramos... fichero y estado lo ponemos Creado.
                                    $estado = 'Creado';
                                    $datos = array( 'datos_fichero' =>json_encode($_FILES['fichero']),
                                                    'token'=>$thisTpv->getTokenUsuario($Usuario),
                                                    'type' =>$_FILES['fichero']['type'],
                                                    'name' =>$_FILES['fichero']['name'],
                                                    'fecha_inicio' => date("Y-m-d H:i:s"),
                                                    'estado' => $estado,
                                                    'Registros_originales' => $importarDbf->Num_registros,
                                                    'campos' => json_encode($importarDbf->campos)
                                                );
                                    $id = $importarDbf->registroImportar($datos);
                                    if (gettype($id) === 'integer'){
                                        $mensajes[] = $importarDbf->getAvisosHtml(12,'info',$id);
                                    } else {
                                        // Hubo un error al insertar
                                         $mensajes[] = $importarDbf->getAvisosHtml(14,'danger',$respuesta['descripcion']);
                                         $estado = 'Error';
                                    }
                                } else {
                                    // Fallo al crear tabla
                                    // Ahora comprobamos si existe la tabla modulo_importar_ARTICULO y tiene registros, sin procesar.
                                    $existe_tabla = $importarDbf->InfoTabla('modulo_importar_ARTICULO');
                                    if ( isset($existe_tabla['error'])){
                                        // No existe la tabla por lo que realmente es un error grave, no debería pasar nunca.
                                        $mensajes[] = $importarDbf->getAvisosHtml(13,'danger');
                                        $estado = 'Error';

                                    } else {
                                        // Ahora comprobamos si tiene registros
                                        $rows = $existe_tabla['info']['Rows'];
                                        if ($rows === '0' ){
                                            // Esto es un error, está fusionando y existe tabla, pero no tiene registros.
                                            // permitimos eliminar tabla.
                                            $mensajes[] = $importarDbf->getAvisosHtml(13,'danger',',tiene 0 registros');
                                            $estado = 'Error';
                                        } 
                                    }
                                    $mensajes[] =  $importarDbf->getAvisosHtml(13,'warning',$respuesta['descripcion']);
                                }
                                
                            } else {
                                // Fallo al mover fichero.
                                $mensajes[] = $importarDbf->getAvisosHtml(1,'dannger');
                            }
                        } else {
                            // Ya existe el fichero por lo que no movemos ( lo ideal seria generar un numero al final nombre... )
                            $mensajes[] = $importarDbf->getAvisosHtml(2,'danger');
                        }
                    } else {
                        // NO existe ruta donde subir.
                        $mensajes[] = $importarDbf->getAvisosHtml(3,'warning');
                    }
                }
                
            }
        }  else {
            // No existe el fichero subido
            $mensajes[] = $importarDbf->getAvisosHtml(4,'warning');
        }        

    } else {
       // Aquí redireccionamos a index.php vino directamente y no envio nada.
       header("Location: index.php");
    }
} else {
    // El ultimo registro no esta fusionado , por lo que puede que se este ejecutando,
    // no continuamos.
    if ($estado === 'Importado'){
        echo '<a href ="importarDBF.php">Continuar</a>';
    }
    $mensajes[] = $importarDbf->getAvisosHtml(15,'warning');

}
?>
<!DOCTYPE html>
<html>
<head>
<?php
    include_once $URLCom.'/head.php';
?>

</head>
<body>
<?php 
     include_once $URLCom.'/modulos/mod_menu/menu.php';
?>
<div class="container">

    <div class="col-md-12">
        <h1> Fichero subido y creando tabla</h1>
        <?php
            echo 'Estado:'.$estado;
            foreach ($mensajes as $mensaje){
                echo $mensaje;
            }
        if ($estado=== 'Creado') {
            $btn =   ' <input type="submit" name="importarBtn" value="Empezar" />'
                    .' <input type="hidden" name="token" value="'.$thisTpv->getTokenUsuario($Usuario).'" />';
            echo   '<form method="POST" action="importarDBF.php"><p>Importar DBF a Mysql:<br/>'.$btn.'</form>';
        }
        ?>
    </div>
</div>
 </body>
</html>





