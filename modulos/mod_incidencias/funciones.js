function enviarIncidencia(){
	var usuario=$("#inci_usuario").val();
	var fecha=$("#inci_fecha").val();
	var datos=$("#inci_datos").val();
	var dedonde=$("#inci_dedonde").val();
	var estado=$("#inci_estado").val();
	var mensaje=$("#inci_mensaje").val();
	var numIncidencia=$("#numIncidencia").val();
	var parametros = {
		'pulsado':'nuevaIncidencia',
		'usuario':usuario,
		'fecha':fecha,
		'datos':datos,
		'dedonde':dedonde,
		'estado':estado,
		'mensaje':mensaje,
		'numIncidencia':numIncidencia
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
function abrirIndicencia(dedonde, idUsuario, numIncidencia=""){
	var parametros = {
		"pulsado"    : 'abririncidencia',
		"dedonde" : dedonde,
		"usuario":idUsuario,
		"numIncidencia":numIncidencia
		
	};
	console.log(parametros);
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Modificando los importes de la factura  ****************');
		},
		success    :  function (response) {
			console.log('Respuesta de abrir incidencias');
			var resultado =  $.parseJSON(response);
			titulo="Crear incidencia";
			html=resultado.html;
			abrirModal(titulo, html);
		}
	});
}
function abrirModal(titulo,tabla){
	// @ Objetivo :
	// Abril modal con texto buscado y con titulo que le indiquemos.
	console.log('Estamos en abrir modal');
	$('.modal-body > p').html(tabla);
	$('.modal-title').html(titulo);
	$('#busquedaModal').modal('show');
	
	//Se lanza este evento cuando se ha hecho visible el modal al usuario (se espera que concluyan las transiciones de CSS).
	$('#busquedaModal').on('shown.bs.modal', function() {
		// Pongo focus a cada cja pero no se muy bien, porque no funciona si pongo el focus en la accion realizada.
	$('#cajaBusqueda').focus(); //f
	$('#cajaBusquedaproveedor').focus(); //foco en input caja busqueda del proveedor

	});
}
