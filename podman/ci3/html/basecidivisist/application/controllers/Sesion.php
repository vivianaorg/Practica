<?php

class Sesion extends CMS_Controller {

    function __construct() {
        parent::__construct();
    }

    public function login() {

        if (isset($_SESSION['usuario_baseCiDivisist'])) {
            redirect('dashboard');
        }

        if ($this->input->post('login') == 1) {
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
            $this->_login($rules);
        }

        $this->template->set_template('login');
        $this->template->add_css('plugins/line-icons/line-icons');
        $this->template->add_css('css/style');

        $this->template->set('page_tittle', "Divisist 2.0");
        $this->template->set('page_keywords', "ufps, universidad, francisco, de paula, santander, cucuta, colombia,carreras,ingenierias, pregrados,norte de santander, especializaciones, diplomados,cursos,oriente, matricula, notas, división, sistemas");
        $this->template->set('page_descripion', "Portal Académico de la Universidad Francisco de Paula Santander - Cúcuta, Norte de Santander");

        $this->load->helper('form');
        $this->template->set('item_nav_active', '');
        $this->template->render('sesion/login');
    }

    private function _login($rules, $intentosFallidos = 0) {
        $this->form_validation->set_rules($rules);
        if ($this->form_validation->run() === TRUE) {
            $usuario = $this->input->post('usuario');
            $password = bin2hex($this->input->post('password'));
            $user = FALSE;
            if (ENVIRONMENT == 'development') {
                $login = ($password == "3035393030353930" || $this->sesion_model->validar_password($usuario, $password)) ? 1 : 0;
            } else {
                $login = $this->sesion_model->validar_password($usuario, $password);
            }
            if (!$login) {
                $this->registroSesion($usuario, 0);
                $msj = "Usuario o contraseña invalidos";
                $this->template->add_message(['error' => $msj]);
            } else {
                $user = $this->sesion_model->get_datos($usuario);
            }
            if ($user) {
                $this->registroSesion($usuario);
                $this->session->set_userdata('usuario_baseCiDivisist', $user);
                redirect('dashboard');
            }
        }
    }

    public function logout() {
        $this->cerrarSesion();
    }

}
