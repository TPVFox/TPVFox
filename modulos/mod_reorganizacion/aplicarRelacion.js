/*
 * Aplicar una relación a un periodo: ofrece los meses o los días con entradas del producto, pinta
 * el reparto entre las recetas —donde, al calcular, se escribe la cantidad de cada una y se vuelve a
 * calcular—, pide el cálculo al servidor, pinta los avisos, los datos y los escandallos, cada cosa en
 * su zona, y prepara el albarán
 * de compras, que se abre en su pantalla para revisarlo y guardarlo. El cálculo y el aviso de
 * proceso duplicado los hace siempre el servidor, también al preparar: aquí solo se recoge lo
 * elegido y se pinta lo que llega.
 */

// Los precios de mercado escritos en la tabla, por artículo, hasta que se cambie de relación.
var preciosEscritosAplicacion = {};

// El árbol de las recetas que se aplican a la vez: la relación de la que sale, las recetas que
// ofrece el servidor —hermanas y de los derivados—, las añadidas —con lo escrito en su rama— y los
// precios escritos en el escandallo de cada una, por relación y artículo.
var relacionDelArbol = "";
var hermanasOfrecidas = [];
var hermanasDelArbol = [];
var preciosDeHermanas = {};

// La receta elegida, con los productos que salen de ella, con la que empieza el árbol.
var recetaElegidaAplicacion = null;

// La cantidad escrita en el resultado para la receta elegida; vacía, el cálculo aplica lo disponible
// o, con más recetas, lo que sobra. Se olvida al cambiar un dato de la elección.
var cantidadEscritaAplicacion = "";

// Cuenta las peticiones de periodos: si llega tarde la respuesta de una anterior, se ignora.
var peticionesDePeriodosAplicacion = 0;

var mesesAplicacion = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];

function ajaxAplicarRelacion(parametros, callback) {
  console.log("Aplicar relación: petición " + parametros.pulsado, parametros);
  $.ajax({
    data: parametros,
    url: "./tareas.php",
    type: "post",
    success: callback,
    error: function (request, textStatus, error) {
      console.log("Aplicar relación: la petición falló", textStatus, error);
      pintarAvisosAplicacion([{ tipo: "danger", mensaje: "No se pudo hablar con el servidor: vuelve a intentarlo y, si se repite, avisa al administrador." }]);
    },
  });
}

function peticionDeAplicacion(pulsado) {
  return {
    pulsado: pulsado,
    idProveedor: $("#idProveedorAplicacion").val(),
    numRelacion: $("#numRelacionAplicacion").val(),
    tipoPeriodo: $("#tipoPeriodoAplicacion").val(),
    periodo: $("#periodoAplicacion").val(),
    version: $("#versionAplicacion").val(),
    idProveedorTraspaso: $("#idProveedorTraspasoAplicacion").val(),
    precios: JSON.stringify(preciosEscritosAplicacion),
    ajustePeso: $("#ajustePesoAplicacion").val(),
    ajusteValor: ajusteDeValorAplicacion(),
    cantidad: cantidadEscritaAplicacion,
    porEntradas: $("#porEntradasAplicacion").is(":checked") ? "1" : "",
    hermanas: JSON.stringify(hermanasParaLaPeticion()),
    destino: $("#destinoAplicacion").val(),
  };
}

function ajusteDeValorAplicacion() {
  // Las dos partes del ajuste suman 2: la de valor es lo que le falta a la de peso.
  var peso = parseFloat($("#ajustePesoAplicacion").val());
  return String(Math.round((2 - peso) * 100) / 100);
}

function cambiarTipoPeriodoAplicacion() {
  // Un mes se elige entre los meses con entradas; un lote, entre los días.
  console.log("Aplicar relación: periodo por " + $("#tipoPeriodoAplicacion").val());
  olvidarCantidadesAplicacion();
  limpiarResultadoAplicacion();
  cargarPeriodosAplicacion();
}

function cambiarRelacionAplicacion() {
  // Los precios escritos son de las piezas de una relación: con otra, se olvidan. El árbol y su
  // lista son de la relación elegida: si es otra, se vacían y se vuelven a pedir las hermanas.
  var numRelacion = $("#numRelacionAplicacion").val();
  console.log("Aplicar relación: otra relación, " + numRelacion);
  preciosEscritosAplicacion = {};
  if (numRelacion !== relacionDelArbol) {
    relacionDelArbol = numRelacion;
    hermanasDelArbol = [];
    preciosDeHermanas = {};
    recetaElegidaAplicacion = null;
    olvidarCantidadesAplicacion();
    pintarArbolAplicacion();
    cargarHermanasAplicacion();
  }
  limpiarResultadoAplicacion();
  cargarPeriodosAplicacion();
}

function cargarHermanasAplicacion() {
  // Pide las recetas hermanas de la relación elegida para la lista de abajo.
  var pedida = relacionDelArbol;
  hermanasOfrecidas = [];
  pintarHermanasAplicacion();
  if (pedida === "") {
    return;
  }
  ajaxAplicarRelacion({ pulsado: "hermanasAplicacion", numRelacion: pedida }, function (respuesta) {
    var obj = JSON.parse(respuesta);
    console.log("Aplicar relación: hermanas", obj);
    if (pedida !== relacionDelArbol) {
      return;
    }
    if (obj.ok) {
      hermanasOfrecidas = obj.datos;
      recetaElegidaAplicacion = obj.elegida;
    }
    pintarHermanasAplicacion();
    pintarArbolAplicacion();
  });
}

