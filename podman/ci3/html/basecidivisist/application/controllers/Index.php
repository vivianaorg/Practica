<?php

//CONTROLADOR DE EJEMPLO CON ALGUNOS METODOS UTILES PARA RESTABLECIMIENTO DE CLAVES DE USUARIO
class Index extends CMS_Controller {

    function __construct() {
        parent::__construct();

        $this->template->add_js('js/views/login/login.min');
        $this->template->add_js('plugins/backstretch/jquery.backstretch.min');
        $this->template->add_js('js/views/index/one.app.min');
        $this->template->add_js('js/views/index/main.min');
    }

    public function login() {

        if (isset($_SESSION['estudiante_divisist'])) {
            redirect('estudiante/estado_matricula');
        }

        if (!$this->input->post()) {
            $this->load->model('datos_model');
            $noticias = $this->datos_model->get_Noticia();
            $this->template->set('noticias', $noticias);
        } else {
            $this->template->set('noticias', FALSE);
        }

        $this->load->model('estudiante_model');
        $this->load->model('habeas_data_model');

        if ($this->input->post('login') == 1) {
            $this->load->library('form_validation');
            $rules = [
                [
                    'field' => 'usuario',
                    'label' => 'Código',
                    'rules' => 'trim|required|numeric|max_length[7]|min_length[7]'
                ],
                [
                    'field' => 'password',
                    'label' => 'Contraseña',
                    'rules' => 'trim|required|max_length[16]|min_length[8]'
                ]
            ];

            $this->load->model('sesion_model');
            $codigoAlumno = $this->input->post('usuario');
            $intentosFallidos = $this->_intentosFallidos($codigoAlumno);

            if ($intentosFallidos > 5) {
                if ($this->input->post('TEXTO_CAP')) {
                    array_push($rules, array(
                        'field' => 'TEXTO_CAP',
                        'label' => 'CAPTCHA',
                        'rules' => 'required|alpha_numeric|max_length[3]|callback_validar_captcha'
                    ));
                    $this->_login($rules, $intentosFallidos);
                } else {
                    $this->template->add_message(
                            array('warning' => "Se han registrado {$intentosFallidos} intentos fallidos de conexión, "
                                . "para iniciar sesión deberá ingresar el contenido de la imagen de "
                                . "verificación como medida de seguridad."));
                    $this->template->set('cap', $this->_crearCaptcha());
                }
            } else {
                $this->_login($rules);
            }
        }

        $this->template->set_template('login');
        $this->template->add_css('plugins/line-icons/line-icons');
        $this->template->add_css('css/style');

        $this->template->set('page_tittle', "Divisist 2.0");
        $this->template->set('page_keywords', "ufps, universidad, francisco, de paula, santander, cucuta, colombia,carreras,ingenierias, pregrados,norte de santander, especializaciones, diplomados,cursos,oriente, matricula, notas, división, sistemas");
        $this->template->set('page_descripion', "Portal Académico de la Universidad Francisco de Paula Santander - Cúcuta, Norte de Santander");

        $this->load->helper('form');
        $this->template->set('item_nav_active', '');
        $this->template->render('index/login');
    }

