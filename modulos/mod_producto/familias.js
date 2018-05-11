/* 
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */


$(function () {

    leerFamilias(0, function (respuesta) {
        var obj = JSON.parse(respuesta);
        var datos = obj.datos;
        var tabla = obj.html;

        $('#seccion-' + obj.padre).html(tabla);
    });

        $("#botonnuevo-hijo-0").on("click", function (event) {
            event.stopPropagation();
            event.preventDefault();

            var data = $(event.currentTarget).data();
            $('#selectFamiliaPadre').html('<option value="0" '
                    + ' selected="selected" > Familia Raíz </option>');
            $('#familiaPadre').val(data.alpadre);
            $('#inputNombreModal').val('');
            $('#familiasModal').modal('show');
            $('#inputNombreModal').focus();
        });


    $("#btn-fam-grabar").on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();
        //var data = $(event.currentTarget).data();
        var idpadre = $('#selectFamiliaPadre').val();
        var nombrefamilia = $('#inputNombreModal').val();
        var mensajes = [];
        if (idpadre == -1) {
            mensajes.push('Por favor seccione un padre de la lista');
        }
        if (nombrefamilia.length == 0) {
            mensajes.push('Por favor da un nombre a la familia');
        }
        if (mensajes.length > 0) {
            //errores
        } else {
            grabarFamilia({idpadre: idpadre, nombrefamilia: nombrefamilia}, function (response) {
                if ($("#fila-" + idpadre).hasClass('al-filavisible')) {
                    leerFamilias(idpadre, function (respuesta) {
                        var obj = JSON.parse(respuesta);
                        var datos = obj.datos;
                        var tabla = obj.html;

                        $('#seccion-' + obj.padre).html(tabla);
                    });
                } else {
                    if ($("#botonexpandir-" + idpadre).length === 0) {
                        //repintar abuelo
                        var idabuelo = $("#botonnuevo-hijo-" + idpadre).data('alabuelo');
                        leerFamilias(idabuelo, function (respuesta) {
                            var obj = JSON.parse(respuesta);
                            var datos = obj.datos;
                            var tabla = obj.html;

                            $("#botonexpandir-" + obj.padre).hide();
                            $("#botoncompactar-" + obj.padre).show();
                            $("#fila-" + obj.padre).show();
                            $("#fila-" + obj.padre).addClass('al-filavisible');
                            $('#seccion-' + obj.padre).html(tabla);
                        });
                    }
                }
                $('#inputNombreModal').val('');
                $('#familiasModal').modal('hide');
            });
        }
    });

});

function capturaevento_click(botones) {
    for (var i = 0; i <= botones.length - 1; i++) {
        $("#botonexpandir-" + botones[i]).on("click", function (event) {
            event.stopPropagation();
            event.preventDefault();


            boton = $(event.currentTarget);

            var seccion = boton.data('alseccion');

            leerFamilias(seccion, function (respuesta) {
                var obj = JSON.parse(respuesta);
                var datos = obj.datos;
                var tabla = obj.html;

                $("#botonexpandir-" + obj.padre).hide();
                $("#botoncompactar-" + obj.padre).show();
                $("#fila-" + obj.padre).show();
                $("#fila-" + obj.padre).addClass('al-filavisible');
                $('#seccion-' + obj.padre).html(tabla);
            });
        });

        $("#botoncompactar-" + botones[i]).on("click", function (event) {
            event.stopPropagation();
            event.preventDefault();

            var seccion = $(event.currentTarget).data('alseccion');

            $("#botoncompactar-" + seccion).hide();
            $("#botonexpandir-" + seccion).show();
            $("#fila-" + seccion).removeClass('al-filavisible');
            $("#fila-" + seccion).hide();
        });

        $("#botonnuevo-hijo-" + botones[i]).on("click", function (event) {
            event.stopPropagation();
            event.preventDefault();

            var data = $(event.currentTarget).data();
            $('#selectFamiliaPadre').html('<option value="' + data.alpadre
                    + '" selected="selected" >' + data.altexto + '</option>');
            $('#familiaPadre').val(data.alpadre);
                $('#inputNombreModal').val('');
            
            $('#familiasModal').modal('show');
            $('#inputNombreModal').focus();
        });
    }
}

function leerFamilias(idpadre, callback) {
    var parametros = {
        pulsado: 'leerFamilias',
        idpadre: idpadre
    };

    $.ajax({
        data: parametros,
        url: './tareas.php',
        type: 'post',
        success: callback,
        error: function (request, textStatus, error) {
            console.log(textStatus);
        }
    });
}

function leerFamiliaPadre(idpadre, callback) {
    var parametros = {
        pulsado: 'leerFamiliaPadre',
        idpadre: idpadre
    };

    $.ajax({
        data: parametros,
        url: './tareas.php',
        type: 'post',
        success: callback,
        error: function (request, textStatus, error) {
            console.log(textStatus);
        }
    });
}

function grabarFamilia(datos, callback) {
    var parametros = {
        pulsado: 'grabarFamilia',
        idpadre: datos.idpadre,
        nombre: datos.nombrefamilia
    };

    $.ajax({
        data: parametros,
        url: './tareas.php',
        type: 'post',
        success: callback,
        error: function (request, textStatus, error) {
            console.log(textStatus);
        }
    });
}
