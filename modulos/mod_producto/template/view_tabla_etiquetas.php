 <table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th><input type="checkbox" class="checkSelectTodos" name="checkIdSelectTodos" onclick="CambiarEstadoCheckTodos()"></th>
            <th>ID</th>
            <th>PRODUCTO</th>
            <th>P.V.P</th>
            <th>Tipo<span class="glyphicon glyphicon-info-sign" title="Tipo unidad o  peso"></span></th>
            <th>Cantidad<br/>Etiquetas</th>
            <th>Acciones<br/>
            <span class="glyphicon glyphicon-trash" title="Eliminamos listado etiquetas"></span>
            </th>
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
                <td><?php echo $producto['tipo'];?></td>
                <td>
                    <input type="text" size="4" value="1" style="text-align: right" class="cantidadEtiquetas" data-idarticulo="<?php echo $producto['idArticulo']; ?>"></td>
                <td>
                    <a onclick="selecionarItemProducto(<?php echo $producto['idArticulo'].",'".$dedonde."'";?>)">
                    <span class="glyphicon glyphicon-trash"></span>
                    </a>
                </td>
            </tr>
        <?php
        }
    ?>
    </tbody>
</table>
