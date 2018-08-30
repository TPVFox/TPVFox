function metodoClick(pulsado,adonde){
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'Ver':
			console.log('Entro en Ver pedido');
			// Cargamos variable global ar checkID = [];
			//~ VerIdSeleccionado ();
            checkID = leerChecked('check_'+ adonde);
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
			console.log(resultado);
			$("#formaVenci").html(resultado.html1);
			$("#fechaVencimiento").prepend(resultado.html2);
		}
	});
}
function  modificarEstado(dedonde, estado, idModificar){
	//@Objetivo:
	//Modificar el estado dependiendo de donde venga 
	//Paramtros: 
	//Dedonde: de donde llamamos a la función
	//Estado: estado que vamos a asignarle al registro
	//IdModificar: id del registro que se va a modificar
	if (dedonde=="pedidos"){
		var pulsado='modificarEstadoPedido';
	}
	if (dedonde=="albaran"){
		var pulsado= 'modificarEstadoAlbaran';
		
	}
	if (dedonde=="factura"){
		var pulsado='modificarEstadoFactura';
	}
	var parametros = {
			"pulsado":pulsado,
			"idModificar":idModificar,
			"estado":estado
		};
		console.log(parametros);
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en Modificar estado factura js****************');
		},
		success    :  function (response) {
				var resultado =  $.parseJSON(response); 
				if (resultado.error){
					alert('Error de SQL: '+resultado.consulta);
				}
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
			// Si el archivo de donde viene la consulta es  albaran con lo que devuelve la consulta
			//de buscarCliente se registra en los input y se bloquean posteriormente
			if (resultado.error){
					alert('ERROR DE SQL: '+resultado.consulta);
			}else{
				if (resultado.Nitems==1){
					cabecera.idCliente=resultado.id;
					$('#Cliente').val(resultado.nombre);
					$('#id_cliente').val(resultado.id);
					$('#Cliente').prop('disabled', true);
					$('#id_cliente').prop('disabled', true);
					$("#buscar").css("display", "none");
					
					mostrarFila();
					if (dedonde=="albaran"){
						comprobarPedidosExis();
					}
					if (dedonde=="factura"){
						formasVenciCliente(resultado.formasVenci);
						comprobarAlbaranesExis();
					}
					if(dedonde=="pedido"){
					$('#Referencia').focus();	
					}
					 cerrarPopUp();
					
				}else{
					console.log(resultado.html);
				 var titulo = 'Listado clientes ';
				 var HtmlClientes=resultado.html.html; 
				 abrirModal(titulo,HtmlClientes);
				 focusAlLanzarModal('cajaBusquedacliente');
				if(resultado.html.encontrados>0){
					ponerFocus('N_0');
				}
				 }
			}
			
		}
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
			console.log(caja);
			if( caja.darValor()=="" && caja.id_input=="id_cliente"){
				// Entramos cuando venimos de id de proveedor.
				var d_focus="Cliente";
                ponerFocus(d_focus);
            }else{
				buscarClientes(caja.darParametro('dedonde'),caja.id_input ,caja.darValor());
			}
			
			break;
		case 'saltar_idCliente':
		console.log('Entro en acciones saltar_idCliente');
		var dato = caja.darValor();
		if(caja.id_input=="fecha"){
					cabecera.fecha=dato;
					var d_focus = 'id_cliente';
				ponerFocus(d_focus);
		}
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
			var dato = caja.darValor();
			if(dato.length === 0){
				var d_focus = 'idArticulo';
				ponerFocus(d_focus);
			}
			
			break
		case 'buscarProductos':
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
			productos[nfila].nunidades = caja.darValor();
			productos[nfila].ncant=caja.darValor();
			recalculoImporte(productos[nfila].nunidades,nfila, caja.darParametro('dedonde'));
			if (caja.tipo_event !== "blur"){
                ponerFocus( ObtenerFocusDefectoEntradaLinea());
			}
			
			
			break;
		case 'mover_down':
			// Controlamos si numero fila es correcto.
			console.log(caja);
			var nueva_fila = 0;
			if(caja.id_input=="cajaBusquedacliente" || caja.id_input=="cajaBusqueda"){
				ponerFocus('N_0');
				
			}else{
			if ( isNaN(caja.fila) === false){
				nueva_fila = parseInt(caja.fila)+1;
			} 
			console.log('mover_down:'+nueva_fila);
			
			mover_down(nueva_fila,caja.darParametro('prefijo'));
			}
		break;
		case 'mover_up':
			console.log( 'Accion subir 1 desde fila'+caja.fila);
			var nueva_fila = 0;
			if(caja.fila=='0'){
				if(cabecera.idCliente>0){
					ponerSelect('cajaBusqueda');
				}else{
					$("#cajaBusquedacliente").select();
				}
			}else{
				if ( isNaN(caja.fila) === false){
					nueva_fila = parseInt(caja.fila)-1;
				}
				mover_up(nueva_fila,caja.darParametro('prefijo'));
			}
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
		case 'buscarPedido':
		console.log("Entre en buscar pedido");
		buscarPedido(caja.darParametro('dedonde'),caja.id_input ,caja.darValor());
		
		break;
		case 'buscarAlbaran':
		console.log("Entre en buscar albaran");
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
	} 
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
			if(resultado.error){
				alert('Error de SQL:'+resultado.consulta);
			}else{
				if (resultado.mensaje==1){
					//Se muestra el mensaje cuando el importe es superior al de la factura
					alert("El importe introducido no es correcto");
				}else{
					$("#tablaImporte #fila0").after(resultado.html);
					$("#tabla").find('input').attr("disabled", "disabled");
					$("#tabla").find('a').css("display", "none");
				}
			}
			
		}
	});
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
function ponerSelect (destino_focus){
	// @ Objetivo:
	// 	Poner focus a donde nos indique el parametro, que debe ser id queremos apuntar.
	console.log('Entro en ponerselects de :'+destino_focus);
	setTimeout(function() {   //pongo un tiempo de focus ya que sino no funciona correctamente
		jQuery('#'+destino_focus.toString()).select(); 
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
		console.log(' Entro en Before de '+ caja.id_input)
		caja.fila = caja.id_input.slice(2);
		if(caja.tecla==13){
			if(cabecera.idCliente>0){
				console.log(caja.parametros.dedonde);
				if(caja.parametros.dedonde=='albaran'){
				console.log(caja);
				console.log(caja.parametros.dedonde);
				 buscarProductos('idArticulo', 'a.idArticulo', 'idArticulo', caja.darValor(), caja.parametros.dedonde);
			 }
				
			}else{
				if(caja.parametros.dedonde!="factura"){
					 buscarClientes(caja.parametros.dedonde, "id_cliente", caja.darValor());
				}
				
			}
		}
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
	// 		
	// @ Respuesta:
	//  1.- Un producto unico.
	//  2.- Un listado de productos.
	//  3.- O nada un error.
	console.log('FUNCION buscarProductos JS- Para buscar con el campo');
    	if (busqueda !== "" || idcaja === "Descripcion"){
	var parametros = {
		"pulsado"    : 'buscarProductos',
		"cajaInput"	 : id_input,
		"valorCampo" : busqueda,
		"campo"      : campo,
		"idcaja"	 :idcaja,
		'dedonde'	:dedonde,
		'idCliente'	:cabecera.idCliente
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
							var importe =resultado['datos'][0]['pvpSiva']*1;
							datos.importe=importe.toFixed(2);
							var pvpCiva= parseFloat(resultado['datos'][0]['pvpCiva']);
							datos.precioCiva=pvpCiva.toFixed(2);
							var pvpSiva= parseFloat(resultado['datos'][0]['pvpSiva']);
							datos.pvpSiva=pvpSiva.toFixed(2);
							n_item=parseInt(productos.length)+1;
							var campo='Unidad_Fila_'+n_item;
							productos.push(datos);
							addTemporal(dedonde);
							AgregarFilaProductosAl(datos, dedonde, campo);
							resetCampo(id_input);
							if (dedonde=="factura"){
								$("#tablaAl").hide();
							}
							 cerrarPopUp();
						}else{
							console.log('=== Entro en Estado Listado de funcion buscarProducto =====');
				
							var busqueda = resultado.listado;   
							var HtmlProductos=busqueda.html;   
							var titulo = 'Listado productos encontrados ';
							abrirModal(titulo,HtmlProductos);
							focusAlLanzarModal('cajaBusqueda');
							if (resultado.listado['encontrados'] >0 ){
								// Quiere decir que hay resultados por eso apuntamos al primero
								// focus a primer producto.
								var d_focus = 'N_0';
								 ponerFocus(d_focus);
							}
						}
					
			}

	});

}else{
     console.log('Saltamos a ' + ObtenerCajaSiguiente(idcaja));
        ponerFocus(ObtenerCajaSiguiente(idcaja));
}
}
function resetCampo(campo){
	//@Objetivo: borrar los campos input
	console.log('Entro en resetCampo '+campo);
	document.getElementById(campo).value='';
	return;
}
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
function eliminarFila(num_item, valor=""){
	//@Objetivo
	//Función para cambiar el estado del producto
	console.log("entre en eliminar Fila");
	var line;
	num=num_item-1;
	line = "#Row" + productos[num].nfila;
	productos[num].estadoLinea='Eliminado';
	$(line).addClass('tachado');
	$(line + "> .eliminar").html('<a onclick="retornarFila('+num_item+', '+"'"+valor+"'"+');"><span class="glyphicon glyphicon-export"></span></a>');
	$("#N" +productos[num].nfila + "_Unidad").prop("disabled", true);
	addTemporal(valor);
	
}
function retornarFila(num_item, valor=""){
	// @Objetivo :
	// Es pasar un producto eliminado a activo.
	console.log("entre en retornar fila");
	var line;
	num=num_item-1;
	line = "#Row" +productos[num].nfila;
	productos[num].estadoLinea= 'Activo';
	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarFila('+num_item+' , '+"'"+valor+"'"+');"><span class="glyphicon glyphicon-trash"></span></a>');
	if (productos[num].nunidades == 0) {
		productos[num].nunidades = 1;
	}
	$("#Unidad_Fila_" + productos[num].nfila).prop("disabled", false);
	$("#N" + productos[num].nfila + "_Unidad").prop("disabled", false);
	$("#N" + productos[num].nfila + "_Unidad").val(productos[num].nunidades);	
	addTemporal(valor);
}
function recalculoImporte(cantidad,num_item, dedonde=""){
	
	// @ Objetivo:
	// Recalcular el importe de la fila, si la cantidad cambia.
	// @ Parametros:
	//	cantidad -> Valor ( numerico) de input unidades.
	//	num_item -> El numero que indica el producto que modificamos.
	console.log('Estoy en recalculoImporte');
		if (productos[num_item].ncant == 0 && cantidad != 0) {
			retornarFila(num_item+1, dedonde);
		} else if (cantidad == 0 ) {
			eliminarFila(num_item+1, dedonde);
		}
		productos[num_item].nunidades = cantidad;
		productos[num_item].ncant = cantidad;
		var importe = cantidad*productos[num_item].pvpSiva;
		var id = '#N'+productos[num_item].nfila+'_Importe';
		importe = importe.toFixed(2);
		productos[num_item].importe= importe;
		$(id).html(importe);
		addTemporal(dedonde);
}

