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




function teclaPulsada(event,nombreInput,nfila=0,nomcampo=''){
	// @ Objetivo 
	// La intencion es controlar la tecla pulsada según input y dato  
	// Informacion de event:
	// onkeydown, onkeypress  y onkeyup, pero el valor del input correcto, solo obtenemos en onkeyup ( al soltar)
	// mas info en: http://librosweb.es/libro/javascript/capitulo_6/obteniendo_informacion_del_evento_objeto_event.html
	// @ parametros que recibimos.
	// 	event -> Objeto, donde podemos obtener el numero tecla, typo de evento ( mouse o key).
	// 	nombreInput -> Nombre del id donde se ejecuto la funcion.
	// 	nfila-> Es la fila en la que estamos.
	// 	nomcampo -> Se utiliza modal productos para indicar en cajabusqueda  que campo vamos buscar. 
	console.log ( ' ==== Pulsamos tecla ===== ');
	// Variables que voy utilizar aparte parametros.
	var datoinput = '';
	var pantalla = 'tpv';
	//Creo array con nombresInput de caja entrada de productos
	// Y con la variable SiInputProductos controlo los campos.
	// si esta variable es mayor o igual 0 quiere decir nombInput es uno de esos campo.
	var nombresInputProductos = ["Codbarras","Referencia","Descripcion"];
	var SiInputProductos = nombresInputProductos.indexOf(nombreInput); // Resultado numero index si lo encuentra , sino es -1

	// Obtenemos datoinput menos de filacliente o filaproducto.
		// 1.- Tienen que estar vacios.
		// 		Codbarras 
		// 		Referencia
		// 		Descripcion
		// 2.- Tiene que tener datos para poder buscarlos primero.
		// 		cajaBusquedacliente
		// 		cajaBusqueda
		// 		Unidad
		// 3.- No se obtiene datos solo se mueve.
		//		filaproducto
		//		filacliente
	if ((nombreInput !== 'filacliente') && (nombreInput !== 'filaproducto')) {
		datoinput = obtenerdatos(nombreInput,nfila); // String limpio espacios al principio y final.
	}
	
	//[PULSAMOS : tecla F1 --> 112  Abrimos popup COBRAR]
	if (event.keyCode === 112){
		// [PENDIENTE] Antes de abrir popup deberíamos comprobar si viene Unidad entonces debe recalcular
		var numproduct = productos.length;
		if (numproduct > 0){
			console.log('PULSO F1 - DENTRO CAJA ');
			cobrarF1();
		}
	}
	
	//[PULSAMOS :tecla Enter]
	if(event.keyCode == 13){
		//~ console.log('Pulsamos ENTER -> vamos nomCampo y enviamos->nombreINput:'+nombreInput);
		if ((nombreInput === 'cajaBusqueda') || (nombreInput === 'cajaBusquedacliente')){
			// Cambiamo valor pantalla si estamos en popup
			pantalla= 'popup';
		}
		if (SiInputProductos >= 0) {
			if (datoinput !=='') {
				// Si viene Descripcion,Referencia,Codbarras
				buscarProductos(nombreInput,datoinput,pantalla)
				//~ campo = nombreCampo(nombreInput,nfila,nomcampo,event.keyCode);
				return; // no continuanmos.. volvemos..
			} else {
				// Calculamos el index para obtener el nombre del input siguiente.
				var index= SiInputProductos + 1;
				if (index === nombresInputProductos.length) {
					// Volvemos al primero
					index = 0;
				}
				// Nos movemos.
				$('#'+nombresInputProductos[index]).focus();
			}
			
		}
		if (nombreInput === 'cajaBusqueda'){
			if (datoinput !=='') {
				// Si viene de caja busqueda de productos.
				//~ console.log('Nombre Campo:'+nomcampo);
				buscarProductos(nomcampo,datoinput,pantalla)
				//~ campo = nombreCampo(nombreInput,nfila,nomcampo,event.keyCode);
				return; // no continuanmos.. volvemos..
			}
			
		}
		if (nombreInput === 'cajaBusquedacliente') {
			buscarClientes(datoinput);
			return;// no continuanmos.. volvemos..
		}
		if (nombreInput === 'Unidad'){
			if (parseFloat(datoinput) != productos[nfila-1].unidad){
				// Solo lo ejecutamos si el datos es distinto a lo que teníamos.
				productos[nfila-1].unidad = parseFloat(datoinput);  // Lo pasamos como numero
				recalculoImporte(productos[nfila-1].unidad,nfila-1);
			}
		}
		if (nombreInput === 'entrega'){
			var entrega = datoinput;
			var cambio = entrega - total;
			console.log(entrega);
			if(event.keyCode === 13){
				if (cambio < 0){
					$('#cambio').css('color','red');
				}else {
					$('#cambio').css('color','grey');
				}
			$('#cambio').val(cambio.toFixed(2));
			// Ponemos como focus el btn de aceptar
			$('#CobrarAceptar').focus();
			}
		}




	} 

	//[PULSAMOS : tecla abajo y arriba]
	if ((event.keyCode === 40) || (event.keyCode === 38)){
		console.log('Pulse tecla bajar o subir ,nombreInput:'+nombreInput);
		console.log(' Este es valor nfila' +nfila + ' Antes de cambiar' );

		// [ Se pulso flecha ABAJO en campos Codbarras/Referencia/Descripcion estan vacio]
		
		if ((datoinput === '') && (SiInputProductos >= 0) && (event.keyCode === 40)){
			console.log('Entro en vacio');
			// Antes de enviar a focus, tenemos saber si hay productos.
			if (productos.length>0){
				console.log('Envio focus a nfila'+nfila);
				$('#N' + productos.length + '_Unidad').select();
				return; // no continuanmos.. volvemos..
			}
		}
		// [ Se pulso fecha abajo y arriba en UNIDAD]
		if (nombreInput === 'Unidad'){
			console.log('Estoy en nombreInput->Unidad'+nfila);
			// Recuerdad productos empieza 0 y fila en 1
			if (parseFloat(datoinput) != productos[nfila-1].unidad){
				// Solo lo ejecutamos si el datos es distinto a lo que teníamos.
				productos[nfila-1].unidad = parseFloat(datoinput);  // Lo pasamos como numero
				recalculoImporte(productos[nfila-1].unidad,nfila-1);
			}
			 // recalculo importe 
			if ((nfila === productos.length) && (event.keyCode === 38)){
				// Estoy en fila ultima de productos y pulse arriba ,por lo el focus es codbarras
				$('#Codbarras').focus();
				return; // no continuanmos.. volvemos..
			}
			if ((nfila === 1) &&(event.keyCode === 40)){
				// Estoy en la FILA UNO (ULTIMA) de productos y pulse abajo ..por lo que volvemos codbarras
				//~ console.log('Debería ir CodBarras, ya que pulso abajo y esta en primera fila');
				$('#Codbarras').focus();
				return; // no continuanmos.. volvemos..
			}
			// Ahora subimos o bajamos según pulsado.
			var nueva_fila = nfila;
			if (event.keyCode === 40){
				// Hay que recordad que UNIDAD ( filas productos están de mayor a menor)
				nueva_fila = nueva_fila-1;
			}
			if (event.keyCode === 38){
				// Hay que recordad que UNIDAD ( filas productos están de mayor a menor)
				nueva_fila = nueva_fila+1;
			}
			// Seleccionamos input siguiente o anterior segun tecla.
			$('#N' + nueva_fila + '_Unidad').select();
			return; // no continuanmos.. volvemos..
		}	
		
		// [ Se pulso fecha abajo y arriba en Modal]
		if ((nombreInput === 'cajaBusquedacliente') || (nombreInput === 'cajaBusqueda')){
			// Si estoy modales.
			console.log('Entro en modales ');
			//~ if (nombreInput === 'cajaBusqueda'){  
				// [ pulsamos flecha abajo o arriba  y estamos cajaBusqueda de lista productos] 
				console.log('[ pulsamos flecha abajo o arriba  y estamos cajaBusqueda de lista productos]');
				tiempoEnfoqueInput(nfila); //enfoque en el primer input de la lista
				return;// no continuanmos.. volvemos..
			//~ } 
		}
		// [ Se pulso fecha abajo y arriba en Modal de filaclientes o filaproductos]
		if ((nombreInput === 'filacliente') || (nombreInput === 'filaproducto')) {
			// [ pulsamos flecha abajo o arriba  y estamos lista productos o clientes.(modal)] 
			// Ahora subimos o bajamos según pulsado.

			var nueva_fila = nfila;

			if (event.keyCode === 40){
				// Hay que recordad que UNIDAD ( filas productos están de mayor a menor)
				nueva_fila = nueva_fila+1;
			}
			if (event.keyCode === 38){
				// Hay que recordad que UNIDAD ( filas productos están de mayor a menor)
				nueva_fila = nueva_fila-1;
			}
			console.log('Nueva fila:'+nueva_fila );
			// Si nfila es menor 0 entonces volvemos caja modal
			if (nueva_fila < 0){
				console.log('Entro nueva fila < 0 , ahora debemos enviar a :'+nomcampo );
				$('#'+nomcampo).select();
				
			} else {
				console.log('fila:'+nfila );
				tiempoEnfoqueInput(nueva_fila);// no continuanmos.. volvemos..
			}
		}
	// Cerramos condicional de pulsar tecla abajo y arriba.
	}
}
//en input llamo con onkeydown a teclaPulsada(event,nombreInput,nfila)
//pongo un tiempo de focus en input ventana modal busqueda 
function tiempoEnfoqueInput(nfila){
	setTimeout(function() {   //pongo un tiempo de focus en input modal busqueda 
		$('#N_'+nfila).focus(); 
	}, 100); 
}

