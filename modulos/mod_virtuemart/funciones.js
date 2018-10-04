var regWeb=0;
var regTPV=0;
var prodModif=0;
var prodNuevos=0;
var callbackContarRegistrosWeb= function (response) {
				console.log('Respuesta de contar los productos web ');
				var resultado = $.parseJSON(response);
                cantProductos=resultado['Datos']['item']['productosWeb'];
                regWeb=cantProductos;
                contarProductosTpv( cantProductos);
}

function enviarFormulario(){
    //@OBjetivo: validar si hay tienda marcada y llamar a la función de contar productos
    var tiendaWeb=$("#tiendaWeb").val();
  if(tiendaWeb==0){
      alert("NO HAS SELECCIONADO UNA TIENDA WEB!");
  }

  if(tiendaWeb>0){
    contarProductosWeb(callbackContarRegistrosWeb);
  }
}

function contarProductosTpv(callback){
    //@Objetivo:
    //Contar todos los productos de tpv
    console.log(regWeb);
      var parametros = {
            "pulsado"   : 'contarProductostpv',
           
        };
    $.ajax({
        data       : parametros,
        url        : './tareas.php',
        type       : 'post',
        beforeSend : function () {
            console.log('*********  entre en contar productos tpv  ****************');
        },
        success    :  
        function (response) {
            console.log('REspuesta contar productos tpv');
            var resultado =  $.parseJSON(response);
            regTPV=resultado['productosTpv'][0]['cantTpv'];
            console.log(regTPV);
            total=parseInt(regWeb)-parseInt(regTPV);

            $( "#totalWeb" ).html(regWeb);
            $( "#totalTpv" ).html(regTPV);
            
            nuevosEnTpv();
            nuevosEnWeb();
            
            
        }
        
    });
}
function nuevosEnTpv(){
    //@Objetivo: contar los productos nuevos en tpv
    var tiendaWeb=$("#tiendaWeb").val();
   
    var parametros = {
            "pulsado"   : 'nuevosEnTpv',
            tiendaWeb: tiendaWeb
           
        };
      $.ajax({
        data       : parametros,
        url        : 'tareas.php',
        type       : 'post',
        beforeSend : function () {
            console.log('*********  entre en ccontar nuevos en tpv  ****************');
        },
        success    :  
        function (response) {
            console.log('REspuesta contar nuevo en tpv');
            var resultado =  $.parseJSON(response);
            console.log(resultado);
             $( "#NuevosTpv" ).html(resultado['productos'][0]['cantArticulo']);
            
            
        }
        
    });
}
function nuevosEnWeb(){
    //@Objetivo: contar los productos nuevos en web
     var tiendaWeb=$("#tiendaWeb").val();
     var parametros = {
            "pulsado"   : 'nuevosEnWeb',
            tiendaWeb   :tiendaWeb
    };
    $.ajax({
        data       : parametros,
        url        : 'tareas.php',
        type       : 'post',
        beforeSend : function () {
            console.log('*********  entre en ccontar nuevos en web  ****************');
        },
        success    :  
        function (response) {
            console.log('REspuesta contar nuevo en web');
            var resultado =  $.parseJSON(response);
            console.log(resultado);
            total=parseInt(regWeb)-parseInt(resultado['productos'][0]['cantArticulo'])
            $("#DivOpciones").show();
        }
        
    });
}

