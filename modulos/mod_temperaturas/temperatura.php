<?php
include_once("./../../inicial.php");
include_once("./../../configuracion.php");
//include_once $URLCom . '/modulos/mod_balanza/clases/ClaseGestorDispositivos.php';
include_once($URLCom . '/controllers/parametros.php');
include_once $URLCom . '/controllers/Controladores.php';

$ClasesParametros = new ClaseParametros('parametros.xml');
$Controler = new ControladorComun;
$Controler->loadDbtpv($BDTpv);

$parametros = $ClasesParametros->getRoot();

?>
<!DOCTYPE html>
<html>

<head>
    <?php include_once $URLCom . '/head.php'; ?>
</head>

<body>
    <?php
    include_once $URLCom . '/modulos/mod_menu/menu.php';
    ?>
    <!-- Panel izquierdo [Añadir dispositivo]-->
    <div class="container">
        <div class="col-md-12 text-center">
            <h2>Dispositivos de temperatura</h2>
        </div>
        <div class="col-md-3">
            <h4>Opciones generales</h4>
            <a class="btn btn-default" href="./temperatura.php?new">Añadir</a>
            <a class="btn btn-default" href="./temperatura.php?configurar">Configurar parámetros</a>
            <?php
            if (isset($_GET['new'])) {
                include_once("./vistas/temperaturaDispositivo.php");
            }
            ?>
        </div>
        
        <div class="col-md-9">
            <?php
            if (isset($_GET['configurar'])) {
                include_once("./vistas/configurar_parametros.php");
                echo "<br></br>";
                echo "<hr>";
            }
            ?>
            <h4>Dispositivos añadidos</h4>
            <?php
            include_once("./temperaturaListaDispositivos.php");
            ?>
        </div>



    </div>
</body>