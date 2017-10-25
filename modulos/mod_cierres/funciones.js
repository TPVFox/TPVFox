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
	
	console.log(Ccierre.length);
	
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
			console.log('guardar cierre '+response);
			var resultado =  $.parseJSON(response);
			console.log('recibiendo datos id ');
			
			if (resultado === true){
				
				alert('datos  '+resultado);
			}
			document.location.href='ListaCierres.php';
			// console.log('consulta insert: '+resultado.sqlInsert);		
			//~ console.log('consulta update :'+resultado.sqlUpdate);	
			//~ console.log('num tickets afectados en update '+resultado.Nafectados);
		}
	});
	
	
	
}
