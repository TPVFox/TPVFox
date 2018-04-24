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