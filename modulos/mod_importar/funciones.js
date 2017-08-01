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
var iconoIncorrecto = '<span class="glyphicon glyphicon-remove-sign"></span>';
var campos = [];
var ficheroActual = '';

//variable matriz con nombre tablas que vamos importar ( Bases Datos importar).
//lo nombres de las tablas son los mismos de los ficheros que vamos a obtener
//ojo! cuando vayas al array modifica func tenemos que añadir fila a la tabla errores en importar html
var nombretabla = [];
// No le pongo extension, ya utilizo este mismo array para saber si existe tabla en mysql o si la creamos.
nombretabla[0]='proveedo';
nombretabla[1]='albprot'; 
nombretabla[2]='albprol';
nombretabla[3]='articulo';


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
	console.log( '========================== Entramos en bucle  =============================================');
	console.log('Fichero Actual:'+ficheroActual);

	LimiteActual = 0;
	LimiteFinal = 0;
	campos = []; // Reiniciamos campos ya que es un bucle
	ultimo = nombretabla[nombretabla.length-1]; // El numero de tablas que vamos analizar
	tablaActual= '';
	tablaActual = ficheroActual.slice(0, -4); // Quitamos los ultimos cuatro caracteres... (.dbf)
	console.log('Tabla Actual sin extension:'+tablaActual);
	idFichero = nombretabla.indexOf(tablaActual);
	numFicheros = nombretabla.length;
	nuevoIndice = idFichero + 1;
	if (nuevoIndice < numFicheros ){
		ficheroActual = nombretabla[nuevoIndice]+'.dbf';
		console.log ( 'Fichero Actual:'+ ficheroActual)
		$("#idCabeceraBarra").html('<b>Fichero: '+ficheroActual+'</b>');
		EstrucTabla (ficheroActual);
	}
	
}


//recibir nombre de la tabla
function EstrucTabla (nombreTabla){
	console.log('=============Iniciando funcion de EstruTabla=============');
	console.log(nombreTabla);
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
					$("#resultado").html('Obteniendo estructura de tabla');
			},
			success:  function (response) {			
					// Cuando se recibe un array con JSON tenemos que parseJSON
					var resultado =  $.parseJSON(response)
										
					if (resultado['Estado'] === 'Correcto') {
						console.log('tenemos estructura dbf para crear tabla');
						LimiteFinal = resultado['numeroReg'];
						console.log('Numero de registros tabla: '+ LimiteFinal);
						// Obtenemos numero campos
						NumCampos = resultado['NumCampos'];
						console.log(NumCampos);
						
						campos = []
						for (i = 1; i < NumCampos; i++){
						 campos[i]= {campo :resultado[i]['campo'],tipo :resultado[i]['tipo'],longitud :resultado[i]['longitud'],decimal :resultado[i]['decimal']};	
						 //consigo los campos de la tabla
						// console.log('estructura '+campos[i]['campo']+' '+campos[i]['tipo']+' '+campos[i]['longitud']+' '+campos[i]['decimal'] );
						 
						}					
					
						comprobarTabla();
						return;
					} else {	
						// Con la instruccion:
						// document.getElementsByClassName("CLeerEstructura") 
						// Es obtener un array con los objetos que tiene la clase que sel indica ( " CLeerEstructura
						// Con la instruccion:
						// idMatrizFichero = fichero.indexOf(ficheroActual);
						// Lo que hace es obtener el numero index de array de fichero.
						// Asi podemos insertar datos en la clase.
						// NOTA: 
						// Fundamental tener que el orden de los ID sea el mismo que la tabla en html
						var x = document.getElementsByClassName("CLeerEstructura");
						tablaActual = ficheroActual.slice(0, -4); // Quitamos los ultimos cuatro caracteres... (.dbf)
						idMatrizFichero = nombretabla.indexOf(tablaActual);
						//nos imprime en pantalla (tabla) el error
						x[idMatrizFichero].innerHTML = "Error obtener estructura ";
						return;
					}					
			}
		});
}


