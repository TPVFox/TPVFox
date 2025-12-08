<?php
if (isset($dispositivos) && is_array($dispositivos) && count($dispositivos) > 0) {
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>Nombre</th><th>Ubicación</th><th>Estado</th></tr></thead>";
    echo "<tbody>";
    foreach ($dispositivos as $dispositivo) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($dispositivo['nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($dispositivo['ubicacion']) . "</td>";
        echo "<td>" . ($dispositivo['estado'] == 1 ? 'Activo' : 'Inactivo') . "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
} else {
    echo "<p>No hay dispositivos de temperatura añadidos.</p>";
}