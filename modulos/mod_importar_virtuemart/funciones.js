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
 
  if(tiendaWeb==0){
      alert("NO HAS SELECCIONADO UNA TIENDA WEB!");
  }
  if(tiendaWeb>0){
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
            
        }
        
    });
}

