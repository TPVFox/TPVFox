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
if (isset($_GET['modo'])) {
    switch ($_GET['modo']) {
        case 'media':
            // Mostrar la vista de medias y desviaciones estandar
            $vistaModo = 'media';
            break;
        case 'limite':
            // Mostrar la vista de medias y desviaciones estandar
            $vistaModo = 'limite';
            break;
    }
}
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
    // Si entramos en modo limite y no existe temperatura máxima redirigir a modo media
    if ($vistaModo === 'limite' && (!isset($dispositivo['temp_max']) || $dispositivo['temp_max'] == null)) {
        header("Location: ./temperaturasListado.php?id=" . intval($idDispositivo) . "&modo=media");
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
    // si la vista es limite poner como media la temperatura máxima - 2,5*sd
    if ($vistaModo === 'limite') {
        $mediaLimite = $dispositivo['temp_max'] - 2.5 * $dispositivo['sd'];
        $CValidacion->setMedia($mediaLimite);
    }
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


// Se llega mediante get id del dispositivo
if (isset($_POST)) {
    if (isset($_POST['action']) == 'actualizar_parametros_ds') {
        $datosDispositivo = array(
            'idDispositivo' => intval($_POST['idDispositivo']),
            'media' => floatval($_POST['media']),
            'sd' => floatval($_POST['desviacionEstandar']),
            'temp_min' => floatval($dispositivo['temp_max'] - 5 * floatval($_POST['desviacionEstandar']))
        );
        $ClaseTemperatura->updateDispositivoEstadisticas($datosDispositivo);
        header("Location: ./temperaturasListado.php?id=" . intval($idDispositivo) . "&modo=" . urlencode($vistaModo));
        exit();
    }
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
            <button id="toggleVista" class="btn btn-info btn-sm">
                Ver temperatura directa
            </button>
            <?php
            if (isset($dispositivo['media']) && isset($dispositivo['sd']) && isset($dispositivo['temp_max']) && isset($dispositivo['temp_min'])) {
                if ($vistaModo === 'media') {
                    echo '<a href="./temperaturasListado.php?id=' . intval($idDispositivo) . '&modo=limite" class="btn btn-default btn-sm">Ver Rango Límite Crítico</a>';
                } else {
                    echo '<a href="./temperaturasListado.php?id=' . intval($idDispositivo) . '&modo=media" class="btn btn-default btn-sm">Ver Análisis de Desviaciones</a>';
                }
            }
            ?>
            <?php if ($vistaModo === 'media' and isset($dispositivo['temp_max'])): ?>
                <!-- Se crea un boton que permita subir datos a la tabla dispositivos mediante post alineado a la derecha-->
                <div style="float:right;">
                    <form method="post" action="./temperaturasListado.php?id=<?php echo intval($idDispositivo); ?>&modo=media" style="display:inline-block; margin-left:10px;">
                        <input type="hidden" name="idDispositivo" value="<?php echo intval($idDispositivo); ?>">
                        <input type="hidden" name="media" value="<?php echo htmlspecialchars($media); ?>">
                        <input type="hidden" name="desviacionEstandar" value="<?php echo htmlspecialchars($desviacionEstandar); ?>">
                        <button type="submit" title="Sube la media y la desviación estandar de este dispositivo para tener un registro adecuado de los límites" name="action" value="actualizar_parametros_ds" class="btn btn-warning btn-sm">
                            Actualizar Parámetros DS
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            <div class="ds ds-resumen">
                <?php if ($vistaModo === 'media'): ?>
                    <strong>Media:</strong> <?php echo round($media, 2); ?> &nbsp;&nbsp;
                    <strong>Desviación Estándar:</strong> <?php echo round($desviacionEstandar, 2); ?>
                    <strong>Rango normal 95%:</strong> [ <?php echo round($media - 2 * $desviacionEstandar, 2); ?> ºC <?php echo round($media + 2 * $desviacionEstandar, 2); ?> ºC]
                <?php elseif ($vistaModo === 'limite'): ?>
                    <strong>Media Límite Crítico:</strong> <?php echo round($dispositivo['temp_max'] - 2.5 * $dispositivo['sd'], 2); ?> &nbsp;&nbsp;
                    <strong>Desviación Estándar:</strong> <?php echo round($dispositivo['sd'], 2); ?>
                    <strong>Rango Límite Crítico 95%:</strong> [ <?php echo round($media - 2 * $desviacionEstandar, 2); ?> ºC <?php echo round($media + 2 * $desviacionEstandar, 2); ?> ºC]
                    <strong>Rango normal 95%:</strong> [ <?php echo round($dispositivo['media'] - 2 * $dispositivo['sd'], 2); ?> ºC <?php echo round($dispositivo['media'] + 2 * $dispositivo['sd'], 2); ?> ºC]
                <?php endif; ?>
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
