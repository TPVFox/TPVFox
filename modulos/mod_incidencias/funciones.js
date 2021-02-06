function enviarIncidencia(){
	var datos=$("#inci_datos").val();
	var dedonde=$("#inci_dedonde").val();
	var estado=$("#inci_estado").val();
	var mensaje=$("#inci_mensaje").val();
	var numIncidencia=$("#numIncidencia").val();
	var usuarioSelec=$("#usuarioSelec").val();
	var parametros = {
		'pulsado':'nuevaIncidencia',
		'datos':datos,
		'dedonde':dedonde,
		'estado':estado,
		'mensaje':mensaje,
		'numIncidencia':numIncidencia,
		'usuarioSelec':usuarioSelec
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
			if(resultado.error){
				alert(resultado.mensaje);
			}else{
				$('#busquedaModal').modal('hide');
				if(dedonde=="incidencia"){
					location.reload(true);
				}
			}
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
function abrirModalIndicencia(dedonde, configuracion,numIncidencia=0, numReal){
	// @ Objetivo 
	// 	Preparar el html para mostrar modal de incidencia.
	// @ Parametros:
	// 	dedonde -> indica el modulo desde donde se ejecuta.
	//  configuracion-> Objecto que obtenermos parametros del modulo 
	//					mostrar_select ->donde indica si mostramos select asignar usuarios
	//					usuario_Defecto-> indica al select que usuario tiene por defecto.
	// 	numIncidencia -> Si trae valor es que se esta respondiendo, por lo que estado debería permitir seleccionarlo.
	
	console.log(dedonde);
	
	var parametros = {
		"pulsado"    : 'abririncidencia',
		"dedonde" : dedonde,
		"numIncidencia":numIncidencia,
		"configuracion":configuracion,
		"idReal":numReal
	};
	console.log(configuracion);
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Añadiendo incidencia tanto nueva como respuesta a una   ***********');
		},
		success    :  function (response) {
			console.log('Respuesta de abrir incidencias');
			var resultado =  $.parseJSON(response);
			if (numIncidencia > 0){
				titulo="Respuesta a incidencia nº "+numIncidencia;
			} else {
				titulo="Crear incidencia";
			}
			html=resultado.html;
			abrirModal(titulo, html);
		}
	});
}

