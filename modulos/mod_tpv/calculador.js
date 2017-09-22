/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogoproductos - Funciones sincronizar.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero - SolucionesVigo
 * @Descripcion	Javascript necesarios para modulo importar DBF.
 *
 *  */

function recalculoImporte(cantidad,num_item){
	// @ Objetivo:
	// Recalcular el importe de la fila, si la cantidad cambia.
	// @ Parametros:
	//	cantidad -> Valor ( numerico) de input unidades.
	//	num_item -> El numero que indica el producto que modificamos.
	console.log('Estoy en recalculoImporte');
	//~ console.log('cantidad:'+cantidad);
	if (productos[num_item].unidad == 0 && cantidad != 0) {
		retornarFila(productos[num_item].nfila);
	} else if (cantidad == 0 ) {
		eliminarFila(productos[num_item].nfila);
	}
	console.log('Valor de cantidad'+cantidad);
	productos[num_item].unidad = cantidad;
	//alert('DentroReclaculo:'+producto[nfila]['NPCONIVA']);
	var importe = cantidad*productos[num_item].pvpconiva;
	var id = '#N'+productos[num_item].nfila+'_Importe';
	//alert('recalcular'+id);
	importe = importe.toFixed(2);
	$(id).html(importe);
	sumaImportes();
}

function sumaImportes(){
	//suma los importes de cada fila y devuelve Total Importe de compra.
	var suma_total = 0;
	var total_ivas = [];
	var iva_type;

	total_ivas[4] = 0;
	total_ivas[10] = 0;
	total_ivas[21] = 0;
	
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
			iva_type = parseInt(product.ctipoiva);
			total_ivas[parseInt(product.ctipoiva)] += importe;
			suma_total += importe;
		}
	});
	//~ console.log('Suma: '+ suma_total+' TOTALIVAS ');
	//~ console.log(total_ivas);
	
	var operador;
	//https://stackoverflow.com/a/9329476
	total_ivas.forEach(function(total_importe, tipo_iva) {
		//~ console.log('t iva valor'+tiva);
		//~ console.log('index'+index);
		//~ console.log('Numero caracteres de index'+index.length);
		
		if (total_importe > 0){
			operador = (100 + tipo_iva) / 100;
			console.log('OPERADOR: ' + operador);

			var base = (total_ivas[tipo_iva]/operador).toFixed(2);
			//~ console.log('TOTAL IVAS '+total_ivas[index]);
			$('#line'+tipo_iva).css('display','');
			$('#tipo'+tipo_iva).html(tipo_iva+'%');
			$('#base'+tipo_iva).html(base); 
			//~ console.log('base '+iva_type+':'+base);
			$('#iva'+tipo_iva).html((total_importe-base).toFixed(2));
		} else {
			$('#line'+tipo_iva).css('display','none');
		}
	});
	total = suma_total; // Damos valor a variable global, para poder cobrar.
	$('.totalImporte').html(suma_total.toFixed(2));
	// Llamamos funcion grabar en BD
	grabarTicketsTemporal();
	
	//document.getElementById('total').innerHTML = total;
}



