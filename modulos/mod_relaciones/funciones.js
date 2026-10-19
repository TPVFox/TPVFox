// @ Objetivo
// Lo que hace el navegador en el listado de productos relacionados y en la pantalla de
// relación. Las comprobaciones las hace el servidor: aquí se pregunta, se envía y se muestra lo
// que responde. El límite de cada forma se aplica también aquí, con la misma tabla de formas que
// la admisión, para no dejar añadir lo que no cabe.

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
    case "Ver":
    case "Modificar":
      // Como en los listados de compras: actúan sobre una sola fila marcada.
      var marcadas = leerChecked("Check");
      if (marcadas.length !== 1) {
        alert("Marca una sola relación para " + pulsado.toLowerCase() + "la.");
        return;
      }
      var accion = pulsado === "Ver" ? "ver" : "editar";
      window.location.href = "./relacion.php?id=" + marcadas[0].value + "&accion=" + accion;
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

// --- Pantalla de relación ------------------------------------------------------------------

var cambiosSinGuardar = false;
var productosBuscados = [];
var ladoDeLaBusqueda = "entrada";
// Las líneas se piden al servidor en cada cambio: solo se pinta la respuesta de la última petición.
var peticionDeLineas = 0;

function prepararPantallaRelacion() {
  // @ Objetivo
  // Dejar la pantalla de relación lista al abrirla: el lado que se puede elegir y el aviso al
  // salir con cambios sin guardar.
  console.log("Pantalla de relación abierta");
  console.log(relacion);
  actualizarLado();
  window.onbeforeunload = function () {
    if (cambiosSinGuardar) {
      return "Hay cambios sin guardar.";
    }
  };
}

function controladorAcciones(caja, accion, tecla) {
  // @ Objetivo
  // Lo que hace cada tecla en las cajas de búsqueda, como en el albarán. teclado.js la llama en
  // cada pulsación; las que no tienen acción llegan como «error» y no hacen nada.
  // @ Parametros
  //   caja -> (cajainput) la caja en que se pulsó, con su valor.
  //   accion -> (string) la acción que parametros.xml da a esa tecla.
  switch (accion) {
    case "buscarProductos":
      console.log("Busco en " + caja.id_input + ": " + caja.darValor());
      buscarProductosRelacion(caja.id_input, caja.darValor());
      break;
    case "saltar_idArticulo":
      $("#idArticulo").focus();
      break;
    case "saltar_Referencia":
      $("#Referencia").focus();
      break;
    case "saltar_Codbarras":
      $("#Codbarras").focus();
      break;
    case "saltar_Descripcion":
      $("#Descripcion").focus();
      break;
  }
}

function buscarProductosRelacion(caja, texto) {
  // @ Objetivo
  // Buscar lo escrito en una caja. Un resultado se añade al lado elegido; varios se enseñan en
  // la ventana modal para elegir uno, con su id, su referencia, su código de barras y su nombre.
  // @ Parametros
  //   caja -> (string) la caja: idArticulo, Referencia, Codbarras o Descripcion.
  //   texto -> (string) lo escrito.
  if (texto.trim() === "") {
    return;
  }
  // El producto va al lado elegido al buscar, aunque el selector cambie mientras llega la respuesta.
  var lado = $("#lado").val();
  $.ajax({
    data: { pulsado: "buscarProductos", caja: caja, texto: texto },
    url: "tareas.php",
    type: "post",
    success: function (response) {
      var resultado = $.parseJSON(response);
      console.log("Respuesta de la búsqueda");
      console.log(resultado);
      if (resultado.error) {
        mostrarAviso(resultado.error.tipo, resultado.error.mensaje);
        return;
      }
      var productos = resultado.datos.productos;
      if (productos.length === 0) {
        mostrarAviso("info", "Ningún producto activo tiene «" + texto + "» en " + nombreDeLaCaja(caja) + ": revisa lo escrito o busca por otra caja.");
      } else if (productos.length === 1) {
        anadirProducto(productos[0], lado);
      } else {
        productosBuscados = productos;
        ladoDeLaBusqueda = lado;
        abrirModal("Productos encontrados", resultado.datos.html);
      }
    },
  });
}

