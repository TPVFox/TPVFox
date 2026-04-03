/**
 * PosstockEnriquecedor.js — Enriquecimiento de filas C1a con datos C7b/C7a
 *
 * Responsabilidades:
 *   - Decorar filas C1a con c7b_activo/c7b_offset_estimado cuando existe
 *     una fila C7b del mismo artículo en el lote acumulado.
 *   - Enriquecer filas C2 con datos de merma acumulada de C7a.
 *   - Filtrar filas C7b/C7a del array si el usuario no tiene ese caso activo.
 */

/**
 * C7b-003: decora las filas C1a con c7b_activo/c7b_offset_estimado/c7b_tipo_articulo
 * cuando existe una fila C7b del mismo artículo en el lote acumulado.
 */
function _posstockEnriquecerC1aConC7b(filas) {
    var c7bPorArticulo = {};
    filas.forEach(function (f) {
        if (f.c7_subcaso === "C7b") {
            c7bPorArticulo[f.idArticulo] = {
                offset_estimado: f.offset_estimado,
                tipo_articulo: f.tipo_articulo || "unidad",
            };
        }
    });
    // C7a-007: enriquecer filas C2 ("Entrada con stock alto") con datos de merma acumulada
    var c7aPorArticulo = {};
    filas.forEach(function (f) {
        if (f.c7_subcaso === "C7a" || f.c7_subcaso === "C7a_posible") {
            c7aPorArticulo[f.idArticulo] = {
                delta_acumulado: f.delta_acumulado,
                tipo_articulo: f.tipo_articulo || "unidad",
            };
        }
    });
    if (Object.keys(c7aPorArticulo).length > 0) {
        filas.forEach(function (f) {
            if (
                f.tipo === "Entrada con stock alto" &&
                c7aPorArticulo[f.idArticulo]
            ) {
                f.c7a_activo = true;
                f.c7a_delta_acumulado =
                    c7aPorArticulo[f.idArticulo].delta_acumulado;
                f.c7a_tipo_articulo =
                    c7aPorArticulo[f.idArticulo].tipo_articulo;
            }
        });
    }

    if (Object.keys(c7bPorArticulo).length === 0) return;
    filas.forEach(function (f) {
        if (
            f.tipo === "Inventario en negativo" &&
            c7bPorArticulo[f.idArticulo]
        ) {
            f.c7b_activo = true;
            f.c7b_offset_estimado =
                c7bPorArticulo[f.idArticulo].offset_estimado;
            f.c7b_tipo_articulo = c7bPorArticulo[f.idArticulo].tipo_articulo;
        }
    });
    // Si caso7b no estaba marcado por el usuario, eliminar sus filas del array
    var caso7bMarcado = (function () {
        if (window.posstockTipoActivo === "anual") return true;
        var chk = document.getElementById("posstockChk_caso7b");
        return chk ? chk.checked : true;
    })();
    if (!caso7bMarcado) {
        var i = filas.length;
        while (i--) {
            if (
                filas[i].c7_subcaso === "C7b" ||
                filas[i].c7_subcaso === "C7b_posible" ||
                filas[i].c7_subcaso === "C7b_ruido_peso"
            ) {
                filas.splice(i, 1);
            }
        }
    }
    // Ídem para caso7a
    var caso7aMarcado = (function () {
        if (window.posstockTipoActivo === "anual") return true;
        var chk = document.getElementById("posstockChk_caso7a");
        return chk ? chk.checked : true;
    })();
    if (!caso7aMarcado) {
        var i = filas.length;
        while (i--) {
            if (
                filas[i].c7_subcaso === "C7a" ||
                filas[i].c7_subcaso === "C7a_posible"
            ) {
                filas.splice(i, 1);
            }
        }
    }
}

export { _posstockEnriquecerC1aConC7b };
