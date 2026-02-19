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
  // 1. Generamos el ID único (el "nombre" de nuestra caja)
  var tituloSnakeCase = titulo.replace(/\s+/g, "_").toLowerCase();

  // 2. BUSQUEDA: ¿Ya existe este modal en el DOM (el HTML)?
  var $modalExistente = $("#" + tituloSnakeCase);

  if ($modalExistente.length > 0) {
    // SI EXISTE: No clonamos, solo actualizamos el contenido del que ya está ahí
    console.log("El modal ya existe, actualizando contenido...");
    $modalExistente.find(".modal-body").html(contenido);
    $modalExistente.modal("show");
  } else {
    // NO EXISTE: Entonces sí, procedemos a clonar por primera vez
    console.log("Creando nuevo clon para: " + tituloSnakeCase);

    var $modalClonado = $("#ventanaModal").clone();
    $modalClonado.attr("id", tituloSnakeCase);
    $modalClonado.find(".modal-title").html(titulo);
    $modalClonado.find(".modal-body").html(contenido);

    $("body").append($modalClonado);
    $modalClonado.modal("show");

    // OJO: Si quieres que se pueda "actualizar" mientras está abierto,
    // quizás no quieras borrarlo inmediatamente al cerrar,
    // o asegúrate de que el .remove() funcione bien.
    $modalClonado.on("hidden.bs.modal", function () {
      $(this).remove();
    });
  }

  return tituloSnakeCase;
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
