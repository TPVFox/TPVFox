/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogoproductos - Funciones sincronizar.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero - SolucionesVigo
 * @Descripcion	Javascript necesarios para modulo importar DBF.
 *
 * ej producto
 * producto[0]
 * 				['CCODEBAR']
 * 				['CREF']
 * 				['CDETALLE']
 * 				['UNID']
 * 				['CANT/KILO']
 * 				['NPCONIVA']
 * 				['CTIPOIVA']
 * 				['ESTADO']
 *  
 * ej total
 * 
 * total [total] = 12.00€
 * total [iva]['4']=0.40€
 * total [iva] ['21'] = 1.60€
 * total [base]['4']=0.40€
 * total [base] ['21'] = 1.60€
 * 
 * total [tbases] = sum(total [base])
 * total [tiva] = sum(total [iva])
 * */
var pulsado = '';
var iconoCargar = '<span><img src="../../css/img/ajax-loader.gif"/></span>';
var iconoCorrecto = '<span class="glyphicon glyphicon-ok-sign"></span>';
var iconoIncorrecto = '<span class="glyphicon glyphicon-remove-sign"></span>';
var producto; // Hay que eliminar.. 
var total = 0;




//funciones que tenia ricardo en html dentro de <script>
//evento de tecla
//nombreInput
//nfila , idFila 
//~ function disableF5(e) { 
	//~ if ((e.which || e.keyCode) == 116) {
		//~ e.preventDefault(); 
	//~ }
//~ };



function teclaPulsada(event,nombreInput,nfila=0,nomcampo=''){
	//~ $(document).on("keydown", disableF5);
	//~ $(document).off("keydown", disableF5);
	if(event.keyCode == 13){
		console.log('enter nombreINput '+nombreInput);
	
		campo = nombreCampo(nombreInput,nfila,nomcampo,event.keyCode);
	} 
	
	if ((event.keyCode === 40) || (event.keyCode === 38)){
	console.log('dentro 0');
			//muevo foco de caja busqueda a input inicial 0, estamos bajando
			if ((event.keyCode === 40) && (nombreInput === 'cajaBusquedacliente') || (nombreInput === 'cajaBusqueda')){
			console.log('dentro 2 flechas');
				var datoinput = obtenerdatos(nombreInput);					//vemos si hay datos/valor en input
				if ((datoinput === '') && (nombreInput === 'cajaBusqueda')){   //cajaBusqueda == modalProductos, en vacio tiene lista productos
					tiempoEnfoqueInput(nfila);								//enfoque en el primer input de la lista
				console.log('dentro campo vacio, busqueda producto');
				} 
				console.log('dentro 4datos input '+datoinput);
				tiempoEnfoqueInput(nfila); //se mueve al primer input
				return;
			}
			
			//muevo foco de input inicial al sig input
			if ((event.keyCode === 40) && (nombreInput === 'N_'+nfila)){
				nfila++;  //se mueve hacia abajo
				tiempoEnfoqueInput(nfila);
			} else {  					//es que estas pulsando tecla arriba //flecha
				nfila--; 				//se mueve hacia arriba
				if (nfila === -1){ 		//pasamos al input del campo de busqueda el foque
					$('#cajaBusqueda').select();
					$('#cajaBusquedacliente').select();
				}
				tiempoEnfoqueInput(nfila);
			}
//PENDIENTE: si inputs estan vacios y pulso teclas
		console.log('down nombreINput '+nombreInput+' fila '+nfila);
		campo = nombreCampo(nombreInput,nfila,nomcampo,event.keyCode);
	}
		
	//}
	//tecla F5 116  -- tecla 39 flecha drcha    tecla 27 --> ESC
	//tecla F1 --> 112  COBRAR popup
	if (event.keyCode === 112){
		var numproduct = producto.length;
		numproduct = numproduct -1;
		if (numproduct > 0){
			console.log('F1 ');
			cobrarF5();
		}
	}
	// si es popup de cobrar 
	 if (nombreInput === 'entrega'){
		var entrega = obtenerdatos(nombreInput);
		var cambio = entrega - total;
		console.log(entrega);
		if(event.keyCode === 13){
			if (cambio < 0){
				$('#cambio').css('color','red');
			}else {
				$('#cambio').css('color','grey');
			}
		$('#cambio').val(cambio.toFixed(2));
		}
	 }
	 
	 
	//flecha hacia abajo se mueve por los inputs cantidad/unidad productos en ticket
	if (event.keyCode === '40'){
		nfila = producto.length;
		
		nfila = nfila-1;
		//alert(nfila);
		if (nfila >= 0){
			$('#N'+nfila+'_Unidad').select();
		}
		//alert(nfila);
	}
	
//alert(event.keyCode);
//dice numTecla 

	
	
}
//en input llamo con onkeydown a teclaPulsada(event,nombreInput,nfila)
//pongo un tiempo de focus en input ventana modal busqueda 
function tiempoEnfoqueInput(nfila){
	setTimeout(function() {   //pongo un tiempo de focus en input modal busqueda 
		$('#N_'+nfila).focus(); 
	}, 500); 
}

