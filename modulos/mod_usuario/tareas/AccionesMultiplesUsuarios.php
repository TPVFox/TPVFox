<?php
//@Objetivo: Realizar acciones multiples sobre usuarios seleccionados
// Recibimos array con ids de usuarios seleccionados
// Devolvemos html con opciones a realizar
// Acciones posibles:
// - Cambiar año
// - Inactivar usuarios seleccionados
// - Eliminar usuarios seleccionados
// - Asignar grupo a usuarios seleccionados
if (isset($_POST['idsSeleccionados'])){
    $idsUsuarios = $_POST['idsSeleccionados'];
}
$html = '';
// Si no hay usuarios no es countable
if (isset($idsUsuarios)) {
    $html .= '<h4>Acciones para usuarios seleccionados:</h4>';
    $html .= '<p>Usuarios seleccionados: ' . count($idsUsuarios) . '</p>';
    $html .= '<button class = "btn" onclick="cambiarAnoUsuarios()">Cambiar Año</button>';
    $html .= '<button class = "btn" onclick="activarUsuarios()">Activar usuarios</button>';
    $html .= '<button class = "btn" onclick="inactivarUsuarios()">Inactivar usuarios</button>';
    $html .= '<button class = "btn" onclick="eliminarUsuarios()">Eliminar usuarios</button>';
    $html .= '<button class = "btn" onclick="asignarGrupoUsuarios()">Asignar Grupo</button>';
} else {
    $html .= '<h4>Acciones para usuarios seleccionados:</h4>';
    $html .= '<p>Usuarios seleccionados: 0 </p>';
    $html .= '<button class = "btn" onclick="cambiarAnoUsuarios()">Cambiar Año</button>';
}
$html .= '<hr>';
$html .= '<div id="submenuAccionesMultiplesUsuarios"></div>';
$respuesta['html']=$html;