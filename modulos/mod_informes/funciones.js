
import * as JSTpv from'./../../lib/js/tpvfox.js';
function metodoClick(pulsado){
    checkID = JSTpv.TfObtenerCheck('rowCheck');
    console.log(checkID);
    if (checkID.length >1 || checkID.length=== 0) {
        alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
        return
    }
    // Ahora obtenemos el valor de la opcion seleccionada.
    let opcion = document.getElementById('opcion'+checkID[0]).value;
    // Ahora montamos rango fecha
    let fecha_inicial = document.getElementById('idFechaInicio').value;
    let fecha_final = document.getElementById('idFechaFinal').value;
    if (fecha_final == ''){
        // Si la fecha inicio esta vacia
        let today = new Date();
        let day = today.getDate();
        let month = today.getMonth()+1;
        let year = today.getFullYear();
        fecha_final = year+'-'+month+'-'+day;
    }
    if (fecha_inicial == ''){
        // Si la fecha inicio esta vacia
        let today = new Date();
        
        let year = today.getFullYear();
        fecha_inicial = year+'-01-01';
    }
    if (fecha_inicial > fecha_final ) {
        alert ( 'Error:\n Fecha inicial no puede ser posterior a la fecha final\n o la fecha Final esta vacia');
        return
    }
    
    // Ahora montamos link y redirecionamos
    window.location.href = './informes.php?id='+checkID[0]+'&Finicio='+fecha_inicial+'&Ffinal='+fecha_final+'&opcion='+opcion;			
}
window.metodoClick = metodoClick;