function cobrarF5(){
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
			console.log('ajax success cobrarF5 '+response);
			var resultado =  $.parseJSON(response);
			
			
			
			//HtmlCobrar = resultado;
			//busqueda = resultado.cobrar;
			
			var HtmlCobrar = resultado.html;  //$resultado['html'] de montaje html
			var titulo = 'COBRAR ';
				console.log(' cobrar '+HtmlCobrar);	
			abrirModal(titulo,HtmlCobrar);
			//alert('cobrar');
			
		}
	});
			
	
	return;
}

//quiero conseguir valor del campo
function obtenerdatos(id){
	var aux = document.getElementById(id);
	console.log('Ver id obtenerDatos '+aux.value+' '+id); //Ver id [object HTMLInputElement]
	return aux.value;
}
function movimTecla(numTecla,nfila,nombreInput){

	//tecla hacia abajo
	if (numTecla === 40){
		//alert(' moverse a ' + nfila+' input '+nombreInput);
			//~ alert(nfila + nombreInput);
		nfila=nfila-1;
		if (nombreInput !== 'Unidad') {
			nfila = producto.length - 1;
			nombreInput = 'Unidad';
		}
		$('#N' + nfila + '_' + nombreInput).select();
		
		//quiero pintar en modal al bajar y poder agregar fila con enter
		
		if (nombreInput === 'cajaBusqueda'){
			nfila = 0;
			//alert('modal '+nombreInput+nfila);
			$('#Fila_'+nfila).css('background-color','red');
		}
		
	}
	//tecla hacia arriba
	if (numTecla === 38){
		//alert(' moverse a nfila+1');
		
		nfila=nfila+1;
		$('#N'+nfila+'_'+nombreInput).select();
	}
	//
	if (numTecla === 13){
		$('#C0_Codbarras').focus();
		return;
	}
	
	
	//tecla hacia la drecha
	if (numTecla === 39){
		//tecla izq 37
	}
	
	
}



