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
	producto[nfila]['UNIDAD']=cantidad;
	//alert('DentroReclaculo:'+producto[nfila]['NPCONIVA']);
	var importe = cantidad*pvp;
	var id = '#N'+nfila+'_Importe';
	//alert('recalcular'+id);
	importe = importe.toFixed(2);
	$(id).html(importe);
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
	
  
    total = importeFila.toFixed(2);
    
	$('.totalImporte').html(total);
	//document.getElementById('total').innerHTML = total;
}
