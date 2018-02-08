function controladorAcciones(caja,accion){
	switch(accion) {
		case 'buscarProveedor':
			console.log("Estoy en buscar proveedor");
			buscarProveedor(caja.darParametro('dedonde'),caja.id_input ,caja.darValor());
		break;
		case 'buscarProducto':
			console.log("Pulse buscar Producto");
			buscarProductos(caja.name_cja,caja.darParametro('campo'),caja.id_input , caja.darValor(),caja.darParametro('dedonde'));
		break;
		case 'recalcular_totalProducto':
			console.log("entre en recalcular precio producto");
			// recuerda que lo productos empizan 0 y las filas 1
			var nfila = parseInt(caja.fila)-1;
			// Comprobamos si cambio valor , sino no hacemos nada.
			//~ productos.[nfila].unidad = caja.darValor();
			
			
			productos[nfila].unidad = caja.darValor();
		
			console.log(caja.fila);
			recalculoImporte(productos[nfila].unidad, nfila, caja.darParametro('dedonde'));
			
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
		case 'addProveedorProducto':
			console.log("estoy en add proveedor fila");
			console.log(caja.fila);
			var nfila = parseInt(caja.fila)-1;
			var idArticulo=productos[nfila].idArticulo;
			productos[nfila].crefProveedor = caja.darValor();
			var coste =productos[nfila].ultimoCoste
			addProveedorProducto(productos[nfila].idArticulo, nfila , productos[nfila].crefProveedor, coste);
		break;
		case 'Saltar_idProveedor':
			var dato = caja.darValor();
			if ( dato.length === 0){
				var d_focus = 'id_proveedor';
				ponerFocus(d_focus);
			}
		break;
	}
}


function addProveedorProducto(idArticulo, nfila, valor, coste){
	console.log("ESTOY EN LA FUNCION ADD PROVEEDOR PRODUCTO");
	
	var parametros = {
		"pulsado"    : 'addProveedorArticulo',
		"idArticulo" : idArticulo,
		"refProveedor":valor,
		"idProveedor":cabecera.idProveedor,
		"coste":coste
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
				abrirModal(resultado.html);
				productos[nfila].crefProveedor=valor;
				fila=nfila+1;
				var id="#Proveedor_Fila_"+fila;
				$(id).prop('disabled', true);
				console.log(id);
				addPedidoTemporal();
	
		}
	});
	console.log(parametros);
	
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
		
	 }
} 


function buscarProveedor(dedonde, idcaja, valor=''){
	console.log('FUNCION buscarProveedores JS-AJAX');
	var parametros = {
		"pulsado"    : 'buscarProveedor',
		"busqueda" : valor,
		"dedonde":dedonde,
		"idcaja":idcaja
	};
	console.log(parametros);
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
				console.log(resultado);
				if (resultado.Nitems==1){
					cabecera.idProveedor=resultado.id;
					
					$('#Proveedor').val(resultado.nombre);
					$('#Proveedor').prop('disabled', true);
					$('#id_proveedor').prop('disabled', true);
					$("#buscar").css("display", "none");
				}else{
					var titulo = 'Listado Proveedores ';
					var HtmlProveedores=resultado.html['html']; 
					abrirModal(titulo,HtmlProveedores);
				}
				console.log(resultado);
	
		}
	});
	
}

