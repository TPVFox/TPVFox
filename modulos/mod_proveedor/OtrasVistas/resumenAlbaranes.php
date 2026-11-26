<?php
include_once './../../../inicial.php';
include_once $URLCom . '/modulos/mod_proveedor/funciones.php';
include_once $URLCom . '/controllers/Controladores.php';
include_once $URLCom . '/modulos/mod_producto/clases/ClaseProductos.php';
include_once $URLCom . '/modulos/mod_proveedor/clases/ClaseProveedor.php';
//~ $ClasesParametros = new ClaseParametros('../parametros.xml');
$CTArticulos = new ClaseProductos($BDTpv);
$CProveedor = new ClaseProveedor();
$Controler = new ControladorComun;

// $fechaInicial="";
// $fechaFinal="";
$style = 'style="display:none;"';
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $idProveedor = $id;
    $datosProveedor = $CProveedor->getProveedor($id);
    if (!isset($datosProveedor['error']) && empty($datosProveedor['datos'])) {
        // Un número que no es de un proveedor: se avisa y se termina
        echo '<!DOCTYPE html><html><head>';
        include_once $URLCom . '/head.php';
        echo '</head><body>';
        include_once $URLCom . '/modulos/mod_menu/menu.php';
        echo '<div class="alert alert-warning">No existe el proveedor ' . $id . '</div>';
        echo '</body></html>';
        exit;
    }
    if (isset($datosProveedor['error'])) {
        $errores[1] = array(
            'tipo' => 'DANGER!',
            'dato' => $datosProveedor['consulta'],
            'class' => 'alert alert-danger',
            'mensaje' => 'Error sql'
        );
    } else {
        $titulo = 'Resumen Albaranes';
    }
} else {
    // Sin el número del proveedor no hay nada que consultar: se avisa y se termina
    echo '<!DOCTYPE html><html><head>';
    include_once $URLCom . '/head.php';
    echo '</head><body>';
    include_once $URLCom . '/modulos/mod_menu/menu.php';
    echo '<div class="alert alert-danger"><strong>DANGER! </strong> Error no se ha enviado el id del proveedor</div>';
    echo '</body></html>';
    exit;
}
if (isset($_POST['porfechas'])) {
    //Cuando se envia por fechas se comprueba que las fechas estan bien escritas y que están las dos
    $comprobarFechas = comprobarFechas($_POST['fechaInicial'], $_POST['fechaFinal']);
    if (isset($comprobarFechas['error'])) {
        $errores[8] = array(
            'tipo' => 'Info!',
            'dato' => $comprobarFechas['consulta'],
            'class' => 'alert alert-info',
            'mensaje' => ''
        );
    } else {
        header('Location: resumenAlbaranes.php?fechaIni=' . $comprobarFechas['fechaIni'] .
            '&fechaFin=' . $comprobarFechas['fechaFin'] . '&id=' . $id);
    }
}
if (isset($_POST['portodo'])) {
    //Si buscamos todo recarga la página sin fechas
    header('Location: resumenAlbaranes.php?fechaIni=&fechaFin=&id=' . $id);
}
if (isset($_GET['fechaIni']) & isset($_GET['fechaFin'])) {
    //Cuando recibimos los datos tenga fechas escritas o no buscamos los resumenes en la clase
    //MOstramos errores de sql;
    $fechaIni = $_GET['fechaIni'];
    $fechaFin = $_GET['fechaFin'];
    $fechasBien = true;
    if ($fechaIni <> "" & $fechaFin <> "") {
        // Las fechas de la dirección se comprueban como las del formulario
        $comprobarFechas = comprobarFechas($fechaIni, $fechaFin);
        if (isset($comprobarFechas['error'])) {
            $fechasBien = false;
            $errores[8] = array('tipo' => 'info', 'mensaje' => $comprobarFechas['consulta']);
        } else {
            $fechaIni = $fechaInicial = $comprobarFechas['fechaIni'];
            $fechaFin = $fechaFinal = $comprobarFechas['fechaFin'];
        }
    }
    if ($fechasBien) {
        $style = "";
        $arrayNums = $CProveedor->albaranesProveedoresFechas($idProveedor, $fechaIni, $fechaFin);
        if (isset($arrayNums['error'])) {
            $errores = $arrayNums['error'];
        }
    }
}
// El orden del resumen de productos: una de sus tres cabeceras; si no, por cantidad de más a menos
$campoOrden = 'totalUnidades';
$sentidoOrden = 'DESC';
if (isset($_GET['campoorden']) && in_array($_GET['campoorden'], array('idArticulo', 'cdetalle', 'totalUnidades'), true)) {
    $campoOrden = $_GET['campoorden'];
    $sentidoOrden = (isset($_GET['sentidoorden']) && $_GET['sentidoorden'] === 'ASC') ? 'ASC' : 'DESC';
}
// Obtenemos referencias principales de proveedor (solo artículos donde es proveedor principal).
$num_ref_principales_proveedor = $CTArticulos->GetProductosProveedor($id, 'articulo_name', 'ASC', true);
$num_ref_principales_proveedor = $num_ref_principales_proveedor['NItems'];
$num_referencias_compradas = 0;
$num_ref_principales_compradas = 0;
// Si no existe $_GET fechaIni o fechaFin , no hacemos los calculos.
if (isset($_GET['fechaIni']) && isset($_GET['fechaFin'])) {
    // Ahora sumamos los productos
    $productos = array();
    if (isset($arrayNums['productos'])) {
        $productos = $CProveedor->SumaLineasAlbaranesProveedores($arrayNums['productos'], 'KO');
        // Ahora montamos $arrayNums con cDetalle
        foreach ($productos as $key => $producto) {
            // Obtenemos datos producto, para añadir nombre Codbarras.
            $p = $CTArticulos->GetProducto($producto['idArticulo']);
            $productos[$key]['cdetalle'] = $p['articulo_name'];
            $productos[$key]['tipo'] = $p['tipo'];
            $productos[$key]['prov_principal'] = 'KO';
            if (isset($p['proveedor_principal']['idProveedor'])) {
                // Ya que puede venir vacia.
                if ($p['proveedor_principal']['idProveedor'] == $idProveedor) {
                    // Si coincide como proveedor principal del producto lo marcamos.
                    $productos[$key]['prov_principal'] = 'OK';
                    $num_ref_principales_compradas++;
                }
            }
            $num_referencias_compradas++;
        }
        // Por cantidad, de más a menos, cada producto con su clase; después, el orden de la cabecera elegida
        $cortesAbc = cortesAbcCantidad($URLCom);
        $productos = clasificarProductosAbc($productos, $cortesAbc[0], $cortesAbc[1]);
        $productos = ordenarProductosResumen($productos, $campoOrden, $sentidoOrden);
    }
}
if (!isset($cortesAbc)) {
    $cortesAbc = cortesAbcCantidad($URLCom);
}
$estilosAbc = array('A' => 'label-success', 'B' => 'label-warning', 'C' => 'label-default');
// ---   Comprobación de fechas   ---- //
if (!isset($fechaFinal)) {
    $fechaFinal = date('Y-m-d');
}
if (!isset($fechaInicial)) {
    $fechaInicial = date('Y') . '-01-01';
}

