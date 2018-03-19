function metodoClick(pulsado,adonde){
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'Ver':
			console.log('Entro en Ver pedido');
			// Cargamos variable global ar checkID = [];
			VerIdSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
			// Ahora redireccionamos 
			window.location.href = './'+adonde+'.php?id='+checkID[0];
			break;
		case 'AgregarPedido':
			console.log('entro en agregar producto');
			window.location.href = './pedido.php';
			
			break;
		case 'AgregarAlbaran':
			console.log('entro en agregar producto');
			window.location.href = './albaran.php';
			break;
		case 'AgregarFactura':
			console.log('entro en agregar producto');
			window.location.href = './factura.php';
			break;
		
		case 'NuevaBusquedaPedido':
			// Obtenemos puesto en input de Buscar
			Buscar();
			// Ahora redireccionamos 
			if (BPedido !== ''){
				window.location.href = './'+adonde+'.php?buscar='+BPedido;
			} else {
				// volvemos sin mas..
				return;
			}
			console.log('Resultado Buscar:'+BPedido);
			break;
		case 'AgregarAlbaran':
			console.log('entro en agregar producto');
			window.location.href = './albaran.php';
			
			break;
	case 'AgregarFactura':
			console.log('entro en agregar producto');
			window.location.href = './factura.php';
			
			break;
		
	 }
} 

function formasVenciCliente(formasVenci){
	//@Objetivo:
	// Monta el html de formas de vencimiento del cliente
	console.log("Estoy en formas pago vencimiento factura");

	var parametros = {
		"pulsado"    : 'htmlFomasVenci',
		"formasVenci" : formasVenci
	};
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en escribir html formas pago vencimiento facturas JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de html formas pago vencimiento factura');
			var resultado =  $.parseJSON(response); 
			$("#formaspago").prepend(resultado.html1);
			$("#fechaVencimiento").prepend(resultado.html2);
			
		}
	});
}

