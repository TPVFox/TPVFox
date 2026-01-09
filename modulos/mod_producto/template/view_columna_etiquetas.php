    Selecciona el tamaño:
    <select id="tamanhos" name="tamanhos">
        <option value="A5">A5</option>
        <option value="A7">A7</option>
        <option value="A8">A8</option>
        <option value="A8">A9</option>
    </select>
    <br><br>
    Referencia o tecla:
    <select id="teclaOReferencia" name="teclaOReferencia">
        <option value="1">Sin referencia</option>
        <option value="2">Con Tecla</option>
        <option value="3">Con Referencia</option>
    </select>
    <br><br>
    <input type="submit" value="Imprimir Seleccionado" name="Imprimir" onclick='
            <?php echo 'imprimirEtiquetas(' . '"' . $dedonde . '")'; ?>'>
