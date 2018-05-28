// Funciones modulo de clientes lista clientes. para chekckear y modificar 1 en concreto
function VerIdSeleccionado (){
	$(document).ready(function()
	{
		// Array para meter lo id de los checks
		
		// Contamos check están activos.... 
		checkID = [] ; // Reiniciamos varible global.
		var i= 0;
		// Con la funcion each hace bucle todos los que encuentra..
		$(".rowUsuario").each(function(){ 
			i++;
			//todos los que sean de la clase row1
			if($('input[name=checkUsu'+i+']').is(':checked')){
				// cant cuenta los que está seleccionado.
				valor = '0';
				valor = $('input[name=checkUsu'+i+']').val();
				checkID.push( valor );
			}
			
		});
		console.log('ID de Usuarios seleccionado:'+checkID);
		return;
	});
}


// Lo mismo que lo de arriba, pero con parámetros para que sea realmente global:
// VerIdSeleccionado =equivalente= leerChecked('rowUsuario','checkUsu');
// Pero con este código vale para cualquier conjunto de checks con una clase común 
// y con un id distinto por check
//
//Recibe: clase : común para todos los elementos que se quieren buscar
//Devuelve : array con los ids de los elementos con propiedad checked = true
//
// ¿Pero que coño hace un .js en la carpeta de controllers?
//
function leerChecked(clase) {
    var checks = [];
    
    $('.' + clase).each(function (indice) {
        indice++;
        //todos los que sean de la clase row1
        if ($(this)[0].checked) {
            // cant cuenta los que está seleccionado.
            var id = $(this)[0].id;
            checks.push(id);
        }

    });
    return checks;
}
