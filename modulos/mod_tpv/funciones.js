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


//funciones que tenia ricardo en html dentro de <script>
function teclaPulsada(event,id){
	if(event.keyCode == 13){
		ContadorPulsaciones= 0;
		datoInput = obtenerdatos(id);
		respuesta = buscarProducto(id,datoInput);
		alert('id '+id+' datos: '+datoInput+' buscarProd '+respuesta);
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
	console.log('Ver id '+aux); //Ver id [object HTMLInputElement]
	return aux.value;
}

//script en tpv html 
//~ function Inicio (pulsado) {
	//~ // Objetivo:
	//~ // Focus de input
	//~ case 'nuevo':
		//~ // Acaba de cargar javascript, por lo que inicia proceso.
		//~ //llamar func 
		//~ alert('case js');
	
		//~ break;
	
	
	//~ return;
//~ }


//EN FUNCIONES PHP 
//DETERMINAR si es una ref o un codigoBarras el dato que me pasan para buscar... 
//campoAbuscar = ref , codigoBarras o descripc
//busqueda = dato en input correspondiente
function buscarProducto($campoAbuscar,$busqueda){
	// Objetivo:
	//parametros :
	//campo input 
	//valor campo 
	// los envio a tareas, alli llamo a la funcion de buscarProducto PHP
	// recibo array con datos y trabajo con ellos, seria enviarlos a agregarFila js.
	console.log('entramos en buscarProducto js');
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
			console.log('******** estoy en buscar producto por ref ****************');
		},
		success:  function (response) {
		var resultado =  $.parseJSON(response);
			console.log('response ajax '+resultado);
			
			if (resultado['Estado'] === 'Correcto') {
				console.log('tenemos array datos de uno producto');
			} else {
				console.log('NO HAY DATOS error buscarProducto');
			}
		
		}
	}
	
	}
	
	
}

//http://www.lawebdelprogramador.com/codigo/JQuery/2279-Anadir-y-eliminar-filas-de-una-tabla-con-jquery.html
 //~ //Sera funcion que agrega o elimina linea.
$(function(){
	// Clona la fila oculta que tiene los campos base, y la agrega al final de la tabla
	 //~ $("#agregar").on("click", function(){
		//~ $("#tabla tbody tr:eq(0)").clone().removeClass('fila-base').appendTo("#tabla tbody");
	 //~ });
	$("#agregar").click(function(){
		//obtenemos num filas (td) que tiene la 1ª columna
		//tr del id "tabla"
	});
 
 
 
 
 
	// Evento que selecciona la fila y la elimina 
	$(document).on("click",".eliminar",function(){
		var parent = $(this).parents().get(0);
		$(parent).remove();
	});
});
//~ //fin funcion que agrega o elimina linea
//************************************************************
