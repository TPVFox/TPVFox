/* 
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */


$(function () {
    $('#inputFechadesde').val('01/01/2018');
    $('#inputFechahasta').val('31/12/2018');

    $(".boton-visualizar").on("click", function (event) {
//        event.stopPropagation();
//        event.preventDefault();

        var data = $(event.currentTarget).data();
        $('#linea_' + data.productoid).toggle();
    });

});

function MayorProductos() {

    var idsproducto = $('#idsproducto').val();
    idsproducto = idsproducto.split(",");
    var parametros;
    for (var i = 0; i < idsproducto.length; i++) {
        parametros = {pulsado: 'imprimemayor',
            idproducto: parseInt(idsproducto[i]),
            stockinicial: $('#stkini' + idsproducto[i].trim()).val(),
            fechainicio: $('#inputFechadesde').val(),
            fechafin: $('#inputFechahasta').val(),
        }
        ajaxMayor(parametros, function (response) {
            var resultado = $.parseJSON(response);
            if (resultado['html']) {
                console.log(resultado['tiempo'])
                $('#multiCollapseExample' + resultado['idproducto']).html(resultado['html']);
                ajaxHtml2PDF({idproducto: resultado['idproducto'],
                pulsado:'imprimePDFMayor'}, function (response) {
                    var resultado = JSON.parse(response);
                    $('#imprimir' + resultado['idproducto']).html(resultado['fichero']);
                    console.log(resultado['tiempo']);
                });
//                $('#tablamayor').show();
            } else {
                $('#multiCollapseExample' + resultado['idproducto']).html(resultado['error']);
                $('#imprimir' + resultado['idproducto']).html('');
            }
            $('#visualizar' + resultado['idproducto']).show();
        })
    }

}


function ajaxMayor(parametros, callback) {

    $.ajax({
        data: parametros,
        url: './tareasmayor.php',
        type: 'post',
        beforeSend: function () {
            var html_spinner = '<div class="text-center">'
                    + '<span class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span>'
                    + '</div>';
            $('#imprimir' + parametros.idproducto).html(html_spinner);
            $('#visualizar' + parametros.idproducto).hide();

        },
        success: callback,
        error: function (request, textStatus, error) {
            console.log(textStatus);
        }
    });
}

function ajaxHtml2PDF(parametros, callback) {

    $.ajax({
        data: parametros,
        url: './tareasmayor.php',
        type: 'post',
        beforeSend: function () {
            var html_spinner = '<div class="text-center">'
                    + '<span class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span>'
                    + '</div>';
            $('#imprimir' + parametros.idproducto).html(html_spinner);
            $('#visualizar' + parametros.idproducto).hide();

        },
        success: callback,
        error: function (request, textStatus, error) {
            console.log(textStatus);
        }
    });
}