//case de nombreCampo = mysql , = html, 
//con el id='C0_Codbarras' recojo el valor del campo en funcion obtener datos
// pero necesito  nombreCampo = 'CCODEBAR' para mysql
//nfila, numero fila
function nombreCampo(nombreInput,nfila,nomcampo,numTecla){
	
	var id;
	var campo;
	//alert('nombre input '+nombreInput);
	switch(nombreInput) {
		case 'Codbarras':
			campo = 'CCODEBAR';
			id= 'C'+nfila+'_'+nombreInput;
			datoInput = obtenerdatos(id);
			movimTecla(numTecla,nfila,nombreInput);
			if ((datoInput === '') && ((numTecla === 13) || (numTecla === 38) )){
				$('#C0_Referencia').focus();
				return;
			} else if (numTecla === 40){
				$('#'+id).val('');
				return;
			}
			buscarProducto(campo,datoInput,'tpv');
			break;
		case 'Referencia':
			campo = 'CREF';
			id= 'C'+nfila+'_'+nombreInput;
			console.log(id);
			datoInput = obtenerdatos(id);
			if (datoInput === ''){
				$('#C0_Descripcion').focus();
				return;
			}
			buscarProducto(campo,datoInput,'tpv');
			break;
		case 'Descripcion':
			campo = 'CDETALLE';
			if(nomcampo==='buscar'){ //buscar con lupa click raton
				$('cajaBusqueda').val();
				buscarProducto(campo,'','tpv');
			}
			id= 'C'+nfila+'_'+nombreInput;
			datoInput = obtenerdatos(id);
			if (datoInput === ''){
				$('#C0_Codbarras').focus();
				return;
			}
			buscarProducto(campo,datoInput,'tpv');
			
		
			
			break;
		case 'Unidad':
			id= 'N'+nfila+'_'+nombreInput;
			datoInput = obtenerdatos(id);
			//recalcularImporte
			pvp = producto[nfila]['NPCONIVA'];
			//alert(pvp);
			recalculoImporte(datoInput,pvp,nfila);
			movimTecla(numTecla,nfila,nombreInput);

			//alert('dato input '+datoInput);
			break;
		case 'cajaBusqueda':
			datoInput = obtenerdatos(nombreInput);
			movimTecla(numTecla,nfila,nombreInput);
			buscarProducto(nomcampo,datoInput,'popup');
			//~ viewsResultado(datoInput,nomcampo);
			break;
		case 'busquedaCliente':
			console.log('nomcampo '+nomcampo); // si estoy en buscar vine por lupa, sin datos en input
			//deberia mostrar una lista opc clientes
			//PENDIENTE
			
			var valor = $('#cajaBusquedacliente').val();
			
			console.log('valor input cliente '+valor);
			buscarClientes(valor);
			movimTecla(numTecla,nfila,nombreInput);
		break;
		
	}
	return campo;
}
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

function resetCampo(campo){
	var campos = [];
	campos['CREF'] = 'C0_Referencia';
	campos['CCODEBAR'] = 'C0_Codbarras';
	campos['CDETALLE'] = 'C0_Descripcion';
	console.log('Entro en resetCampo '+campo);
	document.getElementById(campos[campo]).value='';
	return campos[campo];
}


function buscarProducto(campoAbuscar,busqueda,dedonde){
	// @parametros:
	// 		campoAbuscar = ref,codigoBarras o descripc
	// 		busqueda = valor del input que corresponde.
	// 		dedonde  = [tpv] o [popup] 
	// El Objetivo enviar dato y campo a buscar...
	// Y obtener un respuesta , donde puede ser:
	//  1.- Un producto unico.
	//  2.- Un listado de productos.
	//  3.- O nada un error.
	valorCampo = busqueda;
	campo = campoAbuscar;
	console.log('Entramos en buscarProducto JS- Para buscar con el campo'+campo);
	var parametros = {
		"pulsado"    : 'buscarProducto',
		"valorCampo" : valorCampo,
		"campo"      : campo,
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
				console.log(datos);
				agregarFila(datos);
				//limpiar formato de input referencia
				resetCampo(campo);
				console.log('tenemos array datos de uno producto');
			} else 
				if (resultado['Estado'] === 'Listado'){
				console.log('Entro en Estado Listado de funcion buscarProducto');
				console.log(resultado);
				var busqueda = resultado.listado;   //$respuesta['listado']= htmlProductos TAREAS  
				var HtmlProductos=busqueda.html;   //$resultado['html'] de montaje html
				var titulo = 'Listado productos encontrados ';
				// Abrimos modal de productos.
				abrirModal(titulo,HtmlProductos);
				resetCampo(campo);

			} else {
				alert(resultado['Estado']);
				// Limpiamos campos de caja de busqueda.
				resetCampo(campo);
			}
		}
	});
}

