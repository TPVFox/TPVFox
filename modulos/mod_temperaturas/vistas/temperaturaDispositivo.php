<?php
if (isset($_GET['edit'])) {
    $dispositivo = $ClaseTemperatura->getDispositivo(intval($_GET['edit']));
    $nombre = htmlspecialchars($dispositivo['nombre']);
    $ubicacion = htmlspecialchars($dispositivo['ubicacion']);
    $estado = intval($dispositivo['estado']);
    $action = "update_dispositivo";
    $buttonText = "Actualizar Dispositivo";
} else {
    $nombre = "";
    $ubicacion = "";
    $estado = 1; // Valor por defecto
    $action = "save_configuracion";
    $buttonText = "Guardar Dispositivo";
}
echo "<br>";
echo "<hr>";
// Incluir formulario para añadir/modificar un dispositivo de temperatura parametros: nombre ubicación y estado.
?>
<form method="post" action="./temperatura.php">
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
            <option value="1" <?php echo ($estado === 1) ? ' selected' : ''; ?>>Activo</option>
            <option value="2" <?php echo ($estado === 2) ? ' selected' : ''; ?>>Inactivo</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars($buttonText); ?></button>
</form>
