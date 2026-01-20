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

    include_once $URLCom . '/modulos/mod_temperaturas/clases/ClaseValidacion.php';
    foreach ($temperaturasDispositivo as $registro) {
        $datosValidacion['fechas'][] = $registro['fechaRegistro'];
        $datosValidacion['valores'][] = $registro['temperatura'];
        $usuario = $CUsuarios->getUsuarioNombrePorId($registro['idUsuario']);
        if (isset($usuario['datos'][0]['nombre'])) {
            $usuario = $usuario['datos'][0]['nombre'];
        } else {
            $usuario = '';
        }
        if (empty($usuario)) {
            $usuario = 'Sistema';
        }
        $datosValidacion['usuario'][] = $usuario;
    }
    $CValidacion = new ClaseValidacion($datosValidacion);
    $datosValidacionResultado = $CValidacion->getResultados();
    $media = $CValidacion->getMedia();
    $desviacionEstandar = $CValidacion->getDesviacionEstandar();
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
        <div>
        <div>
            <strong>Media:</strong> <?php echo round($media, 2); ?> &nbsp;&nbsp;
            <strong>Desviación Estándar:</strong> <?php echo round($desviacionEstandar, 2); ?>
        </div>
        <br>
        <?php
        if (isset($temperaturasDispositivo) && is_array($temperaturasDispositivo) && count($temperaturasDispositivo) > 0) {
            echo "<table class='table table-striped'>";
            echo "<thead><tr>
                    <th>Fecha Registro</th>
                    <th>-3DS</th>
                    <th>-2DS</th>
                    <th>-1DS</th>
                    <th>Media</th>
                    <th>+1DS</th>
                    <th>+2DS</th>
                    <th>+3DS</th>
                    <th>Regla</th>
                    <th>Tipo</th>
                    <th>Acción</th>
                    <th>Usuario</th>
                  </tr></thead>";
            echo "<tbody>";
            foreach ($datosValidacionResultado['fechas'] as $index => $fechaRegistro) {
                $temperatura = $datosValidacionResultado['valores'][$index];
                $desviacion = round($datosValidacionResultado['desviacion'][$index]);
                $regla = $datosValidacionResultado['reglas'][$index];
                $tipo = $datosValidacionResultado['tipo'][$index];
                $accion = $datosValidacionResultado['acciones'][$index];
                $usuario = $datosValidacionResultado['usuario'][$index];
                // usar desviacion para definir la columna en la que poner el valor $temperatura
                
                echo "<tr>
                        <td>" . htmlspecialchars($fechaRegistro) . "</td>
                        <td>" . ($desviacion == -3 ? htmlspecialchars($temperatura) . 'ºC' : '') . "</td>
                        <td>" . ($desviacion == -2 ? htmlspecialchars($temperatura) . 'ºC' : '') . "</td>
                        <td>" . ($desviacion == -1 ? htmlspecialchars($temperatura) . 'ºC' : '') . "</td>
                        <td>" . ($desviacion == 0 ? htmlspecialchars($temperatura) . 'ºC' : '') . "</td>
                        <td>" . ($desviacion == 1 ? htmlspecialchars($temperatura) . 'ºC' : '') . "</td>
                        <td>" . ($desviacion == 2 ? htmlspecialchars($temperatura) . 'ºC' : '') . "</td>
                        <td>" . ($desviacion == 3 ? htmlspecialchars($temperatura) . 'ºC' : '') . "</td>
                        <td>" . htmlspecialchars($regla) . "</td>
                        <td>" . htmlspecialchars($tipo) . "</td>
                        <td>" . htmlspecialchars($accion) . "</td>
                        <td>" . htmlspecialchars($usuario) . "</td>
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>No hay registros de temperatura para este dispositivo.</p>";
        }
        ?>
        </div>
        <div style="text-align:right;">
            <a class="btn btn-default" href="./temperatura.php">Volver al Listado de Dispositivos</a>
        </div>
    </div>
</body>
