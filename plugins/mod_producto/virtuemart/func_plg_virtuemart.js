function modificarProductoWeb(){
    console.log("entre en modificar producto web ");
    var datos={
        'estado':       $('#estadosWeb').val(),
        'referencia':   $('#referenciaWeb').val(),
        'nombre':       $('#nombreWeb').val(),
        'codBarras':    $('#codBarrasWeb').val(),
        'precioSiva':   $('#precioSivaWeb').val(),
        'iva':          $('#ivasWeb').val(),
        'id':           $('#idWeb').html()
    };
    
    console.log(datos);
     var parametros = {
		"pulsado"    	: 'modificarDatosWeb',
		"datos"	: JSON.stringify(datos)
		};
     $.ajax({
		data       : parametros,
		url        : ruta_plg_virtuemart+'tareas_virtuemart.php',
        type       : 'post',
		beforeSend : function () {
		console.log('********* Envio los datos para modificar el producto en la web  **************');
		},
		success    :  function (response) {
				console.log('Respuesta de modificar los datos de la web  ');
				var resultado = $.parseJSON(response);
                console.log(resultado);
				 
		}	
        });
}