function buscarClientes(dedonde, idcaja, valor=''){
	// @ Objetivo:
	// 	Abrir modal con lista clientes, que permitar buscar en caja modal.
	// 	Ejecutamos Ajax para obtener el html que vamos mostrar.
	// @ parametros :
	//	valor -> Sería el valor caja del propio modal

	console.log('FUNCION buscarClientes JS-AJAX');
	var parametros = {
		"pulsado"    : 'buscarClientes',
		"busqueda" : valor,
		"dedonde":dedonde,
		"idcaja":idcaja
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
			//var encontrados = resultado.encontrados;
			// Si el archivo de donde viene la consulta es  albaran con lo que devuelve la consulta
			//de buscarCliente se registra en los input y se bloquean posteriormente
			
			if (resultado.Nitems==1){
				cabecera.idCliente=resultado.id;
				//cabecera.nombreCliente=resultado.nombre;
				$('#Cliente').val(resultado.nombre);
				$('#Cliente').prop('disabled', true);
				$('#id_cliente').prop('disabled', true);
				$("#buscar").css("display", "none");
				$('#idArticulo').focus();
				mostrarFila();
				if (dedonde=="albaran"){
					comprobarPedidosExis();
				}
				if (dedonde=="factura"){
					formasVenciCliente(resultado.formasVenci);
					comprobarAlbaranesExis();
				}
			}else{
				console.log(resultado.html);
			 var titulo = 'Listado clientes ';
			 var HtmlClientes=resultado.html.html; 
			 abrirModal(titulo,HtmlClientes);
			 }
			
		}
	});
}
function abrirModal(titulo,tabla){
	// @ Objetivo :
	// Abril modal con texto buscado y con titulo que le indiquemos.
	console.log('Estamos en abrir modal');
//	console.log(tabla.html);
	$('.modal-body ').html(tabla);
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
function controladorAcciones(caja,accion, tecla){
	// @ Objetivo es obtener datos si fuera necesario y ejecutar accion despues de pulsar una tecla.
	//  Es Controlador de acciones a pulsar una tecla que llamamos desde teclado.js
	// @ Parametros:
	//  	caja -> Objeto que aparte de los datos que le ponemos en variables globales de cada input
	//				tiene funciones que podemos necesitar como:
	//						darValor -> donde obtiene el valor input
	switch(accion) {
		
		case 'buscarClientes':
			// Esta funcion necesita el valor.
			console.log("Estoy en buscarClientes");
			buscarClientes(caja.darParametro('dedonde'),caja.id_input ,caja.darValor());
			break;
		case 'saltar_idCliente':
		console.log('Entro en acciones saltar_idCliente');
		var dato = caja.darValor();
			if ( dato.length === 0){
				var d_focus = 'id_cliente';
				ponerFocus(d_focus);
			}
			break;
		case 'saltar_idClienteFlechaAbajo':
		console.log('Entro en acciones saltar_idClienteFlechaAbajo');
		var d_focus = 'id_cliente';
		ponerFocus(d_focus);
		break;
		
		case 'saltar_nombreCliente':
		console.log('Entro en acciones saltar_nombreCliente');
		var dato = caja.darValor();
			if ( dato.length === 0){
				
				var d_focus = 'Cliente';
				ponerFocus(d_focus);
			}
			break;
			case 'saltar_nombreClienteArticulo':
		console.log('Entro en acciones saltar_nombreCliente');
		var dato = caja.darValor();
				var d_focus = 'Cliente';
				ponerFocus(d_focus);
			
			break;
		case 'saltar_Fecha':
		console.log('Entro en acciones saltar_fecha');
			var d_focus = 'fecha';
			ponerFocus(d_focus);
			break
		case 'saltar_idArticulo':
		console.log('Entro en acciones saltar_idArticulo');
		var d_focus = 'idArticulo';
			ponerFocus(d_focus);
			
			break
		case 'buscarProductos':
			// Esta funcion necesita el valor.
			console.log('Entro en acciones buscar Productos');
			buscarProductos(caja.name_cja,caja.darParametro('campo'),caja.id_input , caja.darValor(),caja.darParametro('dedonde'));
			break;
			
			
		case 'saltar_CodBarras':
		console.log('Entro en acciones codigo de barras');
			var dato = caja.darValor();
			if ( dato.length === 0){
				// Si esta vacio, sino permitimos saltar.
				var d_focus = 'Codbarras';
				ponerFocus(d_focus);
			}
			break;
		case 'recalcular_totalProducto':
		console.log("entre en recalcular precio producto");
			// recuerda que lo productos empizan 0 y las filas 1
			var nfila = parseInt(caja.fila)-1;
			// Comprobamos si cambio valor , sino no hacemos nada.
			productos[nfila].unidad = caja.darValor();
			recalculoImporte(productos[nfila].unidad,nfila, caja.darParametro('dedonde'));
			if (caja.tipo_event !== "blur"){
				var d_focus = 'idArticulo';
				ponerFocus(d_focus);
			}
			
			
			break;
		case 'mover_down':
		console.log("entro en mover down");
			// Controlamos si numero fila es correcto.
			if ( isNaN(caja.fila) === false){
				var nueva_fila = parseInt(caja.fila)+1;
			} else {
				// quiere decir que no tiene valor.
				var nueva_fila = 0;
			}
			if (caja.id_input === 'cajaBusqueda'){
				var nueva_fila = 0;
			}
			mover_down(nueva_fila,caja.darParametro('prefijo'), caja.darParametro('dedonde'));
			break;
		case 'mover_up':
		console.log("Entro en mover up");
			console.log( 'Accion subir 1 desde fila'+caja.fila);
			if ( isNaN(caja.fila) === false){
				var nueva_fila = parseInt(caja.fila)-1;
			} else {
				// quiere decir que no tiene valor.
				var nueva_fila = 0;
			}
			mover_up(nueva_fila,caja.darParametro('prefijo'), caja.darParametro('dedonde'));
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
				ponerSelect('Unidad_Fila_'+productos.length);
			} else {
			   console.log( ' No nos movemos ya que no hay productos');
			}
			break;
			
	//Acciones de albarán
	
		case 'saltarNumPedido':
				console.log("Ente en fecha Al");
				var dato = caja.darValor();
				cabecera.fecha=dato;
				if  ( $('#numPedido').css('display') == 'none' ) {
						var d_focus='id_clienteAl';
				}else{
					
					var d_focus = 'numPedido';
				}
				
				
				ponerFocus(d_focus);
				
		break;
		case 'saltarNumPedidoCon':
				console.log("Ente en fecha Al");
				var dato = caja.darValor();
				cabecera.fecha=dato;
				if ( dato.length === 0){
					if  ( $('#numPedido').css('display') == 'none' ) {
						var d_focus='id_clienteAl';
					}else{
						var d_focus = 'numPedido';
						
					}
				}
				ponerFocus(d_focus);
				
		break;
		case 'saltarFechaAl':
				console.log("Entre en saltarFechaAl");
				var dato=caja.darValor();
				if ( dato.length === 0){
				var d_focus = 'fechaAl';
				ponerFocus(d_focus);
			}
		break;
		case 'buscarPedido':
		console.log("Entre en buscar pedido");
		buscarPedido(caja.darParametro('dedonde'),caja.id_input ,caja.darValor());
		
		break;
		case 'buscarAlbaran':
		console.log("Entre en buscar pedido");
		buscarAlbaran(caja.darParametro('dedonde'),caja.id_input ,caja.darValor());
		
		break;
		case 'selectFormas':
		console.log("Entre en la funcion select formas");
		selectFormas();
		break;
		case 'buscarClientesAlbaran':
		console.log("Entre en buscarCliente albaran");
		buscarClienteAl(caja.darParametro('dedonde'),caja.id_input ,caja.darValor());
		break;
		
		case 'buscarProductosAl':
		console.log("Entre en buscarPedroductos albaran");
		buscarProductosAl(caja.name_cja,caja.darParametro('campo'),caja.id_input , caja.darValor(),caja.darParametro('dedonde'));

		break;
		
		case 'insertarImporte':
		console.log("Entre en insertarImporte de factura");
		insertarImporte();
		break;
		default :
			console.log ( 'Accion no encontrada '+ accion);
	} 
}
//Función que inserta los importes que se van añadiendo a una factura 
function insertarImporte(valor){
var importe= document.getElementById("Eimporte").value;
var fecha=document.getElementById("Efecha").value;
var parametros = {
		"pulsado"    : 'insertarImporte',
		"importe" : importe,
		"fecha"      : fecha,
		"idFactura": cabecera.idReal
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
			if (resultado.mensaje==1){
				//Se muestra el mensaje cuando el importe es superior al de la factura
				alert("El importe introducido no es correcto");
			}else{
				$("#tablaImporte").append(resultado.html);
				$("#tabla").find('input').attr("disabled", "disabled");
				$("#tabla").find('a').css("display", "none");
			}
			
		}
	});
	
	
}
function ponerFocus (destino_focus){
	// @ Objetivo:
	// 	Poner focus a donde nos indique el parametro, que debe ser id queremos apuntar.
	console.log('Entro en enviar focus de :'+destino_focus);
	setTimeout(function() {   //pongo un tiempo de focus ya que sino no funciona correctamente
		jQuery('#'+destino_focus.toString()).focus(); 
	}, 50); 

}
function ponerSelect(destino_focus){
	console.log('Entro en enviar select de :'+destino_focus);
	jQuery('#'+destino_focus.toString()).select(); 
	

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
		console.log(' Entro en Before de '+ caja.id_input)
		caja.fila = caja.id_input.slice(2);
	}
	if (caja.id_input.indexOf('Unidad_Fila') >-1){
		console.log("input de caja");
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
function buscarProductos(id_input,campo, idcaja, busqueda,dedonde){
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
	var parametros = {
		"pulsado"    : 'buscarProductos',
		"cajaInput"	 : id_input,
		"valorCampo" : busqueda,
		"campo"      : campo,
		"idcaja"	 :idcaja,
		'dedonde'	:dedonde
	};
	if (busqueda==""){
		alert("ERROR NO HAS ESCRITO NADA");
	}else{
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
			 if (resultado['Nitems']===1){
							var datos = new Object();
							datos.Numalbcli=0;
							datos.Numpedcli=0;
							datos.ccodbar=resultado['datos'][0]['codBarras'];
							datos.cdetalle=resultado['datos'][0]['articulo_name'];
							datos.cref=resultado['datos'][0]['crefTienda'];
							datos.estadoLinea="Activo";
							datos.idArticulo=resultado['datos'][0]['idArticulo'];
							datos.idpedcli=0;
							datos.iva=resultado['datos'][0]['iva'];
							datos.ncant=1;
							datos.nfila=productos.length+1;
							datos.nunidades=1;
							var importe =resultado['datos'][0]['pvpCiva']*1;
							datos.importe=importe.toFixed(2);
							var pvpCiva= parseFloat(resultado['datos'][0]['pvpCiva']);
							datos.precioCiva=pvpCiva.toFixed(2);
							
							n_item=parseInt(productos.length)+1;
							var campo='Unidad_Fila_'+n_item;
							productos.push(datos);
							addTemporal(dedonde);
							AgregarFilaProductosAl(datos, dedonde, campo);
							resetCampo(id_input);
						}else{
							console.log('=== Entro en Estado Listado de funcion buscarProducto =====');
				
							var busqueda = resultado.listado;   
							var HtmlProductos=busqueda.html;   
							var titulo = 'Listado productos encontrados ';
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
					
			}

	});
}
}
//html que se muestra cuando añadimos un producto nuevo
function agregarFilaProducto(num_item, campo){
	console.log(num_item);
	//Recibe el número del productos (el número de la fila)
	var parametros = {
		"pulsado"    : 'AgregarFilaProductos',
		"producto" : productos[num_item-1],
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
			//Escribe la fila del producto
			var nuevafila = resultado['html'];
			// devuelve el html de la fila del producto
			$("#tabla").prepend(nuevafila);
			ponerSelect(campo);
			return resultado;
		}
	});
}
//Borra los datos del input
function resetCampo(campo){
	console.log('Entro en resetCampo '+campo);
	document.getElementById(campo).value='';
	return;
}
function agregarFila(datos,campo=''){
	// @ Objetivo
	// 	Añadir producto a productos (JS) y ademas obtener htmlLinea para mostrar
	// Voy a crear objeto producto nuevo..
	console.log('Voy agregar producto');
	productos.push(new ObjProducto(datos));
	var num_item = productos.length -1; // Obtenemos cual es el ultimo ( recuerda que empieza contado 0)
	// Ahora por Ajax montamos el html fila.
	var parametros = {
		"pulsado"    : 'HtmlLineaPedido',
		"producto" : productos[num_item],
		"num_item"      : num_item,
		"CONF_campoPeso"    : CONF_campoPeso
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Obteniendo html de productos albaran  ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de Obtener HTML linea de FUNCION -> agregarFila');
			
			var resultado =  $.parseJSON(response);
			var nuevafila = resultado['html'];
			
			$("#tabla").prepend(nuevafila);
			if ('campo' ==='') {
				// Si no viene dato campo, por lo que focus por defectoe es Codbarras
				$('#Codbarras').focus();  
			} else {
				// Ponemos focus el campo que le indicamos en parametro campo.
				$(campo).focus();
			}
			grabarPedidoTemporal();
		}
	});

	
};