function mover_up(fila,prefijo){
	var d_focus = prefijo+fila;
		// Segun prefijo de la caja seleccionamos o pones focus.
	if ( prefijo === 'Unidad_Fila_'){
		// Seleccionamos
		ponerSelect(d_focus);
	} else {
		ponerFocus(d_focus);
	}
}
function mover_down(fila,prefijo){
	var d_focus = prefijo+fila;
	// Segun prefijo de la caja seleccionamos o pones focus.
	if ( prefijo === 'Unidad_Fila_'){
		// Seleccionamos
		ponerSelect(d_focus);
	} else {
		ponerFocus(d_focus);
	}
}
function mostrarFila(){
	//@Objetivo; 
	//Mostrar la fila de inputs para añadir nuevos productos
	console.log("mostrar fila");
	$("#Row0").removeAttr("style") ;
    ponerFocus( ObtenerFocusDefectoEntradaLinea());
}

function buscarPedido(dedonde, idcaja, valor=''){
	//@Objetivo
	//Buscar los pedidos de un cliente que tenga el estado guardado
	//Parametros:
	//dedonde: archivo del que viene 
	//Idcaja : id de la caja de donde se inserto el número
	//valor: valor que se escribió en la caja
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
			var HtmlPedidos=resultado.html;
			if(resultado.error){
				 alert('Error de SQL: '+resultado.error);
			}else{   
				if (valor==""){ 
					var titulo = 'Listado Pedidos ';
					abrirModal(titulo, HtmlPedidos);
				}else{
					if (resultado.Nitems>0){
						var bandera=0;
						for(i=0; i<pedidos.length; i++){
							var numeroPedido=pedidos[i].Numpedcli;
							var numeroNuevo=resultado['datos'].Numpedcli;
							if (numeroPedido == numeroNuevo){
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
								resultado.productos[i]['importe']=resultado.productos[i]['nunidades']*resultado.productos[i]['pvpSiva'];
								productos.push(resultado.productos[i]);
								numFila++;
							}
							console.log(dedonde);
							addTemporal(dedonde)
							modificarEstado("pedidos", "Facturado",resultado['datos'].idPedCli );
							AgregarFilaPedido(datos, "albaran");
							AgregarFilaProductosAl(resultado.productos, dedonde);
						}else{
							alert("Ya has introducido ese pedido");
						}
					}else{
						alert("No hay resultado");
					}
				}
			}
		}
	});
}
function buscarAlbaran(dedonde, idcaja, valor=''){
	//Objetivos:
	//Buscar los pedidos de un cliente que tenga el estado guardado
	console.log('FUNCION buscar albaran JS-AJAX');
	var parametros = {
		"pulsado"    : 'buscarAlbaran',
		"busqueda" : valor,
		"idCliente":cabecera.idCliente
	};
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
			if(resultado.error){
				alert('Error de sql: '+resultado.consulta);
			}else{
				if (valor==""){ //Si el usuario selecciona el icono de buscar pedido abre un modal 
				//con los pedidos del cliente
					var titulo = 'Listado Albaranes ';
					abrirModal(titulo, HtmlAlbaranes);
				}else{
					if (resultado.Nitems>0){
						var bandera=0;
						for(i=0; i<albaranes.length; i++){//recorre todo el array de arrays de pedidos
							var numeroAlbaran=albaranes[i].Numalbcli;
							var numeroNuevo=resultado['datos'].Numalbcli;
							if (numeroAlbaran == numeroNuevo){
								bandera=bandera+1;
							}
						}
						if (bandera==0){// si no hay repetidos
							var datos = [];
							datos = resultado['datos'];
							n_item=parseInt(albaranes.length)+1;
							datos.nfila=n_item;
							albaranes.push(datos);// En el array de arrays  de pedidos de la cabecera metemos el array de pedido nuevo 
							productosAdd=resultado.productos;
							var numFila=productos.length+1;
							for (i=0; i<productosAdd.length; i++){ //en el array de arrays de productos metemos los productos de ese pedido
								resultado.productos[i]['nfila']=numFila;
								resultado.productos[i]['importe']=resultado.productos[i]['nunidades']*resultado.productos[i]['pvpSiva'];
								productos.push(resultado.productos[i]);
								numFila++;
							}
							console.log(productos);
							addTemporal(dedonde);
							AgregarFilaProductosAl(resultado.productos, dedonde);
							modificarEstado("albaran", "Facturado", resultado['datos'].idalbcli);
							AgregarFilaAlbaran(datos, dedonde);
							
							
							
						}else{
							alert("Ya has introducido ese pedido");
						}
					}else{
						alert("No hay resultado");
					}
				}
			}
		}
	});
}
function bloquearInput(){
	//@Objetivo:
	//Blosquear la linea de insertción de inputs y los input de unidades para que no se puedan modificar
	console.log("Elementos js");
	$('#Row0').css('display', 'none');
	$('.unidad').attr("readonly","readonly");
}
function addTemporal(dedonde){
	//@Objetivo;
	//Añadir un registro temporal o modificarlo
	//@Parametros: 
	//dedonde: de donde viene (factura, albaran, pedidos)
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
		"productos":JSON.stringify(productos),
		"idCliente":cabecera.idCliente
	};
	if (dedonde=="albaran"){
		parametros['pedidos']=pedidos;
	}
	if (dedonde=="factura"){
		parametros['albaranes']=albaranes;
	}
	console.log(productos);
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
			var HtmlClientes=resultado.html;
			if(resultado.error){
				alert('Error de SQL: '+resultado.consulta);
			}else{
				if (resultado.existe == 0){
					history.pushState(null,'','?tActual='+resultado.id);
					cabecera.idTemporal=resultado.id;
				}
				console.log(productos);
				resetearTotales();
				
				total = parseFloat(resultado['totales']['total'])
				$('.totalImporte').html(total.toFixed(2));
				$('#tabla-pie  > tbody ').html(resultado['htmlTabla']);
				var estado="Sin guardar";
				if (cabecera.idReal>0){
					var estado="Sin guardar";
					modificarEstado(dedonde, estado, cabecera.idReal);
				}
				if (dedonde=="factura"){
					var importe= document.getElementById("Eimporte").value;
					if (importe>0){
						insertarImporte(total);
					}
				}
				$("#Cancelar").show();
				$("#Guardar").show();
			}
		}
	});
	
}
function comprobarPedidosExis(){
	//@Objetivo:
	//comprobar que un cliente tiene pedidos con estado guardado
	//Si la respuesta es positiva muestra la entrada de pedidos
	console.log('FUNCION comprobar pedidos existentes  JS-AJAX');
	var parametros = {
		"pulsado"    : 'comprobarPedidos',
		"idCliente" : cabecera.idCliente
		
	};
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
function comprobarAlbaranesExis(){
	//@Objetivo:
	//Buscar los albaranes de el cliente seleccionado 
	//Si la respuesta es positiva muestra la tabla oculta
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
			if (resultado.error){
				alert('Error de SQL: '+resultado.consulta);
			}else{
				if (resultado.alb==1){
					$("#numAlbaranT").show();
					$("#numAlbaran").show();
					$("#buscarAlbaran").show();
					$("#tablaAlbaran").show();
				}
			}
			
		}
	});
}
function AgregarFilaPedido(datos , dedonde=""){
	//@Objetivo:
	//Agregar html con el pedido seleccionado
	//@Parametros:
	//datos: datos del pedido adjunto
	//dedonde: de donde viene
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
function AgregarFilaAlbaran(datos, dedonde){
	//@Objetivo:
	//Agregar html con el albaran seleccionado
	//@Parametros:
	//datos: datos del albaran adjunto
	//dedonde: de donde viene
	console.log("Estoy en agregar fila albaran");
	var parametros = {
		"pulsado"    : 'htmlAgregarFilaAlbaran',
		"datos" : datos,
		"dedonde":dedonde
	};
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en escribir html fila pedidos JS****************');
		},
		success    :  function (response) {
			var resultado =  $.parseJSON(response); 
			var nuevafila = resultado['html'];
			$("#tablaAlbaran").prepend(nuevafila);
			$('#numAlbaran').focus(); 
			$('#numAlbaran').val(""); 
			
		}
	});
}
function AgregarFilaProductosAl(productosAl, dedonde='', campo=''){
	//@Objetivo:
	//Agregar la fila de productos al principio de la tabla
	console.log("Estoy en agregar fila productos albaran");
	if (productosAl.length>1){
		productosAl=productosAl.reverse();
	}
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
			var nuevafila = resultado['html'];
			$("#tabla").prepend(nuevafila);
			ponerSelect(campo);
			if(dedonde=="factura"){
				if(albaranes.length>0){
				bloquearInput();
			}
			}
			
			
		}
	});
}

