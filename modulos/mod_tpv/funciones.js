/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogoproductos - Funciones sincronizar.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero - SolucionesVigo
 * @Descripcion	Javascript necesarios para modulo tpv.
 *
 * 
 * */
var pulsado = '';
var total = 0;



function cobrarF1(){
	//@Objetivo:
	// Recalcular en php los totales.( Si hay diferencia se informa)
	// Abrir modal de htmlcobrar
	if ( productos.length>0){
		var parametros = {
				"pulsado" 	: 'cobrar',
				"total" : total,
				"productos"	 	: productos,
				"configuracion"	: configuracion
				//"dedonde" : dedonde
		};
		$.ajax({ data:  parametros,
			url:   'tareas.php',
			type:  'post',
			beforeSend: function () {
				console.log('Iniciamos tarea de cobrar');
			},
			success:  function (response) {
				console.log('Respuesta ajax - CobrarF1 ');
				var resultado =  $.parseJSON(response);
				//HtmlCobrar = resultado;
				//busqueda = resultado.cobrar;
				
				var HtmlCobrar = resultado.html;  //$resultado['html'] de montaje html
				var titulo = 'COBRAR ';
				abrirModal(titulo,HtmlCobrar);
				//alert('cobrar');
				
			}
		});
	}else {
		alert ('No hay productos no podemos cobrar');
	}
}



function resetCampo(campo){
	console.log('Entro en resetCampo '+campo);
	document.getElementById(campo).value='';
	return;
}


function buscarProductos(id_input,campo,busqueda,dedonde){
	// @ Objetivo:
	//  Buscar productos donde el dato exista en el campo que se busca...
	// @ Parametros:
	// 		nombreinput = id caja de donde viene
	//		campo =  campo a buscar
	// 		busqueda = valor del input que corresponde.
	// 		dedonde  = [tpv] o [popup] 
	// @ Respuesta:
	//  1.- Un producto unico.
	//  2.- Un listado de productos.
	//  3.- O nada un error.
	
	console.log('FUNCION buscarProductos JS- Para buscar con el campo');
	console.log('De donde:'+dedonde);

	var parametros = {
		"pulsado"    : 'buscarProductos',
		"cajaInput"	 : id_input,
		"valorCampo" : busqueda,
		"campo"      : campo,
		"dedonde"    : dedonde
	};
	console.log('Parametros:');
	console.log(parametros);


	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Envio datos para Buscar Producto  ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de FUNCION -> buscarProducto');
			var resultado =  $.parseJSON(response);
			console.log('Estado'+resultado['Estado']);
			if (resultado['Estado'] === 'Correcto') {
				var datos = [];
				datos = resultado.datos[0];
				console.log('Entro en Estado Correcto funcion buscarProducto ->datos (producto)');
				console.log(datos);
				//~ console.log('consulta '+resultado.sql);
				resetCampo(id_input);
				agregarFila(datos);
				
			} else {
				// Se ejecuta tanto sea un listado como un error.
				console.log('=== Entro en Estado Listado de funcion buscarProducto =====');
				var busqueda = resultado.listado;   
				var HtmlProductos=busqueda.html;   
				var titulo = 'Listado productos encontrados ';
				// Abrimos modal de productos.
				abrirModal(titulo,HtmlProductos);
				if (resultado.Nitems >0 ){
					// Quiere decir que hay resultados por eso apuntamos al primero
					// focus a primer producto.
						var d_focus = 'N_0';
						ponerFocus(d_focus);
				} else {
					// No hay resultado pero apuntamos a caj
					ponerFocus(id_input);
				}
			}
		// Al no poner return , esto se va ejecutar siempre.
		//~ document.getElementById(id_input).value='';
		}
		

	});
}