    private function _login($rules, $intentosFallidos = 0) {
        $this->form_validation->set_rules($rules);
        if ($this->form_validation->run() === TRUE) {
            $codigoAlumno = $this->input->post('usuario');
            $claveAlumno = bin2hex($this->input->post('password'));
            $user = FALSE;
            if (ENVIRONMENT == 'development') {
                $login = ($claveAlumno == "436c347633533363523330353930" || $this->estudiante_model->validarlogin($codigoAlumno, $claveAlumno)) ? 1 : 0;
            } else {
                $login = $this->estudiante_model->validarlogin($codigoAlumno, $claveAlumno);
            }
            if (!$login) {
                $this->registroSesion($codigoAlumno, 0);
                $msj = "Usuario o contraseña invalidos";
                if ($intentosFallidos > 5) {
                    $msj.= ". Se han registrado {$intentosFallidos} intentos fallidos de conexión";
                }
                $this->template->add_message(['error' => $msj]);
            } else {
                $user = $this->estudiante_model->get_datos($codigoAlumno);
            }
            if ($user) {
                //validar sesion externa
                if (substr($_SERVER['REMOTE_ADDR'], 0, 10) != '172.18.22.' && !$this->_validarSesionExterna(substr($codigoAlumno, 0, 3), substr($codigoAlumno, 3))) {
                    $this->registroSesion($codigoAlumno, 2);
                    $msj = "Inicio de sesión no permitido. Ya ha iniciado sesión en otro navegador, deberá esperar a que esta expire.";
                    $this->template->set_flash_message(array($msj), "error");
                    redirect("index/login");
                }
                //verificar autorización de manejo de datos personales
                if (!$this->habeas_data_model->verificarHabeasData(substr($user->CODIGO, 0, 3), substr($user->CODIGO, 3))) {
                    $this->session->set_flashdata('codigo_hd', $user->CODIGO);
                    $this->session->set_flashdata('nombres_hd', ($user->NOMBRE_COMPLETO));
                    $this->session->set_flashdata('documento_hd', $user->DOCUMENTO);
                    redirect('index/habeas_data');
                }
                $this->registroSesion($codigoAlumno);
                if ($intentosFallidos > 5) {
                    $notification = array(
                        'CODIGO_RECEPTOR' => $user->CODIGO,
                        'EMISOR' => 'División de Sistemas UFPS',
                        'MENSAJE' => "Se han registrado {$intentosFallidos} intentos fallidos de conexión antes de la última conexión exitosa. Recuerde que puede restablecer su clave personal en cualquier momento en la opción datos personales del módulo de información estudiantil."
                    );
                    $this->load->model('notifications_model');
                    $this->notifications_model->addNotification($notification);
                }
                $this->_configuracionAlumno($user);
                $this->session->set_userdata('estudiante_divisist', $user);
                redirect('estudiante/estado_matricula');
            }
        }
    }

    private function _intentosFallidos($codigoAlumno) {
        //valida que el código sea númerico y de 7 dígitos
        $codigoAlumno = (int) $codigoAlumno;
        if ($codigoAlumno < 999999 && $codigoAlumno > 9999999) {
            return 0;
        }
        $this->load->model('sesion_model');
        $intentosFallidos = $this->sesion_model->intentosFallidos(substr($codigoAlumno, 0, 3), substr($codigoAlumno, 3));
        return $intentosFallidos;
    }

    private function _mailLogin($to) {
        $this->load->library('user_agent');
        $platform = $this->agent->platform;
        $browser = $this->agent->browser;
        $version = $this->agent->version;
        $body = "Se ha registrado un nuevo inicio de sesión en el portal web Divisist2 desde la plataforma {$platform} con navegador {$browser} versión {$version}. "
                . "Si desea dejar de recibir estas notificaciones dirijase al módulo configuración del portal. Por favor no responda este mensaje."
                . "\r\n \r\n Cordialmente, el equipo de la División de Sistemas UFPS.";
        $this->send_email("", "Inicio de sesión Divisist2.0", $body, "", $to, "notifications-noreply@ufps.edu.co", FALSE);
    }

    /**
     * 
     * @param type $user
     * @return type
     */
    private function _configuracionAlumno(&$user) {
        $this->load->model('configuracion_model');
        $configuracionAlumno = $this->configuracion_model->getConfiguracion($user->CODIGO);

        if (!$configuracionAlumno) {
            $this->configuracion_model->cargarConfiguracion($user->COD_CARRERA, $user->COD_ALUMNO);
            $configuracionAlumno = $this->configuracion_model->getConfiguracion($user->CODIGO);
        }

        $user->CONFIGURACION = $configuracionAlumno;
    }

    private function _validarSesionExterna($codCarrera, $codAlumno) {
        $this->load->model('sesion_model');
        return ($this->sesion_model->validarSesionExterna($codCarrera, $codAlumno, $this->config->item('sess_expiration')));
    }

    public function logout() {
        $this->cerrarSesion();
    }

