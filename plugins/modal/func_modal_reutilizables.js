// Funciones necesarias para plugin de modal.
// Pero con parámetros para no tener que trabajar n veces

function abrirDivModal(iddivmodal, titulo) {
    // @ Objetivo :
    // Abril modal con texto buscado y con titulo que le indiquemos.
    console.log('Estamos en abrir modal de func_modal_reutilizable');
    if (titulo !== '') {
        $('.modal-title').html(titulo);
    }
    $('#' + iddivmodal).modal('show');
}

function cerrarDivPopUp(iddivmodal) {
    // @ Objetivo :
    // Cerrar modal ( popUp ), apuntar focus según pantalla cierre.
    //cerrar modal busqueda
    $('#' + iddivmodal).modal('hide');
}

function focusAlLanzarModal(iddivmodal, idCaja) {
    // @Objetivo:
    // Poner focus cuando esta visible el evento modal.
    // Se espera que concluyan las transiciones de CSS

    $('#' + iddivmodal).on('shown.bs.modal', function () {
        // Pongo focus a cada caja pero no se muy bien, porque no funciona si pongo el focus en la accion realizada.
        $('#' + idCaja).focus(); //foco en input caja 

    });
}

function SelectAlLanzarModal(iddivmodal, idCaja) {
    // @Objetivo:
    // Poner select cuando esta visible el evento modal.
    // Se espera que concluyan las transiciones de CSS

    $('#' + iddivmodal).on('shown.bs.modal', function () {
        $('#' + idCaja).select(); //foco en input caja 

    });
}

// -- CSS para fila de tabla... 