function escribirClienteSeleccionado(id, nombre ,dedonde=''){
	//Escribe en los input de cliente los datos 
	//Esta funcon la utilizo para cuando se pulsa un cliente de la ventana modal 
	//transforma los datos para reutilizar la funcion de buscar cliente como si se introduciera un id de cliente 
	//De esta forma no hace falta ninguna función más
	console.log("estamos en escribirClienteSeleccionado");
	$('#id_cliente').val(id);
	$('#Cliente').val(nombre);
	idInput="id_cliente";
	buscarClientes(dedonde, idInput, id);
	 cerrarPopUp();
	 mostrarFila();
}

function abandonFila(cont){
	$('#N_'+cont).css('background-color','white');
}
function sobreFilaCraton(cont){
	console.log("Estoy en fila carton");
	$('#N_'+cont).css('background-color','azure');
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
function escribirProductoSeleccionado(campo,cref,cdetalle,ctipoIva,ccodebar,npconiva,id, dedonde){
	// @ Objetivo:
	//   Realizamos cuando venimos popUp de Productos.
	// @ Parametros:
	// 	 Caja -> Indica la caja queremos que ponga focus
	//   datos -> Es el array que vamos enviar para añadir fila.
	console.log( '--- FUNCION escribirProductoSeleccionado  --- ');
	var datos = new Object();
	if (dedonde=="factura"){
		datos.Numalbcli=0;
		datos.idalbcli=0;
	}
	if (dedonde=="albaran"){
		datos.Numpedcli=0;
	}
	datos.ccodbar=ccodebar;
	datos.cdetalle=cdetalle;
	datos.cref=cref;
	datos.estadoLinea="Activo";
	 datos.iva=ctipoIva;
	 datos.idArticulo=id;
	 datos.ncant=1;
	 datos.nfila=productos.length+1;
	 datos.importe=npconiva.toFixed(2);
	 var pvpCiva= parseFloat(npconiva);
	datos.precioCiva=pvpCiva.toFixed(2);
	productos.push(datos);
	 addTemporal(dedonde);
	 AgregarFilaProductosAl(datos, dedonde);
	 resetCampo(campo);
	 cerrarPopUp(campo);	
}
function eliminarFila(num_item, valor=""){
	//Función para cambiar el estado del producto
	console.log("entre en eliminar Fila");
	var line;
	num=num_item-1;
	console.log(num);
	line = "#Row" + productos[num].nfila;
	// Nueva Objeto de productos.
	if(valor=="albaran" || valor=="factura" || valor=="bandera"){
		productos[num].estadoLinea='Eliminado';
	}else{
		productos[num].estado= 'Eliminado';
	}
	$(line).addClass('tachado');
	if(valor=="albaran" || valor=="factura" || valor=="bandera"){
		
		console.log("estoy eliminar fila factura");
	$(line + "> .eliminar").html('<a onclick="retornarFila('+num_item+', '+"'"+valor+"'"+');"><span class="glyphicon glyphicon-export"></span></a>');
	$("#N" +productos[num].nfila + "_Unidad").prop("disabled", true);
		addTemporal(valor);
		
	}else{
		$(line + "> .eliminar").html('<a onclick="retornarFila('+num_item+');"><span class="glyphicon glyphicon-export"></span></a>');
	$("#N" +productos[num].nfila + "_Unidad").prop("disabled", true);

		addProductoTemp();
	}
	
}
function retornarFila(num_item, valor=""){
	// @Objetivo :
	// Es pasar un producto eliminado a activo.
	console.log("entre en retornar fila");
	var line;
	num=num_item-1;
	line = "#Row" +productos[num].nfila;
	// Nueva Objeto de productos.
	if(valor=="albaran" || valor=="factura" || valor=="bandera"){
		productos[num].estadoLinea= 'Activo';
	}else{
		productos[num].estado= 'Activo';
	}
	if(valor=="albaran" || valor=="factura" || valor=="bandera"){
	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarFila('+num_item+' , '+"'"+valor+"'"+');"><span class="glyphicon glyphicon-trash"></span></a>');
			if (productos[num].nunidades == 0) {
				// Nueva Objeto de productos.
				// Antiguo array productos.
				productos[num].nunidades = 1;
			}
				$("#Unidad_Fila_" + productos[num].nfila).prop("disabled", false);
				$("#N" + productos[num].nfila + "_Unidad").prop("disabled", false);
				$("#N" + productos[num].nfila + "_Unidad").val(productos[num].nunidades);
			
			addTemporal(valor);
	}else{
	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarFila('+num_item+');"><span class="glyphicon glyphicon-trash"></span></a>');

			if (productos[num].unidad == 0) {
				// Nueva Objeto de productos.
				//~ productos[nfila].unidad= 1;
				// Antiguo array productos.
				productos[num].unidad = 1;
				
				
			}
			$("#N" + productos[num].nfila + "_Unidad").prop("disabled", false);
			$("#N" + productos[num].nfila + "_Unidad").val(productos[num].unidad);
			addProductoTemp();
	}

	
}
function recalculoImporte(cantidad,num_item, dedonde=""){
	
	// @ Objetivo:
	// Recalcular el importe de la fila, si la cantidad cambia.
	// @ Parametros:
	//	cantidad -> Valor ( numerico) de input unidades.
	//	num_item -> El numero que indica el producto que modificamos.
	console.log('Estoy en recalculoImporte');
	console.log(num_item);
	if (dedonde=="albaran"|| dedonde=="factura"){
		if (productos[num_item].ncant == 0 && cantidad != 0) {
			retornarFila(num_item+1, dedonde);
		} else if (cantidad == 0 ) {
			eliminarFila(num_item+1, dedonde);
		}
		productos[num_item].ncant = cantidad;
		
		var importe = cantidad*productos[num_item].precioCiva;
		var id = '#N'+productos[num_item].nfila+'_Importe';
		importe = importe.toFixed(2);
		$(id).html(importe);
		
		addTemporal(dedonde);
	
	}else{

	if (productos[num_item].cant == 0 && cantidad != 0) {
		retornarFila(num_item+1);
	} else if (cantidad == 0 ) {
		eliminarFila(num_item+1);
	}
	console.log('Valor de cantidad'+cantidad);
	productos[num_item].cant = cantidad;
	
	var importe = cantidad*productos[num_item].pvpCiva;
	var id = '#N'+productos[num_item].nfila+'_Importe';

	importe = importe.toFixed(2);
	$(id).html(importe);
		addProductoTemp();
	}
}

function mover_down(fila,prefijo, dedonde=""){
	console.log("entro en mover down");
sobreFila(fila);
	var d_focus = prefijo+fila;
	if (prefijo !== 'N_'){
			if ( document.getElementById(d_focus) ) {
				console.log("entre en document.getElement");
				ponerSelect(d_focus);
			}else{
				var d_focus = 'idArticulo';
				ponerSelect(d_focus);
			}
	}	else{
		var ant=fila-1;
		abandonFila(ant);
		ponerFocus(d_focus);
		
	}
}

function mover_up(fila,prefijo, dedonde=""){
	console.log("entro en mover up");
	console.log(fila);
	sobreFila(fila);
	console.log(dedonde);
	if (dedonde !== "cerrados"){
		var d_focus = prefijo+fila;
		ponerSelect(d_focus);
	}else{
		var ant=fila-1;
		abandonFila(ant);
		var d_focus = prefijo+fila;
		ponerFocus(d_focus);
		
	}
	
}
//Muestra la fila de inputs para añadir un producto nuevo 
function mostrarFila(){
	console.log("mostrar fila");
	$("#Row0").removeAttr("style") ;
}

function buscarPedido(dedonde, idcaja, valor=''){
	//Buscar los pedidos de un cliente que tenga el estado guardado
	console.log('FUNCION buscarPedido JS-AJAX');
	var parametros = {
		"pulsado"    : 'buscarPedido',
		"busqueda" : valor,
		"idCliente":cabecera.idCliente,
		"dedonde":dedonde
	};
	console.log (valor);
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en buscar Pedfidos JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de buscar pedidos');
			var resultado =  $.parseJSON(response); 
			var encontrados = resultado.encontrados;
			var HtmlPedidos=resultado.html;   //$resultado['html'] de montaje html
			if (valor==""){ //Si el usuario selecciona el icono de buscar pedido abre un modal 
			//con los pedidos del cliente
				var titulo = 'Listado Pedidos ';
				abrirModal(titulo, HtmlPedidos);
			}else{
				if (resultado.Nitems>0){//Si tiene un resultado comprobamos que el pedido no este en ya en la lista 
				// de pedidos introducidos . Si la bandera es 0 quiere decir que no esta en la lista de los arrays de pedidos introducidos
					var bandera=0;
					for(i=0; i<pedidos.length; i++){//recorre todo el array de arrays de pedidos
						var numeroPedido=pedidos[i].Numpedcli;
						var numeroNuevo=resultado['datos'].Numpedcli;
						if (numeroPedido == numeroNuevo){// Si el número del pedido introducido es igual que el número de pedido
						//del array pedidos entonces la bandera es igual a 1
							bandera=bandera+1;
						}
					}
					if (bandera==0){// si no hay repetidos
						var datos = [];
						datos = resultado['datos'];
						n_item=parseInt(pedidos.length)+1;
						datos.nfila=n_item;
						pedidos.push(datos);// En el array de arrays  de pedidos de la cabecera metemos el array de pedido nuevo 
						productosAdd=resultado.productos;
						var numFila=productos.length+1;
						for (i=0; i<productosAdd.length; i++){ //en el array de arrays de productos metemos los productos de ese pedido
							resultado.productos[i]['nfila']=numFila;
							productos.push(resultado.productos[i]);
							numFila++;
						}
						addTemporal(dedonde)//Añade un albaran temporal o lo modifica
						//Modifica el estado del pedido a Facturado.
						//Quiere decir que cuando se mete un pedido en un albaran ya no se puede volver a meter el pedido en otro albarán
						//Ni se puede modificar  en pedidos
						ModificarEstadoPedido("Albaran", "Facturado", resultado['datos'].Numpedcli, resultado['datos'].idPedCli);
						//Añade el html de la fila del pedido
						
						AgregarFilaPedido(datos, "albaran");
						//Agrega los productos de ese pedido
						AgregarFilaProductosAl(resultado.productos, dedonde);
					}else{
						alert("Ya has introducido ese pedido");
					}
				}else{
					alert("No hay resultado");
				}
			}
		}
	});
}