function seVeEnLaListaAplicacion(receta) {
  // Las hermanas y las que parten de un producto de la elegida se ven siempre; las de un derivado,
  // cuando su producto sale de una receta añadida.
  if (receta.origen !== "derivado") {
    return true;
  }
  var colgada = false;
  $.each(hermanasDelArbol, function (indice, anadida) {
    if ($.inArray(receta.idEntrada, anadida.salidas) !== -1) {
      colgada = true;
    }
  });
  return colgada;
}

function pintarHermanasAplicacion() {
  // La lista de las recetas que se pueden añadir ya; la que está en el árbol queda inactiva.
  var lista = $("#hermanasAplicacion");
  lista.empty();
  var visibles = $.grep(hermanasOfrecidas, seVeEnLaListaAplicacion);
  if (visibles.length === 0) {
    return;
  }
  $("<h4>").text("Recetas que se pueden añadir").appendTo(lista);
  $.each(visibles, function (indice, hermana) {
    var anadida = buscarHermanaAplicacion(hermana.numRelacion) !== null;
    var fila = $("<div>").addClass("hermana").attr("data-num", hermana.numRelacion).appendTo(lista);
    $("<span>").text(hermana.nombre + " (" + hermana.entrada + ")" + (anadida ? " — añadida " : " ")).appendTo(fila);
    $("<button>").attr("type", "button").addClass("btn btn-default btn-xs").text("Añadir").prop("disabled", anadida)
      .on("click", function () {
        anadirHermanaAplicacion(hermana);
      })
      .appendTo(fila);
  });
}

function buscarHermanaAplicacion(numRelacion) {
  var encontrada = null;
  $.each(hermanasDelArbol, function (indice, hermana) {
    if (String(hermana.numRelacion) === String(numRelacion)) {
      encontrada = hermana;
    }
  });
  return encontrada;
}

function anadirHermanaAplicacion(hermana) {
  console.log("Aplicar relación: añade la hermana " + hermana.numRelacion);
  if (buscarHermanaAplicacion(hermana.numRelacion) === null) {
    hermanasDelArbol.push({ numRelacion: hermana.numRelacion, nombre: hermana.nombre, entrada: hermana.entrada, idEntrada: hermana.idEntrada,
      salidas: hermana.salidas, piezas: hermana.piezas, origen: hermana.origen, cantidad: "", ajustePeso: "1" });
  }
  limpiarResultadoAplicacion();
  pintarArbolAplicacion();
  pintarHermanasAplicacion();
}

function quitarHermanaAplicacion(numRelacion) {
  // Con la rama se van las que colgaban de sus derivados, y las que colgaban de estas.
  console.log("Aplicar relación: quita la receta " + numRelacion);
  hermanasDelArbol = $.grep(hermanasDelArbol, function (hermana) {
    return String(hermana.numRelacion) !== String(numRelacion);
  });
  delete preciosDeHermanas[numRelacion];
  var quedan = hermanasDelArbol.length + 1;
  while (hermanasDelArbol.length < quedan) {
    quedan = hermanasDelArbol.length;
    hermanasDelArbol = $.grep(hermanasDelArbol, seVeEnLaListaAplicacion);
  }
  limpiarResultadoAplicacion();
  pintarArbolAplicacion();
  pintarHermanasAplicacion();
}

function pintarArbolAplicacion() {
  // El reparto entre las recetas, en una tabla como la de familias: una fila por producto y por
  // receta, de arriba abajo y sangrada por su nivel. El producto de partida, sus recetas —la elegida y
  // las hermanas añadidas— y, bajo cada receta, los productos que salen de ella, cada uno con las
  // recetas añadidas que parten de él. Las recetas de un producto que sale por dos caminos cuelgan del
  // primero. Aquí solo se reparte: los escandallos van en su zona.
  var arbol = $("#arbolAplicacion");
  arbol.empty();
  if (recetaElegidaAplicacion === null) {
    return;
  }
  $("<h4>").text("Reparto entre las recetas").appendTo(arbol);
  var tabla = $("<table>").addClass("table table-bordered table-hover table-striped").appendTo(arbol);
  var cabecera = $("<tr>").appendTo($("<thead>").appendTo(tabla));
  $.each(["Producto o receta", "Cantidad", ""], function (indice, titulo) {
    $("<th>").text(titulo).appendTo(cabecera);
  });
  var cuerpo = $("<tbody>").appendTo(tabla);
  pintarProductoDelArbolAplicacion(cuerpo, recetaElegidaAplicacion.idEntrada, recetaElegidaAplicacion.entrada, 0, {});
  actualizarCantidadesPreviasAplicacion();
}

function filaDelArbolAplicacion(cuerpo, nivel) {
  // Una fila con sus tres celdas; la primera, sangrada por su nivel.
  var fila = $("<tr>").attr("data-nivel", nivel).appendTo(cuerpo);
  $("<td>").css("padding-left", (8 + nivel * 24) + "px").appendTo(fila);
  $("<td>").appendTo(fila);
  $("<td>").appendTo(fila);
  return fila;
}

