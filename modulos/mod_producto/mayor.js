/* 
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */


$(function () {
    $('#inputFechadesde').val('01/01/2018');
    $('#inputFechahasta').val('31/12/2018');

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
            var resultado = JSON.parse(response);
            if (resultado['html']) {
                $('#tablamayor').html(resultado['html']);
                $('#tablamayor').show();
                $('#imprimir' + resultado['idproducto']).html(resultado['fichero']);
            } else {
                $('#tablamayor').html(resultado['error']);
            }
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
        },
        success: callback,
        error: function (request, textStatus, error) {
            console.log(textStatus);
        }
    });
}

