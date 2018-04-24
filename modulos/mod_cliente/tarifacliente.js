/* 
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

$(function () {
    $('#idArticulo').focus();

    $(".fox-buscar").on("change", function (event) {
        event.stopPropagation();
        event.preventDefault();
        var caja = $(event.target).data('obj');
        var idCliente = $('#id_cliente').val();
        var callback = function (respuesta) {
            var obj = JSON.parse(respuesta);
            var response = obj['datos'];
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

        switch (caja) {
            case 'cajaidArticulo':
                console.log('Entro en cajaidArticulo');
                var idArticulo = $('#idArticulo').val();
                if (idArticulo !== '') {
                    leerArticulo(idCliente, {caja: 'idArticulo', valor: idArticulo}, callback);
                } else {
                    alert('vacio');
                }
                break;
            case 'cajaReferencia':
                console.log('entro en agregarCliente');
                var Referencia = $('#Referencia').val();
                if (Referencia !== '') {
                    leerArticulo(idCliente, {caja: 'Referencia', valor: Referencia}, callback);
                } else {
                    alert('vacio');
                }
                break;
            case 'cajaCodBarras':
                console.log('entro en tarificarCliente');
                if (validarChecks(seleccion)) {
                    window.location.href = '/clientes/' + seleccion[0] + '/tarifa';
                }
                break;
        }


    });

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




});
function anular(e) {
    tecla = (document.all) ? e.keyCode : e.which;
    return (tecla != 13);
}

function controlEventos(event) {
    console.log('===== ENTRE CONTROLEVENTOS LIB/TECLADO ======');
}

function leerArticulo(idcliente, parametro, callback) {
    $.ajax({
        data: parametro,
        url: './leerArticulo.php',
        type: 'post',
        success: callback,
        error: function (request, textStatus, error) {
            console.log(textStatus);
        }
    });

}

