<?php
include_once './../../inicial.php';
include_once './clases/ClaseImportarDbf.php';
$importarDbf = new ImportarDbf();
$mensajes=array();
$btn = '';
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
                            // Ahora debemos registrar campos y num_registros
                            // Debería controlar cuando falla el crear tabla
                            if (!isset($respuesta['error'])){
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
                                    $mensajes[] = $importarDbf->getAvisosHtml(12,'info','Insertado(s) '.$id.' registros');
                                } else {
                                    // Hubo un error al insertar
                                     $mensajes[] = $importarDbf->getAvisosHtml(14,'danger',$id['descripcion']);
                                     $estado = 'Error';
    
                                }
                            } else {
                                $mensajes[] =  $importarDbf->getAvisosHtml(13,'danger',$respuesta['error']);
                            }
                            
                        } else {
                            $mensajes[] = $importarDbf->getAvisosHtml(1,'dannger');
                        }
                    } else {
                        // Ya existe el fichero por lo que no movemos ( lo ideal seria generar un numero al final nombre... )
                        $mensajes[] = $importarDbf->getAvisosHtml(2,'danger');
                    }
                } else {
                        $mensajes[] = $importarDbf->getAvisosHtml(3,'warning');
                       
                }
            }
            
        }
    }  else {
        $mensajes[] = $importarDbf->getAvisosHtml(4,'warning');
        
    }

    // Ahora en segundo plano deberíamos ejecutar php importar...
    

} else {
   // Aquí redireccionamos a index.php vino directamente y no envio nada.
   header("Location: index.php");
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
        <?php
            foreach ($mensajes as $mensaje){
                echo $mensaje;
            }
        if ($estado=== 'Creado') {
            $btn =   ' <input type="submit" name="importarBtn" value="Empezar" />'
                    .' <input type="hidden" name="id_registro" value="'.$id.'" />'
                    .' <input type="hidden" name="token" value="'.$thisTpv->getTokenUsuario($Usuario).'" />'
    ;
            echo   '<form method="POST" action="importarDBF.php"><p>Importar DBF a Mysql:<br/>'.$btn.'</form>';
        }
        ?>
    </div>
</div>
 </body>
</html>





