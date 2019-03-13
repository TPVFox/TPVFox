/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogoproductos - Funciones sincronizar.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero - SolucionesVigo
 * @Descripcion	Javascript necesarios para modulo tpv.
 * 
 * */
function guardarCierreCaja(){
	alert("guardar");
	//Objetivo 
	//enviar datos del cierre de caja
	//para guardar en cierres
	//Ccierre es global
	
	console.log('longitud Ccierre: '+Ccierre.length);
	
	var parametros = {
	"datos_cierre" 	: Ccierre,
	"pulsado" 	: 'insertarCierre'
			};
	$.ajax({
		data:  parametros,
		url:   'tareas.php',
		type:  'post',
		beforeSend: function () {
				console.log('enviando datos para cierre');
		},
		success:  function (response) {
			console.log('guardar cierre response js');
			var resultado =  $.parseJSON(response);
			console.log('recibiendo datos id '+resultado);
			
			//si hay error nos mostrara un mensaje, sino es que todo va bien.
			//if (typeof(resultado['error']) === 'undefined') { //mejorar, no vale ultima tabla falla y no me entero
			//~ var tabla1 = resultado['insertarCierre'];
			//~ var tabla2 = resultado['update_estado'];
			//~ var tabla3 = resultado['insertarIvas']['insertar_ivas_cierre'];
			//~ var tabla4 = resultado['insertarUsuarios']['insertar_FpagoCierres'];
			//~ var tabla5 = resultado['insertarUsuarios']['insertarTickets_usuarios'];
			
			//~ if ((tabla1 === 'Correcto') && (tabla2 === 'Correcto') &&  (tabla3 === 'Correcto') ){
				//~ if ( (tabla4 === 'Correcto') &&  (tabla5 === 'Correcto')){
				//~ console.log('Inserte de cierres correcto.');
				
				//~ } else{
					//~ console.log('resultado '+resultado['sql']);
					//~ console.log('ERROR en alguna insercion de Cierres.'+response);
				//~ }
				
			//~ } else {				
				//~ console.log('ERROR en insercion '+response);
			//~ }
            document.location.href='ListaCierres.php';

			
		}
	});
	
	
	
}


function metodoClick(pulsado){
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'VerCierre':
			console.log('ver cierre caja ');
		//	console.log('id'+id);
			var id	=	VerIdSeleccionado ();
			if (checkID.length >1 || checkID.length === 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
			
			window.location.href = './VistaCierre.php?id='+checkID[0];
			
		break;

        case 'VerTicket':
			console.log('Entro en Ver Ticket Cobrado');
			// Cargamos variable global ar checkID = [];
			//Funcion global en jquery
			VerIdSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
				
			window.location.href = './ticketCerrado.php?id='+checkID[0];
        break;

        case 'EliminarCierre':
			// Cargamos variable global ar checkID = [];
			//Funcion global en jquery
			VerIdSeleccionado ();
            var error = 0;
            if (checkID.length >1 || checkID.length=== 0) {
				error= 1;
                alert ('Debes seleccionar el ultimo cierre');
			} else {
                // Pongo id seleccionado en variable global
                BorrarCierre(checkID[0]);
            }
        break;
		
	}
} 

function after_constructor(padre_caja,event){
	// @ Objetivo:
	// Ejecuta procesos antes construir el obj. caja.
	// Traemos 
	//		(objeto) padre_caja -> Que es objeto el padre del objeto que vamos a crear 
	//		(objeto) event -> Es la accion que hizo, que trae todos los datos input,button , check.
	if (padre_caja.id_input.indexOf('N_') >-1){
		padre_caja.id_input = event.originalTarget.id;
	}
	
	return padre_caja;
}


function before_constructor(caja){
	// @ Objetivo :
	//  Ejecutar procesos para obtener datos despues del construtor de caja.
	//  Estos procesos los indicamos en parametro before_constructor, si hay
	if (caja.id_input ==='cajaBusqueda'){
		caja.parametros.dedonde = 'popup';
		if (caja.name_cja ==='Codbarras'){
			caja.parametros.campo = cajaCodBarras.parametros.campo;
		}
		if (caja.name_cja ==='Referencia'){
			caja.parametros.campo = cajaReferencia.parametros.campo;
		}
		if (caja.name_cja ==='Descripcion'){
			caja.parametros.campo = cajaDescripcion.parametros.campo;
		}
	}
	
	if (caja.id_input.indexOf('N_') >-1){
		caja.fila = caja.id_input.slice(2);
	}
	
	if (caja.id_input.indexOf('Unidad_Fila') >-1){
		caja.parametros.item_max = productos.length;
		caja.fila = caja.id_input.slice(12);
	}
	
	return caja;	
}