function nombreDeLaCaja(caja) {
  // @ Objetivo
  // Cómo se llama en la pantalla cada caja de búsqueda, para nombrarla en los avisos.
  var nombres = { idArticulo: "el id", Referencia: "la referencia", Codbarras: "el código de barras", Descripcion: "el nombre" };
  return nombres[caja] || "esa caja";
}

function elegirProducto(posicion) {
  // @ Objetivo
  // Añadir el producto que se pulsa en la lista de la ventana modal.
  cerrarPopUp();
  anadirProducto(productosBuscados[posicion], ladoDeLaBusqueda);
}

function anadirProducto(producto, lado) {
  // @ Objetivo
  // Añadir un producto al lado elegido al buscarlo, si la forma del tipo lo admite y no está ya.
  // @ Parametros
  //   producto -> (object) el artículo encontrado.
  //   lado -> (string) entrada o salida.
  if (!tipos[relacion.idTipo]) {
    mostrarAviso("warning", "Elige primero el tipo de relación: hasta entonces no se puede añadir ningún producto.");
    return;
  }
  for (var i = 0; i < relacion.lineas.length; i++) {
    if (parseInt(relacion.lineas[i].idArticulo) === parseInt(producto.idArticulo)) {
      mostrarAviso("warning", "«" + producto.articulo_name + "» ya está en la relación, como " + relacion.lineas[i].sentido + ": un producto no se puede repetir.");
      return;
    }
  }
  if (!ladoAdmiteOtra(lado)) {
    var otro = lado === "entrada" ? "salida" : "entrada";
    mostrarAviso("warning", "Este tipo admite una sola " + lado + " y ya la tiene: añade «" + producto.articulo_name + "» como " + otro + ", o quita la " + lado + " que hay.");
    return;
  }
  relacion.lineas.push({
    idArticulo: producto.idArticulo,
    sentido: lado,
    cantidad: "",
    unidad: tipos[relacion.idTipo].llevaCantidad ? "kg" : "",
  });
  console.log("Añadido " + producto.idArticulo + " a " + lado);
  $("#avisosRelacion").empty();
  $("#idArticulo, #Referencia, #Codbarras, #Descripcion").val("");
  marcarCambio();
  componerLineas();
}

function lineasDelLado(lado) {
  // @ Objetivo
  // Cuántos productos hay en un lado.
  var cuantas = 0;
  for (var i = 0; i < relacion.lineas.length; i++) {
    if (relacion.lineas[i].sentido === lado) {
      cuantas++;
    }
  }
  return cuantas;
}

function ladoAdmiteOtra(lado, idTipo) {
  // @ Objetivo
  // Saber si la forma del tipo admite otro producto en ese lado: en las de uno a varios y uno a
  // uno entra uno solo, y en las de varios a uno y uno a uno sale uno solo.
  // @ Parametros
  //   lado -> (string) entrada o salida.
  //   idTipo -> (int) el tipo; sin él, el de la relación.
  var tipo = tipos[idTipo === undefined ? relacion.idTipo : idTipo];
  if (!tipo) {
    return true;
  }
  var forma = formas[tipo.forma];
  var unaSola = lado === "entrada" ? forma.unaSolaEntrada : forma.unaSolaSalida;
  return !(unaSola && lineasDelLado(lado) >= 1);
}

