/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogoproductos - Funciones sincronizar.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero - SolucionesVigo
 * @Descripcion	Javascript necesarios para modulo importar DBF.
 * */
var pulsado = '';
var iconoCargar = '<span><img src="../../css/img/ajax-loader.gif"/></span>';
var iconoCorrecto = '<span class="glyphicon glyphicon-ok-sign"></span>';
var iconoIncorrecto = '<span class="glyphicon glyphicon-remove-sign"></span>';

//script en tpv html 
function Inicio (pulsado) {
	// Objetivo:
	// Focus de input
	case 'nuevo':
		// Acaba de cargar javascript, por lo que inicia proceso.
		//llamar func 
	
	
		break;
	
	
	return;
}

//DETERMINAR si es una ref o un codigoBarras el dato que me pasan para buscar... 
//campoAbuscar = ref , codigoBarras o descripc
//busqueda = dato en input correspondiente
function BuscarProducto($campoAbuscar,$busqueda, $BDImportDbf){
	// Objetivo:
	// Es buscar por Referencia o Codbarras
	//campos:
	//CREF es referencia
	//CCODEBAR es codigo de barras
	//campos a mostrar en hmtl:
		//CCODEBAR , CREF, CDETALLE, NPCONIVA, CTIPOIVA
		//codigobarras, ref, descripc, pvpConIva, tipoIva
		
	//**CASE tipoIva , S=4, R=10, G=21 % 
	
	var parametros = {
			"pulsado" 	: 'buscarProducto',
			"campos" 	: campos
		};
	$.ajax({
		data:  parametros,
		url:   'tareas.php',
		type:  'post',
		beforeSend: function () {
			$("#resultado").html('Comprobamos que el producto existe ');
			console.log('******** estoy en buscar producto por ref ****************');
		},
		success:  function (response) {
		var resultado = response;
		
		
		
		}
	}
	
	}
	
	
	
	
	
	//~ if ($BDImportDbf->connect_errno) {
		//~ echo 'error al conectar';
	//~ } else {
		//~ sql = mysql_fetch_array(mysql_query('SELECT CCODEBAR,CREF,CDETALLE,NPCONIVA,CTIPOIVA FROM '.$nombreTabla.' WHERE '.$campoAbuscar.'='.$busqueda);
	//~ }
	//~ $resultado = array();
	//~ return $resultado;
	
}




//~ //funciones que tenia ricardo en html dentro de <script></script>
function teclaPulsada(event,id){
	if(event.keyCode == 13){
		ContadorPulsaciones= 0;
		respuesta = obtenerdatos(id);
		alert(respuesta);
	} else {
		if (id === 'C0_Descripcion'){
			respuesta = obtenerdatos(id);
			if (respuesta.length > 3){
				alert('Pendiente select autocompletado:'+respuesta.length);
			}
		}
	}
}

function obtenerdatos(id){
	var aux = document.getElementById(id);
	console.log('Ver id'+aux);
	return aux.value;	
}
