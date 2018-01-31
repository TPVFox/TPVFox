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
function Buscar (){
	$(document).ready(function()
	{
		// Lo ideal sería identificar palabras..
		// de momento solo una palabra..
		NuevoValorBuscar = $('input[name=buscar').val();
		NuevoValorBuscar = $.trim(NuevoValorBuscar);
		if (NuevoValorBuscar !== ''){
			BProductos= NuevoValorBuscar;
			console.log('Filtro:'+BProductos);
		} else {
			alert (' Debes poner algun texto ');
			BProductos = '';
		}
		return;
	});
}
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
			//window.location.href = './producto.php?id='+checkID[0];
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
function buscarClientes(dedonde, idcaja, valor=''){
	// @ Objetivo:
	// 	Abrir modal con lista clientes, que permitar buscar en caja modal.
	// 	Ejecutamos Ajax para obtener el html que vamos mostrar.
	// @ parametros :
	//	valor -> Sería el valor caja del propio modal

	console.log('FUNCION buscarClientes JS-AJAX');
	console.log(cabecera);
	var parametros = {
		"pulsado"    : 'buscarClientes',
		"busqueda" : valor,
		"dedonde":dedonde,
		"idcaja":idcaja
	};
	console.log (dedonde);
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
			// Si el archivo de donde viene la consulta es  albaran con lo que devuelve la consulta
			//de buscarCliente se registra en los input y se bloquean posteriormente
			if (dedonde=="albaran"){
				if (resultado.Nitems==1){
					//Se registra en la cabecera tanto el id del clinete como el nombre
					cabecera.idCliente=resultado.idCliente;
					cabecera.nombreCliente=resultado.nombre;
					//Los imput de cliente quedan desactivados y se oculta el botón de buscar
					$('#ClienteAl').val(resultado.nombre);
					$('#ClienteAl').prop('disabled', true);
					$('#id_clienteAl').prop('disabled', true);
					$("#buscar").css("display", "none");
					//Mostrar fila muestra los nombre del cliente en los input
					mostrarFila();
					//Comprueba si ese cliente tiene pedidos en estado guardado, si es así dibuja la caja del input pedidos
					comprobarPedidosExis();
				}
				//Si el archivo de donde viene es de pedidos 
			}else if (dedonde="pedidos"){
				var HtmlClientes=resultado.html;   //$resultado['html'] de montaje html
				if (valor==""){ //Si el valor viene vacio quiere decir que la persona pulsó el icono de buscar
					var titulo = 'Listado clientes ';
					abrirModal(titulo,HtmlClientes);
					// Asignamos focus a caja buscar cliente.
					if (encontrados >0 ){
						// Enfocamos el primer item.
						mover_down(0);
						$('#N_0').focus();
					}else {
						// No hay datos focus a caja buscar cliente.
						$('#cajaBusquedacliente').focus();
					}
				}else if(idcaja==="Cliente"){// Si el cliente escribio en el input del nombre de cliente 
					console.log('entre en cliente');
					console.log(resultado);
					var titulo = 'Listado clientes '; //Muestra los resultados de la consulta en una ventana modal
					abrirModal(titulo,HtmlClientes);
					if (encontrados >0 ){
						// Enfocamos el primer item.
						mover_down(0);
						$('#N_0').focus();
					}else {
						// No hay datos focus a caja buscar cliente.
						$('#cajaBusquedacliente').focus();
					}
				}else if(idcaja==="cajaBusquedacliente"){ // si la consulta viene de la caja input del modal
					console.log('entre en caja buqueda');
					console.log(resultado);
					var titulo = 'Listado clientes ';
					abrirModal(titulo,HtmlClientes);
					if (encontrados >0 ){
						// Enfocamos el primer item.
						mover_down(0);
						$('#N_0').focus();
					}else {
						// No hay datos focus a caja buscar cliente.
						$('#cajaBusquedacliente').focus();
					}
				}else{ //  Si recibión un id se escribe el nombre en el input , en la cabecera se guarda el id 
				// y la función de mostrar fila cubre los campos de cliente
					console.log('no muestro modal');
					$('#Cliente').val(resultado.nombre);
					console.log(resultado.idCliente);
						cabecera.idCliente=resultado.idCliente;
						mostrarFila();
				}
			}else if (dedonde="factura"){
				if (resultado.Nitems==1){
					//Se registra en la cabecera tanto el id del clinete como el nombre
					cabecera.idCliente=resultado.idCliente;
					cabecera.nombreCliente=resultado.nombre;
					//Los imput de cliente quedan desactivados y se oculta el botón de buscar
					$('#ClienteFac').val(resultado.nombre);
					$('#ClienteFac').prop('disabled', true);
					$('#id_clienteFac').prop('disabled', true);
					$("#buscar").css("display", "none");
					//Mostrar fila muestra los nombre del cliente en los input
					mostrarFila();
					//Comprueba si ese cliente tiene pedidos en estado guardado, si es así dibuja la caja del input pedidos
					comprobarPedidosExis();
				}
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
		var dato = caja.darValor();
				var d_focus = 'fecha';
				ponerFocus(d_focus);
			
			break
		case 'saltar_idArticulo':
		console.log('Entro en acciones saltar_idArticulo');
		var dato = caja.darValor();
		
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
			//~ productos.[nfila].unidad = caja.darValor();
			console.log ( caja);
			productos[nfila].unidad = caja.darValor();
			console.log(productos[nfila].unidad);
			recalculoImporte(productos[nfila].unidad,nfila, caja.darParametro('dedonde'));
			
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
			break;
			
	//Acciones de albarán
	
		case 'saltarNumPedido':
				console.log("Ente en fecha Al");
				var dato = caja.darValor();
				cabecera.fecha=dato;
				var d_focus = 'numPedido';
				
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
		case 'buscarClientesAlbaran':
		console.log("Entre en buscarCliente albaran");
		buscarClienteAl(caja.darParametro('dedonde'),caja.id_input ,caja.darValor());
		break;
		
		case 'buscarProductosAl':
		console.log("Entre en buscarPedroductos albaran");
		buscarProductosAl(caja.name_cja,caja.darParametro('campo'),caja.id_input , caja.darValor(),caja.darParametro('dedonde'));

		break;
		default :
			console.log ( 'Accion no encontrada '+ accion);
	} 
}
function ponerFocus (destino_focus){
	// @ Objetivo:
	// 	Poner focus a donde nos indique el parametro, que debe ser id queremos apuntar.
	console.log('Entro en enviar focus de :'+destino_focus);
	setTimeout(function() {   //pongo un tiempo de focus ya que sino no funciona correctamente
		jQuery('#'+destino_focus.toString()).focus(); 
	}, 50); 

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
	console.log(idcaja);
	console.log('FUNCION buscarProductos JS- Para buscar con el campo');
	var parametros = {
		"pulsado"    : 'buscarProductos',
		"cajaInput"	 : id_input,
		"valorCampo" : busqueda,
		"campo"      : campo,
		"idcaja"	 :idcaja
	};
	console.log(dedonde);
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
					//console.log(resultado);
					if(dedonde =="albaran"){
						if (resultado['Nitems']===1){
						var datos = new Object();
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
						
						productos.push(datos);
					
						addAlbaranTemp();
					 	AgregarFilaProductosAl(datos);
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
					if (dedonde == "pedidos"){
					console.log(resultado);	
				if (resultado['Nitems']===1){
					//~ console.log('Estado'+resultado['Estado']);
					var datos = [];
					datos = resultado['datos'][0];
					datos['nfila']=productos.length+1;
					datos['estado']="Activo";
					datos['cant']=1;
					var importe =datos['pvpCiva']*datos['cant'];
					datos['importe']=importe.toFixed(2);
					var pvpCiva= parseFloat(datos['pvpCiva']);
					datos['pvpCiva']=pvpCiva.toFixed(2);
					productos.push(datos);
					var num_item=datos['nfila'];
					//Al inserta un producto se registra en la cabecera el id del cliente 
					if (cabecera.numPedidoTemp==0){
						var idCliente=$('#id_cliente').val();
						console.log(idCliente);
						console.log('----- voy a escribir aaaaaaaaaaaaaa cliente seleccionado -----');
						AddTemp(idCliente);
						cabecera.idCliente=idCliente;
					}
					
					addProductoTemp();
					agregarFilaProducto(num_item);
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
				
		}
		

	});
	
}
function addProductoTemp(){
	console.log('Entro en añadir productos');
	var parametros = {
		"pulsado"    : 'añadirProductos',
		"idTemporal":cabecera.numPedidoTemp,
		"productos":productos
	};
	console.log(productos);
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Envio datos para Añadir productos  ****************');
		},
		success    :  function (response) {
		var resultado =  $.parseJSON(response);
		console.log(resultado);
			
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
			if (resultado['totales']['total'] > 0 ){
				// Quiere decir que hay datos a mostrar en pie.
				total = parseFloat(resultado['totales']['total']) // varible global.
				$('.totalImporte').html(total.toFixed(2));
				// Ahora tengo que pintar los ivas.
				var desgloseIvas = [];
				
				console.log("estoy aqui");
				console.log(resultado['totales']['desglose']);
				
				desgloseIvas.push(resultado['totales']['desglose']);
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
function agregarFilaProducto(num_item){
	console.log(num_item);
	//Recibe el número del productos (el número de la fila)
	var parametros = {
		"pulsado"    : 'HtmlLineaTicket',
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
			console.log(resultado['html']);
			console.log(resultado['producto']);
			//Escribe la fila del producto
			var nuevafila = resultado['html'];
			// devuelve el html de la fila del producto
			$("#tabla").prepend(nuevafila);
			var campo='#Unidad_Fila_'+num_item;
			console.log(campo);
			$(campo).focus();
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
	console.log(datos);
	
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
			grabarPedidoTemporal();
		}
	});

	
};
 
//~ function grabarPedidoTemporal(){
	//~ // @ Objetivo
	//~ // Grabar cabeceras y productos, amabas variables globales en tabla de ticket temporal.
	//~ console.log('Grabamos en BD');
	//~ var i  =0;
	//~ console.log('Productos');
	//~ console.log(productos);
	//~ // Para poder mandar objectos de productos ...
	//~ var parametros = {
		//~ "pulsado"    	: 'grabarPedidos',
		//~ "productos"	 	: productos,//
		//~ "idCliente"	 	: cabecera.idCliente,
		//~ "idTienda" 	 	: cabecera.idTienda,
		//~ "idUsuario"	 	: cabecera.idUsuario,
		//~ "estadoTicket" 	: cabecera.estadoTicket,
		//~ "numTicket"		: cabecera.numTicket,
	//~ };
	//~ $.ajax({
		//~ data       : parametros,
		//~ url        : 'tareas.php',
		//~ type       : 'post',
		//~ beforeSend : function () {
			//~ console.log('******** Voy a grabar****************');
		//~ },
		//~ success    :  function (response) {
			//~ console.log('Respuesta de grabar');
			//console.log(response);
			//~ var resultado =  $.parseJSON(response); 
			//~ console.log(resultado.estadoTicket);
			//~ // Cambiamos el estado :
			//~ cabecera.estadoTicket = resultado.estadoTicket;
			//~ cabecera.numTicket = resultado.NumeroTicket;
			//~ $('#EstadoTicket').html(resultado.estadoTicket);			
			//~ $('#EstadoTicket').css('background-color','red')
			//~ $('#EstadoTicket').css('color','white')
			//~ $('#NTicket').html('0/'+resultado.NumeroTicket);
			
			//~ console.log(productos.length);
				
			//~ //objetivo cuando esta en ticket actual , 
			//~ //en el navegador ponga ?tActual para que no afecte F5 SIN RECARGAR pagina
			//~ if (productos.length ===1 ){ 
				//~ history.pushState(null,'','?tActual='+resultado.NumeroTicket);
			//~ }
			//~ // Limpiamos los valores ivas y bases.
			//~ $('#tipo4').html('');
			//~ $('#tipo10').html('');
			//~ $('#tipo21').html('');
			//~ $('#base4').html('');
			//~ $('#base10').html('');
			//~ $('#base21').html('');
			//~ $('#iva4').html('');
			//~ $('#iva10').html('');
			//~ $('#iva21').html('');
			//~ $('.totalImporte').html('');
			
			//~ // Ahora pintamos pie de ticket.
			//~ if (resultado.total > 0 ){
				//~ // Quiere decir que hay datos a mostrar en pie.
				//~ total = parseFloat(resultado.total) // varible global.
				//~ $('.totalImporte').html(total.toFixed(2));
				//~ // Ahora tengo que pintar los ivas.
				//~ var desgloseIvas = [];
				//~ desgloseIvas.push(resultado.desglose);
				//~ console.log(desgloseIvas);
				//~ // Ahora recorremos array desglose
				//~ desgloseIvas.forEach(function(desglose){
					//~ console.log('Entro foreah');
					//~ // mostramos los tipos ivas , bases y importes.
					//~ var tipos = Object.keys(desglose);
					//~ console.log(desglose);
					//~ for (index in tipos){
						//~ var tipo = tipos[index];
						//~ $('#line'+parseInt(tipo)).css('display','');
						//~ $('#tipo'+parseInt(tipo)).html(parseInt(tipo)+'%');
						//~ $('#base'+parseInt(tipo)).html(desglose[tipo].base); 
						//~ $('#iva'+parseInt(tipo)).html(desglose[tipo].iva);
					//~ }
				//~ });
				
			//~ }
			
		//~ }
	//~ });
//~ }


function escribirClienteSeleccionado(id, nombre ,dedonde=''){
	//Escribe en los input de cliente los datos 
	//Esta funcon la utilizo para cuando se pulsa un cliente de la ventana modal 
	//transforma los datos para reutilizar la funcion de buscar cliente como si se introduciera un id de cliente 
	//De esta forma no hace falta ninguna función más
	$('#id_cliente').val(id);
	$('#Cliente').val(nombre);
	if (dedonde == "pedidos"){
		idInput="id_cliente";
	}else if(dedonde == albaran){
		idInput="id_clienteAl";
		
	}
	buscarClientes(dedonde, idInput, id);
	cerrarPopUp();
	mostrarFila();
}

function abandonFila(cont){
	$('#Fila_'+cont).css('background-color','white');
}
function sobreFilaCraton(cont){
	$('#Fila_'+cont).css('background-color','azure');
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
function mover_down(fila,prefijo){
	sobreFilaCraton(fila);
	var d_focus = prefijo+fila;
	ponerFocus(d_focus);
	
}
function sobreFilaCraton(cont){
	$('#Fila_'+cont).css('background-color','azure');
}
function escribirProductoSeleccionado(campo,cref,cdetalle,ctipoIva,ccodebar,npconiva,id){
	// @ Objetivo:
	//   Realizamos cuando venimos popUp de Productos.
	// @ Parametros:
	// 	 Caja -> Indica la caja queremos que ponga focus
	//   datos -> Es el array que vamos enviar para añadir fila.
	console.log( '--- FUNCION escribirProductoSeleccionado  --- ');
	if (campo=="CodbarrasAl"){
		var datos = new Object();
		datos.Numpedcli=0;
		datos.ccodbar=ccodebar;
		datos.cdetalle=cdetalle;
		datos.cref=cref;
		datos.estadoLinea="Activo";
		datos.idArticulo=id;
		datos.idpedcli=0;
		datos.iva=ctipoIva;
		datos.ncant=1;
		datos.nfila=productos.length+1;
		datos.nunidades=1;
		var importe =npconiva*1;
		datos.importe=importe.toFixed(2);
		var pvpCiva= parseFloat(npconiva);
		datos.precioCiva=pvpCiva.toFixed(2);
		productos.push(datos);
					
		addAlbaranTemp();
		AgregarFilaProductosAl(datos);
		resetCampo(campo);
		cerrarPopUp(campo);
	}else{
		var datos = new Object();
		datos['idArticulo'] 	= id;
		datos['crefTienda'] 	= cref;
		datos['articulo_name'] 	= cdetalle;
		datos['pvpCiva'] 		= npconiva;
		datos['iva'] 			= ctipoIva;
		datos['codBarras']		= ccodebar;
		datos['nfila']=productos.length+1;
		datos['estado']="Activo";
		datos['cant']=1;
		var importe =datos['pvpCiva']*datos['cant'];			
		datos['importe']=importe.toFixed(2);
		var pvpCiva= parseFloat(datos['pvpCiva']);
		datos['pvpCiva']=pvpCiva.toFixed(2);
		console.log(datos);
		productos.push(datos);
		console.log("dentro de productos");
		console.log(productos);
		var num_item=datos['nfila'];
		if (cabecera.numPedidoTemp==0){
							var idCliente=$('#id_cliente').val();
							console.log(idCliente);
							console.log('----- voy a escribir aaaaaaaaaaaaaa cliente seleccionado -----');
							AddTemp(idCliente);
							cabecera.idCliente=idCliente;
		}
		addProductoTemp();
		console.log(num_item);
		agregarFilaProducto(num_item);
		// Eliminamos contenido de cja destino y ponemos focus.
		
		resetCampo(campo);
		var campo='#Unidad_Fila_'+num_item;
		cerrarPopUp(campo);
	}
	
	
	

	
}
function eliminarFila(num_item, valor=""){
	//Función para cambiar el estado del producto
	console.log("entre en eliminar Fila");
	var line;
	num=num_item-1;
	console.log(num);
	line = "#Row" + productos[num].nfila;
	// Nueva Objeto de productos.
	if(valor="albaran"){
		productos[num].estadoLinea='Eliminado';
	}else{
		productos[num].estado= 'Eliminado';
	}
	$(line).addClass('tachado');
		if(valor="albaran"){
	$(line + "> .eliminar").html('<a onclick="retornarFila('+num_item+', '+"'"+'albaran'+"'"+');"><span class="glyphicon glyphicon-export"></span></a>');
	$("#N" +productos[num].nfila + "_Unidad").prop("disabled", true);

		addAlbaranTemp();
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
	console.log(num_item);
	num=num_item-1;
	console.log(productos[num]);
	line = "#Row" +productos[num].nfila;
	console.log(line);
	// Nueva Objeto de productos.
	if(valor=="albaran"){
		productos[num].estadoLinea= 'Activo';
	}else{
		productos[num].estado= 'Activo';
	}
	//~ console.log(productos[num].estado);
	console.log(productos);
	//~ var pvp =productos[num_item].pvpconiva;

	
	if(valor=="albaran"){
	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarFila('+num_item+' , '+"'"+'albaran'+"'"+');"><span class="glyphicon glyphicon-trash"></span></a>');

	console.log(productos[num].nunidades);
			if (productos[num].nunidades == 0) {
				// Nueva Objeto de productos.
				// Antiguo array productos.
				productos[num].nunidades = 1;
				//	recalculoImporte(productos[num].unidad,num_item);
				
			}
				$("#N" + productos[num].nfila + "_Unidad").prop("disabled", false);
				$("#N" + productos[num].nfila + "_Unidad").val(productos[num].nunidades);
			
				addAlbaranTemp();
	}else{
	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarFila('+num_item+');"><span class="glyphicon glyphicon-trash"></span></a>');

			if (productos[num].unidad == 0) {
				// Nueva Objeto de productos.
				//~ productos[nfila].unidad= 1;
				// Antiguo array productos.
				productos[num].unidad = 1;
				//	recalculoImporte(productos[num].unidad,num_item);
				
			}
			$("#N" + productos[num].nfila + "_Unidad").prop("disabled", false);
			$("#N" + productos[num].nfila + "_Unidad").val(productos[num].unidad);
			console.log(productos);
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
	if (dedonde=="albaran"){
		if (productos[num_item].ncant == 0 && cantidad != 0) {
			retornarFila(num_item+1, dedonde);
		} else if (cantidad == 0 ) {
			eliminarFila(num_item+1, dedonde);
		}
		productos[num_item].ncant = cantidad;
		//alert('DentroReclaculo:'+producto[nfila]['NPCONIVA']);
		var importe = cantidad*productos[num_item].precioCiva;
		var id = '#N'+productos[num_item].nfila+'_Importe';
		importe = importe.toFixed(2);
		$(id).html(importe);
	
		addAlbaranTemp();
	}else{
	//~ console.log('cantidad:'+cantidad);
	if (productos[num_item].cant == 0 && cantidad != 0) {
		retornarFila(num_item+1);
	} else if (cantidad == 0 ) {
		eliminarFila(num_item+1);
	}
	console.log('Valor de cantidad'+cantidad);
	productos[num_item].cant = cantidad;
	//alert('DentroReclaculo:'+producto[nfila]['NPCONIVA']);
	var importe = cantidad*productos[num_item].pvpCiva;
	var id = '#N'+productos[num_item].nfila+'_Importe';
	//alert('recalcular'+id);
	importe = importe.toFixed(2);
	$(id).html(importe);
		addProductoTemp();
	}
}
function sobreFilaCraton(cont){
	$('#Fila_'+cont).css('background-color','azure');
}

function mover_down(fila,prefijo){
	console.log("entro en mover down");
	console.log(fila);
	sobreFilaCraton(fila);
	var d_focus = prefijo+fila;
	if ( document.getElementById(d_focus) ) {
		ponerFocus(d_focus);
	}else{
		ponerFocus("idArticulo");
	}	
}

function mover_up(fila,prefijo){
	console.log("entro en mover up");
	console.log(fila);
	
	sobreFilaCraton(fila);
	var d_focus = prefijo+fila;
	ponerFocus(d_focus);
}
//Muestra la fila de inputs para añadir un producto nuevo 
function mostrarFila(){
	console.log("mostrar fila");
	$("#Row0").removeAttr("style") ;
	console.log("realizo funcion");
}
function AddTemp(id){
	console.log("-------------- estoy en add temp -----------");
	var parametros = {
		"pulsado"    : 'escribirCliente',
		"idcliente":id,
		"numPedidoTemp":cabecera.numPedidoTemp,
		"idUsuario":cabecera.idUsuario,
		"idTienda":cabecera.idTienda,
		"estadoPedido":cabecera.estadoPedido,
		"idPedido":cabecera.idPedido
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en añadir cliente JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de añadir cliente');
			var resultado =  $.parseJSON(response); 
			var encontrados = resultado.encontrados;
			var HtmlClientes=resultado.html; 
			history.pushState(null,'','?tActual='+resultado.numPedidoTemp);
			cabecera.numPedidoTemp=parseInt(resultado.numPedidoTemp);
		}
	});
}

function buscarPedido(dedonde, idcaja, valor=''){
	//Buscar los pedidos de un cliente que tenga el estado guardado
	console.log('FUNCION buscarPedido JS-AJAX');
	var parametros = {
		"pulsado"    : 'buscarPedido',
		"busqueda" : valor,
		"idCliente":cabecera.idCliente
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
			console.log(resultado);
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
						console.log("Hay un resultado");
						var datos = [];
						datos = resultado['datos'];
						pedidos.push(datos);// En el array de arrays  de pedidos de la cabecera metemos el array de pedido nuevo 
						productosAdd=resultado.productos;
						console.log("cuento los productos");
						console.log(productos.length);
						var numFila=productos.length+1;
						for (i=0; i<productosAdd.length; i++){ //en el array de arrays de productos metemos los productos de ese pedido
							resultado.productos[i]['nfila']=numFila;
							productos.push(resultado.productos[i]);
							numFila++;
						}
						addAlbaranTemp();//Añade un albaran temporal o lo modifica
						//Modifica el estado del pedido a Facturado.
						//Quiere decir que cuando se mete un pedido en un albaran ya no se puede volver a meter el pedido en otro albarán
						//Ni se puede modificar  en pedidos
						ModificarEstadoPedido("Albaran", "Facturado", resultado['datos'].Numpedcli, resultado['datos'].idPedCli);
						//Añade el html de la fila del pedido
						AgregarFilaPedido(datos);
						//Agrega los productos de ese pedido
						AgregarFilaProductosAl(resultado.productos);
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
function addAlbaranTemp(){
	console.log('FUNCION Añadir albaran temporal JS-AJAX');
	console.log(productos);
	var parametros = {
		"pulsado"    : 'añadirAlbaranTemporal',
		"idAlbaranTemp":cabecera.idAlbaranTemp,
		"idUsuario":cabecera.idUsuario,
		"idTienda":cabecera.idTienda,
		"estadoAlbaran":cabecera.estadoAlbaran,
		"idAlbaran":cabecera.idAlbaran,
		"numAlbaran":cabecera.numAlbaran,
		"fecha":cabecera.fecha,
		"productos":productos,
		"pedidos":pedidos,
		"idCliente":cabecera.idCliente
	};
	console.log(parametros);
	console.log("ESTOY EN AÑADIR ALBARAN");
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
				cabecera.idAlbaranTemp=resultado.id;
			}
				
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
			if (resultado['totales']['total'] > 0 ){
				// Quiere decir que hay datos a mostrar en pie.
				total = parseFloat(resultado['totales']['total']) // varible global.
				$('.totalImporte').html(total.toFixed(2));
				// Ahora tengo que pintar los ivas.
				var desgloseIvas = [];
				
				console.log("estoy aqui");
				console.log(resultado['totales']['desglose']);
				
				desgloseIvas.push(resultado['totales']['desglose']);
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
//Modifica el estado de un pedido, dependiendo de donde venga la función carga unos parametro u otros
function ModificarEstadoPedido(dedonde, estado, numPedido="", idPedido=""){
	console.log("Entre en modificar estado pedido");
	if (dedonde=="pedido"){
		var parametros = {
			"pulsado"    : 'modificarEstadoPedido',
			"idPedido":cabecera.idPedido,
			"numPedidoTemp":cabecera.numPedidoTemp,
			"estado" : estado,
			"dedonde": dedonde
		};
	}
	if (dedonde=="Albaran"){
		var parametros = {
			"pulsado"    : 'modificarEstadoPedido',
			"idPedido":idPedido,
			"numPedidoTemp":numPedido,
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
		//	var encontrados = resultado.encontrados;
		//	var HtmlClientes=resultado.html;   //$resultado['html'] de montaje html
		console.log(resultado);
			if (resultado.ped==1){
				$("#numPedidoT").show();
				$("#numPedido").show();
				$("#buscarPedido").show();
				$("#tablaPedidos").show();
			}
			
		}
	});
}


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
		//	var encontrados = resultado.encontrados;
		//	var HtmlClientes=resultado.html;   //$resultado['html'] de montaje html
		console.log(resultado);
			if (resultado.ped==1){
				$("#numAlbaranT").show();
				$("#numAlbaran").show();
				$("#buscarAlbaran").show();
				$("#tablaAlbaran").show();
			}
			
		}
	});
}



//Agregar un html con el pedido 
function AgregarFilaPedido(datos){
	console.log("Estoy en agregar fila Pedido");
	var parametros = {
		"pulsado"    : 'htmlAgregarFilaPedido',
		"datos" : datos
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
			console.log(resultado);
			var nuevafila = resultado['html'];
			$("#tablaPedidos").prepend(nuevafila);
			$('#numPedido').focus(); 
			$('#numPedido').val(""); 
			
		}
	});
}
function AgregarFilaProductosAl(productosAl){
	console.log("Estoy en agregar fila productos albaran");
	
	if (productosAl.length>1){
		productosAl=productosAl.reverse();
	}
	console.log(productosAl);
	var parametros = {
		"pulsado"    : 'htmlAgregarFilasProductos',
		"productos" : productosAl
	};
	console.log("PARAMETROS");
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
			console.log(resultado);
			var nuevafila = resultado['html'];
			$("#tabla").prepend(nuevafila);
			
			
		}
	});
}

function buscarDatosPedido(NumPedido){
	console.log("Estoy en buscarDatosPedido");
	buscarPedido("Albaran", "numPedido", NumPedido);
	cerrarPopUp();
}
