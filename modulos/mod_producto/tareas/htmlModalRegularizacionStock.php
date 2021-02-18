<?php
$idArticulo = $_POST['idarticulo'];
$Producto = $NCArticulo->GetProducto($idArticulo);

$html = '
    <table>
        <tr>
            <td colspan="2">
                <h5>Stock de '.$Producto['articulo_name'].'</h5>
            </td>                                    
        </tr>
        <tr>
            <td>Stock Actual</td>
            <td>Stock Real</td>
        </tr>
        <tr>
            <td><input type="text" id="stockactual" value="'.number_format($Producto['stocks']['stockOn'], 2, '.', '').'" readonly="readonly"/></td>
            <td><input type="text" id="stockcolocar" value="'.number_format($Producto['stocks']['stockOn'], 2, '.', '').'" /></td>
        </tr>
    </table>
    <button type="submit" class="btn btn-default" onclick="grabarRegularizacion();" >Guardar</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
    <input type="hidden" id="articuloid" value="'.$idArticulo.'" />';

$respuesta['html'] =$html;
           
