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
                <div class="alert alert-info">
                    <strong>Nota:</strong> Si un artículo está asignado a varias familias aparece en cada una de ellas. Los subtotales por familia son correctos entre sí, pero el <strong>total global puede estar inflado</strong> si existen artículos compartidos entre familias.
                </div>

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
                <div class="alert alert-info">
                    <strong>Nota:</strong> Si un artículo está asignado a varias familias aparece en cada una de ellas. Los subtotales por familia son correctos entre sí, pero el <strong>total global puede estar inflado</strong> si existen artículos compartidos entre familias.
                </div>

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
                <div class="alert alert-info">
                    <strong>Nota:</strong> Si un artículo está asignado a varias familias aparece en cada una de ellas. Los subtotales por familia son correctos entre sí, pero el <strong>total global puede estar inflado</strong> si existen artículos compartidos entre familias.
                </div>
                <div class="alert alert-warning">
                    <strong>Coste:</strong> Se usa el <strong>coste medio ponderado de compra del período</strong> para cada artículo. Si no hubo compra en el período se usa el <em>ultimoCoste</em> actual como aproximación. El coste con <span class="label label-default">*</span> indica fallback a coste actual.
                </div>

                <?php
                $rg = $cabecera['resumen_global'];
                if ($rg):
                    $hasMermaSV = count($rg['articulos_merma_sin_ventas']) > 0;
                    $fl = $rg['flujo'];
                    $flResultadoPos = $fl['resultado_siva'] >= 0;
                    // Días del período (para mostrar en cabecera del panel)
                    $diasPeriodo = max(1, (int)round(
                        (strtotime($cabecera['Fecha_Final']) - strtotime($cabecera['Fecha_Inicio'])) / 86400
                    ) + 1);
                    // Ratio V/C: cuántos euros se venden por cada euro comprado
                    $ratioVC = $fl['compras_siva'] > 0
                        ? round($fl['ventas_siva'] / $fl['compras_siva'], 2)
                        : null;
                    // % flujo sobre compras (margen del período)
                    $pctFlujo = $fl['compras_siva'] > 0
                        ? round($fl['resultado_siva'] / $fl['compras_siva'] * 100, 1)
                        : 0;
                    // Rotación = Coste mercancía vendida / Stock al fin del período
                    // Stock medio ≈ stock reconstruido a fechaFinal (apertura año + movimientos)
                    $rotacionGlobal = ($rg['valor_stock'] ?? 0) > 0
                        ? round($rg['totalCoste'] / $rg['valor_stock'], 2)
                        : null;
                ?>
                <!-- ── Vista de flujo: compras vs ventas reales ───────────── -->
                <div class="panel panel-primary">
                    <div class="panel-heading" style="display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <strong><span class="glyphicon glyphicon-transfer"></span> Vista de flujo — Compras vs Ventas del período</strong>
                            <small style="margin-left:8px;opacity:.85;">Datos directos de albaranes · <?php echo $diasPeriodo; ?> días</small>
                        </div>
                        <button type="button" class="btn btn-xs btn-default flujo-avanzado-toggle"
                                style="opacity:.8;"
                                title="Mostrar / ocultar métricas avanzadas (rotación y ROI)">
                            <span class="glyphicon glyphicon-stats"></span> Avanzado
                        </button>
                    </div>
                    <div class="panel-body" style="padding:10px 15px;">
                        <div class="row">
                            <div class="col-xs-12 col-sm-4 text-center" style="border-right:1px solid #ddd;">
                                <div style="font-size:11px;color:#888;margin-bottom:4px;">VENTAS DEL PERÍODO</div>
                                <div style="font-size:20px;font-weight:700;color:#27ae60;"><?php echo number_format($fl['ventas_siva'], 2); ?> €</div>
                                <div style="font-size:11px;color:#aaa;">con IVA: <?php echo number_format($fl['ventas_civa'], 2); ?> €</div>
                            </div>
                            <div class="col-xs-12 col-sm-4 text-center" style="border-right:1px solid #ddd;">
                                <div style="font-size:11px;color:#888;margin-bottom:4px;">COMPRAS DEL PERÍODO</div>
                                <div style="font-size:20px;font-weight:700;color:#e67e22;"><?php echo number_format($fl['compras_siva'], 2); ?> €</div>
                                <div style="font-size:11px;color:#aaa;">con IVA: <?php echo number_format($fl['compras_civa'], 2); ?> €</div>
                            </div>
                            <div class="col-xs-12 col-sm-4 text-center">
                                <div style="font-size:11px;color:#888;margin-bottom:4px;">RESULTADO FLUJO (s/IVA)</div>
                                <div style="font-size:20px;font-weight:700;color:<?php echo $flResultadoPos ? '#27ae60' : '#c0392b'; ?>">
                                    <?php echo number_format($fl['resultado_siva'], 2); ?> €
                                </div>
                                <div style="font-size:11px;color:#aaa;"><?php echo $pctFlujo; ?>% sobre compras</div>
                            </div>
                        </div>
                        <!-- Bloque avanzado: oculto por defecto -->
                        <?php
                        $gmroiGlobal    = $rg['gmroi'] ?? null;
                        $stockGlobal    = $rg['valor_stock'] ?? 0;
                        $gmroiColor     = $gmroiGlobal !== null
                            ? ($gmroiGlobal >= 2 ? '#27ae60' : ($gmroiGlobal >= 1 ? '#e67e22' : '#c0392b'))
                            : '#aaa';
                        ?>
                        <div class="flujo-avanzado" style="display:none;margin-top:12px;padding-top:12px;border-top:2px solid #d6e4f0;">
                            <div class="row">
                                <?php if ($ratioVC !== null): ?>
                                <div class="col-xs-12 col-sm-3 text-center" style="border-right:1px solid #ddd;">
                                    <div style="font-size:11px;color:#888;margin-bottom:4px;">RATIO V/C</div>
                                    <div style="font-size:24px;font-weight:700;color:#8e44ad;"><?php echo $ratioVC; ?>×</div>
                                    <div style="font-size:11px;color:#aaa;">
                                        <?php echo $ratioVC >= 1 ? 'vendiste más de lo que compraste' : 'compraste más de lo que vendiste'; ?>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-3 text-center" style="border-right:1px solid #ddd;">
                                    <div style="font-size:11px;color:#888;margin-bottom:4px;">ROTACIÓN DEL PERÍODO</div>
                                    <?php if ($rotacionGlobal !== null): ?>
                                    <div style="font-size:24px;font-weight:700;color:#2980b9;">
                                        <?php echo number_format($rotacionGlobal, 2); ?>×
                                    </div>
                                    <div style="font-size:11px;color:#aaa;">
                                        coste vendido / stock medio<br>
                                        €<?php echo number_format($rg['valor_stock'], 0, ',', '.'); ?> stock medio
                                    </div>
                                    <?php else: ?>
                                    <div style="font-size:14px;color:#ccc;">sin datos de stock</div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-xs-12 col-sm-3 text-center" style="border-right:1px solid #ddd;">
                                    <div style="font-size:11px;color:#888;margin-bottom:4px;">GMROI</div>
                                    <?php if ($gmroiGlobal !== null): ?>
                                    <div style="font-size:24px;font-weight:700;color:<?php echo $gmroiColor; ?>">
                                        <?php echo number_format($gmroiGlobal, 2); ?>
                                    </div>
                                    <div style="font-size:11px;color:#aaa;">
                                        margen bruto / stock medio<br>
                                        <?php echo $gmroiGlobal >= 2 ? 'bueno' : ($gmroiGlobal >= 1 ? 'aceptable' : 'bajo'); ?>
                                    </div>
                                    <?php else: ?>
                                    <div style="font-size:14px;color:#ccc;">sin datos de stock</div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-xs-12 col-sm-3 text-center">
                                    <div style="font-size:11px;color:#888;margin-bottom:6px;">CÓMO LEERLO</div>
                                    <div style="font-size:11px;color:#777;text-align:left;line-height:1.7;">
                                        <strong>V/C &gt; 1</strong> → vendiste más de lo que compraste<br>
                                        <strong>Rotación</strong> = coste vendido / stock medio<br>
                                        <strong>GMROI</strong> = margen bruto / stock medio<br>
                                        Stock medio = (stock <?php echo $cabecera['Fecha_Inicio']; ?> + stock <?php echo $cabecera['Fecha_Final']; ?>) / 2
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                document.querySelector('.flujo-avanzado-toggle').addEventListener('click', function() {
                    var bloques = document.querySelectorAll('.flujo-avanzado');
                    var visible = bloques[0] && bloques[0].style.display !== 'none';
                    bloques.forEach(function(el) {
                        el.style.display = visible ? 'none' : (el.tagName === 'SPAN' ? 'inline' : 'block');
                    });
                    this.innerHTML = visible
                        ? '<span class="glyphicon glyphicon-stats"></span> Avanzado'
                        : '<span class="glyphicon glyphicon-stats"></span> Ocultar';
                });
                </script>
                <!-- ── Resumen global de rentabilidad ─────────────────────── -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Rentabilidad global del período</strong>
                        <small class="text-muted" style="margin-left:8px;">Ingresos − Coste de mercancía consumida (ventas + merma total)</small>
                    </div>
                    <div class="panel-body" style="padding:10px 15px;">
                        <div class="row">
                            <div class="col-xs-6 col-sm-3 text-center">
                                <div style="font-size:11px;color:#888;">INGRESOS (s/IVA)</div>
                                <div style="font-size:20px;font-weight:700;"><?php echo number_format($rg['totalVenta'], 2); ?> €</div>
                            </div>
                            <div class="col-xs-6 col-sm-3 text-center">
                                <div style="font-size:11px;color:#888;">COSTE MERCANCÍA VENDIDA</div>
                                <div style="font-size:20px;"><?php echo number_format($rg['totalCoste'], 2); ?> €</div>
                            </div>
                            <div class="col-xs-6 col-sm-3 text-center">
                                <div style="font-size:11px;color:#888;">MERMA TOTAL
                                    <?php if ($rg['mermaSinVentas'] > 0): ?>
                                    <span class="label label-warning" title="Incluye <?php echo number_format($rg['mermaSinVentas'],2); ?>€ de artículos sin ventas">!</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:20px;color:#c0392b;"><?php echo number_format($rg['totalMermaGlobal'], 2); ?> €</div>
                            </div>
                            <div class="col-xs-6 col-sm-3 text-center">
                                <?php $clsG = $rg['margen_pct'] < 10 ? 'danger' : ($rg['margen_pct'] < 25 ? 'warning' : 'success'); ?>
                                <div style="font-size:11px;color:#888;">BENEFICIO REAL</div>
                                <div style="font-size:20px;font-weight:700;color:<?php echo $rg['beneficio'] >= 0 ? '#27ae60' : '#c0392b'; ?>">
                                    <?php echo number_format($rg['beneficio'], 2); ?> €
                                    <span class="label label-<?php echo $clsG; ?>" style="font-size:13px;"><?php echo number_format($rg['margen_pct'], 1); ?>%</span>
                                </div>
                            </div>
                        </div>
                        <?php if ($hasMermaSV): ?>
                        <hr style="margin:8px 0;">
                        <div>
                            <a data-toggle="collapse" href="#mermaSinVentas" style="font-size:12px;color:#c0392b;">
                                <span class="glyphicon glyphicon-warning-sign"></span>
                                <?php echo count($rg['articulos_merma_sin_ventas']); ?> artículo(s) con merma declarada pero sin ventas en el período
                                — <?php echo number_format($rg['mermaSinVentas'], 2); ?> € no asignados a ninguna familia
                            </a>
                            <div id="mermaSinVentas" class="collapse" style="margin-top:8px;">
                                <table class="table table-condensed table-bordered" style="margin-bottom:0;">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Artículo</th>
                                            <th>Uds merma</th>
                                            <th>Coste/ud</th>
                                            <th>Valor merma</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($rg['articulos_merma_sin_ventas'] as $msv): ?>
                                        <tr>
                                            <td><?php echo $msv['idArticulo']; ?></td>
                                            <td><?php echo htmlspecialchars($msv['articulo_name']); ?></td>
                                            <td><?php echo number_format($msv['udsMerma'], 3); ?></td>
                                            <td><?php echo number_format($msv['costeUsar'], 4); ?> €</td>
                                            <td class="text-danger"><strong><?php echo number_format($msv['valorMerma'], 2); ?> €</strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($opcion == 1): ?>
                <!-- Opción 1: Solo familias -->
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>FAMILIA</th>
                            <th>Nº REFERENCIAS</th>
                            <th>VENTA (s/IVA)</th>
                            <th>COSTE</th>
                            <th>MERMA</th>
                            <th>BENEFICIO REAL</th>
                            <th>MARGEN %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totVenta = 0; $totCoste = 0; $totBenef = 0; $totMerma = 0;
                        foreach ($datosInforme as $familia) {
                            $totVenta += $familia['totalVenta'];
                            $totCoste += $familia['totalCoste'];
                            $totBenef += $familia['beneficio'];
                            $totMerma += $familia['totalMerma'] ?? 0;
                            $cls = $familia['margen_pct'] < 10 ? 'danger'
                                 : ($familia['margen_pct'] < 25 ? 'warning' : 'success');
                        ?>
                        <?php $flFam = $familia['flujo'] ?? null; ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($familia['nombreN1']); ?></strong>
                                <?php if ($flFam): ?>
                                <?php
                                $fVCr = $flFam['compras_siva'] > 0 ? round($flFam['ventas_siva'] / $flFam['compras_siva'], 2) : null;
                                $fPct = $flFam['compras_siva'] > 0 ? round($flFam['resultado_siva'] / $flFam['compras_siva'] * 100, 1) : 0;
                                $fRoi = ($fVCr !== null) ? round($fPct * (365 / $diasPeriodo), 1) : null;
                                ?>
                                <span style="float:right;font-size:11px;color:#888;">
                                    Compras: <strong><?php echo number_format($flFam['compras_siva'], 2); ?></strong> €
                                    &nbsp;Ventas: <strong><?php echo number_format($flFam['ventas_siva'], 2); ?></strong> €
                                    &nbsp;Flujo: <strong style="color:<?php echo $flFam['resultado_siva'] >= 0 ? '#27ae60' : '#c0392b'; ?>">
                                        <?php echo number_format($flFam['resultado_siva'], 2); ?>
                                    </strong> €
                                    <?php if ($fVCr !== null): ?>
                                    <?php
                                    $fRotacion = (($familia['valor_stock'] ?? 0) > 0)
                                        ? round($familia['totalCoste'] / $familia['valor_stock'], 2)
                                        : null;
                                    ?>
                                    <span class="flujo-avanzado" style="display:none;">
                                    &nbsp;·&nbsp;V/C: <strong style="color:#8e44ad;"><?php echo $fVCr; ?>×</strong>
                                    <?php if ($fRotacion !== null): ?>
                                    &nbsp;Rot: <strong style="color:#2980b9;"><?php echo $fRotacion; ?>×</strong>
                                    <?php endif; ?>
                                    <?php if (isset($familia['gmroi']) && $familia['gmroi'] !== null): ?>
                                    <?php $fGmroiColor = $familia['gmroi'] >= 2 ? '#27ae60' : ($familia['gmroi'] >= 1 ? '#e67e22' : '#c0392b'); ?>
                                    &nbsp;GMROI: <strong style="color:<?php echo $fGmroiColor; ?>"><?php echo number_format($familia['gmroi'], 2); ?></strong>
                                    <?php endif; ?>
                                    </span>
                                    <?php endif; ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $familia['num_referencias']; ?></td>
                            <td><?php echo number_format($familia['totalVenta'], 2); ?></td>
                            <td><?php echo number_format($familia['totalCoste'], 2); ?></td>
                            <td><?php echo ($familia['totalMerma'] ?? 0) > 0
                                    ? '<span class="text-danger">' . number_format($familia['totalMerma'], 2) . '</span>'
                                    : '—'; ?></td>
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
                            <td><?php echo $totMerma > 0 ? '<strong class="text-danger">' . number_format($totMerma, 2) . '</strong>' : '—'; ?></td>
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
                            <th>MERMA</th>
                            <th>BENEFICIO REAL</th>
                            <th>MARGEN %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totVenta = 0; $totCoste = 0; $totBenef = 0; $totMerma = 0;
                        foreach ($datosInforme as $familia) {
                            $totVenta += $familia['totalVenta'];
                            $totCoste += $familia['totalCoste'];
                            $totBenef += $familia['beneficio'];
                            $totMerma += $familia['totalMerma'] ?? 0;
                        ?>
                        <?php $flFam = $familia['flujo'] ?? null; ?>
                        <tr style="background:#e8ecf0;">
                            <td colspan="7">
                                <strong><?php echo htmlspecialchars($familia['nombreN1']); ?></strong>
                                &nbsp;<span class="label label-default"><?php echo $familia['num_referencias']; ?> ref.</span>
                                &nbsp;Venta: <strong><?php echo number_format($familia['totalVenta'], 2); ?></strong> €
                                <?php if (($familia['totalMerma'] ?? 0) > 0): ?>
                                &nbsp;Merma: <strong class="text-danger"><?php echo number_format($familia['totalMerma'], 2); ?></strong> €
                                <?php endif; ?>
                                &nbsp;Benef: <strong><?php echo number_format($familia['beneficio'], 2); ?></strong> €
                                &nbsp;Margen: <strong><?php echo number_format($familia['margen_pct'], 1); ?>%</strong>
                                <?php if ($flFam): ?>
                                <?php
                                $fVCr = $flFam['compras_siva'] > 0 ? round($flFam['ventas_siva'] / $flFam['compras_siva'], 2) : null;
                                $fPct = $flFam['compras_siva'] > 0 ? round($flFam['resultado_siva'] / $flFam['compras_siva'] * 100, 1) : 0;
                                $fRoi = ($fVCr !== null) ? round($fPct * (365 / $diasPeriodo), 1) : null;
                                ?>
                                <span style="float:right;font-size:11px;color:#777;">
                                    Compras: <strong><?php echo number_format($flFam['compras_siva'], 2); ?></strong> €
                                    &nbsp;·&nbsp;Ventas: <strong><?php echo number_format($flFam['ventas_siva'], 2); ?></strong> €
                                    &nbsp;·&nbsp;Flujo: <strong style="color:<?php echo $flFam['resultado_siva'] >= 0 ? '#27ae60' : '#c0392b'; ?>">
                                        <?php echo number_format($flFam['resultado_siva'], 2); ?>
                                    </strong> €
                                    <?php if ($fVCr !== null): ?>
                                    <?php
                                    $fRotacion = (($familia['valor_stock'] ?? 0) > 0)
                                        ? round($familia['totalCoste'] / $familia['valor_stock'], 2)
                                        : null;
                                    ?>
                                    <span class="flujo-avanzado" style="display:none;">
                                    &nbsp;·&nbsp;V/C: <strong style="color:#8e44ad;"><?php echo $fVCr; ?>×</strong>
                                    <?php if ($fRotacion !== null): ?>
                                    &nbsp;Rot: <strong style="color:#2980b9;"><?php echo $fRotacion; ?>×</strong>
                                    <?php endif; ?>
                                    <?php if (isset($familia['gmroi']) && $familia['gmroi'] !== null): ?>
                                    <?php $fGmroiColor = $familia['gmroi'] >= 2 ? '#27ae60' : ($familia['gmroi'] >= 1 ? '#e67e22' : '#c0392b'); ?>
                                    &nbsp;GMROI: <strong style="color:<?php echo $fGmroiColor; ?>"><?php echo number_format($familia['gmroi'], 2); ?></strong>
                                    <?php endif; ?>
                                    </span>
                                    <?php endif; ?>
                                </span>
                                <?php endif; ?>
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
                            <td><?php echo ($sf['totalMerma'] ?? 0) > 0
                                    ? '<span class="text-danger">' . number_format($sf['totalMerma'], 2) . '</span>'
                                    : '—'; ?></td>
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
                            <td><?php echo $totMerma > 0 ? '<strong class="text-danger">' . number_format($totMerma, 2) . '</strong>' : '—'; ?></td>
                            <td><strong><?php echo number_format($totBenef, 2); ?></strong></td>
                            <td><strong><?php echo number_format($margenTotal, 1); ?>%</strong></td>
                        </tr>
                    </tfoot>
                </table>

                <?php elseif ($opcion == 3 || $opcion == 4): ?>
                <!-- Opción 3/4: Familias, subfamilias y productos -->
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>FAMILIA / SUBFAMILIA / PRODUCTO</th>
                            <th>ID</th>
                            <th>CANTIDAD</th>
                            <th>PVP</th>
                            <th>COSTE</th>
                            <th>VENTA</th>
                            <th>MERMA</th>
                            <th>BENEFICIO REAL</th>
                            <th>MARGEN %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totVenta = 0; $totCoste = 0; $totBenef = 0; $totMerma = 0;
                        foreach ($datosInforme as $familia) {
                            $totVenta += $familia['totalVenta'];
                            $totCoste += $familia['totalCoste'];
                            $totBenef += $familia['beneficio'];
                            $totMerma += $familia['totalMerma'] ?? 0;
                        ?>
                        <?php $flFam = $familia['flujo'] ?? null; ?>
                        <tr style="background:#d0d8e4;">
                            <td colspan="9" style="font-weight:700;">
                                <?php echo htmlspecialchars($familia['nombreN1']); ?>
                                &nbsp;<span class="label label-default"><?php echo $familia['num_referencias']; ?> ref.</span>
                                &nbsp;Venta: <?php echo number_format($familia['totalVenta'], 2); ?> €
                                <?php if (($familia['totalMerma'] ?? 0) > 0): ?>
                                &nbsp;Merma: <span class="text-danger"><?php echo number_format($familia['totalMerma'], 2); ?></span> €
                                <?php endif; ?>
                                &nbsp;Benef: <?php echo number_format($familia['beneficio'], 2); ?> €
                                &nbsp;<?php echo number_format($familia['margen_pct'], 1); ?>%
                                <?php if ($flFam): ?>
                                <?php
                                $fVCr = $flFam['compras_siva'] > 0 ? round($flFam['ventas_siva'] / $flFam['compras_siva'], 2) : null;
                                $fPct = $flFam['compras_siva'] > 0 ? round($flFam['resultado_siva'] / $flFam['compras_siva'] * 100, 1) : 0;
                                $fRoi = ($fVCr !== null) ? round($fPct * (365 / $diasPeriodo), 1) : null;
                                ?>
                                <span style="float:right;font-size:11px;color:#555;font-weight:400;">
                                    Compras: <strong><?php echo number_format($flFam['compras_siva'], 2); ?></strong> €
                                    &nbsp;·&nbsp;Ventas: <strong><?php echo number_format($flFam['ventas_siva'], 2); ?></strong> €
                                    &nbsp;·&nbsp;Flujo: <strong style="color:<?php echo $flFam['resultado_siva'] >= 0 ? '#27ae60' : '#c0392b'; ?>">
                                        <?php echo number_format($flFam['resultado_siva'], 2); ?>
                                    </strong> €
                                    <?php if ($fVCr !== null): ?>
                                    <?php
                                    $fRotacion = (($familia['valor_stock'] ?? 0) > 0)
                                        ? round($familia['totalCoste'] / $familia['valor_stock'], 2)
                                        : null;
                                    ?>
                                    <span class="flujo-avanzado" style="display:none;">
                                    &nbsp;·&nbsp;V/C: <strong style="color:#8e44ad;"><?php echo $fVCr; ?>×</strong>
                                    <?php if ($fRotacion !== null): ?>
                                    &nbsp;Rot: <strong style="color:#2980b9;"><?php echo $fRotacion; ?>×</strong>
                                    <?php endif; ?>
                                    <?php if (isset($familia['gmroi']) && $familia['gmroi'] !== null): ?>
                                    <?php $fGmroiColor = $familia['gmroi'] >= 2 ? '#27ae60' : ($familia['gmroi'] >= 1 ? '#e67e22' : '#c0392b'); ?>
                                    &nbsp;GMROI: <strong style="color:<?php echo $fGmroiColor; ?>"><?php echo number_format($familia['gmroi'], 2); ?></strong>
                                    <?php endif; ?>
                                    </span>
                                    <?php endif; ?>
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php foreach ($familia['subfamilias'] as $sf): ?>
                        <tr style="background:#eef1f5;">
                            <td colspan="9" style="padding-left:16px; font-weight:600;">
                                <?php echo htmlspecialchars($sf['nombreN2']); ?>
                                &nbsp;<span class="label label-default"><?php echo $sf['num_referencias']; ?> ref.</span>
                                &nbsp;Venta: <?php echo number_format($sf['totalVenta'], 2); ?> €
                                <?php if (($sf['totalMerma'] ?? 0) > 0): ?>
                                &nbsp;Merma: <span class="text-danger"><?php echo number_format($sf['totalMerma'], 2); ?></span> €
                                <?php endif; ?>
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
                            <td><?php echo number_format($art['costeUsado'], 2);
                                echo $art['coste_es_periodo'] ? '' : ' <span class="label label-default" title="Sin compra en el período — se usa ultimoCoste actual">*</span>'; ?></td>
                            <td><?php echo number_format($art['totalVenta'], 2); ?></td>
                            <td><?php echo ($art['valorMerma'] ?? 0) > 0
                                    ? '<span class="text-danger" title="' . number_format($art['udsMerma'] ?? 0, 3) . ' ud × ' . number_format($art['costeUsado'], 2) . '€/ud">'
                                      . number_format($art['valorMerma'], 2) . '</span>'
                                    : '—'; ?></td>
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
                            <td><?php echo $totMerma > 0 ? '<strong class="text-danger">' . number_format($totMerma, 2) . '</strong>' : '—'; ?></td>
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
