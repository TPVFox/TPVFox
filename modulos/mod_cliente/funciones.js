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



function controladorAcciones(caja,accion){
	// @ Objetivo es obtener datos si fuera necesario y ejecutar accion despues de pulsar una tecla.
	//  Es Controlador de acciones a pulsar una tecla que llamamos desde teclado.js
	// @ Parametros:
	//  	caja -> Objeto que aparte de los datos que le ponemos en variables globales de cada input
	//				tiene funciones que podemos necesitar como:
	//						darValor -> donde obtiene el valor input
	
	switch(accion) {
		
		
		case 'buscarProducto':
			// Esta funcion necesita el valor.
			console.log('Entro en acciones buscar Productos');
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
			 console.log(caja);
			 var parametros = [];
			 parametros[0]= caja.name_cja;
			 parametros[1]= caja.darValor();
			 console.log(parametros[1]);

			 
			 leerArticulo(cliente.idClientes, parametros, callback);
		break;
		
		case 'Ayuda':
			console.log('Ayuda');
		break;
		
		default :
			console.log ( 'Accion no encontrada '+ accion);
	} 
}

function leerArticulo(idcliente, parametros, callback) {
	 var parametro= { caja: parametros[0], valor: parametros[1]} 
	
	
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