function comprobarTabla(){
	// Comprobamos si existe tabla y los campos son correctos  en la BDImportar.
	//     - Si existes y es correcto ejecutamos obtener datos
	//     - No existe o esta mal los campos, pues advertimos del error con Alert y indicamos que se crea 
	tablaActual = ficheroActual.slice(0, -4); // Quitamos los ultimos cuatro caracteres... (.dbf)
	var parametros = {
	"Fichero" 	: tablaActual,
	"pulsado" 	: 'Comprobar-tabla',
	'campos'	: campos
			};
	$.ajax({
		data:  parametros,
		url:   'tareas.php',
		type:  'post',
		beforeSend: function () {
			$("#resultado").html('Comprobamos la tabla si existe o es correcta.Nombre tabla:'+tablaActual);
			console.log('******** estoy en comprobar tabla ****************');
		},
		success:  function (response) {
			// Cuando se recibe un array con JSON tenemos que parseJSON
			var resultado =  $.parseJSON(response);
			console.log('comprobar accion despues de parse '+resultado['accion-creado']);

			console.log('Dropear-table es ' + typeof(resultado['dropear-tabla']));
			if (resultado['dropear-tabla']){
				PintarIcono(tablaActual, "CEstruct", false);
				PintarIcono(tablaActual, "CBorrar");
			}

			if (resultado['accion-creado'] === 'Creada estructura tabla'){
				PintarIcono(tablaActual, "CCrear");
			} else if (resultado['accion-creado'] === ''){
				PintarIcono(tablaActual, "CEstruct");
			}

			if (resultado['Estado'] === 'Correcto') {
				// Respuesta correcta...		
				console.log( 'EXISTE tabla '+ tablaActual+' vamos a obtener datos');
				ObtenerDatosTabla();
				return;
			} else {
				// Error en respuesta.
				console.log(' No existe tabla '+ tablaActual);
				console.log(response);
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
		console.log('Antes Ajax FicheroActual:' + ficheroActual);
		nombrefichero = ficheroActual;
		var parametros = {
			"lineaI" 	: LimiteActual,
			"lineaF" 	: TopeRegistro,
			"pulsado" 	: 'obtenerDbf',
			"Fichero" 	: nombrefichero,
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

				if (resultado['Estado'] === 'Correcto') {
					console.log('entro en estado correcto '+LimiteActual);
					//inicio bucle
					ObtenerDatosTabla(campos);
					return;
					// Pendiente punto siguiente..
				} else {
					//~ alert('ERROR en la obtencion de datos de la tabla VER CONSOLA ');
					console.log(response);

					//tenemos que identificar en que fichero es el error 
					//para mostrar en el fichero correcto el error de estructura usamos el id del array identificandolo
					//array.indexOf("nombreFichero"); consigo el indice del fichero en el array
					tablaActual = ficheroActual.slice(0, -4); // Quitamos los ultimos cuatro caracteres... (.dbf)
					idMatrizFichero = nombretabla.indexOf(tablaActual);
					console.log('tablaActual:'+ tablaActual);

					//nos imprime en pantalla (tabla) el error
					var x = document.getElementsByClassName("CLeerDbf");
					//muestra errores 
					console.log('IdMatrizFichero:'+ idMatrizFichero);
					x[idMatrizFichero].append("\n Error obtener datos tabla: ","limite actual "+LimiteActual+" limite final "+LimiteFinal);
					
					ObtenerDatosTabla();
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

function PintarIcono(tablaActual, className, ok=true){
	idMatrizFichero = nombretabla.indexOf(tablaActual);
	console.log('idMatrizFichero: '+ idMatrizFichero);
	console.log('idfichero '+idMatrizFichero+'*********');
	var x = document.getElementsByClassName(className);
	console.log('x:' + x[2]);
	if (ok){
		$(x[idMatrizFichero]).append(iconoCorrecto);
	} else {
		$(x[idMatrizFichero]).append(iconoIncorrecto);
	}
}
