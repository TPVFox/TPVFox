<?php
// @ Objetivo: generar hoja rápida de conteo línea a línea con TCPDF.
//
// Diseñada para usarse junto a un pedido a proveedor o preparación de pedido
// de cliente. Una fila compacta por artículo: ID, nombre, stock en sistema y
// casilla de conteo real. Los artículos sin stock se marcan visualmente.
//
// Parámetros POST:
//   ids_articulos  — string, IDs separados por comas (opcional)
//   id_familia     — int, filtro por familia (opcional)
//   id_subfamilia  — int, filtro por subfamilia (opcional)
//   solo_activos   — '1'|'0', default '1'
//   titulo         — string, título personalizado (opcional)
//   mostrar_stock  — '1'|'0', default '1' (0 = conteo ciego, no muestra stock)

require_once $RutaServidor . $HostNombre . '/clases/claseimprimir.php';

// ── Parámetros ────────────────────────────────────────────────────────────────

$ids_raw       = trim($_POST['ids_articulos'] ?? '');
$id_familia    = (int)($_POST['id_familia']   ?? 0);
$id_subfamilia = (int)($_POST['id_subfamilia'] ?? 0);
$solo_activos  = ($_POST['solo_activos'] ?? '1') !== '0';
$titulo_custom = trim($_POST['titulo'] ?? '');
$mostrar_stock = ($_POST['mostrar_stock'] ?? '1') !== '0';

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

// ── Constantes de diseño ──────────────────────────────────────────────────────

$titulo      = $titulo_custom ?: 'Hoja rápida de conteo';
$fecha_gen   = date('d/m/Y H:i');
$n_articulos = count($articulos);

// Paleta B/N, optimizada para impresión económica
define('HR_TH_BG',      '#000000');
define('HR_TH_FG',      '#ffffff');
define('HR_ROW_PAR',    '#ffffff');
define('HR_ROW_IMP',    '#f0f0f0');
// Artículo sin stock: fondo muy claro con texto gris para identificar de un vistazo
define('HR_SIN_STOCK_BG', '#e8e8e8');
define('HR_SIN_STOCK_FG', '#888888');

// ── HTML de cabecera ──────────────────────────────────────────────────────────

$col_stock = $mostrar_stock ? 'Stock' : '---';
$aviso_ciego = !$mostrar_stock
    ? ' &nbsp;|&nbsp; <b>CONTEO CIEGO</b> &mdash; stock no visible'
    : '';

$html_cabecera = '
<table border="0" cellpadding="2" cellspacing="0" width="100%">
    <tr>
        <td width="70%"><font size="11"><b>' . htmlspecialchars($titulo) . '</b></font></td>
        <td width="30%" align="right"><font size="7">Generado: ' . $fecha_gen
            . ' &nbsp;|&nbsp; ' . $n_articulos . ' art&iacute;culos'
            . $aviso_ciego . '</font></td>
    </tr>
    <tr>
        <td colspan="2" style="border-bottom:1px solid #000000; padding-bottom:1px;">
            <font size="6" color="#555555">
                Stock = valor sistema al generar. &nbsp;
                Artículos <font color="' . HR_SIN_STOCK_FG . '"><b>en gris</b></font>
                = sin stock registrado en sistema.
            </font>
        </td>
    </tr>
</table>';

// ── HTML del cuerpo: tabla compacta ──────────────────────────────────────────
//
// Columnas:
//   #      4%  — número de orden
//   ID     8%  — idArticulo
//   Nombre 56% — nombre del artículo
//   Stock  12% — stock sistema (o "---" en modo ciego)
//   Conteo 20% — casilla en blanco para escribir
//
// Los artículos con stock_sistema == 0 reciben fondo gris y un marcador "0"
// o "S/S" (sin stock) para que el operario los identifique de un vistazo.

$col_stock_header = $mostrar_stock ? 'Stock' : '&mdash;';

$filas_html = '';
foreach ($articulos as $n => $art) {
    $sin_stock  = ((float)$art['stock_sistema'] == 0.0);
    $row_bg     = $sin_stock
        ? HR_SIN_STOCK_BG
        : (($n % 2 === 0) ? HR_ROW_PAR : HR_ROW_IMP);
    $fg_color   = $sin_stock ? HR_SIN_STOCK_FG : '#000000';

    $num        = $n + 1;
    $id_art     = htmlspecialchars((string)$art['idArticulo']);
    $nombre     = htmlspecialchars($art['articulo_name']);

    if ($mostrar_stock) {
        $stock_val = $sin_stock
            ? '<font color="' . HR_SIN_STOCK_FG . '"><b>S/S</b></font>'
            : number_format((float)$art['stock_sistema'], 2, ',', '');
    } else {
        $stock_val = '&mdash;';
    }

    // nobr="true" evita que TCPDF parta la fila entre páginas
    $filas_html .= '<tr nobr="true" bgcolor="' . $row_bg . '">'
        . '<td width="4%"  align="center"><font size="7" color="' . $fg_color . '">' . $num . '</font></td>'
        . '<td width="8%"  align="center"><font size="7" color="' . $fg_color . '"><b>' . $id_art . '</b></font></td>'
        . '<td width="56%"><font size="8" color="' . $fg_color . '">' . $nombre . '</font></td>'
        . '<td width="12%" align="center" bgcolor="' . ($sin_stock ? HR_SIN_STOCK_BG : '#e8e8e8') . '">'
            . '<font size="8" color="' . $fg_color . '"><b>' . $stock_val . '</b></font>'
        . '</td>'
        . '<td width="20%" bgcolor="#ffffff">&nbsp;</td>'
        . '</tr>';
}

$html_tabla = '
<table border="1" cellpadding="3" cellspacing="0" width="100%">
    <thead>
        <tr bgcolor="' . HR_TH_BG . '" nobr="true">
            <th width="4%"  align="center"><font color="' . HR_TH_FG . '" size="7"><b>#</b></font></th>
            <th width="8%"  align="center"><font color="' . HR_TH_FG . '" size="7"><b>ID</b></font></th>
            <th width="56%"><font color="' . HR_TH_FG . '" size="7"><b>Nombre del art&iacute;culo</b></font></th>
            <th width="12%" align="center"><font color="' . HR_TH_FG . '" size="7"><b>' . $col_stock_header . '</b></font></th>
            <th width="20%" align="center"><font color="' . HR_TH_FG . '" size="7"><b>Conteo real</b></font></th>
        </tr>
    </thead>
    <tbody>' . $filas_html . '</tbody>
</table>';

// ── Generar PDF ───────────────────────────────────────────────────────────────

$pdf = new imprimirPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('TPVFox');
$pdf->SetAuthor('Hoja Rápida Conteo');
$pdf->SetTitle($titulo);
$pdf->SetFont(PDF_FONT_NAME_MAIN, '', 7);
$pdf->SetMargins(6, 18, 6);
$pdf->SetAutoPageBreak(true, 6);
$pdf->setCabecera($html_cabecera);
$pdf->AddPage();
$pdf->writeHTML($html_tabla, true, false, true, false, '');

$fichero  = 'conteo_rapido_' . date('Ymd_His') . '.pdf';
$filename = $RutaServidor . $rutatmp . '/' . $fichero;
$pdf->Output($filename, 'F');

$respuesta['url']   = $rutatmp . '/' . $fichero;
$respuesta['total'] = $n_articulos;
