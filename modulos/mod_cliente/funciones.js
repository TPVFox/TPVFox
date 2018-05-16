function metodoClick(pulsado) {
    console.log("Inicimos switch de control pulsar");
    switch (pulsado) {
        case 'VerCliente':
            console.log('Entro en VerCliente');
            if (this.validarChecks()) {
                window.location.href = './cliente.php?id=' + checkID[0];
            }
            break;

        case 'AgregarCliente':
            console.log('entro en agregarCliente');
            window.location.href = './cliente.php';
            break;

        case 'TarificarCliente':
            console.log('entro en tarificarCliente');
            if (this.validarChecks()) {
                window.location.href = './tarifaCliente.php?id=' + checkID[0];
            }
            break;
    }
}

function validarChecks() {
    // Cargamos variable global ar checkID = [];
    //Funcion global en jquery
    VerIdSeleccionado();
    if (checkID.length > 1 || checkID.length === 0) {
        alert('¿Cuantos items tienes seleccionados? \n Sólo puedes tener uno seleccionado');
        return false;
    }
    return true;
}

function controladorAcciones(caja, accion) {
    // @ Objetivo es obtener datos si fuera necesario y ejecutar accion despues de pulsar una tecla.
    //  Es Controlador de acciones a pulsar una tecla que llamamos desde teclado.js
    // @ Parametros:
    //  	caja -> Objeto que aparte de los datos que le ponemos en variables globales de cada input
    //				tiene funciones que podemos necesitar como:
    //						darValor -> donde obtiene el valor input
    var callback = function (respuesta) {
        var obj = JSON.parse(respuesta);
        var response = obj.datos;
        var idCliente = $('#id_cliente').val();
        if (response.length == 1) {
            response = response[0];
            $('#inputIdArticulo').val(response['idArticulo']);
            $('#inputDescripcion').val(response['descripcion']);
            $('#inputPrecioSin').val(parseFloat(response['pvpSiva']).toFixed(2));
            $('#inputIVA').val(response['ivaArticulo']);
            $('#inputPrecioCon').val(parseFloat(response['pvpCiva']).toFixed(2));
            $('#idcliente').val(idCliente);
            $('#formulario').show();
            $('#inputPrecioSin').focus();
        } else {
            $('#busquedaModal').on('shown.bs.modal', function () {
                $('#cajaBusqueda').focus();
                $('#buscarArticulos').click();
            });
            $('#busquedaModal').modal('show');
        }
    };

    switch (accion) {
        case 'buscarProducto':
            // Esta funcion necesita el valor.            
            leerArticulo({idcliente: cliente.idClientes, caja: caja.name_cja, valor: caja.darValor()}, callback);
            break;

        case 'Ayuda':
            console.log('Ayuda');
            break;

        default :
            console.log('Accion no encontrada ' + accion);
    }
}

function leerArticulo(parametros, callback) {
    $('#campoabuscar').val(parametros.caja);
    $('#cajaBusqueda').val(parametros.valor);
//    $('#inputPaginaModal').val(1);
    borrarInputsFiltro();

    $.ajax({
        data: parametros,
        url: './leerArticulo.php',
        type: 'post',
        success: callback,
        error: function (request, textStatus, error) {
            console.log(textStatus);
        }
    });
}

function borrarArticulo(idcliente, idarticulo, callback) {
    $.ajax({
        data: {idcliente: idcliente,
            idarticulo: idarticulo},
        url: './borrarArticuloCliente.php',
        type: 'post',
        success: callback,
        error: function (request, textStatus, error) {
            console.log(textStatus);
        }
    });
}
