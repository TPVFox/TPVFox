function metodoClick(pulsado,adonde){
    switch(pulsado) {
        case 'AgregarBalanza':
            window.location.href = './balanza.php';
        break;
        case 'VerBalanza':
        
        break;
    }
}
function AgregarBalanza(){
    var nombreBalanza=$('#nombreBalanza').val();
    var modeloBalanza=$('#modeloBalanza').val();
    if(nombreBalanza=="" || modeloBalanza==""){
        alert("Quedan campos IMPORTANTES sin cubrir!!");
    }else{
        var teclas=$('#teclas').val();
        var parametros={
            "pulsado"    	: 'addBalanza',
            'nombreBalanza' : nombreBalanza,
            'modeloBalanza' : modeloBalanza,
            'teclas'         :teclas
        }
        console.log(parametros);
        $.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  enviando datos para add balanzas ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de add balanzas');
			var resultado =  $.parseJSON(response);
			console.log(resultado);
            $('#errores').html(resultado['html']);
		}
	});
    }
}
