<?php
include_once './../../inicial.php';
include_once './clases/ClaseImportarDbf.php';
$ruta_segura = $thisTpv->getRutaSegura();
$importarDbf = new ImportarDbf($ruta_segura);
$mensajes=array();
$estado = '';// Los posibles estado del registro son 'Creado','Importado' y 'Fusionado', aqui solo debería llegar cuano este en alguno de esos estados.
// Obtenemos el ultimo registro
    $dregistro = $importarDbf->ultimoRegistro();
    $datos_registro =$dregistro['datos'][0];
    $estado = $datos_registro['estado'];
    // Ahora compruebo si ya se esta ejecuntado el fichero segundo plano
    $ejecutando = $importarDbf->comprobarSiEjecutaSegundoplano();
    if ($ejecutando === 0){
        error_log('Compruebo si se esta ejecutando valor:'.$ejecutando);
        exec("php -f ./segundo_plano.php > /dev/null &");
        // Ejecutamos... por lo que
        $ejecutando = $importarDbf->comprobarSiEjecutaSegundoplano();
        if ($ejecutando === 0) {
            error_log('No inicia php segundo_plano.php');
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
    // Creamos barra de control fusionar 
        $htmlBarra= '<div class="progress" style="margin:0 100px">
                            <div id="bar2" class="progress-bar progress-bar-info" 
                                 role="progressbar" aria-valuenow="0" 
                                 aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                                0 % completado
                            </div>
                        </div> ';
    
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
                        <strong>Importamos datos de DBF:</strong><br/>
                        <?php
                        if ($estado === 'Creado'){
                            echo '<span class ="parpadea texto-warning">PROCESANDO</span><br/>';
                        } else {
                            echo '<span class ="text-primary">TERMINADO</span><br/>';
                        }
                        ?>
                        Informacion de la tabla que importamos:<br/>
                        Numeros registros en DBF: <strong><?php echo $datos_registro['Registros_originales'].'.';?></strong>. Desconocemos cuantos estan marcados como delete en DBF.<br/>
                        <?php
                        if (!isset($tabla_info['error'])){
                            echo 'Registros nulo que filtramos ya al importar:<strong>'.$datos_registro['nulos'].'</strong><br/>';
                            echo 'Registros errores:<strong>'.$datos_registro['errores'].'</strong><br/>';
                            echo 'Numero registros creados:<strong>'.$tabla_info['info']['Rows'].'</strong><br/>';
                            echo 'Pendientes crear con los marcados delete:<strong>'. $pendiente_importar.'</strong><br/>';
                            echo 'Si esta terminado el proceso importar estos son los que no se crearon.<br/>';
                           
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
                        echo $htmlBarra;
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
    var estado = '<?php echo $estado;?>';
    var inicio = <?php echo $registros_fusionado;?> ;
    var total = <?php echo $tabla_info['info']['Rows']; ?>;
    var idBar = '2';
    if (inicio > 0){
        BarraProceso(inicio,total,idBar);
    }
    if (estado !== 'Fusionado'){
        $(document).ready(function(){
            //Cada 10 segundos se ejecutará la función refrescar
            setTimeout(refrescar, 10000);
        });
        if (inicio == 0){
            alert('Está fusionando pero inicio es 0');
        }
        function refrescar(){
            //Actualiza la el div con los datos de imagenes.php
            location.reload();
          }
    }
</script>

 </body>
</html>









