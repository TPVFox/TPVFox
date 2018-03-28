function enviarIncidencia(){
	var usuario=$("#inci_usuario").val();
	var fecha=$("#inci_fecha").val();
	var datos=$("#inci_datos").val();
	var dedonde=$("#inci_dedonde").val();
	var estado=$("#inci_estado").val();
	var mensaje=$("#inci_mensaje").val();
	
	var parametros = {
		'pulsado':'nuevaIncidencia',
		'usuario':usuario,
		'fecha':fecha,
		'datos':datos,
		'dedonde':dedonde,
		'estado':estado,
		'mensaje':mensaje
	};
	console.log(parametros);
	$.ajax({
		data       : parametros,
		url        : 'http://localhost/solvigo/tpvfox/modulos/mod_incidencias/funciones.php',
		type       : 'get',
		beforeSend : function () {
			console.log('********* Insertar una incidencia  ****************');
		},
		success    :  function (response) {
			console.log('Respuesta de añadir una incidencia');
			var resultado =  $.parseJSON(response);
			console.log(resultado);
			
		}
	});	
	
}
