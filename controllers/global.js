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
    // @ Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
    // @ Objetivo
    //  Obtener los id checks que tenemos seleccionados con la clase indicada,
    //  esto implica que tenemos que poner la misma clase a todos los checks queremos controlar.
    // @ Parametros:
    //      clase -> (string) Nombre de la clase queremos buscar los ids de los checks seleccionados.
    // @ Devuelve -> (array) Nombres completos de los ids que tiene marcado el check.
    
    var checks = [];
    $('.' + clase).each(function (indice) {
        indice++;
        //todos los que sean de la clase row1
        console.log($(this)[0]);
        if ($(this)[0].checked) {
            // cant cuenta los que está seleccionado.
            var id = $(this)[0].id;
            checks.push(id);
        }

    });
   console.log('ID de checks seleccionados:'+checks);

    return checks;
}

function BarraProceso(lineaA,lineaF, idbar) {
    // Esta funcion debería ser una funcion comun , por lo que se debería cargar el javascript comun y ejecutar...
    // Script para generar la barra de proceso.
    // Esta barra proceso se crea con el total de lineas y empieza mostrando la lineas
    // que ya estan añadidas.
    // NOTA:
    // lineaActual no puede ser 0 ya genera in error, por lo que debemos sustituirlo por uno
    if (lineaA == 0 ) {
    lineaA = 1;
    }
    if (lineaF == 0) {
    alert( 'Linea Final es 0 ');
    return;
    }
    var progreso =  Math.round(( lineaA *100 )/lineaF);
    $('#bar'+idbar).css('width', progreso + '%');
    // Añadimos numero linea en resultado.
    document.getElementById("bar"+idbar).innerHTML = progreso + '%';  // Agrego nueva linea antes 
    return;

}

function ObtenerIdString(base,string){
    // @ Objetivo
    //  Leer un string y obtener el id que contiene, es ideal para:
    //      Ejemplo: ((base) row,string) row1
    //          obtenemos objeto con :
    //                  [id] = (int) 1
    // Si devuelve id -1 es que no encontro la base.
    var id;
    if (string.indexOf(base) >-1){
		id = string.slice(base.length);
	} else {
        // No encontro.
        id = -1;
    }
    console.log('Estoy ObtenerIdString');
    return id
}


function redireccionA(url){
    // @ Objetivo
    // Redireccionar a la url indicada.
    window.location.href = url;
}
