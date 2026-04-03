/**
 * PosstockC7cde.js — Fase 2 de resolución de cruces C7c/d/e
 *
 * Responsabilidades:
 *   - Llamar a resolverPOSStockC7cde (AJAX secundario) con los IDs de artículos
 *     C7a+C7b detectados en todos los lotes.
 *   - Aplicar las anotaciones de cruce a las filas acumuladas.
 *   - Delegar en _posstockFinalizarTabla cuando termina (o si hay error).
 *
 * Nota circular: importa _posstockFinalizarTabla de PosstockLoader.
 * ES modules resuelven esto correctamente porque la referencia se resuelve
 * en tiempo de llamada, no en tiempo de importación.
 */

import { _posstockFinalizarTabla } from "./PosstockLoader.js";

/**
 * Fase 2 de C7: llama a resolverPOSStockC7cde con los IDs de artículos C7a+C7b
 * detectados en todos los lotes. Cuando termina, aplica las anotaciones de cruce
 * a las filas acumuladas y procede a renderizar la tabla.
 */
function _posstockResolverC7cde(idsC7a, idsC7b, acum, parametrosLote, periodo) {
    $.ajax({
        data: {
            pulsado: "resolverPOSStockC7cde",
            fecha_inicio_movimientos: parametrosLote.fecha_inicio_movimientos,
            fecha_fin_movimientos: parametrosLote.fecha_fin_movimientos,
            fecha_inicio_stock: parametrosLote.fecha_inicio_stock,
            fecha_fin_stock: parametrosLote.fecha_fin_stock,
            tipo_periodo: parametrosLote.tipo_periodo || "",
            familias_incluir: parametrosLote.familias_incluir || "",
            familias_excluir: parametrosLote.familias_excluir || "",
            proveedores_incluir: parametrosLote.proveedores_incluir || "",
            ids_c7a: idsC7a.join(","),
            ids_c7b: idsC7b.join(","),
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                // Si C7cde falla, renderizar igualmente sin anotaciones de cruce
                _posstockFinalizarTabla(acum, periodo);
                return;
            }
            // Aplicar anotaciones de cruce a las filas ya acumuladas
            var cruces = resultado.cruces || {};
            acum.forEach(function (f) {
                var c = cruces[f.idArticulo];
                if (!c) return;
                f.posible_cruce_con = c.posible_cruce_con;
                f.cruce_score = c.cruce_score;
                f.cruce_nivel = c.cruce_nivel;
                if (c.posible_causa) f.posible_causa = c.posible_causa;
            });
            _posstockFinalizarTabla(acum, periodo);
        },
        error: function () {
            // Si hay error de red, renderizar sin cruces (no bloquear al usuario)
            _posstockFinalizarTabla(acum, periodo);
        },
    });
}

export { _posstockResolverC7cde };
