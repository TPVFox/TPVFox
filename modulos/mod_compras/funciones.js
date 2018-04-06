//Función que controla las acciones que llegan del xml

function controladorAcciones(caja,accion, tecla){
	switch(accion) {
		case 'buscarProveedor':
			console.log("Estoy en buscar proveedor");
			if( caja.darValor()=="" && caja.id_input=="id_proveedor"){
				var d_focus="Proveedor";
				ponerFocus(d_focus);
			}else{
				buscarProveedor(caja.darParametro('dedonde'),caja.id_input ,caja.darValor());
			}
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
			productos[nfila].nunidades = caja.darValor();
		
			console.log(caja.fila);
			recalculoImporte(productos[nfila].nunidades, nfila, caja.darParametro('dedonde'));
			console.log(caja.darParametro('dedonde'));
			if (caja.tipo_event !== "blur"){
				if (caja.darParametro('dedonde') == "pedidos"){
					d_focus="Referencia";
					ponerFocus(d_focus);
				}else{
					d_focus='ultimo_coste_'+parseInt(caja.fila);
					ponerSelect(d_focus);
					
				}
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
		console.log(tecla);
			var dato = caja.darValor();
			var d_focus = 'id_proveedor';
			if (caja.id_input=="suNumero"){
				cabecera.suNumero=caja.darValor();
			}
			if (caja.id_input=="Proveedor"){
				console.log(dato.length);
				if ( dato.length <= 0){
					ponerFocus(d_focus);
				}
			}else{
				//dependiendo de si es de albaran y factura o la tecla en la que se pulse cambia el focos 
				//Esto lo hago por que albaranes y facturas tengo la caja de su numero y en pedidos no , como son los 
				// mismos input por eso se hace estos if 
				if (caja.darParametro('dedonde')=="albaran" || caja.darParametro('dedonde')=="factura"){
					if (caja.id_input=="fecha" & tecla==39 || caja.id_input=="fecha" & tecla==9 ){
						var nuevofocus="suNumero";
						ponerFocus(nuevofocus);
					}else{
						 if ($('#id_proveedor').prop("disabled") == true) {
							 var nuevofocus="idArticulo";
							 ponerFocus(nuevofocus);
						 }
						ponerFocus(d_focus);
					}
				}else{
					 ponerFocus(d_focus);
				}
				
			}
			
			
		break;
		case 'Saltar_Proveedor':
			var dato = caja.darValor();
			var d_focus = 'Proveedor';
			ponerFocus(d_focus);
			
		break;
		case 'Saltar_idArticulo':
			var dato = caja.darValor();
			
			var d_focus = 'idArticulo';
			ponerFocus(d_focus);
			
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
		case 'Saltar_Descripcion':
			var dato = caja.darValor();
			var d_focus = 'Descripcion';
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
			buscarAdjunto(caja.darParametro('dedonde'), caja.darValor());
		break;
		
		case 'buscarUltimoCoste':
			
			var nfila = parseInt(caja.fila)-1;
			
			if (caja.tipo_event !== "blur"){
				
			//console.log("algo:"+productos[nfila].ultimoCoste);
			var costeAnt=productos[nfila].ultimoCoste;
			
		//	alert(productos[nfila].ultimoCoste);
			var idArticulo=productos[nfila].idArticulo;
		
			if (costeAnt===caja.darValor()){
				var d_focus = 'Referencia';
				ponerFocus(d_focus);
				//alert("precios iguales");
			}else{
			
			if(valor=""){
				alert("NO HAS INTRODUCIDO NINGÚN COSTE");
			}else{
					productos[nfila].CosteAnt=costeAnt;
						addCosteProveedor(idArticulo, caja.darValor(), nfila, caja.darParametro('dedonde'));
						if (caja.tipo_event !== "blur"){
						var d_focus = 'Referencia';
						ponerFocus(d_focus);
				}
				
			
			}
		}
		}
		
			
		
			
		break;
		
	}
}
function addCosteProveedor(idArticulo, valor, nfila, dedonde){
	//~ @Objetivo: Añadir o modificar el coste de un producto
	//~ @Parametros: 
	//~ idArticulo: el id del articulo del producto
	//~ idProveedor: el id del proveedor
	//~ valor: valor nuevo 
	//~ dedonde: donde estamos, si en albaranes o facturas 
	//~ nfila: número de la fila que estamos cambiando
	console.log("Entre en addCosteProveedor");
		
	//~ var costeAnt=productos[nfila].ultimoCoste;
	//~ productos[nfila].CosteAnt=costeAnt;

	productos[nfila].importe=parseFloat(valor)*productos[nfila].nunidades;
	var id = '#N'+productos[nfila].nfila+'_Importe';
	importe = productos[nfila].importe.toFixed(2);
productos[nfila].ultimoCoste=valor;	
	$(id).html(importe);
	addTemporal(dedonde);
	
	//~ var parametros ={
		//~ 'pulsado':"AddCosteProveedor",
		//~ 'idArticulo':idArticulo,
		//~ 'valor':valor,
		//~ 'idProveedor':cabecera.idProveedor,
		//~ 'fecha':cabecera.fecha
	//~ };
	//~ $.ajax({
			//~ data       : parametros,
			//~ url        : 'tareas.php',
			//~ type       : 'post',
			//~ beforeSend : function () {
				//~ console.log('******** estoy en buscar clientes JS****************');
			//~ },
			//~ success    :  function (response) {
				//~ console.log('Llegue devuelta respuesta de buscar clientes');
				//~ var resultado =  $.parseJSON(response); 
				//~ console.log(resultado);
				//~ if (resultado.error==1){
					//~ alert("NO PUEDES CAMBIAR EL COSTE DE ESTE PRODUCTO POR SU FECHA");
				//~ }else{
					//~ productos[nfila].ultimoCoste=valor;
					//~ //var bandera=productos[nfila].iva/100;
					//~ productos[nfila].importe=parseFloat(valor)*productos[nfila].nunidades;
					//~ var id = '#N'+productos[nfila].nfila+'_Importe';
					//~ importe = productos[nfila].importe.toFixed(2);
					//~ productos[nfila].CosteAnt=costeAnt;
					//~ $(id).html(importe);
					//~ addTemporal(dedonde);
				//~ }
		//~ }
	//~ });
}

function buscarAdjunto(dedonde, valor=""){
	//@Objetivo: cada vez que vamos a adjuntar un pedido o un albaran a una factura o albaran ejecutamos esta función que 
	//carga tanto los productos del adjunto como realiza la comprobación de si ya existe ....
	//@parametros: 
	//dedonde:desde donde estamos ejecutando la función
	//valor: numero de pedido o albarán que vamos a adjuntar
	console.log("Entre en buscarAdjunto");
	
	var parametros ={
		'pulsado':'buscarAdjunto',
		'numReal':valor,
		'idProveedor':cabecera.idProveedor,
		'dedonde':dedonde
	};
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
			
			if (valor==""){ // Si el valor esta vacio mostramos el modal con los pedidos de ese proveedor
				if (dedonde=="albaran"){
					var titulo = 'Listado Pedidos ';
				}else{
					var titulo= 'Listado Albaranes';
				}
				console.log(resultado.datos);
				abrirModal(titulo, HtmlPedidos);
				
			}else{
				console.log(resultado.datos);
				if (resultado.Nitems>0){
					console.log("entre en resultados numero de items");
					var bandera=0;
					if (dedonde=="albaran"){
						var adjuntos=pedidos;
					}else{
						var adjuntos=albaranes;
					}
					for(i=0; i<adjuntos.length; i++){//recorre todo el array de arrays de pedidos
						console.log("entre en el for");
						//~ if (dedonde=="albaran"){
							//~ var numeroReal=adjuntos[i].Numpedpro;
						//~ }else{
							//~ var numeroReal=adjuntos[i].Numalbpro;
						//~ }
						var numeroReal=adjuntos[i].NumAdjunto;
						var numeroNuevo=resultado['datos'].NumAdjunto;
						if (numeroReal == numeroNuevo){// Si el número del pedido introducido es igual que el número de pedido
						//del array pedidos entonces la bandera es igual a 1
							bandera=bandera+1;
						}
					}
					
						if (bandera==0){
						
							var datos = [];
							datos = resultado['datos'];
							
							var datos = [];
							datos = resultado['datos'];
							n_item=parseInt(adjuntos.length)+1;
							datos.nfila=n_item;
							if (dedonde=="albaran"){
								pedidos.push(datos);
							}else{
								albaranes.push(datos);
							}
							productosAdd=resultado.productos;
							var prodArray=new Array();
							var numFila=productos.length+1;
							for (i=0; i<productosAdd.length; i++){ //en el array de arrays de productos metemos los productos de ese pedido
								// cargamos todos los datos en un objeto y por ultimo lo añadimos a los productos que ya tenemos
								var prod = new Object();
								prod.ccodbar=resultado.productos[i]['ccodbar'];
								prod.cdetalle=resultado.productos[i]['cdetalle'];
								prod.cref=resultado.productos[i]['cref'];
								prod.crefProveedor=resultado.productos[i]['ref_prov'];
								prod.estado=resultado.productos[i]['estadoLinea'];
								prod.idArticulo=resultado.productos[i]['idArticulo'];
								prod.iva=resultado.productos[i]['iva'];
								prod.ncant=resultado.productos[i]['ncant'];
								prod.nfila=numFila;
								prod.nunidades=resultado.productos[i]['nunidades'];
								prod.ultimoCoste=resultado.productos[i]['costeSiva'];
								prod.importe=resultado.productos[i]['costeSiva']*resultado.productos[i]['nunidades'];
								if (dedonde=="albaran"){
									prod.numPedido=resultado.productos[i]['Numpedpro'];
									prod.idpedpro=resultado.productos[i]['idpedpro'];
								}else{
									prod.numAlbaran=resultado.productos[i]['Numalbpro'];
									prod.idalbpro=resultado.productos[i]['idalbpro'];
								}
								var numAdjunto=resultado['datos'].NumAdjunto;
								var idAdjunto=resultado['datos'].idAdjunto;
								productos.push(prod);
								prodArray.push(prod);
								numFila++;
							}
							addTemporal(dedonde);
							modificarEstado(dedonde, "Facturado",  idAdjunto);
							//Agregamos una nueva fila con los datos principales de pedidos
							AgregarAdjunto(datos, dedonde);
							//Agregamos los productos de el pedido seleccionado
							AgregarFilaProductos(prodArray, dedonde);
							//Cierro el modal aqui por que cuando selecciono un pedido del modal llamo a esta misma funcion
							//Pero metiendo el numero del pedido de esta manera el valor de busqueda ya es un numero y no vuelve 
							// a mostrar el modal si no que entra en la segunda parte del if que tenemos mas arriba 
							cerrarPopUp();
							
						}
					
				}
			}
	
		}
	});
}

