<?php
 echo "<br>";
 echo "<hr>";
 // Incluir formulario para añadir/modificar un dispositivo de temperatura parametros: nombre ubicación y estado.
?>
<form method="post" action="./temperatura.php">
    <div class="form-group">
        <label for="deviceName">Nombre del dispositivo:</label>
        <input type="text" class="form-control" id="deviceName" name="deviceName" required>
    </div>
    <div class="form-group">
        <label for="deviceLocation">Ubicación:</label>
        <input type="text" class="form-control" id="deviceLocation" name="deviceLocation" required>
    </div>
    <div class="form-group">
        <label for="deviceStatus">Estado:</label>
        <select class="form-control" id="deviceStatus" name="deviceStatus">
            <option value="active">Activo</option>
            <option value="inactive">Inactivo</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Guardar dispositivo</button>
</form>