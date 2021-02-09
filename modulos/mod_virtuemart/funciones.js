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
            "pulsado"   : 'contarProductostpv'
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


function BuscarImagenes_producto(){
    // @ Objetivo
    // Iniciar el ciclo para buscar los productos que tenemos relacionado con la web,
    // ver si tiene imagen, y si NO tienen entonces buscar en registro media de virtuemart
    // por si ya existe la imagen.
    

    obtenerProductosRelacionados();

}


function  obtenerProductosRelacionados() {
    // @ Objetivo.
    // Obtener los primeros registros o todos si son menos de cien, de tabla articulosTienda:
    if ( totalReferenciasWeb == reg_inicial){
        alert ( 'Algo salio mal o no hay productos relacionas en la web');
        // No continuo.
        return;
    }
    BarraProceso(reg_inicial,totalReferenciasWeb);
    var reg_final = reg_inicial + 100; // reg_final no es una variable global

    
    if (reg_final > totalReferenciasWeb){
        // Si el registro fianl es mayor totalRegistros para evitar error
        // solo pedimos hasta total.
        reg_final = totalReferenciasWeb;
    }
    
    var parametros = {
            "pulsado"       : 'obtenerProductosRelacionados',
            "reg_inicial"   : reg_inicial,
            "reg_final"     : reg_final
    };
      $.ajax({
        data       : parametros,
        url        : 'tareas.php',
        type       : 'post',
        beforeSend : function () {
            console.log('*********  Obtener los datos de la tabla articuloTienda ****************');
        },
        success    :  
        function (response) {
            console.log('Termino la respuesta obtener datos de tabla articuloTienda');
            var resultado =  $.parseJSON(response);
            var Datos = resultado.imagenes.Datos;
            contador= 0;
            Datos.forEach(function(dato) {
                contador = contador+1;
                console.log(reg_inicial+contador);
                $("#reg_actual").html(reg_inicial+contador); 
                if ( dato.imagenes_insert !== undefined ){
                    img_encontradas = img_encontradas+1;
                    $("#img_encontradas").html(img_encontradas); 
                }
            });
            reg_inicial = reg_inicial + contador;
            if (reg_inicial == reg_final){
                if (reg_final < totalReferenciasWeb){
                    obtenerProductosRelacionados();
                } else {
                    // termino
                    BarraProceso(reg_inicial,totalReferenciasWeb);
                }
            }
        }
        
    });

}

function AnhadirCamposPersonalidosIdPeso(){
    // @ Objetivo
    // Iniciar el ciclo para añadir los campos personalizados a los productos tipo peso.
    // Va hacer uno a uno..
    // Si no existe referencia en tienda lo salta.
    if ( totaProductoPeso == reg_inicial){
        alert ( 'Algo salio mal o no hay productos relacionas en la web');
        // No continuo.
        return;
    }
    cicloCamposPersonalizado();

}


function cicloCamposPersonalizado(){
    // Iniciamos el ciclo.
    // Recuerda que el array empieza 0, por eso empezamos obteniendo el reg_inicial 0.
    BarraProceso(reg_inicial,totaProductoPeso);
    $("#reg_actual").html(reg_inicial); 
    var parametros = {
            "pulsado"       : 'obtenerIdVirtuemart',
            "idProductoTpv" : Ids[reg_inicial]
    };
    $.ajax({
        data       : parametros,
        url        : 'tareas.php',
        type       : 'post',
        beforeSend : function () {
            console.log('*********  Obtener idVirtuemar del producto ****************');
        },
        success    :  
        function (response) {
            console.log('*** Estoy devuelta de obtenerIdVirtuemart ***');
            var resultado =  $.parseJSON(response);
            console.log(resultado.idVirtuemart);
            if (parseInt(resultado.idVirtuemart) > 0) {
                AnhadimosCamposVirtuemart(resultado.idVirtuemart);
            } else {
                // NO hay idVirtuemart por lo que continuamos con el siguiente del bucle.. ciclo...
                SinIDVirtuemart = SinIDVirtuemart +1;
                $("#SinIdVirtuemar").html(SinIDVirtuemart); 
                reg_inicial = reg_inicial +1 ;
                if ( totaProductoPeso > reg_inicial){
                    // Repetimos ciclo.
                    cicloCamposPersonalizado();
                } else {
                    alert ( 'Termino');
                    // No continuo.
                
                }
            }
        }
    });
}

function AnhadimosCamposVirtuemart(idVirtuemart) {
    // Objetivo
    // Vamos a la web y añadimos los campos personalizado si no existen ya claro..
    if ( totaProductoPeso => reg_inicial){
        // Debemos añadir ya los campos
        var parametros = {
            "pulsado"       : 'anhadirCamposIdVirtuemart',
            "idVirtuemart" : idVirtuemart
        };
        $.ajax({
            data       : parametros,
            url        : 'tareas.php',
            type       : 'post',
            beforeSend : function () {
                console.log('*********  Añadir campos personalizadso de peso a los productos ****************');
            },
            success    :  
            function (response) {
                console.log('*** Estoy devuelta de Añadir Campos en virtuemart ***');
                var resultado =  $.parseJSON(response);
                console.log(resultado.Datos);
                reg_inicial = reg_inicial +1 ;
                if ( totaProductoPeso > reg_inicial){
                    // Solo repito ciclo si es menor.

                    cicloCamposPersonalizado();
                }
            }
        });
        
    }


    
}
