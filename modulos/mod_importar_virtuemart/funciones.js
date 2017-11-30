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

//~ var Global1 = [];
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
function InicioImportar(){
	// Objetivo es iniciar el proceso de importar
	var parametros = {
	"configuracion" 		: configuracion,
	"pulsado" 		: 'Inicio Importar',
	"mensaje_log" 	: 'Se inicio IMPORTACION'
			};
	$.ajax({
		data:  parametros,
		url:   'tareas.php',
		type:  'post',
		beforeSend: function () {
				$("#resultado").html('Iniciando Importacion de TPV');
		},
		success:  function (response) {			
			// Ya grabamos inici de importacion de log mod_impor_virtuemart
            BucleTablaTemporal();
		}
	});
	
	
}

function InicioActualizar(){
	// Objetivo es iniciar el proceso de importar
    var parametros = {
	"configuracion" 		: configuracion,
	"pulsado" 		: 'Inicio Actualizar',
	"mensaje_log" 	: 'Se inicio ACTUALIZACION'
			};
	$.ajax({
		data:  parametros,
		url:   'tareas.php',
		type:  'post',
		beforeSend: function () {
				$("#resultado").html('Iniciando Actualizacion de TPV');
		},
		success:  function (response) {			
			// Ya grabamos inici de importacion de log mod_impor_virtuemart       
            BucleTablaTemporal();
		}
	});
	
	
}

function VaciarTablas(){
	//@ Objetico es eliminar las tablas de TPV
	console.log ( ' ========================= ENTRAMOS EN VACIARTABLAS ===============================');
	
	
	var parametros = {
	"tablas" 		: nombretabla,
	"pulsado" 		: 'Vaciar tablas',
	"mensaje_log" 	: 'Se borra tablas de TPV'
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
	// Ajax 'Preparar Insert'
	// @ Objetivo es ejecutar funcion php prepararInsertTablasBDTpv
	// para obtener array de inserts de todas tablas.
	console.log ( ' ENTRAMOS EN PREPARAR INSERT ');
		
	var parametros = {
	"tablasImpor" 	: tablaImpor,
	"pulsado" 	: 'Preparar insert'
			};
	$.ajax({
		data:  parametros,
		url:   'tareas.php',
		type:  'post',
		beforeSend: function () {
				$("#resultado").html('Preparando inserts de todas tablas');
		},
		success:  function (response) {			
			// Obtenemos una array de la tablas con array con los datos insert y descartados.
			var i_tablas = [];
			var tablaNombre = '';
			for (ntabla in response) {
				// Recorremos las tablas
				tablaNombre = tablaImpor[ntabla].nombre; // Obtenemos nombre de tabla.
				console.log('Tabla:'+tablaNombre+ ' ->indice :'+ ntabla);
				tabla = response[ntabla][tablaNombre] ;
				// Recorremos los distinto tipos_inserts que pueda tener la tabla.
				i = 0 ;
				tabla.forEach(function(datos){
					if (typeof datos.error !== 'undefined'){
						// Hubo error..
						console.log ( 'Error en ese tipo insert para la tabla'+tablaNombre);
						stringPresentar = ' Error en este tipo insert';
						$("#"+ntabla+" > td.inserts").html(stringPresentar);

					} else {
						// Ahora añadimos las consultas insert que tenemos que realizar esa tabla de tipo insert
						if ( i === 0 ){
						i_tablas[tablaNombre] = datos.Insert;
						console.log(i_tablas[tablaNombre] );
						} else {
						i_tablas[tablaNombre].push.apply(i_tablas[tablaNombre],datos.Insert);
						console.log('Deberia hacer un añadir...con push');
						console.log(i_tablas[tablaNombre] );
						}
						// Ahora contamos los descartados para este tipo_Insert
						if (datos.descartado.length>0){
							// Para meter clase de color cuando hay descartados.
							var clase='class="alert-danger"';
							console.log('Descartados:');
							console.log(datos.descartado);
						} else {
							clase ='';	
						}
						stringPresentar = datos.Insert.length +' / <span '+ clase + '>'+ datos.descartado.length+'</span>';
						$("#"+ntabla+" > td.inserts").html(stringPresentar);

					}
					i = i +1;
					console.log(datos);
				});
				
			};
			// Guardamos comp variable global los inserts.
			insert_tablas_global = i_tablas
			// Ahora enviamos insert para ejecutarlo.
			
			BucleInsert(insert_tablas_global);
		}
	});
	
}

function EjecutarInserts(insert_t,n_tabla) {
	// @ Ajax
	// @ Objetivo hacer insert por tabla
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
			console.log('Respuesta de insert en tablas'+n_tabla);

			if (typeof resultado['error'] !== 'undefined') {
				// Hubo un error
				alert('tipo error en '+n_tabla);
			} 
			BucleInsert(insert_tablas_global)
		}
	});
	
}