function cobrarF1(){
	//@Objetivo:
	// Recalcular en php los totales.( Si hay diferencia se informa)
	// Abrir modal de htmlcobrar
	
	var parametros = {
			"pulsado" 	: 'cobrar',
			"total" : total,
			"productos"	 	: productos
			//"dedonde" : dedonde
	};
	$.ajax({ data:  parametros,
		url:   'tareas.php',
		type:  'post',
		beforeSend: function () {
			$("#resultado").html('Comprobamos que el producto existe ');
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
}

//quiero conseguir valor del campo
function obtenerdatos(id,nfila=0){
	// @Objetivo es obtener el valor del input
	// @Parametros  id-> Identificador id , nfila -> Si el id es usuario necesito el nfila para obtener valor fila
	console.log('ObtenerDatos del id:'+id);
	if (id === 'Unidad'){
		
		id = 'N'+nfila+'_Unidad';
	}
	var aux = document.getElementById(id);
	valorlimpio = aux.value;
	valorlimpio = valorlimpio.trim(); // valor limpio de espacios.
	return valorlimpio;
}

//case de nombreCampo = mysql , = html, 
//con el id='C0_Codbarras' recojo el valor del campo en funcion obtener datos
// pero necesito  nombreCampo = 'CCODEBAR' para mysql
//nfila, numero fila

function resetCampo(campo){
	console.log('Entro en resetCampo '+campo);
	var campos = [];
	campos['CREF'] = 'Referencia';
	campos['CCODEBAR'] = 'Codbarras';
	campos['CDETALLE'] = 'Descripcion';
	document.getElementById(campos[campo]).value='';
	return;
}


function buscarProductos(nombreInput,busqueda,dedonde){
	// @ Objetivo:
	//  Buscar productos donde el dato exista en el campo que se busca...
	// @ Parametros:
	// 		nombreinput = ref,codigoBarras o descripc
	// 		busqueda = valor del input que corresponde.
	// 		dedonde  = [tpv] o [popup] 
	// @ Respuesta:
	//  1.- Un producto unico.
	//  2.- Un listado de productos.
	//  3.- O nada un error.
	
	console.log('FUNCION buscarProductos JS- Para buscar con el campo');
	var parametros = {
		"pulsado"    : 'buscarProductos',
		"valorCampo" : busqueda,
		"campo"      : nombreInput,
		"dedonde"    : dedonde
	};
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
			if (resultado['Estado'] === 'Correcto') {
				var datos = [];
				datos = resultado.datos[0];
				console.log('Entro en Estado Correcto funcion buscarProducto ->datos (producto)');
				//~ console.log(datos);
				//~ console.log('consulta '+resultado.sql);
				agregarFila(datos);
				
			} else {
				// Se ejecuta tanto sea un listado como un error.
				console.log('=== Entro en Estado Listado de funcion buscarProducto =====');
				//~ console.log(resultado);
				//~ console.log('consulta  listado '+resultado.sql);
				//~ console.log('datos--> '+response);
				//~ console.log('Nitems '+resultado.Nitems);
				
				
				var busqueda = resultado.listado;   
				var HtmlProductos=busqueda.html;   
				var titulo = 'Listado productos encontrados ';
				// Abrimos modal de productos.
				abrirModal(titulo,HtmlProductos);
			}
		// Al no poner return , esto se va ejecutar siempre.
		document.getElementById(nombreInput).value='';
		}
		

	});
}