$mod_vista = array('vista' => 'facturasListado.php', 'modulo' => 'mod_compras');
?>

<!DOCTYPE html>
<html>

<head>
    <?php
    include_once $URLCom . '/head.php';
    ?>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_proveedor/funciones.js?v=<?php echo filemtime($URLCom . '/modulos/mod_proveedor/funciones.js'); ?>"></script>

</head>

<body>
    <?php
    include_once $URLCom . '/modulos/mod_menu/menu.php';
    if (isset($errores)) {
        foreach ($errores as $error) {
            echo '<div class="alert alert-' . $error['tipo'] . '">'
                . $error['mensaje']
                . '</div>';
        }
    }
    ?>

    <div class="container">
        <div class="col-md-12 text-center">
            <h2 class="text-center"> <?php echo $titulo; ?></h2>
        </div>
        <div class="col-md-12">
            <div class="col-md-12">
                <?php echo $Controler->getHtmlLinkVolver('Volver'); ?>
            </div>
            <div class="col-md-3 ">
                <a class="btn btn-primary" onclick="imprimirResumen('albaran', '<?php echo $id; ?>', '<?php echo $fechaInicial; ?>', '<?php echo $fechaFinal; ?>')">Imprimir resumen</a>
                <h4><u>DATOS DEL PROVEEDOR</u></h4>
                <form method="get" class="form-inline">
                    <?php if (isset($_GET['fechaIni']) && isset($_GET['fechaFin'])) { ?>
                    <input type="hidden" name="fechaIni" value="<?php echo htmlspecialchars($_GET['fechaIni'], ENT_QUOTES); ?>">
                    <input type="hidden" name="fechaFin" value="<?php echo htmlspecialchars($_GET['fechaFin'], ENT_QUOTES); ?>">
                    <?php } ?>
                    <input type="hidden" name="campoorden" value="<?php echo $campoOrden; ?>">
                    <input type="hidden" name="sentidoorden" value="<?php echo $sentidoOrden; ?>">
                    <b>ID: </b>
                    <span class="input-group input-group-sm" style="width:130px; display:inline-table; vertical-align:middle;">
                        <input type="text" name="id" value="<?php echo $id; ?>" inputmode="numeric" class="form-control" title="Escribe el número de otro proveedor y pulsa Intro: se conservan las fechas">
                        <span class="input-group-btn"><input type="submit" class="btn btn-default" value="Ir"></span>
                    </span>
                </form>
                <b>Nombre: </b><?php echo $datosProveedor['datos'][0]['nombrecomercial']; ?></br>
                <b>Razón social: </b><?php echo $datosProveedor['datos'][0]['razonsocial']; ?></br>
                <b>NIF:</b><?php echo $datosProveedor['datos'][0]['nif']; ?></br>
            </div>
            <div class="col-md-4">
                <form method="post">
                    <label>Fecha Inicial</label>
                    <input type="date" id="fechaInicial" name="fechaInicial" value="<?php echo $fechaInicial; ?>" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" placeholder='dd-mm-yyyy' title=" Formato de entrada dd-mm-yyyy">
                    <label>Fecha Final</label>
                    <input type="date" id="fechaFinal" name="fechaFinal" value="<?php echo $fechaFinal; ?>" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" placeholder='dd-mm-yyyy' title=" Formato de entrada dd-mm-yyyy">
                    <br><br>
                    <input type="submit" name="porfechas" class="btn btn-info" value="Resumen fechas">
                    <input type="submit" name="portodo" class="btn btn-warning" value="Todo">

                </form>
            </div>
            <div class="col-md-5 " <?php echo $style; ?>>
                <h4 class="text-center"><u>TOTALES Y DESGLOSE POR IVAS</u></h4>
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th></th>
                            <th>BASE</th>
                            <th>IVA</th>
                            <th>TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalLinea     = 0;
                        $totalAlbaranes = 0;
                        $totalBases     = 0;
                        $totalIvas      = 0;
                        if (isset($arrayNums['desglose'])) {
                            foreach ($arrayNums['desglose'] as $desglose) {
                                $totalLinea = $desglose['sumBase'] + $desglose['sumiva'];
                                $totalAlbaranes += $totalLinea;
                                $totalBases     += $desglose['sumBase'];
                                $totalIvas      += $desglose['sumiva'];
                                echo '<tr>
                                    <td>' . $desglose['iva'] . '%</td>
                                    <td>' . $desglose['sumBase'] . '</td>
                                    <td>' . $desglose['sumiva'] . '</td>
                                    <td>' . $totalLinea . '</td>
                                </tr>';
                            }
                        }
                        // Ahora ponemos las sumas
                        echo '<tr class="alert-success">';
                        echo '<th>TOTALES</th><th>' . $totalBases . '</th><th>' . $totalIvas . '</th><th>' . $totalAlbaranes . '</th>';
                        echo '</tr>';
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-6" <?php echo $style; ?>>
            <div class="col-md-12">
                <p>
                    <strong>Referencias compradas:</strong><?php echo $num_referencias_compradas; ?><br />
                    <strong>Referencias principales compradas:</strong><?php echo $num_ref_principales_compradas; ?><br />
                    <strong>Referencias asignadas al proveedor:</strong><?php echo $num_ref_principales_proveedor; ?><br />
                    Leyenda:<br />
                    (P) Producto asignado como principal del proveedor. <br />
                    (CM) Coste medio: Si se compro a distintos precios.<br />
                    <span class="label label-success">A</span> hasta el <?php echo $cortesAbc[0]; ?> % de la cantidad comprada,
                    <span class="label label-warning">B</span> hasta el <?php echo $cortesAbc[1]; ?> %,
                    <span class="label label-default">C</span> el resto.<br />
                </p>

            </div>
            <h4 class="text-center"><u>RESUMEN PRODUCTOS</u></h4>
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th title="Clase por la cantidad acumulada">CL</th>
                        <th class="ordenar" data-campo="idArticulo" style="cursor:pointer;">ID <?php echo obtenerIconoOrden($campoOrden, $sentidoOrden, 'idArticulo'); ?></th>
                        <th title="Si este Proveedor es Principal para este producto, marcado *">P</th>
                        <th class="ordenar" data-campo="cdetalle" style="cursor:pointer;">PRODUCTO <?php echo obtenerIconoOrden($campoOrden, $sentidoOrden, 'cdetalle'); ?></th>
                        <th>NºVECES</th>
                        <th class="ordenar" data-campo="totalUnidades" style="cursor:pointer;">CANTIDAD <?php echo obtenerIconoOrden($campoOrden, $sentidoOrden, 'totalUnidades'); ?></th>
                        <th>COSTE</th>
                        <th title="Si cambio el precio, se calcula coste medio, marcado *">CM</th>
                        <th>IMPORTE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalLineas = 0;
                    if (isset($_GET['fechaIni']) & isset($_GET['fechaFin'])) {
                        foreach ($productos as $producto) {
                            $totalLineas += $producto['total_linea'];
                    ?>
                            <tr>
                                <td><span class="label <?php echo $estilosAbc[$producto['clase']]; ?>"><?php echo $producto['clase']; ?></span></td>
                                <td><?php echo $producto['idArticulo']; ?></td>
                                <td><?php
                                    if ($producto['prov_principal'] == 'OK') {
                                        echo '*';
                                    }
                                    ?></td>
                                <td><?php echo $producto['cdetalle']; ?></td>
                                <td><?php echo $producto['num_compras']; ?></td>

                                <td><?php
                                    if ($producto['tipo'] == 'peso') {
                                        echo number_format($producto['totalUnidades'], 3);
                                    } else {
                                        echo number_format($producto['totalUnidades'], 0);
                                    }; ?></td>
                                <td><?php echo number_format($producto['costeSiva'], 2); ?></td>
                                <td><?php
                                    if ($producto['coste_medio'] == 'OK') {
                                        echo '*';
                                    }
                                    ?></td>
                                <td><?php echo number_format($producto['total_linea'], 2); ?></td>
                            </tr>
                    <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
            <div class="col-md-12">
                <div class="col-md-5 col-md-offset-7">
                    <div class="panel panel-success">
                        <div class="panel-heading">
                            <h3 class="panel-title">TOTAL: <?php echo number_format($totalLineas, 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 " <?php echo $style; ?>>
            <h4 class="text-center"><u>ALBARANES</u></h4>
            <?php
            $estadoProveedor = $datosProveedor['datos'][0]['estado'];
            if (isset($ClasePermisos) && $ClasePermisos->getAccion("Crear", $mod_vista) && $estadoProveedor == 'Activo'): ?>
                <button id="btnModoFactura" class="btn btn-primary">
                    Crear factura desde albaranes
                </button>
                <form method="POST" action="../../mod_compras/factura.php" id="facturaDesdeAlbaranesForm">
                    <?php
                    echo '<input type="hidden" name="action" value="crearDesdeAlbaranes">';
                    echo '<input type="hidden" name="idProveedor" value="' . $idProveedor . '">';
                    ?>
                <?php endif; ?>
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th class="modo-factura" style="display:none;"><a title="Seleccionar todos los albaranes" id="selectAllAlbaranes" class="glyphicon glyphicon-check"></a></th>
                            <th>FECHA</th>
                            <th>ALBARÁN</th>
                            <th>Su NºAlbaran</th>
                            <th>ESTADO</th>
                            <th>LINK</th>
                            <th>BASE</th>
                            <th>IVA</th>
                            <th>TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalLinea = 0;
                        $totalbases = 0;
                        if (isset($arrayNums['resumenBases'])) {
                            foreach ($arrayNums['resumenBases'] as $bases) {
                                $totalLinea = $bases['sumabase'] + $bases['sumarIva'];
                                $totalbases = $totalbases + $totalLinea;
                                $date = date_create($bases['fecha']);
                                $checkbox = '';


                                if (isset($ClasePermisos) && $ClasePermisos->getAccion("Crear", $mod_vista)) {
                                    if ($bases['estado'] != 'Facturado') {
                                        $checkbox = '<input type="checkbox" name="albaranes[]" value="' . $bases['Numalbpro'] . '">';
                                    } else {
                                        $checkbox = '<span class="text-muted glyphicon glyphicon-lock"></span>';
                                    }
                                }
                                echo '<tr>';
                                if (isset($ClasePermisos) && $ClasePermisos->getAccion("Crear", $mod_vista)) {
                                    echo '<td class="modo-factura" style="display:none;">
                                        ' . $checkbox . '
                                    </td>';
                                }
                                echo '<td>' . date_format($date, "d/m/y H:i") . '</td>
                                <td>' . $bases['Numalbpro'] . '</td>
                                <td>' . $bases['Su_numero'] . '</td>
                                <td>' . $bases['estado'] . '</td>
                                <td>
                                    <a class="glyphicon glyphicon-pencil" target="_blank"
                                    href="../../mod_compras/albaran.php?id=' . $bases['idalbpro'] . '&accion=editar">
                                    </a>
                                </td>
                                <td>' . $bases['sumabase'] . '</td>
                                <td>' . $bases['sumarIva'] . '</td>
                                <td>' . $totalLinea . '</td>
                            </tr>';
                            }
                        }
                        ?>

                    </tbody>
                </table>

                <?php if (isset($ClasePermisos) && $ClasePermisos->getAccion("Crear", $mod_vista)): ?>
                    <button type="submit" class="btn btn-success modo-factura" style="display:none;">
                        Generar factura
                    </button>

                </form>
            <?php endif; ?>
            <div class="col-md-12">
                <div class="col-md-5">
                </div>
                <div class="col-md-7">
                    <div class="panel panel-success">
                        <div class="panel-heading">
                            <h3 class="panel-title">TOTAL: <?php echo $totalbases; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
<script>
    // Las cabeceras del resumen de productos ordenan al pulsarlas
    $(document).ready(function() {
        catchEvents();
    });
    document.getElementById('btnModoFactura').addEventListener('click', function() {
        const elementos = document.querySelectorAll('.modo-factura');
        elementos.forEach(el => {
            el.style.display = el.style.display === 'none' ? '' : 'none';
        });
    });
    document.getElementById('selectAllAlbaranes').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('input[name="albaranes[]"]');
        const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
        checkboxes.forEach(checkbox => {
            checkbox.checked = !allChecked;
        });
    });
</script>

</html>