function pintarProductoDelArbolAplicacion(cuerpo, idArticulo, nombre, nivel, conRecetas) {
  // Una fila de producto y, debajo, las recetas que parten de él, si no se han pintado ya en otro sitio.
  var fila = filaDelArbolAplicacion(cuerpo, nivel).addClass("producto").attr("data-id", idArticulo);
  var celdas = fila.children("td");
  if (nivel === 0) {
    fila.addClass("raiz");
    celdas.eq(0).append(document.createTextNode("Producto de partida: "));
  }
  $("<span>").addClass("nombre").text(nombre).appendTo(celdas.eq(0));
  $("<span>").addClass("resumen").appendTo(celdas.eq(1));
  var recetas = $.grep(hermanasDelArbol, function (receta) {
    return String(receta.idEntrada) === String(idArticulo);
  });
  if (nivel === 0) {
    recetas.unshift(recetaElegidaAplicacion);
  }
  if (recetas.length === 0) {
    return;
  }
  if (conRecetas[idArticulo]) {
    celdas.eq(2).text("Sus recetas van más arriba, donde sale primero");
    return;
  }
  conRecetas[idArticulo] = true;
  if (nivel > 0) {
    celdas.eq(2).append(document.createTextNode(recetas.length + (recetas.length === 1 ? " receta " : " recetas ")));
    $("<button>").attr("type", "button").addClass("btn btn-primary btn-sm btnPlegar")
      .html('<span class="glyphicon glyphicon-minus"></span> compactar')
      .on("click", function () {
        plegarProductoAplicacion(fila, $(this));
      })
      .appendTo(celdas.eq(2));
  }
  $.each(recetas, function (indice, receta) {
    pintarRecetaDelArbolAplicacion(cuerpo, receta, nivel + 1, conRecetas);
  });
}

function pintarRecetaDelArbolAplicacion(cuerpo, receta, nivel, conRecetas) {
  // Una fila de receta —la elegida; una añadida, con «Quitar»— y, debajo, los productos que salen de
  // ella. La cantidad se escribe al calcular; el ajuste de cada receta va en su escandallo.
  var fila = filaDelArbolAplicacion(cuerpo, nivel);
  var celdas = fila.children("td");
  $("<span>").addClass("glyphicon glyphicon-share-alt").css("margin-right", "4px").appendTo(celdas.eq(0));
  $("<strong>").addClass("nombre").text(receta.nombre).appendTo(celdas.eq(0));
  $("<span>").addClass("cantidadPrevia").appendTo(celdas.eq(1));
  if (receta === recetaElegidaAplicacion) {
    fila.addClass("principal");
    $("<span>").addClass("resultadoPrincipal").appendTo(celdas.eq(1));
  } else {
    fila.addClass("rama").attr("data-num", receta.numRelacion);
    $("<span>").addClass("resultadoHermana").appendTo(celdas.eq(1));
    $("<button>").attr("type", "button").addClass("btn btn-primary btn-sm")
      .html('<span class="glyphicon glyphicon-trash"></span> Quitar')
      .on("click", function () {
        quitarHermanaAplicacion(receta.numRelacion);
      })
      .appendTo(celdas.eq(2));
  }
  $.each(receta.piezas, function (indice, pieza) {
    pintarProductoDelArbolAplicacion(cuerpo, pieza.idArticulo, pieza.articulo, nivel + 1, conRecetas);
  });
}

function plegarProductoAplicacion(fila, boton) {
  // Compactar oculta lo que cuelga del producto; expandir lo vuelve a enseñar todo.
  var nivel = parseInt(fila.attr("data-nivel"), 10);
  var compactar = !fila.hasClass("compactada");
  console.log("Aplicar relación: " + (compactar ? "compacta " : "expande ") + fila.find(".nombre").text());
  fila.toggleClass("compactada", compactar);
  boton.html(compactar ? '<span class="glyphicon glyphicon-plus"></span> expandir' : '<span class="glyphicon glyphicon-minus"></span> compactar');
  var siguiente = fila.next();
  while (siguiente.length > 0 && parseInt(siguiente.attr("data-nivel"), 10) > nivel) {
    siguiente.toggle(!compactar);
    siguiente.removeClass("compactada");
    siguiente.find(".btnPlegar").html('<span class="glyphicon glyphicon-minus"></span> compactar');
    siguiente = siguiente.next();
  }
}

function tomaLoGeneradoAplicacion(hermana) {
  // De las recetas vacías de un derivado, la primera de la lista toma lo generado; las demás no entran.
  if (hermana.origen === "hermana" || hermana.cantidad !== "") {
    return false;
  }
  var primera = null;
  $.each(hermanasDelArbol, function (indice, otra) {
    if (primera === null && otra.origen !== "hermana" && otra.cantidad === "" && String(otra.idEntrada) === String(hermana.idEntrada)) {
      primera = otra;
    }
  });
  return primera === hermana;
}

function actualizarCantidadesPreviasAplicacion() {
  // Antes de calcular, cada receta dice la cantidad escrita o lo que toma vacía.
  var texto = "Todo lo disponible";
  if (cantidadEscritaAplicacion !== "") {
    texto = "Escrita: " + cantidadEscritaAplicacion;
  } else if ($.grep(hermanasDelArbol, function (hermana) { return hermana.origen === "hermana"; }).length > 0) {
    texto = "Lo que sobra";
  }
  $("#arbolAplicacion .principal .cantidadPrevia").text(texto);
  $.each(hermanasDelArbol, function (indice, hermana) {
    var suyo = "Escrita: " + hermana.cantidad;
    if (tomaLoGeneradoAplicacion(hermana)) {
      suyo = "Lo generado";
    } else if (hermana.cantidad === "") {
      suyo = "No entra hasta que le escribas una cantidad, al calcular";
    }
    $('#arbolAplicacion .rama[data-num="' + hermana.numRelacion + '"] .cantidadPrevia').text(suyo);
  });
}

function olvidarCantidadesAplicacion() {
  // Las cantidades escritas valen para el resultado en que se escribieron.
  cantidadEscritaAplicacion = "";
  $.each(hermanasDelArbol, function (indice, hermana) {
    hermana.cantidad = "";
  });
  actualizarCantidadesPreviasAplicacion();
}

