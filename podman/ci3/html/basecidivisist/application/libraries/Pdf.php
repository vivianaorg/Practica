<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once APPPATH . "third_party/fpdf/fpdf.php";
require_once APPPATH . "third_party/fpdf/code128.php";

class Pdf extends PDF_Code128 {

    function __construct($orientation = 'P', $unit = 'mm', $format = 'letter') {
        parent::__construct($orientation, $unit, $format);
    }

    public function pdf_table() {
        $this->titulo_header = "titulo de prueba";
        $this->AliasNbPages();
        $this->AddPage();
        $this->SetFont('Helvetica', '', $tamano_texto);
        $this->MultiCell(0, 5, "texto de prueba");
        $this->Ln(5);
        $this->Output("prueba.pdf", "D");
    }

}

/* End of file Pdf.php */
/* Location: ./application/libraries/Pdf.php */