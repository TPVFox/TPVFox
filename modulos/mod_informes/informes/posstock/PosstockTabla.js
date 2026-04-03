/**
 * PosstockTabla.js — Tabla y filtros visuales POSStock
 *
 * Responsabilidades:
 *   - Ordenación/agrupación de filas por proveedor (_posstockReordenarTabla)
 *   - Persistencia de preferencias UI en localStorage
 *   - Sistema de filtro por badges (3 estados: neutro/incluir/excluir)
 *   - _posstockSortComparator (comparador puro para lotes intermedios)
 *   - _posstockBadgeGrupo (clasificación pura badge → grupo)
 */

// ── Comparador puro ───────────────────────────────────────────────────────────

/**
 * Comparador de ordenación para el array de incidencias acumulado entre lotes.
 * El backend ya genera orden_clave para evitar duplicar la lógica de prioridad.
 */
function _posstockSortComparator(a, b) {
    if (a && b && a.orden_clave && b.orden_clave) {
        if (a.orden_clave < b.orden_clave) return -1;
        if (a.orden_clave > b.orden_clave) return 1;
        return (a.idArticulo || 0) - (b.idArticulo || 0);
    }
    return 0;
}

// ── localStorage — preferencias UI ───────────────────────────────────────────
var _POSSTOCK_LS_KEY = "posstock_ui_v1";
var _posstockAgrupadoPorProv = false;

function _posstockGuardarPrefs() {
    try {
        localStorage.setItem(
            _POSSTOCK_LS_KEY,
            JSON.stringify({
                agruparProv: _posstockAgrupadoPorProv,
                badgeStates: window._posstockBadgeStates || {},
            }),
        );
    } catch (e) {}
}

function _posstockCargarPrefs() {
    try {
        var raw = localStorage.getItem(_POSSTOCK_LS_KEY);
        return raw ? JSON.parse(raw) : {};
    } catch (e) {
        return {};
    }
}

// ── Agrupación por proveedor ──────────────────────────────────────────────────

function posstockToggleAgruparProveedor() {
    _posstockAgrupadoPorProv = !_posstockAgrupadoPorProv;
    var label = document.getElementById("posstockAgruparProvLabel");
    if (label) {
        label.className = _posstockAgrupadoPorProv
            ? "label label-success"
            : "label label-default";
        label.textContent = _posstockAgrupadoPorProv ? "Sí" : "No";
    }
    _posstockGuardarPrefs();
    _posstockReordenarTabla();
}

// Aplica el estado guardado de "agrupar proveedor" al label del botón (sin toggle)
function _posstockRestaurarAgruparProv() {
    var label = document.getElementById("posstockAgruparProvLabel");
    if (label) {
        label.className = _posstockAgrupadoPorProv
            ? "label label-success"
            : "label label-default";
        label.textContent = _posstockAgrupadoPorProv ? "Sí" : "No";
    }
}

/**
 * Reordena las filas del tbody de posstockTabla.
 * - Modo normal:    orden por data-orden (lexicográfico, igual que el backend)
 * - Modo proveedor: agrupa por data-prov (alfabético), dentro de cada grupo
 *                   mantiene el orden por data-orden
 */
