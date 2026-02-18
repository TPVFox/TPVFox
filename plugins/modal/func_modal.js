// Funciones necesarias para plugin de modal.

function abrirModal(titulo, contenido) {
  // @ Objetivo :
  // Abril modal con texto buscado y con titulo que le indiquemos.
  console.log("Estamos en abrir modal de func_modal");
  $(".modal-body").html(contenido);
  $(".modal-title").html(titulo);
  $("#ventanaModal").modal("show");
}

function cerrarPopUp() {
  // @ Objetivo :
  // Cerrar modal ( popUp ), apuntar focus según pantalla cierre.
  //cerrar modal busqueda
  $("#ventanaModal").modal("hide");
}

function abrirModalConTitulo(titulo, contenido) {
  // @ Objetivo :
  // Abril modal con texto buscado y con titulo que le indiquemos. (Permite usar varios modales superpuestos con titulos diferentes)
  console.log("Estamos en abrir modalConTitulo de func_modal");

  // Adaptamos el titulo a SnakeCase para usarlo como ID único
  var tituloSnakeCase = titulo.replace(/\s+/g, "_").toLowerCase();

  // Clonamos el modal original
  var $modalClonado = $("#ventanaModal").clone();

  // Asignamos nuevo ID al modal clonado
  $modalClonado.attr("id", tituloSnakeCase);

  // Actualizamos título y contenido
  $modalClonado.find(".modal-title").html(titulo);
  $modalClonado.find(".modal-body").html(contenido);

  // Agregamos al body
  $("body").append($modalClonado);

  // Mostramos el modal
  $("#" + tituloSnakeCase).modal("show");

  // Cuando se cierre, eliminar del DOM
  $("#" + tituloSnakeCase).on("hidden.bs.modal", function () {
    $(this).remove();
  });

  return tituloSnakeCase; // opcional, por si quieres cerrarlo con JS
}

function cerrarPopUpConTitulo(titulo) {
  // @ Objetivo :
  // Cerrar modal ( popUp ), apuntar focus según pantalla cierre.
  // Adaptamos el titulo a SnakeCase  // Adaptamos el titulo a SnakeCase
  var tituloSnakeCase = titulo.replace(/\s+/g, "_").toLowerCase();
  $("#" + tituloSnakeCase).modal("hide");
}

function focusAlLanzarModal(idCaja) {
  // @Objetivo:
  // Poner focus cuando esta visible el evento modal.
  // Se espera que concluyan las transiciones de CSS

  $("#ventanaModal").on("shown.bs.modal", function () {
    // Pongo focus a cada cja pero no se muy bien, porque no funciona si pongo el focus en la accion realizada.
    $("#" + idCaja).focus(); //foco en input caja busqueda del proveedor
  });
}

function SelectAlLanzarModal(idCaja) {
  // @Objetivo:
  // Poner select cuando esta visible el evento modal.
  // Se espera que concluyan las transiciones de CSS

  $("#ventanaModal").on("shown.bs.modal", function () {
    $("#" + idCaja).select(); //foco en input caja busqueda del proveedor
  });
}

// -- CSS para fila de tabla...
