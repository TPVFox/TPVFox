/* 
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */


$(function () {

    $("#boton-stock").on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

        contarProductosEstoqueables(function (respuesta) {
            var obj = JSON.parse(respuesta);
            if (obj.totalProductos > 0) {
                var totalProductos = obj.totalProductos;
                $("#bar0").show();
                $("#boton-stock").prop("disabled",true);
                RegenerarStock(0, 100, totalProductos,'0');
            }

        });
    });

});

function contarProductosEstoqueables(callback) {
    var parametros = {
        pulsado: 'contarproductos'
    }
    ajaxStock(parametros, callback);
}

function RegenerarStock(inicio, pagina, total,idBar) {

    var parametros = {
        pulsado : 'generastock',
        inicial : parseInt(inicio),
        pagina : pagina,
        totalProductos : total
    };

    BarraProceso(inicio,total,idBar);
    ajaxStock(parametros, function (response) {
        var obj = JSON.parse(response);
        if (obj) {
            elementos = obj.elementos;
            actual = obj.actual;
            totalProductos = obj.totalProductos;
            pagina = obj.pagina;
            
            console.log(obj.stocks);
            
            if (elementos > 0) {
                RegenerarStock(actual, pagina, totalProductos, idBar);
            } else {
                $("#boton-stock").prop("disabled",false);
            }
        }
    });
}


function ajaxStock(parametros, callback) {

    $.ajax({
        data: parametros,
        url: './tareasreorganizar.php',
        type: 'post',
        success: callback,
        error: function (request, textStatus, error) {
            console.log(textStatus);
        }
    });
}


