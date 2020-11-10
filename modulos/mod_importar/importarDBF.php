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
    echo '<pre>';
    print_r($estado);
    echo '</pre>';
// Ahora compruebo si ya se esta ejecuntado el fichero segundo plano
$command = 'ps aux | grep "[p]hp.*segundo_plano"';
exec($command ,$ejecutando); // Compruebo si se esta ejecutando fusionar_eelectronica.php

if (count($ejecutando) >0){
    // Se esta ejecuntando fussion , por lo que no podemos volver ejecutar.
    echo '<pre>';
    echo 'Se esta ejecuntando fussion , por lo que no podemos volver ejecutar.';
    print_r($ejecutando);
    echo '</pre>';
} else {
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

        
        echo '<pre>';
            print_r('comprobar con javascript periodicamente');
            echo '</pre>';
        ?>
    </div>
</div>
 </body>
</html>









