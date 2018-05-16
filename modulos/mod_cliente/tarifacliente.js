/* 
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

$(function () {

    $("#btn-grabar-tc").button().on("click", function (event) {
        self.numero
        event.stopPropagation();
        event.preventDefault();

        var parametros = {
            idarticulo: $('#inputIdArticulo').val(),
            pvpSiva: parseFloat($('#inputPrecioSin').val()).toFixed(2),
            pvpCiva: parseFloat($('#inputPrecioCon').val()).toFixed(2),
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
//        $('#id_cliente').val('');

        $('#formulario').hide();
        $('#idArticulo').focus();
    });

    $(".al-editiva").blur(function (event) {

        event.stopPropagation();
        event.preventDefault();

        var result = $('#' + $(event.target).data('result'));
        var obj = parseFloat($('#' + $(event.target).data('obj')).val()).toFixed(2);
        $('#' + $(event.target).data('obj')).val(obj);
        var percent = $('#' + $(event.target).data('percent')).val();
        var factor = $(event.target).data('factor');

        var valor = eval(obj + factor + (1 + (percent / 100)));
        result.val(valor.toFixed(2));
    });

    $(".art-modificar").button().on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

        var caja = 'idpreciocliente';
        var valor = $(event.target).data('idarticulo');
        var idcliente = $(event.target).data('idcliente');

        var callback = function (respuesta) {
            var obj = JSON.parse(respuesta);
            var response = obj.datos[0];
            var idCliente = $('#id_cliente').val();
            $('#inputIdArticulo').val(response['idArticulo']);
            $('#inputDescripcion').val(response['descripcion']);
            $('#inputPrecioSin').val(parseFloat(response['pvpSiva']).toFixed(2));
            $('#inputIVA').val(response['ivaArticulo']);
            $('#inputPrecioCon').val(parseFloat(response['pvpCiva']).toFixed(2));
            $('#idcliente').val(idCliente);
            $('#formulario').show();
            $('#inputPrecioSin').focus();
        };
        leerArticulo({idcliente: idcliente, caja: caja, valor: valor}, callback);
    });

    $(".art-eliminar").button().on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();
        var idarticulo = $(event.target).data('idarticulo');
        var idcliente = $(event.target).data('idcliente');
        if (confirm('¿Deseas eliminar este articulo de la tarifa del cliente?')) {
            borrarArticulo(idcliente, idarticulo, function (event) {
                window.location.href = './tarifaCliente.php?id=' + idcliente;
            });

        }
    });

    $(".art-buscar").button().on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

        $('#paginabuscar').val(1);
        buscarArticulos();

    });


    $('#cajaidArticulo').focus();
    var optionsBootpag = {
        total: 5,
        page: 1,
        maxVisible: 5,
        leaps: true,
        firstLastUse: true,
        wrapClass: 'pagination',
        activeClass: 'active',
        disabledClass: 'disabled',
        next: 'siguiente',
        prev: 'anterior',
        last: 'final',
        first: 'inicio'
    };
    $('.articulos-page-selection-bottom, .articulos-page-selection-top').bootpag(optionsBootpag).on("page", function (event, num) {
        $("#paginabuscar").val(num);
        buscarArticulos();
    });

});


function buscarArticulos() {
    var campo = $('#campoabuscar').val();
    var valor = $('#cajaBusqueda').val();

//    resetpagina = resetpagina || 0;
//    if (resetpagina) {
//        $('#paginabuscar').val(1);
//    }

    leerArticulo({idcliente: cliente.idClientes
        , caja: campo
        , usarlike: 'si'
        , valor: valor
        , pagina: $('#paginabuscar').val()}, function (respuesta) {
        var obj = JSON.parse(respuesta);
        var datos = obj.datos;
        var tabla = obj.html;

        $('#paginabuscar').val(obj.pagina);

        if (tabla) {
            $('.modal-body > p').html(tabla);
            $('.articulos-page-selection-bottom, .articulos-page-selection-top').bootpag({total: obj.totalPaginas, page: obj.pagina});

            // click en columna 1 de la tabla con el idArticulo
            $(".btn-busca-art").button().on("click", function (event) {
                event.stopPropagation();
                event.preventDefault();

                var idarticulo = $(event.target).data('id');

                var callback = function (respuesta) {
                    var obj = JSON.parse(respuesta);
                    var response = obj.datos;
                    var idCliente = $('#id_cliente').val();
                    if (response.length == 1) {
                        response = response[0];
                        $('#busquedaModal').modal('hide');
                        $('#inputIdArticulo').val(response['idArticulo']);
                        $('#inputDescripcion').val(response['descripcion']);
                        $('#inputPrecioSin').val(parseFloat(response['pvpSiva']).toFixed(2));
                        $('#inputIVA').val(response['ivaArticulo']);
                        $('#inputPrecioCon').val(parseFloat(response['pvpCiva']).toFixed(2));
                        $('#idcliente').val(idCliente);
                        $('#formulario').show();
                        $('#inputPrecioSin').focus();
                    }
                };

                leerArticulo({idcliente: cliente.idClientes
                    , caja: 'idArticulo'
                    , valor: idarticulo}, callback);

            });

        }
    }
    );

}


function borrarInputsFiltro() {
    $('#cajaidArticulo').val('');
    $('#cajaReferencia').val('');
    $('#cajaCodbarras').val('');
    $('#cajaDescripcion').val('');
}

