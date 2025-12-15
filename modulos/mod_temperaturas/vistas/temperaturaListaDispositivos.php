<?php
if (isset($dispositivos) && is_array($dispositivos) && count($dispositivos) > 0) {
    echo "<form method='post' action='./temperatura.php'>";
    echo "<input type='hidden' name='action' value='update_temperaturas'>";
    // hidden input for idUsuario from tabla temperaturas
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>Nombre</th><th>Ubicación</th><th>Estado</th><th>Último Registro</th><th>Última Tª</th><th>Nueva Tª</th><th>Editar</th></tr></thead>";
    echo "<tbody>";
    foreach ($dispositivos as $dispositivo) {
        $id = intval($dispositivo['idDispositivo']);
        echo "<tr>";
        echo "<td>" . htmlspecialchars($dispositivo['nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($dispositivo['ubicacion']) . "</td>";
        echo "<td>" . htmlspecialchars($dispositivo['estado']) . "</td>";
        echo "<td>" . htmlspecialchars($dispositivo['ultimo_registro']) . "</td>";
        echo "<td>" . htmlspecialchars($dispositivo['ultima_temperatura']) . " °C</td>";
        echo "<td>";
        // usamos un array: temperatura[<id>]
        echo "<input type='number' name='temperatura[" . $id . "]' step='0.1' min='-50' max='150' placeholder='Nueva Tª' aria-label='Nueva temperatura del dispositivo " . $id . "'>";
        echo "</td>";
        echo "<td><a class='btn btn-sm btn-default' href='./temperatura.php?edit=" . $id . "'>Editar</a></td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    echo "<div style='text-align:right;'><button type='submit' class='btn btn-primary'>Actualizar Temperaturas</button></div>";
    echo "</form>";
} else {
    echo "<p>No hay dispositivos de temperatura añadidos.</p>";
}
?>