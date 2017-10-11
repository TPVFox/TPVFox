/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogoproductos - Funciones sincronizar.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero - SolucionesVigo
 * @Descripcion	Javascript necesarios para modulo importar DBF.
 * Las funciones que tienen prefijo:
 *  "Import" es que se utilizan solo en importacion de DBF
 *  "Sincro" es que se utilizan solo para sincronizar los datos importados con la base de datos actual..
 * 
 * DEBUG:
 * Una forma de textear el funciomamiento los fallos son :
 * 	- Eliminando algún dbf ¿que no existe?.. 
 * 	- Si cambiamos la estructura de BDFImportar de alguna de las tablas.
 * */
var pulsado = 'Inicio';
var LimiteActual = 0;
var LimiteFinal = 0;
var iconoCargar = '<span><img src="../../css/img/ajax-loader.gif"/></span>';
var iconoCorrecto = '<span class="glyphicon glyphicon-ok-sign"></span>';
var iconoIncorrecto = '<span class="glyphicon glyphicon-remove-sign"></span>';
var tabla_actual = '';
var insert_tablas_global ;
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
	$('#barra').css('width', progreso + '%');
	// Añadimos numero linea en resultado.
	$("#barra").html(progreso + '%');
	return;
	
}
function inicioProcesos(){
	// Objetivo es iniciar los procesos al terminar cargar pagina html Importar_virtuemart.php
	
	
}

function ControlPulsado(pulsado) {
	// Lo que pretendo es tener un proceso que controle y cambio de proceso según sea necesarios.
	// la variable que va controlar es pulsado.
	switch(pulsado) {
						
			case 'preparar_insert':
				// Llamamos a funcion para preparar insert
				PrepararInsert();
				break;
			case 'vaciar_tablas':
				// Llamamos funciones vaciar tablas de BDTpv

				VaciarTablas();
				
				break;
	} 
	return;
}

function VaciarTablas(){
	console.log ( ' ========================= ENTRAMOS EN VACIARTABLAS ===============================')
	
	
	var parametros = {
	"tablas" 	: nombretabla,
	"pulsado" 	: 'Vaciar tablas'
			};
	$.ajax({
		data:  parametros,
		url:   'tareas.php',
		type:  'post',
		beforeSend: function () {
				$("#resultado").html('Vaciando tablas de TPV');
		},
		success:  function (response) {			
			// Cuando se recibe un array con JSON tenemos que parseJSON
			var resultado =  $.parseJSON(response)
			// Recargamos nuevamente pagina
			document.location.href='Importar_virtuemart.php';					
							
		}
	});
}

function PrepararInsert(){
	console.log ( ' ========================= ENTRAMOS EN PREPARAR INSERT ===============================')
	// Quitamos link a icono de PrepararInsert evitamos que vuelva pulsar.
	$("#PrepararInsert").html('<span class="glyphicon glyphicon-log-in"></span>');

	
	var parametros = {
	"tablasImpor" 	: tablaImpor,
	"pulsado" 	: 'Preparar insert'
			};
	$.ajax({
		data:  parametros,
		url:   'tareas.php',
		type:  'post',
		beforeSend: function () {
				$("#resultado").html('Vaciando tablas de TPV');
		},
		success:  function (response) {			
			// Recorremos el objeto tabla.nombretabla.Insert para contar cuantos insert
			var i_tablas = [];
			for (ntabla in response.tabla) {
				//~ console.log("En el índice " + indice + " hay este valor: " + valor+'Este array');
			//~ consolel.log(valor);
			//~ insertObtenido = valor;
			console.log(ntabla + ':' +response.tabla[ntabla].Insert);
			i_tablas[ntabla] = response.tabla[ntabla].Insert
			if (response.tabla[ntabla].descartado.length>0){
				// Para meter clase de color cuando hay descartados.
				var clase='class="alert-danger"';
			} else {
				clase ='';	
			}
			stringPresentar = response.tabla[ntabla].Insert.length +' / <span '+ clase + '>'+ response.tabla[ntabla].descartado.length+'</span>';
			$("#"+ntabla+" > td.inserts").html(stringPresentar);
			
			
			//~ variableGlobal = response.tabla.ntabla;
			//~ console.log (ntabla);				
			};
			// Guardamos comp variable global los inserts.
			insert_tablas_global = i_tablas
			// Ahora enviamos insert para ejecutarlo.
			
			bucleInsert(insert_tablas_global);
		}
	});
	
}