function _posstockReordenarTabla() {
    var tbody = document.querySelector("#posstockTabla tbody");
    if (!tbody) return;

    // Eliminar cabeceras de grupo previas
    Array.from(tbody.querySelectorAll("tr[data-prov-header]")).forEach(
        function (tr) {
            tr.remove();
        },
    );

    var filas = Array.from(tbody.querySelectorAll("tr"));

    if (_posstockAgrupadoPorProv) {
        // Ordenar: primero por proveedor (asc, vacíos al final), luego por orden_clave (asc)
        filas.sort(function (a, b) {
            var pa = a.dataset.prov || "";
            var pb = b.dataset.prov || "";
            if (pa === "" && pb !== "") return 1;
            if (pa !== "" && pb === "") return -1;
            if (pa !== pb) return pa.localeCompare(pb, "es");
            var oa = a.dataset.orden || "";
            var ob = b.dataset.orden || "";
            return oa < ob ? -1 : oa > ob ? 1 : 0;
        });

        // Reinsertar filas e inyectar cabecera al inicio de cada grupo
        var provActual = null;
        var sinProvHeader = false;

        function _crearCabeceraGrupo(provKey, label, icono) {
            var nGrupo = 0,
                costeGrupo = 0;
            filas.forEach(function (f) {
                if (
                    (f.dataset.prov || "") === provKey &&
                    f.style.display !== "none"
                ) {
                    nGrupo++;
                    costeGrupo += parseFloat(f.dataset.coste || "0");
                }
            });
            var costeTexto =
                costeGrupo > 0
                    ? " &nbsp;·&nbsp; Valor est.: <strong>~" +
                      costeGrupo.toLocaleString("es-ES", {
                          maximumFractionDigits: 0,
                      }) +
                      " €</strong>"
                    : "";
            var tr = document.createElement("tr");
            tr.setAttribute("data-prov-header", provKey || "__sinprov__");
            tr.style.cssText =
                "background:#f0f4fa;border-top:2px solid #c8d4e8;" +
                (nGrupo === 0 ? "display:none;" : "");
            tr.innerHTML =
                '<td colspan="7" style="font-weight:600;padding:4px 8px;font-size:12px;">' +
                '<i class="glyphicon glyphicon-' +
                icono +
                '" style="margin-right:5px;color:#5a7ab5;"></i>' +
                label +
                ' &nbsp;<span class="label label-default">' +
                nGrupo +
                " artículo" +
                (nGrupo !== 1 ? "s" : "") +
                "</span>" +
                costeTexto +
                "</td>";
            return tr;
        }

        filas.forEach(function (fila) {
            var prov = fila.dataset.prov || "";
            if (prov !== "" && prov !== provActual) {
                provActual = prov;
                tbody.appendChild(_crearCabeceraGrupo(prov, prov, "truck"));
            } else if (prov === "" && !sinProvHeader) {
                sinProvHeader = true;
                tbody.appendChild(
                    _crearCabeceraGrupo(
                        "",
                        "Proveedor no identificado",
                        "question-sign",
                    ),
                );
            }
            tbody.appendChild(fila);
        });

        // Fila de totales al inicio y al final del agrupado
        var nTotal = 0,
            costeTotal = 0,
            nProveedores = 0;
        var provsVistos = {};
        filas.forEach(function (f) {
            if (f.style.display === "none") return;
            nTotal++;
            costeTotal += parseFloat(f.dataset.coste || "0");
            var p = f.dataset.prov || "__sinprov__";
            if (!provsVistos[p]) {
                provsVistos[p] = true;
                nProveedores++;
            }
        });
        if (nTotal > 0) {
            var costeTotalTexto =
                costeTotal > 0
                    ? " &nbsp;·&nbsp; Valor est. total: <strong>~" +
                      costeTotal.toLocaleString("es-ES", {
                          maximumFractionDigits: 0,
                      }) +
                      " €</strong>"
                    : "";
            var totalInnerHTML =
                '<td colspan="7" style="font-weight:600;padding:4px 8px;font-size:12px;text-align:right;">' +
                'Total: <span class="label label-primary">' +
                nTotal +
                " artículo" +
                (nTotal !== 1 ? "s" : "") +
                "</span>" +
                ' &nbsp;en&nbsp; <span class="label label-default">' +
                nProveedores +
                " proveedor" +
                (nProveedores !== 1 ? "es" : "") +
                "</span>" +
                costeTotalTexto +
                "</td>";
            var trTotalTop = document.createElement("tr");
            trTotalTop.setAttribute("data-prov-header", "__total__");
            trTotalTop.style.cssText =
                "background:#e8edf5; border-bottom:2px solid #b0bdd6;";
            trTotalTop.innerHTML = totalInnerHTML;
            tbody.insertBefore(trTotalTop, tbody.firstChild);
            var trTotalBottom = document.createElement("tr");
            trTotalBottom.setAttribute("data-prov-header", "__total__");
            trTotalBottom.style.cssText =
                "background:#e8edf5; border-top:2px solid #b0bdd6;";
            trTotalBottom.innerHTML = totalInnerHTML;
            tbody.appendChild(trTotalBottom);
        }
    } else {
        // Restaurar orden original por data-orden
        filas.sort(function (a, b) {
            var oa = a.dataset.orden || "";
            var ob = b.dataset.orden || "";
            return oa < ob ? -1 : oa > ob ? 1 : 0;
        });
        filas.forEach(function (fila) {
            tbody.appendChild(fila);
        });
    }
}

// Resetear el estado de agrupación cuando se recarga la tabla
function _posstockResetAgruparProv() {
    // Restaurar desde localStorage en lugar de resetear siempre a false
    var prefs = _posstockCargarPrefs();
    _posstockAgrupadoPorProv = prefs.agruparProv || false;
    _posstockRestaurarAgruparProv();
}

window.posstockToggleAgruparProveedor = posstockToggleAgruparProveedor;

// ── Filtro por badges ─────────────────────────────────────────────────────────

// Mapeo badge → grupo. Los badges dinámicos se detectan por regex en _posstockBadgeGrupo.
var _POSSTOCK_BADGE_GRUPOS = {
    // C1 · Stock negativo
    "Recepción no registrada": "C1",
    "Timing recepción": "C1",
    "Sin entradas": "C1",
    "Stock decimal": "C1",
    // C2 · Sobrestock entrada
    "Duplicado probable": "C2",
    "Posible duplicado": "C2",
    "Acumulación crónica": "C2",
    "Tendencia creciente": "C2",
    "Sobrestock severo": "C2",
    "Sin ventas": "C2",
    "Pedido prematuro": "C2",
    // C3 · Rotación (C3a caída + C3b sin rotación previa)
    "Caída severa": "C3",
    "Rotación caída": "C3",
    "Riesgo caducidad": "C3",
    "Sin rotación": "C3",
    "Reposición sin rotación": "C3",
    "Pedidos repetidos sin venta": "C3",
    // C5 · Rotura
    "En curso": "C5",
    KO: "C5",
    CR: "C5",
    RK: "C5",
    "Stock no fiable": "C5",
    "Error de pesaje": "C5",
    // C7b · Déficit
    "Déficit estable": "C7b",
    "Déficit posible": "C7b",
    "Déficit histórico": "C7b",
    // C7a · Merma tendencial
    "Merma estable": "C7a",
    "Merma posible": "C7a",
    "Merma histórica": "C7a",
    "Alta varianza": "C7a",
    // C7 · Evolución (compartido C7a / C7b)
    Nuevo: "C7ev",
    Mejorando: "C7ev",
    "Sin datos recientes": "C7ev",
    // C9 · Backstaging
    "Merma confirmada": "C9",
    "Merma probable": "C9",
    "Lote abierto": "C9",
    "Sobreventa no compensada": "C9",
    "Pico de merma": "C9",
    "Conservación OK": "C9",
    "Stock desajustado": "C9",
    // Dist · Distribución estadística (C5 / C6)
    Γ: "Dist",
    N: "Dist",
    BN: "Dist",
    Bin: "Dist",
    Poi: "Dist",
    // C6 · Punto de pedido
    "Stock OK": "C6",
    "LT prov.": "C6",
    "~stk": "C6",
};

// Etiquetas de grupo para la barra visual
var _POSSTOCK_GRUPOS_LABEL = {
    C1: "C1 · Neg.",
    C2: "C2 · Sobrestock",
    C3: "C3 · Rotación",
    C5: "C5 · Rotura",
    C7b: "C7b · Déficit",
    C7a: "C7a · Merma",
    C7ev: "C7 · Evolución",
    C9: "C9 · Backstaging",
    Dist: "Distribución",
    C6: "C6 · Pedido",
    Otros: "Otros",
};
var _POSSTOCK_GRUPOS_ORDEN = [
    "C1",
    "C2",
    "C3",
    "C5",
    "C7b",
    "C7a",
    "C7ev",
    "C9",
    "Dist",
    "C6",
    "Otros",
];

// Orden explícito dentro de cada grupo.
// '|' = separador visual de subgrupo (estado / severidad / calidad dato / etc.)
var _POSSTOCK_GRUPO_ORDEN_BADGES = {
    C1: [
        "Recepción no registrada",
        "Timing recepción",
        "|",
        "Sin entradas",
        "Stock decimal",
    ],
    C2: [
        "Duplicado probable",
        "Posible duplicado",
        "|",
        "Acumulación crónica",
        "Tendencia creciente",
        "|",
        "Sobrestock severo",
        "Sin ventas",
        "Pedido prematuro",
    ],
    C3: [
        "Caída severa",
        "Rotación caída",
        "Riesgo caducidad",
        "|",
        "Sin rotación",
        "Reposición sin rotación",
        "Pedidos repetidos sin venta",
    ],
    C5: [
        "En curso",
        "|",
        "KO",
        "CR",
        "RK",
        "|",
        "Stock no fiable",
        "Error de pesaje",
    ],
    C7b: ["Déficit estable", "Déficit posible", "|", "Déficit histórico"],
    C7a: [
        "Merma estable",
        "Merma posible",
        "|",
        "Merma histórica",
        "|",
        "Alta varianza",
    ],
    C7ev: ["Nuevo", "Mejorando", "Sin datos recientes"],
    C9: [
        "Merma confirmada",
        "Merma probable",
        "Merma posible",
        "|",
        "Lote abierto",
        "Sobreventa no compensada",
        "Pico de merma",
        "|",
        "Conservación OK",
        "Stock desajustado",
    ],
    Dist: ["Γ", "N", "BN", "Bin", "Poi"],
    C6: ["Stock OK", "|", "LT prov.", "~stk"],
};

// Mapeo data-tipo (valor exacto del HTML) → grupo del filtro.
var _POSSTOCK_TIPO_A_GRUPO = {
    "Inventario en negativo": "C1",
    "Desajuste Puntual de Stock": "C1",
    "Entrada con stock alto": "C2",
    "Caída de rotación": "C3",
    "Entrada sin rotación previa": "C3",
    "Venta Cero (Posible Rotura Física)": "C5",
    "Posible error de pesaje": "C5",
    "Entrada no registrada": "C7b",
    "Merma acumulada": "C7a",
    "Merma backstaging": "C9",
    "Agotamiento Estimado": "C6",
    "Punto de Pedido": "C6",
    "Stock Inactivo en Periodo": "C6",
};

/**
 * Determina el grupo de un badge. Se llama con el mapa pre-calculado de
 * badge → grupos reales presentes en la tabla (_badgeGruposReales), de modo
 * que badges ambiguos (mismo texto en C7a y C9) se asignan al grupo correcto
 * según las filas que realmente hay en la tabla en ese momento.
 */
function _posstockBadgeGrupo(badge, badgeGruposReales) {
    // 1. Si en la tabla actual el badge solo aparece en filas de un grupo → ese grupo
    var reales = badgeGruposReales ? badgeGruposReales[badge] || [] : [];
    if (reales.length === 1) return reales[0];

    // 2. Mapa estático para badges no ambiguos o cuando aparecen en varios grupos
    if (_POSSTOCK_BADGE_GRUPOS[badge]) return _POSSTOCK_BADGE_GRUPOS[badge];

    // 3. Patrones dinámicos
    if (/^Recuperada\b/i.test(badge)) return "C5";
    if (/^Pedir\s*~/i.test(badge)) return "C6";
    if (/^\d+d$/.test(badge)) return "C6";
    return "Otros";
}

/**
 * Badges: 3 estados por badge.
 *   0 = neutral  (btn-default) — no filtra
 *   1 = incluido (btn-success) — la fila DEBE tener al menos uno de los incluidos (OR)
 *   2 = excluido (btn-danger)  — la fila se oculta si tiene este badge (prioridad)
 * Ciclo al hacer clic: 0 → 1 → 2 → 0
 */
function _posstockIniciarFiltroBadges() {
    var filas = document.querySelectorAll(
        "#posstockTablaWrap tbody tr[data-badges]",
    );
    if (!filas.length) return;

    // Recopilar badges únicos y, por cada badge, qué grupos (data-tipo → grupo) tiene en la tabla
    var badgesSet = {};
    var badgeGruposReales = {};
    var haySinBadge = false;
    filas.forEach(function (tr) {
        var val = tr.getAttribute("data-badges") || "";
        var tipo = tr.getAttribute("data-tipo") || "";
        var grupoFila = _POSSTOCK_TIPO_A_GRUPO[tipo] || null;
        if (val === "") {
            haySinBadge = true;
        } else {
            val.split("|").forEach(function (b) {
                b = b.trim();
                if (!b) return;
                badgesSet[b] = true;
                if (grupoFila) {
                    if (!badgeGruposReales[b]) badgeGruposReales[b] = {};
                    badgeGruposReales[b][grupoFila] = true;
                }
            });
        }
    });
    Object.keys(badgeGruposReales).forEach(function (b) {
        badgeGruposReales[b] = Object.keys(badgeGruposReales[b]);
    });

    var badges = Object.keys(badgesSet).sort();
    if (!badges.length && !haySinBadge) return;

    // Restaurar estados guardados o inicializar a neutral
    var prefs = _posstockCargarPrefs();
    var savedStates = prefs.badgeStates || {};
    window._posstockBadgeStates = {};
    badges.forEach(function (b) {
        window._posstockBadgeStates[b] = savedStates[b] || 0;
    });
    if (haySinBadge) {
        window._posstockBadgeStates["__sinbadge__"] =
            savedStates["__sinbadge__"] || 0;
    }

    // ── Construir barra ──────────────────────────────────────────────────
    if (!document.getElementById("posstockFiltroBadgesStyle")) {
        var st = document.createElement("style");
        st.id = "posstockFiltroBadgesStyle";
        st.textContent = "#posstockFiltroBadges .btn { margin: 0 !important; }";
        document.head.appendChild(st);
    }
    var barra = document.createElement("div");
    barra.id = "posstockFiltroBadges";
    barra.style.cssText =
        "margin-bottom:10px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9; padding:6px 8px;";

    var cabecera = document.createElement("div");
    cabecera.style.cssText =
        "display:flex; align-items:center; gap:6px; margin-bottom:6px;";
    var lblFiltro = document.createElement("span");
    lblFiltro.className = "text-muted small";
    lblFiltro.style.fontWeight = "bold";
    lblFiltro.textContent = "Filtrar por badge:";
    cabecera.appendChild(lblFiltro);
    var btnReset = document.createElement("button");
    btnReset.type = "button";
    btnReset.className = "btn btn-xs btn-default";
    btnReset.setAttribute("data-badge-filtro", "__todos__");
    btnReset.title = "Quitar todos los filtros de badge";
    btnReset.textContent = "Todos";
    btnReset.onclick = function () {
        Object.keys(window._posstockBadgeStates).forEach(function (b) {
            window._posstockBadgeStates[b] = 0;
        });
        _aplicarFiltroBadges(filas);
        _actualizarBotonesEstado(barra);
        _posstockGuardarPrefs();
    };
    cabecera.appendChild(btnReset);
    barra.appendChild(cabecera);

    var porGrupo = {};
    badges.forEach(function (b) {
        var g = _posstockBadgeGrupo(b, badgeGruposReales);
        if (!porGrupo[g]) porGrupo[g] = [];
        porGrupo[g].push(b);
    });

    var filaGrupos = document.createElement("div");
    filaGrupos.style.cssText =
        "display:flex; flex-wrap:wrap; gap:6px; align-items:flex-start;";

    _POSSTOCK_GRUPOS_ORDEN.forEach(function (grupo) {
        if (!porGrupo[grupo]) return;
        var bloque = document.createElement("div");
        bloque.style.cssText =
            "display:inline-flex; align-items:stretch; background:#fff; border:1px solid #e0e0e0; border-radius:3px; overflow:hidden;";
        var etq = document.createElement("span");
        etq.className = "text-muted";
        etq.style.cssText =
            "display:flex; align-items:center; font-size:10px; font-weight:bold; white-space:nowrap; padding:0 6px 0 7px; border-right:1px solid #e0e0e0; background:#f5f5f5;";
        etq.textContent = _POSSTOCK_GRUPOS_LABEL[grupo] || grupo;
        var cuerpo = document.createElement("div");
        cuerpo.style.cssText =
            "display:flex; align-items:center; flex-wrap:wrap; gap:3px; padding:3px 7px;";
        bloque.appendChild(etq);

        var ordenExplicito = _POSSTOCK_GRUPO_ORDEN_BADGES[grupo] || [];
        var yaRenderizados = {};
        ordenExplicito.forEach(function (b) {
            if (b === "|") {
                var sep = document.createElement("span");
                sep.style.cssText =
                    "display:inline-block; width:1px; height:14px; background:#ddd; margin:0 2px; align-self:center; flex-shrink:0;";
                cuerpo.appendChild(sep);
                return;
            }
            if (porGrupo[grupo].indexOf(b) !== -1) {
                cuerpo.appendChild(_crearBtnBadge(b, filas, barra));
                yaRenderizados[b] = true;
            }
        });
        porGrupo[grupo].forEach(function (b) {
            if (!yaRenderizados[b]) {
                cuerpo.appendChild(_crearBtnBadge(b, filas, barra));
            }
        });
        bloque.appendChild(cuerpo);
        filaGrupos.appendChild(bloque);
    });

    if (haySinBadge) {
        var bloqueSin = document.createElement("div");
        bloqueSin.style.cssText =
            "display:inline-flex; align-items:center; gap:3px; background:#fff; border:1px solid #e0e0e0; border-radius:3px; padding:3px 7px;";
        bloqueSin.appendChild(_crearBtnBadge("__sinbadge__", filas, barra));
        filaGrupos.appendChild(bloqueSin);
    }

    barra.appendChild(filaGrupos);

    var wrap = document.getElementById("posstockTablaWrap");
    var tabla = wrap ? wrap.querySelector("table") : null;
    if (tabla) wrap.insertBefore(barra, tabla);

    _aplicarFiltroBadges(filas);
    _actualizarBotonesEstado(barra);
}

