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
		campo = nombreCampo(id);
		respuesta = buscarProducto(campo,datoInput);
		alert('id '+campo+' datos: '+datoInput+' buscarProd '+respuesta);
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
			var resultado =  $.parseJSON(response);
			console.log('response ajax '+response);
			
			if (resultado['Estado'] === 'Correcto') {
				console.log('tenemos array datos de uno producto');
			} else {
				console.log('NO HAY DATOS error buscarProducto');
			}
		}
	});

}

//http://www.lawebdelprogramador.com/codigo/JQuery/2279-Anadir-y-eliminar-filas-de-una-tabla-con-jquery.html
 //~ //Sera funcion que agrega o elimina linea.
$(function(){
	// Clona la fila oculta que tiene los campos base, y la agrega al final de la tabla
	 //~ $("#agregar").on("click", function(){
		//~ $("#tabla tbody tr:eq(0)").clone().removeClass('fila-base').appendTo("#tabla tbody");
	 //~ });
	//~ $("#agregar").on("click", function(){
		//~ //obtenemos num filas (td) que tiene la 1ª columna
		//~ //tr del id "tabla"
		//~ var numFilas = $("#tabla tr:first td").length;
		//~ //obtenemos total de columns tr del id "tabla"
		//~ var numColumna = $("#tabla tr").length;
		//~ console.log('numFilas: '+numFilas+' numColumns: '+numColumna);
		
		//~ var nuevaFila = '<tr>';
		//~ var i = 0;
		//~ for (i=0; i<numFilas; i++){
			//~ //agregamos columnas
		
			//~ nuevaFila += "<td><input id='id' type='text' name='CCODEBAR' value='$_POST['CCODEBAR'.i])' size='1'/></td>";
			//~ nuevaFila += '<td> dentro </td>';
		//~ }
		//~ //agregamos columna con numTotal de columns
		//~ // agregamos 1 al total, ya que cuando cargamos los valores
		//~ //para la columna, todavia no esta agregada
		//~ nuevaFila +='<td>'+(numColumna+1)+' columnas';
		//~ nuevaFila +='</tr>';
		//~ $("#tabla").append(nuevaFila);
	//~ });
 
 
		//~ //$('#tabla > tbody:last').append('<tr id="Row0"><td>Ultima</td></tr>');
		
	//~ }
 
	// Evento que selecciona la fila y la elimina 
	$(document).on("click",".eliminar",function(){
		var parent = $(this).parents().get(0);
		$(parent).remove();
	});
});
//~ //fin funcion que agrega o elimina linea
//************************************************************
