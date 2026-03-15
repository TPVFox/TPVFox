import * as JSTpv from "./../../lib/js/tpvfox.js";
function metodoClick() {
    checkID = JSTpv.TfObtenerCheck("rowCheck");
    console.log(checkID);
    if (checkID.length > 1 || checkID.length === 0) {
        alert(
            "Que items tienes seleccionados? \n Solo puedes tener uno seleccionado",
        );
        return;
    }
    // Ahora obtenemos el valor de la opcion seleccionada.
    let opcion = document.getElementById("opcion" + checkID[0]).value;
    // Ahora montamos rango fecha
    let fecha_inicial = document.getElementById("idFechaInicio").value;
    let fecha_final = document.getElementById("idFechaFinal").value;
    if (fecha_final == "") {
        // Si la fecha inicio esta vacia
        let today = new Date();
        let day = today.getDate();
        let month = today.getMonth() + 1;
        let year = today.getFullYear();
        fecha_final = year + "-" + month + "-" + day;
    }
    if (fecha_inicial == "") {
        // Si la fecha inicio esta vacia
        let today = new Date();

        let year = today.getFullYear();
        fecha_inicial = year + "-01-01";
    }
    if (fecha_inicial > fecha_final) {
        alert(
            "Error:\n Fecha inicial no puede ser posterior a la fecha final\n o la fecha Final esta vacia",
        );
        return;
    }
    AbrirModalLoading(fecha_inicial, fecha_final, opcion);
}
function AbrirModalLoading(fecha_inicial, fecha_final, opcion) {
    var parametros = {
        pulsado: "obtenerLoading",
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log("******** Obteniendo Loading****************");
        },
        success: function (response) {
            var resultado = $.parseJSON(response);

            // Depuración: mostrar resumen del lote recibido
            try {
                console.debug("[POSStock] lote recibido", {
                    offset: inicial,
                    elementos: resultado.elementos,
                    filas_mostradas: (resultado.filas || [])
                        .slice(0, 5)
                        .map(function (f) {
                            return {
                                id: f.idArticulo,
                                orden: f.orden_clave,
                                tipo: f.tipo,
                                sev: f.severidad,
                            };
                        }),
                });
            } catch (e) {
                console.debug("[POSStock] depuracion lote fallo", e);
            }
            abrirModal("Procesando", resultado.html); // Abre una ventana y muestra el texto
            // Ahora montamos link y redirecionamos
            setTimeout(function () {
                window.open(
                    "./informes.php?id=" +
                        checkID[0] +
                        "&Finicio=" +
                        fecha_inicial +
                        "&Ffinal=" +
                        fecha_final +
                        "&opcion=" +
                        opcion,
                    "_blank",
                );
            }, 5000);
        },
        error: function (request) {
            console.log(request);
        },
    });
}
window.metodoClick = metodoClick;

// =====================================================================
//       POSSTOCK — Configuración
// =====================================================================

function abrirModalConfigPosstock() {
    var parametros = { pulsado: "abrirModalConfigPosstock" };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = $.parseJSON(response);
            abrirModal(resultado.titulo, resultado.html);
        },
    });
}

function guardarConfigPosstock() {
    var datosFormulario = $("#formConfigPosstock").serializeArray();
    var parametros = {
        pulsado: "guardarConfigPosstock",
        datosFormulario: JSON.stringify(datosFormulario),
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = $.parseJSON(response);
            if (resultado.error) {
                var htmlError =
                    '<div class="alert alert-danger" role="alert">' +
                    resultado.error +
                    "</div>";
                $("#formConfigPosstock").prepend(htmlError);
            } else {
                cerrarPopUp();
                // Actualizar ventana_dias en JS para que la barra y las restricciones
                // reflejen el nuevo valor sin necesidad de recargar la página.
                if (resultado.ventana_dias !== undefined) {
                    window.POSSTOCK_VENTANA_DIAS = resultado.ventana_dias;
                    // Si hay una barra de botones visible, re-pintarla con el nuevo umbral.
                    if (
                        window.posstockTipoActivo &&
                        window.posstockPeriodoActivo
                    ) {
                        var numeroActivo =
                            parseInt(
                                document.getElementById("posstockNumero").value,
                                10,
                            ) || 1;
                        posstockPintarBarra(
                            window.posstockTipoActivo,
                            numeroActivo,
                            window.posstockPeriodoActivo.total_periodos,
                        );
                    }
                }
                alert(resultado.mensaje);
            }
        },
    });
}

window.abrirModalConfigPosstock = abrirModalConfigPosstock;
window.guardarConfigPosstock = guardarConfigPosstock;

// =====================================================================
//       POSSTOCK — Filtro de familias
// =====================================================================

// Globals: dos arrays independientes de idFamilia
window.posstockFamiliasIncluir = []; // si no está vacío: solo estas familias
window.posstockFamiliasExcluir = []; // si no está vacío: excluir estas familias

var _posstockFamiliasCache = null; // caché de la lista completa

/**
 * Abre el modal de dos tablas (consultar / excluir).
 * Carga familias vía AJAX la primera vez; reutiliza caché en sucesivas.
 */
function posstockAbrirFiltroFamilias() {
    if (_posstockFamiliasCache) {
        _posstockMostrarModalFamilias();
        return;
    }
    $.ajax({
        data: { pulsado: "getFamiliasPosstock" },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = $.parseJSON(response);
            if (resultado.error) {
                alert(resultado.error);
                return;
            }
            _posstockFamiliasCache = resultado.familias;
            _posstockMostrarModalFamilias();
        },
        error: function () {
            alert("Error al cargar familias.");
        },
    });
}

