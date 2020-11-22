<?php
include_once './../../inicial.php';
include_once './clases/ClaseImportarDbf.php';
$importarDbf = new ImportarDbf();
$mensajes=array();
$estado = '';// Los posibles estado del registro son 'Creado','Importado' y 'Fusionado', aqui solo debería llegar cuano este en alguno de esos estados.
// Obtenemos el ultimo registro
    $dregistro = $importarDbf->ultimoRegistro();
    $datos_registro =$dregistro['datos'][0];
    $estado = $datos_registro['estado'];
    // Ahora compruebo si ya se esta ejecuntado el fichero segundo plano
    $ejecutando = $importarDbf->comprobarSiEjecutaSegundoplano();
    if ($ejecutando === 0){
        exec("php -f ./segundo_plano.php > /dev/null &");
        if (isset($_POST['token']) && isset($_POST['importarBtn']))
        {
            if ($_POST['token'] !== $datos_registro['token']){
                // Quiere no es la misma session, esto puede suceder, ya que vamos ejecutar el proceso segundo plano.
                echo '<pre>';
                print_r('No es la misma session');
                echo '</pre>';
            }
        
        }   
    }
    $Num_registros_estado = $importarDbf->contarRegistrosPorEstado(); // Obtener datos importacion.
    $tabla_info = $importarDbf->InfoTabla('modulo_importar_ARTICULO');
    if (!isset ($tabla_info['error'])){
        // No hubo error al obtener datos.
        $pendiente_importar =   strval($datos_registro['Registros_originales'])-
                                strval($datos_registro['nulos'])-
                                strval($datos_registro['errores'])-$tabla_info['info']['Rows'];
    }
    $htmlBarra = array();
    // Creamos dos baras con id= bar1 y bar 2 
    for ($i = 1; $i <= 2; $i++) {
        $htmlBarra[]= '<div class="progress" style="margin:0 100px">
                            <div id="bar'.$i.'" class="progress-bar progress-bar-info" 
                                 role="progressbar" aria-valuenow="0" 
                                 aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                                0 % completado
                            </div>
                        </div> ';
    }
    $registros_fusionado = $tabla_info['info']['Rows'] - $Num_registros_estado['NULL'];
    
?>
<!DOCTYPE html>
<html>
<head>
<?php
    include_once $URLCom.'/head.php';
?>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>

</head>
<body>
<?php 
     include_once $URLCom.'/modulos/mod_menu/menu.php';
?>
<div class="container">

    <div class="col-md-12">
        <h2 class="text-center">Importando y fusionando G4 con tpvfox</h2>
        <table>
            <tbody>
                <tr>
                    <td>
                        <strong>Importamos:</strong><br/>
                        <?php
                        if ($estado === 'Creado'){
                            echo '<span class ="parpadea texto-warning">PROCESANDO</span><br/>';
                        } else {
                            echo '<span class ="text-primary">TERMINADO</span><br/>';
                        }
                        echo $htmlBarra[0];
                        ?>
                        Los datos de la tabla Articulos a Tpvfox, en principio debería ser:<br/>
                        Numeros registros en DBF: <strong><?php echo $datos_registro['Registros_originales'].'.';?></strong> sin filtrar los que tiene Delete DBF.<br/>
                        <?php
                        if (!isset($tabla_info['error'])){
                            echo 'Registros nulo:<strong>'.$datos_registro['nulos'].'</strong><br/>';
                            echo 'Registros errores:<strong>'.$datos_registro['errores'].'</strong><br/>';
                            echo 'Numero registros creados:<strong>'.$tabla_info['info']['Rows'].'</strong><br/>';
                            echo 'Pendiente crear o delete:<strong>'. $pendiente_importar.'</strong><br/>';
                            echo 'No se puede identicar cuantos faltarian ya que no tenemos datos que cuantos estan marcados como delete en DBF,
                            por ese motivo la barra puede queda incompleta.<br/>';
                           
                        } else {
                            echo '<div class="alert alert-danger">';
                            echo '<strong>¡¡Error !!</strong><br/>'.$tabla_info['error'];
                            echo '</div>';
                        }
                        ?>
                        
                        
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>Fusionamos:</strong><br/>
                        <?php
                        if ($estado === 'Importado'){
                            echo '<span class ="parpadea texto-warning">PROCESANDO</span><br/>';
                        } else {
                            // Aqui puede llegar si termino o no empezo.
                            if ($estado === 'Fusionado'){
                                echo '<span class ="text-primary">TERMINADO</span><br/>';
                            } else  {
                                echo '<span class ="texto-warning">SIN EMPEZAR FUSIONAR</span><br/>';
                            }
                        }
                        echo $htmlBarra[1];
                        ?>
                        Creados nuevos:<?php echo $Num_registros_estado['nuevo'];?><br/>
                        Actualizados:<?php echo $Num_registros_estado['actualizado'];?><br/>
                        Errores:<?php echo  $Num_registros_estado['error'];?><br/>
                        Pedientes por procesar: <?php echo  $Num_registros_estado['NULL'];?>
                    </td>
                </tr>
            </tbody>
        </table>
        
    </div>
</div>
 <script>
    <?php
        // Calculo los importados.
        $num_importados = $datos_registro['Registros_originales']-$pendiente_importar;
    ?>
    var inicio = <?php echo $num_importados;?> ;
    var total = <?php echo $datos_registro['Registros_originales']; ?>;
    var idBar = '1';
    BarraProceso(inicio,total,idBar);
    var inicio = <?php echo $registros_fusionado;?> ;
    var total = <?php echo $tabla_info['info']['Rows']; ?>;
    var idBar = '2';
    BarraProceso(inicio,total,idBar);
    $(document).ready(function(){
        //Cada 10 segundos se ejecutará la función refrescar
        setTimeout(refrescar, 30000);
    });
    function refrescar(){
        //Actualiza la el div con los datos de imagenes.php
        location.reload();
      }
</script>
 </body>
</html>









