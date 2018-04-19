// Funciones necesarias para plugin de mod_producto de vehiculos.
function SeleccionMarca(){
	alert ( 'cambio de marca ');
	
}

	


function  SeleccionMarca(event){
	// @Objetivo :
	// Ejecutar en servidor de web funcion que reste stock de productos
	// Pendiente el que no lo haga dos vez , si hace clic o intro muy rapido.
	alert ( 'cambio de marca ');
	console.log(event.target);
	var idMarca = $('select[id=myMarca]').val();
	console.log(ruta_plg_vehiculos);
	var parametros = {
		"pulsado" :  'BuscarModelos',
		"idMarca"    : 'ObtenerMarcasVehiculos'
	};
	$.ajax({
		data       : parametros,
		url        : ruta_plg_vehiculos+'tareas_vehiculos.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Obteniendo modelos de vehiculos  ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de obtener modelos de vehiculos');
			var resultado =  $.parseJSON(response);
			
		}
	});
	
} 
