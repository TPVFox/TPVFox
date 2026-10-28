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

// El árbol de las recetas que se aplican a la vez: la relación de la que sale, las recetas que
// ofrece el servidor —hermanas y de los derivados—, las añadidas —con lo escrito en su rama— y los
// precios escritos en el escandallo de cada una, por relación y artículo.
var relacionDelArbol = "";
var hermanasOfrecidas = [];
var hermanasDelArbol = [];
var preciosDeHermanas = {};

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
      salidas: hermana.salidas, origen: hermana.origen, cantidad: "", ajustePeso: "1" });
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

function textoDeLaRaizAplicacion() {
  var entrada = $("#numRelacionAplicacion option:selected").text();
  $.each(hermanasOfrecidas, function (indice, receta) {
    if (receta.origen === "hermana") {
      entrada = receta.entrada;
    }
  });
  return "Producto de partida: " + entrada;
}

function profundidadEnElArbolAplicacion(receta) {
  // Cuántos niveles cuelga una rama: una hermana, uno; una receta de un producto de la elegida,
  // dos; la de un derivado, uno más que la receta de la que sale su producto.
  if (receta.origen === "hermana") {
    return 1;
  }
  if (receta.origen === "elegida") {
    return 2;
  }
  var profundidad = 2;
  $.each(hermanasDelArbol, function (indice, anadida) {
    if (anadida !== receta && $.inArray(receta.idEntrada, anadida.salidas) !== -1) {
      profundidad = profundidadEnElArbolAplicacion(anadida) + 1;
    }
  });
  return profundidad;
}

function pintarArbolAplicacion() {
  // El árbol de lo que se aplica: el producto de partida, la receta elegida —con la cantidad y el
  // ajuste de arriba— y una rama por receta añadida, colgada a su nivel, con su cantidad, su ajuste y
  // «Quitar». Con una sola receta no se pinta.
  var arbol = $("#arbolAplicacion");
  arbol.empty();
  if (hermanasDelArbol.length === 0) {
    return;
  }
  $("<div>").addClass("raiz").text(textoDeLaRaizAplicacion()).appendTo(arbol);
  $("<div>").addClass("principal").text("└ " + $("#numRelacionAplicacion option:selected").text() + ": la cantidad y el ajuste de arriba; vacía, toma lo que sobra").appendTo(arbol);
  $.each(hermanasDelArbol, function (indice, hermana) {
    var rama = $("<div>").addClass("rama well well-sm").attr("data-num", hermana.numRelacion)
      .css("margin-left", (profundidadEnElArbolAplicacion(hermana) * 20) + "px").appendTo(arbol);
    var titulo = "└ " + hermana.nombre + " ";
    var vacia = "No entra";
    if (hermana.origen !== "hermana") {
      titulo = "└ " + hermana.nombre + " (de " + hermana.entrada + ") ";
      vacia = "Lo generado";
    }
    $("<strong>").text(titulo).appendTo(rama);
    var cantidad = $("<input>").attr("type", "text").addClass("cantidadHermana").attr("placeholder", vacia).val(hermana.cantidad)
      .on("change", function () {
        hermana.cantidad = $(this).val();
        cambiarDatoAplicacion("la cantidad de " + hermana.nombre);
      });
    $("<label>").text(" Cantidad ").append(cantidad).appendTo(rama);
    var ajuste = $("<input>").attr({ type: "range", min: 0, max: 2, step: 0.05 }).addClass("ajusteHermana").val(hermana.ajustePeso)
      .on("input", function () {
        hermana.ajustePeso = $(this).val();
        cambiarDatoAplicacion("el ajuste de " + hermana.nombre);
      });
    $("<label>").text(" Peso y valor ").append(ajuste).appendTo(rama);
    $("<button>").attr("type", "button").addClass("btn btn-default btn-xs").text("Quitar")
      .on("click", function () {
        quitarHermanaAplicacion(hermana.numRelacion);
      })
      .appendTo(rama);
    $("<div>").addClass("resultadoHermana").appendTo(rama);
  });
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
  // Lo que entra en cada rama, con su escandallo —en la de un derivado, también su coste de partida—,
  // en la raíz lo disponible y lo que queda sin aplicar, y lo de cada derivado.
  if (!datos.recetas || hermanasDelArbol.length === 0) {
    return;
  }
  var unidad = "";
  $.each(datos.version.lineas, function (indice, linea) {
    if (linea.sentido === "entrada") {
      unidad = linea.unidad;
    }
  });
  var aplicado = 0;
  $.each(datos.recetas, function (indice, receta) {
    if (receta.deDerivado === null) {
      aplicado += receta.cantidad;
    }
    if (indice === 0) {
      return;
    }
    var resultado = $('#arbolAplicacion .rama[data-num="' + receta.numRelacion + '"] .resultadoHermana');
    resultado.empty();
    var entran = "Entran " + cantidadLegibleAplicacion(receta.cantidad) + " " + receta.unidad;
    if (receta.deDerivado !== null) {
      entran += " de " + receta.entrada + ", a " + numeroLegibleAplicacion(receta.costeDePartida, 4) + " € por " + receta.unidad;
    }
    $("<p>").text(entran).appendTo(resultado);
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
  $("#arbolAplicacion .raiz").text(textoDeLaRaizAplicacion() + " · Disponible: " + cantidadLegibleAplicacion(datos.disponible) + " " + unidad
    + " · Sin aplicar: " + cantidadLegibleAplicacion(datos.disponible - aplicado) + " " + unidad);
  // Lo de cada derivado del que parte alguna receta: lo generado, lo del periodo y su coste.
  $.each(datos.derivados || [], function (indice, derivado) {
    $("<div>").addClass("derivado").text(derivado.articulo + ": genera " + cantidadLegibleAplicacion(derivado.generado) + " " + derivado.unidad
      + " · del periodo " + cantidadLegibleAplicacion(derivado.delPeriodo) + " " + derivado.unidad
      + " · disponible " + cantidadLegibleAplicacion(derivado.disponible) + " " + derivado.unidad
      + " · entran " + cantidadLegibleAplicacion(derivado.aplicado) + " " + derivado.unidad
      + " · sin aplicar " + cantidadLegibleAplicacion(derivado.disponible - derivado.aplicado) + " " + derivado.unidad
      + " · coste de partida " + numeroLegibleAplicacion(derivado.costeDePartida, 4) + " €")
      .insertAfter("#arbolAplicacion .raiz");
  });
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
  $("#arbolAplicacion .resultadoHermana").empty();
  $("#arbolAplicacion .derivado").remove();
  $("#entradasAplicacion").empty();
  $("#arbolAplicacion .raiz").text(textoDeLaRaizAplicacion());
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
      if (obj.datos.escandallo) {
        pintarEscandalloAplicacion(obj.datos.escandallo);
      }
      pintarRecetasDelArbol(obj.datos);
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
