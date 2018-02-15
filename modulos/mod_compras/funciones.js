//Función que controla las acciones que llegan del xml
function controladorAcciones(caja,accion){
	
	switch(accion) {
		
		case 'buscarProveedor':
			console.log("Estoy en buscar proveedor");
			buscarProveedor(caja.darParametro('dedonde'),caja.id_input ,caja.darValor());
		break;
		case 'buscarProducto':
			console.log("Pulse buscar Producto");
			console.log(caja.darParametro('dedonde'));
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
			//Recibe el número de la fila. para poder manipular la referencia de la fila
			var nfila = parseInt(caja.fila)-1;
			var idArticulo=productos[nfila].idArticulo;//Guardamos en una variable el id del articulo
			productos[nfila].crefProveedor = caja.darValor();
			var coste =productos[nfila].ultimoCoste
			addProveedorProducto(productos[nfila].idArticulo, nfila , productos[nfila].crefProveedor, coste, caja.darParametro('dedonde'));
		break;
		case 'Saltar_idProveedor':
			var dato = caja.darValor();
			if (caja.id_input="suNumero"){
				cabecera.suNumero=caja.darValor();
			}
			var d_focus = 'id_proveedor';
			ponerFocus(d_focus);
			
		break;
		case 'Saltar_Proveedor':
			var dato = caja.darValor();
			var d_focus = 'Proveedor';
			ponerFocus(d_focus);
			
		break;
		case 'Saltar_idArticulo':
			var dato = caja.darValor();
			if ( dato.length > 0){
				var d_focus = 'idArticulo';
				ponerFocus(d_focus);
			}
		break;
		case 'Saltar_fecha':
			var dato = caja.darValor();
			var d_focus = 'fecha';
			ponerFocus(d_focus);
		break;
		case 'Saltar_Referencia':
			var dato = caja.darValor();
			var d_focus = 'Referencia';
			ponerFocus(d_focus);
		break;
		case 'Saltar_ReferenciaPro':
			var dato = caja.darValor();
			var d_focus = 'ReferenciaPro';
			ponerFocus(d_focus);
		break;
		case 'Saltar_CodBarras':
			var dato = caja.darValor();
			var d_focus = 'Codbarras';
			ponerFocus(d_focus);
		break;
		case 'addRefProveedor':
			var idArticulo=$('#idArticuloRef').val();
			var fila=$('#numFila').val();
			var coste =productos[fila].ultimoCoste
			console.log(fila);
			nfila=parseInt(fila);
			addProveedorProducto(idArticulo, nfila, caja.darValor(), coste, caja.darParametro('dedonde'));
			cerrarPopUp()
		break;
		case 'addPedidoAlbaran':
		if (caja.darParametro('dedonde')=="albaran"){
			buscarPedido(caja.darValor());
		}
		if (caja.darParametro('dedonde')=="factura"){
			buscarAlbaran(caja.darValor());
		}
			
		break;
		case 'buscarUltimoCoste':
			var nfila = parseInt(caja.fila)-1;
			console.log(nfila);
			var idArticulo=productos[nfila].idArticulo;
			if(valor=""){
				alert("NO HAS INTRODUCIDO NINGÚN COSTE");
			}else{
				addCosteProveedor(idArticulo, caja.darValor(), nfila, caja.darParametro('dedonde'));
			}
			
		break;
		
	}
}
function addCosteProveedor(idArticulo, valor, nfila, dedonde){
	console.log("Entre en addCosteProveedor");
	console.log(idArticulo);
	var parametros ={
		'pulsado':"AddCosteProveedor",
		'idArticulo':idArticulo,
		'valor':valor,
		'idProveedor':cabecera.idProveedor,
		'fecha':cabecera.fecha
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
				console.log(resultado);
				if (resultado.error==1){
					alert("NO PUEDES CAMBIAR EL COSTE DE ESTE PRODUCTO POR SU FECHA");
				}else{
					productos[nfila].ultimoCoste=valor;
					var bandera=productos[nfila].iva/100;
					productos[nfila].importe=(bandera + parseInt (valor))*productos[nfila].ncant;
					console.log(valor);
					var id = '#N'+productos[nfila].nfila+'_Importe';
					importe = productos[nfila].importe.toFixed(2);
					$(id).html(importe);
					
					if (dedonde=="albaran"){
						addAlbaranTemp();
					}
					if (dedonde=="factura"){
						addFacturaTemporal();
					}
					
				}
	
		}
	});
}
function buscarPedido(valor=""){
	var parametros ={
		'pulsado':"BuscarPedido",
		'numPedido':valor,
		'idProveedor':cabecera.idProveedor
	};
	console.log("Entre en buscarPedido");
	console.log(parametros);
	$.ajax({
			data       : parametros,
			url        : 'tareas.php',
			type       : 'post',
			beforeSend : function () {
				console.log('******** estoy en buscar pedido JS****************');
			},
			success    :  function (response) {
				console.log('Llegue devuelta respuesta de buscar pedido');
			var resultado =  $.parseJSON(response); 
			var HtmlPedidos=resultado.html;
			if (valor==""){
				var titulo = 'Listado Pedidos ';
				abrirModal(titulo, HtmlPedidos);
				
			}else{
				console.log(resultado.datos);
				if (resultado.Nitems>0){
					console.log("entre en resultados numero de items");
					
					var bandera=0;
					for(i=0; i<pedidos.length; i++){//recorre todo el array de arrays de pedidos
						console.log("entre en el for");
						var numeroPedido=pedidos[i].Numpedpro;
						var numeroNuevo=resultado['datos'].Numpedpro;
						if (numeroPedido == numeroNuevo){// Si el número del pedido introducido es igual que el número de pedido
						//del array pedidos entonces la bandera es igual a 1
							bandera=bandera+1;
						}
					}
						console.log(bandera);
						if (bandera==0){
							console.log("Hay un resultado");
							var datos = [];
							datos = resultado['datos'];
							console.log(datos);
							var datos = [];
							datos = resultado['datos'];
							pedidos.push(datos);
							productosAdd=resultado.productos;
							var prodArray=new Array();
							var numFila=productos.length+1;
							for (i=0; i<productosAdd.length; i++){ //en el array de arrays de productos metemos los productos de ese pedido
								//~ resultado.productos[i]['nfila']=numFila;
								var prod = new Object();
								prod.ccodbar=resultado.productos[i]['ccodbar'];
								prod.cdetalle=resultado.productos[i]['cdetalle'];
								prod.cref=resultado.productos[i]['cref'];
								prod.crefProveedor=resultado.productos[i]['ref_prov'];
								prod.estado=resultado.productos[i]['estadoLinea'];
								prod.idArticulo=resultado.productos[i]['idArticulo'];
								prod.idpedpro=resultado.productos[i]['idpedpro'];
								prod.iva=resultado.productos[i]['iva'];
								prod.ncant=resultado.productos[i]['ncant'];
								prod.nfila=numFila;
								prod.numPedido=resultado.productos[i]['Numpedpro'];
								prod.nunidades=resultado.productos[i]['nunidades'];
								prod.ultimoCoste=resultado.productos[i]['costeSiva'];
								var bandera2=resultado.productos[i]['iva']/100;
								prod.importe=(bandera2+resultado.productos[i]['costeSiva'])*resultado.productos[i]['nunidades'];
								productos.push(prod);
								prodArray.push(prod);
								numFila++;
							}
							addAlbaranTemp();
							modificarEstadoPedido("albaran", "Facturado", resultado['datos'].Numpedpro, resultado['datos'].idPedido);
							AgregarFilaPedido(datos);
							AgregarFilaProductosAl(prodArray, "albaran");
							cerrarPopUp();
						}
					
				}
			}
	
		}
	});
	

}