function buscarAlbaran(dedonde, idcaja, valor=''){
	//Buscar los pedidos de un cliente que tenga el estado guardado
	console.log('FUNCION buscar albaran JS-AJAX');
	var parametros = {
		"pulsado"    : 'buscarAlbaran',
		"busqueda" : valor,
		"idCliente":cabecera.idCliente
	};
	console.log (valor);
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en buscar Albaran JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de buscar albaran');
			var resultado =  $.parseJSON(response); 
			var encontrados = resultado.encontrados;
			var HtmlAlbaranes=resultado.html;   //$resultado['html'] de montaje html
			if (valor==""){ //Si el usuario selecciona el icono de buscar pedido abre un modal 
			//con los pedidos del cliente
				var titulo = 'Listado Albaranes ';
				abrirModal(titulo, HtmlAlbaranes);
			}else{
				if (resultado.Nitems>0){//Si tiene un resultado comprobamos que el pedido no este en ya en la lista 
				// de pedidos introducidos . Si la bandera es 0 quiere decir que no esta en la lista de los arrays de pedidos introducidos
					var bandera=0;
					for(i=0; i<albaranes.length; i++){//recorre todo el array de arrays de pedidos
						var numeroAlbaran=albaranes[i].Numalbcli;
						var numeroNuevo=resultado['datos'].Numalbcli;
						if (numeroAlbaran == numeroNuevo){// Si el número del pedido introducido es igual que el número de pedido
						//del array pedidos entonces la bandera es igual a 1
							bandera=bandera+1;
						}
					}
					if (bandera==0){// si no hay repetidos
						console.log("Hay un resultado");
						var datos = [];
						datos = resultado['datos'];
						n_item=parseInt(albaranes.length)+1;
						datos.nfila=n_item;
						albaranes.push(datos);// En el array de arrays  de pedidos de la cabecera metemos el array de pedido nuevo 
						productosAdd=resultado.productos;
						var numFila=productos.length+1;
						for (i=0; i<productosAdd.length; i++){ //en el array de arrays de productos metemos los productos de ese pedido
							resultado.productos[i]['nfila']=numFila;
							productos.push(resultado.productos[i]);
							numFila++;
						}
						console.log("llegue hasta aqui en albaranes buscar");
						
						addTemporal(dedonde)//Añade un albaran temporal o lo modifica
						//Modifica el estado del pedido a Facturado.
						//Quiere decir que cuando se mete un pedido en un albaran ya no se puede volver a meter el pedido en otro albarán
						//Ni se puede modificar  en pedidos
					  ModificarEstadoPedido("factura", "Facturado", resultado['datos'].Numalbcli, resultado['datos'].idalbcli);
						//Añade el html de la fila del pedido
						 AgregarFilaAlbaran(datos, dedonde);
						//Agrega los productos de ese pedido
						 AgregarFilaProductosAl(resultado.productos, dedonde);
					}else{
						alert("Ya has introducido ese pedido");
					}
				}else{
					alert("No hay resultado");
				}
			}
		}
	});
}
function addTemporal(dedonde){
		console.log('FUNCION Añadir temporal JS-AJAX');
		if (dedonde=="pedidos"){
			var pulsado= 'anhadirPedidoTemp';
		}
		if (dedonde=="albaran"){
			var pulsado='anhadirAlbaranTemporal';
		}
		if (dedonde=="factura"){
			var pulsado='anhadirfacturaTemporal';
		}
		var parametros = {
		"pulsado"    : pulsado,
		"idTemporal":cabecera.idTemporal,
		"idUsuario":cabecera.idUsuario,
		"idTienda":cabecera.idTienda,
		"estado":cabecera.estado,
		"idReal":cabecera.idReal,
		"fecha":cabecera.fecha,
		"productos":productos,
		"idCliente":cabecera.idCliente
	};
	if (dedonde=="albaran"){
		parametros['pedidos']=pedidos;
	}
	if (dedonde=="factura"){
		parametros['albaranes']=albaranes;
	}
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en añadir albaran temporal JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de añadir albaran temporal');
			var resultado =  $.parseJSON(response); 
		
			var HtmlClientes=resultado.html;//$resultado['html'] de montaje html

			console.log(resultado.id.id);
			if (resultado.existe == 0){
				history.pushState(null,'','?tActual='+resultado.id);
				cabecera.idTemporal=resultado.id;
			}
			resetearTotales();
			
			total = parseFloat(resultado['totales']['total'])
			$('.totalImporte').html(total.toFixed(2));
			$('#tabla-pie  > tbody ').html(resultado['htmlTabla']);
			var estado="Sin guardar";
			if (dedonde=="factura"){
				if (cabecera.idReal>0){
				var estado="Sin guardar";
				modificarEstadoFactura(cabecera.idReal, estado);
			}
			if (dedonde=="albaran"){
				if (cabecera.idReal>0){
				modificarEstadoAlbaran(cabecera.idReal, estado);
				
				}
			}
			if (dedonde=="pedidos"){
					if (cabecera.idReal>0){
						ModificarEstadoPedido(dedonde, estado);
					}
			}
			
			}
			
		}
	});
	
}
// Modificar el estado de la factura para controlar que tiene temporales

