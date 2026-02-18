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
  console.log("Estamos en abrir modal de func_modal");
  $(".modal-body").html(contenido);
  $(".modal-title").html(titulo);
  // Adaptamos el titulo a SnakeCase
  var tituloSnakeCase = titulo.replace(/\s+/g, "_").toLowerCase();
  // Sustituimos el id del modal por el titulo en SnakeCase
  $("#ventanaModal").attr("id", tituloSnakeCase);
  $("#" + tituloSnakeCase).modal("show");
}

function cerrarPopUpConTitulo(titulo) {
  // @ Objetivo :
  // Cerrar modal ( popUp ), apuntar focus según pantalla cierre.
  // Adaptamos el titulo a SnakeCase
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
