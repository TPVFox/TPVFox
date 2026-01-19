<?php
include_once("./../../inicial.php");
include_once("./../../configuracion.php");
//include_once $URLCom . '/modulos/mod_balanza/clases/ClaseGestorDispositivos.php';
include_once($URLCom . '/controllers/parametros.php');
include_once $URLCom . '/controllers/Controladores.php';
include_once $URLCom . '/modulos/mod_temperaturas/clases/ClaseTemperatura.php';
include_once $URLCom . '/modulos/mod_usuario/clases/claseUsuarios.php';
include_once "./funciones.php";

$mod_vista = array('vista' => 'temperatura.php', 'modulo' => 'mod_temperaturas');

$ClasesParametros = new ClaseParametros('parametros.xml');
$Controler = new ControladorComun;
$CUsuarios = new ClaseUsuarios();
$Controler->loadDbtpv($BDTpv);

$ClaseTemperatura = new ClaseTemperatura($BDTpv);
// Se llega mediante get id del dispositivo
if (isset($_GET['id'])) {
    $idDispositivo = intval($_GET['id']);
    $dispositivo = $ClaseTemperatura->getDispositivo($idDispositivo);
    $temperaturasDispositivo = $ClaseTemperatura->getTemperaturasDispositivo($idDispositivo);
} else {
    echo "<div class='alert alert-danger'>No se ha especificado un dispositivo.</div>";
    exit;
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

    <div class="container">
        <h2>Historial de Temperaturas del Dispositivo: <?php echo htmlspecialchars($dispositivo['nombre']); ?></h2>

        <div style="text-align:right;">
            <a class="btn btn-default" href="./temperatura.php">Volver al Listado de Dispositivos</a>
        </div>
        <?php
        if (isset($temperaturasDispositivo) && is_array($temperaturasDispositivo) && count($temperaturasDispositivo) > 0) {
            echo "<table class='table table-striped'>";
            echo "<thead><tr><th>Fecha de Registro</th><th>Temperatura (°C)</th><th>Registrado por Usuario</th></tr></thead><tbody>";
            foreach ($temperaturasDispositivo as $registro) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($registro['fechaRegistro']) . "</td>";
                echo "<td>" . htmlspecialchars($registro['temperatura']) . " °C</td>";
                echo "<td>";
                if (isset($registro['idUsuario']) && $registro['idUsuario'] != 0) {
                    echo htmlspecialchars($CUsuarios->getUsuarioNombrePorId($registro['idUsuario'])['datos'][0]['nombre']);
                }
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>No hay registros de temperatura para este dispositivo.</p>";
        }
        ?>
        <div style="text-align:right;">
            <a class="btn btn-default" href="./temperatura.php">Volver al Listado de Dispositivos</a>
        </div>
    </div>
</body>