function modificarEstadoFactura(idFactura, estado){
	var parametros = {
			"pulsado": 'modificarEstadoFactura',
			"idFactura":idFactura,
			"estado":estado
		};
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en Modificar estado factura js****************');
		},
		success    :  function (response) {
		var resultado =  $.parseJSON(response); 
		}
	});
}
//Modificar el estado del albarán, se utiliza principalmente cuando en facturas escogemos un albarán
function modificarEstadoAlbaran(idAlbaran, estado){
	console.log("Entre en modificar Estado albaran");
	var parametros = {
			"pulsado": 'modificarEstadoAlbaran',
			"idAlbaran":idAlbaran,
			"estado":estado
		};
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en Modificar estado factura js****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de estado pedido js');
			var resultado =  $.parseJSON(response); 
		}
	});
}


//Modifica el estado de un pedido, dependiendo de donde venga la función carga unos parametro u otros
function ModificarEstadoPedido(dedonde, estado, num="", id=""){
	console.log("Entre en modificar estado pedido");
	if (dedonde=="pedidos"){
		var parametros = {
			"pulsado"    : 'modificarEstadoPedido',
			"idPedido":cabecera.idReal,
			"numPedidoTemp":cabecera.idTemporal,
			"estado" : estado,
			"dedonde": dedonde
		};
	}
	if (dedonde=="Albaran"){
		var parametros = {
			"pulsado"    : 'modificarEstadoPedido',
			"idPedido":id,
			"numPedidoTemp":num,
			"estado" : estado,
			"dedonde" : dedonde
		};
	}
	console.log(parametros);
	if (dedonde == "factura"){
		var parametros = {
			"pulsado"    : 'modificarEstadoPedido',
			"idAlbaran":id,
			"numAlbaranTemporal":num,
			"estado" : estado,
			"dedonde" : dedonde
		};
		
	}
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en Modificar estado pedido js****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de estado pedido js');
			var resultado =  $.parseJSON(response); 
			console.log(resultado);
		}
	});
}



