 <table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th><input type="checkbox" class="checkSelectTodos" name="checkIdSelectTodos" onclick="CambiarEstadoCheckTodos()"></th>
            <th>ID</th>
            <th>PRODUCTO</th>
            <th>PVP<br/>con iva</th>
            <th>COSTE</th>
            <th>Tipo<span class="glyphicon glyphicon-info-sign" title="Tipo unidad o  peso"></span></th>
            <th>Stock<br/> ACTUAL</th>
            <th>Acciones<br/>
            <span class="glyphicon glyphicon-trash" title="Eliminamos listado etiquetas"></span>
            </th>
            <th>Cantidad<br/>Etiquetas</th>
        </tr>
    </thead>
    <tbody>
    <?php
        foreach ($Nproductos as $producto) {
            ?>
            <tr>
                <td><input type="checkbox" class="checkSelect" name="checkNameSelect"  value="<?php echo $producto['idArticulo']; ?>" checked >
                </td>
                <td><?php echo $producto['idArticulo'];?></td>
                <td><?php echo $producto['articulo_name'];?></td>
                <td><?php echo number_format($producto['pvpCiva'],2);?>€</td>
                <td><?php echo number_format($producto['ultimoCoste'], 2); ?>€</td>
                <td><?php echo $producto['tipo'];?></td>
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
                    <a onclick="selecionarItemProducto(<?php echo $producto['idArticulo'].",'".$dedonde."'";?>)">
                    <span class="glyphicon glyphicon-trash"></span>
                    </a>
                </td>
                <td>
                    <input type="text" size="4" value="1" style="text-align: right" class="cantidadEtiquetas" data-idarticulo="<?php echo $producto['idArticulo']; ?>">
                </td>
            </tr>
        <?php
        }
    ?>
    </tbody>
</table>
