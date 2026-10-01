<?php

class Moodle extends CMS_Controller {

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
        $this->cursos();
    }

    public function cursos() {
        $this->load->model('Moodle_model');
        $resultado = $this->Moodle_model->listar_cursos();

        $exito = isset($resultado['exito']) ? $resultado['exito'] : false;
        $mensaje_moodle = isset($resultado['mensaje']) ? $resultado['mensaje'] : null;
        $cursos = isset($resultado['datos']['cursos']) ? $resultado['datos']['cursos'] : array();

        $this->template->set('exito', $exito);
        $this->template->set('mensaje_moodle', $mensaje_moodle);
        $this->template->set('cursos', $cursos);

        $this->template->set('item_sidebar_active', 'actividades_moodle');
        $this->template->set('content_header', 'Cursos de Moodle');
        $this->template->set('content_sub_header', 'Selecciona un curso para ver sus actividades');
        $this->template->render('dashboard/cursos_moodle');
    }

    public function actividades($courseId = 2) {
        $this->load->model('Moodle_model');
        $resultado = $this->Moodle_model->listar_actividades($courseId);

        $exito = isset($resultado['exito']) ? $resultado['exito'] : false;
        $mensaje_moodle = isset($resultado['mensaje']) ? $resultado['mensaje'] : null;
        $curso_nombre = isset($resultado['datos']['curso_nombre']) ? $resultado['datos']['curso_nombre'] : null;
        $actividades = isset($resultado['datos']['actividades']) ? $resultado['datos']['actividades'] : array();

        $this->template->set('exito', $exito);
        $this->template->set('mensaje_moodle', $mensaje_moodle);
        $this->template->set('course_id', $courseId);
        $this->template->set('curso_nombre', $curso_nombre);
        $this->template->set('actividades', $actividades);

        $this->template->set('item_sidebar_active', 'actividades_moodle');
        $this->template->set('content_header', 'Actividades de Moodle');
        $this->template->set('content_sub_header', '');
        $this->template->render('dashboard/actividades_moodle');
    }

    // Alias para retrocompatibilidad
    public function cursos_moodle() {
        $this->cursos();
    }

    public function actividades_moodle($courseId = 2) {
        $this->actividades($courseId);
    }
}