//Comprueba los pedidos de un cliente que esten en estado guardado 
function comprobarPedidosExis(){
	console.log('FUNCION comprobar pedidos existentes  JS-AJAX');
	var parametros = {
		"pulsado"    : 'comprobarPedidos',
		"idCliente" : cabecera.idCliente
		
	};
	console.log(parametros);
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en comprobar pedidos existentes JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de comprobar pedidos');
			var resultado =  $.parseJSON(response); 
			if (resultado.ped==1){
				$("#numPedidoT").show();
				$("#numPedido").show();
				$("#buscarPedido").show();
				$("#tablaPedidos").show();
				$("#numPedido").focus();
			}else{
				$('#idArticulo').focus();
			}
			
		}
	});
}
//Busca los albaranes de el cliente seleccionado y muestra la tabla oculta
function comprobarAlbaranesExis(){
	console.log('FUNCION comprobar pedidos existentes  JS-AJAX');
	var parametros = {
		"pulsado"    : 'comprobarAlbaran',
		"idCliente" : cabecera.idCliente
		
	};
	console.log(parametros);
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en comprobar pedidos existentes JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de comprobar pedidos');
			var resultado =  $.parseJSON(response); 
			if (resultado.alb==1){
				$("#numAlbaranT").show();
				$("#numAlbaran").show();
				$("#buscarAlbaran").show();
				$("#tablaAlbaran").show();
			}
			
		}
	});
}