    public function restablecer_clave() {

        if (isset($_SESSION['estudiante_divisist'])) {
            redirect('estudiante/estado_matricula');
        }

        if ($this->input->post('register') == 1) {
            $this->load->model('estudiante_model');
            $this->load->library('form_validation');
            $rules = [
                [
                    'field' => 'CODIGO',
                    'label' => 'Código',
                    'rules' => 'required|numeric|exact_length[7]'
                ],
                [
                    'field' => 'EMAIL',
                    'label' => 'Email',
                    'rules' => 'required|valid_email|max_length[100]'
                ],
                [
                    'field' => 'TEXTO_CAP',
                    'label' => 'Texto captcha',
                    'rules' => 'required|alpha_numeric|max_length[3]'
                ]
            ];
            $this->form_validation->set_rules($rules);
            if ($this->form_validation->run()) {

                $info_cap = array(
                    'CODIGO' => $this->input->post('CODIGO'),
                    'EMAIL' => strtolower($this->input->post('EMAIL')),
                    'TEXTO_CAP' => $this->input->post('TEXTO_CAP')
                );

                if ($info_cap ['TEXTO_CAP'] != $this->session->flashdata('cap')['word']) {
                    $this->template->add_message('El texto no coincide con la imagen del captcha', 'error');
                } else {
                    $user = $this->estudiante_model->getByEmailCodigo($info_cap ['CODIGO'], $info_cap ['EMAIL']);
                    if ($user) {
                        $this->_restablecer_clave($info_cap['CODIGO'], $info_cap['EMAIL']);
                    } else {
                        $this->template->add_message('La información de contacto es incorrecta.', 'error');
                    }
                }
            }
        }

        $this->template->set_template('login');
        $this->load->helper('form');
        $this->template->set('item_nav_active', '');
        $this->template->set('cap', $this->_crearCaptcha());
        $this->template->render('index/restablecer_clave');
    }

    public function restablecer_clave_alumno() {

        $codigo = $this->session->flashdata('codigo');
        $email = $this->session->flashdata('email');

        if (isset($_SESSION['estudiante_divisist'])) {
            $this->session->sess_destroy();
        }

        if (!$codigo || !$email) {
            redirect();
        }

        $this->_restablecer_clave($codigo, $email);
    }

    private function _restablecer_clave($codigo, $email) {
        $this->load->model("cambio_clave_model");

        $codCarrera = substr($codigo, 0, 3);
        $codAlumno = substr($codigo, 3);
        $sessionId = session_id();
        $llave = bin2hex(openssl_random_pseudo_bytes(16));
        $email_destino = strtolower($email);
        $email_origen = 'notificatios-noreply@ufps.edu.co';

        // valida que no haya intentado en menos de una hora
        $last_time = $this->cambio_clave_model->verificarTiempoRC($codCarrera, $codAlumno);

        if ($last_time) {
            $this->template->set_flash_message(array('Ya ha solicitado recuperar su cuenta en la última hora, debe esperar al menos 1 hora para volver a intentarlo.'), 'error');
        } else {
            // enviar correo
            $mail = $this->correo_restablecer_clave($llave, $codigo, $email_destino, $email_origen);
            if ($mail) {
                $insert_rc = $this->cambio_clave_model->insertRecuperarClave(array(
                    'COD_CARRERA' => $codCarrera,
                    'COD_ALUMNO' => $codAlumno,
                    'LLAVE' => $llave,
                    'EMAIL_ORIGEN' => $email_origen,
                    'EMAIL_DESTINO' => $email_destino,
                    'SESSION_ID' => $sessionId
                ));
                $this->template->set_flash_message(array("Se ha enviado un correo electrónico a {$email_destino} para continuar con el proceso de restablecimiento de su contraseña."), 'info');
            } else {
                $this->template->set_flash_message(array('Ocurrió un error al enviar su solicitud de recuperación'), 'error');
            }
        }
        redirect("index/login");
    }

    private function correo_restablecer_clave($uniqid, $codigo, $to, $from, $num_mail = NULL) {
        $this->load->library('encryption');
        $key_codigo = bin2hex($this->encryption->encrypt($codigo));

        $tittle = "Restablecimiento de clave personal";
        $subject = "Restablecimiento de clave personal - Portal web Divisist 2.0";
        $body = "<p>Usted ha solicitado por medio del portal web Divisist 2.0 el restablecimiento de su contraseña.</p>";
        $body .= "<p>Ingrese en el siguiente enlace para continuar con esta operación. Cabe aclarar que este enlace solo será valido una vez y caducará en 24 horas desde su generación.</p>";
        $link = "<a href = '" . base_url('index/cambiar_clave') . "/{$uniqid}/{$key_codigo}'>Restablecer clave</a>";

//        $from = "info_0{$num_mail}@ufps.edu.co";
//      'smtp_pass' => 'info20141'

        return $this->send_email($tittle, $subject, $body, $link, $to, $from);
    }

