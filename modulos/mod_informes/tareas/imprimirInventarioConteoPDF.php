<?php
// @ Objetivo: generar hoja de conteo físico de inventario con TCPDF.
//
// Dos modos controlados por el parámetro POST 'modo':
//
//   'normal' (defecto) — cada artículo tiene su bloque de conteo junto al nombre y stock.
//
//   'ciega'            — el PDF tiene dos secciones separadas por un salto de página:
//                        Sección A (referencia): lista de artículos con stock sistema.
//                        Sección B (conteo):     misma lista numerada SIN stock sistema.
//                        El operario solo recibe la Sección B y no sabe el stock esperado.
//
// Parámetros POST comunes:
//   ids_articulos  — string, IDs separados por comas (opcional)
//   id_familia     — int, filtro por familia (opcional)
//   id_subfamilia  — int, filtro por subfamilia (opcional)
//   solo_activos   — '1'|'0', default '1'
//   titulo         — string, título personalizado (opcional)
//   modo           — 'normal'|'ciega', default 'normal'

require_once $RutaServidor . $HostNombre . '/clases/claseimprimir.php';

// ── Parámetros ────────────────────────────────────────────────────────────────

$ids_raw       = trim($_POST['ids_articulos'] ?? '');
$id_familia    = (int)($_POST['id_familia']   ?? 0);
$id_subfamilia = (int)($_POST['id_subfamilia'] ?? 0);
$solo_activos  = ($_POST['solo_activos'] ?? '1') !== '0';
$titulo_custom = trim($_POST['titulo'] ?? '');
$modo          = ($_POST['modo'] ?? 'normal') === 'ciega' ? 'ciega' : 'normal';

// ── Construir WHERE ───────────────────────────────────────────────────────────

$where_parts = [];

if ($ids_raw !== '') {
    $ids_limpios = array_filter(
        array_map('intval', explode(',', $ids_raw)),
        fn($id) => $id > 0
    );
    if (empty($ids_limpios)) {
        $respuesta['error'] = 'IDs de artículos inválidos';
        return;
    }
    $where_parts[] = 'a.idArticulo IN (' . implode(',', $ids_limpios) . ')';
}

if ($id_familia > 0) {
    $where_parts[] = 'a.idArticulo IN (
        SELECT idArticulo FROM articulosFamilias WHERE idFamilia = ' . (int)$id_familia . '
    )';
}

if ($id_subfamilia > 0) {
    $where_parts[] = 'a.idArticulo IN (
        SELECT idArticulo FROM articulosFamilias WHERE idSubFamilia = ' . (int)$id_subfamilia . '
    )';
}

if ($solo_activos) {
    $where_parts[] = "a.estado = 'Activo'";
}

if (empty($where_parts)) {
    $respuesta['error'] = 'Se requiere al menos un filtro (familia o lista de artículos).';
    return;
}

$where_sql = 'WHERE ' . implode(' AND ', $where_parts);

// ── Consulta artículos ────────────────────────────────────────────────────────