/** Genera y abre el modal con las dos tablas usando los globals actuales. */
function _posstockMostrarModalFamilias() {
    var html =
        _htmlPanelFamilia(
            "incluir",
            "Familias a consultar",
            "Si hay familias aquí, solo se analizarán estas (y sus subfamilias).",
            "panel-success",
            "btn-success",
            window.posstockFamiliasIncluir,
        ) +
        _htmlPanelFamilia(
            "excluir",
            "Familias excluidas",
            "Estas familias (y sus subfamilias) serán excluidas del análisis.",
            "panel-danger",
            "btn-danger",
            window.posstockFamiliasExcluir,
        ) +
        '<div style="margin-top:8px;text-align:right;">' +
        '<button type="button" class="btn btn-primary btn-sm"' +
        ' onclick="posstockAplicarFiltroFamilias()">Aplicar filtro</button>' +
        "</div>";

    abrirModal("Filtrar familias — POSStock", html);

    // Poblar el datalist compartido
    var dl = document.getElementById("posstockFamiliasDatalist");
    if (dl && dl.options.length === 0) {
        _posstockFamiliasCache.forEach(function (f) {
            var opt = document.createElement("option");
            opt.value = f.ruta;
            opt.dataset.id = f.id;
            dl.appendChild(opt);
        });
    }
}

/** Genera el HTML de un panel (incluir o excluir) con su tabla y buscador. */
function _htmlPanelFamilia(
    lista,
    titulo,
    descripcion,
    panelCls,
    btnCls,
    idsActivos,
) {
    var filas = "";
    idsActivos.forEach(function (item) {
        filas += _htmlFilaFamilia(item.id, item.nombre);
    });

    return (
        '<div class="panel ' +
        panelCls +
        '" style="margin-bottom:10px;">' +
        '<div class="panel-heading small"><strong>' +
        titulo +
        "</strong></div>" +
        '<div class="panel-body" style="padding:8px;">' +
        '<p class="text-muted small" style="margin:0 0 6px;">' +
        descripcion +
        "</p>" +
        '<div style="max-height:160px;overflow-y:auto;border:1px solid #ddd;">' +
        '<table class="table table-condensed table-hover" style="margin:0;"' +
        ' id="posstockTabla_' +
        lista +
        '">' +
        '<thead><tr><th class="small">ID</th><th class="small">Familia</th><th></th></tr></thead>' +
        "<tbody>" +
        filas +
        "</tbody>" +
        "</table></div>" +
        '<div class="input-group" style="margin-top:6px;">' +
        '<input type="text" class="form-control input-sm" list="posstockFamiliasDatalist"' +
        ' id="posstockBuscar_' +
        lista +
        '" placeholder="Buscar familia…">' +
        '<span class="input-group-btn">' +
        '<button type="button" class="btn btn-sm ' +
        btnCls +
        '"' +
        " onclick=\"posstockAgregarFamilia('" +
        lista +
        "')\">" +
        '<i class="glyphicon glyphicon-plus"></i> Agregar</button>' +
        "</span></div>" +
        "</div></div>" +
        '<datalist id="posstockFamiliasDatalist"></datalist>'
    );
}

/** Genera una fila de tabla para una familia en una lista. */
function _htmlFilaFamilia(id, nombre) {
    return (
        '<tr data-id="' +
        id +
        '">' +
        "<td>" +
        id +
        "</td>" +
        "<td>" +
        nombre +
        "</td>" +
        '<td><button type="button" class="btn btn-xs btn-link text-danger"' +
        ' onclick="posstockEliminarFamilia(this)">' +
        '<i class="glyphicon glyphicon-trash"></i></button></td>' +
        "</tr>"
    );
}

/**
 * Busca la familia escrita en el input, la añade a la tabla correspondiente
 * si existe en el catálogo y no está duplicada.
 */
function posstockAgregarFamilia(lista) {
    var input = document.getElementById("posstockBuscar_" + lista);
    var texto = input.value.trim();
    if (!texto) return;

    // Buscar en el cache por ruta exacta o por nombre parcial
    var encontrada = null;
    _posstockFamiliasCache.forEach(function (f) {
        if (!encontrada && (f.ruta === texto || f.nombre.trim() === texto)) {
            encontrada = f;
        }
    });
    if (!encontrada) {
        alert(
            "Familia no encontrada. Escribe el nombre exacto de la ruta que aparece en la lista.",
        );
        return;
    }

    // Verificar duplicado en la tabla
    var tabla = document.querySelector("#posstockTabla_" + lista + " tbody");
    if (tabla.querySelector('tr[data-id="' + encontrada.id + '"]')) {
        alert("Esa familia ya está en la lista.");
        input.value = "";
        return;
    }

    tabla.insertAdjacentHTML(
        "beforeend",
        _htmlFilaFamilia(encontrada.id, encontrada.nombre.trim()),
    );
    input.value = "";
}

/** Elimina una fila de la tabla de una lista. */
function posstockEliminarFamilia(boton) {
    boton.closest("tr").remove();
}

/**
 * Lee las dos tablas del modal, actualiza los globals y el badge,
 * y cierra el modal.
 */
function posstockAplicarFiltroFamilias() {
    window.posstockFamiliasIncluir = _leerTablaFamilias("incluir");
    window.posstockFamiliasExcluir = _leerTablaFamilias("excluir");
    _posstockActualizarBadgeFiltro();
    cerrarPopUp();
}

/** Lee las filas de una tabla y devuelve array de {id, nombre}. */
function _leerTablaFamilias(lista) {
    var filas = document.querySelectorAll(
        "#posstockTabla_" + lista + " tbody tr",
    );
    var resultado = [];
    filas.forEach(function (tr) {
        resultado.push({
            id: parseInt(tr.dataset.id, 10),
            nombre: tr.cells[1].textContent.trim(),
        });
    });
    return resultado;
}

/** Actualiza el badge del botón con el resumen activo. */
function _posstockActualizarBadgeFiltro() {
    var badge = document.getElementById("posstockFiltroLabel");
    if (!badge) return;
    var nInc = (window.posstockFamiliasIncluir || []).length;
    var nExc = (window.posstockFamiliasExcluir || []).length;
    if (nInc === 0 && nExc === 0) {
        badge.textContent = "Todas";
        badge.className = "label label-default";
    } else {
        var partes = [];
        if (nInc > 0) partes.push("Consultar: " + nInc);
        if (nExc > 0) partes.push("Excluir: " + nExc);
        badge.textContent = partes.join(" · ");
        badge.className = "label label-warning";
    }
}

