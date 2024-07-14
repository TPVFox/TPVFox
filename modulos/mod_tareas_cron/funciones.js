

function botonEjecutarCron(boton) {

    var tareaid = boton.data('tareaid');

    var parametros = {
        tareaid: tareaid
    }

    $.ajax({
        data: parametros,
        url: 'tareas.php',
        type: 'get',
        success: function (response) {
            if (response.data) {
                console.log('exito->'.response.data);
                //$('#contactoModal').modal('hide');
            }
        },
        error: function (request, textStatus, error) {
            var errores = request.responseJSON.errors;
            if (!errores) {
                errores = [];
                errores[0] = "AJAX error: " + textStatus + ' : ' + error;
            }
            $.each(errores, function (indice, valor) {
                showNotification('bg-deep-orange', valor, 'top', 'right', '', '', '3000', false);
            });
        }
    });

}

function eliminarTarea(objeto){
    var boton = $(objeto);
    let tareaid = boton.data('tareaid');
    let tareanombre = boton.data('tarea-nombre');
    let url = boton.data('url');

    if(confirm('Eliminar tarea '+tareaid+': '+tareanombre)){
        window.location.href = url;
    }
}

function metodoClick(pulsado, objeto) {
    // @ Objetivo:
    //  Controlas los click en listadoproductos.
    // @ parametros:
    //     adonde : a donde quiero ir o donde quiero permanecer: ListaTickets, ListaProductos.. 
    console.log("Inicimos switch de control pulsar");
    switch (pulsado) {
        case 'volver':
            var boton = $(objeto);
            window.location.href = boton.data('url');
            break;
        case 'btn-ejecutar-cron':
            // Cargamos variable global ar checkID = [];
            var boton = $(objeto);
            botonEjecutarCron(boton);
            break;
        case 'btn-eliminar':                        
            eliminarTarea(objeto);
            break;
    }
}
