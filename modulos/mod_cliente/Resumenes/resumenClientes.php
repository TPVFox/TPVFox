<?php
include_once './../../../inicial.php';

include $URLCom . '/modulos/mod_cliente/funciones.php';
include $URLCom . '/controllers/Controladores.php';
include_once($URLCom . '/controllers/parametros.php');
include_once $URLCom . '/modulos/mod_cliente/clases/ClaseCliente.php';

$ClasesParametros = new ClaseParametros('../parametros.xml');
$Cliente = new ClaseCliente($BDTpv);
$Controler = new ControladorComun;
$Controler->loadDbtpv($BDTpv);
$ano = $_SESSION['tiendaTpv']['ano'];
$errores = array();
$titulo = "Resumen Clientes";

$resumenAnual = $Cliente->getResumenAnual();
$resumenAnualValidado = validarResumenAnualTodosClientes($resumenAnual, $ano);

uasort($resumenAnualValidado, function ($a, $b) {
    return $b['total']['total'] <=> $a['total']['total'];
});
?>
<!DOCTYPE html>
<html>

<head>
    <?php include $URLCom . '/head.php'; ?>
</head>

<body>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_cliente/funciones.js"></script>
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
        foreach ($resumenAnualValidado as $idCliente => $resumen) {
            $cliente = $Cliente->getCliente($idCliente)['datos'][0];
            $bgclass = '';
            if ($resumen['total']['total'] >= 3000) {
                $bgclass = 'bg-info';
            } elseif ($resumen['total']['total'] + $resumen['total']['totalIva'] >= 3000) {
                $bgclass = 'bg-warning';
            }
            echo mostrarResumenCliente($cliente, $resumen, $bgclass);
        }
        ?>
    </div>
</body>

</html>
