/*  Fichero con funciones comunes para toda la aplicacion.
 *  tpvfox.js es un modulo para nuestro Javascript.
 *  tpvfoxSinExpost.js es una copia de este sin el export, para siga funcionando tpvfox mientras no cambiemos
 *  toda la aplicacion a type=module.
 *  Nuestra ayuda type=module -> https://ayuda.svigo.es/index.php/programacion/javascript/321-reorganizar-nuestro-javascript-con-import
 * */

function TfObtenerObjetos(clase) {
    // @ Objetivo:
    // Obtener array objetos con la misma clase.
    var objetos = $('.' + clase);
    return objetos;
}


function TfObtenerCheck(clase) {
    // @ Objetivo
    //  Obtener los id checks que tenemos seleccionados con la clase indicada,
    //  esto implica que tenemos que poner la misma clase a todos los checks queremos controlar.
    // @ Parametros:
    //      clase -> (string) Nombre de la clase queremos buscar los ids de los checks seleccionados.
    // @ Devuelve -> (array) Nombres completos de los ids que tiene marcado el check.
    var checks = [];
    var ChecksObj =TfObtenerObjetos(clase);
    console.log(ChecksObj);
    ChecksObj.each(function(indice){
        indice++;
        if ($(this)[0].checked) {
            // Obtenemos el atributo value por defecto.
            var id = $(this)[0].value;
            checks.push(id);
        }
    });
    console.log('ID de checks seleccionados:'+checks);
    return checks;
}

function TfBarraProceso(lineaA,lineaF, idbar) {
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

function TfObtenerIdString(base,string){
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


function TfRedireccionA(url){
    // @ Objetivo
    // Redireccionar a la url indicada.
    window.location.href = url;
}

// Parte que se borra en el fichero tpvfoxSinExport.js


