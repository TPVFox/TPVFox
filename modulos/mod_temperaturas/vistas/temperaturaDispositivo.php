<?php
if (isset($_GET['edit'])) {
    $dispositivo = $ClaseTemperatura->getDispositivo(intval($_GET['edit']));
    $nombre = htmlspecialchars($dispositivo['nombre']);
    $ubicacion = htmlspecialchars($dispositivo['ubicacion']);
    $estado = intval($dispositivo['estado']);
    $media = (float)$dispositivo['media'];
    $sd = (float)$dispositivo['sd'];
    $tempMax = (float)$dispositivo['temp_max'];
    $valorMedioIdeal = $tempMax - 2 * $sd;
    $action = "update_dispositivo";
    $buttonText = "Actualizar Dispositivo";
} else {
    $nombre = "";
    $ubicacion = "";
    $estado = 1; // Valor por defecto
    $action = "save_configuracion";
    $buttonText = "Guardar Dispositivo";
}
$estadosDispositivos = $ClaseTemperatura->getEstadosDispositivos();
echo "<br>";
echo "<hr>";
// Incluir formulario para añadir/modificar un dispositivo de temperatura parametros: nombre ubicación y estado.
?>
<form method="post" action="./temperatura.php">
    <div class="form-group">
        <input type="hidden" name="action" value="<?php echo htmlspecialchars($action); ?>">
        <?php
        // Incluir como input hidden el id del dispositivo si es una edición
        if (isset($_GET['edit'])) {
            echo '<input type="hidden" name="idDispositivo" value="' . intval($_GET['edit']) . '">';
        }
        ?>
        <div class="form-group">
            <label for="deviceName">Nombre del dispositivo:</label>
            <input type="text" class="form-control" id="deviceName" name="deviceName" required value="<?php echo htmlspecialchars($nombre); ?>">
        </div>
        <div class="form-group">
            <label for="deviceLocation">Ubicación:</label>
            <input type="text" class="form-control" id="deviceLocation" name="deviceLocation" required value="<?php echo htmlspecialchars($ubicacion); ?>">
        </div>
        <div class="form-group">
            <label for="deviceStatus">Estado:</label>
            <select class="form-control" id="deviceStatus" name="deviceStatus">
                <?php
                if (isset($estadosDispositivos['error'])) {
                    echo "<option value=''>" . htmlspecialchars($estadosDispositivos['error']) . "</option>";
                } elseif (isset($estadosDispositivos['datos'])) {
                    foreach ($estadosDispositivos['datos'] as $estadoValor) {
                        $selected = ($estado === $estadoValor) ? ' selected' : '';
                        echo "<option value='" . htmlspecialchars($estadoValor) . "'$selected>" . htmlspecialchars(ucfirst($estadoValor)) . "</option>";
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="maxTemperature">Temperatura Máxima Permitida (°C):</label>
            <input type="number" step="0.1" class="form-control" id="maxTemperature" name="maxTemperature" value="<?php echo isset($tempMax) ? htmlspecialchars($tempMax) : ''; ?>">
        </div>
        <!-- si estamos editando mostrar el valor medio del dispositivo y su sd -->
        <?php
        if (isset($_GET['edit'])) {
        ?>
            <div class="container">
                <div class="col-md-4">
                    <label>Temperatura Media (°C): </label> <?php echo htmlspecialchars($media); ?>
                </div>
                <div class="col-md-4">
                    <label>Desviación Estándar (°C): </label> <?php echo htmlspecialchars($sd); ?>
                </div>
                <div class="col-md-4">
                    <label>Valor Medio Ideal (°C): </label> <?php echo htmlspecialchars($valorMedioIdeal); ?>
                </div>
            </div>
        <?php
        }
        ?>
    </div>
    <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars($buttonText); ?></button>
</form>
