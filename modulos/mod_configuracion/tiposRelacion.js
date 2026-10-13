// El bloque «Tipos de relación» de las tablas principales: abre el formulario de un tipo y
// envía sus operaciones a tareas.php. El servidor decide; aquí solo se recoge y se muestra.

function enviarTipoRelacion(parametros, alTerminar) {
  $.ajax({
    data: parametros,
    url: "tareas.php",
    type: "post",
    success: function (response) {
      var resultado = $.parseJSON(response);
      if (resultado !== null && resultado.error) {
        alert(resultado.error.mensaje);
      } else {
        alTerminar(resultado);
      }
    },
  });
}

function abrirModalTipoRelacion(id) {
  enviarTipoRelacion({ pulsado: "abrirModalTipoRelacion", id: id }, function (resultado) {
    var titulo = id == 0 ? "Nuevo tipo de relación" : "Tipo de relación";
    abrirModal(titulo, resultado.html);
  });
}

function recargarTrasTipoRelacion() {
  cerrarPopUp();
  location.reload(true);
}

function guardarTipoRelacion() {
  var formulario = $("#formTipoRelacion");
  var id = formulario.data("id");
  var nombre = $("#tipoRelacionNombre").val();
  var forma = $("#tipoRelacionForma").val();
  var llevaCantidad = $("#tipoRelacionCantidad").is(":checked") ? 1 : 0;

  // En un tipo que ya existe, el nombre y la forma son dos operaciones, cada una con su permiso.
  var cambiaNombre = nombre !== String(formulario.data("nombre"));
  var cambiaForma = forma !== formulario.data("forma") || llevaCantidad != formulario.data("cantidad");
  var modificar = function () {
    if (cambiaForma) {
      enviarTipoRelacion(
        { pulsado: "modificarTipoRelacion", id: id, forma: forma, llevaCantidad: llevaCantidad },
        recargarTrasTipoRelacion
      );
    } else {
      recargarTrasTipoRelacion();
    }
  };
  if (id == 0) {
    enviarTipoRelacion(
      { pulsado: "crearTipoRelacion", nombre: nombre, forma: forma, llevaCantidad: llevaCantidad },
      recargarTrasTipoRelacion
    );
  } else if (cambiaNombre) {
    enviarTipoRelacion({ pulsado: "renombrarTipoRelacion", id: id, nombre: nombre }, modificar);
  } else {
    modificar();
  }
}

function operacionTipoRelacion(pulsado) {
  var id = $("#formTipoRelacion").data("id");
  if (pulsado !== "borrarTipoRelacion" || confirm("¿Borrar este tipo de relación?")) {
    enviarTipoRelacion({ pulsado: pulsado, id: id }, recargarTrasTipoRelacion);
  }
}