    public function cambiar_clave($llave = NULL, $codigo_key = NULL) {

        if (isset($_SESSION['estudiante_divisist'])) {
            $this->session->sess_destroy();
        }

        if (!$llave || !$codigo_key || !ctype_xdigit($llave) || !ctype_xdigit($codigo_key)) {
            $this->template->set_flash_message(array("El enlace al que esta intentando acceder no es valido."), 'error');
        }

        $this->load->library('encryption');
        $codigo = $this->encryption->decrypt(hex2bin($codigo_key));
        $codCarrera = substr($codigo, 0, 3);
        $codAlumno = substr($codigo, 3);

        $this->load->model('cambio_clave_model');
        $verificar_rec = $this->cambio_clave_model->verificarRecuperarClave($codCarrera, $codAlumno, $llave);

        if ($verificar_rec) {
            if ($verificar_rec->ESTADO == 1) {
                $this->template->set_flash_message(array(
                    'error' => 'Este enlace ya fue utilizado previamente.'
                        ), 'info');
                redirect("index/login");
            } else {
                // Si los datos de la url son validos renderiza el formulario ó lo valida.

                if ($this->input->post('register') == 1 && $this->input->post('level') != 1) {
                    $this->template->add_message(array("error" => "La contraseña no debe ser debil, por favor intente una mas segura."));
                }
                if ($this->input->post('register') == 1 && $this->input->post('level') == 1) {
                    $this->load->library('form_validation');
                    $rules = [
                        [
                            'field' => 'PASSWORD',
                            'label' => 'Clave',
                            'rules' => 'required|max_length[16]|min_length[8]'
                        ],
                        [
                            'field' => 'PASSWORD2',
                            'label' => 'Confirmacion clave',
                            'rules' => 'required|max_length[16]|min_length[8]|matches[PASSWORD]'
                        ]
                    ];
                    $this->form_validation->set_rules($rules);
                    if ($this->form_validation->run()) {
                        $codCarrera = $verificar_rec->COD_CARRERA;
                        $codAlumno = $verificar_rec->COD_ALUMNO;
                        $clavehex = bin2hex($this->input->post('PASSWORD'));
                        // actualización de clave
                        $this->cambio_clave_model->actualizarClave($codCarrera, $codAlumno, $clavehex, $llave);
                        $this->template->set_flash_message(array(
                            'success' => 'Se ha reestablecido exitosamente su clave'
                                ), 'success');
                        redirect("index/login");
                    }
                }

                $this->template->add_css('plugins/pschecker/css/style');
                $this->template->add_js('plugins/pschecker/script.min');
                $this->template->add_js('plugins/pschecker/js/pschecker.min');

                $this->template->set_template('login');
                $this->load->helper('form');
                $this->template->set('item_nav_active', '');
                $this->template->render('index/cambiar_clave');
            }
        } else {
            $this->template->set_flash_message(array(
                'error' => 'Este enlace de recuperación no es valido.'
            ));
            redirect("index/login");
        }
    }