function agregarFila(datos,campo=''){
	// @ Objetivo
	// 	Añadir producto a productos (JS) y ademas obtener htmlLinea para mostrar
	// Voy a crear objeto producto nuevo..
	// @ parametro 
	//  campo ->  String que indica al campo donde enfocar.
	console.log('Voy agregar producto');
	console.log(datos);
	productos.push(new ObjProducto(datos));
	var num_item = productos.length -1; // Obtenemos cual es el ultimo ( recuerda que empieza contado 0)
	// Ahora por Ajax montamos el html fila.
	var parametros = {
		"pulsado"    : 'HtmlLineaTicket',
		"producto" : productos[num_item],
		"num_item"      : num_item,
		"CONF_campoPeso"    : configuracion.campo_peso
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Obteniendo html de linea ticket  ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de Obtener HTML linea de FUNCION -> agregarFila');
			
			var resultado =  $.parseJSON(response);
			var nuevafila = resultado['html'];
			//~ console.log(nuevafila);
			
			//$ signifca jQuery 
			//$("#tabla").append(nuevaFila);
			$("#tabla").prepend(nuevafila);
			if ('campo' ==='') {
				// Si no viene dato campo, por lo que focus por defectoe es Codbarras
				$('#Codbarras').focus();  
			} else {
				// Ponemos focus el campo que le indicamos en parametro campo.
				$(campo).focus();
			}
			grabarTicketsTemporal();
		}
	});

	
};
 
 
//Sera funcion que agrega o elimina linea.
function eliminarFila(num_item){
	var line;
	line = "#Row" + productos[num_item].nfila;
	// Nueva Objeto de productos.
	productos[num_item].estado= 'Eliminado';
	$(line).addClass('tachado');
	$(line + "> .eliminar").html('<a onclick="retornarFila('+num_item+');"><span class="glyphicon glyphicon-export"></span></a>');
	$("#N" +productos[num_item].nfila + "_Unidad").prop("disabled", true);
	grabarTicketsTemporal();
}

function retornarFila(num_item){
	// @Objetivo :
	// Es pasar un producto eliminado a activo.
	var line;
	line = "#Row" +productos[num_item].nfila;
	// Nueva Objeto de productos.
	productos[num_item].estado= 'Activo';
	//~ var pvp =productos[num_item].pvpconiva;

	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarFila('+num_item+');"><span class="glyphicon glyphicon-trash"></span></a>');
	if (productos[num_item].unidad == 0) {
		// Nueva Objeto de productos.
		//~ productos[nfila].unidad= 1;
		// Antiguo array productos.
		productos[num_item].unidad = 1;
		recalculoImporte(productos[num_item].unidad,num_item);
	}
	$("#N" + productos[num_item].nfila + "_Unidad").prop("disabled", false);
	$("#N" + productos[num_item].nfila + "_Unidad").val(productos[num_item].unidad);
	console.log(productos);
	grabarTicketsTemporal();
}
//~ //fin funcion que agrega o elimina linea
//************************************************************


function escribirProductoSeleccionado(campo,cref,cdetalle,ctipoIva,ccodebar,npconiva,id){
	// @ Objetivo:
	//   Realizamos cuando venimos popUp de Productos.
	// @ Parametros:
	// 	 Caja -> Indica la caja queremos que ponga focus
	//   datos -> Es el array que vamos enviar para añadir fila.
	console.log( '--- FUNCION escribirProductoSeleccionado  --- ');
	var datos = []
	datos['idArticulo'] 	= id;
	datos['crefTienda'] 	= cref;
	datos['articulo_name'] 	= cdetalle;
	datos['pvpCiva'] 		= npconiva;
	datos['iva'] 			= ctipoIva;
	datos['codBarras']		= ccodebar;
	agregarFila(datos);
	// Eliminamos contenido de cja destino y ponemos focus.
	resetCampo(campo);
	cerrarPopUp(campo);

	
}


