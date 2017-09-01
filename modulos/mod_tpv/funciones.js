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
var producto =[];
var total = 0;
//~ total['total'] = [];
//~ total['total']['4'] = 0;
//~ total['total']['10'] = 0;
//~ total['total']['21'] = 0;



//funciones que tenia ricardo en html dentro de <script>
//evento de tecla
//nombreInput
//nfila , idFila 

function teclaPulsada(event,nombreInput,nfila=0,nomcampo=''){
	if(event.keyCode == 13){
		//ContadorPulsaciones= 0;
		campo = nombreCampo(nombreInput,nfila,nomcampo,event.keyCode);		
	} 
	if ((nombreInput === 'Unidad') || (nombreInput === 'cajaBusqueda')){
		if ((event.keyCode === 40) || (event.keyCode === 38)){
			campo = nombreCampo(nombreInput,nfila,nomcampo,event.keyCode);
		}
		
	}
	//tecla F5 116
	if (event.keyCode === 39){
		var numproduct = producto.length;
		numproduct = numproduct -1;
		if (numproduct > 0){
			
		//	alert('Tienes productos '+numproduct+' resp '+resp);
			
			console.log('f5 ');
			cobrarF5();
		
		}
	}
	// si es popup de cobrar
	 if (nombreInput === 'entrega'){
		
		var entrega = obtenerdatos(nombreInput);
		var cambio = entrega - total;
		if(event.keyCode == 13){
			if (cambio < 0){
				$('#cambio').css('color','red');
		
			}else {
				$('#cambio').css('color','grey');
			}
		
		//$('#cambioText').html(cambio);
		$('#cambio').val(cambio);
		}
	 }
	
	
//alert(event.keyCode);
//dice numTecla 
	return;
}

