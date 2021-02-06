function  ObtenerDatosFamilia(){
    $('#nombreFamilia').val($('#inputnombre').val());
    var parametros = {
            "pulsado"    	: 'obtenerIdFamiliaWeb',
            "idFamiliaTpv"	: $('#inputPadre #combopadre').val()
            };

    $.ajax({
        data       : parametros,
        url        : ruta_plg_virtuemart+'tareas_virtuemart.php',
        type       : 'post',
        beforeSend : function () {
        console.log('********* Envio solicitud de dato id Familia  **************');
        },
        success    :  function (response) {
                console.log('Respuesta de obtener idfamilia relacionada');
                var resultado = $.parseJSON(response);
                if (!resultado['idFamiliaWeb']){
                   // Tuvo que haber un error , por lo que idFamilia
                    resultado['idFamiliaWeb'] = 0;
                    if (resultado['html_alerta']){
                         $('#alertasWeb').html(resultado.html_alerta);
                    }
                } else {
                    var obtengoIndexSelect = $("#combopadreWeb").prop("selectedIndex");
                    var valorIndexSelect = $("#combopadreWeb option");
                    var id = resultado['idFamiliaWeb'];
                    if (valorIndexSelect[obtengoIndexSelect].value !== id){
                        // Elimino selected de actual seleccion.
                        $("#combopadreWeb option:selected").removeAttr("selected");
                        // Añado selected a valor obtenido.
                        $("#combopadreWeb option[value="+id+"]").attr("selected","selected");
                        alert ( 'La familia obtenida no es la misma que la que tienes ahora seleccionada \nno se actualiza en esta pantalla, deberas refrescar');
                    }
                }
                console.log(resultado);
                $('#inputidpadreWeb').val(resultado['idFamiliaWeb']);
            }
        });
    // Ahora debemos llamar a funcion obtener select de combo ya que tiene select que no pertenece.
    
    console.log($('#inputpadreWeb #combopadreWeb').val());
    console.log($('#inputPadre #combopadre').val());
}


function modificarFamiliaWeb(idFamilia="", idTienda=""){
    // Llegamos aquí tanto al modificar como al añadir nueva familia.
    console.log("entre en modificar tienda web ");
    var mensaje = confirm("¿Estás seguro que quieres AÑADIR / MODIFICAR la tienda en la web?");
    if (mensaje) {   
        if( $('#nombreFamilia').val()==""){
            alert("NO has introducido ningún nombre de familia");
        }else{
            var datos={
                'idFamiliaPadreWeb': $('#inputidpadreWeb').val(),
                'idFamiliaTpv': idFamilia,
                'idFamiliaWeb':$('#idFamiliaweb').val(),
                'idTienda': idTienda,
                'nombreFamilia': $('#nombreFamilia').val()
            };
            console.log(ruta_plg_virtuemart);
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
                    var addFamilia = resultado.addFamilia;
                    if (addFamilia){
                        if(resultado.addFamilia.Datos.idFamilia){
                            // Ponemos en input el idFamilia creado, para que no pueda volver a crear ( duplicado)
                            $('#idFamiliaweb').val(resultado.addFamilia.Datos.idFamilia);
                            $('#botonWeb').val("Modificar en Web");
                            $("#botonWeb").attr('class', 'btn btn-primary');
                            
                        }
                    }
                    
                     
                }	
            });
        }
    }
}

function subirHijosWeb(idPadre, idTienda){
    var parametros = {
        pulsado: 'subirHijosWeb',
        'idPadre': idPadre,
        'idTienda': idTienda
    };
    console.log(parametros);
      $.ajax({
        data       : parametros,
        url        :  ruta_plg_virtuemart+'tareas_virtuemart.php',
        type       : 'post',
        beforeSend : function () {
            console.log('*********  entre en subir hijos a web ****************');
        },
        success    :  
        function (response) {
            console.log('REspuesta de subir hijos a web');
            var resultado =  $.parseJSON(response);
            console.log(resultado);
            if(resultado.familiasSubidas.length >0){
                   alert("Familias subidas : "+JSON.stringify(resultado.familiasSubidas));
            }
            if(resultado.familiasNoSubidas.length >0){
                   alert("Familias NO subidas : "+JSON.stringify(resultado.familiasNoSubidas));
            }
        }
        
    });
}




