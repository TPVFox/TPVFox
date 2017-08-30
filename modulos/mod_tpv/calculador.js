/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogoproductos - Funciones sincronizar.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero - SolucionesVigo
 * @Descripcion	Javascript necesarios para modulo importar DBF.
 *
 *  */
var pulsado = '';
var iconoCargar = '<span><img src="../../css/img/ajax-loader.gif"/></span>';
var iconoCorrecto = '<span class="glyphicon glyphicon-ok-sign"></span>';
var iconoIncorrecto = '<span class="glyphicon glyphicon-remove-sign"></span>';
var producto =[];
var Niva;

//multiplica unidad o kilo por pvp = importe de cada fila
//se le pasa cantidad (unidad o kilo) y pvp
//ver si pasar parametros o recogerlos en propia funcion !!!! 
//pvp puedo pasarlo PERO cantidad NO! esta puede variar
//id es hmtl
function recalculoImporte(cantidad,pvp,nfila){
	//recoger cantidad, por defecto es un valor y luego puede variar
	//cantidad es cogida de producto que es global 
	//o del propio imput, donde usamos evento 
						//keypress al pulsar enter recalcula precio
	if (producto[nfila]['UNIDAD'] == 0 && cantidad != 0) {
		retornarFila(nfila);
	} else if (cantidad == 0 ) {
		eliminarFila(nfila);
	}
	producto[nfila]['UNIDAD']=cantidad;
	//alert('DentroReclaculo:'+producto[nfila]['NPCONIVA']);
	var importe = cantidad*pvp;
	var id = '#N'+nfila+'_Importe';
	//alert('recalcular'+id);
	importe = importe.toFixed(2);
	$(id).html(importe);
	sumaImportes();
}


// http://www.forosdelweb.com/f13/mostrar-dos-decimales-con-javascript-122081/
//redondeo y añade 00 como decimal 3.3 3.30
//~ function redondeo(valor, nDec){
    //~ var n = parseFloat(valor);
    //~ var resultado = "0.00";
    //~ if (!isNaN(n)){
     //~ n = Math.round(n * Math.pow(10, nDec)) / Math.pow(10, nDec);
     //~ resultado = String(n);
     //~ resultado += (resultado.indexOf(".") == -1? ".": "") + String(Math.pow(10, nDec)).substr(1);
     //~ resultado = resultado.substr(0, resultado.indexOf(".") + nDec + 1);
    //~ }
    //~ return resultado;
//~ }


//suma los importes de cada fila y devuelve Total Importe de compra.
function sumaImportes(){
	var suma_total = 0;
	var total_ivas = [];
	var iva_type;

	total_ivas['4'] = 0;
	total_ivas['10'] = 0;
	total_ivas['21'] = 0;
	
	$('#base4').html('');
	$('#iva4').html('');
	$('#base10').html('');
	$('#iva10').html('');
	$('#base21').html('');
	$('#iva21').html('');

	//https://stackoverflow.com/a/9329476
	producto.forEach(function(product) {
		if (product['Estado'] != 'Eliminado') {
			var importe = product['UNIDAD'] * product['NPCONIVA'];
			iva_type = tipoIva(product['CTIPOIVA']);
			total_ivas[iva_type] += importe;
			suma_total += importe;
		}
	});
	
	console.log('Suma:'+ suma_total);
	var operador;
	var civa;
	//https://stackoverflow.com/a/9329476
	total_ivas.forEach(function(tiva,index) {
		//~ console.log('t iva valor'+tiva);
		//~ console.log('index'+index);
		//~ console.log('Numero caracteres de index'+index.length);
		
		
		
		
		if (tiva >0){
			iva_type = index.toString();
			civa= iva_type.length;
			if (civa === 1){
				iva_type = '0'+iva_type;
			}
			operador = '1.'+iva_type;
			operador = parseFloat(operador);
			console.log('operador '+typeof operador);
			
			var base = (total_ivas[index]/operador).toFixed(2);
			console.log(total_ivas[index]);
			
			$('#base'+index).html(base); console.log('base '+iva_type+':'+base);
			$('#iva'+index).html(index+'% &nbsp;'+(base*operador).toFixed(2));
			
			
			
			
			//~ if (index === 4 ){
				//~ console.log('operador 4: '+ operador);
				//~ var base4 = (total_ivas['4']/1.04).toFixed(2);
				//~ $('#base4').html(base4);
				//~ $('#iva4').html('4% &nbsp;'+(base4*1.04).toFixed(2));
			//~ }
			//~ if (index === 10){
				//~ console.log('operador: 10: '+ operador);
				//~ var base10 =(total_ivas['10']/1.10).toFixed(2);
				//~ $('#base10').html(base10);
				//~ $('#iva10').html(' 10% &nbsp; '+(base10*1.10).toFixed(2));
			//~ }
			//~ if (index === 21){
				//~ console.log('operador 21: '+ operador);
				//~ var base21 = (total_ivas['21']/1.21).toFixed(2);
				//~ $('#base21').html(base21);
				//~ $('#iva21').html(' 21% &nbsp;'+(base21*1.21).toFixed(2));
			//~ }
		}
	});

	$('#totalImporte').html(suma_total.toFixed(2));
	
	
	
	//document.getElementById('total').innerHTML = total;
}