function cobrarF5(){
			//abrir modal de cobrar htmlCobrar php
			//~ var titulo = 'COBRAR';
			//~ abrirModal(titulo,htmlCobrar(total));
	
	var parametros = {
			"pulsado" 	: 'cobrar',
			"total" : total
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
	console.log('Ver id obtenerDatos '+aux); //Ver id [object HTMLInputElement]
	return aux.value;
}
function movimTecla(numTecla,nfila,nombreInput){

	//tecla hacia abajo
	if (numTecla === 40){
		//alert(' moverse a nfila-1');
		nfila=nfila-1;
		$('#N'+nfila+'_'+nombreInput).focus();
		
		//quiero pintar en modal al bajar y poder agregar fila con enter
		$('#C_'+nfila+'Lin').css("background-color", "red");
		
		
	}
	//tecla hacia arriba
	if (numTecla === 38){
		//alert(' moverse a nfila+1');
		nfila=nfila+1;
		$('#N'+nfila+'_'+nombreInput).focus();
	}
	
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
			if (datoInput === ''){
				$('#C0_Referencia').focus();
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
			//alert('caja busqueda'+nomcampo);
			datoInput = obtenerdatos(nombreInput);
			movimTecla(numTecla,nfila,nombreInput);
			viewsResultado(datoInput,nomcampo);
			
		break;
		
	}
	return campo;
}
//
function resetCampo(campo){
	switch(campo) {
		case 'CREF':
			campo = 'C0_Referencia';
			document.getElementById(campo).value='';
		break;
		case 'CCODEBAR':
			campo = 'C0_Codbarras';
			document.getElementById(campo).value='';
		break;
		case 'CDETALLE':
			campo = 'C0_Descripcion';
			document.getElementById(campo).value='';
		break;
	}
	return campo;
}

function tipoIva(campo){
	switch(campo){
		case 'S':
			campo = '4';
			break;
		case 'R':
			campo = '10';
			break;
		case 'G':
			campo = '21';
			break;
	}
	return campo;
}

//EN FUNCIONES PHP 
//DETERMINAR si es una ref o un codigoBarras el dato que me pasan para buscar... 
//campoAbuscar = ref , codigoBarras o descripc
//busqueda = dato en input correspondiente
function buscarProducto(campoAbuscar,busqueda,dedonde){
	// Objetivo:
	//parametros :
	//campo input 
	//valor campo 
	// los envio a tareas, alli llamo a la funcion de buscarProducto PHP
	// recibo array con datos y trabajo con ellos, seria enviarlos a agregarFila js.
	console.log('entramos en buscarProducto JS');
	valorCampo = busqueda;
	campo = campoAbuscar;
	
	
	var parametros = {
			"pulsado" 	: 'buscarProducto',
			"valorCampo" : valorCampo,
			"campo" 	: campo,
			"dedonde" : dedonde
	};
	$.ajax({
		data:  parametros,
		url:   'tareas.php',
		type:  'post',
		beforeSend: function () {
			$("#resultado").html('Comprobamos que el producto existe ');
			console.log('******** estoy en buscar producto JS****************');
		},
		success:  function (response) {
			console.log('ajax success response '+response);
			var resultado =  $.parseJSON(response);
			//~ console.log('parseJson '+resultado[datos]); //[object object]
			//resultado es [object object]
			//ponemos var global resultado = [], para acceder a datos
			//creo array datos para leer cada dato del array resultado
			
			
			if (resultado['Estado'] === 'Correcto') {
				var datos = [];
				datos = resultado.datos[0];
				//accedo a los datos que recojo con ayuda de 2 array, 1 global resultado, y otro datos.
				console.log('DATOS: '+datos['CCODEBAR']+' '+datos['CREF']+' '+datos['CDETALLE']+' '+datos['NPCONIVA']+' '+datos['CTIPOIVA']); 
				agregarFila(datos);
				
				
	
				//limpiar formato de input referencia
				resetCampo(campo);
				console.log('tenemos array datos de uno producto');
			} else {
				
				if (resultado['Estado'] === 'Listado'){
					
					var busqueda = resultado.listado;   //$respuesta['listado']= htmlProductos TAREAS  
					var HtmlProductos=busqueda.html;   //$resultado['html'] de montaje html
					var titulo = 'Listado productos encontrados ';
					
					abrirModal(titulo,HtmlProductos);
					//alert('Posibles opciones: ');
					resetCampo(campo);
				} else {
					alert(resultado['Estado']);
					resetCampo(campo);
				}
				
				//console.log('NO HAY DATOS error buscarProducto');
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
	
	var CCODEBAR = datos['CCODEBAR'];
	var CREF = datos['CREF'];
	var CDETALLE = datos['CDETALLE'];
	
	var pvp = parseFloat(datos['NPCONIVA']);
	var NPCONIVA = pvp.toFixed(2);
	
	var CTIPOIVA = datos['CTIPOIVA'];
	
	//~ producto[nfila]['CCODEBAR'] = datos['CCODEBAR'];
	producto[nfila]=[];
	producto[nfila]=datos;
	producto[nfila]['NPCONIVA']= NPCONIVA;
	
	producto[nfila]['UNIDAD']=1;
	
	producto[nfila]['Estado']='Activo';
	
		
		//campos: CCODEBAR	CREF	CDETALLE	UNID	CANT/KILO	NPCONIVA	CTIPOIVA	IMPORTE
	
	
		// montamos fila de html de tabla
	var nuevaFila = '<tr id="Row'+(nfila)+'">';
	nuevaFila += '<td id="C'+nfila+'_Linea">'+nfila+'</td>'; //num linea
	nuevaFila += '<td id="C'+nfila+'_Codbarras">'+CCODEBAR+'</td>';
	nuevaFila += '<td id="C'+nfila+'_Referencia">'+CREF+'</td>';
	nuevaFila += '<td id="C'+nfila+'_Detalle">'+CDETALLE+'</td>';
	var campoUd = 'N'+nfila+'_Unidad';
	//
	nuevaFila += '<td><input id="'+campoUd+'" type="text" pattern="[.0-9]+" name="unidad"  placeholder="unidad" size="4"  value="1" onkeypress="teclaPulsada(event,'+"'Unidad'"+','+nfila+')" ></td>'; //unidad
	
	//si en config peso=si, mostramos columna peso
	if (CONF_campoPeso === 'si'){
		nuevaFila += '<td><input id="C'+nfila+'_Kilo" type="text" name="kilo" size="3" placeholder="peso" value="" ></td>'; //cant/kilo
	} else {
		nuevaFila += '<td style="display:none"><input id="C'+nfila+'_Kilo" type="text" name="kilo" size="3" placeholder="peso" value="" ></td>'; //cant/kilo
	}
	nuevaFila += '<td id="N'+nfila+'_Pvp">'+NPCONIVA+'</td>';
	nuevaFila += '<td id="C'+nfila+'_TipoIva">'+tipoIva(CTIPOIVA)+'%</td>';
	nuevaFila += '<td id="N'+nfila+'_Importe" class="importe" >'+NPCONIVA+'</td>'; //importe 
	nuevaFila += '<td class="eliminar"><a onclick="eliminarFila('+nfila+');"><span class="glyphicon glyphicon-trash"></span></a></td>';

	nuevaFila +='</tr>';
	
	//$ signifca jQuery 
	//$("#tabla").append(nuevaFila);
	$("#tabla").prepend(nuevaFila);

	sumaImportes();
};
 
 
//Sera funcion que agrega o elimina linea.
function eliminarFila(nfila){
	var line;
	line = "#Row" + nfila;
	producto[nfila]['Estado'] = 'Eliminado';
	$(line).addClass('tachado');
	$(line + "> .eliminar").html('<a onclick="retornarFila('+nfila+');"><span class="glyphicon glyphicon-export"></span></a>');
	$("#N" + nfila + "_Unidad").prop("disabled", true);
	sumaImportes();
}

function retornarFila(nfila){
	var line;
	line = "#Row" + nfila;
	producto[nfila]['Estado'] = 'Activo';
	var pvp =producto[nfila]['NPCONIVA'];

	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarFila('+nfila+');"><span class="glyphicon glyphicon-trash"></span></a>');
	if (producto[nfila]['UNIDAD'] == 0) {
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
					
}


//function futura cuando buscamos directamente en caja de busqueda
function viewsResultado(datoInput,nomcampo) {
	//alert('vista resultaod'+datoInput+' campo nombre '+nomcampo);
	buscarProducto(nomcampo,datoInput,'popup');

}

function cerrarModal(cref,cdetalle,ctipoIva,ccodebar,npconiva){
	var producto = [];
	producto['CREF'] = cref;
	producto['CDETALLE'] = cdetalle;
	producto['NPCONIVA'] =npconiva;
	producto['CCODEBAR'] =ccodebar;
	producto['CTIPOIVA'] =ctipoIva;
	
	
	//alert('CerrarModal producto'+npconiva);
		
	$('#busquedaModal').modal('hide');
	agregarFila(producto);

}
