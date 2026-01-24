<?php
include_once("./../../inicial.php");
include_once("./../../configuracion.php");
//include_once $URLCom . '/modulos/mod_balanza/clases/ClaseGestorDispositivos.php';
include_once($URLCom . '/controllers/parametros.php');
include_once $URLCom . '/controllers/Controladores.php';
include_once $URLCom . '/modulos/mod_temperaturas/clases/ClaseTemperatura.php';
include_once "./funciones.php";

$mod_vista = array('vista' => 'temperatura.php', 'modulo' => 'mod_temperaturas');

$ClasesParametros = new ClaseParametros('parametros.xml');
$Controler = new ControladorComun;
$Controler->loadDbtpv($BDTpv);

$ClaseTemperatura = new ClaseTemperatura($BDTpv);
$dispositivos = $ClaseTemperatura->getDispositivos();
$temperaturas = $ClaseTemperatura->getTemperaturas();

// Unir en un solo array la información de dispositivos y sus últimas temperaturas
$dispositivos = agregarUltimasTemperaturas($dispositivos, $temperaturas);

$parametros = $ClasesParametros->getRoot();

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'save_configuracion':
            // Procesar el formulario de nuevo dispositivo
            $dispositivo = array(
                'nombre' => $_POST['deviceName'],
                'ubicacion' => $_POST['deviceLocation'],
                'estado' => $_POST['deviceStatus'],
                'temp_max' => $_POST['maxTemperature']
            );
            $ClaseTemperatura->addDispositivo($dispositivo);

            echo "<div class='alert alert-success'>Dispositivo '$dispositivo' añadido correctamente.</div>";
            break;
        case 'update_dispositivo':
            // Procesar el formulario de edición de dispositivo
            $idDispositivo = intval($_POST['idDispositivo']);
            $dispositivo = array(
                'nombre' => $_POST['deviceName'],
                'ubicacion' => $_POST['deviceLocation'],
                'estado' => $_POST['deviceStatus'],
                'temp_max' => $_POST['maxTemperature']
            );
            $ClaseTemperatura->updateDispositivo($idDispositivo, $dispositivo);

            echo "<div class='alert alert-success'>Dispositivo actualizado correctamente.</div>";
            break;
        case 'update_temperaturas':
            // Procesar el formulario de actualización de temperaturas
            $idUsuario = $_SESSION['usuarioTpv']['id'];
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
    header("Location: ./temperatura.php");
    exit();
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
        <div class="col-md-4">
            <h4>Opciones generales</h4>
            <?php if (isset($ClasePermisos) && $ClasePermisos->getAccion("crearDispositivo", $mod_vista)): ?>
                <a class="btn btn-default" href="./temperatura.php?new">Añadir</a>
            <?php endif; ?>
            <a class="btn btn-default" href="./temperatura.php?configurar">Configurar parámetros</a>
            <?php
            if (isset($_GET['new']) or isset($_GET['edit'])) {
                include_once("./vistas/temperaturaDispositivo.php");
            }
            if (isset($_GET['configurar'])) {
                include_once("./vistas/configurar_parametros.php");
                echo "<br></br>";
                echo "<hr>";
            }
            ?>
        </div>

        <div class="col-md-8">
            <h4>Dispositivos añadidos</h4>
            <?php
            include_once("./vistas/temperaturaListaDispositivos.php");
            ?>
        </div>
        <div class="col-md-12">
            <h4>Histórico de temperaturas</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Dispositivo</th>
                        <th>Temperatura (°C)</th>
                        <th>Fecha de Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($temperaturas as $temp) {
                        echo "<tr>";
                        // mostrar nombre y ubicación del dispositivo en lugar de idDispositivo
                        $dispositivoInfo = array_filter($dispositivos, function ($d) use ($temp) {
                            return $d['idDispositivo'] == $temp['idDispositivo'];
                        });
                        $dispositivoInfo = array_values($dispositivoInfo);
                        if (count($dispositivoInfo) > 0) {
                            $dispositivoNombre = $dispositivoInfo[0]['nombre'] . " (" . $dispositivoInfo[0]['ubicacion'] . ")";
                        } else {
                            $dispositivoNombre = "Desconocido";
                        }
                        echo "<td>" . htmlspecialchars($dispositivoNombre) . "</td>";
                        echo "<td>" . htmlspecialchars($temp['temperatura']) . " °C</td>";
                        echo "<td>" . htmlspecialchars($temp['fechaRegistro']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>



    </div>
</body>