function modificarEstado(dedonde, estado, id=""){
	//~ @Objetivo: Modificar el estado según el id que llegue y de donde para poder filtrar
	//~ @Parametros : el estado se envia en la función
		console.log("Entre en modificar estado pedido");
		var parametros = {
			"pulsado"    : 'modificarEstado',
			"id":id,
			"estado" : estado,
			"dedonde" : dedonde
		};
	console.log(parametros);
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en Modificar estado pedido js****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de estado pedido js');
		}
	});
}
function addProveedorProducto(idArticulo, nfila, valor, coste, dedonde){
	//@Objetivo: añadir una referencia a un proveedor articulo o cambiarla en caso de que exista
	//@parametros;
	//idArticulo: id del articulo
	//nfila:Número de la fila
	//valor: referencia que le vamos a poner 
	//coste : coste de la referencia
	//dedonde: de donde venimos di pedidos , albaranes o facturas
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
				if (valor){
					$(id).prop('disabled', true);// desactivar el input para que no se pueda cambiar 
					$(id).val(valor);
					//$("#enlaceCambio"+fila).prop('disabled', true);
					$('#enlaceCambio'+fila).css("display", "inline");
					var d_focus='idArticulo';
					ponerFocus(d_focus);
				
			}
				addTemporal(dedonde);
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
function imprimir(id, dedonde, idTienda){
	//~ @Objetivo: Imprimir el documento que se ha seleccionado
	//~ @parametros: 
		//~ id: id del documento
		//~ dedonde: de donde es para poder filtrar
		//~ idTienda : id de la tienda 
	var parametros = {
		"pulsado"    : 'datosImprimir',
		"dedonde":dedonde,
		"id":id,
		"idTienda":idTienda
	};
	$.ajax({
			data       : parametros,
			url        : 'tareas.php',
			type       : 'post',
			beforeSend : function () {
				console.log('******** estoy en datos Imprimir JS****************');
			},
			success    :  function (response) {
				 var resultado =  $.parseJSON(response); 
				 window.open(resultado);// Abre una nuvea pestaña con el documento pdf que se generó anteriormente
		}
		
	});
	
		
}
function buscarProveedor(dedonde, idcaja, valor='', popup=''){
	//~ @Objetivo: Buscar y comprobar que la busqueda de proveedor es correcta 
	//~ @parametros: 
	//~ dedonde->De donde venimos 
	//~ idCaja->LAutilizamos en tareas para comprobaciones
	//~ valor-> valor que vamos a buscar
	// popup-> si viene de popup cerramos la ventana modal
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
				console.log(resultado);
				if (resultado.Nitems==2){
					alert("El id del proveedor no existe");
					document.getElementById(idcaja).value='';
					//resetCampo(idcaja);
				}
				if (popup=="popup"){
					cerrarPopUp();
				}
				if (resultado.Nitems==1){
					// Si es solo un resultado pone en la cabecera idProveedor ponemos el id devuelto
					//Desactivamos los input para que no se puede modificar y en el nombre mostramos el valor
					//Se oculta el botón del botón buscar
					cabecera.idProveedor=resultado.id;
					$('#id_proveedor').val(resultado.id);
					$('#Proveedor').val(resultado.nombre);
					$('#Proveedor').prop('disabled', true);
					$('#id_proveedor').prop('disabled', true);
					$("#buscar").css("display", "none");
					
					//Dendiendo de donde venga realizamos unas funciones u otras
					if (dedonde=="albaran"){
						//comprobarPedidos();
						comprobarAdjunto(dedonde);
					}
					if (dedonde=="factura"){
						//comprobarAlbaranes();
						comprobarAdjunto(dedonde);
					}
					if (dedonde=="pedidos"){
						// Si viene de pedido ponemos el foco en idArticulo ya que pedidos no tiene que comprobar nada 
						//Para poder empezar a meter articulos
						ponerFocus("idArticulo");
					}
					mostrarFila();
					
				}else{
					//Si no mostramos un modal con los proveedores según la busqueda
					var titulo = 'Listado Proveedores ';
					var HtmlProveedores=resultado.html['html']; 
					abrirModal(titulo,HtmlProveedores);
				}
				
	
		}
	});
	
}
function comprobarAdjunto(dedonde){
	//@Objetivo: comprobar si el proveedor tiene algun pedido o albaran Guardado que se pueda adjuntar tanto a la factura como al albaran
	console.log("Entre en adjunto proveedor");
	var parametros = {
		"pulsado"    :'comprobarAdjunto',
		"idProveedor": cabecera.idProveedor,
		"dedonde":dedonde
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
				if (resultado == 1){
					console.log("entre en las opciones");
						$('#tablaAl').css("display", "block");
					$('#numPedidoT').css("display", "block");
					$('#numPedido').css("display", "block");
					$('#buscarPedido').css("display", "block");
					$('#tablaPedidos').css("display", "block");
					ponerFocus('numPedido');
				}else{
					ponerFocus('idArticulo');
				}
				console.log(resultado);
				
	
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
	//@Objetivo: 
	// Buscar producto es una función que llamamos desde las distintas cajas de busquedas de los productos
	//Entra en la función de tareas de buscar productos y le envia los parametros
	//Esta función devuelve el número de busquedas
	console.log('FUNCION buscarProductos JS- Para buscar con el campo');
	var parametros = {
		"pulsado"    : 'buscarProductos',
		"id_input"	 : id_input,
		"valorCampo" : busqueda,
		"campo"      : campo,
		"idcaja"	 :idcaja,
		"idProveedor": cabecera.idProveedor,
		"dedonde":dedonde
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
			// Si recibe un solo resultado cargamos el objeto de productos y lo añadimos a los que ya están
			//Llamamos a la función de add pedido temporal y agregar la fila de producto
			var datos = new Object();			
			datos.ccodbar=resultado['datos'][0]['codBarras'];
			datos.cdetalle=resultado['datos'][0]['articulo_name'];
			datos.cref=resultado['datos'][0]['crefTienda'];
			datos.crefProveedor=resultado['datos'][0]['crefProveedor'];
			datos.estado="Activo";
			datos.idArticulo=resultado['datos'][0]['idArticulo'];
			//datos.idpedpro=0;
			datos.iva=resultado['datos'][0]['iva'];
			datos.ncant=1;
			datos.nfila=productos.length+1;
			n_item=parseInt(productos.length)+1;
			datos.nunidades=1;
			
			if (resultado['datos'][0]['coste']>0){
				var ultimoCoste= parseFloat(resultado['datos'][0]['coste']);
			}else{
				var ultimoCoste= parseFloat(resultado['datos'][0]['ultimoCoste']);
			}
			datos.ultimoCoste=ultimoCoste.toFixed(4);
			datos.importe=ultimoCoste.toFixed(2);
			productos.push(datos);
			var campo='Unidad_Fila_'+n_item;
			addTemporal(dedonde)
			document.getElementById(id_input).value='';
			//resetCampo(id_input);
			AgregarFilaProductos(datos, dedonde, campo);
			if(resultado['datos'][0]['fechaActualizacion']>cabecera.fecha){
				alert("LA FECHA DEL COSTE DEL PRODUCTO ES SUPERIOR A LA FECHA ESCRITA");
			}
			ponerSelect(campo);
			if (dedonde=="factura"){
				$("#tablaAl").hide();
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
					$('#cajaBusqueda').focus();
			}
		}
	}
	});
}
}
function AgregarFilaProductos(productos, dedonde='', campo=''){
	//@objetivo: 
	//Agregar la fila de productos y poner select al campo que corresponde en la fila
	console.log("Estoy en agregar fila productos albaran");
	if (productos.length>1){
		producto=productos.reverse();
	}
	console.log(dedonde);
	var parametros = {
		"pulsado"    : 'htmlAgregarFilasProductos',
		"productos" : productos,
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
			ponerSelect(campo);
					if(albaranes.length>0){
				bloquearInput();
		}

		}
	});
}
function bloquearInput(){
	console.log("Elementos js");
	$('#Row0').css('display', 'none');
	$('.unidad').attr("readonly","readonly");
}
function addTemporal(dedonde=""){
	//@Objetivo: añadir un temporal , dependiendo de donde venga se cargan unos parámetros distintos
	//@parámetros:
	//dedonde: de donde venimos , pedidos, albaran, factura
	console.log('FUNCION Añadir temporal JS-AJAX');
	if (dedonde=="pedidos"){
		var pulsado='addPedidoTemporal';
	}
	if (dedonde=="albaran"){
		var pulsado='addAlbaranTemporal';
	}
	if (dedonde=="factura"){
		var pulsado='addFacturaTemporal';
	}
	var parametros = {
		"pulsado"    : pulsado,
		"idTemporal": cabecera.idTemporal,
		"idUsuario":cabecera.idUsuario,
		"idTienda":cabecera.idTienda,
		"estado":cabecera.estado,
		"idReal":cabecera.idReal,
		"fecha":cabecera.fecha,
		"productos":productos,
		"idProveedor":cabecera.idProveedor
	};
	if (dedonde=="albaran"){
		parametros['pedidos']=pedidos;
		parametros['suNumero']=cabecera.suNumero;
	}
	if (dedonde=="factura"){
		parametros['albaranes']=albaranes;
		parametros['suNumero']=cabecera.suNumero;
	}
	console.log(parametros);
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
			if (resultado.error){
				alert(resultado.consulta);
			}else{
				var HtmlClientes=resultado.html;//$resultado['html'] de montaje html
				console.log(resultado.id.id);
				if (resultado.existe == 0){
					history.pushState(null,'','?tActual='+resultado.id);
					cabecera.idTemporal=resultado.id;
				}
				// Creo funcion para restear totales.	
				resetearTotales();
				
				total = parseFloat(resultado['totales']['total'])
				$('.totalImporte').html(total.toFixed(2));
				$('#tabla-pie  > tbody ').html(resultado['htmlTabla']);
				
				if (dedonde=="factura"){
					var importe= document.getElementById("Eimporte").value;
					if (importe>0){
						insertarImporte(total);
					}
				}
				// Ahora pintamos pie de ticket.
				//~ if (resultado['totales']['total'] > 0 ){
					// Quiere decir que hay datos a mostrar en pie.
					//~ pintamosTotales(resultado);
				//~ }
			}
		}
	});
}