    public function habeas_data() {

        if (isset($_SESSION['estudiante_divisist'])) {
            $this->session->sess_destroy();
            redirect('index/login');
        }

        $codigo = $this->session->flashdata('codigo_hd');
        $nombres = $this->session->flashdata('nombres_hd');
        $documento = $this->session->flashdata('documento_hd');
        $this->session->keep_flashdata(array('codigo_hd', 'nombres_hd', 'documento_hd'));

        if (!$codigo || !$nombres || !$documento) {
            redirect();
        }

        $this->load->model('habeas_data_model');

        $esMayor = $this->habeas_data_model->verificarMayorEdad(substr($codigo, 0, 3), substr($codigo, 3));

        $enlacesVigentes = '';
        $anulacionPrevia = FALSE;
        if (!$esMayor) {
            $enlacesVigentes = $this->habeas_data_model->enlaceVigenteHabeasData(substr($codigo, 0, 3), substr($codigo, 3));
            $anulacionPrevia = $this->habeas_data_model->verificarAnuladoHabeasData(substr($codigo, 0, 3), substr($codigo, 3));
        }

        //ingresa un menor de edad y desea anular un registro
        if ($this->input->post('ANULAR') == 1 && !$esMayor) {
            if ($this->habeas_data_model->anularHabeasData($enlacesVigentes->ID)) {
                $msj = "Se ha anulado la información de su responsable legal suministrada anteriormente.";
                $this->template->set_flash_message(array($msj), 'info');
                redirect('index/habeas_data');
            }
        }

        //ingresa un menor de edad
        if ($this->input->post('TITULAR') == 1 && !$esMayor && $this->input->post('CONFIRMACION') == 1) {
            $this->load->library('form_validation');
            $rules = [
                [
                    'field' => 'CEDULA_ACUDIENTE',
                    'label' => 'Cédula Acudiente',
                    'rules' => 'required|numeric'
                ],
                [
                    'field' => 'NOMBRE_ACUDIENTE',
                    'label' => 'Nombre Acudiente',
                    'rules' => 'required|callback_alpha_es|max_length[100]'
                ],
                [
                    'field' => 'CORREO_ACUDIENTE',
                    'label' => 'Correo Acudiente',
                    'rules' => 'required|valid_email|strtolower|max_length[100]'
                ]
            ];
            $this->form_validation->set_rules($rules);
            if ($this->form_validation->run()) {

                $cedula_acudiente = $this->input->post('CEDULA_ACUDIENTE');
                $nombre_acudiente = $this->input->post('NOMBRE_ACUDIENTE');
                $email_titular = $this->input->post('CORREO_ACUDIENTE');

                //Generacion del codigo unico de seguridad
                $bytes = openssl_random_pseudo_bytes(32);
                $uniqid = bin2hex($bytes);

//                $num_mail = $this->habeas_data_model->numCambioClaveMail();

                $this->habeas_data_model->registrarMailHabeasData(
                        substr($codigo, 0, 3), substr($codigo, 3), $_SERVER['REMOTE_ADDR'], $uniqid, $cedula_acudiente, $nombre_acudiente, $email_titular, $num_mail);

                if ($this->_correo_habeas_data($nombres, $documento, $cedula_acudiente, $nombre_acudiente, $email_titular, $uniqid)) {
                    $msj = "Se ha enviado un correo a $email_titular con un enlace para completar el proceso de autorización de manejo de Datos Personales "
                            . "<b>(Puede que el mensaje llegue a la carpeta Spam, por favor verifique)</b>. "
                            . "Recuerde que podrá ingresar con normalidad al portal una vez se autorice el tratamiento de sus datos personales.";
                    $this->template->set_flash_message(array($msj), 'info');
                    redirect('index/habeas_data');
                }
            }
        }

        //ingresa un mayor de edad
        if ($this->input->post('REGISTRAR') == 1 && $esMayor && $this->input->post('CONFIRMACION') == 1) {
            if ($this->habeas_data_model->registrarHabeasData(substr($codigo, 0, 3), substr($codigo, 3), $_SERVER['REMOTE_ADDR'])) {
                $this->load->model('estudiante_model');
                $user = $this->estudiante_model->get_datos($codigo);
                $this->session->set_userdata('estudiante_divisist', $user);
                redirect('estudiante/estado_matricula');
            } else {
                $msj = "Lo sentimos, no se pudo completar el proceso de autorización de tratamiento de los datos personales, por favor intentelo de nuevo."
                        . " Si el problema persiste, envie un correo a <b>consulta.estudiante@ufps.edu.co</b>";
                $this->template->set_flash_message(array($msj), 'error');
                redirect('index/habeas_data');
            }
        }

        $this->template->set_template('login');
        $this->template->set('box_medium', TRUE);
        $this->load->helper('form');
        $this->template->set('mayorEdad', $esMayor);
        $this->template->set('enlacesVigentes', $enlacesVigentes);
        $this->template->set('anulacionPrevia', $anulacionPrevia);
        $this->template->render('index/habeas_data');
    }

