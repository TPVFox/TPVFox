<?php
include './clases/ClaseImportarDbf.php';
$importarDbf = new ImportarDbf();
$mensajes=array();
$estado = '';
// Obtenemos el ultimo registro
    $datos_registro = $importarDbf->ultimoRegistro();
    echo '<pre>';
    print_r($datos_registro);
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
        if ($_POST['token'] !== $usuario_sesion->getToken()){
            // Quiere no es la misma session, esto puede suceder, ya que vamos ejecutar el proceso segundo plano.
            echo '<pre>';
            print_r('No es la misma session');
            echo '</pre>';
        }
        
        // Mas bien esto debería controlar que es el mismo token
        echo '<pre>';
        print_r($_POST);
        echo '</pre>';
        echo '<pre>';
        print_r($usuario_sesion->getToken());
        echo '</pre>';
        
    }

    
}

?>
<html>
 <head>
  <title>Importando dbf a mysql</title>
  <link href="css/bootstrap431/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/template.css" rel="stylesheet">

 </head>
 <body>
<div class="col-md-12">
 
</div>
 </body>
</html>