function actualizarLado() {
  // @ Objetivo
  // Dejar elegir solo el lado que admite otro producto. Si uno está lleno, el selector se fija
  // en el otro; si están llenos los dos, no se puede buscar. Sin tipo no se puede buscar nada,
  // como en el albarán hasta elegir el proveedor.
  if (!tipos[relacion.idTipo]) {
    $("#lado, #idArticulo, #Referencia, #Codbarras, #Descripcion").prop("disabled", true);
    return;
  }
  var admitenEntrada = ladoAdmiteOtra("entrada");
  var admitenSalida = ladoAdmiteOtra("salida");
  $('#lado option[value="entrada"]').prop("disabled", !admitenEntrada);
  $('#lado option[value="salida"]').prop("disabled", !admitenSalida);
  if (!admitenEntrada && admitenSalida) {
    $("#lado").val("salida");
  } else if (admitenEntrada && !admitenSalida) {
    $("#lado").val("entrada");
  }
  var nadaMas = !admitenEntrada && !admitenSalida;
  $("#lado, #idArticulo, #Referencia, #Codbarras, #Descripcion").prop("disabled", nadaMas);
}

function quitarLinea(idArticulo) {
  // @ Objetivo
  // Quitar un producto de lo que se prepara. Si estaba en la versión que rige, sigue a la vista,
  // tachado, para que se vea lo que se quita.
  for (var i = 0; i < relacion.lineas.length; i++) {
    if (parseInt(relacion.lineas[i].idArticulo) === idArticulo) {
      relacion.lineas.splice(i, 1);
      break;
    }
  }
  marcarCambio();
  componerLineas();
}

function reponerLinea(idArticulo) {
  // @ Objetivo
  // Devolver a lo que se prepara un producto quitado de la versión que rige, como estaba.
  for (var i = 0; i < ultimasLineas.length; i++) {
    var linea = ultimasLineas[i];
    if (parseInt(linea.idArticulo) === idArticulo && linea.marca === "quitada") {
      relacion.lineas.push({ idArticulo: linea.idArticulo, sentido: linea.sentido, cantidad: linea.cantidad, unidad: linea.unidad });
    }
  }
  marcarCambio();
  componerLineas();
}

function recogerLinea(elemento) {
  // @ Objetivo
  // Recoger, mientras se escribe, la cantidad o la unidad de una línea. La coma decimal se admite.
  var fila = $(elemento).closest("tr");
  var idArticulo = parseInt(fila.data("articulo"));
  for (var i = 0; i < relacion.lineas.length; i++) {
    if (parseInt(relacion.lineas[i].idArticulo) === idArticulo) {
      relacion.lineas[i].cantidad = fila.find("input.cantidad").val().trim().replace(",", ".");
      relacion.lineas[i].unidad = fila.find("select.unidad").val();
    }
  }
  marcarCambio();
}

function cambiarLinea(elemento) {
  // @ Objetivo
  // Al terminar de cambiar una línea, recogerla y volver a marcar lo que difiere.
  recogerLinea(elemento);
  componerLineas();
}

function reponerValores() {
  // @ Objetivo
  // Tras pintar las tablas, dejar en cada caja lo que hay en pantalla: si se siguió escribiendo
  // mientras llegaba la respuesta, no se pierde.
  for (var i = 0; i < relacion.lineas.length; i++) {
    var linea = relacion.lineas[i];
    var fila = $('tr.linea[data-articulo="' + parseInt(linea.idArticulo) + '"]').not(".marca-quitada");
    fila.find("input.cantidad").val(linea.cantidad === null ? "" : linea.cantidad);
    if (linea.unidad) {
      fila.find("select.unidad").val(linea.unidad);
    }
  }
}