//Agregar un html con el pedido 
function AgregarFilaPedido(datos , dedonde=""){
	console.log("Estoy en agregar fila Pedido");
	var parametros = {
		"pulsado"    : 'htmlAgregarFilaPedido',
		"datos" : datos,
		"dedonde":dedonde
	};
	
	console.log(parametros);
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en escribir html fila pedidos JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de html fila pedidos');
			var resultado =  $.parseJSON(response); 
			var nuevafila = resultado['html'];
			$("#tablaPedidos").prepend(nuevafila);
			$('#numPedido').focus(); 
			$('#numPedido').val(""); 
			
		}
	});
}
//Agrega el html de el albarán seleccionado en factura
function AgregarFilaAlbaran(datos, dedonde){
	console.log("Estoy en agregar fila albaran");
	var parametros = {
		"pulsado"    : 'htmlAgregarFilaAlbaran',
		"datos" : datos,
		"dedonde":dedonde
	};
	console.log(datos);
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en escribir html fila pedidos JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de html fila pedidos');
			var resultado =  $.parseJSON(response); 
			var nuevafila = resultado['html'];
			$("#tablaAlbaran").prepend(nuevafila);
			$('#numAlbaran').focus(); 
			$('#numAlbaran').val(""); 
			
		}
	});
}
// Agrega el nuevo html de un producto al principio de la tabla productos
function AgregarFilaProductosAl(productosAl, dedonde='', campo=''){
	console.log("Estoy en agregar fila productos albaran");
	
	if (productosAl.length>1){
		productosAl=productosAl.reverse();
	}
	
	console.log(dedonde);
	var parametros = {
		"pulsado"    : 'htmlAgregarFilasProductos',
		"productos" : productosAl,
		"dedonde": dedonde
	};
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en escribir html fila pedidos JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de html fila pedidos');
			var resultado =  $.parseJSON(response); 
			console.log(resultado);
			var nuevafila = resultado['html'];
			$("#tabla").prepend(nuevafila);
			
				ponerSelect(campo);
		}
	});
}
// Cuando seleccionamos un pedido o un albarán llamamos a la funciones correspondiente enviandole el número.
//Estas funciones se llama en el modal tando de añadir un pedido como un albarán para no hacer mas grande la funcion 
//lo que hacemos es llamar a la función que llamamos cuando ponemos directamente el número
function buscarDatosPedido(NumPedido){
	console.log("Estoy en buscarDatosPedido");
	buscarPedido("Albaran", "numPedido", NumPedido);
	cerrarPopUp();
}
function buscarDatosAlbaran(NumAlbaran){
	console.log("Estoy en buscar datos albaran");
	buscarAlbaran("factura", "numAlbaran", NumAlbaran);
	cerrarPopUp();
}
//Seleccionar un forma de vencimiento 
function selectFormas(){
	console.log("Esto en selectFormas");
	var option = document.getElementById("formaVenci").value;
	var fecha = document.getElementById("fechaVenci").value;
	var parametros = {
		"pulsado"    : 'ModificarFormasVencimiento',
		"opcion" : option,
		"fechaVenci": fecha,
		"idFacTem":cabecera.idTemporal
	};
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en escribir html fila pedidos JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de html fila pedidos');
			var resultado =  $.parseJSON(response); 
			
			
		}
	});	
}
function imprimir(id, dedonde, tienda){
	var parametros = {
		"pulsado"    : 'datosImprimir',
		"dedonde":dedonde,
		"id":id,
		"tienda":tienda
	};
	console.log(parametros);
	$.ajax({
			data       : parametros,
			url        : 'tareas.php',
			type       : 'post',
			beforeSend : function () {
				console.log('******** estoy en datos Imprimir JS****************');
			},
			success    :  function (response) {
				 var resultado =  $.parseJSON(response); 
				 window.open(resultado);
		}
		
	});
	
		
}
function sobreFila(cont){
	$('#N_'+cont).css('background-color','lightblue');
}

