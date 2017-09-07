// Funciones modulo de recambios vista lista recambios.
function VerRecambiosSeleccionado (){
	$(document).ready(function()
	{
		// Array para meter lo id de los checks
		
		// Contamos check están activos.... 
		checkID = [] ; // Reiniciamos varible global.
		var i= 0;
		// Con la funcion each hace bucle todos los que encuentra..
		$(".rowRecambio").each(function(){ 
			i++;
			//todos los que sean de la clase row1
			if($('input[name=checkRec'+i+']').is(':checked')){
				// cant cuenta los que está seleccionado.
				valor = '0';
				valor = $('input[name=checkRec'+i+']').val();
				checkID.push( valor );
				// Ahora tengo hacer array con id...
			}
			
		});
		console.log('ID de Recmabios seleccionado:'+checkID);
		return;
	});


}
	
function BuscarRecambio (){
	$(document).ready(function()
	{
		// Lo ideal sería identificar palabras..
		// de momento solo una palabra..
		NuevoValorBuscar = $('input[name=Buscar').val();
		NuevoValorBuscar = $.trim(NuevoValorBuscar);
		if (NuevoValorBuscar !== ''){
			BRecambios= NuevoValorBuscar;
			console.log('Filtro:'+BRecambios);
		} else {
			alert (' Debes poner algun texto ');
			BRecambios = '';
		}
		return;
	});


}



function metodoClick(pulsado){
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'VerRecambio':
			console.log('Entro en VerRecambio');
			// Cargamos variable global ar checkID = [];
			VerRecambiosSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
			alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
			return	
			}
			// Ahora redireccionamos 
			// recambi.php?id=id
			window.location.href = './recambio.php?id='+checkID[0];
			
			
			
			
			
			
			break;
		case 'NuevaBusqueda':
			// Obtenemos puesto en input de Buscar
			BuscarRecambio ();
			// Ahora redireccionamos 
			// recambi.php?buscar = buquedaid=id
			if (BRecambios !== ''){
				window.location.href = './ListaRecambios.php?buscar='+BRecambios;
			} else {
				// volvemos sin mas..
				return;
				//~ window.location.href = './ListaRecambios.php';	
			}
			console.log('Resultado Buscar:'+BRecambios);
			break;
		default:
			alert('Error no pulsado incorrecto');
		}
} 




// JavaSCRIPT para modulo de recambios vista Recambio unico.


function copiarAlPortapapeles(id_elemento) {
  //~ document.execCommand("delete")
  document.execCommand("delete");
  console.log('Ver si entra:'+id_elemento);
  //~ var aux;
  var aux = document.getElementById(id_elemento);
  aux.select();
  console.log(aux.value);
  document.execCommand("copy");
};
function copiasIDVirtuemart() {
	// Funcion que utilizamos para copiar en descripcion del producto en virtuemart.
	var id = parseInt($("#IDWeb").val());
	// Ahora montamos el html queremos copiar.
	var ReferenciasCruzadas =document.getElementById("RefCruzadas");
	var DatosRefCruzVersiones = '<div class="col-md-3">' +  ReferenciasCruzadas.innerHTML + '</div>';
	var ReferenciasVersiones = document.getElementById("RefCruVersiones") ;
	var LimpioRefCruVersiones = ReferenciasVersiones.innerHTML;
	var TLetras = LimpioRefCruVersiones.trim().length;
	DatosRefCruzVersiones = DatosRefCruzVersiones +  '<div class="col-md-9">'+ LimpioRefCruVersiones.trim()+ '</div>';
	//~ alert( 'ReferenciasCruzadas:'+ReferenciasCruzadas);
	var parametros = {
		'pulsado': 'CopiarDescripcion',
		'id': id,
		'DatosRefCruzadas': DatosRefCruzVersiones,
		'TotalLetras': TLetras
	};
	$.ajax({
		data: parametros,
		url: 'tareas.php',
		type: 'post',
		beforeSend: function () {
			$("#resultado").html('Copiando datos referencias cruzadas en id');

		},
	success: function (response) {
			var html= "";
			if (response['RowsAfectados'] === 1 ){
				html = "Ok, copiado correctamente descripcion larga de id:";
			} else {
				html = "Error. NO fue correcta la consulta.";
			} 
			$("#resultado").html(html+id);
			

		}
	});
	
};
