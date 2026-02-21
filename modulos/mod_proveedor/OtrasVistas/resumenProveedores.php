<?php
include_once './../../../inicial.php';

include $URLCom . '/modulos/mod_proveedor/funciones.php';
include $URLCom . '/controllers/Controladores.php';
include_once($URLCom . '/controllers/parametros.php');
include_once $URLCom . '/modulos/mod_proveedor/clases/ClaseProveedor.php';

$ClasesParametros = new ClaseParametros('../parametros.xml');
$Proveedor = new ClaseProveedor($BDTpv);
$Controler = new ControladorComun;
$Controler->loadDbtpv($BDTpv);
$ano = $_SESSION['tiendaTpv']['ano'];
$errores = array();
$titulo = "Resumen Proveedores";

$resumenAnual = $Proveedor->obtenerResumenAnualProveedor();
$resumenAnualValidado = validarResumenAnualTodosProveedores($resumenAnual, $ano);


uasort($resumenAnualValidado, function ($a, $b) {
    return $b['facturas']['total'] <=> $a['facturas']['total'];
});
?>
<!DOCTYPE html>
<html>

<head>
    <?php include $URLCom . '/head.php'; ?>
</head>

<body>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_proveedor/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
    <?php
    include_once $URLCom . '/modulos/mod_menu/menu.php';
    if (count($errores) > 0) {
        foreach ($errores as $error) {
            echo '<div class="alert alert-' . $error['tipo'] . '">' . $error['mensaje'] . '</div>';
            if ($error['tipo'] === 'danger') {
                // No permito continuar.
                exit();
            }
        }
    }
    ?>

    <div class="container">
        <div class="col-md-12 text-center">
            <h2 class="text-center"> <?php echo $titulo; ?></h2>
        </div>
    </div>
    <div class="container">
        <?php
        foreach ($resumenAnualValidado as $idProveedor => $resumen) {
            $proveedor = $Proveedor->getProveedor($idProveedor)['datos'][0];
            $bgclass = '';
            if ($resumen['facturas']['total'] >= 3000) {
                $bgclass = 'bg-info';
            } elseif ($resumen['facturas']['total'] + $resumen['facturas']['totalIva'] >= 3000) {
                $bgclass = 'bg-warning';
            } elseif ($resumen['facturas']['total'] + $resumen['facturas']['totalIva'] < 2500) {
                continue;
            }
            echo mostrarResumenProveedor($proveedor, $resumen, $bgclass);
        }
        ?>
    </div>
</body>

</html>
