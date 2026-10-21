<?php

include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesAplicacion.php';

// @ Objetivo
// La pantalla de aplicar una relación a un periodo. Antes de consultar nada comprueba el
// permiso «ver»; después, que las tablas de productos relacionados estén instaladas. Prepara
// las listas que se eligen —proveedores especiales, relaciones que se pueden aplicar y todos
// los proveedores, para el de traspaso— y termina incluyendo su plantilla.

// Sin fila de permiso, getAccion devuelve una cadena vacía: se compara con 1, no con 0.
if ($ClasePermisos->getAccion('ver') != 1) {
    header('Location: ' . $HostNombre . '/index.php');
    exit;
}

$CAplicacion = new RelacionesAplicacion();
$CProveedores = new Proveedores();
$avisos = array();
$relacionesAplicables = array();
$especiales = array();
$proveedores = array();

$estructura = $CAplicacion->estadoEstructura();
if (isset($estructura['error']) || $estructura['datos'] !== 'instalada') {
    $avisos[] = $CAplicacion->montarAdvertencia('danger', 'Las tablas de productos relacionados no están instaladas o están incompletas: avisa al administrador.');
} else {
    $aplicables = $CAplicacion->relacionesAplicables();
    if (isset($aplicables['error'])) {
        $avisos[] = $aplicables['error'];
    } else {
        $relacionesAplicables = $aplicables['datos'];
    }
    foreach ($CProveedores->todosProveedores() as $proveedor) {
        if (is_array($proveedor)) {
            $proveedores[] = $proveedor;
            if ($proveedor['estado'] === 'Especial') {
                $especiales[] = $proveedor;
            }
        }
    }
    if (count($especiales) === 0) {
        $avisos[] = $CAplicacion->montarAdvertencia('info', 'No hay ningún proveedor «Especial»: crea uno en Proveedores con ese estado para aplicar relaciones.');
    }
    if (count($relacionesAplicables) === 0) {
        $avisos[] = $CAplicacion->montarAdvertencia('info', 'Ninguna relación que rige se puede aplicar: por ahora solo los despieces con cantidad y todas sus líneas en la misma unidad.');
    }
}
$puedePreparar = $ClasePermisos->getAccion('Preparar') == 1;
?>
<!DOCTYPE html>
<html>

<head>
    <?php include_once $URLCom . '/head.php'; ?>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_reorganizacion/aplicarRelacion.js?v=<?php echo filemtime(__DIR__ . '/aplicarRelacion.js'); ?>"></script>
</head>

<body>
    <?php
    include_once $URLCom . '/modulos/mod_menu/menu.php';
    include __DIR__ . '/template/view_aplicar_relacion.php';
    ?>
</body>

</html>