function grabarTicketsTemporal(){
	// @ Objetivo
	// Grabar cabeceras y productos, amabas variables globales en tabla de ticket temporal.
	console.log('Grabamos en BD');
	var i  =0;
	console.log('Productos');
	console.log(productos);
	// Para poder mandar objectos de productos ...
	var parametros = {
		"pulsado"    	: 'grabarTickes',
		"productos"	 	: productos,//
		"idCliente"	 	: cabecera.idCliente,
		"idTienda" 	 	: cabecera.idTienda,
		"idUsuario"	 	: cabecera.idUsuario,
		"estadoTicket" 	: cabecera.estadoTicket,
		"numTicket"		: cabecera.numTicket,
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** Voy a grabar****************');
		},
		success    :  function (response) {
			console.log('Respuesta de grabar');
			//~ console.log(response);
			var resultado =  $.parseJSON(response); 
			console.log(resultado.estadoTicket);
			// Cambiamos el estado :
			cabecera.estadoTicket = resultado.estadoTicket;
			cabecera.numTicket = resultado.NumeroTicket;
			$('#EstadoTicket').html(resultado.estadoTicket);			
			$('#EstadoTicket').css('background-color','red')
			$('#EstadoTicket').css('color','white')
			$('#NTicket').html('0/'+resultado.NumeroTicket);
			
			console.log(productos.length);
				
			//objetivo cuando esta en ticket actual , 
			//en el navegador ponga ?tActual para que no afecte F5 SIN RECARGAR pagina
			if (productos.length ===1 ){ 
				history.pushState(null,'','?tActual='+resultado.NumeroTicket);
			}
			// Limpiamos los valores ivas y bases.
			$('#tipo4').html('');
			$('#tipo10').html('');
			$('#tipo21').html('');
			$('#base4').html('');
			$('#base10').html('');
			$('#base21').html('');
			$('#iva4').html('');
			$('#iva10').html('');
			$('#iva21').html('');
			$('.totalImporte').html('');
			
			// Ahora pintamos pie de ticket.
			if (resultado.total > 0 ){
				// Quiere decir que hay datos a mostrar en pie.
				total = parseFloat(resultado.total) // varible global.
				$('.totalImporte').html(total.toFixed(2));
				// Ahora tengo que pintar los ivas.
				var desgloseIvas = [];
				desgloseIvas.push(resultado.desglose);
				console.log(desgloseIvas);
				// Ahora recorremos array desglose
				desgloseIvas.forEach(function(desglose){
					console.log('Entro foreah');
					// mostramos los tipos ivas , bases y importes.
					var tipos = Object.keys(desglose);
					console.log(desglose);
					for (index in tipos){
						var tipo = tipos[index];
						$('#line'+parseInt(tipo)).css('display','');
						$('#tipo'+parseInt(tipo)).html(parseInt(tipo)+'%');
						$('#base'+parseInt(tipo)).html(desglose[tipo].base); 
						$('#iva'+parseInt(tipo)).html(desglose[tipo].iva);
					}
				});
				
			}
			
		}
	});
}

function cerrarTicket(){
	//@ Objetivo:
	// Enviar datos del ticket (cabecera y caja de cobrar)
	// para guaardar como Cobrado en tablas ticket y temporal de ticket se cambia estado a COBRADO
	 var entregado = $('#entrega').val();
	 var formaPago = $('#modoPago').val();
	 //podemos obtener el valor de la propiedad checked, true o false
	 var checkimprimir = $('input[name=checkimprimir]').prop('checked'); 
	 var ruta_impresora = configuracion['impresora'];
	 console.log(ruta_impresora);
	//parche desactivar boton aceptar, no hay impresora de tickets
	$('button[id=CobrarAceptar]').prop('disabled',true);
	// Ahora ejecutamos ajax para guardar ticket
	var parametros = {
		"pulsado"	    	: 'CerrarTicket',
		"idCliente"		 	: cabecera.idCliente,
		"idTienda" 	 		: cabecera.idTienda,
		"idUsuario"	 		: cabecera.idUsuario,
		"estadoTicket" 		: cabecera.estadoTicket,
		"numTickTemporal"	: cabecera.numTicket,
		"total"				: total,
		"entregado"			: entregado,
		"formaPago"			: formaPago,
		"checkimprimir"		: checkimprimir,  //true o false
		"ruta_impresora"	: ruta_impresora 
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** Voy guardar ticket (CERRAdo) ****************');
		},
		success    :  function (response) {
			console.log('Respuesta de Guardar ticket Cerrado');
			console.log(response);
			var resultado =  $.parseJSON(response); 
			console.log(' ********  TERMIANOS DE GRABAR TICKET CERRADO *********** ')
			// Redireccion para volver a empezar un ticket
			//~ window.location="tpv.php";
			console.log(typeof resultado.error_impresora);
			if (typeof resultado.error_impresora =='string'){
				alert( 'Impresora de ticket apagada o no es correcta configuracion , NO SE PUEDE IMPRIMIR !!');
				document.location.href='tpv.php';
			} else {
				document.location.href='tpv.php';

			}
		}
	});
	
	
	
}

