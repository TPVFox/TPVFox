/**
 * PosstockLoader.js — Carga de datos POSStock (AJAX batch) + helpers internos
 *
 * Responsabilidades:
 *   - _posstockMostrarError: helper de error visual (compartido con otros módulos)
 *   - _posstockSortComparator: importado desde PosstockTabla
 *   - cargarDatosPosstock: punto de entrada principal (desde PeriodSelector/PHP)
 *   - _posstockCargaLote: paginación recursiva del batch
 *   - _posstockFinalizarTabla: enriquece + renderiza tras el último lote
 *   - _posstockRenderizarTabla / _posstockRenderLote: render HTML por lotes
 *   - _posstockGetCasosIncluir: lee checkboxes visibles de casos
 *   - posstockRecargarPorCasos: relanza con casos actuales
 */

import {
    _posstockSortComparator,
    _posstockResetAgruparProv,
    _posstockIniciarFiltroBadges,
} from "./PosstockTabla.js";
import { _posstockResolverC7cde } from "./PosstockC7cde.js";
import { _posstockEnriquecerC1aConC7b } from "./PosstockEnriquecedor.js";

// ── Helper de error visual ────────────────────────────────────────────────────

/** Muestra un mensaje de error en el área de resultados. */
function _posstockMostrarError(msg) {
    $("#posstockSpinner").hide();
    $("#posstockTablaWrap")
        .html('<div class="alert alert-danger" role="alert">' + msg + "</div>")
        .show();
}

// ── Carga de datos (AJAX batch) ───────────────────────────────────────────────

function cargarDatosPosstock(periodo, tipoIncidencia) {
    $("#posstockSpinner").show();
    $("#posstockTablaWrap").hide();
    $("#posstockNavegacion").hide();
    $("#posstockBotonesWrap").hide();
    $("#posstockProgreso").text("Calculando incidencias…");

    if (window.posstockTipoActivo === "anual") {
        $("#posstockFilaCasos").hide();
    }

    _posstockCargaLote(0, [], periodo, tipoIncidencia || "");
}

/**
 * Carga incidencias en lotes de forma recursiva.
 * Cuando termina el último lote, envía los datos acumulados a PHP para renderizar.
 */
function _posstockCargaLote(inicial, acumuladas, periodo, tipoIncidencia) {
    var parametros = {
        pulsado: "getPOSStockBatch",
        fecha_inicio_movimientos: periodo.fecha_inicio_movimientos,
        fecha_fin_movimientos: periodo.fecha_fin_movimientos,
        fecha_inicio_stock: periodo.fecha_inicio_stock,
        fecha_fin_stock: periodo.fecha_fin_stock,
        casos_incluir: _posstockGetCasosIncluir(tipoIncidencia),
        tipo_periodo: window.posstockTipoActivo || "",
        min_ventas_c5: (periodo && periodo.min_ventas_c5) || 3,
        inicial: inicial,
        pagina: 250,
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
        proveedores_incluir: (window.posstockProveedoresIncluir || [])
            .map(function (p) {
                return p.id;
            })
            .join(","),
        proveedor_todos_productos: window.posstockProveedorTodosProductos
            ? "1"
            : "0",
    };

    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                _posstockMostrarError(resultado.error);
                return;
            }

            var acum = acumuladas.concat(resultado.filas || []);
            var actual = resultado.actual;
            var elementos = resultado.elementos;
            var paginaEfectiva = resultado.pagina_efectiva || parametros.pagina;

            if (paginaEfectiva > 0 && elementos >= paginaEfectiva) {
                $("#posstockProgreso").text(
                    "Analizando artículos: " + actual + " procesados…",
                );
                _posstockCargaLote(actual, acum, periodo, tipoIncidencia);
            } else {
                acum.sort(_posstockSortComparator);
                var idsC7a = [],
                    idsC7b = [];
                acum.forEach(function (f) {
                    if (f.c7_subcaso === "C7a") idsC7a.push(f.idArticulo);
                    if (f.c7_subcaso === "C7b") idsC7b.push(f.idArticulo);
                });
                if (idsC7a.length > 0 && idsC7b.length > 0) {
                    $("#posstockProgreso").text("Analizando cruces C7…");
                    _posstockResolverC7cde(
                        idsC7a,
                        idsC7b,
                        acum,
                        parametros,
                        periodo,
                    );
                } else {
                    _posstockFinalizarTabla(acum, periodo);
                }
            }
        },
        error: function () {
            _posstockMostrarError("Error de comunicación con el servidor.");
        },
    });
}

/**
 * Enriquece las filas acumuladas y renderiza la tabla. Punto de entrada común
 * tanto si hay cruces C7c/d/e como si no los hay.
 */
