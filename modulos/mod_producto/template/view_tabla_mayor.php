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
            <th>Acciones<br/>
            <span class="glyphicon glyphicon-trash" title="Eliminamos listado etiquetas"></span>
            <span class="glyphicon glyphicon-eye-open" title="Ver por pantalla mayor"></span>
            <span class="glyphicon glyphicon-print" title="Crear pdf para imprimir Mayor"></span>
            <th>Stock<br>INICIAL<span class="glyphicon glyphicon-info-sign" title="Indicamos con cuanto stock quieres que empiece el mayor"></span></th>
            
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
                    <a class="btn" onclick="selecionarItemProducto(<?php echo $producto['idArticulo']; ?>, 'ListaMayor')">
                        <span class="glyphicon glyphicon-trash"></span>
                    </a>
                
                    <a class="btn" onclick="redirecionarMayor(<?php echo $producto['idArticulo']; ?>,'DetalleMayor')">
                        <span class="glyphicon glyphicon-eye-open"></span>
                    </a>
                    <span class="glyphicon glyphicon-print"></span>
                </td>
                <td><input type="text" size="6" value="0" style="text-align: right" id="<?php echo 'stkini' . $producto['idArticulo']; ?>"></td>
                
            </tr>
            <?php
        }
        ?>
    </tbody>
</table>