// ========== SWITCH ver Tickets Cerrados cobrados e IMPRIMIR ticket ========
function metodoClick(pulsado){
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'VerTicket':
			console.log('Entro en Ver Ticket Cobrado');
			// Cargamos variable global ar checkID = [];
			//Funcion global en jquery
			VerIdSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
				
			window.location.href = './ticketCobrado.php?id='+checkID[0];
			break;
		
		case 'imprimirTicket':
		//seleccionar para imprimir ticket elegido
			console.log('entro en imprimir ticket');
			VerIdSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
				
			//window.location.href = './ticketCerrado.php?id='+checkID[0];
			alert('Ticket cerrado, opc imprimir');
			
			break;	
		case 'descontarStockWeb':
			//seleccionar para enviar stock web
			//~ VerIdSeleccionado (); // de momento solo hacemos dentro un ticket cerrado
			//~ if (checkID.length >1 || checkID.length=== 0) {
				//~ alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				//~ return
			//~ }
			alert('Ticket cerrado enviar Sctok a Web');
			
			break;			
	 }
} 






// =========================== OBJETOS  ===================================
function ObjProducto(datos)
{
    console.log('Estoy creando objeto producto');
    this.id = datos.idArticulo;
    this.cref = datos.crefTienda
    this.cdetalle = datos.articulo_name;
    this.pvpconiva = parseFloat(datos.pvpCiva).toFixed(2);
    this.ccodebar = datos.codBarras;
    this.ctipoiva = datos.iva;
    if (datos.unidad === undefined){
		this.unidad = 1; // Valor por defecto.
	} else {
		this.unidad = datos.unidad;
	}
    if (datos.estado === undefined){
		this.estado= 'Activo'; // Valor por defecto.
	} else {
		this.estado = datos.estado;
	}
    this.nfila = productos.length+1;
    this.importe = parseFloat(this.pvpconiva) * this.unidad;
}
	
