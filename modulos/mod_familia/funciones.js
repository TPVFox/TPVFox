/* 
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */


$(function () {

    //~ leerfamiliaspadre0();

    $("#botonnuevo-hijo-0").on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

        window.location.href = 'familia.php?id=0';
    });


    $("#btn-fam-volver").on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

        var data = $(event.currentTarget).data();
        if (data.href) {
            window.location.href = data.href;
        }
    });

    $("#btn-fam-grabar").on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

        var data = $(event.currentTarget).data();

        var id = $('#idfamilia').val();
        var idpadre = $('#inputidpadre').val();
        var nombrefamilia = $('#inputnombre').val();
        var beneficiomedio = $('#inputbeneficio').val();
        var volvera = data.href;

        var mensajes = [];
        if (idpadre == -1) {
            mensajes.push('Por favor seccione un padre de la lista');
        }
        if (nombrefamilia.length == 0) {
            mensajes.push('Por favor da un nombre a la familia');
        }


        if (mensajes.length > 0) {
            //errores
            for (var i = 0; i < mensajes.length; i++) {
                alert(mensajes[i]);
            }
        } else {
            ajaxCall({pulsado: 'grabarFamilia',
                id: id,
                nombrefamilia: nombrefamilia,
                idpadre: idpadre,
                beneficiomedio: beneficiomedio,
                href: volvera}, function (respuesta) {
                var obj = JSON.parse(respuesta);
                var error = obj.error;

                if (error == '0') {
                    var href = obj.href;
                    alert('Grabado correctamente');
                    window.location.href = href;
                } else {
                    alert(error + ' ' + obj.insert);
                }
            }
            );
        }

    });

    $("#btn-padre-grabar").on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();
        //var data = $(event.currentTarget).data();

        var seleccion = seleccionados('idproducto');
        var idfamilia = $('#idfamilia').val();
        var idnuevafamilia = $('#inputIdNuevaModal').val();
        if(seleccion.length > 0) {
            ajaxCall({pulsado: 'cambiarFamiliaProducto',
                idfamilia: idfamilia,
                idnuevafamilia: idnuevafamilia,
                idsproductos : seleccion,
            }, function (respuesta) {
                var obj = JSON.parse(respuesta);
                console.log(obj);
                if (!obj.error) {
                    alert('cambiados correctamente');
                    
                    location.reload();
                } else {
                    alert('Error al borrar');
                }
            }
            );

        }
        
    });

    $("#btn-eliminar").on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

        var seleccion = seleccionados();

        if (seleccion.length > 0) {
            ajaxCall({pulsado: 'borrarFamilias',
                idsfamilias: seleccion,
            }, function (respuesta) {
                var obj = JSON.parse(respuesta);
                console.log(obj);
                if (!obj.error) {
                    alert('borrado correctamente');
                    location.reload();
                } else {
                    alert('Error al borrar');
                }
            }
            );

        }
    });

    $("#btn-expandirtodo").on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

        var quedan = expandirTodos();
        alert('quedan por expandir ' + quedan);
    });

    $("#btn-compactartodo").on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

        compactarTodos();


    });

    $('#inputNombreFamiliaModal').autocomplete({
        minLength: 3,
        source: function (request, response) {
            // Fetch data
            $.ajax({
                url: "tareas.php",
                type: 'post',
                data: {
                    pulsado: 'BuscaNombreFamilia',
                    nombre: request.term
                },
                success: function (data) {
                    var obj = JSON.parse(data);
                    response(obj);
                }
            });
        },
        select: function (event, ui) {
            console.log(event);
            if (ui) {
                $('#nombreFamilia').val(ui.item.label);
                $('#inputIdFamiliaModal').val(ui.item.valor);
            }
            //return false;
        }
    });

    capturaSeleccionar();

    $("#combopadre").combobox({
        select: function (event, ui) {
            $('#inputidpadre').val(ui.item.value);
        },
    });

    $("#combopadremodal").combobox({
        select: function (event, ui) {
            $('#inputIdNuevaModal').val(ui.item.value);
        },
    });

});


function capturaSeleccionar() {
    $("#btn-borrarfamilia").on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

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
                    capturaSeleccionar(); // ¡¡OJO se llama a si mismo!!. ¿Y funciona?
                } else {
                    alert('Error al borrar');
                }
            }
            );

        }
    });

        $(".btn-seleccionar").on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

        var data = $(event.currentTarget).data();
        if (data.idproducto) {
            idtr = "tr_" + data.idproducto;
            if ($('#' + idtr).hasClass('seleccionado')) {
                $('#' + idtr).removeClass('seleccionado');
            } else {
                $('#' + idtr).addClass('seleccionado');
            }
        }
        var cuenta = contarSeleccionados();
        if (cuenta > 0) {
            $('#btn-cambiarpadre').show();
            $('#btn-borrarfamilia').show();
        } else {
            $('#btn-cambiarpadre').hide()
            $('#btn-borrarfamilia').hide()
        }

    });

}



//~ function leerfamiliaspadre0() {
    //~ leerFamilias(0, function (respuesta) {
        //~ var obj = JSON.parse(respuesta);
        //~ var datos = obj.datos;
        //~ var tabla = obj.html;

        //~ $('#seccion-' + obj.padre).html(tabla);
    //~ });
//~ }



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
                $("#botonexpandir-" + obj.padre).removeClass('btn-expandir')
                $("#botonexpandir-" + obj.padre).hide();
                $("#botoncompactar-" + obj.padre).show();
                $("#botoncompactar-" + obj.padre).addClass('btn-compactar');
                $("#fila-" + obj.padre).show();
                $("#fila-" + obj.padre).addClass('al-filavisible');
                $('#seccion-' + obj.padre).html(tabla);
            });
        });

        $("#botoncompactar-" + botones[i]).on("click", function (event) {
            event.stopPropagation();
            event.preventDefault();

            var seccion = $(event.currentTarget).data('alseccion');

            $("#botoncompactar-" + seccion).removeClass('btn-compactar')
            $("#botoncompactar-" + seccion).hide();
            $("#botonexpandir-" + seccion).show();
            $("#botonexpandir-" + seccion).addClass('btn-expandir');
            $("#fila-" + seccion).removeClass('al-filavisible');
            $("#fila-" + seccion).hide();
        });

        $("#botonnuevo-hijo-" + botones[i]).on("click", function (event) {
            event.stopPropagation();
            event.preventDefault();



            var data = $(event.currentTarget).data();

            var parametros = {
                pulsado: 'descendientes',
                idfamilia: data.alpadre
            };

            ajaxCall(parametros, function (respuesta) {
                var obj = JSON.parse(respuesta);
                var datos = obj.datos;
                var tabla = obj.html;
                console.log(obj);
            }
            );

        });

        $("#botonMarcaEliminar-" + botones[i]).on("click", function (event) {
            event.stopPropagation();
            event.preventDefault();


            var idfamilia = $(event.currentTarget).data('alseccion');
            var productos = $(event.currentTarget).data('productos');

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

function grabarPadres(datos, callback) {
    var parametros = {
        pulsado: 'grabarPadres',
        idpadre: datos.idpadre,
        idsfamilia: datos.idsfamilia
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
    return $('.btn-expandir').length;
}

function compactarTodos() {
    $('.btn-compactar').each(function (key, element) {
        var idboton = $(element).attr('id');
        $('#' + idboton).click();
    });
    return $('.btn-compactar').length;
}