$sentencia = $BDTpv->query("
    SELECT
        a.idArticulo,
        a.articulo_name,
        COALESCE(SUM(s.stockOn), 0) AS stock_sistema
    FROM articulos a
    LEFT JOIN articulosStocks s ON s.idArticulo = a.idArticulo
    $where_sql
    GROUP BY a.idArticulo, a.articulo_name
    ORDER BY a.articulo_name
");

if (!$sentencia) {
    $respuesta['error'] = 'Error SQL: ' . $BDTpv->error;
    return;
}

$articulos = [];
while ($fila = $sentencia->fetch_assoc()) {
    $articulos[] = $fila;
}

if (empty($articulos)) {
    $respuesta['error'] = 'No se encontraron artículos con los filtros indicados.';
    return;
}

// ── Constantes de diseño — B/N, optimizado para impresión ────────────────────

$titulo      = $titulo_custom ?: 'Hoja de conteo de inventario';
$fecha_gen   = date('d/m/Y H:i');
$n_articulos = count($articulos);

// Cabecera de columna: fondo negro, letra blanca
define('CF_TH_BG',    '#000000');
define('CF_TH_FG',    '#ffffff');
// Filas pares: blanco; impares: gris muy claro (visible en B/N sin manchar)
define('CF_ROW_PAR',  '#ffffff');
define('CF_ROW_IMP',  '#eeeeee');
// Celda de dato preimpreso (stock, número de ubicación): fondo ligeramente gris
define('CF_DATO_BG',  '#dddddd');
// Celda de escritura manual: fondo blanco puro, alto generoso
define('CF_WRITE_BG', '#ffffff');
// Alto mínimo de cada fila de ubicación (mm × 10 — TCPDF usa puntos internamente)
// Se consigue con padding generoso: cellpadding="7"

// ── Función: cabecera TCPDF ───────────────────────────────────────────────────

function _cfCabecera(string $titulo, string $fecha_gen, int $n_articulos, string $seccion = ''): string
{
    $derecha = 'Generado: ' . $fecha_gen . ' &nbsp;|&nbsp; ' . $n_articulos . ' art&iacute;culos';
    if ($seccion) {
        $derecha = '<b>' . htmlspecialchars($seccion) . '</b> &nbsp;|&nbsp; ' . $derecha;
    }
    return '
<table border="0" cellpadding="2" cellspacing="0" width="100%">
    <tr>
        <td width="70%"><font size="12"><b>' . htmlspecialchars($titulo) . '</b></font></td>
        <td width="30%" align="right"><font size="7">' . $derecha . '</font></td>
    </tr>
    <tr>
        <td colspan="2" style="border-bottom:1px solid #000000; padding-bottom:2px;">
            <font size="7">
                Stock sistema = valor registrado al generar el informe. &nbsp;
                Subtotal = (Packs &times; Ud/pack) + Uds. sueltas. &nbsp;
                TOTAL = suma de las 3 ubicaciones.
            </font>
        </td>
    </tr>
</table>';
}

// ── Función: tabla de referencia — modo ciega (con stock, sin casillas) ───────
//
// Una fila por artículo — sin rowspan.

function _cfTablaReferencia(array $articulos): string
{
    $html = '
<table border="1" cellpadding="5" cellspacing="0" width="100%">
    <thead>
        <tr bgcolor="' . CF_TH_BG . '" nobr="true">
            <th width="5%"  align="center"><font color="' . CF_TH_FG . '" size="8"><b>#</b></font></th>
            <th width="12%" align="center"><font color="' . CF_TH_FG . '" size="8"><b>ID</b></font></th>
            <th width="68%"><font color="' . CF_TH_FG . '" size="8"><b>Nombre del art&iacute;culo</b></font></th>
            <th width="15%" align="center"><font color="' . CF_TH_FG . '" size="8"><b>Stock sistema</b></font></th>
        </tr>
    </thead>
    <tbody>';

    foreach ($articulos as $n => $art) {
        $bg     = ($n % 2 === 0) ? CF_ROW_PAR : CF_ROW_IMP;
        $num    = $n + 1;
        $id_art = htmlspecialchars((string)$art['idArticulo']);
        $nombre = htmlspecialchars($art['articulo_name']);
        $stock  = number_format((float)$art['stock_sistema'], 2, ',', '');
        $html .= '
        <tr nobr="true" bgcolor="' . $bg . '">
            <td align="center"><b>' . $num . '</b></td>
            <td align="center"><font size="8">' . $id_art . '</font></td>
            <td><font size="8">' . $nombre . '</font></td>
            <td align="center" bgcolor="' . CF_DATO_BG . '"><font size="9"><b>' . $stock . '</b></font></td>
        </tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}

// ── Función: HTML de un artículo individual ───────────────────────────────────
//
// Devuelve la tabla de un único artículo para poder escribirla de forma
// independiente y controlar el salto de página antes de dibujarla.

function _cfHtmlArticulo(array $art, int $num, bool $mostrar_stock): string
{
    $id_art = htmlspecialchars((string)$art['idArticulo']);
    $nombre = htmlspecialchars($art['articulo_name']);
    $stock  = number_format((float)$art['stock_sistema'], 2, ',', '');

    $stock_label = $mostrar_stock
        ? '&nbsp;&nbsp;&mdash;&nbsp;&nbsp;<font size="8">Stock: <b>' . $stock . '</b></font>'
        : '';

    $html = '<table border="1" cellpadding="3" cellspacing="0" width="100%">';

    // F1: nombre
    $html .= '<tr bgcolor="' . CF_DATO_BG . '">'
        . '<td colspan="5"><font size="9"><b>' . $num . '.&nbsp; ' . $nombre . '</b>'
        . '&nbsp;&nbsp;<font color="#555555" size="8">ID: ' . $id_art . '</font>'
        . $stock_label . '</font></td>'
        . '</tr>';

    // F2: sub-cabecera
    $html .= '<tr bgcolor="' . CF_ROW_IMP . '">'
        . '<td width="7%"  align="center" bgcolor="' . CF_DATO_BG . '"><font size="7"><b>Ubic.</b></font></td>'
        . '<td width="23%" align="center"><font size="7">Uds. sueltas</font></td>'
        . '<td width="18%" align="center"><font size="7">Packs</font></td>'
        . '<td width="16%" align="center"><font size="7">Ud/pack</font></td>'
        . '<td width="20%" align="center"><font size="7">Subtotal</font></td>'
        . '</tr>';

    // F3-F5: filas de escritura
    for ($u = 1; $u <= 3; $u++) {
        $html .= '<tr>'
            . '<td width="7%"  align="center" bgcolor="' . CF_DATO_BG . '"><font size="9"><b>' . $u . '</b></font></td>'
            . '<td width="23%" bgcolor="' . CF_WRITE_BG . '"><br/>&nbsp;</td>'
            . '<td width="18%" bgcolor="' . CF_WRITE_BG . '"><br/>&nbsp;</td>'
            . '<td width="16%" bgcolor="' . CF_WRITE_BG . '"><br/>&nbsp;</td>'
            . '<td width="20%" bgcolor="' . CF_WRITE_BG . '"><br/>&nbsp;</td>'
            . '</tr>';
    }

    // F6: fila TOTAL
    $html .= '<tr bgcolor="' . CF_ROW_IMP . '">'
        . '<td colspan="4" align="right"><font size="8"><b>TOTAL (suma de las 3 ubicaciones):</b></font></td>'
        . '<td width="20%" bgcolor="' . CF_WRITE_BG . '"><br/>&nbsp;</td>'
        . '</tr>';

    $html .= '</table>';
    return $html;
}

// ── Función: escribir artículos en el PDF con control de salto de página ──────
//
// Escribe cada artículo por separado. Antes de cada uno comprueba si queda
// suficiente espacio en la página; si no, añade una página nueva.
// Esto garantiza que ningún bloque se parte por la mitad.
//
// Altura estimada por artículo (empírica, mm):
//   6 filas × ~7mm/fila ≈ 42mm. Se usa 46mm como margen de seguridad.

define('CF_ALTURA_ARTICULO', 46.0);

function _cfEscribirArticulos(object $pdf, array $articulos, bool $mostrar_stock, int $margen_inf): void
{
    foreach ($articulos as $n => $art) {
        $y_actual   = $pdf->GetY();
        $alto_pagina = $pdf->getPageHeight();

        if ($y_actual + CF_ALTURA_ARTICULO > $alto_pagina - $margen_inf) {
            $pdf->AddPage();
        }

        $pdf->writeHTML(
            _cfHtmlArticulo($art, $n + 1, $mostrar_stock),
            true, false, true, false, ''
        );

        // Separador de 2mm entre artículos
        if ($n < count($articulos) - 1) {
            $pdf->SetY($pdf->GetY() + 2);
        }
    }
}

// ── Función: aviso de sección (solo en modo ciega) ───────────────────────────

function _cfAvisoSeccion(string $texto, bool $invertido = false): string
{
    $bg  = $invertido ? CF_TH_BG  : CF_DATO_BG;
    $fg  = $invertido ? CF_TH_FG  : '#000000';
    return '
<table border="1" cellpadding="5" cellspacing="0" width="100%">
    <tr bgcolor="' . $bg . '">
        <td><font size="8" color="' . $fg . '"><b>' . $texto . '</b></font></td>
    </tr>
</table>
<br/>';
}

// ── Construir PDF ─────────────────────────────────────────────────────────────

$pdf = new imprimirPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('TPVFox');
$pdf->SetAuthor('Inventario Conteo');
$pdf->SetTitle($titulo);
$pdf->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$pdf->SetMargins(8, 20, 8);      // top 20mm (era 25) — cabecera más compacta
$pdf->SetAutoPageBreak(true, 8); // margen inferior 8mm (era 12)

if ($modo === 'ciega') {
    // ── Sección A: referencia (con stock) ────────────────────────────────────
    $pdf->setCabecera(_cfCabecera($titulo, $fecha_gen, $n_articulos, 'Hoja de referencia (con stock)'));
    $pdf->AddPage();
    $pdf->writeHTML(
        _cfAvisoSeccion('HOJA DE REFERENCIA &mdash; USO INTERNO &mdash; No entregar al operario que realiza el conteo.'),
        true,
        false,
        true,
        false,
        ''
    );
    $pdf->writeHTML(_cfTablaReferencia($articulos), true, false, true, false, '');

    // ── Sección B: conteo (sin stock) ────────────────────────────────────────
    $pdf->setCabecera(_cfCabecera($titulo, $fecha_gen, $n_articulos, 'Hoja de conteo (ciega)'));
    $pdf->AddPage();
    $pdf->writeHTML(
        _cfAvisoSeccion('HOJA DE CONTEO &mdash; OPERARIO &mdash; Realice el recuento sin consultar el stock. Subtotal = (Packs &times; Ud/pack) + Uds. sueltas. TOTAL = suma de las 3 ubicaciones.', true),
        true, false, true, false, ''
    );
    _cfEscribirArticulos($pdf, $articulos, false, 8);
} else {
    // ── Modo normal: todo junto ───────────────────────────────────────────────
    $pdf->setCabecera(_cfCabecera($titulo, $fecha_gen, $n_articulos));
    $pdf->AddPage();
    _cfEscribirArticulos($pdf, $articulos, true, 8);
}

$fichero  = 'conteo_inventario_' . date('Ymd_His') . '.pdf';
$filename = $RutaServidor . $rutatmp . '/' . $fichero;
$pdf->Output($filename, 'F');

$respuesta['url']   = $rutatmp . '/' . $fichero;
$respuesta['total'] = $n_articulos;
$respuesta['modo']  = $modo;