function agregarFila(datos){
	// Montamos array
	var nfila = producto.length;
	if (nfila === 0){
		 nfila = 1;
	}
	// Voy a crear objeto producto nuevo..
	productos.push(new ObjProducto(datos,nfila));
		 
	var CCODEBAR = datos['CCODEBAR'];
	var CREF = datos['CREF'];
	var CDETALLE = datos['CDETALLE'];
	var pvp = parseFloat(datos['NPCONIVA']);
	var NPCONIVA = pvp.toFixed(2);
	var CTIPOIVA = datos['CTIPOIVA'];

			//~ datos['crefTienda'];
			//~ datos['articulo_name'];
			//~ datos['iva'];
			//~ datos['codBarras'];
			//~ datos['pvpCiva'];
	//~ producto[nfila]['CCODEBAR'] = datos['CCODEBAR'];
	//~ producto[nfila]=[];
	producto[nfila]= datos;
	producto[nfila]['NPCONIVA']= NPCONIVA;
	producto[nfila]['UNIDAD']=1;
	producto[nfila]['Estado']='Activo';
	
	//campos: CCODEBAR	CREF	CDETALLE	UNID	CANT/KILO	NPCONIVA	CTIPOIVA	IMPORTE

	// montamos fila de html de tabla
	var nuevaFila = '<tr id="Row'+(nfila)+'">';
	nuevaFila += '<td id="C'+nfila+'_Linea">'+nfila+'</td>'; //num linea
	nuevaFila += '<td id="C'+nfila+'_Codbarras" autofocus>'+CCODEBAR+'</td>';
	nuevaFila += '<td id="C'+nfila+'_Referencia">'+CREF+'</td>';
	nuevaFila += '<td id="C'+nfila+'_Detalle">'+CDETALLE+'</td>';
	var campoUd = 'N'+nfila+'_Unidad';
	//
	nuevaFila += '<td><input id="'+campoUd+'" type="text" pattern="[.0-9]+" name="unidad"  placeholder="unidad" size="4"  value="1" onkeydown="teclaPulsada(event,'+"'Unidad'"+','+nfila+')" ></td>'; //unidad

	//si en config peso=si, mostramos columna peso
	if (CONF_campoPeso === 'si'){
		nuevaFila += '<td><input id="C'+nfila+'_Kilo" type="text" name="kilo" size="3" placeholder="peso" value="" ></td>'; //cant/kilo
	} else {
		nuevaFila += '<td style="display:none"><input id="C'+nfila+'_Kilo" type="text" name="kilo" size="3" placeholder="peso" value="" ></td>'; //cant/kilo
	}
	nuevaFila += '<td id="N'+nfila+'_Pvp">'+NPCONIVA+'</td>';
	nuevaFila += '<td id="C'+nfila+'_TipoIva">'+CTIPOIVA+'%</td>';
	nuevaFila += '<td id="N'+nfila+'_Importe" class="importe" >'+NPCONIVA+'</td>'; //importe 
	nuevaFila += '<td class="eliminar"><a onclick="eliminarFila('+nfila+');"><span class="glyphicon glyphicon-trash"></span></a></td>';

	nuevaFila +='</tr>';

	//$ signifca jQuery 
	//$("#tabla").append(nuevaFila);
	$("#tabla").prepend(nuevaFila);
	$('#C0_Codbarras').focus();  //al agregar fila el foco lo coje el input de codigobarras
	sumaImportes();
};
 
 
//Sera funcion que agrega o elimina linea.
function eliminarFila(nfila){
	var line;
	line = "#Row" + nfila;
	// Nueva Objeto de productos.
	productos[nfila-1].estado= 'Eliminado';
	// Antiguo array productos.
	producto[nfila]['Estado'] = 'Eliminado';
	$(line).addClass('tachado');
	$(line + "> .eliminar").html('<a onclick="retornarFila('+nfila+');"><span class="glyphicon glyphicon-export"></span></a>');
	$("#N" + nfila + "_Unidad").prop("disabled", true);
	sumaImportes();
}

