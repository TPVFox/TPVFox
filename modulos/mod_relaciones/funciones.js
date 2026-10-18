// @ Objetivo
// Lo que hace el navegador en el listado de productos relacionados. Las comprobaciones las
// hace el servidor: aquí se pregunta, se envía y se muestra lo que responde.

function metodoClick(pulsado, estado) {
  // @ Objetivo
  // Saber qué opción general se pulsó y actuar sobre las filas marcadas.
  // @ Parametros
  //   pulsado -> (string) la opción pulsada.
  //   estado -> (string) el estado al que lleva «Cambiar estado».
  console.log("Pulsado en el listado de relaciones: " + pulsado);
  switch (pulsado) {
    case "cambiarEstado":
      var checkID = leerChecked("Check");
      if (checkID.length === 0) {
        alert("No tienes items seleccionados");
        return;
      }
      cambiarEstadoRelaciones(checkID, estado);
      break;
  }
}

function cambiarEstadoRelaciones(checkID, estado) {
  // @ Objetivo
  // Pedir confirmación nombrando cada relación marcada y el estado al que pasa, y enviar el
  // cambio. Antes de recargar el listado se muestran los avisos de las que no se cambiaron.
  // @ Parametros
  //   checkID -> (array) las casillas marcadas: su valor es el número de la relación.
  //   estado -> (string) el estado al que pasan.
  var numRelaciones = [];
  var nombres = [];
  for (var i = 0; i < checkID.length; i++) {
    numRelaciones.push(checkID[i].value);
    nombres.push(checkID[i].value + " " + checkID[i].getAttribute("data-nombre"));
  }
  if (!confirm("¿Pasar a " + estado + " estas relaciones?\n" + nombres.join("\n"))) {
    console.log("Cambio de estado cancelado");
    return;
  }
  var parametros = {
    pulsado: "cambiarEstadoRelaciones",
    numRelaciones: numRelaciones,
    estado: estado,
  };
  $.ajax({
    data: parametros,
    url: "tareas.php",
    type: "post",
    beforeSend: function () {
      console.log("Envío el cambio de estado de " + numRelaciones.length + " relaciones");
    },
    success: function (response) {
      var resultado = $.parseJSON(response);
      console.log("Respuesta del cambio de estado");
      console.log(resultado);
      var mensajes = [];
      if (resultado.error) {
        mensajes.push(resultado.error.mensaje);
      }
      if (resultado.avisos) {
        for (var i = 0; i < resultado.avisos.length; i++) {
          mensajes.push(resultado.avisos[i].mensaje);
        }
      }
      if (mensajes.length > 0) {
        alert(mensajes.join("\n"));
      }
      location.reload();
    },
  });
}