function  buscarAlbaran(valor=""){
	console.log("Entre en buscar ALBARAN");
	var parametros ={
		'pulsado':"BuscarAlbaran",
		'numAlbaran':valor,
		'idProveedor':cabecera.idProveedor
	};
	console.log(parametros);
	$.ajax({
			data       : parametros,
			url        : 'tareas.php',
			type       : 'post',
			beforeSend : function () {
				console.log('******** estoy en buscar pedido JS****************');
			},
			success    :  function (response) {
				console.log('Llegue devuelta respuesta de buscar pedido');
			var resultado =  $.parseJSON(response); 
			var HtmlAlbaranes=resultado.html;
			if (valor==""){
				var titulo = 'Listado Albaranes ';
				abrirModal(titulo, HtmlAlbaranes);
				
			}else{
				console.log(resultado.datos);
				console.log(resultado);
				if (resultado.Nitems>0){
					console.log("entre en resultados numero de items");
					
					var bandera=0;
					for(i=0; i<albaranes.length; i++){
						console.log("entre en el for");
						var numeroAlbaran=albaranes[i].Numalbpro;
						var numeroNuevo=resultado['datos'].Numalbpro;
						if (numeroAlbaran == numeroNuevo){// Si el número del pedido introducido es igual que el número de pedido
						//del array pedidos entonces la bandera es igual a 1
							bandera=bandera+1;
						}
					}
						console.log(bandera);
						if (bandera==0){
							console.log("Hay un resultado");
							var datos = [];
							datos = resultado['datos'];
							console.log(datos);
							var datos = [];
							datos = resultado['datos'];
							albaranes.push(datos);
							productosAdd=resultado.productos;
							var prodArray=new Array();
							var numFila=productos.length+1;
							for (i=0; i<productosAdd.length; i++){ //en el array de arrays de productos metemos los productos de ese pedido
								//~ resultado.productos[i]['nfila']=numFila;
								var prod = new Object();
								prod.ccodbar=resultado.productos[i]['ccodbar'];
								prod.cdetalle=resultado.productos[i]['cdetalle'];
								prod.cref=resultado.productos[i]['cref'];
								prod.crefProveedor=resultado.productos[i]['ref_prov'];
								prod.estado=resultado.productos[i]['estadoLinea'];
								prod.idArticulo=resultado.productos[i]['idArticulo'];
								prod.idalbpro=resultado.productos[i]['idalbpro'];
								prod.iva=resultado.productos[i]['iva'];
								prod.ncant=resultado.productos[i]['ncant'];
								prod.nfila=numFila;
								prod.numAlbaran=resultado.productos[i]['Numalbpro'];
								prod.nunidades=resultado.productos[i]['nunidades'];
								prod.ultimoCoste=resultado.productos[i]['costeSiva'];
								var ultimoCoste= parseInt(resultado.productos[i]['costeSiva']);
								var bandera2=parseInt(resultado.productos[i]['iva'])/100;
								var cantidad=parseInt(resultado.productos[i]['nunidades']);
								prod.importe=(bandera2+ultimoCoste)*cantidad;
								
								productos.push(prod);
								prodArray.push(prod);
								numFila++;
							}
							addFacturaTemporal();
							modificarEstadoPedido("factura", "Facturado", resultado['datos'].Numalbpro, resultado['datos'].idAlbaran);
							AgregarFilaPedido(datos);
							AgregarFilaProductosAl(prodArray, "factura");
							cerrarPopUp();
						}
					
				}
			}
	
		}
	});
	
}