// =========================  FUNCIONES COMUNES EN MODULOS TPV Y CIERRES ===================== //
function buscarClientes(pantalla,valor=''){
	// @ Objetivo:
	// 	Abrir modal con lista clientes, que permitar buscar en caja modal.
	// 	Ejecutamos Ajax para obtener el html que vamos mostrar.
	// @ parametros :
	//	valor -> Sería el valor caja del propio modal
	console.log('FUNCION buscarClientes JS-AJAX');
	var parametros = {
		"pulsado"    : 'buscarClientes',
		"busqueda" : valor,
		"dedonde"  : pantalla
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
			var encontrados = resultado.encontrados;
			var HtmlClientes=resultado.html;   //$resultado['html'] de montaje html
			var titulo = 'Listado clientes ';
			abrirModal(titulo,HtmlClientes);
			// Asignamos focus a caja buscar cliente.
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
	// @ Objetivo :
	// Abril modal con texto buscado y con titulo que le indiquemos.
	console.log('Estamos en abrir modal');
	$('.modal-body > p').html(tabla);
	$('.modal-title').html(titulo);
	$('#busquedaModal').modal('show');
	
	//Se lanza este evento cuando se ha hecho visible el modal al usuario (se espera que concluyan las transiciones de CSS).
	$('#busquedaModal').on('shown.bs.modal', function() {
		// Pongo focus a cada cja pero no se muy bien, porque no funciona si pongo el focus en la accion realizada.
		$('#entrega').select(); 	//foco en input entrega MODAL cobrar
		$('#cajaBusqueda').focus(); //foco en input caja busqueda del cliente
		$('#cajaBusquedacliente').focus(); //foco en input caja busqueda del cliente
	});
}



function escribirClienteSeleccionado(id,nombre,dedonde=''){
	// @ Objetivo:
	// 	 Escribir el nombre y id cliente en pantalla principal.
	// 	id -> Del cliente
	//  nombre ->  Nombre cliente
	// 	dedonde -> tpv
	//			   cobrados
	// mostrarlos en cajas se son las mismas para las dos.
	$('#id_cliente').val(id);
	$('#Cliente').val(nombre);
	// Cerramos modal  y le indicamos destino focus.
	cerrarPopUp(); // Destino no indicamo ya que no sabes...
	if (dedonde ='tpv'){
		// Ponemos focus por defecto.
		ponerFocus('Codbarras');
	} 
	
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
		case 'buscarProductos':
			// Esta funcion necesita el valor.
			console.log('Entro en acciones buscar Productos');
			buscarProductos(caja.name_cja,caja.darParametro('campo'),caja.darValor(),caja.darParametro('dedonde'));
			break;
		case 'recalcular_ticket':
			// recuerda que lo productos empizan 0 y las filas 1
			var nfila = parseInt(caja.fila)-1;
			// Comprobamos si cambio valor , sino no hacemos nada.
			//~ productos.[nfila].unidad = caja.darValor();
			console.log ( caja);
			productos[nfila].unidad = caja.darValor();
			recalculoImporte(productos[nfila].unidad,nfila);
			
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
			console.log( 'Accion subir 1 desde fila'+caja.fila);
			if ( isNaN(caja.fila) === false){
				var nueva_fila = parseInt(caja.fila)-1;
			} else {
				// quiere decir que no tiene valor.
				var nueva_fila = 0;
			}
			mover_up(nueva_fila,caja.darParametro('prefijo'));
			break;
		case 'saltar_Referencia':
			var dato = caja.darValor();
			if ( dato.length === 0){
				// Si esta vacio, sino permitimos saltar.
				var d_focus = 'Referencia';
				ponerFocus(d_focus);
			}
			break;
		case 'saltar_Descripcion':
			var dato = caja.darValor();
			if ( dato.length === 0){
				// Si esta vacio, sino permitimos saltar.
				var d_focus = 'Descripcion';
				ponerFocus(d_focus);
			}
			break;
		case 'saltar_CodBarras':
			var dato = caja.darValor();
			if ( dato.length === 0){
				// Si esta vacio, sino permitimos saltar.
				var d_focus = 'Codbarras';
				ponerFocus(d_focus);
			}
			break;
		case  'saltar_productos':
			if (productos.length >0){
			// Debería añadir al caja N cuantos hay
				console.log ( 'Entro en saltar a producto que hay '+ productos.length);
				ponerFocus('Unidad_Fila_'+productos.length);
			} else {
			   console.log( ' No nos movemos ya que no hay productos');
			}
			break
		case 'cobrar':
			console.log( ' Entro en accion cobrar');
			cobrarF1();
			break
			
		case 'poner_entrega':
			var cambio = parseFloat(caja.darValor()) - total;
			console.log(cambio);
			if (cambio < 0){
				$('#cambio').css('color','red');
			}else {
				$('#cambio').css('color','grey');
			}
			$('#cambio').val(cambio.toFixed(2));
			// Ponemos como focus el btn de aceptar
			ponerFocus('CobrarAceptar');
			break;
		
		case 'cerrar_ticket':
			console.log(' Entro en contralador de acciones, cerrar ticket');
			CobrarAceptar.parametros.pulsado_intro = 'Si';
			// Ahora grabamos y cerramos ticket
			cerrarTicket()
			break;
		case 'focus_entrega':
			ponerFocus('entrega');
			break;
			
		case 'focus_modoPago':
			ponerFocus('modoPago');
			break;
		
		default :
			console.log ( 'Accion no encontrada '+ accion);
	} 
}

