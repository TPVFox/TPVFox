var regWeb=0;
var regTPV=0;
var callbackContarRegistrosWeb= function (response) {
				console.log('Respuesta de contar los productos web ');
				var resultado = $.parseJSON(response);
                cantProductos=resultado['Datos']['item']['productosWeb'];
                regWeb=cantProductos;
                contarProductosTpv( cantProductos);
}

function enviarFormulario(){
    var tiendaWeb=$("#tiendaWeb").val();
    var sukWeb=$("#sukWeb").val();
    var refWeb=$("#refWeb").val();
    var publicado=$("#estadoPublicado").val();
    var noPublicado=$("#estadoNoPublicado").val();
  if(tiendaWeb==0){
      alert("NO HAS SELECCIONADO UNA TIENDA WEB!");
  }
  if(sukWeb==0){
      alert("NO HAS SELECCIONADO UNA OPCIÓN VALIDA DE CÓDIGO DE BARRAS!");
  }
  if(refWeb==0){
      alert("NO HAS SELECCIONADO UNA OPCIÓN VALIDA DE REFERENCIA WEB!");
  }
  if(refWeb==0){
      alert("NO HAS SELECCIONADO UNA OPCIÓN VALIDA DE REFERENCIA WEB!");
  }
  if(publicado==0){
      alert("NO HAS SELECCIONADO UNA OPCIÓN PARA EL ESTADO PUBLICADO!");
  }
  if(noPublicado==0){
      alert("NO HAS SELECCIONADO UNA OPCIÓN PARA EL ESTADO NO PUBLICADO!");
  }
  if(tiendaWeb>0 && sukWeb>0 && refWeb>0 && publicado>0 && noPublicado>0){
    contarProductosWeb(callbackContarRegistrosWeb);
  }
  
}

function contarProductosTpv(callback){
    console.log(regWeb);
      var parametros = {
            "pulsado"   : 'contarProductostpv',
           
        };
    $.ajax({
        data       : parametros,
        url        : 'tareas.php',
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
             $( "#NuevosWeb" ).html(total);
             $("#DivOpciones").show();
        }
        
    });
}

function comprobarProductos(productos){
    var tiendaWeb=$("#tiendaWeb").val();
    var parametros = {
            "pulsado"   : 'comprobarProductos',
            "productos" : productos,
            "idTienda"  :tiendaWeb
    };
     $.ajax({
        data       : parametros,
        url        : 'tareas.php',
        type       : 'post',
        beforeSend : function () {
            console.log('*********  comprobar productos ****************');
        },
        success    :  
        function (response) {
            console.log('Respuesta de comprobar productos');
            var resultado =  $.parseJSON(response);
            console.log(resultado);
            $("#productosNuevos").html(resultado['htmlNuevos']);
            $("#productosMod").html(resultado['htmlMod']);
        }
        
    });
}

function modificarProductosTpvWeb(nombre, refTienda, iva, precioSiva, codBarras, id, linea){
    console.log("Entre en modificar pro");
    var parametros = {
            "pulsado"   : 'modificarProducto',
            "nombre"    : nombre,
            "refTienda" :refTienda,
            "iva"       :iva,
            "precioSiva":precioSiva,
            "codBarras":codBarras,
            "id"        :id
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
            if(resultado['modificar']['PrimeraConsulta']['NAfectados']>=0 && resultado['modificar']['SegundaConsulta']['NAfectados']>=0){
                 $("#mod_" + linea).remove();   
            }else{
                alert(resultado['modificar']['PrimeraConsulta']['error']+ resultado['modificar']['PrimeraConsulta']['consulta']) ;
            }
          
        }
        
    });
}