function ponerFocus (destino_focus){
	// @ Objetivo:
	// 	Poner focus a donde nos indique el parametro, que debe ser id queremos apuntar.
	console.log('Entro en enviar focus de :'+destino_focus);
	//console.log(destino_focus.toString());
	
	setTimeout(function() {   //pongo un tiempo de focus ya que sino no funciona correctamente
		jQuery('#'+destino_focus.toString()).focus(); 
		
	}, 50); 
	
}
function ponerSelect(destino_focus){
	//@objetivo:
	//seleccionar la cantidad 
	console.log('Entro en enviar select de :'+destino_focus);
	console.log(destino_focus.toString());
	jQuery('#'+destino_focus.toString()).select(); 
	

}

function escribirProductoSeleccionado(campo,cref,cdetalle,ctipoIva,ccodebar,ultimoCoste,id , dedonde, crefProveedor){
	//@Objetivo:
	//Función para escribir el producto seleccionado del modal
	//LO que hacemos en la función es que recibimos los campos del producto que hemos seleccionado y creamos un objeto
	//En el que vamos metiendo los campos (algunos como importe hay que calcularlos)
	//Y dependiendo de donde venga el modal llamamos a una función u otra de esta manera utilizamos esta función estemos donde estemo
	if (dedonde=="factura"){
		$("#tablaAl").hide();
	}
	console.log(datos);
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
		datos.crefProveedor=crefProveedor;
		var bandera=ctipoIva/100;
		var importe =(bandera+ultimoCoste)*1;
		datos.importe=importe.toFixed(2);
		var ultimoCoste= parseFloat(ultimoCoste);
		datos.ultimoCoste=ultimoCoste.toFixed(4);
		productos.push(datos);
		addTemporal(dedonde);
		var num_item=datos.nfila;
		var campo1='Unidad_Fila_'+num_item;
		AgregarFilaProductos(datos, dedonde,  campo1);
		document.getElementById(campo).value='';
	//	resetCampo(campo);
		var campo='Unidad_Fila_'+num_item;
		cerrarPopUp(campo);
		
}
//~ //Elimina el contenido del campo
//~ function resetCampo(campo){
	
	//~ console.log('Entro en resetCampo '+campo);
	//~ document.getElementById(campo).value='';
	//~ return;