function modificarEstadoPedido(dedonde, estado, num="", id=""){
		console.log("Entre en modificar estado pedido");
	//~ if (dedonde=="pedido"){
		//~ var parametros = {
			//~ "pulsado"    : 'modificarEstadoPedido',
			//~ "idPedido":cabecera.idPedido,
			//~ "numPedidoTemp":cabecera.numPedidoTemp,
			//~ "estado" : estado,
			//~ "dedonde": dedonde
		//~ };
	//~ }
	if (dedonde=="albaran"){
		var parametros = {
			"pulsado"    : 'modificarEstadoPedido',
			"idPedido":id,
			//~ "idPedidoTemp":num,
			"estado" : estado,
			"dedonde" : dedonde
		};
	}
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
// add una referencia de un proveedor a un articulo
function addProveedorProducto(idArticulo, nfila, valor, coste, dedonde){
	console.log("ESTOY EN LA FUNCION ADD PROVEEDOR PRODUCTO");
	console.log(nfila);
	var parametros = {
		"pulsado"    : 'addProveedorArticulo',
		"idArticulo" : idArticulo,
		"refProveedor":valor,
		"idProveedor":cabecera.idProveedor,
		"coste":coste
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
				var resultado =  $.parseJSON(response); //Muestra el modal con el resultado html
				//abrirModal(resultado.html);
				productos[nfila].crefProveedor=valor;// pone le valor en el input 
				fila=nfila+1;//sumamos uno a la fila
				var id="#Proveedor_Fila_"+fila;
				$(id).prop('disabled', true);// desactivar el input para que no se pueda cambiar 
				$(id).val(valor);
				$('#enlaceCambio').css("display", "block");
				console.log(id);
				if (dedonde=="pedidos"){
					addPedidoTemporal();//Modificamos los productos del pedido
				}
				if(dedonde=="albaran"){
					addAlbaranTemp();
				}
				if (dedonde=="factura"){
					addFacturaTemporal();
				}
				
	
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
		case 'AgregarAlbaran':
			window.location.href = './albaran.php';
			break;
		case 'AgregarFactura':
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
		
	 }
} 

// Función para buscar un proveedor 
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
					// Si es solo un resultado pone en la cabecera idProveedor ponemos el id devuelto
					//Desactivamos los input para que no se puede modificar y en el nombre mostramos el valor
					//Se oculta el botón del botón buscar
					cabecera.idProveedor=resultado.id;
					
					$('#Proveedor').val(resultado.nombre);
					$('#Proveedor').prop('disabled', true);
					$('#id_proveedor').prop('disabled', true);
					$("#buscar").css("display", "none");
					console.log(dedonde);
					if (dedonde="albaran"){
						comprobarPedidos();
						
					}
					if (dedonde="factura"){
						comprobarAlbaranes();
					}
					mostrarFila()
				}else{
					//Si no mostramos un modal con los proveedores según la busqueda
					var titulo = 'Listado Proveedores ';
					var HtmlProveedores=resultado.html['html']; 
					abrirModal(titulo,HtmlProveedores);
				}
				console.log(resultado);
	
		}
	});
	
}