function before_constructor(caja){
	// @ Objetivo :
	//  Ejecutar procesos para obtener datos despues del construtor de caja.
	//  Estos procesos los indicamos en parametro before_constructor, si hay
	console.log( 'Entro en before');
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
		console.log(' Entro en Before de '+caja.id_input)
		caja.fila = caja.id_input.slice(2);
		console.log(caja.fila);
	}
	
	if (caja.id_input.indexOf('Unidad_Fila') >-1){
		caja.parametros.item_max = productos.length;
		caja.fila = caja.id_input.slice(12);
	}
	
	return caja;	
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
	if (padre_caja.id_input.indexOf('Unidad_Fila') >-1){
		padre_caja.id_input = event.originalTarget.id;
	}
	
	return padre_caja;
}

// ===================  FUNCIONES DE PINTAR BONITO y MOVIMIENTOS =========================
//html onfocus 
function sobreFila(cont){
	$('#Fila_'+cont).css('background-color','lightblue');
}
//html onfocusout y onmouseout
function abandonFila(cont){
	$('#Fila_'+cont).css('background-color','white');
}

function sobreFilaCraton(cont){
	$('#Fila_'+cont).css('background-color','azure');
}

function mover_down(fila,prefijo){
	sobreFilaCraton(fila);
	var d_focus = prefijo+fila;
	ponerFocus(d_focus);
	
}

function mover_up(fila,prefijo){
	sobreFilaCraton(fila);
	var d_focus = prefijo+fila;
	ponerFocus(d_focus);
}
function cerrarPopUp(destino_focus=''){
	// @ Objetivo :
	// Cerrar modal ( popUp ), apuntar focus según pantalla cierre.
	//cerrar modal busqueda
	$('#busquedaModal').modal('hide');
	if (destino_focus !== ''){
		ponerFocus(destino_focus);
	}
	
}

function ponerFocus (destino_focus){
	// @ Objetivo:
	// 	Poner focus a donde nos indique el parametro, que debe ser id queremos apuntar.
	console.log('Entro en enviar focus de :'+destino_focus);
	setTimeout(function() {   //pongo un tiempo de focus ya que sino no funciona correctamente
		jQuery('#'+destino_focus.toString()).select(); 
	}, 50); 

}

function recalculoImporte(cantidad,num_item){
	// @ Objetivo:
	// Recalcular el importe de la fila, si la cantidad cambia.
	// @ Parametros:
	//	cantidad -> Valor ( numerico) de input unidades.
	//	num_item -> El numero que indica el producto que modificamos.
	console.log('Estoy en recalculoImporte');
	//~ console.log('cantidad:'+cantidad);
	if (productos[num_item].unidad == 0 && cantidad != 0) {
		retornarFila(num_item);
	} else if (cantidad == 0 ) {
		eliminarFila(num_item);
	}
	console.log('Valor de cantidad'+cantidad);
	productos[num_item].unidad = cantidad;
	//alert('DentroReclaculo:'+producto[nfila]['NPCONIVA']);
	var importe = cantidad*productos[num_item].pvpconiva;
	var id = '#N'+productos[num_item].nfila+'_Importe';
	//alert('recalcular'+id);
	importe = importe.toFixed(2);
	$(id).html(importe);
	grabarTicketsTemporal();
}

function PrepararEnviarStockWeb(){
	// @ Objetivo:
	//  Enviar URl de servidor productos para cambiar stock
	//  Esta es funcion provisional, ya que deberíamos saber con configuracion a que web queremos cambiar el stock
	//  o con una seleccion de webs... 
	//  Inicializamos Variables:
	var tienda_web = [];	
	console.log('PREPARAMOS DATOS PARA ENVIAR');
	var parametros = {
		"pulsado" : 'ObtenerRefTiendaWeb',
		"productos"    : productos,
		"web"		 : '2'
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			// Debería tener un sitio para meter barra proceso, ya que puede tardar..
			// Este proceso no,pero el siguiente si..
			// De momento utilizo alert
			console.log('*********  Preparando datos para enviar... ****************');
		},
		success    :  function (response) {
			console.log('Respuesta de envio de datos');
			var resultado =  $.parseJSON(response); 
			// Ponemos datos de tienda_web en variable
			tienda_web = resultado.tienda;
			console.log(response);
			// Recuerda que el repción de los datos no es el mismo que envio, por debemos asociar key con valor.
			if (typeof resultado.idVirtuemart !== 'undefined'){;
				// Hubo resultado, recorremos para añadir a productos.
				var idsVirtuemart = resultado.idVirtuemart;
				var Key;
				for (i = 0; i < idsVirtuemart.length ; i ++){
					key = Object.keys(idsVirtuemart[i]);	
					// Buscamos key en producto
					for ( x=0; x < productos.length ; x ++){
						if (productos[x].id == key){
							productos[x].idVirtuemart = parseInt(idsVirtuemart[i][key]);
						}
					}
				}
				
			// Ahora aquellos productos que tiene idVirtuemart
			EnviarStockWeb(tienda_web,productos);
				
			} else {
				alert( 'No hay idVirtuemart para los productos, o hubo un error');
				return;
			}
		}
	});
	
}

