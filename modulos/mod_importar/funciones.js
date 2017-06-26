/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogoproductos - Funciones sincronizar.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero - SolucionesVigo
 * @Descripcion	Javascript necesarios para modulo importar DBF.
 * */
var pulsado = 'Inicio'
var LimiteActual = 0;
var LimiteFinal = 0;
var icono = '<span><img src="../../css/img/ajax-loader.gif"/></span>';
var iconoCorrecto = '<span class="glyphicon glyphicon-ok-sign"></span>';

// Funcion para mostrar la barra de proceso..
function BarraProceso(lineaA,lineaF) {
	// Esta fucion debería ser una funcion comun , por lo que se debería cargar el javascript comun y ejecutar...
	// Script para generar la barra de proceso.
	// Esta barra proceso se crea con el total de lineas y empieza mostrando la lineas
	// que ya estan añadidas.
	// NOTA:
	// lineaActual no puede ser 0 ya genera in error, por lo que debemos sustituirlo por uno
	if (lineaA == 0 ) {
		lineaA = 1;
	}
	if (lineaF == 0) {
	 alert( 'Linea Final es 0 ');
	 return;
	}
	var progreso =  Math.round(( lineaA *100 )/lineaF);
	$('#bar').css('width', progreso + '%');
	// Añadimos numero linea en resultado.
	document.getElementById("bar").innerHTML = progreso + '%';  // Agrego nueva linea antes 
	return;
	
}
function Inicio (pulsado) {
	// Lo que pretendo es tener un proceso que controle y cambio de proceso según sea necesarios.
	// la variable que va controlar es pulsado.
	
	switch(pulsado) {
			case 'pulso_inicio':
				// Acaba de cargar javascript, por lo que inicia proceso.
				Importar();
				break;
	} 
	
}


function Importar (){
	alert('algo');
	//estructura articulos
	var parametros = {
	//~ "lineaI" 	: linea,
	//~ "lineaF" 	: lineaF,
	//~ "Fichero" 	: fichero,
	"pulsado" 	: 'Inicio'
			};
	$.ajax({
			data:  parametros,
			url:   'tareas.php',
			type:  'post',
			beforeSend: function () {
					$("#resultado").html('Obteniendo estructura de tabla <span><img src="./img/ajax-loader.gif"/></span>');
			},
			success:  function (response) {
					// Cuando se recibe un array con JSON tenemos que parseJSON
					//~ var resultado =  $.parseJSON(response)
					//~ $("#resultado").html('Linea Inicio:'+resultado['Inicio']+'<br/>'
									//~ +'Linea Final:'+resultado['Final']+'<br/>'
									//~ );
					//~ // Si hay un mal insert deberiamos contarlos y anotarlo aqui.
					//~ if (resultado['Resultado'] != "Correcto el insert" ) {
					//~ // Primero cambiamos la clase , para poner advertencia.
					//~ $('#ErrorInsert').addClass('alert alert-danger');
					//~ $("#ErrorInsert").html('<strong>Error INSERT </strong>'+'<br/>'+' Ver console de javascript, error fichero de msql_csv.php');
					//~ console.log("Responde");
					//~ console.log(response.toString());
					//~ }
					console.log(response.toString());


			}
		});

}



function Ciclo(f) {
	// El objetivo de esta funcion volver a ejectuar la funcion
	// y intentarlo 20 veces, si fuera necesario.
	// Si fallara , mostraría un error diciendo que funcion no respondió.
	contador = contador +1;
	$("#resultado").html('Esperando respuesta intento:'+ contador +' funcion:'+f);
	// Solo hacemos 20 intentos ... 
	if (contador<20){
		// Ahora comprobamos la funcion que llamo al esta.
		switch(f) {
			case 'Contar':
				ContarProductoVirtuemart();
				break;
			case 'BuscarError':
				ComprobarRefVirtuemart(paso_actual);
				break;
			case 'Esperar':
				ComprobarRefVirtuemart(paso_actual);
				break;
		} 
	} else {
		console.log(' Hubo un error porque lo intento 20 veces....')
		$("#resultado").html('Error lo intento 20 veces, funcion' +f);

	}
}