function _posstockFinalizarTabla(acum, periodo) {
    acum.sort(_posstockSortComparator);
    _posstockEnriquecerC1aConC7b(acum);
    window._posstockUltimasFilas = acum;
    window._posstockUltimoPeriodo = periodo;
    $("#posstockProgreso").text("Generando tabla…");
    _posstockRenderizarTabla(acum, periodo);

    $("#posstockLabelMovimientos").text(periodo.label_movimientos || "");
    $("#posstockLabelStock").text(periodo.label_stock || "");
    $("#posstockNavegacion").show();
    $("#posstockBotonesWrap").show();
}

/** Tamaño de lote para el renderizado de la tabla (filas por petición HTTP). */
var POSSTOCK_RENDER_BATCH = 150;

/**
 * Llama a PHP para renderizar la tabla HTML en lotes de ≤150 filas.
 */
function _posstockRenderizarTabla(filas, periodo) {
    _posstockRenderLote(filas, periodo, 0);
}

function _posstockRenderLote(filas, periodo, offset) {
    var batch = filas.slice(offset, offset + POSSTOCK_RENDER_BATCH);
    var esPrimerLote = offset === 0;

    $.ajax({
        data: {
            pulsado: "renderizarTablaPosstock",
            filas_json: JSON.stringify(batch),
            modo: esPrimerLote ? "completo" : "filas",
            fecha_fin_movimientos: periodo.fecha_fin_movimientos || "",
            fecha_inicio_stock: periodo.fecha_inicio_stock || "",
            anio: window.posstockAnioActivo || new Date().getFullYear(),
            c3b_dias_post: window.POSSTOCK_C3B_DIAS_POST || 14,
            c6b_dias_historico: window.POSSTOCK_C6B_DIAS_HISTORICO || 90,
            mostrar_tecnico: window.POSSTOCK_MOSTRAR_TECNICO ? 1 : 0,
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                _posstockMostrarError(resultado.error);
                return;
            }
            if (esPrimerLote) {
                $("#posstockTablaWrap").html(resultado.html).show();
            } else {
                $("#posstockTabla tbody").append(resultado.html);
            }

            var nextOffset = offset + batch.length;
            if (nextOffset < filas.length) {
                $("#posstockProgreso").text(
                    "Preparando tabla: " +
                        nextOffset +
                        " / " +
                        filas.length +
                        "…",
                );
                _posstockRenderLote(filas, periodo, nextOffset);
            } else {
                // Último lote: tabla completa en DOM
                $("#posstockSpinner").hide();
                _posstockResetAgruparProv();
                if (filas.length > 0) {
                    $("#posstockBtnExportar, #posstockBtnImprimir").show();
                    _posstockIniciarFiltroBadges();
                } else {
                    $("#posstockBtnExportar, #posstockBtnImprimir").hide();
                }
            }
        },
        error: function () {
            _posstockMostrarError(
                "Error al renderizar la tabla de incidencias.",
            );
        },
    });
}

// ── Filtro de tipos de incidencia (checkboxes) ────────────────────────────────

/**
 * Devuelve el valor de casos_incluir para enviar al backend.
 * Lee los checkboxes renderizados por PHP en #posstockFilaCasos.
 */
function _posstockGetCasosIncluir(tipoIncidenciaAnual) {
    if (tipoIncidenciaAnual) return tipoIncidenciaAnual;
    var seleccionados = [];
    document
        .querySelectorAll("#posstockFilaCasos input[type=checkbox]")
        .forEach(function (chk) {
            if (chk.checked) seleccionados.push(chk.value);
        });
    if (seleccionados.length === 0) return "";
    // C7b-003: si caso1 está seleccionado, incluir siempre caso7b para poder
    // enriquecer las filas C1a con el badge, aunque el usuario no haya marcado caso7b.
    if (
        seleccionados.indexOf("caso1") !== -1 &&
        seleccionados.indexOf("caso7b") === -1
    ) {
        seleccionados.push("caso7b");
    }
    return seleccionados.join(",");
}

/** Relanza la consulta con los casos actualmente seleccionados. */
function posstockRecargarPorCasos() {
    if (!window.posstockPeriodoActivo) return;
    cargarDatosPosstock(
        window.posstockPeriodoActivo,
        window.posstockTipoIncidenciaActivo || "",
    );
}

window.cargarDatosPosstock = cargarDatosPosstock;
window.posstockRecargarPorCasos = posstockRecargarPorCasos;

export {
    _posstockMostrarError,
    cargarDatosPosstock,
    _posstockCargaLote,
    _posstockFinalizarTabla,
    POSSTOCK_RENDER_BATCH,
    _posstockRenderizarTabla,
    _posstockRenderLote,
    _posstockGetCasosIncluir,
    posstockRecargarPorCasos,
};