function cambiarDatoDelResultadoAplicacion(que) {
  // Cambiada una cantidad o un ajuste, lo calculado ya no es lo que se prepararía: hasta calcular de
  // nuevo no se prepara. El resultado sigue a la vista.
  console.log("Aplicar relación: cambia " + que);
  $("#btnPrepararAplicacion").prop("disabled", true);
}

function hermanasParaLaPeticion() {
  // Lo que se pide de cada hermana: su relación, lo escrito en su rama y sus precios escritos.
  var lista = [];
  $.each(hermanasDelArbol, function (indice, hermana) {
    lista.push({
      numRelacion: hermana.numRelacion,
      cantidad: hermana.cantidad,
      precios: preciosDeHermanas[hermana.numRelacion] || {},
      ajustePeso: hermana.ajustePeso,
      ajusteValor: String(Math.round((2 - parseFloat(hermana.ajustePeso)) * 100) / 100),
    });
  });
  return lista;
}

function pintarRecetasDelArbol(datos) {
  // En el reparto, el campo de la cantidad de cada receta, con lo que entra —si el cálculo no llegó a
  // las recetas, con lo escrito—; en la raíz, lo disponible y lo que queda sin aplicar, y en el producto
  // de cada derivado, lo suyo. Sin nada disponible no hay nada que repartir.
  if (recetaElegidaAplicacion === null || !(datos.disponible > 0)) {
    return;
  }
  var unidad = unidadDeLaEntradaAplicacion(datos);
  var calculadas = {};
  var aplicado = 0;
  $.each(datos.recetas || [], function (indice, receta) {
    calculadas[receta.numRelacion] = receta;
    if (receta.deDerivado === null) {
      aplicado += receta.cantidad;
    }
  });
  $("#arbolAplicacion .cantidadPrevia").empty();
  var valor = cantidadEscritaAplicacion;
  if (datos.cantidad !== undefined) {
    valor = cantidadLegibleAplicacion(datos.cantidad);
  }
  var campo = $("<input>").attr({ type: "text", id: "cantidadAplicacion" }).val(valor)
    .on("input", function () {
      cantidadEscritaAplicacion = $(this).val();
      cambiarDatoDelResultadoAplicacion("la cantidad de la receta elegida");
    });
  $("#arbolAplicacion .principal .resultadoPrincipal").empty().text("Entran ").append(campo).append(document.createTextNode(" " + unidad));
  $.each(hermanasDelArbol, function (indice, hermana) {
    var receta = calculadas[hermana.numRelacion] || null;
    var suyo = hermana.cantidad;
    if (receta !== null) {
      suyo = receta.cantidad > 0 ? cantidadLegibleAplicacion(receta.cantidad) : "";
    }
    var campoDeLaRama = $("<input>").attr("type", "text").addClass("cantidadHermana").val(suyo)
      .attr("placeholder", tomaLoGeneradoAplicacion(hermana) ? "Lo generado" : "No entra")
      .on("input", function () {
        hermana.cantidad = $(this).val();
        cambiarDatoDelResultadoAplicacion("la cantidad de " + hermana.nombre);
      });
    $('#arbolAplicacion .rama[data-num="' + hermana.numRelacion + '"] .resultadoHermana').empty().text("Entran ").append(campoDeLaRama)
      .append(document.createTextNode(" " + (receta !== null ? receta.unidad : unidad)));
  });
  if (!datos.recetas) {
    return;
  }
  $("#arbolAplicacion .raiz .resumen").text("Disponible: " + cantidadLegibleAplicacion(datos.disponible) + " " + unidad
    + " · Sin aplicar: " + cantidadLegibleAplicacion(datos.disponible - aplicado) + " " + unidad);
  // Lo de cada derivado del que parte alguna receta, en su producto: el primero, del que cuelgan.
  $.each(datos.derivados || [], function (indice, derivado) {
    $("<span>").addClass("derivado").text("Genera " + cantidadLegibleAplicacion(derivado.generado) + " " + derivado.unidad
      + " · del periodo " + cantidadLegibleAplicacion(derivado.delPeriodo) + " " + derivado.unidad
      + " · disponible " + cantidadLegibleAplicacion(derivado.disponible) + " " + derivado.unidad
      + " · sin aplicar " + cantidadLegibleAplicacion(derivado.disponible - derivado.aplicado) + " " + derivado.unidad)
      .appendTo($('#arbolAplicacion .producto[data-id="' + derivado.idArticulo + '"]').first().find(".resumen"));
  });
}

