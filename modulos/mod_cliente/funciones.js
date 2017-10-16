function metodoClick(pulsado){
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'VerCliente':
			console.log('Entro en VerCliente');
			// Cargamos variable global ar checkID = [];
			//Funcion global en jquery
			VerUsuariosSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
				
			window.location.href = './cliente.php?id='+checkID[0];
			break;
		
		case 'AgregarCliente':
			console.log('entro en agregarCliente');
			window.location.href = './cliente.php';
			
			break;		
	 }
} 