window.posstockAbrirFiltroFamilias = posstockAbrirFiltroFamilias;
window.posstockAgregarFamilia = posstockAgregarFamilia;
window.posstockEliminarFamilia = posstockEliminarFamilia;
window.posstockAplicarFiltroFamilias = posstockAplicarFiltroFamilias;

// =====================================================================
//       POSSTOCK — Carga de datos e incidencias
// =====================================================================

/**
 * Llama al endpoint getPOSStockData con las fechas del periodo actual,
 * pinta la tabla de incidencias y muestra la barra de botones de navegación.
 *
 * @param {Object} periodo  Objeto con las 4 fechas y los labels:
 *   { fecha_inicio_movimientos, fecha_fin_movimientos,
 *     fecha_inicio_stock, fecha_fin_stock,
 *     label_movimientos, label_stock }
 */
function cargarDatosPosstock(periodo, tipoIncidencia) {
    // Spinner visible, tabla y barra ocultas mientras se carga
    $("#posstockSpinner").show();
    $("#posstockTablaWrap").hide();
    $("#posstockNavegacion").hide();
    $("#posstockBotonesWrap").hide();
    $("#posstockProgreso").text("Calculando incidencias…");

    _posstockCargaLote(0, [], periodo, tipoIncidencia || "");
}

/**
 * Carga las incidencias en lotes siguiendo el patrón de mod_reorganizacion.
 * Se llama recursivamente hasta que actual >= total.
 *
 * @param {number} inicial       Offset del lote actual
 * @param {Array}  acumuladas    Incidencias acumuladas de lotes anteriores
 * @param {Object} periodo       Objeto con las 4 fechas y los labels
 * @param {string} tipoIncidencia
 */
function _posstockCargaLote(inicial, acumuladas, periodo, tipoIncidencia) {
    var parametros = {
        pulsado: "getPOSStockBatch",
        fecha_inicio_movimientos: periodo.fecha_inicio_movimientos,
        fecha_fin_movimientos: periodo.fecha_fin_movimientos,
        fecha_inicio_stock: periodo.fecha_inicio_stock,
        fecha_fin_stock: periodo.fecha_fin_stock,
        casos_incluir: _posstockGetCasosIncluir(tipoIncidencia),
        inicial: inicial,
        pagina: 150,
        familias_incluir: (window.posstockFamiliasIncluir || [])
            .map(function (f) {
                return f.id;
            })
            .join(","),
        familias_excluir: (window.posstockFamiliasExcluir || [])
            .map(function (f) {
                return f.id;
            })
            .join(","),
    };

    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = $.parseJSON(response);

            if (resultado.error) {
                $("#posstockSpinner").hide();
                $("#posstockTablaWrap")
                    .html(
                        '<div class="alert alert-danger">' +
                            resultado.error +
                            "</div>",
                    )
                    .show();
                return;
            }

            var acum = acumuladas.concat(resultado.filas || []);
            try {
                console.debug("[POSStock] tras concat — muestra primer lote", {
                    offset: inicial,
                    muestras: (resultado.filas || [])
                        .slice(0, 6)
                        .map(function (f) {
                            return {
                                id: f.idArticulo,
                                orden: f.orden_clave,
                                tipo: f.tipo,
                                sev: f.severidad,
                            };
                        }),
                });
                console.debug(
                    "[POSStock] acum longitud",
                    acum.length,
                    "primeros",
                    acum.slice(0, 8).map(function (f) {
                        return { id: f.idArticulo, orden: f.orden_clave };
                    }),
                );
            } catch (e) {
                console.debug("[POSStock] depuracion concat fallo", e);
            }
            // Mantener `acum` ordenado entre lotes para evitar mezclas al concatenar.
            acum.sort(function (a, b) {
                if (a && b && a.orden_clave && b.orden_clave) {
                    if (a.orden_clave < b.orden_clave) return -1;
                    if (a.orden_clave > b.orden_clave) return 1;
                    return (a.idArticulo || 0) - (b.idArticulo || 0);
                }
                var ordenSev = { CRITICA: 1, ALTA: 2, MEDIA: 3, BAJA: 4 };
                var sa = ordenSev[a.severidad] || 9;
                var sb = ordenSev[b.severidad] || 9;
                if (sa !== sb) return sa - sb;

                // Dentro de MEDIA: C2 -> C5 -> C3a
                if (sa === 3) {
                    var mapMedia = {
                        "Entrada con stock alto": 0,
                        "Venta Cero (Posible Rotura Física)": 1,
                        "Riesgo de caducidad teórica": 2,
                    };
                    var ta = mapMedia[a.tipo] ?? 99;
                    var tb = mapMedia[b.tipo] ?? 99;
                    if (ta !== tb) return ta - tb;
                }

                // Dentro de BAJA: C3b con ultima_salida (0) -> C3b sin ultima (1) -> C4 (2)
                if (sa === 4) {
                    function subTipo(x) {
                        if (x.tipo === "Entrada sin rotación previa") {
                            return x.ultima_salida && x.ultima_salida !== null
                                ? 0
                                : 1;
                        }
                        if (x.tipo === "Stock Inactivo en Periodo") return 2;
                        return 99;
                    }
                    var sra = subTipo(a),
                        srb = subTipo(b);
                    if (sra !== srb) return sra - srb;
                }

                // Desempate final por idArticulo
                return (a.idArticulo || 0) - (b.idArticulo || 0);
            });
            var actual = resultado.actual;
            var elementos = resultado.elementos;

            // Continuar mientras el lote devuelva exactamente $pagina artículos.
            // Cuando devuelva menos (o 0) es el último lote.
            if (elementos >= 150) {
                $("#posstockProgreso").text(
                    "Analizando artículos: " + actual + " procesados…",
                );
                _posstockCargaLote(actual, acum, periodo, tipoIncidencia);
            } else {
                // Último lote: ordenar globalmente y pintar.
                // Preferimos la clave lexicográfica `orden_clave` proporcionada por el
                // backend; si no existe, usamos un fallback por severidad/tipo/id.
                acum.sort(function (a, b) {
                    if (a && b && a.orden_clave && b.orden_clave) {
                        if (a.orden_clave < b.orden_clave) return -1;
                        if (a.orden_clave > b.orden_clave) return 1;
                        return (a.idArticulo || 0) - (b.idArticulo || 0);
                    }

                    var ordenSev = { CRITICA: 1, ALTA: 2, MEDIA: 3, BAJA: 4 };
                    var sa = ordenSev[a.severidad] || 9;
                    var sb = ordenSev[b.severidad] || 9;
                    if (sa !== sb) return sa - sb;

                    // Dentro de MEDIA: C2 -> C5 -> C3a
                    if (sa === 3) {
                        var mapMedia = {
                            "Entrada con stock alto": 0,
                            "Venta Cero (Posible Rotura Física)": 1,
                            "Riesgo de caducidad teórica": 2,
                        };
                        var ta = mapMedia[a.tipo] ?? 99;
                        var tb = mapMedia[b.tipo] ?? 99;
                        if (ta !== tb) return ta - tb;
                    }

                    // Dentro de BAJA: C3b con ultima_salida (0) -> C3b sin ultima (1) -> C4 (2)
                    if (sa === 4) {
                        function subTipo(x) {
                            if (x.tipo === "Entrada sin rotación previa") {
                                return x.ultima_salida &&
                                    x.ultima_salida !== null
                                    ? 0
                                    : 1;
                            }
                            if (x.tipo === "Stock Inactivo en Periodo")
                                return 2;
                            return 99;
                        }
                        var sra = subTipo(a),
                            srb = subTipo(b);
                        if (sra !== srb) return sra - srb;
                    }

                    // Desempate final por idArticulo
                    return (a.idArticulo || 0) - (b.idArticulo || 0);
                });

                try {
                    console.debug(
                        "[POSStock] orden final antes de pintar — primeros 20",
                        acum.slice(0, 20).map(function (f) {
                            return {
                                id: f.idArticulo,
                                orden: f.orden_clave,
                                tipo: f.tipo,
                                sev: f.severidad,
                            };
                        }),
                    );
                } catch (e) {
                    console.debug("[POSStock] depuracion orden final fallo", e);
                }

                $("#posstockSpinner").hide();

                // Actualizar labels de periodo
                $("#posstockLabelMovimientos").text(
                    periodo.label_movimientos || "",
                );
                $("#posstockLabelStock").text(periodo.label_stock || "");

                pintarTablaIncidencias(acum);

                $("#posstockNavegacion").show();
                $("#posstockBotonesWrap").show();
                $("#posstockTablaWrap").show();

                if (acum.length > 0) {
                    $("#posstockBtnExportar, #posstockBtnImprimir").show();
                } else {
                    $("#posstockBtnExportar, #posstockBtnImprimir").hide();
                }
            }
        },
        error: function (request) {
            $("#posstockSpinner").hide();
            $("#posstockTablaWrap")
                .html(
                    '<div class="alert alert-danger">Error de comunicación con el servidor.</div>',
                )
                .show();
            console.error("getPOSStockBatch error", request);
        },
    });
}

