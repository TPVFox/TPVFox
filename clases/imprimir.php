<?php
require_once($RutaServidor.$HostNombre.'/lib/tcpdf/tcpdf.php');
class imprimir extends TCPDF {
	//~ //Datos de la cabecera 
        //~ public function Cabecera() {
				 //~ $headerData = getHeaderData();
              //~ //  $this->setJPEGQuality(90);
                //~ //archivo de imagen al principio y al final enlace al que se quiere redirigir el clicar en la imagen
             //~ //   $this->Image('', 120, 10, 75, 0, 'PNG', '');
             //~ $this->writeHTML($headerData['string']);
        //~ }
        
        var $htmlHeader;
        public function setHtmlHeader($htmlHeader) {
			$this->htmlHeader = $htmlHeader;
		}

		public function Header() {
				$this->writeHTMLCell(
				$w = 0, $h = 100000, $x = '', $y = '',
				$this->htmlHeader, $border = 0, $ln = 10, $fill = 0,
				$reseth = true, $align = 'top', $autopadding = true);
				
		
		}
        public function PieDePagina() {
                $this->SetY(-15);
                $this->SetFont(PDF_FONT_NAME_MAIN, 'I', 8);
                $this->Cell(0, 10, 'esto es una prueba de tpv', 0, false, 'C');
        }
        public function CajaDeTexto($textval, $x = 0, $y, $width = 0, $height = 10, $fontsize = 10, $fontstyle = '', $align = 'L') {
                $this->SetXY($x+20, $y); // 20 = margin left
                $this->SetFont(PDF_FONT_NAME_MAIN, $fontstyle, $fontsize);
                $this->Cell($width, $height, $textval, 0, false, $align);
        }
}

?>