function eliminarAdjunto(numRegistro, dedonde, nfila){
	
	console.log("entre en eliminar Fila");
	var line;
	num=nfila-1;
	if (dedonde=="factura"){
		line = "#lineaP" + albaranes[num].nfila;
		albaranes[num].estado= 'Eliminado';
	}
	if (dedonde=="albaran"){
		line = "#lineaP" + pedidos[num].nfila;
		pedidos[num].estado= 'Eliminado';
	}
	
	$(line).addClass('tachado');
	$(line + "> .eliminar").html('<a onclick="retornarAdjunto('+numRegistro+', '+"'"+dedonde+"'," + nfila+');"><span class="glyphicon glyphicon-export"></span></a>');
	if (dedonde=="pedido"){
			addPedidoTemporal();
		}
		if (dedonde=="albaran"){
			for(i=0;i<productos.length; i++){
				if (productos[i].Numpedcli){
					var numProducto=productos[i].Numpedcli;
					if (numRegistro == numProducto){
						eliminarFila(productos[i].nfila, "bandera");
						
					}
				}else{
					var numProducto=productos[i].NumpedCli;
					if (numRegistro == numProducto){
						eliminarFila(productos[i].nfila, "bandera");
						
					}
				}
				
			}
			num=nfila-1;
			ModificarEstadoPedido("Albaran", "Guardado", numRegistro, pedidos[num].idPedido);
			addTemporal("Albaran");
		}
		if (dedonde=="factura"){
			for(i=0;i<productos.length; i++){
				if (productos[i].Numalbcli){
					var numProducto=productos[i].Numalbcli;
					if (numRegistro == numProducto){
						eliminarFila(productos[i].nfila, "bandera");
						
					}
				}else{
					var numProducto=productos[i].NumalbCli;
					if (numRegistro == numProducto){
						eliminarFila(productos[i].nfila, "bandera");
						
					}
				}
			}
			num=nfila-1;
			modificarEstadoAlbaran(albaranes[num].idAlbaran, "Guardado");
		
			addTemporal("factura");
		}
}
function retornarAdjunto(numRegistro, dedonde, nfila){
	console.log("entre en retornar fila adjunto");
	var estado="Guardado";
	var line;
	num=nfila-1;
	if (dedonde=="factura"){
		line = "#lineaP" + albaranes[num].nfila;
		albaranes[num].estado= 'Activo';
	}
	if (dedonde=="albaran"){
		line = "#lineaP" + pedidos[num].nfila;
		pedidos[num].estado= 'Activo';
	}
	
	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarAdjunto('+numRegistro+' , '+"'"+dedonde+"', "+nfila+');"><span class="glyphicon glyphicon-trash"></span></a>');
	if (dedonde=="pedido"){
		addPedidoTemporal();
	}
	if (dedonde=="albaran"){
		for(i=0;i<productos.length; i++){
			if (productos[i].Numpedcli){
				var numProducto=productos[i].Numpedcli;
				if (numRegistro==numProducto){
					retornarFila(productos[i].nfila, "bandera");
				}
			}else{
				var numProducto=productos[i].NumpedCli;
				if (numRegistro==numProducto){
					retornarFila(productos[i].nfila, "bandera");
				}
				
			}
		}
		num=nfila-1;
		ModificarEstadoPedido("Albaran", "Facturado", numRegistro, pedidos[num].idPedido);
		
		addTemporal("albaran");
	}
	if (dedonde=="factura"){
		for(i=0;i<productos.length; i++){
				if (productos[i].Numalbcli){
				var numProducto=productos[i].Numalbcli;
				if (numRegistro==numProducto){
					retornarFila(productos[i].nfila, "bandera");
				}
			}else{
				var numProducto=productos[i].NumalbCli;
				if (numRegistro==numProducto){
					retornarFila(productos[i].nfila, "bandera");
				}
				
			}
			}
		num=nfila-1;
		modificarEstadoAlbaran(albaranes[num].idAlbaran, "Facturado");
		addTemporal("factura");
	
	}
}
function resetearTotales(){
	// Funcion para resetear totales.
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
	
}