function controladorAcciones(caja,accion){
	// @ Objetivo es obtener datos si fuera necesario y ejecutar accion despues de pulsar una tecla.
	//  Es Controlador de acciones a pulsar una tecla que llamamos desde teclado.js
	// @ Parametros:
	//  	caja -> Objeto que aparte de los datos que le ponemos en variables globales de cada input
	//				tiene funciones que podemos necesitar como:
	//						darValor -> donde obtiene el valor input
	switch(accion) {
		case 'buscarClientes':
			// Esta funcion necesita el valor.
			buscarClientes(caja.darParametro('dedonde'),caja.darValor());
			break;
		
		case 'mover_down':
			// Controlamos si numero fila es correcto.
			if ( isNaN(caja.fila) === false){
				var nueva_fila = parseInt(caja.fila)+1;
			} else {
				// quiere decir que no tiene valor.
				var nueva_fila = 0;
			}
			mover_down(nueva_fila,caja.darParametro('prefijo'));
			break;
		case 'mover_up':
			if ( isNaN(caja.fila) === false){
				var nueva_fila = parseInt(caja.fila)-1;
			} else {
				// quiere decir que no tiene valor.
				var nueva_fila = 0;
			}
			mover_up(nueva_fila,caja.darParametro('prefijo'));
			break;
		
		default :
			console.log ( 'Accion no encontrada '+ accion);
	} 
}



// =========================  FUNCIONES COMUNES EN MODULOS TPV Y CIERRES ===================== //
function buscarClientes(deDonde,valor=''){
	// @ Objetivo:
	// 	Abrir modal con lista clientes, que permitar buscar en caja modal.
	// 	Ejecutamos Ajax para obtener el html que vamos mostrar.
	// @ parametros :
	//	valor -> Sería el valor caja del propio modal
	console.log('FUNCION buscarClientes JS-AJAX');
	console.log('deDonde'+deDonde);
	var parametros = {
		"pulsado"    : 'buscarClientes',
		"busqueda" : valor,
		"dedonde"  : deDonde
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en buscar clientes JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de buscar clientes');
			var resultado =  $.parseJSON(response); 
			var HtmlClientes=resultado.html;   //$resultado['html'] de montaje html
			var encontrados = resultado.encontrados;
			var titulo = 'Listado clientes ';
			
			abrirModal(titulo,HtmlClientes);
			// Asignamos focus
			if (encontrados >0 ){
				// Enfocamos el primer item.
				mover_down(0);
				$('#N_0').focus();
			}else {
				// No hay datos focus a caja buscar cliente.
				$('#cajaBusquedacliente').focus();
			}
		}
	});
}


function abrirModal(titulo,tabla){
	// Recibimos titulo -> String.( podemos cambiarlos cuando queramos)
	// datos -> Puede ser un array o puede ser vacio
	console.log(' ========  ABRIMOS MODAL ==============');
	$('.modal-body > p').html(tabla);
	$('.modal-title').html(titulo);
	$('#busquedaModal').modal('show');
	//Se lanza este evento cuando se ha hecho visible el modal al usuario (se espera que concluyan las transiciones de CSS).
	$('#busquedaModal').on('shown.bs.modal', function() {
	$('#cajaBusquedacliente').focus(); //foco en input caja busqueda del cliente
	});

}


function cerrarModalClientes(id,nombre,dedonde=''){
	// @ parametros recibidos.
	// 	id -> Del cliente
	//  nombre ->  Nombre cliente
	// 	dedonde -> 	1 (viene ticket cerrados)
	//				2 (viene ticket cobrados)
	// mostrarlos en tpv
	
	//cerrar modal busqueda
	$('#busquedaModal').modal('hide');
	
	//agregar datos funcion js
	$('#id_cliente').val(id);

	
	$('#Cliente').val(nombre);
	
}

function controladorAcciones(caja,accion){
	// Controlador de acciones a realizar de teclado.js
	switch(accion) {
		case 'buscarClientes':
			// Esta funcion necesita el valor.
			buscarClientes(caja.darParametro('dedonde'),caja.darValor());
			break;
		case 'mover_down':
			mover_down(parseInt(caja.fila)+1);
			break;
		case 'mover_up':
			mover_down(parseInt(caja.fila)-1);
			break;
	} 
}
function BorrarCierre(idseleccionado){
    // Borramos el ultimo cierre si es el seleccionado

    var opcion = confirm("Vas borrar eliminar el ultimo cierre ?");
    if (opcion == true) {
            var parametros = {
            "pulsado"       : 'BorrarCierre',
            "idSeleccionado": idseleccionado
        };
        $.ajax({
            data       : parametros,
            url        : 'tareas.php',
            type       : 'post',
            beforeSend : function () {
                console.log('******** Estoy obteniendo el ultimo id Cierre ****************');
            },
            success    :  function (response) {
                var resultado =  $.parseJSON(response); 
                console.log(resultado);
                // Aqui deberíamos analizar el resultado y crear un mensaje antes de redireccionar.
                document.location.href='ListaCierres.php';

            }
        });
	} 





    

}



// ===================  FUNCIONES DE PINTAR BONITO y MOVIMIENTOS =========================

function mover_down(fila){
	sobreFilaCraton(fila);
	setTimeout(function() {   //pongo un tiempo de focus ya que sino no funciona correctamente
		$('#N_'+fila).focus(); 
	}, 50); 
	
}

function mover_up(fila){
	sobreFilaCraton(fila);
	setTimeout(function() {   //pongo un tiempo de focus ya que sino no funciona correctamente
		$('#N_'+fila).focus(); 
	}, 50); 
	
}
