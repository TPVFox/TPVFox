/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogoproductos - Funciones sincronizar.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero - SolucionesVigo
 * @Descripcion	Javascript necesarios para modulo importar DBF.
 * */
var pulsado = 'Inicio';
var LimiteActual = 0;
var LimiteFinal = 0;
var icono = '<span><img src="../../css/img/ajax-loader.gif"/></span>';
var iconoCorrecto = '<span class="glyphicon glyphicon-ok-sign"></span>';
var campos = [];
var ficheroActual = '';

//variable matriz con los ficheros que vamos a obtener
//ojo! cuando vayas al array modifica func 
var fichero = [];
fichero[0]='proveedo.dbf';
fichero[1]='albprot.dbf'; 
fichero[2]='albprol.dbf';
fichero[3]='articulo.dbf';


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
				//llamar func que hace bucle de la matriz de nombres tabla (fichero)
				
				bucleFicheros();
				break;
	} 
	return;
}


//matriz en variables
function bucleFicheros(){
	LimiteActual = 0;
	LimiteFinal = 0;
	campos = [];
	//stop parar bucle de lectura de ficheros
	stop = '';
	//me indica el ultimo fichero de la matriz (articulo.dbf) y asi paro el bucle
	ultimo = fichero[fichero.length-1];
	//~ console.log('ultimo fichero '+ultimo);
		
	switch(ficheroActual){
		case '':
			ficheroActual=fichero[0];
			break;
		case 'proveedo.dbf':
			ficheroActual=fichero[1];
			break;
		case 'albprot.dbf':
			ficheroActual=fichero[2];
			break;
		case 'albprol.dbf':
			ficheroActual=fichero[3];
			break;
		case ultimo:
			stop= 'parar';
			break
		}
	$("#idCabeceraBarra").html('<b>Fichero: '+ficheroActual+'</b>');
	if (stop !='parar') {
		EstrucTabla (ficheroActual);
	}		
}


//recibir nombre de la tabla
function EstrucTabla (nombreTabla){
	console.log('Iniciando funcion de EstruTabla');

	//estructura articulos
	var parametros = {
	"Fichero" 	: nombreTabla,
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
					var resultado =  $.parseJSON(response)
										
					if (resultado['Estado'] === 'Correcto') {
						LimiteFinal = resultado['numeroReg'];
						console.log('Numero de registros tabla: '+ LimiteFinal);
						// Obtenemos numero campos
						NumCampos = resultado['NumCampos'];
						console.log(NumCampos);
						
						campos = []
						for (i = 1; i < NumCampos; i++){
						 campos[i]= {campo :resultado[i]['campo'],tipo :resultado[i]['tipo']};	
						}					
					
						ObtenerDatosTabla();
						return;
					} else {	
						//tenemos que identificar en que fichero es el error 
						//para mostrar en el fichero correcto el error de estructura usamos el id del array identificandolo
						//array.indexOf("nombreFichero"); consigo el indice del fichero en el array
						idMatrizFichero = fichero.indexOf(ficheroActual);
						//nos imprime en pantalla (tabla) el error
						var x = document.getElementsByClassName("CLeerEsctructura");
						x[idMatrizFichero].innerHTML = "Error obtener estructura ";
						return;
					}					
			}
		});
}


function ObtenerDatosTabla(){
	// Intervalo minimo... 
	// le paso objetos
	BarraProceso(LimiteActual,LimiteFinal);
	if (LimiteActual < LimiteFinal) {
		diferencia = LimiteFinal - LimiteActual;
		if (diferencia >5000 ) {
			diferencia= 4999;
			TopeRegistro= LimiteActual + diferencia;
		} else {
			TopeRegistro = LimiteFinal;
		}
		console.log('Obtener datos funcion js');
		
			var parametros = {
		"lineaI" 	: LimiteActual,
		"lineaF" 	: TopeRegistro,
		"pulsado" 	: 'obtenerDbf',
		"Fichero" 	: ficheroActual,
		"campos" 	: campos
				};
		$.ajax({
			data:  parametros,
			url:   'tareas.php',
			type:  'post',
			beforeSend: function () {
					$("#resultado").html('Obteniendo daatos de tabla ');
			},
			success:  function (response) {	
				// Cuando se recibe un array con JSON tenemos que parseJSON
				var resultado =  $.parseJSON(response);				
				//muestra object Object
				console.log(resultado);	
				//vuelvo a llamar para crear bucler 
				LimiteActual = LimiteActual + diferencia + 1;
				console.log('====== DESPUES de ajax ====================');
				console.log('limiteactual:'+LimiteActual);
				console.log('limite final:'+LimiteFinal);
				console.log('diferencia:'+diferencia);	
				//inicio bucle
				ObtenerDatosTabla(campos);
				
				if (resultado['Estado'] === 'Correcto') {
					console.log('entro en estado correcto '+LimiteActual);
					return;
						// Pendiente punto siguiente..
				} else {
					//~ alert('ERROR en la obtencion de datos de la tabla VER CONSOLA ');
					console.log(response);
					
					//tenemos que identificar en que fichero es el error 
					//para mostrar en el fichero correcto el error de estructura usamos el id del array identificandolo
					//array.indexOf("nombreFichero"); consigo el indice del fichero en el array
					idMatrizFichero = fichero.indexOf(ficheroActual);
					//nos imprime en pantalla (tabla) el error
					var x = document.getElementsByClassName("CLeerDbf");
					x[idMatrizFichero].innerHTML = "Error obtener datos tabla ";
					return;
				}
			}
		});		
	} else {
		//recorre el sig. fichero
		bucleFicheros();
		//alert('termine de obtener datos tabla');
	}
}

function Ciclo(f){
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
		console.log(' Hubo un error porque lo intento 20 veces....');
		$("#resultado").html('Error lo intento 20 veces, funcion' +f);
	}
}