function comprobarPedidos(){
	var parametros = {
		"pulsado"    : 'comprobarPedido',
		"idProveedor": cabecera.idProveedor
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
				if (resultado==1){
					$('#numPedidoT').css("display", "block");
					$('#numPedido').css("display", "block");
					$('#buscarPedido').css("display", "block");
					$('#tablaPedidos').css("display", "block");
				}
				console.log(resultado);
				
	
		}
	});
	
	
	
}
function comprobarAlbaranes(){
	console.log("Entre en comprobar albaranes");
	var parametros = {
		"pulsado"    : 'comprobarAlbaranes',
		"idProveedor": cabecera.idProveedor
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
				if (resultado==1){
					$('#numPedidoT').css("display", "block");
					$('#numPedido').css("display", "block");
					$('#buscarPedido').css("display", "block");
					$('#tablaPedidos').css("display", "block");
				}
				console.log(resultado);
				
	
		}
	});
}
//Esta funcion se activa cuando en el modal de proveedor pinchamos encima de uno de los proveedores
//Lo que hacemos es volver a la función buscar proveedor pero mandado de busqueda el id del nombre que hemos seleccionado
// De esta manera nos ahorramos procedimientos
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
// Buscar producto es una función que llamamos desde las distintas cajas de busquedas de los productos
//Entra en la función de tareas de buscar productos y le envia los parametros
//Esta función devuelve el número de busquedas
function buscarProductos (id_input,campo, idcaja, busqueda,dedonde){
	console.log(dedonde);
	console.log('FUNCION buscarProductos JS- Para buscar con el campo');
	var parametros = {
		"pulsado"    : 'buscarProductos',
		"cajaInput"	 : id_input,
		"valorCampo" : busqueda,
		"campo"      : campo,
		"idcaja"	 :idcaja,
		"idProveedor": cabecera.idProveedor,
		"dedonde":dedonde
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
			console.log("A DONDE");
			console.log(dedonde);
		
		if (resultado['Nitems']===1){
			// Si recibe un solo resultado cargamos el objeto de productos y lo añadimos a los que ya están
			//Llamamos a la función de add pedido temporal y agregar la fila de producto
			var datos = new Object();
			datos.ccodbar=resultado['datos'][0]['codBarras'];
			datos.cdetalle=resultado['datos'][0]['articulo_name'];
			datos.cref=resultado['datos'][0]['crefTienda'];
			datos.crefProveedor=resultado['datos'][0]['crefProveedor'];
			datos.estado="Activo";
			datos.idArticulo=resultado['datos'][0]['idArticulo'];
			datos.idpedpro=0;
			datos.iva=resultado['datos'][0]['iva'];
			datos.ncant=1;
			datos.nfila=productos.length+1;
			datos.nunidades=1;
			if (resultado['datos'][0]['coste']>0){
				var ultimoCoste= parseFloat(resultado['datos'][0]['coste']);
			}else{
				var ultimoCoste= parseFloat(resultado['datos'][0]['ultimoCoste']);
			}
			
			datos.ultimoCoste=ultimoCoste.toFixed(2);
			var ivares =(resultado['datos'][0]['iva']/100);
			var importe =(ivares+ultimoCoste)*1;
			console.log(importe);
			//~ var importe =resultado['datos'][0]['ultimoCoste']*1;
			datos.importe=importe.toFixed(2);
			
			productos.push(datos);
			
			if (dedonde=="pedidos"){
				addPedidoTemporal();
			}
			if (dedonde=="albaran"){
				addAlbaranTemp();
			}
			if (dedonde=="factura"){
				addFacturaTemporal();
			}
			
			AgregarFilaProductosAl(datos, dedonde);
			
			if(resultado['datos'][0]['fechaActualizacion']>cabecera.fecha){
				alert("LA FECHA DEL COSTE DEL PRODUCTO ES SUPERIOR A LA FECHA ESCRITA");
			}
		}else{
			// Si no mandamos el resultado html a abrir el modal para poder seleccionar uno de los resultados
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

//Funcion que agrega una fila a la tabla productos 
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
			$("#tabla").prepend(nuevafila);// añadir la fila como primera de la tabla
			
			
		}
	});
}
function addAlbaranTemp(){
	console.log('FUNCION Añadir albaran temporal JS-AJAX');
	var parametros = {
		"pulsado"    : 'addAlbaranTemporal',
		"idAlbaranTemp": cabecera.idAlbaranTemp,
		"idUsuario":cabecera.idUsuario,
		"idTienda":cabecera.idTienda,
		"estado":cabecera.estado,
		"idAlbaran":cabecera.idAlbaran,
		"numAlbaran":cabecera.numAlbaran,
		"fecha":cabecera.fecha,
		"productos":productos,
		"pedidos":pedidos,
		"idProveedor":cabecera.idProveedor,
		"suNumero":cabecera.suNumero
	};
	console.log("ESTOY EN AÑADIR ALBARAN");
	console.log(parametros);
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

function addFacturaTemporal(){
	console.log('FUNCION Añadir albaran temporal JS-AJAX');
	var parametros = {
		"pulsado"    : 'addFacturaTemporal',
		"idFacturaTemp": cabecera.idFacturaTemp,
		"idUsuario":cabecera.idUsuario,
		"idTienda":cabecera.idTienda,
		"estado":cabecera.estado,
		"idFactura":cabecera.idFactura,
		"numFactura":cabecera.numFactura,
		"fecha":cabecera.fecha,
		"productos":productos,
		"albaranes":albaranes,
		"idProveedor":cabecera.idProveedor,
		"suNumero":cabecera.suNumero
	};
	console.log("ESTOY EN AÑADIR FACTURA");
	console.log(parametros);
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

			
			if (resultado.existe == 0){
				history.pushState(null,'','?tActual='+resultado.id);
				cabecera.idFacturaTemp=resultado.id;
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
// Añadir un pedido temporal
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
			console.log('******** estoy en añadir PEDIDO temporal JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de añadir PEDIDO temporal');
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
		jQuery('#'+destino_focus.toString()).focus(); 
	}, 50); 

}
function escribirProductoSeleccionado(campo,cref,cdetalle,ctipoIva,ccodebar,ultimoCoste,id , dedonde){
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
		var bandera=ctipoIva/100;
		var importe =(bandera+ultimoCoste)*1;
		datos.importe=importe.toFixed(2);
		var ultimoCoste= parseFloat(ultimoCoste);
		datos.ultimoCoste=ultimoCoste.toFixed(2);
		productos.push(datos);
		if(dedonde=="pedidos"){
			addPedidoTemporal();
		}
		if (dedonde=="albaran"){
			addAlbaranTemp();
		}
		if (dedonde=="factura"){
			addFacturaTemporal();
		}
		
		AgregarFilaProductosAl(datos, dedonde);
		var num_item=datos.nfila;
		resetCampo(campo);
		var campo='Unidad_Fila_'+num_item;
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
		if (valor=="pedidos"){
			addPedidoTemporal();
		}
		if (valor=="albaran"){
			addAlbaranTemp();
		}
		if (valor=="factura"){
			addFacturaTemporal();
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
			
	if (valor=="pedidos"){
		addPedidoTemporal();
	}
	if (valor=="albaran"){
		addAlbaranTemp();
	}
	if (valor="factura"){
		addFacturaTemporal();
	}
	
	
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
		if (dedonde=="pedidos"){
			addPedidoTemporal();
		}
		if (dedonde=="albaran"){
			addAlbaranTemp();
		}
		if (dedonde=="factura"){
			addFacturaTemporal();
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
	if (padre_caja.id_input.indexOf('Unidad_Fila') >-1){
		padre_caja.id_input = event.originalTarget.id;
	}
	if (padre_caja.id_input.indexOf('Proveedor_Fila') >-1){
		padre_caja.id_input = event.originalTarget.id;
	}
	if (padre_caja.id_input.indexOf('ultimo_coste') >-1){
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
	if (caja.id_input.indexOf('ultimo_coste') >-1){
		console.log("entro en ultimo_coste_");
		caja.parametros.item_max = productos.length;
		caja.fila = caja.id_input.slice(13);
		
	}
	
	return caja;	
}

function buscarReferencia(idArticulo, nfila){
	console.log("Entre en buscar referencia");
	fila=nfila-1;
	var coste=productos[fila].ultimoCoste;
	var parametros = {
		"pulsado"    : 'buscarReferencia',
		"idArticulo":idArticulo,
		"idProveedor":cabecera.idProveedor,
		"fila":fila,
		"coste":coste
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
function mostrarFila(){
	console.log("mostrar fila");
	$("#Row0").removeAttr("style") ;
	console.log("realizo funcion");
}