function comprobarProductos(productos, final, bandera=""){
    //@Objetivo: comprobar productos, dividir entre los nuevos en tpv y los modificados
    var tiendaWeb=$("#tiendaWeb").val();
    var sel_codigoBarras = $("#codBarras option:selected").val();
    var sel_referencia = $("#refTienda option:selected").val();

    console.log('Seleccion codbarras:'+ sel_codigoBarras);
    console.log('Seleccion referencia:'+ sel_referencia);

    var parametros = {
            "pulsado"   : 'comprobarProductos',
            "productos" : productos,
            "idTienda"  :tiendaWeb,
            "prodModif" :prodModif,
            "prodNuevos":prodNuevos,
            "sel_codigoBarras":sel_codigoBarras,
            "sel_referencia" : sel_referencia
    };
    console.log(productos);
     $.ajax({
        data       : parametros,
        url        : './tareas.php',
        type       : 'post',
        beforeSend : function () {
            console.log('*********  comprobar productos ****************');
        },
        success    :  
        function (response) {
            console.log('Respuesta de comprobar productos');
            var resultado =  $.parseJSON(response);
            console.log(resultado);
            $("#productosNuevos").append(resultado['htmlNuevos']);
            $("#productosMod").append(resultado['htmlMod']);
            prodModif=prodModif+resultado['totalModificados'];
            prodNuevos=prodNuevos+resultado['totalNuevos'];
             $("#modifTpv").html(prodModif);
              $("#NuevosWeb").html(prodNuevos);
             if(bandera==""){
                  actualizarProductosWeb(final);
             }
           

        }
        
    });
}


function addProductoWeb(nombre, refTienda, iva, precioSiva, codBarras, id, linea){
    //@OBjetivo: añadir producto nuevo en tpv
    
    console.log("Entre en añadir producto");
    var tiendaWeb=$("#tiendaWeb").val();
    var optCodBarra=$("#codBarras").val();
    var optRef=$("#refTienda").val();
    var optEstado=$("#estadoNuevo").val();
    var beneficio=$("#beneficio").val();
    var ultimoCoste=$("#ultimoCoste").val();
    console.log(refTienda);
     var parametros = {
            "pulsado"   : 'addProductoTpv',
            "nombre"    : nombre,
            "refTienda" :refTienda,
            "iva"       :iva,
            "precioSiva":precioSiva,
            "codBarras":codBarras,
            "optCodBarra":optCodBarra,
            "optRefWeb":optRef,
            "optEstado": optEstado,
            'beneficio':beneficio,
            'optCoste':ultimoCoste,
            'tiendaWeb':tiendaWeb,
            'id':id
    };
     $.ajax({
        data       : parametros,
        url        : 'tareas.php',
        type       : 'post',
        beforeSend : function () {
            console.log('*********  add producto ****************');
        },
        success    :  
        function (response) {
            console.log('Respuesta add Producto');
            var resultado =  $.parseJSON(response);
            console.log(resultado);
            if(resultado['add']['error']){
                alert("Error de SQL "+resultado['add']['error']);
            }else{
                $("#nuevo_"+linea).remove();  
                console.log("entre aquí"); 
                console.log(linea);
            }
           
           
        }
        
    });
    
}


function modificarProductosTpvWeb(nombre, refTienda, iva, precioSiva, codBarras, id, linea){
    //@OBjetivo: modificar producto de web a tpv
    console.log("Entre en modificar pro");
    var tiendaWeb=$("#tiendaWeb").val();
    var optCodBarra=$("#codBarras").val();
    var optRef=$("#refTienda").val();
    var optEstado=$("#estadoMod").val();
    
    var parametros = {
            "pulsado"   : 'modificarProducto',
            "nombre"    : nombre,
            "refTienda" :refTienda,
            "iva"       :iva,
            "precioSiva":precioSiva,
            "codBarras":codBarras,
            "id"        :id,
            "tiendaWeb" :tiendaWeb,
            "optCodBarra":optCodBarra,
            'optRef':optRef,
            'optEstado':optEstado
    };
      $.ajax({
        data       : parametros,
        url        : 'tareas.php',
        type       : 'post',
        beforeSend : function () {
            console.log('*********  modificar producto ****************');
        },
        success    :  
        function (response) {
            console.log('Respuesta modificar producto');
            var resultado =  $.parseJSON(response);
            console.log(resultado);
            if(resultado['modificar']['error']){
                alert(resultado['modificar']['error']);
            }else{
                $("#mod_" + linea).remove();   
            }
          
        }
        
    });
}
