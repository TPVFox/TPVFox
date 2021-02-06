/* 
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

function contarProductosWeb(){
    var callback = function (respuesta){
        var obj = JSON.parse(respuesta);
            if (obj.totalProductos > 0) {
                var totalProductos = obj.totalProductos;
                $("#bar1").show();
                $("#boton_subir_stock").prop("disabled",true);
                var cantidad=900;
                if (obj.totalProductos <= 900){
                    cantidad= obj.totalProductos
                }
                SubirStockWeb(0,cantidad, totalProductos,'1');
            }
        }   
    var parametros = {
        pulsado: 'contarproductos',
        tipo:'web'
    }

    ajaxStock(parametros, callback);

}


function contarProductosEstoqueables(callback) {
    var parametros = {
        pulsado: 'contarproductos',
        tipo:'tpv'
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



function SubirStockWeb(inicio, cantidad, total,idBar) {

    var parametros = {
        pulsado : 'subirStockYPrecio',
        inicial : parseInt(inicio),
        cantidad : cantidad,
        totalProductos : total
    };

    BarraProceso(inicio,total,idBar);
    ajaxStock(parametros, function (response) {
        var obj = JSON.parse(response);
        if (obj) {
            elementos = obj.elementos;
            actual = obj.actual;
            totalProductos = obj.totalProductos;
            if (actual < totalProductos){
                cantidad = totalProductos-actual;
                if (cantidad >900){
                    cantidad= 900;
                }
                console.log('elementos:'+elementos.length + ' Cantidad:'+cantidad+' Actual:'+actual);
                if (elementos.length > 0) {
                    SubirStockWeb(actual, cantidad, totalProductos, idBar);
                }
            } else {
            // fin
            BarraProceso(total,total,idBar);

            }
        } 
    });
}



function limpiarPermisosModulos() {
    var parametros = {
        pulsado : 'limpiarPermisosModulos',
    };
    ajaxStock(parametros, function (response) {
        
        var obj = JSON.parse(response);
        var n_elementos = Object.keys(obj.eliminado);
        console.log(obj);
        $("#boton_limpiar_permisos").prop("disabled",false);
        alert ( 'Eliminamos '+ n_elementos.length + ' modulos \n'+  'Pendiente continuar maquetando resultado');
        
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


