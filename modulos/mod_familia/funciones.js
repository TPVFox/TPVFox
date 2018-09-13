/* 
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */


$(function () {
    $("#combopadre").combobox({
        select: function (event, ui) {
            console.log(ui.item.value);
            $('#inputidpadre').val(ui.item.value);
        },
    });

});

function EliminarFamiliasSeleccionadas(){
        var seleccion = seleccionados();
        if (seleccion.length > 0) {
            ajaxCall({pulsado: 'borrarFamilias',
                idsfamilias: seleccion,
            }, function (respuesta) {
                var respuesta = JSON.parse(respuesta);
                console.log(respuesta);
                if (!respuesta.error) {
                    alert('borrado correctamente');
                    location.reload();
                } else {
                    alert('Error al borrar');
                }
            }
            );

        }
}
function guardarFamilia(){
        var id = $('#idfamilia').val();
        var idpadre = $('#inputidpadre').val();
        var nombrefamilia = $('#inputnombre').val();
        var beneficiomedio = $('#inputbeneficio').val();

        var mensajes = [];
        if (idpadre == -1) {
            mensajes.push('Por favor seccione un padre de la lista');
        }
        if (nombrefamilia.length == 0) {
            mensajes.push('Por favor da un nombre a la familia');
        }
        if (mensajes.length > 0) {
            for (var i = 0; i < mensajes.length; i++) {
                alert(mensajes[i]);
            }
        } else {
            ajaxCall({pulsado: 'grabarFamilia',
                id: id,
                nombrefamilia: nombrefamilia,
                idpadre: idpadre,
                beneficiomedio: beneficiomedio
                }, function (respuesta) {
                var respuesta = JSON.parse(respuesta);
                var error = respuesta.error;

                if (error == '0') {
                    alert('Grabado correctamente');
                    window.location.href = './ListaFamilias.php';
                } else {
                    alert(error + ' ' + respuesta.insert);
                }
            }
            );
        }
}

function borrarProductoFamilia(){
    console.log("Entre en borrar familia");
    var seleccion = seleccionados('idproducto');
        var idfamilia = $('#idfamilia').val();

        if(seleccion.length > 0) {
            ajaxCall({pulsado: 'borrarFamiliaProducto',
                idfamilia: idfamilia,
                idsproductos : seleccion,
            }, function (respuesta) {
                var obj = JSON.parse(respuesta);
                console.log(obj);
                if (!obj.error) {
                    $('#tproductos').parent().html(obj.html);
                    alert('borrado correctamente');
                    borrarFamilia(); // ¡¡OJO se llama a si mismo!!. ¿Y funciona?
                } else {
                    alert('Error al borrar');
                }
            }
            );

        }
}
function compactar(idFamilia){
            $("#botoncompactar-" + idFamilia).removeClass('btn-compactar')
            $("#botoncompactar-" + idFamilia).hide();
            $("#botonexpandir-" + idFamilia).show();
            $("#botonexpandir-" + idFamilia).addClass('btn-expandir');
            $("#fila-" + idFamilia).removeClass('al-filavisible');
            $("#fila-" + idFamilia).hide();
}
function expandir(idFamilia){
        leerFamilias(idFamilia, function (respuesta) {
                var obj = JSON.parse(respuesta);
                var datos = obj.datos;
                var tabla = obj.html;
                $("#botonexpandir-" + obj.padre).removeClass('btn-expandir')
                $("#botonexpandir-" + obj.padre).hide();
                $("#botoncompactar-" + obj.padre).show();
                $("#botoncompactar-" + obj.padre).addClass('btn-compactar');
                $("#fila-" + obj.padre).show();
                $("#fila-" + obj.padre).addClass('al-filavisible');
                $('#seccion-' + obj.padre).html(tabla);
            });
}
function seleccionarProductos(idProducto){
            idtr = "tr_" + idProducto;
            if ($('#' + idtr).hasClass('seleccionado')) {
                $('#' + idtr).removeClass('seleccionado');
            } else {
                $('#' + idtr).addClass('seleccionado');
            }
        var cuenta = contarSeleccionados();
        if (cuenta > 0) {
            $('#btn-cambiarpadre').show();
            $('#btn-borrarfamilia').show();
        } else {
            $('#btn-cambiarpadre').hide()
            $('#btn-borrarfamilia').hide()
        }
}
function marcarFamiliaEliminar(productos, idfamilia){
            if (productos === 0) {
                if ($('#fila0-' + idfamilia).hasClass('seleccionado')) {
                    $('#fila0-' + idfamilia).removeClass('seleccionado');
                    $('#check' + idfamilia).prop("checked", false);
                } else {
                    $('#fila0-' + idfamilia).addClass('seleccionado');
                    $('#check' + idfamilia).prop("checked", true);
                }
                var contador = contarSeleccionados();
                if (contador > 0) {
                    if ($('#btn-eliminar')) {
                        $('#btn-eliminar').show();
                    }
                } else {
                    if ($('#btn-eliminar')) {
                        $('#btn-eliminar').hide();
                    }
                }
            } else {
                alert('Si quieres dejar huerfanos ' + productos + ' productos, pídele permiso al Riiichard');
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

function ajaxCall(parametros, callback) {

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

function contarSeleccionados() {
    return $('.seleccionado').length;
}

function seleccionados(propiedad='idfamilia') {   
    var ids = [];
    $('.seleccionado').each(function (key, element) {
        var valores = $(element).data(propiedad);
        ids.push(valores);
    });
    return ids;
}

function expandirTodos() {
    $('.btn-expandir').each(function (key, element) {
        var idboton = $(element).attr('id');
        $('#' + idboton).click();
    });
    alert('quedan por expandir ' + $('.btn-expandir').length);
}

function compactarTodos() {
    $('.btn-compactar').each(function (key, element) {
        var idboton = $(element).attr('id');
        $('#' + idboton).click();
    });
}