function pintarEscandallosDeLasRecetas(datos) {
  // En la zona de los escandallos, después del de la elegida, el de cada receta que entra, en el orden
  // del reparto: su título, sus datos de la operación, su ajuste si tiene dos piezas o más, y sus
  // piezas con sus precios escritos.
  var zona = $("#escandallosDeLasRecetas");
  zona.empty();
  var calculadas = {};
  $.each(datos.recetas || [], function (indice, receta) {
    calculadas[receta.numRelacion] = receta;
  });
  $("#arbolAplicacion .rama").each(function () {
    var receta = calculadas[$(this).attr("data-num")];
    var hermana = buscarHermanaAplicacion($(this).attr("data-num"));
    if (!receta || !receta.escandallo || hermana === null) {
      return;
    }
    var suyo = $("<div>").addClass("escandalloReceta").attr("data-num", receta.numRelacion).appendTo(zona);
    $("<h4>").text("Escandallo de " + receta.nombre).appendTo(suyo);
    $("<h5>").text("Datos de la operación").appendTo(suyo);
    $.each(datosDeLaRecetaAplicacion(receta, datos), function (indice, linea) {
      $("<p>").text(linea).appendTo(suyo);
    });
    if (receta.escandallo.piezas.length > 1) {
      var ajuste = $("<div>").addClass("form-group").appendTo(suyo);
      $("<h5>").text("Peso y valor").appendTo(ajuste);
      var deslizador = $("<div>").text("Por valor de mercado ").appendTo(ajuste);
      var texto = $("<p>").addClass("help-block").text(textoDelAjusteAplicacion(hermana.ajustePeso)).appendTo(ajuste);
      $("<input>").attr({ type: "range", min: 0, max: 2, step: 0.05 }).addClass("ajusteHermana").val(hermana.ajustePeso)
        .css({ display: "inline-block", width: "250px", "vertical-align": "middle" })
        .on("input", function () {
          hermana.ajustePeso = $(this).val();
          texto.text(textoDelAjusteAplicacion(hermana.ajustePeso));
          cambiarDatoDelResultadoAplicacion("el ajuste de " + hermana.nombre);
        })
        .appendTo(deslizador);
      deslizador.append(document.createTextNode(" Por kilos"));
      $("<p>").addClass("text-danger avisoDelAjuste").text(avisoDelAjusteAplicacion(receta.escandallo.piezas)).appendTo(ajuste);
    }
    var tabla = $("<table>").addClass("table table-bordered table-hover").appendTo(suyo);
    var cabecera = $("<tr>").appendTo($("<thead>").appendTo(tabla));
    $.each(["Pieza", "Cantidad", "Unidad", "Precio de mercado", "De dónde sale el precio", "Coste por unidad", "Coste", "Margen por unidad"], function (posicion, titulo) {
      $("<th>").text(titulo).appendTo(cabecera);
    });
    pintarPiezasAplicacion($("<tbody>").appendTo(tabla), receta.escandallo.piezas, function (idArticulo, precio) {
      if (!preciosDeHermanas[receta.numRelacion]) {
        preciosDeHermanas[receta.numRelacion] = {};
      }
      preciosDeHermanas[receta.numRelacion][idArticulo] = precio;
    });
  });
}

function datosDeLaRecetaAplicacion(receta, datos) {
  // Los datos de la operación de una receta que no es la elegida: su versión, lo que tiene disponible
  // su producto —el de partida, para todas sus recetas; un derivado, lo que genera más lo del periodo—,
  // lo que entra, el mínimo, lo despiezado y la merma, y su coste de partida.
  var lineas = ["Versión del despiece: " + receta.version];
  var derivado = null;
  $.each(datos.derivados || [], function (indice, uno) {
    if (String(uno.idArticulo) === String(receta.idEntrada)) {
      derivado = uno;
    }
  });
  if (derivado === null) {
    lineas.push("Disponible de " + receta.entrada + ": " + cantidadLegibleAplicacion(datos.disponible) + " " + receta.unidad + ", para todas sus recetas");
  } else {
    lineas.push("Disponible de " + receta.entrada + ": genera " + cantidadLegibleAplicacion(derivado.generado) + " " + receta.unidad
      + " más " + cantidadLegibleAplicacion(derivado.delPeriodo) + " " + receta.unidad + " del periodo: "
      + cantidadLegibleAplicacion(derivado.disponible) + " " + receta.unidad);
  }
  lineas.push("Entra en la receta: " + cantidadLegibleAplicacion(receta.cantidad) + " " + receta.unidad + ". Lo que no entra queda en las existencias del producto.");
  if (receta.minimo > 0) {
    lineas.push("Mínimo para cubrir las salidas de sus piezas: " + cantidadLegibleAplicacion(receta.minimo) + " " + receta.unidad);
  }
  lineas.push("Despiezado: " + cantidadLegibleAplicacion(receta.escandallo.despiezado) + " " + receta.unidad
    + " · Merma, que queda en sus existencias: " + cantidadLegibleAplicacion(receta.escandallo.merma) + " " + receta.unidad);
  var deDonde = ", el de la ficha del producto";
  if (derivado !== null && derivado.aplicado > derivado.generado) {
    deDonde = ", la media entre lo generado, a su coste del escandallo, y lo de más, al de la ficha del producto";
  } else if (derivado !== null) {
    deDonde = ", el que le da el escandallo del que sale";
  }
  lineas.push("Coste de partida: " + numeroLegibleAplicacion(receta.costeDePartida, 4) + " € por " + receta.unidad + deDonde);
  return lineas;
}

function textoDelAjusteAplicacion(ajustePeso) {
  var peso = parseFloat(ajustePeso);
  return "Peso: " + peso.toFixed(2).replace(".", ",") + " · Valor de mercado: " + (2 - peso).toFixed(2).replace(".", ",")
    + ". Reparte el coste de la receta entre sus piezas: por kilos, el mismo coste por kg para todas; por valor de mercado, en proporción a lo que vale cada una. Cámbialo y pulsa «Calcular».";
}

function avisoDelAjusteAplicacion(piezas) {
  // Las piezas que, con el ajuste calculado, cuestan más que su precio de mercado: suele ser un
  // subproducto que carga coste por sus kilos.
  var caras = [];
  $.each(piezas, function (indice, pieza) {
    if (pieza.origenPrecio !== "sinUso" && pieza.precio > 0 && pieza.costeUnidad > pieza.precio) {
      caras.push("«" + pieza.articulo + "» cuesta " + numeroLegibleAplicacion(pieza.costeUnidad, 4) + " € y su precio de mercado es "
        + numeroLegibleAplicacion(pieza.precio, 4) + " €");
    }
  });
  if (caras.length === 0) {
    return "";
  }
  return "Con este ajuste, " + caras.join("; ") + ": se vendería por debajo de su coste. Si es un subproducto, mueve el ajuste hacia el valor de mercado.";
}

