<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once APPPATH . "third_party/tcpdf/tcpdf.php";

class Tc_pdf extends TCPDF {

    function __construct() {
        parent::__construct();
    }

    /**
     * Metodo para establecer la plantilla del encabezado
     * Utiliza el metodo nativo de la clase TCPDF
     */
    public function Header() {
        //establecer imagen de fondo con marca de agua institucional
        $bMargin = $this->getBreakMargin();
        $auto_page_break = $this->AutoPageBreak;
        $this->SetAutoPageBreak(false, 0);
        $this->Image(K_PATH_IMAGES . 'ufps/fondo_ufps.png', 0, 0, 215.9, 279.4, 'PNG');
        //establecer header con logo y titulo institucional
        parent::Header();
    }

    /**
     * Genera un archivo pdf
     * 
     * @param string $subtittle cadena de texto de subtitulo
     * @param string-array $html puede contener dos valores<ul>
     * <li><b>string:</b> cadena de texto en formato heredoc con el contenido html a incluir en el documento pdf)</li>
     * <li><b>array:</b> arreglo con multiples cadenas de texto en formato heredoc con contenido html; cada cadena será mostrada en una pagina direfente</li>
     * </ul>
     * @param string $file_name nombre del archivo que sera descargado
     * @param string $download puede contener los siguientes valores<ul>
     * <li>'I': mostrar archivo en pantalla</li>
     * <li>'D': descargar archivo</li></ul>
     */
    public function pdf_html($subtittle = "", $html = "", $file_name = "ufps_download", $download = 'D', $font_size = 8) {

        // set default header data
        $this->SetHeaderData("ufps/logo_ufps.png", 15, "Universidad Francisco de Paula Santander", $subtittle, array(0, 0, 0), array(0, 0, 0));
        // set header and footer fonts
        $this->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $this->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $this->setLanguageArray($l);
        }

        // ---------------------------------------------------------
        // set default font subsetting mode
        $this->setFontSubsetting(true);

        // Set font
        // dejavusans is a UTF-8 Unicode font, if you only need to
        // print standard ASCII chars, you can use core fonts like
        // helvetica or times to reduce file size.
        $this->SetFont('helvetica', '', $font_size, '', true);

        // set text shadow effect
        //$this->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

        if (is_array($html)) {
            foreach ($html as $value) {
                $this->AddPage();
                $this->writeHTML($value);
            }
        } else {
            $this->AddPage();
            // Set some content to print
            // Print text using writeHTMLCell()
            $this->writeHTML($html);
        }

        // ---------------------------------------------------------
        // Close and output PDF document
        // This method has several options, check the source code documentation for more information.
        $this->Output($file_name . '.pdf', $download);
    }

}

/* End of file Pdf.php */
/* Location: ./application/libraries/Pdf.php */