function agregarFila(datos){
	// @ Objetivo
	// 	Añadir producto a productos y ademas obtener htmlLinea para mostrar
	// Voy a crear objeto producto nuevo..
	productos.push(new ObjProducto(datos));
	var num_item = productos.length -1; // Obtenemos cual es el ultimo ( recuerda que empieza contado 0)
	// Ahora por Ajax montamos el html fila.
	var parametros = {
		"pulsado"    : 'HtmlLineaTicket',
		"producto" : productos[num_item],
		"num_item"      : num_item,
		"CONF_campoPeso"    : CONF_campoPeso
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
			console.log(nuevafila);
			
			//$ signifca jQuery 
			//$("#tabla").append(nuevaFila);
			$("#tabla").prepend(nuevafila);
			$('#Codbarras').focus();  //al agregar fila el foco lo coje el input de codigobarras
			sumaImportes();
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
	sumaImportes();
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
	sumaImportes();
}
//~ //fin funcion que agrega o elimina linea
//************************************************************


//creamos funcion de abrir modal pasandole datos ej. titulo
//para asi pintarlo con jquery en html
function abrirModal(titulo,tabla){
	// Recibimos titulo -> String.( podemos cambiarlos cuando queramos)
	// datos -> Puede ser un array o puede ser vacio
	//~ if (opcion === "htmlProductos"){		
	//~ }
	$('.modal-body > p').html(tabla);
	$('.modal-title').html(titulo);
	$('#busquedaModal').modal('show');
	
	//Se lanza este evento cuando se ha hecho visible el modal al usuario (se espera que concluyan las transiciones de CSS).
	$('#busquedaModal').on('shown.bs.modal', function() {
		$('#cajaBusqueda').focus(); //foco en input cajaBusqueda MODAL listadoProductos
		//~ 
		$('#entrega').select(); 	//foco en input entrega MODAL cobrar
		
		$('#cajaBusquedacliente').focus(); //foco en input caja busqueda del cliente
	//~ 
	});

}

function cerrarModal(cref,cdetalle,ctipoIva,ccodebar,npconiva,id){
	// @ Llegamos aquí desde el modal de productos (funciones.php) en htmlProductos()
	// Nuevos datos tabla nueva... 
	var datos = []
	datos['idArticulo'] 	= id;
	datos['crefTienda'] 	= cref;
	datos['articulo_name'] 	= cdetalle;
	datos['pvpCiva'] 		= npconiva;
	datos['iva'] 			= ctipoIva;
	datos['codBarras']		= ccodebar;
	$('#busquedaModal').modal('hide');
	$('#Codbarras').focus();	
	agregarFila(datos);
	
	
}


function cerrarModalClientes(id,nombre){
	// @ parametros recibidos.
	// 	id -> Del cliente
	//  nombre ->  Nombre cliente
	// mostrarlos en tpv
	
	//cerrar modal busqueda
	$('#busquedaModal').modal('hide');
	
	//agregar datos funcion js
	$('#id_cliente').val(id);

	
	$('#Cliente').val(nombre);	
	cabecera['idCliente'] = id;
	$('#Descripcion').focus();
}


function buscarClientes(valor=''){
	// @ Objetivo:
	// 	Abrir modal con lista clientes, que permitar buscar en caja modal.
	// 	Ejecutamos Ajax para obtener el html que vamos mostrar.
	// @ parametros :
	//	valor -> Sería el valor caja del propio modal
	console.log('FUNCION buscarClientes JS-AJAX');
	var parametros = {
		"pulsado"    : 'buscarClientes',
		"busqueda" : valor
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
			var titulo = 'Listado clientes ';
			abrirModal(titulo,HtmlClientes);
			// Asignamos focus a caja buscar cliente.
			$('#cajaBusquedacliente').focus();
		}
	});
}
function grabarTicketsTemporal(){
	// Objetivo es enviar los datos necesarios para poder hacer un ticket temporal.
	console.log('Grabamos en BD');
	//~ alert('Grabar');
	// Montamos array
	//~ var UNproduct =[];
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
		"total"			: total
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
			// Ahora pintamos pie de ticket.
			if (resultado.total > 0 ){
				// Quiere decir que hay datos a mostrar en pie.
				total = parseInt(resultado.total) // varible global.
				$('.totalImporte').html(total.toFixed(2));
				// Ahora tengo que pintar los ivas.
				// Eliminamos los que hay..
				
				$('#tipo4').html('');
				$('#tipo10').html('');
				$('#tipo21').html('');
				$('#base4').html('');
				$('#base10').html('');
				$('#base21').html
				$('#iva4').html('');
				$('#iva10').html('');
				$('#iva21').html('');
				var desgloseIvas = [];
				desgloseIvas.push(resultado.desglose);
				// Ahora recorremos array desglose
				desgloseIvas.forEach(function(desglose){
					// mostramos los tipos ivas , bases y importes.
					var tipos = Object.keys(desglose);
					for (index in tipos){
						var tipo = tipos[index];
						$('#line'+tipo).css('display','');
						$('#tipo'+tipo).html(tipo+'%');
						$('#base'+tipo).html(desglose[tipo].base); 
						$('#iva'+tipo).html(desglose[tipo].iva);
					}
				});
				
				
				//~ console.log(desgloseIvas.toString()); 
				
			}
			
		}
	});
}