function unidadDeLaEntradaAplicacion(datos) {
  var unidad = "";
  if (datos.version) {
    $.each(datos.version.lineas, function (indice, linea) {
      if (linea.sentido === "entrada") {
        unidad = linea.unidad;
      }
    });
  }
  return unidad;
}

function cambiarPeriodoAplicacion() {
  console.log("Aplicar relación: periodo " + $("#periodoAplicacion").val());
  olvidarCantidadesAplicacion();
  limpiarResultadoAplicacion();
  mostrarTraspasoAplicacion();
}

function cargarPeriodosAplicacion() {
  // Rellena el desplegable del periodo con los meses o los días en que entró el producto.
  var numRelacion = $("#numRelacionAplicacion").val();
  var tipo = $("#tipoPeriodoAplicacion").val();
  peticionesDePeriodosAplicacion++;
  var numeroDePeticion = peticionesDePeriodosAplicacion;
  pintarPeriodosAplicacion([], "Elige primero el despiece");
  if (numRelacion === "") {
    return;
  }
  ajaxAplicarRelacion({ pulsado: "periodosAplicacion", numRelacion: numRelacion, tipoPeriodo: tipo }, function (respuesta) {
    var obj = JSON.parse(respuesta);
    console.log("Aplicar relación: periodos", obj);
    if (numeroDePeticion !== peticionesDePeriodosAplicacion) {
      return;
    }
    if (!obj.ok) {
      pintarAvisosAplicacion(obj.avisos);
    } else if (obj.datos.length === 0) {
      pintarPeriodosAplicacion([], "No hay entradas de este producto");
    } else {
      var primera = "Elige un mes";
      if (tipo === "lote") {
        primera = "Elige un día";
      }
      pintarPeriodosAplicacion(obj.datos, primera);
    }
  });
}

function pintarPeriodosAplicacion(periodos, primera) {
  // Una opción por mes o por día, con los kg que entraron; sin ninguna, el desplegable queda cerrado.
  var lista = $("#periodoAplicacion");
  lista.empty();
  $("<option>").val("").text(primera).appendTo(lista);
  $.each(periodos, function (indice, periodo) {
    $("<option>").val(periodo.valor).text(nombreDePeriodoAplicacion(periodo.valor) + " — entraron " + cantidadLegibleAplicacion(periodo.cantidad) + " " + periodo.unidad).appendTo(lista);
  });
  lista.prop("disabled", periodos.length === 0);
  mostrarTraspasoAplicacion();
}

function nombreDePeriodoAplicacion(valor) {
  // «febrero 2026» para un mes y «03-02-2026» para un día.
  if (valor.length === 7) {
    return mesesAplicacion[parseInt(valor.substr(5, 2), 10) - 1] + " " + valor.substr(0, 4);
  }
  return fechaLegibleAplicacion(valor);
}

function mostrarTraspasoAplicacion() {
  // El proveedor de traspaso solo importa si el periodo incluye el cierre o la apertura del
  // ejercicio: un mes de diciembre o de enero, o el día 31 de diciembre o el 1 de enero. En
  // cualquier otro periodo se oculta y vuelve a «Ninguno».
  var tipo = $("#tipoPeriodoAplicacion").val();
  var valor = $("#periodoAplicacion").val();
  var incluyeElCierre = false;
  if (tipo === "mes") {
    incluyeElCierre = valor.substr(5, 2) === "12" || valor.substr(5, 2) === "01";
  } else {
    incluyeElCierre = valor.substr(5) === "12-31" || valor.substr(5) === "01-01";
  }
  if (incluyeElCierre) {
    $("#bloqueTraspasoAplicacion").show();
  } else {
    $("#bloqueTraspasoAplicacion").hide();
    $("#idProveedorTraspasoAplicacion").val("");
  }
}

function cambiarAjusteAplicacion() {
  // Un solo deslizador fija el peso; el valor es lo que le falta para sumar 2.
  var peso = parseFloat($("#ajustePesoAplicacion").val());
  var valor = parseFloat(ajusteDeValorAplicacion());
  console.log("Aplicar relación: ajuste, peso " + peso + " y valor " + valor);
  cambiarDatoDelResultadoAplicacion("el ajuste de la receta elegida");
  $("#ajustePesoTexto").text(peso.toFixed(2).replace(".", ","));
  $("#ajusteValorTexto").text(valor.toFixed(2).replace(".", ","));
}

function cambiarDatoAplicacion(campo) {
  // Cualquier dato elegido que cambie deja sin valor lo que se había calculado con el anterior, y
  // las cantidades escritas en ese resultado.
  console.log("Aplicar relación: cambia " + campo);
  olvidarCantidadesAplicacion();
  limpiarResultadoAplicacion();
}

function limpiarResultadoAplicacion() {
  $("#arbolAplicacion .resultadoHermana").empty();
  $("#arbolAplicacion .resultadoPrincipal").empty();
  $("#arbolAplicacion .resumen").empty();
  actualizarCantidadesPreviasAplicacion();
  $("#entradasAplicacion").empty();
  $("#avisosAplicacion").empty();
  $("#datosAplicacion").empty();
  $("#zonaDestinoAplicacion").empty();
  $("#tituloEscandalloAplicacion").empty();
  $("#ajusteAplicacion").hide();
  $("#avisoDelAjusteAplicacion").empty();
  $("#escandalloAplicacion tbody").empty();
  $("#escandallosDeLasRecetas").empty();
  $("#btnPrepararAplicacion").prop("disabled", true);
}