function cambiarTipo(selector) {
  // @ Objetivo
  // Cambiar el tipo de una relación nueva. Si las líneas puestas no caben en la forma del tipo
  // nuevo, se avisa de lo que sobra y el tipo no cambia.
  var nuevo = parseInt(selector.value);
  if (tipos[nuevo]) {
    var forma = formas[tipos[nuevo].forma];
    var sobra = "";
    if (forma.unaSolaEntrada && lineasDelLado("entrada") > 1) {
      sobra = "Con ese tipo sobran entradas: admite una sola y hay " + lineasDelLado("entrada") + ".";
    } else if (forma.unaSolaSalida && lineasDelLado("salida") > 1) {
      sobra = "Con ese tipo sobran salidas: admite una sola y hay " + lineasDelLado("salida") + ".";
    }
    if (sobra !== "") {
      mostrarAviso("warning", sobra + " Quita las que sobran antes de cambiar el tipo.");
      selector.value = relacion.idTipo;
      return;
    }
    if (!tipos[nuevo].llevaCantidad) {
      for (var i = 0; i < relacion.lineas.length; i++) {
        relacion.lineas[i].cantidad = "";
        relacion.lineas[i].unidad = "";
      }
    }
    $(".columnaCantidad").toggle(tipos[nuevo].llevaCantidad);
  }
  relacion.idTipo = nuevo;
  marcarCambio();
  componerLineas();
}

function cambiarDatos() {
  // @ Objetivo
  // Un dato de la relación cambia: hay algo sin guardar y puede cambiar cómo constará.
  marcarCambio();
  componerLineas();
}

function marcarCambio() {
  cambiosSinGuardar = true;
}

function datosDeLaPantalla() {
  // @ Objetivo
  // Lo que hay en la pantalla, como lo espera el servidor.
  return {
    id: relacion.idTemporal,
    numRelacion: relacion.numRelacion,
    idTipo: relacion.idTipo,
    nombre: $("#nombre").val(),
    descripcion: $("#descripcion").val(),
    diasViabilidad: $("#diasViabilidad").val(),
    origenAjuste: $("#origenAjuste").length > 0 ? $("#origenAjuste").val() : "",
    lineas: relacion.lineas,
    fechaModificacion: relacion.fechaModificacion,
  };
}

function componerLineas(despues) {
  // @ Objetivo
  // Pedir al servidor las tablas de Entrada y de Salida con lo que difiere de la versión que
  // rige marcado, y el tipo de cambio que constará. Las compone el servidor con la misma
  // comparación que usará el historial.
  // @ Parametros
  //   despues -> (function) opcional: lo que se hace cuando están pintadas.
  var datos = datosDeLaPantalla();
  var estaPeticion = ++peticionDeLineas;
  $.ajax({
    data: {
      pulsado: "htmlLineasRelacion",
      numRelacion: datos.numRelacion,
      idTipo: datos.idTipo,
      lineas: datos.lineas,
      nombre: datos.nombre,
      descripcion: datos.descripcion,
      diasViabilidad: datos.diasViabilidad,
    },
    url: "tareas.php",
    type: "post",
    success: function (response) {
      if (estaPeticion !== peticionDeLineas) {
        console.log("Respuesta de líneas ya superada por otra");
        return;
      }
      var resultado = $.parseJSON(response);
      if (resultado.error) {
        mostrarAviso(resultado.error.tipo, resultado.error.mensaje);
        return;
      }
      ultimasLineas = resultado.datos.lineas;
      $("#tablaEntrada tbody").html(resultado.datos.entrada);
      $("#tablaSalida tbody").html(resultado.datos.salida);
      reponerValores();
      $("#tipoCambio").text(textosTipoCambio[resultado.datos.tipoCambio]);
      actualizarLado();
      if (typeof despues === "function") {
        despues();
      }
    },
  });
}

function mostrarAviso(tipo, mensaje) {
  // @ Objetivo
  // Enseñar un aviso encima de la relación, como texto.
  var aviso = $('<div class="alert"></div>').addClass("alert-" + tipo).text(mensaje);
  $("#avisosRelacion").html(aviso);
  window.scrollTo(0, Math.max(0, $("#avisosRelacion").offset().top - 80));
}

function nombreDeLaRelacion() {
  // @ Objetivo
  // Cómo se nombra la relación en las preguntas: con su número y su nombre, o como nueva.
  if (relacion.numRelacion > 0) {
    return "la relación " + relacion.numRelacion + " " + $("#nombre").val();
  }
  return "la relación nueva " + $("#nombre").val();
}

