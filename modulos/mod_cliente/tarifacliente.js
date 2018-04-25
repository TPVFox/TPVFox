/* 
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

$(function () {
    var self = this;

    $('#idArticulo').focus();

    var testigo = 0;

    $("#btn-grabar-tc").button().on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

        var parametros = {
            idarticulo: $('#inputIdArticulo').val(),
            pvpSiva: $('#inputPrecioSin').val(),
            pvpCiva: $('#inputPrecioCon').val(),
            idcliente: $('#id_cliente').val()
        };

        $.ajax({
            data: parametros,
            url: './grabarArticuloCliente.php',
            type: 'post',
            success: function (response) {
                var idcliente = $('#id_cliente').val();
                window.location.href = './tarifaCliente.php?id=' + idcliente;
            },
            error: function (request, textStatus, error) {
                console.log(textStatus);
            }
        });
    });

    $("#btn-cancelar-tc").button().on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

        $('#inputIdArticulo').val('');
        $('#inputPrecioSin').val('');
        $('#inputPrecioCon').val('');
        $('#id_cliente').val('');

        $('#formulario').hide();
        $('#idArticulo').focus();
    });

    $(".al-editiva").blur(function (event) {

        event.stopPropagation();
        event.preventDefault();

        var result = $('#' + $(event.target).data('result'));
        var obj = $('#' + $(event.target).data('obj')).val();
        var percent = $('#' + $(event.target).data('percent')).val();
        var factor = $(event.target).data('factor');

        var valor = eval(obj + factor + (1 + (percent / 100)));
        result.val(valor);
    });

    $(".art-modificar").button().on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

        var caja = 'idArticulo';
        var valor = $(event.target).data('idarticulo');
        var idcliente = $(event.target).data('idcliente');

        var callback = function (respuesta) {
                var obj = JSON.parse(respuesta);
                var response = obj.datos;
                var idCliente = $('#id_cliente').val();
                $('#inputIdArticulo').val(response['idArticulo']);
                $('#inputDescripcion').val(response['descripcion']);
                $('#inputPrecioSin').val(response['pvpSiva']);
                $('#inputIVA').val(response['ivaArticulo']);
                $('#inputPrecioCon').val(response['pvpCiva']);
                $('#idcliente').val(idCliente);
                $('#formulario').show();
                $('#inputPrecioSin').focus();
            };
        leerArticulo(idcliente, {caja:caja,valor:valor}, callback)
    });

});
