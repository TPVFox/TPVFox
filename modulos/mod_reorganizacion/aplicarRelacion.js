/*
 * Aplicar una relación a un periodo: ofrece los meses o los días con entradas del producto,
 * pide el cálculo al servidor con la cantidad que entra en la receta, pinta los avisos, los datos
 * —lo disponible y lo que se aplica— y el escandallo, y prepara el albarán
 * de compras, que se abre en su pantalla para revisarlo y guardarlo. El cálculo y el aviso de
 * proceso duplicado los hace siempre el servidor, también al preparar: aquí solo se recoge lo
 * elegido y se pinta lo que llega.
 */

// Los precios de mercado escritos en la tabla, por artículo, hasta que se cambie de relación.
var preciosEscritosAplicacion = {};

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
    cantidad: $("#cantidadAplicacion").val(),
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
  limpiarResultadoAplicacion();
  cargarPeriodosAplicacion();
}

function cambiarRelacionAplicacion() {
  // Los precios escritos son de las piezas de una relación: con otra, se olvidan.
  console.log("Aplicar relación: otra relación, " + $("#numRelacionAplicacion").val());
  preciosEscritosAplicacion = {};
  limpiarResultadoAplicacion();
  cargarPeriodosAplicacion();
}

function cambiarPeriodoAplicacion() {
  console.log("Aplicar relación: periodo " + $("#periodoAplicacion").val());
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
  // Cualquier dato elegido que cambie deja sin valor lo que se había calculado con el anterior.
  console.log("Aplicar relación: cambia " + campo);
  limpiarResultadoAplicacion();
}

function limpiarResultadoAplicacion() {
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
    if (obj.ok) {
      pintarEscandalloAplicacion(obj.datos.escandallo);
      pintarDestinosAplicacion(obj.datos.destinos);
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
      window.location.href = "../mod_compras/albaran.php?tActual=" + obj.datos.idTemporal;
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
  // entra en la receta, el mínimo para cubrir las salidas de sus piezas y el coste.
  var lineas = [];
  var unidad = "";
  if (datos.version) {
    $.each(datos.version.lineas, function (indice, linea) {
      if (linea.sentido === "entrada") {
        unidad = linea.unidad;
      }
    });
  }
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
  $.each(lineas, function (indice, texto) {
    $("<p>").text(texto).appendTo("#datosAplicacion");
  });
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
  // Una fila por pieza. El precio de mercado se puede escribir: vale en el siguiente cálculo.
  var cuerpo = $("#escandalloAplicacion tbody");
  cuerpo.empty();
  $.each(escandallo.piezas, function (indice, pieza) {
    var origen = "media de sus proveedores";
    if (pieza.origenPrecio === "escrito") {
      origen = "escrito";
    } else if (pieza.origenPrecio === "sinUso") {
      origen = "no se usa con este ajuste";
    }
    var precio = $("<input>").attr("type", "text").addClass("precio form-control").attr("data-id", pieza.idArticulo)
      .val(numeroLegibleAplicacion(pieza.precio, 4))
      .on("change", function () {
        preciosEscritosAplicacion[pieza.idArticulo] = $(this).val();
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
