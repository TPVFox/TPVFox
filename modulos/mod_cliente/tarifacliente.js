/* 
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

$(function () {
// Esta variable global la necesita para montar la lineas.
// En configuracion podemos definir SI / NO

    var CONF_campoPeso = "no";
    var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
    cabecera['idUsuario'] = 1; // Tuve que adelantar la carga, sino funcionaria js.
    cabecera['idTienda'] = 1;
    cabecera['estado'] = 'Abierto'; // Si no hay datos GET es 'Nuevo'
    cabecera['idTemporal'] = 0;
    cabecera['idReal'] = 0;
    cabecera['idCliente'] = 0;
    cabecera['fecha'] = '2018-04-23';
    // Si no hay datos GET es 'Nuevo';
    var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array

    console.log("entre en el javascript");
    var cajaFecha = {id_input: 'fecha', acciones: {40: 'saltar_idClienteFlechaAbajo', 39: 'saltar_idCliente', 13: 'saltar_idCliente', 9: 'saltar_idCliente'}, parametros: {dedonde: 'pedidos'}};
    var cajaIdCliente = {id_input: 'id_cliente', acciones: {13: 'buscarClientes', 39: 'saltar_nombreCliente', 38: 'saltar_Fecha', 9: 'saltar_nombreCliente', 40: 'saltar_idArticulo'}, parametros: {dedonde: 'pedidos'}};
    var cajaCliente = {id_input: 'Cliente', acciones: {13: 'buscarClientes', 39: 'saltar_idArticulo', 38: 'saltar_Fecha', 9: 'saltar_idArticulo', 40: 'saltar_idArticulo', 37: 'saltar_idCliente'}, parametros: {dedonde: 'pedidos'}};
    var cajaBusquedacliente = {id_input: 'cajaBusquedacliente', acciones: {9: 'buscarClientes', 13: 'buscarClientes', 40: 'buscarClientes'}, parametros: {dedonde: 'pedidos'}};
    var cajaidArticulo = {id_input: 'idArticulo', acciones: {13: 'buscarProductos', 39: 'saltar_Referencia', 38: 'saltar_nombreClienteArticulo', 9: 'saltar_Referencia', 40: 'saltar_productos', 37: 'saltar_idCliente'}, parametros: {dedonde: 'pedidos', campo: 'a.idArticulo'}};
    var cajaReferencia = {id_input: 'Referencia', acciones: {13: 'buscarProductos', 38: 'saltar_nombreClienteArticulo', 9: 'saltar_CodBarras', 37: 'saltar_idArticulo', 39: 'saltar_CodBarras'}, parametros: {dedonde: 'pedidos', campo: 'at.crefTienda'}};
    var cajaCodBarras = {id_input: 'Codbarras', acciones: {13: 'buscarProductos', 38: 'saltar_nombreClienteArticulo', 9: 'saltar_Descripcion', 39: 'saltar_Descripcion', 37: 'saltar_Referencia'}, parametros: {dedonde: 'pedidos', campo: 'ac.codBarras'}};
    var cajaDescripcion = {id_input: 'Descripcion', acciones: {13: 'buscarProductos', 37: 'saltar_CodBarras', 38: 'saltar_nombreClienteArticulo'}, parametros: {dedonde: 'pedidos', campo: 'a.articulo_name'}};
    var cajaBusquedaproductos = {id_input: 'cajaBusqueda', acciones: {13: 'buscarProductos', 40: 'mover_down', 9: 'mover_down'}, parametros: {dedonde: 'pedidos', campo: '', prefijo: 'Fila_'}, before_constructor: 'Si'};
    var idN = {after_constructor: 'Si', id_input: 'N_', acciones: {40: 'mover_down', 9: 'mover_down', 38: 'mover_up'}, parametros: {dedonde: 'cerrados', campo: '', prefijo: 'N_'}, before_constructor: 'Si'};
    var Unidad_Fila = {after_constructor: 'Si', id_input: 'Unidad_Fila', acciones: {13: 'recalcular_totalProducto', 40: 'mover_up', 38: 'mover_down'}, parametros: {dedonde: 'pedidos', campo: '', prefijo: 'Unidad_Fila_'}, before_constructor: 'Si'};
    var numPedido = {id_input: 'numPedido', acciones: {39: 'saltar_idCliente', 37: 'saltarFechaAl', 9: 'saltar_idCliente', 13: 'buscarPedido'}, parametros: {dedonde: 'albaran'}};
    var fechaVenci = {id_input: 'fechaVenci', acciones: {13: 'selectFormas'}, parametros: {dedonde: 'factura'}};
    var cajaEimporte = {id_input: 'Eimporte', acciones: {13: 'insertarImporte'}, parametros: {dedonde: 'factura'}};
    $('#idArticulo').focus();

    $(".fox-buscar").on("change", function (event) {
        event.stopPropagation();
        event.preventDefault();
        var caja = $(event.target).data('obj');
        var idCliente = $('#id_cliente').val();
        var callback = function (respuesta) {
            var obj = JSON.parse(respuesta);
            var response = obj['datos']['datos'];
            var idCliente = $('#id_cliente').val();
            $('#inputIdArticulo').val(response['idArticulo']);
            $('#inputDescripcion').val(response['descripcion']);
            $('#inputPrecioSin').val(response['pvpSiva']);
            $('#inputIVA').val(response['ivaArticulo']);
            $('#inputPrecioCon').val(response['pvpCiva']);
            $('#idcliente').val(idCliente);
            $('#formulario').show();
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

