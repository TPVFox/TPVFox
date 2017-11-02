// Funciones modulo de recambios vista lista recambios.
//funcion usada en js case metodoclick para el check de la lista
function VerTiendasSeleccionado (){
	$(document).ready(function()
	{
		// Array para meter lo id de los checks
		
		// Contamos check están activos.... 
		checkID = [] ; // Reiniciamos varible global.
		var i= 0;
		// Con la funcion each hace bucle todos los que encuentra..
		$(".rowTienda").each(function(){ 
			i++;
			//todos los que sean de la clase row1
			if($('input[name=checkUsu'+i+']').is(':checked')){
				// cant cuenta los que está seleccionado.
				valor = '0';
				valor = $('input[name=checkUsu'+i+']').val();
				checkID.push( valor );
			}
			
		});
		console.log('ID de Tienda seleccionado:'+checkID);
		return;
	});


}

function metodoClick(pulsado){
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'VerTienda':
			console.log('Entro en VerUsuario');
			// Cargamos variable global ar checkID = [];
			VerTiendasSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
			// Ahora redireccionamos 
			// recambi.php?id=id
				
			window.location.href = './tienda.php?id='+checkID[0];
			
			
			break;
		
		case 'AgregarTienda':
			console.log('entro en agregarUsuario');
			window.location.href = './tienda.php';
			
			break;
		
		
		
	 }
} 

function camposFormRequired(idscampos){
	// El objetivo es poner requerido campos que le indicamos en parametro.
	// @Parametro:
	// idscampos = array con idscampos ( identificador campos input).
	console.log(idscampos);
	idscampos.forEach(function(idcampo) {
		console.log(idcampo);
		$('#'+idcampo).prop('required',true);
	});
	return;
}
function camposFormQuitarRequired(idscampos){
	// El objetivo es poner requerido campos que le indicamos en parametro.
	// @Parametro:
	// idscampos = array con idscampos ( identificador campos input).
	console.log(idscampos);
	idscampos.forEach(function(idcampo) {
		console.log(idcampo);
		$('#'+idcampo).prop('required',false);
	});
	return;
}