function CerrarTicket(){
	//@ Objetivo:
	// Enviar datos del ticket (cabecera y caja de cobrar)
	// para guaardar como Cobrado en tablas ticket y temporal de ticket se cambia estado a COBRADO
	 var entregado = obtenerdatos('entrega')
	 var formaPago = $('#modoPago').val();
	 //podemos obtener el valor de la propiedad checked, true o false
	 var checkimprimir = $('input[name=checkimprimir]').prop('checked'); 
	 
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
		"checkimprimir"		: checkimprimir  //true o false
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
			document.location.href='tpv.php';
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
				
			window.location.href = './ticketCerrado.php?id='+checkID[0];
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
	 }
} 




// ===================  FUNCIONES DE PINTAR BONITO =========================
//html onfocus 
function sobreProducto(cont){
	$('#Fila_'+cont).css('background-color','lightblue');
}
//html onfocusout y onmouseout
function abandonProducto(cont){
	$('#Fila_'+cont).css('background-color','white');
}

function sobreProductoCraton(cont){
	$('#Fila_'+cont).css('background-color','azure');
}




// =========================== OBJETOS  ===================================
function ObjProducto(datos,valor=1,estado ='Activo')
{
    console.log('Estoy creando objeto producto');
    this.id = datos.idArticulo;
    this.cref = datos.crefTienda
    this.cdetalle = datos.articulo_name;
    this.pvpconiva = parseFloat(datos.pvpCiva).toFixed(2);
    this.ccodebar = datos.codBarras;
    this.ctipoiva = datos.iva;
    this.unidad = valor;
    this.estado = estado;
    this.nfila = productos.length+1;
    this.importe = parseFloat(this.pvpconiva) * this.unidad;
}
	

	

