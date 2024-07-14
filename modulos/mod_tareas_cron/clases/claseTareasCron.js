
class TareasCron {

    constructor() {
        this.inicializarEventos();
    }

    inicializarEventos() {
        var that = this;


        // $('#contactoModal').draggable({
        //     handle: ".modal-header"
        // });

        // $("#contactoModal").on('shown.bs.modal', function () {
        //     $("#inputdescripcion").focus();
        // });


        $(".btn-ejecutar-cron").off('click').on('click', function (event) {
            event.stopPropagation();
            event.preventDefault();
                
            var boton = $(this);
            var tareaid = boton.data('tareaid');
            var url = boton.data('url');

            var parametros = {
                tareaid : tareaid
            }

            $.ajax({
                data: parametros,
                url: 'Ejecutar.php',
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

        });

    }



}
