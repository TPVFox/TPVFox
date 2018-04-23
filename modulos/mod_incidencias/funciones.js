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
		url        : 'tareas.php',
		type       : 'post',
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
function metodoClick(pulsado,adonde){
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'Ver':
			console.log('Entro en Ver pedido');
			// Cargamos variable global ar checkID = [];
			VerIdSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
			// Ahora redireccionamos 
			window.location.href = './'+adonde+'.php?id='+checkID[0];
			//window.location.href = './producto.php?id='+checkID[0];
			break;
		
		
	 }
} 
