/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogoproductos - Funciones sincronizar.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero - SolucionesVigo
 * @Descripcion	Javascript necesarios para modulo importar DBF.
 * */
var pulsado = '';
var iconoCargar = '<span><img src="../../css/img/ajax-loader.gif"/></span>';
var iconoCorrecto = '<span class="glyphicon glyphicon-ok-sign"></span>';
var iconoIncorrecto = '<span class="glyphicon glyphicon-remove-sign"></span>';
var resultado = [] ;

//funciones que tenia ricardo en html dentro de <script>
function teclaPulsada(event,id){
	if(event.keyCode == 13){
		ContadorPulsaciones= 0;
		datoInput = obtenerdatos(id);
		campo = nombreCampo(id);
		respuesta = buscarProducto(campo,datoInput);
		alert('id '+campo+' datos: '+datoInput);
	} else {
		if (id === 'C0_Descripcion'){
			respuesta = obtenerdatos(id);
			if (respuesta.length > 3){
				alert('Pendiente select autocompletado:'+respuesta.length);
			}
		}
	}
}
//quiero conseguir valor del campo
function obtenerdatos(id){
	var aux = document.getElementById(id);
	//console.log('Ver id '+aux); //Ver id [object HTMLInputElement]
	return aux.value;
}

//case de nombreCampo = mysql , = html, 
//con el id='C0_Codbarras' recojo el valor del campo en funcion obtener datos
// pero necesito  nombreCampo = 'CCODEBAR' para mysql
function nombreCampo(campo){
	switch(campo) {
		case 'C0_Codbarras':
			campo = 'CCODEBAR';
			break;
		case 'C0_Referencia':
			campo = 'CREF';
			break;
		case 'C0_Descripcion':
			campo = 'CDETALLE';
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
			resultado =  $.parseJSON(response);
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
				console.log('tenemos array datos de uno producto');
				return;
			} else {
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
	//var datos = 22;
	var nuevaFila = '<tr>';
	
	var CCODEBAR = datos['CCODEBAR'];
	var CREF = datos['CREF'];
	var CDETALLE = datos['CDETALLE'];
	var NPCONIVA = datos['NPCONIVA'];
	var CTIPOIVA = datos['CTIPOIVA'];
		
		//campos: CCODEBAR	CREF	CDETALLE	UNID	CANT/KILO	NPCONIVA	CTIPOIVA	IMPORTE
		
		//	nuevaFila += "<td><input id='id' type='text' name='CCODEBAR' value='$_POST['CCODEBAR'.i])' size='1'/></td>";
		//nuevaFila += '<td>'+datos['CCODEBAR']+'</td>';
	nuevaFila += '<td></td>'; //num linea
	nuevaFila += '<td>'+CCODEBAR+'</td>';
	nuevaFila += '<td>'+CREF+'</td>';
	nuevaFila += '<td>'+CDETALLE+'</td>';
	nuevaFila += '<td></td>'; //unidad
	nuevaFila += '<td></td>'; //cant/kilo
	nuevaFila += '<td>'+NPCONIVA+'</td>';
	nuevaFila += '<td>'+CTIPOIVA+'</td>';
	nuevaFila += '<td></td>'; //importe 
		
	nuevaFila +='</tr>';
	$("#tabla").append(nuevaFila);
};
 
 
 
	//~ // Evento que selecciona la fila y la elimina 
	//~ $(document).on("click",".eliminar",function(){
		//~ var parent = $(this).parents().get(0);
		//~ $(parent).remove();
	//~ });
//~ });
//~ //fin funcion que agrega o elimina linea
//************************************************************