function retornarFila(nfila){
	var line;
	line = "#Row" + nfila;
	// Nueva Objeto de productos.
	productos[nfila-1].estado= 'Activo';
	// Antiguo array productos.
	producto[nfila]['Estado'] = 'Activo';
	var pvp =producto[nfila]['NPCONIVA'];

	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarFila('+nfila+');"><span class="glyphicon glyphicon-trash"></span></a>');
	if (producto[nfila]['UNIDAD'] == 0) {
		// Nueva Objeto de productos.
		productos[nfila].unidad= 1;
		// Antiguo array productos.
		producto[nfila]['UNIDAD'] = 1;
		recalculoImporte(producto[nfila]['UNIDAD'],pvp,nfila);
	}
	$("#N" + nfila + "_Unidad").prop("disabled", false);
	$("#N" + nfila + "_Unidad").val(producto[nfila]['UNIDAD']);

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
		
		$('#entrega').select(); 	//foco en input entrega MODAL cobrar
		
		$('#cajaBusquedacliente').focus(); //foco en input caja busqueda del cliente
	
	});

	
	//indico que en input de ventana modal buscar cliente PRESIONO tecla va a la funcion teclaPulsada
	$('#cajaBusquedacliente').on('keydown', function ( e ) {
		teclaPulsada(e,'cajaBusquedacliente');
	});
	$('#cajaBusqueda').on('keydown', function(e){
		teclaPulsada(e,'cajaBusqueda');
	});
}

function cerrarModal(cref,cdetalle,ctipoIva,ccodebar,npconiva,id){
	// Nuevos datos tabla nueva... 
	var datos = []
	datos['idArticulo'] 	= id;
	datos['crefTienda'] 	= cref;
	datos['articulo_name'] 	= cdetalle;
	datos['pvpCiva'] 		= npconiva;
	datos['iva'] 			= ctipoIva;
	// Antiguos datos, tablas BDF
	datos['idArticulo'] = id;
	datos['CREF'] = cref;
	datos['CDETALLE'] = cdetalle;
	datos['NPCONIVA'] =npconiva;
	datos['CCODEBAR'] =ccodebar;
	datos['CTIPOIVA'] =ctipoIva;
	$('#busquedaModal').modal('hide');
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
	cabecera['idCliente'] = id;
	
	$('#Cliente').val(nombre);
}


function buscarClientes(valor=''){
	// Objetivo:
	//parametros :
	//campo input 
	//valor campo 
	// los envio a tareas, alli llamo a la funcion de buscarProducto PHP
	// recibo array con datos y trabajo con ellos, seria enviarlos a agregarFila js.
	console.log('entramos en buscarcliente JS');
	
	//alert('contenido valor '+valor);
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
			console.log('ajax success response '+response);
			var resultado =  $.parseJSON(response); 
			//alert (resultado); //html
			var HtmlClientes=resultado.html;   //$resultado['html'] de montaje html
			var titulo = 'Listado clientes ';

				abrirModal(titulo,HtmlClientes);
			
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
			console.log(response);
			var resultado =  $.parseJSON(response); 
			console.log(resultado.estadoTicket);
			// Cambiamos el estado :
			cabecera.estadoTicket = resultado.estadoTicket;
			cabecera.numTicket = resultado.NumeroTicket;
			$('#EstadoTicket').html(resultado.estadoTicket);
			$('#EstadoTicket').css('background-color','red')
			$('#EstadoTicket').css('color','white')
			$('#NTicket').html('0/'+resultado.NumeroTicket);


		}
	});
}

function ObjProducto(datos,nfila,valor=1,estado ='Activo')
{
    this.id = datos.idArticulo;
    this.cref = datos.crefTienda
    this.cdetalle = datos.articulo_name;
    this.npconiva = datos.pvpCiva;
    this.ccodebar = datos.codBarras;
    this.ctipoiva = datos.iva;
    this.unidad = valor;
    this.estado = estado;
    this.nfila = nfila;



}
	

	

