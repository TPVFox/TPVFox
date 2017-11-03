/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogoproductos - Funciones sincronizar.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero - SolucionesVigo
 * @Descripcion	Javascript necesarios para modulo tpv.
 * 
 * */


function guardarCierreCaja(){
	alert("guardar");
	//Objetivo 
	//enviar datos del cierre de caja
	//para guardar en cierres
	//Ccierre es global
	
	console.log('longitud Ccierre: '+Ccierre.length);
	
	var parametros = {
	"datos_cierre" 	: Ccierre,
	"pulsado" 	: 'insertarCierre'
			};
	$.ajax({
		data:  parametros,
		url:   'tareas.php',
		type:  'post',
		beforeSend: function () {
				console.log('enviando datos para cierre');
		},
		success:  function (response) {
			console.log('guardar cierre response js');
			var resultado =  $.parseJSON(response);
			console.log('recibiendo datos id '+resultado['insertarCierre']);
			
			//si hay error nos mostrara un mensaje, sino es que todo va bien.
			//if (typeof(resultado['error']) === 'undefined') { //mejorar, no vale ultima tabla falla y no me entero
			var tabla1 = resultado['insertarCierre'];
			var tabla2 = resultado['update_estado'];
			var tabla3 = resultado['insertarIvas']['insertar_ivas_cierre'];
			var tabla4 = resultado['insertarUsuarios']['insertar_FpagoCierres'];
			var tabla5 = resultado['insertarUsuarios']['insertarTickets_usuarios'];
			
			if ((tabla1 === 'Correcto') && (tabla2 === 'Correcto') &&  (tabla3 === 'Correcto') ){
				if ( (tabla4 === 'Correcto') &&  (tabla5 === 'Correcto')){
				console.log('Inserte de cierres correcto.');
				
			document.location.href='ListaCierres.php';
				} else{
					console.log('resultado '+resultado['sql']);
					console.log('ERROR en alguna insercion de Cierres.'+response);
				}
				
			} else {				
				console.log('ERROR en insercion '+response);
			}
			
		}
	});
	
	
	
}


function metodoClick(pulsado){
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'VerCierre':
			console.log('ver cierre caja ');
		//	console.log('id'+id);
			var id	=	VerIdSeleccionado ();
			if (checkID.length >1 || checkID.length === 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
			
			window.location.href = './VistaCierre.php?id='+checkID[0];
			
			
			
			
		break;
	}
} 



