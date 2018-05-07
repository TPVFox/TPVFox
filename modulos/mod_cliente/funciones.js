/* 
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

// variable que es Funcion de respuesta 
var callback = function (respuesta) {
        var obj = JSON.parse(respuesta);
        var response = obj.datos;
        var idCliente = $('#id_cliente').val();
        console.log(obj);
        if (response.length == 1) {
			// Si hay respuesta mostramos caja de entrada precios.
			$('#formulario').removeAttr( 'style' );
            response = response[0];
            $('#inputIdArticulo').val(response['idArticulo']);
            $('#inputDescripcion').val(response['descripcion']);
            $('#inputPrecioSin').val(parseFloat(response['pvpSiva']).toFixed(2));
            $('#inputIVA').val(response['ivaArticulo']);
            $('#inputPrecioCon').val(parseFloat(response['pvpCiva']).toFixed(2));
            $('#idcliente').val(idCliente);
            $('#formulario').show();
			$('#inputPrecioSin').select();
        } else {
            var titulo= 'Buscar producto por ';
            var contenido = obj.html
            console.log(contenido)
            abrirModal(titulo,contenido);
			focusAlLanzarModal('cajaBusqueda');
        }
    };




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
    

    switch (accion) {
        case 'buscarProducto':
            // Esta funcion necesita el valor.  
            if (caja.darValor()!==''){           
				leerArticulo({idcliente: cliente.idClientes, caja: caja.name_cja, valor: caja.darValor()}, callback);
				console.log('Volvi de leer');
			} else {
				ponerFocusCajasEntradas(caja.name_cja);
			}
            break;

        case 'Ayuda':
            console.log('Ayuda');
            break;
		
		case 'saltar_preciosCon':
			$('#inputPrecioCon').select();
			console.log(caja.id_input);
			recalcularPvp(caja.id_input);
			break;
		
		case 'grabarArticulo':
			recalcularPvp(caja.id_input);
			grabarArticulo(caja);
			$('#cajaidArticulo').focus();
			break;
		
		case 'cancelarAnhadir':
			console.log('vamos cancelar');
			cancelarAnhadir();
			break;
		
		case 'modificarArticulo':
			console.log(caja);
			leerArticulo({idcliente: cliente.idclientes, caja: 'idArticulo', valor: caja.idArticulo}, callback);
			break;
		
		case 'cancelarArticulo':
			console.log(caja);
			borrarArticulo(caja.idArticulo);
			break;
		
        default :
            console.log('Accion no encontrada ' + accion);
    }
}

function after_constructor(padre_caja,event){
	// @ Objetivo:
	// Ejecuta procesos ANTES ( mi ingles-- :-) de construir el obj. caja.
	// Traemos 
	//		(objeto) padre_caja -> Que es objeto el padre del objeto que vamos a crear 
	//		(objeto) event -> Es la accion que hizo, que trae todos los datos input,button , check.

	if (padre_caja.id_input.indexOf('btn_modificar_') >-1){
		// Ponemos como id realmente el de evento no el caja xml.
		padre_caja.id_input = event.target.id;
	}
	if (padre_caja.id_input.indexOf('btn_cancelar_') >-1){
		// Ponemos como id realmente el de evento no el caja xml.
		padre_caja.id_input = event.target.id;
	}
	
	return padre_caja;
}
function before_constructor(caja){
	if (caja.id_input.indexOf('btn_modificar_') >-1){
		caja.idArticulo = caja.id_input.slice(14);
	}
	if (caja.id_input.indexOf('btn_cancelar_') >-1){
		caja.idArticulo = caja.id_input.slice(13);
	}
	return caja;
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

function borrarArticulo(idarticulo) {
    $.ajax({
        data: {pulsado: 'Borrar_producto_tarifa_cliente',idcliente: cliente.idClientes,
            idarticulo: idarticulo},
        url: 'tareas.php',
        type: 'post',
        beforeSend : function () {
		console.log('*********  Eliminando producto de tarifa del cliente **************');
		},
		success    :  function (response) {
				console.log('Respuesta despues de eliminar producto de tarifa.');
				var resultado = $.parseJSON(response);
				//~ var resultado = response;
				if (resultado.error === "0" ){
					//quiere decir que elimino correctamente
	                window.location.href = './tarifaCliente.php?id=' + cliente.idClientes;
				} else {
					alert( ' Hubo un error al eliminarlo ');
					console.log(resultado);

				}
			}
    });
}




function buscarArticulos() {
    var campo = $('#campoabuscar').val();
    var valor = $('#cajaBusqueda').val();

//    resetpagina = resetpagina || 0;
//    if (resetpagina) {
//        $('#paginabuscar').val(1);
//    }

    leerArticulo({idcliente: cliente.idClientes
        , caja: campo
        , usarlike: 'si'
        , valor: valor
        , pagina: $('#paginabuscar').val()}, function (respuesta) {
        var obj = JSON.parse(respuesta);
        var datos = obj.datos;
        var tabla = obj.html;

        $('#paginabuscar').val(obj.pagina);

        if (tabla) {
            $('.modal-body > p').html(tabla);
            $('.articulos-page-selection-bottom, .articulos-page-selection-top').bootpag({total: obj.totalPaginas, page: obj.pagina});

            // click en columna 1 de la tabla con el idArticulo
            $(".btn-busca-art").button().on("click", function (event) {
                event.stopPropagation();
                event.preventDefault();

                var idarticulo = $(event.target).data('id');

                var callback = function (respuesta) {
                    var obj = JSON.parse(respuesta);
                    var response = obj.datos;
                    var idCliente = $('#id_cliente').val();
                    if (response.length == 1) {
                        response = response[0];
                        $('#busquedaModal').modal('hide');
                        $('#inputIdArticulo').val(response['idArticulo']);
                        $('#inputDescripcion').val(response['descripcion']);
                        $('#inputPrecioSin').val(parseFloat(response['pvpSiva']).toFixed(2));
                        $('#inputIVA').val(response['ivaArticulo']);
                        $('#inputPrecioCon').val(parseFloat(response['pvpCiva']).toFixed(2));
                        $('#idcliente').val(idCliente);
                        $('#formulario').show();
                        $('#inputPrecioSin').focus();
                    }
                };

                leerArticulo({idcliente: cliente.idClientes
                    , caja: 'idArticulo'
                    , valor: idarticulo}, callback);

            });

        }
    }
    );

}


function borrarInputsFiltro() {
    $('#cajaidArticulo').val('');
    $('#cajaReferencia').val('');
    $('#cajaCodbarras').val('');
    $('#cajaDescripcion').val('');
}

function grabarArticulo(event){
		console.log('Grabar producto');
        var parametros = {
			'pulsado' : 'Grabar_tarifa_producto_cliente',
            idarticulo: $('#inputIdArticulo').val(),
            pvpSiva: parseFloat($('#inputPrecioSin').val()).toFixed(2),
            pvpCiva: parseFloat($('#inputPrecioCon').val()).toFixed(2),
            idcliente: $('#id_cliente').val()
        };
        console.log(parametros);

        $.ajax({
            data: parametros,
            url: './tareas.php',
            type: 'post',
            success: function (response) {
                var idcliente = $('#id_cliente').val();
                window.location.href = './tarifaCliente.php?id=' + idcliente;
            },
            // No se realmente cual es el funcionamiento de esto...
            error: function (request, textStatus, error) {
                console.log(textStatus);
            }
        });
	
	
}

function recalcularPvp(dedonde){
	// @ Objetivo:
	// Recalcular precio s/iva y precio c/iva segun los datos que tengan las cjas y de donde venga.
	// @ Parametros:
	//  dedonde = (string) id_input.
	// Obtenemos iva ( deberías ser funcion)
	var iva = $('#inputIVA').val();
	console.log('De donde:'+dedonde);
	if (dedonde === 'inputPrecioSin'){
		var precioSiva = parseFloat($('#inputPrecioSin').val(),2);
		var precioCiva = precioSiva+((precioSiva*iva)/100);
		console.log(precioCiva.toFixed(2));
		$('#inputPrecioCon').val(precioCiva.toFixed(2));
		// Ahora destacamos los input que cambiamos.		
		destacarCambioCaja('inputPrecioCon');
	} else {
		var precioCiva = parseFloat($('#inputPrecioCon').val(),2);
		var precioSiva = precioCiva -((precioCiva*iva)/100);
		console.log(precioSiva.toFixed(2));
		$('#inputPrecioSin').val(precioSiva.toFixed(2));

		// Ahora destacamos los input que cambiamos		
		destacarCambioCaja('inputPrecioSin');
	}

}

function destacarCambioCaja(idcaja){
	$("#"+idcaja).css("outline-style","solid");
	$("#"+idcaja).css("outline-color","coral");
	$("#"+idcaja).animate({
			"opacity": "0.3"
		 },2000);
	t = setTimeout(volverMostrar,2000,idcaja);
	
}


function volverMostrar(idcaja){
	console.log('Entro volver mostrar');
	$("#"+idcaja).animate({
			"opacity": "1"
		 },1000);
	$("#"+idcaja).css("outline-color","transparent")
}



function ponerFocusCajasEntradas(caja_name){
	// @ Objetivo:
	// Si el valor de la caja esta vacio poner focus a la caja siguiente de busqueda
	// @ Parametro:
	// 		caja_name : (string ) con nombre de la caja que estamos actualmente.
	switch (caja_name) {
		case 'idArticulo':
			$('#cajaReferencia').focus();
		break;
		
		case 'Referencia':
			$('#cajaCodbarras').focus();
		break;
		
		case 'Codbarras':
			$('#cajaDescripcion').focus();
		break;
		
		case 'Descripcion':
			$('#cajaidArticulo').focus();
		break;
	
	}
	
		
	console.log(caja_name);
}

function cancelarAnhadir(){
	$('#inputIdArticulo').val('');
    $('#inputPrecioSin').val('');
    $('#inputPrecioCon').val('');
    $('#formulario').hide();
	$('#idArticulo').focus();
	
}
