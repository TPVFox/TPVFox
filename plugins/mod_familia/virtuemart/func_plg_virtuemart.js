function  ObtenerDatosFamilia(){
    $('#nombreFamilia').val($('#inputnombre').val());
    $('#inputpadreWeb #combopadre').val($('#inputPadre #combopadre').val());
    $("#inputpadreWeb #combopadre option:selected").text($("#inputPadre #combopadre option:selected").text());
    console.log($('#inputpadreWeb #combopadre').val());
    console.log($('#inputPadre #combopadre').val());
}
$(function () {
    //~ $("#inputpadreWeb #combopadre").combobox({
        //~ select: function (event, ui) {
            //~ console.log("valor de input"+ui.item.value);
            //~ $('#inputpadreWeb #inputidpadre').val(ui.item.value);
        //~ },
    //~ });

});

function modificarFamiliaWeb(idFamilia="", idTienda=""){
    console.log("entre en modificar tienda web ");
    var mensaje = confirm("¿Estás seguro que quieres AÑADIR / MODIFICAR la tienda en la web?");
    if (mensaje) {   
        if( $('#nombreFamilia').val()==""){
            alert("NO has introducido ningún nombre de familia");
        }else{
            var datos={
                'idFamiliaPadre': $('#inputpadreWeb #combopadre').val(),
                'idFamiliaTpv': idFamilia,
                'idFamiliaWeb':$('#idFamiliaweb').val(),
                'idTienda': idTienda,
                'nombreFamilia': $('#nombreFamilia').val()
            };
            console.log(datos);
            var parametros = {
            "pulsado"    	: 'modificarFamiliaWeb',
            "datos"	: JSON.stringify(datos)
            };
              $.ajax({
            data       : parametros,
            url        : ruta_plg_virtuemart+'tareas_virtuemart.php',
            type       : 'post',
            beforeSend : function () {
            console.log('********* Envio los datos para modificar la familia en la web  **************');
            },
            success    :  function (response) {
                    console.log('Respuesta de modificar los datos de la web  ');
                    var resultado = $.parseJSON(response);
                    console.log(resultado);
                    if(resultado.htmlAlerta){
                        $('#alertasWeb').html(resultado.htmlAlerta);
                    }
                  
                    if(resultado.resul.Datos.idArticulo){
                        $('#idWeb').html(resultado.resul.Datos.idArticulo);
                        $('#botonWeb').val("Modificar en Web");
                    }
                    
                     
                }	
            });
        }
    }
}
