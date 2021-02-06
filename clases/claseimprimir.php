<?php

require_once $RutaServidor.$HostNombre.'/lib/tcpdf/tcpdf.php';

class imprimirPDF extends TCPDF {

    private $htmlHeader;

//    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false) {
//        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
//    }

    public function setCabecera($htmlHeader) {
        $this->htmlHeader = $htmlHeader;
    }

    public function Header() {
        $this->writeHTMLCell(
                $w = 0, $h = 100000, $x = '', $y = '', $this->htmlHeader, $border = 0, $ln = 10, $fill = 0, $reseth = true, $align = 'top', $autopadding = true);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont(PDF_FONT_NAME_MAIN, 'I', 8);
//        $this->Cell(0, 10, 'esto es una prueba de tpv', 0, false, 'C');
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

//    public function CajaDeTexto($textval, $x = 0, $y, $width = 0, $height = 10, $fontsize = 10, $fontstyle = '', $align = 'L') {
//        $this->SetXY($x + 20, $y); // 20 = margin left
//        $this->SetFont(PDF_FONT_NAME_MAIN, $fontstyle, $fontsize);
//        $this->Cell($width, $height, $textval, 0, false, $align);
//    }

}

