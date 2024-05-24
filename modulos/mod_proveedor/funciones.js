function catchEvents() {
    $(".ordenar").off('click').on('click', function (event) {
        event.stopPropagation();
        event.preventDefault();

        let urlString = window.location.href;   // url actual
        let url = new URL(urlString);           // objeto URL con la url actual
        let parametrosURL = url.searchParams;   //parametros GET del objeto url

        let campoorden = $(this).data('campo'); // variable array data, elemento campo
        let ordenascendente = true; //$(this).data('sentido');  // variable array data, elemento campo
        let campourl = parametrosURL.get('campoorden');
        let sentidourl = parametrosURL.get('sentidoorden') == 'ASC';

        if (campourl && (campourl == campoorden)) {
            ordenascendente = !sentidourl;
        }

        parametrosURL.set('campoorden', campoorden);    // cambiar el valor del parametro de la url campoorden
        // si no existe se asigna y si existe se modifica
        parametrosURL.set('sentidoorden', (ordenascendente ? 'ASC' : 'DESC'));

        url.search = parametrosURL.toString();  // actualizar los parametros de la URL

        window.location.href = url.toString();  // cambiar de URL

    });

}



function metodoClick(pulsado) {
    console.log("Inicimos switch de control pulsar");
    switch (pulsado) {
        case 'VerProveedor':
            console.log('Entro en proveedor ver');
            // Cargamos variable global ar checkID = [];
            VerIdSeleccionado();
            if (checkID.length > 1 || checkID.length === 0) {
                alert('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
                return
            }
            window.location.href = './proveedor.php?id=' + checkID[0] + '&accion=ver';
            break;

        case 'AgregarProveedor':
            console.log('entro en agregar proveedor');
            window.location.href = './proveedor.php';
            break;

        case 'ListadoProductos':
            console.log('Entro en Listado de productos de un proveedor');
            // Cargamos variable global ar checkID = [];
            VerIdSeleccionado();
            if (checkID.length > 1 || checkID.length === 0) {
                alert('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
                return
            }
            window.location.href = './OtrasVistas/ListadoProductosDeProveedor.php?id=' + checkID[0];
            break;

    }
}

function resumen(dedonde, idProveedor) {

    window.location.href = './OtrasVistas/resumenAlbaranes.php?id=' + idProveedor;
}

function imprimirResumen(dedonde, id, fechaInicial, fechaFinal) {
    var parametros = {
        'pulsado': 'imprimirResumenAlbaran',
        idProveedor: id,
        fechaInicial: fechaInicial,
        fechaFinal: fechaFinal
    };
    $.ajax({
        data: parametros,
        url: './../tareas.php',
        type: 'post',
        success: function (response) {
            var resultado = $.parseJSON(response);
            console.log(resultado);
            window.open(resultado);
        },

    });
}
function filtroEstado(input, id) {
    // @ Objetivo
    // Ocultar o mostrar Row con estado tal.
    if (input.value == '1') {
        // Ocultamos
        $(input).val('0');
        var x = document.getElementsByClassName('Row_' + id);

        for (var i = 0; i < x.length; i++) {
            x[i].style.display = "none";
        }


    } else {
        // Mostramos
        $(input).val('1');
        var x = document.getElementsByClassName('Row_' + id);
        for (var i = 0; i < x.length; i++) {
            x[i].removeAttribute("style");
        }
    }

}

function filtroFamilias(input, id) {

    if (input.value == '1') {
        // Ocultamos
        $(input).val('0');
        $('.Padre_' + id).val('0');
        $('.Padre_' + id).prop('checked', false);
        var x = document.getElementsByClassName('Familia_' + id);
        for (var i = 0; i < x.length; i++) {
            x[i].style.display = "none";
        }

        var y = document.getElementsByClassName('FamiliaPadre_' + id);

        for (var i = 0; i < y.length; i++) {
            y[i].style.display = "none";
        }

    } else {
        // Mostramos
        $(input).val('1');
        $('.Padre_' + id).val('1');

        var x = document.getElementsByClassName('Familia_' + id);
        $('.Padre_' + id).prop('checked', true);
        for (var i = 0; i < x.length; i++) {
            x[i].removeAttribute("style");
        }
        var y = document.getElementsByClassName('FamiliaPadre_' + id);
        for (var i = 0; i < y.length; i++) {
            y[i].removeAttribute("style");
        }
    }
}

function SeleccionarTodos() {
    var checkGeneral = $("#chekArticuloAll").val();
    if (checkGeneral == 0) {
        $(".table tbody input").prop("checked", true);
        $("#chekArticuloAll").val("1");
    } else {
        $(".table tbody input").prop("checked", false);
        $("#chekArticuloAll").val("0");
    }
}

function imprimirSeleccion(id) {
    if ($('.table tbody input').is(':checked') && $('.table tbody input').not([style = "display:none"])) {
        var idProductos = TfObtenerCheck('chekArticulo');
        var parametros = {
            "pulsado": 'imprimirListadoProductos',
            "productos": idProductos,
            "idProveedor": id
        };
        $.ajax({
            data: parametros,
            url: './../tareas.php',
            type: 'post',
            beforeSend: function () {
                console.log('*********  Imprimir listado de productos  **************');
            },
            success: function (response) {
                console.log('Respuesta de imprimir listado de productos ');
                var resultado = $.parseJSON(response);
                console.log(resultado);
                window.open(resultado);

            }
        });
    } else {
        alert("No has seleccionado ningún articulo");
    }
}
function obtenerEstadoProductoWeb(ids_productos,id_tiendaWeb){
    // Objetivo es obtener el estado de los productos que enviemos a la web.
    // @ Parametros:
    //      ids_productos = (array) ids de la los productos de tpv.
    //      id_web = (int) con el id de la tienda web.
	var parametros = {
		"pulsado"       : 'obtenerEstadoProductoWeb',
		"ids_productos" : ids_productos,
        "id_tiendaWeb"  : id_tiendaWeb
	};
	$.ajax({
		data       : parametros,
		url        : '../tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Obteniendo Estado de productos de la web  ****************');
		},
		success    :  function (response) {
			console.log('Respuesta de Obtener Estado de productos de la web');
			var resultado =  $.parseJSON(response);
			resultado.forEach(function(producto) {
                // Los estado 0 son sin publicar.
                if (producto.estado === "1"){
                    $("#idProducto_estadoWeb_"+producto.idArticulo).removeClass( "icono_web despublicado" );
                    //~ console.log(producto.idArticulo);
                }
                if (producto.estado === "Error"){
                    $("#idProducto_estadoWeb_"+producto.idArticulo).removeClass('icono_web despublicado')
                    .addClass('icono_web error_estadoWeb') ;       
                }
            });
		}
	});
}