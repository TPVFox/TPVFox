<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>IdArticulo</th>
            <th>PRODUCTO</th>
            <th>PVP<br/>con iva</th>
            <th>COSTE</th>
            <th>Tipo</th>
            <th>STOCK INICIAL<span class="glyphicon glyphicon-info-sign" title="Indicamos con cuanto stock quieres que empiece el mayor"></span></th>
            <th>STOCK ACTUAL</th>
            <th>ELIMINAR <span class="glyphicon glyphicon-info-sign" title="Lo borramos de la selección"></span></th>
            <th>VISUALIZAR</th>
            <th>IMPRIMIR</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($Nproductos as $producto) {
            ?>
            <tr>
                <td><?php echo $producto['idArticulo']; ?></td>
                <td><?php echo $producto['articulo_name']; ?></td>
                <td><?php echo number_format($producto['pvpCiva'], 2); ?>€</td>
                <td><?php echo number_format($producto['ultimoCoste'], 2); ?>€</td>
                <td><?php echo $producto['tipo']; ?></td>

                <td><input type="text" size="6" value="0" style="text-align: right" id="<?php echo 'stkini' . $producto['idArticulo']; ?>"></td>
                <td>
                    <?php
                    // Si es de peso mostramos decimales , sino entero solo..
                    $redondeo = 0;
                    if ($producto['tipo'] === 'peso'){
                        $redondeo = 3;
                    }
                     echo number_format(round($producto['stock'],3),$redondeo);
                     ?>
                </td>
                <td>
                    <a onclick="selecionarItemProducto(<?php echo $producto['idArticulo']; ?>, 'ListaMayor')">
                        <span class="glyphicon glyphicon-trash"></span>
                    </a>
                </td>
                <td>
                    <a onclick="redirecionarMayor(<?php echo $producto['idArticulo']; ?>,'DetalleMayor')">
                        <span class="glyphicon glyphicon-eye-open"></span>
                    </a>
                </td>
                <td>
                    <span class="glyphicon glyphicon-print"></span>
                </td>
            </tr>
            <?php
        }
        ?>
    </tbody>
</table>