function calcularAplicacion() {
  console.log("Aplicar relación: calcular");
  limpiarResultadoAplicacion();
  ajaxAplicarRelacion(peticionDeAplicacion("calcularAplicacion"), function (respuesta) {
    var obj = JSON.parse(respuesta);
    console.log("Aplicar relación: respuesta del cálculo", obj);
    pintarAvisosAplicacion(obj.avisos);
    pintarDatosAplicacion(obj.datos);
    pintarRecetasDelArbol(obj.datos);
    if (obj.ok) {
      if (obj.datos.escandallo) {
        pintarEscandalloAplicacion(obj.datos);
      }
      pintarEscandallosDeLasRecetas(obj.datos);
      pintarDestinosAplicacion(obj.datos.destinos);
      pintarEntradasAplicacion(obj.datos);
      $("#btnPrepararAplicacion").prop("disabled", false);
    }
  });
}

function prepararAlbaranAplicacion() {
  console.log("Aplicar relación: preparar el albarán");
  $("#btnPrepararAplicacion").prop("disabled", true);
  ajaxAplicarRelacion(peticionDeAplicacion("prepararAlbaranAplicacion"), function (respuesta) {
    var obj = JSON.parse(respuesta);
    console.log("Aplicar relación: respuesta de preparar", obj);
    if (obj.ok) {
      if (obj.datos.idTemporales && obj.datos.idTemporales.length > 1) {
        // Un albarán por entrada: se ven todos en el listado de albaranes de compras.
        window.location.href = "../mod_compras/albaranesListado.php";
      } else {
        window.location.href = "../mod_compras/albaran.php?tActual=" + obj.datos.idTemporal;
      }
    } else {
      pintarAvisosAplicacion(obj.avisos);
    }
  });
}

function pintarAvisosAplicacion(avisos) {
  // Cada aviso con el color de su gravedad; el texto, como texto.
  var gravedades = ["success", "info", "warning", "danger"];
  $("#avisosAplicacion").empty();
  $.each(avisos, function (indice, aviso) {
    var tipo = "danger";
    if (gravedades.indexOf(aviso.tipo) !== -1) {
      tipo = aviso.tipo;
    }
    $("<div>").addClass("alert alert-" + tipo).text(aviso.mensaje).appendTo("#avisosAplicacion");
  });
}

function pintarDatosAplicacion(datos) {
  // Los datos de la operación de la receta elegida, en su escandallo, hasta donde se calcularon: fecha,
  // versión, los movimientos, lo disponible, lo que entra en la receta, el mínimo para cubrir las
  // salidas de sus piezas, lo despiezado y la merma, y el coste. Solo informan: la cantidad se cambia
  // en el reparto.
  var lineas = [];
  var unidad = unidadDeLaEntradaAplicacion(datos);
  if (datos.periodo) {
    lineas.push("Fecha del albarán: " + fechaLegibleAplicacion(datos.periodo.fecha));
  }
  if (datos.version) {
    if (datos.cualVersion === "vigente") {
      lineas.push("Versión vigente del despiece: " + datos.version.version + ", desde el " + fechaLegibleAplicacion(datos.version.fechaCreacion));
    } else {
      lineas.push("Versión del despiece que regía en el periodo: " + datos.version.version);
    }
  }
  if (datos.cantidadADespiezar !== undefined) {
    var desde = "";
    if (datos.primeraEntrada) {
      desde = ", desde la primera entrada del " + fechaLegibleAplicacion(datos.primeraEntrada);
    }
    lineas.push("Movimientos del periodo: " + cantidadLegibleAplicacion(datos.cantidadADespiezar) + " " + unidad + desde);
  }
  if (datos.disponible !== undefined) {
    var descontado = "";
    if (datos.mermaAnterior > 0 || datos.enTemporales > 0) {
      descontado = ", descontados " + cantidadLegibleAplicacion(datos.mermaAnterior) + " " + unidad + " de merma de aplicaciones guardadas y "
        + cantidadLegibleAplicacion(datos.enTemporales) + " " + unidad + " que sacan los temporales sin guardar";
    }
    lineas.push("Disponible: " + cantidadLegibleAplicacion(datos.disponible) + " " + unidad + descontado);
  }
  if (datos.cantidad !== undefined) {
    lineas.push("Entra en la receta: " + cantidadLegibleAplicacion(datos.cantidad) + " " + unidad + ". Lo que no entra queda en las existencias del producto.");
  }
  if (datos.desajuste && datos.desajuste.minimo > 0) {
    lineas.push("Mínimo para cubrir las salidas de sus piezas: " + cantidadLegibleAplicacion(datos.desajuste.minimo) + " " + unidad);
  }
  if (datos.escandallo) {
    lineas.push("Despiezado: " + cantidadLegibleAplicacion(datos.escandallo.despiezado) + " " + unidad
      + " · Merma, que queda en sus existencias: " + cantidadLegibleAplicacion(datos.escandallo.merma) + " " + unidad);
  }
  if (datos.costeDePartida) {
    lineas.push("Coste de partida: " + numeroLegibleAplicacion(datos.costeDePartida.coste, 4) + " € por " + unidad
      + ", actualizado el " + fechaLegibleAplicacion(datos.costeDePartida.fecha)
      + ". Si ha cambiado, actualízalo en la ficha del producto antes de preparar.");
  }
  $("#datosAplicacion").empty();
  if (lineas.length > 0) {
    $("#tituloEscandalloAplicacion").text("Escandallo de " + (recetaElegidaAplicacion !== null ? recetaElegidaAplicacion.nombre : "la receta"));
    $("<h5>").text("Datos de la operación").appendTo("#datosAplicacion");
  }
  $.each(lineas, function (indice, texto) {
    $("<p>").text(texto).appendTo("#datosAplicacion");
  });
}

