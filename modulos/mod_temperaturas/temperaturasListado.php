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
    // Si temperaturasDispositivo tiene error
    if (isset($temperaturasDispositivo['error'])) {
        // redirigir a temperatura.php con mensaje de error
        header("Location: ./temperatura.php?error=" . urlencode($temperaturasDispositivo['error']));
        exit;
    }
    $temperaturasDispositivo = $temperaturasDispositivo['datos'];

    include_once $URLCom . '/modulos/mod_temperaturas/clases/ClaseValidacion.php';
    foreach ($temperaturasDispositivo as $registro) {
        $datosValidacion['fechas'][] = $registro['fechaRegistro'];
        $datosValidacion['valores'][] = $registro['temperatura'];
        $idUsuario = $registro['idUsuario'];
        if (!isset($idUsuario) || $idUsuario == null || $idUsuario == 0) {
            $usuario = 'Sistema';
        } else {
            $usuario = $CUsuarios->getUsuarioNombrePorId($idUsuario);
            $usuario = $usuario['datos'][0]['nombre'];
        }
        $datosValidacion['usuario'][] = $usuario;
    }
    $CValidacion = new ClaseValidacion($datosValidacion);
    $datosValidacionResultado = $CValidacion->getResultados();
    $media = $CValidacion->getMedia();
    $desviacionEstandar = $CValidacion->getDesviacionEstandar();
    //invertir el orden para que salga el más reciente primero
    $datosValidacionResultado['fechas'] = array_reverse($datosValidacionResultado['fechas']);
    $datosValidacionResultado['valores'] = array_reverse($datosValidacionResultado['valores']);
    $datosValidacionResultado['desviacion'] = array_reverse($datosValidacionResultado['desviacion']);
    $datosValidacionResultado['reglas'] = array_reverse($datosValidacionResultado['reglas']);
    $datosValidacionResultado['tipo'] = array_reverse($datosValidacionResultado['tipo']);
    $datosValidacionResultado['acciones'] = array_reverse($datosValidacionResultado['acciones']);
    $datosValidacionResultado['usuario'] = array_reverse($datosValidacionResultado['usuario']);
} else {
    echo "<div class='alert alert-danger'>No se ha especificado un dispositivo.</div>";
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <?php include_once $URLCom . '/head.php'; ?>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_temperaturas/funciones.js"></script>
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
            <!-- Centrar titulo y boton -->
            <div class="col-md-12 text-center">
                <h3>Panel de Control Preventivo<span class="ds ds-title">: Análisis de Desviaciones</span></h3>
            </div>
            <button id="toggleVista" class="btn btn-default btn-sm">
                Ver temperatura directa
            </button>
            <div class="ds ds-resumen">
                <strong>Media:</strong> <?php echo round($media, 2); ?> &nbsp;&nbsp;
                <strong>Desviación Estándar:</strong> <?php echo round($desviacionEstandar, 2); ?>
                <strong>Rango normal 95%:</strong> [<?php echo round($media - 2 * $desviacionEstandar, 2); ?> ºC - <?php echo round($media + 2 * $desviacionEstandar, 2); ?> ºC]
            </div>
            <br>
            <?php
            if (isset($temperaturasDispositivo) && is_array($temperaturasDispositivo) && count($temperaturasDispositivo) > 0) {
                echo "<table class='table table-striped'>";
                echo "<thead><tr>
                    <th>Fecha Registro</th>
                    <th class='temperatura-directa' style='display:none;'>Temp.</th>
                    <th class='ds ds--3'>-3DS</th>
                    <th class='ds ds--2'>-2DS</th>
                    <th class='ds ds--1'>-1DS</th>
                    <th class='ds ds-0'>Media</th>
                    <th class='ds ds-1'>+1DS</th>
                    <th class='ds ds-2'>+2DS</th>
                    <th class='ds ds-3'>+3DS</th>
                    <th>Regla</th>
                    <th>Tipo</th>
                    <th>Acción</th>
                    <th>Usuario</th>
                  </tr></thead>";
                echo "<tbody>";
                foreach ($datosValidacionResultado['fechas'] as $index => $fechaRegistro) {
                    $temperatura = $datosValidacionResultado['valores'][$index];
                    $desviacion = intval($datosValidacionResultado['desviacion'][$index]);
                    $regla = $datosValidacionResultado['reglas'][$index];
                    $tipo = $datosValidacionResultado['tipo'][$index];
                    $accion = $datosValidacionResultado['acciones'][$index];
                    $usuario = $datosValidacionResultado['usuario'][$index];
                    // usar desviacion para definir la columna en la que poner el valor $temperatura
                    // bg-danger: 1:3S
                    // bg-warning: 1:2S y 4:1S
                    // bg-info: 1:2S, R:4S
                    $filaClass = '';
                    if ($regla == '1:3s') {
                        $filaClass = 'bg-danger';
                    } elseif (in_array($regla, array('1:2s', '4:1s'))) {
                        $filaClass = 'bg-warning';
                    } elseif (in_array($regla, array('2:2s', 'R:4s'))) {
                        $filaClass = 'bg-info';
                    }
                    echo "<tr>
                        <td class='$filaClass'>" . htmlspecialchars($fechaRegistro) . "</td>
                        <td class='temperatura-directa $filaClass' style='display:none;'>" . htmlspecialchars($temperatura) . ' ºC' . "</td>
                        <td class='ds ds--3 $filaClass'>" . ($desviacion == -3 ? htmlspecialchars($temperatura) . ' ºC' : '') . "</td>
                        <td class='ds ds--2 $filaClass'>" . ($desviacion == -2 ? htmlspecialchars($temperatura) . ' ºC' : '') . "</td>
                        <td class='ds ds--1 $filaClass'>" . ($desviacion == -1 ? htmlspecialchars($temperatura) . ' ºC' : '') . "</td>
                        <td class='ds ds-0 $filaClass'>" . ($desviacion == 0 ? htmlspecialchars($temperatura) . ' ºC' : '') . "</td>
                        <td class='ds ds-1 $filaClass'>" . ($desviacion == 1 ? htmlspecialchars($temperatura) . ' ºC' : '') . "</td>
                        <td class='ds ds-2 $filaClass'>" . ($desviacion == 2 ? htmlspecialchars($temperatura) . ' ºC' : '') . "</td>
                        <td class='ds ds-3 $filaClass'>" . ($desviacion == 3 ? htmlspecialchars($temperatura) . ' ºC' : '') . "</td>
                        <td class='$filaClass'>" . htmlspecialchars($regla) . "</td>
                        <td class='$filaClass'>" . htmlspecialchars($tipo) . "</td>
                        <td class='$filaClass'>" . htmlspecialchars($accion) . "</td>
                        <td class='$filaClass'>" . htmlspecialchars($usuario) . "</td>
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