/**
 * Genera el HTML de la tabla de incidencias y lo inyecta en #posstockTablaWrap.
 *
 * @param {Array} filas  Array de incidencias devuelto por getIncidencias().
 */
function pintarTablaIncidencias(filas) {
    var badgeSev = {
        CRITICA: '<span class="label label-danger">Crítica</span>',
        ALTA: '<span class="label label-danger" style="background-color:#e8600a;">Alta</span>',
        MEDIA: '<span class="label label-warning">Media</span>',
        BAJA: '<span class="label label-info">Baja</span>',
    };

    if (!filas || filas.length === 0) {
        $("#posstockTablaWrap").html(
            '<div class="alert alert-success">Sin incidencias detectadas para este periodo.</div>',
        );
        return;
    }

    // Asegurar orden consistente antes de renderizar (fallback si backend no envía orden_clave)
    filas.sort(function (a, b) {
        if (a && b && a.orden_clave && b.orden_clave) {
            if (a.orden_clave < b.orden_clave) return -1;
            if (a.orden_clave > b.orden_clave) return 1;
            return (a.idArticulo || 0) - (b.idArticulo || 0);
        }
        var ordenSev = { CRITICA: 1, ALTA: 2, MEDIA: 3, BAJA: 4 };
        var sa = ordenSev[a.severidad] || 9;
        var sb = ordenSev[b.severidad] || 9;
        if (sa !== sb) return sa - sb;
        if (sa === 3) {
            var mapMedia = {
                "Entrada con stock alto": 0,
                "Venta Cero (Posible Rotura Física)": 1,
                "Riesgo de caducidad teórica": 2,
            };
            var ta = mapMedia[a.tipo] ?? 99;
            var tb = mapMedia[b.tipo] ?? 99;
            if (ta !== tb) return ta - tb;
        }
        if (sa === 4) {
            function subTipo(x) {
                if (x.tipo === "Entrada sin rotación previa") {
                    return x.ultima_salida && x.ultima_salida !== null ? 0 : 1;
                }
                if (x.tipo === "Stock Inactivo en Periodo") return 2;
                return 99;
            }
            var sra = subTipo(a),
                srb = subTipo(b);
            if (sra !== srb) return sra - srb;
        }
        return (a.idArticulo || 0) - (b.idArticulo || 0);
    });

    var periodo = window.posstockPeriodoActivo || {};
    var anio = window.posstockAnioActivo || new Date().getFullYear();
    var ffMov = periodo.fecha_fin_movimientos || "";
    var fiInicio = anio + "-01-01";

    var html =
        '<table class="table table-condensed table-hover table-bordered small" id="posstockTabla">';
    html +=
        "<thead><tr>" +
        "<th>Artículo</th>" +
        "<th>Nombre</th>" +
        "<th>Tipo incidencia</th>" +
        "<th>Severidad</th>" +
        "<th>Detalle</th>" +
        "<th>Posible causa</th>" +
        "<th>Listado mayor</th>" +
        "</tr></thead><tbody>";

    filas.forEach(function (f) {
        var detalle = "";
        if (f.tipo === "Stock Negativo") {
            detalle =
                "Stock actual: <strong>" +
                (f.stock_actual !== undefined
                    ? parseFloat(f.stock_actual).toFixed(2)
                    : "—") +
                "</strong>" +
                (f.min_balance !== undefined
                    ? " | Mín. intra-periodo: " +
                      parseFloat(f.min_balance).toFixed(2)
                    : "");
        } else if (f.tipo === "Desajuste Puntual de Stock") {
            detalle =
                "Stock final: " +
                (f.stock_actual !== undefined
                    ? parseFloat(f.stock_actual).toFixed(2)
                    : "—") +
                " | Mín. intra-periodo: <strong>" +
                (f.min_balance !== undefined
                    ? parseFloat(f.min_balance).toFixed(2)
                    : "—") +
                "</strong>";
        } else if (f.tipo === "Entrada con stock alto") {
            detalle =
                "Stock previo: " +
                parseFloat(f.stock_previo).toFixed(2) +
                " | Entrada: " +
                parseFloat(f.ncant).toFixed(2) +
                " | Fecha: " +
                (f.fecha || "—");
        } else if (f.tipo === "Riesgo de caducidad teórica") {
            detalle =
                "Últ. venta: " +
                (f.ultima_venta || "—") +
                " | " +
                (f.semanas_desde_ultima_venta || "—") +
                " sem.";
        } else if (f.tipo === "Venta Cero (Posible Rotura Física)") {
            var estadoRotura = f.fecha_fin_rotura
                ? "Recuperada " + f.fecha_fin_rotura
                : '<span class="label label-danger">En curso</span>';
            detalle =
                "Últ. venta: " +
                (f.ultima_venta || "—") +
                " | Rotura desde: <strong>" +
                (f.fecha_inicio_rotura || "—") +
                "</strong>" +
                " | " +
                estadoRotura +
                " | " +
                (f.dias_rotura !== undefined ? f.dias_rotura + " d" : "—") +
                " | μ: " +
                (f.avg_dias_entre_ventas !== undefined
                    ? f.avg_dias_entre_ventas + " d"
                    : "—") +
                " σ: " +
                (f.sd_dias !== undefined ? f.sd_dias + " d" : "—") +
                " (umbral " +
                (f.umbral_dias !== undefined ? f.umbral_dias + " d" : "—") +
                ")";
        } else if (f.tipo === "Entrada sin rotación previa") {
            detalle = f.ultima_salida
                ? "Últ. salida: " +
                  f.ultima_salida +
                  " | " +
                  (f.semanas_desde_ultima_salida || "—") +
                  " sem."
                : "Sin salidas registradas";
        } else if (f.tipo === "Stock Inactivo en Periodo") {
            detalle =
                "Stock en periodo: " +
                (f.stock_actual !== undefined
                    ? parseFloat(f.stock_actual).toFixed(2)
                    : "—");
        }

        var urlMayor =
            "../../modulos/mod_producto/DetalleMayor.php" +
            "?idArticulo=" +
            f.idArticulo +
            "&fecha_inicial=" +
            fiInicio +
            "&fecha_final=" +
            ffMov;

        html +=
            "<tr data-tipo='" +
            f.tipo.replace(/'/g, "&#39;") +
            "'>" +
            "<td>" +
            f.idArticulo +
            "</td>" +
            "<td>" +
            (f.nombre || "—") +
            "</td>" +
            "<td>" +
            f.tipo +
            "</td>" +
            "<td>" +
            (badgeSev[f.severidad] || f.severidad) +
            "</td>" +
            "<td>" +
            detalle +
            "</td>" +
            "<td class='text-muted'>" +
            (f.posible_causa || "") +
            "</td>" +
            "<td><a href='" +
            urlMayor +
            "' target='_blank'>" +
            "<i class='glyphicon glyphicon-list-alt'></i> Ver mayor" +
            "</a></td>" +
            "</tr>";
    });

    html += "</tbody></table>";
    $("#posstockTablaWrap").html(html);
}

/**
 * Descarga las incidencias del periodo activo como fichero CSV.
 * Usa un formulario oculto para forzar la descarga del fichero (AJAX no puede
 * disparar una descarga de navegador directamente).
 */
function exportarPOSStockCSV() {
    var periodo = window.posstockPeriodoActivo;
    if (!periodo) return;

    var params = {
        pulsado: "exportarPOSStockCSV",
        fecha_inicio_movimientos: periodo.fecha_inicio_movimientos,
        fecha_fin_movimientos: periodo.fecha_fin_movimientos,
        fecha_inicio_stock: periodo.fecha_inicio_stock,
        fecha_fin_stock: periodo.fecha_fin_stock,
        casos_incluir: _posstockGetCasosIncluir(window.posstockTipoIncidenciaActivo || ""),
        familias_incluir: (window.posstockFamiliasIncluir || [])
            .map(function (f) {
                return f.id;
            })
            .join(","),
        familias_excluir: (window.posstockFamiliasExcluir || [])
            .map(function (f) {
                return f.id;
            })
            .join(","),
    };

    var form = document.createElement("form");
    form.method = "POST";
    form.action = "tareas.php";
    Object.keys(params).forEach(function (key) {
        var input = document.createElement("input");
        input.type = "hidden";
        input.name = key;
        input.value = params[key];
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

/**
 * Genera el PDF de incidencias del periodo activo con TCPDF (servidor)
 * y lo abre en una nueva pestaña.
 */
function imprimirPOSStockPDF() {
    var periodo = window.posstockPeriodoActivo;
    if (!periodo) return;

    var btn = document.getElementById("posstockBtnImprimir");
    if (btn) {
        btn.disabled = true;
        btn.textContent = "Generando PDF…";
    }

    $.ajax({
        data: {
            pulsado: "imprimirPOSStockPDF",
            fecha_inicio_movimientos: periodo.fecha_inicio_movimientos,
            fecha_fin_movimientos: periodo.fecha_fin_movimientos,
            fecha_inicio_stock: periodo.fecha_inicio_stock,
            fecha_fin_stock: periodo.fecha_fin_stock,
            casos_incluir: _posstockGetCasosIncluir(window.posstockTipoIncidenciaActivo || ""),
            familias_incluir: (window.posstockFamiliasIncluir || [])
                .map(function (f) {
                    return f.id;
                })
                .join(","),
            familias_excluir: (window.posstockFamiliasExcluir || [])
                .map(function (f) {
                    return f.id;
                })
                .join(","),
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = $.parseJSON(response);
            if (resultado.error) {
                alert("Error al generar PDF: " + resultado.error);
            } else {
                window.open(resultado.url, "_blank");
            }
        },
        error: function () {
            alert("Error de comunicación al generar el PDF.");
        },
        complete: function () {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML =
                    '<i class="glyphicon glyphicon-print"></i> Imprimir PDF';
            }
        },
    });
}

window.cargarDatosPosstock = cargarDatosPosstock;
window.pintarTablaIncidencias = pintarTablaIncidencias;
window.exportarPOSStockCSV = exportarPOSStockCSV;
window.imprimirPOSStockPDF = imprimirPOSStockPDF;

// =====================================================================
//       POSSTOCK — Selectores de periodo y barra de navegación
// =====================================================================

// Tipos de incidencia disponibles para el filtro anual.
// caso4 se añade dinámicamente en tiempo de ejecución si POSSTOCK_INCLUIR_STOCK_INACTIVO === true.
var POSSTOCK_TIPOS_INCIDENCIA = [
    { v: "caso1", t: "Stock Negativo / Desajuste", short: "C1" },
    { v: "caso2", t: "Entrada con stock alto", short: "C2" },
    { v: "caso3a", t: "Riesgo de caducidad teórica", short: "C3a" },
    { v: "caso3b", t: "Entrada sin rotación previa", short: "C3b" },
    { v: "caso5", t: "Venta Cero (Rotura física)", short: "C5" },
];

/** Devuelve los tipos de incidencia aplicables incluyendo caso4 si está habilitado. */
function _posstockTiposActivos() {
    var tipos = POSSTOCK_TIPOS_INCIDENCIA.slice();
    if (window.POSSTOCK_INCLUIR_STOCK_INACTIVO) {
        tipos.push({ v: "caso4", t: "Stock Inactivo en Periodo", short: "C4" });
    }
    return tipos;
}

/**
 * Devuelve el valor de casos_incluir para enviar al backend.
 *
 * - Vista anual: tipoIncidenciaAnual es el caso concreto (string); se devuelve tal cual.
 * - Resto: lee los checkboxes marcados y devuelve una cadena separada por comas.
 *   Si no hay checkboxes (aún no inicializados), devuelve "" (todos los casos activos).
 */
function _posstockGetCasosIncluir(tipoIncidenciaAnual) {
    if (tipoIncidenciaAnual) return tipoIncidenciaAnual;
    var seleccionados = [];
    _posstockTiposActivos().forEach(function (ti) {
        var chk = document.getElementById("posstockChk_" + ti.v);
        if (!chk || chk.checked) seleccionados.push(ti.v);
    });
    return seleccionados.join(",");
}

/**
 * Renderiza los checkboxes de tipo de incidencia en #posstockChecksCasos
 * y muestra la fila. Solo se llama en vistas no anuales.
 * Los checks se inicializan todos marcados.
 */
function _posstockInicializarFiltroCasos() {
    var wrap = document.getElementById("posstockChecksCasos");
    if (!wrap) return;
    var tipos = _posstockTiposActivos();
    var html = "";
    tipos.forEach(function (ti) {
        html +=
            '<label class="checkbox-inline" style="margin-left:8px; font-weight:normal;">' +
            '<input type="checkbox" id="posstockChk_' +
            ti.v +
            '" value="' +
            ti.v +
            '" ' +
            'checked onchange="posstockRecargarPorCasos()"> ' +
            '<span class="label label-default">' +
            ti.short +
            "</span> " +
            ti.t +
            "</label>";
    });
    wrap.innerHTML = html;
    document.getElementById("posstockFilaCasos").style.display = "";
}

/**
 * Relanza la consulta al backend con los casos actualmente seleccionados.
 * Se llama desde onchange de los checkboxes de tipo de incidencia.
 */
function posstockRecargarPorCasos() {
    if (!window.posstockPeriodoActivo) return;
    cargarDatosPosstock(
        window.posstockPeriodoActivo,
        window.posstockTipoIncidenciaActivo || ""
    );
}
window.posstockRecargarPorCasos = posstockRecargarPorCasos;

var MESES = [
    "Ene",
    "Feb",
    "Mar",
    "Abr",
    "May",
    "Jun",
    "Jul",
    "Ago",
    "Sep",
    "Oct",
    "Nov",
    "Dic",
];

/**
 * Rellena el selector de número de periodo según el tipo elegido.
 * También habilita/deshabilita el botón Generar.
 */
function posstockActualizarNumero() {
    var tipo = document.getElementById("posstockTipo").value;
    var anio = parseInt(document.getElementById("posstockAnio").value, 10);
    var sel = document.getElementById("posstockNumero");
    sel.innerHTML = "";
    sel.disabled = true;
    document.getElementById("posstockBtnGenerar").disabled = true;
    document.getElementById("posstockAvisoVentana").style.display = "none";

    // Cambiar label según contexto
    var labelEl = document.getElementById("posstockLabelNumero");
    if (labelEl)
        labelEl.textContent = tipo === "anual" ? "Tipo de análisis" : "Periodo";

    if (!tipo || !anio) return;

    var opciones = [];

    if (tipo === "semana") {
        // Semanas ancladas al 01-Ene (misma lógica que PHP).
        var totalSem = _posstockTotalSemanas(anio);
        for (var s = 1; s <= totalSem; s++)
            opciones.push({ v: s, t: "Semana " + s });
    } else if (tipo === "quincena") {
        for (var q = 1; q <= 24; q++) {
            var mes = Math.ceil(q / 2);
            var mitad = q % 2 === 1 ? "1ª" : "2ª";
            opciones.push({ v: q, t: mitad + " quincena " + MESES[mes - 1] });
        }
    } else if (tipo === "mes") {
        for (var m = 1; m <= 12; m++)
            opciones.push({ v: m, t: MESES[m - 1] + " " + anio });
    } else if (tipo === "trimestre") {
        opciones = [
            { v: 1, t: "T1 (Ene–Mar)" },
            { v: 2, t: "T2 (Abr–Jun)" },
            { v: 3, t: "T3 (Jul–Sep)" },
            { v: 4, t: "T4 (Oct–Dic)" },
        ];
    } else if (tipo === "cuatrimestre") {
        opciones = [
            { v: 1, t: "C1 (Ene–Abr)" },
            { v: 2, t: "C2 (May–Ago)" },
            { v: 3, t: "C3 (Sep–Dic)" },
        ];
    } else if (tipo === "semestre") {
        opciones = [
            { v: 1, t: "1er semestre (Ene–Jun)" },
            { v: 2, t: "2º semestre (Jul–Dic)" },
        ];
    } else if (tipo === "anual") {
        // Para anual el "número" de periodo es el tipo de incidencia a analizar
        opciones = _posstockTiposActivos().map(function (ti) {
            return { v: ti.v, t: ti.t };
        });
    }

    sel.innerHTML = '<option value="">— seleccionar —</option>';
    opciones.forEach(function (o) {
        sel.innerHTML += '<option value="' + o.v + '">' + o.t + "</option>";
    });
    sel.disabled = false;
}

/**
 * Devuelve el Date correspondiente al fin de la semana 1 del año
 * (semana 1 = 01-Ene → primer domingo del año).
 * Si el 01-Ene ya es domingo, la semana 1 termina el propio 01-Ene.
 */
function _posstockFinSem1(anio) {
    var jan1 = new Date(anio, 0, 1);
    var dow = jan1.getDay(); // 0=dom, 1=lun … 6=sab
    if (dow === 0) return jan1;
    return new Date(anio, 0, 1 + (7 - dow));
}

/**
 * Calcula el número total de semanas del año con el sistema anclado al 01-Ene.
 * Semana 1 = 01-Ene → primer domingo. Semanas 2+ = lun→dom.
 */
function _posstockTotalSemanas(anio) {
    var finSem1 = _posstockFinSem1(anio);
    var dec31 = new Date(anio, 11, 31);
    var diasRest = Math.round((dec31 - finSem1) / 86400000);
    return 1 + Math.ceil(diasRest / 7);
}

/**
 * Llama a calcularPeriodoPosstock (AJAX), comprueba la ventana de consolidación
 * y si es válido llama a cargarDatosPosstock() y pinta la barra de botones.
 */
function posstockGenerar() {
    var tipo = document.getElementById("posstockTipo").value;
    var numero = document.getElementById("posstockNumero").value;
    var anio = document.getElementById("posstockAnio").value;

    if (!tipo || !numero || !anio) return;

    $.ajax({
        data: {
            pulsado: "calcularPeriodoPosstock",
            tipo: tipo,
            numero: numero,
            anio: anio,
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var periodo = $.parseJSON(response);
            if (periodo.error) {
                alert("Error al calcular periodo: " + periodo.error);
                return;
            }

            // ── Restricción ventana_dias ──────────────────────────────
            var hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            var limite = new Date(hoy);
            limite.setDate(
                limite.getDate() - (window.POSSTOCK_VENTANA_DIAS || 7),
            );
            var ffMov = new Date(periodo.fecha_fin_movimientos);

            if (ffMov >= limite) {
                document.getElementById("posstockAvisoVentana").style.display =
                    "";
                return;
            }
            document.getElementById("posstockAvisoVentana").style.display =
                "none";

            // Guardar periodo activo y cargar datos
            window.posstockPeriodoActivo = periodo;
            window.posstockTipoActivo = tipo;
            window.posstockAnioActivo = parseInt(anio, 10);

            // Para anual: numero es el tipo de incidencia (string); resto: entero
            var tipoInc = tipo === "anual" ? numero : "";
            window.posstockTipoIncidenciaActivo = tipoInc;

            cargarDatosPosstock(periodo, tipoInc);
            posstockPintarBarra(
                tipo,
                tipo === "anual" ? numero : parseInt(numero, 10),
                periodo.total_periodos,
            );
        },
        error: function () {
            alert("Error de comunicación al calcular el periodo.");
        },
    });
}

/**
 * Pinta la barra de botones numerados bajo los selectores.
 * El botón del periodo activo queda resaltado (btn-primary).
 */
/**
 * Calcula la fecha_fin_movimientos de un periodo directamente en JS,
 * sin AJAX, para poder marcar botones fuera de ventana.
 */
function posstockFechaFinPeriodo(tipo, n, anio) {
    if (tipo === "mes") {
        return new Date(anio, n, 0); // día 0 del mes siguiente = último día del mes n
    }
    if (tipo === "trimestre") {
        return new Date(anio, n * 3, 0); // último día del mes 3n
    }
    if (tipo === "cuatrimestre") {
        return new Date(anio, n * 4, 0); // último día del mes 4n
    }
    if (tipo === "semestre") {
        return new Date(anio, n * 6, 0); // último día del mes 6n
    }
    if (tipo === "anual") {
        return new Date(anio, 11, 31);
    }
    if (tipo === "quincena") {
        var mes = Math.ceil(n / 2);
        if (n % 2 === 1) {
            return new Date(anio, mes - 1, 15); // 1ª quincena → día 15
        } else {
            return new Date(anio, mes, 0); // 2ª quincena → último día del mes
        }
    }
    if (tipo === "semana") {
        // Semanas ancladas al 01-Ene (igual que PHP)
        var finSem1 = _posstockFinSem1(anio);
        if (n === 1) return finSem1;
        var dec31 = new Date(anio, 11, 31);
        var inicio = new Date(finSem1);
        inicio.setDate(finSem1.getDate() + (n - 1) * 7 - 6);
        var fin = new Date(inicio);
        fin.setDate(inicio.getDate() + 6);
        return fin > dec31 ? dec31 : fin;
    }
    return null;
}

function posstockPintarBarra(tipo, numeroActivo, totalPeriodos) {
    var wrap = document.getElementById("posstockBotonesPeriodo");
    wrap.innerHTML = "";

    // Para anual: botones por tipo de incidencia (no por número de periodo)
    if (tipo === "anual") {
        _posstockTiposActivos().forEach(function (ti) {
            var cls =
                ti.v === numeroActivo
                    ? "btn btn-primary btn-xs"
                    : "btn btn-default btn-xs";
            wrap.innerHTML +=
                '<button type="button" class="' +
                cls +
                '" ' +
                "onclick=\"posstockNavegar('" +
                ti.v +
                "')\" " +
                'id="posstockBtn_' +
                ti.v +
                '">' +
                ti.short +
                " " +
                ti.t +
                "</button> ";
        });
        document.getElementById("posstockBarraBotones").style.display = "";
        return;
    }

    var hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    var limite = new Date(hoy);
    limite.setDate(limite.getDate() - (window.POSSTOCK_VENTANA_DIAS || 7));
    var anio = window.posstockAnioActivo || new Date().getFullYear();

    for (var n = 1; n <= totalPeriodos; n++) {
        var etiqueta = posstockEtiquetaBoton(tipo, n);
        var ffPeriodo = posstockFechaFinPeriodo(tipo, n, anio);
        var enVentana = ffPeriodo && ffPeriodo >= limite;

        var cls, extras;
        if (enVentana) {
            cls = "btn btn-default btn-xs disabled";
            extras =
                'disabled title="Dentro de la ventana de consolidación (' +
                (window.POSSTOCK_VENTANA_DIAS || 7) +
                ' días)"';
        } else if (n === numeroActivo) {
            cls = "btn btn-primary btn-xs";
            extras = "";
        } else {
            cls = "btn btn-default btn-xs";
            extras = "";
        }

        wrap.innerHTML +=
            '<button type="button" class="' +
            cls +
            '" ' +
            extras +
            " " +
            'onclick="posstockNavegar(' +
            n +
            ')" ' +
            'id="posstockBtn_' +
            n +
            '">' +
            etiqueta +
            "</button> ";
    }

    document.getElementById("posstockBarraBotones").style.display = "";
}

/** Devuelve la etiqueta de un botón de la barra según tipo y número. */
function posstockEtiquetaBoton(tipo, n) {
    if (tipo === "semana") return "S" + n;
    if (tipo === "mes") return MESES[n - 1];
    if (tipo === "trimestre") return "T" + n;
    if (tipo === "cuatrimestre") return "C" + n;
    if (tipo === "semestre") return n === 1 ? "1S" : "2S";
    if (tipo === "anual") return "Año";
    if (tipo === "quincena") {
        var mes = Math.ceil(n / 2);
        var mitad = n % 2 === 1 ? "a" : "b";
        return MESES[mes - 1] + mitad;
    }
    return n;
}

/**
 * Navega a otro periodo de la barra sin necesidad de pulsar "Generar".
 * Actualiza el resaltado del botón activo.
 */
function posstockNavegar(numero) {
    var tipo = window.posstockTipoActivo;
    var anio = window.posstockAnioActivo;
    if (!tipo || !anio) return;

    // Para anual: numero es el tipo de incidencia (string), no hay ventana de consolidación
    if (tipo === "anual") {
        window.posstockTipoIncidenciaActivo = numero;
        var botonesActuales = document.querySelectorAll("[id^='posstockBtn_']");
        botonesActuales.forEach(function (b) {
            b.className = "btn btn-default btn-xs";
        });
        var btnActivo = document.getElementById("posstockBtn_" + numero);
        if (btnActivo) btnActivo.className = "btn btn-primary btn-xs";
        cargarDatosPosstock(window.posstockPeriodoActivo, numero);
        return;
    }

    // Ignorar botones dentro de la ventana de consolidación
    var btn = document.getElementById("posstockBtn_" + numero);
    if (btn && btn.disabled) return;

    // Actualizar resaltado de botones (solo los no desactivados)
    var botonesActuales = document.querySelectorAll("[id^='posstockBtn_']");
    botonesActuales.forEach(function (b) {
        if (!b.disabled) b.className = "btn btn-default btn-xs";
    });
    var btnActivo = document.getElementById("posstockBtn_" + numero);
    if (btnActivo) btnActivo.className = "btn btn-primary btn-xs";

    $.ajax({
        data: {
            pulsado: "calcularPeriodoPosstock",
            tipo: tipo,
            numero: numero,
            anio: anio,
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var periodo = $.parseJSON(response);
            if (periodo.error) {
                alert(periodo.error);
                return;
            }
            window.posstockPeriodoActivo = periodo;
            cargarDatosPosstock(periodo);
        },
    });
}

// Habilitar botón Generar cuando se elige un número de periodo
// e inicializar los checkboxes de casos en la carga de página.
document.addEventListener("DOMContentLoaded", function () {
    document
        .getElementById("posstockNumero")
        .addEventListener("change", function () {
            document.getElementById("posstockBtnGenerar").disabled =
                this.value === "";
        });

    _posstockInicializarFiltroCasos();
});

window.posstockActualizarNumero = posstockActualizarNumero;
window.posstockGenerar = posstockGenerar;
window.posstockNavegar = posstockNavegar;
