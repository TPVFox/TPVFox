<?php
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_informes/funciones.php';
include_once $URLCom . '/modulos/mod_producto/clases/ClaseProductos.php';
include_once $URLCom . '/modulos/mod_informes/clases/ClaseInformes.php';
$CInformes   = new ClaseInformes();
$CTArticulos = new ClaseProductos($BDTpv);
$DatosInforme = $CInformes->ObtenerdatosInforme();
$cabecera     = $DatosInforme['cabecera'];
$datosInforme = $DatosInforme['datos'];
?>

<!DOCTYPE html>
<html>

<head>
    <?php include_once $URLCom . '/head.php'; ?>
</head>

<body>
    <?php include_once $URLCom . '/modulos/mod_menu/menu.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <?php echo getHtmlTitulo($cabecera); ?>
            </div>
            <div class="col-md-12">
                <?php echo getRangoFechas($cabecera); ?>
            </div>

            <?php if ($cabecera['id'] == 1): ?>
            <!-- ── INFORME 1: Suma de compras por proveedores ─────────────── -->
            <div class="col-md-12">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>PRODUCTO</th>
                            <th>Nº VECES</th>
                            <th>CANTIDAD</th>
                            <th>COSTE</th>
                            <th title="Si cambió el precio, se calcula coste medio, marcado *">CM</th>
                            <th>IMPORTE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalLineas = 0;
                        if (isset($datosInforme['informe']['productos'])) {
                            foreach ($datosInforme['informe']['productos'] as $producto) {
                                $totalLineas += $producto['total_linea'];
                                $p        = $CTArticulos->GetProducto($producto['idArticulo']);
                                $cdetalle = $p['articulo_name'];
                                $tipo     = $p['tipo'];
                        ?>
                        <tr>
                            <td><?php echo $producto['idArticulo']; ?></td>
                            <td><?php echo htmlspecialchars($cdetalle); ?></td>
                            <td><?php echo $producto['num_compras']; ?></td>
                            <td><?php echo ($tipo === 'peso')
                                    ? number_format($producto['totalUnidades'], 3)
                                    : number_format($producto['totalUnidades'], 0); ?></td>
                            <td><?php echo number_format($producto['costeSiva'], 2); ?></td>
                            <td><?php echo ($producto['coste_medio'] === 'OK') ? '*' : ''; ?></td>
                            <td><?php echo number_format($producto['total_linea'], 2); ?></td>
                        </tr>
                        <?php
                            }
                        }
                        ?>
                    </tbody>
                    <?php if ($totalLineas > 0): ?>
                    <tfoot>
                        <tr class="active">
                            <td colspan="6"><strong>TOTAL</strong></td>
                            <td><strong><?php echo number_format($totalLineas, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <?php elseif ($cabecera['id'] == 2): ?>
            <!-- ── INFORME 2: Suma de compras por familias ───────────────── -->
            <?php
            $opcion = (int)$cabecera['opcion'];
            ?>
            <div class="col-md-12">

                <?php if ($opcion == 1): ?>
                <!-- Opción 1: Solo familias -->
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>FAMILIA</th>
                            <th>Nº REFERENCIAS</th>
                            <th>IMPORTE TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalGeneral = 0;
                        foreach ($datosInforme as $familia) {
                            $totalGeneral += $familia['total_linea'];
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($familia['nombreN1']); ?></strong></td>
                            <td><?php echo $familia['num_referencias']; ?></td>
                            <td><?php echo number_format($familia['total_linea'], 2); ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="active">
                            <td colspan="2"><strong>TOTAL</strong></td>
                            <td><strong><?php echo number_format($totalGeneral, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>

                <?php elseif ($opcion == 2): ?>
                <!-- Opción 2: Familias y subfamilias -->
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>FAMILIA / SUBFAMILIA</th>
                            <th>Nº REFERENCIAS</th>
                            <th>IMPORTE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalGeneral = 0;
                        foreach ($datosInforme as $familia) {
                            $totalGeneral += $familia['total_linea'];
                        ?>
                        <tr style="background:#e8ecf0;">
                            <td colspan="3">
                                <strong><?php echo htmlspecialchars($familia['nombreN1']); ?></strong>
                                &nbsp;<span class="label label-default"><?php echo $familia['num_referencias']; ?> ref.</span>
                                &nbsp;<strong><?php echo number_format($familia['total_linea'], 2); ?> €</strong>
                            </td>
                        </tr>
                        <?php foreach ($familia['subfamilias'] as $sf): ?>
                        <tr>
                            <td style="padding-left:24px;"><?php echo htmlspecialchars($sf['nombreN2']); ?></td>
                            <td><?php echo $sf['num_referencias']; ?></td>
                            <td><?php echo number_format($sf['total_linea'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="active">
                            <td colspan="2"><strong>TOTAL</strong></td>
                            <td><strong><?php echo number_format($totalGeneral, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>

                <?php elseif ($opcion == 3 || $opcion == 4): ?>
                <!-- Opción 3: Familias, subfamilias y productos -->
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>FAMILIA / SUBFAMILIA / PRODUCTO</th>
                            <th>ID</th>
                            <th>Nº VECES</th>
                            <th>CANTIDAD</th>
                            <th>COSTE</th>
                            <th title="Si cambió el precio, se calcula coste medio, marcado *">CM</th>
                            <th>IMPORTE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalGeneral = 0;
                        foreach ($datosInforme as $familia) {
                            $totalGeneral += $familia['total_linea'];
                        ?>
                        <!-- Cabecera familia N1 -->
                        <tr style="background:#d0d8e4;">
                            <td colspan="7" style="font-weight:700;">
                                <?php echo htmlspecialchars($familia['nombreN1']); ?>
                                &nbsp;<span class="label label-default"><?php echo $familia['num_referencias']; ?> ref.</span>
                                &nbsp;<?php echo number_format($familia['total_linea'], 2); ?> €
                            </td>
                        </tr>
                        <?php foreach ($familia['subfamilias'] as $sf): ?>
                        <!-- Cabecera subfamilia N2 -->
                        <tr style="background:#eef1f5;">
                            <td colspan="7" style="padding-left:16px; font-weight:600;">
                                <?php echo htmlspecialchars($sf['nombreN2']); ?>
                                &nbsp;<span class="label label-default"><?php echo $sf['num_referencias']; ?> ref.</span>
                                &nbsp;<?php echo number_format($sf['total_linea'], 2); ?> €
                            </td>
                        </tr>
                        <?php foreach ($sf['articulos'] as $art):
                            $p        = $CTArticulos->GetProducto($art['idArticulo']);
                            $cdetalle = $p['articulo_name'];
                            $tipo     = $p['tipo'];
                        ?>
                        <tr>
                            <td style="padding-left:32px;"><?php echo htmlspecialchars($cdetalle); ?></td>
                            <td><?php echo $art['idArticulo']; ?></td>
                            <td><?php echo $art['num_compras']; ?></td>
                            <td><?php echo ($tipo === 'peso')
                                    ? number_format($art['totalUnidades'], 3)
                                    : number_format($art['totalUnidades'], 0); ?></td>
                            <td><?php echo number_format($art['costeSiva'], 2); ?></td>
                            <td><?php echo ($art['coste_medio'] === 'OK') ? '*' : ''; ?></td>
                            <td><?php echo number_format($art['total_linea'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="active">
                            <td colspan="6"><strong>TOTAL</strong></td>
                            <td><strong><?php echo number_format($totalGeneral, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>

                <?php endif; ?>
            </div>
            <?php elseif ($cabecera['id'] == 4): ?>
            <!-- ── INFORME 4: Suma de ventas por familias ───────────────── -->
            <?php
            $opcion = (int)$cabecera['opcion'];
            ?>
            <div class="col-md-12">

                <?php if ($opcion == 1): ?>
                <!-- Opción 1: Solo familias -->
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>FAMILIA</th>
                            <th>Nº REFERENCIAS</th>
                            <th>IMPORTE VENTA (s/IVA)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalGeneral = 0;
                        foreach ($datosInforme as $familia) {
                            $totalGeneral += $familia['total_linea'];
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($familia['nombreN1']); ?></strong></td>
                            <td><?php echo $familia['num_referencias']; ?></td>
                            <td><?php echo number_format($familia['total_linea'], 2); ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="active">
                            <td colspan="2"><strong>TOTAL</strong></td>
                            <td><strong><?php echo number_format($totalGeneral, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>

                <?php elseif ($opcion == 2): ?>
                <!-- Opción 2: Familias y subfamilias -->
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>FAMILIA / SUBFAMILIA</th>
                            <th>Nº REFERENCIAS</th>
                            <th>IMPORTE VENTA (s/IVA)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalGeneral = 0;
                        foreach ($datosInforme as $familia) {
                            $totalGeneral += $familia['total_linea'];
                        ?>
                        <tr style="background:#e8ecf0;">
                            <td colspan="3">
                                <strong><?php echo htmlspecialchars($familia['nombreN1']); ?></strong>
                                &nbsp;<span class="label label-default"><?php echo $familia['num_referencias']; ?> ref.</span>
                                &nbsp;<strong><?php echo number_format($familia['total_linea'], 2); ?> €</strong>
                            </td>
                        </tr>
                        <?php foreach ($familia['subfamilias'] as $sf): ?>
                        <tr>
                            <td style="padding-left:24px;"><?php echo htmlspecialchars($sf['nombreN2']); ?></td>
                            <td><?php echo $sf['num_referencias']; ?></td>
                            <td><?php echo number_format($sf['total_linea'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="active">
                            <td colspan="2"><strong>TOTAL</strong></td>
                            <td><strong><?php echo number_format($totalGeneral, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>

                <?php elseif ($opcion == 3 || $opcion == 4): ?>
                <!-- Opción 3: Familias, subfamilias y productos -->
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>FAMILIA / SUBFAMILIA / PRODUCTO</th>
                            <th>ID</th>
                            <th>Nº VENTAS</th>
                            <th>CANTIDAD</th>
                            <th>PVP</th>
                            <th title="Si cambió el precio, se calcula precio medio, marcado *">PM</th>
                            <th>IMPORTE (s/IVA)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalGeneral = 0;
                        foreach ($datosInforme as $familia) {
                            $totalGeneral += $familia['total_linea'];
                        ?>
                        <tr style="background:#d0d8e4;">
                            <td colspan="7" style="font-weight:700;">
                                <?php echo htmlspecialchars($familia['nombreN1']); ?>
                                &nbsp;<span class="label label-default"><?php echo $familia['num_referencias']; ?> ref.</span>
                                &nbsp;<?php echo number_format($familia['total_linea'], 2); ?> €
                            </td>
                        </tr>
                        <?php foreach ($familia['subfamilias'] as $sf): ?>
                        <tr style="background:#eef1f5;">
                            <td colspan="7" style="padding-left:16px; font-weight:600;">
                                <?php echo htmlspecialchars($sf['nombreN2']); ?>
                                &nbsp;<span class="label label-default"><?php echo $sf['num_referencias']; ?> ref.</span>
                                &nbsp;<?php echo number_format($sf['total_linea'], 2); ?> €
                            </td>
                        </tr>
                        <?php foreach ($sf['articulos'] as $art): ?>
                        <tr>
                            <td style="padding-left:32px;"><?php echo htmlspecialchars($art['articulo_name']); ?></td>
                            <td><?php echo $art['idArticulo']; ?></td>
                            <td><?php echo $art['num_ventas']; ?></td>
                            <td><?php echo ($art['tipo'] === 'peso')
                                    ? number_format($art['totalUnidades'], 3)
                                    : number_format($art['totalUnidades'], 0); ?></td>
                            <td><?php echo number_format($art['pvpSiva'], 2); ?></td>
                            <td><?php echo ($art['precio_medio'] === 'OK') ? '*' : ''; ?></td>
                            <td><?php echo number_format($art['total_linea'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="active">
                            <td colspan="6"><strong>TOTAL</strong></td>
                            <td><strong><?php echo number_format($totalGeneral, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>

                <?php endif; ?>
            </div>

            <?php elseif ($cabecera['id'] == 6): ?>
            <!-- ── INFORME 6: Beneficio por familias ───────────────────── -->
            <?php
            $opcion = (int)$cabecera['opcion'];
            ?>
            <div class="col-md-12">
                <div class="alert alert-warning">
                    <strong>Nota:</strong> El coste utilizado es <em>ultimoCoste</em> del artículo en el momento de ejecutar este informe, no el coste histórico en el momento de la venta.
                </div>

                <?php if ($opcion == 1): ?>
                <!-- Opción 1: Solo familias -->
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>FAMILIA</th>
                            <th>Nº REFERENCIAS</th>
                            <th>VENTA (s/IVA)</th>
                            <th>COSTE</th>
                            <th>BENEFICIO</th>
                            <th>MARGEN %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totVenta = 0; $totCoste = 0; $totBenef = 0;
                        foreach ($datosInforme as $familia) {
                            $totVenta += $familia['totalVenta'];
                            $totCoste += $familia['totalCoste'];
                            $totBenef += $familia['beneficio'];
                            $cls = $familia['margen_pct'] < 10 ? 'danger'
                                 : ($familia['margen_pct'] < 25 ? 'warning' : 'success');
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($familia['nombreN1']); ?></strong></td>
                            <td><?php echo $familia['num_referencias']; ?></td>
                            <td><?php echo number_format($familia['totalVenta'], 2); ?></td>
                            <td><?php echo number_format($familia['totalCoste'], 2); ?></td>
                            <td><?php echo number_format($familia['beneficio'], 2); ?></td>
                            <td>
                                <div class="progress" style="margin-bottom:0;">
                                    <div class="progress-bar progress-bar-<?php echo $cls; ?>" style="min-width:2em;width:<?php echo min(100, abs($familia['margen_pct'])); ?>%">
                                        <?php echo number_format($familia['margen_pct'], 1); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="active">
                            <?php
                            $margenTotal = $totVenta > 0 ? round($totBenef / $totVenta * 100, 2) : 0;
                            ?>
                            <td colspan="2"><strong>TOTAL</strong></td>
                            <td><strong><?php echo number_format($totVenta, 2); ?></strong></td>
                            <td><strong><?php echo number_format($totCoste, 2); ?></strong></td>
                            <td><strong><?php echo number_format($totBenef, 2); ?></strong></td>
                            <td><strong><?php echo number_format($margenTotal, 1); ?>%</strong></td>
                        </tr>
                    </tfoot>
                </table>

                <?php elseif ($opcion == 2): ?>
                <!-- Opción 2: Familias y subfamilias -->
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>FAMILIA / SUBFAMILIA</th>
                            <th>Nº REFERENCIAS</th>
                            <th>VENTA (s/IVA)</th>
                            <th>COSTE</th>
                            <th>BENEFICIO</th>
                            <th>MARGEN %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totVenta = 0; $totCoste = 0; $totBenef = 0;
                        foreach ($datosInforme as $familia) {
                            $totVenta += $familia['totalVenta'];
                            $totCoste += $familia['totalCoste'];
                            $totBenef += $familia['beneficio'];
                        ?>
                        <tr style="background:#e8ecf0;">
                            <td colspan="6">
                                <strong><?php echo htmlspecialchars($familia['nombreN1']); ?></strong>
                                &nbsp;<span class="label label-default"><?php echo $familia['num_referencias']; ?> ref.</span>
                                &nbsp;Venta: <strong><?php echo number_format($familia['totalVenta'], 2); ?></strong> €
                                &nbsp;Benef: <strong><?php echo number_format($familia['beneficio'], 2); ?></strong> €
                                &nbsp;Margen: <strong><?php echo number_format($familia['margen_pct'], 1); ?>%</strong>
                            </td>
                        </tr>
                        <?php foreach ($familia['subfamilias'] as $sf):
                            $cls = $sf['margen_pct'] < 10 ? 'danger'
                                 : ($sf['margen_pct'] < 25 ? 'warning' : 'success');
                        ?>
                        <tr>
                            <td style="padding-left:24px;"><?php echo htmlspecialchars($sf['nombreN2']); ?></td>
                            <td><?php echo $sf['num_referencias']; ?></td>
                            <td><?php echo number_format($sf['totalVenta'], 2); ?></td>
                            <td><?php echo number_format($sf['totalCoste'], 2); ?></td>
                            <td><?php echo number_format($sf['beneficio'], 2); ?></td>
                            <td>
                                <div class="progress" style="margin-bottom:0;">
                                    <div class="progress-bar progress-bar-<?php echo $cls; ?>" style="min-width:2em;width:<?php echo min(100, abs($sf['margen_pct'])); ?>%">
                                        <?php echo number_format($sf['margen_pct'], 1); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="active">
                            <?php $margenTotal = $totVenta > 0 ? round($totBenef / $totVenta * 100, 2) : 0; ?>
                            <td colspan="2"><strong>TOTAL</strong></td>
                            <td><strong><?php echo number_format($totVenta, 2); ?></strong></td>
                            <td><strong><?php echo number_format($totCoste, 2); ?></strong></td>
                            <td><strong><?php echo number_format($totBenef, 2); ?></strong></td>
                            <td><strong><?php echo number_format($margenTotal, 1); ?>%</strong></td>
                        </tr>
                    </tfoot>
                </table>

                <?php elseif ($opcion == 3 || $opcion == 4): ?>
                <!-- Opción 3: Familias, subfamilias y productos -->
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>FAMILIA / SUBFAMILIA / PRODUCTO</th>
                            <th>ID</th>
                            <th>CANTIDAD</th>
                            <th>PVP</th>
                            <th>COSTE</th>
                            <th>VENTA</th>
                            <th>BENEFICIO</th>
                            <th>MARGEN %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totVenta = 0; $totCoste = 0; $totBenef = 0;
                        foreach ($datosInforme as $familia) {
                            $totVenta += $familia['totalVenta'];
                            $totCoste += $familia['totalCoste'];
                            $totBenef += $familia['beneficio'];
                        ?>
                        <tr style="background:#d0d8e4;">
                            <td colspan="8" style="font-weight:700;">
                                <?php echo htmlspecialchars($familia['nombreN1']); ?>
                                &nbsp;<span class="label label-default"><?php echo $familia['num_referencias']; ?> ref.</span>
                                &nbsp;Venta: <?php echo number_format($familia['totalVenta'], 2); ?> €
                                &nbsp;Benef: <?php echo number_format($familia['beneficio'], 2); ?> €
                                &nbsp;<?php echo number_format($familia['margen_pct'], 1); ?>%
                            </td>
                        </tr>
                        <?php foreach ($familia['subfamilias'] as $sf): ?>
                        <tr style="background:#eef1f5;">
                            <td colspan="8" style="padding-left:16px; font-weight:600;">
                                <?php echo htmlspecialchars($sf['nombreN2']); ?>
                                &nbsp;<span class="label label-default"><?php echo $sf['num_referencias']; ?> ref.</span>
                                &nbsp;Venta: <?php echo number_format($sf['totalVenta'], 2); ?> €
                                &nbsp;Benef: <?php echo number_format($sf['beneficio'], 2); ?> €
                                &nbsp;<?php echo number_format($sf['margen_pct'], 1); ?>%
                            </td>
                        </tr>
                        <?php foreach ($sf['articulos'] as $art):
                            $cls = $art['margen_pct'] < 10 ? 'danger'
                                 : ($art['margen_pct'] < 25 ? 'warning' : 'success');
                        ?>
                        <tr>
                            <td style="padding-left:32px;"><?php echo htmlspecialchars($art['articulo_name']); ?></td>
                            <td><?php echo $art['idArticulo']; ?></td>
                            <td><?php echo ($art['tipo'] === 'peso')
                                    ? number_format($art['totalUnidades'], 3)
                                    : number_format($art['totalUnidades'], 0); ?></td>
                            <td><?php echo number_format($art['pvpSiva'], 2); ?></td>
                            <td><?php echo number_format($art['ultimoCoste'], 2); ?></td>
                            <td><?php echo number_format($art['totalVenta'], 2); ?></td>
                            <td><?php echo number_format($art['beneficio'], 2); ?></td>
                            <td>
                                <span class="label label-<?php echo $cls; ?>">
                                    <?php echo number_format($art['margen_pct'], 1); ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="active">
                            <?php $margenTotal = $totVenta > 0 ? round($totBenef / $totVenta * 100, 2) : 0; ?>
                            <td colspan="5"><strong>TOTAL</strong></td>
                            <td><strong><?php echo number_format($totVenta, 2); ?></strong></td>
                            <td><strong><?php echo number_format($totBenef, 2); ?></strong></td>
                            <td><strong><?php echo number_format($margenTotal, 1); ?>%</strong></td>
                        </tr>
                    </tfoot>
                </table>

                <?php endif; ?>
            </div>

            <?php endif; ?>

        </div>
    </div>

    <script src="<?php echo $HostNombre; ?>/modulos/mod_informes/funciones.js" type="module"></script>

</body>

</html>