function EjecutarInserts(insert_t,n_tabla) {
	
	console.log(' ========================= EJECUTAMOS INSERT ===============================');
	
	var parametros = {
	"InsertRealizar" 	: insert_t,
	"pulsado" 	: 'Realizar insert'
			};
	$.ajax({
		data:  parametros,
		url:   'tareas.php',
		type:  'post',
		beforeSend: function () {
				$("#resultado").html('Realizando insert en tablas'+n_tabla);
		},
		success:  function (response) {			
			// Recorremos el objeto tabla.nombretabla.Insert para contar cuantos insert
			var resultado =  $.parseJSON(response);
			
			if (typeof resultado['error'] !== 'undefined') {
				// Hubo un error
				alert('tipo error definida');
			} 
			bucleInsert(insert_tablas_global)
		}
	});
	
}

function bucleInsert(insert_tablas){
	// @ Objetivo es hacer los inserts obtenidos .
	// @ insert_tablas 
	//   Es un array ejemplo:
	// [NOMBRETABLA] => Array
	//				(
    //				[Select] => SELECT idArticulo,idTienda,crefTienda FROM `tmp_articulosCompleta`
	//				[Num_Insert_hacer] => 3
	// 				[descartado] => Array
	//						(
	//						)
	//				[Insert] => Array
	//			    	(
	//					[0] => INSERT INTO articulosTiendas (idArticulo,idTienda,crefTienda) VALUES ....
	//					[1] => INSERT INTO articulosTiendas (idArticulo,idTienda,crefTienda) VALUES ("
	//					)
	//				)	
	console.log(' ========================= BUCLE PARA EJECUTAR INSERTS ===============================');
	// Reiniciamos Barra proceso 
	LimiteActual = 0;
	LimiteFinal = 0;
	// Buscamos numero index que estamos.
	idTabla = nombretabla.indexOf(tabla_actual);
	nuevoIndice = idTabla + 1;
	
	
	numMaximo = nombretabla.length; // Obtenemos el indice mayor que puede ser...
	if (nuevoIndice < numMaximo ){
		tabla_actual = nombretabla[nuevoIndice];
		// Obtenemos los inserts de la tabla que vamos ejecutar.
		Nuevo_insert = insert_tablas[tabla_actual];
		console.log ( insert_tablas[tabla_actual]);
		EjecutarInserts(Nuevo_insert,tabla_actual)
	} else {
		// termino...
		alert('termino de importar fichero');
		$(".btn-actualizar").css("display", "block");
	}
	
}


function BucleTablaTemporal(){
	// @ Objetivo :
	// Es recorrer el arrat de tablasTemporales 
	// @ Parametros:
	// NO HAY , son variables globales (tablasTemporales y tablatemporal_actual)
	console.log(' tabla temporales a crear.'+tablasTemporales.length);
	var i = 0;
	var y = 0;
	tablasTemporales.forEach(function(tablatemporal) {
		// Recorremos tablas y creamos las tablas.
		//~ console.log('tablatemporal_actual:'+ tablatemporal_actual['nombre_tabla_temporal']);
		console.log('tablatemporal:'+ tablatemporal['nombre_tabla_temporal']);
		if (typeof tablatemporal_actual === 'undefined'){
				y = 0
		} else if ( tablatemporal_actual['nombre_tabla_temporal'] === tablatemporal['nombre_tabla_temporal']){
			y = i+ 1;
			
		}
		i++;
	});
	// Rellenamos barra proceso.
	lineaF = tablasTemporales.length;
	lineaA = y+1;
	BarraProceso(y,lineaF);
	// Ejecutamos sino se pasa index de la tablatemporales
	if ( y <= tablasTemporales.length-1){
		tablatemporal_actual = tablasTemporales[y];
		CrearTablaTemporal(tablatemporal_actual,y);
	} else {
	// El proceso se termina y se vuelve en CrearTablaTemporal
	BarraProceso(y,lineaF); 
	console.log('Ahora hacesmos las comprobaciones');
	BucleComprobacionesTemporales();
	}
}

