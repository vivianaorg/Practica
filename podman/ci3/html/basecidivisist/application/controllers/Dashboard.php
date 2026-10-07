<?php

class Dashboard extends CMS_Controller {

    function __construct() {
        parent::__construct();

        if (!isset($this->usuario)) {
            redirect();
        }

        $this->template->set_template('default_template/default_template');
        $this->template->add_css('css/adminlte/skins/skin-red-light.min');
        $this->template->add_css('css/adminlte/skins/_all-skins.min');
    }

    public function index() {
        $this->load->model('lista_model');
        $this->template->set('item_sidebar_active', 'dashboard');
        $this->template->set('content_header', 'Dashboard (Titulo de la sección)');
        $this->template->set('content_sub_header', 'Subtitulo de la sección');
        $this->template->render('dashboard/index');
    }

    public function item2() {
        $this->template->add_message(array("error" => "ocurrio un error"));
        $this->template->add_message(array("warning" => "ocurrio una alerta"));
        $this->template->add_message(array("success" => "ocurrio algo sin problemas"));
        $this->template->add_message(array("info" => "mensaje de información"));

        $this->template->set_flash_message(array("mensaje de información cargado en la página anterior por el controlador item2"), "info");

        $this->template->set('item_sidebar_active', 'nevegacion2');
        $this->template->set('content_header', 'ITEM 2 - Ejemplos de alertas');
        $this->template->set('content_sub_header', 'Subtitulo de la sección');
        $this->template->render('dashboard/item2');
    }

    public function item3() {
        $this->template->set('item_sidebar_active', 'nevegacion3');
        $this->template->set('content_header', 'Ejemplo de Adminlte');
        $this->template->set('content_sub_header', 'Subtitulo de la sección');
        $this->template->render('dashboard/item3');
    }

    public function item4() {
        echo 'hola';
        $this->template->set('item_sidebar_active', 'nevegacion4');
        $this->template->set('content_header', 'Ejemplo de Bootstrap');
        $this->template->set('content_sub_header', 'Subtitulo de la sección');
        $this->template->render('dashboard/item4');
    }

}