function escribirProveedorSeleccionado(id, nombre, dedonde){
	idcaja="id_proveedor";
	valor=id;
	$('#id_proveedor').val(id);
	$('#Proveedor').val(nombre);
	buscarProveedor(dedonde, idcaja, valor);
	cerrarPopUp();
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
function sobreFilaCraton(cont){
	$('#Fila_'+cont).css('background-color','azure');
}
function abandonFila(cont){
	$('#Fila_'+cont).css('background-color','white');
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
function buscarProductos (id_input,campo, idcaja, busqueda,dedonde){
	console.log(idcaja);
	console.log('FUNCION buscarProductos JS- Para buscar con el campo');
	var parametros = {
		"pulsado"    : 'buscarProductos',
		"cajaInput"	 : id_input,
		"valorCampo" : busqueda,
		"campo"      : campo,
		"idcaja"	 :idcaja,
		"idProveedor": cabecera.idProveedor
	};
	console.log(id_input);
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
			console.log(resultado);
		
		if (resultado['Nitems']===1){
			var datos = new Object();
			datos.ccodbar=resultado['datos'][0]['codBarras'];
			datos.cdetalle=resultado['datos'][0]['articulo_name'];
			datos.cref=resultado['datos'][0]['crefTienda'];
			datos.crefProveedor=resultado['datos'][0]['crefProveedor'];
			datos.estado="Activo";
			datos.idArticulo=resultado['datos'][0]['idArticulo'];
			datos.idpedcli=0;
			datos.iva=resultado['datos'][0]['iva'];
			datos.ncant=1;
			datos.nfila=productos.length+1;
			datos.nunidades=1;
			var ultimoCoste= parseFloat(resultado['datos'][0]['ultimoCoste']);
			datos.ultimoCoste=ultimoCoste.toFixed(2);
			var ivares =(resultado['datos'][0]['iva']/100);
			var importe =(ivares+ultimoCoste)*1;
			console.log(importe);
			//~ var importe =resultado['datos'][0]['ultimoCoste']*1;
			datos.importe=importe.toFixed(2);
			
			productos.push(datos);
			addPedidoTemporal();
			AgregarFilaProductosAl(datos, dedonde);
		}else{
			console.log('=== Entro en Estado Listado de funcion buscarProducto =====');
				
			var busqueda = resultado.listado;   
			var HtmlProductos=busqueda['html']; 
			console.log(HtmlProductos);
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
function AgregarFilaProductosAl(productosAl, dedonde=''){
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
function addPedidoTemporal(){
	console.log('FUNCION Añadir pedido temporal JS-AJAX');
	console.log(productos);
	var parametros = {
		"pulsado"    : 'addPedidoTemporal',
		"numPedidoTemp": cabecera.numPedidoTemp,
		"idUsuario":cabecera.idUsuario,
		"idTienda":cabecera.idTienda,
		"estadoPedido":cabecera.estadoPedido,
		"idPedido":cabecera.idPedido,
		"numPedido":cabecera.numPedido,
		"fecha":cabecera.fecha,
		"productos":productos,
		"idProveedor":cabecera.idProveedor
	};
	
		
	console.log("ESTOY EN AÑADIR PEDIDO");
	console.log(cabecera.fecha);
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
				cabecera.numPedidoTemp=resultado.id;
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
			//~ if (cabecera.idAlbaran>0){
			//~ console.log("entre en modificar albaran");
				//~ var estado="Sin guardar";
				//~ modificarEstadoAlbaran(cabecera.idAlbaran, estado);
				
			//~ }
			
			
		}
	});
}
function ponerFocus (destino_focus){
	// @ Objetivo:
	// 	Poner focus a donde nos indique el parametro, que debe ser id queremos apuntar.
	console.log('Entro en enviar focus de :'+destino_focus);
	setTimeout(function() {   //pongo un tiempo de focus ya que sino no funciona correctamente
		jQuery(destino_focus.toString()).focus(); 
	}, 50); 

}
function escribirProductoSeleccionado(campo,cref,cdetalle,ctipoIva,ccodebar,npconiva,id){
		var datos = new Object();
		datos.ccodbar=ccodebar;
		datos.cdetalle=cdetalle;
		datos.cref=cref;
		datos.estado="Activo";
		datos.idArticulo=id;
		datos.iva=ctipoIva;
		datos.ncant=1;
		datos.nfila=productos.length+1;
		datos.nunidades=1;
		var importe =npconiva*1;
		datos.importe=importe.toFixed(2);
		var pvpCiva= parseFloat(npconiva);
		datos.precioCiva=pvpCiva.toFixed(2);
		productos.push(datos);
		dedonde="pedido";
		addPedidoTemporal();
		AgregarFilaProductosAl(datos, dedonde);
		var num_item=datos.nfila;
		resetCampo(campo);
		var campo='#Unidad_Fila_'+num_item;
		cerrarPopUp(campo);
}
function resetCampo(campo){
	console.log('Entro en resetCampo '+campo);
	document.getElementById(campo).value='';
	return;
}
function eliminarFila(num_item, valor=""){
	//Función para cambiar el estado del producto
	console.log("entre en eliminar Fila");
	console.log(valor);
	var line;
	num=num_item-1;
	console.log(num);
	line = "#Row" + productos[num].nfila;
	// Nueva Objeto de productos.
	
	productos[num].estado= 'Eliminado';
	
	$(line).addClass('tachado');
	
	$(line + "> .eliminar").html('<a onclick="retornarFila('+num_item+', '+"'"+valor+"'"+');"><span class="glyphicon glyphicon-export"></span></a>');
	$("#N" +productos[num].nfila + "_Unidad").prop("disabled", true);
		
	addPedidoTemporal()
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
	
	productos[num].estado= 'Activo';
	
	
	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarFila('+num_item+' , '+"'"+valor+"'"+');"><span class="glyphicon glyphicon-trash"></span></a>');

	console.log(productos[num].nunidades);
			if (productos[num].nunidades == 0) {
				// Nueva Objeto de productos.
				// Antiguo array productos.
				productos[num].nunidades = 1;
			}
				$("#Unidad_Fila_" + productos[num].nfila).prop("disabled", false);
				$("#N" + productos[num].nfila + "_Unidad").prop("disabled", false);
				$("#N" + productos[num].nfila + "_Unidad").val(productos[num].nunidades);
			
	
	addPedidoTemporal();
	
}
function recalculoImporte(cantidad, num_item, dedonde=""){
	
	// @ Objetivo:
	// Recalcular el importe de la fila, si la cantidad cambia.
	// @ Parametros:
	//	cantidad -> Valor ( numerico) de input unidades.
	//	num_item -> El numero que indica el producto que modificamos.
	console.log('Estoy en recalculoImporte');
	console.log(num_item);
	
		if (productos[num_item].ncant == 0 && cantidad != 0) {
			retornarFila(num_item+1, dedonde);
		} else if (cantidad == 0 ) {
			eliminarFila(num_item+1, dedonde);
		}
		productos[num_item].ncant = cantidad;
		var bandera=productos[num_item].iva/100;
		var importe=(parseFloat(productos[num_item].ultimoCoste)+parseFloat(bandera))*cantidad;
		console.log(productos[num_item].ultimoCoste+bandera);
		//alert('DentroReclaculo:'+producto[nfila]['NPCONIVA']);
		//var importe = cantidad*productos[num_item].precioCiva;
		var id = '#N'+productos[num_item].nfila+'_Importe';
		importe = importe.toFixed(2);
		$(id).html(importe);
		addPedidoTemporal()
		
	
	
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
	if (padre_caja.id_input.indexOf('Proveedor_Fila') >-1){
		padre_caja.id_input = event.originalTarget.id;
	}
	return padre_caja;
}
function before_constructor(caja){
	// @ Objetivo :
	//  Ejecutar procesos para obtener datos despues del construtor de caja.
	//  Estos procesos los indicamos en parametro before_constructor, si hay
	console.log( 'Entro en before');
	console.log(caja);
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
		console.log("entro en unidad fila");
		
		caja.parametros.item_max = productos.length;
		caja.fila = caja.id_input.slice(12);
	}
	if (caja.id_input.indexOf('Proveedor_Fila') >-1){
		console.log("entro en Proveedor_Fila_");
		
		caja.parametros.item_max = productos.length;
		caja.fila = caja.id_input.slice(15);
	}
	
	return caja;	
}

function buscarReferencia(idArticulo, nfila){
	console.log("Entre en buscar referencia");
	fila=nfila-1;
	var parametros = {
		"pulsado"    : 'buscarReferencia',
		"idArticulo":idArticulo,
		"idProveedor":cabecera.idProveedor,
		"fila":fila
	};
	$.ajax({
			data       : parametros,
			url        : 'tareas.php',
			type       : 'post',
			beforeSend : function () {
				console.log('******** estoy en buscarReferencia****************');
			},
			success    :  function (response) {
				console.log('Llegue devuelta respuesta de buscarReferencia');
				var resultado =  $.parseJSON(response); 
				titulo="Modificar referencia";
				html=resultado.html;
				abrirModal(titulo, html);
				
		}
	});
	
	
	
}