function CrearTablaTemporal(tablatemporal,y){
	// Objetivo: 
	// Es crear la tablas temporales en virtuemart.
	var nom_tabla_temporal = tablatemporal['nombre_tabla_temporal'];
	console.log('Tabla temporal que vamos crear'+ nom_tabla_temporal);
	var parametros = {
		"TablaTemporal" 	: tablatemporal,
		"pulsado" 	: 'Crear Tabla Temporal'
				};
		$.ajax({
			data:  parametros,
			url:   'tareas.php',
			type:  'post',
			beforeSend: function () {
					$("#resultado").html('Creamos tabla temporal:'+nom_tabla_temporal);
			},
			success:  function (response) {			
				// Recorremos el objeto tabla.nombretabla.Insert para contar cuantos insert
				var resultado =  $.parseJSON(response);
				console.log(response);
				var num_registro = resultado[nom_tabla_temporal]['Num_articulos'];
				// Ahora metemos los datos en pantalla.
				if (num_registro > 0 ){
					$("#"+nom_tabla_temporal+"> td.num_registros").html(num_registro);
					$("#"+nom_tabla_temporal+"> td.check").html('<span class="glyphicon glyphicon-ok"></span>');
				} else {
					$("#"+nom_tabla_temporal+"> td.check").html('<span class=".glyphicon glyphicon-remove"></span>');
				}
				// Volvemos ejecutar ... 
				
				BucleTablaTemporal();
				
			}
		});
		
	console.log('Terminamos de crear la tabla temporal ' + tablatemporal['nombre_tabla_temporal']);
}
	
	
function BucleComprobacionesTemporales() {
	// @ Objetivo es hacer las comprobaciones en tablas temporales.
	// @ Paramentros:
	// No hay es la varible globar JS comprobaciones
	console.log(' Entramos en Bucle de Comprobaciones Temporales');
	var i = 0;
	var y = 0;
	comprobacionesTemporales.forEach(
		function(comprobacion) {
			//~ console.log(comprobacion['nom_funcion']);
			if (typeof comprobacion_actual === 'undefined'){
				y = 0
			} else if ( comprobacion_actual['nom_funcion'] === comprobacion['nom_funcion']){
				y = i+ 1;
			}
			i++
		}
	);
	// Rellenamos barra proceso.
	lineaF = comprobacionesTemporales.length;
	lineaA = y +1;
	BarraProceso(lineaA,lineaF);
	

	// Ejecutamos sino se pasa index de la tablatemporales recuerda array empieza 0
	if ( y <= comprobacionesTemporales.length-1){
		comprobacion_actual = comprobacionesTemporales[y];
		console.log('Comprobacion a realizar:'+ comprobacion_actual['nom_funcion']);
		console.log('valor LineaA :'+ lineaA);
		console.log('valor LineaF :'+ lineaF);
		ejecutarComprobaciones();
	} else {
	// El proceso se termina y se vuelve en CrearTablaTemporal
	BarraProceso(y,lineaF); 
	console.log('Terminamos comprobaciones.');
	//~ BucleComprobacionesTemporales();
	}
	
}
	
	
function ejecutarComprobaciones(){
	// @ Objetivo:
	// Es ejecutar por AJAX las comprobaciones.
	alert( comprobacion_actual['nom_funcion']);
	//~ var funcion = comprobacion_actual['nom_funcion'];
	var parametros = {
		"funcion" 	:  comprobacion_actual,
		"pulsado" 	: 'Comprobaciones'
				};
		$.ajax({
			data:  parametros,
			url:   'tareas.php',
			type:  'post',
			beforeSend: function () {
					$("#resultado").html('Realizando comprobacion:' + comprobacion_actual['nom_funcion']);
			},
			success:  function (response) {			
				// Recorremos el objeto tabla.nombretabla.Insert para contar cuantos insert
				var resultado =  $.parseJSON(response);
				console.log(response);
				//~ var num_registro = resultado[nom_tabla_temporal]['Num_articulos'];
				//~ // Ahora metemos los datos en pantalla.
				//~ if (num_registro > 0 ){
					//~ $("#"+nom_tabla_temporal+"> td.num_registros").html(num_registro);
					//~ $("#"+nom_tabla_temporal+"> td.check").html('<span class="glyphicon glyphicon-ok"></span>');
				//~ } else {
					//~ $("#"+nom_tabla_temporal+"> td.check").html('<span class=".glyphicon glyphicon-remove"></span>');
				//~ }
				// Volvemos ejecutar ... 
				
				BucleComprobacionesTemporales()				
			}
		});
	
}
	
	
	
	