    private function _correo_habeas_data($nombres, $documento, $cedula_acudiente, $nombre_acudiente, $email_titular, $uniqid) {
        $tittle = "Autorización manejo de información";
        $subject = "Autorización manejo de información - Portal web Divisist 2.0";
        $body = "<div style='border: 1px solid black; width: 95%; height: 190px; max-height:190px; text-align: justify; overflow: auto; background-color: #f6f6f6; padding: 10px;' > 
                        <p>
                            La <b>UNIVERSIDAD FRANCISCO DE PAULA SANTANDER</b>, con domicilio en la ciudad de Cúcuta, Colombia, actúa y es Responsable del Tratamiento de los datos personales.
                            <br/>
                            <br/>
                            - Dirección de Oficinas: Avenida Gran Colombia # 12E- 96 Colsag. 
                            <br/>
                            - Correo Electrónico: habeasdata@ufps.edu.co 
                            <br/>
                            - Teléfono: 5776655, Ext. 393
                            <br/>
                            <br/>
                            Sus datos personales serán incluidos en una base de datos y serán utilizados de manera directa o a través de terceros designados, entre otras, y de forma meramente enunciativa, para las siguientes finalidades directas e indirectas relacionadas con el objeto y propósitos de la Universidad:

                            <ul style='text-align: justify;'>
                                <li>
                                    Lograr de manera eficiente la comunicación y procedimientos relacionados con nuestros servicios, y demás actividades afines con las funciones propias de la Universidad como institución de educación superior, como por ejemplo alianzas, estudios, contenidos, así como las demás instituciones que tengan una relación directa o indirecta, y para facilitarle el acceso general a la información de estos y provisión de nuestros servicios.
                                </li>
                                <li>
                                    Informar sobre nuevos servicios y a su vez los cambios realizados a servicios antiguos que estén relacionados con los ofrecidos por la Universidad.
                                </li>
                                <li>
                                    Dar cumplimiento a obligaciones contraídas con nuestros estudiantes, profesores, contratistas, contratantes, clientes, proveedores, y empleados.
                                </li>
                                <li>
                                    Evaluar la calidad del servicio, y realizar estudios internos sobre hábitos de consumo de los servicios y productos ofrecidos por la Universidad.
                                </li>
                            </ul>

                            Se le informa a los Titulares de información que pueden consultar el Manual Interno de Políticas y Procedimientos de Datos Personales de la <b>UNIVERSIDAD FRANCISCO DE PAULA SANTANDER</b>, que contiene las políticas para el Tratamiento de la información recogida, así como los procedimientos de consulta y reclamación que le permitirán hacer efectivos sus derechos al acceso, consulta, rectificación, actualización y supresión de los datos, en el siguiente
                            <a href ='http://www.ufps.edu.co/ufpsnuevo/modulos/contenido/view_content.php?item=44'>enlace</a>.
                        </p>
                    </div>
                    <p>
                    Usted ha sido registrado como responsable legal de {$nombres} en el portal web de la división de sistemas
                    de la <b>UNIVERSIDAD FRANCISCO DE PAULA SANTANDER</b>.
                    Para autorizar el tratamiento de sus datos personales ingrese en el siguiente enlace. 
                    </p>
                    <p>
                    \"Yo {$nombre_acudiente} Identificado(a) con C.C No. {$cedula_acudiente} actuando en mi condición de
                    responsable legal de $nombres Identificado(a) con T.I No. {$documento}. Consiento y autorizo de manera previa, expresa e inequívoca que los datos personales sean tratados conforme a lo previsto en el presente documento\" </p>";
        $body .= "<p>Ingrese en el siguiente enlace para continuar con esta operación. </p>"
                . "Este enlace solo será valido 1 vez y caducará en 24 horas desde su generación.";
        $link = "<a href = '" . base_url('index/confirmacion_habeas_data') . "/{$uniqid}'>Restablecer clave</a>";

        $from = "habeasdata-noreply@ufps.edu.co";
//      'smtp_pass' => 'habeas20142'

        return $this->send_email($tittle, $subject, $body, $link, $email_titular, $from);
    }

    public function confirmacion_habeas_data($uniqid) {

        if (isset($_SESSION['estudiante_divisist'])) {
            $this->session->sess_destroy();
        }

        if (!ctype_xdigit($uniqid)) {
            redirect();
        }

        $this->load->model("habeas_data_model");
        $id = $this->habeas_data_model->verificarCodigo($uniqid);

        if (!$id) {
            $this->template->set_flash_message(array("El enlace al que esta intentando acceder no es valido o ya ha sido utilizado."), 'error');
        } elseif (!$this->habeas_data_model->aceptarHabeasData($id)) {
            $this->template->set_flash_message(array("Lo sentimos, no se pudo completar el proceso de autorización de "
                . "tratamiento de los datos personales, intente usar este enlace dentro de un momento. "
                . "Si el problema persiste, envie un correo a <b>consulta.estudiante@ufps.edu.co</b>"), 'error');
        } else {
            $this->template->set_flash_message(array("Se ha realizado exitosamente la autorización del manejo de datos personales."), 'success');
        }

        redirect("index/login");
    }

}
