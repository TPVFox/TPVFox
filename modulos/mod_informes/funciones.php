<?php 
function getHtmlTitulo($cabecera){
    $html = '<h1>'.$cabecera['titulo_informe'].'<h1>';
    return $html;
}
function getRangoFechas($cabecera){
    $html = 'Listado entre las fechas :'.$cabecera['Fecha_Inicio'].'-----'.$cabecera['Fecha_Final'];
    return $html;
}





?>
