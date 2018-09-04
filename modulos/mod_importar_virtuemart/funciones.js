var regWeb=0;
var bandera=0;
function enviarFormulario(){
  var tiendaWeb=$("#tiendaWeb").val();
 
  if(tiendaWeb==0){
      alert("NO HAS SELECCIONADO UNA TIENDA WEB!");
  }
 
  if(tiendaWeb>0){
    function returnCantProductos(cantProductos){
        regWeb=cantProductos;
    }
    contarProductosWeb(returnCantProductos);
    if(regWeb==0){
      bandera=setInterval(function(){ comprobarDatos(); }, 200);
    }
    contarProductosTpv();
  }
}
function comprobarDatos(){
    console.log("entro en comprobar datos"+regWeb);
    if(typeof regWeb=='string'){
        clearInterval(bandera);
    }
}

function contarProductosTpv(){
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
        success    :  function (response) {
            console.log('REspuesta contar productos tpv');
            var resultado =  $.parseJSON(response);
            console.log(resultado);
           
        }
        
    });
}