//~ }
function eliminarFila(num_item, valor=""){
	//@Objetivo:
	//Función para cambiar el estado del producto , deja en estado Eliminado el producto
	console.log("entre en eliminar Fila Producto");
	var line;
	num=num_item-1;
	line = "#Row" + productos[num].nfila;
	productos[num].estado= 'Eliminado';
	$(line).addClass('tachado');
	$(line + "> .eliminar").html('<a onclick="retornarFila('+num_item+', '+"'"+valor+"'"+');"><span class="glyphicon glyphicon-export"></span></a>');
	$("#N" +productos[num].nfila + "_Unidad").prop("disabled", true);
	addTemporal(valor);
	
}
function eliminarAdjunto(numRegistro, dedonde, nfila){
	//@Objetivo: esta acción se ejecuta cuando eleiminamos un pedio o albaran de albaranes o facturas 
	//pone la fila de los datos del pedido y albaran como eliminada y todos sus productos
	//@parámetros:
	//numRegistro: número tanto del pedido como del alabarán
	//dedonde: de donde venimos , pedidos , albaran o factura
	//nfila: número de la fila del pedido o albarán
	console.log("entre en eliminar Fila");
	var line;
	num=nfila-1;
	if (dedonde=="factura"){
		line = "#lineaP" + albaranes[num].nfila;
		albaranes[num].estado= 'Eliminado';
		var idAdjunto=albaranes[num].idAlbaran;
	}
	if (dedonde=="albaran"){
		line = "#lineaP" + pedidos[num].nfila;
		pedidos[num].estado= 'Eliminado';
		
		var idAdjunto=pedidos[num].idPedido;
	}
	$(line).addClass('tachado');
	$(line + "> .eliminar").html('<a onclick="retornarAdjunto('+numRegistro+', '+"'"+dedonde+"'," + nfila+');"><span class="glyphicon glyphicon-export"></span></a>');
		for(i=0;i<productos.length; i++){
			if (dedonde=="albaran"){
				var numProducto=productos[i].numPedido;
				
			}else{
				var numProducto=productos[i].numAlbaran;
			}
			if (numRegistro == numProducto){
					eliminarFila(productos[i].nfila, "bandera");
			}
		}
		
		modificarEstado(dedonde, "Guardado", numRegistro, idAdjunto);
		addTemporal(dedonde);
}
function retornarFila(num_item, valor=""){
	// @Objetivo :
	// Es pasar un producto eliminado a activo.
	console.log("entre en retornar fila producto");
	var line;
	num=num_item-1;
	line = "#Row" +productos[num].nfila;
	console.log(line);
	// Nueva Objeto de productos.
	productos[num].estado= 'Activo';
	console.log(productos[num]);
	
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
	addTemporal(valor);
}

