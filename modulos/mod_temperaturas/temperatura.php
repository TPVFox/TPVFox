<?php
include_once("./../../inicial.php");
include_once("./../../configuracion.php");
//include_once $URLCom . '/modulos/mod_balanza/clases/ClaseGestorDispositivos.php';
include_once($URLCom . '/controllers/parametros.php');
include_once $URLCom . '/controllers/Controladores.php';
include_once $URLCom . '/modulos/mod_temperaturas/clases/ClaseTemperatura.php';
include_once "./funciones.php";

$ClasesParametros = new ClaseParametros('parametros.xml');
$Controler = new ControladorComun;
$Controler->loadDbtpv($BDTpv);

$ClaseTemperatura = new ClaseTemperatura($BDTpv);
$dispositivos = $ClaseTemperatura->getDispositivos();
$temperaturas = $ClaseTemperatura->getTemperaturas();

// Unir en un solo array la información de dispositivos y sus últimas temperaturas
$dispositivos = agregarUltimasTemperaturas($dispositivos, $temperaturas);

$parametros = $ClasesParametros->getRoot();

if (isset($_POST['action'])){
    switch($_POST['action']){
        case 'save_configuracion':
            // Procesar el formulario de nuevo dispositivo
            $dispositivo = array(
                'nombre' => $_POST['deviceName'],
                'ubicacion' => $_POST['deviceLocation'],
                'estado' => $_POST['deviceStatus']
            );
            $ClaseTemperatura->addDispositivo($dispositivo);

            // Aquí se debería agregar la lógica para guardar el nuevo dispositivo
            // Por ejemplo, insertarlo en la base de datos o en un archivo de configuración

            echo "<div class='alert alert-success'>Dispositivo '$dispositivo' añadido correctamente.</div>";
        break;
        case 'update_temperaturas':
            // Procesar el formulario de actualización de temperaturas
            $idUsuario = intval($_POST['idUsuario']);
            $temperaturas = $_POST['temperatura']; // Array de temperaturas
            $datosTemperatura = array(); // idDispositivo, temperatura, idUsuario

            foreach ($temperaturas as $idDispositivo => $nuevaTemperatura) {
                $idDispositivo = intval($idDispositivo);
                $nuevaTemperatura = floatval($nuevaTemperatura);
                if ($nuevaTemperatura != 0) { // Solo actualizar si se ha proporcionado una temperatura
                    $datosTemperatura[] = array(
                        'idDispositivo' => $idDispositivo,
                        'temperatura' => $nuevaTemperatura,
                        'idUsuario' => $idUsuario
                    );
                }
            }
            if (count($datosTemperatura) > 0) {
                $ClaseTemperatura->addTemperaturas($datosTemperatura);
            }

            echo "<div class='alert alert-success'>Temperaturas actualizadas correctamente.</div>";
        break;
    }
}

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
            if (isset($_GET['configurar'])) {
                include_once("./vistas/configurar_parametros.php");
                echo "<br></br>";
                echo "<hr>";
            }
            ?>
        </div>
        
        <div class="col-md-9">
            <h4>Dispositivos añadidos</h4>
            <?php
            include_once("./vistas/temperaturaListaDispositivos.php");
            ?>
        </div>



    </div>
</body>