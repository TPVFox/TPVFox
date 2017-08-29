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
 *  */
var pulsado = '';
var iconoCargar = '<span><img src="../../css/img/ajax-loader.gif"/></span>';
var iconoCorrecto = '<span class="glyphicon glyphicon-ok-sign"></span>';
var iconoIncorrecto = '<span class="glyphicon glyphicon-remove-sign"></span>';
var producto =[];


//funciones que tenia ricardo en html dentro de <script>
//evento de tecla
//nombreInput
//nfila , idFila 

function teclaPulsada(event,nombreInput,nfila){
	if(event.keyCode == 13){
		ContadorPulsaciones= 0;
		
		campo = nombreCampo(nombreInput,nfila);
		
		
				
		//alert('nombreINput '+campo+' nfila: '+nfila);
	} 
	return;
}
//quiero conseguir valor del campo
function obtenerdatos(id){
	var aux = document.getElementById(id);
	console.log('Ver id obtenerDatos '+aux); //Ver id [object HTMLInputElement]
	return aux.value;
}


//case de nombreCampo = mysql , = html, 
//con el id='C0_Codbarras' recojo el valor del campo en funcion obtener datos
// pero necesito  nombreCampo = 'CCODEBAR' para mysql
//nfila, numero fila
function nombreCampo(nombreInput,nfila){
	var id;
	var campo;
	//alert('nombre input '+nombreInput);
	switch(nombreInput) {
		case 'Codbarras':
			campo = 'CCODEBAR';
			id= 'C'+nfila+'_'+nombreInput;
			datoInput = obtenerdatos(id);
			buscarProducto(campo,datoInput);
			break;
		case 'Referencia':
			campo = 'CREF';
			id= 'C'+nfila+'_'+nombreInput;
			console.log(id);
			datoInput = obtenerdatos(id);
			buscarProducto(campo,datoInput);
			break;
		case 'Descripcion':
			campo = 'CDETALLE';
			id= 'C'+nfila+'_'+nombreInput;
			datoInput = obtenerdatos(id);
			buscarProducto(campo,datoInput);
			break;
		case 'Unidad':
			id= 'N'+nfila+'_'+nombreInput;
			datoInput = obtenerdatos(id);
			//recalcularImporte
			pvp = producto[nfila]['NPCONIVA'];
			//alert(pvp);
			recalculoImporte(datoInput,pvp,nfila);
		//	alert('dato input '+datoInput);
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
			campo = '4%';
			break;
		case 'R':
			campo = '10%';
			break;
		case 'G':
			campo = '21%';
			break;
	}
	return campo;
}

//EN FUNCIONES PHP 
//DETERMINAR si es una ref o un codigoBarras el dato que me pasan para buscar... 
//campoAbuscar = ref , codigoBarras o descripc
//busqueda = dato en input correspondiente
function buscarProducto(campoAbuscar,busqueda){
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
			"campo" 	: campo
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
			var datos = [];
			datos = resultado.datos[0];
			
			if (resultado['Estado'] === 'Correcto') {
				//accedo a los datos que recojo con ayuda de 2 array, 1 global resultado, y otro datos.
				console.log('DATOS: '+datos['CCODEBAR']+' '+datos['CREF']+' '+datos['CDETALLE']+' '+datos['NPCONIVA']+' '+datos['CTIPOIVA']); 
				agregarFila(datos);
				
				
	
				//limpiar formato de input referencia
				resetCampo(campo);
				console.log('tenemos array datos de uno producto');
				return;
			} else {
				if (resultado['Estado'] === 'Listado'){
					alert('Posibles opciones: ');
					resetCampo(campo);
				} else {
					alert(resultado['Estado']);
					resetCampo(campo);
				}
				
				console.log('NO HAY DATOS error buscarProducto');
				return;
			}
		}
	});

}

//http://www.lawebdelprogramador.com/codigo/JQuery/2279-Anadir-y-eliminar-filas-de-una-tabla-con-jquery.html
 //~ //Sera funcion que agrega o elimina linea.
//~ $(function(){
	//~ //le paso un array datos
	//~ $('#agregar').on('click', function(){
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
	nuevaFila += '<td><input id="'+campoUd+'" type="text" name="unidad"  placeholder="unidad" size="4"  value="1" onkeypress="teclaPulsada(event,'+"'Unidad'"+','+nfila+')" ></td>'; //unidad
	
	//si en config peso=si, mostramos columna peso
	if (CONF_campoPeso === 'si'){
		nuevaFila += '<td><input id="C'+nfila+'_Kilo" type="text" name="kilo" size="3" placeholder="peso" value="" ></td>'; //cant/kilo
	} else {
		nuevaFila += '<td style="display:none"><input id="C'+nfila+'_Kilo" type="text" name="kilo" size="3" placeholder="peso" value="" ></td>'; //cant/kilo
	}
	nuevaFila += '<td id="N'+nfila+'_Pvp">'+NPCONIVA+'</td>';
	nuevaFila += '<td id="C'+nfila+'_TipoIva">'+tipoIva(CTIPOIVA)+'</td>';
	nuevaFila += '<td id="N'+nfila+'_Importe" class="importe" >'+NPCONIVA+'</td>'; //importe 
	nuevaFila += '<td class="eliminar"><a onclick="eliminarFila('+nfila+');"><span class="glyphicon glyphicon-trash"></span></a></td>';

		
	nuevaFila +='</tr>';
	
	
	
	
	//$ signifca jQuery 
	//$("#tabla").append(nuevaFila);
	$("#tabla").prepend(nuevaFila);
	
	return;
	

};
 
 
//Sera funcion que agrega o elimina linea.
function eliminarFila(nfila){
	//alert('funcion eliminar');
	// Evento que selecciona la fila y la elimina 
	$(document).on("click",".eliminar",function(){
		var parent = $(this).parents().get(0);
				
		producto[nfila]['Estado']='Eliminado';
		
		//parent es la fila que voy a eliminar 
		//rowIndex es el num de fila que quiero eliminar
		console.log('eliminar '+parent.rowIndex);
		//modificar remove a ocultar fila meter una clase tachado
		$(parent).addClass('tachado'); //añado class al elemento a eliminar
		//$(parent).remove();
	});
	return;
}
//~ //fin funcion que agrega o elimina linea
//************************************************************
