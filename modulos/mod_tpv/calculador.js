/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogoproductos - Funciones sincronizar.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero - SolucionesVigo
 * @Descripcion	Javascript necesarios para modulo TPV.
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
		retornarFila(num_item);
	} else if (cantidad == 0 ) {
		eliminarFila(num_item);
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
	//suma los importes  y grabamos.
	
	grabarTicketsTemporal();
	
	//document.getElementById('total').innerHTML = total;
}



