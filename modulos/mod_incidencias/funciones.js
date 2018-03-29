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
	$.ajax({
		data       : parametros,
		url        : 'funciones.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Modificando los importes de la factura  ****************');
		},
		success    :  function (response) {
			console.log('Respuesta de la modificación de los importes');
			var resultado =  $.parseJSON(response);
			
		}
	});
	
	
	
}
