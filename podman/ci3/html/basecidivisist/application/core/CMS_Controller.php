<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class CMS_Controller extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if (ENVIRONMENT == 'development') {
            $this->output->enable_profiler(TRUE);
        }
        //Usado por la libreria Database2 para el seguimiento de las consultas ejecutadas
        $this->session->userdata['queries'] = array();
        $this->session->userdata['query_time'] = array();
        $this->session->userdata['query_nrows'] = array();

        //Captura la fecha con el proposito de informar al usuario
        $this->template->set('fecha_actual', $this->ajuste_fecha(date_format(new DateTime(), 'd/m/Y')));


        //Procesos que se deben ejecutar cuando el usuario ha iniciado sesión
        $usuario = $this->session->userdata('usuario_baseCiDivisist');
        $this->unread_notifications = NULL;

        if ($usuario) {
            $this->usuario = $usuario;
            $this->template->set('informacion_usuario', $usuario);
            //Notificaciones
//            $this->load->model('notifications_model');
//            $this->unread_notifications = $this->notifications_model->unreaded_notifications($usuario->CODIGO);
        }
        $this->template->set('unread_notifications', $this->unread_notifications);
    }

    /**
     * Función utilizada para validar el texto de una imagen captcha; compara el parametro recibido 
     * contra un valor existente como session flash. Puede ser utilizada dentro de las reglas de 
     * form validation.
     * Retorna true si el texto coincide, False de lo contrario.
     * @param String $texto_cap 
     * @return boolean
     */
    public function validar_captcha($texto_cap = "") {
        if (!$this->session->flashdata('cap')) {
            return FALSE;
        }
        if ($texto_cap != $this->session->flashdata('cap')['word']) {
            $msj = 'El texto no coincide con la imagen de verificación';
            $this->form_validation->set_message('validar_captcha', $msj);
            $this->template->set('cap', $this->_crearCaptcha());
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Función para crear un objeto captcha, este objeto contiene como atributos la ruta de la imagen
     * de captcha y la cadena de texto equivalente; esta cadena se almacena como Flash Session para 
     * ser comparada en la siguiente página por el metodo <b>validar_captcha</b>
     * @return Object
     */
    public function _crearCaptcha() {
        $this->load->helper('captcha');
        $vals = array(
            'img_path' => './captcha/',
            'img_url' => site_url('captcha') . '/',
            'font_path' => "./system/fonts/Gravity-Regular.otf",
            'img_width' => '320',
            'img_height' => 50,
            'expiration' => 7200,
            'word_length' => 3,
            'font_size' => 24,
            'img_id' => 'Imageid',
            'pool' => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            // White background and border, black text and red grid
            'colors' => array(
                'background' => array(255, 255, 255),
                'border' => array(210, 214, 222),
                'text' => array(0, 0, 0),
                'grid' => array(255, 40, 40)
            )
        );
        $cap = create_captcha($vals);
        $this->session->set_flashdata('cap', $cap);
        return $cap;
    }

    public function getConfiguracion($id_configuracion) {
        $usuario = $this->session->userdata('usuario_baseCiDivisist');
        return isset($usuario->CONFIGURACION[$id_configuracion]) && $usuario->CONFIGURACION[$id_configuracion] == 1 ? TRUE : FALSE;
    }

    /**
     * Metodo para imprimir en pantalla el valor de un objeto, arreglo o variable. Solo desplegará la información si el 
     * entorno en el que se ejecuta es <b>development</b>.
     * @param type $var
     */
    public function dump($var) {
        if (ENVIRONMENT == 'development') {
            echo "<pre>";
            echo var_dump($var);
            echo "</pre><hr>";
        }
    }

    /**
     * Función utilizada como callback en las reglas de form validatio. Valida que una cadena de texto sea
     * alfabética y permite incluir 'ñÑ' y acentos en las vocales.
     * 
     * @param String $str
     * @return boolean
     */
    public function alpha_es($str) {
        if (!preg_match("/^([ a-zA-ZñÑáéíóú])+$/i", $str)) {
            $this->form_validation->set_message('alpha_es', 'El campo {field} solo puede contener caracteres alfabéticos');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Funcion para ser utilizada en validaciones de formularios, 
     * retorna TRUE si la fecha coincide con el formado DD/MM/YYY (oracle) d/m/Y (php),
     * FALSE de lo contrario
     * @param string $date
     * @return boolean
     */
    public function valid_date($date) {
        if (date_create_from_format('d/m/Y', $date)) {
            return TRUE;
        } else {
            $this->form_validation->set_message('valid_date', '%s no cumple con el formato aceptado.');
            return FALSE;
        }
    }

    /**
     * dado un arreglo con datos de la consulta sql, retorna una tabla html.
     * 
     * @param array $info - arreglo que contiene el numero de registros devueltos y el fetch de la consulta realizada
     * @param boolean $utf8_decode - TRUE si se necesita aplicar utf8_decode al corenido de la tabla (valor por defecto true)
     * @param array ws - array con tamaños personalizados para las colomnas dada determinada key
     * @param array $frags - Arreglo con los fragmentos en los que se desea partir la tabla.
     * @param int frag - una vez termine de evaluar el array de frags, este sera el numero máximo de cada fragmento.
     */
    public function table_html($info, $utf8_decode = TRUE, $ws = array(), $frags = array(), $max_frag = 0) {
        $nrows = $info[0];
        $fetch = $info[1];
        $keys = array_keys($fetch);

        if (!empty($frags)) {
            $return = array();
            $i = 0;
            $rest = $nrows;
            foreach ($frags as $frag) {
                if ($frag <= $rest) {
                    $html = $this->_draw_table_html($i, ($frag + $i), $keys, $fetch, $utf8_decode, $ws);
                    $return[] = $html;
                    $rest = $rest - $frag;
                    $i = ($frag + $i);
                }
            }
            if ($rest > 0) {
                while ($max_frag < $rest && $max_frag != 0) {
                    $frag = $max_frag;
                    $html = $this->_draw_table_html($i, ($frag + $i), $keys, $fetch, $utf8_decode, $ws);
                    $return[] = $html;
                    $rest = $rest - $frag;
                    $i = ($frag + $i);
                }
                $html = $this->_draw_table_html($i, $nrows, $keys, $fetch, $utf8_decode, $ws);
                $return[] = $html;
            }
            return $return;
        } else {
            $html = $this->_draw_table_html(0, $nrows, $keys, $fetch, $utf8_decode, $ws);
            return $html;
        }
    }

    /**
     * Función privada utilizada por <b>table_html</b> para la construcción automática de tablas html.
     * 
     * @param type $ini
     * @param type $nrows
     * @param type $keys
     * @param type $fetch
     * @param type $utf8_decode
     * @param type $ws
     * @return string
     */
    private function _draw_table_html($ini, $nrows, $keys, $fetch, $utf8_decode, $ws) {
        $html = '<table border="1" cellpadding="2" align="center" >'
                . '<tr bgcolor="#cccccc">';
        foreach ($keys as $key) {
            $titulo = ucfirst(strtolower(str_replace("_", " ", $key)));
            $w = "";
            if (isset($ws[$key])) {
                $w = 'width="' . $ws[$key] . '"';
            }
            $html .= "<td $w ><strong>$titulo</strong></td>";
        }
        $html .= "</tr>";
        for ($i = $ini; $i < $nrows; $i++) {
            $html .= "<tr>";
            foreach ($keys as $key) {
                $html .= "<td>";
                if ($utf8_decode) {
                    $html .= utf8_decode($fetch[$key][$i]);
                } else {
                    $html .= $fetch[$key][$i];
                }
                $html .= "</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</table>";
        return $html;
    }

    /**
     * Función empleada para desplegar en el navegador un documento PDF que contiene un recibo en pdf
     * (Utilizado para generar liquidaciones). La librería utilizada para la generación de este recibo con
     * código de barras esta basada en FPDF, para mayor información consulte la documentación oficial.
     * 
     * Ejemplo de construcción de los arreglos de configuración:
     * 
     * $info_recibo = array(
     *      'codigo_recibo' => ($codigo_recibo ? $codigo_recibo : "-"),
     *      'codigo_persona' => $codigo,
     *      'nombre_persona' => utf8_decode($info_persona->NOMBRE),
     *      'tipo_persona' => utf8_decode($info_persona->TIPO),
     *      'nombre_programa' => utf8_decode($info_persona->PROGRAMA),
     *      'nombre_banco' => ( $nombre_banco ? utf8_decode($nombre_banco) : " - " ),
     *      'numero_cuenta' => ($numero_cuenta ? $numero_cuenta : " - "),
     *     'tipo_pago' => ($tipo_pago ? $tipo_pago : " - "),
     *       'fecha_limite' => ($fecha_limite_ordinario ? $fecha_limite_ordinario : " - "),
     *      'msj1_recibo' => "",
     *      'msj2_recibo' => "",
     *      'msj_sub_barras' => 'PAGO SOLO EN EFECTIVO',
     *      'msj_arriba_barras' => ('PAGUE EN BANCO: ' . $nombre_banco ),
     *      'titulo_recibo' => utf8_decode("LIQUIDACIÓN DERECHOS DE MATRÍCULA - "),
     *      'periodo_recibo' => ($periodo_inscripcion ? utf8_decode($periodo_inscripcion) : " - "),
     *      'programa_recibo' => ($info_persona->PROGRAMA ? utf8_decode($info_persona->PROGRAMA) : " - "),
     *      'informacion_pago' => $informacion_pago
     *  );
     * 
     * $info_pdf = array(
     *      'celdas' => 0,
     *      'linea' => 2,
     *      'alineacion_texto' => 'A',
     *      'alineacion_texto2' => 'R',
     *      'alineacion_texto3' => 'C',
     *      'tipo_letra' => 'helvetica',
     *      'tamano_letra' => 8,
     *      'ruta_imagen_fondo' => 'assets/img/liquidacion/666F726D61746F.jpg',
     *      'nombre_archivo' => 'recibo.pdf',
     *      'output_type' => (($download == 'download') ? 'D' : 'I')
     *  );
     * 
     * @param array $info_recibo - Parametros de creación del documento PDF
     * @param array $info_pdf -¨Parametros del recibo de pago
     */
    public function recibo_pdf($info_recibo, $info_pdf) {

        //para ajustar textos que se salgan de las celdas
        $this->load->helper('text');

        // parametro fechas
        $date = date_create_from_format("d/m/Y", $info_recibo ['fecha_limite']);
        $fecha = $this->ajuste_fecha($info_recibo ['fecha_limite']);
        $fecha_hoy = $this->ajuste_fecha(date_format(new DateTime(), 'd/m/Y'));

        // informacion del pago (conceptos y valores)
        $conceptos = &$info_recibo ['informacion_pago'] ['DESCRIPCION'];
        $valores = &$info_recibo ['informacion_pago'] ['VALOR'];
        $total = 0;
        for ($f = 0; $f < count($valores); $f ++) {
            $total = round($total + $valores [$f]);
        }

        // parametros codigos de barras
        $tam_x_barras = 90;
        $tam_y_barras = 15;
        $barras1 = '415' . '7709998005938' . '8020' . str_pad($info_recibo ['codigo_persona'], 10, '0', STR_PAD_LEFT) . '3900' . str_pad($total, 8, '0', STR_PAD_LEFT) . '96' . date_format($date, 'Ymd');
        $barras_numero1 = '(415)' . '7709998005938' . '(8020)' . str_pad($info_recibo ['codigo_persona'], 10, '0', STR_PAD_LEFT) . '(3900)' . str_pad($total, 8, '0', STR_PAD_LEFT) . '(96)' . date_format($date, 'Ymd');

        // Cargue de la libreria pdf y nueva pagina
        $this->load->library('pdf');
        $this->pdf->AddPage();

        // se ubica la imagen de fondo con formato para pago ordinario
        $this->pdf->Image($info_pdf ['ruta_imagen_fondo'], 0, 0, 215.9);

        // se ajusta el tipo de fuenta y el tamaño
        $this->pdf->SetFont($info_pdf ['tipo_letra'], '', $info_pdf ['tamano_letra']);

        /*         * ****************************************CODIGO DE RECIBO, FECHA Y TITULO*************************************** */
        // escribir el codigo del recibo
        $this->pdf->SetXY(146, 25);
        $this->pdf->Cell(50, 4, $fecha_hoy, $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto3']);
        $this->pdf->SetXY(146, 33);
        $this->pdf->Cell(50, 4, $info_recibo ['codigo_recibo'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto3']);
        $this->pdf->SetXY(21, 49);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], 'B', $info_pdf ['tamano_letra'] + 1);
        $this->pdf->Cell(175, 4, ($info_recibo ['titulo_recibo'] . $info_recibo ['periodo_recibo']), $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto3']);
        $this->pdf->Cell(175, 4, $info_recibo ['programa_recibo'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto3']);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], '', $info_pdf ['tamano_letra']);

        /*         * ****************************************SECCION DE LA PERSONA************************************************* */
        // escribir la informacion de la persona
        $this->pdf->SetXY(47, 61.3);
        $this->pdf->Cell(74, 4, $info_recibo ['nombre_persona'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);
        $this->pdf->Cell(74, 4, $info_recibo ['tipo_persona'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);
        $this->pdf->Cell(74, 4, $info_recibo ['codigo_persona'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);

        // escribir informacion del banco
        $this->pdf->SetXY(146, 61.3);
        $this->pdf->Cell(50, 4, $info_recibo ['nombre_banco'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);
        $this->pdf->Cell(50, 4, $info_recibo ['numero_cuenta'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);

        /*         * ****************************************SECCION PAGO ORDINARIO************************************************* */
        // escribir informacion del pago ordinario
        $this->pdf->SetXY(47, 81.8);
        $this->pdf->Cell(50, 4, $info_recibo ['tipo_pago'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);
        $this->pdf->Cell(50, 4, $fecha, $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);

        // escribir informacion conceptos pago ordinario
        $this->pdf->SetXY(22, 94);
        for ($f = 0; $f < count($conceptos); $f ++) {
            $this->pdf->Cell(60, 4, ucwords(utf8_decode($conceptos [$f])), $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);
        }

        // escribir informacion valores pago ordinario
        $this->pdf->SetXY(82, 94);
        for ($f = 0; $f < count($valores); $f ++) {
            $this->pdf->Cell(16, 4, '$' . number_format($valores [$f]), $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto2']);
        }

        // escribir total pago ordinario
        $this->pdf->SetXY(82, 138.5);
        $this->pdf->Cell(16, 4, '$' . number_format($total), $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto2']);

        // imprimir codigo de barras pago ordinario
        $this->pdf->SetXY(102, 84.8);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], 'B', $info_pdf ['tamano_letra'] + 5);
        $this->pdf->Cell(91, 5, $info_recibo ['msj_arriba_barras'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto3']);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], '', $info_pdf ['tamano_letra']);

        $this->pdf->Code128(102, 109, $barras1, $tam_x_barras, $tam_y_barras, array(
            1,
            16,
            22
        ));
        $this->pdf->SetXY(102, 125);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], '', $info_pdf ['tamano_letra'] - 2);
        $this->pdf->Cell(91, 5, $barras_numero1, $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto3']);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], '', $info_pdf ['tamano_letra']);

        $this->pdf->SetXY(102, 135);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], 'B', $info_pdf ['tamano_letra'] + 1);
        $this->pdf->Cell(91, 5, $info_recibo ['msj_sub_barras'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto3']);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], '', $info_pdf ['tamano_letra']);

        // informacion fecha de inclucion de materias
        $this->pdf->SetXY(21, 178);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], 'B', $info_pdf ['tamano_letra'] + 1);
        $this->pdf->Cell(175, 4, $info_recibo ['msj1_recibo'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto3']);
        $this->pdf->Cell(175, 4, $info_recibo ['msj2_recibo'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto3']);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], '', $info_pdf ['tamano_letra']);

        /*         * ****************************************SECCION DEL BANCO************************************************* */

        // escribir la informacion de la persona
        $this->pdf->SetXY(47, 203.6);
        $this->pdf->Cell(74, 4, $info_recibo ['nombre_persona'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);
        $this->pdf->Cell(74, 4, $info_recibo ['tipo_persona'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);
        $this->pdf->Cell(74, 4, $info_recibo ['codigo_persona'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);

        // escribir informacion del banco
        $this->pdf->SetXY(146, 203.6);
        $this->pdf->Cell(50, 4, $info_recibo ['nombre_banco'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);
        $this->pdf->Cell(50, 4, $info_recibo ['numero_cuenta'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);

        /*         * ****************************************SECCION PAGO ORDINARIO************************************************* */
        // escribir informacion de pago ordinario
        $this->pdf->SetXY(120, 215.5);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], 'B', $info_pdf ['tamano_letra']);
        $this->pdf->Cell(50, 4, 'Tipo de pago: ', $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], '', $info_pdf ['tamano_letra']);
        $this->pdf->SetXY(169, 215.5);
//        $this->pdf->Cell(50, 4, $this->get_siglas($info_recibo ['tipo_pago']), $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);
        $this->pdf->Cell(50, 4, $info_recibo ['tipo_pago'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);
        $this->pdf->SetXY(169, 220);
        $this->pdf->Cell(27.4, 4, date_format($date, 'd/m/Y'), $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], '', $info_pdf ['tamano_letra'] + 4);
        $this->pdf->SetXY(169, 235);
        $this->pdf->Cell(27.4, 4, '$' . number_format($total), $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto']);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], '', $info_pdf ['tamano_letra']);

        // imprimir codigo de barras pago ordinario
        $this->pdf->Code128(26, 219.4, $barras1, $tam_x_barras, $tam_y_barras, array(
            1,
            16,
            22
        ));
        $this->pdf->SetXY(25.7, 234.4);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], '', $info_pdf ['tamano_letra'] - 2);
        $this->pdf->Cell(91, 5, $barras_numero1, $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto3']);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], '', $info_pdf ['tamano_letra']);

        $this->pdf->SetXY(25.7, 239.4);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], 'B', $info_pdf ['tamano_letra'] + 1);
        $this->pdf->Cell(91, 5, $info_recibo ['msj_sub_barras'], $info_pdf ['celdas'], $info_pdf ['linea'], $info_pdf ['alineacion_texto3']);
        $this->pdf->SetFont($info_pdf ['tipo_letra'], '', $info_pdf ['tamano_letra']);

        $this->pdf->Output($info_pdf ['nombre_archivo'], $info_pdf ['output_type']);
    }

    /**
     * Función que recibe una fecha en formato DD/MM/YYY y la retorna en un formato legible.
     * 
     * Ejemplo:
     * recibe   01/01/2016
     * retorna  Viernes 1 de Enero de 2016
     * 
     * @param String $fecha
     * @return string
     */
    public function ajuste_fecha($fecha) {

        if (!$this->valid_date($fecha)) {
            return "fecha invalida";
        }

        $dias = array('Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado');
        $meses = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiempre', 'Octubre', 'Noviembre', 'Diciembre');
        $date = date_create_from_format("d/m/Y", $fecha);
        $wday = $date->format('w');
        $tmp = explode('/', $fecha);
        return sprintf("%s %s de %s de %s", $dias[$wday], $tmp[0], $meses[$tmp[1] - 1], $tmp[2]);
    }

    /**
     * Función que recibe una cadena de texto y retorna sus siglas.
     * 
     * Ejemplo:
     * recibe:      División de Sistemas UFPS
     * returna:     DDSU
     * 
     * @param type $cadena
     * @return type
     */
    public function get_siglas($cadena) {
        $res = '';
        $captura = TRUE;
        for ($i = 0; $i < strlen($cadena); $i++) {
            if ($captura) {
                $res.= $cadena{$i};
            }
            $captura = FALSE;
            if ($cadena{$i} == ' ') {
                $captura = TRUE;
            }
        }
        return $res;
    }

    /**
     * Función que utiliza una plantilla estandar de la institución para enviar correos electronicos a cualquier destinatario.
     * 
     * @param String $tittle - Titulo del Cuerpo del mensaje
     * @param String $subject - Asunto del mensaje
     * @param String $body - Contenido del Cuerpo del mensaje.
     * @param String $link - Enlace del cuerpo del mensaje (Usado cuando se envian correos de activación de usuarios o recuperación de claves)
     * @param String $to - A quien se le envia el mensaje.
     * @param String $from - Quien envia el mensaje 
     * 
     * Generalmente se utiliza un correo que contenga la palabra "noreply", 
     * este correo puede existir o no. Debe ser del dominio @ufps.edu.co. 
     * Ejemplo: notifications-noreply@ufps.edu.co
     * 
     * @param boolean $template - Por defecto es TRUE, en caso de ser FALSE no utilizada la plantilla institucional
     * @return boolean - TRUE: Mensaje enviado con éxito, FALSE  de lo contrario.
     */
    public function send_email($tittle = "", $subject = "", $body = "", $link = "", $to = NULL, $from = NULL, $template = TRUE) {

        if (!$to && !$from) {
            return FALSE;
        }

        $this->load->library("email");

        $configGmail = $this->_mailConfigPostfix();
        $this->email->initialize($configGmail);
        $this->email->clear(TRUE);
//        $this->dump(base_url("assets/email/email_tpl.html"));
//        $this->dump(file_get_contents(base_url("assets/email/email_tpl.html")));
        if ($template) {
            $html = file_get_contents(base_url("assets/email/email_tpl.php"));
            $html = str_replace("@tittle-email", $tittle, $html);
            $html = str_replace("@body-email", $body, $html);
            $html = str_replace("@link-email", $link, $html);
        } else {
            $html = $body;
        }

        $this->email->from($from, 'División de Sistemas UFPS');
        $this->email->to(strtolower($to));

        $this->email->subject($subject);
        $this->email->message($html);
        $send = $this->email->send();
//        $this->dump($this->email->print_debugger());
        return $send;
    }

    private function _mailConfigGmail($credentials = array()) {
        if (empty($credentials)) {
            exit;
        }

        $configMail = array(
            'protocol' => 'smtp',
            'smtp_host' => 'ssl://smtp.gmail.com',
            'smtp_port' => 465,
            'mailtype' => 'html',
            'charset' => 'utf-8',
            'newline' => "\r\n"
        );

        $configMail['smtp_user'] = $credentials['smtp_user'];
        $configMail['smtp_pass'] = $credentials['smtp_pass'];

        return $configMail;
    }

    private function _mailConfigPostfix() {
        $mailConfig = array(
            'protocol' => 'smtp',
            'smtp_host' => 'ssl://192.168.13.164',
            'smtp_port' => 465,
            'mailtype' => 'html',
            'charset' => 'utf-8',
            'newline' => "\r\n"
        );

        if (ENVIRONMENT == 'development') {
            $mailConfig['smtp_user'] = "Divisist180";
            $mailConfig['smtp_pass'] = "C0rre0M4sib0180";
        } else {
            $mailConfig['smtp_user'] = "Divisist8";
            $mailConfig['smtp_pass'] = "C0rre0M4sib0";
        }

        return $mailConfig;
    }

    /**
     * Función que realiza un registro de solicitud de cierre de sesión y la da por terminada.
     * Se utiliza en este controlador por que dentro de la aplicación pueden ocurrir eventos que requieran el cierre
     * de la sesión del usuario.
     * 
     * @param boolean $redirect - TRUE para redireccionar al usuario a la página principal, FALSE de lo contrario.
     */
    public function cerrarSesion($redirect = TRUE) {
        if ($this->session->userdata('usuario_baseCiDivisist')) {
            $this->registroSesion($this->session->userdata('usuario_baseCiDivisist')->CODIGO, TRUE, 'O');
            $this->session->sess_destroy();
        }
        if ($redirect) {
            redirect();
        }
    }

    /**
     * Función que realiza un registro de la función del usuario con fines de auditar las conexiones realizadas en el portal.
     * 
     * @param String $codigo - Código del usuario
     * @param int $estado - 1 o 0 dependiendo de si se realizo con exito la operación o no.
     * @param Char $proceso - I o O dependiendo si se realiza un login o un logout.
     * @return boolean - TRUE si se registra la operaicón, FALSE de lo contrario.
     */
    public function registroSesion($codigo, $estado = 1, $proceso = 'I') {
        if (ENVIRONMENT == 'development') {
            return true;
        }
        $this->load->library('user_agent');
        $this->load->model('sesion_model');
        $session = array(
            'SESSION_ID' => session_id(),
            'SESSION_EXPIRATION' => $this->config->item('sess_expiration'),
            'ESTADO' => $estado,
            'PROCESO' => $proceso,
            'COD_CARRERA' => substr($codigo, 0, 3),
            'COD_ALUMNO' => substr($codigo, 3),
            'IP' => $_SERVER['REMOTE_ADDR'],
            'AGENT' => $this->agent->agent,
            'PLATFORM' => $this->agent->platform,
            'BROWSER' => $this->agent->browser,
            'BROWSER_VERSION' => $this->agent->version,
            'MOBILE' => $this->agent->mobile,
            'ROBOT' => $this->agent->robot
        );
        $this->sesion_model->registrarSesion($session);
    }

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */