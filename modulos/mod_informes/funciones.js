/**
 * funciones.js — barrel de compatibilidad (Fase 7 · paso 3)
 *
 * Importa todos los módulos para que sus efectos de carga (window.X = X)
 * se registren cuando se incluye este archivo.
 * Eliminable en v2 cuando POSStock.php y ListaInformes.php importen directamente.
 */
export * from "./informes/classic/ListaInformesUI.js";
export * from "./informes/posstock/PosstockConfig.js";
export * from "./informes/posstock/PosstockFiltros.js";
export * from "./informes/posstock/PosstockTabla.js";
export * from "./informes/posstock/PosstockEnriquecedor.js";
export * from "./informes/posstock/PosstockC7cde.js";
export * from "./informes/posstock/PosstockLoader.js";
export * from "./informes/posstock/PosstockExport.js";
export * from "./informes/shared/PeriodSelector.js";
