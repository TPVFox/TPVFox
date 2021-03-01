<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th><input type="checkbox" class="checkSelectTodos" name="checkIdSelectTodos" onclick="CambiarEstadoCheckTodos()"></th>
            <th>IdArticulo</th>
            <th>PRODUCTO</th>
            <th>PVP<br/>con iva</th>
            <th>COSTE</th>
            <th>Tipo</th>
            <th>Stock<br/> ACTUAL</th>
            <?php echo $Tpl['th_columnas_mayores'];?>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($Nproductos as $producto) {
            ?>
            <tr>
                <td><input type="checkbox" class="checkSelect" name="checkNameSelect"  value="<?php echo $producto['idArticulo']; ?>" checked >
                </td>
                <td><?php echo $producto['idArticulo']; ?></td>
                <td><?php echo $producto['articulo_name']; ?></td>
                <td><?php echo number_format($producto['pvpCiva'], 2); ?>€</td>
                <td><?php echo number_format($producto['ultimoCoste'], 2); ?>€</td>
                <td><?php echo $producto['tipo']; ?></td>
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
                    <?php echo $producto['td_acciones'];?>
                </td>
                <td>
                    <?php echo $producto['input'];?>
                </td>
                
            </tr>
            <?php
        }
        ?>
    </tbody>
</table>
