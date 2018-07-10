// Funciones necesarias para plugin de mod_producto de vehiculos.



function  SeleccionMarca(event){
	// @Objetivo :
	// Ejecutar en servidor de web funcion que reste stock de productos
	// Pendiente el que no lo haga dos vez , si hace clic o intro muy rapido.
	console.log(event.target);
	var idMarca = $('select[id=myMarca]').val();
	console.log(ruta_plg_vehiculos);
	var parametros = {
		"pulsado" :  'BuscarModelos',
		"idMarca"    : idMarca
	};
	$.ajax({
		data       : parametros,
		url        : ruta_plg_vehiculos+'tareas_vehiculos.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Obteniendo modelos de vehiculos  ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de obtener modelos de una marca');
			var resultado =  $.parseJSON(response);
			$('#myModelo').removeAttr("disabled");
			$('#myModelo >option').remove();
			$("#myModelo").append(resultado.options);
			
		}
	});
	
} 

function  SeleccionModelo(event){
	// @Objetivo :
	// Ejecutar en servidor de web funcion que reste stock de productos
	// Pendiente el que no lo haga dos vez , si hace clic o intro muy rapido.
	console.log(event.target);
	var idModelo = $('select[id=myModelo]').val();
	console.log(ruta_plg_vehiculos);
	var parametros = {
		"pulsado" :  'BuscarVersionVehiculo',
		"idModelo"    : idModelo
	};
	$.ajax({
		data       : parametros,
		url        : ruta_plg_vehiculos+'tareas_vehiculos.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Obteniendo modelos de vehiculos  ****************');
		},
		success    :  function (response) {
			console.log('Respuesta de obtener versiones de un modelo');
			var resultado =  $.parseJSON(response);
			$('#myVersion').removeAttr("disabled");
			$('#myVersion >option').remove();
			$("#myVersion").append(resultado.options);
			
		}
	});
	
} 

function  SeleccionVersion(event="",dedonde=""){
	// @Objetivo :
	// Ejecutar en servidor de web funcion que reste stock de productos
	// Pendiente el que no lo haga dos vez , si hace clic o intro muy rapido.
	console.log(event.target);
	//~ var idVersion = $('select[id=myVersion]').val();
    var idVersion =event;
	console.log(idVersion);
	var parametros = {
		"pulsado" :  'BuscarVehiculo',
		"idVersion"    : idVersion
	};
	$.ajax({
		data       : parametros,
		url        : ruta_plg_vehiculos+'tareas_vehiculos.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Obteniendo modelos de vehiculos  ****************');
		},
		success    :  function (response) {
			console.log('Respuesta de obtener versiones de un modelo');
			var resultado =  $.parseJSON(response);
			if (resultado['Datos'].item === null){
				alert( 'Hubo un error al obtener el vehiculo version: '+idVersion);
				return;
			} else {
				console.log('Obtuvimos los siguiente datos del vehiculo');
				GuardoVehiculoSeleccionado(resultado['Datos'].item,resultado['Datos'].recambios);
				console.log(resultado['Datos'].item);
				// Ahora tendría que redireccionar pero no se adonde, porque no tengo el dato en que pagina estoy...
				 location.href =dedonde;
			}
			
		}
	});
	
} 



function  GuardoVehiculoSeleccionado(vehiculo,recambios){
	// Objetivo .
	// Guardar en session del vehiculo y mostrar
	var parametros = {
		"pulsado" :  'GuardarVehiculoSeleccionado',
		"datosVehiculo"    : vehiculo,
		"idRecambios" : recambios
	};
	$.ajax({
		data       : parametros,
		url        : ruta_plg_vehiculos+'tareas_vehiculos.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Guarda datos del vehiculo  ****************');
		},
		success    :  function (response) {
			console.log('Respues de guardar datos del vehiculo');
			var resultado =  $.parseJSON(response);
			$("#vehiculos_seleccionados" ).html(resultado.html);
			console.log(resultado);
			
		}
	});
	
}

function EliminarVehiculoSeleccionado(event,item,dedonde){
	// Objetivo .
	// Eliminar un determinado vehiculo seleccionado.
	var parametros = {
		"pulsado" :  'EliminarVehiculoSeleccionado',
		"item_vehiculo"    : item,
	};
	$.ajax({
		data       : parametros,
		url        : ruta_plg_vehiculos+'tareas_vehiculos.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Eliminar datos del vehiculo seleccionado ****************');
		},
		success    :  function (response) {
			console.log('Respuesta despues eliminar vehiculo seleccionado');
			var resultado =  $.parseJSON(response);
			console.log(resultado);
			location.href =dedonde;
			
		}
	});
	
}

 $( function() {
      //@Objetivo: llamar a la librería autocomplete 
    $( ".marca" ).combobox({
        select : function(event, ui){ 
            mostrarSelectModelos(ui.item.value);
        }
       
    });
    $( ".modelo" ).combobox({
        select : function(event, ui){ 
            mostrarSelectVersiones(ui.item.value);
        }
    });
     $( ".version" ).combobox({
         select : function(event, ui){
             
        var botonhtml='<button class="btn btn-primary" onclick="SeleccionVersion('+ui.item.value+')">Seleccionar</button>';
          $('#botonVer').html(botonhtml);   
        }
    });
    $( "#toggle" ).on( "click", function() {
        $( "#combobox" ).toggle();
    });
  } );

function mostrarSelectModelos(marca){
    	var parametros = {
		"pulsado"    	: 'modelosDeMarca',
		"marca"	: marca
		};
        $.ajax({
		data       : parametros,
		url        : ruta_plg_vehiculos+'tareas_vehiculos.php',
        type       : 'post',
		beforeSend : function () {
		console.log('********* Envio para devolver el html con las opciones de marcas  **************');
		},
		success    :  function (response) {
				console.log('Respuesta de html con las opciones de marcas ');
				var resultado = $.parseJSON(response);
                $('.modelo').html(resultado.html);
                $('#modeloLabel').html("Modelo : "+resultado.items);
                $('#divModelo').find('input').focus();
				 
		}	
	});
}
function mostrarSelectVersiones(modelo){
    var parametros = {
		"pulsado"    	: 'versionesModelo',
		"modelo"	: modelo
		};
        $.ajax({
		data       : parametros,
		url        : ruta_plg_vehiculos+'tareas_vehiculos.php',
        type       : 'post',
		beforeSend : function () {
		console.log('********* Envio para devolver el html con las opciones de marcas  **************');
		},
		success    :  function (response) {
				console.log('Respuesta de html con las opciones de marcas ');
				var resultado = $.parseJSON(response);
				$("#divVersion").show();
                $('#combobox').removeAttr('disabled');
                $('.version').html(resultado.html);
                $('#versionesLabel').html("Version : "+resultado.items);
                $('#divVersion').find('input').focus()
				 
		}	
        });
}