function BucleInsert(insert_tablas){
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
		//~ console.log ( insert_tablas[tabla_actual]);
		EjecutarInserts(Nuevo_insert,tabla_actual)
	} else {
		// termino...
		alert('termino de importar fichero');
		$(".btn-actualizar").css("display", "block");
	}
	
}


function BucleTablaTemporal(){
	// @ Objetivo :
	// Es recorrer el array de tablasTemporales 
	// @ Parametros:
	//   tipo: String -> importar / actualizar, indica que vamos hacer despues de hacer al termina crear.
	console.log(' Bucle');
	var tipo = configuracion['0'].tipo;
	var i = 0;
	var y = 0;
	tablasTemporales.forEach(function(tablatemporal) {
		// Recorremos tablas y creamos las tablas.
		//~ console.log('tablatemporal_actual:'+ tablatemporal_actual['nombre_tabla_temporal']);
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
		CrearTablaTemporal(tablatemporal_actual);
	} else {
		// El proceso se termina y se vuelve en CrearTablaTemporal
		BarraProceso(y,lineaF); 
		// Ahora debemos indicar que vamos hacer 
		console.log('Tipo es '+tipo);
		if (tipo === 'importar'){
			console.log('Estamos en importar, ahora vamos a comprobaciones');
			BucleComprobacionesTemporales();
		}
		if (tipo === 'actualizar'){
			alert(' Ahora deberíamos continuar con actualizar');
			
		}
	}
	
}

function CrearTablaTemporal(tablatemporal){
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
		EjecutarComprobaciones(y);
	} else {
	// El proceso se termina y se vuelve en CrearTablaTemporal
	BarraProceso(y,lineaF); 
	console.log('Terminamos comprobaciones.');
	PrepararInsert();
	}
	
}
	
	
function EjecutarComprobaciones(index){
	// @ Objetivo:
	// Es ejecutar por AJAX las comprobaciones.
	// @ Paramentros:
	// 		index = > Es el numero de indice en el que esta comprobacion_actual con respecto comprobaciones
	//				Este parametro lo necesito para mostrar el resultado, saber que td
	console.log('============ Realizando comprobacion:' + comprobacion_actual['nom_funcion']+ '====================');
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
				// Obtenemos los subprocesos que deberíamos obtener respuesta.
				var subprocesos = comprobacion_actual['subprocesos'];
				var resultado =  $.parseJSON(response);
				var errores = 0;
				console.log(response);
				// Recorremos subproceso
				var contador = 0;
				// Recorremos los subproceso que tenemos asignados.(lo nombre tiene que coincidir con 
				// los objetos de resultado, sino da un error.
				subprocesos.forEach(
					function(subproceso){
						contador = contador +1
						console.log(subproceso);
						console.log(resultado);
						if (resultado[subproceso]['estado'] !=  true){
							console.log(' estado:'+ resultado[subproceso]['estado']);
							errores ++;
						}
					}
				);
				var classError = '';
				if (errores > 0 ){
					classError = 'class="alert-danger"';
				}
				console.log('Errores encontrados:'+errores);
				$("#comprobacion_"+index+"> td.errores").html(contador + ' / <span '+ classError +'>'+errores+'</span>');
				// Ahora continuamos con el bucle
				BucleComprobacionesTemporales()				
			}
		});
	
}