function guardarRelacion(despues) {
  // @ Objetivo
  // Guardar lo que se prepara, sin que rija. La primera vez crea el temporal. Pulsado
  // «Guardar», lo dice en verde; desde «Activar» no, para que un rechazo no salga junto a él.
  // @ Parametros
  //   despues -> (function) opcional: lo que se hace si se guardó.
  $.ajax({
    data: Object.assign({ pulsado: "guardarTemporal" }, datosDeLaPantalla()),
    url: "tareas.php",
    type: "post",
    beforeSend: function () {
      console.log("Guardo la relación en preparación");
    },
    success: function (response) {
      var resultado = $.parseJSON(response);
      console.log(resultado);
      if (resultado.error) {
        mostrarAviso(resultado.error.tipo, resultado.error.mensaje);
        return;
      }
      relacion.idTemporal = resultado.datos.id;
      relacion.fechaModificacion = resultado.datos.fechaModificacion;
      cambiosSinGuardar = false;
      $("#datoTemporal").val(resultado.datos.id);
      $("#datoInicio").val(resultado.datos.fechaInicio);
      $("#datoGuardado").val(usuarioDeLaSesion + ", " + resultado.datos.fechaModificacion);
      if (typeof despues === "function") {
        despues();
      } else {
        mostrarAviso("success", "Guardado en el temporal " + resultado.datos.id + ": lo preparado no rige hasta activarlo.");
      }
    },
  });
}

function activarRelacion() {
  // @ Objetivo
  // Poner a regir lo preparado: primero se guarda lo que hay en pantalla y se vuelve a marcar, y
  // después se pregunta diciendo cómo constará en el historial.
  guardarRelacion(function () {
    componerLineas(preguntarYActivar);
  });
}

function preguntarYActivar() {
  // @ Objetivo
  // Preguntar, con el tipo de cambio recién calculado, y activar lo guardado.
  var pregunta = "¿Poner a regir " + nombreDeLaRelacion() + "?\nConstará como " + $("#tipoCambio").text() + ".";
  if (!confirm(pregunta)) {
    console.log("Activación cancelada");
    return;
  }
  $.ajax({
    data: { pulsado: "activarTemporal", id: relacion.idTemporal },
    url: "tareas.php",
    type: "post",
    success: function (response) {
      var resultado = $.parseJSON(response);
      console.log(resultado);
      if (resultado.error) {
        mostrarAviso(resultado.error.tipo, resultado.error.mensaje);
        return;
      }
      cambiosSinGuardar = false;
      window.location.href = "./ListaRelaciones.php?activada=" + resultado.datos.numRelacion + "&version=" + resultado.datos.version;
    },
  });
}

function cancelarRelacion() {
  // @ Objetivo
  // Borrar lo que se prepara, tras confirmarlo nombrando la relación, y volver al listado. Si
  // no se llegó a guardar, no hay nada que borrar.
  if (!confirm("¿Cancelar lo que se prepara de " + nombreDeLaRelacion() + "?\nSe borra lo guardado y lo que no se haya guardado.")) {
    console.log("Cancelación no confirmada");
    return;
  }
  if (relacion.idTemporal === 0) {
    cambiosSinGuardar = false;
    window.location.href = "./ListaRelaciones.php?cancelada=1";
    return;
  }
  $.ajax({
    data: { pulsado: "cancelarTemporal", id: relacion.idTemporal },
    url: "tareas.php",
    type: "post",
    success: function (response) {
      var resultado = $.parseJSON(response);
      if (resultado.error) {
        mostrarAviso(resultado.error.tipo, resultado.error.mensaje);
        return;
      }
      cambiosSinGuardar = false;
      window.location.href = "./ListaRelaciones.php?cancelada=1";
    },
  });
}