function EnviarStockWeb(tienda_web,productos){
	// @Objetivo :
	// Ejecutar en servidor de web funcion que reste stock de productos
	// Pendiente el que no lo haga dos vez , si hace clic o intro muy rapido.
	$("#DescontarStock").prop("disabled", true);
	var url_ruta = tienda_web.dominio + '/administrator/apisv/tareas.php';
	var parametros = {
		"key" :  tienda_web.key_api,
		"action"    : 'RestarStock',
		"productos"	: productos
	};
	$.ajax({
		data       : parametros,
		url        : url_ruta,
		type       : 'post',
		beforeSend : function () {
		console.log('*********  Envio datos para Buscar Producto  ****************');
		},
		success    :  function (response) {
				console.log('Respuesta de envio de datos');
				//~ var resultado = $.parseJSON(response);
				var resultado = response;
	
				if (resultado['Datos'].estado !== 'Correcto'){
					// Quiere decir que algo salio mal.. por lo que debemos guardalo en registro como error.
					alert(' Error, algo salio mal.');
				}
				// Ahora registramos en tpv ( importar_virtuemart_ticketst el resultado)
				console.log(resultado['Datos']);

				RegistrarRestarStockTicket(resultado['Datos']);
			}
			
	});
}  

function RegistrarRestarStockTicket(respuesta){
	// Ejecutar en servidor local (tpv) registro de que ya se resto stock.
	var parametros = {
		"pulsado"    		: 'RegistrarRestaStock',
		"id_ticketst"		: id_ticketst,
		"respuesta_servidor": respuesta
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
		console.log('*********  Registros de stock en tpv de aquellos tickests que se resto en servidor **************');
		},
		success    :  function (response) {
				console.log('Respuesta de registro resta de stock en tpv');
				//~ var resultado = $.parseJSON(response);
				var resultado = response;
			}
			
	});
	
	
}

function GuardarConfiguracion(){
	// Si llega aquí es porque cambio el valor de check impresion...
	// por lo que cambiamos el valor en configuracion.
	alert('Grabar configuracion');
	console.log(configuracion);
	if (configuracion.impresion_ticket==='Si'){
		configuracion.impresion_ticket = 'No'
	} else {
		configuracion.impresion_ticket = 'Si'
	}
	console.log ('Despues de cambio');
	console.log(configuracion);
	
	var parametros = {
		"pulsado"    		: 'Grabar_configuracion',
		"configuracion"		: configuracion,
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
		console.log('*********  Grabando configuracion **************');
		},
		success    :  function (response) {
				console.log('Respuesta de grabar configuracion');
				//~ var resultado = $.parseJSON(response);
				var resultado = response;
			}
			
	});
	
	
	
	
	
}
function abrirIndicencia(dedonde){
	var parametros = {
		"pulsado"    : 'abririncidencia',
		"dedonde" : dedonde,
		"usuario":cabecera.idUsuario,
		"idReal":cabecera.idReal
	};
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Modificando los importes de la factura  ****************');
		},
		success    :  function (response) {
			console.log('Respuesta de la modificación de los importes');
			var resultado =  $.parseJSON(response);
			titulo="Crear incidencia";
			html=resultado.html;
			abrirModal(titulo, html);
		}
	});
}
