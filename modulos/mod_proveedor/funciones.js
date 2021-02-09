
function metodoClick(pulsado){
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'VerProveedor':
			console.log('Entro en proveedor ver');
			// Cargamos variable global ar checkID = [];
			VerIdSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
			// Ahora redireccionamos 
			// recambi.php?id=id
				
			window.location.href = './proveedor.php?id='+checkID[0]+'&accion=ver';
			
			
			break;
		
		case 'AgregarProveedor':
			console.log('entro en agregar proveedor');
			window.location.href = './proveedor.php';
			
			break;
		
		
		
	 }
} 

function resumen(dedonde, idProveedor){

	window.location.href = './Resumenes/resumenAlbaranes.php?id='+idProveedor;
}

function imprimirResumen(dedonde, id, fechaInicial, fechaFinal){
		  var parametros = {
			'pulsado' : 'imprimirResumenAlbaran',
            idProveedor: id,
            fechaInicial: fechaInicial,
            fechaFinal: fechaFinal
           
        };
         $.ajax({
            data: parametros,
            url: './../tareas.php',
            type: 'post',
            success: function (response) {
              var resultado =  $.parseJSON(response); 
				console.log(resultado);
				 window.open(resultado);
            },
           
        });
}

