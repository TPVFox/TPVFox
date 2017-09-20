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
function recalculoImporte(cantidad,num_item){
	//recoger cantidad, por defecto es un valor y luego puede variar
	//cantidad es cogida de producto que es global 
	//o del propio imput, donde usamos evento 
						//keypress al pulsar enter recalcula precio
	console.log('Estoy en recalculoImporte');
	//~ console.log('cantidad:'+cantidad);
	if (productos[num_item].unidad == 0 && cantidad != 0) {
		retornarFila(productos[num_item].nfila);
	} else if (cantidad == 0 ) {
		eliminarFila(productos[num_item].nfila);
	}
	productos[num_item].unidad = cantidad;
	//alert('DentroReclaculo:'+producto[nfila]['NPCONIVA']);
	var importe = cantidad*productos[num_item].pvpconiva;
	var id = '#N'+productos[num_item].nfila+'_Importe';
	//alert('recalcular'+id);
	importe = importe.toFixed(2);
	$(id).html(importe);
	sumaImportes();
}




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
	productos.forEach(function(product) {
		if (product.estado != 'Eliminado') {
			var importe = product.unidad * product.pvpconiva;
			iva_type = parseFloat(product.ctipoiva);
			total_ivas[iva_type] += importe;
			suma_total += importe;
		}
	});
	//~ console.log('Suma: '+ suma_total+' TOTALIVAS ');
	//~ console.log(total_ivas);
	
	var operador;
	var civa;
	//https://stackoverflow.com/a/9329476
	total_ivas.forEach(function(tiva,index) {
		//~ console.log('t iva valor'+tiva);
		//~ console.log('index'+index);
		//~ console.log('Numero caracteres de index'+index.length);
		
		if (tiva >0){
			iva_type = index.toString();
			//console.log('ivatype ---- '+iva_type);
			civa= iva_type.length;
			if (civa === 1){
				iva_type = '0'+iva_type;
			}
			operador = '1.'+iva_type;
			operador = parseFloat(operador);
			//~ console.log('operador '+typeof operador);
			
			var base = (total_ivas[index]/operador).toFixed(2);
			//~ console.log('TOTAL IVAS '+total_ivas[index]);
			
			$('#base'+index).html(base); 
			//~ console.log('base '+iva_type+':'+base);
			$('#iva'+index).html(index+'% &nbsp;'+((base*operador)-base).toFixed(2));

		}
	});
	total = suma_total; // Damos valor a variable global, para poder cobrar.
	$('#totalImporte').html(suma_total.toFixed(2));
	// Llamamos funcion grabar en BD
	grabarTicketsTemporal();
	
	//document.getElementById('total').innerHTML = total;
}