function _crearBtnBadge(badge, filas, barra) {
    var btn = document.createElement("button");
    btn.type = "button";
    btn.className = "btn btn-xs btn-default";
    btn.setAttribute("data-badge-filtro", badge);
    btn.textContent = badge === "__sinbadge__" ? "Sin badge" : badge;
    btn.title = "Clic: neutro → incluir (verde) → excluir (rojo) → neutro";
    btn.onclick = function () {
        var cur = window._posstockBadgeStates[badge] || 0;
        window._posstockBadgeStates[badge] = (cur + 1) % 3;
        _aplicarFiltroBadges(filas);
        _actualizarBotonesEstado(barra);
        _posstockGuardarPrefs();
    };
    return btn;
}

/**
 * Aplica show/hide a las filas según el estado de 3 niveles de cada badge.
 * Lógica: exclusiones tienen prioridad; inclusiones son OR entre sí.
 * Actualiza window._posstockFilasVisibles para CSV/PDF filtrado.
 */
function _aplicarFiltroBadges(filas) {
    var states = window._posstockBadgeStates || {};
    var incluidosBadges = Object.keys(states).filter(function (b) {
        return b !== "__sinbadge__" && states[b] === 1;
    });
    var excluidosBadges = Object.keys(states).filter(function (b) {
        return b !== "__sinbadge__" && states[b] === 2;
    });
    var sinBadgeState = states["__sinbadge__"] || 0;
    var hayInclusiones = incluidosBadges.length > 0 || sinBadgeState === 1;

    filas.forEach(function (tr) {
        if (tr.hasAttribute("data-prov-header")) return;
        var val = tr.getAttribute("data-badges") || "";
        var rowBadges =
            val === ""
                ? []
                : val.split("|").map(function (b) {
                      return b.trim();
                  });
        var esSinBadge = val === "";
        var mostrar = true;

        // 1. Exclusiones (prioridad máxima)
        if (esSinBadge) {
            if (sinBadgeState === 2) mostrar = false;
        } else {
            if (
                excluidosBadges.some(function (b) {
                    return rowBadges.indexOf(b) !== -1;
                })
            ) {
                mostrar = false;
            }
        }

        // 2. Inclusiones OR (solo si hay al menos un incluido activo)
        if (mostrar && hayInclusiones) {
            if (esSinBadge) {
                mostrar = sinBadgeState === 1;
            } else {
                mostrar = incluidosBadges.some(function (b) {
                    return rowBadges.indexOf(b) !== -1;
                });
            }
        }

        tr.style.display = mostrar ? "" : "none";
    });

    window._posstockFilasVisibles = Array.from(filas).filter(function (tr) {
        return (
            tr.style.display !== "none" && !tr.hasAttribute("data-prov-header")
        );
    });
}

function _actualizarBotonesEstado(barra) {
    var states = window._posstockBadgeStates || {};
    var hayFiltro = Object.keys(states).some(function (b) {
        return states[b] !== 0;
    });
    barra.querySelectorAll("[data-badge-filtro]").forEach(function (btn) {
        var b = btn.getAttribute("data-badge-filtro");
        if (b === "__todos__") {
            btn.className =
                "btn btn-xs " + (hayFiltro ? "btn-warning" : "btn-default");
            return;
        }
        var estado = states[b] || 0;
        btn.className =
            "btn btn-xs " +
            (estado === 1
                ? "btn-success active"
                : estado === 2
                  ? "btn-danger  active"
                  : "btn-default");
    });
}

export {
    _posstockSortComparator,
    _posstockGuardarPrefs,
    _posstockCargarPrefs,
    posstockToggleAgruparProveedor,
    _posstockRestaurarAgruparProv,
    _posstockReordenarTabla,
    _posstockResetAgruparProv,
    _POSSTOCK_BADGE_GRUPOS,
    _POSSTOCK_GRUPOS_LABEL,
    _POSSTOCK_GRUPOS_ORDEN,
    _POSSTOCK_GRUPO_ORDEN_BADGES,
    _POSSTOCK_TIPO_A_GRUPO,
    _posstockBadgeGrupo,
    _posstockIniciarFiltroBadges,
    _crearBtnBadge,
    _aplicarFiltroBadges,
    _actualizarBotonesEstado,
};