function buscarDatosPedido(NumPedido){
	//@Objetivo:
	// Cuando seleccionamos un pedido o un albarán llamamos a la funciones correspondiente enviandole el número.
	//Estas funciones se llama en el modal tando de añadir un pedido como un albarán para no hacer mas grande la funcion 
	//lo que hacemos es llamar a la función que llamamos cuando ponemos directamente el número
	console.log("Estoy en buscarDatosPedido");
	buscarPedido("albaran", "numPedido", NumPedido);
	cerrarPopUp();
}
function buscarDatosAlbaran(NumAlbaran){
	//@Objetivo:
	//Cuando se selecciona un albaran se llama a la función buscarAlbaran con los datos principales
	console.log("Estoy en buscar datos albaran");
	buscarAlbaran("factura", "numAlbaran", NumAlbaran);
	cerrarPopUp();
}

function selectFormas(){
	//@Objetivo:
	//Seleccionar un forma de vencimiento 
	//PENDIENTE DE ELIMINAR
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
			var resultado =  $.parseJSON(response); 
			if(resultado.error){
				alert('Error de SQL: '+resultado.consulta);
			}
		}
	});	
	if(option==4){
		$("#talon").show();
	}else{
		$("#talon").hide();
	}
}
function imprimir(id, dedonde, tienda){
	//@Objetivo:
	//imprimir en pdf tanto una factura, albaran o pedido de una tienda determinada
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

function eliminarAdjunto(numRegistro, dedonde, nfila){
	//@Objetivo:
	//Eliminar tanto un pedido o albaran adjunto en una factura o albaran , elimina por lo tanto los productos
	//de ese adjunto y le modifica el estado a guardado
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
						eliminarFila(productos[i].nfila, dedonde);
						
					}
				}else{
					var numProducto=productos[i].NumpedCli;
					if (numRegistro == numProducto){
						eliminarFila(productos[i].nfila, dedonde);
						
					}
				}
				
			}
			num=nfila-1;
			modificarEstado("pedidos", "Guardado", pedidos[num].idPedido);
			addTemporal(dedonde);
		}
		if (dedonde=="factura"){
			for(i=0;i<productos.length; i++){
				if (productos[i].Numalbcli){
					var numProducto=productos[i].Numalbcli;
					if (numRegistro == numProducto){
						eliminarFila(productos[i].nfila, dedonde);
						
					}
				}else{
					var numProducto=productos[i].NumalbCli;
					if (numRegistro == numProducto){
						eliminarFila(productos[i].nfila, dedonde);
						
					}
				}
			}
			num=nfila-1;
			modificarEstado("albaran", "Guardado", albaranes[num].idAlbaran);
			addTemporal(dedonde);
		}
}
function retornarAdjunto(numRegistro, dedonde, nfila){
	//@Objetivo:
	//retornar un adjunto eliminado , modifica el estado del adjunto a facturado y añade los productos de ese adjunto
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
	if (dedonde=="albaran"){
		for(i=0;i<productos.length; i++){
			if (productos[i].Numpedcli){
				var numProducto=productos[i].Numpedcli;
				if (numRegistro==numProducto){
					retornarFila(productos[i].nfila, dedonde);
				}
			}else{
				var numProducto=productos[i].NumpedCli;
				if (numRegistro==numProducto){
					retornarFila(productos[i].nfila, dedonde);
				}
				
			}
		}
		num=nfila-1;
		modificarEstado("pedidos", "Facturado", pedidos[num].idPedido);
		addTemporal(dedonde);
	}
	if (dedonde=="factura"){
		for(i=0;i<productos.length; i++){
				if (productos[i].Numalbcli){
				var numProducto=productos[i].Numalbcli;
				if (numRegistro==numProducto){
					retornarFila(productos[i].nfila, dedonde);
				}
			}else{
				var numProducto=productos[i].NumalbCli;
				if (numRegistro==numProducto){
					retornarFila(productos[i].nfila, dedonde);
				}
				
			}
			}
		num=nfila-1;
		modificarEstado("albaran", "Facturado",albaranes[num].idAlbaran );
		addTemporal(dedonde);
	
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
function mensajeCancelar(idTemporal, dedonde){
	var mensaje = confirm("Estas  seguro que quieres cancelar");
	if (mensaje) {
		if (idTemporal=="0"){
			alert("No puedes cancelar si está guardado");
		}else{
			var parametros = {
				"pulsado"    : 'cancelarTemporal',
				"dedonde" : dedonde,
				"idTemporal"      : idTemporal
			};
			$.ajax({
				data       : parametros,
				url        : 'tareas.php',
				type       : 'post',
				beforeSend : function () {
					console.log('*********  Entre en cancelar archivos temporales  ****************');
				},
				success    :  function (response) {
					console.log('REspuesta de cancelar temporales');
					var resultado =  $.parseJSON(response);
					console.log(resultado);
						if(resultado.mensaje){
							alert(resultado.mensaje+": "+resultado.dato);
						}else{
							switch(dedonde){
								case 'pedidos':
									location.href="pedidosListado.php";
								break;
								case 'albaran':
									location.href="albaranesListado.php";
								break;
								case 'factura':
									location.href="facturasListado.php";
								break;
							}
						}
					}
					
					
				
			});
		}
	}else{
		switch(dedonde){
			case 'pedidos':
				location.href="pedidosListado.php";
			break;
			case 'albaran':
				location.href="albaranesListado.php";
			break;
			case 'factura':
				location.href="facturasListado.php";
			break;
		}
	}
}
function abrirIncidenciasAdjuntas(id, modulo, dedonde){
		var parametros = {
				"pulsado"    : 'abrirIncidenciasAdjuntas',
				"id" : id,
				"modulo"      : modulo,
				"dedonde": dedonde
			};
			$.ajax({
				data       : parametros,
				url        : 'tareas.php',
				type       : 'post',
				beforeSend : function () {
					console.log('*********  Entre en cancelar archivos temporales  ****************');
				},
				success    :  function (response) {
					console.log('REspuesta de cancelar temporales');
					var resultado =  $.parseJSON(response);
					console.log(resultado);
					if(resultado.error){
						alert(resultado.consulta);
					}else{
						var titulo = 'Listado de incidencias ';
						abrirModal(titulo,resultado.html);
					}
				}
				
			});
}
function ObtenerCajaSiguiente(idCaja){
    // @ Objetivo
    //  Obtener cual es la caja siguiente salto 
    // @ Parametro
    //   idcaja -> la caja actual.
    // @ Devolvemos
    //   d_focus -> string con id caja siguiente.
    var d_focus = '';
    switch(idCaja){
        case 'idArticulo':
            d_focus = 'Referencia';
        break;
        
        case 'Referencia':
            d_focus = 'Codbarras';
        break;

        case 'Codbarras':
            d_focus = 'Descripcion';
        break;
    }
    return d_focus;
}


function ObtenerFocusDefectoEntradaLinea(){
	var valor = $("#salto").val();
	switch(valor){
		case '0':
			d_focus='Referencia';
		break;
		case '1':
			d_focus='idArticulo';
		break;
		case '2':
			d_focus='Referencia';
		break;
		
		case '3':
			d_focus='Codbarras';
		break;
		case '4':
			d_focus='Descripcion';
		break;
		default:
			d_focus='Referencia';
		break;
		
	}
    return d_focus;
}