function retornarAdjunto(numRegistro, dedonde, nfila){
	//@Objetivo: activar pedido o albaran con estado eliminado en albaran o factura
	//@Parámetros:
	//nunRegitro: número del pedido o albarán a activar
	//dedonde: de donde venimos si de albaran o factura
	//nfila: número de la fila
	console.log("entre en retornar fila adjunto");
	var estado="Guardado";
	var line;
	num=nfila-1;
	if (dedonde=="factura"){
		line = "#lineaP" + albaranes[num].nfila;
		albaranes[num].estado= 'activo';
		var idAdjunto=albaranes[num].idAlbaran;
	}
	if (dedonde=="albaran"){
		line = "#lineaP" + pedidos[num].nfila;
		pedidos[num].estado= 'activo';
		var idAdjunto=pedidos[num].idPedido;
	}
	
	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarAdjunto('+numRegistro+' , '+"'"+dedonde+"', "+nfila+');"><span class="glyphicon glyphicon-trash"></span></a>');
	for(i=0;i<productos.length; i++){
				if (dedonde=="albaran"){
					var numProducto=productos[i].numPedido;
				}else{
					var numProducto=productos[i].numAlbaran;
				}
				
				if (numRegistro==numProducto){
					retornarFila(productos[i].nfila, "bandera");
				}
	}
		num=nfila-1;
		modificarEstado(dedonde, "Facturado", numRegistro, idAdjunto);
		addTemporal(dedonde);
}
function recalculoImporte(cantidad, num_item, dedonde=""){
	
	// @ Objetivo:
	// Recalcular el importe de la fila, si la cantidad cambia.
	// @ Parametros:
	//	cantidad -> Valor ( numerico) de input unidades.
	//	num_item -> El numero que indica el producto que modificamos.
	console.log('Estoy en recalculoImporte');
	console.log(num_item);
	
		if (productos[num_item].nunidades == 0 && cantidad != 0) {
			retornarFila(num_item+1, dedonde);
		} else if (cantidad == 0 ) {
			eliminarFila(num_item+1, dedonde);
		}
		productos[num_item].nunidades = cantidad;
		var bandera=productos[num_item].iva/100;
		var importe=parseFloat(productos[num_item].ultimoCoste)*cantidad;
		console.log(productos[num_item].ultimoCoste+bandera);
		var id = '#N'+productos[num_item].nfila+'_Importe';
		importe = importe.toFixed(2);
		console.log(importe);
		productos[num_item].importe=importe;
		$(id).html(importe);
		addTemporal(dedonde);
	
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
		//caja.parametros.dedonde = 'popup';
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
		caja.fila = caja.id_input.slice(13);
		caja.parametros.item_max = productos.length;
		
		
	}
	
	return caja;	
}
function buscarReferencia(idinput){
	//@Objetivo:
	// modificar el input del id para que se pueda modificar la referencia del proveedor articulo
	console.log("Entre en buscar referencia");
	$("#"+idinput).prop('disabled', false);
}
function AgregarAdjunto(datos, dedonde){
	//@	Objetivo: 
	//Esta función la utilizamos desde albarán o desde factura 
	//Desde albaran es para agregar la fila del pedido seleccionado y desde factura para agregar el albaran
	console.log("Estoy en agregar fila Pedido");
	var parametros = {
		"pulsado"    : 'htmlAgregarFilaAdjunto',
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
			console.log(resultado);
			var nuevafila = resultado['html'];
			$("#tablaPedidos").prepend(nuevafila);
			$('#numPedido').focus(); 
			$('#numPedido').val(""); 
			
		}
	});
}
function mostrarFila(){
	//@Objetivo: Mostrar la fila principal de articulos
	console.log("mostrar fila");
	$("#Row0").removeAttr("style") ;
	$('#idArticulo').focus();
}

