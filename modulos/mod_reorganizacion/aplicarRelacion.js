/*
 * Aplicar una relación a un periodo: ofrece los meses o los días con entradas del producto,
 * pide el cálculo al servidor, pinta los avisos, los datos —lo disponible y lo que se aplica, con el
 * campo de la cantidad que entra en la receta, que se cambia y se vuelve a calcular— y el escandallo,
 * y prepara el albarán
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

function sangriaDelArbolAplicacion(nivel) {
  return { "margin-left": (nivel * 24) + "px", "border-left": "2px solid #ddd", "padding-left": "6px" };
}

function pintarArbolAplicacion() {
  // El árbol de lo que se aplica, de arriba abajo, alternando productos y recetas: el producto de
  // partida, sus recetas —la elegida y las hermanas añadidas— y, bajo cada receta, los productos que
  // salen de ella, cada uno con las recetas añadidas que parten de él. Cada fila lleva su nivel. Las
  // recetas de un producto que sale por dos caminos cuelgan del primero. Sin recetas añadidas no se pinta.
  var arbol = $("#arbolAplicacion");
  arbol.empty();
  if (hermanasDelArbol.length === 0 || recetaElegidaAplicacion === null) {
    return;
  }
  pintarProductoDelArbolAplicacion(arbol, recetaElegidaAplicacion.idEntrada, recetaElegidaAplicacion.entrada, 0, {});
  actualizarCantidadesPreviasAplicacion();
}

function pintarProductoDelArbolAplicacion(arbol, idArticulo, nombre, nivel, conRecetas) {
  // Una fila de producto y, debajo, las recetas que parten de él, si no se han pintado ya en otro sitio.
  var fila = $("<div>").addClass("producto").attr({ "data-id": idArticulo, "data-nivel": nivel }).css(sangriaDelArbolAplicacion(nivel)).appendTo(arbol);
  if (nivel === 0) {
    fila.addClass("raiz");
    $("<span>").text("Producto de partida: ").appendTo(fila);
  } else {
    $("<span>").addClass("glyphicon glyphicon-stop").css({ "font-size": "8px", "margin-right": "4px", color: "#999" }).appendTo(fila);
  }
  $("<span>").addClass("nombre").text(nombre).appendTo(fila);
  $("<span>").addClass("resumen").appendTo(fila);
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
    $("<span>").text(" · sus recetas van más arriba, donde sale primero").appendTo(fila);
    return;
  }
  conRecetas[idArticulo] = true;
  $.each(recetas, function (indice, receta) {
    pintarRecetaDelArbolAplicacion(arbol, receta, nivel + 1, conRecetas);
  });
}

function pintarRecetaDelArbolAplicacion(arbol, receta, nivel, conRecetas) {
  // Una fila de receta —la elegida, con el ajuste de arriba; una añadida, con su ajuste y «Quitar»— y,
  // debajo, los productos que salen de ella.
  var fila = $("<div>").attr("data-nivel", nivel).css(sangriaDelArbolAplicacion(nivel)).appendTo(arbol);
  $("<span>").addClass("glyphicon glyphicon-share-alt").css({ "margin-right": "4px", color: "#999" }).appendTo(fila);
  $("<strong>").addClass("nombre").text(receta.nombre).appendTo(fila);
  if (receta === recetaElegidaAplicacion) {
    fila.addClass("principal");
    $("<span>").text(" · el ajuste de arriba y el escandallo de abajo").appendTo(fila);
    $("<span>").addClass("cantidadPrevia").appendTo(fila);
    $("<span>").addClass("resumen").appendTo(fila);
  } else {
    fila.addClass("rama well well-sm").attr("data-num", receta.numRelacion).css({ "margin-bottom": "4px", padding: "4px 8px" });
    $("<span>").addClass("cantidadPrevia").appendTo(fila);
    var ajuste = $("<input>").attr({ type: "range", min: 0, max: 2, step: 0.05 }).addClass("ajusteHermana").val(receta.ajustePeso)
      .css({ display: "inline-block", width: "150px", "vertical-align": "middle" })
      .on("input", function () {
        receta.ajustePeso = $(this).val();
        console.log("Aplicar relación: cambia el ajuste de " + receta.nombre);
        limpiarResultadoAplicacion();
      });
    $("<label>").text(" · peso y valor ").css({ "font-weight": "normal", margin: "0 8px 0 0" }).append(ajuste).appendTo(fila);
    $("<button>").attr("type", "button").addClass("btn btn-default btn-xs").text("Quitar")
      .on("click", function () {
        quitarHermanaAplicacion(receta.numRelacion);
      })
      .appendTo(fila);
    $("<div>").addClass("resultadoHermana").appendTo(fila);
  }
  $.each(receta.piezas, function (indice, pieza) {
    pintarProductoDelArbolAplicacion(arbol, pieza.idArticulo, pieza.articulo, nivel + 1, conRecetas);
  });
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
  $("#arbolAplicacion .principal .cantidadPrevia").text(cantidadEscritaAplicacion === ""
    ? " · toma lo que sobra" : " · cantidad escrita: " + cantidadEscritaAplicacion);
  $.each(hermanasDelArbol, function (indice, hermana) {
    var texto = " · cantidad escrita: " + hermana.cantidad;
    if (tomaLoGeneradoAplicacion(hermana)) {
      texto = " · toma lo generado";
    } else if (hermana.cantidad === "") {
      texto = " · no entra hasta que le escribas una cantidad, al calcular";
    }
    $('#arbolAplicacion .rama[data-num="' + hermana.numRelacion + '"] .cantidadPrevia').text(texto);
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

function cambiarCantidadAplicacion(receta) {
  // Cambiada una cantidad, lo calculado ya no es lo que se prepararía: hasta calcular de nuevo no se
  // prepara. El resultado sigue a la vista.
  console.log("Aplicar relación: cambia la cantidad de " + receta);
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
  // En cada rama, lo que entra, en el campo de su cantidad —en la de un derivado, también su coste de
  // partida—, y su escandallo; en la raíz, lo disponible y lo que queda sin aplicar, y en el producto
  // de cada derivado, lo suyo. Si el cálculo no llegó a las recetas, cada campo lleva lo escrito.
  if (hermanasDelArbol.length === 0 || !(datos.disponible > 0)) {
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
  $.each(hermanasDelArbol, function (indice, hermana) {
    var receta = calculadas[hermana.numRelacion] || null;
    var resultado = $('#arbolAplicacion .rama[data-num="' + hermana.numRelacion + '"] .resultadoHermana');
    resultado.empty();
    $('#arbolAplicacion .rama[data-num="' + hermana.numRelacion + '"] .cantidadPrevia').empty();
    var valor = hermana.cantidad;
    if (receta !== null) {
      valor = receta.cantidad > 0 ? cantidadLegibleAplicacion(receta.cantidad) : "";
    }
    var campo = $("<input>").attr("type", "text").addClass("cantidadHermana").val(valor)
      .attr("placeholder", tomaLoGeneradoAplicacion(hermana) ? "Lo generado" : "No entra")
      .on("input", function () {
        hermana.cantidad = $(this).val();
        cambiarCantidadAplicacion(hermana.nombre);
      });
    var linea = $("<p>").text("Entran ").append(campo).append(document.createTextNode(" " + (receta !== null ? receta.unidad : unidad))).appendTo(resultado);
    if (receta === null) {
      return;
    }
    if (receta.deDerivado !== null) {
      linea.append(document.createTextNode(" de " + receta.entrada + ", a " + numeroLegibleAplicacion(receta.costeDePartida, 4) + " € por " + receta.unidad));
    }
    if (receta.escandallo) {
      var tabla = $("<table>").addClass("table table-bordered table-condensed").appendTo(resultado);
      var cabecera = $("<tr>").appendTo($("<thead>").appendTo(tabla));
      $.each(["Pieza", "Cantidad", "Unidad", "Precio de mercado", "De dónde sale el precio", "Coste por unidad", "Coste"], function (posicion, titulo) {
        $("<th>").text(titulo).appendTo(cabecera);
      });
      pintarPiezasAplicacion($("<tbody>").appendTo(tabla), receta.escandallo.piezas, function (idArticulo, precio) {
        if (!preciosDeHermanas[receta.numRelacion]) {
          preciosDeHermanas[receta.numRelacion] = {};
        }
        preciosDeHermanas[receta.numRelacion][idArticulo] = precio;
      });
    }
  });
  if (!datos.recetas) {
    return;
  }
  $("#arbolAplicacion .principal .cantidadPrevia").empty();
  $("#arbolAplicacion .principal .resumen").text(" · entran " + cantidadLegibleAplicacion(datos.cantidad) + " " + unidad);
  $("#arbolAplicacion .raiz .resumen").text(" · Disponible: " + cantidadLegibleAplicacion(datos.disponible) + " " + unidad
    + " · Sin aplicar: " + cantidadLegibleAplicacion(datos.disponible - aplicado) + " " + unidad);
  // Lo de cada derivado del que parte alguna receta, en su producto: el primero, del que cuelgan.
  $.each(datos.derivados || [], function (indice, derivado) {
    $("<span>").addClass("derivado").text(" · genera " + cantidadLegibleAplicacion(derivado.generado) + " " + derivado.unidad
      + " · del periodo " + cantidadLegibleAplicacion(derivado.delPeriodo) + " " + derivado.unidad
      + " · disponible " + cantidadLegibleAplicacion(derivado.disponible) + " " + derivado.unidad
      + " · entran " + cantidadLegibleAplicacion(derivado.aplicado) + " " + derivado.unidad
      + " · sin aplicar " + cantidadLegibleAplicacion(derivado.disponible - derivado.aplicado) + " " + derivado.unidad
      + " · coste de partida " + numeroLegibleAplicacion(derivado.costeDePartida, 4) + " €")
      .appendTo($('#arbolAplicacion .producto[data-id="' + derivado.idArticulo + '"]').first());
  });
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
  limpiarResultadoAplicacion();
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
  $("#arbolAplicacion .resumen").empty();
  $("#arbolAplicacion .derivado").remove();
  actualizarCantidadesPreviasAplicacion();
  $("#entradasAplicacion").empty();
  $("#avisosAplicacion").empty();
  $("#datosAplicacion").empty();
  $("#zonaDestinoAplicacion").empty();
  $("#escandalloAplicacion tbody").empty();
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
        pintarEscandalloAplicacion(obj.datos.escandallo);
      }
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
  // Lo que se calculó hasta donde se pudo: fecha, versión, los movimientos, lo disponible, lo que
  // entra en la receta —en su campo, que se cambia y se vuelve a calcular—, el mínimo para cubrir las
  // salidas de sus piezas y el coste. Si el cálculo no llegó a la cantidad, el campo lleva lo escrito.
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
  var conCampo = lineas.length;
  if (datos.disponible > 0) {
    lineas.push(" " + unidad + ". Lo que no entra queda en las existencias del producto. Cámbiala y pulsa «Calcular».");
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
  $.each(lineas, function (indice, texto) {
    var parrafo = $("<p>").text(texto).appendTo("#datosAplicacion");
    if (indice === conCampo && datos.disponible > 0) {
      var valor = cantidadEscritaAplicacion;
      if (datos.cantidad !== undefined) {
        valor = cantidadLegibleAplicacion(datos.cantidad);
      }
      var campo = $("<input>").attr({ type: "text", id: "cantidadAplicacion" }).val(valor)
        .on("input", function () {
          cantidadEscritaAplicacion = $(this).val();
          cambiarCantidadAplicacion("la receta elegida");
        });
      parrafo.prepend(campo).prepend(document.createTextNode("Entra en la receta: "));
    }
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

function pintarEscandalloAplicacion(escandallo) {
  // El escandallo de la receta elegida, en la tabla de la pantalla.
  var cuerpo = $("#escandalloAplicacion tbody");
  cuerpo.empty();
  pintarPiezasAplicacion(cuerpo, escandallo.piezas, function (idArticulo, precio) {
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
      .appendTo(cuerpo);
  });
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