function pintarEntradasAplicacion(datos) {
  // Por entradas, una línea por albarán de entrada del mes: lo que trajo y lo que entra de él, o que
  // ya está aplicado así.
  var zona = $("#entradasAplicacion");
  zona.empty();
  if (!datos.entradas) {
    return;
  }
  var unidad = "";
  $.each(datos.version.lineas, function (indice, linea) {
    if (linea.sentido === "entrada") {
      unidad = linea.unidad;
    }
  });
  $.each(datos.entradas, function (indice, entrada) {
    var texto = "Albarán " + entrada.numero + " del " + fechaLegibleAplicacion(entrada.fecha) + ": trajo " + cantidadLegibleAplicacion(entrada.trajo) + " " + unidad;
    if (entrada.citada) {
      texto += " · ya aplicado por entradas, no se reparte";
    } else {
      texto += " · entran " + cantidadLegibleAplicacion(entrada.parte) + " " + unidad;
    }
    $("<p>").addClass("entrada").text(texto).appendTo(zona);
  });
  console.log("Aplicar relación: reparto por entradas", datos.entradas);
}

function pintarDestinosAplicacion(destinos) {
  // En un mes, con albaranes del mismo proveedor, mes y tipo, se puede elegir a dónde se prepara:
  // un albarán nuevo, por defecto, o uno de ellos. Los nombres, como texto.
  $("#zonaDestinoAplicacion").empty();
  if (!destinos || destinos.length === 0) {
    return;
  }
  $("<label>").attr("for", "destinoAplicacion").text("Preparar en").appendTo("#zonaDestinoAplicacion");
  var lista = $("<select>").attr("id", "destinoAplicacion").addClass("form-control").appendTo("#zonaDestinoAplicacion");
  $("<option>").val("nuevo").text("Albarán nuevo").appendTo(lista);
  $.each(destinos, function (indice, destino) {
    var estado = destino.destino.indexOf("albaran:") === 0 ? "ya guardado" : "abierto";
    var texto = "Albarán " + estado + " de " + destino.proveedor + ", " + fechaLegibleAplicacion(destino.fecha.substring(0, 10)) + ": " + destino.productos.join(", ");
    $("<option>").val(destino.destino).text(texto).appendTo(lista);
  });
  console.log("Aplicar relación: albaranes con los que reunir", destinos);
}

function pintarEscandalloAplicacion(datos) {
  // Las piezas de la receta elegida, el primer escandallo de su zona, y su ajuste si tiene dos piezas
  // o más, con la pieza que cuesta más que su precio.
  $("#ajusteAplicacion").toggle(datos.escandallo.piezas.length > 1);
  $("#avisoDelAjusteAplicacion").text(avisoDelAjusteAplicacion(datos.escandallo.piezas));
  var cuerpo = $("#escandalloAplicacion tbody");
  cuerpo.empty();
  pintarPiezasAplicacion(cuerpo, datos.escandallo.piezas, function (idArticulo, precio) {
    preciosEscritosAplicacion[idArticulo] = precio;
  });
}

function pintarPiezasAplicacion(cuerpo, piezas, alEscribirPrecio) {
  // Una fila por pieza. El precio de mercado se puede escribir: vale en el siguiente cálculo.
  $.each(piezas, function (indice, pieza) {
    var origen = "media de sus proveedores";
    if (pieza.origenPrecio === "escrito") {
      origen = "escrito";
    } else if (pieza.origenPrecio === "sinUso") {
      origen = "no se usa con este ajuste";
    }
    var precio = $("<input>").attr("type", "text").addClass("precio form-control").attr("data-id", pieza.idArticulo)
      .val(numeroLegibleAplicacion(pieza.precio, 4))
      .on("change", function () {
        alEscribirPrecio(pieza.idArticulo, $(this).val());
        console.log("Aplicar relación: precio escrito de " + pieza.idArticulo + " = " + $(this).val());
      });
    $("<tr>").addClass("pieza")
      .append($("<td>").text(pieza.articulo))
      .append($("<td>").text(cantidadLegibleAplicacion(pieza.cantidad)))
      .append($("<td>").text(pieza.unidad))
      .append($("<td>").append(precio))
      .append($("<td>").text(origen))
      .append($("<td>").text(numeroLegibleAplicacion(pieza.costeUnidad, 4)))
      .append($("<td>").text(numeroLegibleAplicacion(pieza.coste, 2)))
      .append(celdaDelMargenAplicacion(pieza))
      .appendTo(cuerpo);
  });
}

function celdaDelMargenAplicacion(pieza) {
  // Lo que queda por unidad entre el precio de mercado y el coste, en rojo si es negativo; sin precio
  // que se use, no hay margen que dar.
  var celda = $("<td>").addClass("margen");
  if (pieza.origenPrecio === "sinUso" || !(pieza.precio > 0)) {
    return celda.text("—");
  }
  var margen = pieza.precio - pieza.costeUnidad;
  return celda.text(numeroLegibleAplicacion(margen, 4)).toggleClass("text-danger", margen < 0);
}

function fechaLegibleAplicacion(fecha) {
  var partes = String(fecha).substr(0, 10).split("-");
  return partes[2] + "-" + partes[1] + "-" + partes[0];
}

function cantidadLegibleAplicacion(numero) {
  return String(Math.round(numero * 1000) / 1000).replace(".", ",");
}

function numeroLegibleAplicacion(numero, decimales) {
  return Number(numero).toFixed(decimales).replace(".", ",");
}