function mover_up(fila,prefijo){
	console.log("entro en mover up");
	console.log(fila);
	
	sobreFilaCraton(fila);
	var d_focus = prefijo+fila;
	
	console.log(d_focus);
	ponerSelect(d_focus);
}
function mover_down(fila,prefijo){
	sobreFilaCraton(fila);
	var d_focus = prefijo+fila;
		if ( document.getElementById(d_focus) ) {
			ponerSelect(d_focus);
		}else{
			var d_focus = 'idArticulo';
			ponerFocus(d_focus);
		}
}
function sobreFila(cont){
	$('#Fila_'+cont).css('background-color','lightblue');
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
function pintamosTotales (DesgloseTotal) {
	// Quiere decir que hay datos a mostrar en pie.
	total = parseFloat(DesgloseTotal['totales']['total']) // varible global.
	$('.totalImporte').html(total.toFixed(2));
	// Ahora tengo que pintar los ivas.
	var desgloseIvas = [];
	desgloseIvas.push(DesgloseTotal['totales']['desglose']);
	console.log(desgloseIvas);
	// Ahora recorremos array desglose
	desgloseIvas.forEach(function(desglose){
		console.log('Entro foreah');
		// mostramos los tipos ivas , bases y importes.
		var tipos = Object.keys(desglose);
		console.log(desglose);
		//~ console.log(index);
		for (index in tipos){
			var iva = tipos[index];
			console.log(desglose[iva].base);
			console.log(parseInt(iva));
			$('#line'+parseInt(iva)).css('display','');
			$('#tipo'+parseInt(iva)).html(parseInt(iva)+'%');
			$('#base'+parseInt(iva)).html(desglose[iva].base); 
			$('#iva'+parseInt(iva)).html(desglose[iva].iva);
		}
	});
	
}
function insertarImporte(total){
	//@Objetivo: insertar importe de pago 
	//Parametros: recibe el total de la factura
	//Recogemos primero los valores de entrada , se calcula y se escribe el nuevo registro
var importe= document.getElementById("Eimporte").value;
var fecha=document.getElementById("Efecha").value;
var forma=document.getElementById("Eformas").value;
var referencia=document.getElementById("Ereferencia").value;
if (forma==0){
	alert("NO HAS SELECCIONADO UNA FORMA DE PAGO");
}else{
var parametros = {
		"pulsado"    : 'insertarImporte',
		"importe" : importe,
		"fecha"      : fecha,
		'forma':forma,
		'referencia':referencia,
		'total':total,
		"idTemporal": cabecera.idTemporal,
		"idReal":cabecera.idReal
	};
	console.log(parametros);
	
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
				$("#tablaImporte #fila0").after(resultado.html);
				$("#tabla").find('input').attr("disabled", "disabled");
				$("#tabla").find('a').css("display", "none");
			}
			
			
		}
	});
}
	
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
