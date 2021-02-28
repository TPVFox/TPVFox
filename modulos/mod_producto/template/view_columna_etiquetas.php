    Selecciona el tamaño: 
    <select id="tamanhos" name="tamanhos">
        <option value="1">A5</option>
        <?php
        if ( $ClasePermisos->getModulo('mod_balanza') == 1) {
            echo '<option value="1T">A5-Con Tecla</option>';
        }
        ?>
        <option value="2">A7</option>
        <?php
        if ( $ClasePermisos->getModulo('mod_balanza') == 1) {
            echo '<option value="2T">A7-Con Tecla</option>';
        }
        ?>
        <option value="3">A8</option>
        <option value="4">A9</option>
    </select>
    <br><br>
    <input type="submit" value="Imprimir Seleccionado" name="Imprimir" onclick='
            <?php echo'imprimirEtiquetas('.'"'.$dedonde.'")';?>'>
