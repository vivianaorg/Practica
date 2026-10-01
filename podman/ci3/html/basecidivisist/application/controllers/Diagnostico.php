<?php

class Diagnostico extends CMS_Controller {

    function __construct() {
        parent::__construct();

        if (!isset($this->usuario)) {
            redirect();
        }

        $this->load->model('Diagnostico_oracle_model');
    }

    public function index() {
        $this->test_oracle();
    }

    public function test_oracle() {
        $this->Diagnostico_oracle_model->test_oracle();
    }

    public function resumen_esquemas() {
        $this->Diagnostico_oracle_model->resumen_esquemas();
    }

    public function dump_esquema($owner = null) {
        $this->Diagnostico_oracle_model->dump_esquema($owner);
    }

    public function ver_grupo_cargado($cod_profesor = null) {
        $this->Diagnostico_oracle_model->ver_grupo_cargado($cod_profesor);
    }
}
